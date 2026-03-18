@extends('layouts.auth_layout')

@section('title', 'Stripe Connected Successfully')

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
        <i class="ti ti-check-circle ti-5x text-success"></i>
      </div>
      <h4 class="mb-2">Stripe Account Connected!</h4>
      <p class="text-muted mb-0">Hello {{ $studioName ?? 'Studio' }}</p>
    </div>

    <div class="alert alert-success" role="alert">
      <i class="ti ti-check me-2"></i>
      <strong>Success!</strong> Your Stripe account has been successfully connected.
    </div>

    <div class="card border border-success mb-4">
      <div class="card-body">
        <h6 class="mb-3">What's Next?</h6>
        <p class="mb-2">
          <i class="ti ti-arrow-right text-success me-2"></i>
          Payments for bookings made through Inkjin by <strong>{{ $artistName ?? 'the artist' }}</strong> will now be sent to your studio's Stripe account.
        </p>
        <p class="mb-0">
          <i class="ti ti-arrow-right text-success me-2"></i>
          You will receive payments automatically as bookings are completed.
        </p>
      </div>
    </div>

    <div class="text-center mt-4">
      <p class="text-muted small">
        You can close this page. If you need to manage your Stripe account, you can do so through your Stripe dashboard.
      </p>
    </div>
  </div>
</div>
@endsection
