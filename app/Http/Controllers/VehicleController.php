<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVehicleRequest;
use App\Http\Requests\UpdateVehicleRequest;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;

#[Group('Veículos')]
class VehicleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    #[Endpoint('Listar veículos', 'Retorna todos os veículos cadastrados do usuário autenticado.')]
    public function index()
    {
        return Vehicle::where('user_id', Auth::id())->get();
    }

    /**
     * Store a newly created resource in storage.
     */
    #[Endpoint('Criar veículo', 'Cria um novo veículo para o usuário autenticado.')]
    public function store(StoreVehicleRequest $request)
    {
        try {

            $vehicle = Vehicle::create([
                ...$request->validated(),
                'user_id' => Auth::id(),
            ]);

            return response()->json($vehicle, 201);

        } catch (\Exception $ex) {

            return response()->json([
                'error' => 'Falha ao criar veículo!'
            ], 500);

        } 
    }

    /**
     * Display the specified resource.
     */
    #[Endpoint('Buscar veículo', 'Retorna os dados de um veículo específico.')]
    public function show(string $id)
    {
        try {
            $vehicle = Vehicle::where('user_id', Auth::id())->findOrFail($id);
            return response()->json($vehicle, 200);
        } catch (\Exception $ex) {
            return response()->json(['error' => 'Falha ao buscar veículo!'], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    #[Endpoint('Atualizar veículo', 'Atualiza os dados de um veículo específico.')]
    public function update(UpdateVehicleRequest $request, string $id)
    {
        $validatedData = $request->validated();

        try {
            $vehicle = Vehicle::where('user_id', Auth::id())->findOrFail($id);
            $vehicle->update($validatedData);
            return response()->json($vehicle, 200);
        } catch (\Exception $ex) {
            return response()->json(['error' => 'Falha ao atualizar veículo!'], 500);
        }
    }
    /**
     * Remove the specified resource from storage.
     */
    #[Endpoint('Deletar veículo', 'Deleta um veículo específico.')]
    public function destroy(string $id)
    {
        try {
            $vehicle = Vehicle::where('user_id', Auth::id())->findOrFail($id);
            $vehicle->delete();
            return response()->json(['message' => 'Veículo deletado com sucesso!'], 200);
        } catch (\Exception $ex) {
            return response()->json(['error' => 'Falha ao deletar veículo!'], 500);
        }
    }
}
