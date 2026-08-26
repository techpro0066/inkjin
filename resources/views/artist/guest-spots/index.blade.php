@extends('layouts.artist_dashboard_layout')

@section('title', 'Guest spots')

@section('styles')
@if(config('services.google.place_api_key'))
<script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google.place_api_key') }}&libraries=places"></script>
@endif
<style>
    .radio-card {
      border: 2px solid #cac4d3;
      border-radius: 16px;
      padding: 14px 16px;
      cursor: pointer;
      transition: all 0.2s;
    }
    .radio-card:hover { border-color: #664db1; }
    .radio-card.selected {
      border-color: #310f7a;
      background: rgba(49, 15, 122, 0.04);
    }
    .radio-card.selected .radio-dot {
      background: #310f7a;
      border-color: #310f7a;
    }
    .radio-card.selected .radio-dot::after {
      content: '';
      display: block;
      width: 8px;
      height: 8px;
      background: white;
      border-radius: 50%;
    }
    .radio-dot {
      width: 20px;
      height: 20px;
      border: 2px solid #7a7583;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
      transition: all 0.2s;
    }

    .guest-spot-row {
      transition: background 0.15s ease, box-shadow 0.15s ease, opacity 0.15s ease;
      border-bottom: 1px solid rgba(202, 196, 211, 0.15);
      cursor: grab;
    }
    .guest-spot-row:last-child { border-bottom: none; }
    .guest-spot-row:hover { background: #f8f1fb; }
    .guest-spot-row.dragging { opacity: 0.45; cursor: grabbing; }
    .guest-spot-row.drag-over-top { box-shadow: 0 -2px 0 0 #310f7a inset; }
    .guest-spot-row.drag-over-bottom { box-shadow: 0 2px 0 0 #310f7a inset; }

    .status-badge {
      font-size: 11px;
      font-weight: 600;
      padding: 3px 10px;
      border-radius: 9999px;
      white-space: nowrap;
    }
    .status-badge.available {
      background: rgba(34, 197, 94, 0.15);
      color: #22C55E;
    }
    .status-badge.planned {
      background: #e8ddff;
      color: #310f7a;
    }
    .status-badge.full {
      background: rgba(255, 191, 0, 0.18);
      color: #FFBF00;
    }
    .status-badge.completed {
      background: rgba(209, 213, 219, 0.45);
      color: #000000;
    }

    .guest-spot-action {
      width: 32px;
      height: 32px;
      border-radius: 8px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      color: #7a7583;
      transition: background 0.15s, color 0.15s;
    }
    .guest-spot-edit:hover {
      background: #e8ddff;
      color: #310f7a;
    }
    .guest-spot-delete:hover {
      background: #fce8e8;
      color: #ba1a1a;
    }

    .guest-spot-row.is-editing {
      background: rgba(49, 15, 122, 0.06);
      box-shadow: inset 3px 0 0 #310f7a;
    }

    .guest-spot-details {
      border-top: 1px dashed rgba(202, 196, 211, 0.35);
      margin-top: 0.35rem;
      padding-top: 0.5rem;
    }
    .guest-spot-detail-item {
      display: inline-flex;
      align-items: flex-start;
      gap: 0.25rem;
      max-width: 100%;
    }
    .guest-spot-detail-item .material-symbols-outlined {
      font-size: 15px;
      line-height: 1.25rem;
      flex-shrink: 0;
      margin-top: 1px;
    }

    .info-tooltip { position: relative; display: inline-flex; cursor: help; vertical-align: middle; }
    .info-tooltip .tooltip-text {
      visibility: hidden;
      opacity: 0;
      position: absolute;
      bottom: calc(100% + 8px);
      left: 50%;
      transform: translateX(-50%);
      background: #322f36;
      color: white;
      padding: 8px 12px;
      border-radius: 8px;
      font-size: 0.75rem;
      font-weight: 500;
      width: 260px;
      text-align: left;
      transition: opacity 0.2s;
      z-index: 20;
      line-height: 1.4;
      pointer-events: none;
    }
    .info-tooltip:hover .tooltip-text,
    .info-tooltip:focus-within .tooltip-text { visibility: visible; opacity: 1; }

    .pac-container { z-index: 10060 !important; }

    .modal-backdrop {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.6);
      z-index: 400;
      align-items: center;
      justify-content: center;
      opacity: 0;
      transition: opacity 0.3s ease;
    }
    .modal-backdrop.modal-visible { display: flex; }
    .modal-backdrop.modal-visible:not(.modal-open) { pointer-events: none; }
    .modal-backdrop.modal-open { opacity: 1; pointer-events: auto; }
    .delete-guest-modal-inner {
      transform: scale(0.96) translateY(10px);
      opacity: 0;
      transition: transform 0.32s cubic-bezier(0.22, 1, 0.36, 1), opacity 0.28s ease;
    }
    .modal-backdrop.modal-open .delete-guest-modal-inner {
      transform: scale(1) translateY(0);
      opacity: 1;
    }

    @media (max-width: 1023px) {
      .main-content { overflow-x: hidden; padding: 16px; padding-top: 70px; }
      body { overflow-x: hidden; }
    }
</style>
@endsection

@section('content')
<main class="main-content flex-1 min-h-screen">
  <div class="p-6 md:p-10 lg:p-12 max-w-6xl">

    @php
      $bookingPageUsername = Auth::user()->userDetail->user_name ?? null;
      $bookingPageUrl = $bookingPageUsername ? 'https://inkjin.com/@'.$bookingPageUsername.'#guest-spots' : null;
    @endphp
    <div class="mb-8">
      <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
        <div>
          <h2 class="text-3xl font-extrabold text-on-surface tracking-tight">Booking Page</h2>
          <p class="text-on-surface-variant mt-1">Manage your intake forms, available designs, portfolio and the style of your page</p>
        </div>
        @if ($bookingPageUrl)
        <a href="{{ $bookingPageUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 text-sm font-semibold text-primary hover:underline bg-primary/5 px-4 py-2 rounded-xl transition-colors shrink-0">
          <span class="material-symbols-outlined text-lg">open_in_new</span> Open your booking page
        </a>
        @endif
      </div>
    </div>

    @include('artist.partials.booking-page-tabs', ['activeTab' => 'guest-spots'])

    <div class="mb-8">
      <p class="text-on-surface-variant">Add upcoming guest locations so clients know where you’ll be tattooing next.</p>
    </div>

    <div id="guestSpotAlert" class="hidden mb-6 max-w-3xl rounded-xl px-4 py-3 text-sm"></div>

    <!-- Add / edit guest location form -->
    <div class="bg-white rounded-2xl p-5 md:p-6 mb-8 border border-outline-variant/20 max-w-3xl" id="guestSpotFormCard">
      <div class="flex items-center gap-3 mb-6">
        <div class="w-9 h-9 rounded-xl bg-primary/10 flex items-center justify-center">
          <span class="material-symbols-outlined text-primary text-lg" id="guestFormIcon">travel_explore</span>
        </div>
        <div>
          <h3 class="font-bold text-on-surface" id="guestFormTitle">Add guest location</h3>
          <p class="text-xs text-on-surface-variant" id="guestFormSubtitle">Tell clients when and where you’ll be guesting.</p>
        </div>
      </div>

      <form id="guestSpotForm">
        @csrf
        <input type="hidden" id="guest_editing_id" value="">
        <input type="hidden" id="guest_status" name="status" value="available">

        <div class="mb-5">
          <label class="block text-sm font-semibold text-on-surface mb-2">Status <span class="text-red-600">*</span></label>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3" id="guestStatusCards">
            <div class="radio-card selected" data-status="available" onclick="selectGuestStatus(this)">
              <div class="flex items-center gap-3">
                <div class="radio-dot"></div>
                <div>
                  <p class="text-sm font-bold text-on-surface">Available</p>
                  <p class="text-xs text-on-surface-variant mt-0.5">Open for bookings at this location</p>
                </div>
              </div>
            </div>
            <div class="radio-card" data-status="planned" onclick="selectGuestStatus(this)">
              <div class="flex items-center gap-3">
                <div class="radio-dot"></div>
                <div>
                  <p class="text-sm font-bold text-on-surface">Planned</p>
                  <p class="text-xs text-on-surface-variant mt-0.5">Coming soon — not bookable yet</p>
                </div>
              </div>
            </div>
          </div>
          <p id="status_error" class="text-error text-xs mt-1 hidden"></p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-5">
          <div>
            <label for="guest_city" class="block text-sm font-semibold text-on-surface mb-2">City <span class="text-red-600">*</span></label>
            <input type="text" id="guest_city" name="city" maxlength="255" placeholder="e.g. Athens" class="w-full text-sm border border-outline-variant/30 rounded-xl px-4 py-3 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
            <p id="city_error" class="text-error text-xs mt-1 hidden"></p>
          </div>
          <div>
            <label for="guest_country" class="block text-sm font-semibold text-on-surface mb-2">Country <span class="text-red-600">*</span></label>
            <input type="text" id="guest_country" name="country" maxlength="255" placeholder="e.g. Greece" class="w-full text-sm border border-outline-variant/30 rounded-xl px-4 py-3 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
            <p id="country_error" class="text-error text-xs mt-1 hidden"></p>
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-5">
          <div>
            <label for="guest_from" class="block text-sm font-semibold text-on-surface mb-2">From <span class="text-red-600">*</span></label>
            <input type="date" id="guest_from" name="from_date" class="w-full text-sm border border-outline-variant/30 rounded-xl px-4 py-3 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
            <p id="from_date_error" class="text-error text-xs mt-1 hidden"></p>
          </div>
          <div>
            <label for="guest_to" class="block text-sm font-semibold text-on-surface mb-2">To <span class="text-red-600">*</span></label>
            <input type="date" id="guest_to" name="to_date" class="w-full text-sm border border-outline-variant/30 rounded-xl px-4 py-3 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
            <p id="to_date_error" class="text-error text-xs mt-1 hidden"></p>
          </div>
        </div>

        {{-- Available-only fields --}}
        <div id="guestAvailableFields" class="space-y-5 mb-6">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label for="guest_start_time" class="block text-sm font-semibold text-on-surface mb-2">Starting time <span class="text-red-600">*</span></label>
              <div class="inkjin-time-wrap">
                <input type="time" id="guest_start_time" name="start_time" class="inkjin-time-input w-full text-sm px-4 py-3 bg-white text-on-surface">
              </div>
              <p id="start_time_error" class="text-error text-xs mt-1 hidden"></p>
            </div>
            <div>
              <label for="guest_end_time" class="block text-sm font-semibold text-on-surface mb-2">Ending time <span class="text-red-600">*</span></label>
              <div class="inkjin-time-wrap">
                <input type="time" id="guest_end_time" name="end_time" class="inkjin-time-input w-full text-sm px-4 py-3 bg-white text-on-surface">
              </div>
              <p id="end_time_error" class="text-error text-xs mt-1 hidden"></p>
            </div>
          </div>
          <p class="text-xs text-on-surface-variant -mt-2 leading-relaxed">Daily hours you're available for bookings during this guest spot.</p>

          <div class="rounded-2xl border border-outline-variant/20 bg-surface-container-low/60 p-4 sm:p-5 space-y-5">
            <div>
              <h4 class="text-sm font-bold text-on-surface">Studio Information</h4>
              <p class="text-xs text-on-surface-variant mt-0.5">Where clients will find you for this guest spot.</p>
            </div>

            <div>
              <label for="guest_studio_name" class="block text-sm font-semibold text-on-surface mb-2">Studio Name <span class="text-red-600">*</span></label>
              <input type="text" id="guest_studio_name" name="guest_studio_name" maxlength="255" placeholder="e.g., Ink & Soul Tattoo Studio" class="w-full text-sm border border-outline-variant/30 rounded-xl px-4 py-3 bg-white text-on-surface placeholder:text-outline focus:outline-none focus:ring-2 focus:ring-primary/30">
              <p id="guest_studio_name_error" class="text-error text-xs mt-1 hidden"></p>
            </div>

            <div>
              <label for="guest_address_search" class="block text-sm font-semibold text-on-surface mb-2">Find Your Address <span class="text-red-600">*</span></label>
              <div class="relative" id="guestAddressSearchWrapper">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-lg">location_on</span>
                <input type="text" id="guest_address_search" autocomplete="off" placeholder="Start typing your studio address..." class="w-full text-sm border border-outline-variant/30 rounded-xl pl-10 pr-4 py-3 bg-white text-on-surface placeholder:text-outline focus:outline-none focus:ring-2 focus:ring-primary/30">
              </div>
              <p class="text-on-surface-variant text-xs mt-1.5">Start typing and select from Google suggestions to auto-fill address fields.</p>
              <p id="guest_studio_address_error" class="text-error text-xs mt-1 hidden"></p>
            </div>
            <input type="hidden" name="guest_studio_address" id="guest_studio_address" value="">
            <input type="hidden" name="guest_latitude" id="guest_latitude" value="">
            <input type="hidden" name="guest_longitude" id="guest_longitude" value="">

            <div class="grid grid-cols-1 sm:grid-cols-12 gap-4">
              <div class="sm:col-span-4">
                <label for="guest_street_number" class="block text-sm font-semibold text-on-surface mb-2">Street Number <span class="text-red-600">*</span></label>
                <input type="text" id="guest_street_number" name="guest_street_number" placeholder="e.g. 42" class="w-full text-sm border border-outline-variant/30 rounded-xl px-4 py-3 bg-white text-on-surface placeholder:text-outline focus:outline-none focus:ring-2 focus:ring-primary/30">
                <p id="guest_street_number_error" class="text-error text-xs mt-1 hidden"></p>
              </div>
              <div class="sm:col-span-8">
                <label for="guest_street_name" class="block text-sm font-semibold text-on-surface mb-2">Street Name <span class="text-red-600">*</span></label>
                <input type="text" id="guest_street_name" name="guest_street_name" placeholder="e.g. Main Street" class="w-full text-sm border border-outline-variant/30 rounded-xl px-4 py-3 bg-white text-on-surface placeholder:text-outline focus:outline-none focus:ring-2 focus:ring-primary/30">
                <p id="guest_street_name_error" class="text-error text-xs mt-1 hidden"></p>
              </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label for="guest_studio_city" class="block text-sm font-semibold text-on-surface mb-2">City <span class="text-red-600">*</span></label>
                <input type="text" id="guest_studio_city" name="guest_studio_city" class="w-full text-sm border border-outline-variant/30 rounded-xl px-4 py-3 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
                <p id="guest_studio_city_error" class="text-error text-xs mt-1 hidden"></p>
              </div>
              <div>
                <label for="guest_studio_state" class="block text-sm font-semibold text-on-surface mb-2">State / Province <span class="text-red-600">*</span></label>
                <input type="text" id="guest_studio_state" name="guest_studio_state" class="w-full text-sm border border-outline-variant/30 rounded-xl px-4 py-3 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
                <p id="guest_studio_state_error" class="text-error text-xs mt-1 hidden"></p>
              </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label for="guest_postal_code" class="block text-sm font-semibold text-on-surface mb-2">Postal / Zip Code <span class="text-red-600">*</span></label>
                <input type="text" id="guest_postal_code" name="guest_postal_code" class="w-full text-sm border border-outline-variant/30 rounded-xl px-4 py-3 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
                <p id="guest_postal_code_error" class="text-error text-xs mt-1 hidden"></p>
              </div>
              <div>
                <label for="guest_studio_country" class="block text-sm font-semibold text-on-surface mb-2">Country <span class="text-red-600">*</span></label>
                <input type="text" id="guest_studio_country" name="guest_studio_country" class="w-full text-sm border border-outline-variant/30 rounded-xl px-4 py-3 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
                <p id="guest_studio_country_error" class="text-error text-xs mt-1 hidden"></p>
              </div>
            </div>

            <div>
              <label for="guest_google_maps_link" class="block text-sm font-semibold text-on-surface mb-2">Google Maps Link</label>
              <div class="relative">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-lg">location_on</span>
                <input type="url" id="guest_google_maps_link" name="guest_google_maps_link" placeholder="Paste your Google Maps link" class="w-full text-sm border border-outline-variant/30 rounded-xl pl-10 pr-4 py-3 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
              </div>
              <p class="text-on-surface-variant text-xs mt-1.5">Paste the Google Maps link to your studio so clients can find you easily.</p>
              <p id="guest_google_maps_link_error" class="text-error text-xs mt-1 hidden"></p>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-start">
            <div>
              <label class="block text-sm font-semibold text-on-surface mb-2">
                Buffer Days
                <span class="info-tooltip ml-1" tabindex="0" aria-label="Buffer days help">
                  <span class="material-symbols-outlined text-[16px] text-outline align-middle">info</span>
                  <span class="tooltip-text">Block your home-studio calendar for this many days before and after the guest spot dates, to account for travel.</span>
                </span>
              </label>
              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label for="guest_buffer_days_before" class="block text-[11px] font-semibold uppercase tracking-wide text-on-surface-variant mb-1.5">Before</label>
                  <div class="relative">
                    <input
                      type="number"
                      id="guest_buffer_days_before"
                      name="buffer_days_before"
                      min="0"
                      step="1"
                      value=""
                      placeholder="0"
                      class="w-full text-sm border border-outline-variant/30 rounded-xl px-4 py-3 pr-14 bg-white text-on-surface placeholder:text-outline/50 focus:outline-none focus:ring-2 focus:ring-primary/30"
                    >
                    <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-xs font-semibold text-on-surface-variant">days</span>
                  </div>
                  <p id="buffer_days_before_error" class="text-error text-xs mt-1 hidden"></p>
                </div>
                <div>
                  <label for="guest_buffer_days_after" class="block text-[11px] font-semibold uppercase tracking-wide text-on-surface-variant mb-1.5">After</label>
                  <div class="relative">
                    <input
                      type="number"
                      id="guest_buffer_days_after"
                      name="buffer_days_after"
                      min="0"
                      step="1"
                      value=""
                      placeholder="0"
                      class="w-full text-sm border border-outline-variant/30 rounded-xl px-4 py-3 pr-14 bg-white text-on-surface placeholder:text-outline/50 focus:outline-none focus:ring-2 focus:ring-primary/30"
                    >
                    <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-xs font-semibold text-on-surface-variant">days</span>
                  </div>
                  <p id="buffer_days_after_error" class="text-error text-xs mt-1 hidden"></p>
                </div>
              </div>
            </div>

            <div>
              <label for="guest_number_of_spots" class="block text-sm font-semibold text-on-surface mb-2">
                Number of Spots
                <span class="info-tooltip ml-1" tabindex="0" aria-label="Number of spots help">
                  <span class="material-symbols-outlined text-[16px] text-outline align-middle">info</span>
                  <span class="tooltip-text">Leave at 0 for unlimited spots.</span>
                </span>
              </label>
              <div class="relative sm:mt-[1.625rem]">
                <input
                  type="number"
                  id="guest_number_of_spots"
                  name="number_of_spots"
                  min="0"
                  step="1"
                  value=""
                  placeholder="0"
                  class="w-full text-sm border border-outline-variant/30 rounded-xl px-4 py-3 pr-14 bg-white text-on-surface placeholder:text-outline/50 focus:outline-none focus:ring-2 focus:ring-primary/30"
                >
                <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-xs font-semibold text-on-surface-variant">spots</span>
              </div>
              <p class="text-xs text-on-surface-variant mt-1.5 leading-relaxed">Once all spots are filled, new requests will be paused until you free one up.</p>
              <p id="number_of_spots_error" class="text-error text-xs mt-1 hidden"></p>
            </div>
          </div>

          <div>
            <label for="guest_response_deadline" class="block text-sm font-semibold text-on-surface mb-1">Response Deadline <span class="text-red-600">*</span></label>
            <p class="text-xs text-on-surface-variant mb-2 leading-relaxed">How long a client has to confirm a proposed time slot before it's released back to available spots.</p>
            <div class="flex gap-2 max-w-md">
              <input
                type="number"
                id="guest_response_deadline"
                name="response_deadline"
                min="1"
                step="1"
                placeholder="e.g. 48"
                class="flex-1 min-w-0 text-sm border border-outline-variant/30 rounded-xl px-4 py-3 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30"
              >
              <select
                id="guest_response_deadline_unit"
                name="response_deadline_unit"
                class="w-[7.5rem] shrink-0 text-sm border border-outline-variant/30 rounded-xl px-3 py-3 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30"
              >
                <option value="hours" selected>Hours</option>
                <option value="days">Days</option>
              </select>
            </div>
            <p id="response_deadline_error" class="text-error text-xs mt-1 hidden"></p>
          </div>
        </div>

        <div class="flex items-center justify-end gap-3">
          <button type="button" id="btnCancelGuestSpotEdit" class="hidden text-sm font-semibold text-on-surface-variant hover:text-on-surface px-4 py-3 rounded-xl transition-colors">
            Cancel
          </button>
          <button type="submit" id="btnAddGuestSpot" class="inline-flex items-center gap-2 bg-gradient-to-br from-primary to-primary-container text-white font-bold py-3 px-6 rounded-xl shadow-lg shadow-primary/20 hover:opacity-90 transition-all active:scale-[0.98]">
            <span class="material-symbols-outlined text-lg btn-icon">add</span>
            <span class="btn-label">Add location</span>
          </button>
        </div>
      </form>
    </div>

    <!-- Guest locations list -->
    <div class="bg-white rounded-2xl shadow-sm border border-outline-variant/20 overflow-hidden max-w-3xl">
      <div class="px-6 py-4 border-b border-outline-variant/15 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center">
            <span class="material-symbols-outlined text-primary" style="font-size:18px;">reorder</span>
          </div>
          <div>
            <h3 class="font-bold text-on-surface text-sm">Guest locations</h3>
            <p class="text-[11px] text-on-surface-variant">Drag to reorder how they appear on your page</p>
          </div>
        </div>
        <span id="guestSpotCount" class="text-xs font-semibold text-on-surface-variant bg-surface-container-high px-2.5 py-1 rounded-lg">{{ $guestSpots->count() }}</span>
      </div>

      <div id="guestSpotsEmpty" class="{{ $guestSpots->isEmpty() ? '' : 'hidden' }} px-6 py-12 text-center">
        <span class="material-symbols-outlined text-4xl text-outline/40 mb-3 block">flight_takeoff</span>
        <p class="font-semibold text-sm text-on-surface">No guest locations yet</p>
        <p class="text-xs text-on-surface-variant mt-1">Add your first guest spot using the form above.</p>
      </div>

      <div id="guestSpotsListWrap" class="{{ $guestSpots->isEmpty() ? 'hidden' : '' }}">
        <div class="hidden sm:grid grid-cols-[28px_100px_1fr_1fr_110px_110px_72px] gap-3 px-6 py-2.5 bg-surface-container-low text-[11px] font-semibold uppercase tracking-wide text-on-surface-variant border-b border-outline-variant/10">
          <span></span>
          <span>Status</span>
          <span>City</span>
          <span>Country</span>
          <span>From</span>
          <span>To</span>
          <span></span>
        </div>

        <div id="guestSpotsList">
          @foreach ($guestSpots as $spot)
            <div
              class="guest-spot-row grid grid-cols-1 sm:grid-cols-[28px_100px_1fr_1fr_110px_110px_72px] gap-2 sm:gap-3 items-center px-6 py-3.5"
              draggable="true"
              data-id="{{ $spot->id }}"
              data-status="{{ $spot->status }}"
              data-city="{{ $spot->city }}"
              data-country="{{ $spot->country }}"
              data-from="{{ $spot->from_date->format('Y-m-d') }}"
              data-to="{{ $spot->to_date->format('Y-m-d') }}"
              data-spot='@json($spot->toFormArray())'
              data-update-url="{{ route('guest-spots.update', $spot) }}"
              data-delete-url="{{ route('guest-spots.destroy', $spot) }}"
            >
              <span class="material-symbols-outlined text-outline/50 text-xl hidden sm:inline select-none" style="font-size:20px;">drag_indicator</span>
              <div class="flex items-center gap-2 sm:block">
                <span class="sm:hidden text-[11px] font-semibold uppercase text-on-surface-variant w-16 shrink-0">Status</span>
              <span class="status-badge {{ $spot->effectiveStatusKey() }}">{{ $spot->publicStatusLabel() }}</span>
              </div>
              <div class="flex items-center gap-2 sm:block">
                <span class="sm:hidden text-[11px] font-semibold uppercase text-on-surface-variant w-16 shrink-0">City</span>
                <span class="text-sm font-semibold text-on-surface guest-spot-city">{{ $spot->city }}</span>
              </div>
              <div class="flex items-center gap-2 sm:block">
                <span class="sm:hidden text-[11px] font-semibold uppercase text-on-surface-variant w-16 shrink-0">Country</span>
                <span class="text-sm text-on-surface-variant guest-spot-country">{{ $spot->country }}</span>
              </div>
              <div class="flex items-center gap-2 sm:block">
                <span class="sm:hidden text-[11px] font-semibold uppercase text-on-surface-variant w-16 shrink-0">From</span>
                <span class="text-sm text-on-surface guest-spot-from">{{ $spot->from_date->format('M j, Y') }}</span>
              </div>
              <div class="flex items-center gap-2 sm:block">
                <span class="sm:hidden text-[11px] font-semibold uppercase text-on-surface-variant w-16 shrink-0">To</span>
                <span class="text-sm text-on-surface guest-spot-to">{{ $spot->to_date->format('M j, Y') }}</span>
              </div>
              <div class="flex items-center justify-end gap-0.5 guest-spot-actions">
                @if ($spot->effectiveStatusKey() !== 'completed')
                <button type="button" class="guest-spot-action guest-spot-edit" title="Edit" aria-label="Edit guest location">
                  <span class="material-symbols-outlined text-[18px]">edit</span>
                </button>
                <button type="button" class="guest-spot-action guest-spot-delete" title="Remove" aria-label="Remove guest location">
                  <span class="material-symbols-outlined text-[18px]">delete</span>
                </button>
                @endif
              </div>
              @if ($spot->hasListDetails())
              <div class="guest-spot-details sm:col-span-7 flex flex-col sm:flex-row sm:flex-wrap sm:items-center gap-2 sm:gap-x-5 sm:gap-y-1.5 sm:pl-10 pt-2 sm:pt-2.5 mt-1 sm:mt-0">
                @if ($spot->listAvailabilityTimeLabel())
                <span class="guest-spot-detail-item text-xs text-on-surface-variant">
                  <span class="material-symbols-outlined text-outline/70">schedule</span>
                  <span class="guest-spot-hours">{{ $spot->listAvailabilityTimeLabel() }}</span>
                </span>
                @endif
                @if ($spot->listStudioLabel())
                <span class="guest-spot-detail-item text-xs text-on-surface">
                  <span class="material-symbols-outlined text-outline/70">store</span>
                  <span class="guest-spot-studio font-semibold">{{ $spot->listStudioLabel() }}</span>
                </span>
                @endif
                @if ($spot->listLocationLabel())
                <span class="guest-spot-detail-item text-xs text-on-surface-variant">
                  <span class="material-symbols-outlined text-outline/70">location_on</span>
                  <span class="guest-spot-location">{{ $spot->listLocationLabel() }}</span>
                </span>
                @endif
                @if ($spot->listBufferLabel())
                <span class="guest-spot-detail-item text-xs text-on-surface-variant">
                  <span class="material-symbols-outlined text-outline/70">event_busy</span>
                  <span class="guest-spot-buffer">{{ $spot->listBufferLabel() }}</span>
                </span>
                @endif
                @if ($spot->listRemainingSpotsLabel())
                <span class="guest-spot-detail-item text-xs text-on-surface-variant">
                  <span class="material-symbols-outlined text-outline/70">group</span>
                  <span class="guest-spot-remaining">{{ $spot->listRemainingSpotsLabel() }}</span>
                </span>
                @endif
              </div>
              @else
              <div class="guest-spot-details hidden sm:col-span-7"></div>
              @endif
            </div>
          @endforeach
        </div>
      </div>
    </div>

  </div>
</main>

  <!-- Delete guest location confirmation -->
  <div class="modal-backdrop" id="deleteGuestSpotModal" aria-hidden="true">
    <div class="delete-guest-modal-inner bg-white rounded-2xl w-full max-w-md mx-4 shadow-2xl overflow-hidden">
      <div class="p-6">
        <div class="flex items-start gap-4">
          <div class="w-12 h-12 rounded-2xl bg-error-container flex items-center justify-center flex-shrink-0">
            <span class="material-symbols-outlined text-error text-2xl">delete_forever</span>
          </div>
          <div class="min-w-0 flex-1">
            <h3 class="text-lg font-bold text-on-surface tracking-tight">Delete this guest location?</h3>
            <p class="text-sm text-on-surface-variant mt-2 leading-relaxed">
              This will permanently remove <span id="deleteGuestSpotLabel" class="font-semibold text-on-surface"></span> from your list. You cannot undo this.
            </p>
            <p id="deleteGuestSpotError" class="hidden mt-3 text-xs text-error font-semibold leading-snug"></p>
          </div>
        </div>
      </div>
      <div class="px-6 py-4 border-t border-outline-variant/15 flex items-center justify-end gap-3 bg-surface-container-low/30">
        <button type="button" id="btnDeleteGuestSpotCancel" class="text-sm font-semibold text-on-surface-variant hover:text-on-surface px-4 py-2.5 rounded-xl transition-colors">Cancel</button>
        <button type="button" id="btnDeleteGuestSpotConfirm" class="bg-error text-on-error px-5 py-2.5 rounded-xl font-semibold text-sm hover:opacity-95 transition-opacity shadow-sm flex items-center gap-2">
          <span class="material-symbols-outlined text-lg confirm-delete-icon">delete</span>
          <span class="confirm-delete-label">Delete</span>
        </button>
      </div>
    </div>
  </div>
@endsection

@section('scripts')
<script>
function selectGuestStatus(card) {
  $('#guestStatusCards .radio-card').removeClass('selected');
  $(card).addClass('selected');
  $('#guest_status').val($(card).data('status') || 'available');
  $('#status_error').addClass('hidden').text('');
  if (typeof window.syncGuestAvailableFields === 'function') {
    window.syncGuestAvailableFields();
  }
}

(function () {
  var storeUrl = @json(route('guest-spots.store'));
  var reorderUrl = @json(route('guest-spots.reorder'));
  var csrf = $('meta[name="csrf-token"]').attr('content');
  var $list = $('#guestSpotsList');
  var $listWrap = $('#guestSpotsListWrap');
  var $empty = $('#guestSpotsEmpty');
  var $count = $('#guestSpotCount');
  var $alert = $('#guestSpotAlert');
  var $formCard = $('#guestSpotFormCard');
  var $availableFields = $('#guestAvailableFields');
  var dragSrc = null;
  var reorderTimer = null;

  function showAlert(message, type) {
    var ok = type === 'success';
    $alert
      .removeClass('hidden border-emerald-200 bg-emerald-50 text-emerald-800 border-error/30 bg-error/10 text-error')
      .addClass(ok
        ? 'border border-emerald-200 bg-emerald-50 text-emerald-800'
        : 'border border-error/30 bg-error/10 text-error')
      .text(message);
    clearTimeout($alert.data('timer'));
    $alert.data('timer', setTimeout(function () {
      $alert.addClass('hidden').text('');
    }, 4000));
  }

  function clearFieldErrors() {
    [
      'status', 'city', 'country', 'from_date', 'to_date', 'start_time', 'end_time', 'response_deadline', 'buffer_days_before', 'buffer_days_after', 'number_of_spots',
      'guest_studio_name', 'guest_studio_address', 'guest_street_number', 'guest_street_name',
      'guest_studio_city', 'guest_studio_state', 'guest_postal_code', 'guest_studio_country', 'guest_google_maps_link',
    ].forEach(function (field) {
      $('#' + field + '_error').addClass('hidden').text('');
    });
    $('#guestSpotForm input, #guestSpotForm select').removeClass('border-error');
    $('#guestSpotForm .inkjin-time-wrap').removeClass('border-error');
  }

  var fieldErrorMap = {
    status: { error: '#status_error', input: '#guest_status' },
    city: { error: '#city_error', input: '#guest_city' },
    country: { error: '#country_error', input: '#guest_country' },
    from_date: { error: '#from_date_error', input: '#guest_from' },
    to_date: { error: '#to_date_error', input: '#guest_to' },
    start_time: { error: '#start_time_error', input: '#guest_start_time' },
    end_time: { error: '#end_time_error', input: '#guest_end_time' },
    response_deadline: { error: '#response_deadline_error', input: '#guest_response_deadline' },
    response_deadline_unit: { error: '#response_deadline_error', input: '#guest_response_deadline_unit' },
    buffer_days_before: { error: '#buffer_days_before_error', input: '#guest_buffer_days_before' },
    buffer_days_after: { error: '#buffer_days_after_error', input: '#guest_buffer_days_after' },
    number_of_spots: { error: '#number_of_spots_error', input: '#guest_number_of_spots' },
    guest_studio_name: { error: '#guest_studio_name_error', input: '#guest_studio_name' },
    studio_name: { error: '#guest_studio_name_error', input: '#guest_studio_name' },
    guest_studio_address: { error: '#guest_studio_address_error', input: '#guest_address_search' },
    studio_address: { error: '#guest_studio_address_error', input: '#guest_address_search' },
    guest_address_search: { error: '#guest_studio_address_error', input: '#guest_address_search' },
    guest_street_number: { error: '#guest_street_number_error', input: '#guest_street_number' },
    street_number: { error: '#guest_street_number_error', input: '#guest_street_number' },
    guest_street_name: { error: '#guest_street_name_error', input: '#guest_street_name' },
    street_name: { error: '#guest_street_name_error', input: '#guest_street_name' },
    guest_studio_city: { error: '#guest_studio_city_error', input: '#guest_studio_city' },
    guest_studio_state: { error: '#guest_studio_state_error', input: '#guest_studio_state' },
    state: { error: '#guest_studio_state_error', input: '#guest_studio_state' },
    guest_postal_code: { error: '#guest_postal_code_error', input: '#guest_postal_code' },
    postal_code: { error: '#guest_postal_code_error', input: '#guest_postal_code' },
    guest_studio_country: { error: '#guest_studio_country_error', input: '#guest_studio_country' },
    guest_google_maps_link: { error: '#guest_google_maps_link_error', input: '#guest_google_maps_link' },
    google_maps_link: { error: '#guest_google_maps_link_error', input: '#guest_google_maps_link' },
  };

  var fieldErrorOrder = [
    'status', 'city', 'country', 'from_date', 'to_date', 'start_time', 'end_time',
    'guest_studio_name', 'studio_name',
    'guest_studio_address', 'studio_address', 'guest_address_search',
    'guest_street_number', 'street_number', 'guest_street_name', 'street_name',
    'guest_studio_city', 'guest_studio_state', 'state', 'guest_postal_code', 'postal_code', 'guest_studio_country',
    'guest_google_maps_link', 'google_maps_link',
    'buffer_days_before', 'buffer_days_after', 'number_of_spots',
    'response_deadline', 'response_deadline_unit',
  ];

  function setFieldErrors(errors) {
    clearFieldErrors();
    var firstInput = null;

    fieldErrorOrder.forEach(function (field) {
      if (!errors || !errors[field]) return;
      var msg = (errors[field] && errors[field][0]) || '';
      if (!msg) return;

      var map = fieldErrorMap[field] || {
        error: '#' + field + '_error',
        input: '#' + field,
      };
      var $err = $(map.error);
      var $input = $(map.input);

      if ($err.length) {
        $err.removeClass('hidden').text(msg);
      }
      if ($input.length) {
        var $timeWrap = $input.closest('.inkjin-time-wrap');
        if ($timeWrap.length) {
          $timeWrap.addClass('border-error');
        } else {
          $input.addClass('border-error');
        }
        if (!firstInput) firstInput = $input;
      }
    });

    // Any remaining unmapped keys
    Object.keys(errors || {}).forEach(function (field) {
      if (fieldErrorOrder.indexOf(field) !== -1) return;
      var msg = (errors[field] && errors[field][0]) || '';
      if (!msg) return;
      var $err = $('#' + field + '_error');
      var $input = $('#' + field).length ? $('#' + field) : $('#guest_' + field);
      if ($err.length) $err.removeClass('hidden').text(msg);
      if ($input.length) {
        var $timeWrap = $input.closest('.inkjin-time-wrap');
        if ($timeWrap.length) {
          $timeWrap.addClass('border-error');
        } else {
          $input.addClass('border-error');
        }
        if (!firstInput) firstInput = $input;
      }
    });

    scrollToFirstError(firstInput);
  }

  function scrollToFirstError($input) {
    var $target = $input && $input.length
      ? $input
      : $('#guestSpotForm [id$="_error"]').filter(function () {
          return !$(this).hasClass('hidden') && $.trim($(this).text()) !== '';
        }).first();

    if (!$target || !$target.length) return;

    var top = $target.offset().top - 100;
    $('html, body').stop(true).animate({ scrollTop: Math.max(0, top) }, 450, 'swing', function () {
      if ($input && $input.length && $input.is('input, select, textarea')) {
        try { $input.trigger('focus'); } catch (e) {}
      }
    });
  }

  function syncGuestAvailableFields() {
    var isAvailable = ($('#guest_status').val() || 'available') === 'available';
    $availableFields.toggleClass('hidden', !isAvailable);
    if (!isAvailable) {
      $('#response_deadline_error, #start_time_error, #end_time_error, #buffer_days_before_error, #buffer_days_after_error, #number_of_spots_error, #guest_studio_name_error, #guest_studio_address_error, #guest_street_number_error, #guest_street_name_error, #guest_studio_city_error, #guest_studio_state_error, #guest_postal_code_error, #guest_studio_country_error, #guest_google_maps_link_error').addClass('hidden').text('');
    }
  }
  window.syncGuestAvailableFields = syncGuestAvailableFields;

  function refreshListVisibility() {
    var n = $list.find('.guest-spot-row').length;
    $count.text(n);
    if (n === 0) {
      $listWrap.addClass('hidden');
      $empty.removeClass('hidden');
    } else {
      $empty.addClass('hidden');
      $listWrap.removeClass('hidden');
    }
  }

  function resourceUrl(id) {
    return storeUrl.replace(/\/?$/, '') + '/' + id;
  }

  function setStatusCards(status) {
    $('#guestStatusCards .radio-card').removeClass('selected');
    $('#guestStatusCards .radio-card[data-status="' + status + '"]').addClass('selected');
    $('#guest_status').val(status || 'available');
    syncGuestAvailableFields();
  }

  function setFormMode(mode) {
    var editing = mode === 'edit';
    $('#guestFormTitle').text(editing ? 'Edit guest location' : 'Add guest location');
    $('#guestFormSubtitle').text(editing ? 'Update the details for this guest spot.' : 'Tell clients when and where you’ll be guesting.');
    $('#guestFormIcon').text(editing ? 'edit_location' : 'travel_explore');
    $('#btnCancelGuestSpotEdit').toggleClass('hidden', !editing);
    $('#btnAddGuestSpot .btn-icon').text(editing ? 'save' : 'add');
    $('#btnAddGuestSpot .btn-label').text(editing ? 'Save changes' : 'Add location');
  }

  function fillRow($row, spot) {
    var displayKey = spot.display_status || spot.status || '';
    var statusLabel = spot.display_status_label
      || (spot.status ? spot.status.charAt(0).toUpperCase() + spot.status.slice(1) : '');
    $row.attr({
      'data-id': spot.id,
      'data-status': spot.status,
      'data-city': spot.city,
      'data-country': spot.country,
      'data-from': spot.from_date,
      'data-to': spot.to_date,
      'data-spot': JSON.stringify(spot),
      'data-update-url': resourceUrl(spot.id),
      'data-delete-url': resourceUrl(spot.id),
    });
    $row.find('.status-badge')
      .removeClass('available planned full completed')
      .addClass(displayKey)
      .text(statusLabel);
    $row.find('.guest-spot-city').text(spot.city);
    $row.find('.guest-spot-country').text(spot.country);
    $row.find('.guest-spot-from').text(spot.from_label);
    $row.find('.guest-spot-to').text(spot.to_label);
    syncRowActions($row, displayKey);
    fillRowDetails($row, spot);
  }

  function syncRowActions($row, displayKey) {
    var $actions = $row.find('.guest-spot-actions');
    if (!$actions.length) return;
    if (displayKey === 'completed') {
      $actions.empty();
      return;
    }
    if ($actions.find('.guest-spot-edit').length) return;
    $actions.html(
      '<button type="button" class="guest-spot-action guest-spot-edit" title="Edit" aria-label="Edit guest location">' +
        '<span class="material-symbols-outlined text-[18px]">edit</span>' +
      '</button>' +
      '<button type="button" class="guest-spot-action guest-spot-delete" title="Remove" aria-label="Remove guest location">' +
        '<span class="material-symbols-outlined text-[18px]">delete</span>' +
      '</button>'
    );
  }

  function detailItemHtml(icon, text, extraClass) {
    return (
      '<span class="guest-spot-detail-item text-xs ' + (extraClass || 'text-on-surface-variant') + '">' +
        '<span class="material-symbols-outlined text-outline/70">' + icon + '</span>' +
        '<span>' + $('<div>').text(text).html() + '</span>' +
      '</span>'
    );
  }

  function fillRowDetails($row, spot) {
    var $details = $row.find('.guest-spot-details');
    var canShowDetails = spot && (spot.status === 'available' || spot.status === 'completed' || spot.display_status === 'completed');
    if (!canShowDetails) {
      $details.addClass('hidden').empty();
      return;
    }

    var html = [];
    if (spot.list_availability_time) {
      html.push(detailItemHtml('schedule', spot.list_availability_time, 'guest-spot-hours'));
    }
    if (spot.list_studio) {
      html.push(detailItemHtml('store', spot.list_studio, 'text-on-surface guest-spot-studio font-semibold'));
    }
    if (spot.list_location) {
      html.push(detailItemHtml('location_on', spot.list_location, 'guest-spot-location'));
    }
    if (spot.list_buffer) {
      html.push(detailItemHtml('event_busy', spot.list_buffer, 'guest-spot-buffer'));
    }
    if (spot.status === 'available' && spot.list_remaining_spots) {
      html.push(detailItemHtml('group', spot.list_remaining_spots, 'guest-spot-remaining'));
    }

    if (!html.length) {
      $details.addClass('hidden').empty();
      return;
    }

    $details
      .removeClass('hidden')
      .addClass('flex flex-col sm:flex-row sm:flex-wrap sm:items-center gap-2 sm:gap-x-5 sm:gap-y-1.5 sm:pl-10 pt-2 sm:pt-2.5 mt-1 sm:mt-0 sm:col-span-7 guest-spot-details')
      .html(html.join(''));
  }

  function buildRow(spot) {
    var displayKey = spot.display_status || spot.status || '';
    var actionsHtml = displayKey === 'completed'
      ? ''
      : (
        '<button type="button" class="guest-spot-action guest-spot-edit" title="Edit" aria-label="Edit guest location">' +
          '<span class="material-symbols-outlined text-[18px]">edit</span>' +
        '</button>' +
        '<button type="button" class="guest-spot-action guest-spot-delete" title="Remove" aria-label="Remove guest location">' +
          '<span class="material-symbols-outlined text-[18px]">delete</span>' +
        '</button>'
      );
    var $row = $(
      '<div class="guest-spot-row grid grid-cols-1 sm:grid-cols-[28px_100px_1fr_1fr_110px_110px_72px] gap-2 sm:gap-3 items-center px-6 py-3.5" draggable="true">' +
        '<span class="material-symbols-outlined text-outline/50 text-xl hidden sm:inline select-none" style="font-size:20px;">drag_indicator</span>' +
        '<div class="flex items-center gap-2 sm:block">' +
          '<span class="sm:hidden text-[11px] font-semibold uppercase text-on-surface-variant w-16 shrink-0">Status</span>' +
          '<span class="status-badge"></span>' +
        '</div>' +
        '<div class="flex items-center gap-2 sm:block">' +
          '<span class="sm:hidden text-[11px] font-semibold uppercase text-on-surface-variant w-16 shrink-0">City</span>' +
          '<span class="text-sm font-semibold text-on-surface guest-spot-city"></span>' +
        '</div>' +
        '<div class="flex items-center gap-2 sm:block">' +
          '<span class="sm:hidden text-[11px] font-semibold uppercase text-on-surface-variant w-16 shrink-0">Country</span>' +
          '<span class="text-sm text-on-surface-variant guest-spot-country"></span>' +
        '</div>' +
        '<div class="flex items-center gap-2 sm:block">' +
          '<span class="sm:hidden text-[11px] font-semibold uppercase text-on-surface-variant w-16 shrink-0">From</span>' +
          '<span class="text-sm text-on-surface guest-spot-from"></span>' +
        '</div>' +
        '<div class="flex items-center gap-2 sm:block">' +
          '<span class="sm:hidden text-[11px] font-semibold uppercase text-on-surface-variant w-16 shrink-0">To</span>' +
          '<span class="text-sm text-on-surface guest-spot-to"></span>' +
        '</div>' +
        '<div class="flex items-center justify-end gap-0.5 guest-spot-actions">' + actionsHtml + '</div>' +
        '<div class="guest-spot-details hidden sm:col-span-7"></div>' +
      '</div>'
    );
    fillRow($row, spot);
    return $row;
  }

  function populateAvailableFields(spot) {
    if (!spot) return;
    $('#guest_response_deadline').val(spot.response_deadline != null ? spot.response_deadline : '');
    $('#guest_response_deadline_unit').val(spot.response_deadline_unit || 'hours');
    $('#guest_start_time').val(spot.start_time || '');
    $('#guest_end_time').val(spot.end_time || '');
    $('#guest_buffer_days_before').val(
      spot.buffer_days_before != null && Number(spot.buffer_days_before) > 0
        ? spot.buffer_days_before
        : ''
    );
    $('#guest_buffer_days_after').val(
      spot.buffer_days_after != null && Number(spot.buffer_days_after) > 0
        ? spot.buffer_days_after
        : ''
    );
    $('#guest_number_of_spots').val(
      spot.number_of_spots != null && Number(spot.number_of_spots) > 0
        ? spot.number_of_spots
        : ''
    );
    $('#guest_studio_name').val(spot.guest_studio_name || '');
    $('#guest_address_search').val(spot.guest_studio_address || '');
    $('#guest_studio_address').val(spot.guest_studio_address || '');
    $('#guest_street_number').val(spot.guest_street_number || '');
    $('#guest_street_name').val(spot.guest_street_name || '');
    $('#guest_studio_city').val(spot.guest_studio_city || '');
    $('#guest_studio_state').val(spot.guest_studio_state || '');
    $('#guest_postal_code').val(spot.guest_postal_code || '');
    $('#guest_studio_country').val(spot.guest_studio_country || '');
    $('#guest_google_maps_link').val(spot.guest_google_maps_link || '');
    $('#guest_latitude').val(spot.guest_latitude != null ? spot.guest_latitude : '');
    $('#guest_longitude').val(spot.guest_longitude != null ? spot.guest_longitude : '');
  }

  function resetAvailableFields() {
    $('#guest_response_deadline').val('');
    $('#guest_response_deadline_unit').val('hours');
    $('#guest_start_time, #guest_end_time').val('');
    $('#guest_buffer_days_before').val('');
    $('#guest_buffer_days_after').val('');
    $('#guest_number_of_spots').val('');
    $('#guest_studio_name').val('');
    $('#guest_address_search').val('').removeClass('border-error');
    $('#guest_studio_address').val('');
    $('#guest_latitude, #guest_longitude').val('');
    $('#guest_street_number, #guest_street_name, #guest_studio_city, #guest_studio_state, #guest_postal_code, #guest_studio_country, #guest_google_maps_link').val('');
    $('#response_deadline_error, #start_time_error, #end_time_error, #buffer_days_before_error, #buffer_days_after_error, #number_of_spots_error, #guest_studio_name_error, #guest_studio_address_error, #guest_street_number_error, #guest_street_name_error, #guest_studio_city_error, #guest_studio_state_error, #guest_postal_code_error, #guest_studio_country_error, #guest_google_maps_link_error').addClass('hidden').text('');
  }

  function resetForm() {
    $('#guest_editing_id').val('');
    $('#guest_city').val('');
    $('#guest_country').val('');
    $('#guest_from').val('');
    $('#guest_to').val('');
    resetAvailableFields();
    setStatusCards('available');
    clearFieldErrors();
    setFormMode('add');
    $list.find('.guest-spot-row').removeClass('is-editing');
  }

  function enterEditMode($row) {
    var id = String($row.data('id') || '');
    var status = $row.attr('data-status') || 'available';
    var spot = null;
    try {
      spot = JSON.parse($row.attr('data-spot') || 'null');
    } catch (e) {
      spot = null;
    }
    $('#guest_editing_id').val(id);
    $('#guest_city').val($row.attr('data-city') || '');
    $('#guest_country').val($row.attr('data-country') || '');
    $('#guest_from').val($row.attr('data-from') || '');
    $('#guest_to').val($row.attr('data-to') || '');
    resetAvailableFields();
    setStatusCards(status);
    if (status === 'available' && spot) {
      populateAvailableFields(spot);
    }
    clearFieldErrors();
    setFormMode('edit');
    $list.find('.guest-spot-row').removeClass('is-editing');
    $row.addClass('is-editing');

    $('html, body').animate({ scrollTop: $formCard.offset().top - 80 }, 250);
    $('#guest_city').trigger('focus');
  }

  function persistOrder() {
    var ids = $list.find('.guest-spot-row').map(function () {
      return parseInt($(this).data('id'), 10);
    }).get().filter(Boolean);

    if (!ids.length) return;

    $.ajax({
      url: reorderUrl,
      method: 'POST',
      data: { ids: ids, _token: csrf },
      error: function (xhr) {
        var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'Could not save order.';
        showAlert(msg, 'error');
      }
    });
  }

  function scheduleReorderPersist() {
    clearTimeout(reorderTimer);
    reorderTimer = setTimeout(persistOrder, 250);
  }

  $list.on('dragstart', '.guest-spot-row', function (e) {
    dragSrc = this;
    $(this).addClass('dragging');
    e.originalEvent.dataTransfer.effectAllowed = 'move';
    e.originalEvent.dataTransfer.setData('text/plain', '');
  });

  $list.on('dragend', '.guest-spot-row', function () {
    $(this).removeClass('dragging');
    $list.find('.guest-spot-row').removeClass('drag-over-top drag-over-bottom');
    dragSrc = null;
  });

  $list.on('dragover', '.guest-spot-row', function (e) {
    e.preventDefault();
    if (!dragSrc || dragSrc === this) return;
    var rect = this.getBoundingClientRect();
    var mid = rect.top + rect.height / 2;
    var before = e.originalEvent.clientY < mid;
    $(this).toggleClass('drag-over-top', before).toggleClass('drag-over-bottom', !before);
  });

  $list.on('dragleave', '.guest-spot-row', function () {
    $(this).removeClass('drag-over-top drag-over-bottom');
  });

  $list.on('drop', '.guest-spot-row', function (e) {
    e.preventDefault();
    if (!dragSrc || dragSrc === this) return;
    var rect = this.getBoundingClientRect();
    var mid = rect.top + rect.height / 2;
    var before = e.originalEvent.clientY < mid;
    if (before) {
      $(this).before(dragSrc);
    } else {
      $(this).after(dragSrc);
    }
    $list.find('.guest-spot-row').removeClass('drag-over-top drag-over-bottom');
    scheduleReorderPersist();
  });

  var $deleteModal = $('#deleteGuestSpotModal');
  var MODAL_MS = 350;
  var pendingDelete = null;

  function openDeleteModal($row) {
    var city = $row.attr('data-city') || '';
    var country = $row.attr('data-country') || '';
    var label = [city, country].filter(Boolean).join(', ') || 'this guest location';
    pendingDelete = {
      url: $row.attr('data-delete-url'),
      id: String($row.data('id') || ''),
      $row: $row,
    };
    $('#deleteGuestSpotLabel').text(label);
    $('#deleteGuestSpotError').addClass('hidden').text('');
    $('#btnDeleteGuestSpotConfirm').prop('disabled', false);
    $('#btnDeleteGuestSpotConfirm .confirm-delete-icon').text('delete');
    $('#btnDeleteGuestSpotConfirm .confirm-delete-label').text('Delete');
    clearTimeout($deleteModal.data('closeTimer'));
    $deleteModal.addClass('modal-visible').attr('aria-hidden', 'false');
    requestAnimationFrame(function () {
      $deleteModal.addClass('modal-open');
    });
  }

  function closeDeleteModal() {
    $deleteModal.removeClass('modal-open');
    clearTimeout($deleteModal.data('closeTimer'));
    var t = setTimeout(function () {
      $deleteModal.removeClass('modal-visible').attr('aria-hidden', 'true');
      pendingDelete = null;
    }, MODAL_MS);
    $deleteModal.data('closeTimer', t);
  }

  $list.on('click', '.guest-spot-edit', function () {
    enterEditMode($(this).closest('.guest-spot-row'));
  });

  $list.on('click', '.guest-spot-delete', function () {
    var $row = $(this).closest('.guest-spot-row');
    if (!$row.attr('data-delete-url')) return;
    openDeleteModal($row);
  });

  $('#btnDeleteGuestSpotCancel').on('click', closeDeleteModal);
  $deleteModal.on('click', function (e) {
    if (e.target === this) closeDeleteModal();
  });
  $deleteModal.find('.delete-guest-modal-inner').on('click', function (e) {
    e.stopPropagation();
  });

  $('#btnDeleteGuestSpotConfirm').on('click', function () {
    if (!pendingDelete || !pendingDelete.url) return;
    var $btn = $(this);
    var url = pendingDelete.url;
    var id = pendingDelete.id;
    var $row = pendingDelete.$row;
    var editingId = String($('#guest_editing_id').val() || '');

    $btn.prop('disabled', true);
    $btn.find('.confirm-delete-label').text('Deleting…');
    $('#deleteGuestSpotError').addClass('hidden').text('');

    $.ajax({
      url: url,
      method: 'POST',
      data: { _token: csrf, _method: 'DELETE' },
      success: function (res) {
        if (editingId && editingId === id) {
          resetForm();
        }
        if ($row && $row.length) {
          $row.remove();
        }
        refreshListVisibility();
        closeDeleteModal();
        showAlert((res && res.message) || 'Guest location removed.', 'success');
      },
      error: function (xhr) {
        $btn.prop('disabled', false);
        $btn.find('.confirm-delete-icon').text('delete');
        $btn.find('.confirm-delete-label').text('Delete');
        var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'Could not remove location.';
        $('#deleteGuestSpotError').removeClass('hidden').text(msg);
      }
    });
  });

  $(document).on('keydown', function (e) {
    if (e.key === 'Escape' && $deleteModal.hasClass('modal-open')) {
      closeDeleteModal();
    }
  });

  $('#btnCancelGuestSpotEdit').on('click', function () {
    resetForm();
  });

  $('#guestSpotForm').on('submit', function (e) {
    e.preventDefault();
    clearFieldErrors();

    var $btn = $('#btnAddGuestSpot');
    if ($btn.prop('disabled')) return;

    var editingId = $.trim($('#guest_editing_id').val());
    var isEdit = !!editingId;
    var payload = {
      _token: csrf,
      status: $('#guest_status').val(),
      city: $.trim($('#guest_city').val()),
      country: $.trim($('#guest_country').val()),
      from_date: $('#guest_from').val(),
      to_date: $('#guest_to').val(),
    };

    if (($('#guest_status').val() || '') === 'available') {
      if (!$.trim($('#guest_studio_address').val())) {
        $('#guest_studio_address').val($.trim($('#guest_address_search').val()));
      }
      Object.assign(payload, {
        start_time: $('#guest_start_time').val(),
        end_time: $('#guest_end_time').val(),
        response_deadline: $.trim($('#guest_response_deadline').val()),
        response_deadline_unit: $('#guest_response_deadline_unit').val(),
        buffer_days_before: $.trim($('#guest_buffer_days_before').val()) || '0',
        buffer_days_after: $.trim($('#guest_buffer_days_after').val()) || '0',
        number_of_spots: $.trim($('#guest_number_of_spots').val()) || '0',
        guest_studio_name: $.trim($('#guest_studio_name').val()),
        guest_studio_address: $.trim($('#guest_studio_address').val()),
        guest_street_number: $.trim($('#guest_street_number').val()),
        guest_street_name: $.trim($('#guest_street_name').val()),
        guest_studio_city: $.trim($('#guest_studio_city').val()),
        guest_studio_state: $.trim($('#guest_studio_state').val()),
        guest_postal_code: $.trim($('#guest_postal_code').val()),
        guest_studio_country: $.trim($('#guest_studio_country').val()),
        guest_google_maps_link: $.trim($('#guest_google_maps_link').val()),
        guest_latitude: $.trim($('#guest_latitude').val()),
        guest_longitude: $.trim($('#guest_longitude').val()),
      });
    }

    if (isEdit) {
      payload._method = 'PUT';
    }

    $btn.prop('disabled', true);
    $btn.find('.btn-label').text(isEdit ? 'Saving…' : 'Saving…');

    $.ajax({
      url: isEdit ? resourceUrl(editingId) : storeUrl,
      method: 'POST',
      data: payload,
      success: function (res) {
        if (!(res && res.guest_spot)) return;
        if (isEdit) {
          var $row = $list.find('.guest-spot-row[data-id="' + editingId + '"]');
          if ($row.length) {
            fillRow($row, res.guest_spot);
          }
          showAlert(res.message || 'Guest location updated.', 'success');
        } else {
          $list.append(buildRow(res.guest_spot));
          refreshListVisibility();
          showAlert(res.message || 'Guest location added.', 'success');
        }
        resetForm();
      },
      error: function (xhr) {
        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
          setFieldErrors(xhr.responseJSON.errors);
        } else {
          var msg = (xhr.responseJSON && xhr.responseJSON.message) || (isEdit ? 'Could not update location.' : 'Could not add location.');
          showAlert(msg, 'error');
        }
      },
      complete: function () {
        $btn.prop('disabled', false);
        var stillEditing = !!$.trim($('#guest_editing_id').val());
        $btn.find('.btn-label').text(stillEditing ? 'Save changes' : 'Add location');
      }
    });
  });

  syncGuestAvailableFields();

@if(config('services.google.place_api_key'))
  (function initGuestStudioAddressAutocomplete() {
    var input = document.getElementById('guest_address_search');
    if (!input || typeof google === 'undefined' || !google.maps || !google.maps.places) return;
    var ac = new google.maps.places.Autocomplete(input, {
      types: ['address'],
      fields: ['address_components', 'formatted_address', 'place_id', 'geometry'],
    });
    ac.addListener('place_changed', function () {
      var place = ac.getPlace();
      if (!place.address_components) return;
      if (place.geometry && place.geometry.location) {
        $('#guest_latitude').val(place.geometry.location.lat());
        $('#guest_longitude').val(place.geometry.location.lng());
      }
      var sn = '', st = '', city = '', state = '', zip = '', country = '';
      for (var i = 0; i < place.address_components.length; i++) {
        var c = place.address_components[i];
        var t = c.types;
        if (t.indexOf('street_number') !== -1) sn = c.long_name;
        if (t.indexOf('route') !== -1) st = c.long_name;
        if (t.indexOf('locality') !== -1) city = c.long_name;
        else if (t.indexOf('postal_town') !== -1 && !city) city = c.long_name;
        if (t.indexOf('administrative_area_level_1') !== -1) state = c.short_name || c.long_name;
        if (t.indexOf('postal_code') !== -1) zip = c.long_name;
        if (t.indexOf('country') !== -1) country = c.long_name;
      }
      $('#guest_street_number').val(sn);
      $('#guest_street_name').val(st);
      $('#guest_studio_city').val(city);
      $('#guest_studio_state').val(state);
      $('#guest_postal_code').val(zip);
      $('#guest_studio_country').val(country);
      $('#guest_studio_address').val(place.formatted_address || '');
      $('#guest_address_search').val(place.formatted_address || '').removeClass('border-error');
      $('#guest_studio_address_error').addClass('hidden').text('');
      if (place.place_id) {
        $('#guest_google_maps_link').val('https://www.google.com/maps/place/?q=place_id:' + place.place_id);
      }
    });
  })();
@endif

  $('#guest_address_search').on('input', function () {
    $('#guest_studio_address').val($(this).val());
    $('#guest_studio_address_error').text('').addClass('hidden');
    $(this).removeClass('border-error');
  });

  $.each([
    'guest_studio_name', 'guest_street_number', 'guest_street_name', 'guest_studio_city',
    'guest_studio_state', 'guest_postal_code', 'guest_studio_country', 'guest_google_maps_link',
    'guest_number_of_spots', 'guest_response_deadline', 'guest_buffer_days_before', 'guest_buffer_days_after',
  ], function (_, id) {
    $('#' + id).on('input', function () {
      $(this).removeClass('border-error');
      $('#' + id + '_error').text('').addClass('hidden');
      if (id === 'guest_number_of_spots') {
        $('#number_of_spots_error').text('').addClass('hidden');
      }
      if (id === 'guest_response_deadline') {
        $('#response_deadline_error').text('').addClass('hidden');
      }
      if (id === 'guest_buffer_days_before') {
        $('#buffer_days_before_error').text('').addClass('hidden');
      }
      if (id === 'guest_buffer_days_after') {
        $('#buffer_days_after_error').text('').addClass('hidden');
      }
    });
  });

  // Hide placeholder on focus; restore on blur if still empty.
  $('#guest_buffer_days_before, #guest_buffer_days_after, #guest_number_of_spots').each(function () {
    var $el = $(this);
    if (!$el.attr('data-placeholder')) {
      $el.attr('data-placeholder', $el.attr('placeholder') || '0');
    }
  }).on('focus', function () {
    $(this).attr('placeholder', '');
  }).on('blur', function () {
    var $el = $(this);
    if (!$.trim($el.val())) {
      $el.attr('placeholder', $el.attr('data-placeholder') || '0');
    }
  });
})();
</script>
@endsection
