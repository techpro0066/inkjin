@extends('layouts.auth_layout')

@section('title', 'Error - Stripe Setup')

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
      <div class="mb-3">
        <i class="ti ti-alert-circle ti-5x text-danger"></i>
      </div>
      <h4 class="mb-2">Something Went Wrong</h4>
    </div>

    <div class="alert alert-danger" role="alert">
      <i class="ti ti-alert-triangle me-2"></i>
      {{ $error ?? 'An error occurred while processing your Stripe account setup.' }}
    </div>

    <div class="text-center mt-4">
      <p class="text-muted">
        Please contact the artist who invited you or reach out to Inkjin support for assistance.
      </p>
      <a href="{{ url('/') }}" class="btn btn-primary">
        <i class="ti ti-home me-2"></i>
        Go to Homepage
      </a>
    </div>
  </div>
</div>
@endsection
