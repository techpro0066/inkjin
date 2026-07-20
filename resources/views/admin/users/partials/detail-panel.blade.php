<div class="detail-panel {{ !empty($open) ? 'open' : '' }} bg-surface-container-low/50 border-t border-outline-variant/15">
  <div class="p-6 space-y-5">
    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-6 gap-3">
      <div class="bg-white rounded-xl p-3 border border-outline-variant/15 text-center">
        <p class="text-lg font-bold">{{ number_format($user['bookings']) }}</p>
        <p class="text-xs text-on-surface-variant">Bookings</p>
      </div>
      <div class="bg-white rounded-xl p-3 border border-outline-variant/15 text-center">
        <p class="text-lg font-bold">€{{ number_format($user['revenue'], 0) }}</p>
        <p class="text-xs text-on-surface-variant">Revenue</p>
      </div>
      <div class="bg-white rounded-xl p-3 border border-outline-variant/15 text-center">
        <p class="text-lg font-bold">{{ number_format($user['designs']) }}</p>
        <p class="text-xs text-on-surface-variant">Designs</p>
      </div>
      <div class="bg-white rounded-xl p-3 border border-outline-variant/15 text-center">
        <p class="text-lg font-bold">{{ number_format($user['portfolio_items']) }}</p>
        <p class="text-xs text-on-surface-variant">Portfolio Items</p>
      </div>
      <div class="bg-white rounded-xl p-3 border border-outline-variant/15 text-center">
        <p class="text-lg font-bold">{{ $user['email_verified'] ? 'Yes' : 'No' }}</p>
        <p class="text-xs text-on-surface-variant">Email Verified</p>
      </div>
      <div class="bg-white rounded-xl p-3 border border-outline-variant/15 text-center">
        <p class="text-lg font-bold">{{ $user['join_date_label'] }}</p>
        <p class="text-xs text-on-surface-variant">Joined</p>
      </div>
    </div>

    <div class="bg-white rounded-xl border border-outline-variant/15 p-4 space-y-0">
      <h4 class="text-sm font-bold mb-2">Profile Info</h4>
      <div class="flex justify-between py-2 border-b border-outline-variant/10 gap-4">
        <span class="text-sm text-on-surface-variant shrink-0">Name</span>
        <span class="text-sm font-semibold text-on-surface text-right">{{ $user['name'] }}</span>
      </div>
      <div class="flex justify-between py-2 border-b border-outline-variant/10 gap-4">
        <span class="text-sm text-on-surface-variant shrink-0">Email</span>
        <span class="text-sm font-semibold text-on-surface text-right break-all">{{ $user['email'] }}</span>
      </div>
      <div class="flex justify-between py-2 border-b border-outline-variant/10 gap-4">
        <span class="text-sm text-on-surface-variant shrink-0">Phone</span>
        <span class="text-sm font-semibold text-on-surface text-right">{{ $user['phone'] }}</span>
      </div>
      @if($user['role'] === 'artist' && $user['username'])
        <div class="flex justify-between py-2 border-b border-outline-variant/10 gap-4">
          <span class="text-sm text-on-surface-variant shrink-0">Username</span>
          <span class="text-sm font-semibold text-on-surface text-right"><a href="https://inkjin.com/{{ '@' . $user['username'] }}" target="_blank">{{ '@' . $user['username'] }}</a></span>
        </div>
      @endif
      <div class="flex justify-between py-2 border-b border-outline-variant/10 gap-4">
        <span class="text-sm text-on-surface-variant shrink-0">Role</span>
        <span class="text-sm font-semibold text-on-surface text-right">{{ $user['role'] === 'artist' ? 'Artist' : 'Client' }}</span>
      </div>
      <div class="flex justify-between py-2 border-b border-outline-variant/10 gap-4">
        <span class="text-sm text-on-surface-variant shrink-0">Join Date</span>
        <span class="text-sm font-semibold text-on-surface text-right">{{ $user['join_date_label'] }}</span>
      </div>
      <div class="flex justify-between py-2 gap-4">
        <span class="text-sm text-on-surface-variant shrink-0">Location</span>
        <span class="text-sm font-semibold text-on-surface text-right">{{ $user['location'] }}</span>
      </div>
    </div>

    @if($user['role'] === 'artist' && !empty($user['onboarding_progress']))
      @include('admin.users.partials.onboarding-progress', ['user' => $user])
    @endif

    @if($user['role'] === 'artist')
      <div class="bg-white rounded-xl border border-outline-variant/15 p-4 space-y-0">
        <h4 class="text-sm font-bold mb-2">Studio & Scheduling</h4>
        <div class="flex justify-between py-2 border-b border-outline-variant/10 gap-4">
          <span class="text-sm text-on-surface-variant shrink-0">Studio</span>
          <span class="text-sm font-semibold text-on-surface text-right">{{ $user['studio'] }}</span>
        </div>
        <div class="flex justify-between py-2 border-b border-outline-variant/10 gap-4">
          <span class="text-sm text-on-surface-variant shrink-0">Address</span>
          <span class="text-sm font-semibold text-on-surface text-right">{{ $user['studio_address'] ?: '—' }}</span>
        </div>
        <div class="flex justify-between py-2 border-b border-outline-variant/10 gap-4">
          <span class="text-sm text-on-surface-variant shrink-0">Scheduling</span>
          <span class="text-sm font-semibold text-on-surface text-right">{{ $user['scheduling_type'] ?: '—' }}</span>
        </div>
        <div class="flex justify-between py-2 border-b border-outline-variant/10 gap-4">
          <span class="text-sm text-on-surface-variant shrink-0">Google Calendar</span>
          <span class="text-sm font-semibold text-on-surface text-right">{{ $user['google_calendar_connected'] ? 'Connected' : 'Not connected' }}</span>
        </div>
        <div class="flex justify-between py-2 gap-4">
          <span class="text-sm text-on-surface-variant shrink-0">Availability slots</span>
          <span class="text-sm font-semibold text-on-surface text-right">{{ number_format($user['availability_slots']) }}</span>
        </div>
      </div>

      @if(!empty($user['styles']))
        <div>
          <h4 class="text-sm font-bold mb-2">Styles</h4>
          <div class="flex flex-wrap gap-2">
            @foreach($user['styles'] as $style)
              <span class="inline-block bg-primary/5 text-primary text-xs font-semibold px-3 py-1 rounded-full">{{ $style }}</span>
            @endforeach
          </div>
        </div>
      @endif

      <div class="flex flex-wrap gap-2 text-xs font-semibold text-primary">
        <span>{{ number_format($user['designs']) }} designs</span>
        <span class="text-outline">·</span>
        <span>{{ number_format($user['portfolio_items']) }} portfolio items</span>
      </div>
    @endif

    <div class="bg-white rounded-xl border border-outline-variant/15 p-4 space-y-0">
      <h4 class="text-sm font-bold mb-2">Payments</h4>
      <div class="flex justify-between py-2 border-b border-outline-variant/10 gap-4">
        <span class="text-sm text-on-surface-variant shrink-0">Payment type</span>
        <span class="text-sm font-semibold text-on-surface text-right">{{ $user['payment_type'] ?: '—' }}</span>
      </div>
      <div class="flex justify-between py-2 gap-4">
        <span class="text-sm text-on-surface-variant shrink-0">Payment status</span>
        <span class="text-sm font-semibold text-on-surface text-right">{{ $user['payment_status'] ?: '—' }}</span>
      </div>
    </div>

    <div>
      <h4 class="text-sm font-bold mb-2">Payout History</h4>
      <div class="bg-white rounded-xl border border-outline-variant/15 overflow-hidden">
        <table class="w-full text-sm">
          <thead>
            <tr class="bg-surface-container-low/50 text-on-surface-variant text-xs uppercase tracking-wider">
              <th class="text-left px-4 py-2 font-semibold">Period</th>
              <th class="text-left px-4 py-2 font-semibold">Amount</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td colspan="2" class="px-4 py-3 text-sm text-outline">No payouts yet</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
