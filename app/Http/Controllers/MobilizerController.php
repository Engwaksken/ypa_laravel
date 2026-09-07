<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMobilizerRequest;
use App\Http\Requests\UpdateMobilizerRequest;
use App\Models\Branch;
use App\Models\Mobilizer;
use App\Services\PermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class MobilizerController extends Controller
{
    protected PermissionService $permission;

    public function __construct()
    {
        $this->permission = app(PermissionService::class);
    }

    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
            new Middleware('user.status'),
            (new Middleware('permission:mobilizers'))->only(['index', 'show']),
            (new Middleware('permission:mobilizers_create'))->only(['create', 'store']),
            (new Middleware('permission:mobilizers_edit'))->only(['edit', 'update']),
            (new Middleware('permission:mobilizers_delete'))->only(['destroy']),
            (new Middleware('throttle:30,1'))->only(['store', 'update', 'destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $departmentFilter = trim((string) $request->query('department', ''));
        $statusFilter = trim((string) $request->query('status', ''));
        $regionFilter = trim((string) $request->query('region', ''));

        $query = Mobilizer::query()
            ->orderByDesc('created_at');

        if ($search !== '') {
            $like = '%' . $search . '%';
            $query->where(function ($q) use ($like) {
                $q->where('first_name', 'like', $like)
                    ->orWhere('last_name', 'like', $like)
                    ->orWhere('contact_number', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('position', 'like', $like);
            });
        }

        if ($departmentFilter !== '') {
            $query->where('department', $departmentFilter);
        }

        if ($statusFilter !== '') {
            $query->where('status', $statusFilter);
        }

        if ($regionFilter !== '') {
            $query->where('branch_region', $regionFilter);
        }

        $mobilizers = $query->get();

        // Stats.
        $stats = [
            'total' => Mobilizer::count(),
            'active' => Mobilizer::where('status', 'Active')->count(),
            'inactive' => Mobilizer::where('status', 'Inactive')->count(),
            'suspended' => Mobilizer::where('status', 'Suspended')->count(),
            'terminated' => Mobilizer::where('status', 'Terminated')->count(),
        ];

        // Department / position lookup lists (defaults + distinct values).
        $departmentDefaults = [
            'Field Operations',
            'Sales and Marketing',
            'Community Mobilization',
            'Membership',
            'Finance',
            'Administration',
            'Operations',
        ];
        $positionDefaults = [
            'Mobilizer',
            'Senior Mobilizer',
            'Field Officer',
            'Team Leader',
            'Supervisor',
            'Coordinator',
            'Manager',
        ];

        $departmentArr = array_values(array_unique(array_merge(
            $departmentDefaults,
            Mobilizer::query()->whereNotNull('department')->where('department', '!=', '')->distinct()->pluck('department')->all()
        )));
        sort($departmentArr, SORT_NATURAL | SORT_FLAG_CASE);

        $positionArr = array_values(array_unique(array_merge(
            $positionDefaults,
            Mobilizer::query()->whereNotNull('position')->where('position', '!=', '')->distinct()->pluck('position')->all()
        )));
        sort($positionArr, SORT_NATURAL | SORT_FLAG_CASE);

        $branches = Branch::query()->where('status', 1)->orderBy('name')->get();

        $hasFilters = $search !== '' || $departmentFilter !== '' || $statusFilter !== '' || $regionFilter !== '';

        return view('mobilizers.index', compact(
            'mobilizers',
            'search',
            'departmentFilter',
            'statusFilter',
            'regionFilter',
            'stats',
            'departmentArr',
            'positionArr',
            'branches',
            'hasFilters'
        ));
    }

    public function create(): View
    {
        $branches = Branch::query()->where('status', 1)->orderBy('name')->get();

        return view('mobilizers.create', compact('branches'));
    }

    public function store(StoreMobilizerRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['contact_number'] = $this->normalizeMobile($data['contact_number'] ?? '');
        $data['created_by'] = auth()->id();

        $mobilizer = Mobilizer::create($data);

        return redirect()
            ->route('mobilizers.index')
            ->with('success', 'Mobilizer added successfully!');
    }

    public function show(Mobilizer $mobilizer): View
    {
        return view('mobilizers.show', compact('mobilizer'));
    }

    public function edit(Mobilizer $mobilizer): View
    {
        $branches = Branch::query()->where('status', 1)->orderBy('name')->get();

        return view('mobilizers.edit', compact('mobilizer', 'branches'));
    }

    public function update(UpdateMobilizerRequest $request, Mobilizer $mobilizer): RedirectResponse
    {
        $data = $request->validated();
        $data['contact_number'] = $this->normalizeMobile($data['contact_number'] ?? '');

        $mobilizer->update($data);

        return redirect()
            ->route('mobilizers.index')
            ->with('success', 'Mobilizer updated successfully!');
    }

    public function destroy(Mobilizer $mobilizer): RedirectResponse
    {
        $mobilizer->delete();

        return redirect()
            ->route('mobilizers.index')
            ->with('success', 'Mobilizer deleted successfully!');
    }

    /**
     * AJAX endpoint mirroring process_mobilizer.php get action.
     */
    public function get(Request $request): JsonResponse
    {
        $id = (int) $request->input('mobilizer_id', 0);
        $mobilizer = Mobilizer::query()->find($id);

        if (!$mobilizer) {
            return response()->json(['success' => false, 'message' => 'Mobilizer not found']);
        }

        return response()->json(['success' => true, 'mobilizer' => $mobilizer]);
    }

    protected function normalizeMobile(string $mobile): string
    {
        $mobile = trim($mobile);
        $mobile = str_replace([' ', '-', '(', ')'], '', $mobile);

        if (str_starts_with($mobile, '256')) {
            $mobile = '+' . $mobile;
        }

        return $mobile;
    }
}
