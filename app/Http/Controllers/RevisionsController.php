<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRevisionsRequest;
use App\Http\Requests\UpdateRevisionsRequest;
use App\Http\Requests\UpdateRevisionStatusRequest;
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
/**
     * Display a listing of the resource.
     */
    #[Endpoint('Listar revisões', 'Retorna todas as revisões cadastradas do usuário autenticado.')]
    public function index(Request $request)
    {
        // 🟢 AQUI — eager load do veículo + dono, resolvendo o TODO do Kanban
        // ("trocar por modelo/placa do veículo e nome do proprietário").
        // Sem isso, cada card não tinha como saber o person_id pra abrir o
        // RevisionsModal.
        $query = Revisions::where('user_id', Auth::id())->with('vehicle.people');

        // Filter by vehicle when the frontend asks for a specific vehicle's
        // revisions (RevisionsModal.vue calls this once per vehicle).
        if ($request->filled('vehicle_id')) {
            $query->where('vehicle_id', $request->query('vehicle_id'));
        }

        $per_page = $request->query('per_page', 15);

        $revisions = $query
            ->orderByDesc('revision_date')
            ->paginate($per_page);

        // 🟢 AQUI — achata vehicle.people em campos soltos (person_id,
        // person_name, vehicle_model, vehicle_license_plate) pra não expor a
        // estrutura aninhada pro frontend, que só precisa desses valores.
        $items = collect($revisions->items())->map(function ($revision) {
            $data = $revision->toArray();
            $data['person_id'] = $revision->vehicle?->people?->id;
            $data['person_name'] = $revision->vehicle?->people?->name;
            $data['vehicle_model'] = $revision->vehicle?->model;
            $data['vehicle_license_plate'] = $revision->vehicle?->license_plate;
            unset($data['vehicle']);
            return $data;
        });

        return response()->json($items, 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    #[Endpoint('Criar revisão', 'Cria uma nova revisão.')]
    public function store(StoreRevisionsRequest $request)
    {
        try {

            $revision = new Revisions([
                ...$request->safe()->except(['status', 'status_pagamento']),
                'user_id' => Auth::id(),
            ]);

            if ($request->filled('status')) {
                $revision->status = $request->validated('status');
            }
            if ($request->filled('status_pagamento')) {
                $revision->status_pagamento = $request->validated('status_pagamento');
            }

            $revision->save();

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
            return response()->json(['error' => 'Falha ao atualizar revisão!'], 500);
        }
    }

    /**
     * Update the status and/or payment status of the revision.
     * Used by the Kanban drag-and-drop (status only) and by the manual
     * edit form in RevisionsModal (status and/or status_pagamento).
     */
    #[Endpoint('Atualizar status da revisão', 'Move a revisão para outra coluna do Kanban e/ou atualiza o status de pagamento.')]
    public function updateStatus(UpdateRevisionStatusRequest $request, string $id)
    {
        try {
            $revision = Revisions::where('user_id', Auth::id())->findOrFail($id);

            if ($request->filled('status')) {
                $revision->status = $request->validated('status');
            }
            if ($request->filled('status_pagamento')) {
                $revision->status_pagamento = $request->validated('status_pagamento');
            }

            $revision->save();

            return response()->json($revision, 200);
        } catch (\Exception $ex) {
            return response()->json(['error' => 'Falha ao atualizar status da revisão!'], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    #[Endpoint('Deletar revisão', 'Deleta uma revisão específica.')]
    public function destroy($id)
    {
        $revisao = Revisions::where('user_id', Auth::id())->findOrFail($id);
        $revisao->moverParaLixeira();

        return response()->json([
            'mensagem' => 'Revisão movida para a lixeira. Pode ser recuperada em até 7 dias.'
        ]);
    }
}