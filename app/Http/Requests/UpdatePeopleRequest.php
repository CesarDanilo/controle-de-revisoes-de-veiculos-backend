<?php

namespace App\Http\Requests;

use App\Models\People;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdatePeopleRequest extends FormRequest
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
        // 🔧 CORRIGIDO — BUG CRÍTICO: o controller recebe `string $id`
        // (PeopleController::update), então o parâmetro da rota se chama
        // 'id', não 'person'. Com o nome errado, $peopleId vinha sempre
        // null, então ->ignore(null) não excluía o próprio registro da
        // checagem de unicidade — editar uma pessoa SEM mudar email ou
        // documento fazia a validação achar que já existia outro registro
        // igual (que era ela mesma) e rejeitar o update. Mesmo padrão que
        // UpdateVehicleRequest/UpdateColorRequest já usam corretamente.
        $peopleId = $this->route('id');

        // person_type pode não vir no payload de um update parcial — nesse
        // caso, busca o valor já salvo no banco pra decidir as regras
        // condicionais (formato do documento, obrigatoriedade de
        // birth_date/gender) sem exigir que o front sempre reenvie o campo.
        $personType = $this->input(
            'person_type',
            People::where('id', $peopleId)->value('person_type') ?? 'PF'
        );

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('people')
                    ->where(fn ($query) => $query->where('user_id', Auth::id()))
                    ->ignore($peopleId),
            ],
            // 🔧 CORRIGIDO — mesmo tratamento de formato do Store.
            'document' => [
                'required',
                'string',
                $personType === 'PJ' ? 'digits:14' : 'digits:11',
                Rule::unique('people')
                    ->where(fn ($query) => $query->where('user_id', Auth::id()))
                    ->ignore($peopleId),
            ],
            'phone' => ['nullable', 'string', 'max:20'],
            // 🟢 NOVO — mesmo tratamento do Store.
            'person_type' => ['required', Rule::in(['PF', 'PJ'])],
            'birth_date' => ['nullable', 'date', 'required_if:person_type,PF'],
            'gender' => ['nullable', Rule::in(['M', 'F', 'O']), 'required_if:person_type,PF'],
        ];
    }
}