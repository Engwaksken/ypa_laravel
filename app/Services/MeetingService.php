<?php

namespace App\Services;

use App\Models\Meeting;
use App\Models\MeetingAttendance;
use App\Models\MeetingInvite;
use App\Models\Member;
use Illuminate\Support\Facades\DB;

/**
 * Faithful port of the legacy meeting business rules from
 * meetings.php / meetings_ajax.php / process_meeting.php:
 *  - meeting create/update payload (real `meetings` columns)
 *  - attendance save (action=save_attendance)
 *  - invite management (action=get_invites / save_invites)
 *  - mark_complete workflow
 */
class MeetingService
{
    public const STATUSES = ['Scheduled', 'Ongoing', 'Completed', 'Cancelled'];

    public const MEETING_TYPES = ['Monthly', 'Quarterly', 'Annual'];

    /**
     * Convert an empty string to null (for nullable columns / enum safety).
     */
    public function nullIfEmpty(string $value): ?string
    {
        $value = trim($value);

        return $value === '' ? null : $value;
    }

    /**
     * Build the meeting payload for create/update against the real `meetings`
     * columns. `created_by` is intentionally NOT set here — the controller
     * sets it on store only, so an update never overwrites the original
     * creator. `attendance_count` is also left alone (DB default 0 on create;
     * updates must not reset it).
     */
    public function buildMeetingPayload(array $data): array
    {
        $status = trim((string) ($data['status'] ?? 'Scheduled')) ?: 'Scheduled';

        return [
            'meeting_type' => trim((string) ($data['meeting_type'] ?? 'Monthly')) ?: 'Monthly',
            'meeting_title' => trim((string) ($data['meeting_title'] ?? '')),
            'meeting_date' => $this->nullIfEmpty((string) ($data['meeting_date'] ?? '')),
            'meeting_time' => $this->nullIfEmpty((string) ($data['meeting_time'] ?? '')),
            'location' => $this->nullIfEmpty((string) ($data['location'] ?? '')),
            'agenda' => $this->nullIfEmpty((string) ($data['agenda'] ?? '')),
            // Legacy behaviour: minutes are only stored once a meeting is
            // completed (meetings_ajax.php handleAdd).
            'minutes' => $status === 'Completed'
                ? $this->nullIfEmpty((string) ($data['minutes'] ?? ''))
                : null,
            'status' => $status,
            'chaired_by' => $this->nullIfEmpty((string) ($data['chaired_by'] ?? '')),
            'notes' => $this->nullIfEmpty((string) ($data['notes'] ?? '')),
        ];
    }

    /**
     * Save attendance for a meeting. $attendeeIds is the list of member ids
     * marked as attended; any existing attendance rows for members not in the
     * list are marked not-attended (legacy save_attendance behaviour).
     *
     * The meeting_attendance schema has no `attended` boolean — attendance is
     * a status enum ('Present'/'Absent'/'Apology'/'Late'), so attended members
     * are written as 'Present' and removed members as 'Absent'.
     */
    public function saveAttendance(int $meetingId, array $attendeeIds): void
    {
        $attendeeIds = array_values(array_unique(array_map('intval', $attendeeIds)));
        $attendeeIds = array_filter($attendeeIds, fn ($id) => $id > 0);

        DB::transaction(function () use ($meetingId, $attendeeIds): void {
            // Upsert attendance for the attended members.
            foreach ($attendeeIds as $memberId) {
                MeetingAttendance::updateOrCreate(
                    ['meeting_id' => $meetingId, 'member_id' => $memberId],
                    ['status' => 'Present']
                );
            }

            // Mark previously-attended members who are no longer in the list.
            // Flip EVERY non-Absent status (Present, Late, Apology) to Absent
            // so unchecked members never still count as attended.
            $query = MeetingAttendance::query()
                ->where('meeting_id', $meetingId)
                ->where('status', '!=', 'Absent');

            if ($attendeeIds !== []) {
                $query->whereNotIn('member_id', $attendeeIds);
            }

            $query->update(['status' => 'Absent']);

            // Keep meetings.attendance_count in sync with the saved attendee
            // list (legacy process_meeting.php behaviour).
            Meeting::query()
                ->where('id', $meetingId)
                ->update(['attendance_count' => count($attendeeIds)]);
        });
    }

    /**
     * Replace the invite list for a meeting. $memberIds is the full list of
     * invited member ids (legacy save_invites behaviour).
     *
     * The meeting_invites schema has no member_id column: user_id (NOT NULL)
     * references the member's user account and invite_status is the enum.
     * Members without a user account cannot be invited under this schema and
     * are skipped.
     */
    public function saveInvites(int $meetingId, array $memberIds): void
    {
        $memberIds = array_values(array_unique(array_map('intval', $memberIds)));
        $memberIds = array_filter($memberIds, fn ($id) => $id > 0);

        DB::transaction(function () use ($meetingId, $memberIds): void {
            MeetingInvite::query()->where('meeting_id', $meetingId)->delete();

            $userIds = Member::query()
                ->whereIn('id', $memberIds)
                ->whereNotNull('user_id')
                ->pluck('user_id', 'id');

            foreach ($userIds as $memberId => $userId) {
                MeetingInvite::create([
                    'meeting_id' => $meetingId,
                    'user_id' => $userId,
                    'invite_status' => 'Pending',
                ]);
            }
        });
    }

    /**
     * Mark a meeting as completed (legacy mark_complete action).
     */
    public function markComplete(int $meetingId): void
    {
        Meeting::query()->where('id', $meetingId)->update(['status' => 'Completed']);
    }
}