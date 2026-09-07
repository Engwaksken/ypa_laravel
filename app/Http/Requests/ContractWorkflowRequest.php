<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ContractWorkflowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(['submit', 'sign', 'reject', 'finalise'])],
            'notes' => ['nullable', 'string'],
            'signature_data' => ['nullable', 'string'],
            'signature_method' => ['nullable', 'string', 'max:100'],
            'expected_step' => ['nullable', 'integer', 'min:0'],
            'expected_role' => ['nullable', 'string', 'max:50'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(array_map(static function ($value) {
            return is_string($value) ? trim($value) : $value;
        }, $this->all()));
    }
}
