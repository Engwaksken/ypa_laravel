@extends('layouts.app')

@section('title', 'Payments')

@section('content')
@php($perm = app(\App\Services\PermissionService::class))
<div class="dash-wrap">

    <div class="dash-head">
        <div>
            <h1 class="dash-name">Payments</h1>
            <div class="dash-date">Contract payment ledger</div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            @if($perm->can('payments_export'))
                <a href="{{ route('payments.export', request()->query()) }}" class="btn btn-outline-success"><i class="fas fa-file-csv"></i> Export CSV</a>
            @endif
            @if($canRecord)
                <button type="button" class="btn btn-primary" onclick="openPaymentModal()"><i class="fas fa-plus"></i> Record Payment</button>
            @endif
        </div>
    </div>

    <div class="dash-grid d-grid-2 mb-4">
        <div class="dash-card accent-primary">
            <div class="dash-card-top">
                <span class="dash-card-title">Transactions</span>
                <span class="dash-card-icon"><i class="fas fa-receipt"></i></span>
            </div>
            <div class="dash-card-value">{{ number_format($payments->total()) }}</div>
        </div>
        <div class="dash-card accent-success">
            <div class="dash-card-top">
                <span class="dash-card-title">Total Amount</span>
                <span class="dash-card-icon"><i class="fas fa-money-bill-wave"></i></span>
            </div>
            <div class="dash-card-value">{{ number_format((float) $totalAmount, 2) }}</div>
        </div>
    </div>

    <div class="dash-panel">
        <div class="dash-panel-head">
            <span><i class="fas fa-filter"></i> Filter Payments</span>
        </div>
        <div class="dash-panel-body">
            <form method="GET" action="{{ route('payments.index') }}" class="row g-3">
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
                <div class="col-md-2">
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
                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i> Filter</button>
                    <a href="{{ route('payments.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="dash-panel mt-4">
        <div class="dash-panel-head">
            <span><i class="fas fa-list"></i> Transactions</span>
            <span class="text-muted small">{{ $payments->total() }} result(s)</span>
        </div>
        <div class="dash-panel-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Transaction</th>
                            <th>Contract</th>
                            <th>Party</th>
                            <th>Method</th>
                            <th class="num">Amount</th>
                            <th>Receipt</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payments as $payment)
                            @php
                                $statusClass = match (strtoupper((string) $payment->status)) {
                                    'APPROVED' => 'success',
                                    'PENDING' => 'warning',
                                    'REJECTED', 'CANCELLED' => 'danger',
                                    default => 'secondary',
                                };
                            @endphp
                            <tr>
                                <td><strong>{{ $payment->transaction_number }}</strong></td>
                                <td>{{ $payment->contract->contract_number ?? '-' }}</td>
                                <td>
                                    {{ strtolower((string) optional($payment->contract)->contract_for) === 'group' ? optional($payment->contract->group)->group_name : optional(optional($payment->contract)->member)->full_name }}
                                </td>
                                <td>{{ $payment->paymentMethod->method_name ?? '-' }}</td>
                                <td class="num">{{ number_format((float) $payment->amount, 2) }}</td>
                                <td>{{ $payment->receipt_number ?? '-' }}</td>
                                <td>{{ $payment->transactionType->type_name ?? ucfirst(str_replace('_', ' ', (string) $payment->payment_type)) }}</td>
                                <td><span class="badge bg-{{ $statusClass }}">{{ $payment->status }}</span></td>
                                <td>{{ optional($payment->transaction_date)->format('Y-m-d H:i') ?? '-' }}</td>
                                <td class="text-end text-nowrap">
                                    <a href="{{ route('payments.show', $payment) }}" class="btn btn-sm btn-outline-primary" title="View"><i class="fas fa-eye"></i></a>
                                    @if($payment->receipt_number)
                                        <a href="{{ route('payments.receipt', $payment) }}" class="btn btn-sm btn-outline-secondary" title="Receipt"><i class="fas fa-print"></i></a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr class="empty-row"><td colspan="10"><i class="fas fa-inbox"></i>No payment records found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="dash-panel-foot">{{ $payments->links() }}</div>
    </div>

</div>

@if($canRecord)
<div class="modal fade" id="paymentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="paymentForm" method="POST" action="{{ route('payments.store') }}">
                @csrf
                <input type="hidden" name="_method" id="paymentFormMethod" value="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="paymentModalTitle">Add Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @include('payments._fields', ['contract' => null])
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i> Save Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function openPaymentModal() {
        var form = document.getElementById('paymentForm');
        form.reset();
        form.action = "{{ route('payments.store') }}";
        document.getElementById('paymentFormMethod').value = 'POST';
        document.getElementById('paymentDate').value = "{{ now()->format('Y-m-d') }}";
        if (window.bootstrap && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('paymentModal')).show();
        }
    }

    document.getElementById('paymentContract').addEventListener('change', function () {
        var option = this.options[this.selectedIndex];
        var amount = document.getElementById('paymentAmount');
        if (option && option.dataset.outstanding && parseFloat(option.dataset.outstanding) > 0) {
            amount.value = option.dataset.outstanding;
        }
    });
</script>
@endpush
@endif
@endsection
