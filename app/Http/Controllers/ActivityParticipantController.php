<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivityParticipant;
use App\Services\ActivityService;
use App\Services\PermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ActivityParticipantController extends Controller
{
    protected PermissionService $permission;

    protected ActivityService $activityService;

    public function __construct()
    {
        $this->permission = app(PermissionService::class);
        $this->activityService = app(ActivityService::class);
    }

    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
            new Middleware('user.status'),
            (new Middleware('permission:activities'))->only(['index']),
            (new Middleware('permission:activities_edit'))->only(['store', 'updateAttendance', 'destroy', 'searchMembers']),
        ];
    }

    /**
     * Participant list for an activity (mirrors activity_participants.php).
     */
    public function index(Request $request, Activity $activity): View
    {
        $search = trim((string) $request->query('search', ''));

        $query = $activity->participants()
            ->with('member')
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if ($search !== '') {
            $like = '%' . $search . '%';
            $query->whereHas('member', function ($q) use ($like) {
                $q->where('membership_id', 'like', $like)
                    ->orWhere('first_name', 'like', $like)
                    ->orWhere('last_name', 'like', $like)
                    ->orWhere('other_name', 'like', $like)
                    ->orWhere('telephone1', 'like', $like)
                    ->orWhereRaw("TRIM(CONCAT_WS(' ', COALESCE(first_name,''), COALESCE(last_name,''), COALESCE(other_name,''))) LIKE ?", [$like]);
            });
        }

        $participants = $query->paginate(25)->withQueryString();

        return view('activities.participants', compact('activity', 'participants', 'search'));
    }

    /**
     * Register a member for an activity (mirrors process_participants.php add).
     */
    public function store(Request $request, Activity $activity): RedirectResponse
    {
        $request->validate([
            'member_id' => ['required', 'integer', 'exists:members,id'],
            'participant_type' => ['nullable', 'string', 'max:255', Rule::in(ActivityService::PARTICIPANT_TYPES)],
        ]);

        $participant = $this->activityService->registerMember(
            $activity->id,
            (int) $request->input('member_id'),
            trim((string) $request->input('participant_type', 'Member')) ?: 'Member'
        );

        return redirect()
            ->route('activities.participants', $activity)
            ->with('success', 'Participant registered successfully.');
    }

    /**
     * Update attendance for a participant (mirrors process_participants.php).
     */
    public function updateAttendance(Request $request, Activity $activity, ActivityParticipant $participant): RedirectResponse
    {
        $this->ensureParticipantBelongsToActivity($activity, $participant);

        $request->validate([
            'attended' => ['nullable', 'boolean'],
        ]);

        $participant->update([
            'attended' => $request->boolean('attended'),
        ]);

        return redirect()
            ->route('activities.participants', $activity)
            ->with('success', 'Attendance updated successfully.');
    }

    /**
     * Remove a participant from an activity.
     */
    public function destroy(Activity $activity, ActivityParticipant $participant): RedirectResponse
    {
        $this->ensureParticipantBelongsToActivity($activity, $participant);

        $participant->delete();

        return redirect()
            ->route('activities.participants', $activity)
            ->with('success', 'Participant removed successfully.');
    }

    /**
     * AJAX member search for the participant picker (mirrors
     * activity-registration.php ?ajax=search_member).
     */
    public function searchMembers(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('term', ''));

        return response()->json([
            'success' => true,
            'members' => $this->activityService->searchMembers($term),
        ]);
    }

    protected function ensureParticipantBelongsToActivity(Activity $activity, ActivityParticipant $participant): void
    {
        if ((int) $participant->activity_id !== (int) $activity->id) {
            abort(404);
        }
    }
}
