@extends('layouts.onboarding_bookpay')

@section('title', 'Studio payment status')

@section('content')
@php
  $isRejected = $status === 'rejected';
  $isApproved = $status === 'approved';
  $isPending = !$isRejected && !$isApproved;
@endphp

<div class="flex-1 p-8 md:p-12 max-w-2xl w-full mx-auto">
  <div class="mb-10 text-center md:text-left">
    <h2 class="text-3xl font-extrabold text-on-surface tracking-tight">Studio payout setup</h2>
    <p class="text-on-surface-variant mt-2 text-sm md:text-base max-w-lg mx-auto md:mx-0">
      @if($isApproved)
        Your studio has submitted bank payout details for your account.
      @elseif($isRejected)
        Payout setup for this studio path could not be completed.
      @else
        We are waiting for your studio to complete the secure payout form from the email we sent them.
      @endif
    </p>
  </div>

  <div class="bg-surface-container-low rounded-2xl border border-outline-variant/20 p-8 md:p-10 shadow-sm text-center">
    <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl mb-6
      @if($isApproved) bg-green-50 text-green-700 ring-2 ring-green-200/60
      @elseif($isRejected) bg-error-container/40 text-error ring-2 ring-error/20
      @else bg-secondary-fixed text-primary ring-2 ring-primary/15 @endif">
      <span class="material-symbols-outlined text-4xl">
        @if($isApproved) check_circle
        @elseif($isRejected) cancel
        @else hourglass_top @endif
      </span>
    </div>

    @if($isApproved)
      <h3 class="text-xl font-bold text-on-surface mb-3">Payout details on file</h3>
    @elseif($isRejected)
      <h3 class="text-xl font-bold text-on-surface mb-3">Setup not completed</h3>
    @else
      <h3 class="text-xl font-bold text-on-surface mb-3">Waiting for studio</h3>
    @endif

    <p class="text-on-surface-variant text-sm leading-relaxed mb-6">{{ $message }}</p>

    @if($isRejected)
      <p class="text-on-surface-variant/90 text-sm mb-8">
        Contact your studio or change your payment method in settings once you have access.
      </p>
    @elseif($isPending)
      <p class="text-on-surface-variant/90 text-sm mb-8">
        Other areas of the app stay locked until your studio completes the email we sent (bank form, or approve/decline if they already have payout details on file). You can resend from payment settings if needed.
      </p>
    @else
      <p class="text-on-surface-variant/90 text-sm mb-8">
        You can continue to the dashboard from here if this page is still shown.
      </p>
    @endif

    <div class="flex flex-col sm:flex-row gap-3 justify-center items-stretch sm:items-center">
      @if($isApproved)
        <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center gap-2 bg-gradient-to-br from-primary to-primary-container text-white font-bold py-3 px-8 rounded-xl shadow-lg shadow-primary/20 hover:opacity-90 transition-all active:scale-[0.98]">
          <span class="material-symbols-outlined text-lg">dashboard</span>
          Go to dashboard
        </a>
      @endif

      <form method="POST" action="{{ route('logout') }}" class="inline-flex justify-center">
        @csrf
        <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl border border-outline-variant/60 text-on-surface font-semibold py-3 px-8 text-sm hover:bg-surface-container transition-colors w-full sm:w-auto">
          <span class="material-symbols-outlined text-lg">logout</span>
          Log out
        </button>
      </form>
    </div>
  </div>
</div>
@endsection
