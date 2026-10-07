@extends('layouts.auth')

@section('title', 'Login')

@section('content')
<div class="auth-wrap">
    <div class="auth-card">
        <div class="auth-logo">
            <img src="{{ asset($siteLogo ?: 'images/logo.png') }}" alt="{{ $siteName }}" data-fallback="{{ asset('images/logo.png') }}" onerror="this.onerror = null; this.src = this.dataset.fallback;">
        </div>

        <p class="auth-subtitle">Please sign in to your account</p>

        @if($errors->any())
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login.attempt') }}">
            @csrf
            <div class="form-group">
                <label class="form-label" for="email">Email Address</label>
                <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" value="{{ old('email') }}" required autofocus>
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
            </div>

            <button type="submit" class="btn-primary">
                <i class="fas fa-sign-in-alt"></i> Sign In
            </button>
        </form>

        <p style="text-align:center;margin-top:18px;font-size:.85rem">
            <a href="{{ route('password.request') }}">Forgot your password?</a>
        </p>
    </div>
</div>
@endsection
