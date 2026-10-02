@php
  $accountTab = $accountTab ?? 'profile';
@endphp
<div class="tabs">
  <a href="{{ route('studio.account.profile') }}" class="{{ $accountTab === 'profile' ? 'on' : '' }}">Profile</a>
  <a href="{{ route('studio.account.password') }}" class="{{ $accountTab === 'password' ? 'on' : '' }}">Password</a>
  <a href="{{ route('studio.account.studio') }}" class="{{ $accountTab === 'studio' ? 'on' : '' }}">Studio details</a>
  <a href="{{ route('studio.account.regional') }}" class="{{ $accountTab === 'regional' ? 'on' : '' }}">Regional</a>
</div>
