@php
  $activeProfileTab = $activeProfileTab ?? 'profile';
  $ud = auth()->user()?->userDetail;
  $studioLabel = trim((string) ($ud?->resolvedStudioName() ?? ''));
  $country = trim((string) ($ud?->country ?? ''));
  $dateFmt = trim((string) ($ud?->date_time_format ?? ''));
  $regionalBits = array_values(array_filter([$country !== '' ? $country : null, $dateFmt !== '' ? $dateFmt : null]));
@endphp
<div class="tabs">
  <a href="{{ route('profile.edit') }}" class="{{ $activeProfileTab === 'profile' ? 'on' : '' }}">Profile</a>
  <a href="{{ route('profile.password') }}" class="{{ $activeProfileTab === 'password' ? 'on' : '' }}">Password</a>
  <a href="{{ route('settings.studio') }}" class="{{ $activeProfileTab === 'studio' || request()->routeIs('settings.studio') ? 'on' : '' }}">Studio</a>
  <a href="{{ route('settings.regional') }}" class="{{ $activeProfileTab === 'regional' || request()->routeIs('settings.regional') ? 'on' : '' }}">Regional</a>
  <a href="{{ route('settings.notifications') }}" class="{{ $activeProfileTab === 'notifications' || request()->routeIs('settings.notifications') ? 'on' : '' }}">Notifications</a>
  <a href="{{ route('settings.plans') }}" class="{{ $activeProfileTab === 'plans' || request()->routeIs('settings.plans') ? 'on' : '' }}">Plans</a>
</div>
