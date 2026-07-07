<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRevisionsRequest;
use App\Http\Requests\UpdateRevisionsRequest;
use App\Models\Revisions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;

#[Group('Revisões')]
class RevisionsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    #[Endpoint('Listar revisões', 'Retorna todas as revisões cadastradas do usuário autenticado.')]
    public function index(Request $request)
    {
        $current_page = $request->query('current_page') ?? 1;
        $per_page = 10;
        $skip = ($current_page - 1) * $per_page;
        $revisions = Revisions::skip($skip)->take($per_page)->get();
        return response()->json($revisions, 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    #[Endpoint('Criar revisão', 'Cria uma nova revisão.')]
    public function store(StoreRevisionsRequest $request)
    {
        try {

            $revision = Revisions::create([
                ...$request->validated(),
                'user_id' => Auth::id(),
            ]);

            return response()->json($revision, 201);

        } catch (\Exception $ex) {

            return response()->json([
                'error' => 'Falha ao criar revisão!'
            ], 500);

        }
    }

    /**
     * Display the specified resource.
     */
    #[Endpoint('Buscar revisão', 'Retorna os dados de uma revisão específica.')]
    public function show(string $id)
    {
        try {
            $revision = Revisions::where('user_id', Auth::id())->findOrFail($id);
            return response()->json($revision, 200);
        } catch (\Exception $ex) {
            return response()->json(['error' => 'Falha ao buscar revisão!'], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    #[Endpoint('Atualizar revisão', 'Atualiza os dados de uma revisão específica.')]
    public function update(UpdateRevisionsRequest $request, string $id)
    {
        $validatedData = $request->validated();
        try {
            $revision = Revisions::where('user_id', Auth::id())->findOrFail($id);
            $revision->update($validatedData);
            return response()->json($revision, 200);
        } catch (\Exception $ex) {
            dd($ex);
            return response()->json(['error' => 'Falha ao atualizar revisão!'], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    #[Endpoint('Deletar revisão', 'Deleta uma revisão específica.')]
    public function destroy(string $id)
    {
        try {
            $revision = Revisions::where('user_id', Auth::id())->findOrFail($id);
            $revision->delete();
            return response()->json(['message' => 'Revisão deletada com sucesso!'], 200);
        } catch (\Exception $ex) {
            return response()->json(['error' => 'Falha ao deletar revisão!'], 500);
        }
    }
}
