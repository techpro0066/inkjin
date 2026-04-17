@extends('layouts.inkjin_auth_layout')

@section('title', 'Register')

@push('styles')
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
  <style>
    .select2-container { width: 100% !important; z-index: 1; }
    .select2-container--open { z-index: 10060 !important; }
    .select2-container--default .select2-selection--single {
      height: 48px;
      border-radius: 0.75rem;
      border-color: rgba(202, 196, 211, 0.5);
      background: #F8F1FB;
      display: flex;
      align-items: center;
      padding-left: 0.5rem;
      padding-right: 0.5rem;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
      line-height: 48px;
      color: #1c1b21;
      font-size: 0.875rem;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow { height: 46px; }
    .select2-container--default.select2-container--focus .select2-selection--single,
    .select2-container--default.select2-container--open .select2-selection--single {
      border-color: rgba(102,77,177,0.45);
      box-shadow: 0 0 0 2px rgba(102,77,177,0.12);
    }
    .select2-dropdown { border-radius: 0.75rem; border-color: rgba(202,196,211,0.5); overflow: hidden; }
    .select2-container--default .select2-results__option--highlighted[aria-selected] { background-color: #310f7a !important; }
    .select2-container--default .select2-search--dropdown .select2-search__field {
      border-radius: 0.5rem;
      border: 1px solid rgba(202,196,211,0.6);
      padding: 0.5rem 0.75rem;
    }
  </style>
@endpush

@section('content')
<main class="w-full flex flex-col">
  <section class="min-h-screen flex items-center justify-center p-6 md:p-12 relative overflow-hidden">
  <!-- Background Monogram Watermark Restored -->
  <div class="absolute inset-0 flex items-center justify-center opacity-[0.03] pointer-events-none select-none">
  <span class="text-[40rem] font-black tracking-tighter text-primary" style="">ij</span>
  </div>
  <div class="max-w-6xl w-full grid grid-cols-1 lg:grid-cols-2 gap-0 overflow-hidden rounded-xl shadow-2xl shadow-primary/5 relative z-10">
  <!-- Branding Column -->
  <div class="hidden lg:flex flex-col justify-between p-16 bg-primary text-on-primary relative overflow-hidden">
  <!-- Abstract Texture Overlay -->
  <div class="absolute inset-0 opacity-10">
  <div class="absolute top-[-10%] right-[-10%] w-96 h-96 rounded-full bg-primary-container blur-[100px]"></div>
  <div class="absolute bottom-[-5%] left-[-5%] w-64 h-64 rounded-full bg-secondary-container blur-[80px]"></div>
  </div>
  <div class="relative z-10">
  <div class="flex flex-col gap-1 mb-12"><span class="text-white text-4xl font-bold tracking-tighter leading-none" style="font-family: 'Space Grotesk', sans-serif;">bookpay</span><span class="text-white/60 text-[10px] uppercase tracking-widest font-medium leading-tight">Tattoo artist platform<br>by Inkjin</span></div>
  <h2 class="text-5xl font-extrabold tracking-tight mb-6 leading-tight" style="font-family: 'Space Grotesk', sans-serif;">Booking and payments, built for tattoo artists.</h2>
  
  <p class="text-lg text-primary-fixed-dim/80 leading-relaxed mb-6">Share one booking link, collect the right details, approve requests, and lock in sessions with deposits — without the endless DM back-and-forth.</p>
  
  <div class="flex flex-wrap gap-3 mb-10">
    <span class="text-xs bg-white/10 text-white px-3 py-1.5 rounded-full flex items-center gap-1.5">✓ 100% free for artists</span>
    <span class="text-xs bg-white/10 text-white px-3 py-1.5 rounded-full flex items-center gap-1.5">✓ Keep 100% of what you earn</span>
    <span class="text-xs bg-white/10 text-white px-3 py-1.5 rounded-full flex items-center gap-1.5">✓ No monthly subscriptions</span>
    <span class="text-xs bg-white/10 text-white px-3 py-1.5 rounded-full flex items-center gap-1.5">✓ Clients pay a €10 booking fee</span>
  </div>
  
  </div>
  <div class="relative z-10">
  <div class="flex items-center gap-4 mb-8">
  <div class="flex -space-x-3">
  <div class="w-10 h-10 rounded-full border-2 border-primary overflow-hidden">
  <img class="w-full h-full object-cover" alt="Artist portrait" src="https://images.unsplash.com/photo-1599566150163-29194dcaad36?ixlib=rb-4.0.3&auto=format&fit=crop&w=100&q=80"></div><div class="w-10 h-10 rounded-full border-2 border-primary overflow-hidden">
  <img class="w-full h-full object-cover" alt="Artist portrait" src="https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?ixlib=rb-4.0.3&auto=format&fit=crop&w=100&q=80">
  </div>
  <div class="w-10 h-10 rounded-full border-2 border-primary overflow-hidden">
  <img class="w-full h-full object-cover" alt="Artist portrait" src="https://images.unsplash.com/photo-1494790108377-be9c29b29330?ixlib=rb-4.0.3&auto=format&fit=crop&w=100&q=80">
  </div>
  </div>
  <span class="text-sm font-medium text-primary-fixed" style="">Trusted by professional tattoo artists</span>
  </div>
  </div>
  </div>
  <!-- Form Column -->
  <div class="bg-surface-container-lowest p-8 md:p-16 flex flex-col justify-center">
  <div class="max-w-md mx-auto w-full">
  <div class="mb-10">
  <div class="lg:hidden mb-8">
  <div class="flex flex-col gap-1"><span class="text-on-surface text-3xl font-bold tracking-tighter leading-none" style="font-family: 'Space Grotesk', sans-serif;">bookpay</span><span class="text-on-surface-variant text-[9px] uppercase tracking-widest font-medium leading-tight">Tattoo artist platform<br>by Inkjin</span></div>
  </div>
  <h1 class="text-3xl font-extrabold text-on-surface tracking-tight mb-2" style="" style="font-family: 'Space Grotesk', sans-serif;">Join bookpay for Artists</h1>
  <p class="text-on-surface-variant" style="">Sign up and simplify your bookings.</p>
  
  
  </div>
  <form class="space-y-6" id="register-form" action="{{ route('register') }}" method="POST">
  @csrf
  <div id="register-alert" class="hidden rounded-xl bg-error-container/40 border border-error-container/60 px-4 py-3 text-sm text-error"></div>
  <div class="space-y-2">
  <label class="block text-sm font-semibold text-on-surface mb-2" for="email" style="">Email Address</label>
  <input class="w-full px-4 py-3 rounded-xl border border-outline-variant/30 bg-white focus:ring-2 focus:ring-primary/40 transition-all text-on-surface placeholder:text-outline/50 " style="background-color:#F8F1FB;" id="signup-email" name="email" placeholder="name@company.com" type="email">
  <p class="text-sm text-error mt-1 hidden" id="signup-email-error"></p>
  </div>
  <div class="space-y-2">
  <label class="block text-sm font-semibold text-on-surface mb-2" for="password" style="">Password</label>
  <div class="relative">
  <input class="w-full px-4 py-3 rounded-xl border border-outline-variant/30 bg-white focus:ring-2 focus:ring-primary/40 transition-all text-on-surface placeholder:text-outline/50 " style="background-color:#F8F1FB;" id="signup-password" name="password" placeholder="••••••••" type="password">
  <button class="absolute right-4 top-1/2 -translate-y-1/2 text-outline-variant hover:text-primary transition-colors eye-toggle" style="" type="button" data-target="#signup-password">
  <span class="material-symbols-outlined text-[20px]" style="">visibility</span>
  </button>
  </div>
  <p class="text-sm text-error mt-1 hidden" id="signup-password-error"></p>
  </div>
  <div class="space-y-2">
  <label class="block text-sm font-semibold text-on-surface mb-2" for="confirm-password" style="">Confirm Password</label>
  <div class="relative">
  <input class="w-full px-4 py-3 rounded-xl border border-outline-variant/30 bg-white focus:ring-2 focus:ring-primary/40 transition-all text-on-surface placeholder:text-outline/50 " style="background-color:#F8F1FB;" id="signup-confirm-password" name="password_confirmation" placeholder="••••••••" type="password">
  <button class="absolute right-4 top-1/2 -translate-y-1/2 text-outline-variant hover:text-primary transition-colors eye-toggle" style="" type="button" data-target="#signup-confirm-password">
  <span class="material-symbols-outlined text-[20px]" style="">visibility</span>
  </button>
  </div>
  <p class="text-sm text-error mt-1 hidden" id="signup-password-confirmation-error"></p>
  </div>
  <div class="space-y-2">
    <label class="text-sm font-semibold text-on-surface-variant ml-1" for="referral_source">How did you hear about us? <span class="text-xs text-on-surface-variant font-normal">(optional)</span></label>
    <select id="referral_source" name="referral_source" class="js-select2 w-full text-sm border border-outline-variant/30 rounded-xl px-4 py-3 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30" style="background-color:#F8F1FB;">
      <option value="">Select...</option>
      <option value="instagram">Instagram</option>
      <option value="tiktok">TikTok</option>
      <option value="google">Google Search</option>
      <option value="friend">Friend / Referral</option>
      <option value="convention">Tattoo Convention</option>
      <option value="blog">Blog / Article</option>
      <option value="other">Other</option>
    </select>
  </div>
  <div class="pt-2">
  <button class="w-full inline-flex items-center justify-center gap-2 bg-gradient-to-br from-primary to-primary-container text-white font-bold py-3 px-8 rounded-xl shadow-lg shadow-primary/20 hover:opacity-90 transition-all active:scale-[0.98]" style="" type="submit" id="signup-submit">
                                  Sign Up
                                  <span class="material-symbols-outlined" style="">arrow_forward</span>
  </button>
  </div>
  </form>
  <div class="mt-8 pt-8 border-t border-outline-variant/10 flex flex-col items-center gap-6">
  <p class="text-on-surface-variant text-sm" style="">
                              Already have an account? 
                              <a class="text-primary font-bold hover:underline underline-offset-4 decoration-2" href="{{ route('login') }}" style="">Sign in</a>
  </p>
  <div class="flex items-center gap-6 text-outline-variant">
  <div class="h-[1px] w-12 bg-outline-variant/30"></div>
  <div class="h-[1px] w-12 bg-outline-variant/30"></div>
  </div>
  <div class="flex gap-4">
  </div>
  </div>
  </div>
  </div>
  </section>
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
@endsection

@push('scripts')
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
  <script>
    $(function () {
      if (window.jQuery && $.fn.select2) {
        $('#referral_source.js-select2').select2({
          width: '100%',
          dropdownParent: $('body'),
          placeholder: 'Select...'
        });
      }

      function clearErrors() {
        $('#register-alert').addClass('hidden').text('');
        $('#signup-email-error').addClass('hidden').text('');
        $('#signup-password-error').addClass('hidden').text('');
        $('#signup-password-confirmation-error').addClass('hidden').text('');
        $('#signup-email, #signup-password, #signup-confirm-password').removeClass('border-error');
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

      $('#register-form').on('submit', function (e) {
        e.preventDefault();
        clearErrors();

        var $form = $(this);
        var $submitBtn = $('#signup-submit');
        var originalBtnHtml = $submitBtn.html();

        $submitBtn.prop('disabled', true).html('Signing up...');

        $.ajax({
          url: $form.attr('action'),
          method: 'POST',
          data: $form.serialize(),
          headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
          }
        }).done(function (_response, _textStatus, xhr) {
          window.location.href = xhr.responseURL || '{{ route('verification.notice') }}';
        }).fail(function (xhr) {
          if (xhr.status === 422 && xhr.responseJSON) {
            var errors = xhr.responseJSON.errors || {};

            if (errors.email && errors.email.length) {
              $('#signup-email-error').removeClass('hidden').text(errors.email[0]);
              $('#signup-email').addClass('border-error');
            }

            if (errors.password && errors.password.length) {
              $('#signup-password-error').removeClass('hidden').text(errors.password[0]);
              $('#signup-password').addClass('border-error');
            }

            if (errors.password_confirmation && errors.password_confirmation.length) {
              $('#signup-password-confirmation-error').removeClass('hidden').text(errors.password_confirmation[0]);
              $('#signup-confirm-password').addClass('border-error');
            }

            if (!errors.email && !errors.password && !errors.password_confirmation) {
              var fallbackMessage = xhr.responseJSON.message || 'Registration failed. Please check your details.';
              $('#register-alert').removeClass('hidden').text(fallbackMessage);
            }
          } else {
            $('#register-alert')
              .removeClass('hidden')
              .text('Something went wrong while signing up. Please try again.');
          }
        }).always(function () {
          $submitBtn.prop('disabled', false).html(originalBtnHtml);
        });
      });
    });
  </script>
@endpush
