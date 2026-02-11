@extends('layouts.dashboard_layout')

@section('title', 'Dashboard')

@section('content')
@if (session('success'))
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="ti ti-check-circle me-2"></i>
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
@endif

<h4 class="fw-bold py-3 mb-4">
  <span class="text-muted fw-light">Dashboard /</span> Overview
</h4>

<!-- Cards -->
<div class="row">
  @if(auth()->user()->role === 'admin' && isset($stats))
    <!-- Total Users Card (Admin) -->
    <div class="col-lg-4 col-md-6 col-sm-6 mb-4">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
              <span class="fw-semibold d-block mb-1">Total Users</span>
              <h3 class="card-title mb-0">{{ number_format($stats['total_users']) }}</h3>
            </div>
            <div class="avatar">
              <div class="avatar-initial bg-label-primary rounded">
                <i class="ti ti-users ti-md"></i>
              </div>
            </div>
          </div>
          <div class="border-top pt-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span class="text-muted">Total Users</span>
              <span class="fw-semibold">{{ number_format($stats['total_regular_users']) }}</span>
            </div>
            <div class="d-flex justify-content-between align-items-center">
              <span class="text-muted">Total Artists</span>
              <span class="fw-semibold">{{ number_format($stats['total_artists']) }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  @elseif(auth()->user()->role === 'artist')
    <!-- Total Users -->
    <div class="col-lg-3 col-md-6 col-sm-6 mb-4">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between">
            <div>
              <span class="fw-semibold d-block mb-1">Total Users</span>
              <h3 class="card-title mb-2">2,840</h3>
              <small class="text-success fw-semibold">
                <i class="ti ti-arrow-up"></i> +12.5%
              </small>
            </div>
            <div class="avatar">
              <div class="avatar-initial bg-label-primary rounded">
                <i class="ti ti-users ti-md"></i>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Total Artists -->
    <div class="col-lg-3 col-md-6 col-sm-6 mb-4">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between">
            <div>
              <span class="fw-semibold d-block mb-1">Total Artists</span>
              <h3 class="card-title mb-2">1,420</h3>
              <small class="text-success fw-semibold">
                <i class="ti ti-arrow-up"></i> +8.2%
              </small>
            </div>
            <div class="avatar">
              <div class="avatar-initial bg-label-success rounded">
                <i class="ti ti-palette ti-md"></i>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Active Projects -->
    <div class="col-lg-3 col-md-6 col-sm-6 mb-4">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between">
            <div>
              <span class="fw-semibold d-block mb-1">Active Projects</span>
              <h3 class="card-title mb-2">324</h3>
              <small class="text-success fw-semibold">
                <i class="ti ti-arrow-up"></i> +4.8%
              </small>
            </div>
            <div class="avatar">
              <div class="avatar-initial bg-label-info rounded">
                <i class="ti ti-folder ti-md"></i>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Revenue -->
    <div class="col-lg-3 col-md-6 col-sm-6 mb-4">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between">
            <div>
              <span class="fw-semibold d-block mb-1">Revenue</span>
              <h3 class="card-title mb-2">$42,389</h3>
              <small class="text-success fw-semibold">
                <i class="ti ti-arrow-up"></i> +18.2%
              </small>
            </div>
            <div class="avatar">
              <div class="avatar-initial bg-label-warning rounded">
                <i class="ti ti-currency-dollar ti-md"></i>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    @elseif(auth()->user()->role === 'user')
    <!-- Total Users -->
    <div class="col-lg-3 col-md-6 col-sm-6 mb-4">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between">
            <div>
              <span class="fw-semibold d-block mb-1">Bookings</span>
              <h3 class="card-title mb-2">0</h3>
            </div>
            <div class="avatar">
              <div class="avatar-initial bg-label-primary rounded">
                <i class="ti ti-calendar ti-md"></i>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
  @endif
</div>
@endsection
