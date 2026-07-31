<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreVehicleRequest extends FormRequest
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
            'model' => 'required|string|max:255',
            'year' => 'required|integer|min:1900|max:' . (date('Y') + 1),
            'color_id' => ['required', 'exists:colors,id'],
            'brand_id' => 'required|exists:brands,id',
            'people_id' => 'nullable|exists:people,id',
            'license_plate' => [
                'required',
                'string',
                'max:10',
                // 🔴 CORRIGIDO — a tabela real é 'vehicle' (singular, confirmado pelo
                // erro "relation vehicles does not exist"). O problema nunca foi o nome
                // da tabela, e sim a falta de escopo: antes 'unique:vehicle,license_plate'
                // verificava a placa pra QUALQUER usuário. Agora a checagem de duplicidade
                // só considera os veículos do próprio usuário autenticado, permitindo a
                // mesma placa em contas/empresas diferentes.
                Rule::unique('vehicle', 'license_plate')
                    ->where(fn ($query) => $query->where('user_id', Auth::id())),
                    // 🔴 Se a tabela `vehicle` tiver soft delete (coluna `deleted_at`),
                    // descomente a linha abaixo pra não bloquear com placas que já
                    // foram movidas pra lixeira:
                    // ->whereNull('deleted_at'),
            ],
        ];
    }
}