@php
    $appearance = app(\App\Services\AppearanceService::class);
    $paper = $appearance->get($kind . '_paper');
    $thermal = in_array($paper, ['58mm', '80mm'], true);
@endphp
<style>
@page { size: {{ $appearance->paperCss($kind) }}; margin: {{ $thermal ? min(4, (int) $appearance->get($kind . '_margin')) : $appearance->get($kind . '_margin') }}mm; }
@media print {
    body { font-family: {!! $appearance->font() !!}; font-size: {{ $appearance->get($kind . '_font_size') }}pt; background:#fff; color: {{ $appearance->get('theme_text') }}; }
    table, .table { font-size:inherit; }
    .sidebar,.top-nav,.overlay,.print-actions,.ypa-flash-stack { display:none!important; }
    .main-content { margin:0!important; padding:0!important; }
    .receipt-card,.sales-receipt { box-shadow:none!important; border:0!important; padding:0!important; }
    .receipt-card .card-body { padding:0!important; }
    .document-wording { white-space:pre-line; overflow-wrap:anywhere; }
    @if($thermal)
    .sales-receipt,.receipt-card,.container,.dash-wrap { width:100%!important; max-width:100%!important; margin:0!important; padding:0!important; }
    .sales-receipt-header,.sales-receipt-bottom,.sales-receipt-brand,.receipt-card .d-flex { display:block!important; }
    .sales-receipt-details,.receipt-card .row { display:block!important; }
    .receipt-card [class*="col-"] { width:100%!important; }
    .sales-receipt-number { text-align:left; }
    .sales-receipt-totals { min-width:0; width:100%; gap:4px; }
    .table-responsive { overflow:visible!important; }
    table,.table { width:100%!important; min-width:0!important; table-layout:fixed; }
    th,td { overflow-wrap:anywhere; padding:3px!important; }
    @endif
}
</style>
