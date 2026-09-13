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
    @forelse($orders as $order)<tr><td><strong>{{ $order->order_number }}</strong><div class="small text-muted">{{ $order->is_guest_order ? 'Guest order' : 'Account order' }}</div></td><td>{{ $order->customer_name }}<div class="small text-muted">{{ $order->phone }}</div></td><td>{{ $order->branch->name ?? '-' }}</td><td>{{ $order->items->sum('quantity') }}</td><td>UGX {{ number_format($order->total_amount, 0) }}</td><td><span class="badge text-bg-{{ ['pending'=>'warning','processing'=>'info','completed'=>'success','cancelled'=>'danger'][$order->status] ?? 'secondary' }}">{{ ucfirst($order->status) }}</span></td><td>{{ $order->created_at?->format('M d, Y H:i') }}</td><td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('orders.show', $order) }}" title="View"><i class="fas fa-eye"></i></a>@if(in_array($order->status, ['pending','processing'], true))<button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#statusModal" data-order="{{ $order->order_number }}" data-action="{{ route('orders.status',$order) }}" data-status="{{ $order->status }}"><i class="fas fa-pen"></i></button>@endif</td></tr>@empty<tr><td colspan="8" class="text-center text-muted py-5">No orders found.</td></tr>@endforelse
    </tbody></table></div></div><div class="dash-panel-body">{{ $orders->links() }}</div></div>
</div>
<div class="modal fade" id="statusModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog"><form class="modal-content" method="POST" id="statusForm">@csrf @method('PATCH')<div class="modal-header"><h5 class="modal-title">Update order status</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><p>Update <strong id="statusOrder"></strong>.</p><select name="status" class="form-select"><option value="processing">Processing</option><option value="completed">Completed</option><option value="cancelled">Cancelled</option></select></div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Save status</button></div></form></div></div>
@push('scripts')<script>document.getElementById('statusModal')?.addEventListener('show.bs.modal',e=>{const b=e.relatedTarget;document.getElementById('statusOrder').textContent=b.dataset.order;document.getElementById('statusForm').action=b.dataset.action;});</script>@endpush
@endsection
