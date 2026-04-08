@php
  $isSettingsOpen = request()->routeIs('profile.edit') || request()->routeIs('settings.*');
@endphp

<aside class="sidebar hidden lg:flex fixed top-0 left-0 h-screen overflow-y-auto bg-primary flex-col justify-between p-6 z-40" id="mobileSidebar">
  <div>
    <div class="mb-10">
      <h1 class="text-white text-xl font-bold">Inkjin</h1>
      <p class="text-white/50 text-[10px] uppercase tracking-[2px] mt-1">Book & Pay</p>
    </div>

    <nav class="flex flex-col gap-1">
      <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
        <span class="material-symbols-outlined">home</span> Home
      </a>
      <a href="{{ route('bookings.index') }}" class="nav-item {{ request()->routeIs('bookings.*') ? 'active' : '' }}">
        <span class="material-symbols-outlined">calendar_month</span> Bookings
      </a>
      <a href="{{ route('availability.index') }}" class="nav-item {{ request()->routeIs('availability.*') ? 'active' : '' }}">
        <span class="material-symbols-outlined">schedule</span> Availability
      </a>
      <a href="{{ route('questions.index') }}" class="nav-item {{ request()->routeIs('questions.*') ? 'active' : '' }}">
        <span class="material-symbols-outlined">quiz</span> Questions
      </a>

      <div>
        <a href="#" class="nav-item {{ $isSettingsOpen ? 'active' : '' }}" onclick="var s=document.getElementById('settingsSub');var a=document.getElementById('settingsArrow');s.classList.toggle('open');a.classList.toggle('rotated');return false;">
          <span class="material-symbols-outlined">settings</span> Settings
          <span id="settingsArrow" class="material-symbols-outlined content-arrow text-sm ml-auto {{ $isSettingsOpen ? 'rotated' : '' }}">expand_more</span>
        </a>
        <div id="settingsSub" class="sub-menu {{ $isSettingsOpen ? 'open' : '' }}">
          <a href="{{ route('profile.edit') }}" class="sub-menu-item {{ request()->routeIs('profile.edit') ? 'active' : '' }}">
            <span class="material-symbols-outlined">person</span> Profile
          </a>
          <a href="{{ route('settings.styles') }}" class="sub-menu-item {{ request()->routeIs('settings.styles') ? 'active' : '' }}">
            <span class="material-symbols-outlined">palette</span> Styles
          </a>
          <a href="{{ route('settings.studio') }}" class="sub-menu-item {{ request()->routeIs('settings.studio') ? 'active' : '' }}">
            <span class="material-symbols-outlined">storefront</span> Studio
          </a>
          <a href="{{ route('settings.preferences') }}" class="sub-menu-item {{ request()->routeIs('settings.preferences') ? 'active' : '' }}">
            <span class="material-symbols-outlined">tune</span> Preferences
          </a>
          <a href="{{ route('settings.calendar') }}" class="sub-menu-item {{ request()->routeIs('settings.calendar') ? 'active' : '' }}">
            <span class="material-symbols-outlined">calendar_today</span> Calendar
          </a>
          <a href="{{ route('settings.payment') }}" class="sub-menu-item {{ request()->routeIs('settings.payment') ? 'active' : '' }}">
            <span class="material-symbols-outlined">payments</span> Payments
          </a>
        </div>
      </div>
    </nav>
  </div>

  <div>
    <div class="border-t border-white/10 pt-4 mt-4">
      <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="nav-item text-white/60 hover:text-white">
          <span class="material-symbols-outlined">logout</span> Log Out
        </button>
      </form>
    </div>
    <div class="flex items-center gap-3 mt-4 pt-4 border-t border-white/10">
      <div class="w-10 h-10 rounded-full bg-primary-fixed-dim flex items-center justify-center text-primary font-bold text-sm overflow-hidden">
        @if(auth()->user()->userDetail?->avatar)
          <img src="{{ asset(auth()->user()->userDetail->avatar) }}" alt="User Image" class="w-full h-full object-cover rounded-full">
        @endif
      </div>
      <div>
        <div class="text-white text-sm font-semibold">{{ auth()->user()->first_name }} {{ auth()->user()->last_name }}</div>
        <div class="text-white/50 text-xs">{{ auth()->user()->email }}</div>
      </div>
    </div>
  </div>
</aside>