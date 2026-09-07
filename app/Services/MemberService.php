<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Member;

/**
 * Faithful port of the legacy member business rules from
 * process_register_member.php / members.php:
 *  - phone normalisation (+256... exactly 13 chars)
 *  - membership_id auto-generation (BRANCH-YYYY-000001)
 *  - enum-violating empty strings treated as null
 *  - source / employment "Other" handling
 */
class MemberService
{
    public const RADIO_STATIONS = [
        'Impact FM',
        'Prime FM',
        'Pearl FM',
        'Centenary Radio-Masaka',
        'Radio8-Masaka',
        'Top Radio',
    ];

    public const TV_STATIONS = ['BBS', 'U24', 'Sanyuka', 'NTV'];

    public const SOURCES = [
        'Radio', 'TV', 'Social Media', 'YPA Website', 'Outreaches',
        'Exhibitions', 'Personal', 'Referral', 'Other',
    ];

    public const EMPLOYMENT_STATUSES = [
        'Employed (Full-time)',
        'Employed (Part-time)',
        'Self-employed',
        'Casual / Temporary worker',
        'Contract employee',
        'Unemployed',
        'Student',
        'Retired',
        'Farmer / Agribusiness operator',
        'Business owner / Entrepreneur',
        'Informal sector worker',
        'Other',
    ];

    public const MARITAL_STATUSES = ['Single', 'Married', 'Divorced', 'Separated'];

    public const ACCOUNT_TYPES = ['Personal', 'Joint', 'Group', 'Infant'];

    public const REGIONS = ['Central', 'Eastern', 'Western', 'Northern'];

    public const MEMBERSHIP_STATUSES = ['Active', 'Pending', 'Suspended', 'Expired', 'Inactive'];

    public const SEXES = ['Male', 'Female', 'Other'];

    /**
     * Normalise a phone number: strip whitespace and non-digit/+ chars, and
     * prefix a leading "256" with "+".
     */
    public function normalizePhone(string $phone): string
    {
        $phone = trim($phone);
        $phone = preg_replace('/\s+/', '', $phone) ?? '';
        $phone = preg_replace('/[^\d+]/', '', $phone) ?? '';

        if (str_starts_with($phone, '256')) {
            $phone = '+' . $phone;
        }

        return $phone;
    }

    /**
     * Validate a phone number. When $required is false an empty value passes.
     */
    public function validPhone(string $phone, bool $required = true): bool
    {
        $phone = $this->normalizePhone($phone);

        if ($phone === '') {
            return !$required;
        }

        return (bool) preg_match('/^\+[0-9]{12}$/', $phone);
    }

    /**
     * Normalise and validate a phone, returning the normalised value.
     * Throws a RuntimeException with the legacy message when invalid.
     */
    public function requirePhone(string $phone, string $label, bool $required = true): string
    {
        $phone = $this->normalizePhone($phone);

        if (!$this->validPhone($phone, $required)) {
            if ($required || $phone !== '') {
                throw new \RuntimeException(
                    "{$label} must start with country code, for example +256, and must be exactly 13 characters including +."
                );
            }
        }

        return $phone;
    }

    /**
     * Derive a 3-letter branch prefix from the branch name (legacy
     * prm_branch_prefix). Falls back to "MEM".
     */
    public function branchPrefix(?int $branchId): string
    {
        $prefix = 'MEM';

        if ($branchId === null || $branchId <= 0) {
            return $prefix;
        }

        $branch = Branch::query()->where('id', $branchId)->first();

        if (!$branch) {
            return $prefix;
        }

        $name = strtoupper((string) ($branch->name ?? ''));
        $name = preg_replace('/[^A-Z]/', '', $name) ?? '';

        if (strlen($name) >= 3) {
            return substr($name, 0, 3);
        }

        if ($name !== '') {
            return str_pad($name, 3, 'X');
        }

        return $prefix;
    }

    /**
     * Generate the next membership_id for a branch: {PREFIX}-{YEAR}-{000001}.
     */
    public function nextMembershipId(?int $branchId): string
    {
        $year = date('Y');
        $branchPrefix = $this->branchPrefix($branchId);
        $prefix = "{$branchPrefix}-{$year}-";
        $next = 1;

        $last = Member::query()
            ->where('membership_id', 'like', $prefix . '%')
            ->orderByRaw('CAST(SUBSTRING_INDEX(membership_id, "-", -1) AS UNSIGNED) DESC')
            ->first();

        if ($last && preg_match('/(\d+)$/', (string) $last->membership_id, $m)) {
            $next = (int) $m[1] + 1;
        }

        return $prefix . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Normalise a source value: "Television" becomes "TV".
     */
    public function normalizeSource(string $source): string
    {
        $source = trim($source);

        return strcasecmp($source, 'Television') === 0 ? 'TV' : $source;
    }

    /**
     * Convert an empty string to null (for nullable columns / enum safety).
     */
    public function nullIfEmpty(string $value): ?string
    {
        $value = trim($value);

        return $value === '' ? null : $value;
    }

    /**
     * Resolve the effective employment value: when status is "Other" the
     * stored value is the free-text employment_other.
     */
    public function employmentValue(string $status, string $other): string
    {
        return $status === 'Other' ? $other : $status;
    }

    /**
     * Resolve the effective source value. When there is no dedicated station
     * column the legacy code stored "Source - Station" in `source`; here we
     * always have the station columns so we store the base source.
     */
    public function sourceValue(string $source, string $sourceOther): string
    {
        return $source === 'Other' ? $sourceOther : $source;
    }

    /**
     * Build the member payload for create/update, applying enum-safe nulls
     * and the legacy field mapping (including duplicate columns like
     * gender/tin/tax_identification_number that mirror the primary column).
     */
    public function buildMemberPayload(array $data, ?int $branchId, ?int $mobilizerId, ?int $userId): array
    {
        $sex = trim((string) ($data['sex'] ?? ''));
        $tinNumber = trim((string) ($data['tin_number'] ?? ''))
            ?: trim((string) ($data['tin'] ?? ''))
            ?: trim((string) ($data['tax_identification_number'] ?? ''));

        $source = $this->normalizeSource((string) ($data['source'] ?? ''));
        $sourceStation = trim((string) ($data['source_station'] ?? ''));
        $sourceOther = trim((string) ($data['source_other'] ?? ''));

        $employmentStatus = trim((string) ($data['employment_status'] ?? ''));
        $employmentOther = trim((string) ($data['employment_other'] ?? ''));

        $payload = [
            'first_name' => trim((string) ($data['first_name'] ?? '')),
            'last_name' => trim((string) ($data['last_name'] ?? '')),
            'other_name' => $this->nullIfEmpty((string) ($data['other_name'] ?? '')),
            'date_of_birth' => $this->nullIfEmpty((string) ($data['date_of_birth'] ?? '')),
            'sex' => $sex !== '' ? $sex : null,
            'gender' => $sex !== '' ? $sex : null,
            'nin' => $this->nullIfEmpty((string) ($data['nin'] ?? '')),
            'tin_number' => $this->nullIfEmpty($tinNumber),
            'tin' => $this->nullIfEmpty($tinNumber),
            'tax_identification_number' => $this->nullIfEmpty($tinNumber),
            'nationality' => $this->nullIfEmpty((string) ($data['nationality'] ?? '')),
            'address' => $this->nullIfEmpty((string) ($data['address'] ?? '')),
            'region' => $this->nullIfEmpty((string) ($data['region'] ?? '')),
            'district_residence' => $this->nullIfEmpty((string) ($data['district_residence'] ?? '')),
            'district' => $this->nullIfEmpty((string) ($data['district'] ?? '')),
            'employment_status' => $this->employmentValue($employmentStatus, $employmentOther),
            'employment_other' => $this->nullIfEmpty($employmentOther),
            'marital_status' => $this->nullIfEmpty((string) ($data['marital_status'] ?? '')),
            'children_count' => max(0, (int) ($data['children_count'] ?? 0)),
            'source' => $this->sourceValue($source, $sourceOther),
            'source_type' => $source,
            'source_station' => $this->nullIfEmpty($sourceStation),
            'radio_station' => $source === 'Radio' ? $this->nullIfEmpty($sourceStation) : null,
            'tv_station' => $source === 'TV' ? $this->nullIfEmpty($sourceStation) : null,
            'source_other' => $source === 'Other' ? $sourceOther : null,
            'email' => $this->nullIfEmpty((string) ($data['email'] ?? '')),
            'telephone1' => $this->normalizePhone((string) ($data['telephone1'] ?? '')),
            'telephone2' => $this->normalizePhone((string) ($data['telephone2'] ?? '')),
            'mother_name' => $this->nullIfEmpty((string) ($data['mother_name'] ?? '')),
            'mother_phone' => $this->normalizePhone((string) ($data['mother_phone'] ?? '')),
            'father_name' => $this->nullIfEmpty((string) ($data['father_name'] ?? '')),
            'father_phone' => $this->normalizePhone((string) ($data['father_phone'] ?? '')),
            'account_type' => $this->nullIfEmpty((string) ($data['account_type'] ?? '')),
            'bank_account' => $this->nullIfEmpty((string) ($data['bank_account'] ?? '')),
            'bank_account_name' => $this->nullIfEmpty((string) ($data['bank_account_name'] ?? '')),
            'bank_name' => $this->nullIfEmpty((string) ($data['bank_name'] ?? '')),
            'bank_branch' => $this->nullIfEmpty((string) ($data['bank_branch'] ?? '')),
            'branch_id' => $branchId,
            'mobilizer_id' => $mobilizerId,
            'membership_status' => trim((string) ($data['membership_status'] ?? 'Pending')) ?: 'Pending',
            'user_id' => $userId,
        ];

        return $payload;
    }

    /**
     * Check whether a value already exists in a unique member column,
     * ignoring a given member id (for updates).
     */
    public function uniqueExists(string $column, string $value, int $ignoreId = 0): bool
    {
        if ($value === '') {
            return false;
        }

        $query = Member::query()->where($column, $value);

        if ($ignoreId > 0) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }
}
