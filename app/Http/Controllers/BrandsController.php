<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBrandsRequest;
use App\Http\Requests\UpdateBrandsRequest;
use App\Models\Brands;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
        $current_page = $request->query('current_page') ?? 1;
        $per_page = 10;
        $skip = ($current_page - 1) * $per_page;

        // 🔧 CORRIGIDO — antes listava marcas de TODOS os usuários (sem
        // where user_id). O frontend mostrava opções no dropdown que não
        // pertenciam ao usuário logado; ao selecionar uma dessas marcas e
        // enviar o formulário de veículo, a validação escopada por usuário
        // (StoreVehicleRequest/UpdateVehicleRequest) rejeitava com "The
        // selected brand id is invalid.", porque a marca de fato não
        // pertence a esse usuário. Agora index() só retorna as marcas do
        // próprio usuário autenticado, igual show/update/destroy já fazem.
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
                ...$request->validated(),
                'user_id' => Auth::id(),
            ]);

            return response()->json($brand, 201);

        } catch (\Exception $ex) {

            return response()->json([
                'error' => 'Falha ao criar marca!'
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
            return response()->json(['error' => 'Falha ao buscar marca!'], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    #[Endpoint('Atualizar marca', 'Atualiza os dados de uma marca específica.')]
    public function update(UpdateBrandsRequest $request, string $id)
    {
        $validatedData = $request->validated();
        try {
            $brands = Brands::where('user_id', Auth::id())->findOrFail($id);
            $brands->update($validatedData);
            return response()->json($brands, 200);
        } catch (\Exception $ex) {
            // 🔧 CORRIGIDO — dd($ex) interrompia a resposta em ambiente de
            // produção/teste com var_dump bruto no meio do fluxo HTTP,
            // impedindo o response()->json() de ser retornado. Removido.
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