@extends('layouts.inkjin_auth_layout')

@section('title', 'Verify Email')

@section('content')
  <!-- Background Decoration: The "ij" Watermark -->
  <div class="fixed inset-0 overflow-hidden pointer-events-none z-0">
    <div class="absolute -top-24 -right-24 w-96 h-96 brand-gradient opacity-[0.03] rounded-full blur-3xl"></div>
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 text-[40rem] font-black text-primary-fixed-dim opacity-[0.05] select-none tracking-tighter">
      ij
    </div>
  </div>

  <main class="flex-grow flex items-center justify-center p-6 md:p-12 relative z-10">
    <div class="w-full max-auto max-w-[440px]">
      <!-- Logo Section -->
      <div class="flex justify-center mb-10">
        <img
          alt="Inkjin Logo"
          class="h-16 w-auto object-contain"
          src="{{ asset('design/images/logo-blue.png') }}"
        />
      </div>

      <!-- Card Container -->
      <div class="bg-surface-container-lowest rounded-xl p-8 md:p-10 shadow-[0_32px_64px_-12px_rgba(49,15,122,0.06)] ring-1 ring-outline-variant/15">
        <div class="text-center mb-8">
          <h1 class="font-headline text-3xl font-extrabold text-on-surface tracking-tight mb-3">
            Verify Your Email Address
          </h1>
          <p class="text-on-surface-variant">
            Thanks for signing up! Before getting started, please verify your email address by clicking the link we emailed you.
            If you didn't receive the email, you can resend it below.
          </p>
        </div>

        @if (session('status') == 'verification-link-sent')
          <div class="mb-5 rounded-xl bg-primary-container/25 border border-primary-container/40 px-4 py-3 text-sm text-on-surface">
            <strong>Success!</strong> A new verification link has been sent to the email address you provided during registration.
          </div>
        @endif

        <div id="resendMessage" class="mb-5 rounded-xl bg-primary-container/20 border border-primary-container/40 px-4 py-3 text-sm text-on-surface" style="display: none;">
          <small><strong>Note:</strong> <span id="resendMessageText"></span></small>
        </div>

        <div class="space-y-4">
          <form method="POST" action="{{ route('verification.send') }}" id="resendVerificationForm" class="mb-0">
            @csrf
            <button
              type="submit"
              id="resendButton"
              class="w-full inline-flex items-center justify-center gap-2 bg-gradient-to-br from-primary to-primary-container text-white font-bold py-3 px-8 rounded-xl shadow-lg shadow-primary/20 hover:opacity-90 transition-all active:scale-[0.98]"
            >
              <span id="buttonText">{{ __('Resend Verification Email') }}</span>
              <span id="countdownText" style="display: none;"></span>
            </button>
          </form>

          <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button
              type="submit"
              class="w-full inline-flex items-center justify-center bg-surface-container-highest text-on-surface-variant font-semibold py-3 px-8 rounded-xl border border-outline-variant/30 hover:bg-white transition-all"
            >
              {{ __('Log Out') }}
            </button>
          </form>
        </div>
      </div>
    </div>
  </main>

  <!-- Footer -->
  <footer class="py-8 w-full bg-surface text-on-surface-variant text-sm">
    <div class="text-center px-6">
      <div class="flex flex-wrap justify-center gap-4 mb-3">
        <a class="hover:text-primary transition-colors duration-300" href="#">Privacy Policy</a>
        <span class="text-outline-variant/40">·</span>
        <a class="hover:text-primary transition-colors duration-300" href="#">Terms of Service</a>
        <span class="text-outline-variant/40">·</span>
        <a class="hover:text-primary transition-colors duration-300" href="#">Help Center</a>
      </div>
      <div class="text-on-surface-variant/60 font-medium">© 2026 Inkjin. All rights reserved.</div>
    </div>
  </footer>

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

      const activeButtonClasses = [
        'bg-gradient-to-br',
        'from-primary',
        'to-primary-container',
        'text-white',
        'hover:opacity-90',
        'shadow-lg',
        'shadow-primary/20'
      ];
      const cooldownButtonClasses = [
        'bg-surface-container-highest',
        'text-on-surface-variant'
      ];

      let countdownInterval = null;
      let isSubmitting = false;

      // Check for existing cooldown in localStorage
      function checkExistingCooldown() {
        const storedData = localStorage.getItem(STORAGE_KEY);
        if (storedData) {
          const data = JSON.parse(storedData);
          const now = Date.now();
          const elapsed = Math.floor((now - data.timestamp) / 1000);
          const remaining = COOLDOWN_DURATION - elapsed;

          if (remaining > 0) {
            startCooldown(remaining, data.timestamp);

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
      function startCooldown(initialSeconds, startedAt) {
        let remaining = initialSeconds || COOLDOWN_DURATION;

        // Disable button
        resendButton.disabled = true;
        buttonText.style.display = 'none';
        countdownText.style.display = 'inline';
        resendButton.classList.remove(...activeButtonClasses);
        resendButton.classList.add(...cooldownButtonClasses);

        // Keep original timestamp when restoring from localStorage after refresh.
        localStorage.setItem(STORAGE_KEY, JSON.stringify({
          timestamp: startedAt || Date.now(),
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
            buttonText.textContent = 'Resend Verification Email';
            countdownText.style.display = 'none';
            resendButton.classList.remove(...cooldownButtonClasses);
            resendButton.classList.add(...activeButtonClasses);

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

      // Handle form submission without page refresh (AJAX)
      form.addEventListener('submit', async function(e) {
        e.preventDefault();

        // Check if button is disabled (cooldown active)
        if (resendButton.disabled) {
          resendMessageText.textContent = 'Email already sent, please wait before retrying.';
          resendMessage.style.display = 'block';
          localStorage.setItem(MESSAGE_KEY, 'Email already sent, please wait before retrying.');
          return;
        }

        if (isSubmitting) return;
        isSubmitting = true;

        const originalButtonText = buttonText.textContent;
        buttonText.textContent = 'Sending...';
        resendButton.disabled = true;

        try {
          const csrfToken = form.querySelector('input[name="_token"]')?.value || '';
          const response = await fetch(form.action, {
            method: 'POST',
            headers: {
              'X-CSRF-TOKEN': csrfToken,
              'X-Requested-With': 'XMLHttpRequest',
              'Accept': 'application/json',
              'Content-Type': 'application/json'
            },
            body: JSON.stringify({})
          });

          if (response.ok) {
            const successMessage = 'Verification email sent successfully! Please check your inbox.';
            resendMessageText.textContent = successMessage;
            resendMessage.style.display = 'block';
            localStorage.setItem(MESSAGE_KEY, successMessage);
            startCooldown(COOLDOWN_DURATION);
          } else if (response.status === 429) {
            const throttleMessage = 'Too many requests. Please wait and try again.';
            resendMessageText.textContent = throttleMessage;
            resendMessage.style.display = 'block';
            localStorage.setItem(MESSAGE_KEY, throttleMessage);
            resendButton.disabled = false;
            buttonText.textContent = originalButtonText;
            resendButton.classList.remove(...cooldownButtonClasses);
            resendButton.classList.add(...activeButtonClasses);
          } else {
            const errorMessage = 'Unable to resend verification email right now. Please try again.';
            resendMessageText.textContent = errorMessage;
            resendMessage.style.display = 'block';
            localStorage.setItem(MESSAGE_KEY, errorMessage);
            resendButton.disabled = false;
            buttonText.textContent = originalButtonText;
            resendButton.classList.remove(...cooldownButtonClasses);
            resendButton.classList.add(...activeButtonClasses);
          }
        } catch (error) {
          resendMessageText.textContent = 'Network error. Please check your connection and try again.';
          resendMessage.style.display = 'block';
          localStorage.setItem(MESSAGE_KEY, 'Network error. Please check your connection and try again.');
          resendButton.disabled = false;
          buttonText.textContent = originalButtonText;
          resendButton.classList.remove(...cooldownButtonClasses);
          resendButton.classList.add(...activeButtonClasses);
        } finally {
          isSubmitting = false;
        }
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
