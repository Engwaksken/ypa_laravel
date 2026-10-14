<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProjectRequest extends FormRequest
{
    public const STATUSES = ['Planning', 'Active', 'On Hold', 'Completed', 'Cancelled'];

    public function authorize(): bool { return true; }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('calculation_schema'))) {
            $decoded = json_decode($this->input('calculation_schema'), true);
            if (is_array($decoded)) $this->merge(['calculation_schema' => $decoded]);
        }
    }

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
            'calculation_schema' => ['sometimes', 'nullable', 'array:inputs,formulas', function ($attribute, $value, $fail) {
                if (! is_array($value)) return;
                try {
                    app(\App\Services\ProjectCalculationService::class)->validateSchema($value);
                } catch (\InvalidArgumentException $e) {
                    $fail($e->getMessage());
                } catch (\Illuminate\Validation\ValidationException $e) {
                    foreach ($e->errors() as $messages) foreach ($messages as $message) $fail($message);
                }
            }],
        ];
    }
}
