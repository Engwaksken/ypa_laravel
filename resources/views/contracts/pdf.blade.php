<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $contract->contract_number }}</title>
    <style>
        @page { margin: 20mm 16mm 22mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; line-height: 1.55; color: #111827; }
        h1, h2, h3 { color: #0f2044; margin: 0 0 10px; }
        h1 { font-size: 20px; } h2 { font-size: 15px; } h3 { font-size: 12px; }
        p { margin: 0 0 9px; } ol, ul { padding-left: 22px; } li { margin-bottom: 6px; }
        table { width: 100%; border-collapse: collapse; } th, td { padding: 7px; vertical-align: top; }
        .letterhead { border-bottom: 3px solid #0f2044; margin-bottom: 14px; }
        .letterhead td { padding: 0 0 14px; } .letterhead .document-id { text-align: right; width: 36%; }
        .letterhead img { max-height: 50px; max-width: 130px; margin-bottom: 8px; }
        .muted { color: #6b7280; } .meta { background: #f3f4f6; padding: 9px; margin-bottom: 16px; }
        .section { margin-bottom: 18px; } .section h3 { border-bottom: 1px solid #d1d5db; padding-bottom: 6px; text-transform: uppercase; }
        .details td { width: 50%; border: 1px solid #e5e7eb; } .label { display: block; font-size: 8px; color: #6b7280; text-transform: uppercase; }
        .finance td, .items th, .items td { border: 1px solid #d1d5db; } .finance .amount { text-align: right; font-weight: bold; }
        .items th { background: #f3f4f6; text-align: left; } thead { display: table-header-group; } tr { page-break-inside: avoid; }
        .signature-block { page-break-inside: avoid; margin-top: 22px; } .signature-table td { width: 33%; font-size: 9px; }
        .document-footer { position: fixed; bottom: -13mm; left: 0; right: 0; border-top: 1px solid #d1d5db; padding-top: 6px; font-size: 8px; color: #6b7280; }
        .template-content img { max-width: 100%; } .template-content table td, .template-content table th { border: 1px solid #d1d5db; }
        .cover-page { border: 3px solid #0b1f3a; padding: 40px 24px; height: 580px; text-align: center; page-break-after: always; }
        .cover-page h1 { font-size: 22px; margin: 42px 0 20px; } .cover-page h2 { margin: 18px 0; }
        .cover-page .cover-party { border-top: 1px solid #c9973a; margin-top: 60px; padding-top: 20px; }
        .template-content { font-family: DejaVu Serif, serif; font-size: 10.5px; line-height: 1.65; }
        .cd-header { text-align: center; border-bottom: 3px solid #0b1f3a; padding-bottom: 18px; margin-bottom: 22px; }
        .cd-republic { font-size: 9px; color: #64748b; } .cd-title, .cd-subtitle { color: #c00000; font-weight: bold; }
        .cd-parties { background: #f8fafc; border: 1px solid #e2e8f0; padding: 16px; margin: 18px 0; }
        .cd-clause { border-left: 3px solid #0b1f3a; padding-left: 14px; margin: 16px 0; }
        .cd-clause-title { font-weight: bold; color: #0b1f3a; } .sig-doc-table td { border: 0 !important; }
        .manual-signing-block { margin: 24px 0; } .manual-signing-block h3 { margin-top: 18px; }
        .sig-line { display: inline-block; width: 200px; height: 15px; border-bottom: 1px solid #111827; }
        .manual-signing-block table, .manual-signing-block h3 { page-break-inside: avoid; }
    </style>
</head>
<body>
    <div class="document-footer">{{ $siteName }} · Contract {{ $contract->contract_number }} · Generated {{ now()->format('d M Y') }}</div>
    @if($template && !trim((string) $template->cover_page))
        @include('contracts.documents.cover')
    @endif
    <table class="letterhead">
        <tr>
            <td>
                @if($siteLogo && is_file(public_path($siteLogo)))<img src="{{ public_path($siteLogo) }}" alt="Logo"><br>@endif
                <h2>{{ $siteName }}</h2><span class="muted">Contract Agreement Document</span>
            </td>
            <td class="document-id"><span class="muted">CONTRACT</span><h2>{{ $contract->contract_number }}</h2><span>{{ $contract->project->project_name ?? '-' }}</span></td>
        </tr>
    </table>

    @if($template && $renderedHtml !== '')
        <article class="template-content">{!! $renderedHtml !!}</article>
        @if(!trim((string) $template->contract_signature) && !preg_match('/org_signature_block|contract_signature|IN WITNESS WHEREOF|manual-signing-block/i', (string) $template->template_body))
            @include('contracts.documents.manual-signatures')
        @endif
        @include('contracts.documents.purchases')
    @else
        @include('contracts.documents.summary')
    @endif
    @include('contracts.documents.project-calculation')
</body>
</html>
