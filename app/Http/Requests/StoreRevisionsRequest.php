<?php

namespace App\Http\Requests;

use App\Enums\StatusPagamento;
use App\Enums\StatusRevisao;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreRevisionsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // 🔧 CORRIGIDO — antes 'exists:vehicle,id' sem escopo por
            // usuário: qualquer usuário autenticado podia enviar o
            // vehicle_id de outra conta e a validação aceitava, criando
            // uma revisão presa a um veículo que não é dele.
            'vehicle_id' => [
                'required',
                Rule::exists('vehicle', 'id')->where(fn ($q) => $q->where('user_id', Auth::id())),
            ],
            'description' => 'nullable|string|max:255',
            'revision_date' => 'required|date',
            'cost' => 'nullable|numeric|min:0',
            'next_revision_date' => 'nullable|date|after:revision_date',
            'next_revision_km' => 'nullable|integer|min:0',
            'km' => 'nullable|integer|min:0',
            // 🔴 AQUI — status/status_pagamento agora também podem vir na
            // criação (o formulário do frontend já manda os defaults
            // 'aberto'/'pendente' ou o que o usuário escolher). 'sometimes'
            // porque, se não vierem, o Controller usa o default da coluna.
            'status' => ['sometimes', new Enum(StatusRevisao::class)],
            'status_pagamento' => ['sometimes', new Enum(StatusPagamento::class)],
        ];
    }
}