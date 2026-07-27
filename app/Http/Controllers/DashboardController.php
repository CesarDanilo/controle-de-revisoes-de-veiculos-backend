<?php

namespace App\Http\Controllers;

use App\Models\People;
use App\Models\Revisions;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Retorna os totais agregados do dashboard (pessoas, veículos,
     * revisões e valor total investido) já calculados no banco,
     * evitando trafegar as listas completas pro frontend.
     */
    public function summary(): JsonResponse
    {
        $userId = Auth::id();

        $peopleCount = People::where('user_id', $userId)->count();

        $vehiclesCount = Vehicle::where('user_id', $userId)->count();

        // Uma única query traz a contagem e a soma juntas, evitando
        // duas idas separadas à tabela de revisions.
        $revisionsAggregate = Revisions::where('user_id', $userId)
            ->selectRaw('COUNT(*) as revisions_count, COALESCE(SUM(cost), 0) as total_invested')
            ->first();

        return response()->json([
            'people_count' => $peopleCount,
            'vehicles_count' => $vehiclesCount,
            'revisions_count' => (int) $revisionsAggregate->revisions_count,
            'total_invested' => (float) $revisionsAggregate->total_invested,
        ]);
    }
}