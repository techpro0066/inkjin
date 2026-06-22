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
        <a href="{{ route('user.chat.index') }}" class="nav-item {{ request()->routeIs('user.chat.*') ? 'active' : '' }}">
          <span class="material-symbols-outlined">mail</span> Inbox
          <span id="inboxUnreadDot" class="nav-inbox-unread-dot hidden ml-auto flex-shrink-0 rounded-full px-1.5 py-0.5 text-[10px] font-semibold leading-none" aria-hidden="true"></span>
        </a>
        <a href="{{ route('user.requests.index') }}" class="nav-item {{ request()->routeIs('user.requests.*') ? 'active' : '' }}">
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
    <div class="flex-shrink-0 w-full min-w-0">
      <div class="border-t border-white/10 pt-4 mt-4">
        <form method="POST" action="{{ route('logout') }}" class="m-0">
          @csrf
          <input type="hidden" name="_token" value="{{ csrf_token() }}">
          <button type="submit" class="nav-item text-white/60 hover:text-white w-full text-left border-0 bg-transparent cursor-pointer font-[inherit]">
            <span class="material-symbols-outlined">logout</span> Log Out
          </button>
        </form>
      </div>
      <div class="flex min-w-0 items-center gap-3 mt-4 pt-4 border-t border-white/10">
        <div class="shrink-0 w-10 h-10 rounded-full bg-primary-fixed-dim flex items-center justify-center text-primary font-bold text-sm overflow-hidden">
          <img src="{{ (Auth::user()->userDetail && Auth::user()->userDetail->avatar != "") ? asset(Auth::user()->userDetail->avatar) : asset('design/images/icons/avatar.jpg') }}" alt="{{ Auth::user()->first_name }}" class="w-full h-full object-cover rounded-full">
        </div>
        <div class="min-w-0 flex-1">
          <div class="text-white text-sm font-semibold truncate" title="{{ Auth::user()->first_name }} {{ Auth::user()->last_name }}">{{ Auth::user()->first_name }} {{ Auth::user()->last_name }}</div>
          <div class="text-white/50 text-xs truncate" title="{{ Auth::user()->email }}">{{ Auth::user()->email }}</div>
        </div>
      </div>
    </div>
  </aside>