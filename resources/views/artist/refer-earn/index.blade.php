@extends('layouts.artist_dashboard_layout')

@section('title', 'Refer & Earn')

@section('styles')
<style>
  .refer-earn-tab-btn { transition: color 0.2s, border-color 0.2s; }
  .refer-earn-card {
    background: #fff;
    border-radius: 16px;
    padding: 24px;
  }
  .refer-earn-step-num {
    width: 28px;
    height: 28px;
    border-radius: 9999px;
    background: #f8f1fb;
    color: #1c1b21;
    font-size: 13px;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }
  .refer-link-box {
    background: #f8f1fb;
    border-radius: 12px;
    padding: 14px 16px;
    font-size: 14px;
    color: #1c1b21;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
  .refer-copy-btn {
    background: #1a1a1a;
    color: #fff;
    border: 0;
    border-radius: 12px;
    padding: 14px 22px;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    flex-shrink: 0;
    transition: opacity 0.15s, background 0.15s, color 0.15s;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    min-width: 96px;
  }
  .refer-copy-btn:hover { opacity: 0.9; }
  .refer-copy-btn .material-symbols-outlined {
    font-size: 18px;
    line-height: 1;
  }
  .refer-copy-btn.is-copied .material-symbols-outlined {
    color: #22c55e;
    font-variation-settings: 'FILL' 1, 'wght' 600, 'GRAD' 0, 'opsz' 24;
  }
  .refer-action-btn {
    background: #fff;
    border: 1px solid rgba(202,196,211,0.35);
    border-radius: 14px;
    padding: 16px 20px;
    font-size: 15px;
    font-weight: 600;
    color: #1c1b21;
    cursor: pointer;
    transition: background 0.15s;
  }
  .refer-action-btn:hover { background: #f8f1fb; }
  .refer-action-btn:disabled {
    opacity: 0.45;
    cursor: not-allowed;
  }
  .refer-action-btn:disabled:hover { background: #fff; }
  .refer-action-btn.is-copied {
    background: #f0fdf4;
    border-color: rgba(34, 197, 94, 0.35);
    color: #15803d;
  }
  .refer-action-btn .material-symbols-outlined {
    font-size: 18px;
    line-height: 1;
    vertical-align: -3px;
  }
  .refer-action-btn.is-copied .material-symbols-outlined {
    font-variation-settings: 'FILL' 1, 'wght' 600, 'GRAD' 0, 'opsz' 24;
  }
  .referrals-table th {
    text-align: left;
    font-size: 12px;
    font-weight: 600;
    color: #494552;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 12px 16px;
    border-bottom: 1px solid rgba(202,196,211,0.3);
  }
  .referrals-table td {
    padding: 14px 16px;
    font-size: 14px;
    border-bottom: 1px solid rgba(202,196,211,0.15);
    vertical-align: middle;
  }
  .referrals-table tbody tr { transition: background 0.15s; }
  .referrals-table tbody tr:hover { background: #f8f1fb; }
  .refer-status {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: 9999px;
    font-size: 12px;
    font-weight: 600;
  }
  .refer-status-dot {
    width: 7px;
    height: 7px;
    border-radius: 9999px;
  }
  .refer-status-pending { background: #fffbeb; color: #b45309; }
  .refer-status-pending .refer-status-dot { background: #f59e0b; }
  .refer-status-rewarded { background: #f0fdf4; color: #15803d; }
  .refer-status-rewarded .refer-status-dot { background: #22c55e; }
  .refer-status-rejected { background: #fef2f2; color: #b91c1c; }
  .refer-status-rejected .refer-status-dot { background: #ef4444; }
  .refer-view-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: 10px;
    border: 0;
    background: transparent;
    color: #494552;
    cursor: pointer;
    transition: background 0.15s, color 0.15s;
  }
  .refer-view-btn:hover { background: #f8f1fb; color: #1c1b21; }
  .referrals-table-scroll {
    overflow-x: auto;
    max-width: 100%;
    -webkit-overflow-scrolling: touch;
  }
  .referrals-table-scroll table { min-width: 640px; }
  .refer-earnings-stat {
    background: #fff;
    border-radius: 16px;
    padding: 20px 24px;
    border: 1px solid rgba(202,196,211,0.2);
  }
  .refer-earnings-stat-label {
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: #494552;
    margin-bottom: 8px;
  }
  .refer-earnings-stat-value {
    font-size: 28px;
    font-weight: 800;
    color: #1c1b21;
    line-height: 1.1;
  }
  .refer-earnings-list {
    background: #fff;
    border-radius: 16px;
    border: 1px solid rgba(202,196,211,0.2);
    overflow: hidden;
  }
  .refer-earnings-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 18px 24px;
    border-bottom: 1px solid rgba(202,196,211,0.15);
  }
  .refer-earnings-item:last-child { border-bottom: 0; }
  .refer-earnings-item-title {
    font-size: 15px;
    font-weight: 700;
    color: #1c1b21;
    line-height: 1.35;
  }
  .refer-earnings-item-subtitle {
    font-size: 13px;
    color: #494552;
    margin-top: 2px;
  }
  .refer-earnings-item-amount {
    font-size: 15px;
    font-weight: 700;
    white-space: nowrap;
    flex-shrink: 0;
  }
  .refer-earnings-item-amount.is-pending { color: #494552; }
  .refer-earnings-item-amount.is-paid { color: #15803d; }
  @media (max-width: 1023px) {
    .main-content { padding: 16px; padding-top: 70px; }
  }
</style>
@endsection

@section('content')
@php
  $user = Auth::user();
  $user->loadMissing('userDetail');
  $referralUsername = trim((string) ($user->userDetail?->user_name ?? ''));
  $referralLink = $referralUsername !== ''
    ? rtrim((string) config('app.url'), '/').'/register/'.$referralUsername
    : null;
  $referralLinkDisplay = $referralLink
    ? preg_replace('#^https?://#', '', $referralLink)
    : null;
  $shareMessage = $referralLinkDisplay
    ? "I'm using BookPay to manage bookings and payments and I think you'd love it too. Use my link to sign up and your first client's booking fee will be free: ".$referralLinkDisplay
    : null;
  $referrals = $referrals ?? collect();
  $earnings = $earnings ?? ['pending_total' => 0, 'paid_total' => 0, 'items' => collect()];
  $earningsItems = $earnings['items'] ?? collect();
@endphp
<main class="main-content flex-1 min-h-screen min-w-0 w-full">
  <div class="p-6 md:p-10 lg:p-12 max-w-6xl w-full min-w-0 mx-auto">

    <div class="mb-8">
      <h2 class="text-3xl font-extrabold text-on-surface tracking-tight">Refer & Earn</h2>
      <p class="text-on-surface-variant mt-1">Earn cash when you bring artists you know to Bookpay.</p>
    </div>

    <div class="flex gap-6 sm:gap-8 border-b border-outline-variant/20 mb-6 overflow-x-auto">
      <button type="button" data-refer-tab="link" class="refer-earn-tab-btn shrink-0 pb-3.5 text-sm font-bold border-b-2 border-on-surface text-on-surface whitespace-nowrap">
        Get your link
      </button>
      <button type="button" data-refer-tab="referrals" class="refer-earn-tab-btn shrink-0 pb-3.5 text-sm font-medium border-b-2 border-transparent text-on-surface-variant hover:text-on-surface whitespace-nowrap">
        Referrals
      </button>
      <button type="button" data-refer-tab="earnings" class="refer-earn-tab-btn shrink-0 pb-3.5 text-sm font-medium border-b-2 border-transparent text-on-surface-variant hover:text-on-surface whitespace-nowrap">
        Earnings
      </button>
    </div>

    <div data-refer-panel="link" class="space-y-4">
      <div class="refer-earn-card">
        <p class="text-[15px] leading-relaxed text-on-surface">
          Know an artist who'd love Bookpay? Share your link. When they complete their first booking, you'll get <strong>$20</strong> straight to your account — and they'll get their <strong>first booking fee waived.</strong>
        </p>
        <p class="text-sm text-on-surface-variant mt-3 leading-relaxed">
          No limit on how many artists you can refer. No expiration — whenever they book, you get paid.
        </p>
      </div>

      <div class="refer-earn-card">
        <p class="text-xs font-semibold tracking-wider text-on-surface-variant uppercase mb-4">How it works</p>
        <ol class="space-y-3.5">
          <li class="flex items-center gap-3 text-[15px] text-on-surface">
            <span class="refer-earn-step-num">1</span>
            <span>Share your unique referral link</span>
          </li>
          <li class="flex items-center gap-3 text-[15px] text-on-surface">
            <span class="refer-earn-step-num">2</span>
            <span>An artist friend signs up and completes their first booking</span>
          </li>
          <li class="flex items-center gap-3 text-[15px] text-on-surface">
            <span class="refer-earn-step-num">3</span>
            <span>You get $20, paid straight to your account</span>
          </li>
          <li class="flex items-center gap-3 text-[15px] text-on-surface">
            <span class="refer-earn-step-num">4</span>
            <span>They get their first client's booking fee waived</span>
          </li>
        </ol>
        <p class="mt-5 pt-1">
          <a href="https://inkjin.com/en/refer-and-earn-terms" target="_blank" rel="noopener noreferrer" class="text-sm font-semibold text-primary underline underline-offset-2 hover:opacity-80">
            Refer &amp; Earn terms and conditions
          </a>
        </p>
      </div>

      <div class="refer-earn-card">
        <p class="text-xs font-semibold tracking-wider text-on-surface-variant uppercase mb-4">Your referral link</p>
        @if ($referralLink)
          <div class="flex flex-col sm:flex-row gap-3">
            <div class="refer-link-box flex-1 min-w-0" id="referLinkDisplay" title="{{ $referralLinkDisplay }}">{{ $referralLinkDisplay }}</div>
            <button type="button" class="refer-copy-btn" id="referCopyBtn" data-link="{{ $referralLink }}">
              <span class="refer-copy-label">Copy</span>
            </button>
          </div>
        @else
          <p class="text-sm text-on-surface-variant leading-relaxed">
            Set your username first to generate your referral link.
          </p>
          <a href="{{ route('profile.edit') }}" class="inline-flex mt-4 text-sm font-semibold text-on-surface underline underline-offset-2 hover:opacity-80">
            Set username
          </a>
        @endif
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
        <button type="button" class="refer-action-btn" id="referShareMessageBtn" @disabled(! $shareMessage) data-message="{{ $shareMessage }}">
          <span class="refer-share-label">Share via message</span>
        </button>
        <button type="button" class="refer-action-btn" id="referShowQrBtn" @disabled(! $referralLink)>Show QR code</button>
      </div>
    </div>

    <div data-refer-panel="referrals" class="hidden">
      <div class="bg-white rounded-2xl shadow-sm border border-outline-variant/20 overflow-hidden min-w-0">
        <div class="referrals-table-scroll">
          <table class="w-full referrals-table">
            <thead>
              <tr class="bg-surface-container-low/50">
                <th>Requested Artist</th>
                <th>Date</th>
                <th>Status</th>
                <th>Amount</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($referrals as $referral)
                <tr>
                  <td>
                    <div class="font-semibold text-on-surface">{{ $referral['artist_name'] }}</div>
                    @if ($referral['artist_username'] !== '')
                      <div class="text-xs text-on-surface-variant mt-0.5">{{ '@'.$referral['artist_username'] }}</div>
                    @elseif ($referral['artist_email'] !== '')
                      <div class="text-xs text-on-surface-variant mt-0.5">{{ $referral['artist_email'] }}</div>
                    @endif
                  </td>
                  <td class="text-on-surface">{{ $referral['date'] }}</td>
                  <td>
                    <span class="refer-status refer-status-{{ $referral['status'] }}">
                      <span class="refer-status-dot"></span>
                      {{ $referral['status_label'] }}
                    </span>
                  </td>
                  <td class="font-semibold text-on-surface">${{ number_format($referral['amount'], 2) }}</td>
                  <td>
                    <button
                      type="button"
                      class="refer-view-btn"
                      data-refer-view
                      data-artist="{{ $referral['artist_name'] }}"
                      data-username="{{ $referral['artist_username'] }}"
                      data-email="{{ $referral['artist_email'] }}"
                      data-date="{{ $referral['date_full'] }}"
                      data-status="{{ $referral['status_label'] }}"
                      data-amount="${{ number_format($referral['amount'], 2) }}"
                      data-fee-waived="{{ $referral['fee_waived'] ? 'Yes' : 'No' }}"
                      data-paid-at="{{ $referral['reward_paid_at'] ?? '—' }}"
                      data-rejection-reason="{{ $referral['rejection_reason'] ?? '' }}"
                      data-rejected-at="{{ $referral['rejected_at'] ?? '—' }}"
                      aria-label="View referral"
                    >
                      <span class="material-symbols-outlined text-[20px]">visibility</span>
                    </button>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="5" class="px-6 py-12 text-center text-sm text-on-surface-variant">
                    No referrals yet. Share your link to get started.
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <div data-refer-panel="earnings" class="hidden space-y-4">
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="refer-earnings-stat">
          <p class="refer-earnings-stat-label">Pending</p>
          <p class="refer-earnings-stat-value">${{ number_format((float) ($earnings['pending_total'] ?? 0), 0) }}</p>
        </div>
        <div class="refer-earnings-stat">
          <p class="refer-earnings-stat-label">Paid out</p>
          <p class="refer-earnings-stat-value">${{ number_format((float) ($earnings['paid_total'] ?? 0), 0) }}</p>
        </div>
      </div>

      <div class="refer-earnings-list">
        @forelse ($earningsItems as $item)
          <div class="refer-earnings-item">
            <div class="min-w-0">
              <p class="refer-earnings-item-title">{{ $item['artist_label'] }} — first booking</p>
              <p class="refer-earnings-item-subtitle">{{ $item['subtitle'] }}</p>
            </div>
            <p class="refer-earnings-item-amount {{ $item['state'] === 'paid' ? 'is-paid' : 'is-pending' }}">
              ${{ number_format((float) $item['amount'], 0) }}
            </p>
          </div>
        @empty
          <div class="px-6 py-12 text-center text-sm text-on-surface-variant">
            No earnings yet. When a referred artist completes their first booking, your reward will show here.
          </div>
        @endforelse
      </div>
    </div>

  </div>
</main>

<div id="referQrModal" class="hidden fixed inset-0 z-[200] flex items-center justify-center p-4 bg-black/50" role="dialog" aria-modal="true" aria-labelledby="referQrTitle" aria-hidden="true">
  <div class="bg-white rounded-2xl max-w-sm w-full p-6 shadow-xl" onclick="event.stopPropagation()">
    <div class="flex items-start justify-between gap-3 mb-1">
      <h5 id="referQrTitle" class="text-lg font-bold text-on-surface">Referral QR code</h5>
      <button type="button" id="referQrCloseBtn" class="rounded-lg p-1 text-on-surface-variant hover:bg-surface-container-low transition-colors" aria-label="Close">
        <span class="material-symbols-outlined text-[22px]">close</span>
      </button>
    </div>
    <p class="text-sm text-on-surface-variant mb-5">Scan to open your referral link.</p>
    <div class="flex justify-center mb-4">
      <div id="referQrCode" class="rounded-xl bg-white p-3 border border-outline-variant/20" data-link="{{ $referralLink }}"></div>
    </div>
    <p class="text-xs text-on-surface-variant text-center break-all">{{ $referralLinkDisplay }}</p>
  </div>
</div>

<div id="referViewModal" class="hidden fixed inset-0 z-[200] flex items-center justify-center p-4 bg-black/50" role="dialog" aria-modal="true" aria-labelledby="referViewTitle" aria-hidden="true">
  <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl" onclick="event.stopPropagation()">
    <div class="flex items-start justify-between gap-3 mb-4">
      <h5 id="referViewTitle" class="text-lg font-bold text-on-surface">Referral details</h5>
      <button type="button" id="referViewCloseBtn" class="rounded-lg p-1 text-on-surface-variant hover:bg-surface-container-low transition-colors" aria-label="Close">
        <span class="material-symbols-outlined text-[22px]">close</span>
      </button>
    </div>
    <dl class="space-y-3 text-sm">
      <div class="flex items-start justify-between gap-4">
        <dt class="text-on-surface-variant">Requested artist</dt>
        <dd id="referViewArtist" class="font-semibold text-on-surface text-right"></dd>
      </div>
      <div class="flex items-start justify-between gap-4">
        <dt class="text-on-surface-variant">Username</dt>
        <dd id="referViewUsername" class="text-on-surface text-right"></dd>
      </div>
      <div class="flex items-start justify-between gap-4">
        <dt class="text-on-surface-variant">Email</dt>
        <dd id="referViewEmail" class="text-on-surface text-right break-all"></dd>
      </div>
      <div class="flex items-start justify-between gap-4">
        <dt class="text-on-surface-variant">Date</dt>
        <dd id="referViewDate" class="text-on-surface text-right"></dd>
      </div>
      <div class="flex items-start justify-between gap-4">
        <dt class="text-on-surface-variant">Status</dt>
        <dd id="referViewStatus" class="font-semibold text-on-surface text-right"></dd>
      </div>
      <div class="flex items-start justify-between gap-4">
        <dt class="text-on-surface-variant">Amount</dt>
        <dd id="referViewAmount" class="font-semibold text-on-surface text-right"></dd>
      </div>
      <div class="flex items-start justify-between gap-4">
        <dt class="text-on-surface-variant">Fee waived</dt>
        <dd id="referViewFeeWaived" class="text-on-surface text-right"></dd>
      </div>
      <div class="flex items-start justify-between gap-4">
        <dt class="text-on-surface-variant">Paid at</dt>
        <dd id="referViewPaidAt" class="text-on-surface text-right"></dd>
      </div>
      <div id="referViewRejectedWrap" class="hidden space-y-3">
        <div class="flex items-start justify-between gap-4">
          <dt class="text-on-surface-variant">Rejected at</dt>
          <dd id="referViewRejectedAt" class="text-on-surface text-right"></dd>
        </div>
        <div>
          <dt class="text-on-surface-variant mb-1">Reason</dt>
          <dd id="referViewRejectionReason" class="text-on-surface text-sm leading-relaxed"></dd>
        </div>
      </div>
    </dl>
  </div>
</div>
@endsection

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
  (function () {
    var buttons = document.querySelectorAll('[data-refer-tab]');
    var panels = document.querySelectorAll('[data-refer-panel]');

    buttons.forEach(function (btn) {
      btn.addEventListener('click', function () {
        var tab = btn.getAttribute('data-refer-tab');

        buttons.forEach(function (b) {
          var active = b.getAttribute('data-refer-tab') === tab;
          b.classList.toggle('border-on-surface', active);
          b.classList.toggle('text-on-surface', active);
          b.classList.toggle('font-bold', active);
          b.classList.toggle('border-transparent', !active);
          b.classList.toggle('text-on-surface-variant', !active);
          b.classList.toggle('font-medium', !active);
        });

        panels.forEach(function (panel) {
          panel.classList.toggle('hidden', panel.getAttribute('data-refer-panel') !== tab);
        });
      });
    });

    function fallbackCopyText(text, onSuccess) {
      var input = document.createElement('textarea');
      input.value = text;
      input.setAttribute('readonly', '');
      input.style.position = 'absolute';
      input.style.left = '-9999px';
      document.body.appendChild(input);
      input.select();
      try {
        document.execCommand('copy');
        if (typeof onSuccess === 'function') {
          onSuccess();
        }
      } catch (e) {}
      document.body.removeChild(input);
    }

    function copyText(text, onSuccess) {
      if (!text) {
        return;
      }
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(onSuccess).catch(function () {
          fallbackCopyText(text, onSuccess);
        });
        return;
      }
      fallbackCopyText(text, onSuccess);
    }

    var copyBtn = document.getElementById('referCopyBtn');
    if (copyBtn) {
      var resetTimer = null;

      function showCopiedState() {
        copyBtn.classList.add('is-copied');
        copyBtn.innerHTML = '<span class="material-symbols-outlined" aria-hidden="true">check_circle</span><span class="refer-copy-label">Copied</span>';
        if (resetTimer) {
          clearTimeout(resetTimer);
        }
        resetTimer = setTimeout(function () {
          copyBtn.classList.remove('is-copied');
          copyBtn.innerHTML = '<span class="refer-copy-label">Copy</span>';
        }, 2000);
      }

      copyBtn.addEventListener('click', function () {
        copyText(copyBtn.getAttribute('data-link') || '', showCopiedState);
      });
    }

    var shareMessageBtn = document.getElementById('referShareMessageBtn');
    if (shareMessageBtn) {
      var shareResetTimer = null;
      var defaultShareLabel = 'Share via message';

      function showMessageCopiedState() {
        shareMessageBtn.classList.add('is-copied');
        shareMessageBtn.innerHTML = '<span class="material-symbols-outlined" aria-hidden="true">check_circle</span> <span class="refer-share-label">Message copied</span>';
        if (shareResetTimer) {
          clearTimeout(shareResetTimer);
        }
        shareResetTimer = setTimeout(function () {
          shareMessageBtn.classList.remove('is-copied');
          shareMessageBtn.innerHTML = '<span class="refer-share-label">' + defaultShareLabel + '</span>';
        }, 2000);
      }

      shareMessageBtn.addEventListener('click', function () {
        if (shareMessageBtn.disabled) {
          return;
        }
        copyText(shareMessageBtn.getAttribute('data-message') || '', showMessageCopiedState);
      });
    }

    var qrModal = document.getElementById('referQrModal');
    var qrContainer = document.getElementById('referQrCode');
    var showQrBtn = document.getElementById('referShowQrBtn');
    var closeQrBtn = document.getElementById('referQrCloseBtn');
    var qrGenerated = false;

    function openQrModal() {
      if (!qrModal || !qrContainer) {
        return;
      }

      if (!qrGenerated && typeof QRCode !== 'undefined') {
        qrContainer.innerHTML = '';
        new QRCode(qrContainer, {
          text: qrContainer.getAttribute('data-link') || '',
          width: 220,
          height: 220,
          colorDark: '#1a1a1a',
          colorLight: '#ffffff',
          correctLevel: QRCode.CorrectLevel.M
        });
        qrGenerated = true;
      }

      qrModal.classList.remove('hidden');
      qrModal.setAttribute('aria-hidden', 'false');
      document.body.style.overflow = 'hidden';
    }

    function closeQrModal() {
      if (!qrModal) {
        return;
      }
      qrModal.classList.add('hidden');
      qrModal.setAttribute('aria-hidden', 'true');
      document.body.style.overflow = '';
    }

    if (showQrBtn) {
      showQrBtn.addEventListener('click', openQrModal);
    }
    if (closeQrBtn) {
      closeQrBtn.addEventListener('click', closeQrModal);
    }
    if (qrModal) {
      qrModal.addEventListener('click', function (e) {
        if (e.target === qrModal) {
          closeQrModal();
        }
      });
    }

    var viewModal = document.getElementById('referViewModal');
    var viewCloseBtn = document.getElementById('referViewCloseBtn');

    function setViewText(id, value) {
      var el = document.getElementById(id);
      if (el) {
        el.textContent = value || '—';
      }
    }

    function openViewModal(btn) {
      if (!viewModal) {
        return;
      }

      var username = btn.getAttribute('data-username') || '';
      setViewText('referViewArtist', btn.getAttribute('data-artist'));
      setViewText('referViewUsername', username ? '@' + username : '—');
      setViewText('referViewEmail', btn.getAttribute('data-email'));
      setViewText('referViewDate', btn.getAttribute('data-date'));
      setViewText('referViewStatus', btn.getAttribute('data-status'));
      setViewText('referViewAmount', btn.getAttribute('data-amount'));
      setViewText('referViewFeeWaived', btn.getAttribute('data-fee-waived'));
      setViewText('referViewPaidAt', btn.getAttribute('data-paid-at'));

      var rejectedWrap = document.getElementById('referViewRejectedWrap');
      var rejectionReason = btn.getAttribute('data-rejection-reason') || '';
      var isRejected = rejectionReason !== '' || (btn.getAttribute('data-status') || '').toLowerCase() === 'rejected';
      if (rejectedWrap) {
        rejectedWrap.classList.toggle('hidden', !isRejected);
      }
      if (isRejected) {
        setViewText('referViewRejectedAt', btn.getAttribute('data-rejected-at'));
        setViewText('referViewRejectionReason', rejectionReason);
      }

      viewModal.classList.remove('hidden');
      viewModal.setAttribute('aria-hidden', 'false');
      document.body.style.overflow = 'hidden';
    }

    function closeViewModal() {
      if (!viewModal) {
        return;
      }
      viewModal.classList.add('hidden');
      viewModal.setAttribute('aria-hidden', 'true');
      document.body.style.overflow = '';
    }

    document.querySelectorAll('[data-refer-view]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        openViewModal(btn);
      });
    });

    if (viewCloseBtn) {
      viewCloseBtn.addEventListener('click', closeViewModal);
    }
    if (viewModal) {
      viewModal.addEventListener('click', function (e) {
        if (e.target === viewModal) {
          closeViewModal();
        }
      });
    }

    document.addEventListener('keydown', function (e) {
      if (e.key !== 'Escape') {
        return;
      }
      if (qrModal && !qrModal.classList.contains('hidden')) {
        closeQrModal();
      }
      if (viewModal && !viewModal.classList.contains('hidden')) {
        closeViewModal();
      }
    });
  })();
</script>
@endsection

