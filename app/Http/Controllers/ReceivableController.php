<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReceivablePaymentRequest;
use App\Http\Requests\StoreReceivableRequest;
use App\Models\Branch;
use App\Models\Group;
use App\Models\Member;
use App\Models\PaymentMethod;
use App\Models\PaymentTransaction;
use App\Models\PaymentTransactionType;
use App\Models\Receivable;
use App\Models\ReceivablePayment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ReceivableController extends Controller
{
    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
            new Middleware('user.status'),
            (new Middleware('permission:receivables_view'))->only(['index', 'show']),
            (new Middleware('permission:receivables_create'))->only(['create', 'store', 'pay']),
            (new Middleware('permission:receivables_edit'))->only(['edit', 'update']),
            (new Middleware('permission:receivables_delete'))->only(['destroy']),
            (new Middleware('permission:receivables_export'))->only(['export']),
            (new Middleware('throttle:30,1'))->only(['store', 'update', 'destroy', 'pay']),
        ];
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $statusFilter = trim((string) $request->query('status', ''));
        $categoryFilter = trim((string) $request->query('category', ''));
        $perPage = max(10, min(100, (int) $request->query('per_page', 25)));

        $query = $this->filteredReceivablesQuery($request)
            ->with(['member', 'group', 'branch'])
            ->orderByDesc('received_date')
            ->orderByDesc('id');

        $totalPayable = (clone $query)->sum('net_amount_payable');
        $totalPaid = (clone $query)->sum('amount_paid');
        $totalOutstanding = (clone $query)->sum('outstanding_balance');
        $receivables = $query->paginate($perPage)->withQueryString();

        return view('receivables.index', compact(
            'receivables',
            'search',
            'statusFilter',
            'categoryFilter',
            'perPage',
            'totalPayable',
            'totalPaid',
            'totalOutstanding'
        ));
    }

    public function create(): View
    {
        return view('receivables.create', $this->formData());
    }

    public function store(StoreReceivableRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $userId = (int) auth()->id();

        $receivable = DB::transaction(function () use ($data, $userId): Receivable {
            $payload = $this->payload($data, $userId);
            $payload['reference_no'] = $this->referenceNumber();
            $receivable = Receivable::forceCreate($payload);

            if ((float) $payload['amount_paid'] > 0) {
                $receiptNumber = 'RCT-' . now()->format('YmdHis') . '-' . random_int(100, 999);
                ReceivablePayment::create([
                    'receivable_id' => $receivable->id,
                    'payment_date' => $payload['received_date'],
                    'amount_paid' => $payload['amount_paid'],
                    'payment_method' => $payload['payment_method'],
                    'payment_reference' => $payload['payment_reference'] ?? null,
                    'receipt_number' => $receiptNumber,
                    'invoice_number' => $receivable->reference_no,
                    'notes' => $payload['description'] ?? null,
                    'created_by' => $userId,
                ]);

                $payment = $this->recordPayment($receivable, [
                    'payment_date' => $payload['received_date'],
                    'amount_paid' => $payload['amount_paid'],
                    'payment_method' => $payload['payment_method'],
                    'payment_reference' => $payload['payment_reference'] ?? null,
                    'notes' => $payload['description'] ?? null,
                ], $userId, $receiptNumber);

                // payment_transaction_id is intentionally NOT in
                // Receivable::$fillable, so a plain update() would silently
                // drop the FK. forceFill matches the batch pattern.
                $receivable->forceFill([
                    'payment_transaction_id' => $payment?->id,
                ])->save();
            }

            return $receivable->refresh();
        });

        return redirect()->route('receivables.show', $receivable)->with('success', 'Receivable recorded successfully.');
    }

    public function show(Receivable $receivable): View
    {
        $receivable->load(['member', 'group', 'branch', 'creator', 'paymentTransaction', 'payments.creator']);

        return view('receivables.show', compact('receivable'));
    }

    public function edit(Receivable $receivable): View
    {
        return view('receivables.edit', array_merge($this->formData(), compact('receivable')));
    }

    public function update(StoreReceivableRequest $request, Receivable $receivable): RedirectResponse
    {
        $data = $request->validated();

        // Only amount_payable / discount (and descriptive fields) may be
        // adjusted on update. amount_paid / status / payment fields are
        // managed exclusively through the pay() flow.
        unset($data['amount_paid'], $data['status'], $data['payment_method'], $data['payment_reference']);

        $payload = $this->payload($data, (int) ($receivable->created_by ?? auth()->id()), $receivable);

        // Preserve the existing paid amount and recompute outstanding from the
        // new payable figure; never let an update silently change money received.
        $payload['amount_paid'] = (float) $receivable->amount_paid;
        // `amount` tracks the amount received (legacy semantics: it mirrors the
        // payment amount, see pay()). payload() derives it from amount_paid,
        // which update() unsets, so it would be zeroed here without this line.
        $payload['amount'] = (float) $receivable->amount;
        $payload['outstanding_balance'] = max(0, round((float) $payload['net_amount_payable'] - (float) $receivable->amount_paid, 2));
        $payload['status'] = strtoupper((string) $receivable->status) === 'CANCELLED'
            ? 'Cancelled'
            : ((float) $payload['outstanding_balance'] <= 0 ? 'Received' : 'Pending');

        $receivable->forceFill($payload)->save();

        return redirect()->route('receivables.show', $receivable)->with('success', 'Receivable updated successfully.');
    }

    public function destroy(Receivable $receivable): RedirectResponse
    {
        DB::transaction(function () use ($receivable): void {
            $receivable->payments()->delete();

            // Soft-delete the linked payment transaction (payment_transactions
            // uses SoftDeletes) so the money trail is preserved but hidden from
            // reports. This matches the legacy behaviour of removing the
            // receivable while keeping the audit trail intact.
            if ($receivable->payment_transaction_id) {
                $transaction = PaymentTransaction::query()->find($receivable->payment_transaction_id);
                if ($transaction) {
                    $transaction->delete();
                }
            }

            $receivable->delete();
        });

        return redirect()->route('receivables.index')->with('success', 'Receivable deleted successfully.');
    }

    public function pay(ReceivablePaymentRequest $request, Receivable $receivable): RedirectResponse
    {
        $data = $request->validated();
        $userId = (int) auth()->id();

        DB::transaction(function () use ($receivable, $data, $userId): void {
            $locked = Receivable::query()->lockForUpdate()->findOrFail($receivable->id);
            $amount = round((float) $data['amount_paid'], 2);
            $outstanding = (float) $locked->outstanding_balance;

            if (strtoupper((string) $locked->status) === 'CANCELLED') {
                throw ValidationException::withMessages([
                    'amount_paid' => 'Cannot record a payment on a cancelled receivable.',
                ]);
            }

            if ($outstanding <= 0) {
                throw ValidationException::withMessages([
                    'amount_paid' => 'This receivable is already fully paid.',
                ]);
            }

            if ($amount > $outstanding) {
                throw ValidationException::withMessages([
                    'amount_paid' => 'Payment amount cannot exceed the outstanding balance.',
                ]);
            }

            $receiptNumber = 'RCT-' . now()->format('YmdHis') . '-' . random_int(100, 999);
            ReceivablePayment::create([
                'receivable_id' => $locked->id,
                'payment_date' => $data['payment_date'],
                'amount_paid' => $amount,
                'payment_method' => $data['payment_method'],
                'payment_reference' => $data['payment_reference'] ?? null,
                'receipt_number' => $receiptNumber,
                'invoice_number' => $locked->reference_no,
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId,
            ]);

            $newPaid = round((float) $locked->amount_paid + $amount, 2);
            $newOutstanding = max(0, round((float) $locked->net_amount_payable - $newPaid, 2));
            $payment = $this->recordPayment($locked, $data, $userId, $receiptNumber);

            $locked->forceFill([
                'amount' => $amount,
                'amount_paid' => $newPaid,
                'outstanding_balance' => $newOutstanding,
                'status' => $newOutstanding <= 0 ? 'Received' : 'Pending',
                'last_payment_date' => $data['payment_date'],
                'payment_method' => $data['payment_method'],
                'payment_reference' => $data['payment_reference'] ?? null,
                'payment_transaction_id' => $payment?->id,
            ])->save();
        });

        return redirect()->route('receivables.show', $receivable)->with('success', 'Receivable payment recorded successfully.');
    }

    public function export(Request $request)
    {
        $query = $this->filteredReceivablesQuery($request)
            ->with(['member', 'group', 'branch'])
            ->orderByDesc('received_date')
            ->orderByDesc('id');

        $filename = 'receivables_export_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($query): void {
            $out = fopen('php://output', 'w');
            fwrite($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($out, ['Reference', 'Date', 'Payer', 'Phone', 'Type', 'Category', 'Receivable Type', 'Payable', 'Paid', 'Outstanding', 'Method', 'Status']);

            $query->chunk(500, function ($receivables) use ($out): void {
                foreach ($receivables as $receivable) {
                    fputcsv($out, [
                        $this->csvSafe($receivable->reference_no),
                        optional($receivable->received_date)->format('Y-m-d') ?? '',
                        $this->csvSafe($receivable->payer_name),
                        $this->csvSafe($receivable->payer_phone),
                        $this->csvSafe($receivable->payer_type),
                        $this->csvSafe($receivable->category),
                        $this->csvSafe($receivable->receivable_type),
                        number_format((float) $receivable->net_amount_payable, 2, '.', ''),
                        number_format((float) $receivable->amount_paid, 2, '.', ''),
                        number_format((float) $receivable->outstanding_balance, 2, '.', ''),
                        $this->csvSafe($receivable->payment_method),
                        $this->csvSafe($receivable->status),
                    ]);
                }
            });

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    protected function formData(): array
    {
        return [
            'members' => Member::query()->orderBy('first_name')->orderBy('last_name')->get(),
            'groups' => Group::query()->orderBy('group_name')->get(),
            'branches' => Branch::query()->orderBy('name')->get(),
        ];
    }

    protected function payload(array $data, int $userId, ?Receivable $receivable = null): array
    {
        $amountPayable = round((float) ($data['amount_payable'] ?? 0), 2);
        $discount = round((float) ($data['discount'] ?? 0), 2);
        $netPayable = max(0, round($amountPayable - $discount, 2));
        $amountPaid = round((float) ($data['amount_paid'] ?? 0), 2);

        if ($amountPaid > $netPayable) {
            throw ValidationException::withMessages([
                'amount_paid' => 'Amount paid cannot exceed the net amount payable.',
            ]);
        }

        $outstanding = max(0, round($netPayable - $amountPaid, 2));
        // Status is always derived from the payment math; a user-supplied
        // status (Received/Pending/Cancelled) is ignored on create.
        $status = $outstanding <= 0 ? 'Received' : 'Pending';

        return [
            'received_date' => $data['received_date'],
            'member_id' => $data['member_id'] ?? null,
            'group_id' => $data['group_id'] ?? null,
            'branch_id' => $data['branch_id'] ?? null,
            'payer_name' => $data['payer_name'],
            'payer_phone' => $data['payer_phone'] ?? null,
            'receiver_email' => $data['receiver_email'] ?? null,
            'payer_type' => $data['payer_type'],
            'group_name' => $data['group_name'] ?? null,
            'category' => $data['category'],
            'receivable_type' => $data['receivable_type'],
            'other_type' => $data['other_type'] ?? null,
            'amount' => $amountPaid,
            'amount_payable' => $amountPayable,
            'discount' => $discount,
            'net_amount_payable' => $netPayable,
            'amount_paid' => $amountPaid,
            'outstanding_balance' => $outstanding,
            'income_type' => 'Income',
            'payment_method' => $data['payment_method'] ?? optional($receivable)->payment_method,
            'payment_reference' => $data['payment_reference'] ?? optional($receivable)->payment_reference,
            'status' => $status,
            'description' => $data['description'] ?? null,
            'last_payment_date' => $amountPaid > 0 ? $data['received_date'] : optional($receivable)->last_payment_date,
            'created_by' => $receivable?->created_by ?? $userId,
        ];
    }

    protected function recordPayment(Receivable $receivable, array $data, int $userId, ?string $receiptNumber = null): ?PaymentTransaction
    {
        $amount = round((float) ($data['amount_paid'] ?? 0), 2);
        if ($amount <= 0) {
            return null;
        }

        $method = (string) ($data['payment_method'] ?? $receivable->payment_method ?? 'Cash');
        $paymentMethod = $this->paymentMethod($method);
        $type = $this->transactionType();
        $reference = $data['payment_reference'] ?? $receivable->payment_reference ?? $receivable->reference_no;

        return PaymentTransaction::forceCreate([
            'transaction_number' => 'TXN-RCV-' . now()->format('YmdHis') . '-' . random_int(100, 999),
            'account_or_mobile' => $method === 'Cash' ? 'Not Applicable' : $reference,
            'payment_type' => 'income',
            'payer_type' => $receivable->group_id ? 'group' : 'member',
            'member_id' => $receivable->member_id,
            'group_id' => $receivable->group_id,
            'contract_id' => null,
            'chart_account_id' => $receivable->chart_account_id,
            'project_id' => null,
            'branch_id' => $receivable->branch_id,
            'transaction_type_id' => $type?->id,
            'transaction_date' => $data['payment_date'] ?? $receivable->received_date,
            'amount' => $amount,
            'penalties' => 0,
            'processing_fee' => 0,
            'other_charges' => 0,
            'total_fees' => 0,
            'net_amount' => $amount,
            'payment_method_id' => $paymentMethod?->id,
            'membership_fee' => 0,
            'membership_fee_paid' => 0,
            'membership_outstanding' => 0,
            'registration_fee' => 0,
            'registration_fee_paid' => 0,
            'registration_outstanding' => 0,
            'admin_fee' => 0,
            'admin_fee_paid' => 0,
            'admin_fee_outstanding' => 0,
            'contract_amount' => 0,
            'contract_amount_paid' => 0,
            'contract_amount_outstanding' => 0,
            'total_amount' => $amount,
            'total_amount_paid' => $amount,
            'total_amount_outstanding' => 0,
            'reference' => $reference,
            'receipt_number' => $receiptNumber ?: 'RCV-' . now()->format('YmdHis') . '-' . random_int(100, 999),
            'notes' => $data['notes'] ?? $receivable->description,
            'status' => 'APPROVED',
            'reconciliation_status' => 'UNRECONCILED',
            'created_by' => $userId,
            'approved_by' => $userId,
            'approval_date' => now(),
        ]);
    }

    protected function paymentMethod(string $method): ?PaymentMethod
    {
        $query = PaymentMethod::query();

        if ($method === 'Bank') {
            return $query->where('method_name', 'like', '%Bank%')->first();
        }

        return $query->where('method_name', $method)->first();
    }

    protected function transactionType(): ?PaymentTransactionType
    {
        // Prefer 'Receivable Income' over 'Receivable Payment'. The previous
        // MySQL-only FIELD() ordering broke on sqlite (no such function: FIELD);
        // CASE WHEN is portable and preserves the same preference.
        $type = PaymentTransactionType::query()
            ->whereIn('type_name', ['Receivable Income', 'Receivable Payment'])
            ->orderByRaw("CASE WHEN type_name = 'Receivable Income' THEN 0 ELSE 1 END")
            ->first();

        return $type ?: PaymentTransactionType::query()->create([
            'type_name' => 'Receivable Income',
            'description' => 'Receivable income payment',
            'is_active' => 1,
        ]);
    }

    protected function filteredReceivablesQuery(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $statusFilter = trim((string) $request->query('status', ''));
        $categoryFilter = trim((string) $request->query('category', ''));

        $query = Receivable::query();

        if ($search !== '') {
            $like = '%' . $search . '%';
            $query->where(function ($q) use ($like): void {
                $q->where('reference_no', 'like', $like)
                    ->orWhere('payer_name', 'like', $like)
                    ->orWhere('payer_phone', 'like', $like)
                    ->orWhere('receivable_type', 'like', $like)
                    ->orWhere('description', 'like', $like)
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

        if ($categoryFilter !== '') {
            $query->where('category', $categoryFilter);
        }

        return $query;
    }

    protected function referenceNumber(): string
    {
        do {
            $reference = 'RCV-' . now()->format('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
        } while (Receivable::query()->where('reference_no', $reference)->exists());

        return $reference;
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
