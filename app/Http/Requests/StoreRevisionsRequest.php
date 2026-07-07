<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreRevisionsRequest extends FormRequest
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
            'vehicle_id' => 'required|exists:vehicle,id',
            'description' => 'nullable|string|max:255',
            'revision_date' => 'required|date',
            'cost' => 'nullable|numeric|min:0',
            'next_revision_date' => 'nullable|date|after:revision_date',
            'next_revision_km' => 'nullable|integer|min:0',
            'km' => 'nullable|integer|min:0',
        ];
    }
}