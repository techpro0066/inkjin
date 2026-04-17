@extends('layouts.inkjin_auth_layout')

@section('title', 'Reset Password')

@section('content')
<body class="bg-background text-on-surface min-h-screen flex flex-col selection:bg-primary-fixed-dim">
  <main class="flex-grow flex items-center justify-center p-6 md:p-12 relative z-10">
    <div class="w-full max-auto max-w-[440px]">
      <div class="flex flex-col items-center mb-8">
        <span class="text-4xl font-bold text-on-surface tracking-tighter leading-none mb-1" style="font-family: 'Space Grotesk', sans-serif;">bookpay</span>
        <span class="text-[10px] font-medium text-on-surface-variant uppercase tracking-widest text-center leading-tight">Tattoo artist platform<br>by Inkjin</span>
      </div>

      <div class="bg-surface-container-lowest rounded-xl p-8 md:p-10 shadow-[0_32px_64px_-12px_rgba(49,15,122,0.06)] ring-1 ring-outline-variant/15">
        <div class="text-center mb-8">
          <h1 class="font-headline text-3xl font-extrabold text-on-surface tracking-tight mb-3">Create new password</h1>
          <p class="text-on-surface-variant">Enter your email and choose a secure new password.</p>
        </div>

        <div id="reset-password-status-message" class="mb-6 rounded-xl border border-primary/10 bg-primary/5 px-4 py-3 text-sm text-on-surface {{ session('status') ? '' : 'hidden' }}">{{ session('status') }}</div>

        <form action="{{ route('password.store') }}" class="space-y-6" id="reset-password-form" method="POST" novalidate>
          @csrf
          <input type="hidden" name="token" value="{{ $request->route('token') }}">

          <div class="space-y-2">
            <label class="block text-sm font-semibold text-on-surface mb-2" for="reset-email">Email address</label>
            <input class="form-input @error('email') border-red-500 @enderror" style="background-color:#F8F1FB;" id="reset-email" name="email" placeholder="name@company.com" type="email" value="{{ old('email', $request->email) }}" autocomplete="username" required autofocus data-error-key="email" readonly>
            <p class="text-xs text-red-500 mt-1 @error('email') @else hidden @enderror" data-error-for="email">@error('email'){{ $message }}@enderror</p>
          </div>

          <div class="space-y-2">
            <label class="block text-sm font-semibold text-on-surface mb-2" for="reset-password">New Password</label>
            <div class="relative">
              <input class="form-input @error('password') border-red-500 @enderror" style="background-color:#F8F1FB;" id="reset-password" name="password" placeholder="••••••••" type="password" autocomplete="new-password" required data-error-key="password">
              <button class="absolute right-4 top-1/2 -translate-y-1/2 text-outline-variant hover:text-primary transition-colors" type="button" onclick="togglePassword(this)">
                <span class="material-symbols-outlined text-[20px]">visibility</span>
              </button>
            </div>
            <p class="text-xs text-red-500 mt-1 @error('password') @else hidden @enderror" data-error-for="password">@error('password'){{ $message }}@enderror</p>
          </div>

          <div class="space-y-2">
            <label class="block text-sm font-semibold text-on-surface mb-2" for="reset-password-confirmation">Confirm Password</label>
            <div class="relative">
              <input class="form-input @error('password') border-red-500 @enderror" style="background-color:#F8F1FB;" id="reset-password-confirmation" name="password_confirmation" placeholder="••••••••" type="password" autocomplete="new-password" required data-error-key="password_confirmation">
              <button class="absolute right-4 top-1/2 -translate-y-1/2 text-outline-variant hover:text-primary transition-colors" type="button" onclick="togglePassword(this)">
                <span class="material-symbols-outlined text-[20px]">visibility</span>
              </button>
            </div>
            <p class="text-xs text-red-500 mt-1 hidden" data-error-for="password_confirmation"></p>
          </div>

          <button class="w-full inline-flex items-center justify-center gap-2 bg-gradient-to-br from-primary to-primary-container text-white font-bold py-3 px-8 rounded-xl shadow-lg shadow-primary/20 hover:opacity-90 transition-all active:scale-[0.98]" id="reset-password-submit" type="submit">
            <span>Reset Password</span>
            <span class="material-symbols-outlined text-lg">arrow_forward</span>
          </button>
        </form>

        <div class="mt-8 text-center">
          <a class="inline-flex items-center gap-2 text-primary font-semibold hover:text-primary-container transition-colors group" href="{{ route('login') }}">
            <span class="material-symbols-outlined text-lg">keyboard_backspace</span>
            <span>Back to Sign in</span>
          </a>
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

  <script>
    function togglePassword(btn) {
      const input = btn.closest('.relative').querySelector('input');
      const icon = btn.querySelector('.material-symbols-outlined');
      if (input.type === 'password') {
        input.type = 'text';
        icon.textContent = 'visibility_off';
      } else {
        input.type = 'password';
        icon.textContent = 'visibility';
      }
    }

    const resetPasswordForm = document.getElementById('reset-password-form');
    const resetPasswordSubmitButton = document.getElementById('reset-password-submit');
    const resetPasswordStatusMessage = document.getElementById('reset-password-status-message');

    function setResetPasswordFieldError(fieldName, message) {
      const input = resetPasswordForm.querySelector(`[data-error-key="${fieldName}"]`);
      const errorEl = resetPasswordForm.querySelector(`[data-error-for="${fieldName}"]`);

      if (input) {
        input.classList.add('border-red-500');
      }

      if (errorEl) {
        errorEl.textContent = message;
        errorEl.classList.remove('hidden');
      }
    }

    function clearResetPasswordErrors() {
      resetPasswordForm.querySelectorAll('[data-error-key]').forEach((input) => {
        input.classList.remove('border-red-500');
      });

      resetPasswordForm.querySelectorAll('[data-error-for]').forEach((errorEl) => {
        errorEl.textContent = '';
        errorEl.classList.add('hidden');
      });
    }

    resetPasswordForm.querySelectorAll('[data-error-key]').forEach((input) => {
      input.addEventListener('input', () => {
        const errorEl = resetPasswordForm.querySelector(`[data-error-for="${input.dataset.errorKey}"]`);
        input.classList.remove('border-red-500');
        if (errorEl) {
          errorEl.textContent = '';
          errorEl.classList.add('hidden');
        }
      });
    });

    resetPasswordForm.addEventListener('submit', async function (e) {
      e.preventDefault();
      clearResetPasswordErrors();
      resetPasswordStatusMessage.textContent = '';
      resetPasswordStatusMessage.classList.add('hidden');
      resetPasswordSubmitButton.disabled = true;
      resetPasswordSubmitButton.classList.add('opacity-70', 'cursor-not-allowed');

      try {
        const response = await fetch(resetPasswordForm.action, {
          method: 'POST',
          headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
          },
          body: new FormData(resetPasswordForm),
          credentials: 'same-origin',
        });

        const data = await response.json();

        if (!response.ok) {
          if (response.status === 422 && data.errors) {
            const firstField = Object.keys(data.errors)[0];

            Object.entries(data.errors).forEach(([field, messages]) => {
              setResetPasswordFieldError(field, messages[0]);
            });

            const firstInput = resetPasswordForm.querySelector(`[data-error-key="${firstField}"]`);
            if (firstInput) {
              firstInput.focus();
            } else {
              resetPasswordStatusMessage.textContent = data.message || 'Unable to reset your password.';
              resetPasswordStatusMessage.classList.remove('hidden');
            }
            return;
          }

          resetPasswordStatusMessage.textContent = data.message || 'Unable to reset your password right now. Please try again.';
          resetPasswordStatusMessage.classList.remove('hidden');
          return;
        }

        if (data.redirect) {
          window.location.href = data.redirect;
        }
      } catch (error) {
        resetPasswordStatusMessage.textContent = 'A network error occurred. Please try again.';
        resetPasswordStatusMessage.classList.remove('hidden');
      } finally {
        resetPasswordSubmitButton.disabled = false;
        resetPasswordSubmitButton.classList.remove('opacity-70', 'cursor-not-allowed');
      }
    });
  </script>
</body>
@endsection
