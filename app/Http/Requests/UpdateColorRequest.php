<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateColorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $colorId = $this->route('id');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:100', 'unique:colors,name,' . $colorId],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome da cor é obrigatório.',
            'name.unique' => 'Essa cor já está cadastrada.',
        ];
    }
}