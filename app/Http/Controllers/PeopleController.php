<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePeopleRequest;
use App\Http\Requests\UpdatePeopleRequest;
use App\Models\People;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;


#[Group('Pessoas')]
class PeopleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    #[Endpoint('Listar pessoas', 'Retorna todas as pessoas cadastradas do usuário autenticado, com suporte a paginação e filtros.')]
    public function index(Request $request)
    {
        $currentPage = max(1, (int) $request->query('current_page', 1));
        $perPage = 10;

        $query = People::where('user_id', Auth::id());

        // Filtros opcionais - cada um só é aplicado se vier preenchido na query string
        if ($name = $request->query('name')) {
            $query->where('name', 'ilike', "%{$name}%");
        }

        if ($email = $request->query('email')) {
            $query->where('email', 'ilike', "%{$email}%");
        }

        if ($phone = $request->query('phone')) {
            // Remove qualquer coisa que não seja dígito antes de comparar,
            // já que o telefone salvo pode ter máscara ou não.
            $digitsOnly = preg_replace('/\D/', '', $phone);
            $query->whereRaw("regexp_replace(phone, '[^0-9]', '', 'g') ilike ?", ["%{$digitsOnly}%"]);
        }

        if ($document = $request->query('document')) {
            $digitsOnly = preg_replace('/\D/', '', $document);
            $query->whereRaw("regexp_replace(document, '[^0-9]', '', 'g') ilike ?", ["%{$digitsOnly}%"]);
        }

        $people = $query
            ->orderBy('name')
            ->paginate($perPage, ['*'], 'current_page', $currentPage);

        return response()->json($people, 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    #[Endpoint('Criar pessoa', 'Cria uma nova pessoa para o usuário autenticado.')]
    public function store(StorePeopleRequest $request)
    {
        try {

            $person = People::create([
                ...$request->validated(),
                'user_id' => Auth::id(),
            ]);

            return response()->json($person, 201);

        } catch (\Exception $ex) {

            Log::error('Erro ao criar pessoa', [
                'error' => $ex->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'error' => 'Falha ao criar pessoa!'
            ], 500);

        }
    }

    /**
     * Display the specified resource.
     */
    #[Endpoint('Buscar pessoa', 'Retorna os dados de uma pessoa específica do usuário autenticado.')]
    public function show(string $id)
    {
        try {
            $people = People::where('user_id', Auth::id())->findOrFail($id);
            return response()->json($people, 200);
        } catch (\Exception $ex) {
            return response()->json(['error' => 'Falha ao buscar pessoa!'], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    #[Endpoint('Atualizar pessoa', 'Atualiza os dados de uma pessoa específica do usuário autenticado.')]
    public function update(UpdatePeopleRequest $request, string $id)
    {
        $validatedData = $request->validated();
        try {
            $people = People::where('user_id', Auth::id())->findOrFail($id);
            $people->update($validatedData);
            return response()->json($people, 200);
        } catch (\Exception $ex) {

            Log::error('Erro ao atualizar pessoa', [
                'error' => $ex->getMessage(),
                'person_id' => $id,
                'user_id' => Auth::id(),
            ]);

            return response()->json(['error' => 'Falha ao atualizar pessoa!'], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    #[Endpoint('Deletar pessoa', 'Move uma pessoa para a lixeira (pode ser restaurada em até 7 dias).')]
    public function destroy(string $id)
    {
        try {
            $people = People::where('user_id', Auth::id())->findOrFail($id);
            $people->moverParaLixeira();
            return response()->json(['message' => 'Pessoa movida para a lixeira. Pode ser recuperada em até 7 dias.'], 200);
        } catch (\Exception $ex) {
            return response()->json(['error' => 'Falha ao deletar pessoa!'], 500);
        }
    }
}