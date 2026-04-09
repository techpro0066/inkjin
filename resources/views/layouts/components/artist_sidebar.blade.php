<aside class="sidebar hidden lg:flex fixed top-0 left-0 bg-primary flex-col p-6 z-40" id="mobileSidebar">
  <!-- Top: Logo (fixed height) -->
  <div class="mb-6">
    <h1 class="text-white text-xl font-bold">Inkjin</h1>
    <p class="text-white/50 text-[10px] uppercase tracking-[2px] mt-1">Book & Pay</p>
  </div>

  <!-- Middle: Nav (scrollable) -->
  <div class="flex-1 overflow-y-auto">
    <nav class="flex flex-col gap-1">
      <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
        <span class="material-symbols-outlined">dashboard</span> Dashboard
      </a>
      <a href="{{ route('bookings.index') }}" class="nav-item {{ request()->routeIs('bookings.index') ? 'active' : '' }}">
        <span class="material-symbols-outlined">calendar_month</span> Bookings
      </a>
      <a href="{{ route('availability.index') }}" class="nav-item {{ request()->routeIs('availability.index') ? 'active' : '' }}">
        <span class="material-symbols-outlined">event_available</span> Availability
      </a>
      <a href="#" class="nav-item">
        <span class="material-symbols-outlined">edit_note</span> Requests
      </a>
      <a href="#" class="nav-item">
        <span class="material-symbols-outlined">payments</span> Payments
      </a>
      <a href="#" class="nav-item">
        <span class="material-symbols-outlined">folder_open</span> Content
      </a>
      <a href="#" class="nav-item">
        <span class="material-symbols-outlined">group</span> Clients
      </a>
      <a href="{{ route('profile.edit') }}" class="nav-item {{ request()->routeIs('profile.edit') || request()->routeIs('settings.*') ? 'active' : '' }}">
        <span class="material-symbols-outlined">settings</span> Settings
      </a>
    </nav>
  </div>

  <!-- Bottom: Log Out + Avatar (sticky, always visible) -->
  <div class="flex-shrink-0">
    <div class="border-t border-white/10 pt-4 mt-4">
      <a href="index.html" class="nav-item text-white/60 hover:text-white"><span class="material-symbols-outlined">logout</span> Log Out</a>
    </div>
    <div class="flex min-w-0 items-center gap-3 mt-4 pt-4 border-t border-white/10">
      <div class="shrink-0 w-10 h-10 rounded-full bg-primary-fixed-dim flex items-center justify-center text-primary font-bold text-sm overflow-hidden">
        <img src="{{ asset(Auth::user()->userDetail->avatar) }}" alt="Avatar" class="w-full h-full object-cover rounded-full">
      </div>
      <div class="min-w-0 flex-1">
        <div class="text-white text-sm font-semibold truncate" title="{{ Auth::user()->first_name }} {{ Auth::user()->last_name }}">{{ Auth::user()->first_name }} {{ Auth::user()->last_name }}</div>
        <div class="text-white/50 text-xs truncate" title="{{ Auth::user()->email }}">{{ Auth::user()->email }}</div>
      </div>
    </div>
  </div>
</aside>