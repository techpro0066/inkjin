@php
  /** @var \App\Models\Booking $booking */
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
  $startEnd = ($bt !== null && isset($bt['start'], $bt['end'])) ? "{$bt['start']} – {$bt['end']}" : '—';
  $startOnly = ($bt !== null && isset($bt['start'])) ? (string) $bt['start'] : '';
  $duration = '—';
  $durationShort = '';
  if ($bt !== null && isset($bt['duration_minutes'])) {
      $mins = (int) $bt['duration_minutes'];
      $hours = intdiv(max(1, $mins), 60);
      $tail = max(1, $mins) % 60;
      $duration = $mins < 60
          ? "{$mins} min"
          : ($tail ? "{$hours}h {$tail}m" : "{$hours}h");
      $durationShort = $mins < 60 ? "{$mins}m" : ($tail ? "{$hours}h {$tail}m" : "{$hours}h");
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
@endphp
