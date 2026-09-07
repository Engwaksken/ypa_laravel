<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Code - {{ $siteName }}</title>

    <link rel="icon" type="image/png" href="{{ asset($siteFavicon) }}">
    <link rel="apple-touch-icon" href="{{ asset($siteFavicon) }}">

    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="auth-wrap">
    <div class="auth-card">
        <div class="auth-logo">
            @if($siteLogo)
                <img src="{{ asset($siteLogo) }}" alt="{{ $siteName }}">
            @endif
        </div>

        <h2 class="auth-title">Verify Your Email</h2>
        <p class="auth-subtitle">
            We've sent a 6-digit verification code to<br>
            <strong>{{ $email }}</strong>
        </p>

        @if($errors->any())
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                {{ $errors->first() }}
            </div>
        @endif

        @if(session('success'))
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                {{ session('success') }}
            </div>
        @endif

        <form method="POST" action="{{ route('verify.attempt') }}">
            @csrf
            <div class="form-group">
                <label class="form-label" for="code">Verification Code</label>
                <input type="text"
                       class="form-control"
                       id="code"
                       name="code"
                       placeholder="000000"
                       maxlength="6"
                       pattern="[0-9]{6}"
                       inputmode="numeric"
                       required
                       autofocus>
            </div>

            <button type="submit" name="verify" class="btn-primary">
                <i class="fas fa-check"></i> Verify Code
            </button>
        </form>

        <div style="text-align:center;margin-top:18px">
            <p style="font-size:.85rem;color:var(--ds-text-muted);margin-bottom:6px">Didn't receive the code?</p>
            <form method="POST" action="{{ route('verify.resend') }}">
                @csrf
                <button type="submit" name="resend" class="btn-link">
                    <i class="fas fa-redo"></i> Resend Code
                </button>
            </form>
        </div>

        <div style="text-align:center;margin-top:14px">
            <a href="{{ route('login') }}" style="font-size:.85rem">
                <i class="fas fa-arrow-left"></i> Back to Login
            </a>
        </div>
    </div>
</div>
<script>
document.getElementById('code').addEventListener('input', function (e) {
    this.value = this.value.replace(/[^0-9]/g, '');
});
</script>
</body>
</html>