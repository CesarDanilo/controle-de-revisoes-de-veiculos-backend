<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\Request;

use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;

#[Group('Usuários')]
class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    #[Endpoint('Listar usuários', 'Retorna todos os usuários cadastrados.')]
    public function index(Request $request)
    {
        $current_page = $request->query('current_page') ?? 1;
        $per_page = 10;
        $skip = ($current_page - 1) * $per_page;
        $users = User::skip($skip)->take($per_page)->get();
        return response()->json($users, 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    #[Endpoint('Criar usuário', 'Cria um novo usuário.')]
    public function store(StoreUserRequest $request)
    {
        $validatedData = $request->validated();

        try {
            $user = User::create([
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'password' => bcrypt($validatedData['password']),
            ]);

            return response()->json($user, 201);
        } catch (\Exception $ex) {
            return response()->json(['error' => 'Falha ao criar usuário!'], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    #[Endpoint('Buscar usuário', 'Retorna os dados de um usuário específico.')]
    public function show(string $id)
    {
        try {
            $user = User::findOrFail($id);
            return response()->json($user, 200);
        } catch (\Exception $ex) {
            return response()->json(['error' => 'Falha ao buscar usuário!'], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    #[Endpoint('Atualizar usuário', 'Atualiza os dados de um usuário específico.')]
    public function update(UpdateUserRequest $request, string $id)
    {
        $validatedData = $request->validated();
        try {
            $user = User::findOrFail($id);
            if(!$user){
                return response()->json(['error' => 'Usuário não encontrado!'], 404);
            }
            $user->update($validatedData);
            return response()->json($user, 200);
        } catch (\Exception $ex) {
            return response()->json(['error' => 'Falha ao atualizar usuário!'], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    #[Endpoint('Deletar usuário', 'Deleta um usuário específico.')]
    public function destroy(string $id)
    {
        try {
            $user = User::findOrFail($id);
            $user->delete();
            return response()->json(['message' => 'Usuário deletado com sucesso!'], 204);
        } catch (\Exception $ex) {
            return response()->json(['error' => 'Falha ao deletar usuário!'], 500);
        }
    }
}
