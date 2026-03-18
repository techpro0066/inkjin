@extends('layouts.dashboard_layout')

@section('title', 'Calendar Settings')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
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
</div>

@push('scripts')
<script>
  // Select scheduling type
  function selectSchedulingType(type, element) {
    // Update hidden input
    document.getElementById('scheduling_type').value = type;
    
    // Update visual selection
    document.querySelectorAll('.scheduling-option').forEach(option => {
      option.classList.remove('border-primary');
      option.classList.add('border-dashed');
      const icon = option.querySelector('i.ti-calendar-automated, i.ti-calendar-user');
      if (icon) {
        icon.classList.remove('text-primary');
        icon.classList.add('text-muted');
      }
    });
    
    element.classList.remove('border-dashed');
    element.classList.add('border-primary');
    const selectedIcon = element.querySelector('i.ti-calendar-automated, i.ti-calendar-user');
    if (selectedIcon) {
      selectedIcon.classList.remove('text-muted');
      selectedIcon.classList.add('text-primary');
    }
    
    // Show/hide calendar connection section
    const calendarSection = document.getElementById('calendarConnectionSection');
    if (type === 'auto') {
      if (calendarSection) calendarSection.style.display = 'flex';
    } else {
      if (calendarSection) calendarSection.style.display = 'none';
    }
  }

  // Connect Calendar - Redirect to Google OAuth
  document.getElementById('connectCalendarBtn')?.addEventListener('click', () => {
    window.location.href = '{{ route("google.calendar.redirect") }}';
  });

  // Disconnect Calendar - Show Modal
  const disconnectCalendarBtn = document.getElementById('disconnectCalendarBtn');
  const disconnectCalendarModal = document.getElementById('disconnectCalendarModal');
  const confirmDisconnectBtn = document.getElementById('confirmDisconnectBtn');
  
  if (disconnectCalendarBtn) {
    disconnectCalendarBtn.addEventListener('click', () => {
      const modal = new bootstrap.Modal(disconnectCalendarModal);
      modal.show();
    });
  }

  // Confirm Disconnect from Modal
  if (confirmDisconnectBtn) {
    confirmDisconnectBtn.addEventListener('click', async () => {
      // Close modal
      const modal = bootstrap.Modal.getInstance(disconnectCalendarModal);
      modal.hide();

      const btn = document.getElementById('disconnectCalendarBtn');
      const originalText = btn.innerHTML;
      btn.disabled = true;
      btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Disconnecting...';

      try {
        const response = await fetch('{{ route("google.calendar.disconnect") }}', {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
            'Content-Type': 'application/json',
          }
        });

        const data = await response.json();
        
        if (data.success) {
          // Reload page to update UI
          window.location.reload();
        } else {
          alert(data.message || 'Failed to disconnect Google Calendar');
          btn.disabled = false;
          btn.innerHTML = originalText;
        }
      } catch (error) {
        alert('An error occurred while disconnecting. Please try again.');
        btn.disabled = false;
        btn.innerHTML = originalText;
      }
    });
  }

  // Form validation before submit
  document.getElementById('calendarForm')?.addEventListener('submit', function(e) {
    const schedulingType = document.getElementById('scheduling_type').value;
    
    if (!schedulingType) {
      e.preventDefault();
      alert('Please select a scheduling type.');
      return false;
    }
    
    // If auto scheduling is selected, check if calendar is connected
    if (schedulingType === 'auto') {
      const calendarConnected = document.getElementById('google_calendar_connected').value === '1';
      if (!calendarConnected) {
        e.preventDefault();
        alert('Please connect your Google Calendar for auto scheduling.');
        return false;
      }
    }
  });
</script>
@endpush
@endsection
