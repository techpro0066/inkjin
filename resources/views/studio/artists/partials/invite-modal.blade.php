<div class="smodal" id="m-invite" hidden>
  <div class="smbox" role="dialog" aria-modal="true" aria-labelledby="m-invite-t">
    <div class="smh">
      <div class="ic" style="background:#F3E8FF;color:#3E007C"><span class="ms">person_add</span></div>
      <div>
        <h2 id="m-invite-t">Invite an artist</h2>
        <div class="muted" style="font-size:13.5px;margin-top:4px">They join once they accept.</div>
      </div>
      <button type="button" class="x" data-close aria-label="Close"><span class="ms">close</span></button>
    </div>
    <div class="smb">
      <div class="formgrid">
        <div class="full">
          <span class="fl">Email or Bookpay username <span style="color:#C62828">*</span></span>
          <input class="in" id="inv-email" type="text" value="" placeholder="artist@email.com or @username">
        </div>
        <div class="full">
          <span class="fl">Relationship</span>
          <select class="in" id="inv-rel">
            @foreach (($relationshipOptions ?? []) as $key => $label)
              <option value="{{ $key }}" @selected($key === 'resident')>{{ $label }}</option>
            @endforeach
          </select>
        </div>
      </div>
      <span class="fl" style="margin-top:14px">How they're paid at your studio</span>
      <div class="grid invite-pay-opts" data-group="inv" style="gap:10px;margin-top:8px">
        <button type="button" class="opt on" data-v="split" role="radio" aria-checked="true">
          <span class="ms rad">radio_button_checked</span>
          <div style="flex:1">
            <b>Revenue split</b>
            <small>Each payment is split automatically. The artist accepts it with the invite.</small>
            <div data-when="inv:split">
              <div class="split">
                <div class="box">
                  <span>Artist gets</span>
                  <input type="number" min="0" max="100" value="60" id="inv-artist-pct" aria-label="Artist gets" onclick="event.stopPropagation()">%
                </div>
                <div class="box">
                  <span>Studio gets</span>
                  <b class="calc" id="invsp">40%</b>
                </div>
              </div>
              <div class="help">Your default is Artist 60% · Studio 40%.</div>
            </div>
          </div>
        </button>
        <button type="button" class="opt" data-v="direct" role="radio" aria-checked="false">
          <span class="ms rad">radio_button_unchecked</span>
          <div style="flex:1">
            <b>Workstation rent</b>
            <small>The artist gets the full payment for each booking and pays you rent outside Bookpay.</small>
          </div>
        </button>
      </div>
    </div>
    <div class="smf">
      <button type="button" class="btn ghost" data-close>Cancel</button>
      <button type="button" class="btn" id="inv-send"><span class="ms">send</span>Send invite</button>
    </div>
  </div>
</div>
