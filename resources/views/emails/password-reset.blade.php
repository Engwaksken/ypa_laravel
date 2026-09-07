<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 0 auto; padding: 0; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
        .header h1 { margin: 0 0 10px 0; font-size: 28px; }
        .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
        .content h2 { color: #333; margin-top: 0; }
        .btn { display: inline-block; padding: 15px 40px; background: #667eea; color: white !important; text-decoration: none; border-radius: 6px; margin: 20px 0; font-weight: bold; }
        .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; border-radius: 4px; }
        .warning strong { color: #856404; }
        .footer { text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ config('app.name') }}</h1>
            <p>Password Reset Request</p>
        </div>
        <div class="content">
            <h2>Hello {{ $name }},</h2>
            <p>We received a request to reset your password. Click the button below to set a new password:</p>

            <div style="text-align: center;">
                <a href="{{ $resetLink }}" class="btn">Reset Password</a>
            </div>

            <p style="color: #666; font-size: 14px;">
                Or copy and paste this link into your browser:<br>
                <a href="{{ $resetLink }}">{{ $resetLink }}</a>
            </p>

            <div class="warning">
                <strong>Security Notice:</strong><br>
                This link will expire in 1 hour. If you did not request a password reset, please ignore this email.
            </div>

            <p style="margin-top: 30px;">Thank you,<br>
            <strong>{{ config('app.name') }} Team</strong></p>

            <div class="footer">
                <p>This is an automated message, please do not reply to this email.</p>
                <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>