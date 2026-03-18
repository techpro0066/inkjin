@extends('layouts.auth_layout')

@section('title', 'Stripe Setup')

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

    <div class="text-center mb-4">
      <i class="ti ti-credit-card ti-3x text-primary mb-3"></i>
      <h4 class="mb-2">Stripe Account Setup</h4>
      <p class="text-muted mb-0">Hello {{ $studioName ?? 'Studio' }}</p>
    </div>

    <div class="alert alert-info" role="alert">
      <i class="ti ti-info-circle me-2"></i>
      {{ $message ?? 'Please complete your Stripe account setup to receive payments.' }}
    </div>

    <div class="text-center mt-4">
      <p class="text-muted">
        If you need to continue your Stripe setup, please check your email for the setup link or contact the artist who invited you.
      </p>
    </div>
  </div>
</div>
@endsection
