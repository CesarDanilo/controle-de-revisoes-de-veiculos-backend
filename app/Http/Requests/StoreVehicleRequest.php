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
            // color_id é de tabela global (colors não tem user_id), então
            // não faz sentido escopar por usuário aqui.
            'color_id' => ['required', 'exists:colors,id'],
            // 🔧 CORRIGIDO — antes só 'exists:brands,id', sem checar o dono.
            // Um usuário podia enviar o brand_id de outro usuário e a
            // validação aceitava normalmente (sem isolamento entre contas).
            'brand_id' => [
                'required',
                Rule::exists('brands', 'id')->where(fn ($q) => $q->where('user_id', Auth::id())),
            ],
            // 🔧 CORRIGIDO — estava 'nullable', mas a coluna people_id no
            // banco é NOT NULL (migration create_vehicle_table não tem
            // ->nullable()). Antes, se o front esquecesse de mandar, o
            // erro só aparecia como 500 genérico do banco. Também agora
            // escopado por usuário, pelo mesmo motivo do brand_id acima.
            'people_id' => [
                'required',
                Rule::exists('people', 'id')->where(fn ($q) => $q->where('user_id', Auth::id())),
            ],
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