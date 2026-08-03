<?php

namespace App\Jobs;

use App\Models\ReportExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GenerateReportExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 600;

    private float $maxMbSize = 4.0;
    private int $rowsPerChunk = 100;

    public function __construct(private readonly string $reportExportId)
    {
    }

    public function handle(): void
    {
        ini_set('memory_limit', '1024M');
        set_time_limit(600);

        $export = ReportExport::findOrFail($this->reportExportId);
        $export->update(['status' => 'processing']);

        try {
            $userId = $export->user_id;
            $start = $export->params['start'] ?? null;
            $end = $export->params['end'] ?? null;

            [$view, $payload, $chunkField] = match ($export->type) {
                'full'      => ['reports.export', $this->buildFullReport($userId, $start, $end), 'revisionsRows'],
                'revisions' => ['reports.export-revisions', $this->buildRevisionsReport($userId, $start, $end), 'periodRows'],
                'vehicles'  => ['reports.export-table', $this->buildVehiclesReport($userId, $start, $end), 'rows'],
                'people'    => ['reports.export-table', $this->buildPeopleReport($userId, $start, $end), 'rows'],
                default     => throw new \InvalidArgumentException("Tipo de relatório inválido: {$export->type}"),
            };

            $this->renderAndStore($export, $view, $payload, $chunkField);
        } catch (\Throwable $e) {
            Log::error('Falha ao gerar relatório em fila', [
                'export_id' => $export->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $export->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }
    }

    // ------------------------------------------------------------------
    // Render + storage (genérico, reaproveitado por todos os tipos)
    // ------------------------------------------------------------------
    private function renderAndStore(ReportExport $export, string $view, array $payload, string $chunkField): void
    {
        $rows = collect($payload[$chunkField] ?? []);
        $totalRows = $rows->count();

        if ($totalRows <= $this->rowsPerChunk) {
            $pdfOutput = Pdf::loadView($view, $payload)->setPaper('a4')->output();
            $sizeInMb = strlen($pdfOutput) / (1024 * 1024);

            if ($sizeInMb <= $this->maxMbSize) {
                $relativePath = "reports/{$export->user_id}/{$export->id}.pdf";
                Storage::disk('local')->put($relativePath, $pdfOutput);

                $export->update([
                    'status' => 'done',
                    'file_path' => json_encode([$relativePath]),
                ]);
                return;
            }
        }

        $generatedFiles = $this->generatePartitionedPdfs($export->user_id, $export->id, $view, $payload, $chunkField);

        $export->update([
            'status' => 'done',
            'file_path' => json_encode($generatedFiles),
        ]);
    }

    private function generatePartitionedPdfs(string $userId, string $exportId, string $view, array $payload, string $chunkField): array
    {
        $allRows = collect($payload[$chunkField] ?? []);
        $chunks = $allRows->chunk($this->rowsPerChunk);

        $relativeDirectory = "reports/{$userId}";
        Storage::disk('local')->makeDirectory($relativeDirectory);

        $savedFiles = [];
        $part = 1;
        $totalParts = count($chunks);

        foreach ($chunks as $chunk) {
            $partPayload = $payload;
            $partPayload[$chunkField] = $chunk;
            $partPayload['partInfo'] = "Parte {$part} de {$totalParts}";

            $pdfContent = Pdf::loadView($view, $partPayload)->setPaper('a4')->output();
            $sizeInMb = strlen($pdfContent) / (1024 * 1024);

            if ($sizeInMb > $this->maxMbSize) {
                $subChunks = $chunk->chunk((int) ceil($chunk->count() / 2));
                foreach ($subChunks as $subChunk) {
                    $partPayload[$chunkField] = $subChunk;
                    $fileName = "{$relativeDirectory}/{$exportId}_parte_{$part}.pdf";
                    Storage::disk('local')->put($fileName, Pdf::loadView($view, $partPayload)->setPaper('a4')->output());
                    $savedFiles[] = $fileName;
                    $part++;
                }
            } else {
                $fileName = "{$relativeDirectory}/{$exportId}_parte_{$part}.pdf";
                Storage::disk('local')->put($fileName, $pdfContent);
                $savedFiles[] = $fileName;
                $part++;
            }
        }

        return $savedFiles;
    }

    public function failed(\Throwable $e): void
    {
        ReportExport::where('id', $this->reportExportId)->update([
            'status' => 'failed',
            'error_message' => $e->getMessage(),
        ]);
    }

    private function applyPeriod($query, ?string $start, ?string $end, string $column = 'revisions.revision_date')
    {
        if ($start && $end) {
            $query->whereBetween($column, ["{$start} 00:00:00", "{$end} 23:59:59"]);
        } elseif ($start) {
            $query->where($column, '>=', "{$start} 00:00:00");
        } elseif ($end) {
            $query->where($column, '<=', "{$end} 23:59:59");
        }
        return $query;
    }

    private function validRevisionIds(string $userId, ?string $start, ?string $end)
    {
        $query = DB::table('revisions')->where('revisions.user_id', $userId);
        $this->applyPeriod($query, $start, $end);
        return $query->pluck('id');
    }

    private function periodLabel(?string $start, ?string $end): string
    {
        return $start && $end
            ? Carbon::parse($start)->format('d/m/Y') . ' a ' . Carbon::parse($end)->format('d/m/Y')
            : 'Todos os períodos';
    }

    // ------------------------------------------------------------------
    // TYPE = full
    // ------------------------------------------------------------------
    private function buildFullReport(string $userId, ?string $start, ?string $end): array
    {
        $validRevisionIds = $this->validRevisionIds($userId, $start, $end);

        $summary = DB::table('revisions')
            ->join('vehicle', 'vehicle.id', '=', 'revisions.vehicle_id')
            ->join('people', 'people.id', '=', 'vehicle.people_id')
            ->whereIn('revisions.id', $validRevisionIds)
            ->selectRaw('
                count(*) as total_revisions,
                count(distinct vehicle.id) as vehicles_count,
                count(distinct people.id) as people_count,
                coalesce(sum(revisions.cost), 0) as total_cost
            ')->first();

        $brandsRanking = DB::table('revisions')
            ->join('vehicle', 'vehicle.id', '=', 'revisions.vehicle_id')
            ->join('brands', 'brands.id', '=', 'vehicle.brand_id')
            ->whereIn('revisions.id', $validRevisionIds)
            ->select('brands.name as brand', DB::raw('count(*) as count'))
            ->groupBy('brands.name')
            ->orderByDesc('count')
            ->get();

        $peopleRanking = DB::table('revisions')
            ->join('vehicle', 'vehicle.id', '=', 'revisions.vehicle_id')
            ->join('people', 'people.id', '=', 'vehicle.people_id')
            ->whereIn('revisions.id', $validRevisionIds)
            ->select('people.name as person_name', DB::raw('count(*) as count'))
            ->groupBy('people.name')
            ->orderByDesc('count')
            ->get();

        return [
            'periodLabel' => $this->periodLabel($start, $end),
            'summary' => $summary,
            'brandsRanking' => $brandsRanking,
            'peopleRanking' => $peopleRanking,
            'revisionsRows' => $this->fetchRevisionsRows($validRevisionIds),
            'vehicleRows' => $this->fetchVehicleRows($validRevisionIds),
            'peopleRows' => $this->fetchPeopleRows($validRevisionIds),
        ];
    }

    // ------------------------------------------------------------------
    // TYPE = revisions (tempo médio entre revisões + revisões do período)
    // ------------------------------------------------------------------
    private function buildRevisionsReport(string $userId, ?string $start, ?string $end): array
    {
        $validRevisionIds = $this->validRevisionIds($userId, $start, $end);

        return [
            'title' => 'Revisões',
            'periodLabel' => $this->periodLabel($start, $end),
            'avgIntervalRows' => $this->buildAvgIntervalByPerson($userId),
            'periodRows' => $this->fetchRevisionsRows($validRevisionIds),
        ];
    }

    // ------------------------------------------------------------------
    // TYPE = vehicles
        // ------------------------------------------------------------------
    private function buildVehiclesReport(string $userId, ?string $start, ?string $end): array
    {
        $rows = DB::table('vehicle')
            ->join('people', 'people.id', '=', 'vehicle.people_id')
            ->join('brands', 'brands.id', '=', 'vehicle.brand_id')
            ->where('people.user_id', $userId)
            ->select(
                'people.name as person_name',
                'vehicle.license_plate as plate',
                'vehicle.model',
                'brands.name as brand'
            )
            ->orderBy('people.name')
            ->get();

        return [
            'title' => 'Todos os veículos por pessoa',
            'columns' => [
                ['key' => 'person_name', 'label' => 'Proprietário'],
                ['key' => 'plate', 'label' => 'Placa'],
                ['key' => 'model', 'label' => 'Modelo'],
                ['key' => 'brand', 'label' => 'Marca'],
            ],
            'rows' => $rows,
        ];
    }

    private function buildPeopleReport(string $userId, ?string $start, ?string $end): array
    {
        $rows = DB::table('people')
            ->where('people.user_id', $userId)
            ->select('people.name', 'people.email', 'people.phone')
            ->orderBy('people.name')
            ->get();

        return [
            'title' => 'Todas as pessoas',
            'columns' => [
                ['key' => 'name', 'label' => 'Nome'],
                ['key' => 'email', 'label' => 'E-mail'],
                ['key' => 'phone', 'label' => 'Telefone'],
            ],
            'rows' => $rows,
        ];
    }

    // ------------------------------------------------------------------
    // Queries reutilizáveis
    // ------------------------------------------------------------------
    private function fetchRevisionsRows($validRevisionIds)
    {
        return DB::table('revisions')
            ->join('vehicle', 'vehicle.id', '=', 'revisions.vehicle_id')
            ->join('people', 'people.id', '=', 'vehicle.people_id')
            ->whereIn('revisions.id', $validRevisionIds)
            ->select(
                'revisions.revision_date as date',
                'people.name as person_name',
                'vehicle.model as vehicle',
                'revisions.description',
                'revisions.cost'
            )
            ->orderByDesc('revisions.revision_date')
            ->get();
    }

    private function fetchVehicleRows($validRevisionIds)
    {
        return DB::table('vehicle')
            ->join('people', 'people.id', '=', 'vehicle.people_id')
            ->join('brands', 'brands.id', '=', 'vehicle.brand_id')
            ->whereIn('vehicle.id', function ($q) use ($validRevisionIds) {
                $q->select('vehicle_id')->from('revisions')->whereIn('id', $validRevisionIds);
            })
            ->select(
                'people.name as person_name',
                'vehicle.license_plate as plate',
                'vehicle.model',
                'brands.name as brand'
            )
            ->orderBy('people.name')
            ->get();
    }

    private function fetchPeopleRows($validRevisionIds)
    {
        return DB::table('people')
            ->whereIn('people.id', function ($q) use ($validRevisionIds) {
                $q->select('people_id')
                    ->from('vehicle')
                    ->join('revisions', 'revisions.vehicle_id', '=', 'vehicle.id')
                    ->whereIn('revisions.id', $validRevisionIds);
            })
            ->select('people.name', 'people.email', 'people.phone')
            ->orderBy('people.name')
            ->get();
    }

    // Tempo médio entre revisões por pessoa — todo o histórico do usuário,
    // sem filtro de período (mesmo comportamento do fetchAvgIntervalByPerson()
    // do frontend, chamado sem start/end no loadAll()).
    // ⚠️ Se o seu ReportController calcula isso de outra forma (SQL nativo, etc),
    // me manda esse método pra eu alinhar exatamente igual.
    private function buildAvgIntervalByPerson(string $userId): array
    {
        $rows = DB::table('revisions')
            ->join('vehicle', 'vehicle.id', '=', 'revisions.vehicle_id')
            ->join('people', 'people.id', '=', 'vehicle.people_id')
            ->where('revisions.user_id', $userId)
            ->select('people.id as person_id', 'people.name as person_name', 'revisions.revision_date')
            ->orderBy('people.name')
            ->orderBy('revisions.revision_date')
            ->get();

        return $rows->groupBy('person_id')
            ->map(function ($personRows) {
                $dates = $personRows->pluck('revision_date')->map(fn ($d) => Carbon::parse($d))->values();

                if ($dates->count() < 2) {
                    return [
                        'person_name' => $personRows->first()->person_name,
                        'avg_days' => null,
                    ];
                }

                $diffs = [];
                for ($i = 1; $i < $dates->count(); $i++) {
                    $diffs[] = $dates[$i]->diffInDays($dates[$i - 1]);
                }

                return [
                    'person_name' => $personRows->first()->person_name,
                    'avg_days' => round(array_sum($diffs) / count($diffs), 1),
                ];
            })
            ->sortBy('person_name')
            ->values()
            ->all();
    }
}