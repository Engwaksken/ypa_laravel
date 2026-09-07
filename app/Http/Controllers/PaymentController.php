<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContractPaymentRequest;
use App\Models\Contract;
use App\Models\PaymentMethod;
use App\Models\PaymentTransaction;
use App\Models\PaymentTransactionType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
            new Middleware('user.status'),
            (new Middleware('permission:payments_view'))->only(['index', 'show', 'receipt']),
            (new Middleware('permission:new_payments'))->only(['create', 'store']),
            (new Middleware('permission:payments_export'))->only(['export']),
            (new Middleware('permission:payments_maintain'))->only(['approve', 'reject', 'reconcile']),
        ];
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $statusFilter = trim((string) $request->query('status', ''));
        $typeFilter = trim((string) $request->query('type', ''));
        $perPage = max(10, min(100, (int) $request->query('per_page', 25)));

        $query = PaymentTransaction::query()
            ->with(['contract.member', 'contract.group', 'paymentMethod', 'transactionType', 'creator'])
            ->orderByDesc('transaction_date')
            ->orderByDesc('id');

        if ($search !== '') {
            $like = '%' . $search . '%';
            $query->where(function ($q) use ($like): void {
                $q->where('transaction_number', 'like', $like)
                    ->orWhere('receipt_number', 'like', $like)
                    ->orWhere('reference', 'like', $like)
                    ->orWhere('payment_type', 'like', $like)
                    ->orWhere('notes', 'like', $like)
                    ->orWhereHas('contract', function ($cq) use ($like): void {
                        $cq->where('contract_number', 'like', $like);
                    })
                    ->orWhereHas('paymentMethod', function ($pmq) use ($like): void {
                        $pmq->where('method_name', 'like', $like);
                    })
                    ->orWhereHas('transactionType', function ($ttq) use ($like): void {
                        $ttq->where('type_name', 'like', $like);
                    })
                    ->orWhereHas('member', function ($mq) use ($like): void {
                        $mq->whereRaw("TRIM(CONCAT_WS(' ', COALESCE(first_name,''), COALESCE(last_name,''), COALESCE(other_name,''))) LIKE ?", [$like]);
                    })
                    ->orWhereHas('group', function ($gq) use ($like): void {
                        $gq->where('group_name', 'like', $like)
                            ->orWhere('group_code', 'like', $like);
                    });
            });
        }

        if ($statusFilter !== '') {
            $query->where('status', $statusFilter);
        }

        if ($typeFilter !== '') {
            $query->whereHas('transactionType', function ($tq) use ($typeFilter): void {
                $tq->where('type_name', $typeFilter);
            });
        }

        $totalAmount = (clone $query)->sum('amount');
        $payments = $query->paginate($perPage)->withQueryString();
        $types = PaymentTransactionType::query()->where('is_active', 1)->orderBy('type_name')->get();

        return view('payments.index', compact('payments', 'search', 'statusFilter', 'typeFilter', 'perPage', 'totalAmount', 'types'));
    }

    public function create(Request $request): View
    {
        $contractId = (int) $request->query('contract_id', 0);

        $contract = $contractId > 0
            ? Contract::query()->with(['member', 'group', 'project', 'branch', 'paymentMethod'])->find($contractId)
            : null;

        return view('payments.create', [
            'contract' => $contract,
            'contracts' => Contract::query()->with(['member', 'group'])->whereNotIn('status', ['TERMINATED', 'CANCELLED', 'COMPLETED'])->orderByDesc('id')->limit(200)->get(),
            'paymentMethods' => PaymentMethod::query()->where('status', 'active')->orderBy('sort_order')->orderBy('method_name')->get(),
            'transactionTypes' => PaymentTransactionType::query()->where('is_active', 1)->orderBy('type_name')->get(),
        ]);
    }

    public function store(ContractPaymentRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $userId = (int) auth()->id();

        $payment = DB::transaction(function () use ($data, $userId): PaymentTransaction {
            $contract = Contract::query()->lockForUpdate()->with(['member', 'group', 'project', 'branch'])->findOrFail((int) $data['contract_id']);

            if (strtoupper((string) ($contract->status ?? '')) !== 'ACTIVE') {
                abort(422, 'Only active contracts can receive payments.');
            }

            $contractAmount = (float) ($contract->contract_amount ?? $contract->total_amount ?? 0);
            $existingPaid = (float) PaymentTransaction::query()
                ->where('contract_id', $contract->id)
                ->whereRaw("UPPER(COALESCE(status, '')) = 'APPROVED'")
                ->selectRaw('COALESCE(SUM(CASE WHEN contract_amount_paid > 0 THEN contract_amount_paid ELSE amount END), 0) AS paid_total')
                ->value('paid_total');
            $paymentDate = isset($data['payment_date']) && $data['payment_date'] !== ''
                ? \Illuminate\Support\Carbon::parse($data['payment_date'])->startOfDay()
                : now();
            $amount = round((float) $data['amount'], 2);
            $existingOutstanding = max(0, round($contractAmount - $existingPaid, 2));
            if ($existingOutstanding > 0 && $amount > $existingOutstanding) {
                throw ValidationException::withMessages([
                    'amount' => 'Payment amount cannot be greater than the outstanding balance.',
                ]);
            }

            $newPaid = round($existingPaid + $amount, 2);
            $newOutstanding = max(0, round($contractAmount - $newPaid, 2));

            $paymentMethod = PaymentMethod::query()->find((int) $data['payment_method_id']);
            $paymentMethodId = $paymentMethod?->id;
            $chartAccountId = $paymentMethod?->chart_account_id;

            $payment = PaymentTransaction::forceCreate([
                'transaction_number' => $this->transactionNumber(),
                'account_or_mobile' => $data['reference'] ?? null,
                'payment_type' => 'contract_payment',
                'payer_type' => strtolower((string) ($contract->contract_for ?? 'member')) === 'group' ? 'group' : 'member',
                'member_id' => $contract->member_id,
                'group_id' => $contract->group_id,
                'contract_id' => $contract->id,
                'chart_account_id' => $chartAccountId,
                'project_id' => $contract->project_id,
                'branch_id' => $contract->branch_id,
                'transaction_type_id' => $this->contractPaymentType()->id,
                'transaction_date' => $paymentDate->toDateTimeString(),
                'amount' => $amount,
                'penalties' => 0,
                'processing_fee' => 0,
                'other_charges' => 0,
                'total_fees' => 0,
                'deduction_description' => $data['notes'] ?? null,
                'net_amount' => $amount,
                'payment_method_id' => $paymentMethodId,
                'membership_fee' => 0,
                'membership_fee_paid' => 0,
                'membership_outstanding' => 0,
                'registration_fee' => 0,
                'registration_fee_paid' => 0,
                'registration_outstanding' => 0,
                'admin_fee' => 0,
                'admin_fee_paid' => 0,
                'admin_fee_outstanding' => 0,
                'contract_amount' => $contractAmount,
                'contract_amount_paid' => $amount,
                'contract_amount_outstanding' => $newOutstanding,
                'total_amount' => $contractAmount,
                'total_amount_paid' => $amount,
                'total_amount_outstanding' => $newOutstanding,
                'reference' => $data['reference'] ?? null,
                'receipt_number' => $this->receiptNumber(),
                'notes' => $data['notes'] ?? null,
                'status' => 'PENDING',
                'reconciliation_status' => 'UNRECONCILED',
                'created_by' => $userId,
                'approved_by' => null,
                'approval_date' => null,
            ]);

            // Contract paid/outstanding totals are NOT touched here: a PENDING
            // payment has not been approved yet. recalculateContractTotals()
            // runs when the payment is approved and only counts APPROVED rows.
            return $payment;
        });

        return redirect()->route('payments.show', $payment)->with('success', 'Payment recorded successfully.');
    }

    public function show(PaymentTransaction $payment): View
    {
        $payment->load(['contract.member', 'contract.group', 'contract.project', 'contract.branch', 'paymentMethod', 'transactionType', 'creator', 'approver']);

        return view('payments.show', compact('payment'));
    }

    public function approve(Request $request, PaymentTransaction $payment): RedirectResponse
    {
        $data = $request->validate([
            'notes' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($payment, $data): void {
            $locked = PaymentTransaction::query()->lockForUpdate()->findOrFail($payment->id);

            if (strtoupper((string) $locked->status) !== 'PENDING') {
                abort(422, 'Only pending payments can be approved.');
            }

            if ($locked->created_by === null) {
                throw ValidationException::withMessages(['payment' => 'Payment has no recorded creator and cannot be approved.']);
            }

            if ((int) $locked->created_by === (int) auth()->id()) {
                throw ValidationException::withMessages(['payment' => 'You cannot approve a payment you recorded.']);
            }

            $locked->forceFill([
                'status' => 'APPROVED',
                'approved_by' => auth()->id(),
                'approval_date' => now(),
                'notes' => $this->appendActionNote($locked->notes, 'APPROVED', $data['notes'] ?? null),
            ])->save();

            if ($locked->contract_id) {
                $this->recalculateContractTotals((int) $locked->contract_id);
            }
        });

        return redirect()->route('payments.show', $payment)->with('success', 'Payment approved successfully.');
    }

    public function reject(Request $request, PaymentTransaction $payment): RedirectResponse
    {
        $data = $request->validate([
            'notes' => ['required', 'string'],
        ]);

        DB::transaction(function () use ($payment, $data): void {
            $locked = PaymentTransaction::query()->lockForUpdate()->findOrFail($payment->id);

            if (strtoupper((string) $locked->status) !== 'PENDING') {
                abort(422, 'Only pending payments can be rejected.');
            }

            if ($locked->created_by === null) {
                throw ValidationException::withMessages(['payment' => 'Payment has no recorded creator and cannot be rejected.']);
            }

            if ((int) $locked->created_by === (int) auth()->id()) {
                throw ValidationException::withMessages(['payment' => 'You cannot reject a payment you recorded.']);
            }

            $locked->forceFill([
                'status' => 'REJECTED',
                'approved_by' => auth()->id(),
                'approval_date' => now(),
                'notes' => $this->appendActionNote($locked->notes, 'REJECTED', $data['notes']),
            ])->save();
        });

        return redirect()->route('payments.show', $payment)->with('success', 'Payment rejected successfully.');
    }

    public function reconcile(PaymentTransaction $payment): RedirectResponse
    {
        DB::transaction(function () use ($payment): void {
            $locked = PaymentTransaction::query()->lockForUpdate()->findOrFail($payment->id);

            if (strtoupper((string) $locked->status) !== 'APPROVED') {
                abort(422, 'Only approved payments can be reconciled.');
            }

            if (strtoupper((string) $locked->reconciliation_status) === 'RECONCILED') {
                abort(422, 'Payment is already reconciled.');
            }

            $locked->forceFill([
                'reconciliation_status' => 'RECONCILED',
                'reconciled_by' => auth()->id(),
                'reconciled_date' => now(),
            ])->save();
        });

        return redirect()->route('payments.show', $payment)->with('success', 'Payment reconciled successfully.');
    }

    public function export(Request $request)
    {
        $query = $this->filteredPaymentsQuery($request)
            ->with(['contract.member', 'contract.group', 'paymentMethod', 'transactionType'])
            ->orderByDesc('transaction_date')
            ->orderByDesc('id');

        $filename = 'payments_export_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($query): void {
            $out = fopen('php://output', 'w');
            fwrite($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($out, ['Transaction', 'Receipt', 'Contract', 'Party', 'Type', 'Method', 'Amount', 'Status', 'Reconciliation', 'Date']);

            $query->chunk(500, function ($payments) use ($out): void {
                foreach ($payments as $payment) {
                    $contract = $payment->contract;
                    $party = strtolower((string) optional($contract)->contract_for) === 'group'
                        ? (string) optional(optional($contract)->group)->group_name
                        : (string) optional(optional($contract)->member)->full_name;

                    fputcsv($out, [
                        $this->csvSafe($payment->transaction_number),
                        $this->csvSafe($payment->receipt_number),
                        $this->csvSafe(optional($contract)->contract_number),
                        $this->csvSafe($party),
                        $this->csvSafe($payment->transactionType->type_name ?? ucfirst(str_replace('_', ' ', (string) $payment->payment_type))),
                        $this->csvSafe($payment->paymentMethod->method_name ?? ''),
                        number_format((float) $payment->amount, 2, '.', ''),
                        $this->csvSafe($payment->status),
                        $this->csvSafe($payment->reconciliation_status),
                        optional($payment->transaction_date)->format('Y-m-d H:i:s') ?? '',
                    ]);
                }
            });

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function receipt(PaymentTransaction $payment): View
    {
        $payment->load(['contract.member', 'contract.group', 'contract.project', 'contract.branch', 'paymentMethod', 'transactionType', 'creator']);

        return view('payments.receipt', compact('payment'));
    }

    protected function filteredPaymentsQuery(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $statusFilter = trim((string) $request->query('status', ''));
        $typeFilter = trim((string) $request->query('type', ''));

        $query = PaymentTransaction::query();

        if ($search !== '') {
            $like = '%' . $search . '%';
            $query->where(function ($q) use ($like): void {
                $q->where('transaction_number', 'like', $like)
                    ->orWhere('receipt_number', 'like', $like)
                    ->orWhere('reference', 'like', $like)
                    ->orWhere('payment_type', 'like', $like)
                    ->orWhere('notes', 'like', $like)
                    ->orWhereHas('contract', function ($cq) use ($like): void {
                        $cq->where('contract_number', 'like', $like);
                    })
                    ->orWhereHas('paymentMethod', function ($pmq) use ($like): void {
                        $pmq->where('method_name', 'like', $like);
                    })
                    ->orWhereHas('transactionType', function ($ttq) use ($like): void {
                        $ttq->where('type_name', 'like', $like);
                    })
                    ->orWhereHas('member', function ($mq) use ($like): void {
                        $mq->whereRaw("TRIM(CONCAT_WS(' ', COALESCE(first_name,''), COALESCE(last_name,''), COALESCE(other_name,''))) LIKE ?", [$like]);
                    })
                    ->orWhereHas('group', function ($gq) use ($like): void {
                        $gq->where('group_name', 'like', $like)
                            ->orWhere('group_code', 'like', $like);
                    });
            });
        }

        if ($statusFilter !== '') {
            $query->where('status', $statusFilter);
        }

        if ($typeFilter !== '') {
            $query->whereHas('transactionType', function ($tq) use ($typeFilter): void {
                $tq->where('type_name', $typeFilter);
            });
        }

        return $query;
    }

    protected function appendActionNote(?string $notes, string $action, ?string $extra = null): string
    {
        $line = '[' . $action . ' by user #' . auth()->id() . ' on ' . now()->format('Y-m-d H:i:s') . ']';
        if ($extra !== null && trim($extra) !== '') {
            $line .= ' ' . trim($extra);
        }

        return trim(trim((string) $notes) . PHP_EOL . $line);
    }

    protected function recalculateContractTotals(int $contractId): void
    {
        $contract = Contract::query()->lockForUpdate()->find($contractId);
        if (!$contract) {
            return;
        }

        $paid = (float) PaymentTransaction::query()
            ->where('contract_id', $contractId)
            ->whereRaw("UPPER(COALESCE(status, '')) = 'APPROVED'")
            ->selectRaw('COALESCE(SUM(CASE WHEN contract_amount_paid > 0 THEN contract_amount_paid ELSE amount END), 0) AS paid_total')
            ->value('paid_total');

        $contractAmount = (float) ($contract->contract_amount ?? $contract->total_amount ?? 0);
        $outstanding = max(0, round($contractAmount - $paid, 2));

        $contract->update([
            'amount_paid' => round($paid, 2),
            'outstanding_balance' => $outstanding,
            'total_paid' => round($paid, 2),
            'total_outstanding' => $outstanding,
            'contract_outstanding' => $outstanding,
            'status' => $outstanding <= 0 ? 'COMPLETED' : 'ACTIVE',
            'updated_by' => auth()->id(),
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

    protected function contractPaymentType(): PaymentTransactionType
    {
        // firstOrCreate narrows the create race window vs. a manual
        // select-then-create. A unique index on payment_transaction_types
        // (type_name) would close it entirely — flagged as a follow-up.
        return PaymentTransactionType::query()->firstOrCreate(
            ['type_name' => 'Contract Payment'],
            ['description' => 'Contract payment transaction', 'is_active' => 1]
        );
    }

    protected function transactionNumber(): string
    {
        return 'TXN-' . now()->format('YmdHis') . '-' . random_int(100, 999);
    }

    protected function receiptNumber(): string
    {
        return 'RCT-' . now()->format('YmdHis') . '-' . random_int(100, 999);
    }
}
