@extends('layouts.inkjin_auth_layout')

@section('title', 'Forgot Password')

@section('content')
<body class="bg-background text-on-surface min-h-screen flex flex-col selection:bg-primary-fixed-dim">
  <!-- Hero Decorative Monogram (The Atrium Aesthetic) -->
  <!-- Main Content Canvas -->
  <main class="flex-grow flex items-center justify-center p-6 md:p-12 relative z-10">
  <!-- Authentication Card -->
  <div class="w-full max-auto max-w-[440px]">
  <!-- Logo Section -->
  <div class="flex flex-col items-center mb-8">
    <span class="text-4xl font-bold text-on-surface tracking-tighter leading-none mb-1" style="font-family: 'Space Grotesk', sans-serif;">bookpay</span>
    <span class="text-[10px] font-medium text-on-surface-variant uppercase tracking-widest text-center leading-tight">Tattoo artist platform<br>by Inkjin</span>
  </div>
  <!-- Card Container -->
  <div class="bg-surface-container-lowest rounded-xl p-8 md:p-10 shadow-[0_32px_64px_-12px_rgba(49,15,122,0.06)] ring-1 ring-outline-variant/15">
  <!-- Header -->
  <div class="text-center mb-8">
  <h1 class="font-headline text-3xl font-extrabold text-on-surface tracking-tight mb-3" style="">
                          Forgot password?
                      </h1>
  <p class="text-on-surface-variant" style="">
                          No worries, we'll send you a link to reset your password.</p>
  </div>
  <!-- Form -->
  <div id="forgot-password-status-message" class="mb-6 rounded-xl border border-primary/10 bg-primary/5 px-4 py-3 text-sm text-on-surface {{ session('status') ? '' : 'hidden' }}">{{ session('status') }}</div>
  <form action="{{ route('password.email') }}" class="space-y-6" method="POST" id="forgot-password-form" novalidate>
  @csrf
  <div class="space-y-2">
  
  <label class="block text-sm font-semibold text-on-surface mb-2" for="reset-email" style="">
                              Email address
                          </label>
  <div class="relative">
  <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
  </div>
  <input class="form-input @error('email') border-red-500 @enderror" style="background-color:#F8F1FB;" id="reset-email" name="email" placeholder="name@company.com" type="email" value="{{ old('email') }}" autocomplete="username" required autofocus data-error-key="email">
  </div>
  <p class="text-xs text-red-500 mt-1 @error('email') @else hidden @enderror" data-error-for="email">@error('email'){{ $message }}@enderror</p>
  </div>
  <!-- CTA Button -->
  <button class="w-full inline-flex items-center justify-center gap-2 bg-gradient-to-br from-primary to-primary-container text-white font-bold py-3 px-8 rounded-xl shadow-lg shadow-primary/20 hover:opacity-90 transition-all active:scale-[0.98]" type="submit" style="" id="forgot-password-submit">
  <span class="" style="">Send Reset Link</span>
  <span class="material-symbols-outlined text-lg group-hover:translate-x-1 transition-transform" data-icon="arrow_forward" style="">arrow_forward</span>
  </button>
  </form>
  <!-- Sign in -->
  <div class="mt-8 text-center">
  <a class="inline-flex items-center gap-2 text-primary font-semibold hover:text-primary-container transition-colors group" href="{{ route('login') }}" style="">
  <span class="material-symbols-outlined text-lg" data-icon="keyboard_backspace" style="">keyboard_backspace</span>
  <span class="" style="">Back to Sign in</span>
  </a>
  </div>
  </div>
  <!-- Secondary Help Text -->
  <p class="mt-8 text-center text-sm text-on-surface-variant" style="">
                  Having trouble? <a class="text-primary font-medium underline underline-offset-4 decoration-primary/30 hover:decoration-primary" href="#" style="">Contact Support</a>
  </p>
  </div>
  </main>
  <!-- Footer Component -->
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
  <script>
  const forgotPasswordForm = document.getElementById('forgot-password-form');
  const forgotPasswordSubmitButton = document.getElementById('forgot-password-submit');
  const forgotPasswordStatusMessage = document.getElementById('forgot-password-status-message');
  const forgotPasswordEmailInput = document.getElementById('reset-email');

  function setForgotPasswordFieldError(fieldName, message) {
    const input = forgotPasswordForm.querySelector(`[data-error-key="${fieldName}"]`);
    const errorEl = forgotPasswordForm.querySelector(`[data-error-for="${fieldName}"]`);

    if (input) {
      input.classList.add('border-red-500');
    }

    if (errorEl) {
      errorEl.textContent = message;
      errorEl.classList.remove('hidden');
    }
  }

  function clearForgotPasswordErrors() {
    forgotPasswordForm.querySelectorAll('[data-error-key]').forEach((input) => {
      input.classList.remove('border-red-500');
    });

    forgotPasswordForm.querySelectorAll('[data-error-for]').forEach((errorEl) => {
      errorEl.textContent = '';
      errorEl.classList.add('hidden');
    });
  }

  forgotPasswordEmailInput.addEventListener('input', function() {
    this.classList.remove('border-red-500');
    const errorEl = forgotPasswordForm.querySelector('[data-error-for="email"]');
    if (errorEl) {
      errorEl.textContent = '';
      errorEl.classList.add('hidden');
    }
  });

  forgotPasswordForm.addEventListener('submit', async function(e) {
    e.preventDefault();
    clearForgotPasswordErrors();
    forgotPasswordStatusMessage.textContent = '';
    forgotPasswordStatusMessage.classList.add('hidden');
    forgotPasswordSubmitButton.disabled = true;
    forgotPasswordSubmitButton.classList.add('opacity-70', 'cursor-not-allowed');

    try {
      const response = await fetch(forgotPasswordForm.action, {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: new FormData(forgotPasswordForm),
        credentials: 'same-origin',
      });

      const data = await response.json();

      if (!response.ok) {
        if (response.status === 422 && data.errors) {
          const firstField = Object.keys(data.errors)[0];

          Object.entries(data.errors).forEach(([field, messages]) => {
            setForgotPasswordFieldError(field, messages[0]);
          });

          const firstInput = forgotPasswordForm.querySelector(`[data-error-key="${firstField}"]`);
          if (firstInput) {
            firstInput.focus();
          }
          return;
        }

        forgotPasswordStatusMessage.textContent = data.message || 'Unable to send reset link right now. Please try again.';
        forgotPasswordStatusMessage.classList.remove('hidden');
        return;
      }

      forgotPasswordStatusMessage.textContent = data.status || 'Password reset link sent successfully.';
      forgotPasswordStatusMessage.classList.remove('hidden');
      forgotPasswordForm.reset();
    } catch (error) {
      forgotPasswordStatusMessage.textContent = 'A network error occurred. Please try again.';
      forgotPasswordStatusMessage.classList.remove('hidden');
    } finally {
      forgotPasswordSubmitButton.disabled = false;
      forgotPasswordSubmitButton.classList.remove('opacity-70', 'cursor-not-allowed');
    }
  });
  </script>
</body>
@endsection
