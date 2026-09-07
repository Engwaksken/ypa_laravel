<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreActivityRequest;
use App\Http\Requests\UpdateActivityRequest;
use App\Models\Activity;
use App\Models\ActivityType;
use App\Services\ActivityService;
use App\Services\PermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class ActivityController extends Controller
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
            (new Middleware('permission:activities'))->only(['index', 'show']),
            (new Middleware('permission:activities_create'))->only(['create', 'store']),
            (new Middleware('permission:activities_edit'))->only(['edit', 'update']),
            (new Middleware('permission:activities_delete'))->only(['destroy']),
            (new Middleware('permission:activities_export'))->only(['export']),
            (new Middleware('throttle:30,1'))->only(['store', 'update', 'destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $statusFilter = trim((string) $request->query('status', ''));
        $typeFilter = (int) $request->query('type', 0);
        $promotionFilter = trim((string) $request->query('is_promotion', ''));
        $perPage = max(10, min(100, (int) $request->query('per_page', 25)));

        $query = Activity::query()
            ->with(['type'])
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if ($search !== '') {
            $like = '%' . $search . '%';
            $query->where(function ($q) use ($like) {
                $q->where('activity_code', 'like', $like)
                    ->orWhere('activity_name', 'like', $like)
                    ->orWhere('location', 'like', $like)
                    ->orWhereHas('type', function ($tq) use ($like) {
                        $tq->where('type_name', 'like', $like)
                            ->orWhere('type_code', 'like', $like);
                    });
            });
        }

        if ($statusFilter !== '') {
            $query->where('status', $statusFilter);
        }

        if ($typeFilter > 0) {
            $query->where('activity_type_id', $typeFilter);
        }

        if ($promotionFilter !== '') {
            $query->where('is_promotion', $promotionFilter);
        }

        $activities = $query->paginate($perPage)->withQueryString();

        $types = ActivityType::query()->orderBy('type_name')->get();

        $kpi = [
            'total' => Activity::count(),
            'planned' => Activity::where('status', 'Planned')->count(),
            'ongoing' => Activity::where('status', 'Ongoing')->count(),
            'completed' => Activity::where('status', 'Completed')->count(),
            'cancelled' => Activity::where('status', 'Cancelled')->count(),
        ];

        return view('activities.index', compact(
            'activities',
            'search',
            'statusFilter',
            'typeFilter',
            'promotionFilter',
            'perPage',
            'types',
            'kpi'
        ));
    }

    public function create(): View
    {
        $types = ActivityType::query()->orderBy('type_name')->get();

        return view('activities.create', compact('types'));
    }

    public function store(StoreActivityRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $userId = auth()->id();

        $payload = $this->activityService->buildActivityPayload($data, $userId);
        $payload['activity_code'] = $this->activityService->nextActivityCode();
        $payload['created_by'] = $userId;

        $activity = Activity::create($payload);

        return redirect()
            ->route('activities.show', $activity)
            ->with('success', 'Activity created successfully. Activity Code: ' . $activity->activity_code);
    }

    public function show(Activity $activity): View
    {
        $activity->load(['type', 'creator', 'participants.member', 'participantItems']);

        return view('activities.show', compact('activity'));
    }

    public function edit(Activity $activity): View
    {
        $types = ActivityType::query()->orderBy('type_name')->get();

        return view('activities.edit', compact('activity', 'types'));
    }

    public function update(UpdateActivityRequest $request, Activity $activity): RedirectResponse
    {
        $data = $request->validated();
        $userId = auth()->id();

        $payload = $this->activityService->buildActivityPayload($data, $userId);
        $payload['updated_by'] = $userId;

        $activity->update($payload);

        return redirect()
            ->route('activities.show', $activity)
            ->with('success', 'Activity updated successfully.');
    }

    public function destroy(Activity $activity): RedirectResponse
    {
        $activity->delete();

        return redirect()
            ->route('activities.index')
            ->with('success', 'Activity deleted successfully.');
    }

    /**
     * CSV export mirroring export_activities.php. Returns a streamed CSV.
     */
    public function export(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $statusFilter = trim((string) $request->query('status', ''));
        $typeFilter = (int) $request->query('type', 0);

        $query = Activity::query()
            ->with(['type'])
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if ($search !== '') {
            $like = '%' . $search . '%';
            $query->where(function ($q) use ($like) {
                $q->where('activity_code', 'like', $like)
                    ->orWhere('activity_name', 'like', $like)
                    ->orWhere('location', 'like', $like)
                    ->orWhereHas('type', function ($tq) use ($like) {
                        $tq->where('type_name', 'like', $like)
                            ->orWhere('type_code', 'like', $like);
                    });
            });
        }

        if ($statusFilter !== '') {
            $query->where('status', $statusFilter);
        }

        if ($typeFilter > 0) {
            $query->where('activity_type_id', $typeFilter);
        }

        $activities = $query->get();

        $columns = $this->exportColumns();
        $filename = 'activities_export_' . now()->format('Ymd_His') . '.csv';

        $callback = function () use ($columns, $activities) {
            $out = fopen('php://output', 'w');
            fwrite($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM
            fputcsv($out, array_values($columns));

            foreach ($activities as $activity) {
                $row = [];
                foreach (array_keys($columns) as $key) {
                    $row[] = $this->exportValue($activity, $key);
                }
                fputcsv($out, $row);
            }

            fclose($out);
        };

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * AJAX endpoint mirroring get_activity_details.php.
     */
    public function getActivityDetails(Request $request): JsonResponse
    {
        $id = (int) $request->query('id', 0);

        if ($id <= 0) {
            return response()->json(['success' => false, 'message' => 'Invalid activity id']);
        }

        $activity = Activity::query()->with(['type'])->find($id);

        if (!$activity) {
            return response()->json(['success' => false, 'message' => 'Activity not found']);
        }

        $data = $activity->only([
            'id', 'activity_code', 'activity_name', 'activity_type_id',
            'description', 'start_date', 'end_date', 'location', 'budget',
            'is_promotion', 'status', 'created_at',
        ]);

        foreach (['start_date', 'end_date'] as $field) {
            if (!empty($data[$field])) {
                $data[$field] = substr((string) $data[$field], 0, 10);
            }
        }

        $data['type_name'] = $activity->type->type_name ?? '';

        return response()->json(['success' => true, 'activity' => $data]);
    }

    protected function exportColumns(): array
    {
        return [
            'activity_code' => 'Activity Code',
            'activity_name' => 'Activity Name',
            'type_name' => 'Type',
            'description' => 'Description',
            'start_date' => 'Start Date',
            'end_date' => 'End Date',
            'location' => 'Location',
            'budget' => 'Budget',
            'is_promotion' => 'Promotion',
            'status' => 'Status',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    protected function exportValue(Activity $activity, string $key): string
    {
        $value = '';

        switch ($key) {
            case 'type_name':
                $value = (string) ($activity->type->type_name ?? '');
                break;
            case 'start_date':
            case 'end_date':
            case 'created_at':
            case 'updated_at':
                $raw = $activity->{$key};
                $value = $raw ? $raw->format('Y-m-d H:i:s') : '';
                break;
            default:
                $value = (string) ($activity->{$key} ?? '');
                break;
        }

        return $this->sanitizeCsvValue($value);
    }

    /**
     * Mitigate CSV formula injection by prefixing cells that begin with a
     * spreadsheet formula character (=, +, -, @) with a single quote.
     */
    protected function sanitizeCsvValue(string $value): string
    {
        if ($value !== '' && in_array($value[0], ['=', '+', '-', '@'], true)) {
            return "'" . $value;
        }

        return $value;
    }
}