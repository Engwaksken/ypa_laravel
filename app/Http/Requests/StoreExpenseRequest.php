<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExpenseRequest extends FormRequest
{
    public const CATEGORIES = [
        'Rent',
        'Utilities',
        'Salaries',
        'Supplies',
        'Marketing',
        'Transportation',
        'Maintenance',
        'Insurance',
        'Goat Harvest Payout',
        'Honey Harvest Payout',
        'Venom Harvest Payout',
        'Maize Harvest Payout',
        'CDC Withdrawal',
        'Cash Withdrawal',
        'Other',
    ];

    public const PAYMENT_METHODS = ['Cash', 'Mobile Money', 'Bank', 'Cheque', 'Other'];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:191'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'expense_date' => ['required', 'date'],
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'category' => ['required', 'string', 'max:100'],
            'custom_category' => ['nullable', 'string', 'max:100'],
            'payment_method' => ['required', 'string', Rule::in(self::PAYMENT_METHODS)],
            'description' => ['nullable', 'string'],
            'debit_account_code' => ['nullable', 'string', 'max:20'],
            'credit_account_code' => ['nullable', 'string', 'max:20'],
        ];
    }

    public function resolvedCategory(): string
    {
        $category = trim((string) $this->input('category', ''));
        if ($category === 'Other') {
            $custom = trim((string) $this->input('custom_category', ''));
            if ($custom !== '') {
                return $custom;
            }
        }

        return $category;
    }

    public function amounts(): array
    {
        return [
            'amount' => (float) $this->input('amount', 0),
        ];
    }
}