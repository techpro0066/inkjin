@extends('layouts.admin_dashboard_layout')

@section('title', 'Booking '.$booking->referenceLabel())

@section('content')
@php
  $typeKey = $booking->adminTypeKey();
  $typeClasses = [
    'flash' => 'bg-sky-50 text-sky-700',
    'custom' => 'bg-violet-50 text-violet-700',
    'payment_link' => 'bg-fuchsia-50 text-fuchsia-700',
  ];
  $statusClasses = [
    'pending' => 'bg-amber-50 text-amber-700',
    'confirmed' => 'bg-green-50 text-green-700',
    'completed' => 'bg-blue-50 text-blue-700',
    'cancelled' => 'bg-red-50 text-red-700',
    'no_show' => 'bg-orange-50 text-orange-700',
    'rescheduled' => 'bg-purple-50 text-purple-700',
  ];
  $sessionTimes = $booking->booking_time;
  $consultTimes = $booking->consultation_time;
  $details = is_array($booking->custom_tattoo_details) ? $booking->custom_tattoo_details : [];
@endphp
<main class="main-content flex-1 min-h-screen">
  <div class="p-6 md:p-10 lg:p-12 max-w-5xl">
    <a href="{{ route('admin.bookings.index') }}" class="inline-flex items-center gap-1 text-sm text-on-surface-variant hover:text-primary mb-6 transition-colors">
      <span class="material-symbols-outlined text-[18px]">arrow_back</span> Back to bookings
    </a>

    <div class="mb-6 flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
      <div>
        <h2 class="text-3xl font-extrabold text-on-surface tracking-tight">{{ $booking->referenceLabel() }}</h2>
        <p class="text-on-surface-variant mt-1">{{ $booking->displayTitle() }}</p>
        <div class="flex flex-wrap items-center gap-2 mt-3">
          <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold {{ $typeClasses[$typeKey] ?? 'bg-gray-100 text-gray-700' }}">{{ $booking->adminTypeLabel() }}</span>
          <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold {{ $statusClasses[$booking->status] ?? 'bg-gray-100 text-gray-700' }}">{{ str_replace('_', ' ', ucfirst((string) $booking->status)) }}</span>
          <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold bg-surface-container text-on-surface-variant">Payment: {{ ucfirst((string) ($booking->payment_status ?: '—')) }}</span>
        </div>
      </div>
      <p class="text-sm text-on-surface-variant">Created {{ $booking->created_at?->format('M j, Y · H:i') }}</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-5">
      @include('admin.partials.detail-card', [
        'title' => 'Client',
        'rows' => [
          ['label' => 'Name', 'value' => $booking->user?->name ?: ('Client #'.$booking->user_id)],
          ['label' => 'Email', 'value' => $booking->user?->email],
          ['label' => 'Phone', 'value' => $booking->user?->phone_number],
          ['label' => 'User ID', 'value' => $booking->user_id],
        ],
      ])

      @include('admin.partials.detail-card', [
        'title' => 'Artist',
        'rows' => [
          ['label' => 'Name', 'value' => $booking->artist?->name ?: ('Artist #'.$booking->artist_user_id)],
          ['label' => 'Email', 'value' => $booking->artist?->email],
          ['label' => 'Phone', 'value' => $booking->artist?->phone_number],
          ['label' => 'User ID', 'value' => $booking->artist_user_id],
        ],
      ])
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-5">
      @include('admin.partials.detail-card', [
        'title' => 'Session',
        'rows' => [
          ['label' => 'Session date', 'value' => $booking->booking_date?->format('l, F j, Y')],
          ['label' => 'Session time', 'value' => is_array($sessionTimes) ? (($sessionTimes['start'] ?? '—').' – '.($sessionTimes['end'] ?? '—')) : null],
          ['label' => 'Timezone', 'value' => $booking->timezone ?: 'UTC'],
          ['label' => 'Consultation', 'value' => $booking->has_consultation ? 'Yes' : 'No'],
          ['label' => 'Consultation date', 'value' => $booking->has_consultation ? ($booking->consultation_date?->format('l, F j, Y') ?: $booking->booking_date?->format('l, F j, Y')) : null],
          ['label' => 'Consultation time', 'value' => $booking->has_consultation && is_array($consultTimes) ? (($consultTimes['start'] ?? '—').' – '.($consultTimes['end'] ?? '—')) : null],
          ['label' => 'Consultation timing', 'value' => $booking->consultation_timing_type],
        ],
      ])

      @include('admin.partials.detail-card', [
        'title' => 'Payment',
        'rows' => [
          ['label' => 'Deposit', 'value' => $booking->deposit_amount !== null ? '€'.number_format((float) $booking->deposit_amount, 2) : null],
          ['label' => 'Quoted amount', 'value' => $booking->quoteAmount() > 0 ? '€'.number_format($booking->quoteAmount(), 2) : null],
          ['label' => 'Total paid', 'value' => $booking->total_amount_paid !== null ? '€'.number_format((float) $booking->total_amount_paid, 2) : null],
          ['label' => 'Platform fee', 'value' => $booking->platform_fee !== null ? '€'.number_format((float) $booking->platform_fee, 2) : null],
          ['label' => 'Tax', 'value' => $booking->tax_amount !== null ? '€'.number_format((float) $booking->tax_amount, 2).($booking->tax_label ? ' ('.$booking->tax_label.')' : '') : null],
          ['label' => 'Currency', 'value' => strtoupper((string) ($booking->currency ?: 'EUR'))],
          ['label' => 'Provider', 'value' => $booking->payment_provider],
          ['label' => 'Payment intent', 'value' => $booking->payment_intent_id],
          ['label' => 'Viva order', 'value' => $booking->viva_order_code],
          ['label' => 'Full amount paid', 'value' => $booking->full_amount_paid ? 'Yes' : 'No'],
          ['label' => 'Balance due', 'value' => '€'.number_format($booking->remainingBalanceAmount(), 2)],
        ],
      ])
    </div>

    @if($booking->tattoo || $details !== [])
      <div class="mb-5">
        @include('admin.partials.detail-card', [
          'title' => 'Design / service',
          'rows' => array_values(array_filter([
            ['label' => 'Title', 'value' => $booking->displayTitle()],
            $booking->tattoo ? ['label' => 'Design ID', 'value' => $booking->tattoo_id] : null,
            $booking->tattoo ? ['label' => 'Design price range', 'value' => '€'.number_format((float) ($booking->tattoo->min_price ?? 0), 2).' – €'.number_format((float) ($booking->tattoo->max_price ?? 0), 2)] : null,
            isset($details['payment_link_id']) ? ['label' => 'Payment link ID', 'value' => $details['payment_link_id']] : null,
            isset($details['custom_request_id']) ? ['label' => 'Custom request ID', 'value' => $details['custom_request_id']] : null,
            isset($details['reference']) ? ['label' => 'Reference', 'value' => $details['reference']] : null,
            isset($details['estimated_price']) ? ['label' => 'Estimated price', 'value' => '€'.number_format((float) $details['estimated_price'], 2)] : null,
            isset($details['estimated_time']) ? ['label' => 'Estimated time', 'value' => $details['estimated_time']] : null,
            isset($details['number_of_sessions']) ? ['label' => 'Sessions', 'value' => $details['number_of_sessions']] : null,
          ])),
        ])
      </div>
    @endif

    <div class="mb-5">
      @include('admin.partials.question-answers', ['questionsAnswers' => $booking->questions_answers])
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-5">
      @include('admin.partials.detail-card', [
        'title' => 'Notes & history',
        'rows' => [
          ['label' => 'Notes', 'value' => $booking->notes],
          ['label' => 'Completion notes', 'value' => $booking->completion_notes],
          ['label' => 'Completed at', 'value' => $booking->completed_at?->format('M j, Y · H:i')],
          ['label' => 'Cancelled at', 'value' => $booking->cancelled_at?->format('M j, Y · H:i')],
          ['label' => 'Cancellation reason', 'value' => $booking->cancellation_reason],
          ['label' => 'Reschedule status', 'value' => $booking->reschedule_status],
          ['label' => 'Reschedule reason', 'value' => $booking->reschedule_reason],
        ],
      ])

      @include('admin.partials.detail-card', [
        'title' => 'System',
        'rows' => [
          ['label' => 'Booking ID', 'value' => $booking->id],
          ['label' => 'Booking type (DB)', 'value' => $booking->booking_type],
          ['label' => 'Google Calendar event', 'value' => $booking->google_calendar_event_id],
          ['label' => 'Updated', 'value' => $booking->updated_at?->format('M j, Y · H:i')],
        ],
      ])
    </div>
  </div>
</main>
@endsection
