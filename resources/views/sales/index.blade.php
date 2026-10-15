@extends('layouts.app')
@section('title', 'Point of Sales')
@section('content')
@php
    $money = fn ($value) => 'UGX ' . number_format((float) $value, 2);
    $oldItems = old('items', []);
    if (is_string($oldItems)) $oldItems = json_decode($oldItems, true) ?: [];
    $oldQuantities = collect(is_array($oldItems) ? $oldItems : [])->keyBy('id');
    $unavailableSelections = $oldQuantities->keys()->diff(collect($products)->pluck('id'));
    $selectedPayer = old('customer_id');
    if (!$selectedPayer && in_array(old('customer_type'), ['member', 'group'], true)) {
        $selectedPayer = old('customer_type') . '_' . old('customer_ref_id');
    }
@endphp
<div class="dash-wrap">
    <div class="dash-head sales-page-heading"><div><div class="dash-greeting"><i class="fas fa-cash-register me-2"></i>Sales &amp; checkout</div><h1 class="dash-name">Point of Sales</h1><div class="dash-date">Create a clear, accurate invoice from your branch’s available stock.</div></div></div>
    <div class="dash-panel mb-4 sales-branch-panel"><div class="dash-panel-body">
        <div class="sales-section-heading"><span class="sales-heading-icon"><i class="fas fa-code-branch"></i></span><div><strong>Choose sales branch</strong><small>Products, customers and invoices are scoped to the selected branch.</small></div></div>
        <form method="GET" action="{{ route('sales.index') }}" class="row g-3 align-items-end">
            <div class="col-md-8"><label for="salesBranch" class="form-label">Branch</label><select id="salesBranch" name="branch_id" class="form-select" required>
                <option value="">Select a branch</option>
                @foreach($branches as $branch)<option value="{{ $branch->id }}" @selected((int) $branchId === (int) $branch->id)>{{ $branch->name }}</option>@endforeach
            </select></div>
            <div class="col-md-4 d-flex align-items-end gap-3"><button class="btn btn-outline-primary" type="submit"><i class="fas fa-arrow-right-to-bracket me-1"></i>Load branch</button><small class="text-muted">Loading a branch starts a new cart.</small></div>
        </form>
    </div></div>
    @php($checkoutActive = $branchId > 0)
    <ul class="nav nav-tabs sales-workspace-tabs" id="salesWorkspaceTabs" role="tablist">
        @if($branchId > 0)
            <li class="nav-item" role="presentation"><button class="nav-link {{ $checkoutActive || !$sales->isEmpty() ? 'active' : '' }}" id="sales-checkout-tab" data-bs-toggle="tab" data-bs-target="#sales-checkout" type="button" role="tab" aria-controls="sales-checkout" aria-selected="{{ $checkoutActive || !$sales->isEmpty() ? 'true' : 'false' }}"><i class="fas fa-cart-shopping me-2"></i>New sale</button></li>
        @endif
        <li class="nav-item" role="presentation"><button class="nav-link {{ !$checkoutActive && ($branchId <= 0 || $sales->isEmpty()) ? 'active' : '' }}" id="sales-history-tab" data-bs-toggle="tab" data-bs-target="#sales-history" type="button" role="tab" aria-controls="sales-history" aria-selected="{{ !$checkoutActive && ($branchId <= 0 || $sales->isEmpty()) ? 'true' : 'false' }}"><i class="fas fa-clock-rotate-left me-2"></i>Recent invoices <span class="sales-tab-count">{{ $sales->total() }}</span></button></li>
    </ul>
    <div class="tab-content sales-workspace-content" id="salesWorkspaceContent">
    @if($branchId > 0)
    <section class="tab-pane fade {{ $checkoutActive || !$sales->isEmpty() ? 'show active' : '' }}" id="sales-checkout" role="tabpanel" aria-labelledby="sales-checkout-tab" tabindex="0">
    @if($unavailableSelections->isNotEmpty())<div class="alert alert-warning" role="alert">Some previously selected products are no longer available in this branch and were removed from the cart. Review the remaining quantities and total before processing.</div>@endif
    <form method="POST" action="{{ route('sales.store') }}" id="posForm" novalidate>
        @csrf
        <input type="hidden" name="request_token" value="{{ old('request_token', $requestToken) }}">
        <input type="hidden" name="branch_id" value="{{ $branchId }}">
        <input type="hidden" name="items" id="posItems" value="">
        <div class="row g-4">
            <div class="col-lg-8"><div class="dash-panel">
                <div class="dash-panel-head"><span><i class="fas fa-box-open me-2 text-primary"></i>Available products</span><span class="badge bg-light text-dark">{{ count($products) }} available</span></div>
                <div class="dash-panel-body"><label for="posSearch" class="form-label">Find a product</label><div class="sales-search-wrap mb-3"><i class="fas fa-search" aria-hidden="true"></i><input type="search" id="posSearch" class="form-control" placeholder="Search product name or SKU" autocomplete="off"></div>
                    <div class="table-responsive"><table class="table align-middle"><caption class="visually-hidden">Branch products, available quantities and selling prices</caption>
                        <thead><tr><th scope="col">Product</th><th scope="col">Available</th><th scope="col" class="text-end">Price</th><th scope="col">Quantity</th></tr></thead>
                        <tbody>
                        @forelse($products as $product)
                            <tr data-pos-product data-search="{{ strtolower($product['name'] . ' ' . ($product['sku'] ?? '')) }}">
                                <td>{{ $product['name'] }}<small class="d-block text-muted">{{ $product['sku'] ?? '' }}</small></td>
                                <td>{{ $product['quantity'] }}</td><td class="text-end">{{ $money($product['selling_price']) }}</td>
                                <td><label class="visually-hidden" for="posQty{{ $product['id'] }}">Quantity of {{ $product['name'] }}</label><input class="form-control pos-quantity" style="min-width:90px" type="number" id="posQty{{ $product['id'] }}" min="0" max="{{ $product['quantity'] }}" step="1" value="{{ $oldQuantities->get($product['id'])['quantity'] ?? 0 }}" data-id="{{ $product['id'] }}" data-price="{{ $product['selling_price'] }}"></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">No sellable stock is available in this branch.</td></tr>
                        @endforelse
                        </tbody>
                    </table></div>
                </div>
            </div></div>
            <div class="col-lg-4"><div class="dash-panel sales-invoice-panel"><div class="dash-panel-head"><span><i class="fas fa-file-invoice me-2 text-primary"></i>Invoice details</span></div><div class="dash-panel-body">
                <div class="mb-3"><label for="posCustomer" class="form-label">Customer, member or group</label><select id="posCustomer" name="customer_id" class="form-select"><option value="">Walk-in customer</option>@foreach($customers as $customer)<option value="{{ $customer->id }}" @selected((string) $selectedPayer === (string) $customer->id)>{{ $customer->name }}</option>@endforeach</select><small class="text-muted">Select a registered payer for credit or partial payment.</small></div>
                <div class="mb-3"><label for="posPaymentMethod" class="form-label">Payment method</label><select id="posPaymentMethod" name="payment_method_id" class="form-select" required><option value="">Select payment method</option>@foreach($paymentMethods as $method)<option value="{{ $method->id }}" @selected((string) old('payment_method_id') === (string) $method->id)>{{ $method->method_name }}</option>@endforeach</select></div>
                <div class="mb-3"><label for="posPaymentStatus" class="form-label">Payment status</label><select id="posPaymentStatus" name="payment_status" class="form-select"><option value="paid" @selected(old('payment_status', 'paid') === 'paid')>Paid in full</option><option value="partially paid" @selected(old('payment_status') === 'partially paid')>Partially paid</option><option value="oncredit" @selected(old('payment_status') === 'oncredit')>On credit</option></select></div>
                <div class="mb-3"><label for="posDiscount" class="form-label">Discount (UGX)</label><input type="number" id="posDiscount" name="discount" class="form-control" min="0" step="0.01" value="{{ old('discount', 0) }}" required></div>
                <div class="mb-3" id="posPartialGroup"><label for="posAmountPaid" class="form-label">Amount paid (UGX)</label><input type="number" id="posAmountPaid" name="amount_paid" class="form-control" min="0.01" step="0.01" value="{{ old('amount_paid', '') }}"></div>
                <dl class="row mb-3 sales-totals"><dt class="col-6">Subtotal</dt><dd class="col-6 text-end" id="posSubtotal">UGX 0.00</dd><dt class="col-6">Total due</dt><dd class="col-6 text-end fw-bold" id="posTotal">UGX 0.00</dd><dt class="col-6">Balance</dt><dd class="col-6 text-end" id="posBalance">UGX 0.00</dd></dl>
                <p class="small text-muted">The invoice uses current stock prices at processing time. Check the receipt for the confirmed total.</p>
                <div id="posError" class="alert alert-danger" role="alert" hidden></div>
                <button type="submit" id="posSubmit" class="btn btn-primary w-100" @disabled(count($products) === 0)>Process invoice</button>
            </div></div></div>
        </div>
    </form>
    </section>
    @endif
    <section class="tab-pane fade {{ !$checkoutActive && ($branchId <= 0 || $sales->isEmpty()) ? 'show active' : '' }}" id="sales-history" role="tabpanel" aria-labelledby="sales-history-tab" tabindex="0">
    @include('sales.partials.history')
    </section>
    </div>
</div>
@push('scripts')
<script src="{{ asset('js/pos.js') }}"></script>
@endpush
@push('styles')
<style>
.sales-page-heading{margin-bottom:1.35rem}.sales-branch-panel{border-top:3px solid var(--ds-brand)}.sales-section-heading{display:flex;align-items:center;gap:12px;margin-bottom:1.1rem}.sales-section-heading>div{display:grid;gap:2px}.sales-section-heading strong{font-size:.98rem}.sales-section-heading small{color:var(--ds-text-muted)}.sales-heading-icon{display:grid;place-items:center;width:42px;height:42px;border-radius:12px;background:#eaf4fc;color:var(--ds-brand);font-size:1.05rem}
.sales-search-wrap{position:relative}.sales-search-wrap>i{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#64748b;pointer-events:none}.sales-search-wrap input{padding-left:40px;min-height:44px}.sales-invoice-panel{position:sticky;top:76px;border-top:3px solid var(--ds-brand)}.sales-invoice-panel .dash-panel-body{padding:1.2rem}.sales-invoice-panel .form-control,.sales-invoice-panel .form-select{min-height:42px}.sales-totals{padding:14px 12px;margin:1rem 0!important;background:#f5f8fc;border:1px solid #e2e8f0;border-radius:10px}.sales-totals dt,.sales-totals dd{margin-bottom:8px}.sales-totals dt:last-of-type,.sales-totals dd:last-of-type{margin-bottom:0}.sales-totals #posTotal{font-size:1.2rem;color:var(--ds-brand)}
.sales-page-heading+.sales-branch-panel{box-shadow:0 4px 18px #0f172a0a}.sales-branch-panel .btn,.sales-branch-panel select{min-height:42px}
.sales-workspace-tabs{gap:6px;border-bottom:1px solid #dbe3ec;margin:0 0 1.2rem;padding:0 8px}.sales-workspace-tabs .nav-link{border:0;border-bottom:3px solid transparent;border-radius:8px 8px 0 0;padding:12px 16px;color:#64748b;font-weight:700}.sales-workspace-tabs .nav-link:hover{color:var(--ds-brand);background:#f4f8fc}.sales-workspace-tabs .nav-link.active{color:var(--ds-brand);background:#fff;border-bottom-color:var(--ds-brand)}.sales-tab-count{display:inline-grid;place-items:center;min-width:22px;height:22px;margin-left:5px;padding:0 6px;border-radius:999px;background:#eef2f7;color:#475569;font-size:.72rem}.sales-workspace-content>.tab-pane{outline:none}
@media(max-width:991.98px){.sales-invoice-panel{position:static}.sales-invoice-panel .dash-panel-body{padding:1rem}}
@media(max-width:575.98px){.sales-section-heading small{line-height:1.4}.sales-branch-panel .col-md-4{align-items:flex-start!important;flex-direction:column}.sales-branch-panel .col-md-4 small{margin:0}}
</style>
@endpush
@endsection
