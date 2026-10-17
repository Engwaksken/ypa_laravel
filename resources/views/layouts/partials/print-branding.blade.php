@php($printBrand = app(\App\Services\BrandingService::class))
{{-- Shown only when printing: letterhead on top, faint logo watermark behind every page. --}}
<div class="ui-print-watermark" aria-hidden="true"><img src="{{ $printBrand->logoUrl() }}" alt=""></div>
<header class="ui-print-brand" aria-hidden="true">
    <img src="{{ $printBrand->logoUrl() }}" alt="">
    <div>
        <strong>{{ $printBrand->name() }}</strong>
        <span>@yield('title', 'Report') · Printed {{ now()->format('j M Y, H:i') }}</span>
    </div>
</header>
