@php
  /** @var \App\Models\Booking $booking */
  $statusStyles = $statusStyles ?? [
      'confirmed' => 'g',
      'pending' => 'a',
      'cancelled' => 'r',
      'declined' => 'r',
      'completed' => 'k',
      'no_show' => 'a',
      'rescheduled' => 'b',
  ];
  $statusBadgeRing = $statusBadgeRing ?? [
      'confirmed' => 'bg-green-50 text-green-700 ring-green-500/20',
      'pending' => 'bg-amber-50 text-amber-900 ring-amber-500/20',
      'cancelled' => 'bg-red-50 text-red-700 ring-red-500/20',
      'declined' => 'bg-red-50 text-red-700 ring-red-500/20',
      'completed' => 'bg-slate-100 text-slate-700 ring-slate-500/15',
      'no_show' => 'bg-orange-50 text-orange-900 ring-orange-500/20',
      'rescheduled' => 'bg-blue-50 text-blue-800 ring-blue-500/15',
  ];
  $bt = $booking->booking_time;
  $sessionDate = $booking->booking_date?->format('Y-m-d') ?? '';
  $clientName = trim(($booking->user?->first_name ?? '') . ' ' . ($booking->user?->last_name ?? ''));
  $clientName = $clientName !== '' ? $clientName : 'Client #' . ($booking->user_id ?? '');
  $clientLower = \Illuminate\Support\Str::lower($clientName);
  $nameParts = preg_split('/\s+/', trim($clientName)) ?: [];
  $clientInitials = strtoupper(collect($nameParts)->filter()->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode('') ?: 'CL');
  $details = $booking->custom_tattoo_details ?? null;
  $customTitle = is_array($details) ? ($details['title'] ?? null) : null;
  $serviceTitle = ($booking->tattoo && filled($booking->tattoo->title))
      ? $booking->tattoo->title
      : (filled($customTitle) ? (string) $customTitle : ucfirst((string) $booking->booking_type) . ' booking');
  $startEnd = '—';
  $startOnly = '';
  if ($bt !== null && ! empty($bt['start_datetime']) && ! empty($bt['end_datetime'])) {
      $startLocal = \Carbon\Carbon::parse($bt['start_datetime']);
      $endLocal = \Carbon\Carbon::parse($bt['end_datetime']);
      $startEnd = $startLocal->format('H:i').' – '.$endLocal->format('H:i');
      $startOnly = $startLocal->format('H:i');
  } elseif ($bt !== null && isset($bt['start'], $bt['end'])) {
      $startEnd = "{$bt['start']} – {$bt['end']}";
      $startOnly = (string) $bt['start'];
  }
  $duration = '—';
  $durationShort = '';
  if ($bt !== null && isset($bt['duration_minutes'])) {
      $mins = (int) $bt['duration_minutes'];
      $hours = intdiv(max(0, $mins), 60);
      $tail = max(0, $mins) % 60;
      if ($mins < 60) {
          $duration = "{$mins} min";
          $durationShort = "{$mins}m";
      } else {
          $duration = $tail ? "{$hours}h {$tail}m" : "{$hours}h";
          $durationShort = $tail ? "{$hours}h {$tail}m" : "{$hours}h";
      }
  }
  $stKey = strtolower((string) ($booking->status ?? ''));
  $badgeCls = ($statusBadgeRing[$stKey] ?? null) ?: 'bg-surface-container text-on-surface ring-outline-variant/20';
  $pillCls = ($statusStyles[$stKey] ?? null) ?: 'k';
  $label = ucfirst(str_replace('_', ' ', $stKey ?: 'unknown'));
  $sortStamp = strtotime($sessionDate ?: '1970-01-01') ?: 0;
  $artistRequestPending = (
      $booking->reschedule_status === 'pending'
      && $booking->reschedule_requested_by === 'artist'
  );
  $designImage = asset('design/images/icons/avatar.jpg');
  if ($booking->tattoo && ! empty($booking->tattoo->image)) {
      $imgRaw = (string) $booking->tattoo->image;
      $designImage = str_starts_with($imgRaw, 'http://') || str_starts_with($imgRaw, 'https://')
          ? $imgRaw
          : asset(ltrim($imgRaw, '/'));
  }
  $bookingRef = $booking->referenceLabel();
  $dateLong = $booking->booking_date?->format('l, F j, Y') ?? '—';
  $dateShort = $booking->booking_date?->format('M j, Y') ?? '—';
  $clientEmail = (string) ($booking->user?->email ?? '');
  $clientPhone = trim((string) ($booking->user?->phone_number ?? ''));
  if ($clientPhone === '') {
      $clientPhone = trim((string) ($booking->user?->userDetail?->mobile_number ?? ''));
  }
  $questionsAnswersData = is_array($booking->questions_answers ?? null) ? $booking->questions_answers : [];
  $balanceLabel = $booking->estimatedBalanceLabel();
  $balanceDue = $booking->remainingBalanceAmount();
  $showBalanceDue = $balanceDue > 0
      && ! $booking->remaining_amount_released
      && ! $booking->full_amount_paid
      && ! in_array($stKey, ['cancelled', 'completed', 'declined'], true);
  $balanceDueRounded = round($balanceDue, 2);
  $balanceDueLabel = fmod($balanceDueRounded, 1.0) === 0.0
      ? '€'.(string) (int) $balanceDueRounded
      : '€'.number_format($balanceDueRounded, 2, '.', '');
  $paidRounded = round((float) ($booking->deposit_amount ?? 0), 2);
  $paidLabel = fmod($paidRounded, 1.0) === 0.0
      ? '€'.(string) (int) $paidRounded
      : '€'.number_format($paidRounded, 2, '.', '');
  $totalRounded = round($paidRounded + $balanceDue, 2);
  $totalLabel = fmod($totalRounded, 1.0) === 0.0
      ? '€'.(string) (int) $totalRounded
      : '€'.number_format($totalRounded, 2, '.', '');
  $unsettledCollection = $booking->latestBalanceCollection;
  $isUnsettledReminder = $unsettledCollection && $unsettledCollection->isUnsettledOpen();
  $expectedPhrase = $isUnsettledReminder ? $unsettledCollection->expectedDuePhrase() : '';
  $collectBarLabel = $isUnsettledReminder
      ? $balanceDueLabel.' due · '.$expectedPhrase
      : $balanceDueLabel.' balance due';
  $unsettledNote = $isUnsettledReminder ? trim((string) $unsettledCollection->note) : '';
  $unsettledFirstName = trim((string) strtok($clientName, ' '));
  $unsettledNudge = $isUnsettledReminder
      ? (string) ($unsettledCollection->reminderNudge($unsettledFirstName, $balanceDueLabel) ?? '')
      : '';

  $consentAnswer = $booking->consentAnswer;
  $consentCompleted = $consentAnswer && $consentAnswer->isCompleted();
  if (! $consentCompleted && $stKey === 'confirmed') {
      try {
          $consentAnswer = app(\App\Services\BookingConsentService::class)->syncForBooking($booking) ?? $consentAnswer;
      } catch (\Throwable $e) {
          // leave as-is
      }
  }
  $consentNeedsGuardian = false;
  if ($consentAnswer && $consentAnswer->date_of_birth) {
      $consentNeedsGuardian = $consentAnswer->date_of_birth->age < 18
          && empty($consentAnswer->guardian_signed_at);
  }
  $consentUrl = ($consentAnswer && ! $consentCompleted && $consentAnswer->isOpenForClient())
      ? $consentAnswer->publicUrl()
      : null;
  $consentDetail = $consentCompleted ? $consentAnswer->toArtistDetailArray() : null;
  $resendUrl = route('api.bookings.consent.resend', $booking->id);
  $clientFirstName = trim((string) ($booking->user?->first_name ?? ''));
  if ($clientFirstName === '') {
      $clientFirstName = explode(' ', $clientName)[0] ?? 'Client';
  }
  $consentDateShort = $booking->booking_date?->format('D, M j') ?? $dateShort;
  $consentAutoNote = '';
  if ($consentAnswer && ($consentAnswer->sent_at || $consentAnswer->send_at)) {
      $consentAutoNote = 'Consent form sent 48 hours before booking';
  }

  $paymentState = 'none';
  $paymentLabel = '';
  if ($stKey === 'pending') {
      $paymentState = 'waiting';
      $paymentLabel = 'Waiting for deposit';
  } elseif ($showBalanceDue) {
      $paymentState = 'due';
      $paymentLabel = $balanceDueLabel.' due';
  } elseif ($stKey === 'confirmed' || $stKey === 'completed') {
      $paymentState = 'paid';
      $paymentLabel = 'Paid';
  }

  $consentState = 'none';
  $consentLabel = '';
  if ($stKey === 'confirmed' || $consentCompleted) {
      if ($consentCompleted && ! $consentNeedsGuardian) {
          $consentState = 'signed';
          $consentLabel = 'Signed';
      } elseif ($consentNeedsGuardian) {
          $consentState = 'guardian';
          $consentLabel = 'Guardian needed';
      } else {
          $consentState = 'pending';
          $consentLabel = 'Not signed';
      }
  }

  $dateMeta = trim(($startEnd !== '—' ? $startEnd : '').($durationShort !== '' ? ' · '.$durationShort : ''));

  $artistUserDetail = $artistUserDetail ?? auth()->user()?->userDetail;
  $moneyBreakdown = app(\App\Services\ArtistPayoutService::class)
      ->bookingDetailsMoneyBreakdown($booking, $artistUserDetail);
  $moneyBreakdownAttr = base64_encode(json_encode($moneyBreakdown, JSON_THROW_ON_ERROR));
@endphp
<tr class="booking-group border-t border-outline-variant/10 hover:bg-[#FBF8FC]"
    data-booking-row
    data-booking-id="{{ $booking->id }}"
    data-status="{{ $stKey }}"
    data-client="{{ e($clientLower) }}"
    data-ref="{{ e(\Illuminate\Support\Str::lower($bookingRef)) }}"
    data-date="{{ $sessionDate }}"
    data-time="{{ e($startOnly) }}"
    data-service="{{ e($serviceTitle) }}"
    data-sort-ts="{{ $sortStamp }}"
    data-sort-name="{{ e($clientLower) }}"
    data-sort-id="{{ $booking->id }}"
    data-money="{{ $moneyBreakdownAttr }}">
  <td data-label="CLIENT">
    <div class="row" style="gap:10px">
      <div class="ini" style="width:30px;height:30px;font-size:11px">{{ $clientInitials }}</div>
      <div>
        <b style="font-weight:600;white-space:nowrap">{{ $clientName }}</b>
        <div class="faint" style="font-size:11.5px">{{ $bookingRef }}</div>
      </div>
    </div>
  </td>
  <td data-label="SERVICE" style="white-space:nowrap">{{ $serviceTitle }}</td>
  <td data-label="DATE" style="white-space:nowrap">
    {{ $dateShort }}
    @if($dateMeta !== '')
      <div class="faint" style="font-size:11.5px">{{ $dateMeta }}</div>
    @endif
  </td>
  <td data-label="STATUS">
    <span class="js-artist-status-badge pill {{ $pillCls }}">{{ $label }}</span>
  </td>
  <td data-label="PAYMENT" style="white-space:nowrap">
    @if($paymentState === 'due')
      <button type="button"
        class="js-artist-collect-payment stbtn"
        title="Request payment"
        aria-haspopup="dialog"
        aria-controls="artistRequestPaymentModal"
        data-booking-id="{{ $booking->id }}"
        data-store-url="{{ route('api.bookings.balance-collections.store', $booking->id) }}"
        data-service="{{ e($serviceTitle) }}"
        data-client-name="{{ e($clientName) }}"
        data-client-initials="{{ e($clientInitials) }}"
        data-booking-ref="{{ e($bookingRef) }}"
        data-balance-label="{{ e($balanceDueLabel) }}"
        data-paid-label="{{ e($paidLabel) }}"
        data-total-label="{{ e($totalLabel) }}"
        data-date-display="{{ e($booking->booking_date?->format('D, M j') ?? $dateShort) }}"
        data-time-range="{{ e($startEnd) }}"
        data-duration-minutes="{{ (int) (is_array($bt) ? ($bt['duration_minutes'] ?? 0) : 0) }}"
        data-status-label="{{ e($label) }}"
        data-status-pill="{{ e($pillCls) }}"
        data-status-badge-class="{{ e($badgeCls) }}"
        data-unsettled="{{ $isUnsettledReminder ? '1' : '0' }}"
        data-expected-label="{{ e($expectedPhrase) }}"
        data-unsettled-note="{{ e($unsettledNote) }}"
        data-nudge="{{ e($unsettledNudge) }}"
        style="display:inline-flex;align-items:center;gap:2px;padding:3px 4px 3px 10px;border-radius:20px;background:#FFF4E0;color:#B86E00;border:1px solid #F2D39A;font-size:12px;font-weight:700;white-space:nowrap;cursor:pointer">
        {{ $paymentLabel }}<span class="material-symbols-outlined" style="font-size:16px">chevron_right</span>
      </button>
    @elseif($paymentState === 'paid')
      <button type="button"
        class="js-artist-booking-view stbtn"
        title="View payment details"
        aria-haspopup="dialog"
        aria-controls="artistBookingDetailModal"
        data-booking-id="{{ $booking->id }}"
        data-booking-ref="{{ e($bookingRef) }}"
        data-client-name="{{ e($clientName) }}"
        data-client-email="{{ e($clientEmail) }}"
        data-client-phone="{{ e($clientPhone) }}"
        data-service="{{ e($serviceTitle) }}"
        data-date-display="{{ e($dateLong) }}"
        data-time-range="{{ e($startEnd) }}"
        data-duration="{{ e($duration) }}"
        data-timezone="{{ e($booking->timezone ?: 'UTC') }}"
        data-status-label="{{ e($label) }}"
        data-status-badge-class="{{ e($badgeCls) }}"
        data-booking-type="{{ e(ucfirst((string) ($booking->booking_type ?? ''))) }}"
        data-deposit="{{ e('€' . number_format((float) ($booking->deposit_amount ?? 0), 2)) }}"
        data-balance="{{ e($balanceLabel) }}"
        data-questions='@json($questionsAnswersData)'
        data-design-image="{{ e($designImage) }}"
        data-booking-status="{{ e($stKey) }}"
        data-full-amount-paid="{{ $booking->full_amount_paid ? '1' : '0' }}"
        data-balance-due-label="{{ e($balanceDueLabel) }}"
        data-aftercare-sent="{{ $booking->aftercare_sent_at ? '1' : '0' }}"
        data-money="{{ $moneyBreakdownAttr }}"
        style="display:inline-flex;align-items:center;gap:2px;padding:3px 4px 3px 10px;border-radius:20px;background:#E6F6EC;color:#1F7A45;border:1px solid #B7E2C6;font-size:12px;font-weight:700;white-space:nowrap;cursor:pointer">
        Paid<span class="material-symbols-outlined" style="font-size:16px">chevron_right</span>
      </button>
    @elseif($paymentState === 'waiting')
      <span class="text-[#9A929E]">{{ $paymentLabel }}</span>
    @endif
  </td>
  <td data-label="CONSENT">
    @if($consentState === 'signed')
      <button type="button"
        class="js-artist-consent-view stbtn"
        title="View signed form"
        aria-haspopup="dialog"
        aria-controls="artistConsentDetailModal"
        data-booking-id="{{ $booking->id }}"
        data-client-name="{{ e($clientName) }}"
        data-booking-ref="{{ e($bookingRef) }}"
        data-date-display="{{ e($dateLong) }}"
        data-time-range="{{ e($startEnd) }}"
        data-consent='@json($consentDetail)'
        style="display:inline-flex;align-items:center;gap:2px;padding:3px 4px 3px 10px;border-radius:20px;background:#E6F6EC;color:#1F7A45;border:1px solid #B7E2C6;font-size:12px;font-weight:700;white-space:nowrap;cursor:pointer">
        Signed<span class="material-symbols-outlined" style="font-size:16px">chevron_right</span>
      </button>
    @elseif($consentState === 'guardian' || $consentState === 'pending')
      <button type="button"
        class="js-artist-consent-pending stbtn"
        title="{{ $consentState === 'guardian' ? 'Client is under 18. Guardian must sign in studio' : 'Resend or copy the form link' }}"
        aria-haspopup="dialog"
        aria-controls="artistConsentPendingModal"
        data-booking-id="{{ $booking->id }}"
        data-client-name="{{ e($clientName) }}"
        data-client-first-name="{{ e($clientFirstName) }}"
        data-client-initials="{{ e($clientInitials) }}"
        data-service="{{ e($serviceTitle) }}"
        data-booking-ref="{{ e($bookingRef) }}"
        data-date-display="{{ e($consentDateShort) }}"
        data-time-range="{{ e($startEnd) }}"
        data-status-label="{{ e($label) }}"
        data-status-pill="{{ e($pillCls) }}"
        data-auto-note="{{ e($consentAutoNote) }}"
        data-consent-url="{{ e($consentUrl ?? '') }}"
        data-resend-url="{{ e($resendUrl) }}"
        style="display:inline-flex;align-items:center;gap:2px;padding:3px 4px 3px 10px;border-radius:20px;background:#FFF4E0;color:#B86E00;border:1px solid #F2D39A;font-size:12px;font-weight:700;white-space:nowrap;cursor:pointer">
        {{ $consentLabel }}<span class="material-symbols-outlined" style="font-size:16px">chevron_right</span>
      </button>
    @endif
  </td>
  <td data-label="ACTIONS" class="relative">
    <div class="js-artist-row-actions flex items-center justify-end">
      <button type="button"
        class="js-artist-booking-menu inline-flex h-9 w-9 items-center justify-center rounded-lg text-[#9A929E] hover:bg-[#F0EDF2] hover:text-[#1A1A1A]"
        title="More actions"
        aria-haspopup="menu"
        aria-expanded="false"
        aria-label="Booking actions"
        data-booking-id="{{ $booking->id }}">
        <span class="material-symbols-outlined ms" style="font-size:22px;color:#9A929E">more_horiz</span>
      </button>
      <div class="js-artist-booking-menu-source hidden" aria-hidden="true">
        <button type="button"
          class="js-artist-booking-view"
          data-menu-icon="visibility"
          data-menu-label="View booking"
          aria-haspopup="dialog"
          aria-controls="artistBookingDetailModal"
          data-booking-id="{{ $booking->id }}"
          data-booking-ref="{{ e($bookingRef) }}"
          data-client-name="{{ e($clientName) }}"
          data-client-email="{{ e($clientEmail) }}"
          data-client-phone="{{ e($clientPhone) }}"
          data-service="{{ e($serviceTitle) }}"
          data-date-display="{{ e($dateLong) }}"
          data-time-range="{{ e($startEnd) }}"
          data-duration="{{ e($duration) }}"
          data-timezone="{{ e($booking->timezone ?: 'UTC') }}"
          data-status-label="{{ e($label) }}"
          data-status-badge-class="{{ e($badgeCls) }}"
          data-booking-type="{{ e(ucfirst((string) ($booking->booking_type ?? ''))) }}"
          data-deposit="{{ e('€' . number_format((float) ($booking->deposit_amount ?? 0), 2)) }}"
          data-balance="{{ e($balanceLabel) }}"
          data-questions='@json($questionsAnswersData)'
          data-design-image="{{ e($designImage) }}"
          data-booking-status="{{ e($stKey) }}"
          data-full-amount-paid="{{ $booking->full_amount_paid ? '1' : '0' }}"
          data-balance-due-label="{{ e($balanceDueLabel) }}"
          data-aftercare-sent="{{ $booking->aftercare_sent_at ? '1' : '0' }}"
          data-money="{{ $moneyBreakdownAttr }}"></button>
        @if($booking->canViewChat())
          <a href="{{ route('artist.chat.index', ['client' => $booking->user_id, 'booking' => $booking->id]) }}"
            class="js-artist-message-client"
            data-menu-icon="chat_bubble"
            data-menu-label="{{ $booking->isOpenForChat() ? 'Message client' : 'View chat history' }}"
            data-client-id="{{ $booking->user_id }}"
            data-booking-id="{{ $booking->id }}"
            data-client-name="{{ e($clientName) }}"
            data-client-initials="{{ e($clientInitials) }}"
            data-booking-ref="{{ e($bookingRef) }}"
            data-booking-meta="{{ e(trim(($serviceTitle ?: '') . ($dateShort && $dateShort !== '—' ? ' · ' . $dateShort : ''))) }}"
            data-can-chat="{{ $booking->isOpenForChat() ? '1' : '0' }}"></a>
        @endif
        @if(($booking->status === 'confirmed' || $artistRequestPending) && ! $artistRequestPending)
          <button type="button"
            class="js-artist-reschedule-request"
            data-menu-icon="event_repeat"
            data-menu-label="Reschedule"
            data-booking-id="{{ $booking->id }}"
            data-request-url="{{ route('api.bookings.artist-request-reschedule', $booking->id) }}"
            data-pending="0"
            data-client-name="{{ e($clientName) }}"
            data-client-initials="{{ e($clientInitials) }}"
            data-service="{{ e($serviceTitle) }}"
            data-booking-ref="{{ e($bookingRef) }}"
            data-booking-date="{{ e($booking->booking_date?->format('D, M j') ?? $dateShort) }}"
            data-time-range="{{ e($startEnd) }}"
            data-status-label="{{ e($label) }}"
            data-status-pill="{{ e($pillCls) }}"></button>
        @endif
        @if($paymentState === 'due')
          <button type="button"
            class="js-artist-collect-payment"
            data-menu-icon="payments"
            data-menu-label="Request payment"
            data-booking-id="{{ $booking->id }}"
            data-store-url="{{ route('api.bookings.balance-collections.store', $booking->id) }}"
            data-service="{{ e($serviceTitle) }}"
            data-client-name="{{ e($clientName) }}"
            data-client-initials="{{ e($clientInitials) }}"
            data-booking-ref="{{ e($bookingRef) }}"
            data-balance-label="{{ e($balanceDueLabel) }}"
            data-paid-label="{{ e($paidLabel) }}"
            data-total-label="{{ e($totalLabel) }}"
            data-date-display="{{ e($booking->booking_date?->format('D, M j') ?? $dateShort) }}"
            data-time-range="{{ e($startEnd) }}"
            data-duration-minutes="{{ (int) (is_array($bt) ? ($bt['duration_minutes'] ?? 0) : 0) }}"
            data-status-label="{{ e($label) }}"
            data-status-pill="{{ e($pillCls) }}"
            data-status-badge-class="{{ e($badgeCls) }}"
            data-unsettled="{{ $isUnsettledReminder ? '1' : '0' }}"
            data-expected-label="{{ e($expectedPhrase) }}"
            data-unsettled-note="{{ e($unsettledNote) }}"
            data-nudge="{{ e($unsettledNudge) }}"></button>
        @endif
        @if($consentState === 'signed')
          <button type="button"
            class="js-artist-consent-view"
            data-menu-icon="draw"
            data-menu-label="Consent form"
            aria-haspopup="dialog"
            aria-controls="artistConsentDetailModal"
            data-booking-id="{{ $booking->id }}"
            data-client-name="{{ e($clientName) }}"
            data-booking-ref="{{ e($bookingRef) }}"
            data-date-display="{{ e($dateLong) }}"
            data-time-range="{{ e($startEnd) }}"
            data-consent='@json($consentDetail)'></button>
        @elseif($consentState === 'guardian' || $consentState === 'pending')
          <button type="button"
            class="js-artist-consent-pending"
            data-menu-icon="draw"
            data-menu-label="Consent form"
            aria-haspopup="dialog"
            aria-controls="artistConsentPendingModal"
            data-booking-id="{{ $booking->id }}"
            data-client-name="{{ e($clientName) }}"
            data-client-first-name="{{ e($clientFirstName) }}"
            data-client-initials="{{ e($clientInitials) }}"
            data-service="{{ e($serviceTitle) }}"
            data-booking-ref="{{ e($bookingRef) }}"
            data-date-display="{{ e($consentDateShort) }}"
            data-time-range="{{ e($startEnd) }}"
            data-status-label="{{ e($label) }}"
            data-status-pill="{{ e($pillCls) }}"
            data-auto-note="{{ e($consentAutoNote) }}"
            data-consent-url="{{ e($consentUrl ?? '') }}"
            data-resend-url="{{ e($resendUrl) }}"></button>
        @endif
        @if($booking->status === 'confirmed' && ! $showBalanceDue)
          <button type="button"
            class="js-artist-mark-complete"
            data-menu-icon="task_alt"
            data-menu-label="Complete booking"
            data-booking-id="{{ $booking->id }}"
            data-has-balance="0"
            data-client-name="{{ e($clientName) }}"
            data-service="{{ e($serviceTitle) }}"
            data-mark-completed-url="{{ route('api.bookings.mark-completed', $booking->id) }}"></button>
        @elseif($stKey === 'completed')
          @php
            $aftercareCanSendAgain = ! $booking->aftercare_sent_at
              || $booking->aftercare_sent_at->lte(now()->subDay());
            $aftercareMenuLabel = ! $booking->aftercare_sent_at
              ? 'Send aftercare'
              : ($aftercareCanSendAgain ? 'Send again' : 'Aftercare sent');
          @endphp
          <button type="button"
            class="js-artist-send-aftercare"
            data-menu-icon="healing"
            data-menu-label="{{ $aftercareMenuLabel }}"
            data-booking-id="{{ $booking->id }}"
            data-aftercare-sent="{{ $booking->aftercare_sent_at ? '1' : '0' }}"
            data-client-name="{{ e($clientName) }}"
            data-client-email="{{ e($clientEmail) }}"
            data-service="{{ e($serviceTitle) }}"
            data-booking-ref="{{ e($bookingRef) }}"></button>
        @endif
        @if($booking->status === 'confirmed')
          <button type="button"
            class="js-artist-cancel-booking"
            data-menu-icon="cancel"
            data-menu-label="Cancel booking"
            data-menu-danger="1"
            data-menu-divider-before="1"
            data-booking-id="{{ $booking->id }}"
            data-cancel-url="{{ route('api.bookings.cancel', $booking->id) }}"
            data-cancel-info-url="{{ route('api.bookings.cancellation-info', $booking->id) }}"
            data-booking-ref="{{ e($bookingRef) }}"></button>
        @endif
      </div>
    </div>
  </td>
</tr>
