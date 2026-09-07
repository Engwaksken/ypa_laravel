<?php

namespace App\Http\Requests;

use App\Models\Contract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'contract_for' => ['required', Rule::in(['member', 'group'])],
            'member_id' => ['required_if:contract_for,member', 'nullable', 'integer', 'exists:members,id'],
            'group_id' => ['required_if:contract_for,group', 'nullable', 'integer', 'exists:groups,id'],
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'payment_method_id' => ['nullable', 'integer', 'exists:payment_methods,id'],
            'payment_frequency' => ['required', Rule::in(Contract::PAYMENT_FREQUENCIES)],
            'signing_date' => ['required', 'date'],
            'duration' => ['required', 'integer', 'min:1'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'contract_amount' => ['nullable', 'numeric', 'min:0'],
            'total_amount' => ['nullable', 'numeric', 'min:0'],
            'template_path' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'items' => ['nullable', 'array'],
            'items.*.item_name' => ['nullable', 'string', 'max:255'],
            'items.*.item_type' => ['nullable', 'string', 'max:255'],
            'items.*.quantity' => ['nullable', 'numeric', 'min:0'],
            'items.*.unit_name' => ['nullable', 'string', 'max:255'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.monthly_return' => ['nullable', 'numeric', 'min:0'],
            'items.*.total_hives' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
