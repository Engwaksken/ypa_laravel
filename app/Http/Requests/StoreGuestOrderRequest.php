<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGuestOrderRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    protected function prepareForValidation(): void
    {
        // Checkout posts the cart as JSON in multipart FormData.
        if (is_string($this->input('items'))) {
            $this->merge(['items' => json_decode($this->input('items'), true)]);
        }
    }

    public function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'max:191'],
            'phone' => ['required', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:150'],
            'delivery_location' => ['required', 'string', 'max:500'],
            'branch_id' => ['required', 'integer', Rule::exists('branches', 'id')->where('status', true)],
            'payment_method' => ['required', Rule::in(['mobile-money', 'cash-on-delivery'])],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.id' => ['required', 'integer', 'distinct', Rule::exists('products', 'id')->where('is_active', true)],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
        ];
    }
}
