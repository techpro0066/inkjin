@extends('layouts.inkjin_auth_layout')

@section('title', 'Register')

@section('content')
  <main class="flex-grow flex items-center justify-center p-6 md:p-12 relative overflow-hidden">
    {{-- Background Monogram Watermark Restored --}}
    <div class="absolute inset-0 flex items-center justify-center opacity-[0.03] pointer-events-none select-none">
      <span class="text-[40rem] font-black tracking-tighter text-primary">ij</span>
    </div>

    <div class="max-w-6xl w-full grid grid-cols-1 lg:grid-cols-2 gap-0 overflow-hidden rounded-xl shadow-2xl shadow-primary/5 relative z-10">
      {{-- Branding Column --}}
      <div class="hidden lg:flex flex-col justify-between p-16 bg-primary text-on-primary relative overflow-hidden">
        <div class="absolute inset-0 opacity-10">
          <div class="absolute top-[-10%] right-[-10%] w-96 h-96 rounded-full bg-primary-container blur-[100px]"></div>
          <div class="absolute bottom-[-5%] left-[-5%] w-64 h-64 rounded-full bg-secondary-container blur-[80px]"></div>
        </div>

        <div class="relative z-10">
          <img
            alt="Inkjin Brand Mark"
            class="h-10 w-auto mb-12"
            src="{{ asset('design/images/logo-white.webp') }}"
          />

          <h2 class="text-5xl font-extrabold tracking-tight mb-6 leading-tight">Inkjin turns DMs into booked and paid sessions.</h2>
          <p class="text-lg text-primary-fixed-dim/80 max-w-sm leading-relaxed"></p>

          <div class="mt-10">
            <span class="font-extrabold text-primary-fixed-dim/90">100% free for artists</span>&nbsp;—&nbsp;no hidden fees or percentages taken.
            <div class="mt-4">
              - <b>Personal booking page and link</b> — share it anywhere on your bios and social.
              <div class="mt-2">- <b>Custom booking form</b> — collect exactly the info you need.</div>
              <div class="mt-2">- <b>Accept payments</b> — debit/credit card, Apple Pay, Google Pay, or installments.</div>
              <div class="mt-2">- <b>Built-in chat</b> — unlimited text and email notifications to clients.</div>
            </div>
          </div>
        </div>

        <div class="relative z-10">
          <div class="flex items-center gap-4 mb-8">
            <div class="flex -space-x-3">
              <div class="w-10 h-10 rounded-full border-2 border-primary overflow-hidden">
                <img class="w-full h-full object-cover" src="{{ asset('design/images/fontas_cast.jpeg') }}" alt="Artist portrait 1" />
              </div>
              <div class="w-10 h-10 rounded-full border-2 border-primary overflow-hidden">
                <img class="w-full h-full object-cover" src="{{ asset('design/images/romanos.jpeg') }}" alt="Artist portrait 2" />
              </div>
              <div class="w-10 h-10 rounded-full border-2 border-primary overflow-hidden">
                <img class="w-full h-full object-cover" src="{{ asset('design/images/vasso_lowbrow.jpeg') }}" alt="Artist portrait 3" />
              </div>
            </div>
            <span class="text-sm font-medium text-primary-fixed">Trusted by professional tattoo artists</span>
          </div>
        </div>
      </div>

      {{-- Form Column --}}
      <div class="bg-surface-container-lowest p-8 md:p-16 flex flex-col justify-center">
        <div class="max-w-md mx-auto w-full">
          <div class="mb-10">
            <div class="lg:hidden mb-8">
              <img alt="Inkjin Brand Mark" class="h-8 w-auto" src="{{ asset('design/images/logo-blue.png') }}" />
            </div>
            <h1 class="text-3xl font-extrabold text-on-surface tracking-tight mb-2">Join Inkjin for Artists</h1>
            <p class="text-on-surface-variant">Sign up and simplify your bookings.</p>
          </div>

          <form class="space-y-6" method="POST" action="{{ route('register') }}">
            @csrf

            <!-- Email -->
            <div class="space-y-2">
              <label class="block text-sm font-semibold text-on-surface mb-2" for="signup-email">Email Address</label>
              <input
                class="w-full px-4 py-3 rounded-xl border border-outline-variant/30 bg-white focus:ring-2 focus:ring-primary/40 transition-all text-on-surface placeholder:text-outline/50 {{ $errors->has('email') ? 'border border-error' : '' }}"
                id="signup-email"
                name="email"
                placeholder="name@company.com"
                type="email"
                value="{{ old('email') }}"
              />
              @error('email')
                <p class="text-sm text-error">{{ $message }}</p>
              @enderror
            </div>

            <!-- Role -->
            <div class="space-y-2">
              <label class="block text-sm font-semibold text-on-surface mb-2" for="signup-role">Role</label>
              <div class="relative">
                <select
                  class="w-full px-4 py-3 rounded-xl border border-outline-variant/30 bg-white focus:ring-2 focus:ring-primary/40 transition-all text-on-surface placeholder:text-outline/50 appearance-none pr-10 {{ $errors->has('role') ? 'border border-error' : '' }}"
                  id="signup-role"
                  name="role"
                >
                  <option value="" disabled {{ old('role') ? '' : 'selected' }}>Select Role</option>
                  <option value="user" {{ old('role') === 'user' ? 'selected' : '' }}>User</option>
                  <option value="artist" {{ old('role') === 'artist' ? 'selected' : '' }}>Artist</option>
                </select>
                {{-- <span class="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 text-on-surface-variant">▾</span> --}}
              </div>
              @error('role')
                <p class="text-sm text-error">{{ $message }}</p>
              @enderror
            </div>

            <!-- Password -->
            <div class="space-y-2">
              <label class="block text-sm font-semibold text-on-surface mb-2" for="signup-password">Password</label>
              <div class="relative">
                <input
                  class="w-full px-4 py-3 rounded-xl border border-outline-variant/30 bg-white focus:ring-2 focus:ring-primary/40 transition-all text-on-surface placeholder:text-outline/50 {{ $errors->has('password') ? 'border border-error' : '' }}"
                  id="signup-password"
                  name="password"
                  placeholder="••••••••"
                  type="password"
                />
                <button
                  class="absolute right-4 top-1/2 -translate-y-1/2 text-outline-variant hover:text-primary transition-colors eye-toggle"
                  data-target="#signup-password"
                  type="button"
                  aria-label="Toggle password visibility"
                >
                  <span class="material-symbols-outlined text-[20px]">visibility</span>
                </button>
              </div>
              @error('password')
                <p class="text-sm text-error">{{ $message }}</p>
              @enderror
            </div>

            <!-- Confirm Password -->
            <div class="space-y-2">
              <label class="block text-sm font-semibold text-on-surface mb-2" for="signup-confirm-password">Confirm Password</label>
              <div class="relative">
                <input
                  class="w-full px-4 py-3 rounded-xl border border-outline-variant/30 bg-white focus:ring-2 focus:ring-primary/40 transition-all text-on-surface placeholder:text-outline/50 {{ $errors->has('password_confirmation') ? 'border border-error' : '' }}"
                  id="signup-confirm-password"
                  name="password_confirmation"
                  placeholder="••••••••"
                  type="password"
                />
                <button
                  class="absolute right-4 top-1/2 -translate-y-1/2 text-outline-variant hover:text-primary transition-colors eye-toggle"
                  data-target="#signup-confirm-password"
                  type="button"
                  aria-label="Toggle confirm password visibility"
                >
                  <span class="material-symbols-outlined text-[20px]">visibility</span>
                </button>
              </div>
              @error('password_confirmation')
                <p class="text-sm text-error">{{ $message }}</p>
              @enderror
            </div>

            <div class="pt-2">
              <button
                class="w-full inline-flex items-center justify-center gap-2 bg-gradient-to-br from-primary to-primary-container text-white font-bold py-3 px-8 rounded-xl shadow-lg shadow-primary/20 hover:opacity-90 transition-all active:scale-[0.98]"
                type="submit"
              >
                Sign Up
                <span class="material-symbols-outlined">arrow_forward</span>
              </button>
            </div>
          </form>

          <div class="mt-8 pt-8 border-t border-outline-variant/10 flex flex-col items-center gap-6">
            <p class="text-on-surface-variant text-sm">
              Already have an account?
              <a class="text-primary font-bold hover:underline underline-offset-4 decoration-2" href="{{ route('login') }}">
                Sign in
              </a>
            </p>
          </div>
        </div>
      </div>
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
@endsection

@push('scripts')
  <script>
    (function () {
      function toggleFor(button) {
        var targetSelector = button.getAttribute('data-target');
        var input = targetSelector ? document.querySelector(targetSelector) : null;
        if (!input) return;

        var icon = button.querySelector('.material-symbols-outlined');
        if (!icon) return;

        var isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';
        icon.textContent = isPassword ? 'visibility_off' : 'visibility';
      }

      document.addEventListener('click', function (e) {
        var btn = e.target.closest('.eye-toggle');
        if (!btn) return;
        toggleFor(btn);
      });
    })();
  </script>
@endpush
