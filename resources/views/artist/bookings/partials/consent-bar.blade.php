@php
  $consentAnswer = $booking->consentAnswer;
  $consentCompleted = $consentAnswer && $consentAnswer->isCompleted();
  $consentDetail = $consentCompleted ? $consentAnswer->toArtistDetailArray() : null;

  if (! $consentCompleted && (string) $booking->status === 'confirmed') {
      $consentAnswer = app(\App\Services\BookingConsentService::class)->syncForBooking($booking) ?? $consentAnswer;
  }

  $consentUrl = ($consentAnswer && ! $consentCompleted && $consentAnswer->isOpenForClient())
      ? $consentAnswer->publicUrl()
      : null;
  $resendUrl = route('api.bookings.consent.resend', $booking->id);

  $barPad = ($compact ?? false) ? 'py-2.5' : 'py-2';
  $barExtra = ($compact ?? false) ? '' : 'mb-2 min-w-[13.5rem]';
@endphp
@if($consentCompleted)
  <div class="js-artist-consent-wrap flex items-center justify-between gap-3 rounded-xl bg-[#f4eee4] px-3 {{ $barPad }} {{ $barExtra }}">
    <p class="js-artist-consent-label text-sm font-bold text-[#8a5a12] whitespace-nowrap">Consent submitted &amp; signed</p>
    <button type="button"
      class="js-artist-consent-view text-sm font-bold text-[#1b5e4a] underline underline-offset-2 whitespace-nowrap"
      aria-haspopup="dialog"
      aria-controls="artistConsentDetailModal"
      data-booking-id="{{ $booking->id }}"
      data-client-name="{{ e($clientName) }}"
      data-booking-ref="{{ e($bookingRef) }}"
      data-date-display="{{ e($dateLong) }}"
      data-time-range="{{ e($startEnd) }}"
      data-consent='@json($consentDetail)'>View detail</button>
  </div>
@else
  <div class="js-artist-consent-wrap flex items-center justify-between gap-3 rounded-xl bg-[#f4eee4] px-3 {{ $barPad }} {{ $barExtra }}">
    <p class="js-artist-consent-label text-sm font-bold text-[#8a5a12] whitespace-nowrap">Consent pending</p>
    <button type="button"
      class="js-artist-consent-pending text-sm font-bold text-[#1b5e4a] underline underline-offset-2 whitespace-nowrap"
      aria-haspopup="dialog"
      aria-controls="artistConsentPendingModal"
      data-booking-id="{{ $booking->id }}"
      data-client-name="{{ e($clientName) }}"
      data-booking-ref="{{ e($bookingRef) }}"
      data-date-display="{{ e($dateLong) }}"
      data-time-range="{{ e($startEnd) }}"
      data-consent-url="{{ e($consentUrl ?? '') }}"
      data-resend-url="{{ e($resendUrl) }}">View</button>
  </div>
@endif
