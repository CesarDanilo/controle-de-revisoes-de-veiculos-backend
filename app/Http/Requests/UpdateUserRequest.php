<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
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
        // 🔧 CORRIGIDO — mesmo bug do UpdatePeopleRequest: o controller
        // recebe `string $id` (UserController::update), então o parâmetro
        // da rota é 'id', não 'user'. Estava sempre null, então o unique
        // de email comparava o registro contra ele mesmo e rejeitava
        // updates que mantinham o mesmo e-mail.
        $userId = $this->route('id');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $userId],
            'password' => ['nullable', 'string', 'min:8'],
            'status' => ['nullable', 'boolean'],
        ];
    }
}