<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\Member;
use App\Services\MeetingService;
use App\Services\PermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\Middleware;

/**
 * AJAX endpoints mirroring meetings_ajax.php. The legacy endpoint only
 * allowed admin/manager roles; here we enforce the same via the
 * `meetings_manage` permission (granted to admin/manager roles).
 */
class MeetingAjaxController extends Controller
{
    protected PermissionService $permission;

    protected MeetingService $meetingService;

    public function __construct()
    {
        $this->permission = app(PermissionService::class);
        $this->meetingService = app(MeetingService::class);
    }

    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
            new Middleware('user.status'),
            new Middleware('permission:meetings_manage'),
        ];
    }

    /**
     * action=get_invites — return the current invite list for a meeting.
     */
    public function getInvites(Request $request): JsonResponse
    {
        $id = (int) $request->query('id', 0);

        if ($id <= 0) {
            return response()->json(['success' => false, 'message' => 'Invalid meeting id']);
        }

        $meeting = Meeting::query()->find($id);

        if (!$meeting) {
            return response()->json(['success' => false, 'message' => 'Meeting not found']);
        }

        $invites = $meeting->invites()
            ->with('member')
            ->orderByDesc('id')
            ->get()
            ->map(function ($invite) {
                return [
                    'id' => $invite->id,
                    // meeting_invites stores user_id, not member_id; resolve
                    // the member through the user account for the picker.
                    'member_id' => $invite->member?->id,
                    'name' => $invite->member ? $invite->member->full_name : 'Unknown',
                    'membership_id' => $invite->member->membership_id ?? '',
                    'invited' => (bool) $invite->invited,
                ];
            });

        return response()->json([
            'success' => true,
            'meeting_id' => $meeting->id,
            'invites' => $invites,
        ]);
    }

    /**
     * action=save_invites — replace the invite list for a meeting.
     */
    public function saveInvites(Request $request): JsonResponse
    {
        $meetingId = (int) $request->input('meeting_id', 0);
        $memberIds = (array) $request->input('member_ids', []);

        $meeting = Meeting::query()->find($meetingId);

        if (!$meeting) {
            return response()->json(['success' => false, 'message' => 'Meeting not found']);
        }

        $this->meetingService->saveInvites($meetingId, $memberIds);

        return response()->json([
            'success' => true,
            'message' => 'Invites saved successfully.',
        ]);
    }

    /**
     * action=get_attendance — return the attendance list for a meeting.
     */
    public function getAttendance(Request $request): JsonResponse
    {
        $id = (int) $request->query('id', 0);

        if ($id <= 0) {
            return response()->json(['success' => false, 'message' => 'Invalid meeting id']);
        }

        $meeting = Meeting::query()->find($id);

        if (!$meeting) {
            return response()->json(['success' => false, 'message' => 'Meeting not found']);
        }

        $attendance = $meeting->attendance()
            ->with('member')
            ->orderByDesc('id')
            ->get()
            ->map(function ($row) {
                return [
                    'id' => $row->id,
                    'member_id' => $row->member_id,
                    'name' => $row->member ? $row->member->full_name : 'Unknown',
                    'membership_id' => $row->member->membership_id ?? '',
                    'attended' => (bool) $row->attended,
                ];
            });

        return response()->json([
            'success' => true,
            'meeting_id' => $meeting->id,
            'attendance' => $attendance,
        ]);
    }

    /**
     * action=save_attendance — save the attendance list for a meeting.
     */
    public function saveAttendance(Request $request): JsonResponse
    {
        $meetingId = (int) $request->input('meeting_id', 0);
        $attendees = (array) $request->input('attendees', []);

        $meeting = Meeting::query()->find($meetingId);

        if (!$meeting) {
            return response()->json(['success' => false, 'message' => 'Meeting not found']);
        }

        $this->meetingService->saveAttendance($meetingId, $attendees);

        return response()->json([
            'success' => true,
            'message' => 'Attendance saved successfully.',
        ]);
    }

    /**
     * action=mark_complete — mark a meeting as completed.
     */
    public function markComplete(Request $request): JsonResponse
    {
        $meetingId = (int) $request->input('meeting_id', 0);

        $meeting = Meeting::query()->find($meetingId);

        if (!$meeting) {
            return response()->json(['success' => false, 'message' => 'Meeting not found']);
        }

        $this->meetingService->markComplete($meetingId);

        return response()->json([
            'success' => true,
            'message' => 'Meeting marked as completed.',
        ]);
    }

    /**
     * Member search for the invite/attendance pickers.
     */
    public function searchMembers(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('term', ''));

        $query = Member::query()
            ->select('id', 'membership_id', 'first_name', 'last_name', 'other_name', 'telephone1', 'email')
            ->orderByDesc('id');

        if ($term !== '') {
            $like = '%' . $term . '%';
            $query->where(function ($q) use ($like) {
                $q->where('membership_id', 'like', $like)
                    ->orWhere('first_name', 'like', $like)
                    ->orWhere('last_name', 'like', $like)
                    ->orWhere('other_name', 'like', $like)
                    ->orWhere('telephone1', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhereRaw("TRIM(CONCAT_WS(' ', COALESCE(first_name,''), COALESCE(last_name,''), COALESCE(other_name,''))) LIKE ?", [$like]);
            });
        }

        $members = $query->limit(20)->get()->map(function ($member) {
            return [
                'id' => $member->id,
                'membership_id' => $member->membership_id,
                'name' => $member->full_name,
                'telephone1' => $member->telephone1,
                'email' => $member->email,
            ];
        });

        return response()->json([
            'success' => true,
            'members' => $members,
        ]);
    }
}