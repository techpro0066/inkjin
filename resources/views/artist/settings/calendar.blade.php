@extends('layouts.artist_dashboard_layout')

@section('title', 'Calendar Settings')

@section('styles')
<style>
  .schedule-card { border: 1.5px solid #cac4d3; border-radius: 16px; padding: 32px; cursor: pointer; transition: all 0.2s; background: white; position: relative; }
    .schedule-card.selected { border-color: #310f7a; border-width: 2px; }
    .schedule-card .radio-indicator { width: 20px; height: 20px; border-radius: 50%; border: 2px solid #cac4d3; position: absolute; top: 20px; right: 20px; transition: all 0.2s; display: flex; align-items: center; justify-content: center; }
    .schedule-card.selected .radio-indicator { border-color: #310f7a; background: #310f7a; }
    .schedule-card.selected .radio-indicator::after { content: ''; width: 6px; height: 6px; background: white; border-radius: 50%; }

    @media (max-width: 1023px) {
      .main-content { overflow-x: hidden; padding: 16px; padding-top: 70px; }
      body { overflow-x: hidden; }
    }
</style>
@endsection

@section('content')
{{-- <div class="container-xxl flex-grow-1 container-p-y">
  <h4 class="fw-bold py-3 mb-4">
    <span class="text-muted fw-light">Settings /</span> Calendar
  </h4>

  @if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <i class="ti ti-check-circle me-2"></i>
      {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  @if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <i class="ti ti-alert-circle me-2"></i>
      {{ session('error') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-header">
          <h5 class="card-title mb-0">Scheduling Type</h5>
          <p class="text-muted mb-0">Choose how you want to manage your scheduling</p>
        </div>
        <div class="card-body">
          <form method="POST" action="{{ route('settings.calendar.update') }}" id="calendarForm">
            @csrf
            <input type="hidden" name="scheduling_type" id="scheduling_type" value="{{ $userDetail->scheduling_type ?? '' }}">
            
            <div class="row g-3 mb-4">
              <div class="col-md-6">
                <div class="card border-2 h-100 cursor-pointer scheduling-option {{ ($userDetail->scheduling_type ?? '') == 'auto' ? 'border-primary' : 'border-dashed' }}" 
                     data-scheduling-type="auto"
                     onclick="selectSchedulingType('auto', this)" 
                     style="cursor: pointer; transition: all 0.3s;">
                  <div class="card-body text-center py-5">
                    <i class="ti ti-calendar-automated ti-3x {{ ($userDetail->scheduling_type ?? '') == 'auto' ? 'text-primary' : 'text-muted' }} mb-3"></i>
                    <h6 class="mb-2">Auto Scheduling</h6>
                    <p class="text-muted mb-3">Connect your Google Calendar to automatically sync your availability and bookings.</p>
                    @if(($userDetail->scheduling_type ?? '') == 'auto')
                      <span class="badge bg-primary mb-3">
                        <i class="ti ti-check me-1"></i> Selected
                      </span>
                    @endif
                  </div>
                </div>
              </div>
              
              <div class="col-md-6">
                <div class="card border-2 h-100 cursor-pointer scheduling-option {{ ($userDetail->scheduling_type ?? '') == 'managed' ? 'border-primary' : 'border-dashed' }}" 
                     data-scheduling-type="managed"
                     onclick="selectSchedulingType('managed', this)" 
                     style="cursor: pointer; transition: all 0.3s;">
                  <div class="card-body text-center py-5">
                    <i class="ti ti-calendar-user ti-3x {{ ($userDetail->scheduling_type ?? '') == 'managed' ? 'text-primary' : 'text-muted' }} mb-3"></i>
                    <h6 class="mb-2">Managed Scheduling</h6>
                    <p class="text-muted mb-3">Manage your schedule manually without connecting a calendar.</p>
                    @if(($userDetail->scheduling_type ?? '') == 'managed')
                      <span class="badge bg-primary mb-3">
                        <i class="ti ti-check me-1"></i> Selected
                      </span>
                    @endif
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Calendar Connection Section (only shown for Auto Scheduling) -->
            <div class="row g-3 mb-4" id="calendarConnectionSection" style="display: {{ ($userDetail->scheduling_type ?? '') == 'auto' ? 'flex' : 'none' }};">
              <div class="col-12">
                <div class="card border-2 {{ ($userDetail->google_calendar_token ?? null) ? 'border-success' : 'border-dashed' }}">
                  <div class="card-body text-center py-4">
                    <i class="ti ti-calendar ti-3x {{ ($userDetail->google_calendar_token ?? null) ? 'text-success' : 'text-muted' }} mb-3"></i>
                    <h6 class="mb-2">Connect Your Google Calendar</h6>
                    <p class="text-muted mb-4">Connect your Google Calendar to enable automatic scheduling.</p>
                    
                    @if($userDetail->google_calendar_token ?? null)
                      <div class="mb-3">
                        <span class="badge bg-success mb-3">
                          <i class="ti ti-check me-1"></i> Google Calendar Connected
                        </span>
                      </div>
                      <button type="button" class="btn btn-label-danger" id="disconnectCalendarBtn">
                        <i class="ti ti-unlink me-2"></i>
                        Disconnect Calendar
                      </button>
                    @else
                      <button type="button" class="btn btn-outline-primary" id="connectCalendarBtn">
                        <i class="ti ti-brand-google me-2"></i>
                        Connect Google Calendar
                      </button>
                    @endif
                    
                    <input type="hidden" name="google_calendar_connected" id="google_calendar_connected" value="{{ ($userDetail->google_calendar_token ?? null) ? '1' : '0' }}">
                  </div>
                </div>
              </div>
            </div>
            
            @error('scheduling_type')
              <p class="text-danger mt-1 mb-3">{{ $message }}</p>
            @enderror
            
            <div class="d-flex justify-content-end mt-4">
              <button type="submit" class="btn btn-primary">
                <i class="ti ti-device-floppy me-2"></i>
                Save Changes
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Disconnect Calendar Confirmation Modal -->
<div class="modal fade" id="disconnectCalendarModal" tabindex="-1" aria-labelledby="disconnectCalendarModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="disconnectCalendarModalLabel">
          <i class="ti ti-alert-triangle text-warning me-2"></i>
          Disconnect Google Calendar
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p class="mb-0">Are you sure you want to disconnect your Google Calendar? You can reconnect it later if needed.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
          Cancel
        </button>
        <button type="button" class="btn btn-danger" id="confirmDisconnectBtn">
          <i class="ti ti-unlink me-2"></i>
          Disconnect
        </button>
      </div>
    </div>
  </div>
</div> --}}

@php
  $st = $userDetail->scheduling_type ?? '';
  $defaultSched = $st !== '' ? $st : 'auto';
  $isAuto = $defaultSched === 'auto';
  $gcal = !empty($userDetail->google_calendar_token);
@endphp
  <main class="main-content flex-1 min-h-screen flex flex-col">
    <form id="calendarForm" class="contents">
      @csrf
      <input type="hidden" name="scheduling_type" id="scheduling_type" value="{{ $defaultSched }}">
    <div class="flex-1 p-6 md:p-10 lg:p-12 max-w-4xl">

      <!-- Settings Tabs -->
      <div class="flex items-center gap-1 mb-6 border-b border-outline-variant/20 pb-0 overflow-x-auto">
        <a href="{{route('profile.edit')}}" class="px-4 py-3 text-sm font-semibold whitespace-nowrap border-b-2 border-transparent text-on-surface-variant hover:text-on-surface hover:border-outline-variant transition-all">Profile</a>
        <a href="{{route('settings.styles')}}" class="px-4 py-3 text-sm font-semibold whitespace-nowrap border-b-2 border-transparent text-on-surface-variant hover:text-on-surface hover:border-outline-variant transition-all">Styles &amp; Social</a>
        <a href="{{route('settings.studio')}}" class="px-4 py-3 text-sm font-semibold whitespace-nowrap border-b-2 border-transparent text-on-surface-variant hover:text-on-surface hover:border-outline-variant transition-all">Studio</a>
        <a href="{{route('settings.preferences')}}" class="px-4 py-3 text-sm font-semibold whitespace-nowrap border-b-2 border-transparent text-on-surface-variant hover:text-on-surface hover:border-outline-variant transition-all">Preferences</a>
        <a href="javascript:void(0)" class="px-4 py-3 text-sm font-semibold whitespace-nowrap border-b-2 border-primary text-primary hover:text-on-surface hover:border-outline-variant transition-all">Calendar</a>
        <a href="{{route('settings.payment')}}" class="px-4 py-3 text-sm font-semibold whitespace-nowrap border-b-2 border-transparent text-on-surface-variant hover:text-on-surface hover:border-outline-variant transition-all">Payments</a>
        {{-- <a href="{{ route('settings.notifications') }}" class="px-4 py-3 text-sm font-semibold whitespace-nowrap border-b-2 border-transparent text-on-surface-variant hover:text-on-surface hover:border-outline-variant transition-all">Notifications</a> --}}
      </div>


      <!-- Page Header -->
      <div class="mb-8">
        <h2 class="text-3xl font-extrabold text-on-surface tracking-tight">Calendar Settings</h2>
        <p class="text-on-surface-variant mt-1">Choose your scheduling model and manage calendar integrations.</p>
      </div>
      <p id="scheduling_type_error" class="text-error text-sm mt-1 mb-4 hidden"></p>
      <div id="calAlert" class="hidden rounded-xl px-4 py-3 text-sm mb-6"></div>

      <!-- Schedule Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <!-- Auto Scheduling -->
        <div class="schedule-card {{ $isAuto ? 'selected' : '' }}" onclick="selectSchedule('auto', this)" id="card-auto">
          <div class="radio-indicator"></div>
          <div class="w-12 h-12 rounded-xl bg-secondary-fixed flex items-center justify-center mb-5">
            <span class="material-symbols-outlined text-primary text-2xl">calendar_month</span>
          </div>
          <h3 class="text-xl font-bold text-on-surface mb-2">Auto Scheduling</h3>
          <p class="text-on-surface-variant text-sm leading-relaxed mb-6">Sync your Google Calendar to automatically block off busy times and let clients book into open slots instantly.</p>
          <span class="inline-block text-[10px] uppercase tracking-[1.5px] font-bold text-primary">Most Efficient</span>
        </div>

        <!-- Managed Scheduling -->
        <div class="schedule-card {{ $defaultSched === 'managed' ? 'selected' : '' }}" onclick="selectSchedule('managed', this)" id="card-managed">
          <div class="radio-indicator"></div>
          <div class="w-12 h-12 rounded-xl bg-secondary-fixed flex items-center justify-center mb-5">
            <span class="material-symbols-outlined text-primary text-2xl">edit_calendar</span>
          </div>
          <h3 class="text-xl font-bold text-on-surface mb-2">Managed Scheduling</h3>
          <p class="text-on-surface-variant text-sm leading-relaxed mb-6">Total manual control. Review every request before it hits your books. Ideal for high-detail custom work.</p>
          <span class="inline-block text-[10px] uppercase tracking-[1.5px] font-bold text-primary">Most Control</span>
        </div>
      </div>

      <!-- Google Calendar Connection Status -->
      <div id="google-calendar-status" class="bg-surface-container-low rounded-2xl p-6" style="display: {{ $isAuto ? 'block' : 'none' }};">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-white flex items-center justify-center shadow-sm border border-outline-variant/15">
              <svg class="w-7 h-7" viewBox="0 0 24 24">
                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 01-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z"/>
                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
              </svg>
            </div>
            <div>
              <div class="flex items-center gap-2">
                <h4 class="text-sm font-bold text-on-surface">Google Calendar</h4>
                @if($gcal)
                  <span id="googleStatusBadge" class="inline-flex items-center gap-1 text-xs font-semibold text-green-700 bg-green-50 px-2 py-0.5 rounded-full">
                    <span class="w-1.5 h-1.5 rounded-full bg-green-600"></span> Connected
                  </span>
                @else
                  <span id="googleStatusBadge" class="inline-flex items-center gap-1 text-xs font-semibold text-amber-700 bg-amber-50 px-2 py-0.5 rounded-full">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-600"></span> Not connected
                  </span>
                @endif
              </div>
              <p id="googleStatusText" class="text-on-surface-variant text-xs mt-1">{{ $gcal ? 'Google Calendar is connected and ready for auto scheduling.' : 'Connect Google Calendar to enable auto scheduling.' }}</p>
            </div>
          </div>
          <div id="googleActionWrap">
            @if($gcal)
              <button type="button" id="disconnectCalendarBtn" class="text-sm font-semibold text-error hover:text-on-error-container border border-error/20 px-4 py-2 rounded-xl hover:bg-error-container/30 transition-colors">
                Disconnect
              </button>
            @else
              <button type="button" id="connectCalendarBtn" class="text-sm font-semibold text-primary border border-primary/25 px-4 py-2 rounded-xl hover:bg-primary/10 transition-colors">
                Connect
              </button>
            @endif
          </div>
        </div>
      </div>
    </div>

    <!-- Footer: Save Changes -->
    <div class="sticky bottom-0 bg-surface border-t border-outline-variant/10 px-6 md:px-10 lg:px-12 py-5 flex items-center justify-end">
      <button type="submit" id="calSubmit" class="inline-flex items-center gap-2 bg-gradient-to-br from-primary to-primary-container text-white font-bold py-3 px-8 rounded-xl shadow-lg shadow-primary/20 hover:opacity-90 transition-all active:scale-[0.98]">
        <span class="material-symbols-outlined text-lg">save</span> Save Changes
      </button>
    </div>
    </form>
  </main>

  <div id="disconnectCalendarModal" class="hidden fixed inset-0 z-[200] flex items-center justify-center p-4 bg-black/50" role="dialog" aria-modal="true">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl">
      <h5 class="text-lg font-bold text-on-surface mb-2">Disconnect calendar?</h5>
      <p class="text-on-surface-variant text-sm mb-6">You can reconnect later.</p>
      <div class="flex justify-end gap-3">
        <button type="button" id="cancelDisconnectCal" class="rounded-xl px-5 py-2.5 text-sm font-semibold text-on-surface hover:bg-surface-container-low">Cancel</button>
        <button type="button" id="confirmDisconnectBtn" class="rounded-xl px-5 py-2.5 text-sm font-semibold bg-error text-white hover:opacity-90">Disconnect</button>
      </div>
    </div>
  </div>
@endsection

@section('scripts')

<script>
  function showCalAlert(msg) {
    var $alertEl = $('#calAlert');
    $alertEl.attr('class', 'rounded-xl px-4 py-3 text-sm mb-6 bg-red-50 text-red-800 border border-red-200');
    $alertEl.text(msg).removeClass('hidden');
  }
  function selectSchedule(type, el) {
    document.querySelectorAll('.schedule-card').forEach(c => c.classList.remove('selected'));
    el.classList.add('selected');
    document.getElementById('scheduling_type').value = type;
    $('#scheduling_type_error').addClass('hidden').text('');
    $('#calAlert').addClass('hidden').text('');

    // Show/hide Google Calendar status based on selection
    const gcStatus = document.getElementById('google-calendar-status');
    if (type === 'auto') {
      gcStatus.style.display = '';
    } else {
      gcStatus.style.display = 'none';
    }
  }
  $(function () {
    function bindConnectButton() {
      $('#connectCalendarBtn').off('click').on('click', function () {
        window.location.href = @json(route('google.calendar.redirect'));
      });
    }
    function openCalModal() {
      $('#disconnectCalendarModal').removeClass('hidden');
    }
    function closeCalModal() {
      $('#disconnectCalendarModal').addClass('hidden');
    }
    function bindDisconnectButton() {
      $('#disconnectCalendarBtn').off('click').on('click', openCalModal);
    }
    function markCalendarDisconnectedUi() {
      $('#googleStatusBadge')
        .attr('class', 'inline-flex items-center gap-1 text-xs font-semibold text-amber-700 bg-amber-50 px-2 py-0.5 rounded-full')
        .html('<span class="w-1.5 h-1.5 rounded-full bg-amber-600"></span> Not connected');
      $('#googleStatusText').text('Connect Google Calendar to enable auto scheduling.');
      $('#googleActionWrap').html('<button type="button" id="connectCalendarBtn" class="text-sm font-semibold text-primary border border-primary/25 px-4 py-2 rounded-xl hover:bg-primary/10 transition-colors">Connect</button>');
      bindConnectButton();
    }

    bindConnectButton();
    bindDisconnectButton();
    $('#cancelDisconnectCal').on('click', closeCalModal);
    $('#disconnectCalendarModal').on('click', function (e) {
      if (e.target === this) closeCalModal();
    });
    $('#confirmDisconnectBtn').on('click', function () {
      closeCalModal();
      $.ajax({
        url: @json(route('google.calendar.disconnect')),
        type: 'POST',
        data: { _token: @json(csrf_token()) },
        headers: {
          'X-CSRF-TOKEN': @json(csrf_token()),
          Accept: 'application/json',
        },
      }).done(function (data) {
        if (data.success) {
          markCalendarDisconnectedUi();
          $('#calAlert').attr('class', 'rounded-xl px-4 py-3 text-sm mb-6 bg-green-50 text-green-800 border border-green-200').text('Google Calendar disconnected successfully.').removeClass('hidden');
        }
      });
    });

    $('#calendarForm').on('submit', function (e) {
      e.preventDefault();
      var st = $('#scheduling_type').val();
      if (!st) {
        $('#scheduling_type_error').text('Choose a scheduling model.').removeClass('hidden');
        return;
      }
      $('#scheduling_type_error').addClass('hidden').text('');
      $('#calAlert').addClass('hidden').text('');
      var $btn = $('#calSubmit');
      $btn.prop('disabled', true).html('<span class="material-symbols-outlined text-lg">hourglass_top</span> Saving...');
      var fd = new FormData(this);
      $.ajax({
        url: @json(route('settings.calendar.update')),
        type: 'POST',
        data: fd,
        processData: false,
        contentType: false,
        headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
          Accept: 'application/json',
        },
      })
        .done(function (data) {
          if (data.success) {
            $('#calAlert').attr('class', 'rounded-xl px-4 py-3 text-sm mb-6 bg-green-50 text-green-800 border border-green-200').text(data.message || 'Calendar settings updated successfully.').removeClass('hidden');
            showSaveToast();
            return;
          }
          showCalAlert(data.message || 'Could not save');
        })
        .fail(function (xhr) {
          if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
            var first = Object.values(xhr.responseJSON.errors)[0];
            showCalAlert((first && first[0]) || xhr.responseJSON.message || 'Please fix the validation errors.');
          } else {
            showCalAlert((xhr.responseJSON && xhr.responseJSON.message) || 'Network error');
          }
        })
        .always(function () {
          $btn.prop('disabled', false).html('<span class="material-symbols-outlined text-lg">save</span> Save Changes');
        });
    });
  });
</script>
@endsection
