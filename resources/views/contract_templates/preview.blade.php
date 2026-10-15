@extends('layouts.app')

@section('title', 'Preview: ' . $template->template_name)

@section('content')
@php
    try { $previewLogo = \App\Models\Setting::asset('site_logo', 'images/logo.png'); }
    catch (\Throwable) { $previewLogo = 'images/logo.png'; }
    $previewParty = strtolower((string) ($contract?->contract_for ?? 'member')) === 'group'
        ? $contract?->group?->group_name
        : $contract?->member?->full_name;
    $pdfUrl = $contract ? route('contracts.pdf', ['contract' => $contract, 'template_id' => $template->id]) : null;
@endphp
<div class="dash-wrap">

    <div class="dash-head">
        <div>
            <h1 class="dash-name">Preview: {{ $template->template_name }}</h1>
            <div class="dash-date"><a href="{{ route('contract-templates.index') }}">Contract Templates</a> / {{ $contract ? $contract->contract_number : 'Template preview' }}</div>
        </div>
        <a href="{{ route('contract-templates.show', $template) }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> Back</a>
    </div>

    <div class="dash-panel contract-preview-shell">
        <div class="dash-panel-head">
            <span><i class="fas fa-file-contract"></i> {{ $contract ? 'Agreement preview' : 'Template preview' }}</span>
            @if($contract)
                <div class="d-flex flex-wrap gap-2">
                    <a class="btn btn-sm btn-outline-secondary" href="{{ $pdfUrl }}" target="_blank" rel="noopener"><i class="fas fa-up-right-from-square me-1"></i>Open PDF</a>
                    <a class="btn btn-sm btn-primary" href="{{ $pdfUrl }}&download=1"><i class="fas fa-download me-1"></i>Download PDF</a>
                </div>
            @endif
        </div>
        <div class="dash-panel-body contract-preview-body">
            <div class="contract-preview-brand">
                <img src="{{ asset($previewLogo) }}" alt="{{ config('app.name') }} logo" onerror="this.style.display='none'">
                <div><strong>{{ config('app.name') }}</strong><span>{{ $contract?->project?->project_name ?? $template->template_name }}</span></div>
                @if($contract)<div class="contract-preview-meta"><span>Contract</span><strong>{{ $contract->contract_number }}</strong><span>Prepared for</span><strong>{{ $previewParty ?: '—' }}</strong></div>@endif
            </div>
            @if($contract)
                @if($contract->member?->member_photo && strtolower((string) $contract->contract_for) !== 'group')
                    <div class="contract-preview-person"><img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($contract->member->member_photo) }}" alt="{{ $previewParty }}" onerror="this.style.display='none'"><span>Member photo</span></div>
                @endif
                <iframe class="platform-pdf-frame contract-preview-frame" src="{{ $pdfUrl }}#view=FitH" title="PDF agreement preview for {{ $contract->contract_number }}"></iframe>
                <p class="small text-muted mt-3 mb-0">This preview uses the selected project template and recorded contract details. Use your PDF viewer’s print control to print.</p>
            @else
                <article class="contract-template-paper">{!! $renderedHtml !!}</article>
            @endif
        </div>
    </div>

</div>
@endsection

@push('styles')
<style>
.contract-preview-body{background:#eef2f7;padding:clamp(16px,3vw,32px)}
.contract-preview-brand{max-width:1100px;margin:0 auto 16px;background:#fff;border:1px solid #dbe3ec;border-radius:12px;padding:16px 20px;display:flex;align-items:center;gap:16px;box-shadow:0 3px 12px #0f172a0a}
.contract-preview-brand>img{max-width:150px;max-height:58px;object-fit:contain}.contract-preview-brand>div:nth-child(2){display:grid;gap:3px}.contract-preview-brand>div:nth-child(2) strong{font-size:1.1rem;color:#0b1f3a}.contract-preview-brand>div:nth-child(2) span,.contract-preview-meta span{font-size:.82rem;color:#64748b}
.contract-preview-meta{margin-left:auto;display:grid;grid-template-columns:auto auto;gap:2px 12px;align-items:center}.contract-preview-meta strong{text-align:right;color:#0f172a}
.contract-preview-person{max-width:1100px;margin:0 auto 12px;display:flex;align-items:center;gap:10px;color:#64748b;font-size:.8rem}.contract-preview-person img{width:48px;height:48px;object-fit:cover;border-radius:50%;border:2px solid white;box-shadow:0 1px 4px #0f172a33}
.contract-preview-frame{display:block;width:min(100%,1100px);height:min(78vh,1100px);min-height:620px;margin:0 auto;background:white;border:1px solid #dbe3ec;border-radius:8px;box-shadow:0 8px 28px #0f172a1a}
.contract-template-paper{max-width:900px;min-height:700px;margin:20px auto;padding:clamp(24px,5vw,64px);background:#fff;border:1px solid #dbe3ec;box-shadow:0 8px 28px #0f172a1a;overflow-wrap:anywhere}.contract-template-paper img{max-width:100%;height:auto}
@media(max-width:640px){.contract-preview-brand{align-items:flex-start;flex-wrap:wrap}.contract-preview-meta{width:100%;margin:8px 0 0}.contract-preview-frame{min-height:65vh;height:65vh}}
@media print{body{background:white!important}.dash-head,.dash-panel-head,.contract-preview-brand,.contract-preview-person,.small.text-muted{display:none!important}.dash-wrap,.dash-panel,.dash-panel-body{padding:0!important;margin:0!important;border:0!important;box-shadow:none!important}.contract-template-paper{max-width:none;min-height:0;margin:0;padding:0;border:0;box-shadow:none}}
</style>
@endpush
