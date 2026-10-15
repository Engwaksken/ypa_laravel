<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Print Contract {{ $contract->contract_number }}</title>
    <style>
        @page{size:A4;margin:18mm 17mm 20mm}*{box-sizing:border-box}body{margin:0;background:#eef2f7;color:#172033;font:11pt/1.65 Arial,Helvetica,sans-serif}.print-toolbar{position:sticky;top:0;z-index:2;display:flex;justify-content:center;gap:10px;padding:14px;background:#fff;border-bottom:1px solid #dbe3ec}.print-toolbar button,.print-toolbar a{border:0;border-radius:7px;padding:10px 16px;background:#005fa3;color:#fff;font-weight:700;text-decoration:none;cursor:pointer}.print-toolbar a{background:#edf2f7;color:#334155}.print-sheet{width:min(210mm,calc(100% - 32px));min-height:297mm;margin:24px auto;padding:18mm;background:#fff;box-shadow:0 8px 32px #0f172a1a}.print-brand{display:flex;align-items:center;gap:18px;padding-bottom:16px;border-bottom:2px solid #005fa3;margin-bottom:24px}.print-brand img{max-width:150px;max-height:64px;object-fit:contain}.print-brand div{display:grid}.print-brand strong{color:#005fa3;font-size:17pt}.print-brand span{color:#64748b;font-size:9pt}.print-meta{display:grid;grid-template-columns:1fr 1fr;gap:10px 24px;margin:0 0 22px;padding:14px;background:#f5f8fc;border:1px solid #e2e8f0;border-radius:6px}.print-meta div{display:grid}.print-meta dt{color:#64748b;font-size:8pt;text-transform:uppercase;letter-spacing:.05em}.print-meta dd{margin:2px 0 0;font-weight:700}.party-photos{display:flex;gap:20px;margin:20px 0;break-inside:avoid}.party-photo{display:flex;align-items:center;gap:10px;color:#475569;font-size:9pt}.party-photo img{width:72px;height:86px;object-fit:cover;border:1px solid #cbd5e1;border-radius:4px}.custom-cover{break-after:page}.contract-body{overflow-wrap:anywhere}.contract-body img,.custom-cover img{max-width:100%;height:auto}.contract-body table,.custom-cover table{max-width:100%;border-collapse:collapse}.contract-body th,.contract-body td,.custom-cover th,.custom-cover td{padding:6px;border:1px solid #cbd5e1;vertical-align:top}.contract-body h1,.contract-body h2,.contract-body h3,.contract-body h4{break-after:avoid;color:#0b1f3a}.contract-body p{orphans:3;widows:3}.contract-signature{margin-top:26px;break-inside:avoid}.items-appendix{margin-top:28px;break-before:page}.items-appendix h2{font-size:14pt;color:#0b1f3a}.items-appendix table{width:100%;border-collapse:collapse}.items-appendix th,.items-appendix td{padding:7px;border:1px solid #cbd5e1;text-align:left}.items-appendix th{background:#f1f5f9}.print-foot{margin-top:28px;padding-top:10px;border-top:1px solid #e2e8f0;color:#64748b;font-size:8pt;text-align:center}
        @media print{body{background:#fff;font-size:10pt}.print-toolbar{display:none!important}.print-sheet{width:auto;min-height:0;margin:0;padding:0;box-shadow:none}.print-brand{print-color-adjust:exact;-webkit-print-color-adjust:exact}.print-meta{print-color-adjust:exact;-webkit-print-color-adjust:exact}.contract-body a{color:inherit;text-decoration:none}}
        @media(max-width:600px){.print-sheet{padding:20px}.print-meta{grid-template-columns:1fr}.print-brand{align-items:flex-start;flex-direction:column}}
    </style>
</head>
<body>
    <nav class="print-toolbar" aria-label="Print controls">
        <button type="button" onclick="window.print()"><span aria-hidden="true">🖨</span> Print contract</button>
        <a href="{{ route('contracts.pdf', $contract) }}" target="_blank" rel="noopener">Open PDF</a>
        <button type="button" onclick="window.close()" style="background:#64748b">Close</button>
    </nav>
    <main class="print-sheet">
        <header class="print-brand"><img src="{{ $logoUrl }}" alt="{{ config('app.name') }} logo" onerror="this.style.display='none'"><div><strong>{{ config('app.name') }}</strong><span>Contract Agreement</span></div></header>
        <dl class="print-meta">
            <div><dt>Contract number</dt><dd>{{ $contract->contract_number }}</dd></div>
            <div><dt>Project</dt><dd>{{ $contract->project?->project_name ?: '—' }}</dd></div>
            <div><dt>Agreement party</dt><dd>{{ $partyName ?: '—' }}</dd></div>
            <div><dt>Branch</dt><dd>{{ $contract->branch?->name ?: '—' }}</dd></div>
            <div><dt>Agreement type</dt><dd>{{ $contract->contract_type_name ?: $template?->template_name ?: 'Contract Agreement' }}</dd></div>
            <div><dt>Generated</dt><dd>{{ now()->format('j M Y, H:i') }}</dd></div>
        </dl>
        @if($partyPhoto || $kinPhoto)
            <div class="party-photos">
                @if($partyPhoto)<div class="party-photo"><img src="{{ $partyPhoto }}" alt="{{ $partyName }}"><span>{{ $group ? 'Group representative' : 'Member' }}</span></div>@endif
                @if($kinPhoto)<div class="party-photo"><img src="{{ $kinPhoto }}" alt="Next of kin"><span>Next of kin{{ $nextOfKin?->full_name ? ': ' . $nextOfKin->full_name : '' }}</span></div>@endif
            </div>
        @endif
        @if($coverHtml !== '')<div class="custom-cover">{!! $coverHtml !!}</div>@endif
        <article class="contract-body">
            @if($bodyHtml !== ''){!! $bodyHtml !!}@else
                <h1>Contract summary</h1><p>This contract is issued to <strong>{{ $partyName ?: 'the named party' }}</strong> for the project <strong>{{ $contract->project?->project_name ?: '—' }}</strong>.</p>
                <p>Contract amount: <strong>{{ number_format((float) ($contract->contract_amount ?? 0), 2) }}</strong></p>
            @endif
        </article>
        @if($signatureHtml !== '')<section class="contract-signature">{!! $signatureHtml !!}</section>@else
            <section class="contract-signature">@include('contracts.documents.manual-signatures')</section>
        @endif
        @if($contract->items->isNotEmpty())
            <section class="items-appendix"><h2>Contract Items</h2><table><thead><tr><th>Item</th><th>Type</th><th>Quantity</th><th>Unit</th><th>Unit price</th><th>Total</th></tr></thead><tbody>
                @foreach($contract->items as $item)<tr><td>{{ $item->item_name }}</td><td>{{ $item->item_type }}</td><td>{{ $item->quantity }}</td><td>{{ $item->unit_name }}</td><td>{{ number_format((float) $item->unit_price, 2) }}</td><td>{{ number_format((float) $item->total_price, 2) }}</td></tr>@endforeach
            </tbody></table></section>
        @endif
        <footer class="print-foot">{{ config('app.name') }} · {{ $contract->contract_number }}</footer>
    </main>
    <script>window.addEventListener('load', function () { window.setTimeout(function () { window.print(); }, 400); });</script>
</body>
</html>
