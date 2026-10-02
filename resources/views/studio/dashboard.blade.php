@extends('layouts.studio-dashboard-layout')

@section('title', 'Home')

@section('content')
  <div class="head">
    <div>
      <h1>Welcome, {{ $studioName }}</h1>
      <div class="sub">
        @if ($primaryArtistName)
          Your share of {{ $primaryArtistName }}'s bookings, in one place.
        @else
          Your studio share of artist bookings, in one place.
        @endif
      </div>
    </div>
    <button type="button" class="btn ghost" data-locked="New booking"><span class="ms">add</span>New booking</button>
  </div>

  @unless ($profileComplete)
    <div class="card" style="margin-top:22px;background:linear-gradient(135deg,#3E007C,#6A1FB0);color:#fff;border:0">
      <div style="padding:22px 24px;display:flex;gap:18px;align-items:center;flex-wrap:wrap">
        <div style="width:46px;height:46px;border-radius:12px;background:rgba(255,255,255,.16);display:flex;align-items:center;justify-content:center;flex:none">
          <span class="ms" style="font-size:24px">storefront</span>
        </div>
        <div style="flex:1;min-width:220px">
          <b style="font-size:17px">Complete your studio profile</b>
          <div style="font-size:13.5px;opacity:.9;margin-top:3px;line-height:1.5">Get full access to your studio dashboard. Free for studios. No monthly fee, no setup fee, no commission.</div>
        </div>
        <div class="row" style="gap:8px;flex-wrap:wrap">
          <button type="button" class="btn ghost" data-open-profile-prompt style="background:rgba(255,255,255,.12);color:#fff;border-color:rgba(255,255,255,.35)">See what you get</button>
          <a class="btn" href="{{ route('studio.onboarding.profile') }}" style="background:#fff;color:#3E007C">Complete profile</a>
        </div>
      </div>
    </div>
  @endunless

  @unless ($stripeConnected)
    <div class="card" id="nostripe" style="margin-top:14px;border-color:#F5C2C2;background:#FDF3F3">
      <div style="padding:16px 22px;display:flex;gap:14px;align-items:center;flex-wrap:wrap">
        <span class="ms" style="color:#C62828">account_balance</span>
        <div style="flex:1;min-width:220px">
          <b>Connect Stripe to get your share</b>
          <div class="faint" style="font-size:12.5px;margin-top:2px">
            @if ($primaryArtistName)
              Until then, {{ $primaryArtistName }} is paid directly and your share isn't collected.
            @else
              Until then, artists are paid directly and your share isn't collected.
            @endif
          </div>
        </div>
        <button type="button" class="btn" data-locked="Money">Connect Stripe</button>
      </div>
    </div>
  @endunless

  <div class="grid" style="grid-template-columns:repeat(3,1fr);gap:14px;margin-top:16px">
    <div class="card" style="padding:18px 20px">
      <div class="faint" style="font-size:12.5px">Your share this month</div>
      <div style="font-size:24px;font-weight:800;margin-top:4px">€0.00</div>
      <div class="faint" style="font-size:12px;margin-top:2px">From 0 bookings</div>
    </div>
    <div class="card" style="padding:18px 20px">
      <div class="faint" style="font-size:12.5px">Pending</div>
      <div style="font-size:24px;font-weight:800;margin-top:4px">€0.00</div>
      <div class="faint" style="font-size:12px;margin-top:2px">Released when each cancellation window ends</div>
    </div>
    <div class="card" style="padding:18px 20px">
      <div class="faint" style="font-size:12.5px">Next payout</div>
      <div style="font-size:24px;font-weight:800;margin-top:4px">€0.00</div>
      <div class="faint" style="font-size:12px;margin-top:2px">
        @if ($stripeConnected)
          After your next released booking
        @else
          Connect Stripe to receive payouts
        @endif
      </div>
    </div>
  </div>

  <div class="grid" style="grid-template-columns:1fr 1.5fr;gap:14px;margin-top:14px">
    <div class="card">
      <div class="ch">
        <h3>Your artists</h3>
        <a href="#" data-locked="Artists" style="font-size:13px;font-weight:700;color:var(--pri);text-decoration:none">View</a>
      </div>
      <div style="padding:14px 22px 18px">
        @forelse ($artists as $artistDetail)
          @php
            $name = $artistDetail->publicDisplayName();
            if ($name === '' || $name === 'Artist') {
              $fallback = trim(($artistDetail->user?->first_name ?? '').' '.($artistDetail->user?->last_name ?? ''));
              $name = $fallback !== '' ? $fallback : ($artistDetail->user_name ?? 'Artist');
            }
            $handle = trim((string) ($artistDetail->user_name ?? ''));
            $avatar = trim((string) ($artistDetail->avatar ?? ''));
            $initials = $artistDetail->publicDisplayInitials();
            $relationshipLabels = [
              'co_owner' => 'Co-owner',
              'resident' => 'Resident',
              'collective_member' => 'Collective member',
              'apprentice' => 'Apprentice',
              'other' => 'Other',
            ];
            $rel = $relationshipLabels[$artistDetail->studio_relationship_type ?? ''] ?? null;
            $artistPct = (int) ($artistDetail->studio_revenue_artist_percent ?? 50);
            $artistPct = max(0, min(100, $artistPct));
            $studioPct = 100 - $artistPct;
          @endphp
          <div class="row" style="gap:12px;{{ ! $loop->first ? 'margin-top:14px' : '' }}">
            <div class="avatar" @if($avatar !== '') style="background-image:url('{{ asset($avatar) }}')" @endif>
              @if ($avatar === '')
                {{ $initials }}
              @endif
            </div>
            <div style="min-width:0;flex:1">
              <b style="display:block">{{ $name }}</b>
              <div class="faint" style="font-size:12.5px;margin-top:2px;line-height:1.4">
                @if ($handle !== '')
                  {{ '@'.$handle }}
                @endif
                @if ($rel)
                  · {{ $rel }}
                @endif
                · Revenue split: {{ explode(' ', $name)[0] }} {{ $artistPct }}%, you {{ $studioPct }}%
              </div>
            </div>
          </div>
        @empty
          <div class="faint" style="font-size:13.5px;line-height:1.45">No linked artists yet. When an artist invites your studio, they’ll show up here.</div>
        @endforelse
      </div>
    </div>

    <div class="card">
      <div class="ch">
        <h3>Recent bookings at your studio</h3>
        <a href="#" data-locked="Bookings" style="font-size:13px;font-weight:700;color:var(--pri);text-decoration:none">All bookings</a>
      </div>
      <div style="padding:0 6px 8px">
        <table>
          <thead>
            <tr>
              <th>Date</th>
              <th>Client</th>
              <th>Total</th>
              <th>Your share</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td colspan="5" class="faint" style="text-align:center;padding:28px 16px">No bookings yet.</td>
            </tr>
          </tbody>
        </table>
        @if ($primaryArtistName)
          <div class="faint" style="font-size:12px;padding:8px 16px 10px">Clients' contact details stay with {{ explode(' ', $primaryArtistName)[0] }}. Your share is after the booking fee and your part of Stripe's fee.</div>
        @endif
      </div>
    </div>
  </div>
@endsection
