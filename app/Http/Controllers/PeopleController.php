<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePeopleRequest;
use App\Http\Requests\UpdatePeopleRequest;
use App\Models\People;
use App\Models\User;
use Illuminate\Http\Request;

class PeopleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $current_page = $request->query('current_page') ?? 1;
        $per_page = 10;
        $skip = ($current_page - 1) * $per_page;
        $people = People::skip($skip)->take($per_page)->get();
        return response()->json($people, 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePeopleRequest $request)
    {
        $validatedData = $request->validated();
        try {
            $user = User::findOrFail($validatedData['user_id']);
            if (!$user) {
                return response()->json(['error' => 'Usuário não encontrado!'], 404);
            }
            $people = People::create($validatedData);
            return response()->json($people, 201);
        } catch (\Exception $ex) {
            return response()->json(['error' => 'Falha ao criar pessoa!'], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $people = People::findOrFail($id);
            return response()->json($people, 200);
        } catch (\Exception $ex) {
            return response()->json(['error' => 'Falha ao buscar pessoa!'], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePeopleRequest $request, string $id)
    {
        $validatedData = $request->validated();
        try {
            $people = People::findOrFail($id);
            $people->update($validatedData);
            return response()->json($people, 200);
        } catch (\Exception $ex) {
            dd($ex);
            return response()->json(['error' => 'Falha ao atualizar pessoa!'], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $people = People::findOrFail($id);
            $people->delete();
            return response()->json(['message' => 'Pessoa deletada com sucesso!'], 200);
        } catch (\Exception $ex) {
            return response()->json(['error' => 'Falha ao deletar pessoa!'], 500);
        }
    }
}
