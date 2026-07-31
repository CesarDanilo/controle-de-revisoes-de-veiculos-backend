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
        // 🔴 CORRIGIDO — id do veículo sendo editado, pego da própria rota
        // (o controller recebe `string $id` no update, então o parâmetro
        // da rota provavelmente se chama 'id'; se sua rota usar outro nome
        // de parâmetro, ajuste aqui).
        $vehicleId = $this->route('id');

        return [
            'model' => 'sometimes|required|string|max:255',
            'year' => 'sometimes|required|integer|min:1900|max:' . (date('Y') + 1),
            // 🔴 CORRIGIDO — estava sempre 'required', quebrando updates parciais
            // que não alteram a cor (seu front só envia os campos que mudaram).
            'color_id' => 'sometimes|required|exists:colors,id',
            'brand_id' => 'sometimes|required|exists:brands,id',
            'people_id' => 'nullable|exists:people,id',
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