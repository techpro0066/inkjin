{{-- Shared artist booking detail modal (Bookings page + Inbox). --}}
@php
  $inboxMode = (bool) ($inboxMode ?? false);
@endphp
<div id="artistBookingDetailModal"
  class="artist-bdm"
  aria-hidden="true"
  aria-modal="true"
  role="dialog"
  aria-labelledby="abdmTitle"
  @if($inboxMode) data-inbox-mode="1" @endif>
  <div class="artist-bdm-backdrop" data-close-artist-bdm></div>
  <div class="artist-bdm-panel">
    <div class="abdm-mh">
      <div><h2 id="abdmTitle">—</h2></div>
      <button type="button" class="abdm-faint inline-flex" data-close-artist-bdm aria-label="Close" style="background:none;border:0;cursor:pointer;padding:0">
        <span class="material-symbols-outlined" style="font-size:22px">close</span>
      </button>
    </div>
    <div class="abdm-mb">
      <div class="abdm-tabs bdtabs" role="tablist" aria-label="Booking detail sections">
        <button type="button" id="abdmTabDetails" role="tab" aria-selected="true" aria-controls="abdmPanelDetails" data-abdm-tab="details" class="on">Details</button>
        <button type="button" id="abdmTabRequest" role="tab" aria-selected="false" aria-controls="abdmPanelRequest" data-abdm-tab="request">Original request</button>
      </div>

      <div id="abdmPanelDetails" role="tabpanel" aria-labelledby="abdmTabDetails">
        <div class="flex items-center" style="gap:16px;margin-bottom:8px">
          <div id="abdmImageWrap" style="width:84px;height:84px;border-radius:14px;background:#EEE center/cover;flex-shrink:0"></div>
          <div>
            <div id="abdmRef" class="abdm-faint" style="font-size:12.5px;font-weight:600">—</div>
            <span id="abdmStatus" class="abdm-pill g" style="margin:6px 0;display:inline-flex">Confirmed</span>
            <div id="abdmType" class="abdm-muted" style="font-size:13px">—</div>
          </div>
        </div>
        <div class="abdm-grid2">
          <div>
            <div class="abdm-kv"><span>Client</span><span id="abdmClientName">—</span></div>
            <div class="abdm-kv" id="abdmClientEmailRow"><span>Email</span><span id="abdmClientEmail">—</span></div>
            <div class="abdm-kv" id="abdmClientPhoneRow"><span>Mobile</span><span id="abdmClientPhone">—</span></div>
            <div class="abdm-kv"><span>Service</span><span id="abdmService">—</span></div>
            <div class="abdm-kv"><span>Deposit</span><span id="abdmDeposit">—</span></div>
          </div>
          <div>
            <div class="abdm-kv"><span>Date</span><span id="abdmDate">—</span></div>
            <div class="abdm-kv"><span>Time</span><span id="abdmTime">—</span></div>
            <div class="abdm-kv"><span>Duration</span><span id="abdmDuration">—</span></div>
            <div class="abdm-kv"><span>Timezone</span><span id="abdmTimezone">—</span></div>
            <div class="abdm-kv"><span>Balance (est.)</span><span id="abdmBalance">—</span></div>
          </div>
        </div>
        <div class="abdm-sect" id="abdmMoney">
          <div class="flex items-center justify-between">
            <div class="lbl" style="margin:0;font-size:11px;letter-spacing:.8px;font-weight:700;color:#6F6874;text-transform:uppercase">Money for this booking</div>
            <span id="abdmMoneyPill" class="abdm-pill a">Pending</span>
          </div>
          <div class="abdm-kv" style="padding:8px 0;border-bottom:1px solid #F3EEF4"><span id="abdmMoneyDepositLabel">Client paid · deposit</span><span id="abdmMoneyDeposit" style="font-weight:600">—</span></div>
          <div class="abdm-kv" id="abdmPaidRow" style="padding:8px 0;border-bottom:1px solid #F3EEF4;display:none"><span>Paid</span><span id="abdmPaid" style="font-weight:600">—</span></div>
          <div class="abdm-kv" id="abdmBookingFeeRow" style="padding:8px 0;border-bottom:1px solid #F3EEF4;display:none"><span>Service Booking Fee <span id="abdmBookingFeeNote" class="abdm-faint" style="font-size:12px"></span></span><span id="abdmBookingFee" style="font-weight:600">—</span></div>
          <div class="abdm-kv" id="abdmStripeFeeRow" style="padding:8px 0;border-bottom:1px solid #F3EEF4;display:none"><span>Stripe fee <span class="abdm-faint" style="font-size:12px">1.5% + €0.25</span></span><span id="abdmStripeFee" style="font-weight:600">—</span></div>
          <div class="abdm-kv" id="abdmSplitRow" style="padding:8px 0;border-bottom:1px solid #F3EEF4;display:none"><span id="abdmSplitLabel">Split</span><span id="abdmSplitValue" style="font-weight:600">—</span></div>
          <div class="abdm-kv" id="abdmYourShareRow" style="padding:8px 0;border-bottom:1px solid #F3EEF4;display:none"><span>Your share <span id="abdmYourShareNote" class="abdm-faint" style="font-size:12px"></span></span><span id="abdmYourShare" style="font-weight:600">—</span></div>
          <div class="abdm-kv" id="abdmYourStripeRow" style="padding:8px 0;border-bottom:1px solid #F3EEF4;display:none"><span>Your part of the Stripe fee <span id="abdmYourStripeNote" class="abdm-faint" style="font-size:12px"></span></span><span id="abdmYourStripe" style="font-weight:600">—</span></div>
          <div class="abdm-kv" id="abdmYourBookingFeeRow" style="padding:8px 0;border-bottom:1px solid #F3EEF4;display:none"><span>Your part of the booking fee <span id="abdmYourBookingFeeNote" class="abdm-faint" style="font-size:12px"></span></span><span id="abdmYourBookingFee" style="font-weight:600">—</span></div>
          <div class="abdm-kv" id="abdmStudioGetsRow" style="padding:8px 0;border-bottom:1px solid #F3EEF4;display:none"><span id="abdmStudioGetsLabel">Studio gets</span><span id="abdmStudioGets" style="font-weight:600">—</span></div>
          <div class="abdm-kv" style="padding:12px 0 4px;font-size:15px;border-bottom:0"><span style="color:#1A1A1A;font-weight:700">You get</span><span id="abdmYouGet" style="font-weight:800;font-size:18px">—</span></div>
          <div id="abdmMoneyFootnote" class="abdm-faint" style="font-size:12.5px;display:none"></div>
          <div id="abdmMoneyNote" class="abdm-tint" style="margin-top:12px;display:none"></div>
        </div>
      </div>

      <div id="abdmPanelRequest" role="tabpanel" aria-labelledby="abdmTabRequest" hidden>
        <div id="abdmQaList" class="abdm-grid2" style="column-gap:20px"></div>
        <p id="abdmQaEmpty" class="abdm-muted" style="font-size:13.5px;display:none">No intake form answers for this booking.</p>
      </div>
    </div>
    <div class="abdm-mf">
      <button type="button" id="abdmMessageBtn" class="abdm-btn ghost" style="display:none"><span class="material-symbols-outlined">chat_bubble</span>Message</button>
      <button type="button" id="abdmAftercareBtn" class="abdm-btn ghost" style="margin-right:auto;{{ $inboxMode ? 'display:none' : '' }}" aria-disabled="false">
        <span class="material-symbols-outlined">healing</span><span id="abdmAftercareLabel">Send aftercare</span>
      </button>
      <a href="{{ route('artist.bookings.index') }}" id="abdmManageBtn" class="abdm-btn ghost" style="{{ $inboxMode ? '' : 'display:none' }};text-decoration:none;margin-right:auto">
        <span class="material-symbols-outlined">open_in_new</span>Manage booking
      </a>
      <button type="button" class="abdm-btn ghost" data-close-artist-bdm>Close</button>
      <button type="button" id="abdmConfirmBtn" class="abdm-btn confirm" style="display:none"><span class="material-symbols-outlined">task_alt</span>Confirm booking</button>
    </div>
  </div>
</div>
