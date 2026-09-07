<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContractRequest;
use App\Http\Requests\UpdateContractRequest;
use App\Models\Branch;
use App\Models\Contract;
use App\Models\ContractItem;
use App\Models\ContractItemHarvest;
use App\Models\ContractTemplate;
use App\Models\ContractTermination;
use App\Models\Group;
use App\Models\Harvest;
use App\Models\Member;
use App\Models\PaymentMethod;
use App\Models\PaymentTransaction;
use App\Models\Project;
use App\Services\ContractService;
use App\Services\PermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ContractController extends Controller
{
    protected PermissionService $permission;

    protected ContractService $contractService;

    public function __construct()
    {
        $this->permission = app(PermissionService::class);
        $this->contractService = app(ContractService::class);
    }

    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
            new Middleware('user.status'),
            (new Middleware('permission:contracts_view'))->only(['index', 'show', 'export', 'pdf']),
            (new Middleware('permission:contracts_create'))->only(['create', 'store']),
            (new Middleware('permission:contracts_edit'))->only(['edit', 'update']),
            (new Middleware('permission:contracts_delete'))->only(['destroy']),
            (new Middleware('throttle:30,1'))->only(['store', 'update', 'destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $statusFilter = trim((string) $request->query('status', ''));
        $contractFor = trim((string) $request->query('contract_for', ''));
        $perPage = max(10, min(100, (int) $request->query('per_page', 25)));

        $query = Contract::query()
            ->with(['member', 'group', 'project', 'branch', 'paymentMethod'])
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if ($search !== '') {
            $like = '%' . $search . '%';
            $query->where(function ($q) use ($like): void {
                $q->where('contract_number', 'like', $like)
                    ->orWhere('workflow_status', 'like', $like)
                    ->orWhere('status', 'like', $like)
                    ->orWhereHas('member', function ($mq) use ($like): void {
                        $mq->where('first_name', 'like', $like)
                            ->orWhere('last_name', 'like', $like)
                            ->orWhereRaw("TRIM(CONCAT_WS(' ', COALESCE(first_name,''), COALESCE(last_name,''), COALESCE(other_name,''))) LIKE ?", [$like]);
                    })
                    ->orWhereHas('group', function ($gq) use ($like): void {
                        $gq->where('group_name', 'like', $like)
                            ->orWhere('group_code', 'like', $like);
                    })
                    ->orWhereHas('project', function ($pq) use ($like): void {
                        $pq->where('project_name', 'like', $like)
                            ->orWhere('project_code', 'like', $like);
                    })
                    ->orWhereHas('branch', function ($bq) use ($like): void {
                        $bq->where('name', 'like', $like);
                    })
                    ->orWhereHas('paymentMethod', function ($pmq) use ($like): void {
                        $pmq->where('method_name', 'like', $like);
                    });
            });
        }

        if ($statusFilter !== '') {
            $query->where('status', $statusFilter);
        }

        if ($contractFor !== '') {
            $query->where('contract_for', $contractFor);
        }

        $contracts = $query->paginate($perPage)->withQueryString();

        return view('contracts.index', compact('contracts', 'search', 'statusFilter', 'contractFor', 'perPage'));
    }

    public function create(): View
    {
        return view('contracts.create', $this->formData());
    }

    public function store(StoreContractRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $userId = auth()->id();

        $contract = DB::transaction(function () use ($data, $userId) {
            $payload = $this->contractPayload($data, $userId, true);
            $payload['contract_number'] = $this->contractService->nextContractNumber();
            $payload['created_by'] = $userId;

            $contract = Contract::create($payload);

            $this->syncContractItems($contract, $data['items'] ?? []);

            return $contract->refresh();
        });

        return redirect()->route('contracts.show', $contract)->with('success', 'Contract created successfully.');
    }

    public function show(Contract $contract): View
    {
        $contract->load([
            'member', 'group', 'project', 'branch', 'paymentMethod', 'creator', 'updater',
            'items.project', 'projectData', 'approvals.user', 'attachments.uploader',
            'notifications', 'terminations', 'schedules', 'payouts', 'harvests', 'itemHarvests',
        ]);

        $template = null;
        $renderedHtml = '';
        if (!empty($contract->template_path)) {
            $template = ContractTemplate::query()
                ->where('template_name', $contract->template_path)
                ->orWhere('template_key', $contract->template_path)
                ->first();

            if ($template) {
                $renderedHtml = $this->contractService->renderTemplate($contract, $template);
            }
        }

        return view('contracts.show', compact('contract', 'template', 'renderedHtml'));
    }

    public function edit(Contract $contract): View
    {
        $contract->load(['items']);

        return view('contracts.edit', array_merge($this->formData(), compact('contract')));
    }

    public function update(UpdateContractRequest $request, Contract $contract): RedirectResponse
    {
        $data = $request->validated();
        $userId = auth()->id();

        DB::transaction(function () use ($contract, $data, $userId): void {
            $payload = $this->contractPayload($data, $userId, false, $contract);
            $payload['updated_by'] = $userId;

            unset($payload['status']);

            $contract->update($payload);
            $this->syncContractItems($contract, $data['items'] ?? [], true);
        });

        return redirect()->route('contracts.show', $contract)->with('success', 'Contract updated successfully.');
    }

    public function destroy(Contract $contract): RedirectResponse
    {
        $hasFinancialRecords = PaymentTransaction::query()->where('contract_id', $contract->id)->exists()
            || Harvest::query()->where('contract_id', $contract->id)->exists()
            || ContractItemHarvest::query()->where('contract_id', $contract->id)->exists()
            || ContractTermination::query()->where('contract_id', $contract->id)->exists();

        if ($hasFinancialRecords) {
            throw ValidationException::withMessages(['contract' => 'Contract cannot be deleted because it has payment, harvest, or termination records.']);
        }

        $contract->delete();

        return redirect()->route('contracts.index')->with('success', 'Contract deleted successfully.');
    }

    public function export(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $statusFilter = trim((string) $request->query('status', ''));
        $contractFor = trim((string) $request->query('contract_for', ''));

        $query = Contract::query()
            ->with(['member', 'group', 'project', 'branch', 'paymentMethod'])
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if ($search !== '') {
            $like = '%' . $search . '%';
            $query->where(function ($q) use ($like): void {
                $q->where('contract_number', 'like', $like)
                    ->orWhere('workflow_status', 'like', $like)
                    ->orWhere('status', 'like', $like)
                    ->orWhereHas('member', function ($mq) use ($like): void {
                        $mq->whereRaw("TRIM(CONCAT_WS(' ', COALESCE(first_name,''), COALESCE(last_name,''), COALESCE(other_name,''))) LIKE ?", [$like]);
                    })
                    ->orWhereHas('group', function ($gq) use ($like): void {
                        $gq->where('group_name', 'like', $like)
                            ->orWhere('group_code', 'like', $like);
                    })
                    ->orWhereHas('project', function ($pq) use ($like): void {
                        $pq->where('project_name', 'like', $like)
                            ->orWhere('project_code', 'like', $like);
                    })
                    ->orWhereHas('branch', function ($bq) use ($like): void {
                        $bq->where('name', 'like', $like);
                    });
            });
        }

        if ($statusFilter !== '') {
            $query->where('status', $statusFilter);
        }

        if ($contractFor !== '') {
            $query->where('contract_for', $contractFor);
        }

        $contracts = $query->get();
        $filename = 'contracts_export_' . now()->format('Ymd_His') . '.csv';

        $columns = [
            'contract_number' => 'Contract Number',
            'contract_for' => 'For',
            'party_name' => 'Party Name',
            'project_name' => 'Project',
            'branch_name' => 'Branch',
            'payment_method' => 'Payment Method',
            'payment_frequency' => 'Payment Frequency',
            'status' => 'Status',
            'workflow_status' => 'Workflow',
            'contract_amount' => 'Contract Amount',
            'total_paid' => 'Total Paid',
            'total_outstanding' => 'Outstanding',
            'created_at' => 'Created At',
        ];

        $callback = function () use ($contracts, $columns): void {
            $out = fopen('php://output', 'w');
            fwrite($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($out, array_values($columns));

            foreach ($contracts as $contract) {
                $totals = $this->contractService->contractTotals($contract);
                $partyName = strtolower((string) ($contract->contract_for ?? 'member')) === 'group'
                    ? (string) ($contract->group->group_name ?? '')
                    : trim((string) optional($contract->member)->full_name);

                $row = [
                    $this->csvValue($contract->contract_number),
                    $this->csvValue($contract->contract_for),
                    $this->csvValue($partyName),
                    $this->csvValue($contract->project->project_name ?? ''),
                    $this->csvValue($contract->branch->name ?? ''),
                    $this->csvValue($contract->paymentMethod->method_name ?? ''),
                    $this->csvValue($contract->payment_frequency),
                    $this->csvValue($contract->status),
                    $this->csvValue($contract->workflow_status),
                    $this->csvValue(number_format((float) ($contract->contract_amount ?? 0), 2)),
                    $this->csvValue(number_format($totals['total_paid'], 2)),
                    $this->csvValue(number_format($totals['total_outstanding'], 2)),
                    $this->csvValue(optional($contract->created_at)->format('Y-m-d H:i:s') ?? ''),
                ];

                fputcsv($out, $row);
            }

            fclose($out);
        };

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function pdf(Contract $contract, Request $request)
    {
        $contract->loadMissing(['member', 'group', 'project', 'branch', 'paymentMethod', 'items']);
        $template = null;

        if ($request->filled('template_id')) {
            $template = ContractTemplate::query()->find((int) $request->query('template_id'));
        }

        $html = $this->contractService->renderTemplate($contract, $template);

        if (class_exists('Barryvdh\\DomPDF\\Facade\\Pdf')) {
            return \Barryvdh\DomPDF\Facade\Pdf::loadView('contracts.pdf', [
                'contract' => $contract,
                'template' => $template,
                'renderedHtml' => $html,
            ])->download(($contract->contract_number ?: 'contract') . '.pdf');
        }

        return response()->view('contracts.pdf', [
            'contract' => $contract,
            'template' => $template,
            'renderedHtml' => $html,
        ]);
    }

    protected function formData(): array
    {
        return [
            'members' => Member::query()->orderBy('first_name')->orderBy('last_name')->get(),
            'groups' => Group::query()->orderBy('group_name')->get(),
            'projects' => Project::query()->orderBy('project_name')->get(),
            'branches' => Branch::query()->orderBy('name')->get(),
            'paymentMethods' => PaymentMethod::query()->orderBy('method_name')->get(),
            'templates' => ContractTemplate::query()->orderBy('template_name')->get(),
        ];
    }

    protected function contractPayload(array $data, int $userId, bool $resetWorkflow = true, ?Contract $contract = null): array
    {
        $contractFor = strtolower((string) ($data['contract_for'] ?? 'member'));
        $project = Project::query()->find((int) ($data['project_id'] ?? 0));
        $totalPayable = round((float) ($data['total_amount'] ?? $data['contract_amount'] ?? 0), 2);

        // Paid/outstanding are derived from APPROVED payment transactions only —
        // never from form input. The legacy total_amount_paid / total_amount_outstanding
        // columns do not exist on contracts.
        $paid = $contract ? $this->approvedPaidTotal($contract->id) : 0.0;
        $outstanding = max(0, round($totalPayable - $paid, 2));

        $payload = [
            'contract_for' => $contractFor,
            'member_id' => $contractFor === 'member' ? ($data['member_id'] ?? null) : null,
            'group_id' => $contractFor === 'group' ? ($data['group_id'] ?? null) : null,
            'project_id' => $data['project_id'] ?? null,
            'branch_id' => $data['branch_id'] ?? null,
            'project_code' => (string) ($project?->project_code ?? ''),
            'payment_method_id' => $data['payment_method_id'] ?? null,
            'payment_frequency' => $data['payment_frequency'] ?? null,
            'status' => 'DRAFT',
            'template_path' => $data['template_path'] ?? null,
            'signing_date' => $data['signing_date'] ?? null,
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
            'duration' => (int) ($data['duration'] ?? 0),
            'contract_amount' => $data['contract_amount'] ?? null,
            'total_amount' => $data['total_amount'] ?? null,
            'total_payable' => $totalPayable,
            'total_paid' => round($paid, 2),
            'total_outstanding' => $outstanding,
            'amount_paid' => round($paid, 2),
            'outstanding_balance' => $outstanding,
            'contract_outstanding' => $outstanding,
            'contract_amount_paid' => round($paid, 2),
        ];

        if ($resetWorkflow) {
            $payload['workflow_status'] = 'draft';
            $payload['workflow_step'] = 0;
        }

        return $payload;
    }

    protected function approvedPaidTotal(int $contractId): float
    {
        return (float) PaymentTransaction::query()
            ->where('contract_id', $contractId)
            ->whereRaw("UPPER(COALESCE(status, '')) = 'APPROVED'")
            ->selectRaw('COALESCE(SUM(CASE WHEN contract_amount_paid > 0 THEN contract_amount_paid ELSE amount END), 0) AS paid_total')
            ->value('paid_total');
    }

    protected function syncContractItems(Contract $contract, array $items, bool $replace = false): void
    {
        if ($replace) {
            $contract->items()->delete();
        }

        $rows = [];
        foreach ($items as $item) {
            $itemName = trim((string) ($item['item_name'] ?? ''));
            if ($itemName === '') {
                continue;
            }

            $quantity = (float) ($item['quantity'] ?? 0);
            $unitPrice = (float) ($item['unit_price'] ?? 0);
            $monthlyReturn = (float) ($item['monthly_return'] ?? 0);
            $totalHives = (float) ($item['total_hives'] ?? 0);

            $rows[] = [
                'item_name' => $itemName,
                'item_type' => trim((string) ($item['item_type'] ?? '')),
                'quantity' => $quantity,
                'unit_name' => trim((string) ($item['unit_name'] ?? '')),
                'unit_price' => $unitPrice,
                'total_price' => round($quantity * $unitPrice, 2),
                'monthly_return' => $monthlyReturn,
                'monthly_payout_amount' => round($totalHives * $monthlyReturn, 2),
                'total_hives' => $totalHives,
                'project_id' => $contract->project_id,
                'item_order' => count($rows) + 1,
            ];
        }

        if ($rows !== []) {
            $contract->items()->createMany($rows);
        }
    }

    protected function csvValue(mixed $value): string
    {
        return $this->contractService->csvSafeValue($value);
    }
}
