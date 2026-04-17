@extends('layouts.inkjin_auth_layout')

@section('title', 'Register')

@section('content')
<body class="bg-surface text-on-surface min-h-screen flex flex-col">
  <!-- Top Navigation Suppression: Linear Journey (Sign Up) -->
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
  <div id="register-status-message" class="mb-6 rounded-xl border border-primary/10 bg-primary/5 px-4 py-3 text-sm text-on-surface hidden"></div>

  <form action="{{ route('register') }}" class="space-y-6" method="POST" id="register-form" novalidate>
  @csrf
  <div class="space-y-2">
  <label class="block text-sm font-semibold text-on-surface mb-2" for="signup-email" style="">Email Address</label>
  <input class="form-input @error('email') border-red-500 @enderror" style="background-color:#F8F1FB;" id="signup-email" name="email" placeholder="name@company.com" type="email" value="{{ old('email') }}" autocomplete="username" autofocus required data-error-key="email">
  <p class="text-xs text-red-500 mt-1 @error('email') @else hidden @enderror" data-error-for="email">@error('email'){{ $message }}@enderror</p>
  </div>
  <div class="space-y-2">
  <label class="block text-sm font-semibold text-on-surface mb-2" for="signup-password" style="">Password</label>
  <div class="relative">
  <input class="form-input @error('password') border-red-500 @enderror" style="background-color:#F8F1FB;" id="signup-password" name="password" placeholder="••••••••" type="password" autocomplete="new-password" required data-error-key="password">
  <button class="absolute right-4 top-1/2 -translate-y-1/2 text-outline-variant hover:text-primary transition-colors" style="" type="button" onclick="togglePassword(this)">
  <span class="material-symbols-outlined text-[20px]" style="">visibility</span>
  </button>
  </div>
  <p class="text-xs text-red-500 mt-1 @error('password') @else hidden @enderror" data-error-for="password">@error('password'){{ $message }}@enderror</p>
  </div>
  <div class="space-y-2">
  <label class="block text-sm font-semibold text-on-surface mb-2" for="signup-confirm-password" style="">Confirm Password</label>
  <div class="relative">
  <input class="form-input @error('password') border-red-500 @enderror" style="background-color:#F8F1FB;" id="signup-confirm-password" name="password_confirmation" placeholder="••••••••" type="password" autocomplete="new-password" required data-error-key="password_confirmation">
  <button class="absolute right-4 top-1/2 -translate-y-1/2 text-outline-variant hover:text-primary transition-colors" type="button" onclick="togglePassword(this)">
  <span class="material-symbols-outlined text-[20px]">visibility</span>
  </button>
  </div>
  <p class="text-xs text-red-500 mt-1 hidden" data-error-for="password_confirmation"></p>
  </div>
  <div class="space-y-2">
    <label class="text-sm font-semibold text-on-surface-variant ml-1" for="referral_source">How did you hear about us? <span class="text-xs text-on-surface-variant font-normal">(optional)</span></label>
    <select id="referral_source" name="referral_source" class="w-full text-sm border border-outline-variant/30 rounded-xl px-4 py-3 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30" style="background-color:#F8F1FB;">
      <option value="">Select...</option>
      <option value="instagram" {{ old('referral_source') === 'instagram' ? 'selected' : '' }}>Instagram</option>
      <option value="tiktok" {{ old('referral_source') === 'tiktok' ? 'selected' : '' }}>TikTok</option>
      <option value="google" {{ old('referral_source') === 'google' ? 'selected' : '' }}>Google Search</option>
      <option value="friend" {{ old('referral_source') === 'friend' ? 'selected' : '' }}>Friend / Referral</option>
      <option value="convention" {{ old('referral_source') === 'convention' ? 'selected' : '' }}>Tattoo Convention</option>
      <option value="blog" {{ old('referral_source') === 'blog' ? 'selected' : '' }}>Blog / Article</option>
      <option value="other" {{ old('referral_source') === 'other' ? 'selected' : '' }}>Other</option>
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

  const registerForm = document.getElementById('register-form');
  const registerSubmitButton = document.getElementById('signup-submit');
  const registerStatusMessage = document.getElementById('register-status-message');

  function setRegisterFieldError(fieldName, message) {
    const input = registerForm.querySelector(`[data-error-key="${fieldName}"]`);
    const errorEl = registerForm.querySelector(`[data-error-for="${fieldName}"]`);

    if (input) {
      input.classList.add('border-red-500');
    }

    if (errorEl) {
      errorEl.textContent = message;
      errorEl.classList.remove('hidden');
    }
  }

  function clearRegisterErrors() {
    registerForm.querySelectorAll('[data-error-key]').forEach((input) => {
      input.classList.remove('border-red-500');
    });

    registerForm.querySelectorAll('[data-error-for]').forEach((errorEl) => {
      errorEl.textContent = '';
      errorEl.classList.add('hidden');
    });

    if (registerStatusMessage) {
      registerStatusMessage.textContent = '';
      registerStatusMessage.classList.add('hidden');
    }
  }

  registerForm.querySelectorAll('[data-error-key]').forEach((input) => {
    input.addEventListener('input', () => {
      const errorEl = registerForm.querySelector(`[data-error-for="${input.dataset.errorKey}"]`);
      input.classList.remove('border-red-500');

      if (errorEl) {
        errorEl.textContent = '';
        errorEl.classList.add('hidden');
      }
    });
  });

  registerForm.addEventListener('submit', async function (e) {
    e.preventDefault();
    clearRegisterErrors();
    registerSubmitButton.disabled = true;
    registerSubmitButton.classList.add('opacity-70', 'cursor-not-allowed');

    try {
      const response = await fetch(registerForm.action, {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: new FormData(registerForm),
        credentials: 'same-origin',
      });

      const data = await response.json();

      if (!response.ok) {
        if (response.status === 422 && data.errors) {
          const firstField = Object.keys(data.errors)[0];

          Object.entries(data.errors).forEach(([field, messages]) => {
            setRegisterFieldError(field, messages[0]);
          });

          const firstInput = registerForm.querySelector(`[data-error-key="${firstField}"]`);
          if (firstInput) {
            firstInput.focus();
          }
          return;
        }

        if (registerStatusMessage) {
          registerStatusMessage.textContent = data.message || 'Unable to create your account right now. Please try again.';
          registerStatusMessage.classList.remove('hidden');
        }
        return;
      }

      if (data.redirect) {
        window.location.href = data.redirect;
      }
    } catch (error) {
      if (registerStatusMessage) {
        registerStatusMessage.textContent = 'A network error occurred. Please try again.';
        registerStatusMessage.classList.remove('hidden');
      }
    } finally {
      registerSubmitButton.disabled = false;
      registerSubmitButton.classList.remove('opacity-70', 'cursor-not-allowed');
    }
  });
  
  </script>
</body>
@endsection