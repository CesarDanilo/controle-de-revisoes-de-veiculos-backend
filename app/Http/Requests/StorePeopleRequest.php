<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StorePeopleRequest extends FormRequest
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
        // 🔴 AQUI — usado pra decidir o formato exigido do documento e se
        // birth_date/gender são obrigatórios. Default 'PF' se não vier nada.
        $personType = $this->input('person_type', 'PF');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('people')->where(fn ($query) => $query->where('user_id', Auth::id())),
            ],
            // 🔧 CORRIGIDO — antes era só 'string|max:20', aceitando
            // qualquer coisa. Agora exige exatamente o número de dígitos
            // certo conforme person_type (11 = CPF, 14 = CNPJ), igual ao
            // que o PersonFormModal já envia (só dígitos, sem pontuação).
            'document' => [
                'required',
                'string',
                $personType === 'PJ' ? 'digits:14' : 'digits:11',
                Rule::unique('people')->where(fn ($query) => $query->where('user_id', Auth::id())),
            ],
            'phone' => ['nullable', 'string', 'max:20'],
            // 🟢 NOVO — antes person_type nunca era validado; podia vir
            // vazio/errado e cair no default 'PF' da coluna sem checagem.
            'person_type' => ['required', Rule::in(['PF', 'PJ'])],
            // 🟢 NOVO — obrigatórios só para Pessoa Física (PJ continua
            // sem gênero/nascimento, igual à migration que tornou essas
            // colunas nullable especificamente pro caso PJ).
            'birth_date' => ['nullable', 'date', 'required_if:person_type,PF'],
            'gender' => ['nullable', Rule::in(['M', 'F', 'O']), 'required_if:person_type,PF'],
        ];
    }
}