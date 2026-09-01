<!-- Mobile Header -->
<div class="mobile-header fixed top-0 left-0 right-0 z-[60] bg-inverse-surface text-white px-4 py-3 items-center justify-between">
  <div class="flex flex-col gap-0.5 min-w-0 pr-2">
    <div class="flex items-center gap-2">
      <span class="text-lg font-bold leading-tight">inkjin</span>
      <span class="text-[9px] font-bold uppercase tracking-wider bg-amber-400 text-inverse-surface px-1.5 py-0.5 rounded shrink-0">Admin</span>
    </div>
    <p class="text-white/40 text-[10px] uppercase tracking-[2px] leading-tight">Platform Management</p>
  </div>
  <button type="button" id="adminMobileMenuBtn" class="material-symbols-outlined text-white text-2xl w-10 h-10 shrink-0 flex items-center justify-center rounded-xl hover:bg-white/10 transition-colors" aria-controls="mobileSidebar" aria-expanded="false" aria-label="Open menu">menu</button>
</div>

<!-- Sidebar -->
<aside class="sidebar hidden lg:flex fixed top-0 left-0 bg-inverse-surface flex-col p-6 z-40" id="mobileSidebar">
  <div class="mb-6 hidden lg:block shrink-0">
    <div class="flex items-center gap-2">
      <h1 class="text-white text-xl font-bold">inkjin</h1>
      <span class="text-[9px] font-bold uppercase tracking-wider bg-amber-400 text-inverse-surface px-1.5 py-0.5 rounded">Admin</span>
    </div>
    <p class="text-white/40 text-[10px] uppercase tracking-[2px] mt-1">Platform Management</p>
  </div>

  <nav class="sidebar-nav flex-1 min-h-0 overflow-y-auto flex flex-col gap-1 pr-1">
    <a href="{{ route('admin.dashboard') }}" class="nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"><span class="material-symbols-outlined">dashboard</span> Dashboard</a>
    <a href="{{ route('admin.bookings.index') }}" class="nav-item {{ request()->routeIs('admin.bookings.*') ? 'active' : '' }}"><span class="material-symbols-outlined">calendar_month</span> Bookings</a>
    <a href="{{ route('admin.requests.index') }}" class="nav-item {{ request()->routeIs('admin.requests.*') ? 'active' : '' }}"><span class="material-symbols-outlined">inbox</span> Requests</a>
    <a href="{{ route('admin.forms.index') }}" class="nav-item {{ request()->routeIs('admin.forms.index') ? 'active' : '' }}"><span class="material-symbols-outlined">description</span> Forms</a>
    <a href="{{ route('admin.styles.index') }}" class="nav-item {{ request()->routeIs('admin.styles.*') ? 'active' : '' }}"><span class="material-symbols-outlined">brush</span> Styles</a>
    <a href="{{ route('admin.placements.index') }}" class="nav-item {{ request()->routeIs('admin.placements.*') ? 'active' : '' }}"><span class="material-symbols-outlined">accessibility_new</span> Placements</a>
    <a href="{{ route('admin.sizes.index') }}" class="nav-item {{ request()->routeIs('admin.sizes.*') ? 'active' : '' }}"><span class="material-symbols-outlined">straighten</span> Sizes</a>
    <a href="{{ route('admin.users.index') }}" class="nav-item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}"><span class="material-symbols-outlined">group</span> Users</a>
    <a href="{{ route('admin.referrals.index') }}" class="nav-item {{ request()->routeIs('admin.referrals.*') ? 'active' : '' }}"><span class="material-symbols-outlined">redeem</span> Referrals</a>
    <div class="text-white/35 text-[10px] uppercase tracking-[2px] font-bold px-4 pt-5 pb-1">Financials</div>
    <a href="{{ route('admin.revenue.index') }}" class="nav-item {{ request()->routeIs('admin.revenue.*') ? 'active' : '' }}"><span class="material-symbols-outlined">trending_up</span> Revenue</a>
    <a href="{{ route('admin.fees.index') }}" class="nav-item {{ request()->routeIs('admin.fees.*') ? 'active' : '' }}"><span class="material-symbols-outlined">receipt_long</span> Fees</a>
    <a href="{{ route('admin.payouts.index') }}" class="nav-item {{ request()->routeIs('admin.payouts.*') ? 'active' : '' }}"><span class="material-symbols-outlined">payments</span> Payouts</a>
    <a href="{{ route('admin.stripe-accounts.index') }}" class="nav-item {{ request()->routeIs('admin.stripe-accounts.*') ? 'active' : '' }}"><span class="material-symbols-outlined">account_balance</span> Stripe Accounts</a>
    <div class="text-white/35 text-[10px] uppercase tracking-[2px] font-bold px-4 pt-5 pb-1">Account</div>
    <a href="{{ route('admin.settings.password') }}" class="nav-item {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}"><span class="material-symbols-outlined">settings</span> Settings</a>
  </nav>

  <div class="sidebar-footer shrink-0 pt-4 mt-4 border-t border-white/10">
    <form method="POST" action="{{ route('logout') }}" class="m-0">
      @csrf
      <input type="hidden" name="_token" value="{{ csrf_token() }}">
      <button type="submit" class="nav-item text-white/60 hover:text-white w-full text-left border-0 bg-transparent cursor-pointer font-[inherit]">
        <span class="material-symbols-outlined">logout</span> Log Out
      </button>
    </form>
    <div class="flex min-w-0 items-center gap-3 mt-4 pt-4 border-t border-white/10">
      <div class="shrink-0 w-10 h-10 rounded-full bg-amber-400 flex items-center justify-center text-inverse-surface font-bold text-sm">A</div>
      <div class="min-w-0 flex-1">
        <div class="text-white text-sm font-semibold truncate">Admin</div>
        <div class="text-white/50 text-xs truncate" title="{{ Auth::user()->email }}">{{ Auth::user()->email }}</div>
      </div>
    </div>
  </div>
</aside>
