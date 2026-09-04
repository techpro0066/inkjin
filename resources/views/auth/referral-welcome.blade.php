@extends('layouts.inkjin_auth_layout')

@section('title', 'Welcome to Bookpay | Referred signup')
@section('meta_description', 'You’ve been referred to Bookpay. Sign up and get your first booking fee waived.')
@section('robots', 'noindex, follow')

@section('content')
@php
  $referrer = $referrer ?? null;
  $firstName = trim((string) ($referrer?->first_name ?? ''));
  $lastName = trim((string) ($referrer?->last_name ?? ''));
  $username = trim((string) ($referrerUsername ?? ''));

  if ($firstName !== '' && $lastName !== '') {
    $displayName = $firstName.' '.strtoupper(mb_substr($lastName, 0, 1)).'.';
  } elseif ($firstName !== '') {
    $displayName = $firstName;
  } elseif ($username !== '') {
    $displayName = '@'.$username;
  } else {
    $displayName = 'an artist';
  }

  $initials = '';
  if ($firstName !== '') {
    $initials .= strtoupper(mb_substr($firstName, 0, 1));
  }
  if ($lastName !== '') {
    $initials .= strtoupper(mb_substr($lastName, 0, 1));
  }
  if ($initials === '' && $username !== '') {
    $initials = strtoupper(mb_substr($username, 0, 2));
  }
  if ($initials === '') {
    $initials = 'BP';
  }

  $avatarPath = trim((string) ($referrer?->userDetail?->avatar ?? ''));
  $instagramPicture = trim((string) ($referrer?->userDetail?->instagram_profile_picture ?? ''));
  $avatarUrl = null;
  if ($avatarPath !== '') {
    $avatarUrl = asset($avatarPath);
  } elseif ($instagramPicture !== '') {
    $avatarUrl = str_starts_with($instagramPicture, 'http://') || str_starts_with($instagramPicture, 'https://')
      ? $instagramPicture
      : asset($instagramPicture);
  }

  $signupUrl = route('register', ['username' => $username, 'signup' => 1]);
@endphp

<main class="w-full min-h-screen flex items-center justify-center p-6 md:p-10 bg-background">
  <div class="w-full max-w-md bg-white rounded-2xl shadow-xl shadow-primary/5 px-8 py-10 sm:px-10 sm:py-12 text-center">
    <div class="mx-auto mb-5 w-16 h-16 rounded-full bg-surface-container-high flex items-center justify-center overflow-hidden">
      @if($avatarUrl)
        <img src="{{ $avatarUrl }}" alt="{{ $displayName }}" class="w-full h-full object-cover">
      @else
        <span class="text-xl font-extrabold text-on-surface tracking-tight">{{ $initials }}</span>
      @endif
    </div>

    <p class="text-sm text-on-surface-variant mb-1">Referred by</p>
    <p class="text-base font-bold text-on-surface mb-6">{{ $displayName }}</p>

    <h1 class="text-3xl font-extrabold text-on-surface tracking-tight mb-3" style="font-family: 'Space Grotesk', sans-serif;">
      Welcome to Bookpay
    </h1>
    <p class="text-sm text-on-surface-variant leading-relaxed mb-6">
      Your first booking’s client fee is on us — a $10 credit, automatically applied when you sign up.
    </p>

    <div class="rounded-xl bg-surface-container-low px-4 py-3 mb-8">
      <p class="text-sm font-bold text-on-surface">$10 first booking fee waived</p>
    </div>

    <a
      href="{{ $signupUrl }}"
      class="block w-full rounded-xl bg-[#1a1a1a] text-white font-bold py-3.5 px-6 hover:opacity-90 transition-opacity"
    >
      Sign up
    </a>

    <p class="mt-5 text-sm text-on-surface-variant">
      Already have an account?
      <a href="{{ route('login') }}" class="font-bold text-on-surface hover:underline underline-offset-2">Log in</a>
    </p>
  </div>
</main>
@endsection
