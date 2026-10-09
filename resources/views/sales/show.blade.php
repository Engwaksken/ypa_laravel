@extends('layouts.app')
@section('title', 'Invoice receipt')
@section('content')
@php($money = fn ($value) => 'UGX ' . number_format((float) $value, 2))
<div class="dash-wrap">
    <div class="dash-head"><div><div class="dash-greeting">Sales</div><h1 class="dash-name">Invoice {{ $sale->invoice_no }}</h1><div class="dash-date">{{ $sale->sale_date }}</div></div><div class="d-print-none"><a class="btn btn-outline-primary" href="{{ route('sales.index', ['branch_id' => $sale->branch_id]) }}">New invoice</a><button type="button" class="btn btn-outline-secondary" id="printReceipt">Print receipt</button></div></div>
    <div class="dash-panel" id="invoiceReceipt"><div class="dash-panel-body">
        <h2 class="h4">{{ $siteName }} — Invoice receipt</h2>
        <dl class="row mt-3"><dt class="col-sm-3">Branch</dt><dd class="col-sm-9">{{ $branch->name ?? '—' }}</dd><dt class="col-sm-3">Customer</dt><dd class="col-sm-9">{{ $sale->customer_name ?: 'Walk-in customer' }} {{ $sale->customer_phone ? '(' . $sale->customer_phone . ')' : '' }}</dd><dt class="col-sm-3">Payment status</dt><dd class="col-sm-9">{{ ucfirst($sale->payment_status) }}</dd></dl>
        <div class="table-responsive"><table class="table"><caption class="visually-hidden">Invoice items</caption><thead><tr><th scope="col">Product</th><th scope="col" class="text-end">Quantity</th><th scope="col" class="text-end">Unit price</th><th scope="col" class="text-end">Line total</th></tr></thead><tbody>
            @foreach($sale->items as $item)<tr><td>{{ $item->product_name }}</td><td class="text-end">{{ $item->quantity }}</td><td class="text-end">{{ $money($item->price) }}</td><td class="text-end">{{ $money($item->total) }}</td></tr>@endforeach
        </tbody><tfoot><tr><th scope="row" colspan="3">Subtotal</th><td class="text-end">{{ $money($sale->total_amount + $sale->discount) }}</td></tr><tr><th scope="row" colspan="3">Discount</th><td class="text-end">{{ $money($sale->discount) }}</td></tr><tr><th scope="row" colspan="3">Total due</th><td class="text-end fw-bold">{{ $money($sale->total_amount) }}</td></tr><tr><th scope="row" colspan="3">Amount paid</th><td class="text-end">{{ $money($sale->partial_amount) }}</td></tr><tr><th scope="row" colspan="3">Balance</th><td class="text-end">{{ $money($sale->balance_amount) }}</td></tr></tfoot></table></div>
        @if(!empty($receipt))<p class="small text-muted mb-0">Payment receipt: {{ $receipt->receipt_number }}</p>@endif
    </div></div>
</div>
@endsection
@push('styles')
<style>@media print { .sidebar, .top-nav, .overlay, .d-print-none { display:none !important; } .main-content { margin-left:0 !important; padding:0 !important; } .dash-panel { box-shadow:none; border:0; } }</style>
@endpush
@push('scripts')
<script>document.getElementById('printReceipt').addEventListener('click', () => window.print());</script>
@endpush
