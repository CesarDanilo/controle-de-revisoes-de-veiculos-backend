<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

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
            'color' => 'required|string|max:255',
            'brand_id' => 'required|exists:brands,id',
            'people_id' => 'nullable|exists:people,id',
            'license_plate' => 'required|string|max:10|unique:vehicles,license_plate',
        ];
    }
}
