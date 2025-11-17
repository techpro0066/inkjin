@extends('layouts.auth_layout')

@section('title', 'Reset Password')

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

    <h4 class="mb-1 pt-2">Reset Password 🔐</h4>
    <p class="mb-4">Please enter your new password below.</p>

    <form id="formAuthentication" class="mb-3" method="POST" action="{{ route('password.store') }}">
        @csrf

        <!-- Password Reset Token -->
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

      <div class="mb-3">
        <label for="email" class="form-label">Email</label>
        <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $request->email) }}" placeholder="Enter your email" required autofocus autocomplete="username" />
        @error('email')
          <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        </div>

      <div class="mb-3 form-password-toggle">
        <label class="form-label" for="password">New Password</label>
        <div class="input-group input-group-merge">
          <input type="password" id="password" class="form-control @error('password') is-invalid @enderror" name="password" placeholder="••••••••" required autocomplete="new-password" />
          <span class="input-group-text cursor-pointer"><i class="ti ti-eye-off"></i></span>
          @error('password')
            <div class="invalid-feedback d-block">{{ $message }}</div>
          @enderror
        </div>
        </div>

      <div class="mb-3 form-password-toggle">
        <label class="form-label" for="password_confirmation">Confirm Password</label>
        <div class="input-group input-group-merge">
          <input type="password" id="password_confirmation" class="form-control" name="password_confirmation" placeholder="••••••••" required autocomplete="new-password" />
          <span class="input-group-text cursor-pointer"><i class="ti ti-eye-off"></i></span>
        </div>
      </div>

      <div class="mb-3">
        <button class="btn btn-primary d-grid w-100" type="submit">
          <span class="d-flex align-items-center justify-content-center">
            <i class="ti ti-refresh me-2"></i>
            Reset Password
          </span>
        </button>
        </div>
    </form>

    <div class="text-center">
      <a href="{{ route('login') }}" class="d-flex align-items-center justify-content-center">
        <i class="ti ti-chevron-left me-1"></i>
        <span>Back to login</span>
      </a>
    </div>
  </div>
</div>
@endsection
