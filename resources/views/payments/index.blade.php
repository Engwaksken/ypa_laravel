@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h4 class="mb-0">Payments</h4>
            <small class="text-muted">Contract payment ledger</small>
        </div>
        <div class="d-flex gap-2">
            @if(app(\App\Services\PermissionService::class)->can('payments_export'))
                <a href="{{ route('payments.export', request()->query()) }}" class="btn btn-outline-secondary">Export CSV</a>
            @endif
            @if(app(\App\Services\PermissionService::class)->can('new_payments'))
                <a href="{{ route('payments.create') }}" class="btn btn-success">Record Payment</a>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="Transaction, receipt, contract...">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All</option>
                        @foreach(['PENDING','APPROVED','REJECTED','CANCELLED'] as $status)
                            <option value="{{ $status }}" @selected($statusFilter === $status)>{{ $status }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-select">
                        <option value="">All</option>
                        @foreach($types as $type)
                            <option value="{{ $type->type_name }}" @selected($typeFilter === $type->type_name)>{{ $type->type_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Per Page</label>
                    <select name="per_page" class="form-select">
                        @foreach([10,25,50,100] as $size)
                            <option value="{{ $size }}" @selected((int) $perPage === $size)>{{ $size }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1 d-grid">
                    <button class="btn btn-primary" type="submit">Go</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong>Transactions</strong>
            <span class="text-muted">Total: {{ number_format((float) $totalAmount, 2) }}</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Transaction</th>
                        <th>Contract</th>
                        <th>Party</th>
                        <th>Method</th>
                        <th class="text-end">Amount</th>
                        <th>Receipt</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $payment)
                        <tr>
                            <td>{{ $payment->transaction_number }}</td>
                            <td>{{ $payment->contract->contract_number ?? '-' }}</td>
                            <td>
                                {{ strtolower((string) optional($payment->contract)->contract_for) === 'group' ? optional($payment->contract->group)->group_name : optional(optional($payment->contract)->member)->full_name }}
                            </td>
                            <td>{{ $payment->paymentMethod->method_name ?? '-' }}</td>
                            <td class="text-end">{{ number_format((float) $payment->amount, 2) }}</td>
                            <td>{{ $payment->receipt_number ?? '-' }}</td>
                            <td>{{ $payment->transactionType->type_name ?? ucfirst(str_replace('_', ' ', (string) $payment->payment_type)) }}</td>
                            <td>{{ $payment->status }}</td>
                            <td>{{ optional($payment->transaction_date)->format('Y-m-d H:i') ?? '-' }}</td>
                            <td class="text-nowrap">
                                <a href="{{ route('payments.show', $payment) }}" class="btn btn-sm btn-outline-secondary">View</a>
                                @if($payment->receipt_number)
                                    <a href="{{ route('payments.receipt', $payment) }}" class="btn btn-sm btn-outline-primary">Receipt</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="text-center text-muted py-4">No payment records found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body">
            {{ $payments->links() }}
        </div>
    </div>
</div>
@endsection
