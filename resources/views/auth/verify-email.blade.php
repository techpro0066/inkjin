@extends('layouts.inkjin_auth_layout')

@section('title', 'Verify Email')

@section('content')
  <div class="fixed inset-0 overflow-hidden pointer-events-none z-0">
    <div class="absolute -top-24 -right-24 w-96 h-96 brand-gradient opacity-[0.03] rounded-full blur-3xl"></div>
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 text-[40rem] font-black text-primary-fixed-dim opacity-[0.05] select-none tracking-tighter">
      ij
    </div>
  </div>

  <main class="flex-grow flex items-center justify-center p-6 md:p-12 relative z-10">
    <div class="w-full max-auto max-w-[440px]">
      <div class="flex flex-col items-center mb-8">
        <span class="text-4xl font-bold text-on-surface tracking-tighter leading-none mb-1" style="font-family: 'Space Grotesk', sans-serif;">
          bookpay
        </span>
        <span class="text-[10px] font-medium text-on-surface-variant uppercase tracking-widest text-center leading-tight">
          Tattoo artist platform<br>by Inkjin
        </span>
      </div>

      <div class="surface-container-lowest glass-panel rounded-xl shadow-[0_32px_64px_-12px_rgba(49,15,122,0.06)] p-8 md:p-10">
        <div class="w-full max-w-md">
          <div class="flex flex-col items-center mb-8 text-center">
            <div class="w-14 h-14 rounded-full bg-primary-container/15 flex items-center justify-center mb-4">
              <span class="material-symbols-outlined text-primary text-[28px]">mark_email_read</span>
            </div>
            <h1 class="text-3xl font-extrabold text-on-surface tracking-tight mb-2" style="font-family: 'Space Grotesk', sans-serif;">
              Verify your email
            </h1>
            <p class="text-on-surface-variant">
              Thanks for signing up. Please click the verification link sent to your inbox before continuing. If you did not receive it, resend below.
            </p>
          </div>

          @if (session('status') == 'verification-link-sent')
            <div class="mb-5 rounded-xl bg-primary-container/25 border border-primary-container/40 px-4 py-3 text-sm text-on-surface">
              <strong>Success:</strong> A new verification link has been sent to your email address.
            </div>
          @endif

          <div id="resendMessage" class="mb-5 rounded-xl bg-primary-container/20 border border-primary-container/40 px-4 py-3 text-sm text-on-surface hidden">
            <strong>Note:</strong> <span id="resendMessageText"></span>
          </div>

          <div class="space-y-4">
            <form method="POST" action="{{ route('verification.send') }}" id="resendVerificationForm" class="mb-0">
              @csrf
              <button
                type="submit"
                id="resendButton"
                class="w-full inline-flex items-center justify-center gap-2 bg-gradient-to-br from-primary to-primary-container text-white font-bold py-4 px-8 rounded-xl shadow-lg shadow-primary/20 hover:opacity-90 transition-all active:scale-[0.98]"
              >
                <span id="buttonText">{{ __('Resend Verification Email') }}</span>
                <span id="countdownText" class="hidden"></span>
              </button>
            </form>

            <form method="POST" action="{{ route('logout') }}">
              @csrf
              <button
                type="submit"
                class="w-full inline-flex items-center justify-center bg-surface-container-highest text-on-surface-variant font-semibold py-4 px-8 rounded-xl border border-outline-variant/30 hover:bg-white transition-all"
              >
                {{ __('Log Out') }}
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </main>

  <footer class="py-8 w-full bg-surface text-on-surface-variant text-sm">
    <div class="text-center px-6">
      <div class="flex flex-wrap justify-center gap-4 mb-3">
        <a class="hover:text-primary transition-colors duration-300" href="https://inkjin.com/en/privacy.html">Privacy Policy</a>
        <span class="text-outline-variant/40">·</span>
        <a class="hover:text-primary transition-colors duration-300" href="artists_terms.html">Terms of Service</a>
        <span class="text-outline-variant/40">·</span>
        <a class="hover:text-primary transition-colors duration-300" href="https://help.inkjin.com/en/">Help Center</a>
      </div>
      <div class="text-on-surface-variant/60 font-medium">© 2026 Inkjin. All rights reserved.</div>
    </div>
  </footer>
@endsection

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
              resendMessage.classList.remove('hidden');
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
          resendMessage.classList.remove('hidden');
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
            resendMessage.classList.remove('hidden');
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
        buttonText.classList.add('hidden');
        countdownText.classList.remove('hidden');
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
            buttonText.classList.remove('hidden');
            buttonText.textContent = 'Resend Verification Email';
            countdownText.classList.add('hidden');
            resendButton.classList.remove(...cooldownButtonClasses);
            resendButton.classList.add(...activeButtonClasses);

            // Clear localStorage
            localStorage.removeItem(STORAGE_KEY);

            // Clear message
            resendMessage.classList.add('hidden');
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
          resendMessage.classList.remove('hidden');
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
            resendMessage.classList.remove('hidden');
            localStorage.setItem(MESSAGE_KEY, successMessage);
            startCooldown(COOLDOWN_DURATION);
          } else if (response.status === 429) {
            const throttleMessage = 'Too many requests. Please wait and try again.';
            resendMessageText.textContent = throttleMessage;
            resendMessage.classList.remove('hidden');
            localStorage.setItem(MESSAGE_KEY, throttleMessage);
            resendButton.disabled = false;
            buttonText.textContent = originalButtonText;
            resendButton.classList.remove(...cooldownButtonClasses);
            resendButton.classList.add(...activeButtonClasses);
          } else {
            const errorMessage = 'Unable to resend verification email right now. Please try again.';
            resendMessageText.textContent = errorMessage;
            resendMessage.classList.remove('hidden');
            localStorage.setItem(MESSAGE_KEY, errorMessage);
            resendButton.disabled = false;
            buttonText.textContent = originalButtonText;
            resendButton.classList.remove(...cooldownButtonClasses);
            resendButton.classList.add(...activeButtonClasses);
          }
        } catch (error) {
          resendMessageText.textContent = 'Network error. Please check your connection and try again.';
          resendMessage.classList.remove('hidden');
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
