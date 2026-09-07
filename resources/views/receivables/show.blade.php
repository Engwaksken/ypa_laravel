@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h4 class="mb-0">{{ $receivable->reference_no }}</h4>
            <small class="text-muted">{{ $receivable->receivable_type }}</small>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            @if(app(\App\Services\PermissionService::class)->can('receivables_edit'))
                <a href="{{ route('receivables.edit', $receivable) }}" class="btn btn-outline-warning">Edit</a>
            @endif
            @if(app(\App\Services\PermissionService::class)->can('receivables_delete'))
                <form method="POST" action="{{ route('receivables.destroy', $receivable) }}" onsubmit="return confirm('Delete this receivable?')">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-outline-danger" type="submit">Delete</button>
                </form>
            @endif
            <a href="{{ route('receivables.index') }}" class="btn btn-outline-dark">Back</a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="row g-3 mb-3">
        <div class="col-md-4"><div class="card"><div class="card-body"><strong>Payer:</strong> {{ $receivable->payer_name }}</div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body"><strong>Phone:</strong> {{ $receivable->payer_phone ?: '-' }}</div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body"><strong>Status:</strong> {{ $receivable->status }}</div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body"><strong>Net Payable:</strong> {{ number_format((float) $receivable->net_amount_payable, 2) }}</div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body"><strong>Paid:</strong> {{ number_format((float) $receivable->amount_paid, 2) }}</div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body"><strong>Outstanding:</strong> {{ number_format((float) $receivable->outstanding_balance, 2) }}</div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body"><strong>Method:</strong> {{ $receivable->payment_method }}</div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body"><strong>Reference:</strong> {{ $receivable->payment_reference ?: '-' }}</div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body"><strong>Date:</strong> {{ optional($receivable->received_date)->format('Y-m-d') ?? '-' }}</div></div></div>
    </div>

    @if(app(\App\Services\PermissionService::class)->can('receivables_create') && (float) $receivable->outstanding_balance > 0 && $receivable->status !== 'Cancelled')
        <div class="card mb-3">
            <div class="card-header"><strong>Record Payment</strong></div>
            <div class="card-body">
                <form method="POST" action="{{ route('receivables.pay', $receivable) }}" class="row g-3">
                    @csrf
                    <div class="col-md-3">
                        <label class="form-label">Payment Date</label>
                        <input type="date" name="payment_date" value="{{ old('payment_date', now()->format('Y-m-d')) }}" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Amount</label>
                        <input type="number" step="0.01" min="0.01" name="amount_paid" value="{{ old('amount_paid', $receivable->outstanding_balance) }}" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Method</label>
                        <select name="payment_method" class="form-select" required>
                            @foreach(['Cash','Mobile Money','Bank'] as $method)
                                <option value="{{ $method }}" @selected(old('payment_method', $receivable->payment_method) === $method)>{{ $method }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Reference</label>
                        <input type="text" name="payment_reference" value="{{ old('payment_reference') }}" class="form-control">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" rows="2" class="form-control">{{ old('notes') }}</textarea>
                    </div>
                    <div class="col-12"><button class="btn btn-success" type="submit">Save Payment</button></div>
                </form>
            </div>
        </div>
    @endif

    <div class="card mb-3">
        <div class="card-header"><strong>Payment History</strong></div>
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead class="table-light"><tr><th>Date</th><th>Receipt</th><th>Method</th><th>Reference</th><th class="text-end">Amount</th></tr></thead>
                <tbody>
                    @forelse($receivable->payments as $payment)
                        <tr>
                            <td>{{ optional($payment->payment_date)->format('Y-m-d') ?? '-' }}</td>
                            <td>{{ $payment->receipt_number ?? '-' }}</td>
                            <td>{{ $payment->payment_method ?? '-' }}</td>
                            <td>{{ $payment->payment_reference ?? '-' }}</td>
                            <td class="text-end">{{ number_format((float) $payment->amount_paid, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-3">No payment records found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card"><div class="card-body"><strong>Description:</strong><div class="mt-2">{{ $receivable->description ?: '-' }}</div></div></div>
</div>
@endsection
