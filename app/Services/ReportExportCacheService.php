<?php

namespace App\Services;

use App\Models\ReportExport;
use Illuminate\Support\Facades\Cache;

class ReportExportCacheService
{
    /**
     * Chave onde guardamos o número da "versão" atual dos dados de relatórios.
     * Toda vez que revisão, veículo ou pessoa muda, essa versão é incrementada.
     */
    protected const VERSION_KEY = 'reports:data_version';

    /**
     * Por quantos dias um export "done" ainda pode ser reaproveitado,
     * mesmo que a versão dos dados não tenha mudado (evita reaproveitar
     * um export gerado há meses).
     */
    protected const REUSE_WINDOW_DIAS = 7;

    /**
     * Retorna a versão atual dos dados. Se nunca foi definida, começa em 1.
     */
    public function getVersaoAtual(): int
    {
        return (int) Cache::get(self::VERSION_KEY, 1);
    }

    /**
     * Invalida o cache de relatórios: incrementa a versão dos dados.
     * Chamado pelo Observer sempre que Revisions, Vehicle ou People mudam.
     * Não apaga nenhum export existente — apenas faz com que eles deixem
     * de ser "reaproveitáveis" na próxima requisição de store().
     */
    public function invalidarCache(): void
    {
        Cache::forever(self::VERSION_KEY, $this->getVersaoAtual() + 1);
    }

    /**
     * Gera um hash único para identificar exports com os mesmos filtros
     * (mesmo type + start + end). Usado para achar exports "iguais".
     */
    public function hashParams(string $type, array $params): string
    {
        $normalizado = [
            'type' => $type,
            'start' => $params['start'] ?? null,
            'end' => $params['end'] ?? null,
        ];

        return md5(json_encode($normalizado));
    }

    /**
     * Procura um export já concluído, com os mesmos parâmetros, gerado
     * na versão de dados atual (ou seja: nada mudou desde então) e dentro
     * da janela de reaproveitamento. Retorna null se não achar nenhum,
     * daí o controller precisa criar um export novo de verdade.
     *
     * 🔴 FIX — $userId é UUID (string), não int. O projeto usa
     * HasUuidPrimaryKey em todos os models (users.id é uuid no banco),
     * então o type hint `int` quebrava com TypeError assim que o
     * Auth::id() (string) era passado no ReportExportController::store().
     */
    public function buscarExportReaproveitavel(string $userId, string $type, array $params): ?ReportExport
    {
        $hash = $this->hashParams($type, $params);
        $versaoAtual = $this->getVersaoAtual();

        return ReportExport::where('user_id', $userId)
            ->where('type', $type)
            ->where('params_hash', $hash)
            ->where('data_version', $versaoAtual)
            ->where('status', 'done')
            ->where('created_at', '>=', now()->subDays(self::REUSE_WINDOW_DIAS))
            ->latest('created_at')
            ->first();
    }
}