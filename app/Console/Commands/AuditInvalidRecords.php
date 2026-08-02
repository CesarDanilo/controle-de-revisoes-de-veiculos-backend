<?php

namespace App\Console\Commands;

use App\Models\Brands;
use App\Models\Colors;
use App\Models\People;
use App\Models\Vehicle;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Encontra registros que foram gravados direto no banco (seeders, tinker,
 * import manual, migração de dados) pulando completamente a validação da
 * aplicação — e por isso têm campos obrigatórios vazios ou fora do padrão
 * que o frontend espera pra renderizar (ex: veículo sem nome/modelo).
 *
 * Uso:
 *   php artisan data:audit-invalid            -> só lista, não altera nada
 *   php artisan data:audit-invalid --fix      -> move os encontrados pra lixeira
 *
 * IMPORTANTE: rode isso ANTES de aplicar a migration que adiciona as CHECK
 * constraints (add_not_blank_constraints), senão a migration vai falhar
 * ao tentar validar linhas que já violam a regra.
 */
class AuditInvalidRecords extends Command
{
    protected $signature = 'data:audit-invalid {--fix : Move os registros encontrados para a lixeira em vez de só listar}';

    protected $description = 'Audita (e opcionalmente corrige) registros inválidos gravados diretamente no banco, sem passar pela validação da aplicação.';

    public function handle(): int
    {
        $fix = (bool) $this->option('fix');

        $this->check(
            'Veículos com modelo em branco',
            Vehicle::query()->whereRaw("btrim(model) = ''"),
            'vehicle',
            $fix
        );

        $this->check(
            'Veículos com placa em branco',
            Vehicle::query()->whereRaw("btrim(license_plate) = ''"),
            'vehicle',
            $fix
        );

        $this->check(
            'Pessoas com nome em branco',
            People::query()->whereRaw("btrim(name) = ''"),
            'people',
            $fix
        );

        $this->check(
            'Pessoas com documento em branco ou fora do padrão (CPF: 11 dígitos, CNPJ: 14)',
            People::query()->where(function (Builder $q) {
                $q->whereRaw("btrim(document) = ''")
                    ->orWhereRaw("(person_type = 'PF' AND length(regexp_replace(document, '[^0-9]', '', 'g')) <> 11)")
                    ->orWhereRaw("(person_type = 'PJ' AND length(regexp_replace(document, '[^0-9]', '', 'g')) <> 14)");
            }),
            'people',
            $fix
        );

        $this->check(
            'Marcas com nome em branco',
            Brands::query()->whereRaw("btrim(name) = ''"),
            'brands',
            $fix
        );

        $this->check(
            'Cores com nome em branco',
            Colors::query()->whereRaw("btrim(name) = ''"),
            'colors',
            $fix
        );

        if (! $fix) {
            $this->newLine();
            $this->comment('Nenhuma alteração foi feita. Rode novamente com --fix para mover os registros acima para a lixeira.');
        }

        return self::SUCCESS;
    }

    private function check(string $label, Builder $query, string $tabelaOrigem, bool $fix): void
    {
        $count = (clone $query)->count();

        if ($count === 0) {
            $this->info("✔ {$label}: nenhum registro encontrado.");
            return;
        }

        $this->warn("✘ {$label}: {$count} registro(s) encontrado(s).");

        if (! $fix) {
            (clone $query)->limit(10)->get(['id'])->each(
                fn ($row) => $this->line("   - id: {$row->id}")
            );
            if ($count > 10) {
                $this->line('   ... e mais ' . ($count - 10) . ' registro(s).');
            }
            return;
        }

        $moved = 0;

        (clone $query)->get()->each(function ($registro) use ($tabelaOrigem, &$moved) {
            DB::table('lixeira')->insert([
                'id' => (string) Str::uuid(),
                // 'user_id' só existe em tabelas com dono (vehicle, people,
                // brands); em colors (tabela global) fica null.
                'user_id' => $registro->user_id ?? null,
                'tabela_origem' => $tabelaOrigem,
                'registro_id' => $registro->id,
                'dados' => json_encode($registro->toArray()),
                // limpeza automática, sem usuário humano responsável pela ação
                'excluido_por' => null,
                'excluido_em' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $registro->delete();
            $moved++;
        });

        $this->info("   → {$moved} registro(s) movido(s) para a lixeira (podem ser revisados/restaurados por lá em até 7 dias).");
    }
}