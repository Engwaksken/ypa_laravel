@php
    $brand = app(\App\Services\BrandingService::class);
    $brandName = $brand->name();
    $logoFile = $brand->emailLogoFile();
    // Inline (CID) attachment when sent through Mail::send; plain URL for previews.
    $logoSrc = isset($message) && $logoFile && method_exists($message, 'embed') ? $message->embed($logoFile) : $brand->logoUrl();
    $accent = '#005fa3';
@endphp
<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="x-apple-disable-message-reformatting">
    <meta name="color-scheme" content="light">
    <title>@yield('subject', $brandName)</title>
    <style>
        body { margin: 0; padding: 0; width: 100% !important; background: #eef2f7; -webkit-text-size-adjust: 100%; }
        table { border-collapse: collapse; }
        img { border: 0; line-height: 100%; outline: none; text-decoration: none; }
        a { color: {{ $accent }}; }
        @media only screen and (max-width: 620px) {
            .email-shell { width: 100% !important; }
            .email-pad { padding-left: 20px !important; padding-right: 20px !important; }
            .email-title { font-size: 21px !important; }
            .email-button { display: block !important; }
            .email-button a { display: block !important; }
        }
    </style>
</head>
<body style="margin:0;padding:0;background:#eef2f7;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#1e293b;">
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;">@yield('preheader')</div>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#eef2f7;">
        <tr>
            <td align="center" style="padding:28px 12px;">
                <table role="presentation" class="email-shell" width="600" cellpadding="0" cellspacing="0" style="width:600px;max-width:600px;background:#ffffff;border-radius:14px;overflow:hidden;box-shadow:0 6px 24px rgba(15,23,42,.08);">
                    <tr>
                        <td style="height:5px;background:{{ $accent }};line-height:5px;font-size:0;">&nbsp;</td>
                    </tr>
                    <tr>
                        <td class="email-pad" align="center" style="padding:28px 40px 18px;border-bottom:1px solid #eef2f6;">
                            <img src="{{ $logoSrc }}" alt="{{ $brandName }}" height="56" style="display:block;height:56px;width:auto;max-width:200px;margin:0 auto 10px;">
                            <div style="font-size:13px;font-weight:600;letter-spacing:.04em;text-transform:uppercase;color:#64748b;">{{ $brandName }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td class="email-pad" style="padding:32px 40px 8px;font-size:15px;line-height:1.65;">
                            <h1 class="email-title" style="margin:0 0 6px;font-size:23px;line-height:1.3;color:#0f172a;">@yield('heading')</h1>
                            @hasSection('subheading')
                                <p style="margin:0 0 22px;font-size:14px;color:#64748b;">@yield('subheading')</p>
                            @endif
                            @yield('content')
                        </td>
                    </tr>
                    <tr>
                        <td class="email-pad" style="padding:8px 40px 30px;font-size:15px;line-height:1.6;">
                            <p style="margin:24px 0 0;">Kind regards,<br><strong>{{ $brandName }} Team</strong></p>
                        </td>
                    </tr>
                    <tr>
                        <td class="email-pad" align="center" style="padding:20px 40px;background:#f8fafc;border-top:1px solid #eef2f6;font-size:12px;line-height:1.6;color:#64748b;">
                            This is an automated message, please do not reply.<br>
                            &copy; {{ date('Y') }} {{ $brandName }}. All rights reserved.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
