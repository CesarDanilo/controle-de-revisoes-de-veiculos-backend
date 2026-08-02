<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdateVehicleRequest extends FormRequest
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
        // id do veículo sendo editado, pego da própria rota (o controller
        // recebe `string $id` no update).
        $vehicleId = $this->route('id');

        return [
            'model' => 'sometimes|required|string|max:255',
            'year' => 'sometimes|required|integer|min:1900|max:' . (date('Y') + 1),
            'color_id' => 'sometimes|required|exists:colors,id',
            // 🔧 CORRIGIDO — agora escopado por usuário, mesmo motivo do Store.
            'brand_id' => [
                'sometimes',
                'required',
                Rule::exists('brands', 'id')->where(fn ($q) => $q->where('user_id', Auth::id())),
            ],
            // 🔧 CORRIGIDO — antes 'nullable' (a coluna é NOT NULL no banco).
            // Vira 'sometimes' porque em update parcial pode não vir no
            // payload — mas se vier, tem que ser válido e do próprio usuário.
            'people_id' => [
                'sometimes',
                'required',
                Rule::exists('people', 'id')->where(fn ($q) => $q->where('user_id', Auth::id())),
            ],
            'license_plate' => [
                'sometimes',
                'required',
                'string',
                'max:10',
                // 🔴 CORRIGIDO — tabela real é 'vehicle' (singular). Escopado por
                // usuário (mesma correção do Store) e ignorando o próprio veículo,
                // senão salvar sem mudar a placa acusava duplicidade contra ele mesmo.
                Rule::unique('vehicle', 'license_plate')
                    ->where(fn ($query) => $query->where('user_id', Auth::id()))
                    ->ignore($vehicleId),
                    // 🔴 Mesma observação do Store: se `vehicle` tiver soft delete,
                    // descomente pra ignorar placas na lixeira:
                    // ->whereNull('deleted_at'),
            ],
        ];
    }
}