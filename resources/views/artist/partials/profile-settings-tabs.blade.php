@php
  $activeProfileTab = $activeProfileTab ?? 'profile';
  $profileSubTabClass = fn (string $key) => $activeProfileTab === $key
      ? 'px-4 py-2 text-sm font-semibold rounded-xl bg-primary text-on-primary transition-all'
      : 'px-4 py-2 text-sm font-semibold rounded-xl text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface transition-all';
@endphp
<div class="flex items-center gap-2 mb-6">
  <a href="{{ route('profile.edit') }}" class="{{ $profileSubTabClass('profile') }}">Profile</a>
  <a href="{{ route('profile.password') }}" class="{{ $profileSubTabClass('password') }}">Password</a>
</div>
