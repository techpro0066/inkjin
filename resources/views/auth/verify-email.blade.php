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
              We are sending a secure 4-digit code to your email—check your inbox (and spam). You can resend below if you need a new code.
            </p>
          </div>

          @if (session('verification_send_error'))
            <div class="mb-5 rounded-xl bg-error-container/40 border border-error/30 px-4 py-3 text-sm text-error" role="alert">
              {{ session('verification_send_error') }}
            </div>
          @endif

          <div id="resendMessage" class="items-center gap-2 text-sm text-green-700 bg-green-50 border border-green-200 rounded-xl px-3 py-2 mb-3 hidden">
            <strong>Note:</strong> <span id="resendMessageText"></span>
          </div>

          <div class="space-y-4">
            <form method="POST" action="{{ route('verification.verify-code') }}" class="mb-0 space-y-3">
              @csrf
              <div>
                <label class="text-sm font-semibold text-on-surface-variant ml-1 mb-1 inline-block" for="verification_code">{{ __('4-digit code') }}</label>
                <input
                  type="text"
                  name="code"
                  id="verification_code"
                  value="{{ old('code') }}"
                  maxlength="4"
                  inputmode="numeric"
                  pattern="[0-9]*"
                  autocomplete="one-time-code"
                  placeholder="1234"
                  class="w-full border bg-white rounded-2xl px-6 py-4 text-lg tracking-[0.3em] text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30 {{ $errors->has('code') ? 'border-error ring-1 ring-error/30' : 'border-outline-variant/30' }}"
                />
              </div>

              @if ($errors->has('code'))
                <div class="rounded-xl bg-error-container/40 border border-error/30 px-4 py-3 text-sm text-error" role="alert">
                  {{ $errors->first('code') }}
                </div>
              @endif

              <button
                type="submit"
                class="w-full py-3.5 bg-primary text-on-primary rounded-full font-bold text-sm hover:bg-primary-container transition-colors shadow-lg shadow-primary/20"
              >
                {{ __('Verify email') }}
              </button>
            </form>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <form method="POST" action="{{ route('verification.send') }}" id="resendVerificationForm" class="mb-0 min-w-0">
                @csrf
                <button
                  type="submit"
                  id="resendButton"
                  class="w-full py-3.5 bg-surface-container-high text-on-surface rounded-full font-bold text-sm hover:bg-surface-container transition-colors"
                >
                  <span id="buttonText">{{ __('Resend code') }}</span>
                  <span id="countdownText" class="hidden"></span>
                </button>
              </form>

              <form method="POST" action="{{ route('logout') }}" class="min-w-0">
                @csrf
                <button
                  type="submit"
                  class="w-full py-3.5 bg-surface-container-high text-on-surface rounded-full font-bold text-sm hover:bg-surface-container transition-colors"
                >
                  {{ __('Log Out') }}
                </button>
              </form>
            </div>
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

      const idleResendClasses = [
        'bg-surface-container-high',
        'text-on-surface',
        'hover:bg-surface-container',
      ];
      const cooldownResendClasses = [
        'bg-surface-container-high',
        'text-on-surface-variant',
        'opacity-70',
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
        const emailJustSent = @json(session('email_sent_on_registration') || session('status') == 'verification-code-sent' || session('status') == 'verification-link-sent');

        if (emailJustSent && !localStorage.getItem(STORAGE_KEY)) {
          // Email was just sent (from registration or manual resend), start cooldown
          startCooldown(COOLDOWN_DURATION);

          if (!storedMessage) {
            const message = 'Verification code sent! Please check your inbox.';
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
        resendButton.classList.remove(...idleResendClasses);
        resendButton.classList.add(...cooldownResendClasses);

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
            buttonText.textContent = 'Resend code';
            countdownText.classList.add('hidden');
            resendButton.classList.remove(...cooldownResendClasses);
            resendButton.classList.add(...idleResendClasses);

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
            const successMessage = 'A new verification code was sent. Please check your inbox.';
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
            resendButton.classList.remove(...cooldownResendClasses);
            resendButton.classList.add(...idleResendClasses);
          } else {
            const errorMessage = 'Unable to resend the code right now. Please try again.';
            resendMessageText.textContent = errorMessage;
            resendMessage.classList.remove('hidden');
            localStorage.setItem(MESSAGE_KEY, errorMessage);
            resendButton.disabled = false;
            buttonText.textContent = originalButtonText;
            resendButton.classList.remove(...cooldownResendClasses);
            resendButton.classList.add(...idleResendClasses);
          }
        } catch (error) {
          resendMessageText.textContent = 'Network error. Please check your connection and try again.';
          resendMessage.classList.remove('hidden');
          localStorage.setItem(MESSAGE_KEY, 'Network error. Please check your connection and try again.');
          resendButton.disabled = false;
          buttonText.textContent = originalButtonText;
          resendButton.classList.remove(...cooldownResendClasses);
          resendButton.classList.add(...idleResendClasses);
        } finally {
          isSubmitting = false;
        }
      });

      // Initialize on page load
      checkExistingCooldown();

      // If the resend control is still enabled (no active cooldown / no "just sent" session), trigger resend once
      if (!resendButton.disabled && !isSubmitting) {
        if (typeof form.requestSubmit === 'function') {
          form.requestSubmit(resendButton);
        } else {
          resendButton.click();
        }
      }

      // Clean up interval on page unload
      window.addEventListener('beforeunload', function() {
        if (countdownInterval) {
          clearInterval(countdownInterval);
        }
      });
    })();
  </script>
@endpush
