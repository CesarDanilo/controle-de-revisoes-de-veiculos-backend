<?php

namespace App\Http\Requests;

use App\Enums\StatusPagamento;
use App\Enums\StatusRevisao;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRevisionStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        // a checagem de "é dono da revisão" já é feita no controller
        // (findOrFail com where user_id), então aqui só valida o payload
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'required', 'string', Rule::in(StatusRevisao::values())],
            'status_pagamento' => ['sometimes', 'required', 'string', Rule::in(StatusPagamento::values())],
        ];
    }
}