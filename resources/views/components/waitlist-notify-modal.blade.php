<style>
  #waitlistNotifyModal{
    position:fixed;inset:0;z-index:320;display:none;align-items:center;justify-content:center;padding:16px;
    opacity:0;pointer-events:none;transition:opacity .2s ease;
  }
  #waitlistNotifyModal.is-open{opacity:1;pointer-events:auto}
  #waitlistNotifyModal.flex,#waitlistNotifyModal.is-open{display:flex}
  #waitlistNotifyModal.hidden{display:none!important}
  #waitlistNotifyModalBackdrop{position:absolute;inset:0;background:rgba(20,10,30,.45);border:0;cursor:default}
  #waitlistNotifyModal .waitlist-notify-dialog{
    position:relative;background:#fff;border-radius:22px;width:440px;max-width:100%;
    box-shadow:0 20px 60px rgba(0,0,0,.25);overflow:hidden;
    transform:scale(.96) translateY(8px);transition:transform .2s ease;
  }
  #waitlistNotifyModal.is-open .waitlist-notify-dialog{transform:scale(1) translateY(0)}
  #waitlistNotifyModal .wn-body{padding:24px 24px 18px}
  #waitlistNotifyModal .wn-icon{
    width:48px;height:48px;border-radius:12px;background:#EDEDED;color:#1A1A1A;
    display:flex;align-items:center;justify-content:center;margin-bottom:14px;
  }
  #waitlistNotifyModal .wn-icon .ms,
  #waitlistNotifyModal .wn-icon .material-symbols-outlined{font-size:24px;line-height:1;color:#1A1A1A}
  #waitlistNotifyModal .wn-title{
    font-size:20px;font-weight:800;margin:0 0 8px;color:#1A1A1A;letter-spacing:-.2px;line-height:1.25;
  }
  #waitlistNotifyModal .wn-copy{
    margin:0;color:#3a3a3a;font-size:14px;line-height:1.55;
  }
  #waitlistNotifyModal .wn-copy b,
  #waitlistNotifyModal .wn-copy .font-semibold{font-weight:700;color:#1A1A1A}
  #waitlistNotifyModal .wn-note{
    background:#F7EAFD;border:1px solid #EBD6F7;border-radius:12px;
    padding:12px 16px;font-size:12.5px;color:#555;margin-top:16px;line-height:1.5;
  }
  #waitlistNotifyModal .wn-note[hidden],
  #waitlistNotifyModal .wn-note.hidden{display:none!important}
  #waitlistNotifyModal .wn-footer{
    display:flex;gap:12px;padding:16px 24px;background:#FCF6FE;border-top:1px solid #F0E4F5;
  }
  #waitlistNotifyModal .wn-footer button{
    flex:1;justify-content:center;padding:13px 16px;border-radius:12px;font:inherit;font-size:14px;
    font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:8px;
  }
  #waitlistNotifyModal #waitlistNotifyModalCancel{
    border:1px solid #E8DFEA;background:#fff;color:#1A1A1A;
  }
  #waitlistNotifyModal #waitlistNotifyModalCancel:hover{background:#FAF7FB}
  #waitlistNotifyModal #waitlistNotifyModalConfirm{
    border:0;background:#1A1A1A;color:#fff;
  }
  #waitlistNotifyModal #waitlistNotifyModalConfirm:hover{opacity:.92}
  #waitlistNotifyModal #waitlistNotifyModalConfirm .ms,
  #waitlistNotifyModal #waitlistNotifyModalConfirm .material-symbols-outlined{font-size:18px;line-height:1}
  @media (max-width:560px){
    #waitlistNotifyModal{align-items:flex-end;padding:0}
    #waitlistNotifyModal .waitlist-notify-dialog{width:100%;border-radius:18px 18px 0 0}
    #waitlistNotifyModal .wn-footer{flex-direction:column-reverse}
  }
</style>

<div id="waitlistNotifyModal" class="hidden" aria-hidden="true" role="dialog" aria-labelledby="waitlistNotifyModalTitle" aria-modal="true">
  <button type="button" id="waitlistNotifyModalBackdrop" aria-label="Close dialog"></button>
  <div class="waitlist-notify-dialog">
    <div class="wn-body">
      <div class="wn-icon" id="waitlistNotifyModalIcon" aria-hidden="true">
        <span class="ms">mail</span>
      </div>
      <h3 id="waitlistNotifyModalTitle" class="wn-title">Send waitlist email?</h3>
      <p id="waitlistNotifyModalBody" class="wn-copy">
        We'll email <b id="waitlistNotifyModalCount">0</b>
        <span id="waitlistNotifyModalCountLabel">subscribers</span> that your books are open and they can book on your artist page.
      </p>
      <div id="waitlistNotifyModalNote" class="wn-note">
        Subscribers who have already been notified will not receive another email.
      </div>
    </div>
    <div class="wn-footer">
      <button type="button" id="waitlistNotifyModalCancel">Cancel</button>
      <button type="button" id="waitlistNotifyModalConfirm">
        <span class="ms">send</span>
        Send email
      </button>
    </div>
  </div>
</div>
