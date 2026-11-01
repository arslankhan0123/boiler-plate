@extends('layouts.auth')
@section('title', 'Sign in')

@section('content')
    <h1>Welcome back 👋</h1>
    <p class="sub">Sign in to your account to continue.</p>

    <form method="POST" action="{{ route('login') }}" data-loading="Signing in…" novalidate>
        @csrf
        <x-admin.input name="email" type="email" label="Email address" placeholder="you@company.com" autocomplete="username" autofocus required />
        <x-auth-password name="password" label="Password" />

        <div class="d-flex justify-content-between align-items-center mt-3 mb-4">
            <label class="d-flex align-items-center gap-2 mb-0 small" style="cursor:pointer">
                <input type="checkbox" class="form-check-input mt-0" name="remember" @checked(old('remember'))> Remember me
            </label>
            @if (Route::has('password.request'))
                <a class="auth-link small" href="{{ route('password.request') }}">Forgot password?</a>
            @endif
        </div>

        <button type="submit" class="btn btn-primary"><i class="bi bi-box-arrow-in-right"></i> Sign in</button>
    </form>

    @if (Route::has('register'))
        <p class="auth-divider">Don't have an account? <a class="auth-link" href="{{ route('register') }}">Create one</a></p>
    @endif
@endsection
