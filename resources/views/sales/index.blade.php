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
    <div class="dash-head"><div><div class="dash-greeting">Sales</div><h1 class="dash-name">Point of Sales</h1><div class="dash-date">Select available stock and record an invoice</div></div></div>
    <div class="dash-panel mb-4"><div class="dash-panel-body">
        <form method="GET" action="{{ route('sales.index') }}" class="row g-3 align-items-end">
            <div class="col-md-6"><label for="salesBranch" class="form-label">Branch</label><select id="salesBranch" name="branch_id" class="form-select" required>
                <option value="">Select a branch</option>
                @foreach($branches as $branch)<option value="{{ $branch->id }}" @selected((int) $branchId === (int) $branch->id)>{{ $branch->name }}</option>@endforeach
            </select></div>
            <div class="col-md-6"><button class="btn btn-outline-primary" type="submit">Load branch</button><small class="d-block text-muted mt-2">Loading a branch starts a new cart.</small></div>
        </form>
    </div></div>
    @if($branchId > 0)
    @if($unavailableSelections->isNotEmpty())<div class="alert alert-warning" role="alert">Some previously selected products are no longer available in this branch and were removed from the cart. Review the remaining quantities and total before processing.</div>@endif
    <form method="POST" action="{{ route('sales.store') }}" id="posForm" novalidate>
        @csrf
        <input type="hidden" name="request_token" value="{{ old('request_token', $requestToken) }}">
        <input type="hidden" name="branch_id" value="{{ $branchId }}">
        <input type="hidden" name="items" id="posItems" value="">
        <div class="row g-4">
            <div class="col-lg-8"><div class="dash-panel">
                <div class="dash-panel-head"><span>Available products</span></div>
                <div class="dash-panel-body"><label for="posSearch" class="form-label">Search product name or SKU</label><input type="search" id="posSearch" class="form-control mb-3" autocomplete="off">
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
            <div class="col-lg-4"><div class="dash-panel"><div class="dash-panel-head"><span>Invoice details</span></div><div class="dash-panel-body">
                <div class="mb-3"><label for="posCustomer" class="form-label">Customer, member or group</label><select id="posCustomer" name="customer_id" class="form-select"><option value="">Walk-in customer</option>@foreach($customers as $customer)<option value="{{ $customer->id }}" @selected((string) $selectedPayer === (string) $customer->id)>{{ $customer->name }}</option>@endforeach</select><small class="text-muted">Select a registered payer for credit or partial payment.</small></div>
                <div class="mb-3"><label for="posPaymentMethod" class="form-label">Payment method</label><select id="posPaymentMethod" name="payment_method_id" class="form-select" required><option value="">Select payment method</option>@foreach($paymentMethods as $method)<option value="{{ $method->id }}" @selected((string) old('payment_method_id') === (string) $method->id)>{{ $method->method_name }}</option>@endforeach</select></div>
                <div class="mb-3"><label for="posPaymentStatus" class="form-label">Payment status</label><select id="posPaymentStatus" name="payment_status" class="form-select"><option value="paid" @selected(old('payment_status', 'paid') === 'paid')>Paid in full</option><option value="partially paid" @selected(old('payment_status') === 'partially paid')>Partially paid</option><option value="oncredit" @selected(old('payment_status') === 'oncredit')>On credit</option></select></div>
                <div class="mb-3"><label for="posDiscount" class="form-label">Discount (UGX)</label><input type="number" id="posDiscount" name="discount" class="form-control" min="0" step="0.01" value="{{ old('discount', 0) }}" required></div>
                <div class="mb-3" id="posPartialGroup"><label for="posAmountPaid" class="form-label">Amount paid (UGX)</label><input type="number" id="posAmountPaid" name="amount_paid" class="form-control" min="0.01" step="0.01" value="{{ old('amount_paid', '') }}"></div>
                <dl class="row mb-3"><dt class="col-6">Subtotal</dt><dd class="col-6 text-end" id="posSubtotal">UGX 0.00</dd><dt class="col-6">Total due</dt><dd class="col-6 text-end fw-bold" id="posTotal">UGX 0.00</dd><dt class="col-6">Balance</dt><dd class="col-6 text-end" id="posBalance">UGX 0.00</dd></dl>
                <p class="small text-muted">The invoice uses current stock prices at processing time. Check the receipt for the confirmed total.</p>
                <div id="posError" class="alert alert-danger" role="alert" hidden></div>
                <button type="submit" id="posSubmit" class="btn btn-primary w-100" @disabled(count($products) === 0)>Process invoice</button>
            </div></div></div>
        </div>
    </form>
    @endif
    @include('sales.partials.history')
</div>
@endsection
@push('scripts')
<script src="{{ asset('js/pos.js') }}"></script>
@endpush
