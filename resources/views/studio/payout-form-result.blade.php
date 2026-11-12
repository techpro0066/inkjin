@extends('layouts.onboarding_bookpay', ['hideSidebar' => true])

@section('title', 'Payout details')

@section('content')
@php
  $showLogout = (bool) ($showLogout ?? false);
@endphp
<div class="flex-1 p-8 md:p-12 max-w-xl w-full mx-auto text-center">
  <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl mb-6
    {{ $success ? 'bg-green-50 text-green-700 ring-2 ring-green-200/60' : 'bg-error-container/40 text-error ring-2 ring-error/20' }}">
    <span class="material-symbols-outlined text-4xl">{{ $success ? 'check_circle' : 'error' }}</span>
  </div>
  <h2 class="text-2xl font-extrabold text-on-surface tracking-tight mb-3">
    {{ $title ?? ($success ? 'Details saved' : 'Something went wrong') }}
  </h2>
  <p class="text-on-surface-variant text-sm leading-relaxed mb-8">{{ $message }}</p>
  <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
    @if ($showLogout && auth()->check())
      <form method="POST" action="{{ route('logout') }}">
        @csrf
        <input type="hidden" name="to" value="login">
        <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary text-on-primary font-semibold py-3 px-8 text-sm hover:opacity-90 transition-opacity">
          <span class="material-symbols-outlined text-lg">logout</span>
          Log out and sign in
        </button>
      </form>
      <a href="{{ authenticated_home_url() }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-outline-variant/60 text-on-surface font-semibold py-3 px-8 text-sm hover:bg-surface-container transition-colors">
        Go to my dashboard
      </a>
    @else
      <a href="{{ auth()->check() ? authenticated_home_url() : route('login') }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-outline-variant/60 text-on-surface font-semibold py-3 px-8 text-sm hover:bg-surface-container transition-colors">
        <span class="material-symbols-outlined text-lg">{{ auth()->check() ? 'home' : 'login' }}</span>
        {{ auth()->check() ? 'Go to my dashboard' : 'Close' }}
      </a>
    @endif
  </div>
</div>
@endsection
