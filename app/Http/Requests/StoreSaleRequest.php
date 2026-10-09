<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSaleRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('items'))) {
            $this->merge(['items' => json_decode($this->input('items'), true)]);
        }
        if (preg_match('/^(customer|member|group)_(\d+)$/', (string) $this->input('customer_id'), $matches)) {
            $this->merge($matches[1] === 'customer' ? ['customer_id' => $matches[2]]
                : ['customer_id' => null, 'customer_type' => $matches[1], 'customer_ref_id' => $matches[2]]);
        }
    }

    public function rules(): array
    {
        return [
            'request_token' => ['required', 'uuid'],
            'branch_id' => ['required', 'integer', Rule::exists('branches', 'id')->where('status', true)],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'customer_type' => ['nullable', Rule::in(['member', 'group'])],
            'customer_ref_id' => ['nullable', 'integer', 'required_with:customer_type'],
            'payment_method_id' => ['required', 'integer', 'exists:payment_methods,id'],
            'payment_status' => ['required', Rule::in(['paid', 'partially paid', 'oncredit'])],
            'discount' => ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:999999999999.99'],
            'amount_paid' => ['nullable', 'required_if:payment_status,partially paid', 'numeric', 'decimal:0,2', 'min:0', 'max:999999999999.99'],
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.id' => ['required', 'integer', 'distinct', Rule::exists('products', 'id')->where('is_active', true)],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
            'items.*.expected_price' => ['nullable', 'numeric', 'decimal:0,2', 'min:0.01', 'max:1000000000'],
        ];
    }
}
