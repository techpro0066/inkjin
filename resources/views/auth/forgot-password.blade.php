@extends('layouts.auth_layout')

@section('title', 'Forgot Password')

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

    <h4 class="mb-1 pt-2">Forgot Password? 🔒</h4>
    <p class="mb-4">No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.</p>

    <!-- Session Status -->
    @if (session('status'))
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="ti ti-check-circle me-2"></i>
        {{ session('status') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif

    <form id="formAuthentication" class="mb-3" method="POST" action="{{ route('password.email') }}">
        @csrf

      <div class="mb-3">
        <label for="email" class="form-label">Email</label>
        <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" placeholder="Enter your email" autofocus required />
        @error('email')
          <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        </div>

      <div class="mb-3">
        <button class="btn btn-primary d-grid w-100" type="submit">
          <span class="d-flex align-items-center justify-content-center">
            <i class="ti ti-mail me-2"></i>
            Email Password Reset Link
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
