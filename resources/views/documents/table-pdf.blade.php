@php
    $brand = app(\App\Services\BrandingService::class);
    $pdfLogo = $brand->pdfLogo();
    $brandName = $brand->name();
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        @page { margin: 30mm 12mm 18mm; }
        body { font: 9px DejaVu Sans, sans-serif; color: #111827; }
        .pdf-header { position: fixed; top: -22mm; left: 0; right: 0; height: 18mm; border-bottom: 2px solid #005fa3; }
        .pdf-header table, .pdf-footer table { width: 100%; border-collapse: collapse; table-layout: auto; }
        .pdf-header td, .pdf-footer td { border: 0; padding: 0; vertical-align: middle; }
        .pdf-header .logo { width: 62px; }
        .pdf-header img { max-height: 15mm; max-width: 56px; }
        .pdf-header .org { font-size: 13px; font-weight: bold; color: #005fa3; }
        .pdf-header .doc { font-size: 8px; color: #64748b; }
        .pdf-header .meta { text-align: right; font-size: 8px; color: #64748b; line-height: 1.5; }
        .pdf-footer { position: fixed; bottom: -12mm; left: 0; right: 0; height: 8mm; border-top: 1px solid #e2e8f0; font-size: 7.5px; color: #64748b; padding-top: 4px; }
        .pdf-footer .page { text-align: right; }
        .pdf-footer .page-number:after { content: counter(page); }
        .watermark { position: fixed; top: 32%; left: 0; right: 0; text-align: center; z-index: -1000; }
        .watermark img { width: 300px; opacity: .06; }
        h1 { margin: 0 0 4px; font-size: 15px; color: #0f2044; }
        .summary { margin: 0 0 10px; color: #64748b; }
        .data { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .data th, .data td { border: 1px solid #d9e0e8; padding: 5px 6px; text-align: left; word-wrap: break-word; }
        .data th { background: #005fa3; color: #fff; font-weight: bold; }
        .data tbody tr:nth-child(even) td { background: #f6f9fc; }
        thead { display: table-header-group; } tr { page-break-inside: avoid; }
        .empty { padding: 18px; text-align: center; color: #64748b; }
    </style>
</head>
<body>
    @if($pdfLogo)<div class="watermark"><img src="{{ $pdfLogo }}" alt=""></div>@endif

    <div class="pdf-header">
        <table>
            <tr>
                @if($pdfLogo)<td class="logo"><img src="{{ $pdfLogo }}" alt=""></td>@endif
                <td>
                    <div class="org">{{ $brandName }}</div>
                    <div class="doc">{{ $title }}</div>
                </td>
                <td class="meta">Generated {{ now()->format('d M Y, H:i') }}<br>{{ auth()->user()?->name ? 'By ' . auth()->user()->name : '' }}</td>
            </tr>
        </table>
    </div>

    <div class="pdf-footer">
        <table>
            <tr>
                <td>{{ $brandName }} · Confidential</td>
                <td class="page">Page <span class="page-number"></span></td>
            </tr>
        </table>
    </div>

    <h1>{{ $title }}</h1>
    <p class="summary">{{ number_format(count($rows)) }} {{ \Illuminate\Support\Str::plural('record', count($rows)) }} exported</p>
    <table class="data">
        <thead><tr>@foreach($headers as $header)<th>{{ $header }}</th>@endforeach</tr></thead>
        <tbody>
            @forelse($rows as $row)<tr>@foreach($row as $cell)<td>{{ $cell }}</td>@endforeach</tr>
            @empty<tr><td class="empty" colspan="{{ count($headers) }}">No records to display.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
