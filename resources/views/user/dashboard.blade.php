@extends('layouts.user_dashboard_layout')

@section('title', 'Dashboard')

@section('content')
<main class="main-content flex-1 min-h-screen">
    <div class="p-6 md:p-10 lg:p-12 max-w-6xl">

      <!-- Welcome Header -->
      <div class="mb-8">
        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-2 mb-2">
          <h2 class="text-3xl font-extrabold text-on-surface tracking-tight">Welcome back, {{ Auth::user()->first_name }}</h2>
          <p class="text-sm text-outline font-medium" id="currentDate"></p>
        </div>
        <p class="text-on-surface-variant mt-1 max-w-lg">Here's what's happening with your bookings.</p>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-2 xl:grid-cols-4 gap-5 mb-8">
        <div class="stat-card bg-white rounded-2xl p-5 shadow-sm border border-outline-variant/20">
          <div class="flex items-start justify-between mb-4">
            <div class="w-11 h-11 rounded-xl bg-primary/10 flex items-center justify-center">
              <span class="material-symbols-outlined text-primary">calendar_today</span>
            </div>
          </div>
          <p class="text-3xl font-extrabold text-on-surface">{{ $stats['upcoming_count'] }}</p>
          <p class="text-sm font-semibold text-on-surface mt-1">Upcoming Bookings</p>
          <p class="text-xs text-on-surface-variant mt-1">{{ $stats['upcoming_subtitle'] }}</p>
        </div>
        <div class="stat-card bg-white rounded-2xl p-5 shadow-sm border border-outline-variant/20">
          <div class="flex items-start justify-between mb-4">
            <div class="w-11 h-11 rounded-xl bg-amber-500/10 flex items-center justify-center">
              <span class="material-symbols-outlined text-amber-600">pending_actions</span>
            </div>
          </div>
          <p class="text-3xl font-extrabold text-on-surface">{{ $stats['pending_requests_count'] }}</p>
          <p class="text-sm font-semibold text-on-surface mt-1">Pending Requests</p>
          <p class="text-xs text-on-surface-variant mt-1">{{ $stats['pending_requests_subtitle'] }}</p>
        </div>
        <div class="stat-card bg-white rounded-2xl p-5 shadow-sm border border-outline-variant/20">
          <div class="flex items-start justify-between mb-4">
            <div class="w-11 h-11 rounded-xl bg-primary/10 flex items-center justify-center">
              <span class="material-symbols-outlined text-primary">mail</span>
            </div>
          </div>
          <p class="text-3xl font-extrabold text-on-surface">{{ $stats['unread_messages_count'] }}</p>
          <p class="text-sm font-semibold text-on-surface mt-1">Unread Messages</p>
          <p class="text-xs text-on-surface-variant mt-1">{{ $stats['unread_messages_subtitle'] }}</p>
        </div>
        <div class="stat-card bg-white rounded-2xl p-5 shadow-sm border border-outline-variant/20">
          <div class="flex items-start justify-between mb-4">
            <div class="w-11 h-11 rounded-xl bg-green-500/10 flex items-center justify-center">
              <span class="material-symbols-outlined text-green-600">payments</span>
            </div>
          </div>
          <p class="text-3xl font-extrabold text-on-surface">{{ $stats['total_spent_label'] }}</p>
          <p class="text-sm font-semibold text-on-surface mt-1">Total Spent</p>
          <p class="text-xs text-on-surface-variant mt-1">{{ $stats['sessions_subtitle'] }}</p>
        </div>
      </div>

      <!-- Upcoming Appointments -->
      <div class="bg-white rounded-2xl shadow-sm border border-outline-variant/20 mb-8 overflow-hidden">
        <div class="flex items-center justify-between px-6 py-5 border-b border-outline-variant/15">
          <h3 class="text-lg font-bold text-on-surface">Upcoming Appointments</h3>
          <a href="{{ route('user.bookings.index') }}" class="text-sm font-semibold text-primary hover:underline flex items-center gap-1">
            View All <span class="material-symbols-outlined text-base">arrow_forward</span>
          </a>
        </div>
        <div class="divide-y divide-outline-variant/10">
          @forelse($upcomingBookings as $booking)
          <div class="p-5 flex flex-col sm:flex-row sm:items-center gap-4 hover:bg-surface-container-low/50 transition-colors">
            <div class="flex items-center gap-3 flex-1 min-w-0">
              @if(!empty($booking['avatar']))
                <img src="{{ $booking['avatar'] }}" alt="" class="w-11 h-11 rounded-full object-cover flex-shrink-0">
              @else
                <div class="w-11 h-11 rounded-full bg-gradient-to-br from-primary to-primary-container flex items-center justify-center flex-shrink-0">
                  <span class="text-white text-xs font-bold">{{ $booking['initials'] }}</span>
                </div>
              @endif
              <div class="min-w-0">
                <p class="font-semibold text-on-surface text-sm">{{ $booking['title'] }}</p>
                <p class="text-xs text-on-surface-variant mt-0.5">{{ $booking['artist_line'] }}</p>
              </div>
            </div>
            <div class="flex items-center gap-3 sm:gap-5 flex-shrink-0">
              <div class="text-right">
                <p class="text-sm font-semibold text-on-surface">{{ $booking['when'] }}</p>
                <span class="inline-flex items-center gap-1.5 {{ $booking['badge_class'] }} text-xs font-semibold px-2.5 py-0.5 rounded-full mt-1">
                  <span class="w-1.5 h-1.5 {{ $booking['dot_class'] }} rounded-full"></span> {{ $booking['status_label'] }}
                </span>
              </div>
              <a href="{{ $booking['details_url'] }}" class="text-sm font-semibold text-primary hover:underline whitespace-nowrap">View Details</a>
            </div>
          </div>
          @empty
          <div class="p-8 text-center">
            <span class="material-symbols-outlined text-3xl text-on-surface-variant mb-2">event_busy</span>
            <p class="text-sm font-semibold text-on-surface">No upcoming appointments</p>
            <p class="text-xs text-on-surface-variant mt-1">When you book a tattoo, it will show up here.</p>
          </div>
          @endforelse
        </div>
      </div>

      <!-- Recent Messages -->
      <div class="bg-white rounded-2xl shadow-sm border border-outline-variant/20 mb-8 overflow-hidden">
        <div class="flex items-center justify-between px-6 py-5 border-b border-outline-variant/15">
          <h3 class="text-lg font-bold text-on-surface">Recent Messages</h3>
          <a href="{{ route('user.chat.index') }}" class="text-sm font-semibold text-primary hover:underline flex items-center gap-1">
            View All Messages <span class="material-symbols-outlined text-base">arrow_forward</span>
          </a>
        </div>
        <div class="divide-y divide-outline-variant/10">
          @forelse($recentMessages as $message)
          <a href="{{ $message['url'] }}" class="p-4 flex items-center gap-3 hover:bg-surface-container-low/50 transition-colors">
            <div class="relative flex-shrink-0">
              @if(!empty($message['avatar']))
                <img src="{{ $message['avatar'] }}" alt="" class="w-10 h-10 rounded-full object-cover">
              @else
                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-primary to-primary-container flex items-center justify-center">
                  <span class="text-white text-xs font-bold">{{ $message['initials'] }}</span>
                </div>
              @endif
              @if($message['has_unread'])
                <span class="absolute -top-0.5 -right-0.5 w-3 h-3 bg-primary rounded-full border-2 border-white"></span>
              @endif
            </div>
            <div class="flex-1 min-w-0">
              <div class="flex items-center justify-between">
                <p class="font-semibold text-on-surface text-sm">{{ $message['artist_name'] }}</p>
                <span class="text-xs text-outline">{{ $message['time_ago'] }}</span>
              </div>
              <p class="text-sm text-on-surface-variant truncate {{ $message['has_unread'] ? 'font-medium text-on-surface' : '' }}">{{ $message['preview'] }}</p>
            </div>
          </a>
          @empty
          <div class="p-8 text-center">
            <span class="material-symbols-outlined text-3xl text-on-surface-variant mb-2">chat_bubble_outline</span>
            <p class="text-sm font-semibold text-on-surface">No messages yet</p>
            <p class="text-xs text-on-surface-variant mt-1">Conversations with artists will appear here after you book.</p>
          </div>
          @endforelse
        </div>
      </div>

      <!-- Tattoo Guides -->
      <div class="mb-8">
        <div class="flex items-center justify-between mb-5">
          <div>
            <h3 class="text-lg font-bold text-on-surface">Preparing for your tattoo?</h3>
            <p class="text-sm text-on-surface-variant mt-0.5">Helpful guides to get you ready</p>
          </div>
          <a href="https://inkjin.com/tattoo-guides/" class="text-sm font-semibold text-primary hover:underline flex items-center gap-1">
            All Guides <span class="material-symbols-outlined text-base">arrow_forward</span>
          </a>
        </div>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
          <a href="https://inkjin.com/tattoo-guides/how-much-do-tattoos-cost" class="guide-card bg-surface-container-low rounded-2xl p-5 block">
            <span class="text-2xl mb-3 block">💰</span>
            <p class="font-bold text-on-surface text-sm mb-1">How Much Do Tattoos Cost?</p>
            <p class="text-xs text-on-surface-variant">Pricing factors & what to expect</p>
          </a>
          <a href="https://inkjin.com/tattoo-guides/tattoo-pain-chart" class="guide-card bg-surface-container-low rounded-2xl p-5 block">
            <span class="text-2xl mb-3 block">🗺️</span>
            <p class="font-bold text-on-surface text-sm mb-1">Tattoo Pain Chart</p>
            <p class="text-xs text-on-surface-variant">Find out what to expect for your placement</p>
          </a>
          <a href="https://inkjin.com/tattoo-guides/how-much-to-tip-tattoo-artist" class="guide-card bg-surface-container-low rounded-2xl p-5 block">
            <span class="text-2xl mb-3 block">🤝</span>
            <p class="font-bold text-on-surface text-sm mb-1">How to Tip Your Artist</p>
            <p class="text-xs text-on-surface-variant">Tipping etiquette & guidelines</p>
          </a>
          <a href="https://inkjin.com/tattoo-guides/first-tattoo-ideas" class="guide-card bg-surface-container-low rounded-2xl p-5 block">
            <span class="text-2xl mb-3 block">✨</span>
            <p class="font-bold text-on-surface text-sm mb-1">First Tattoo Guide</p>
            <p class="text-xs text-on-surface-variant">Everything you need to know</p>
          </a>
        </div>
      </div>

      <!-- Active Requests -->
      <div class="bg-white rounded-2xl shadow-sm border border-outline-variant/20 mb-8 overflow-hidden">
        <div class="flex items-center justify-between px-6 py-5 border-b border-outline-variant/15">
          <h3 class="text-lg font-bold text-on-surface">Active Requests</h3>
          <div class="flex items-center gap-4">
            <a href="{{ route('user.requests.index', ['tab' => 'custom']) }}" class="text-sm font-semibold text-on-surface-variant hover:text-primary transition-colors">Custom</a>
            <a href="{{ route('user.requests.index') }}" class="text-sm font-semibold text-primary hover:underline flex items-center gap-1">
              View all <span class="material-symbols-outlined text-base">arrow_forward</span>
            </a>
          </div>
        </div>
        <div class="divide-y divide-outline-variant/10">
          @forelse($activeRequests as $request)
          <div class="p-5 flex flex-col sm:flex-row sm:items-center gap-3 hover:bg-surface-container-low/50 transition-colors">
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2 mb-1">
                <span class="inline-flex items-center gap-1.5 {{ $request['badge_class'] }} text-xs font-semibold px-2.5 py-0.5 rounded-full">
                  <span class="w-1.5 h-1.5 {{ $request['dot_class'] }} rounded-full"></span> {{ $request['status_label'] }}
                </span>
                <span class="text-xs text-outline">{{ $request['time_ago'] }}</span>
              </div>
              <p class="font-semibold text-on-surface text-sm">{{ $request['title'] }}</p>
              <p class="text-xs text-on-surface-variant mt-0.5">{{ $request['artist_line'] }}</p>
            </div>
            <a href="{{ $request['action_url'] }}" class="text-sm font-semibold text-primary hover:underline whitespace-nowrap">{{ $request['action_label'] }}</a>
          </div>
          @empty
          <div class="p-8 text-center">
            <span class="material-symbols-outlined text-3xl text-on-surface-variant mb-2">inbox</span>
            <p class="text-sm font-semibold text-on-surface">No active requests</p>
            <p class="text-xs text-on-surface-variant mt-1">Custom and managed design requests will show here.</p>
          </div>
          @endforelse
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="mb-10">
        <h3 class="text-lg font-bold text-on-surface mb-5">Quick Actions</h3>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
          <a href="https://inkjin.com" target="_blank" rel="noopener noreferrer" class="quick-action bg-white rounded-2xl p-5 shadow-sm border border-outline-variant/20 text-left group block">
            <div class="w-11 h-11 rounded-xl bg-primary/10 flex items-center justify-center mb-4 group-hover:bg-primary/15 transition-colors">
              <span class="material-symbols-outlined text-primary">search</span>
            </div>
            <p class="font-bold text-on-surface text-sm">Find an Artist</p>
            <p class="text-xs text-on-surface-variant mt-1">Browse tattoo artists near you</p>
          </a>
          <a href="{{ route('user.requests.index', ['tab' => 'custom']) }}" class="quick-action bg-white rounded-2xl p-5 shadow-sm border border-outline-variant/20 text-left group block">
            <div class="w-11 h-11 rounded-xl bg-primary/10 flex items-center justify-center mb-4 group-hover:bg-primary/15 transition-colors">
              <span class="material-symbols-outlined text-primary">edit</span>
            </div>
            <p class="font-bold text-on-surface text-sm">Request Custom Tattoo</p>
            <p class="text-xs text-on-surface-variant mt-1">Track custom requests you've sent</p>
          </a>
          <a href="https://inkjin.com" target="_blank" rel="noopener noreferrer" class="quick-action bg-white rounded-2xl p-5 shadow-sm border border-outline-variant/20 text-left group block">
            <div class="w-11 h-11 rounded-xl bg-primary/10 flex items-center justify-center mb-4 group-hover:bg-primary/15 transition-colors">
              <span class="material-symbols-outlined text-primary">palette</span>
            </div>
            <p class="font-bold text-on-surface text-sm">Browse Designs</p>
            <p class="text-xs text-on-surface-variant mt-1">Explore available tattoo designs</p>
          </a>
        </div>
      </div>

    </div>
</main>
@endsection

@section('scripts')
    <script>
        const dateEl = document.getElementById('currentDate');
        if (dateEl) {
          const now = new Date();
          dateEl.textContent = now.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
        }
    </script>
@endsection
