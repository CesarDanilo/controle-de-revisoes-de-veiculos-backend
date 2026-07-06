<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBrandsRequest;
use App\Http\Requests\UpdateBrandsRequest;
use App\Models\Brands;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BrandsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $current_page = $request->query('current_page') ?? 1;
        $per_page = 10;
        $skip = ($current_page - 1) * $per_page;
        $brands = Brands::skip($skip)->take($per_page)->get();
        return response()->json($brands, 200);
    }

    /**
     * Store a newly created resource in storage.
     */
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
    public function update(UpdateBrandsRequest $request, string $id)
    {
        $validatedData = $request->validated();
        try {
            $brands = Brands::where('user_id', Auth::id())->findOrFail($id);
            $brands->update($validatedData);
            return response()->json($brands, 200);
        } catch (\Exception $ex) {
            dd($ex);
            return response()->json(['error' => 'Falha ao atualizar marca!'], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
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
