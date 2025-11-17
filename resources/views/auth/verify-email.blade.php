@extends('layouts.auth_layout')

@section('title', 'Verify Email')

@section('content')
<div class="card">
  <div class="card-body">
    <div class="app-brand justify-content-center mb-4 mt-2">
      <a href="{{ url('/') }}" class="app-brand-link gap-2">
        <span class="app-brand-logo demo">
          <img src="{{ asset('assets/img/branding/logo.png') }}" alt="Inkjin Logo" width="34" height="34" />
        </span>
        <span class="app-brand-text demo text-body fw-bold ms-1">{{ config('app.name', 'Inkjin') }}</span>
      </a>
    </div>

    <h4 class="mb-1 pt-2">Verify Your Email Address 📧</h4>
    <p class="mb-4">Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn't receive the email, we will gladly send you another.</p>

    @if (session('status') == 'verification-link-sent')
      <div class="alert alert-success mb-4">
        <strong>Success!</strong> A new verification link has been sent to the email address you provided during registration.
        </div>
    @endif

    <div id="resendMessage" class="alert alert-info mb-3" style="display: none;">
      <small><strong>Note:</strong> <span id="resendMessageText"></span></small>
    </div>

    <div class="mt-4">
      <form method="POST" action="{{ route('verification.send') }}" id="resendVerificationForm" class="mb-3">
            @csrf
        <button type="submit" id="resendButton" class="btn btn-primary d-grid w-100">
          <span id="buttonText">{{ __('Resend Verification Email') }}</span>
          <span id="countdownText" style="display: none;"></span>
        </button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
        <button type="submit" class="btn btn-outline-secondary d-grid w-100">
                {{ __('Log Out') }}
            </button>
        </form>
    </div>
  </div>
</div>

@push('scripts')
<script>
(function() {
  const COOLDOWN_DURATION = 60; // 60 seconds
  const STORAGE_KEY = 'email_verification_cooldown';
  const MESSAGE_KEY = 'email_verification_message';
  
  const form = document.getElementById('resendVerificationForm');
  const resendButton = document.getElementById('resendButton');
  const buttonText = document.getElementById('buttonText');
  const countdownText = document.getElementById('countdownText');
  const resendMessage = document.getElementById('resendMessage');
  const resendMessageText = document.getElementById('resendMessageText');
  
  if (!form || !resendButton) return;
  
  let countdownInterval = null;
  
  // Check for existing cooldown in localStorage
  function checkExistingCooldown() {
    const storedData = localStorage.getItem(STORAGE_KEY);
    if (storedData) {
      const data = JSON.parse(storedData);
      const now = Date.now();
      const elapsed = Math.floor((now - data.timestamp) / 1000);
      const remaining = COOLDOWN_DURATION - elapsed;
      
      if (remaining > 0) {
        startCooldown(remaining);
        
        // Check for stored message
        const storedMessage = localStorage.getItem(MESSAGE_KEY);
        if (storedMessage) {
          resendMessageText.textContent = storedMessage;
          resendMessage.style.display = 'block';
        }
        return;
      } else {
        // Cooldown expired, clean up
        localStorage.removeItem(STORAGE_KEY);
        localStorage.removeItem(MESSAGE_KEY);
      }
    }
    
    // Check for stored message
    const storedMessage = localStorage.getItem(MESSAGE_KEY);
    if (storedMessage) {
      resendMessageText.textContent = storedMessage;
      resendMessage.style.display = 'block';
    }
    
    // Check if email was just sent (either from registration or manual resend)
    const emailJustSent = @json(session('email_sent_on_registration') || session('status') == 'verification-link-sent');
    
    if (emailJustSent && !localStorage.getItem(STORAGE_KEY)) {
      // Email was just sent (from registration or manual resend), start cooldown
      startCooldown(COOLDOWN_DURATION);
      
      if (!storedMessage) {
        const message = 'Verification email sent! Please check your inbox.';
        localStorage.setItem(MESSAGE_KEY, message);
        resendMessageText.textContent = message;
        resendMessage.style.display = 'block';
      }
      
      // Clear session flag via AJAX to prevent timer restart on refresh
      @if(session('email_sent_on_registration'))
        const csrfToken = document.querySelector('input[name="_token"]')?.value || '';
        if (csrfToken) {
          fetch('{{ route('verification.clear-registration-flag') }}', {
            method: 'POST',
            headers: {
              'X-CSRF-TOKEN': csrfToken,
              'Content-Type': 'application/json',
              'Accept': 'application/json'
            },
            body: JSON.stringify({})
          }).catch(() => {}); // Ignore errors
        }
      @endif
    }
  }
  
  // Start cooldown timer
  function startCooldown(initialSeconds) {
    let remaining = initialSeconds || COOLDOWN_DURATION;
    
    // Disable button
    resendButton.disabled = true;
    buttonText.style.display = 'none';
    countdownText.style.display = 'inline';
    
    // Store cooldown timestamp in localStorage
    localStorage.setItem(STORAGE_KEY, JSON.stringify({
      timestamp: Date.now(),
      duration: COOLDOWN_DURATION
    }));
    
    // Update countdown display
    function updateCountdown() {
      countdownText.textContent = `Resend available in ${remaining}s`;
      
      if (remaining <= 0) {
        clearInterval(countdownInterval);
        countdownInterval = null;
        
        // Enable button
        resendButton.disabled = false;
        buttonText.style.display = 'inline';
        countdownText.style.display = 'none';
        
        // Clear localStorage
        localStorage.removeItem(STORAGE_KEY);
        
        // Clear message
        resendMessage.style.display = 'none';
        localStorage.removeItem(MESSAGE_KEY);
      } else {
        remaining--;
      }
    }
    
    updateCountdown();
    countdownInterval = setInterval(updateCountdown, 1000);
  }
  
  // Handle form submission
  form.addEventListener('submit', function(e) {
    // Check if button is disabled (cooldown active)
    if (resendButton.disabled) {
      e.preventDefault();
      
      // Show message
      resendMessageText.textContent = 'Email already sent, please wait before retrying.';
      resendMessage.style.display = 'block';
      
      // Store message in localStorage
      localStorage.setItem(MESSAGE_KEY, 'Email already sent, please wait before retrying.');
      
      // Hide message after 3 seconds
      setTimeout(function() {
        resendMessage.style.display = 'none';
      }, 3000);
      
      return false;
    }
    
    // Start cooldown on successful submission
    // The form will submit normally, and on page reload we'll check localStorage
    startCooldown(COOLDOWN_DURATION);
    
    // Store success message
    localStorage.setItem(MESSAGE_KEY, 'Verification email sent successfully! Please check your inbox.');
  });
  
  // Initialize on page load
  checkExistingCooldown();
  
  // Clean up interval on page unload
  window.addEventListener('beforeunload', function() {
    if (countdownInterval) {
      clearInterval(countdownInterval);
    }
  });
})();
</script>
@endpush
@endsection
