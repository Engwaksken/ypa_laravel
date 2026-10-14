<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $contract->contract_number }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #111; }
        .container { width: 100%; }
        .muted { color: #666; }
        .section { margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 6px; text-align: left; }
        h1, h2, h3, h4 { margin: 0 0 8px 0; }
        @media print { .screen-only { display: none; } }
    </style>
</head>
<body>
    @if($browserPrint ?? true)
    <div class="screen-only" style="margin-bottom:16px">
        <button type="button" onclick="window.print()">Print / Save as PDF</button>
        <p>Choose Save as PDF in your browser's print dialog.</p>
    </div>
    @endif
    <div class="container">
        @if($template)
            <article class="contract-document">{!! $renderedHtml !!}</article>
        @else
        <p>No matching template is configured for this project and category. The summary below is not a templated contract.</p>
        <div class="section">
            <h2>{{ $contract->contract_number }}</h2>
            <div class="muted">{{ ucfirst((string) $contract->contract_for) }} contract</div>
        </div>

        <div class="section">
            <strong>Party:</strong> {{ strtolower((string) $contract->contract_for) === 'group' ? optional($contract->group)->group_name : optional($contract->member)->full_name }}<br>
            <strong>Project:</strong> {{ $contract->project->project_name ?? '-' }}<br>
            <strong>Branch:</strong> {{ $contract->branch->name ?? '-' }}<br>
            <strong>Payment Method:</strong> {{ $contract->paymentMethod->method_name ?? '-' }}
        </div>

        <div class="section">
            {!! $renderedHtml !!}
        </div>

        <div class="section">
            <h4>Items</h4>
            <table>
                <thead>
                    <tr>
                        <th>Name</th><th>Type</th><th>Qty</th><th>Unit</th><th>Monthly Return</th><th>Total Hives</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($contract->items as $item)
                        <tr>
                            <td>{{ $item->item_name }}</td>
                            <td>{{ $item->item_type }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td>{{ $item->unit_name }}</td>
                            <td>{{ number_format((float) $item->monthly_return, 2) }}</td>
                            <td>{{ number_format((float) $item->total_hives, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6">No items found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @endif
    </div>
</body>
</html>
