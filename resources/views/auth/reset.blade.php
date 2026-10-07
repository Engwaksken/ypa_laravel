@extends('layouts.auth')

@section('title', 'Reset Password')

@section('content')
<div class="auth-wrap">
    <div class="auth-card">
        <div class="auth-logo">
            <img src="{{ asset($siteLogo ?: 'images/logo.png') }}" alt="{{ $siteName }}" data-fallback="{{ asset('images/logo.png') }}" onerror="this.onerror = null; this.src = this.dataset.fallback;">
        </div>

        <h2 class="auth-title">Reset Password</h2>
        <p class="auth-subtitle">
            Enter your email address and we'll send you a link to reset your password.
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

        <form method="POST" action="{{ route('password.email') }}">
            @csrf
            <div class="form-group">
                <label class="form-label" for="email">Email Address</label>
                <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" value="{{ old('email') }}" required autofocus>
            </div>

            <button type="submit" class="btn-primary">
                <i class="fas fa-paper-plane"></i> Send Reset Link
            </button>
        </form>

        <div style="text-align:center;margin-top:14px">
            <a href="{{ route('login') }}" style="font-size:.85rem">
                <i class="fas fa-arrow-left"></i> Back to Login
            </a>
        </div>
    </div>
</div>
@endsection
