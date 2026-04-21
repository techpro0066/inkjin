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
<aside class="sidebar hidden lg:flex fixed top-0 left-0 bg-inverse-surface flex-col justify-between p-6 z-40 overflow-y-auto" id="mobileSidebar">
  <div>
    <div class="mb-10 hidden lg:block">
      <div class="flex items-center gap-2">
        <h1 class="text-white text-xl font-bold">inkjin</h1>
        <span class="text-[9px] font-bold uppercase tracking-wider bg-amber-400 text-inverse-surface px-1.5 py-0.5 rounded">Admin</span>
      </div>
      <p class="text-white/40 text-[10px] uppercase tracking-[2px] mt-1">Platform Management</p>
    </div>
    <nav class="flex flex-col gap-1">
      <a href="{{ route('admin.dashboard') }}" class="nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"><span class="material-symbols-outlined">dashboard</span> Dashboard</a>
      <a href="{{ route('admin.forms.index') }}" class="nav-item {{ request()->routeIs('admin.forms.index') ? 'active' : '' }}"><span class="material-symbols-outlined">description</span> Forms</a>
    </nav>
  </div>
  <div>
    <div class="border-t border-white/10 pt-4 mt-4">
      <form method="POST" action="{{ route('logout') }}" class="m-0">
        @csrf
        <input type="hidden" name="_token" value="{{ csrf_token() }}">
        <button type="submit" class="nav-item text-white/60 hover:text-white w-full text-left border-0 bg-transparent cursor-pointer font-[inherit] w-100">
          <span class="material-symbols-outlined">logout</span> Log Out
        </button>
      </form>
    </div>
    <div class="flex items-center gap-3 mt-4 pt-4 border-t border-white/10">
      <div class="w-10 h-10 rounded-full bg-amber-400 flex items-center justify-center text-inverse-surface font-bold text-sm">A</div>
      <div>
        <div class="text-white text-sm font-semibold">Admin</div>
        <div class="text-white/50 text-xs">{{ Auth::user()->email }}</div>
      </div>
    </div>
  </div>
</aside>