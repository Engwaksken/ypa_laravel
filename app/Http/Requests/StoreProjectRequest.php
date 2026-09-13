<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreProjectRequest extends FormRequest
{
    public const STATUSES = ['Planning', 'Active', 'On Hold', 'Completed', 'Cancelled'];

    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'project_category_id' => ['required', 'integer', 'exists:project_categories,id'],
            'project_name' => ['required', 'string', 'max:191'],
            'project_code' => ['required', 'string', 'max:100', Rule::unique('projects', 'project_code')],
            'description' => ['required', 'string'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'registration_fee' => ['required', 'numeric', 'min:0'],
            'administrative_fee' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(self::STATUSES)],
        ];
    }
}