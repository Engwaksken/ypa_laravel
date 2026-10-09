@extends('layouts.app')

@section('title', 'Harvest #' . $harvest->id)

@section('content')
@php
    $perm = app(\App\Services\PermissionService::class);
    $stage = strtolower((string) $harvest->approval_stage);
    $statusClass = match (strtolower((string) $harvest->status)) {
        'approved' => 'primary',
        'paid' => 'success',
        'rejected' => 'danger',
        'pending' => 'warning',
        default => 'secondary',
    };
    $canReview = $perm->can('harvest_requests_review') && !in_array($stage, ['approved','paid','rejected'], true);
    $canApprove = $perm->can('harvest_requests_approve') && !in_array($stage, ['approved','paid','rejected'], true);
    $canReject = $perm->can('harvest_requests_reject') && !in_array($stage, ['paid','rejected'], true);
    $canPay = $perm->can('harvest_requests_pay') && $stage === 'approved';
@endphp
<div class="dash-wrap">
    <div class="dash-head">
        <div>
            <h1 class="dash-name">Harvest #{{ $harvest->id }}</h1>
            <div class="dash-date">{{ $harvest->contract->contract_number ?? '-' }} &middot; {{ $harvest->harvest_type }}</div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            @if($perm->can('harvest_manage'))
                <form method="POST" action="{{ route('harvests.destroy', $harvest) }}" class="d-inline ypa-confirm-delete" data-confirm-title="Delete harvest?" data-confirm-message="Delete harvest #{{ $harvest->id }}? This cannot be undone.">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-outline-danger" type="submit"><i class="fas fa-trash"></i> Delete</button>
                </form>
            @endif
            <a href="{{ route('harvests.index') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left"></i> Back</a>
        </div>
    </div>

    <div class="dash-grid mb-4">
        <div class="dash-card accent-info">
            <div class="dash-card-top">
                <span class="dash-card-title">Amount</span>
                <span class="dash-card-icon"><i class="fas fa-coins"></i></span>
            </div>
            <div class="dash-card-value">{{ number_format((float) $harvest->amount_harvested, 2) }}</div>
        </div>
        <div class="dash-card accent-purple">
            <div class="dash-card-top">
                <span class="dash-card-title">Quantity</span>
                <span class="dash-card-icon"><i class="fas fa-cubes"></i></span>
            </div>
            <div class="dash-card-value">{{ number_format((float) $harvest->quantity_harvested, 2) }}</div>
        </div>
        <div class="dash-card accent-success">
            <div class="dash-card-top">
                <span class="dash-card-title">Net</span>
                <span class="dash-card-icon"><i class="fas fa-wallet"></i></span>
            </div>
            <div class="dash-card-value">{{ number_format((float) $harvest->net_amount, 2) }}</div>
        </div>
    </div>

    <div class="dash-panel">
        <div class="dash-panel-head">
            <span><i class="fas fa-info-circle"></i> Harvest Details</span>
            <span class="badge bg-{{ $statusClass }}">{{ $harvest->status }}</span>
        </div>
        <div class="dash-panel-body">
            <div class="row g-3">
                <div class="col-md-4"><div class="text-muted small">Type</div><div class="fw-semibold">{{ $harvest->harvest_type }}</div></div>
                <div class="col-md-4"><div class="text-muted small">Stage</div><div><span class="badge bg-light text-dark border">{{ $harvest->approval_stage }}</span></div></div>
                <div class="col-md-4"><div class="text-muted small">Date</div><div class="fw-semibold">{{ optional($harvest->harvest_date)->format('Y-m-d') ?? '-' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">Payment Method</div><div class="fw-semibold">{{ $harvest->payment_method ?: '-' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">Payment Reference</div><div class="fw-semibold">{{ $harvest->payment_reference ?: '-' }}</div></div>
                <div class="col-12"><div class="text-muted small">Notes</div><div>{{ $harvest->notes ?: '-' }}</div></div>
            </div>
        </div>
    </div>

    <div class="dash-panel mt-4">
        <div class="dash-panel-head">
            <span><i class="fas fa-project-diagram"></i> Workflow</span>
        </div>
        <div class="dash-panel-body d-flex gap-2 flex-wrap">
            @if($canReview)
                <form method="POST" action="{{ route('harvests.review', $harvest) }}" class="d-flex gap-2">
                    @csrf
                    <input type="text" name="notes" class="form-control form-control-sm" placeholder="Review note">
                    <button class="btn btn-sm btn-outline-primary" type="submit">Review</button>
                </form>
            @endif
            @if($canApprove)
                <form method="POST" action="{{ route('harvests.approve', $harvest) }}" class="d-flex gap-2">
                    @csrf
                    <input type="text" name="notes" class="form-control form-control-sm" placeholder="Approval note">
                    <button class="btn btn-sm btn-success" type="submit">Approve</button>
                </form>
            @endif
            @if($canReject)
                <form method="POST" action="{{ route('harvests.reject', $harvest) }}" class="d-flex gap-2">
                    @csrf
                    <input type="text" name="notes" class="form-control form-control-sm" placeholder="Rejection reason" required>
                    <button class="btn btn-sm btn-outline-danger" type="submit">Reject</button>
                </form>
            @endif
            @if($canPay)
                <form method="POST" action="{{ route('harvests.pay', $harvest) }}" class="d-flex gap-2 flex-wrap">
                    @csrf
                    <input type="text" name="payment_method" class="form-control form-control-sm" placeholder="Payment method" value="{{ $harvest->payment_method ?: 'Cash' }}" required>
                    <input type="text" name="payment_reference" class="form-control form-control-sm" placeholder="Reference">
                    <button class="btn btn-sm btn-dark" type="submit">Pay</button>
                </form>
            @endif
            @unless($canReview || $canApprove || $canReject || $canPay)
                <span class="text-muted small">No workflow actions available.</span>
            @endunless
        </div>
    </div>

    <div class="dash-panel mt-4">
        <div class="dash-panel-head">
            <span><i class="fas fa-boxes"></i> Item Details</span>
        </div>
        <div class="dash-panel-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th class="num">Amount</th>
                            <th class="num">Quantity</th>
                            <th class="num">Before</th>
                            <th class="num">After</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($harvest->itemHarvests as $itemHarvest)
                            <tr>
                                <td><strong>{{ $itemHarvest->item->item_name ?? '-' }}</strong></td>
                                <td class="num">{{ number_format((float) $itemHarvest->amount_harvested, 2) }}</td>
                                <td class="num">{{ number_format((float) $itemHarvest->quantity_harvested, 2) }}</td>
                                <td class="num">{{ number_format((float) $itemHarvest->balance_before_amount, 2) }}</td>
                                <td class="num">{{ number_format((float) $itemHarvest->balance_after_amount, 2) }}</td>
                                <td><span class="badge bg-secondary">{{ $itemHarvest->status }}</span></td>
                            </tr>
                        @empty
                            <tr class="empty-row"><td colspan="6"><i class="fas fa-inbox"></i>No item-level harvest rows found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
