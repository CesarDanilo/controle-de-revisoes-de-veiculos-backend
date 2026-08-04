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
                'full'             => ['reports.export', $this->buildFullReport($userId, $start, $end), 'revisionsRows'],
                'revisions'        => ['reports.export-revisions', $this->buildRevisionsReport($userId, $start, $end), 'periodRows'],
                'vehicles'         => ['reports.export-table', $this->buildVehiclesReport($userId, $start, $end), 'rows'],
                'people'           => ['reports.export-table', $this->buildPeopleReport($userId, $start, $end), 'rows'],
                'period_revisions' => ['reports.export-table', $this->buildPeriodRevisionsReport($userId, $start, $end), 'rows'],
                'upcoming'         => ['reports.export-table', $this->buildUpcomingReport($userId), 'rows'],
                'overview'         => ['reports.export-overview', $this->buildOverviewReport($userId, $start, $end), null],
                default            => throw new \InvalidArgumentException("Tipo de relatório inválido: {$export->type}"),
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
    private function renderAndStore(ReportExport $export, string $view, array $payload, ?string $chunkField): void
    {
        // "overview" nunca precisa fragmentar (KPIs + rankings são sempre pequenos)
        if ($chunkField === null) {
            $pdfOutput = Pdf::loadView($view, $payload)->setPaper('a4')->output();
            $relativePath = "reports/{$export->user_id}/{$export->id}.pdf";
            Storage::disk('local')->put($relativePath, $pdfOutput);

            $export->update([
                'status' => 'done',
                'file_path' => json_encode([$relativePath]),
            ]);
            return;
        }

        $rows = collect($payload[$chunkField] ?? []);
        $totalRows = $rows->count();

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
    // TYPE = period_revisions (só a tabela "Revisões no período selecionado")
    // ------------------------------------------------------------------
    private function buildPeriodRevisionsReport(string $userId, ?string $start, ?string $end): array
    {
        $validRevisionIds = $this->validRevisionIds($userId, $start, $end);

        $rows = $this->fetchRevisionsRows($validRevisionIds)->map(fn ($row) => [
            'date' => Carbon::parse($row->date)->format('d/m/Y'),
            'person_name' => $row->person_name,
            'vehicle' => $row->vehicle,
            'description' => $row->description,
        ]);

        return [
            'title' => 'Revisões no período selecionado (' . $this->periodLabel($start, $end) . ')',
            'columns' => [
                ['key' => 'date', 'label' => 'Data'],
                ['key' => 'person_name', 'label' => 'Pessoa'],
                ['key' => 'vehicle', 'label' => 'Veículo'],
                ['key' => 'description', 'label' => 'Descrição'],
            ],
            'rows' => $rows,
        ];
    }

    // ------------------------------------------------------------------
    // TYPE = upcoming (próximas revisões previstas)
    // ------------------------------------------------------------------
    // ⚠️ Mesma lógica de ReportController::upcomingRevisions, com colunas
    // reduzidas ao que o PDF realmente exibe. Se você mudar uma, lembre de
    // alinhar a outra (dava pra extrair pra um Service compartilhado depois).
    private function upcomingPredictionsQuery(string $userId)
    {
        $today = Carbon::now('America/Sao_Paulo')->toDateString();

        $pastLatestRevisionsCte = "(
            select revisions.*, row_number() over (
                partition by revisions.vehicle_id
                order by revisions.revision_date desc, revisions.id desc
            ) as rn
            from revisions
            where revisions.user_id = '{$userId}' and revisions.revision_date <= '{$today}'
        ) as past_latest";

        $avgIntervalsCte = "(
            select vehicle_id, round(avg(date_diff)) as avg_days, round(avg(km_diff)) as avg_km
            from (
                select
                    vehicle_id,
                    revision_date - lag(revision_date) over (partition by vehicle_id order by revision_date) as date_diff,
                    km - lag(km) over (partition by vehicle_id order by revision_date) as km_diff
                from revisions
                where user_id = '{$userId}'
            ) as diffs
            where date_diff is not null
            group by vehicle_id
        ) as avg_intervals";

        $informedCandidatesCte = "(
            select *, row_number() over (
                partition by vehicle_id order by next_revision_date asc, id asc
            ) as rn
            from revisions
            where user_id = '{$userId}' and revision_date <= '{$today}' and next_revision_date is not null
        ) as informed_candidates";

        $informedPartSql = "
            select people.name as person_name, vehicle.model as vehicle,
                informed_candidates.next_revision_date as predicted_date, false as is_estimated_date
            from {$informedCandidatesCte}
            join vehicle on vehicle.id = informed_candidates.vehicle_id
            join people on people.id = vehicle.people_id
            where informed_candidates.rn = 1
        ";

        $estimatedPartSql = "
            select people.name as person_name, vehicle.model as vehicle,
                (past_latest.revision_date + (avg_intervals.avg_days || ' days')::interval)::date as predicted_date,
                true as is_estimated_date
            from {$pastLatestRevisionsCte}
            join vehicle on vehicle.id = past_latest.vehicle_id
            join people on people.id = vehicle.people_id
            join {$avgIntervalsCte} on avg_intervals.vehicle_id = past_latest.vehicle_id
            where past_latest.rn = 1
            and avg_intervals.avg_days is not null
            and past_latest.vehicle_id not in (
                select vehicle_id from revisions
                where user_id = '{$userId}' and revision_date <= '{$today}' and next_revision_date is not null
            )
        ";

        $scheduledSql = "
            select people.name as person_name, vehicle.model as vehicle,
                revisions.revision_date as predicted_date, false as is_estimated_date
            from revisions
            join vehicle on vehicle.id = revisions.vehicle_id
            join people on people.id = vehicle.people_id
            where revisions.user_id = '{$userId}' and revisions.revision_date > '{$today}'
        ";

        $unionSql = "({$informedPartSql}) union all ({$estimatedPartSql}) union all ({$scheduledSql})";

        return DB::table(DB::raw("({$unionSql}) as upcoming_predictions"));
    }

    private function buildUpcomingReport(string $userId): array
    {
        $today = Carbon::now('America/Sao_Paulo')->toDateString();

        $rows = $this->upcomingPredictionsQuery($userId)
            ->where('predicted_date', '>=', $today)
            ->orderBy('predicted_date')
            ->get()
            ->map(fn ($row) => [
                'person_name' => $row->person_name,
                'vehicle' => $row->vehicle,
                'predicted_date' => Carbon::parse($row->predicted_date)->format('d/m/Y'),
                'origin' => $row->is_estimated_date ? 'Estimado' : 'Informado',
            ]);

        return [
            'title' => 'Próximas revisões',
            'columns' => [
                ['key' => 'person_name', 'label' => 'Pessoa'],
                ['key' => 'vehicle', 'label' => 'Veículo'],
                ['key' => 'predicted_date', 'label' => 'Previsão'],
                ['key' => 'origin', 'label' => 'Origem'],
            ],
            'rows' => $rows,
        ];
    }

    private function countUpcomingWithinDays(string $userId, int $days): int
    {
        $limit = Carbon::now('America/Sao_Paulo')->addDays($days)->toDateString();

        return $this->upcomingPredictionsQuery($userId)
            ->where('predicted_date', '<=', $limit)
            ->count();
    }

    // ------------------------------------------------------------------
    // TYPE = overview (KPIs + rankings + gênero — espelha addOverviewContent do useReportPdf.js)
    // ------------------------------------------------------------------
    private function buildOverviewReport(string $userId, ?string $start, ?string $end): array
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

        $totalRevisions = (int) $summary->total_revisions;
        $totalCost = (float) $summary->total_cost;
        $avgTicket = $totalRevisions ? $totalCost / $totalRevisions : 0;

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

        $vehiclesByGender = DB::table('vehicle')
            ->join('people', 'people.id', '=', 'vehicle.people_id')
            ->whereIn('vehicle.id', function ($q) use ($validRevisionIds) {
                $q->select('vehicle_id')->from('revisions')->whereIn('id', $validRevisionIds);
            })
            ->select('people.gender', DB::raw('count(distinct vehicle.id) as count'))
            ->groupBy('people.gender')
            ->get();

        $peopleByGender = DB::table('people')
            ->whereIn('people.id', function ($q) use ($validRevisionIds) {
                $q->select('people_id')
                    ->from('vehicle')
                    ->join('revisions', 'revisions.vehicle_id', '=', 'vehicle.id')
                    ->whereIn('revisions.id', $validRevisionIds);
            })
            ->select('people.gender', DB::raw('count(*) as count'))
            ->groupBy('people.gender')
            ->get();

        $genderLabel = fn (?string $code) => match ($code) {
            'M' => 'Homens',
            'F' => 'Mulheres',
            default => 'Outros',
        };

        $genderBreakdown = $vehiclesByGender
            ->groupBy(fn ($row) => $genderLabel($row->gender))
            ->map(fn ($rows, $label) => ['Veículos', $label, (string) $rows->sum('count')])
            ->values()
            ->concat(
                $peopleByGender
                    ->groupBy(fn ($row) => $genderLabel($row->gender))
                    ->map(fn ($rows, $label) => ['Pessoas', $label, (string) $rows->sum('count')])
                    ->values()
            )
            ->all();

        return [
            'periodLabel' => $this->periodLabel($start, $end),
            'kpis' => [
                ['label' => 'Revisões', 'value' => (string) $totalRevisions],
                ['label' => 'Veículos atendidos', 'value' => (string) (int) $summary->vehicles_count],
                ['label' => 'Clientes atendidos', 'value' => (string) (int) $summary->people_count],
                ['label' => 'Próximas revisões (atrasadas/próx. 7 dias)', 'value' => (string) $this->countUpcomingWithinDays($userId, 7)],
                ['label' => 'Custo total', 'value' => 'R$ ' . number_format($totalCost, 2, ',', '.')],
                ['label' => 'Ticket médio', 'value' => 'R$ ' . number_format($avgTicket, 2, ',', '.')],
            ],
            'brandsRanking' => $brandsRanking->map(fn ($b) => ['label' => $b->brand, 'value' => (string) $b->count])->all(),
            'peopleRanking' => $peopleRanking->map(fn ($p) => ['label' => $p->person_name, 'value' => (string) $p->count])->all(),
            'genderBreakdown' => $genderBreakdown,
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