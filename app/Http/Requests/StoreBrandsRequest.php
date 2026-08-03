<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreBrandsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                // Valida se a marca já existe APENAS para o usuário atual
                Rule::unique('brands', 'name')->where(function ($query) {
                    return $query->where('user_id', Auth::id());
                }),
            ],
        ];
    }
}