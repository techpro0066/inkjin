{{-- Booking detail modal (artist A02 layout, studio-facing money) --}}
<div class="smodal" id="m-booking" hidden>
  <div class="smbox" role="dialog" aria-modal="true" aria-labelledby="m-booking-t">
    <div class="smh">
      <div><h2 id="m-booking-t">Booking</h2></div>
      <button type="button" class="x" data-close aria-label="Close"><span class="ms">close</span></button>
    </div>
    <div class="smb">
      <div class="sbdm-tabs" role="tablist" aria-label="Booking detail sections">
        <button type="button" class="on" data-sbdm-tab="details" role="tab" aria-selected="true">Details</button>
        <button type="button" data-sbdm-tab="request" role="tab" aria-selected="false">Original request</button>
      </div>

      <div id="sbdmPanelDetails" role="tabpanel">
        <div style="display:flex;align-items:center;gap:16px;margin-bottom:8px">
          <div id="sbdmImage" style="width:84px;height:84px;border-radius:14px;background:#EEE center/cover;flex-shrink:0"></div>
          <div>
            <div id="sbdmRef" class="sbdm-faint" style="font-size:12.5px;font-weight:600">—</div>
            <span id="sbdmStatus" class="sbdm-pill g" style="margin:6px 0;display:inline-flex">Confirmed</span>
            <div id="sbdmType" class="sbdm-muted" style="font-size:13px">—</div>
          </div>
        </div>

        <div class="sbdm-grid2">
          <div>
            <div class="sbdm-kv"><span>Client</span><span id="sbdmClient">—</span></div>
            <div class="sbdm-kv"><span>Artist</span><span id="sbdmArtist">—</span></div>
            <div class="sbdm-kv"><span>Service</span><span id="sbdmService">—</span></div>
            <div class="sbdm-kv"><span>Booked through</span><span id="sbdmThrough">—</span></div>
            <div class="sbdm-kv"><span>Deposit</span><span id="sbdmDeposit">—</span></div>
          </div>
          <div>
            <div class="sbdm-kv"><span>Date</span><span id="sbdmDate">—</span></div>
            <div class="sbdm-kv"><span>Time</span><span id="sbdmTime">—</span></div>
            <div class="sbdm-kv"><span>Duration</span><span id="sbdmDuration">—</span></div>
            <div class="sbdm-kv"><span>Timezone</span><span id="sbdmTimezone">—</span></div>
            <div class="sbdm-kv"><span>Balance (est.)</span><span id="sbdmBalance">—</span></div>
          </div>
        </div>

        <div style="margin-top:18px">
          <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:4px">
            <div class="lbl" style="margin:0;font-size:11px;letter-spacing:.8px;font-weight:700;color:#6F6874;text-transform:uppercase">Money for this booking</div>
            <span id="sbdmMoneyPill" class="sbdm-pill a">Pending</span>
          </div>
          <div class="sbdm-kv" style="padding:8px 0;border-bottom:1px solid #F3EEF4"><span id="sbdmMoneyDepositLabel">Client paid · deposit</span><span id="sbdmMoneyDeposit">—</span></div>
          <div class="sbdm-kv" id="sbdmBookingFeeRow" style="padding:8px 0;border-bottom:1px solid #F3EEF4;display:none"><span>Service Booking Fee <span id="sbdmBookingFeeNote" class="sbdm-faint" style="font-size:12px"></span></span><span id="sbdmBookingFee">—</span></div>
          <div class="sbdm-kv" style="padding:8px 0;border-bottom:1px solid #F3EEF4"><span>Stripe fee <span class="sbdm-faint" style="font-size:12px">1.5% + €0.25</span></span><span id="sbdmStripeFee">—</span></div>
          <div class="sbdm-kv" id="sbdmSplitRow" style="padding:8px 0;border-bottom:1px solid #F3EEF4;display:none"><span>Split</span><span id="sbdmSplitValue">—</span></div>
          <div class="sbdm-kv" id="sbdmArtistGetsRow" style="padding:8px 0;border-bottom:1px solid #F3EEF4;display:none"><span><span id="sbdmArtistGetsLabel">Artist gets</span> <span id="sbdmArtistGetsNote" class="sbdm-faint" style="font-size:12px"></span></span><span id="sbdmArtistGets">—</span></div>
          <div class="sbdm-kv" id="sbdmStudioShareRow" style="padding:8px 0;border-bottom:1px solid #F3EEF4;display:none"><span>Studio share <span id="sbdmStudioShareNote" class="sbdm-faint" style="font-size:12px"></span></span><span id="sbdmStudioShare">—</span></div>
          <div class="sbdm-kv" id="sbdmStudioStripeRow" style="padding:8px 0;border-bottom:1px solid #F3EEF4;display:none"><span>Your part of the Stripe fee <span id="sbdmStudioStripeNote" class="sbdm-faint" style="font-size:12px"></span></span><span id="sbdmStudioStripe">—</span></div>
          <div class="sbdm-kv" id="sbdmStudioBookingFeeRow" style="padding:8px 0;border-bottom:1px solid #F3EEF4;display:none"><span>Your part of the booking fee <span id="sbdmStudioBookingFeeNote" class="sbdm-faint" style="font-size:12px"></span></span><span id="sbdmStudioBookingFee">—</span></div>
          <div class="sbdm-kv" style="padding:12px 0 4px;font-size:15px;border-bottom:0"><span style="color:#1A1A1A;font-weight:700">Studio gets</span><span id="sbdmStudioGets" style="font-weight:800;font-size:18px">—</span></div>
          <div id="sbdmFootnote" class="sbdm-faint" style="font-size:12.5px;margin-top:2px;display:none"></div>
          <div id="sbdmBalanceNote" class="sbdm-tint" style="margin-top:12px;display:none"></div>
        </div>
      </div>

      <div id="sbdmPanelRequest" role="tabpanel" hidden>
        <div id="sbdmReqBanner" class="sbdm-req-banner">
          <div class="sbdm-req-ic"><span class="ms" id="sbdmReqIcon">brush</span></div>
          <div style="min-width:0">
            <b id="sbdmReqTitle">Custom tattoo request</b>
            <div id="sbdmReqMeta" class="sbdm-faint">—</div>
          </div>
        </div>
        <div id="sbdmQaList" class="sbdm-req-qa"></div>
        <p id="sbdmQaEmpty" class="sbdm-muted" style="font-size:13.5px;margin-top:14px;display:none">No intake form answers for this booking.</p>
        <p id="sbdmReqCaption" class="sbdm-req-caption" style="display:none"></p>
      </div>
    </div>
    <div class="smf">
      <button type="button" id="sbdmMessage" class="sbdm-btn ghost" style="margin-right:auto;display:none"><span class="ms">chat_bubble</span><span id="sbdmMessageLabel">Message</span></button>
      <button type="button" class="sbdm-btn" data-close>Close</button>
    </div>
  </div>
</div>
