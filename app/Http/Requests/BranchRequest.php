<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:191'],
            'location' => ['nullable', 'string', 'max:191'],
            'contact' => ['nullable', 'string', 'max:191'],
            'branch_email' => ['nullable', 'email', 'max:191'],
            'status' => ['nullable', 'boolean'],
        ];
    }
}
