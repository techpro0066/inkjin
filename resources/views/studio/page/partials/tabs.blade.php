@php
  $pageTab = $pageTab ?? 'about';
@endphp
<div class="tabs">
  <a href="{{ route('studio.page.about') }}" class="{{ $pageTab === 'about' ? 'on' : '' }}">About</a>
  <a href="{{ route('studio.page.artists') }}" class="{{ $pageTab === 'artists' ? 'on' : '' }}">Artists</a>
  <a href="{{ route('studio.page.portfolio') }}" class="{{ $pageTab === 'portfolio' ? 'on' : '' }}">Portfolio</a>
  <a href="{{ route('studio.page.look') }}" class="{{ $pageTab === 'look' ? 'on' : '' }}">Appearance</a>
</div>
