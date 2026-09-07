<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\ActivityParticipant;
use App\Models\Member;

/**
 * Faithful port of the legacy activity business rules from
 * activities.php / process_activities.php / export_activities.php:
 *  - activity_code auto-generation (ACT-YYYY-000001)
 *  - is_promotion / status enums
 *  - participant registration + attendance
 *  - gender normalisation for external registrations
 */
class ActivityService
{
    public const STATUSES = ['Planned', 'Ongoing', 'Completed', 'Cancelled'];

    public const IS_PROMOTION = ['No', 'Yes'];

    public const PARTICIPANT_TYPES = ['Member', 'Non-Member', 'External'];

    /**
     * Generate the next activity_code: ACT-{YEAR}-{000001}.
     */
    public function nextActivityCode(): string
    {
        $year = date('Y');
        $prefix = "ACT-{$year}-";
        $next = 1;

        $last = Activity::query()
            ->where('activity_code', 'like', $prefix . '%')
            ->orderByDesc('activity_code')
            ->first();

        if ($last && preg_match('/(\d+)$/', (string) $last->activity_code, $m)) {
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
     * Normalise a gender value: m/male -> Male, f/female -> Female,
     * other -> Other, anything else passes through unchanged.
     */
    public function normalizeGender(string $gender): string
    {
        $gender = trim($gender);
        $lower = strtolower($gender);

        return match ($lower) {
            'm', 'male' => 'Male',
            'f', 'female' => 'Female',
            'other' => 'Other',
            default => $gender,
        };
    }

    /**
     * Build the activity payload for create/update.
     */
    public function buildActivityPayload(array $data, ?int $userId): array
    {
        return [
            'activity_code' => trim((string) ($data['activity_code'] ?? '')),
            'activity_name' => trim((string) ($data['activity_name'] ?? '')),
            'activity_type_id' => (int) ($data['activity_type_id'] ?? 0) > 0 ? (int) $data['activity_type_id'] : null,
            'description' => $this->nullIfEmpty((string) ($data['description'] ?? '')),
            'start_date' => $this->nullIfEmpty((string) ($data['start_date'] ?? '')),
            'end_date' => $this->nullIfEmpty((string) ($data['end_date'] ?? '')),
            'location' => $this->nullIfEmpty((string) ($data['location'] ?? '')),
            'budget' => isset($data['budget']) && trim((string) $data['budget']) !== ''
                ? (float) str_replace(',', '', (string) $data['budget'])
                : null,
            'is_promotion' => trim((string) ($data['is_promotion'] ?? 'No')) ?: 'No',
            'status' => trim((string) ($data['status'] ?? 'Planned')) ?: 'Planned',
            'created_by' => $userId,
        ];
    }

    /**
     * Register a member for an activity (idempotent).
     */
    public function registerMember(int $activityId, int $memberId, string $participantType = 'Member'): ActivityParticipant
    {
        $existing = ActivityParticipant::query()
            ->where('activity_id', $activityId)
            ->where('member_id', $memberId)
            ->first();

        if ($existing) {
            return $existing;
        }

        return ActivityParticipant::create([
            'activity_id' => $activityId,
            'member_id' => $memberId,
            'participant_type' => $participantType,
            'attended' => false,
        ]);
    }

    /**
     * Search members for the AJAX member picker (legacy ?ajax=search_member).
     */
    public function searchMembers(string $term, int $limit = 10): array
    {
        $term = trim($term);
        $query = Member::query()
            ->select('id', 'membership_id', 'first_name', 'last_name', 'other_name', 'telephone1', 'email')
            ->orderByDesc('id');

        if ($term !== '') {
            $like = '%' . $term . '%';
            $query->where(function ($q) use ($like, $term) {
                $q->where('membership_id', 'like', $like)
                    ->orWhere('first_name', 'like', $like)
                    ->orWhere('last_name', 'like', $like)
                    ->orWhere('other_name', 'like', $like)
                    ->orWhere('telephone1', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhereRaw("TRIM(CONCAT_WS(' ', COALESCE(first_name,''), COALESCE(last_name,''), COALESCE(other_name,''))) LIKE ?", [$like]);
            });
        }

        return $query->limit($limit)->get()->map(function ($member) {
            return [
                'id' => $member->id,
                'membership_id' => $member->membership_id,
                'name' => trim(($member->first_name ?? '') . ' ' . ($member->last_name ?? '') . (!empty($member->other_name) ? ' ' . $member->other_name : '')),
                'telephone1' => $member->telephone1,
                'email' => $member->email,
            ];
        })->all();
    }
}