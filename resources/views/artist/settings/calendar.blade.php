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
          <h5 class="card-title mb-0">Google Calendar Connection</h5>
          <p class="text-muted mb-0">Connect your Google Calendar to sync your appointments</p>
        </div>
        <div class="card-body">
          <div class="text-center py-5">
            <i class="ti ti-calendar ti-3x {{ ($userDetail->google_calendar_token ?? null) ? 'text-success' : 'text-muted' }} mb-3"></i>
            <h6 class="mb-2">Connect Your Google Calendar</h6>
            <p class="text-muted mb-4">Sync your appointments and manage your schedule seamlessly.</p>
            
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
              <button type="button" class="btn btn-primary" id="connectCalendarBtn">
                <i class="ti ti-brand-google me-2"></i>
                Connect Google Calendar
              </button>
            @endif
          </div>
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
</script>
@endpush
@endsection

