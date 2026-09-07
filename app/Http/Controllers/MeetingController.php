<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMeetingRequest;
use App\Http\Requests\UpdateMeetingRequest;
use App\Models\Meeting;
use App\Services\MeetingService;
use App\Services\PermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class MeetingController extends Controller
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
            (new Middleware('permission:meetings'))->only(['index', 'show']),
            (new Middleware('permission:meetings_create'))->only(['create', 'store']),
            (new Middleware('permission:meetings_edit'))->only(['edit', 'update']),
            (new Middleware('permission:meetings_delete'))->only(['destroy']),
            (new Middleware('throttle:30,1'))->only(['store', 'update', 'destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $statusFilter = trim((string) $request->query('status', ''));
        $perPage = max(10, min(100, (int) $request->query('per_page', 25)));

        $query = Meeting::query()
            ->orderByDesc('meeting_date')
            ->orderByDesc('id');

        if ($search !== '') {
            $like = '%' . $search . '%';
            $query->where(function ($q) use ($like) {
                $q->where('meeting_title', 'like', $like)
                    ->orWhere('location', 'like', $like)
                    ->orWhere('agenda', 'like', $like);
            });
        }

        if ($statusFilter !== '') {
            $query->where('status', $statusFilter);
        }

        $meetings = $query->paginate($perPage)->withQueryString();

        $kpi = [
            'total' => Meeting::count(),
            'scheduled' => Meeting::where('status', 'Scheduled')->count(),
            'ongoing' => Meeting::where('status', 'Ongoing')->count(),
            'completed' => Meeting::where('status', 'Completed')->count(),
            'cancelled' => Meeting::where('status', 'Cancelled')->count(),
        ];

        return view('meetings.index', compact(
            'meetings',
            'search',
            'statusFilter',
            'perPage',
            'kpi'
        ));
    }

    public function create(): View
    {
        return view('meetings.create');
    }

    public function store(StoreMeetingRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $userId = auth()->id();

        $payload = $this->meetingService->buildMeetingPayload($data);
        $payload['created_by'] = $userId;

        $meeting = Meeting::create($payload);

        return redirect()
            ->route('meetings.show', $meeting)
            ->with('success', 'Meeting created successfully.');
    }

    public function show(Meeting $meeting): View
    {
        $meeting->load(['creator', 'attendance.member', 'invites.member']);

        return view('meetings.show', compact('meeting'));
    }

    public function edit(Meeting $meeting): View
    {
        return view('meetings.edit', compact('meeting'));
    }

    public function update(UpdateMeetingRequest $request, Meeting $meeting): RedirectResponse
    {
        $data = $request->validated();

        $payload = $this->meetingService->buildMeetingPayload($data);

        $meeting->update($payload);

        return redirect()
            ->route('meetings.show', $meeting)
            ->with('success', 'Meeting updated successfully.');
    }

    public function destroy(Meeting $meeting): RedirectResponse
    {
        $meeting->delete();

        return redirect()
            ->route('meetings.index')
            ->with('success', 'Meeting deleted successfully.');
    }
}