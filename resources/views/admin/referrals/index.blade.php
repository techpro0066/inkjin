@extends('layouts.admin_dashboard_layout')

@section('title', 'Referrals')

@section('content')
@php
  use App\Models\ArtistReferral;
  use App\Services\ArtistReferralRewardService;
  $statusClasses = [
    'pending' => 'bg-amber-50 text-amber-700',
    'sent_to_admin' => 'bg-blue-50 text-blue-700',
    'rewarded' => 'bg-green-50 text-green-700',
    'rejected' => 'bg-red-50 text-red-700',
  ];
@endphp
<main class="main-content flex-1 min-h-screen">
  <div class="p-6 md:p-10 lg:p-12 max-w-7xl">
    <div class="mb-6 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
      <div>
        <h2 class="text-3xl font-extrabold text-on-surface tracking-tight">Referrals</h2>
        <p class="text-on-surface-variant mt-1">Artist referral rewards awaiting manual payout.</p>
      </div>
      <p class="text-sm font-semibold text-on-surface-variant">{{ number_format($total) }} {{ Str::plural('referral', $total) }}</p>
    </div>

    <div id="referralActionAlert" class="hidden rounded-xl px-4 py-3 text-sm mb-6 border"></div>

    <form method="GET" class="bg-surface-container-low rounded-2xl p-5 mb-6 border border-outline-variant/20">
      <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <div class="xl:col-span-2">
          <label for="referralSearch" class="block text-xs font-semibold text-on-surface-variant mb-1.5">Search</label>
          <div class="relative">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-lg">search</span>
            <input id="referralSearch" name="q" value="{{ $filters['q'] }}" placeholder="Referrer or referred artist…" class="w-full text-sm border border-outline-variant/30 rounded-xl pl-9 pr-3 py-2 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
          </div>
        </div>
        <div>
          <label for="referralStatus" class="block text-xs font-semibold text-on-surface-variant mb-1.5">Status</label>
          <select id="referralStatus" name="status" class="w-full text-sm border border-outline-variant/30 rounded-xl px-3 py-2 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
            <option value="all">All statuses</option>
            @foreach($statuses as $status)
              <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ ArtistReferralRewardService::statusLabel($status) }}</option>
            @endforeach
          </select>
        </div>
        @include('admin.partials.per-page', ['perPage' => $perPage ?? ($filters['per_page'] ?? 10), 'selectId' => 'referralsPerPage'])
      </div>
      <div class="mt-4 flex flex-wrap justify-end gap-2">
        <a href="{{ route('admin.referrals.index') }}" class="px-4 py-2 rounded-xl text-sm font-semibold border border-outline-variant/30 bg-white text-on-surface-variant hover:text-on-surface">Clear</a>
        <button class="inline-flex items-center gap-2 bg-primary text-white px-4 py-2 rounded-xl font-semibold text-sm hover:bg-primary-container">
          <span class="material-symbols-outlined text-[18px]">filter_alt</span> Apply filters
        </button>
      </div>
    </form>

    <div class="bg-white rounded-2xl shadow-sm border border-outline-variant/20 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm min-w-[900px]">
          <thead>
            <tr class="bg-surface-container-low/50 text-on-surface-variant text-xs uppercase tracking-wider">
              <th class="text-left px-5 py-3 font-semibold">Referrer</th>
              <th class="text-left px-5 py-3 font-semibold">Referred artist</th>
              <th class="text-left px-5 py-3 font-semibold">Status</th>
              <th class="text-left px-5 py-3 font-semibold">Reward</th>
              <th class="text-left px-5 py-3 font-semibold">Qualified booking</th>
              <th class="text-right px-5 py-3 font-semibold">Action</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-outline-variant/10">
            @forelse($referrals as $referral)
              <tr class="hover:bg-surface-container-low/50">
                <td class="px-5 py-4">
                  <p class="font-semibold">{{ ArtistReferralRewardService::displayName($referral->referrer) }}</p>
                  <p class="text-xs text-outline">{{ $referral->referrer?->email }}</p>
                </td>
                <td class="px-5 py-4">
                  <p class="font-semibold">{{ ArtistReferralRewardService::displayName($referral->referred) }}</p>
                  <p class="text-xs text-outline">{{ $referral->referred?->email }}</p>
                </td>
                <td class="px-5 py-4">
                  <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold {{ $statusClasses[$referral->status] ?? 'bg-gray-100 text-gray-700' }}">
                    {{ ArtistReferralRewardService::statusLabel($referral->status) }}
                  </span>
                </td>
                <td class="px-5 py-4 font-semibold whitespace-nowrap">${{ number_format((float) $referral->reward_amount, 2) }}</td>
                <td class="px-5 py-4">
                  @if($referral->qualifiedBooking)
                    <a href="{{ route('admin.bookings.show', $referral->qualifiedBooking) }}" class="font-semibold text-primary hover:underline">
                      {{ $referral->qualifiedBooking->referenceLabel() }}
                    </a>
                    <p class="text-xs text-outline">#{{ $referral->qualified_booking_id }}</p>
                  @else
                    <span class="text-on-surface-variant">—</span>
                  @endif
                </td>
                <td class="px-5 py-4 text-right whitespace-nowrap">
                  <div class="inline-flex items-center justify-end gap-2">
                    <button
                      type="button"
                      class="js-view-referral inline-flex items-center justify-center w-8 h-8 rounded-xl text-primary bg-primary/10 hover:bg-primary hover:text-white transition-colors"
                      aria-label="View referral details"
                      data-referrer-name="{{ ArtistReferralRewardService::displayName($referral->referrer) }}"
                      data-referrer-email="{{ $referral->referrer?->email }}"
                      data-referred-name="{{ ArtistReferralRewardService::displayName($referral->referred) }}"
                      data-referred-email="{{ $referral->referred?->email }}"
                      data-signed-up="{{ $referral->created_at?->format('M j, Y g:i A') }}"
                      data-status-label="{{ ArtistReferralRewardService::statusLabel($referral->status) }}"
                      data-reward="${{ number_format((float) $referral->reward_amount, 2) }}"
                      data-fee-waived="{{ $referral->fee_waived ? 'Yes' : 'No' }}"
                      data-qualified-booking="{{ $referral->qualifiedBooking?->referenceLabel() }}"
                      data-qualified-booking-url="{{ $referral->qualifiedBooking ? route('admin.bookings.show', $referral->qualifiedBooking) : '' }}"
                      data-qualified-at="{{ $referral->qualified_at?->format('M j, Y g:i A') }}"
                      data-admin-notified="{{ $referral->admin_notified_at?->format('M j, Y g:i A') }}"
                      data-reward-paid="{{ $referral->reward_paid_at?->format('M j, Y g:i A') }}"
                      data-stripe-transfer="{{ $referral->stripe_transfer_id }}"
                      data-rejection-reason="{{ $referral->rejection_reason }}"
                      data-rejected-at="{{ $referral->rejected_at?->format('M j, Y g:i A') }}"
                    >
                      <span class="material-symbols-outlined text-[18px]">visibility</span>
                    </button>
                    @if($referral->status === ArtistReferral::STATUS_SENT_TO_ADMIN)
                      <button
                        type="button"
                        class="js-reject-referral inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold border border-red-200 text-red-700 bg-red-50 hover:bg-red-100 transition-colors"
                        data-referrer-name="{{ ArtistReferralRewardService::displayName($referral->referrer) }}"
                        data-reject-url="{{ route('admin.referrals.reject', $referral) }}"
                      >
                        <span class="material-symbols-outlined text-[16px]">block</span>
                        Reject
                      </button>
                      <button
                        type="button"
                        class="js-send-referral-reward inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold bg-primary text-white hover:bg-primary-container transition-colors"
                        data-referral-id="{{ $referral->id }}"
                        data-referrer-name="{{ ArtistReferralRewardService::displayName($referral->referrer) }}"
                        data-reward-amount="${{ number_format((float) $referral->reward_amount, 2) }}"
                        data-send-url="{{ route('admin.referrals.send-reward', $referral) }}"
                      >
                        <span class="material-symbols-outlined text-[16px]">payments</span>
                        Send reward
                      </button>
                    @endif
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="px-5 py-12 text-center text-on-surface-variant">No referrals match these filters.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
      @if($referrals->hasPages())
        <div class="px-5 py-4 border-t border-outline-variant/15">
          {{ $referrals->onEachSide(1)->links('admin.partials.pagination') }}
        </div>
      @endif
    </div>
  </div>
</main>

<div id="sendReferralRewardModal" class="hidden fixed inset-0 z-[200] flex items-center justify-center p-4 bg-black/50" role="dialog" aria-modal="true" aria-labelledby="sendReferralRewardTitle">
  <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl">
    <h5 id="sendReferralRewardTitle" class="text-lg font-bold text-on-surface mb-2">Send reward to artist?</h5>
    <p class="text-on-surface-variant text-sm mb-2">
      You are about to send the referral reward to <strong id="sendReferralRewardReferrer">the referrer</strong>'s connected Stripe account.
    </p>
    <p class="text-on-surface-variant text-sm mb-6">
      Reward amount: <strong id="sendReferralRewardAmount">$0.00</strong>. The referring artist will also receive an email confirmation.
    </p>
    <div id="sendReferralRewardError" class="hidden rounded-xl px-4 py-3 text-sm mb-4 bg-red-50 text-red-800 border border-red-200"></div>
    <div class="flex justify-end gap-3">
      <button type="button" id="cancelSendReferralReward" class="rounded-xl px-5 py-2.5 text-sm font-semibold text-on-surface hover:bg-surface-container-low">Cancel</button>
      <button type="button" id="confirmSendReferralReward" class="rounded-xl px-5 py-2.5 text-sm font-semibold bg-primary text-white hover:bg-primary-container">Confirm &amp; send reward</button>
    </div>
  </div>
</div>

<div id="rejectReferralModal" class="hidden fixed inset-0 z-[200] flex items-center justify-center p-4 bg-black/50" role="dialog" aria-modal="true" aria-labelledby="rejectReferralTitle">
  <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl">
    <h5 id="rejectReferralTitle" class="text-lg font-bold text-on-surface mb-2">Reject referral reward?</h5>
    <p class="text-on-surface-variant text-sm mb-4">
      You are about to reject the referral reward for <strong id="rejectReferralReferrer">the referrer</strong>. The referring artist will be notified by email.
    </p>
    <div class="mb-4">
      <label for="rejectReferralReason" class="block text-xs font-semibold text-on-surface-variant mb-1.5">Reason for rejection <span class="text-red-600">*</span></label>
      <textarea
        id="rejectReferralReason"
        rows="4"
        maxlength="2000"
        placeholder="Explain why this referral reward is being rejected…"
        class="w-full text-sm border border-outline-variant/30 rounded-xl px-3 py-2 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30 resize-y min-h-[100px]"
      ></textarea>
      <p class="text-xs text-on-surface-variant mt-1">Minimum 10 characters. This will be included in the email to the artist.</p>
    </div>
    <div id="rejectReferralError" class="hidden rounded-xl px-4 py-3 text-sm mb-4 bg-red-50 text-red-800 border border-red-200"></div>
    <div class="flex justify-end gap-3">
      <button type="button" id="cancelRejectReferral" class="rounded-xl px-5 py-2.5 text-sm font-semibold text-on-surface hover:bg-surface-container-low">Cancel</button>
      <button type="button" id="confirmRejectReferral" class="rounded-xl px-5 py-2.5 text-sm font-semibold bg-red-600 text-white hover:bg-red-700">Confirm rejection</button>
    </div>
  </div>
</div>

<div id="referralDetailsModal" class="hidden fixed inset-0 z-[200] flex items-center justify-center p-4 bg-black/50" role="dialog" aria-modal="true" aria-labelledby="referralDetailsTitle">
  <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-xl max-h-[90vh] overflow-y-auto" onclick="event.stopPropagation()">
    <div class="flex items-start justify-between gap-3 mb-4">
      <h5 id="referralDetailsTitle" class="text-lg font-bold text-on-surface">Referral details</h5>
      <button type="button" id="closeReferralDetails" class="rounded-lg p-1 text-on-surface-variant hover:bg-surface-container-low transition-colors" aria-label="Close">
        <span class="material-symbols-outlined text-[22px]">close</span>
      </button>
    </div>
    <dl class="space-y-3 text-sm">
      <div class="flex items-start justify-between gap-4">
        <dt class="text-on-surface-variant shrink-0">Signed up</dt>
        <dd id="detailSignedUp" class="text-on-surface text-right"></dd>
      </div>
      <div class="flex items-start justify-between gap-4">
        <dt class="text-on-surface-variant shrink-0">Referrer</dt>
        <dd class="text-right">
          <p id="detailReferrerName" class="font-semibold text-on-surface"></p>
          <p id="detailReferrerEmail" class="text-xs text-outline break-all"></p>
        </dd>
      </div>
      <div class="flex items-start justify-between gap-4">
        <dt class="text-on-surface-variant shrink-0">Referred artist</dt>
        <dd class="text-right">
          <p id="detailReferredName" class="font-semibold text-on-surface"></p>
          <p id="detailReferredEmail" class="text-xs text-outline break-all"></p>
        </dd>
      </div>
      <div class="flex items-start justify-between gap-4">
        <dt class="text-on-surface-variant shrink-0">Status</dt>
        <dd id="detailStatus" class="font-semibold text-on-surface text-right"></dd>
      </div>
      <div class="flex items-start justify-between gap-4">
        <dt class="text-on-surface-variant shrink-0">Reward</dt>
        <dd id="detailReward" class="font-semibold text-on-surface text-right"></dd>
      </div>
      <div class="flex items-start justify-between gap-4">
        <dt class="text-on-surface-variant shrink-0">Fee waived</dt>
        <dd id="detailFeeWaived" class="text-on-surface text-right"></dd>
      </div>
      <div class="flex items-start justify-between gap-4">
        <dt class="text-on-surface-variant shrink-0">Qualified booking</dt>
        <dd id="detailQualifiedBooking" class="text-on-surface text-right"></dd>
      </div>
      <div class="flex items-start justify-between gap-4">
        <dt class="text-on-surface-variant shrink-0">Qualified at</dt>
        <dd id="detailQualifiedAt" class="text-on-surface text-right"></dd>
      </div>
      <div class="flex items-start justify-between gap-4">
        <dt class="text-on-surface-variant shrink-0">Admin notified</dt>
        <dd id="detailAdminNotified" class="text-on-surface text-right"></dd>
      </div>
      <div id="detailRewardedWrap" class="hidden space-y-3 pt-1 border-t border-outline-variant/15">
        <div class="flex items-start justify-between gap-4">
          <dt class="text-on-surface-variant shrink-0">Reward paid</dt>
          <dd id="detailRewardPaid" class="text-on-surface text-right"></dd>
        </div>
        <div class="flex items-start justify-between gap-4">
          <dt class="text-on-surface-variant shrink-0">Stripe transfer</dt>
          <dd id="detailStripeTransfer" class="text-on-surface text-right break-all text-xs font-mono"></dd>
        </div>
      </div>
      <div id="detailRejectedWrap" class="hidden pt-1 border-t border-outline-variant/15">
        <div class="flex items-start justify-between gap-4 mb-3">
          <dt class="text-on-surface-variant shrink-0">Rejected at</dt>
          <dd id="detailRejectedAt" class="text-on-surface text-right"></dd>
        </div>
        <div>
          <dt class="text-on-surface-variant mb-1">Rejection reason</dt>
          <dd id="detailRejectionReason" class="text-on-surface text-sm leading-relaxed bg-red-50 border border-red-100 rounded-xl p-3"></dd>
        </div>
      </div>
    </dl>
  </div>
</div>
@endsection

@section('scripts')
<script>
(function () {
  var modal = document.getElementById('sendReferralRewardModal');
  var alertEl = document.getElementById('referralActionAlert');
  var errorEl = document.getElementById('sendReferralRewardError');
  var referrerEl = document.getElementById('sendReferralRewardReferrer');
  var amountEl = document.getElementById('sendReferralRewardAmount');
  var confirmBtn = document.getElementById('confirmSendReferralReward');
  var cancelBtn = document.getElementById('cancelSendReferralReward');
  var activeUrl = null;
  var csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

  function showAlert(message, type) {
    if (!alertEl) return;
    alertEl.textContent = message;
    alertEl.className = 'rounded-xl px-4 py-3 text-sm mb-6 border ' + (
      type === 'success'
        ? 'bg-green-50 text-green-800 border-green-200'
        : 'bg-red-50 text-red-800 border-red-200'
    );
    alertEl.classList.remove('hidden');
  }

  function openModal(btn) {
    activeUrl = btn.getAttribute('data-send-url');
    if (referrerEl) referrerEl.textContent = btn.getAttribute('data-referrer-name') || 'the referrer';
    if (amountEl) amountEl.textContent = btn.getAttribute('data-reward-amount') || '$0.00';
    if (errorEl) {
      errorEl.classList.add('hidden');
      errorEl.textContent = '';
    }
    if (confirmBtn) {
      confirmBtn.disabled = false;
      confirmBtn.textContent = 'Confirm & send reward';
    }
    modal?.classList.remove('hidden');
  }

  function closeModal() {
    activeUrl = null;
    modal?.classList.add('hidden');
  }

  document.querySelectorAll('.js-send-referral-reward').forEach(function (btn) {
    btn.addEventListener('click', function () {
      openModal(btn);
    });
  });

  cancelBtn?.addEventListener('click', closeModal);
  modal?.addEventListener('click', function (e) {
    if (e.target === modal) closeModal();
  });

  confirmBtn?.addEventListener('click', function () {
    if (!activeUrl || !confirmBtn) return;

    confirmBtn.disabled = true;
    confirmBtn.textContent = 'Sending…';
    if (errorEl) {
      errorEl.classList.add('hidden');
      errorEl.textContent = '';
    }

    fetch(activeUrl, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': csrfToken,
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
    })
      .then(function (res) {
        return res.json().then(function (data) {
          return { ok: res.ok, data: data };
        });
      })
      .then(function (result) {
        if (!result.ok || !result.data.success) {
          throw new Error((result.data && result.data.message) || 'Could not send reward.');
        }

        closeModal();
        showAlert(result.data.message || 'Reward sent successfully.', 'success');
        window.setTimeout(function () {
          window.location.reload();
        }, 800);
      })
      .catch(function (err) {
        if (errorEl) {
          errorEl.textContent = err.message || 'Could not send reward.';
          errorEl.classList.remove('hidden');
        }
        confirmBtn.disabled = false;
        confirmBtn.textContent = 'Confirm & send reward';
      });
  });
})();

(function () {
  var modal = document.getElementById('rejectReferralModal');
  var alertEl = document.getElementById('referralActionAlert');
  var errorEl = document.getElementById('rejectReferralError');
  var referrerEl = document.getElementById('rejectReferralReferrer');
  var reasonEl = document.getElementById('rejectReferralReason');
  var confirmBtn = document.getElementById('confirmRejectReferral');
  var cancelBtn = document.getElementById('cancelRejectReferral');
  var activeUrl = null;
  var csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

  function showAlert(message, type) {
    if (!alertEl) return;
    alertEl.textContent = message;
    alertEl.className = 'rounded-xl px-4 py-3 text-sm mb-6 border ' + (
      type === 'success'
        ? 'bg-green-50 text-green-800 border-green-200'
        : 'bg-red-50 text-red-800 border-red-200'
    );
    alertEl.classList.remove('hidden');
  }

  function openModal(btn) {
    activeUrl = btn.getAttribute('data-reject-url');
    if (referrerEl) referrerEl.textContent = btn.getAttribute('data-referrer-name') || 'the referrer';
    if (reasonEl) reasonEl.value = '';
    if (errorEl) {
      errorEl.classList.add('hidden');
      errorEl.textContent = '';
    }
    if (confirmBtn) {
      confirmBtn.disabled = false;
      confirmBtn.textContent = 'Confirm rejection';
    }
    modal?.classList.remove('hidden');
    reasonEl?.focus();
  }

  function closeModal() {
    activeUrl = null;
    modal?.classList.add('hidden');
  }

  document.querySelectorAll('.js-reject-referral').forEach(function (btn) {
    btn.addEventListener('click', function () {
      openModal(btn);
    });
  });

  cancelBtn?.addEventListener('click', closeModal);
  modal?.addEventListener('click', function (e) {
    if (e.target === modal) closeModal();
  });

  confirmBtn?.addEventListener('click', function () {
    if (!activeUrl || !confirmBtn || !reasonEl) return;

    var reason = reasonEl.value.trim();
    if (reason.length < 10) {
      if (errorEl) {
        errorEl.textContent = 'Please enter a rejection reason of at least 10 characters.';
        errorEl.classList.remove('hidden');
      }
      return;
    }

    confirmBtn.disabled = true;
    confirmBtn.textContent = 'Rejecting…';
    if (errorEl) {
      errorEl.classList.add('hidden');
      errorEl.textContent = '';
    }

    fetch(activeUrl, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': csrfToken,
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ reason: reason }),
    })
      .then(function (res) {
        return res.json().then(function (data) {
          return { ok: res.ok, data: data };
        });
      })
      .then(function (result) {
        if (!result.ok || !result.data.success) {
          var message = (result.data && result.data.message) || 'Could not reject referral.';
          if (result.data && result.data.errors && result.data.errors.reason && result.data.errors.reason[0]) {
            message = result.data.errors.reason[0];
          }
          throw new Error(message);
        }

        closeModal();
        showAlert(result.data.message || 'Referral rejected successfully.', 'success');
        window.setTimeout(function () {
          window.location.reload();
        }, 800);
      })
      .catch(function (err) {
        if (errorEl) {
          errorEl.textContent = err.message || 'Could not reject referral.';
          errorEl.classList.remove('hidden');
        }
        confirmBtn.disabled = false;
        confirmBtn.textContent = 'Confirm rejection';
      });
  });
})();

(function () {
  var modal = document.getElementById('referralDetailsModal');
  var closeBtn = document.getElementById('closeReferralDetails');

  function setText(id, value) {
    var el = document.getElementById(id);
    if (el) {
      el.textContent = value || '—';
    }
  }

  function openDetailsModal(btn) {
    if (!modal) return;

    setText('detailSignedUp', btn.getAttribute('data-signed-up'));
    setText('detailReferrerName', btn.getAttribute('data-referrer-name'));
    setText('detailReferrerEmail', btn.getAttribute('data-referrer-email'));
    setText('detailReferredName', btn.getAttribute('data-referred-name'));
    setText('detailReferredEmail', btn.getAttribute('data-referred-email'));
    setText('detailStatus', btn.getAttribute('data-status-label'));
    setText('detailReward', btn.getAttribute('data-reward'));
    setText('detailFeeWaived', btn.getAttribute('data-fee-waived'));
    setText('detailQualifiedAt', btn.getAttribute('data-qualified-at'));
    setText('detailAdminNotified', btn.getAttribute('data-admin-notified'));

    var bookingEl = document.getElementById('detailQualifiedBooking');
    var bookingLabel = btn.getAttribute('data-qualified-booking') || '';
    var bookingUrl = btn.getAttribute('data-qualified-booking-url') || '';
    if (bookingEl) {
      if (bookingLabel && bookingUrl) {
        bookingEl.innerHTML = '<a href="' + bookingUrl + '" class="font-semibold text-primary hover:underline">' + bookingLabel + '</a>';
      } else {
        bookingEl.textContent = '—';
      }
    }

    var rewardPaid = btn.getAttribute('data-reward-paid') || '';
    var stripeTransfer = btn.getAttribute('data-stripe-transfer') || '';
    var rewardedWrap = document.getElementById('detailRewardedWrap');
    if (rewardedWrap) {
      var showRewarded = rewardPaid !== '' || stripeTransfer !== '';
      rewardedWrap.classList.toggle('hidden', !showRewarded);
      if (showRewarded) {
        setText('detailRewardPaid', rewardPaid);
        setText('detailStripeTransfer', stripeTransfer);
      }
    }

    var rejectionReason = btn.getAttribute('data-rejection-reason') || '';
    var rejectedAt = btn.getAttribute('data-rejected-at') || '';
    var rejectedWrap = document.getElementById('detailRejectedWrap');
    if (rejectedWrap) {
      var showRejected = rejectionReason !== '' || rejectedAt !== '';
      rejectedWrap.classList.toggle('hidden', !showRejected);
      if (showRejected) {
        setText('detailRejectedAt', rejectedAt);
        setText('detailRejectionReason', rejectionReason);
      }
    }

    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
  }

  function closeDetailsModal() {
    if (!modal) return;
    modal.classList.add('hidden');
    document.body.style.overflow = '';
  }

  document.querySelectorAll('.js-view-referral').forEach(function (btn) {
    btn.addEventListener('click', function () {
      openDetailsModal(btn);
    });
  });

  closeBtn?.addEventListener('click', closeDetailsModal);
  modal?.addEventListener('click', function (e) {
    if (e.target === modal) closeDetailsModal();
  });
})();
</script>
@endsection
