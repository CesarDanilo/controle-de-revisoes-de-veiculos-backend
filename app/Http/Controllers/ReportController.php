<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Illuminate\Support\Facades\Cache;

#[Group('Relatórios')]
class ReportController extends Controller
{
    // Tamanho de página padrão e máximo permitido
    private const DEFAULT_PER_PAGE = 15;
    private const MAX_PER_PAGE = 100;

    /**
     * Lê o parâmetro per_page da request, limitando ao máximo permitido.
     */
    private function perPage(Request $request): int
    {
        $perPage = (int) $request->query('per_page', self::DEFAULT_PER_PAGE);

        if ($perPage < 1) {
            $perPage = self::DEFAULT_PER_PAGE;
        }

        return min($perPage, self::MAX_PER_PAGE);
    }

    // ---------- VEÍCULOS ----------

    // i. Todos os veículos
    #[Endpoint('Listar veículos', 'Retorna todos os veículos cadastrados do usuário autenticado.')]
    public function allVehicles(Request $request)
    {
        $userId = $request->user()->id;

        return DB::table('vehicle')
            ->join('people', 'people.id', '=', 'vehicle.people_id')
            ->join('brands', 'brands.id', '=', 'vehicle.brand_id')
            ->join('colors', 'colors.id', '=', 'vehicle.color_id')
            ->where('vehicle.user_id', $userId)
            ->select(
                'vehicle.id',
                'vehicle.license_plate',
                'vehicle.model',
                'vehicle.year',
                'colors.name as color',
                'brands.name as brand',
                'people.name as person_name'
            )
            ->orderBy('vehicle.model')
            ->get();
    }

    // ii. Todos os veículos por pessoa, ordenado por nome (PAGINADO)
    #[Endpoint('Listar veículos por pessoa', 'Retorna todos os veículos cadastrados do usuário autenticado, agrupados por pessoa.')]
    public function vehiclesByPerson(Request $request)
    {
        $userId = $request->user()->id;

        return DB::table('vehicle')
            ->join('people', 'people.id', '=', 'vehicle.people_id')
            ->join('brands', 'brands.id', '=', 'vehicle.brand_id')
            ->where('vehicle.user_id', $userId)
            ->select(
                'people.id as person_id',
                'vehicle.id as vehicle_id',
                'people.name as person_name',
                'vehicle.license_plate as plate',
                'vehicle.model',
                'brands.name as brand'
            )
            ->orderBy('people.name')
            ->paginate($this->perPage($request));
    }

    // iii. Quem tem mais veículos: homens ou mulheres
    #[Endpoint('Listar veículos por gênero', 'Retorna a quantidade de veículos cadastrados do usuário autenticado, separados por gênero.')]
    public function vehiclesByGender(Request $request)
    {
        $userId = $request->user()->id;

        return DB::table('vehicle')
            ->join('people', 'people.id', '=', 'vehicle.people_id')
            ->where('vehicle.user_id', $userId)
            ->select('people.gender', DB::raw('count(*) as count'))
            ->groupBy('people.gender')
            ->get();
    }

    public function brandsRanking(Request $request)
    {
        $userId = $request->user()->id;

        return Cache::remember("reports:brands-ranking:{$userId}", 300, function () use ($userId) {
            return DB::table('vehicle')
                ->join('brands', 'brands.id', '=', 'vehicle.brand_id')
                ->where('vehicle.user_id', $userId)
                ->select('brands.name as brand', DB::raw('count(*) as count'))
                ->groupBy('brands.name')
                ->orderByDesc('count')
                ->get()
                ->toArray(); // 🔴 força array simples em vez de Collection
        });
    }

    // v. Totais de marcas do maior para o menor, separados por gênero
    #[Endpoint('Listar marcas por gênero', 'Retorna todas as marcas cadastradas do usuário autenticado, separadas por gênero.')]
    public function brandsByGender(Request $request)
    {
        $userId = $request->user()->id;

        return DB::table('vehicle')
            ->join('brands', 'brands.id', '=', 'vehicle.brand_id')
            ->join('people', 'people.id', '=', 'vehicle.people_id')
            ->where('vehicle.user_id', $userId)
            ->select(
                'brands.name as brand',
                DB::raw("count(*) filter (where people.gender = 'M') as male_count"),
                DB::raw("count(*) filter (where people.gender = 'F') as female_count"),
                DB::raw("count(*) filter (where people.gender not in ('M', 'F') or people.gender is null) as other_count")
            )
            ->groupBy('brands.name')
            ->orderByDesc(DB::raw('count(*)'))
            ->get();
    }

    // ---------- PESSOAS ----------

    // i. Todas as pessoas (PAGINADO)
    #[Endpoint('Listar pessoas', 'Retorna todas as pessoas cadastradas do usuário autenticado.')]
    public function allPeople(Request $request)
    {
        $userId = $request->user()->id;

        return DB::table('people')
            ->where('user_id', $userId)
            ->select('id', 'name', 'email', 'phone', 'document', 'gender', 'birth_date')
            ->orderBy('name')
            ->paginate($this->perPage($request));
    }

    // ii. Pessoas por gênero, com idade média
    #[Endpoint('Listar pessoas por gênero', 'Retorna a quantidade de pessoas cadastradas do usuário autenticado, separadas por gênero, com a idade média de cada grupo.')]
    public function peopleByGender(Request $request)
    {
        $userId = $request->user()->id;

        return DB::table('people')
            ->where('user_id', $userId)
            ->whereNotNull('gender')
            ->select(
                'gender',
                DB::raw('count(*) as count'),
                DB::raw("round(avg(date_part('year', age(birth_date)))) as avg_age")
            )
            ->groupBy('gender')
            ->get();
    }

    // ---------- REVISÕES ----------

    // i. Revisões dentro de um período (PAGINADO)
    #[Endpoint('Listar revisões por período', 'Retorna todas as revisões cadastradas do usuário autenticado, dentro de um período específico.')]
    public function revisionsByPeriod(Request $request)
    {
        $userId = $request->user()->id;
        $start = $request->query('start');
        $end = $request->query('end');

        $query = DB::table('revisions')
            ->join('vehicle', 'vehicle.id', '=', 'revisions.vehicle_id')
            ->join('people', 'people.id', '=', 'vehicle.people_id')
            ->where('revisions.user_id', $userId)
            ->select(
                // IDs necessários no front para abrir o modal já na revisão clicada.
                'revisions.id as revision_id',
                'people.id as person_id',
                'vehicle.id as vehicle_id',
                'revisions.revision_date as date',
                'revisions.description',
                'revisions.cost',
                'people.name as person_name',
                'vehicle.model as vehicle'
            );

        if ($start) {
            $query->where('revisions.revision_date', '>=', $start);
        }
        if ($end) {
            $query->where('revisions.revision_date', '<=', $end);
        }

        return $query
            ->orderByDesc('revisions.revision_date')
            ->paginate($this->perPage($request));
    }

    // ii. Marcas com maior número de revisões
    #[Endpoint('Listar ranking de marcas por revisões', 'Retorna todas as marcas cadastradas do usuário autenticado, ordenadas pelo número de revisões.')]
    public function brandsRevisionRanking(Request $request)
    {
        $userId = $request->user()->id;

        return DB::table('revisions')
            ->join('vehicle', 'vehicle.id', '=', 'revisions.vehicle_id')
            ->join('brands', 'brands.id', '=', 'vehicle.brand_id')
            ->where('revisions.user_id', $userId)
            ->select('brands.name as brand', DB::raw('count(*) as count'))
            ->groupBy('brands.name')
            ->orderByDesc('count')
            ->limit(5)
            ->get();
    }

    // iii. Pessoas com maior número de revisões
    #[Endpoint('Listar ranking de pessoas por revisões', 'Retorna todas as pessoas cadastradas do usuário autenticado, ordenadas pelo número de revisões.')]
    public function peopleRevisionRanking(Request $request)
    {
        $userId = $request->user()->id;

        return DB::table('revisions')
            ->join('vehicle', 'vehicle.id', '=', 'revisions.vehicle_id')
            ->join('people', 'people.id', '=', 'vehicle.people_id')
            ->where('revisions.user_id', $userId)
            ->select('people.name as person_name', DB::raw('count(*) as count'))
            ->groupBy('people.name')
            ->orderByDesc('count')
            ->limit(5)
            ->get();
    }

    // iv. Média de tempo entre revisões de uma mesma pessoa (PAGINADO)
    #[Endpoint('Listar média de intervalo entre revisões por pessoa', 'Retorna a média de dias entre revisões de cada pessoa cadastrada do usuário autenticado.')]
    public function avgIntervalByPerson(Request $request)
    {
        $userId = $request->user()->id;

        return DB::table(DB::raw("(
            select
                people.id as person_id,
                people.name as person_name,
                revisions.revision_date,
                lag(revisions.revision_date) over (
                    partition by people.id order by revisions.revision_date
                ) as previous_date
            from revisions
            join vehicle on vehicle.id = revisions.vehicle_id
            join people on people.id = vehicle.people_id
            where revisions.user_id = '{$userId}'
        ) as intervals"))
            ->select(
                'person_name',
                DB::raw('round(avg(revision_date - previous_date)) as avg_days')
            )
            ->whereNotNull('previous_date')
            ->groupBy('person_id', 'person_name') // agrupa por ID; nome só acompanha
            ->orderBy('person_name')
            ->paginate($this->perPage($request));
    }

    // v. Próximas revisões previstas (PAGINADO — SEPARADO POR TIPO)
    #[Endpoint('Listar próximas revisões', 'Retorna: (a) TODAS as revisões agendadas no futuro de cada veículo do usuário autenticado (uma entrada por revisão, sem limitar a uma por veículo), e (b) para veículos cuja última revisão já foi realizada, a previsão calculada (valor informado em next_revision_date ou estimativa pelo histórico). Aceita o parâmetro "type" (upcoming|overdue) para retornar cada grupo separadamente, com paginação independente.')]
    public function upcomingRevisions(Request $request)
    {
        $userId = $request->user()->id;

        $type = $request->query('type', 'upcoming');
        if (! in_array($type, ['upcoming', 'overdue'], true)) {
            $type = 'upcoming';
        }

        // 🔧 CORRIGIDO — CAUSA RAIZ DO BUG: a CTE antiga (latest_revisions)
        // pegava, via row_number()/rn=1, SÓ UMA revisão por veículo — a de
        // revision_date mais alta, seja ela passada ou futura. Isso fazia
        // uma revisão agendada mais distante (ex: 31/08) "esconder"
        // completamente outra revisão agendada mais próxima do mesmo
        // veículo (ex: 08/08), porque só a mais distante ganhava o rn=1.
        // Um veículo pode ter VÁRIAS revisões agendadas ao mesmo tempo, e
        // todas precisam aparecer — não só uma por veículo.
        //
        // A correção separa duas lógicas diferentes que estavam
        // erroneamente fundidas numa única CTE:
        //
        //  BLOCO A — pastLatestRevisions: contém só revisões JÁ REALIZADAS
        //  (revision_date <= hoje). Aqui SIM faz sentido "1 por veículo"
        //  (rn = 1), pois é usado só para CALCULAR uma previsão (informada
        //  via next_revision_date, ou estimada pela média histórica).
        //
        //  BLOCO B — scheduledRevisions: contém TODAS as revisões com
        //  revision_date no FUTURO, sem nenhum filtro de "só uma por
        //  veículo" — cada revisão agendada vira sua própria entrada na
        //  lista, então um veículo com 2, 3, N revisões agendadas mostra
        //  todas elas.
        //
        // Os dois blocos são unidos (UNION ALL) e paginados juntos.
        $pastLatestRevisionsCte = "(
            select
                revisions.*,
                row_number() over (
                    partition by revisions.vehicle_id
                    order by revisions.revision_date desc
                ) as rn
            from revisions
            where revisions.user_id = '{$userId}'
              and revisions.revision_date <= current_date
        ) as past_latest";

        $avgIntervalsCte = "(
            select
                vehicle_id,
                round(avg(date_diff)) as avg_days,
                round(avg(km_diff)) as avg_km
            from (
                select
                    vehicle_id,
                    revision_date - lag(revision_date) over (
                        partition by vehicle_id order by revision_date
                    ) as date_diff,
                    km - lag(km) over (
                        partition by vehicle_id order by revision_date
                    ) as km_diff
                from revisions
                where user_id = '{$userId}'
            ) as diffs
            where date_diff is not null
            group by vehicle_id
        ) as avg_intervals";

        // BLOCO A — previsão calculada (informada/estimada), 1 por veículo,
        // baseada apenas na última revisão JÁ REALIZADA.
        $informedOrEstimatedSql = "
            select
                people.id as person_id,
                vehicle.id as vehicle_id,
                past_latest.id as revision_id,
                people.name as person_name,
                vehicle.model as vehicle,
                vehicle.license_plate as vehicle_plate,
                past_latest.revision_date as last_revision,
                past_latest.next_revision_date as informed_date,
                past_latest.next_revision_km as informed_km,
                coalesce(
                    past_latest.next_revision_date,
                    (past_latest.revision_date + (avg_intervals.avg_days || ' days')::interval)::date
                ) as predicted_date,
                coalesce(past_latest.next_revision_km, past_latest.km + avg_intervals.avg_km) as predicted_km,
                (past_latest.next_revision_date is null and avg_intervals.avg_days is not null) as is_estimated_date,
                (past_latest.next_revision_km is null and avg_intervals.avg_km is not null) as is_estimated_km,
                false as is_scheduled,
                avg_intervals.avg_days as avg_interval_days
            from {$pastLatestRevisionsCte}
            join vehicle on vehicle.id = past_latest.vehicle_id
            join people on people.id = vehicle.people_id
            left join {$avgIntervalsCte} on avg_intervals.vehicle_id = past_latest.vehicle_id
            where past_latest.rn = 1
              and coalesce(
                    past_latest.next_revision_date,
                    (past_latest.revision_date + (avg_intervals.avg_days || ' days')::interval)::date
                  ) is not null
        ";

        // BLOCO B — TODAS as revisões agendadas no futuro, sem limitar a
        // uma por veículo. predicted_date é a própria data da revisão.
        $scheduledSql = "
            select
                people.id as person_id,
                vehicle.id as vehicle_id,
                revisions.id as revision_id,
                people.name as person_name,
                vehicle.model as vehicle,
                vehicle.license_plate as vehicle_plate,
                revisions.revision_date as last_revision,
                revisions.next_revision_date as informed_date,
                revisions.next_revision_km as informed_km,
                revisions.revision_date as predicted_date,
                revisions.km as predicted_km,
                false as is_estimated_date,
                false as is_estimated_km,
                true as is_scheduled,
                null::numeric as avg_interval_days
            from revisions
            join vehicle on vehicle.id = revisions.vehicle_id
            join people on people.id = vehicle.people_id
            where revisions.user_id = '{$userId}'
              and revisions.revision_date > current_date
        ";

        // 🟢 NOVO — como "predicted_date" agora é uma coluna real da tabela
        // derivada "upcoming_predictions" (resultado do UNION ALL), não um
        // simples alias dentro do mesmo nível de SELECT, o Postgres permite
        // referenciá-la normalmente em WHERE/ORDER BY — sem o problema que
        // tivemos antes com orderByRaw('abs(predicted_date - ...)').
        $unionSql = "({$informedOrEstimatedSql}) union all ({$scheduledSql})";

        $query = DB::table(DB::raw("({$unionSql}) as upcoming_predictions"));

        if ($type === 'overdue') {
            // atrasadas: predicted_date < hoje. Mais urgente (mais recente) primeiro.
            // Revisões agendadas (bloco B) nunca caem aqui — sua predicted_date
            // é sempre > current_date por definição (revision_date > current_date).
            $query
                ->where('predicted_date', '<', DB::raw('current_date'))
                ->orderByDesc('predicted_date');
        } else {
            // próximas: predicted_date >= hoje. Mais próxima primeiro.
            $query
                ->where('predicted_date', '>=', DB::raw('current_date'))
                ->orderBy('predicted_date');
        }

        return $query->paginate($this->perPage($request));
    }
}