@php
    $document = $document ?? app(\App\Services\ContractPdfDocumentService::class)->prepare($contract, $template, $renderedHtml ?? '');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $contract->contract_number }}</title>
    <style>
        @page { margin: 12mm 10mm 16mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; line-height: 1.6; color: #0b1f3a; }
        h1, h2, h3, h4 { margin: 0 0 12px; page-break-after: avoid; }
        h1 { font-size: 22px; } h2 { font-size: 17px; } h3 { font-size: 12px; }
        p { margin: 0 0 12px; } ol, ul { padding-left: 26px; } li { margin-bottom: 6px; }
        table { width: 100%; border-collapse: collapse; } th, td { padding: 8px; vertical-align: top; }
        thead { display: table-header-group; } tr { page-break-inside: avoid; }
        .watermark { position: fixed; top: 30%; left: 0; right: 0; text-align: center; z-index: -1000; }
        .watermark img { width: 360px; opacity: .06; }
        .cover-page { text-align: center; page-break-after: always; padding: 0 20px; }
        .cover-page h2 { font-size: 18px; margin: 16px 0; }
        .cover-page .cover-logo { display: block; width: 285px; height: 340px; margin: 12px auto; }
        .cover-page h1 { margin: 18px 0 24px; font-size: 24px; }
        .cover-mode { text-transform: uppercase; } .cover-contract { font-family: DejaVu Serif, serif; font-size: 14px; font-weight: bold; }
        .cover-party { margin-top: 10px; } .cover-party h2 { text-transform: uppercase; }
        .custom-cover { page-break-after: always; } .custom-cover img { max-width: 100%; }
        .party-photos { margin: 0 0 22px; text-align: center; page-break-inside: avoid; }
        .party-photos th, .party-photos td { width: 50%; border: 0; padding: 3px; }
        .party-photos th { color: #c00000; font-family: DejaVu Serif, serif; font-size: 11px; }
        .party-photos img { height: 90px; max-width: 100px; border: 1px solid #0b1f3a; }
        .missing-photo { display: inline-block; width: 85px; height: 65px; border: 1px solid #cbd5e1; padding-top: 25px; font-size: 9px; color: #64748b; }
        .project-title { font-family: DejaVu Serif, serif; text-align: center; text-transform: uppercase; }
        .template-content { color: #111; } .template-content img { max-width: 100%; }
        .template-content table td, .template-content table th { border: 1px solid #a3a3a3; }
        .keep-heading, .cd-clause-title { page-break-after: avoid; font-weight: bold; }
        .signature-page, .appendix-page { page-break-before: always; }
        .signature-page { font-family: DejaVu Serif, serif; color: #0b1f3a; }
        .signature-page h3 { margin: 16px 0 10px; }
        .signature-page .sig-doc-table { margin-bottom: 10px; }
        .sig-doc-table td { border: 0 !important; padding: 4px 6px; }
        .sig-doc-table .sig-label { width: 35%; font-weight: bold; }
        .sig-line { display: inline-block; width: 225px; height: 15px; border-bottom: 1px solid #0b1f3a; }
        .signature-page .sig-line.date { width: 135px; }
        .manual-signing-block { border-top: 1px solid #0b1f3a; padding-top: 16px; }
        .muted { color: #64748b; } .meta { background: #f1f5f9; padding: 10px; margin-bottom: 16px; }
        .section { margin-bottom: 18px; } .section h3 { text-transform: uppercase; }
        .details td { width: 50%; border: 1px solid #cbd5e1; } .label { display: block; color: #64748b; font-size: 9px; }
        .appendix-title { border-left: 4px solid #c9973a; padding-left: 8px; font-family: DejaVu Serif, serif; font-weight: bold; }
        .finance td, .finance th, .items th, .items td { border: 1px solid #dbe2ea; }
        .finance th, .items th { background: #0b1f3a; color: white; text-align: left; }
        .finance .amount { font-weight: bold; } .finance .row-number { width: 25px; text-align: center; color: #64748b; }
        .finance tbody tr:nth-child(even) { background: #f8fafc; }
        .items { font-size: 8px; table-layout: fixed; } .items th, .items td { padding: 6px 4px; word-wrap: break-word; }
        .receipt-heading { border-bottom: 3px solid #0b1f3a; margin-bottom: 24px; }
        .receipt-heading td { border: 0; padding: 0 0 14px; } .receipt-heading .logo-cell { width: 15%; }
        .receipt-heading img { width: 55px; } .receipt-heading .receipt-title { text-align: center; }
        .receipt-heading .contract-number { width: 25%; text-align: right; font-size: 9px; }
        .receipt-table { font-size: 8px; table-layout: fixed; } .receipt-table th, .receipt-table td { padding: 6px 3px; word-wrap: break-word; }
        .receipt-table th { color: white; background: #0b1f3a; text-align: left; }
        .receipt-table td { border-bottom: 1px solid #e2e8f0; } .receipt-table .reference { font-size: 7px; }
    </style>
    @php($appearance = app(\App\Services\AppearanceService::class))
    <style>
        @page { margin: {{ $appearance->get('contract_margin') }}mm; }
        body { font-family: {{ $appearance->font(true) }}; font-size: {{ $appearance->get('contract_font_size') }}pt; color: {{ $appearance->get('theme_text') }}; }
        .finance th, .items th, .receipt-table th { background: {{ $appearance->get('theme_primary') }}; }
    </style>
</head>
<body>
    @if($document['logo'])<div class="watermark"><img src="{{ $document['logo'] }}" alt=""></div>@endif
    @if($document['coverHtml'] !== '')
        <div class="custom-cover">{!! $document['coverHtml'] !!}</div>
    @else
        @include('contracts.documents.cover')
    @endif

    @include('contracts.documents.party-photos')
    @if($template && $document['bodyHtml'] !== '')
        <article class="template-content">{!! $document['bodyHtml'] !!}</article>
    @else
        @include('contracts.documents.summary')
    @endif

    <div class="signature-page">
        @if($document['signatureHtml'] !== '' && !($document['standardSignatures'] ?? false))
            {!! $document['signatureHtml'] !!}
        @else
            @include('contracts.documents.manual-signatures')
        @endif
    </div>
    @include('contracts.documents.purchases')
    @include('contracts.documents.items')
    @if($contract->project_calculation)
        <div class="appendix-page">@include('contracts.documents.project-calculation')</div>
    @endif
    @include('contracts.documents.receipt')
</body>
</html>
