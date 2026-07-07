<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVehicleRequest;
use App\Http\Requests\UpdateVehicleRequest;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VehicleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Vehicle::all();
    }

    /**
     * Store a newly created resource in storage.
     */
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
