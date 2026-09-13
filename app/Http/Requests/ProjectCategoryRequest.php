<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProjectCategoryRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'category_name' => [
                'required', 'string', 'max:191',
                Rule::unique('project_categories', 'category_name')->ignore($this->route('project_category')),
            ],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'integer', Rule::in([0, 1])],
        ];
    }
}