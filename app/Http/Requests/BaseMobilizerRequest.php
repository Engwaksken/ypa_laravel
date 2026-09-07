<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Shared validation for mobilizer create/update. branch_region is a branch
 * NAME string in the legacy data (e.g. 'Mbarara'), not a branch id.
 */
abstract class BaseMobilizerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'department' => ['required', 'string', 'max:255'],
            'position' => ['required', 'string', 'max:255'],
            'contact_number' => ['required', 'string'],
            'email' => ['nullable', 'email', 'max:255'],
            'branch_region' => ['nullable', 'string'],
            'supervisor' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:Active,Inactive,Suspended,Terminated'],
            'remarks' => ['nullable', 'string'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $contact = trim((string) $this->input('contact_number'));
            $normalized = preg_replace('/[\s\-()]/', '', $contact) ?? $contact;

            if ($normalized !== '' && !preg_match('/^\+[0-9]{12}$/', $normalized)) {
                $validator->errors()->add(
                    'contact_number',
                    'Contact Number must start with country code, for example +256, and must be exactly 13 characters including +.'
                );
            }
        });
    }
}