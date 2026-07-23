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

    // ---------- VEÍCULOS ----------

    // i. Todos os veículos
    #[Endpoint('Listar veículos', 'Retorna todos os veículos cadastrados do usuário autenticado.')]
    public function allVehicles(Request $request)
    {
        $userId = $request->user()->id;

        return DB::table('vehicle')
            ->join('people', 'people.id', '=', 'vehicle.people_id')
            ->join('brands', 'brands.id', '=', 'vehicle.brand_id')
            ->where('vehicle.user_id', $userId)
            ->select(
                'vehicle.id',
                'vehicle.license_plate',
                'vehicle.model',
                'vehicle.year',
                'vehicle.color',
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

    // iv. Todas as marcas ordenadas pelo número de veículos
    #[Endpoint('Listar ranking de marcas', 'Retorna todas as marcas cadastradas do usuário autenticado, ordenadas pelo número de veículos.')]
    public function brandsRanking(Request $request)
    {
        $userId = $request->user()->id;

        return DB::table('vehicle')
            ->join('brands', 'brands.id', '=', 'vehicle.brand_id')
            ->where('vehicle.user_id', $userId)
            ->select('brands.name as brand', DB::raw('count(*) as count'))
            ->groupBy('brands.name')
            ->orderByDesc('count')
            ->get();
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
            ->select('id', 'name', 'email', 'phone', 'gender', 'birth_date')
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

    // v. Próximas revisões previstas (PAGINADO)
    #[Endpoint('Listar próximas revisões', 'Retorna a previsão da próxima revisão de cada veículo do usuário autenticado, usando o valor informado ou, na ausência dele, uma estimativa baseada no histórico do veículo.')]
    public function upcomingRevisions(Request $request)
    {
        $userId = $request->user()->id;

        $latestRevisions = DB::raw("(
            select
                revisions.*,
                row_number() over (
                    partition by revisions.vehicle_id
                    order by revisions.revision_date desc
                ) as rn
            from revisions
            where revisions.user_id = '{$userId}'
        ) as latest_revisions");

        $avgIntervals = DB::raw("(
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
        ) as avg_intervals");

        return DB::table($latestRevisions)
            ->join('vehicle', 'vehicle.id', '=', 'latest_revisions.vehicle_id')
            ->join('people', 'people.id', '=', 'vehicle.people_id')
            ->leftJoin($avgIntervals, 'avg_intervals.vehicle_id', '=', 'latest_revisions.vehicle_id')
            ->select(
                'people.name as person_name',
                'vehicle.model as vehicle',
                'latest_revisions.revision_date as last_revision',
                'latest_revisions.next_revision_date as informed_date',
                'latest_revisions.next_revision_km as informed_km',
                DB::raw("
                    coalesce(
                        latest_revisions.next_revision_date,
                        (latest_revisions.revision_date + (avg_intervals.avg_days || ' days')::interval)::date
                    ) as predicted_date
                "),
                DB::raw('coalesce(latest_revisions.next_revision_km, latest_revisions.km + avg_intervals.avg_km) as predicted_km'),
                DB::raw('(latest_revisions.next_revision_date is null and avg_intervals.avg_days is not null) as is_estimated_date'),
                DB::raw('(latest_revisions.next_revision_km is null and avg_intervals.avg_km is not null) as is_estimated_km')
            )
            ->where('latest_revisions.rn', 1)
            ->whereRaw("
                coalesce(
                    latest_revisions.next_revision_date,
                    (latest_revisions.revision_date + (avg_intervals.avg_days || ' days')::interval)::date
                ) is not null
            ")
            ->orderBy('predicted_date')
            ->paginate($this->perPage($request));
    }
}