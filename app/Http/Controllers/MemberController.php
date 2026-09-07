<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMemberRequest;
use App\Http\Requests\UpdateMemberRequest;
use App\Models\Branch;
use App\Models\Member;
use App\Models\Mobilizer;
use App\Services\MemberService;
use App\Services\PermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class MemberController extends Controller
{
    protected PermissionService $permission;

    protected MemberService $memberService;

    public function __construct()
    {
        $this->permission = app(PermissionService::class);
        $this->memberService = app(MemberService::class);
    }

    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
            new Middleware('user.status'),
            (new Middleware('permission:members'))->only(['index', 'show']),
            (new Middleware('permission:members_register'))->only(['create', 'store']),
            (new Middleware('permission:members_edit'))->only(['edit', 'update']),
            (new Middleware('permission:members_delete'))->only(['destroy']),
            (new Middleware('permission:members_export'))->only(['export']),
            (new Middleware('throttle:30,1'))->only(['store', 'update', 'destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $statusFilter = trim((string) $request->query('status', ''));
        $paymentFilter = strtoupper(trim((string) $request->query('payment_status', '')));
        $perPage = max(10, min(100, (int) $request->query('per_page', 25)));
        $page = max(1, (int) $request->query('page', 1));

        $query = Member::query()
            ->with(['branch', 'mobilizer'])
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        $this->applySearch($query, $search);

        if ($statusFilter !== '') {
            $query->where('membership_status', $statusFilter);
        }

        // Payment status filter is applied in PHP after pagination because it
        // depends on the ledger summary (payment_transactions), which is not
        // part of the members table. We filter the collection.
        $members = $query->paginate($perPage)->withQueryString();

        if ($paymentFilter !== '') {
            $filtered = $members->getCollection()->filter(function ($member) use ($paymentFilter) {
                return strtoupper($this->ledgerPayStatus($member)) === $paymentFilter;
            })->values();

            $members->setCollection($filtered);
        }

        // KPI stats.
        $kpi = [
            'total' => Member::count(),
            'active' => Member::where('membership_status', 'Active')->count(),
            'pending' => Member::where('membership_status', 'Pending')->count(),
            'paid' => 0,
            'partial' => 0,
            'unpaid' => 0,
            'mf' => 0.0,
            'rf' => 0.0,
            'af' => 0.0,
            'cf' => 0.0,
            'tp' => 0.0,
            'to' => 0.0,
        ];

        if (Schema::hasTable('payment_transactions') && Schema::hasColumn('payment_transactions', 'membership_fee_paid') && Schema::hasColumn('payment_transactions', 'total_amount_paid')) {
            $ledger = DB::table('payment_transactions')
                ->whereNotNull('member_id')
                ->selectRaw("
                    SUM(CASE WHEN total_amount_paid > 0 AND COALESCE(total_amount_outstanding,0) <= 0.009 THEN 1 ELSE 0 END) as paid_cnt,
                    SUM(CASE WHEN total_amount_paid > 0 AND COALESCE(total_amount_outstanding,0) > 0.009 THEN 1 ELSE 0 END) as partial_cnt,
                    SUM(CASE WHEN total_amount_paid <= 0 THEN 1 ELSE 0 END) as unpaid_cnt,
                    COALESCE(SUM(membership_fee_paid),0) as mf,
                    COALESCE(SUM(registration_fee_paid),0) as rf,
                    COALESCE(SUM(admin_fee_paid),0) as af,
                    COALESCE(SUM(contract_amount_paid),0) as cf,
                    COALESCE(SUM(total_amount_paid),0) as tp,
                    COALESCE(SUM(total_amount_outstanding),0) as to_
                ")
                ->first();

            if ($ledger) {
                $kpi['paid'] = (int) ($ledger->paid_cnt ?? 0);
                $kpi['partial'] = (int) ($ledger->partial_cnt ?? 0);
                $kpi['unpaid'] = (int) ($ledger->unpaid_cnt ?? 0);
                $kpi['mf'] = (float) ($ledger->mf ?? 0);
                $kpi['rf'] = (float) ($ledger->rf ?? 0);
                $kpi['af'] = (float) ($ledger->af ?? 0);
                $kpi['cf'] = (float) ($ledger->cf ?? 0);
                $kpi['tp'] = (float) ($ledger->tp ?? 0);
                $kpi['to'] = (float) ($ledger->to_ ?? 0);
            }
        }

        $branches = Branch::query()->orderBy('name')->get();
        $mobilizers = Mobilizer::query()->orderBy('first_name')->get();

        return view('members.index', compact(
            'members',
            'search',
            'statusFilter',
            'paymentFilter',
            'perPage',
            'kpi',
            'branches',
            'mobilizers'
        ));
    }

    public function create(): View
    {
        $branches = Branch::query()->orderBy('name')->get();
        $mobilizers = Mobilizer::query()->orderBy('first_name')->get();

        return view('members.create', compact('branches', 'mobilizers'));
    }

    public function store(StoreMemberRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $branchId = (int) $data['branch_id'];
        $mobilizerId = (int) $data['mobilizer_id'];
        $userId = auth()->id();

        $membershipId = $this->memberService->nextMembershipId($branchId);
        $payload = $this->memberService->buildMemberPayload($data, $branchId, $mobilizerId, $userId);
        $payload['membership_id'] = $membershipId;
        $payload['created_by'] = $userId;

        $member = Member::create($payload);

        return redirect()
            ->route('members.show', $member)
            ->with('success', 'Member registered successfully. Membership ID: ' . $membershipId);
    }

    public function show(Member $member): View
    {
        $member->load(['branch', 'mobilizer', 'nextOfKin', 'bankDetail']);

        return view('members.show', compact('member'));
    }

    public function edit(Member $member): View
    {
        $branches = Branch::query()->orderBy('name')->get();
        $mobilizers = Mobilizer::query()->orderBy('first_name')->get();

        return view('members.edit', compact('member', 'branches', 'mobilizers'));
    }

    public function update(UpdateMemberRequest $request, Member $member): RedirectResponse
    {
        $data = $request->validated();
        $branchId = (int) $data['branch_id'];
        $mobilizerId = (int) $data['mobilizer_id'];
        $userId = auth()->id();

        $payload = $this->memberService->buildMemberPayload($data, $branchId, $mobilizerId, $userId);
        $payload['updated_by'] = $userId;

        $member->update($payload);

        return redirect()
            ->route('members.show', $member)
            ->with('success', 'Member updated successfully.');
    }

    public function destroy(Member $member): RedirectResponse
    {
        $member->delete();

        return redirect()
            ->route('members.index')
            ->with('success', 'Member deleted successfully.');
    }

    /**
     * CSV export mirroring export_members.php. Returns a streamed CSV.
     */
    public function export(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $statusFilter = trim((string) $request->query('status', ''));
        $paymentFilter = strtoupper(trim((string) $request->query('payment_status', '')));
        $columns = $request->query('columns', []);

        $query = Member::query()
            ->with(['branch', 'mobilizer', 'mobilizations'])
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        $this->applySearch($query, $search);

        if ($statusFilter !== '') {
            $query->where('membership_status', $statusFilter);
        }

        $members = $query->get();

        if ($paymentFilter !== '') {
            $members = $members->filter(function ($member) use ($paymentFilter) {
                return strtoupper($this->ledgerPayStatus($member)) === $paymentFilter;
            })->values();
        }

        $allColumns = $this->exportColumns();
        $selected = [];
        foreach ((array) $columns as $col) {
            $col = trim((string) $col);
            if ($col !== '' && isset($allColumns[$col])) {
                $selected[$col] = $allColumns[$col];
            }
        }
        if (!$selected) {
            $selected = $allColumns;
        }

        $filename = 'members_export_' . now()->format('Ymd_His') . '.csv';

        $callback = function () use ($selected, $members) {
            $out = fopen('php://output', 'w');
            fwrite($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM
            fputcsv($out, array_values($selected));

            foreach ($members as $member) {
                $row = [];
                foreach (array_keys($selected) as $key) {
                    $row[] = $this->exportValue($member, $key);
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
     * AJAX endpoint mirroring get_member_details.php.
     */
    public function getMemberDetails(Request $request): JsonResponse
    {
        $id = (int) $request->query('id', 0);

        if ($id <= 0) {
            return response()->json(['success' => false, 'message' => 'Invalid member id']);
        }

        $member = Member::query()->find($id);

        if (!$member) {
            return response()->json(['success' => false, 'message' => 'Member not found']);
        }

        $data = $member->only([
            'id', 'user_id', 'membership_id', 'first_name', 'last_name', 'other_name',
            'date_of_birth', 'sex', 'nin', 'nationality', 'address', 'region',
            'district_residence', 'district', 'employment_status', 'employment_other',
            'marital_status', 'children_count', 'source', 'source_other', 'email',
            'telephone1', 'telephone2', 'mother_name', 'mother_phone', 'father_name',
            'father_phone', 'account_type', 'membership_status', 'created_at', 'notes',
        ]);

        if (!empty($data['date_of_birth'])) {
            $data['date_of_birth'] = substr((string) $data['date_of_birth'], 0, 10);
        }

        return response()->json(['success' => true, 'member' => $data]);
    }

    /**
     * Shared search filter used by index() and export(). Mirrors the legacy
     * members.php search: full-column LIKE on the canonical column set, branch
     * name search, full-name concatenation, and per-token matching.
     */
    protected function applySearch($query, string $search): void
    {
        if ($search === '') {
            return;
        }

        $like = '%' . $search . '%';

        $query->where(function ($q) use ($like, $search) {
            $q->where('first_name', 'like', $like)
                ->orWhere('last_name', 'like', $like)
                ->orWhere('other_name', 'like', $like)
                ->orWhere('email', 'like', $like)
                ->orWhere('membership_id', 'like', $like)
                ->orWhere('telephone1', 'like', $like)
                ->orWhere('telephone2', 'like', $like)
                ->orWhere('nin', 'like', $like)
                ->orWhere('tin_number', 'like', $like)
                ->orWhere('tin', 'like', $like)
                ->orWhere('tax_identification_number', 'like', $like)
                ->orWhere('bank_account', 'like', $like)
                ->orWhere('source', 'like', $like)
                ->orWhere('source_type', 'like', $like)
                ->orWhere('source_station', 'like', $like)
                ->orWhere('radio_station', 'like', $like)
                ->orWhere('tv_station', 'like', $like)
                ->orWhereRaw("TRIM(CONCAT_WS(' ', COALESCE(first_name,''), COALESCE(last_name,''), COALESCE(other_name,''))) LIKE ?", [$like]);

            // Branch name search.
            $q->orWhereHas('branch', function ($bq) use ($like) {
                $bq->where('name', 'like', $like)
                    ->orWhere('branch_name', 'like', $like)
                    ->orWhere('title', 'like', $like);
            });

            // Per-token search (legacy behaviour).
            foreach (preg_split('/\s+/', $search) ?: [] as $token) {
                $token = trim($token);
                if ($token === '') {
                    continue;
                }
                $tokenLike = '%' . $token . '%';
                $q->orWhere(function ($tq) use ($tokenLike) {
                    $tq->where('first_name', 'like', $tokenLike)
                        ->orWhere('last_name', 'like', $tokenLike)
                        ->orWhere('other_name', 'like', $tokenLike)
                        ->orWhere('membership_id', 'like', $tokenLike)
                        ->orWhere('telephone1', 'like', $tokenLike)
                        ->orWhere('telephone2', 'like', $tokenLike)
                        ->orWhere('nin', 'like', $tokenLike)
                        ->orWhere('tin_number', 'like', $tokenLike)
                        ->orWhere('tin', 'like', $tokenLike)
                        ->orWhere('source', 'like', $tokenLike)
                        ->orWhere('source_type', 'like', $tokenLike)
                        ->orWhere('source_station', 'like', $tokenLike)
                        ->orWhere('radio_station', 'like', $tokenLike)
                        ->orWhere('tv_station', 'like', $tokenLike);
                });
            }
        });
    }

    /**
     * Compute the ledger payment status for a member (UNPAID / PAID / PARTIAL).
     */
    protected function ledgerPayStatus(Member $member): string
    {
        if (!Schema::hasTable('payment_transactions')) {
            return 'UNPAID';
        }

        $row = DB::table('payment_transactions')
            ->where('member_id', $member->id)
            ->selectRaw('COALESCE(SUM(total_amount_paid),0) as paid, COALESCE(SUM(total_amount_outstanding),0) as out')
            ->first();

        $paid = (float) ($row->paid ?? 0);
        $out = (float) ($row->out ?? 0);

        if ($paid <= 0) {
            return 'UNPAID';
        }

        if ($out <= 0.009) {
            return 'PAID';
        }

        return 'PARTIAL';
    }

    protected function exportColumns(): array
    {
        return [
            'membership_id' => 'Membership ID',
            'first_name' => 'First Name',
            'last_name' => 'Surname',
            'other_name' => 'Other Name',
            'email' => 'Email',
            'telephone1' => 'Telephone 1',
            'telephone2' => 'Telephone 2',
            'nin' => 'NIN',
            'tin_number' => 'TIN Number',
            'sex' => 'Sex',
            'date_of_birth' => 'Date of Birth',
            'nationality' => 'Nationality',
            'address' => 'Address',
            'branch_name' => 'Branch',
            'region' => 'Region',
            'district_residence' => 'District of Residence',
            'district' => 'Home District',
            'employment_status' => 'Employment',
            'source' => 'Source',
            'source_station' => 'Source Station',
            'marital_status' => 'Marital Status',
            'membership_status' => 'Status',
            'mobilizer_name' => 'Mobilizer',
            'channel' => 'Channel',
            'mobilized_date' => 'Mobilized Date',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    protected function exportValue(Member $member, string $key): string
    {
        $value = '';

        switch ($key) {
            case 'branch_name':
                $value = (string) ($member->branch->name ?? '');
                break;
            case 'mobilizer_name':
                $value = (string) ($member->mobilizer->full_name ?? '');
                break;
            case 'channel':
            case 'mobilized_date':
                $mob = $member->mobilizations->sortByDesc('mobilized_date')->first();
                if ($mob) {
                    $value = $key === 'channel' ? (string) ($mob->channel ?? '') : (string) ($mob->mobilized_date ?? '');
                }
                break;
            case 'date_of_birth':
            case 'created_at':
            case 'updated_at':
                $raw = $member->{$key};
                $value = $raw ? $raw->format('Y-m-d H:i:s') : '';
                break;
            default:
                $value = (string) ($member->{$key} ?? '');
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
