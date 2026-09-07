<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreContractTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'template_name' => ['required', 'string', 'max:255'],
            'project_type_id' => ['nullable', 'integer', 'exists:project_types,id'],
            'project_category_id' => ['nullable', 'integer', 'exists:project_categories,id'],
            'template_key' => ['nullable', 'string', 'max:255'],
            'cover_page' => ['nullable', 'string'],
            'template_body' => ['required', 'string'],
            'contract_footer' => ['nullable', 'string'],
            'contract_signature' => ['nullable', 'string'],
            'template_sections' => ['nullable'],
            'version' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
