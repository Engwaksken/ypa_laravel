@php
    $appearance = app(\App\Services\AppearanceService::class);
@endphp
@if($position === 'header')
    @if($appearance->get($kind . '_subtitle'))<p class="document-wording" style="white-space:pre-line">{{ $appearance->get($kind . '_subtitle') }}</p>@endif
    @if($appearance->get($kind . '_header'))<p class="document-wording" style="white-space:pre-line">{{ $appearance->get($kind . '_header') }}</p>@endif
    @php
        try { $documentAddress = \App\Models\Setting::value('address_details'); }
        catch (\Throwable) { $documentAddress = ''; }
    @endphp
    @if($documentAddress)<p class="document-wording" style="white-space:pre-line">{{ $documentAddress }}</p>@endif
@else
    @if($appearance->get($kind . '_' . $position))<p class="document-wording" style="white-space:pre-line">{{ $appearance->get($kind . '_' . $position) }}</p>@endif
@endif
