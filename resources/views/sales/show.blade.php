@extends('layouts.app')
@section('title', 'Invoice ' . $sale->invoice_no)
@section('body_class', 'has-own-print-brand')
@section('content')
@php
    $money = fn ($value) => 'UGX ' . number_format((float) $value, 2);
    try { $receiptLogo = \App\Models\Setting::asset('site_logo', 'images/logo.png'); }
    catch (\Throwable) { $receiptLogo = 'images/logo.png'; }
    $statusStyle = match (strtolower((string) $sale->payment_status)) {
        'paid' => 'success', 'partially paid' => 'warning', 'oncredit' => 'danger', default => 'secondary',
    };
@endphp
<div class="dash-wrap sales-receipt-page">
    <div class="dash-head d-print-none">
        <div><div class="dash-greeting"><i class="fas fa-receipt me-2"></i>Sales receipt</div><h1 class="dash-name">Invoice {{ $sale->invoice_no }}</h1><div class="dash-date">Recorded {{ $sale->sale_date }}</div></div>
        <div class="d-flex flex-wrap gap-2"><a class="btn btn-outline-secondary" href="{{ route('sales.index', ['branch_id' => $sale->branch_id]) }}"><i class="fas fa-plus me-1"></i>New invoice</a><button type="button" class="btn btn-primary" id="printReceipt"><i class="fas fa-print me-1"></i>Print receipt</button></div>
    </div>

    <article class="sales-receipt" id="invoiceReceipt">
        <header class="sales-receipt-header">
            <div class="sales-receipt-brand">
                <img src="{{ asset($receiptLogo) }}" alt="{{ $siteName }} logo" onerror="this.style.display='none'">
                <div><span class="sales-receipt-eyebrow">{{ $siteName }}</span><h2>Sales receipt</h2><span class="text-muted">{{ $branch->name ?? 'Branch' }}</span></div>
            </div>
            <div class="sales-receipt-number"><span>INVOICE NUMBER</span><strong>{{ $sale->invoice_no }}</strong><span>{{ $sale->sale_date }}</span></div>
        </header>

        <section class="sales-receipt-details" aria-label="Invoice details">
            <div><span>Customer</span><strong>{{ $sale->customer_name ?: 'Walk-in customer' }}</strong>@if($sale->customer_phone)<small>{{ $sale->customer_phone }}</small>@endif</div>
            <div><span>Payment status</span><strong><span class="badge bg-{{ $statusStyle }}">{{ ucfirst($sale->payment_status) }}</span></strong></div>
            <div><span>Branch</span><strong>{{ $branch->name ?? '—' }}</strong></div>
        </section>

        <div class="table-responsive sales-receipt-table-wrap">
            <table class="table align-middle mb-0"><caption class="visually-hidden">Invoice items and totals</caption>
                <thead><tr><th scope="col">Product</th><th scope="col" class="text-end">Qty</th><th scope="col" class="text-end">Unit price</th><th scope="col" class="text-end">Line total</th></tr></thead>
                <tbody>@foreach($sale->items as $item)
                    <tr><td><strong>{{ $item->product_name }}</strong></td><td class="text-end">{{ number_format((float) $item->quantity, 0) }}</td><td class="text-end">{{ $money($item->price) }}</td><td class="text-end fw-semibold">{{ $money($item->total) }}</td></tr>
                @endforeach</tbody>
            </table>
        </div>
        <div class="sales-receipt-bottom">
            <div class="sales-receipt-thanks"><i class="fas fa-circle-check"></i><span>Thank you for your purchase.</span></div>
            <dl class="sales-receipt-totals">
                <dt>Subtotal</dt><dd>{{ $money($sale->total_amount + $sale->discount) }}</dd>
                <dt>Discount</dt><dd>− {{ $money($sale->discount) }}</dd>
                <dt class="grand-total">Total due</dt><dd class="grand-total">{{ $money($sale->total_amount) }}</dd>
                <dt>Amount paid</dt><dd>{{ $money($sale->partial_amount) }}</dd>
                <dt>Balance</dt><dd>{{ $money($sale->balance_amount) }}</dd>
            </dl>
        </div>
        @if(!empty($receipt))<footer class="sales-receipt-footer"><span>Payment receipt reference</span><strong>{{ $receipt->receipt_number }}</strong></footer>@endif
    </article>
</div>
@endsection
@push('styles')
<style>
.sales-receipt{max-width:1000px;margin:0 auto;background:#fff;border:1px solid #e2e8f0;border-radius:16px;box-shadow:0 8px 28px #0f172a0d;padding:clamp(20px,4vw,44px)}.sales-receipt-header{display:flex;justify-content:space-between;align-items:flex-start;gap:24px;padding-bottom:24px;border-bottom:1px solid #e2e8f0}.sales-receipt-brand{display:flex;align-items:center;gap:18px}.sales-receipt-brand img{max-width:150px;max-height:72px;object-fit:contain}.sales-receipt-brand h2{margin:2px 0 4px;font-size:1.55rem;color:#0b1f3a}.sales-receipt-eyebrow,.sales-receipt-number span,.sales-receipt-details>div>span,.sales-receipt-footer span{display:block;color:#64748b;font-size:.72rem;font-weight:700;letter-spacing:.07em;text-transform:uppercase}.sales-receipt-number{text-align:right;display:grid;gap:4px}.sales-receipt-number strong{font-size:1.08rem;color:var(--ds-brand)}.sales-receipt-details{display:grid;grid-template-columns:2fr 1fr 1fr;gap:20px;padding:24px 0}.sales-receipt-details strong{display:block;margin-top:6px;color:#0f172a}.sales-receipt-details small{display:block;margin-top:3px;color:#64748b}.sales-receipt-table-wrap{border:1px solid #e2e8f0;border-radius:10px}.sales-receipt-table-wrap thead th{background:#f5f8fc;color:#475569;font-size:.76rem;text-transform:uppercase;letter-spacing:.04em;padding:13px}.sales-receipt-table-wrap tbody td{padding:14px 13px;border-color:#edf1f5}.sales-receipt-bottom{display:flex;justify-content:space-between;align-items:flex-start;gap:24px;padding-top:24px}.sales-receipt-thanks{display:flex;gap:10px;align-items:center;color:#475569}.sales-receipt-thanks i{color:#15803d;font-size:1.2rem}.sales-receipt-totals{display:grid;grid-template-columns:1fr auto;gap:9px 28px;min-width:min(100%,340px);margin:0}.sales-receipt-totals dt{font-weight:500;color:#64748b}.sales-receipt-totals dd{text-align:right;margin:0;color:#0f172a}.sales-receipt-totals .grand-total{padding-top:10px;border-top:1px solid #dbe3ec;color:#0b1f3a;font-weight:800;font-size:1.1rem}.sales-receipt-footer{margin-top:24px;padding-top:16px;border-top:1px dashed #cbd5e1;display:flex;justify-content:space-between;gap:16px}.sales-receipt-footer strong{color:#0f172a}
@media(max-width:640px){.sales-receipt-header{flex-direction:column}.sales-receipt-number{text-align:left}.sales-receipt-details{grid-template-columns:1fr 1fr}.sales-receipt-bottom{flex-direction:column}.sales-receipt-totals{width:100%}.sales-receipt-brand{align-items:flex-start;flex-direction:column}}
@media print{@page{size:A4;margin:14mm}.sidebar,.top-nav,.overlay,.d-print-none,.ypa-flash-stack{display:none!important}.main-content{margin:0!important;padding:0!important;background:white!important}.dash-wrap{margin:0!important;padding:0!important;max-width:none!important}.sales-receipt{max-width:none;margin:0;border:0;border-radius:0;box-shadow:none;padding:0}.sales-receipt-table-wrap{overflow:visible}.sales-receipt-table-wrap table{min-width:100%}.sales-receipt-details,.sales-receipt-bottom,.sales-receipt-header{break-inside:avoid}}
</style>
@endpush
@push('scripts')
<script>document.getElementById('printReceipt')?.addEventListener('click', () => window.print());</script>
@endpush
