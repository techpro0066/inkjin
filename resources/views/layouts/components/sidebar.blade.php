<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
  <div class="app-brand demo">
    <a href="{{ route('dashboard') }}" class="app-brand-link">
      <span class="app-brand-logo demo">
        <img src="{{ asset('assets/img/branding/logo.png') }}" alt="{{ config('app.name', 'Inkjin') }}" width="32" height="32" />
      </span>
      <span class="app-brand-text demo menu-text fw-bold">{{ config('app.name', 'Inkjin') }}</span>
    </a>

    <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
      <i class="ti menu-toggle-icon d-none d-xl-block ti-sm align-middle"></i>
      <i class="ti ti-x d-block d-xl-none ti-sm align-middle"></i>
    </a>
  </div>

  <div class="menu-inner-shadow"></div>

  <ul class="menu-inner py-1">
    <!-- Dashboard -->
    <li class="menu-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
      <a href="{{ route('dashboard') }}" class="menu-link">
        <i class="menu-icon tf-icons ti ti-smart-home"></i>
        <div data-i18n="Dashboard">Dashboard</div>
      </a>
    </li>

    @auth
      <!-- Settings -->
      <li class="menu-item {{ request()->routeIs('profile.edit') || request()->routeIs('settings.*') || request()->routeIs('password.update') || request()->routeIs('preferences.*') ? 'active open' : '' }}">
          <a href="javascript:void(0);" class="menu-link menu-toggle">
            <i class="menu-icon tf-icons ti ti-settings"></i>
            <div data-i18n="Settings">Settings</div>
          </a>
          <ul class="menu-sub">
            <li class="menu-item {{ request()->routeIs('profile.edit') ? 'active' : '' }}">
              <a href="{{ route('profile.edit') }}" class="menu-link">
                <i class="menu-icon tf-icons ti ti-user me-2"></i>
                <div data-i18n="Update Profile">Update Profile</div>
              </a>
            </li>
            @if(auth()->user()->role === 'artist')
              <li class="menu-item {{ request()->routeIs('settings.studio') ? 'active' : '' }}">
                <a href="{{ route('settings.studio') }}" class="menu-link">
                  <i class="menu-icon tf-icons ti ti-building me-2"></i>
                  <div data-i18n="Studio Information">Studio Information</div>
                </a>
              </li>
              <li class="menu-item {{ request()->routeIs('settings.calendar') ? 'active' : '' }}">
                <a href="{{ route('settings.calendar') }}" class="menu-link">
                  <i class="menu-icon tf-icons ti ti-calendar me-2"></i>
                  <div data-i18n="Calendar">Calendar</div>
                </a>
              </li>
              <li class="menu-item {{ request()->routeIs('settings.preferences') ? 'active' : '' }}">
                <a href="{{ route('settings.preferences') }}" class="menu-link">
                  <i class="menu-icon tf-icons ti ti-adjustments me-2"></i>
                  <div data-i18n="Preferences">Preferences</div>
                </a>
              </li>
              <li class="menu-item {{ request()->routeIs('settings.payment') ? 'active' : '' }}">
                <a href="{{ route('settings.payment') }}" class="menu-link">
                  <i class="menu-icon tf-icons ti ti-credit-card me-2"></i>
                  <div data-i18n="Payment">Payment</div>
                </a>
              </li>
            @endif
          </ul>
      </li>
      <!-- Bookings (for all users) -->
      <li class="menu-item {{ request()->routeIs('bookings.*') ? 'active' : '' }}">
        <a href="{{ route('bookings.index') }}" class="menu-link">
          <i class="menu-icon tf-icons ti ti-calendar-check"></i>
          <div data-i18n="Bookings">Bookings</div>
        </a>
      </li>
      
      @if(auth()->user()->role === 'artist')
        <!-- Artist Menu Items -->
        <li class="menu-item {{ request()->routeIs('availability.*') ? 'active' : '' }}">
          <a href="{{ route('availability.index') }}" class="menu-link">
            <i class="menu-icon tf-icons ti ti-calendar"></i>
            <div data-i18n="Availability">Availability</div>
          </a>
        </li>
        <li class="menu-item {{ request()->routeIs('questions.*') ? 'active' : '' }}">
          <a href="{{ route('questions.index') }}" class="menu-link">
            <i class="menu-icon tf-icons ti ti-help"></i>
            <div data-i18n="Questions">Questions</div>
          </a>
        </li>
      @endif
      @if(auth()->user()->role === 'admin')
        <!-- Admin Menu Items -->
        <li class="menu-header small text-uppercase">
          <span class="menu-header-text">Administration</span>
        </li>
        <li class="menu-item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
          <a href="{{ route('admin.users.index') }}" class="menu-link">
            <i class="menu-icon tf-icons ti ti-users"></i>
            <div data-i18n="Users">Users</div>
          </a>
        </li>
        <li class="menu-item {{ request()->routeIs('admin.questions.*') ? 'active' : '' }}">
          <a href="{{ route('admin.questions.index') }}" class="menu-link">
            <i class="menu-icon tf-icons ti ti-help"></i>
            <div data-i18n="Default Questions">Default Questions</div>
          </a>
        </li>
      @endif

      @if(auth()->user()->role === 'user')
        <!-- Artists (for all authenticated users) -->
        <li class="menu-item {{ request()->routeIs('dashboard.artists') ? 'active' : '' }}">
          <a href="{{ route('dashboard.artists') }}" class="menu-link">
            <i class="menu-icon tf-icons ti ti-palette"></i>
            <div data-i18n="Artists">Artists</div>
          </a>
        </li>
      @endif
    @endauth
  </ul>
</aside>
<!-- / Menu -->

