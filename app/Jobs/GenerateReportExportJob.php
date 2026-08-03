<?php

namespace App\Jobs;

use App\Models\ReportExport;
use Barryvdh\DomPDF\Facade\Pdf;
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

    // Limite máximo por PDF em MB
    private float $maxMbSize = 4.0;

    // Quantidade de revisões por fatia de PDF
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

            $payload = $this->buildFullReport($userId, $start, $end);
            
            $revisions = collect($payload['revisionsRows']);
            $totalRows = $revisions->count();

            // 1. Tenta gerar 1 único PDF se a quantidade de registros for pequena
            if ($totalRows <= $this->rowsPerChunk) {
                $pdfOutput = Pdf::loadView('reports.export', $payload)->setPaper('a4')->output();
                $sizeInMb = strlen($pdfOutput) / (1024 * 1024);

                if ($sizeInMb <= $this->maxMbSize) {
                    $relativePath = "reports/{$userId}/{$export->id}.pdf";
                    Storage::disk('local')->put($relativePath, $pdfOutput);

                    $export->update([
                        'status' => 'done',
                        'file_path' => json_encode([$relativePath]),
                    ]);
                    return;
                }
            }

            // 2. Se for grande, particiona mantendo os dados consolidados
            $generatedFiles = $this->generatePartitionedPdfs($userId, $export->id, $payload);

            $export->update([
                'status' => 'done',
                'file_path' => json_encode($generatedFiles),
            ]);

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

    private function generatePartitionedPdfs(string $userId, string $exportId, array $payload): array
    {
        $allRevisions = collect($payload['revisionsRows']);
        $chunks = $allRevisions->chunk($this->rowsPerChunk);

        $relativeDirectory = "reports/{$userId}";
        Storage::disk('local')->makeDirectory($relativeDirectory);

        $savedFiles = [];
        $part = 1;
        $totalParts = count($chunks);

        foreach ($chunks as $chunk) {
            $partPayload = $payload;

            // Mantém os dados agregados e de resumo visíveis em todas as partes
            $partPayload['revisionsRows'] = $chunk;
            $partPayload['partInfo'] = "Parte {$part} de {$totalParts}";

            $pdfContent = Pdf::loadView('reports.export', $partPayload)->setPaper('a4')->output();
            $sizeInMb = strlen($pdfContent) / (1024 * 1024);

            if ($sizeInMb > $this->maxMbSize) {
                $subChunks = $chunk->chunk((int) ceil($chunk->count() / 2));
                foreach ($subChunks as $subChunk) {
                    $partPayload['revisionsRows'] = $subChunk;
                    $fileName = "{$relativeDirectory}/{$exportId}_parte_{$part}.pdf";
                    Storage::disk('local')->put($fileName, Pdf::loadView('reports.export', $partPayload)->setPaper('a4')->output());
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

    private function buildFullReport(string $userId, ?string $start, ?string $end): array
    {
        // Subquery base com IDs de revisões válidas no período
        $revisionIdsQuery = DB::table('revisions')->where('revisions.user_id', $userId);
        $this->applyPeriod($revisionIdsQuery, $start, $end, 'revisions.revision_date');
        $validRevisionIds = $revisionIdsQuery->pluck('id');

        // 1. Resumo no Período
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

        // 2. Ranking de Marcas
        $brandsRanking = DB::table('revisions')
            ->join('vehicle', 'vehicle.id', '=', 'revisions.vehicle_id')
            ->join('brands', 'brands.id', '=', 'vehicle.brand_id')
            ->whereIn('revisions.id', $validRevisionIds)
            ->select('brands.name as brand', DB::raw('count(*) as count'))
            ->groupBy('brands.name')
            ->orderByDesc('count')
            ->get();

        // 3. Ranking de Clientes
        $peopleRanking = DB::table('revisions')
            ->join('vehicle', 'vehicle.id', '=', 'revisions.vehicle_id')
            ->join('people', 'people.id', '=', 'vehicle.people_id')
            ->whereIn('revisions.id', $validRevisionIds)
            ->select('people.name as person_name', DB::raw('count(*) as count'))
            ->groupBy('people.name')
            ->orderByDesc('count')
            ->get();

        // 4. Lista de todas as Revisões no Período
        $revisionsRows = DB::table('revisions')
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

        // 5. Veículos atrelados a revisões do período (Sem limitação)
        $vehicleRows = DB::table('vehicle')
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

        // 6. Pessoas atreladas a revisões do período (Sem limitação)
        $peopleRows = DB::table('people')
            ->whereIn('people.id', function ($q) use ($validRevisionIds) {
                $q->select('people_id')
                    ->from('vehicle')
                    ->join('revisions', 'revisions.vehicle_id', '=', 'vehicle.id')
                    ->whereIn('revisions.id', $validRevisionIds);
            })
            ->select('people.name', 'people.email', 'people.phone')
            ->orderBy('people.name')
            ->get();

        return [
            'periodLabel' => $start && $end 
                ? \Carbon\Carbon::parse($start)->format('d/m/Y') . " a " . \Carbon\Carbon::parse($end)->format('d/m/Y')
                : 'Todos os períodos',
            'summary' => $summary,
            'brandsRanking' => $brandsRanking,
            'peopleRanking' => $peopleRanking,
            'revisionsRows' => $revisionsRows,
            'vehicleRows' => $vehicleRows,
            'peopleRows' => $peopleRows,
        ];
    }
}