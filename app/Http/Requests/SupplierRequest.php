<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'member_id' => ['nullable', 'integer'],
            'supplier_type' => ['required', Rule::in(['member', 'non_member'])],
            'branch_id' => ['nullable', 'integer'],
            'name' => ['required', 'string', 'max:191'],
            'contact_name' => ['nullable', 'string', 'max:191'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:150'],
            'address' => ['nullable', 'string'],
        ];
    }
}
