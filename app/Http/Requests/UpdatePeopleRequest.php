<?php

namespace App\Http\Requests;

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
        $peopleId = $this->route()->parameter('person');

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
            'document' => [
                'required',
                'string',
                'max:20',
                Rule::unique('people')
                    ->where(fn ($query) => $query->where('user_id', Auth::id()))
                    ->ignore($peopleId),
            ],
            'phone' => ['nullable', 'string', 'max:20'],
            'birth_date' => ['nullable', 'date'],
            'gender' => ['nullable', Rule::in(['M', 'F', 'O'])],
        ];
    }
}