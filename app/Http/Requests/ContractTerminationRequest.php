<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContractTerminationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'contract_id' => ['required', 'integer', 'exists:contracts,id'],
            'termination_date' => ['required', 'date'],
            'reason' => ['required', 'string'],
            'project_projection' => ['nullable', 'numeric', 'min:0'],
            'deduction_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'deduction_amount' => ['nullable', 'numeric', 'min:0'],
            'refund_amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(array_map(static function ($value) {
            return is_string($value) ? trim($value) : $value;
        }, $this->all()));
    }
}
