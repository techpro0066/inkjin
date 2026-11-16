@extends('layouts.admin_dashboard_layout')

@section('title', $pageTitle)

@section('styles')
<style>
  .user-row { transition: background 0.15s ease; cursor: pointer; }
  .user-row:hover { background: #f8f1fb; }
  .detail-panel { max-height: 0; overflow: hidden; transition: max-height 0.4s ease; }
  .detail-panel.open { max-height: 4000px; }
  .modal-backdrop {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 200;
    align-items: center;
    justify-content: center;
    background: rgba(28, 27, 33, 0.45);
    opacity: 0;
    transition: opacity 0.2s ease;
  }
  .modal-backdrop.modal-visible { display: flex; }
  .modal-backdrop.modal-visible:not(.modal-open) { pointer-events: none; }
  .modal-backdrop.modal-open { opacity: 1; pointer-events: auto; }
  .modal-backdrop.modal-open .modal-inner {
    transform: translateY(0);
    opacity: 1;
  }
  .modal-inner {
    transform: translateY(8px);
    opacity: 0;
    transition: transform 0.2s ease, opacity 0.2s ease;
  }
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
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
        <div class="lg:col-span-2 xl:col-span-2">
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
            <option value="studio" {{ $roleFilter === 'studio' ? 'selected' : '' }}>Studios</option>
            <option value="user" {{ $roleFilter === 'user' ? 'selected' : '' }}>Clients</option>
          </select>
        </div>
        <div>
          <label class="block text-xs font-semibold text-on-surface-variant mb-1.5">Status</label>
          <select name="status" class="w-full text-sm border border-outline-variant/30 rounded-xl px-3 py-2 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
            <option value="all" {{ $statusFilter === 'all' ? 'selected' : '' }}>All</option>
            <option value="active" {{ $statusFilter === 'active' ? 'selected' : '' }}>Active</option>
            <option value="pending_onboarding" {{ $statusFilter === 'pending_onboarding' ? 'selected' : '' }}>Pending Onboarding</option>
            @foreach (\App\Support\OnboardingProgress::STEP_LABELS as $step => $stepLabel)
              <option value="step_{{ $step }}" {{ $statusFilter === 'step_'.$step ? 'selected' : '' }}>
                Step {{ $step }} — {{ $stepLabel }}
              </option>
            @endforeach
          </select>
        </div>
        <div>
          <label class="block text-xs font-semibold text-on-surface-variant mb-1.5">Books</label>
          <select name="books" class="w-full text-sm border border-outline-variant/30 rounded-xl px-3 py-2 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
            <option value="all" {{ ($booksFilter ?? 'all') === 'all' ? 'selected' : '' }}>All</option>
            <option value="open" {{ ($booksFilter ?? 'all') === 'open' ? 'selected' : '' }}>Open</option>
            <option value="closed" {{ ($booksFilter ?? 'all') === 'closed' ? 'selected' : '' }}>Closed</option>
          </select>
        </div>
        <div>
          <label class="block text-xs font-semibold text-on-surface-variant mb-1.5">Payouts</label>
          <select name="payouts" class="w-full text-sm border border-outline-variant/30 rounded-xl px-3 py-2 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
            <option value="all" {{ ($payoutsFilter ?? 'all') === 'all' ? 'selected' : '' }}>All</option>
            <option value="connected" {{ ($payoutsFilter ?? 'all') === 'connected' ? 'selected' : '' }}>Connected</option>
            <option value="not_connected" {{ ($payoutsFilter ?? 'all') === 'not_connected' ? 'selected' : '' }}>Not connected</option>
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
        @include('admin.partials.per-page', ['perPage' => $perPage ?? 10, 'selectId' => 'usersPerPage'])
      </div>
      <div class="mt-4 flex flex-wrap justify-end gap-2">
        <a
          href="{{ route('admin.users.export', request()->query()) }}"
          class="inline-flex items-center gap-2 border border-outline-variant/40 bg-white text-on-surface px-4 py-2 rounded-xl font-semibold text-sm hover:bg-surface-container-low transition-colors"
        >
          <span class="material-symbols-outlined text-[18px]">download</span>
          Export CSV
        </a>
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
              <th class="text-left px-5 py-3 font-semibold">Date</th>
              <th class="text-left px-5 py-3 font-semibold">{{ $roleFilter === 'user' ? 'Client' : ($roleFilter === 'artist' ? 'Artist' : ($roleFilter === 'studio' ? 'Studio' : 'User')) }}</th>
              <th class="text-left px-5 py-3 font-semibold">Email</th>
              <th class="text-left px-5 py-3 font-semibold">Location</th>
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
                <td class="px-5 py-4 text-on-surface-variant whitespace-nowrap">{{ $user['join_date_label'] }}</td>
                <td class="px-5 py-4">
                  <div class="flex items-center gap-3">
                    @if(!empty($user['avatar']))
                      <img src="{{ asset(ltrim($user['avatar'], '/')) }}" alt="" class="w-9 h-9 rounded-full object-cover">
                    @else
                      <div class="w-9 h-9 rounded-full bg-primary/10 flex items-center justify-center text-primary font-bold text-xs">{{ $user['initials'] }}</div>
                    @endif
                    <div>
                      <span class="font-semibold block">{{ $user['name'] }}</span>
                      <span class="text-[11px] text-on-surface-variant">{{ $user['role_label'] ?? ($user['role'] === 'artist' ? 'Artist' : ($user['role'] === 'studio' ? 'Studio' : 'Client')) }}</span>
                    </div>
                  </div>
                </td>
                <td class="px-5 py-4 text-on-surface-variant">{{ $user['email'] }}</td>
                <td class="px-5 py-4 text-on-surface-variant">{{ $user['location'] }}</td>
                <td class="px-5 py-4 font-semibold">{{ number_format($user['bookings']) }}</td>
                <td class="px-5 py-4 font-semibold">€{{ number_format($user['revenue'], 0) }}</td>
                <td class="px-5 py-4">
                  @include('admin.users.partials.status-cell', [
                    'status' => $user['status'],
                    'onboardingProgress' => $user['onboarding_progress'] ?? null,
                  ])
                </td>
                <td class="px-5 py-4">
                  <div class="flex flex-col items-start gap-1.5">
                    <div class="flex items-center gap-1">
                      <button type="button" class="w-8 h-8 rounded-lg flex items-center justify-center hover:bg-surface-container-low" onclick="event.stopPropagation(); toggleUserDetail({{ $user['id'] }})" title="View details">
                        <span class="material-symbols-outlined text-on-surface-variant text-lg user-toggle-icon" data-user-id="{{ $user['id'] }}">{{ $expanded ? 'expand_less' : 'visibility' }}</span>
                      </button>
                      <button
                        type="button"
                        class="js-admin-delete-user w-8 h-8 rounded-lg flex items-center justify-center hover:bg-red-50"
                        title="Delete user"
                        data-user-id="{{ $user['id'] }}"
                        data-user-name="{{ $user['name'] }}"
                        data-user-email="{{ $user['email'] }}"
                        data-delete-block-reason="{{ $user['delete_block_reason'] ?? '' }}"
                      >
                        <span class="material-symbols-outlined text-red-600 text-lg">delete</span>
                      </button>
                    </div>
                    @if(in_array($user['role'], ['artist', 'studio'], true))
                      <button
                        type="button"
                        class="js-admin-deactivate-user inline-flex items-center justify-center px-3.5 py-2 rounded-xl font-semibold text-xs transition-colors {{ !empty($user['is_deactivated']) ? 'bg-green-600 text-white hover:bg-green-700' : 'bg-red-600 text-white hover:bg-red-700' }}"
                        data-user-id="{{ $user['id'] }}"
                        data-user-name="{{ $user['name'] }}"
                        data-user-email="{{ $user['email'] }}"
                        data-user-role="{{ $user['role'] }}"
                        data-is-deactivated="{{ !empty($user['is_deactivated']) ? '1' : '0' }}"
                      >
                        {{ !empty($user['is_deactivated']) ? 'Activate' : 'Deactivate' }}
                      </button>
                    @endif
                  </div>
                </td>
              </tr>
              <tr class="detail-row" data-detail-for="{{ $user['id'] }}" style="{{ $expanded ? '' : 'display:none;' }}">
                <td colspan="8" class="p-0">
                  @include('admin.users.partials.detail-panel', ['user' => $user, 'open' => $expanded])
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="8" class="px-6 py-12 text-center text-sm text-on-surface-variant">No users found for this filter.</td>
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
              @include('admin.users.partials.status-cell', [
                'status' => $user['status'],
                'onboardingProgress' => $user['onboarding_progress'] ?? null,
              ])
            </div>
            <div class="flex flex-wrap gap-3 text-xs text-on-surface-variant mt-1">
              <span>Signed up {{ $user['join_date_label'] }}</span>
              <span>{{ $user['location'] }}</span>
              <span>{{ number_format($user['bookings']) }} bookings</span>
              <span>€{{ number_format($user['revenue'], 0) }}</span>
            </div>
            <div class="mt-3 flex flex-col items-start gap-2">
              <div class="flex flex-wrap gap-2">
                <button type="button" onclick="toggleUserDetail({{ $user['id'] }})" class="inline-flex items-center gap-1.5 bg-primary/10 text-primary px-3 py-1.5 rounded-lg font-semibold text-xs user-mobile-toggle" data-user-id="{{ $user['id'] }}">
                  <span class="material-symbols-outlined user-toggle-icon" data-user-id="{{ $user['id'] }}" style="font-size:16px;">{{ $expanded ? 'expand_less' : 'visibility' }}</span>
                  <span class="user-toggle-label" data-user-id="{{ $user['id'] }}">{{ $expanded ? 'Close' : 'View' }}</span>
                </button>
                <button
                  type="button"
                  class="js-admin-delete-user inline-flex items-center gap-1.5 bg-red-50 text-red-700 px-3 py-1.5 rounded-lg font-semibold text-xs"
                  data-user-id="{{ $user['id'] }}"
                  data-user-name="{{ $user['name'] }}"
                  data-user-email="{{ $user['email'] }}"
                  data-delete-block-reason="{{ $user['delete_block_reason'] ?? '' }}"
                >
                  <span class="material-symbols-outlined" style="font-size:16px;">delete</span>
                  Delete
                </button>
              </div>
              @if(in_array($user['role'], ['artist', 'studio'], true))
                <button
                  type="button"
                  class="js-admin-deactivate-user inline-flex items-center justify-center px-4 py-2.5 rounded-xl font-semibold text-sm transition-colors {{ !empty($user['is_deactivated']) ? 'bg-green-600 text-white hover:bg-green-700' : 'bg-red-600 text-white hover:bg-red-700' }}"
                  data-user-id="{{ $user['id'] }}"
                  data-user-name="{{ $user['name'] }}"
                  data-user-email="{{ $user['email'] }}"
                  data-user-role="{{ $user['role'] }}"
                  data-is-deactivated="{{ !empty($user['is_deactivated']) ? '1' : '0' }}"
                >
                  {{ !empty($user['is_deactivated']) ? 'Activate' : 'Deactivate' }}
                </button>
              @endif
            </div>
            <div class="mobile-detail-panel {{ $expanded ? '' : 'hidden' }}" data-mobile-detail-for="{{ $user['id'] }}">
              @include('admin.users.partials.detail-panel', ['user' => $user, 'open' => true])
            </div>
          </div>
        @empty
          <div class="p-8 text-center text-sm text-on-surface-variant">No users found for this filter.</div>
        @endforelse
      </div>

      @if($users->hasPages())
        <div class="px-5 py-4 border-t border-outline-variant/15">
          {{ $users->onEachSide(1)->links('admin.partials.pagination') }}
        </div>
      @endif
    </div>
  </div>
</main>

<div class="modal-backdrop p-4" id="deactivateUserModal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="deactivateUserModalTitle">
  <div class="modal-inner bg-white rounded-2xl w-full max-w-md shadow-2xl">
    <div class="flex items-center justify-between px-5 py-4 border-b border-outline-variant/15">
      <h3 id="deactivateUserModalTitle" class="text-lg font-bold text-on-surface">Deactivate account</h3>
      <button type="button" id="btnCloseDeactivateUserModal" class="w-9 h-9 rounded-xl flex items-center justify-center hover:bg-surface-container-low transition-colors" aria-label="Close">
        <span class="material-symbols-outlined text-on-surface-variant">close</span>
      </button>
    </div>
    <div class="p-5 space-y-2">
      <p class="text-sm text-on-surface" id="deactivateUserModalBody"></p>
      <p class="text-xs text-on-surface-variant" id="deactivateUserModalHint"></p>
      <p id="deactivateUserError" class="hidden text-sm text-error rounded-xl bg-error-container/30 border border-error/20 px-3 py-2"></p>
    </div>
    <div class="px-5 py-4 border-t border-outline-variant/15 flex items-center justify-end gap-3">
      <button type="button" id="btnCancelDeactivateUserModal" class="text-sm font-semibold text-on-surface-variant hover:text-on-surface px-4 py-2 rounded-xl transition-colors">Cancel</button>
      <button type="button" id="btnConfirmDeactivateUser" class="bg-red-600 text-white px-5 py-2.5 rounded-xl font-semibold text-sm hover:bg-red-700 transition-colors shadow-sm inline-flex items-center gap-2 disabled:opacity-60">
        <span class="material-symbols-outlined text-lg" id="btnConfirmDeactivateUserIcon">visibility_off</span>
        <span id="btnConfirmDeactivateUserLabel">Deactivate</span>
      </button>
    </div>
  </div>
</div>

<div class="modal-backdrop p-4" id="deleteUserModal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="deleteUserModalTitle">
  <div class="modal-inner bg-white rounded-2xl w-full max-w-md shadow-2xl">
    <div class="flex items-center justify-between px-5 py-4 border-b border-outline-variant/15">
      <h3 id="deleteUserModalTitle" class="text-lg font-bold text-on-surface">Delete user</h3>
      <button type="button" id="btnCloseDeleteUserModal" class="w-9 h-9 rounded-xl flex items-center justify-center hover:bg-surface-container-low transition-colors" aria-label="Close">
        <span class="material-symbols-outlined text-on-surface-variant">close</span>
      </button>
    </div>
    <div class="p-5 space-y-2">
      <p class="text-sm text-on-surface">
        Are you sure you want to permanently delete
        <span class="font-semibold" id="deleteUserModalName"></span>
        (<span class="text-on-surface-variant">(<span id="deleteUserModalEmail"></span>)</span>?
      </p>
      <p class="text-xs text-on-surface-variant">
        They will lose login access and receive a deletion email. Bookings, payouts, and payment history are kept. This cannot be undone.
      </p>
      <p id="deleteUserError" class="hidden text-sm text-error rounded-xl bg-error-container/30 border border-error/20 px-3 py-2"></p>
    </div>
    <div class="px-5 py-4 border-t border-outline-variant/15 flex items-center justify-end gap-3">
      <button type="button" id="btnCancelDeleteUserModal" class="text-sm font-semibold text-on-surface-variant hover:text-on-surface px-4 py-2 rounded-xl transition-colors">Cancel</button>
      <button type="button" id="btnConfirmDeleteUser" class="bg-red-600 text-white px-5 py-2.5 rounded-xl font-semibold text-sm hover:bg-red-700 transition-colors shadow-sm inline-flex items-center gap-2 disabled:opacity-60">
        <span class="material-symbols-outlined text-lg">delete</span>
        <span id="btnConfirmDeleteUserLabel">Delete user</span>
      </button>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script>
  let expandedUserId = {{ $expandedId ?: 'null' }};
  const DELETE_USER_URL_TEMPLATE = @json(route('admin.users.destroy', ['id' => '__ID__']));
  const DEACTIVATE_USER_URL_TEMPLATE = @json(route('admin.users.deactivate', ['id' => '__ID__']));
  const CSRF_TOKEN = @json(csrf_token());

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

    const modal = document.getElementById('deleteUserModal');
    const errorEl = document.getElementById('deleteUserError');
    const confirmBtn = document.getElementById('btnConfirmDeleteUser');
    const confirmLabel = document.getElementById('btnConfirmDeleteUserLabel');
    let pendingUserId = null;

    const deactivateModal = document.getElementById('deactivateUserModal');
    const deactivateErrorEl = document.getElementById('deactivateUserError');
    const deactivateConfirmBtn = document.getElementById('btnConfirmDeactivateUser');
    const deactivateConfirmLabel = document.getElementById('btnConfirmDeactivateUserLabel');
    const deactivateConfirmIcon = document.getElementById('btnConfirmDeactivateUserIcon');
    let pendingDeactivateUserId = null;
    let pendingDeactivateAction = true;

    function openDeactivateUserModal(userId, name, email, role, isDeactivated) {
      pendingDeactivateUserId = userId;
      pendingDeactivateAction = !isDeactivated;
      const roleLabel = role === 'studio' ? 'studio' : 'artist';
      document.getElementById('deactivateUserModalTitle').textContent = pendingDeactivateAction ? 'Deactivate account' : 'Activate account';
      document.getElementById('deactivateUserModalBody').innerHTML =
        (pendingDeactivateAction ? 'Deactivate ' : 'Activate ') +
        '<span class="font-semibold"></span>' +
        ' <span class="text-on-surface-variant">()</span>?';
      const body = document.getElementById('deactivateUserModalBody');
      body.querySelector('.font-semibold').textContent = name || 'this account';
      body.querySelector('.text-on-surface-variant').textContent = '(' + (email || '—') + ')';
      document.getElementById('deactivateUserModalHint').textContent = pendingDeactivateAction
        ? 'Their public ' + roleLabel + ' page will be hidden from clients and they will not be able to sign in. This is reversible.'
        : 'Their public ' + roleLabel + ' page will be visible again and they can sign in.';
      deactivateConfirmLabel.textContent = pendingDeactivateAction ? 'Deactivate' : 'Activate';
      deactivateConfirmIcon.textContent = pendingDeactivateAction ? 'visibility_off' : 'visibility';
      deactivateConfirmBtn.classList.toggle('bg-red-600', pendingDeactivateAction);
      deactivateConfirmBtn.classList.toggle('hover:bg-red-700', pendingDeactivateAction);
      deactivateConfirmBtn.classList.toggle('bg-amber-600', false);
      deactivateConfirmBtn.classList.toggle('hover:bg-amber-700', false);
      deactivateConfirmBtn.classList.toggle('bg-green-600', !pendingDeactivateAction);
      deactivateConfirmBtn.classList.toggle('hover:bg-green-700', !pendingDeactivateAction);
      deactivateErrorEl.classList.add('hidden');
      deactivateErrorEl.textContent = '';
      deactivateConfirmBtn.disabled = false;
      deactivateModal.setAttribute('aria-hidden', 'false');
      deactivateModal.classList.add('modal-visible');
      requestAnimationFrame(function () {
        deactivateModal.classList.add('modal-open');
      });
    }

    function closeDeactivateUserModal() {
      deactivateModal.classList.remove('modal-open');
      setTimeout(function () {
        deactivateModal.classList.remove('modal-visible');
        deactivateModal.setAttribute('aria-hidden', 'true');
        pendingDeactivateUserId = null;
        deactivateErrorEl.classList.add('hidden');
        deactivateErrorEl.textContent = '';
        deactivateConfirmBtn.disabled = false;
      }, 200);
    }

    function openDeleteUserModal(userId, name, email, blockReason) {
      pendingUserId = userId;
      document.getElementById('deleteUserModalName').textContent = name || 'this user';
      document.getElementById('deleteUserModalEmail').textContent = email || '—';
      confirmLabel.textContent = 'Delete user';

      const reason = (blockReason || '').trim();
      if (reason) {
        errorEl.textContent = reason;
        errorEl.classList.remove('hidden');
        confirmBtn.disabled = true;
        pendingUserId = null;
      } else {
        errorEl.classList.add('hidden');
        errorEl.textContent = '';
        confirmBtn.disabled = false;
      }

      modal.setAttribute('aria-hidden', 'false');
      modal.classList.add('modal-visible');
      requestAnimationFrame(function () {
        modal.classList.add('modal-open');
      });
    }

    function closeDeleteUserModal() {
      modal.classList.remove('modal-open');
      setTimeout(function () {
        modal.classList.remove('modal-visible');
        modal.setAttribute('aria-hidden', 'true');
        pendingUserId = null;
        errorEl.classList.add('hidden');
        errorEl.textContent = '';
        confirmBtn.disabled = false;
        confirmLabel.textContent = 'Delete user';
      }, 200);
    }

    // Capture phase so this runs before row onclick / stopPropagation on parents.
    document.addEventListener('click', function (event) {
      const deactivateBtn = event.target.closest('.js-admin-deactivate-user');
      if (deactivateBtn) {
        event.preventDefault();
        event.stopPropagation();
        openDeactivateUserModal(
          parseInt(deactivateBtn.getAttribute('data-user-id'), 10),
          deactivateBtn.getAttribute('data-user-name') || '',
          deactivateBtn.getAttribute('data-user-email') || '',
          deactivateBtn.getAttribute('data-user-role') || 'artist',
          deactivateBtn.getAttribute('data-is-deactivated') === '1'
        );
        return;
      }

      const btn = event.target.closest('.js-admin-delete-user');
      if (!btn) return;
      event.preventDefault();
      event.stopPropagation();
      openDeleteUserModal(
        parseInt(btn.getAttribute('data-user-id'), 10),
        btn.getAttribute('data-user-name') || '',
        btn.getAttribute('data-user-email') || '',
        btn.getAttribute('data-delete-block-reason') || ''
      );
    }, true);

    document.getElementById('btnCloseDeactivateUserModal').addEventListener('click', closeDeactivateUserModal);
    document.getElementById('btnCancelDeactivateUserModal').addEventListener('click', closeDeactivateUserModal);
    deactivateModal.addEventListener('click', function (event) {
      if (event.target === deactivateModal) closeDeactivateUserModal();
    });

    deactivateConfirmBtn.addEventListener('click', function () {
      if (!pendingDeactivateUserId || deactivateConfirmBtn.disabled) return;
      deactivateConfirmBtn.disabled = true;
      deactivateConfirmLabel.textContent = pendingDeactivateAction ? 'Deactivating…' : 'Activating…';
      deactivateErrorEl.classList.add('hidden');
      deactivateErrorEl.textContent = '';

      fetch(DEACTIVATE_USER_URL_TEMPLATE.replace('__ID__', String(pendingDeactivateUserId)), {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': CSRF_TOKEN,
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({ deactivate: pendingDeactivateAction }),
      })
        .then(function (res) {
          return res.json().then(function (data) {
            return { ok: res.ok, data: data };
          }).catch(function () {
            return { ok: res.ok, data: null };
          });
        })
        .then(function (result) {
          if (!result.ok || !result.data || !result.data.success) {
            deactivateErrorEl.textContent = (result.data && result.data.message)
              ? result.data.message
              : 'Could not update this account.';
            deactivateErrorEl.classList.remove('hidden');
            deactivateConfirmBtn.disabled = false;
            deactivateConfirmLabel.textContent = pendingDeactivateAction ? 'Deactivate' : 'Activate';
            return;
          }
          window.location.reload();
        })
        .catch(function () {
          deactivateErrorEl.textContent = 'Could not update this account. Please try again.';
          deactivateErrorEl.classList.remove('hidden');
          deactivateConfirmBtn.disabled = false;
          deactivateConfirmLabel.textContent = pendingDeactivateAction ? 'Deactivate' : 'Activate';
        });
    });

    document.getElementById('btnCloseDeleteUserModal').addEventListener('click', closeDeleteUserModal);
    document.getElementById('btnCancelDeleteUserModal').addEventListener('click', closeDeleteUserModal);
    modal.addEventListener('click', function (event) {
      if (event.target === modal) closeDeleteUserModal();
    });

    confirmBtn.addEventListener('click', function () {
      if (!pendingUserId || confirmBtn.disabled) return;
      confirmBtn.disabled = true;
      confirmLabel.textContent = 'Deleting…';
      errorEl.classList.add('hidden');
      errorEl.textContent = '';

      fetch(DELETE_USER_URL_TEMPLATE.replace('__ID__', String(pendingUserId)), {
        method: 'DELETE',
        headers: {
          'Accept': 'application/json',
          'X-CSRF-TOKEN': CSRF_TOKEN,
          'X-Requested-With': 'XMLHttpRequest',
        },
      })
        .then(function (res) {
          return res.json().then(function (data) {
            return { ok: res.ok, status: res.status, data: data };
          }).catch(function () {
            return { ok: res.ok, status: res.status, data: null };
          });
        })
        .then(function (result) {
          if (!result.ok || !result.data || !result.data.success) {
            errorEl.textContent = (result.data && result.data.message)
              ? result.data.message
              : 'Could not delete this user.';
            errorEl.classList.remove('hidden');
            confirmBtn.disabled = false;
            confirmLabel.textContent = 'Delete user';
            return;
          }
          window.location.reload();
        })
        .catch(function () {
          errorEl.textContent = 'Could not delete this user. Please try again.';
          errorEl.classList.remove('hidden');
          confirmBtn.disabled = false;
          confirmLabel.textContent = 'Delete user';
        });
    });
  });
</script>
@endsection
