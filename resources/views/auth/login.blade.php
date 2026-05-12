@extends('layouts.inkjin_auth_layout')

@section('title', 'Login')

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
      <div class="flex flex-col items-center mb-8">
        <span class="text-4xl font-bold text-on-surface tracking-tighter leading-none mb-1"
          style="font-family: 'Space Grotesk', sans-serif;">bookpay</span>
        <span
          class="text-[10px] font-medium text-on-surface-variant uppercase tracking-widest text-center leading-tight">Tattoo
          artist platform<br>by Inkjin</span>
      </div>

      <!-- Auth Card -->
      <div class="surface-container-lowest glass-panel rounded-xl shadow-[0_32px_64px_-12px_rgba(49,15,122,0.06)] p-8 md:p-10">
        <div class="w-full max-w-md">
          <!-- Brand Header -->
          
          <div class="flex flex-col items-center mb-10 text-center">
            <h1 class="text-3xl font-extrabold text-on-surface tracking-tight mb-2"
              style="font-family: 'Space Grotesk', sans-serif;">Welcome back!</h1>
            <p class="text-on-surface-variant">Continue to your booking and payments.</p>
          </div>

          @if (session('status') === 'email-changed')
            <div class="mb-4 rounded-xl bg-error-container/40 border border-error-container/60 px-4 py-3 text-sm text-error">
              {{ session('message', 'Your email address has been updated. Please verify your new email address before logging in again.') }}
            </div>
          {{-- @elseif (session('status'))
            <div class="mb-4 rounded-xl bg-amber-50 border border-amber-200/80 px-4 py-3 text-sm text-amber-950" role="status">
              {{ session('status') }}
            </div> --}}
          @endif

          <!-- Form -->
          <form action="{{ route('login') }}" class="space-y-6" method="POST" id="login-form">
            <div id="login-alert" class="hidden rounded-xl bg-error-container/40 border border-error-container/60 px-4 py-3 text-sm text-error"></div>

            <!-- Email Input -->
            <div class="space-y-2">
              <label class="text-sm font-semibold text-on-surface-variant ml-1" for="login-email">Email Address</label>
              <div class="relative group">
                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                <input
                  class="w-full px-4 py-3 rounded-xl border border-outline-variant/30 bg-white focus:ring-2 focus:ring-primary/40 transition-all text-on-surface placeholder:text-outline/50 @error('email') border-error @enderror"
                  id="login-email"
                  name="email"
                  placeholder="name@company.com"
                  type="email"
                  value="{{ old('email') }}"
                  autofocus
                />
              </div>
              <p class="text-sm text-error mt-1 hidden" id="email-error"></p>
              @error('email')
                <p class="text-sm text-error mt-1">{{ $message }}</p>
              @enderror
            </div>

            <!-- Password Input -->
            <div class="space-y-2">
              <div class="flex justify-between items-center ml-1">
                <label class="text-sm font-semibold text-on-surface-variant ml-1" for="login-password">Password</label>
                @if (Route::has('password.request'))
                  <a class="text-xs font-bold text-primary hover:opacity-80 transition-opacity" href="{{ route('password.request') }}">
                    Forgot Password?
                  </a>
                @endif
              </div>

              <div class="relative group">
                <input
                  class="w-full px-4 py-3 rounded-xl border border-outline-variant/30 bg-white focus:ring-2 focus:ring-primary/40 transition-all text-on-surface placeholder:text-outline/50 @error('password') border-error @enderror"
                  id="login-password"
                  name="password"
                  placeholder="••••••••"
                  type="password"
                />

                <button
                  class="absolute right-4 top-1/2 -translate-y-1/2 text-outline-variant hover:text-on-surface-variant eye-toggle"
                  data-target="#login-password"
                  type="button"
                  aria-label="Toggle password visibility"
                >
                  <span class="material-symbols-outlined text-[20px]">visibility</span>
                </button>
              </div>

              <p class="text-sm text-error mt-1 hidden" id="password-error"></p>
              @error('password')
                <p class="text-sm text-error mt-1">{{ $message }}</p>
              @enderror
            </div>

            <!-- Remember Me -->
            <div class="flex items-center space-x-3 ml-1">
              <input
                class="w-5 h-5 rounded border-outline-variant text-primary focus:ring-primary bg-surface-container-highest"
                id="remember"
                name="remember"
                type="checkbox"
                {{ old('remember') ? 'checked' : '' }}
              />
              <label class="text-sm text-on-surface-variant cursor-pointer select-none" for="remember">
                Remember this device
              </label>
            </div>

            <!-- Submit Button -->
            <button
              class="w-full bg-gradient-to-br from-primary to-primary-container text-on-primary font-bold py-4 px-8 rounded-xl shadow-lg shadow-primary/20 hover:opacity-90 transition-all active:scale-[0.98] flex justify-center items-center gap-2"
              type="submit"
              id="login-submit-btn"
            >
              Sign In
              <span class="material-symbols-outlined text-[20px] group-hover:translate-x-1 transition-transform">arrow_forward</span>
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
    </div>
  </main>

  <!-- Shared Footer Navigation -->
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
    $(function () {
      function clearErrors() {
        $('#login-alert').addClass('hidden').text('');
        $('#email-error').addClass('hidden').text('');
        $('#password-error').addClass('hidden').text('');
        $('#login-email, #login-password').removeClass('border-error');
      }

      $(document).on('click', '.eye-toggle', function () {

        console.log('...');
        var targetSelector = $(this).data('target');
        var $input = targetSelector ? $(targetSelector) : $();
        if (!$input.length) return;

        var $icon = $(this).find('.material-symbols-outlined');
        var isPassword = $input.attr('type') === 'password';
        $input.attr('type', isPassword ? 'text' : 'password');
        $icon.text(isPassword ? 'visibility_off' : 'visibility');
      });

      $('#login-form').on('submit', function (e) {
        e.preventDefault();
        clearErrors();

        var $form = $(this);
        var $submitBtn = $('#login-submit-btn');
        var originalBtnHtml = $submitBtn.html();

        $submitBtn.prop('disabled', true).html('Signing in...');

        $.ajax({
          url: $form.attr('action'),
          method: 'POST',
          data: $form.serialize(),
          headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
          },
          success: function(_response, _textStatus, xhr) {
            window.location.href = '{{ authenticated_home_url() }}';
          },
          error: function(xhr) {
            if (xhr.status === 422 && xhr.responseJSON) {
              var errors = xhr.responseJSON.errors || {};

              if (errors.email && errors.email.length) {
                $('#email-error').removeClass('hidden').text(errors.email[0]);
                $('#login-email').addClass('border-error');
              }

              if (errors.password && errors.password.length) {
                $('#password-error').removeClass('hidden').text(errors.password[0]);
                $('#login-password').addClass('border-error');
              }

              if (!errors.email && !errors.password) {
                var fallbackMessage = xhr.responseJSON.message || 'Login failed. Please check your credentials.';
                $('#login-alert').removeClass('hidden').text(fallbackMessage);
              }
            } else {
              $('#login-alert')
                .removeClass('hidden')
                .text('Something went wrong while signing in. Please try again.');
            }

            $submitBtn.prop('disabled', false).html(originalBtnHtml);
          }
        })
      });
    });
  </script>
@endpush
