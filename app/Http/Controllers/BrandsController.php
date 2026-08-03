<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBrandsRequest;
use App\Http\Requests\UpdateBrandsRequest;
use App\Models\Brands;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;

#[Group('Marcas')]
class BrandsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    #[Endpoint('Listar marcas', 'Retorna todas as marcas cadastradas.')]
    public function index(Request $request)
    {
        $current_page = (int) $request->query('current_page', 1);
        $per_page = 10;
        $skip = ($current_page - 1) * $per_page;

        $brands = Brands::where('user_id', Auth::id())
            ->skip($skip)
            ->take($per_page)
            ->get();

        return response()->json($brands, 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    #[Endpoint('Criar marca', 'Cria uma nova marca para o usuário autenticado.')]
    public function store(StoreBrandsRequest $request)
    {
        try {
            $brand = Brands::create([
                'name' => $request->validated('name'),
                'user_id' => Auth::id(),
            ]);

            return response()->json($brand, 201);

        } catch (\Throwable $ex) {
            // ✅ Grava o erro detalhado nos logs do Laravel (storage/logs/laravel.log)
            Log::error('Erro ao criar marca: ' . $ex->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $ex->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Falha ao criar marca!',
                'message' => config('app.debug') ? $ex->getMessage() : 'Erro interno no servidor.'
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    #[Endpoint('Buscar marca', 'Retorna os dados de uma marca específica.')]
    public function show(string $id)
    {
        try {
            $brands = Brands::where('user_id', Auth::id())->findOrFail($id);
            return response()->json($brands, 200);
        } catch (\Exception $ex) {
            return response()->json(['error' => 'Marca não encontrada!'], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    #[Endpoint('Atualizar marca', 'Atualiza os dados de uma marca específica.')]
    public function update(UpdateBrandsRequest $request, string $id)
    {
        try {
            $brands = Brands::where('user_id', Auth::id())->findOrFail($id);
            $brands->update($request->validated());
            return response()->json($brands, 200);
        } catch (\Exception $ex) {
            return response()->json(['error' => 'Falha ao atualizar marca!'], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    #[Endpoint('Deletar marca', 'Deleta uma marca específica.')]
    public function destroy(string $id)
    {
        try {
            $brands = Brands::where('user_id', Auth::id())->findOrFail($id);
            $brands->delete();
            return response()->json(['message' => 'Marca deletada com sucesso!'], 200);
        } catch (\Exception $ex) {
            return response()->json(['error' => 'Falha ao deletar marca!'], 500);
        }
    }
}