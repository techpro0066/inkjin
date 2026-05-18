<aside class="sidebar hidden lg:flex fixed top-0 left-0 bg-primary flex-col justify-between p-6 z-40" id="mobileSidebar">
    <div>
      <div class="mb-10">
        <h1 class="text-white text-xl font-bold">inkjin</h1>
        <p class="text-white/50 text-[10px] uppercase tracking-[2px] mt-1">Book & Pay</p>
      </div>
      <nav class="flex flex-col gap-1">
        <a href="{{ route('user.dashboard') }}" class="nav-item {{ request()->routeIs('user.dashboard') ? 'active' : '' }}">
          <span class="material-symbols-outlined">dashboard</span> Dashboard
        </a>
        <a href="{{ route('user.requests.index') }}" class="nav-item {{ request()->routeIs('user.requests.index') ? 'active' : '' }}">
          <span class="material-symbols-outlined">inbox</span> My Requests
        </a>
        <a href="{{ route('user.bookings.index') }}" class="nav-item {{ request()->routeIs('user.bookings.index') ? 'active' : '' }}">
          <span class="material-symbols-outlined">calendar_month</span> My Bookings
        </a>
        <a href="{{ route('user.settings') }}" class="nav-item {{ request()->routeIs('user.settings') ? 'active' : '' }}">
          <span class="material-symbols-outlined">settings</span> Settings
        </a>
      </nav>
    </div>
    <div>
      <div class="border-t border-white/10 pt-4 mt-4">
        <form method="POST" action="{{ route('logout') }}" class="m-0">
          @csrf
          <input type="hidden" name="_token" value="{{ csrf_token() }}">
          <button type="submit" class="nav-item text-white/60 hover:text-white w-full text-left border-0 bg-transparent cursor-pointer font-[inherit]">
            <span class="material-symbols-outlined">logout</span> Log Out
          </button>
        </form>
      </div>
      <div class="flex items-center gap-3 mt-4 pt-4 border-t border-white/10">
        <div class="w-10 h-10 rounded-full bg-primary-fixed-dim flex items-center justify-center text-primary font-bold text-sm">
          <img src="{{ (Auth::user()->userDetail && Auth::user()->userDetail->avatar != "") ? asset(Auth::user()->userDetail->avatar) : asset('design/images/icons/avatar.jpg') }}" alt="{{ Auth::user()->first_name }}" class="w-full h-full object-cover rounded-full">
        </div>
        <div>
          <div class="text-white text-sm font-semibold">{{ Auth::user()->first_name }}</div>
          <div class="text-white/50 text-xs">{{ Auth::user()->email }}</div>
        </div>
      </div>
    </div>
  </aside>