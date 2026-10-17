@php
    $palette = [
        'info' => ['#eff6ff', '#2563eb', '#1e3a8a'],
        'warning' => ['#fffbeb', '#d97706', '#78350f'],
    ][$tone ?? 'info'];
@endphp
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:18px 0;">
    <tr>
        <td style="padding:14px 16px;background:{{ $palette[0] }};border-left:4px solid {{ $palette[1] }};border-radius:6px;font-size:14px;line-height:1.55;color:{{ $palette[2] }};">
            <strong>{{ $title }}</strong><br>{{ $body }}
        </td>
    </tr>
</table>
