@extends('layouts.inkjin_auth_layout')

@section('title', 'Reset Password')

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
            <h1 class="text-3xl font-extrabold text-on-surface tracking-tight mb-2" style="font-family: 'Space Grotesk', sans-serif;">
              Reset Password
            </h1>
            <p class="text-on-surface-variant">
              Set a new password for your account below.
            </p>
          </div>

          <div id="reset-success" class="hidden mb-5 rounded-xl bg-primary-container/25 border border-primary-container/40 px-4 py-3 text-sm text-on-surface"></div>
          <div id="reset-error" class="hidden mb-5 rounded-xl bg-error-container/40 border border-error-container/60 px-4 py-3 text-sm text-error"></div>

          <form id="reset-password-form" class="space-y-6" method="POST" action="{{ route('password.store') }}">
        @csrf

        <!-- Password Reset Token -->
            <input type="hidden" name="token" value="{{ $request->route('token') }}">

            <div class="space-y-2">
              <label for="email" class="block text-sm font-semibold text-on-surface mb-2">Email Address</label>
              <input
                type="email"
                class="w-full px-4 py-3 rounded-xl border border-outline-variant/30 bg-gray-100 focus:ring-2 focus:ring-primary/40 transition-all text-on-surface placeholder:text-outline/50 @error('email') border-error @enderror"
                id="email"
                name="email"
                value="{{ old('email', $request->email) }}"
                placeholder="name@company.com"
                autofocus
                autocomplete="username"
                readonly
              />
              <p class="text-sm text-error mt-1 hidden" id="email-error"></p>
              @error('email')
                <p class="text-sm text-error mt-1">{{ $message }}</p>
              @enderror
            </div>

            <div class="space-y-2">
              <label class="block text-sm font-semibold text-on-surface mb-2" for="password">New Password</label>
              <div class="relative">
                <input
                  type="password"
                  id="password"
                  class="w-full px-4 py-3 rounded-xl border border-outline-variant/30 bg-white focus:ring-2 focus:ring-primary/40 transition-all text-on-surface placeholder:text-outline/50 @error('password') border-error @enderror"
                  name="password"
                  placeholder="••••••••"
                  autocomplete="new-password"
                />
                <button
                  class="absolute right-4 top-1/2 -translate-y-1/2 text-outline-variant hover:text-on-surface-variant eye-toggle"
                  type="button"
                  data-target="#password"
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

            <div class="space-y-2">
              <label class="block text-sm font-semibold text-on-surface mb-2" for="password_confirmation">Confirm Password</label>
              <div class="relative">
                <input
                  type="password"
                  id="password_confirmation"
                  class="w-full px-4 py-3 rounded-xl border border-outline-variant/30 bg-white focus:ring-2 focus:ring-primary/40 transition-all text-on-surface placeholder:text-outline/50"
                  name="password_confirmation"
                  placeholder="••••••••"
                  autocomplete="new-password"
                />
                <button
                  class="absolute right-4 top-1/2 -translate-y-1/2 text-outline-variant hover:text-on-surface-variant eye-toggle"
                  type="button"
                  data-target="#password_confirmation"
                  aria-label="Toggle confirm password visibility"
                >
                  <span class="material-symbols-outlined text-[20px]">visibility</span>
                </button>
              </div>
              <p class="text-sm text-error mt-1 hidden" id="password-confirmation-error"></p>
            </div>

            <div class="pt-2">
              <button
                class="w-full inline-flex items-center justify-center gap-2 bg-gradient-to-br from-primary to-primary-container text-white font-bold py-3 px-8 rounded-xl shadow-lg shadow-primary/20 hover:opacity-90 transition-all active:scale-[0.98]"
                type="submit"
                id="reset-password-submit"
              >
                <span>Reset Password</span>
                <span class="material-symbols-outlined text-lg">arrow_forward</span>
              </button>
            </div>
          </form>

          <div class="mt-8 text-center">
            <a
              href="{{ route('login') }}"
              class="inline-flex items-center justify-center gap-2 text-primary font-semibold hover:text-primary-container transition-colors"
            >
              <span class="material-symbols-outlined text-lg">keyboard_backspace</span>
              <span>Back to Sign in</span>
            </a>
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
    $(function () {
      function clearErrors() {
        $('#reset-success, #reset-error').addClass('hidden').text('');
        $('#email-error, #password-error, #password-confirmation-error').addClass('hidden').text('');
        $('#email, #password, #password_confirmation').removeClass('border-error');
      }

      $(document).on('click', '.eye-toggle', function () {
        var targetSelector = $(this).data('target');
        var $input = targetSelector ? $(targetSelector) : $();
        if (!$input.length) return;

        var $icon = $(this).find('.material-symbols-outlined');
        var isPassword = $input.attr('type') === 'password';
        $input.attr('type', isPassword ? 'text' : 'password');
        $icon.text(isPassword ? 'visibility_off' : 'visibility');
      });

      $('#reset-password-form').on('submit', function (e) {
        e.preventDefault();
        clearErrors();

        var $form = $(this);
        var $submitBtn = $('#reset-password-submit');
        var originalBtnHtml = $submitBtn.html();

        $submitBtn.prop('disabled', true).html('<span>Updating...</span>');

        $.ajax({
          url: $form.attr('action'),
          method: 'POST',
          data: $form.serialize(),
          headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
          }
        }).done(function (response) {
          var successMessage = (response && response.message)
            ? response.message
            : 'Password reset successfully.';

          $('#reset-success').removeClass('hidden').text(successMessage);

          setTimeout(function () {
            window.location.href = (response && response.redirect) ? response.redirect : '{{ route('login') }}';
          }, 600);
        }).fail(function (xhr) {
          if (xhr.status === 422 && xhr.responseJSON) {
            var errors = xhr.responseJSON.errors || {};

            if (errors.email && errors.email.length) {
              $('#email-error').removeClass('hidden').text(errors.email[0]);
              $('#email').addClass('border-error');
            }

            if (errors.password && errors.password.length) {
              $('#password-error').removeClass('hidden').text(errors.password[0]);
              $('#password').addClass('border-error');
            }

            if (errors.password_confirmation && errors.password_confirmation.length) {
              $('#password-confirmation-error').removeClass('hidden').text(errors.password_confirmation[0]);
              $('#password_confirmation').addClass('border-error');
            }

            if (!errors.email && !errors.password && !errors.password_confirmation) {
              var fallbackMessage = xhr.responseJSON.message || 'Unable to reset password. Please try again.';
              $('#reset-error').removeClass('hidden').text(fallbackMessage);
            }
          } else {
            $('#reset-error').removeClass('hidden').text('Something went wrong. Please try again.');
          }
        }).always(function () {
          $submitBtn.prop('disabled', false).html(originalBtnHtml);
        });
      });
    });
  </script>
@endpush
