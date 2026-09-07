<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReceivableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'received_date' => ['required', 'date'],
            'payer_type' => ['required', Rule::in(['Member', 'Non-Member'])],
            'member_id' => ['nullable', 'integer', 'exists:members,id'],
            'group_id' => ['nullable', 'integer', 'exists:groups,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'payer_name' => ['required', 'string', 'max:150'],
            'payer_phone' => ['nullable', 'string', 'max:30'],
            'receiver_email' => ['nullable', 'email', 'max:255'],
            'group_name' => ['nullable', 'string', 'max:180'],
            'category' => ['required', Rule::in(['Farm and Livestock Related', 'Services', 'Administrative / Other'])],
            'receivable_type' => ['required', 'string', 'max:150'],
            'other_type' => ['nullable', 'string', 'max:150'],
            'amount_payable' => ['required', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0', 'lte:amount_payable'],
            'amount_paid' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['required', Rule::in(['Cash', 'Mobile Money', 'Bank'])],
            'payment_reference' => ['nullable', 'string', 'max:150'],
            'status' => ['nullable', Rule::in(['Received', 'Pending', 'Cancelled'])],
            'description' => ['nullable', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(array_map(static function ($value) {
            return is_string($value) ? trim($value) : $value;
        }, $this->all()));
    }
}
