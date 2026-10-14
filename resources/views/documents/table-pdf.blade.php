<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        @page { margin: 14mm; }
        body { font: 9px DejaVu Sans, sans-serif; color: #111827; }
        h1 { font-size: 16px; color: #0f2044; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td { border: 1px solid #d1d5db; padding: 6px; text-align: left; word-wrap: break-word; }
        th { background: #f3f4f6; } thead { display: table-header-group; } tr { page-break-inside: avoid; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <p>{{ $siteName }} · Loaded rows: {{ count($rows) }} · Generated {{ now()->format('d M Y') }}</p>
    <table>
        <thead><tr>@foreach($headers as $header)<th>{{ $header }}</th>@endforeach</tr></thead>
        <tbody>@foreach($rows as $row)<tr>@foreach($row as $cell)<td>{{ $cell }}</td>@endforeach</tr>@endforeach</tbody>
    </table>
</body>
</html>
