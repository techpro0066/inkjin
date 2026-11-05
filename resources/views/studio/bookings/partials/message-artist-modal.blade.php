<style>
  .studio-msm{pointer-events:none;opacity:0;transition:opacity .28s cubic-bezier(.22,1,.36,1);padding:24px;align-items:center;justify-content:center;overflow:hidden;position:fixed;inset:0;z-index:500;display:flex}
  .studio-msm.studio-msm-open{pointer-events:auto;opacity:1}
  .studio-msm-backdrop{opacity:0;transition:opacity .32s cubic-bezier(.22,1,.36,1);position:absolute;inset:0;background:rgba(20,10,30,.45)}
  .studio-msm.studio-msm-open .studio-msm-backdrop{opacity:1}
  .studio-msm-panel{
    display:flex;flex-direction:column;width:600px;max-width:100%;
    max-height:calc(100vh - 48px);max-height:calc(100dvh - 48px);
    background:#fff;border-radius:20px;box-shadow:0 20px 60px rgba(0,0,0,.25);overflow:hidden;
    opacity:0;transform:translateY(1rem) scale(.97);position:relative;z-index:10;
    transition:opacity .32s cubic-bezier(.22,1,.36,1),transform .36s cubic-bezier(.22,1,.36,1)
  }
  .studio-msm.studio-msm-open .studio-msm-panel{opacity:1;transform:translateY(0) scale(1)}
  .studio-msm-backdrop,.studio-msm-panel{pointer-events:none}
  .studio-msm.studio-msm-open .studio-msm-backdrop,
  .studio-msm.studio-msm-open .studio-msm-panel{pointer-events:auto}
  .studio-msm .smm-mh{padding:20px 22px 14px;display:flex;justify-content:space-between;align-items:flex-start;border-bottom:1px solid #F0EAF2;flex-shrink:0;gap:12px}
  .studio-msm .smm-mh h2{font-size:18px;font-weight:800;letter-spacing:-.3px;color:#1A1A1A;margin:0}
  .studio-msm .smm-sub{font-size:12.5px;color:#6F6874;margin-top:4px}
  .studio-msm .smm-ini{width:40px;height:40px;border-radius:50%;background:#1A1A1A;color:#fff;font-size:13px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0}
  .studio-msm .smm-mb{padding:16px 20px;flex:1 1 auto;min-height:220px;max-height:360px;overflow-y:auto;overscroll-behavior:contain;display:flex;flex-direction:column;gap:10px;justify-content:flex-end}
  .studio-msm .smm-bubble-other{align-self:flex-start;max-width:78%;background:#F4F0F6;border-radius:14px 14px 14px 4px;padding:10px 14px;font-size:13.5px;color:#1A1A1A;line-height:1.45;white-space:pre-wrap;word-break:break-word}
  .studio-msm .smm-bubble-self{align-self:flex-end;max-width:78%;background:#3E007C;color:#fff;border-radius:14px 14px 4px 14px;padding:10px 14px;font-size:13.5px;line-height:1.45;white-space:pre-wrap;word-break:break-word}
  .studio-msm .smm-empty{align-self:stretch;display:flex;align-items:center;justify-content:center;min-height:160px;color:#6F6874;font-size:13.5px;text-align:center;padding:12px}
  .studio-msm .smm-composer{padding:12px 20px;border-top:1px solid #E8DFEA;display:flex;align-items:center;gap:10px;flex-shrink:0}
  .studio-msm .smm-in{
    flex:1;min-width:0;background:#fff;border:1px solid #E8DFEA;border-radius:10px;padding:10px 13px;
    display:flex;align-items:center;gap:8px;min-height:40px;box-shadow:none
  }
  .studio-msm .smm-in:focus-within{border-color:#3E007C;box-shadow:0 0 0 3px #F3E8FF}
  .studio-msm .smm-in input,
  .studio-msm #smmInput{
    appearance:none;-webkit-appearance:none;
    border:0!important;outline:0!important;box-shadow:none!important;
    font:inherit;flex:1;min-width:0;background:transparent!important;color:#1A1A1A;
    padding:0!important;margin:0!important;width:100%;min-height:0;line-height:1.4;border-radius:0
  }
  .studio-msm .smm-in input:disabled{color:#9A929E}
  .studio-msm .smm-locked{display:none;width:100%;align-items:center;gap:8px;color:#6F6874;font-size:13px}
  .studio-msm.smm-readonly .smm-locked{display:flex}
  .studio-msm.smm-readonly .smm-input-wrap{display:none}
  .studio-msm.smm-readonly #smmSendBtn{display:none!important}
  .studio-msm .smm-mf{padding:14px 22px;border-top:1px solid #F0EAF2;display:flex;align-items:center;justify-content:flex-end;gap:10px;background:#FCFAFD;flex-shrink:0}
  .studio-msm .smm-btn{display:inline-flex;align-items:center;gap:8px;background:#1A1A1A;color:#fff;border-radius:10px;padding:10px 16px;font-weight:600;font-size:13.5px;white-space:nowrap;border:0;cursor:pointer;font:inherit;text-decoration:none}
  .studio-msm .smm-btn .ms{font-size:18px}
  .studio-msm .smm-btn.ghost{background:#fff;color:#1A1A1A;border:1px solid #E8DFEA}
  .studio-msm .smm-btn:disabled{opacity:.45;pointer-events:none;cursor:not-allowed}
  .studio-msm .smm-send-ico{padding:10px 12px}
  .studio-msm .smm-faint{color:#9A929E}
  @media (max-width:900px){
    .studio-msm{padding:12px}
    .studio-msm-panel{width:100%;max-height:calc(100dvh - 24px);border-radius:16px}
    .studio-msm .smm-mb{max-height:none;min-height:180px}
    .studio-msm .smm-mf{padding:12px 14px;gap:8px;flex-wrap:wrap}
  }
</style>

<div id="studioMessageArtistModal"
  class="studio-msm"
  aria-hidden="true"
  aria-modal="true"
  role="dialog"
  aria-labelledby="smmTitle">
  <div class="studio-msm-backdrop" data-close-studio-msm></div>
  <div class="studio-msm-panel">
    <div class="smm-mh">
      <div style="display:flex;align-items:center;gap:12px;min-width:0">
        <div id="smmAvatar" class="smm-ini">?</div>
        <div style="min-width:0">
          <h2 id="smmTitle">Message artist</h2>
          <div id="smmSub" class="smm-sub">—</div>
        </div>
      </div>
      <button type="button" class="smm-faint" data-close-studio-msm aria-label="Close" style="background:none;border:0;cursor:pointer;padding:0;display:inline-flex">
        <span class="ms" style="font-size:22px">close</span>
      </button>
    </div>
    <div id="smmMessages" class="smm-mb" aria-live="polite">
      <div class="smm-empty">Loading conversation…</div>
    </div>
    <div class="smm-composer" id="smmComposer">
      <span class="ms smm-faint" style="font-size:20px">attach_file</span>
      <div class="smm-in">
        <div class="smm-input-wrap" style="display:flex;flex:1;min-width:0">
          <input type="text" id="smmInput" placeholder="Type a message" autocomplete="off" aria-label="Message">
        </div>
        <div class="smm-locked">
          <span class="ms" style="font-size:18px;color:#9A929E">lock</span>
          <span id="smmLockedText">This chat is read-only.</span>
        </div>
      </div>
      <button type="button" id="smmSendBtn" class="smm-btn smm-send-ico" aria-label="Send">
        <span class="ms" style="color:#fff">send</span>
      </button>
    </div>
    <div class="smm-mf">
      <a id="smmOpenInbox" href="{{ route('studio.chat.index') }}" class="smm-btn ghost"><span class="ms">mail</span>Open in Inbox</a>
    </div>
  </div>
</div>
