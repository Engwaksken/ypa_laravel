<?php

namespace App\Http\Requests;

use App\Services\ActivityService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'activity_name' => ['required', 'string', 'max:255'],
            'activity_type_id' => ['required', 'integer', 'exists:activity_types,id'],
            'description' => ['nullable', 'string'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'location' => ['nullable', 'string', 'max:255'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'is_promotion' => ['nullable', Rule::in(ActivityService::IS_PROMOTION)],
            'status' => ['nullable', Rule::in(ActivityService::STATUSES)],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $data = $this->all();

            $start = trim((string) ($data['start_date'] ?? ''));
            $end = trim((string) ($data['end_date'] ?? ''));
            if ($start !== '' && $end !== '' && strtotime($end) < strtotime($start)) {
                $validator->errors()->add('end_date', 'End date cannot be before the start date.');
            }
        });
    }
}