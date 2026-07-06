<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePeopleRequest;
use App\Http\Requests\UpdatePeopleRequest;
use App\Models\People;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
        $people = People::where('user_id', Auth::id())->skip($skip)->take($per_page)->get();
        return response()->json($people, 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePeopleRequest $request)
    {
        try {

            $person = People::create([
                ...$request->validated(),
                'user_id' => Auth::id(),
            ]);

            return response()->json($person, 201);

        } catch (\Exception $ex) {

            return response()->json([
                'error' => 'Falha ao criar pessoa!'
            ], 500);

        }
    }

    /**
     * Display the specified resource.
     */
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
    public function update(UpdatePeopleRequest $request, string $id)
    {
        $validatedData = $request->validated();
        try {
            $people = People::where('user_id', Auth::id())->findOrFail($id);
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
            $people = People::where('user_id', Auth::id())->findOrFail($id);
            $people->delete();
            return response()->json(['message' => 'Pessoa deletada com sucesso!'], 200);
        } catch (\Exception $ex) {
            return response()->json(['error' => 'Falha ao deletar pessoa!'], 500);
        }
    }
}
