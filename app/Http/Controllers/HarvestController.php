<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreHarvestRequest;
use App\Models\Contract;
use App\Models\ContractItem;
use App\Models\ContractItemHarvest;
use App\Models\Harvest;
use App\Models\PaymentMethod;
use App\Models\PaymentTransaction;
use App\Models\PaymentTransactionType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class HarvestController extends Controller
{
    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
            new Middleware('user.status'),
            (new Middleware('permission:harvest_view'))->only(['index', 'show', 'export']),
            (new Middleware('permission:harvest_manage'))->only(['create', 'store', 'destroy']),
            (new Middleware('permission:harvest_requests_review'))->only(['review']),
            (new Middleware('permission:harvest_requests_approve'))->only(['approve']),
            (new Middleware('permission:harvest_requests_reject'))->only(['reject']),
            (new Middleware('permission:harvest_requests_pay'))->only(['pay']),
            (new Middleware('throttle:30,1'))->only(['store', 'update', 'destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $statusFilter = trim((string) $request->query('status', ''));
        $stageFilter = trim((string) $request->query('stage', ''));
        $perPage = max(10, min(100, (int) $request->query('per_page', 25)));

        $query = $this->filteredHarvestsQuery($request)
            ->with(['contract.member', 'contract.group', 'contract.project', 'branch', 'itemHarvests.item'])
            ->orderByDesc('harvest_date')
            ->orderByDesc('id');

        $totalAmount = (clone $query)->sum('amount_harvested');
        $totalNet = (clone $query)->sum('net_amount');
        $harvests = $query->paginate($perPage)->withQueryString();

        return view('harvests.index', compact('harvests', 'search', 'statusFilter', 'stageFilter', 'perPage', 'totalAmount', 'totalNet'));
    }

    public function create(Request $request): View
    {
        $contractItem = null;
        if ($request->filled('contract_item_id')) {
            $contractItem = ContractItem::query()->with(['contract.member', 'contract.group', 'contract.project'])->find((int) $request->query('contract_item_id'));
        }

        return view('harvests.create', [
            'contractItem' => $contractItem,
            'contracts' => Contract::query()->with(['member', 'group'])->whereNotIn('status', ['DRAFT', 'TERMINATED', 'CANCELLED'])->orderByDesc('id')->limit(200)->get(),
        ]);
    }

    public function store(StoreHarvestRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $harvest = DB::transaction(fn () => $this->createHarvest($data, (int) auth()->id()));

        return redirect()->route('harvests.show', $harvest)->with('success', 'Harvest request recorded successfully.');
    }

    public function show(Harvest $harvest): View
    {
        $harvest->load(['contract.member', 'contract.group', 'contract.project', 'branch', 'itemHarvests.item', 'creator', 'reviewer', 'approver', 'payer']);

        return view('harvests.show', compact('harvest'));
    }

    public function destroy(Harvest $harvest): RedirectResponse
    {
        if (strtolower((string) $harvest->approval_stage) === 'paid' || strtoupper((string) $harvest->status) === 'PAID') {
            throw ValidationException::withMessages(['harvest' => 'Paid harvests cannot be deleted without a reversal.']);
        }

        DB::transaction(function () use ($harvest): void {
            $harvest->itemHarvests()->delete();
            $harvest->delete();
        });

        return redirect()->route('harvests.index')->with('success', 'Harvest deleted successfully.');
    }

    public function review(Request $request, Harvest $harvest): RedirectResponse
    {
        $data = $request->validate(['notes' => ['nullable', 'string']]);

        DB::transaction(function () use ($harvest, $data): void {
            $locked = Harvest::query()->lockForUpdate()->findOrFail($harvest->id);

            if (in_array(strtolower((string) $locked->approval_stage), ['paid', 'approved'], true)) {
                throw ValidationException::withMessages(['harvest' => 'Paid or approved harvests cannot be reviewed again.']);
            }

            $locked->forceFill([
                'status' => 'Pending',
                'approval_stage' => 'reviewed',
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
                'review_comment' => $data['notes'] ?? null,
                'updated_by' => auth()->id(),
            ])->save();
        });

        return redirect()->route('harvests.show', $harvest)->with('success', 'Harvest reviewed successfully.');
    }

    public function approve(Request $request, Harvest $harvest): RedirectResponse
    {
        $data = $request->validate(['notes' => ['nullable', 'string']]);

        DB::transaction(function () use ($harvest, $data): void {
            $locked = Harvest::query()->lockForUpdate()->findOrFail($harvest->id);

            if (strtolower((string) $locked->approval_stage) !== 'reviewed') {
                throw ValidationException::withMessages(['harvest' => 'Only reviewed harvests can be approved.']);
            }

            $locked->forceFill([
                'status' => 'Approved',
                'approval_stage' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'approval_date' => now(),
                'approve_comment' => $data['notes'] ?? null,
                'updated_by' => auth()->id(),
            ])->save();
        });

        return redirect()->route('harvests.show', $harvest)->with('success', 'Harvest approved successfully.');
    }

    public function reject(Request $request, Harvest $harvest): RedirectResponse
    {
        $data = $request->validate(['notes' => ['required', 'string']]);

        DB::transaction(function () use ($harvest, $data): void {
            $locked = Harvest::query()->lockForUpdate()->findOrFail($harvest->id);

            if (strtolower((string) $locked->approval_stage) === 'paid') {
                throw ValidationException::withMessages(['harvest' => 'Paid harvests cannot be rejected.']);
            }

            $locked->forceFill([
                'status' => 'Rejected',
                'approval_stage' => 'rejected',
                'rejection_reason' => $data['notes'],
                'rejection_comment' => $data['notes'],
                'rejected_by' => auth()->id(),
                'rejected_at' => now(),
                'updated_by' => auth()->id(),
            ])->save();
        });

        return redirect()->route('harvests.show', $harvest)->with('success', 'Harvest rejected successfully.');
    }

    public function pay(Request $request, Harvest $harvest): RedirectResponse
    {
        $data = $request->validate([
            'payment_method' => ['required', 'string', 'max:50'],
            'payment_reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($harvest, $data): void {
            $locked = Harvest::query()->lockForUpdate()->with(['contract', 'itemHarvests.item'])->findOrFail($harvest->id);

            if (!in_array(strtolower((string) $locked->approval_stage), ['approved', 'paid'], true) && strtoupper((string) $locked->status) !== 'APPROVED') {
                throw ValidationException::withMessages(['harvest' => 'Only approved harvests can be paid.']);
            }

            if (strtolower((string) $locked->approval_stage) === 'paid' || strtoupper((string) $locked->status) === 'PAID') {
                throw ValidationException::withMessages(['harvest' => 'Harvest is already paid.']);
            }

            $payment = $this->recordHarvestPayment($locked, $data, (int) auth()->id());

            foreach ($locked->itemHarvests as $itemHarvest) {
                $itemHarvest->forceFill([
                    'status' => 'Paid',
                    'approval_stage' => 'paid',
                    'payment_transaction_id' => $payment?->id,
                    'updated_by' => auth()->id(),
                ])->save();

                if ($itemHarvest->item) {
                    $this->applyItemBalance($itemHarvest->item, $itemHarvest);
                }
            }

            $locked->forceFill([
                'status' => 'Paid',
                'approval_stage' => 'paid',
                'payment_method' => $data['payment_method'],
                'payment_reference' => $data['payment_reference'] ?? $locked->payment_reference,
                'paid_by' => auth()->id(),
                'paid_at' => now(),
                'updated_by' => auth()->id(),
            ])->save();
        });

        return redirect()->route('harvests.show', $harvest)->with('success', 'Harvest paid successfully.');
    }

    public function export(Request $request)
    {
        $query = $this->filteredHarvestsQuery($request)
            ->with(['contract.member', 'contract.group', 'contract.project', 'itemHarvests.item'])
            ->orderByDesc('harvest_date')
            ->orderByDesc('id');

        $filename = 'harvests_export_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($query): void {
            $out = fopen('php://output', 'w');
            fwrite($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($out, ['Harvest', 'Contract', 'Owner', 'Project', 'Item', 'Type', 'Amount', 'Quantity', 'Net', 'Status', 'Stage', 'Date']);

            $query->chunk(500, function ($harvests) use ($out): void {
                foreach ($harvests as $harvest) {
                    $contract = $harvest->contract;
                    $owner = strtolower((string) optional($contract)->contract_for) === 'group'
                        ? (string) optional(optional($contract)->group)->group_name
                        : (string) optional(optional($contract)->member)->full_name;
                    $itemName = optional(optional($harvest->itemHarvests->first())->item)->item_name;

                    fputcsv($out, [
                        $harvest->id,
                        $this->csvSafe(optional($contract)->contract_number),
                        $this->csvSafe($owner),
                        $this->csvSafe(optional(optional($contract)->project)->project_name),
                        $this->csvSafe($itemName),
                        $this->csvSafe($harvest->harvest_type),
                        number_format((float) $harvest->amount_harvested, 2, '.', ''),
                        number_format((float) $harvest->quantity_harvested, 2, '.', ''),
                        number_format((float) $harvest->net_amount, 2, '.', ''),
                        $this->csvSafe($harvest->status),
                        $this->csvSafe($harvest->approval_stage),
                        optional($harvest->harvest_date)->format('Y-m-d') ?? '',
                    ]);
                }
            });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function createHarvest(array $data, int $userId): Harvest
    {
        $contract = Contract::query()->with(['member', 'group', 'project', 'branch'])->findOrFail((int) $data['contract_id']);
        $item = !empty($data['contract_item_id'])
            ? ContractItem::query()->lockForUpdate()->find((int) $data['contract_item_id'])
            : null;

        if ($item && $item->contract_id !== $contract->id) {
            throw ValidationException::withMessages(['contract_item_id' => 'The selected item does not belong to the selected contract.']);
        }

        $amount = round((float) ($data['amount_harvested'] ?? 0), 2);
        $quantity = round((float) ($data['quantity_harvested'] ?? 0), 2);
        $goats = (int) ($data['number_of_goats_harvested'] ?? 0);
        $fees = 0.0;
        $netAmount = max(0, round($amount - $fees, 2));

        // Server-side cap: a harvest cannot exceed the item's remaining balance
        // minus other in-flight (pending/reviewed/approved, unpaid) harvests.
        if ($item) {
            $itemBalance = (float) ($item->balance_amount ?? $item->projected_harvest_balance ?? 0);
            $committed = (float) ContractItemHarvest::query()
                ->where('contract_item_id', $item->id)
                ->whereHas('harvest', function ($q): void {
                    $q->whereNotIn('approval_stage', ['paid', 'rejected'])
                        ->whereNotIn('status', ['Paid', 'Rejected']);
                })
                // Locking read: under REPEATABLE READ a plain SUM could use a
                // snapshot taken before a concurrent harvest committed, letting
                // two requests over-commit the same item.
                ->lockForUpdate()
                ->sum('amount_harvested');
            $available = max(0, round($itemBalance - $committed, 2));

            if ($amount > $available) {
                throw ValidationException::withMessages([
                    'amount_harvested' => 'Harvest amount cannot exceed the item\'s remaining balance (' . number_format($available, 2) . ').',
                ]);
            }
        }

        $harvest = Harvest::forceCreate([
            'branch_id' => $contract->branch_id ?: 1,
            'contract_id' => $contract->id,
            'member_id' => $contract->member_id,
            'group_id' => $contract->group_id,
            'owner_type' => strtolower((string) ($contract->contract_for ?? 'member')),
            'harvest_type' => $data['harvest_type'],
            'harvest_date' => $data['harvest_date'],
            'periods_due' => max(1, (int) ($data['periods_due'] ?? 1)),
            'projected_harvest_amount' => $item ? (float) ($item->projected_harvest_amount ?? $item->harvest_amount ?? 0) : (float) ($contract->contract_amount ?? 0),
            'seasonal_harvest_amount' => $item ? (float) ($item->seasonal_harvest_amount ?? 0) : null,
            'seasonal_harvest_quantity' => $item ? (float) ($item->seasonal_harvest_quantity ?? 0) : null,
            'amount_harvested' => $amount,
            'quantity_harvested' => $quantity,
            'number_of_goats_harvested' => $goats > 0 ? $goats : null,
            'balance_amount' => $item ? max(0, (float) ($item->balance_amount ?? $item->projected_harvest_balance ?? 0) - $amount) : null,
            'balance_quantity' => $item ? max(0, (float) ($item->balance_quantity ?? $item->harvest_quantity ?? 0) - $quantity) : null,
            'status' => 'Pending',
            'approval_stage' => 'review',
            'payment_method' => $data['payment_method'] ?? null,
            'payment_reference' => $data['payment_reference'] ?? null,
            'notes' => $data['notes'] ?? null,
            'created_by' => $userId,
            'total_fees' => $fees,
            'net_amount' => $netAmount,
            'project_kind' => $item?->classification ?? $item?->item_type,
            'project_name_snap' => $item?->project_name ?? optional($contract->project)->project_name,
        ]);

        if ($item) {
            ContractItemHarvest::forceCreate([
                'contract_item_id' => $item->id,
                'harvest_id' => $harvest->id,
                'contract_id' => $contract->id,
                'project_kind' => $item->classification ?? $item->item_type,
                'harvest_type' => $data['harvest_type'],
                'harvest_mode' => $data['harvest_type'],
                'harvest_date' => $data['harvest_date'],
                'start_date' => $item->start_date ?? $contract->start_date,
                'end_date' => $item->end_date ?? $contract->end_date,
                'periods_due' => max(1, (int) ($data['periods_due'] ?? 1)),
                'withdrawable_amount' => $amount,
                'withdrawable_quantity' => $quantity,
                'projected_harvest_amount' => (float) ($item->projected_harvest_amount ?? $item->harvest_amount ?? 0),
                'projected_harvest_quantity' => (float) ($item->expected_quantity ?? $item->harvest_quantity ?? 0),
                'amount_payable' => $amount,
                'gross_entitlement' => $amount,
                'amount_harvested' => $amount,
                'quantity_harvested' => $quantity,
                'balance_before_amount' => (float) ($item->balance_amount ?? $item->projected_harvest_balance ?? 0),
                'balance_after_amount' => max(0, (float) ($item->balance_amount ?? $item->projected_harvest_balance ?? 0) - $amount),
                'balance_before_quantity' => (float) ($item->balance_quantity ?? $item->harvest_quantity ?? 0),
                'balance_after_quantity' => max(0, (float) ($item->balance_quantity ?? $item->harvest_quantity ?? 0) - $quantity),
                'net_amount' => $netAmount,
                'number_of_goats_harvested' => $goats,
                'bee_sub_type' => $item->bee_sub_type,
                'goat_contract_mode' => $item->goat_contract_mode,
                'notes' => $data['notes'] ?? null,
                'status' => 'Pending Review',
                'approval_stage' => 'review',
                'created_by' => $userId,
            ]);
        }

        return $harvest->refresh();
    }

    protected function filteredHarvestsQuery(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $statusFilter = trim((string) $request->query('status', ''));
        $stageFilter = trim((string) $request->query('stage', ''));

        $query = Harvest::query();

        if ($search !== '') {
            $like = '%' . $search . '%';
            $query->where(function ($q) use ($like): void {
                $q->where('harvest_type', 'like', $like)
                    ->orWhere('status', 'like', $like)
                    ->orWhere('approval_stage', 'like', $like)
                    ->orWhere('payment_reference', 'like', $like)
                    ->orWhereHas('contract', function ($cq) use ($like): void {
                        $cq->where('contract_number', 'like', $like);
                    })
                    ->orWhereHas('contract.member', function ($mq) use ($like): void {
                        $mq->whereRaw("TRIM(CONCAT_WS(' ', COALESCE(first_name,''), COALESCE(last_name,''), COALESCE(other_name,''))) LIKE ?", [$like]);
                    })
                    ->orWhereHas('contract.group', function ($gq) use ($like): void {
                        $gq->where('group_name', 'like', $like)->orWhere('group_code', 'like', $like);
                    });
            });
        }

        if ($statusFilter !== '') {
            $query->where('status', $statusFilter);
        }

        if ($stageFilter !== '') {
            $query->where('approval_stage', $stageFilter);
        }

        return $query;
    }

    protected function recordHarvestPayment(Harvest $harvest, array $data, int $userId): ?PaymentTransaction
    {
        $amount = (float) ($harvest->net_amount ?: $harvest->amount_harvested ?: 0);
        if ($amount <= 0) {
            return null;
        }

        $method = (string) ($data['payment_method'] ?? $harvest->payment_method ?? 'Cash');
        $paymentMethod = $this->paymentMethod($method);
        $type = $this->harvestPaymentType();

        return PaymentTransaction::forceCreate([
            'transaction_number' => 'TXN-HRV-' . now()->format('YmdHis') . '-' . random_int(100, 999),
            'account_or_mobile' => $method === 'Cash' ? 'Not Applicable' : ($data['payment_reference'] ?? $harvest->payment_reference),
            'payment_type' => 'harvest_payment',
            'payer_type' => strtolower((string) ($harvest->owner_type ?? 'member')) === 'group' ? 'group' : 'member',
            'member_id' => $harvest->member_id,
            'group_id' => $harvest->group_id,
            'contract_id' => $harvest->contract_id,
            'chart_account_id' => $paymentMethod?->chart_account_id,
            'project_id' => $harvest->contract?->project_id,
            'branch_id' => $harvest->branch_id,
            'transaction_type_id' => $type->id,
            'transaction_date' => now(),
            'amount' => $amount,
            'penalties' => 0,
            'processing_fee' => 0,
            'other_charges' => 0,
            'total_fees' => 0,
            'net_amount' => $amount,
            'payment_method_id' => $paymentMethod?->id,
            'contract_amount' => 0,
            'contract_amount_paid' => 0,
            'contract_amount_outstanding' => 0,
            'total_amount' => $amount,
            'total_amount_paid' => $amount,
            'total_amount_outstanding' => 0,
            'reference' => $data['payment_reference'] ?? $harvest->payment_reference,
            'receipt_number' => 'HRV-' . now()->format('YmdHis') . '-' . random_int(100, 999),
            'notes' => $data['notes'] ?? $harvest->notes,
            'status' => 'APPROVED',
            'reconciliation_status' => 'UNRECONCILED',
            'created_by' => $userId,
            'approved_by' => $userId,
            'approval_date' => now(),
        ]);
    }

    protected function applyItemBalance(ContractItem $item, ContractItemHarvest $itemHarvest): void
    {
        $paidAmount = round((float) ($item->paid_amount ?? 0) + (float) ($itemHarvest->amount_harvested ?? 0), 2);
        $paidQuantity = round((float) ($item->paid_quantity ?? 0) + (float) ($itemHarvest->quantity_harvested ?? 0), 2);

        // paid_count / paid_amount / paid_quantity / last_harvest_date are real
        // schema columns but intentionally NOT in ContractItem::$fillable, so
        // a plain update() would silently drop them. forceFill matches the
        // batch pattern and keeps paid tracking / next-due-date working.
        $item->forceFill([
            'paid_count' => (int) ($item->paid_count ?? 0) + max(1, (int) ($itemHarvest->periods_due ?? 1)),
            'paid_amount' => $paidAmount,
            'paid_quantity' => $paidQuantity,
            'harvested_amount' => round((float) ($item->harvested_amount ?? 0) + (float) ($itemHarvest->amount_harvested ?? 0), 2),
            'harvested_quantity' => round((float) ($item->harvested_quantity ?? 0) + (float) ($itemHarvest->quantity_harvested ?? 0), 2),
            'balance_amount' => (float) $itemHarvest->balance_after_amount,
            'balance_quantity' => (float) $itemHarvest->balance_after_quantity,
            'last_harvest_date' => $itemHarvest->harvest_date,
        ])->save();
    }

    protected function paymentMethod(string $method): ?PaymentMethod
    {
        if ($method === 'Bank') {
            return PaymentMethod::query()->where('method_name', 'like', '%Bank%')->first();
        }

        return PaymentMethod::query()->where('method_name', $method)->first();
    }

    protected function harvestPaymentType(): PaymentTransactionType
    {
        $type = PaymentTransactionType::query()->where('type_name', 'Harvest Payment')->first();

        return $type ?: PaymentTransactionType::query()->create([
            'type_name' => 'Harvest Payment',
            'description' => 'Harvest withdrawal/payment transaction',
            'is_active' => 1,
        ]);
    }

    protected function csvSafe(mixed $value): string
    {
        $value = (string) ($value ?? '');
        if ($value !== '' && in_array($value[0], ['=', '+', '-', '@'], true)) {
            return "'" . $value;
        }

        return $value;
    }
}
