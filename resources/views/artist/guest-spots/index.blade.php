@extends('layouts.artist_dashboard_layout')

@section('title', 'Guest spots')

@section('styles')
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
      background: #e8ddff;
      color: #310f7a;
    }
    .status-badge.planned {
      background: #f2ecf5;
      color: #494552;
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
      $bookingPageUrl = $bookingPageUsername ? 'https://inkjin.com/@'.$bookingPageUsername : null;
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

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
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
              data-update-url="{{ route('guest-spots.update', $spot) }}"
              data-delete-url="{{ route('guest-spots.destroy', $spot) }}"
            >
              <span class="material-symbols-outlined text-outline/50 text-xl hidden sm:inline select-none" style="font-size:20px;">drag_indicator</span>
              <div class="flex items-center gap-2 sm:block">
                <span class="sm:hidden text-[11px] font-semibold uppercase text-on-surface-variant w-16 shrink-0">Status</span>
                <span class="status-badge {{ $spot->status }}">{{ ucfirst($spot->status) }}</span>
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
              <div class="flex items-center justify-end gap-0.5">
                <button type="button" class="guest-spot-action guest-spot-edit" title="Edit" aria-label="Edit guest location">
                  <span class="material-symbols-outlined text-[18px]">edit</span>
                </button>
                <button type="button" class="guest-spot-action guest-spot-delete" title="Remove" aria-label="Remove guest location">
                  <span class="material-symbols-outlined text-[18px]">delete</span>
                </button>
              </div>
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
    ['status', 'city', 'country', 'from_date', 'to_date'].forEach(function (field) {
      $('#' + field + '_error').addClass('hidden').text('');
    });
  }

  function setFieldErrors(errors) {
    clearFieldErrors();
    Object.keys(errors || {}).forEach(function (field) {
      var msg = (errors[field] && errors[field][0]) || '';
      var $el = $('#' + field + '_error');
      if ($el.length && msg) {
        $el.removeClass('hidden').text(msg);
      }
    });
  }

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
    var statusLabel = spot.status ? spot.status.charAt(0).toUpperCase() + spot.status.slice(1) : '';
    $row.attr({
      'data-id': spot.id,
      'data-status': spot.status,
      'data-city': spot.city,
      'data-country': spot.country,
      'data-from': spot.from_date,
      'data-to': spot.to_date,
      'data-update-url': resourceUrl(spot.id),
      'data-delete-url': resourceUrl(spot.id),
    });
    $row.find('.status-badge')
      .removeClass('available planned')
      .addClass(spot.status)
      .text(statusLabel);
    $row.find('.guest-spot-city').text(spot.city);
    $row.find('.guest-spot-country').text(spot.country);
    $row.find('.guest-spot-from').text(spot.from_label);
    $row.find('.guest-spot-to').text(spot.to_label);
  }

  function buildRow(spot) {
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
        '<div class="flex items-center justify-end gap-0.5">' +
          '<button type="button" class="guest-spot-action guest-spot-edit" title="Edit" aria-label="Edit guest location">' +
            '<span class="material-symbols-outlined text-[18px]">edit</span>' +
          '</button>' +
          '<button type="button" class="guest-spot-action guest-spot-delete" title="Remove" aria-label="Remove guest location">' +
            '<span class="material-symbols-outlined text-[18px]">delete</span>' +
          '</button>' +
        '</div>' +
      '</div>'
    );
    fillRow($row, spot);
    return $row;
  }

  function resetForm() {
    $('#guest_editing_id').val('');
    $('#guest_city').val('');
    $('#guest_country').val('');
    $('#guest_from').val('');
    $('#guest_to').val('');
    setStatusCards('available');
    clearFieldErrors();
    setFormMode('add');
    $list.find('.guest-spot-row').removeClass('is-editing');
  }

  function enterEditMode($row) {
    var id = String($row.data('id') || '');
    var status = $row.attr('data-status') || 'available';
    $('#guest_editing_id').val(id);
    $('#guest_city').val($row.attr('data-city') || '');
    $('#guest_country').val($row.attr('data-country') || '');
    $('#guest_from').val($row.attr('data-from') || '');
    $('#guest_to').val($row.attr('data-to') || '');
    setStatusCards(status);
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
          var first = Object.values(xhr.responseJSON.errors)[0];
          if (first && first[0]) showAlert(first[0], 'error');
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
})();
</script>
@endsection
