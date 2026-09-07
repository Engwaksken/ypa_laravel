@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h4 class="mb-0">Harvest #{{ $harvest->id }}</h4>
            <small class="text-muted">{{ $harvest->contract->contract_number ?? '-' }}</small>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            @if(app(\App\Services\PermissionService::class)->can('harvest_manage'))
                <form method="POST" action="{{ route('harvests.destroy', $harvest) }}" onsubmit="return confirm('Delete this harvest?')">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-outline-danger" type="submit">Delete</button>
                </form>
            @endif
            <a href="{{ route('harvests.index') }}" class="btn btn-outline-dark">Back</a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="row g-3 mb-3">
        <div class="col-md-4"><div class="card"><div class="card-body"><strong>Type:</strong> {{ $harvest->harvest_type }}</div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body"><strong>Status:</strong> {{ $harvest->status }}</div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body"><strong>Stage:</strong> {{ $harvest->approval_stage }}</div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body"><strong>Amount:</strong> {{ number_format((float) $harvest->amount_harvested, 2) }}</div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body"><strong>Quantity:</strong> {{ number_format((float) $harvest->quantity_harvested, 2) }}</div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body"><strong>Net:</strong> {{ number_format((float) $harvest->net_amount, 2) }}</div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body"><strong>Date:</strong> {{ optional($harvest->harvest_date)->format('Y-m-d') ?? '-' }}</div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body"><strong>Payment Method:</strong> {{ $harvest->payment_method ?: '-' }}</div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body"><strong>Payment Reference:</strong> {{ $harvest->payment_reference ?: '-' }}</div></div></div>
    </div>

    <div class="card mb-3">
        <div class="card-header"><strong>Workflow</strong></div>
        <div class="card-body d-flex gap-2 flex-wrap">
            @if(app(\App\Services\PermissionService::class)->can('harvest_requests_review') && !in_array(strtolower((string) $harvest->approval_stage), ['approved','paid','rejected'], true))
                <form method="POST" action="{{ route('harvests.review', $harvest) }}" class="d-flex gap-2">
                    @csrf
                    <input type="text" name="notes" class="form-control form-control-sm" placeholder="Review note">
                    <button class="btn btn-sm btn-outline-primary" type="submit">Review</button>
                </form>
            @endif
            @if(app(\App\Services\PermissionService::class)->can('harvest_requests_approve') && !in_array(strtolower((string) $harvest->approval_stage), ['approved','paid','rejected'], true))
                <form method="POST" action="{{ route('harvests.approve', $harvest) }}" class="d-flex gap-2">
                    @csrf
                    <input type="text" name="notes" class="form-control form-control-sm" placeholder="Approval note">
                    <button class="btn btn-sm btn-success" type="submit">Approve</button>
                </form>
            @endif
            @if(app(\App\Services\PermissionService::class)->can('harvest_requests_reject') && !in_array(strtolower((string) $harvest->approval_stage), ['paid','rejected'], true))
                <form method="POST" action="{{ route('harvests.reject', $harvest) }}" class="d-flex gap-2">
                    @csrf
                    <input type="text" name="notes" class="form-control form-control-sm" placeholder="Rejection reason" required>
                    <button class="btn btn-sm btn-outline-danger" type="submit">Reject</button>
                </form>
            @endif
            @if(app(\App\Services\PermissionService::class)->can('harvest_requests_pay') && strtolower((string) $harvest->approval_stage) === 'approved')
                <form method="POST" action="{{ route('harvests.pay', $harvest) }}" class="d-flex gap-2 flex-wrap">
                    @csrf
                    <input type="text" name="payment_method" class="form-control form-control-sm" placeholder="Payment method" value="{{ $harvest->payment_method ?: 'Cash' }}" required>
                    <input type="text" name="payment_reference" class="form-control form-control-sm" placeholder="Reference">
                    <button class="btn btn-sm btn-dark" type="submit">Pay</button>
                </form>
            @endif
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header"><strong>Item Details</strong></div>
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead class="table-light"><tr><th>Item</th><th class="text-end">Amount</th><th class="text-end">Quantity</th><th class="text-end">Before</th><th class="text-end">After</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse($harvest->itemHarvests as $itemHarvest)
                        <tr>
                            <td>{{ $itemHarvest->item->item_name ?? '-' }}</td>
                            <td class="text-end">{{ number_format((float) $itemHarvest->amount_harvested, 2) }}</td>
                            <td class="text-end">{{ number_format((float) $itemHarvest->quantity_harvested, 2) }}</td>
                            <td class="text-end">{{ number_format((float) $itemHarvest->balance_before_amount, 2) }}</td>
                            <td class="text-end">{{ number_format((float) $itemHarvest->balance_after_amount, 2) }}</td>
                            <td>{{ $itemHarvest->status }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-3">No item-level harvest rows found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card"><div class="card-body"><strong>Notes:</strong><div class="mt-2">{{ $harvest->notes ?: '-' }}</div></div></div>
</div>
@endsection
