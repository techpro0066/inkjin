@extends('layouts.admin_dashboard_layout')

@section('title', $pageTitle)

@section('styles')
<style>
  .user-row { transition: background 0.15s ease; cursor: pointer; }
  .user-row:hover { background: #f8f1fb; }
  .detail-panel { max-height: 0; overflow: hidden; transition: max-height 0.4s ease; }
  .detail-panel.open { max-height: 4000px; }
  @media (max-width: 1023px) {
    .main-content { overflow-x: hidden; padding: 16px; padding-top: 70px; }
    body { overflow-x: hidden; }
  }
</style>
@endsection

@section('content')
<main class="main-content flex-1 min-h-screen">
  <div class="p-6 md:p-10 lg:p-12 max-w-7xl">

    <div class="mb-8">
      <h2 class="text-3xl font-extrabold text-on-surface tracking-tight">{{ $pageTitle }}</h2>
      <p class="text-on-surface-variant mt-1">{{ $pageSubtitle }}</p>
    </div>

    <form method="GET" action="{{ route('admin.users.index') }}" class="bg-surface-container-low rounded-2xl p-5 mb-6 border border-outline-variant/20">
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        <div class="lg:col-span-2">
          <label class="block text-xs font-semibold text-on-surface-variant mb-1.5">Search</label>
          <div class="relative">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-lg">search</span>
            <input
              type="text"
              name="q"
              value="{{ $search }}"
              placeholder="Search by name, email, or studio..."
              class="w-full text-sm border border-outline-variant/30 rounded-xl pl-9 pr-3 py-2 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30"
            >
          </div>
        </div>
        <div>
          <label class="block text-xs font-semibold text-on-surface-variant mb-1.5">Role</label>
          <select name="role" class="w-full text-sm border border-outline-variant/30 rounded-xl px-3 py-2 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
            <option value="all" {{ $roleFilter === 'all' ? 'selected' : '' }}>All users</option>
            <option value="artist" {{ $roleFilter === 'artist' ? 'selected' : '' }}>Artists</option>
            <option value="user" {{ $roleFilter === 'user' ? 'selected' : '' }}>Clients</option>
          </select>
        </div>
        <div>
          <label class="block text-xs font-semibold text-on-surface-variant mb-1.5">Status</label>
          <select name="status" class="w-full text-sm border border-outline-variant/30 rounded-xl px-3 py-2 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
            <option value="all" {{ $statusFilter === 'all' ? 'selected' : '' }}>All</option>
            <option value="active" {{ $statusFilter === 'active' ? 'selected' : '' }}>Active</option>
            <option value="pending_onboarding" {{ $statusFilter === 'pending_onboarding' ? 'selected' : '' }}>Pending Onboarding</option>
          </select>
        </div>
        <div>
          <label class="block text-xs font-semibold text-on-surface-variant mb-1.5">Sort by</label>
          <select name="sort" class="w-full text-sm border border-outline-variant/30 rounded-xl px-3 py-2 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
            <option value="newest" {{ $sort === 'newest' ? 'selected' : '' }}>Newest</option>
            <option value="bookings" {{ $sort === 'bookings' ? 'selected' : '' }}>Most Bookings</option>
            <option value="revenue" {{ $sort === 'revenue' ? 'selected' : '' }}>Highest Revenue</option>
            <option value="name" {{ $sort === 'name' ? 'selected' : '' }}>Name A-Z</option>
          </select>
        </div>
      </div>
      <div class="mt-4 flex justify-end">
        <button type="submit" class="inline-flex items-center gap-2 bg-primary text-white px-4 py-2 rounded-xl font-semibold text-sm hover:bg-primary-container transition-colors">
          <span class="material-symbols-outlined text-[18px]">filter_alt</span>
          Apply filters
        </button>
      </div>
    </form>

    <div class="bg-white rounded-2xl shadow-sm border border-outline-variant/20 mb-6 overflow-hidden">
      <div class="hidden lg:block overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="bg-surface-container-low/50 text-on-surface-variant text-xs uppercase tracking-wider">
              <th class="text-left px-5 py-3 font-semibold">{{ $roleFilter === 'user' ? 'Client' : ($roleFilter === 'artist' ? 'Artist' : 'User') }}</th>
              <th class="text-left px-5 py-3 font-semibold">Email</th>
              <th class="text-left px-5 py-3 font-semibold">Studio</th>
              <th class="text-left px-5 py-3 font-semibold">Location</th>
              <th class="text-left px-5 py-3 font-semibold">Styles</th>
              <th class="text-left px-5 py-3 font-semibold">Bookings</th>
              <th class="text-left px-5 py-3 font-semibold">Revenue</th>
              <th class="text-left px-5 py-3 font-semibold">Status</th>
              <th class="text-left px-5 py-3 font-semibold">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-outline-variant/10" id="usersBody">
            @forelse($users as $user)
              @php $expanded = (int) $expandedId === (int) $user['id']; @endphp
              <tr class="user-row" data-user-id="{{ $user['id'] }}" onclick="toggleUserDetail({{ $user['id'] }})">
                <td class="px-5 py-4">
                  <div class="flex items-center gap-3">
                    @if(!empty($user['avatar']))
                      <img src="{{ asset(ltrim($user['avatar'], '/')) }}" alt="" class="w-9 h-9 rounded-full object-cover">
                    @else
                      <div class="w-9 h-9 rounded-full bg-primary/10 flex items-center justify-center text-primary font-bold text-xs">{{ $user['initials'] }}</div>
                    @endif
                    <div>
                      <span class="font-semibold block">{{ $user['name'] }}</span>
                      <span class="text-[11px] text-on-surface-variant">{{ $user['role'] === 'artist' ? 'Artist' : 'Client' }}</span>
                    </div>
                  </div>
                </td>
                <td class="px-5 py-4 text-on-surface-variant">{{ $user['email'] }}</td>
                <td class="px-5 py-4 text-on-surface-variant">{{ $user['studio'] }}</td>
                <td class="px-5 py-4 text-on-surface-variant">{{ $user['location'] }}</td>
                <td class="px-5 py-4">
                  <div class="flex flex-wrap gap-1">
                    @forelse(array_slice($user['styles'], 0, 3) as $style)
                      <span class="inline-block bg-primary/5 text-primary text-[10px] font-semibold px-2 py-0.5 rounded-full">{{ $style }}</span>
                    @empty
                      <span class="text-on-surface-variant">—</span>
                    @endforelse
                  </div>
                </td>
                <td class="px-5 py-4 font-semibold">{{ number_format($user['bookings']) }}</td>
                <td class="px-5 py-4 font-semibold">€{{ number_format($user['revenue'], 0) }}</td>
                <td class="px-5 py-4">@include('admin.users.partials.status-badge', ['status' => $user['status']])</td>
                <td class="px-5 py-4">
                  <button type="button" class="w-8 h-8 rounded-lg flex items-center justify-center hover:bg-surface-container-low" onclick="event.stopPropagation(); toggleUserDetail({{ $user['id'] }})">
                    <span class="material-symbols-outlined text-on-surface-variant text-lg user-toggle-icon" data-user-id="{{ $user['id'] }}">{{ $expanded ? 'expand_less' : 'visibility' }}</span>
                  </button>
                </td>
              </tr>
              <tr class="detail-row" data-detail-for="{{ $user['id'] }}" style="{{ $expanded ? '' : 'display:none;' }}">
                <td colspan="9" class="p-0">
                  @include('admin.users.partials.detail-panel', ['user' => $user, 'open' => $expanded])
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="9" class="px-6 py-12 text-center text-sm text-on-surface-variant">No users found for this filter.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="lg:hidden divide-y divide-outline-variant/10" id="usersMobile">
        @forelse($users as $user)
          @php $expanded = (int) $expandedId === (int) $user['id']; @endphp
          <div class="p-4" data-mobile-user="{{ $user['id'] }}">
            <div class="flex items-center justify-between mb-2">
              <div class="flex items-center gap-3">
                @if(!empty($user['avatar']))
                  <img src="{{ asset(ltrim($user['avatar'], '/')) }}" alt="" class="w-9 h-9 rounded-full object-cover">
                @else
                  <div class="w-9 h-9 rounded-full bg-primary/10 flex items-center justify-center text-primary font-bold text-xs">{{ $user['initials'] }}</div>
                @endif
                <div>
                  <p class="font-semibold">{{ $user['name'] }}</p>
                  <p class="text-xs text-on-surface-variant">{{ $user['email'] }}</p>
                </div>
              </div>
              @include('admin.users.partials.status-badge', ['status' => $user['status']])
            </div>
            <div class="flex flex-wrap gap-3 text-xs text-on-surface-variant mt-1">
              <span>{{ $user['studio'] }}</span>
              <span>{{ number_format($user['bookings']) }} bookings</span>
              <span>€{{ number_format($user['revenue'], 0) }}</span>
            </div>
            <button type="button" onclick="toggleUserDetail({{ $user['id'] }})" class="mt-3 inline-flex items-center gap-1.5 bg-primary/10 text-primary px-3 py-1.5 rounded-lg font-semibold text-xs user-mobile-toggle" data-user-id="{{ $user['id'] }}">
              <span class="material-symbols-outlined user-toggle-icon" data-user-id="{{ $user['id'] }}" style="font-size:16px;">{{ $expanded ? 'expand_less' : 'visibility' }}</span>
              <span class="user-toggle-label" data-user-id="{{ $user['id'] }}">{{ $expanded ? 'Close' : 'View' }}</span>
            </button>
            <div class="mobile-detail-panel {{ $expanded ? '' : 'hidden' }}" data-mobile-detail-for="{{ $user['id'] }}">
              @include('admin.users.partials.detail-panel', ['user' => $user, 'open' => true])
            </div>
          </div>
        @empty
          <div class="p-8 text-center text-sm text-on-surface-variant">No users found for this filter.</div>
        @endforelse
      </div>
    </div>
  </div>
</main>
@endsection

@section('scripts')
<script>
  let expandedUserId = {{ $expandedId ?: 'null' }};

  function toggleUserDetail(id) {
    const nextId = expandedUserId === id ? null : id;
    expandedUserId = nextId;

    document.querySelectorAll('.detail-row').forEach(function (row) {
      const rowId = parseInt(row.getAttribute('data-detail-for'), 10);
      const open = rowId === nextId;
      row.style.display = open ? '' : 'none';
      const panel = row.querySelector('.detail-panel');
      if (panel) {
        panel.classList.toggle('open', open);
      }
    });

    document.querySelectorAll('.mobile-detail-panel').forEach(function (panel) {
      const rowId = parseInt(panel.getAttribute('data-mobile-detail-for'), 10);
      const open = rowId === nextId;
      panel.classList.toggle('hidden', !open);
      const inner = panel.querySelector('.detail-panel');
      if (inner) inner.classList.toggle('open', open);
    });

    document.querySelectorAll('.user-toggle-icon').forEach(function (icon) {
      const rowId = parseInt(icon.getAttribute('data-user-id'), 10);
      icon.textContent = rowId === nextId ? 'expand_less' : 'visibility';
    });

    document.querySelectorAll('.user-toggle-label').forEach(function (label) {
      const rowId = parseInt(label.getAttribute('data-user-id'), 10);
      label.textContent = rowId === nextId ? 'Close' : 'View';
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    if (expandedUserId) {
      toggleUserDetail(expandedUserId);
    }
  });
</script>
@endsection
