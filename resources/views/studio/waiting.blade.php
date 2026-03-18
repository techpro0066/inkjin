@extends('layouts.auth_layout')

@section('title', 'Waiting for Studio Stripe Connection')

@section('content')
<div class="card">
    <div class="card-body text-center py-5">
        <div class="mb-4">
            <i class="ti ti-clock-hour-4 ti-5x text-warning"></i>
        </div>
        
        <h3 class="mb-3">Waiting for Studio Stripe Connection</h3>
        
        <div class="alert alert-info mb-4">
            <i class="ti ti-info-circle me-2"></i>
            <strong>Studio Account Selected</strong><br>
            You've selected <strong>{{ $studioName }}</strong> as your payment recipient.
        </div>

        <div class="card border border-warning mb-4">
            <div class="card-body">
                <h6 class="mb-3">
                    <i class="ti ti-mail me-2"></i>
                    What's Happening?
                </h6>
                <p class="mb-2 text-start">
                    An invitation email has been sent to <strong>{{ $studioEmail }}</strong> to connect their Stripe account.
                </p>
                <p class="mb-0 text-start">
                    Once the studio connects their Stripe account, you'll be able to access your dashboard and start receiving bookings.
                </p>
            </div>
        </div>

        <div class="mb-4">
            <p class="text-muted">
                <i class="ti ti-refresh me-2"></i>
                This page will automatically refresh to check the connection status.
            </p>
        </div>

        <div class="d-flex gap-2 justify-content-center flex-wrap mb-4">
            <button type="button" class="btn btn-outline-primary" id="resendInviteBtn">
                <i class="ti ti-mail me-2"></i>
                Resend Invitation Email
            </button>
            <button type="button" class="btn btn-primary" id="checkStatusBtn">
                <i class="ti ti-refresh me-2"></i>
                Check Status
            </button>
        </div>

        <div id="resendMessage" class="mt-3" style="display: none;"></div>

        <div class="mt-4 pt-4 border-top">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn btn-label-secondary">
                    <i class="ti ti-logout me-2"></i>
                    Logout
                </button>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const resendBtn = document.getElementById('resendInviteBtn');
    const checkStatusBtn = document.getElementById('checkStatusBtn');
    const resendMessage = document.getElementById('resendMessage');

    // Resend invite email
    resendBtn.addEventListener('click', async function() {
        const originalText = resendBtn.innerHTML;
        resendBtn.disabled = true;
        resendBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Sending...';

        try {
            const response = await fetch('{{ route("studio.resend-invite") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
            });

            const data = await response.json();
            
            resendMessage.style.display = 'block';
            if (data.success) {
                resendMessage.className = 'alert alert-success mt-3';
                resendMessage.innerHTML = '<i class="ti ti-check me-2"></i>' + data.message;
            } else {
                resendMessage.className = 'alert alert-danger mt-3';
                resendMessage.innerHTML = '<i class="ti ti-alert-circle me-2"></i>' + (data.message || 'Failed to resend email');
            }

            setTimeout(() => {
                resendMessage.style.display = 'none';
            }, 5000);
        } catch (error) {
            resendMessage.style.display = 'block';
            resendMessage.className = 'alert alert-danger mt-3';
            resendMessage.innerHTML = '<i class="ti ti-alert-circle me-2"></i>An error occurred. Please try again.';
        } finally {
            resendBtn.disabled = false;
            resendBtn.innerHTML = originalText;
        }
    });

    // Check status - reload page
    checkStatusBtn.addEventListener('click', function() {
        window.location.reload();
    });

    // Auto-refresh every 30 seconds
    setInterval(function() {
        window.location.reload();
    }, 30000);
});
</script>
@endsection
