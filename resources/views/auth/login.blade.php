@extends('layouts.auth_layout')

@section('title', 'Login')

@section('content')
<div class="card">
  <div class="card-body">
    <div class="app-brand justify-content-center mb-4 mt-2">
      <a href="{{ url('/') }}" class="app-brand-link gap-2">
        <span class="app-brand-logo demo">
          <img src="{{ asset('assets/img/branding/logo.png') }}" alt="Inkjin Logo" width="34" height="34" />
        </span>
        <span class="app-brand-text demo text-body fw-bold ms-1">{{ config('app.name', 'Inkjin') }}</span>
      </a>
    </div>

    <h4 class="mb-1 pt-2">Welcome back 👋</h4>
    <p class="mb-4">Please sign in to continue</p>

    @if (session('status') === 'email-changed')
      <div class="alert alert-info alert-dismissible fade show" role="alert">
        <i class="ti ti-info-circle me-2"></i>
        {{ session('message', 'Your email address has been updated. Please verify your new email address before logging in again.') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif

    <form id="formAuthentication" class="mb-3" method="POST" action="{{ route('login') }}">
      @csrf
      <div class="mb-3">
        <label for="email" class="form-label">Email</label>
        <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" placeholder="Enter your email" autofocus required />
        @error('email')
          <div class="invalid-feedback">{{ $message }}</div>
        @enderror
      </div>

      <div class="mb-3 form-password-toggle">
        <div class="d-flex justify-content-between">
          <label class="form-label" for="password">Password</label>
          @if (Route::has('password.request'))
            <a href="{{ route('password.request') }}"><small>Forgot Password?</small></a>
          @endif
        </div>
        <div class="input-group input-group-merge">
          <input type="password" id="password" class="form-control @error('password') is-invalid @enderror" name="password" placeholder="••••••••••••" required />
          <span class="input-group-text cursor-pointer"><i class="ti ti-eye-off"></i></span>
          @error('password')
            <div class="invalid-feedback d-block">{{ $message }}</div>
          @enderror
        </div>
      </div>

      <div class="mb-3">
        <button class="btn btn-primary d-grid w-100" type="submit">Sign in</button>
      </div>
    </form>

    @if (Route::has('register'))
      <p class="text-center">
        <span>New on our platform?</span>
        <a href="{{ route('register') }}"><span>Create an account</span></a>
      </p>
    @endif
  </div>
</div>
@endsection
