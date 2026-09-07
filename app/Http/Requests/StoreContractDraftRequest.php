<?php

namespace App\Http\Requests;

use App\Models\Contract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreContractDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'contract_number' => ['nullable', 'string', 'max:255', Rule::unique('contracts', 'contract_number')],
            'contract_for' => ['required', Rule::in(['member', 'group'])],
            'member_id' => ['nullable', 'integer', 'exists:members,id'],
            'group_id' => ['nullable', 'integer', 'exists:groups,id'],
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'payment_method_id' => ['nullable', 'integer', 'exists:payment_methods,id'],
            'template_name' => ['nullable', 'string', 'max:255'],
            'template_path' => ['nullable', 'string', 'max:255'],
            'payment_frequency' => ['nullable', Rule::in(Contract::PAYMENT_FREQUENCIES)],
            'status' => ['nullable', Rule::in(Contract::STATUSES)],
            'workflow_status' => ['nullable', Rule::in(Contract::WORKFLOW_STATUSES)],
            'signing_date' => ['nullable', 'date'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'membership_fee' => ['nullable', 'numeric', 'min:0'],
            'registration_fee' => ['nullable', 'numeric', 'min:0'],
            'admin_fee' => ['nullable', 'numeric', 'min:0'],
            'contract_amount' => ['nullable', 'numeric', 'min:0'],
            'total_amount' => ['nullable', 'numeric', 'min:0'],
            'total_paid' => ['nullable', 'numeric', 'min:0'],
            'item_name' => ['nullable', 'string', 'max:255'],
            'item_type' => ['nullable', 'string', 'max:255'],
            'quantity' => ['nullable', 'numeric', 'min:0'],
            'unit_name' => ['nullable', 'string', 'max:255'],
            'unit_price' => ['nullable', 'numeric', 'min:0'],
            'harvest_frequency' => ['nullable', 'string', 'max:255'],
            'monthly_return' => ['nullable', 'numeric', 'min:0'],
            'monthly_payout_amount' => ['nullable', 'numeric', 'min:0'],
            'quarterly_payout' => ['nullable', 'numeric', 'min:0'],
            'total_hives' => ['nullable', 'numeric', 'min:0'],
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
