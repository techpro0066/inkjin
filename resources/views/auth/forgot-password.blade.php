@extends('layouts.inkjin_auth_layout')

@section('title', 'Forgot Password')

@section('content')
  <main class="flex-grow flex items-center justify-center p-6 md:p-12 relative z-10">
    <div class="w-full max-auto max-w-[440px]">
      <!-- Logo Section -->
      <div class="flex justify-center mb-10">
        <img
          alt="Inkjin Logo"
          class="h-16 w-auto object-contain"
          src="{{ asset('design/images/logo-blue.png') }}"
        />
      </div>

      <!-- Card Container -->
      <div class="bg-surface-container-lowest rounded-xl p-8 md:p-10 shadow-[0_32px_64px_-12px_rgba(49,15,122,0.06)] ring-1 ring-outline-variant/15">
        <!-- Header -->
        <div class="text-center mb-8">
          <h1 class="font-headline text-3xl font-extrabold text-on-surface tracking-tight mb-3">Forgot password?</h1>
          <p class="text-on-surface-variant">No worries, we'll send you a link to reset your password.</p>
        </div>

        <!-- Session Status -->
        @if (session('status'))
          <div class="mb-5 rounded-xl bg-primary-container/25 border border-primary-container/40 px-4 py-3 text-sm text-on-surface">
            {{ session('status') }}
          </div>
        @endif

        <form class="space-y-6" method="POST" action="{{ route('password.email') }}">
          @csrf

          <div class="space-y-2">
            <label class="block text-sm font-semibold text-on-surface mb-2" for="reset-email">Email address</label>
            <input
              class="w-full px-4 py-3 rounded-xl border border-outline-variant/30 bg-white focus:ring-2 focus:ring-primary/40 transition-all text-on-surface placeholder:text-outline/50 {{ $errors->has('email') ? 'border border-error' : '' }}"
              id="reset-email"
              name="email"
              placeholder="name@company.com"
              type="email"
              value="{{ old('email') }}"
              autofocus
            />
            @error('email')
              <p class="text-sm text-error mt-1">{{ $message }}</p>
            @enderror
          </div>

          <!-- CTA Button -->
          <button
            class="w-full inline-flex items-center justify-center gap-2 bg-gradient-to-br from-primary to-primary-container text-white font-bold py-3 px-8 rounded-xl shadow-lg shadow-primary/20 hover:opacity-90 transition-all active:scale-[0.98]"
            type="submit"
          >
            <span>Send Reset Link</span>
            <span class="material-symbols-outlined text-lg group-hover:translate-x-1 transition-transform">arrow_forward</span>
          </button>
        </form>

        <!-- Sign in -->
        <div class="mt-8 text-center">
          <a
            class="inline-flex items-center justify-center gap-2 text-primary font-semibold hover:text-primary-container transition-colors group"
            href="{{ route('login') }}"
          >
            <span class="material-symbols-outlined text-lg" data-icon="keyboard_backspace">keyboard_backspace</span>
            <span>Back to Sign in</span>
          </a>
        </div>

        <!-- Secondary Help Text -->
        <p class="mt-8 text-center text-sm text-on-surface-variant">
          Having trouble?
          <a class="text-primary font-medium underline underline-offset-4 decoration-primary/30 hover:decoration-primary" href="#">
            Contact Support
          </a>
        </p>
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
