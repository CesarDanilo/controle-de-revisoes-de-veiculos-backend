<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreColorRequest;
use App\Http\Requests\UpdateColorRequest;
use App\Models\Color;

use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;

#[Group('Cores')]
class ColorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    #[Endpoint('Listar cores', 'Retorna todas as cores cadastradas.')]
    public function index()
    {
        return Color::orderBy('name')->get();
    }

    /**
     * Store a newly created resource in storage.
     */
    #[Endpoint('Criar cor', 'Cria uma nova cor.')]
    public function store(StoreColorRequest $request)
    {
        try {

            $color = Color::create($request->validated());

            return response()->json($color, 201);

        } catch (\Exception $ex) {

            return response()->json([
                'error' => 'Falha ao criar cor!'
            ], 500);

        }
    }

    /**
     * Display the specified resource.
     */
    #[Endpoint('Buscar cor', 'Retorna os dados de uma cor específica.')]
    public function show(string $id)
    {
        try {
            $color = Color::findOrFail($id);
            return response()->json($color, 200);
        } catch (\Exception $ex) {
            return response()->json(['error' => 'Falha ao buscar cor!'], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    #[Endpoint('Atualizar cor', 'Atualiza os dados de uma cor específica.')]
    public function update(UpdateColorRequest $request, string $id)
    {
        $validatedData = $request->validated();

        try {
            $color = Color::findOrFail($id);
            $color->update($validatedData);
            return response()->json($color, 200);
        } catch (\Exception $ex) {
            return response()->json(['error' => 'Falha ao atualizar cor!'], 500);
        }
    }
}