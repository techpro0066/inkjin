@extends('layouts.user_dashboard_layout')

@section('title', 'Choose your appointment time')

@section('styles')
@include('user.requests.partials.confirm-times-styles')
@endsection

@section('content')
<main class="main-content flex-1 min-h-screen">
  <div class="p-6 md:p-10 lg:p-12 max-w-4xl mx-auto">
    <div class="flex flex-wrap items-center gap-4 mb-6">
      @if ($fromPayment ?? false)
        <a href="{{ route('user.custom-requests.payment', $customRequest) }}" class="inline-flex items-center gap-1 text-sm text-on-surface-variant hover:text-primary transition-colors">
          <span class="material-symbols-outlined text-[18px]">arrow_back</span> Back to payment
        </a>
      @endif
      <a href="{{ route('user.requests.index', ['tab' => 'custom']) }}" class="inline-flex items-center gap-1 text-sm text-on-surface-variant hover:text-primary transition-colors">
        <span class="material-symbols-outlined text-[18px]">arrow_back</span> Custom requests
      </a>
    </div>

    <div class="bg-white rounded-2xl border border-outline-variant/20 p-5 mb-6 flex gap-4">
      <div class="w-20 h-20 rounded-xl bg-primary/10 flex items-center justify-center flex-shrink-0 border border-primary/15">
        <span class="material-symbols-outlined text-primary text-3xl">brush</span>
      </div>
      <div class="flex-1">
        <h1 class="text-xl font-extrabold text-on-surface">Pick your appointment times</h1>
        <p class="text-sm text-on-surface-variant mt-1">{{ $artistName }} · {{ $customRequest->referenceLabel() }} · Auto scheduling</p>
        <p class="text-xs text-on-surface-variant mt-2">Session length: ~{{ $durationMinutes }} minutes (from artist quote)</p>
      </div>
    </div>

    @if ($errors->any())
      <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
        <p class="font-semibold mb-1">Please fix the following:</p>
        <ul class="list-disc list-inside space-y-0.5">
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    @if (session('error'))
      <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
    @endif

    @if (($fromPayment ?? false) && (($savedSessionSummary ?? null) || ($savedConsultSummary ?? null)))
      <div class="mb-6 rounded-2xl border border-primary/20 bg-primary/5 p-4">
        <p class="text-sm font-bold text-on-surface mb-2 flex items-center gap-2">
          <span class="material-symbols-outlined text-primary text-[20px]">event_available</span>
          Your selected times
        </p>
        <ul class="text-sm text-on-surface-variant space-y-1">
          @if ($savedConsultSummary ?? null)
            <li><span class="font-semibold text-on-surface">Consultation:</span> {{ $savedConsultSummary }}</li>
          @endif
          @if ($savedSessionSummary ?? null)
            <li><span class="font-semibold text-on-surface">{{ ($consultationRequired ?? false) ? 'Tattoo session' : 'Session' }}:</span> {{ $savedSessionSummary }}</li>
          @endif
        </ul>
        <p class="text-xs text-on-surface-variant mt-2">You can change your selection below, then continue to payment again.</p>
      </div>
    @endif

    <form id="autoConfirmForm" method="POST" action="{{ route('user.custom-requests.confirm-times.store', $customRequest) }}">
      @csrf
      <input type="hidden" name="client_session_slots[0][date]" id="inputSessionDate" value="{{ $savedSelection['date'] ?? '' }}">
      <input type="hidden" name="client_session_slots[0][ranges][0][from]" id="inputSessionFrom" value="{{ $savedSelection['from'] ?? '' }}">
      <input type="hidden" name="client_session_slots[0][ranges][0][to]" id="inputSessionTo" value="{{ $savedSelection['to'] ?? '' }}">

      @if ($consultationRequired ?? false)
        @include('user.requests.partials.auto-scheduling-consultation-flow', [
          'artistName' => $artistName,
          'consultDurationMinutes' => $consultDurationMinutes,
          'studioName' => $studioName ?? '',
          'studioAddress' => $studioAddress ?? '',
          'artistTimezone' => $artistTimezone ?? null,
          'savedConsultSelection' => $savedConsultSelection ?? null,
        ])
      @else
        @include('user.requests.partials.auto-scheduling-calendar', [
          'artistTimezone' => $artistTimezone ?? null,
        ])
      @endif

      <div id="confirmBar" class="hidden mt-6 bg-white rounded-2xl border border-primary/20 p-4 sm:p-5 shadow-lg sticky bottom-4 z-10">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <p id="confirmSummary" class="text-sm font-semibold text-on-surface"></p>
          <button type="submit" id="btnConfirmTimes" disabled class="w-full sm:w-auto px-8 py-3 bg-primary text-white rounded-xl font-bold text-sm disabled:opacity-60 disabled:cursor-not-allowed flex items-center justify-center gap-2">
            Continue to payment
            <span class="material-symbols-outlined text-lg">arrow_forward</span>
          </button>
        </div>
      </div>
    </form>
  </div>
</main>
@endsection

@section('scripts')
@if ($consultationRequired ?? false)
  @include('user.requests.partials.auto-scheduling-consultation-scripts', [
    'calendarPayload' => $calendarPayload,
    'savedSelection' => $savedSelection,
    'savedConsultSelection' => $savedConsultSelection,
    'durationMinutes' => $durationMinutes,
    'consultDurationMinutes' => $consultDurationMinutes,
  ])
@else
  @include('user.requests.partials.auto-scheduling-calendar-scripts', [
    'calendarPayload' => $calendarPayload,
    'savedSelection' => $savedSelection,
    'durationMinutes' => $durationMinutes,
  ])
@endif
<script>
document.getElementById('autoConfirmForm')?.addEventListener('submit', function() {
  if (typeof window.__inkjinPrepareAutoConfirmForm === 'function') {
    window.__inkjinPrepareAutoConfirmForm();
  }
});
</script>
@endsection
