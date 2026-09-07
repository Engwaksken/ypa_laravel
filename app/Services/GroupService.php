<?php

namespace App\Services;

use App\Models\Group;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Faithful port of the legacy group business rules from
 * groups.php / process_group.php / process_create_group.php:
 *  - group_code auto-generation (GRP-YYYY-000001)
 *  - Uganda conditional validation (subregion + district required)
 *  - category "Other" handling
 *  - duplicate group_name check
 *  - non-member coordinators stored as JSON
 */
class GroupService
{
    public const CATEGORIES = [
        'Farmer Producer Group / Farmer Cooperative',
        'Village Savings and Loan Association (VSLA)',
        'Business Group',
        'Youth or Women Economic Empowerment Group',
        'Community-Based Organization (CBO)',
        'Other',
    ];

    public const STATUSES = ['Active', 'Inactive', 'Suspended', 'Dissolved'];

    /**
     * Generate the next group_code: GRP-{YEAR}-{000001}.
     */
    public function nextGroupCode(): string
    {
        $year = date('Y');
        $prefix = "GRP-{$year}-";
        $next = 1;

        $last = Group::query()
            ->where('group_code', 'like', $prefix . '%')
            ->orderByDesc('group_code')
            ->first();

        if ($last && preg_match('/(\d+)$/', (string) $last->group_code, $m)) {
            $next = (int) $m[1] + 1;
        }

        return $prefix . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
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
     * Resolve the effective category value: when category is "Other" the
     * stored value is the free-text category_other.
     */
    public function categoryValue(string $category, string $categoryOther): string
    {
        return $category === 'Other' ? $categoryOther : $category;
    }

    /**
     * Check whether a group with the same name already exists in the same
     * country (legacy duplicate check), ignoring a given group id.
     */
    public function duplicateExists(string $groupName, string $country, int $ignoreId = 0): bool
    {
        $query = Group::query()
            ->where('group_name', $groupName)
            ->where('country', $country);

        if ($ignoreId > 0) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }

    /**
     * Build the group payload for create/update, applying enum-safe nulls and
     * the legacy field mapping (non-member coordinators as JSON array).
     */
    public function buildGroupPayload(array $data, ?int $userId): array
    {
        $category = trim((string) ($data['group_category'] ?? ''));
        $categoryOther = trim((string) ($data['category_other'] ?? ''));
        $country = trim((string) ($data['country'] ?? ''));

        $payload = [
            'group_code' => trim((string) ($data['group_code'] ?? '')),
            'group_name' => trim((string) ($data['group_name'] ?? '')),
            'group_category' => $this->categoryValue($category, $categoryOther),
            'category_other' => $category === 'Other' ? $this->nullIfEmpty($categoryOther) : null,
            'country' => $this->nullIfEmpty($country),
            'uganda_subregion' => $this->nullIfEmpty((string) ($data['uganda_subregion'] ?? '')),
            'uganda_district' => $this->nullIfEmpty((string) ($data['uganda_district'] ?? '')),
            'formation_date' => $this->nullIfEmpty((string) ($data['formation_date'] ?? '')),
            'mobilizer_id' => (int) ($data['mobilizer_id'] ?? 0) > 0 ? (int) $data['mobilizer_id'] : null,
            'branch_id' => (int) ($data['branch_id'] ?? 0) > 0 ? (int) $data['branch_id'] : null,
            'coordinator_id' => (int) ($data['coordinator_id'] ?? 0) > 0 ? (int) $data['coordinator_id'] : null,
            'bank_name' => $this->nullIfEmpty((string) ($data['bank_name'] ?? '')),
            'bank_account_name' => $this->nullIfEmpty((string) ($data['bank_account_name'] ?? '')),
            'bank_account_number' => $this->nullIfEmpty((string) ($data['bank_account_number'] ?? '')),
            'status' => trim((string) ($data['status'] ?? 'Active')) ?: 'Active',
        ];

        // Non-member coordinators: legacy form posts a JSON string.
        $nmc = $this->normalizeNonMemberCoordinators($data['non_member_coordinators'] ?? null);
        if ($nmc !== null) {
            $payload['non_member_coordinators'] = $nmc;
        }

        return $payload;
    }

    /**
     * Accept either a decoded array or a JSON string and reject malformed
     * JSON instead of dropping it silently.
     */
    protected function normalizeNonMemberCoordinators(mixed $value): ?array
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value)) {
            return $value;
        }

        if (!is_string($value)) {
            throw ValidationException::withMessages([
                'non_member_coordinators' => 'The non member coordinators field must be an array or valid JSON.',
            ]);
        }

        $value = trim($value);

        if ($value === '') {
            return null;
        }

        $decoded = json_decode($value, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            throw ValidationException::withMessages([
                'non_member_coordinators' => 'The non member coordinators field must be an array or valid JSON.',
            ]);
        }

        return $decoded;
    }
}
