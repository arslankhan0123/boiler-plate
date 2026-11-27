@extends('layouts.auth')
@section('title', 'Create account')

@section('content')
    <h1>Create your account</h1>
    <p class="sub">It only takes a minute. Your administrator will assign your access.</p>

    <form method="POST" action="{{ route('register') }}" data-loading="Creating account…" novalidate>
        @csrf
        <x-admin.input name="name" label="Full name" placeholder="e.g. Ali Khan" autocomplete="name" autofocus required />
        <x-admin.input name="email" type="email" label="Email address" placeholder="you@company.com" autocomplete="username" required />
        <x-auth-password name="password" label="Password" placeholder="Minimum 8 characters" autocomplete="new-password" />
        <x-auth-password name="password_confirmation" label="Confirm password" placeholder="Re-enter password" autocomplete="new-password" />

        <button type="submit" class="btn btn-primary mt-4"><i class="bi bi-person-plus"></i> Create account</button>
    </form>

    <p class="auth-divider">Already have an account? <a class="auth-link" href="{{ route('login') }}">Sign in</a></p>
@endsection
