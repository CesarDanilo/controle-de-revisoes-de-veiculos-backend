<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateReportExportJob;
use App\Models\ReportExport;
use App\Services\ReportExportCacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;

#[Group('Exportação de relatórios')]
class ReportExportController extends Controller
{
    protected ReportExportCacheService $cacheService;

    // 🟢 NOVO — limite de segurança contra abuso: os primeiros
    // FREE_EXPORTS_POR_DIA pedidos do dia passam livres (sem espera).
    // A partir do pedido seguinte, passa a exigir COOLDOWN_SEGUNDOS
    // entre um pedido e outro.
    protected const FREE_EXPORTS_POR_DIA = 10;
    protected const COOLDOWN_SEGUNDOS = 30;

    public function __construct(ReportExportCacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * Pede a geração de um relatório. Antes de enfileirar um Job novo,
     * verifica se já existe um export "done" com os mesmos filtros,
     * gerado depois da última mudança de dados — se existir, reaproveita
     * e responde na hora, sem gastar fila/tempo de PDF.
     */
    #[Endpoint('Solicitar exportação de relatório', 'Reaproveita um export em cache se nada mudou, ou enfileira a geração de um novo PDF e retorna o id para acompanhamento.')]
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(['full', 'revisions', 'vehicles', 'people', 'overview', 'upcoming', 'period_revisions'])],
            'start' => ['nullable', 'date'],
            'end' => ['nullable', 'date'],
        ]);

        $userId = Auth::id();
        $diaAtual = now()->format('Y-m-d');

        $contadorKey = "export_daily_count:{$userId}:{$diaAtual}";
        $totalHoje = (int) Cache::get($contadorKey, 0);

        // 🔴 Só aplica o cooldown de 30s a partir do 11º pedido do dia
        // (ou seja, quando já usou as FREE_EXPORTS_POR_DIA cotas livres).
        if ($totalHoje >= self::FREE_EXPORTS_POR_DIA) {
            $cooldownKey = "export_cooldown:{$userId}";
            $expiraEm = Cache::get($cooldownKey);

            if ($expiraEm) {
                $restante = $expiraEm - now()->timestamp;

                if ($restante > 0) {
                    return response()->json([
                        'error' => "Você já usou suas " . self::FREE_EXPORTS_POR_DIA . " exportações livres de hoje. Aguarde {$restante} segundos antes de solicitar outra.",
                        'retry_after' => $restante,
                    ], 429);
                }
            }

            // Passou pelo cooldown: renova a espera pro próximo pedido.
            Cache::put($cooldownKey, now()->addSeconds(self::COOLDOWN_SEGUNDOS)->timestamp, self::COOLDOWN_SEGUNDOS);
        }

        // Incrementa o contador do dia SEMPRE (mesmo dentro da cota livre),
        // é isso que faz o 11º pedido cair no bloco do cooldown acima.
        Cache::put($contadorKey, $totalHoje + 1, now()->endOfDay());

        $params = [
            'start' => $validated['start'] ?? null,
            'end' => $validated['end'] ?? null,
        ];

        // 🟢 Tenta reaproveitar um export já pronto e ainda válido.
        $exportExistente = $this->cacheService->buscarExportReaproveitavel(
            $userId,
            $validated['type'],
            $params
        );

        if ($exportExistente) {
            return response()->json([
                'id' => $exportExistente->id,
                'status' => $exportExistente->status,
                'cached' => true,
            ], 200);
        }

        // Nenhum export reaproveitável: cria um novo e enfileira o Job normalmente.
        $export = ReportExport::create([
            'user_id' => $userId,
            'type' => $validated['type'],
            'status' => 'pending',
            'params' => $params,
            'params_hash' => $this->cacheService->hashParams($validated['type'], $params),
            'data_version' => $this->cacheService->getVersaoAtual(),
        ]);

        GenerateReportExportJob::dispatch($export->id);

        return response()->json([
            'id' => $export->id,
            'status' => $export->status,
            'cached' => false,
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