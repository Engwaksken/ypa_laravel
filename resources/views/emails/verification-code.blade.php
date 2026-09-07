<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 0 auto; padding: 0; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
        .header h1 { margin: 0 0 10px 0; font-size: 28px; }
        .header p { margin: 0; font-size: 14px; opacity: 0.9; }
        .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
        .content h2 { color: #333; margin-top: 0; }
        .code-box { background: white; border: 2px dashed #667eea; border-radius: 8px; padding: 20px; text-align: center; margin: 20px 0; }
        .code-label { margin: 0; color: #666; font-size: 14px; margin-bottom: 10px; }
        .code { font-size: 36px; font-weight: bold; color: #667eea; letter-spacing: 8px; font-family: "Courier New", monospace; margin: 10px 0; }
        .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; border-radius: 4px; }
        .warning strong { color: #856404; }
        .info-box { background: #e3f2fd; border-left: 4px solid #2196F3; padding: 15px; margin: 20px 0; border-radius: 4px; }
        .footer { text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ config('app.name') }}</h1>
            <p>Two-Factor Authentication</p>
        </div>
        <div class="content">
            <h2>Hello {{ $name }},</h2>
            <p>You are attempting to sign in to your {{ config('app.name') }} account. Please use the verification code below to complete your login:</p>

            <div class="code-box">
                <p class="code-label">Your verification code is:</p>
                <div class="code">{{ $code }}</div>
            </div>

            <div class="info-box">
                <strong>Expiration Notice:</strong><br>
                This code will expire in <strong>10 minutes</strong> for your security.
            </div>

            <div class="warning">
                <strong>Security Notice:</strong><br>
                If you did not attempt to sign in, please ignore this email and ensure your account is secure. Never share this code with anyone.
            </div>

            <p style="margin-top: 30px;">Thank you for using our system,<br>
            <strong>{{ config('app.name') }} Team</strong></p>

            <div class="footer">
                <p>This is an automated message, please do not reply to this email.</p>
                <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>