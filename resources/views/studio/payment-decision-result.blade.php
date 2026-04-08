@extends('layouts.onboarding_bookpay', ['hideSidebar' => true])

@section('title', 'Studio request')

@section('content')
@php
  $isApproved = $status === 'approved';
  $isRejected = $status === 'rejected';
  $isLocked = $status === 'locked';
@endphp

<div class="flex-1 p-8 md:p-12 max-w-2xl w-full mx-auto">
  <div class="mb-10 text-center md:text-left">
    <h2 class="text-3xl font-extrabold text-on-surface tracking-tight">
      @if($isApproved)
        Request completed
      @elseif($isRejected)
        Request declined
      @elseif($isLocked)
        Already decided
      @else
        Something went wrong
      @endif
    </h2>
    <p class="text-on-surface-variant mt-2 text-sm md:text-base max-w-lg mx-auto md:mx-0">
      @if($isApproved)
        Thank you. Your response has been recorded successfully.
      @elseif($isRejected)
        This artist payout request was not approved.
      @elseif($isLocked)
        This link can only be used once.
      @else
        We could not complete this action.
      @endif
    </p>
  </div>

  <div class="bg-surface-container-low rounded-2xl border border-outline-variant/20 p-8 md:p-10 shadow-sm text-center">
    <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl mb-6
      @if($isApproved) bg-green-50 text-green-700 ring-2 ring-green-200/60
      @elseif($isRejected) bg-error-container/40 text-error ring-2 ring-error/20
      @elseif($isLocked) bg-amber-50 text-amber-800 ring-2 ring-amber-200/80
      @else bg-secondary-fixed text-primary ring-2 ring-primary/15 @endif">
      <span class="material-symbols-outlined text-4xl">
        @if($isApproved) check_circle
        @elseif($isRejected) thumb_down
        @elseif($isLocked) lock
        @else error @endif
      </span>
    </div>

    @if($isApproved)
      <h3 class="text-xl font-bold text-on-surface mb-3">Approved</h3>
    @elseif($isRejected)
      <h3 class="text-xl font-bold text-on-surface mb-3">Declined</h3>
    @elseif($isLocked)
      <h3 class="text-xl font-bold text-on-surface mb-3">No changes made</h3>
    @else
      <h3 class="text-xl font-bold text-on-surface mb-3">Error</h3>
    @endif

    <p class="text-on-surface-variant text-sm leading-relaxed mb-8">{{ $message }}</p>

    <div class="flex flex-col sm:flex-row gap-3 justify-center items-stretch sm:items-center">
      @if($isApproved)
        @auth
          <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center gap-2 bg-gradient-to-br from-primary to-primary-container text-white font-bold py-3 px-8 rounded-xl shadow-lg shadow-primary/20 hover:opacity-90 transition-all active:scale-[0.98]">
            <span class="material-symbols-outlined text-lg">dashboard</span>
            Go to dashboard
          </a>
          <form method="POST" action="{{ route('logout') }}" class="inline-flex justify-center">
            @csrf
            <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl border border-outline-variant/60 text-on-surface font-semibold py-3 px-8 text-sm hover:bg-surface-container transition-colors w-full sm:w-auto">
              <span class="material-symbols-outlined text-lg">logout</span>
              Log out
            </button>
          </form>
        @else
          <a href="{{ route('login') }}" class="inline-flex items-center justify-center gap-2 bg-gradient-to-br from-primary to-primary-container text-white font-bold py-3 px-8 rounded-xl shadow-lg shadow-primary/20 hover:opacity-90 transition-all active:scale-[0.98] w-full sm:w-auto">
            <span class="material-symbols-outlined text-lg">login</span>
            Continue to login
          </a>
        @endauth
      @else
        @auth
          <form method="POST" action="{{ route('logout') }}" class="inline-flex justify-center w-full sm:w-auto">
            @csrf
            <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl border border-outline-variant/60 text-on-surface font-semibold py-3 px-8 text-sm hover:bg-surface-container transition-colors w-full sm:w-auto">
              <span class="material-symbols-outlined text-lg">logout</span>
              Log out
            </button>
          </form>
        @else
          <a href="{{ route('login') }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-outline-variant/60 text-on-surface font-semibold py-3 px-8 text-sm hover:bg-surface-container transition-colors w-full sm:w-auto">
            <span class="material-symbols-outlined text-lg">login</span>
            Log in
          </a>
        @endauth
      @endif
    </div>
  </div>
</div>
@endsection
