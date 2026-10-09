@extends('layouts.app')
@section('title', 'Place Order')
@section('content')
<div class="dash-wrap">
    <div class="dash-head"><div><div class="dash-greeting">Store</div><h1 class="dash-name">Place an Order</h1><div class="dash-date">Select products and submit your delivery details</div></div></div>
    <div class="row g-4">
        <div class="col-lg-8"><div class="row g-3">
            @forelse($products as $product)
                <div class="col-md-6 col-xl-4"><div class="dash-card h-100">
                    <div class="dash-card-top"><span class="dash-card-title">{{ $product->category->name ?? 'Product' }}</span><span class="dash-card-icon"><i class="fas fa-box"></i></span></div>
                    <h3 class="h6 mt-3">{{ $product->name }}</h3>
                    <p class="text-muted small">{{ $product->description ?: 'Available for order' }}</p>
                    <label for="quantity-{{ $product->id }}" class="fw-bold mb-3">Select quantity</label>
                    <input id="quantity-{{ $product->id }}" type="number" min="0" step="1" value="0" class="form-control order-qty" data-id="{{ $product->id }}" data-name="{{ $product->name }}">
                </div></div>
            @empty
                <div class="col-12"><div class="empty-box">No products are currently available.</div></div>
            @endforelse
        </div></div>
        <div class="col-lg-4"><div class="dash-panel sticky-lg-top" style="top:80px">
            <div class="dash-panel-head">Checkout</div>
            <div class="dash-panel-body"><form id="orderForm" method="POST" action="{{ route('place-order.store') }}">
                @csrf
                <input name="customer_name" class="form-control mb-2" placeholder="Full name" aria-label="Full name" required maxlength="191">
                <input name="phone" class="form-control mb-2" placeholder="Phone number" aria-label="Phone number" required maxlength="50">
                <input name="email" type="email" class="form-control mb-2" placeholder="Email (optional)" aria-label="Email" maxlength="150">
                <textarea name="delivery_location" class="form-control mb-2" placeholder="Delivery location" aria-label="Delivery location" required maxlength="500"></textarea>
                <select name="branch_id" class="form-select mb-2" aria-label="Branch" required><option value="">Select branch</option>@foreach($branches as $branch)<option value="{{ $branch->id }}">{{ $branch->name }}</option>@endforeach</select>
                <select name="payment_method" class="form-select mb-3" aria-label="Payment method" required><option value="">Payment method</option><option value="mobile-money">Mobile Money</option><option value="cash-on-delivery">Cash on delivery</option></select>
                <button type="submit" class="btn btn-primary w-100" id="orderSubmit"><i class="fas fa-check"></i> Submit Order</button>
            </form></div>
        </div></div>
    </div>
</div>
<div class="modal fade" id="orderResult" tabindex="-1" aria-labelledby="orderResultTitle" aria-hidden="true">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title" id="orderResultTitle">Order status</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
        <div class="modal-body" id="orderResultBody" role="status" aria-live="polite"></div>
        <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button><a href="{{ route('place-order') }}" id="orderAgain" class="btn btn-primary" hidden>Place another order</a></div>
    </div></div>
</div>
@push('scripts')
<script>
(() => {
    const form = document.getElementById('orderForm');
    const submit = document.getElementById('orderSubmit');
    const title = document.getElementById('orderResultTitle');
    const result = document.getElementById('orderResultBody');
    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('orderResult'));
    let submitting = false;
    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (submitting) return;
        const quantities = [...document.querySelectorAll('.order-qty')];
        if (quantities.some(input => !input.reportValidity())) return;
        const items = quantities.filter(input => +input.value > 0).map(input => ({id: input.dataset.id, quantity: +input.value}));
        document.getElementById('orderAgain').hidden = true;
        if (!items.length) {
            title.textContent = 'Select products';
            result.textContent = 'Please select at least one product before submitting.';
            modal.show();
            return;
        }
        submitting = true;
        submit.disabled = true;
        title.textContent = 'Submitting order';
        result.textContent = 'Please wait...';
        modal.show();
        const body = new FormData(form);
        body.append('items', JSON.stringify(items));
        try {
            const response = await fetch(form.action, {method: 'POST', headers: {'Accept': 'application/json'}, body});
            const data = await response.json();
            if (!response.ok || !data.success) {
                const errors = Object.values(data.errors || {}).flat().join(' ');
                throw new Error(errors || data.message || 'Please check the form and try again.');
            }
            title.textContent = 'Order submitted';
            result.textContent = 'Thank you. Your order number is ' + data.order_number + '.';
            document.getElementById('orderAgain').hidden = false;
            form.reset();
            quantities.forEach(input => { input.value = 0; });
        } catch (error) {
            title.textContent = 'Order could not be submitted';
            result.textContent = error instanceof SyntaxError || error instanceof TypeError
                ? 'Unable to confirm the order. Please check your connection and order status before retrying.'
                : error.message;
        } finally {
            submitting = false;
            submit.disabled = false;
        }
    });
})();
</script>
@endpush
@endsection
