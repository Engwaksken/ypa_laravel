<?php

namespace App\Http\Requests;

use App\Services\MemberService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMemberRequest extends FormRequest
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
            'other_name' => ['nullable', 'string', 'max:255'],
            'date_of_birth' => ['required', 'date'],
            'sex' => ['required', Rule::in(MemberService::SEXES)],
            'nin' => ['nullable', 'string', 'max:255'],
            'tin_number' => ['nullable', 'string', 'max:255'],
            'nationality' => ['nullable', 'string', 'max:255'],
            'address' => ['required', 'string'],
            'region' => ['nullable', Rule::in(MemberService::REGIONS)],
            'district_residence' => ['nullable', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:255'],
            'employment_status' => ['required', Rule::in(MemberService::EMPLOYMENT_STATUSES)],
            'employment_other' => ['nullable', 'string', 'max:255'],
            'marital_status' => ['required', Rule::in(MemberService::MARITAL_STATUSES)],
            'children_count' => ['nullable', 'integer', 'min:0'],
            'source' => ['required', Rule::in(MemberService::SOURCES)],
            'source_station' => ['nullable', 'string', 'max:255'],
            'source_other' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'telephone1' => ['required', 'string'],
            'telephone2' => ['nullable', 'string'],
            'mother_name' => ['nullable', 'string', 'max:255'],
            'mother_phone' => ['nullable', 'string'],
            'father_name' => ['nullable', 'string', 'max:255'],
            'father_phone' => ['nullable', 'string'],
            'account_type' => ['nullable', Rule::in(MemberService::ACCOUNT_TYPES)],
            'bank_account' => ['nullable', 'string', 'max:255'],
            'bank_account_name' => ['nullable', 'string', 'max:255'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_branch' => ['nullable', 'string', 'max:255'],
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'mobilizer_id' => ['required', 'integer', 'exists:mobilizers,id'],
            'membership_status' => ['nullable', Rule::in(MemberService::MEMBERSHIP_STATUSES)],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $service = app(MemberService::class);
            $data = $this->all();
            $memberId = (int) $this->route('member');

            foreach (['telephone1' => 'Phone 1', 'telephone2' => 'Phone 2', 'mother_phone' => 'Mother phone', 'father_phone' => 'Father phone'] as $field => $label) {
                $value = (string) ($data[$field] ?? '');
                $required = $field === 'telephone1';
                if (!$service->validPhone($value, $required)) {
                    if ($required || $value !== '') {
                        $validator->errors()->add(
                            $field,
                            "{$label} must start with country code, for example +256, and must be exactly 13 characters including +."
                        );
                    }
                }
            }

            $nationality = strtolower(trim((string) ($data['nationality'] ?? '')));
            $isUganda = $nationality === '' || $nationality === 'uganda';
            if ($isUganda) {
                foreach (['region' => 'Region', 'district_residence' => 'District of Residence', 'district' => 'Home District'] as $field => $label) {
                    if (trim((string) ($data[$field] ?? '')) === '') {
                        $validator->errors()->add($field, "{$label} is required for Uganda.");
                    }
                }
            }

            if (($data['employment_status'] ?? '') === 'Other' && trim((string) ($data['employment_other'] ?? '')) === '') {
                $validator->errors()->add('employment_other', 'Specify Employment is required when Employment Status is Other.');
            }

            if (($data['source'] ?? '') === 'Other' && trim((string) ($data['source_other'] ?? '')) === '') {
                $validator->errors()->add('source_other', 'Specify Source is required when Source is Other.');
            }

            $source = $service->normalizeSource((string) ($data['source'] ?? ''));
            $station = trim((string) ($data['source_station'] ?? ''));
            if ($source === 'Radio' && ($station === '' || !in_array($station, MemberService::RADIO_STATIONS, true))) {
                $validator->errors()->add('source_station', 'Select a valid radio station.');
            }
            if ($source === 'TV' && ($station === '' || !in_array($station, MemberService::TV_STATIONS, true))) {
                $validator->errors()->add('source_station', 'Select a valid TV station.');
            }

            $email = trim((string) ($data['email'] ?? ''));
            if ($email !== '' && $service->uniqueExists('email', $email, $memberId)) {
                $validator->errors()->add('email', 'A member with this email already exists.');
            }
            $phone1 = $service->normalizePhone((string) ($data['telephone1'] ?? ''));
            if ($phone1 !== '' && $service->uniqueExists('telephone1', $phone1, $memberId)) {
                $validator->errors()->add('telephone1', 'A member with this Phone 1 already exists.');
            }
            $nin = trim((string) ($data['nin'] ?? ''));
            if ($nin !== '' && $service->uniqueExists('nin', $nin, $memberId)) {
                $validator->errors()->add('nin', 'A member with this NIN already exists.');
            }
        });
    }
}
