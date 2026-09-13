<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Contract;
use App\Models\Project;
use App\Models\ProjectCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
            new Middleware('user.status'),
            new Middleware('permission:projects'),
            (new Middleware('permission:projects_create'))->only(['store']),
            (new Middleware('permission:projects_edit'))->only(['update']),
            (new Middleware('permission:projects_delete'))->only(['destroy']),
            (new Middleware('permission:projects_export'))->only(['export']),
            (new Middleware('throttle:30,1'))->only(['store', 'update', 'destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $statusFilter = trim((string) $request->query('status', ''));
        $perPage = max(5, (int) $request->query('per_page', 10));

        // The legacy index honours a whitelist of page sizes; clamp to it.
        $perPage = in_array($perPage, [5, 10, 20, 50, 100], true) ? $perPage : 10;

        $query = Project::query()
            ->with(['category', 'branch'])
            ->withCount(['contracts as member_count' => function ($q) {
                $q->whereIn('status', ['Active', 'Completed', 'Pending']);
            }])
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $like = '%' . $search . '%';
                $q->where('project_name', 'like', $like)
                    ->orWhere('project_code', 'like', $like)
                    ->orWhere('description', 'like', $like)
                    ->orWhereHas('category', function ($cq) use ($like) {
                        $cq->where('category_name', 'like', $like);
                    });
            });
        }

        if ($statusFilter !== '') {
            $query->where('status', $statusFilter);
        }

        $projects = $query->paginate($perPage)->withQueryString();

        $stats = [
            'total' => Project::count(),
            'active' => Project::where('status', 'Active')->count(),
            'planning' => Project::where('status', 'Planning')->count(),
            'completed' => Project::where('status', 'Completed')->count(),
            'total_registration_fee' => (float) Project::sum('registration_fee'),
            'total_admin_fee' => (float) Project::sum('administrative_fee'),
        ];

        $categories = ProjectCategory::query()->active()->orderBy('category_name')->get();
        $statuses = StoreProjectRequest::STATUSES;

        return view('projects.index', compact(
            'projects',
            'search',
            'statusFilter',
            'perPage',
            'stats',
            'categories',
            'statuses'
        ));
    }

    public function store(StoreProjectRequest $request): JsonResponse|RedirectResponse
    {
        $data = $request->validated();
        $data['administrative_fee'] = $data['administrative_fee'] ?? 0;
        $data['created_by'] = auth()->id();

        $project = Project::create($data);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Project '{$project->project_name}' created successfully.",
                'project_id' => $project->id,
            ], 201);
        }

        return redirect()
            ->route('projects.index')
            ->with('success', "Project '{$project->project_name}' created successfully.");
    }

    public function update(UpdateProjectRequest $request, Project $project): JsonResponse|RedirectResponse
    {
        $data = $request->validated();
        $data['administrative_fee'] = $data['administrative_fee'] ?? 0;

        $project->update($data);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Project '{$project->project_name}' updated successfully.",
            ]);
        }

        return redirect()
            ->route('projects.index')
            ->with('success', "Project '{$project->project_name}' updated successfully.");
    }

    public function destroy(Request $request, Project $project): JsonResponse|RedirectResponse
    {
        $contractsCount = Contract::where('project_id', $project->id)->count();

        $memberRecordsCount = 0;
        if (Schema::hasTable('project_members')) {
            $memberRecordsCount = (int) \DB::table('project_members')->where('project_id', $project->id)->count();
        }

        if ($contractsCount > 0) {
            $message = "Cannot delete project '{$project->project_name}' because it has {$contractsCount} related contract(s).";
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $message], 422)
                : back()->with('error', $message);
        }

        if ($memberRecordsCount > 0) {
            $message = "Cannot delete project '{$project->project_name}' because it has {$memberRecordsCount} related member record(s).";
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $message], 422)
                : back()->with('error', $message);
        }

        $project->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Project '{$project->project_name}' deleted successfully.",
            ]);
        }

        return redirect()
            ->route('projects.index')
            ->with('success', "Project '{$project->project_name}' deleted successfully.");
    }

    /**
     * CSV export mirroring export_projects.php. Filters follow the index
     * page; a streamed CSV is returned with a sanitised header row.
     */
    public function export(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $statusFilter = trim((string) $request->query('status', ''));

        $query = Project::query()
            ->with(['category'])
            ->withCount(['contracts as member_count' => function ($q) {
                $q->whereIn('status', ['Active', 'Completed', 'Pending']);
            }])
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $like = '%' . $search . '%';
                $q->where('project_name', 'like', $like)
                    ->orWhere('project_code', 'like', $like)
                    ->orWhere('description', 'like', $like)
                    ->orWhereHas('category', function ($cq) use ($like) {
                        $cq->where('category_name', 'like', $like);
                    });
            });
        }

        if ($statusFilter !== '') {
            $query->where('status', $statusFilter);
        }

        $projects = $query->get();

        $columns = $this->exportColumns();
        $filename = 'projects_export_' . now()->format('Ymd_His') . '.csv';

        $callback = function () use ($columns, $projects) {
            $out = fopen('php://output', 'w');
            fwrite($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM
            fputcsv($out, array_values($columns));

            foreach ($projects as $project) {
                $row = [];
                foreach (array_keys($columns) as $key) {
                    $row[] = $this->exportValue($project, $key);
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
     * AJAX endpoint mirroring the legacy view modal payload.
     */
    public function getProjectDetails(Request $request): JsonResponse
    {
        $id = (int) $request->query('id', 0);

        if ($id <= 0) {
            return response()->json(['success' => false, 'message' => 'Invalid project id']);
        }

        $project = Project::query()
            ->with(['category', 'branch'])
            ->withCount(['contracts as member_count' => function ($q) {
                $q->whereIn('status', ['Active', 'Completed', 'Pending']);
            }])
            ->find($id);

        if (!$project) {
            return response()->json(['success' => false, 'message' => 'Project not found']);
        }

        $data = $project->only([
            'id',
            'project_code',
            'project_name',
            'project_category_id',
            'project_type_id',
            'description',
            'start_date',
            'end_date',
            'registration_fee',
            'administrative_fee',
            'status',
            'created_at',
            'updated_at',
        ]);

        $data['category_name'] = $project->category->category_name ?? '';
        $data['branch_name'] = $project->branch->name ?? '';
        $data['member_count'] = (int) $project->member_count;
        if (!empty($data['start_date'])) {
            $data['start_date'] = substr((string) $data['start_date'], 0, 10);
        }
        if (!empty($data['end_date'])) {
            $data['end_date'] = substr((string) $data['end_date'], 0, 10);
        }

        return response()->json(['success' => true, 'project' => $data]);
    }

    protected function exportColumns(): array
    {
        return [
            'project_code' => 'Project Code',
            'project_name' => 'Project Name',
            'category_name' => 'Category',
            'description' => 'Description',
            'start_date' => 'Start Date',
            'end_date' => 'End Date',
            'registration_fee' => 'Registration Fee',
            'administrative_fee' => 'Administrative Fee',
            'member_count' => 'Members',
            'status' => 'Status',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    protected function exportValue(Project $project, string $key): string
    {
        switch ($key) {
            case 'category_name':
                $value = (string) ($project->category->category_name ?? '');
                break;
            case 'member_count':
                $value = (string) ($project->member_count ?? 0);
                break;
            case 'registration_fee':
            case 'administrative_fee':
                $value = number_format((float) $project->{$key}, 2, '.', '');
                break;
            case 'end_date':
                $value = $project->end_date ? $project->end_date->format('Y-m-d') : 'Ongoing';
                break;
            case 'start_date':
            case 'created_at':
            case 'updated_at':
                $raw = $project->{$key};
                if (!$raw) {
                    $value = '';
                } else {
                    $value = ($key === 'start_date' ? substr((string) $raw, 0, 10) : $raw);
                    if ($key === 'created_at' || $key === 'updated_at') {
                        $value = substr((string) $raw, 0, 19);
                    }
                }
                break;
            default:
                $value = (string) ($project->{$key} ?? '');
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