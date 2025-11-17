@extends('layouts.auth_layout')

@section('title', 'Register')

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

    <h4 class="mb-1 pt-2">Create your account 🚀</h4>

    <form id="formAuthentication" class="mb-3" method="POST" action="{{ route('register') }}">
      @csrf

      <div class="mb-3">
        <label for="name" class="form-label">Name</label>
        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" placeholder="Enter your name" autofocus required />
        @error('name')
          <div class="invalid-feedback">{{ $message }}</div>
        @enderror
      </div>

      <div class="mb-3">
        <label for="email" class="form-label">Email</label>
        <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" placeholder="Enter your email" required />
        @error('email')
          <div class="invalid-feedback">{{ $message }}</div>
        @enderror
      </div>

      <div class="mb-3">
        <label for="role" class="form-label">Role</label>
        <select class="form-select @error('role') is-invalid @enderror" id="role" name="role" required>
          <option value="" selected disabled>Select Role</option>
          <option value="user" {{ old('role') == 'user' ? 'selected' : '' }}>User</option>
          <option value="artist" {{ old('role') == 'artist' ? 'selected' : '' }}>Artist</option>
        </select>
        @error('role')
          <div class="invalid-feedback">{{ $message }}</div>
        @enderror
      </div>

      <div class="mb-3 form-password-toggle">
        <label class="form-label" for="password">Password</label>
        <div class="input-group input-group-merge">
          <input type="password" id="password" class="form-control @error('password') is-invalid @enderror" name="password" placeholder="••••••••••••" required />
          <span class="input-group-text cursor-pointer"><i class="ti ti-eye-off"></i></span>
          @error('password')
            <div class="invalid-feedback d-block">{{ $message }}</div>
          @enderror
        </div>
      </div>

      <div class="mb-3 form-password-toggle">
        <label class="form-label" for="password_confirmation">Confirm Password</label>
        <div class="input-group input-group-merge">
          <input type="password" id="password_confirmation" class="form-control" name="password_confirmation" placeholder="••••••••••••" required />
          <span class="input-group-text cursor-pointer"><i class="ti ti-eye-off"></i></span>
        </div>
      </div>

      <button class="btn btn-primary d-grid w-100" type="submit">Sign up</button>
    </form>

    @if (Route::has('login'))
      <p class="text-center">
        <span>Already have an account?</span>
        <a href="{{ route('login') }}"><span>Sign in instead</span></a>
      </p>
    @endif
  </div>
</div>
@endsection
