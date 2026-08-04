<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateReportExportJob;
use App\Models\ReportExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;

#[Group('Exportação de relatórios')]
class ReportExportController extends Controller
{
    /**
     * Pede a geração de um relatório. Cria o registro "pending" e
     * despacha o Job na fila — retorna imediatamente.
     */
    #[Endpoint('Solicitar exportação de relatório', 'Enfileira a geração de um relatório em PDF e retorna o id para acompanhamento.')]
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(['full', 'revisions', 'vehicles', 'people', 'overview', 'upcoming', 'period_revisions'])],
            'start' => ['nullable', 'date'],
            'end' => ['nullable', 'date'],
        ]);

        $export = ReportExport::create([
            'user_id' => Auth::id(),
            'type' => $validated['type'],
            'status' => 'pending',
            'params' => [
                'start' => $validated['start'] ?? null,
                'end' => $validated['end'] ?? null,
            ],
        ]);

        GenerateReportExportJob::dispatch($export->id);

        return response()->json([
            'id' => $export->id,
            'status' => $export->status,
        ], 201);
    }

    /**
     * Consulta o andamento de uma exportação solicitada.
     */
    #[Endpoint('Consultar status da exportação', 'Retorna o status atual (pending, processing, done, failed) e a URL de download quando pronto.')]
    public function show(string $id)
    {
        $export = ReportExport::where('user_id', Auth::id())->findOrFail($id);

        $files = json_decode($export->file_path ?? '', true);
        $totalParts = is_array($files) ? count($files) : 1;

        return response()->json([
            'id' => $export->id,
            'status' => $export->status,
            'error_message' => $export->error_message,
            'total_parts' => $totalParts,
            'download_url' => $export->isDone()
                ? route('reports.exports.download', $export->id)
                : null,
        ]);
    }

    /**
     * Baixa o PDF já gerado (suporta parâmetro ?part=N para relatórios particionados).
     */
    #[Endpoint('Baixar relatório exportado', 'Retorna o arquivo PDF gerado pela fila.')]
    public function download(Request $request, string $id)
    {
        $export = ReportExport::where('user_id', $request->user()->id)
            ->findOrFail($id);

        if ($export->status !== 'done') {
            return response()->json(['error' => 'O relatório ainda não está pronto para download.'], 400);
        }

        $filePath = $export->file_path;
        $files = json_decode($filePath, true);

        if (is_array($files)) {
            $part = (int) $request->query('part', 1);
            $index = $part - 1;

            if (!isset($files[$index])) {
                return response()->json(['error' => 'Parte do arquivo não encontrada.'], 404);
            }

            $targetFile = $files[$index];
        } else {
            $targetFile = $filePath;
        }

        if (!Storage::disk('local')->exists($targetFile)) {
            return response()->json(['error' => 'Arquivo não encontrado no storage.'], 404);
        }

        return Storage::disk('local')->download($targetFile);
    }
}