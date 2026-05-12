@extends('layouts.onboarding_bookpay', ['hideSidebar' => true])

@section('title', 'Payout details')

@section('content')
<div class="flex-1 p-8 md:p-12 max-w-xl w-full mx-auto text-center">
  <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl mb-6
    {{ $success ? 'bg-green-50 text-green-700 ring-2 ring-green-200/60' : 'bg-error-container/40 text-error ring-2 ring-error/20' }}">
    <span class="material-symbols-outlined text-4xl">{{ $success ? 'check_circle' : 'error' }}</span>
  </div>
  <h2 class="text-2xl font-extrabold text-on-surface tracking-tight mb-3">
    {{ $title ?? ($success ? 'Details saved' : 'Something went wrong') }}
  </h2>
  <p class="text-on-surface-variant text-sm leading-relaxed mb-8">{{ $message }}</p>
  <a href="{{ route('login') }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-outline-variant/60 text-on-surface font-semibold py-3 px-8 text-sm hover:bg-surface-container transition-colors">
    <span class="material-symbols-outlined text-lg">login</span>
    Close
  </a>
</div>
@endsection
