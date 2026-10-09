@extends('layouts.app')
@section('title', 'Orders')
@section('content')
<div class="dash-wrap">
    <div class="dash-head">
        <div><div class="dash-greeting">Operations</div><h1 class="dash-name">Order Management</h1><div class="dash-date">Track customer orders and fulfillment</div></div>
        <a href="{{ route('place-order') }}" class="btn btn-primary"><i class="fas fa-plus"></i> New Order</a>
    </div>
    <div class="dash-grid mb-4">
        @foreach([['Total Orders','total','fa-cart-shopping'],['Pending','pending','fa-clock'],['Processing','processing','fa-spinner'],['Completed','completed','fa-circle-check'],['Revenue','revenue','fa-coins']] as [$label,$key,$icon])
            <div class="dash-card"><div class="dash-card-top"><span class="dash-card-title">{{ $label }}</span><span class="dash-card-icon"><i class="fas {{ $icon }}"></i></span></div><div class="dash-card-value">{{ $key === 'revenue' ? 'UGX '.number_format($stats[$key], 0) : number_format($stats[$key]) }}</div></div>
        @endforeach
    </div>
    <div class="dash-panel mb-4"><div class="dash-panel-body"><form method="GET" class="row g-3 align-items-end">
        <div class="col-md-5"><label class="form-label">Search</label><input name="search" value="{{ request('search') }}" class="form-control" placeholder="Order number, customer or phone"></div>
        <div class="col-md-4"><label class="form-label">Status</label><select name="status" class="form-select"><option value="">All statuses</option>@foreach(['pending','processing','completed','cancelled'] as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>@endforeach</select></div>
        <div class="col-md-3 d-flex gap-2"><button class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button><a href="{{ route('orders.index') }}" class="btn btn-outline-secondary">Reset</a></div>
    </form></div></div>
    <div class="dash-panel"><div class="dash-panel-head"><span><i class="fas fa-list"></i> Orders</span><span class="text-muted small">{{ $orders->total() }} result(s)</span></div><div class="dash-panel-body p-0"><div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th>Order</th><th>Customer</th><th>Branch</th><th>Items</th><th>Total</th><th>Status</th><th>Date</th><th class="text-end">Actions</th></tr></thead><tbody>
    @forelse($orders as $order)<tr><td><strong>{{ $order->order_number }}</strong><div class="small text-muted">{{ $order->is_guest_order ? 'Guest order' : 'Account order' }}</div></td><td>{{ $order->customer_name }}<div class="small text-muted">{{ $order->phone }}</div></td><td>{{ $order->branch->name ?? '-' }}</td><td>{{ $order->items->sum('quantity') }}</td><td>UGX {{ number_format($order->total_amount, 0) }}</td><td><span class="badge text-bg-{{ ['pending'=>'warning','processing'=>'info','completed'=>'success','cancelled'=>'danger'][$order->status] ?? 'secondary' }}">{{ ucfirst($order->status) }}</span></td><td>{{ $order->created_at?->format('M d, Y H:i') }}</td><td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('orders.show', $order) }}" title="View" aria-label="View order {{ $order->order_number }}"><i class="fas fa-eye" aria-hidden="true"></i></a>@if(in_array($order->status, ['pending','processing'], true))<button type="button" class="btn btn-sm btn-outline-secondary" title="Update status" aria-label="Update status for order {{ $order->order_number }}" data-bs-toggle="modal" data-bs-target="#statusModal" data-order="{{ $order->order_number }}" data-action="{{ route('orders.status',$order) }}" data-status="{{ $order->status }}"><i class="fas fa-pen" aria-hidden="true"></i></button>@endif</td></tr>@empty<tr><td colspan="8" class="text-center text-muted py-5">No orders found.</td></tr>@endforelse
    </tbody></table></div></div><div class="dash-panel-body">{{ $orders->links() }}</div></div>
</div>
<div class="modal fade" id="statusModal" tabindex="-1" aria-labelledby="statusModalTitle" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content" method="POST" id="statusForm">
            @csrf
            @method('PATCH')
            <div class="modal-header">
                <h5 class="modal-title" id="statusModalTitle">Update order status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Update <strong id="statusOrder"></strong>.</p>
                <label for="orderStatus" class="form-label">Status</label>
                <select name="status" id="orderStatus" class="form-select">
                    <option value="processing">Processing</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary" id="orderStatusSubmit">Save status</button>
            </div>
        </form>
    </div>
</div>
@push('scripts')
<script>
function initializeOrderStatus(button, restoring) {
    const form = document.getElementById('statusForm');
    if (button) {
        document.getElementById('statusOrder').textContent = button.dataset.order;
        form.action = button.dataset.action;
        form.dataset.modalContext = JSON.stringify({order: button.dataset.order});
    }
    const allowed = {pending: ['processing', 'cancelled'], processing: ['completed', 'cancelled']}[button?.dataset.status] || [];
    form.dataset.allowedStatuses = JSON.stringify(allowed);
    const select = form.querySelector('select[name="status"]');
    [...select.options].forEach(option => {
        option.hidden = !allowed.includes(option.value);
        option.disabled = option.hidden;
    });
    document.getElementById('orderStatusSubmit').disabled = !allowed.length;
    if (!restoring) select.value = allowed[0] || '';
}
document.getElementById('statusModal')?.addEventListener('show.bs.modal', event => {
    // Recovery has already initialized constraints and restored the submitted value.
    if (event.relatedTarget) initializeOrderStatus(event.relatedTarget, false);
});
document.getElementById('statusForm')?.addEventListener('ypa:modal-restoring', event => {
    const trigger = [...document.querySelectorAll('[data-bs-target="#statusModal"][data-action]')].find(button => {
        try { return new URL(button.dataset.action, location.href).href === event.detail.action; } catch { return false; }
    });
    initializeOrderStatus(trigger, true);
    if (!trigger) {
        document.getElementById('statusOrder').textContent = typeof event.detail.context.order === 'string' ? event.detail.context.order : 'this order';
        event.detail.notice = 'This order is not available in the current results. Open its details or reload the listing before changing status.';
    }
});
document.getElementById('statusForm')?.addEventListener('ypa:modal-restored', event => {
    const form = event.currentTarget;
    const allowed = JSON.parse(form.dataset.allowedStatuses || '[]');
    const status = form.querySelector('select[name="status"]').value;
    const valid = allowed.includes(status);
    document.getElementById('orderStatusSubmit').disabled = !valid;
    if (!valid && !event.detail.notice) event.detail.notice = 'The submitted status is no longer an available transition. Review the current order before saving.';
});
document.getElementById('orderStatus')?.addEventListener('change', event => {
    const allowed = JSON.parse(document.getElementById('statusForm').dataset.allowedStatuses || '[]');
    document.getElementById('orderStatusSubmit').disabled = !allowed.includes(event.target.value);
});
</script>
@endpush
@endsection
