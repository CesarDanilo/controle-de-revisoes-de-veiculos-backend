<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;

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

    /**
     * Lê o parâmetro limit da request, limitando ao máximo permitido.
     */
    private function rankingLimit(Request $request): int
    {
        $limit = (int) $request->query('limit', 50);

        if ($limit < 1) {
            $limit = 50;
        }

        return min($limit, 200);
    }

    /**
     * Aplica o filtro de período considerando datas cheias (00:00:00 até 23:59:59)
     */
    private function applyPeriod($query, Request $request, string $column = 'revisions.revision_date')
    {
        $start = $request->query('start');
        $end = $request->query('end');

        if ($start && $end) {
            $query->whereBetween($column, ["{$start} 00:00:00", "{$end} 23:59:59"]);
        } elseif ($start) {
            $query->where($column, '>=', "{$start} 00:00:00");
        } elseif ($end) {
            $query->where($column, '<=', "{$end} 23:59:59");
        }

        return $query;
    }

    // ---------- VEÍCULOS ----------

    // i. Veículos atendidos no período
    #[Endpoint('Listar veículos', 'Retorna os veículos que realizaram revisões no período informado.')]
    public function allVehicles(Request $request)
    {
        $userId = $request->user()->id;

        $query = DB::table('vehicle')
            ->join('people', 'people.id', '=', 'vehicle.people_id')
            ->join('brands', 'brands.id', '=', 'vehicle.brand_id')
            ->join('colors', 'colors.id', '=', 'vehicle.color_id')
            ->join('revisions', 'revisions.vehicle_id', '=', 'vehicle.id')
            ->where('vehicle.user_id', $userId);

        $this->applyPeriod($query, $request);

        return $query
            ->select(
                'vehicle.id',
                'vehicle.license_plate',
                'vehicle.model',
                'vehicle.year',
                'colors.name as color',
                'brands.name as brand',
                'people.name as person_name'
            )
            ->distinct()
            ->orderBy('vehicle.model')
            ->get();
    }

    // ii. Veículos por pessoa atendidos no período (PAGINADO)
    #[Endpoint('Listar veículos por pessoa', 'Retorna os veículos que realizaram revisões no período, agrupados por pessoa.')]
    public function vehiclesByPerson(Request $request)
    {
        $userId = $request->user()->id;

        $query = DB::table('vehicle')
            ->join('people', 'people.id', '=', 'vehicle.people_id')
            ->join('brands', 'brands.id', '=', 'vehicle.brand_id')
            ->join('revisions', 'revisions.vehicle_id', '=', 'vehicle.id')
            ->where('vehicle.user_id', $userId);

        $this->applyPeriod($query, $request);

        return $query
            ->select(
                'people.id as person_id',
                'vehicle.id as vehicle_id',
                'people.name as person_name',
                'vehicle.license_plate as plate',
                'vehicle.model',
                'brands.name as brand'
            )
            ->distinct()
            ->orderBy('people.name')
            ->paginate($this->perPage($request));
    }

    // iii. Veículos com revisões no período por gênero
    #[Endpoint('Listar veículos por gênero', 'Retorna a quantidade de veículos atendidos no período, separados por gênero.')]
    public function vehiclesByGender(Request $request)
    {
        $userId = $request->user()->id;

        $query = DB::table('vehicle')
            ->join('people', 'people.id', '=', 'vehicle.people_id')
            ->join('revisions', 'revisions.vehicle_id', '=', 'vehicle.id')
            ->where('vehicle.user_id', $userId);

        $this->applyPeriod($query, $request);

        return $query
            ->select('people.gender', DB::raw('count(distinct vehicle.id) as count'))
            ->groupBy('people.gender')
            ->get();
    }

    // iv. Ranking de veículos cadastrados/atendidos por marca
    #[Endpoint('Ranking de marcas de veículos', 'Retorna a quantidade de veículos por marca com revisões no período.')]
    public function brandsRanking(Request $request)
    {
        $userId = $request->user()->id;

        $query = DB::table('vehicle')
            ->join('brands', 'brands.id', '=', 'vehicle.brand_id')
            ->join('revisions', 'revisions.vehicle_id', '=', 'vehicle.id')
            ->where('vehicle.user_id', $userId);

        $this->applyPeriod($query, $request);

        return $query
            ->select('brands.name as brand', DB::raw('count(distinct vehicle.id) as count'))
            ->groupBy('brands.name')
            ->orderByDesc('count')
            ->get();
    }

    // v. Marcas atendidas no período separadas por gênero
    #[Endpoint('Listar marcas por gênero', 'Retorna todas as marcas de veículos atendidos no período, separadas por gênero.')]
    public function brandsByGender(Request $request)
    {
        $userId = $request->user()->id;

        $query = DB::table('vehicle')
            ->join('brands', 'brands.id', '=', 'vehicle.brand_id')
            ->join('people', 'people.id', '=', 'vehicle.people_id')
            ->join('revisions', 'revisions.vehicle_id', '=', 'vehicle.id')
            ->where('vehicle.user_id', $userId);

        $this->applyPeriod($query, $request);

        return $query
            ->select(
                'brands.name as brand',
                DB::raw("count(distinct vehicle.id) filter (where people.gender = 'M') as male_count"),
                DB::raw("count(distinct vehicle.id) filter (where people.gender = 'F') as female_count"),
                DB::raw("count(distinct vehicle.id) filter (where people.gender not in ('M', 'F') or people.gender is null) as other_count")
            )
            ->groupBy('brands.name')
            ->orderByDesc(DB::raw('count(distinct vehicle.id)'))
            ->get();
    }

    // ---------- PESSOAS ----------

    // i. Pessoas que realizaram revisões no período (PAGINADO)
    #[Endpoint('Listar pessoas', 'Retorna todas as pessoas que tiveram revisões no período informado.')]
    public function allPeople(Request $request)
    {
        $userId = $request->user()->id;

        $query = DB::table('people')
            ->join('vehicle', 'vehicle.people_id', '=', 'people.id')
            ->join('revisions', 'revisions.vehicle_id', '=', 'vehicle.id')
            ->where('people.user_id', $userId);

        $this->applyPeriod($query, $request);

        return $query
            ->select('people.id', 'people.name', 'people.email', 'people.phone', 'people.document', 'people.gender', 'people.birth_date')
            ->distinct()
            ->orderBy('people.name')
            ->paginate($this->perPage($request));
    }

    // ii. Pessoas atendidas no período por gênero com idade média
    #[Endpoint('Listar pessoas por gênero', 'Retorna a quantidade e idade média das pessoas que realizaram revisões no período, separadas por gênero.')]
    public function peopleByGender(Request $request)
    {
        $userId = $request->user()->id;

        $query = DB::table('people')
            ->join('vehicle', 'vehicle.people_id', '=', 'people.id')
            ->join('revisions', 'revisions.vehicle_id', '=', 'vehicle.id')
            ->where('people.user_id', $userId)
            ->whereNotNull('people.gender');

        $this->applyPeriod($query, $request);

        return $query
            ->select(
                'people.gender',
                DB::raw('count(distinct people.id) as count'),
                DB::raw("round(avg(date_part('year', age(people.birth_date)))) as avg_age")
            )
            ->groupBy('people.gender')
            ->get();
    }

    // ---------- REVISÕES ----------

    // i. Revisões dentro de um período (PAGINADO)
    #[Endpoint('Listar revisões por período', 'Retorna todas as revisões cadastradas no período informado.')]
    public function revisionsByPeriod(Request $request)
    {
        $userId = $request->user()->id;

        $query = DB::table('revisions')
            ->join('vehicle', 'vehicle.id', '=', 'revisions.vehicle_id')
            ->join('people', 'people.id', '=', 'vehicle.people_id')
            ->where('revisions.user_id', $userId);

        $this->applyPeriod($query, $request);

        return $query
            ->select(
                'revisions.id as revision_id',
                'people.id as person_id',
                'vehicle.id as vehicle_id',
                'revisions.revision_date as date',
                'revisions.description',
                'revisions.cost',
                'people.name as person_name',
                'vehicle.model as vehicle'
            )
            ->orderByDesc('revisions.revision_date')
            ->paginate($this->perPage($request));
    }

    // i-b. Resumo agregado das revisões no período
    #[Endpoint('Resumo de revisões no período', 'Retorna estatísticas consolidadas para o período informado.')]
    public function revisionsPeriodSummary(Request $request)
    {
        $userId = $request->user()->id;

        $query = DB::table('revisions')
            ->join('vehicle', 'vehicle.id', '=', 'revisions.vehicle_id')
            ->join('people', 'people.id', '=', 'vehicle.people_id')
            ->where('revisions.user_id', $userId);

        $this->applyPeriod($query, $request);

        $result = $query->selectRaw('
            count(*) as total_revisions,
            count(distinct vehicle.id) as vehicles_count,
            count(distinct people.id) as people_count,
            coalesce(sum(revisions.cost), 0) as total_cost
        ')->first();

        return response()->json([
            'total_revisions' => (int) $result->total_revisions,
            'vehicles_count' => (int) $result->vehicles_count,
            'people_count' => (int) $result->people_count,
            'total_cost' => (float) $result->total_cost,
        ]);
    }

    // ii. Marcas com maior número de revisões no período
    #[Endpoint('Listar ranking de marcas por revisões', 'Retorna marcas ordenadas por revisões no período.')]
    public function brandsRevisionRanking(Request $request)
    {
        $userId = $request->user()->id;

        $query = DB::table('revisions')
            ->join('vehicle', 'vehicle.id', '=', 'revisions.vehicle_id')
            ->join('brands', 'brands.id', '=', 'vehicle.brand_id')
            ->where('revisions.user_id', $userId);

        $this->applyPeriod($query, $request);

        return $query
            ->select('brands.name as brand', DB::raw('count(*) as count'))
            ->groupBy('brands.name')
            ->orderByDesc('count')
            ->limit($this->rankingLimit($request))
            ->get();
    }

    // iii. Pessoas com maior número de revisões no período
    #[Endpoint('Listar ranking de pessoas por revisões', 'Retorna pessoas ordenadas por revisões no período.')]
    public function peopleRevisionRanking(Request $request)
    {
        $userId = $request->user()->id;

        $query = DB::table('revisions')
            ->join('vehicle', 'vehicle.id', '=', 'revisions.vehicle_id')
            ->join('people', 'people.id', '=', 'vehicle.people_id')
            ->where('revisions.user_id', $userId);

        $this->applyPeriod($query, $request);

        return $query
            ->select('people.name as person_name', DB::raw('count(*) as count'))
            ->groupBy('people.name')
            ->orderByDesc('count')
            ->limit($this->rankingLimit($request))
            ->get();
    }

    // iv. Média de tempo entre revisões no período (PAGINADO)
    #[Endpoint('Listar média de intervalo entre revisões por pessoa', 'Retorna a média de dias entre revisões de cada pessoa considerando as revisões do período.')]
    public function avgIntervalByPerson(Request $request)
    {
        $userId = $request->user()->id;
        $start = $request->query('start');
        $end = $request->query('end');

        $wherePeriod = "";
        if ($start && $end) {
            $wherePeriod = " and revisions.revision_date between '{$start} 00:00:00' and '{$end} 23:59:59'";
        } elseif ($start) {
            $wherePeriod = " and revisions.revision_date >= '{$start} 00:00:00'";
        } elseif ($end) {
            $wherePeriod = " and revisions.revision_date <= '{$end} 23:59:59'";
        }

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
            where revisions.user_id = '{$userId}'{$wherePeriod}
        ) as intervals"))
            ->select(
                'person_name',
                DB::raw('round(avg(revision_date - previous_date)) as avg_days')
            )
            ->whereNotNull('previous_date')
            ->groupBy('person_id', 'person_name')
            ->orderBy('person_name')
            ->paginate($this->perPage($request));
    }

    // v. Próximas revisões previstas
    #[Endpoint('Listar próximas revisões', 'Retorna as previsões de próximas revisões.')]
    public function upcomingRevisions(Request $request)
    {
        $userId = $request->user()->id;

        $type = $request->query('type', 'upcoming');
        if (! in_array($type, ['upcoming', 'overdue'], true)) {
            $type = 'upcoming';
        }

        $today = now('America/Sao_Paulo')->toDateString();

        $pastLatestRevisionsCte = "(
            select
                revisions.*,
                row_number() over (
                    partition by revisions.vehicle_id
                    order by revisions.revision_date desc, revisions.id desc
                ) as rn
            from revisions
            where revisions.user_id = '{$userId}'
            and revisions.revision_date <= '{$today}'
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

        $informedCandidatesCte = "(
            select
                *,
                row_number() over (
                    partition by vehicle_id
                    order by next_revision_date asc, id asc
                ) as rn
            from revisions
            where user_id = '{$userId}'
            and revision_date <= '{$today}'
            and next_revision_date is not null
        ) as informed_candidates";

        $informedPartSql = "
            select
                people.id as person_id,
                vehicle.id as vehicle_id,
                informed_candidates.id as revision_id,
                people.name as person_name,
                vehicle.model as vehicle,
                vehicle.license_plate as vehicle_plate,
                informed_candidates.revision_date as last_revision,
                informed_candidates.next_revision_date as informed_date,
                informed_candidates.next_revision_km as informed_km,
                informed_candidates.next_revision_date as predicted_date,
                informed_candidates.next_revision_km as predicted_km,
                false as is_estimated_date,
                false as is_estimated_km,
                false as is_scheduled,
                null::numeric as avg_interval_days
            from {$informedCandidatesCte}
            join vehicle on vehicle.id = informed_candidates.vehicle_id
            join people on people.id = vehicle.people_id
            where informed_candidates.rn = 1
        ";

        $estimatedPartSql = "
            select
                people.id as person_id,
                vehicle.id as vehicle_id,
                past_latest.id as revision_id,
                people.name as person_name,
                vehicle.model as vehicle,
                vehicle.license_plate as vehicle_plate,
                past_latest.revision_date as last_revision,
                null::date as informed_date,
                null::numeric as informed_km,
                (past_latest.revision_date + (avg_intervals.avg_days || ' days')::interval)::date as predicted_date,
                past_latest.km + avg_intervals.avg_km as predicted_km,
                true as is_estimated_date,
                true as is_estimated_km,
                false as is_scheduled,
                avg_intervals.avg_days as avg_interval_days
            from {$pastLatestRevisionsCte}
            join vehicle on vehicle.id = past_latest.vehicle_id
            join people on people.id = vehicle.people_id
            join {$avgIntervalsCte} on avg_intervals.vehicle_id = past_latest.vehicle_id
            where past_latest.rn = 1
            and avg_intervals.avg_days is not null
            and past_latest.vehicle_id not in (
                select vehicle_id from revisions
                where user_id = '{$userId}'
                    and revision_date <= '{$today}'
                    and next_revision_date is not null
            )
        ";

        $informedOrEstimatedSql = "({$informedPartSql}) union all ({$estimatedPartSql})";

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
            and revisions.revision_date > '{$today}'
        ";

        $unionSql = "({$informedOrEstimatedSql}) union all ({$scheduledSql})";

        $query = DB::table(DB::raw("({$unionSql}) as upcoming_predictions"));

        if ($type === 'overdue') {
            $query
                ->where('predicted_date', '<', $today)
                ->orderByDesc('predicted_date');
        } else {
            $query
                ->where('predicted_date', '>=', $today)
                ->orderBy('predicted_date');
        }

        return $query->paginate($this->perPage($request));
    }
}