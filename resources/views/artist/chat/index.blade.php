@extends('layouts.new-artist-dashboard-layout')

@section('title', 'Inbox')

@php
  $activeNav = 'inbox';
@endphp

@section('styles')
  @include('artist.bookings.partials.detail-modal-styles')
  <style>
    /* Closed overlays must not cover Inbox (opacity-only hide failed in practice). */
    #artistBookingDetailModal:not(.artist-bdm-open){display:none!important}
    #customRequestModal.ov:not(.open),
    #customDeclineModal.ov:not(.open),
    #requestDetailModal.ov:not(.open){display:none!important}
  </style>
@endsection

@section('content')
  {{-- Inbox first so the page always paints even if modal partials are heavy. --}}
  <x-chat-inbox
    skin="bookpay"
    :role="$role"
    :stream-configured="$streamConfigured"
    :has-open-booking="$hasOpenBooking"
    :has-conversations="$hasConversations"
  />

  @include('artist.bookings.partials.detail-modal', ['inboxMode' => true])
  @include('artist.requests.partials.custom-detail-modals')
  @include('artist.requests.partials.design-detail-modals')
  <script src="{{ asset('js/question-answer-display.js') }}"></script>
  <script src="{{ asset('js/artist-booking-detail-modal.js') }}?v=4"></script>
  <script>
    window.inkjinInboxDetailUrls = {
      booking: @json(url('/artist/bookings')),
      designRequest: @json(url('/artist/requests')),
      customRequest: @json(url('/artist/custom-requests')),
    };
  </script>
@endsection
