@extends('layouts.inkjin_auth_layout')

@section('title', 'Login')

@section('content')

<body class="bg-background text-on-surface min-h-screen flex flex-col">
  <!-- Background Decoration: The "ij" Watermark -->
  <div class="fixed inset-0 overflow-hidden pointer-events-none z-0">
    <div class="absolute -top-24 -right-24 w-96 h-96 brand-gradient opacity-[0.03] rounded-full blur-3xl"></div>
    <div
      class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 text-[40rem] font-black text-primary-fixed-dim opacity-[0.05] select-none tracking-tighter">
      ij</div>
  </div>
  <!-- Main Content Atrium -->
  <main class="flex-grow flex items-center justify-center p-6 md:p-12 relative z-10">
    <div class="w-full max-auto max-w-[440px]">
      <div class="flex flex-col items-center mb-8">
        <span class="text-4xl font-bold text-on-surface tracking-tighter leading-none mb-1"
          style="font-family: 'Space Grotesk', sans-serif;">bookpay</span>
        <span
          class="text-[10px] font-medium text-on-surface-variant uppercase tracking-widest text-center leading-tight">Tattoo
          artist platform<br>by Inkjin</span>
      </div>

      <!-- Auth Card -->
      <div
        class="surface-container-lowest glass-panel rounded-xl shadow-[0_32px_64px_-12px_rgba(49,15,122,0.06)] p-8 md:p-10">
        <div class="w-full max-w-md">
          <!-- Logo Section -->

          <!-- Brand Header -->
          <div class="flex flex-col items-center mb-10 text-center">
            <h1 class="text-3xl font-extrabold text-on-surface tracking-tight mb-2"
              style="font-family: 'Space Grotesk', sans-serif;">Welcome back!</h1>
            <p class="text-on-surface-variant">Continue to your booking and payments.</p>
          </div>
          <!-- Form -->
          <div id="login-status-message" class="mb-6 rounded-xl border border-primary/10 bg-primary/5 px-4 py-3 text-sm text-on-surface {{ session('status') ? '' : 'hidden' }}">{{ session('status') }}</div>

          <form action="{{ route('login') }}" class="space-y-6" method="POST" id="login-form" novalidate>
            @csrf
            <!-- Email Input -->
            <div class="space-y-2">
              <label class="text-sm font-semibold text-on-surface-variant ml-1" for="login-email">Email Address</label>
              <div class="relative group">
                <input
                  class="w-full text-sm rounded-xl px-4 py-3 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30 placeholder:text-outline/50 @error('email') border border-red-500 @else border border-outline-variant/30 @enderror"
                  id="login-email" name="email" placeholder="name@company.com" type="email"
                  value="{{ old('email') }}" autocomplete="username" autofocus data-error-key="email" />
              </div>
              <p class="text-xs text-red-500 mt-1 @error('email') @else hidden @enderror" data-error-for="email">@error('email'){{ $message }}@enderror</p>
            </div>
            <!-- Password Input -->
            <div class="space-y-2">
              <div class="flex justify-between items-center ml-1">
                <label class="text-sm font-semibold text-on-surface-variant ml-1" for="login-password">Password</label>
                <a class="text-xs font-bold text-primary hover:opacity-80 transition-opacity"
                  href="{{ route('password.request') }}">Forgot Password?</a>
              </div>
              <div class="relative group">
                <input
                  class="w-full text-sm rounded-xl px-4 py-3 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30 placeholder:text-outline/50 @error('password') border border-red-500 @else border border-outline-variant/30 @enderror"
                  id="login-password" name="password" placeholder="••••••••" type="password"
                  autocomplete="current-password" data-error-key="password" />
                <button
                  class="absolute right-4 top-1/2 -translate-y-1/2 text-outline-variant hover:text-on-surface-variant"
                  type="button" onclick="togglePassword(this)">
                  <span class="material-symbols-outlined text-[20px]">visibility</span>
                </button>
              </div>
              <p class="text-xs text-red-500 mt-1 @error('password') @else hidden @enderror" data-error-for="password">@error('password'){{ $message }}@enderror</p>
            </div>
            <!-- Remember Me -->
            <div class="flex items-center space-x-3 ml-1">
              <input
                class="w-5 h-5 rounded border-outline-variant text-primary focus:ring-primary bg-surface-container-highest"
                id="remember" name="remember" type="checkbox" {{ old('remember') ? 'checked' : '' }} />
              <label class="text-sm text-on-surface-variant cursor-pointer select-none" for="remember">Remember this
                device</label>
            </div>
            <!-- Submit Button -->
            <button
              id="login-submit-button"
              class="w-full bg-gradient-to-br from-primary to-primary-container text-on-primary font-bold py-4 px-8 rounded-xl shadow-lg shadow-primary/20 hover:opacity-90 transition-all active:scale-[0.98] flex justify-center items-center gap-2"
              type="submit">
              Sign In
              <span
                class="material-symbols-outlined text-[20px] group-hover:translate-x-1 transition-transform">arrow_forward</span>
            </button>
          </form>
          <!-- Divider -->
          <div class="relative my-10">
            <div class="absolute inset-0 flex items-center">
              <div class="w-full border-t border-outline-variant/20"></div>
            </div>
          </div>

          <!-- Footer Link -->
          <p class="mt-8 text-center text-sm text-on-surface-variant">
            Don't have an account?
            <a class="text-primary font-bold hover:underline" href="{{ route('register') }}">Sign up for free</a>
          </p>
        </div>
      </div>
  </main>
  <!-- Shared Footer Navigation -->
  <footer class="py-8 w-full bg-surface text-on-surface-variant text-sm">
    <div class="text-center px-6">
      <div class="flex flex-wrap justify-center gap-4 mb-3">
        <a class="hover:text-primary transition-colors duration-300" href="https://inkjin.com/en/privacy.html">Privacy
          Policy</a>
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

    const loginForm = document.getElementById('login-form');
    const loginSubmitButton = document.getElementById('login-submit-button');
    const loginStatusMessage = document.getElementById('login-status-message');

    function setLoginFieldError(fieldName, message) {
      const input = loginForm.querySelector(`[data-error-key="${fieldName}"]`);
      const errorEl = loginForm.querySelector(`[data-error-for="${fieldName}"]`);

      if (input) {
        input.classList.remove('border-outline-variant/30');
        input.classList.add('border', 'border-red-500');
      }

      if (errorEl) {
        errorEl.textContent = message;
        errorEl.classList.remove('hidden');
      }
    }

    function clearLoginErrors() {
      loginForm.querySelectorAll('[data-error-key]').forEach((input) => {
        input.classList.remove('border-red-500');
        input.classList.add('border-outline-variant/30');
      });

      loginForm.querySelectorAll('[data-error-for]').forEach((errorEl) => {
        errorEl.textContent = '';
        errorEl.classList.add('hidden');
      });

      if (loginStatusMessage) {
        loginStatusMessage.textContent = '';
        loginStatusMessage.classList.add('hidden');
      }
    }

    loginForm.querySelectorAll('[data-error-key]').forEach((input) => {
      input.addEventListener('input', () => {
        const errorEl = loginForm.querySelector(`[data-error-for="${input.dataset.errorKey}"]`);
        input.classList.remove('border-red-500');
        input.classList.add('border-outline-variant/30');

        if (errorEl) {
          errorEl.textContent = '';
          errorEl.classList.add('hidden');
        }
      });
    });

    loginForm.addEventListener('submit', async function (e) {
      e.preventDefault();
      clearLoginErrors();
      loginSubmitButton.disabled = true;
      loginSubmitButton.classList.add('opacity-70', 'cursor-not-allowed');

      try {
        const response = await fetch(loginForm.action, {
          method: 'POST',
          headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
          },
          body: new FormData(loginForm),
          credentials: 'same-origin',
        });

        const data = await response.json();

        if (!response.ok) {
          if (response.status === 422 && data.errors) {
            const firstField = Object.keys(data.errors)[0];

            Object.entries(data.errors).forEach(([field, messages]) => {
              setLoginFieldError(field, messages[0]);
            });

            const firstInput = loginForm.querySelector(`[data-error-key="${firstField}"]`);
            if (firstInput) {
              firstInput.focus();
            }
            return;
          }

          if (loginStatusMessage) {
            loginStatusMessage.textContent = data.message || 'Unable to sign in right now. Please try again.';
            loginStatusMessage.classList.remove('hidden');
          }
          return;
        }

        if (data.redirect) {
          window.location.href = data.redirect;
        }
      } catch (error) {
        if (loginStatusMessage) {
          loginStatusMessage.textContent = 'A network error occurred. Please try again.';
          loginStatusMessage.classList.remove('hidden');
        }
      } finally {
        loginSubmitButton.disabled = false;
        loginSubmitButton.classList.remove('opacity-70', 'cursor-not-allowed');
      }
    });

  </script>
</body>

@endsection
