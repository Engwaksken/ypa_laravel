<?php

namespace App\Http\Requests;

use App\Services\GroupService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'group_name' => ['required', 'string', 'max:255'],
            'group_category' => ['required', Rule::in(GroupService::CATEGORIES)],
            'category_other' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'],
            'uganda_subregion' => ['nullable', 'string', 'max:255'],
            'uganda_district' => ['nullable', 'string', 'max:255'],
            'formation_date' => ['required', 'date'],
            'mobilizer_id' => ['required', 'integer', 'exists:mobilizers,id'],
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'coordinator_id' => ['nullable', 'integer', 'exists:users,id'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_account_name' => ['nullable', 'string', 'max:255'],
            'bank_account_number' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(GroupService::STATUSES)],
            'non_member_coordinators' => ['nullable', 'array'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (!$this->has('non_member_coordinators')) {
            return;
        }

        $value = $this->input('non_member_coordinators');

        if (is_string($value)) {
            $trimmed = trim($value);

            if ($trimmed === '') {
                $this->merge(['non_member_coordinators' => null]);
                return;
            }

            $decoded = json_decode($trimmed, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                $this->merge(['non_member_coordinators' => $decoded]);
            }
        }
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $service = app(GroupService::class);
            $data = $this->all();

            $country = strtolower(trim((string) ($data['country'] ?? '')));
            if ($country === 'uganda') {
                foreach (['uganda_subregion' => 'Sub-region', 'uganda_district' => 'District'] as $field => $label) {
                    if (trim((string) ($data[$field] ?? '')) === '') {
                        $validator->errors()->add($field, "{$label} is required for Uganda.");
                    }
                }
            }

            if (($data['group_category'] ?? '') === 'Other' && trim((string) ($data['category_other'] ?? '')) === '') {
                $validator->errors()->add('category_other', 'Specify Category is required when Group Category is Other.');
            }

            $groupName = trim((string) ($data['group_name'] ?? ''));
            if ($groupName !== '' && $service->duplicateExists($groupName, trim((string) ($data['country'] ?? '')))) {
                $validator->errors()->add('group_name', 'A group with this name already exists in the selected country.');
            }
        });
    }
}
