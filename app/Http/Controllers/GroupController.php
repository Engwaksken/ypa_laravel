<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGroupRequest;
use App\Http\Requests\UpdateGroupRequest;
use App\Models\Branch;
use App\Models\Group;
use App\Models\Mobilizer;
use App\Services\GroupService;
use App\Services\PermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class GroupController extends Controller
{
    protected PermissionService $permission;

    protected GroupService $groupService;

    public function __construct()
    {
        $this->permission = app(PermissionService::class);
        $this->groupService = app(GroupService::class);
    }

    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
            new Middleware('user.status'),
            (new Middleware('permission:groups'))->only(['index', 'show']),
            (new Middleware('permission:groups_create'))->only(['create', 'store']),
            (new Middleware('permission:groups_edit'))->only(['edit', 'update']),
            (new Middleware('permission:groups_delete'))->only(['destroy']),
            (new Middleware('permission:groups_export'))->only(['export']),
            (new Middleware('throttle:30,1'))->only(['store', 'update', 'destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $statusFilter = trim((string) $request->query('status', ''));
        $categoryFilter = trim((string) $request->query('category', ''));
        $perPage = max(10, min(100, (int) $request->query('per_page', 25)));

        $query = Group::query()
            ->with(['branch', 'mobilizer'])
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if ($search !== '') {
            $like = '%' . $search . '%';
            $query->where(function ($q) use ($like) {
                $q->where('group_code', 'like', $like)
                    ->orWhere('group_name', 'like', $like)
                    ->orWhere('group_category', 'like', $like)
                    ->orWhere('country', 'like', $like)
                    ->orWhere('uganda_district', 'like', $like)
                    ->orWhere('bank_account_number', 'like', $like)
                    ->orWhereHas('branch', function ($bq) use ($like) {
                        $bq->where('name', 'like', $like);
                    });
            });
        }

        if ($statusFilter !== '') {
            $query->where('status', $statusFilter);
        }

        if ($categoryFilter !== '') {
            $query->where('group_category', $categoryFilter);
        }

        $groups = $query->paginate($perPage)->withQueryString();

        $kpi = [
            'total' => Group::count(),
            'active' => Group::where('status', 'Active')->count(),
            'inactive' => Group::where('status', 'Inactive')->count(),
            'suspended' => Group::where('status', 'Suspended')->count(),
            'dissolved' => Group::where('status', 'Dissolved')->count(),
        ];

        return view('groups.index', compact(
            'groups',
            'search',
            'statusFilter',
            'categoryFilter',
            'perPage',
            'kpi'
        ));
    }

    public function create(): View
    {
        $branches = Branch::query()->orderBy('name')->get();
        $mobilizers = Mobilizer::query()->orderBy('first_name')->get();

        return view('groups.create', compact('branches', 'mobilizers'));
    }

    public function store(StoreGroupRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $userId = auth()->id();

        $payload = $this->groupService->buildGroupPayload($data, $userId);
        $payload['group_code'] = $this->groupService->nextGroupCode();
        $payload['created_by'] = $userId;

        $group = Group::create($payload);

        return redirect()
            ->route('groups.show', $group)
            ->with('success', 'Group created successfully. Group Code: ' . $group->group_code);
    }

    public function show(Group $group): View
    {
        $group->load([
            'branch',
            'mobilizer',
            'coordinator',
            'members.member',
            'coordinators.coordinator',
            'nonMemberCoordinators',
            'documents',
            'contributions.member',
            'nextOfKin.member',
        ]);

        return view('groups.show', compact('group'));
    }

    public function edit(Group $group): View
    {
        $branches = Branch::query()->orderBy('name')->get();
        $mobilizers = Mobilizer::query()->orderBy('first_name')->get();

        return view('groups.edit', compact('group', 'branches', 'mobilizers'));
    }

    public function update(UpdateGroupRequest $request, Group $group): RedirectResponse
    {
        $data = $request->validated();
        $userId = auth()->id();

        $payload = $this->groupService->buildGroupPayload($data, $userId);
        $payload['updated_by'] = $userId;

        $group->update($payload);

        return redirect()
            ->route('groups.show', $group)
            ->with('success', 'Group updated successfully.');
    }

    public function destroy(Group $group): RedirectResponse
    {
        $group->delete();

        return redirect()
            ->route('groups.index')
            ->with('success', 'Group deleted successfully.');
    }

    /**
     * CSV export mirroring export_groups.php. Returns a streamed CSV.
     */
    public function export(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $statusFilter = trim((string) $request->query('status', ''));
        $categoryFilter = trim((string) $request->query('category', ''));

        $query = Group::query()
            ->with(['branch', 'mobilizer'])
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if ($search !== '') {
            $like = '%' . $search . '%';
            $query->where(function ($q) use ($like) {
                $q->where('group_code', 'like', $like)
                    ->orWhere('group_name', 'like', $like)
                    ->orWhere('group_category', 'like', $like)
                    ->orWhere('country', 'like', $like)
                    ->orWhere('uganda_district', 'like', $like)
                    ->orWhereHas('branch', function ($bq) use ($like) {
                        $bq->where('name', 'like', $like);
                    });
            });
        }

        if ($statusFilter !== '') {
            $query->where('status', $statusFilter);
        }

        if ($categoryFilter !== '') {
            $query->where('group_category', $categoryFilter);
        }

        $groups = $query->get();

        $columns = $this->exportColumns();
        $filename = 'groups_export_' . now()->format('Ymd_His') . '.csv';

        $callback = function () use ($columns, $groups) {
            $out = fopen('php://output', 'w');
            fwrite($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM
            fputcsv($out, array_values($columns));

            foreach ($groups as $group) {
                $row = [];
                foreach (array_keys($columns) as $key) {
                    $row[] = $this->exportValue($group, $key);
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
     * AJAX endpoint mirroring get_group_details.php.
     */
    public function getGroupDetails(Request $request): JsonResponse
    {
        $id = (int) $request->query('id', 0);

        if ($id <= 0) {
            return response()->json(['success' => false, 'message' => 'Invalid group id']);
        }

        $group = Group::query()->with(['branch', 'mobilizer'])->find($id);

        if (!$group) {
            return response()->json(['success' => false, 'message' => 'Group not found']);
        }

        $data = $group->only([
            'id', 'group_code', 'group_name', 'group_category', 'category_other',
            'country', 'uganda_subregion', 'uganda_district', 'formation_date',
            'mobilizer_id', 'branch_id', 'coordinator_id', 'bank_name',
            'bank_account_name', 'bank_account_number', 'status', 'created_at',
        ]);

        if (!empty($data['formation_date'])) {
            $data['formation_date'] = substr((string) $data['formation_date'], 0, 10);
        }

        $data['branch_name'] = $group->branch->name ?? '';
        $data['mobilizer_name'] = $group->mobilizer->full_name ?? '';

        return response()->json(['success' => true, 'group' => $data]);
    }

    protected function exportColumns(): array
    {
        return [
            'group_code' => 'Group Code',
            'group_name' => 'Group Name',
            'group_category' => 'Category',
            'country' => 'Country',
            'uganda_subregion' => 'Sub-region',
            'uganda_district' => 'District',
            'formation_date' => 'Formation Date',
            'branch_name' => 'Branch',
            'mobilizer_name' => 'Mobilizer',
            'bank_name' => 'Bank Name',
            'bank_account_name' => 'Account Name',
            'bank_account_number' => 'Account Number',
            'status' => 'Status',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    protected function exportValue(Group $group, string $key): string
    {
        $value = '';

        switch ($key) {
            case 'branch_name':
                $value = (string) ($group->branch->name ?? '');
                break;
            case 'mobilizer_name':
                $value = (string) ($group->mobilizer->full_name ?? '');
                break;
            case 'formation_date':
            case 'created_at':
            case 'updated_at':
                $raw = $group->{$key};
                $value = $raw ? $raw->format('Y-m-d H:i:s') : '';
                break;
            default:
                $value = (string) ($group->{$key} ?? '');
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