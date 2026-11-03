{{-- Shared Send-back modal for studio offers + Via-studio custom requests --}}
<style>
  #studioOfferSendBackModal.ov{
    display:none;position:fixed;inset:0;z-index:420;
    background:rgba(20,10,30,.45);padding:16px;
    align-items:center;justify-content:center;
  }
  #studioOfferSendBackModal.ov.open{display:flex}
  #studioOfferSendBackModal .modal{
    width:min(560px,100%);max-width:100%;
    max-height:calc(100vh - 32px);max-height:calc(100dvh - 32px);
    background:#fff;border-radius:22px;box-shadow:0 20px 60px rgba(0,0,0,.25);
    display:flex;flex-direction:column;overflow:hidden;padding:0;
  }
  #studioOfferSendBackModal .mh{
    padding:22px 24px 14px;border-bottom:1px solid #F0EAF2;
    display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-shrink:0;
  }
  #studioOfferSendBackModal .mh h2{font-size:18px;font-weight:800;margin:0;letter-spacing:-.2px;color:#1A1A1A}
  #studioOfferSendBackModal .mh .sub{font-size:13px;color:var(--muted,#6F6874);margin-top:4px;line-height:1.45}
  #studioOfferSendBackModal .mx{
    border:0;background:none;cursor:pointer;color:var(--muted,#6F6874);
    width:36px;height:36px;border-radius:10px;
    display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;
  }
  #studioOfferSendBackModal .mx:hover{background:#F7F3F9;color:#1A1A1A}
  #studioOfferSendBackModal .mb{
    padding:18px 24px;flex:1 1 auto;min-height:0;
    overflow-y:auto;overscroll-behavior:contain;-webkit-overflow-scrolling:touch;
  }
  #studioOfferSendBackModal .mf{
    display:flex;gap:10px;align-items:center;justify-content:flex-end;flex-wrap:wrap;
    padding:16px 24px;background:#FCF6FE;border-top:1px solid #F0E4F5;flex-shrink:0;
  }
  #studioOfferSendBackModal .mf .btn{min-width:110px;justify-content:center}
  #studioOfferSendBackModal .mf .btn[disabled]{opacity:.45;pointer-events:none;cursor:not-allowed}
  #studioOfferSendBackModal .hidden{display:none!important}
  #studioOfferSendBackModal textarea.in{
    background:#fff!important;border:1px solid var(--line,#E8DFEA)!important;
    border-radius:10px!important;box-shadow:none!important;
    padding:10px 13px!important;min-height:90px;width:100%;
    font:inherit;font-size:13.5px;color:var(--ink,#1A1A1A);resize:vertical;
  }
  @media (max-width:640px){
    #studioOfferSendBackModal .mf .btn{flex:1 1 auto}
  }
</style>

<div class="ov" id="studioOfferSendBackModal" role="dialog" aria-modal="true" aria-labelledby="studioOfferSendBackTitle" onclick="if(event.target===this)closeStudioOfferSendBack()">
  <div class="modal" onclick="event.stopPropagation()">
    <div class="mh">
      <div>
        <h2 id="studioOfferSendBackTitle">Send back to studio?</h2>
        <div class="sub">The studio can assign another artist.</div>
      </div>
      <button type="button" class="mx" onclick="closeStudioOfferSendBack()" aria-label="Close">
        <span class="ms faint">close</span>
      </button>
    </div>
    <div class="mb">
      <label class="fl" for="studioOfferSendBackReason">Why? (optional, the studio sees this)</label>
      <textarea id="studioOfferSendBackReason" class="in" rows="4" maxlength="2000" placeholder="e.g. Not my style, fully booked in October"></textarea>
      <p id="studioOfferSendBackError" class="hidden" style="color:#C62828;font-size:13px;font-weight:600;margin-top:8px"></p>
    </div>
    <div class="mf">
      <button type="button" class="btn ghost" onclick="closeStudioOfferSendBack()">Cancel</button>
      <button type="button" class="btn" id="studioOfferSendBackSubmit" style="background:#C62828">
        <span class="ms">send</span>Send back
      </button>
    </div>
  </div>
</div>

<script>
(function () {
  var pendingSendBackUrl = null;
  var csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

  function openSendBackModal(url, studioName) {
    pendingSendBackUrl = url || null;
    var title = document.getElementById('studioOfferSendBackTitle');
    if (title) {
      title.textContent = studioName
        ? ('Send back to ' + studioName + '?')
        : 'Send back to studio?';
    }
    document.getElementById('studioOfferSendBackReason').value = '';
    document.getElementById('studioOfferSendBackError').classList.add('hidden');
    document.getElementById('studioOfferSendBackModal').classList.add('open');
  }

  window.closeStudioOfferSendBack = function () {
    document.getElementById('studioOfferSendBackModal').classList.remove('open');
  };

  window.sendBackStudioRequest = function (el) {
    if (!el) return;
    var url = el.getAttribute('data-url') || '';
    var studio = el.getAttribute('data-studio') || '';
    if (!url) return;
    openSendBackModal(url, studio);
  };

  window.openStudioSendBackForUrl = function (url, studioName) {
    if (!url) return;
    openSendBackModal(url, studioName || '');
  };

  /** Opens the shared custom quote modal for a studio offer (no accept step). */
  window.openStudioOfferDetail = function (id) {
    if (window.CustomRequestUI && typeof window.CustomRequestUI.openStudioOfferDetail === 'function') {
      window.CustomRequestUI.openStudioOfferDetail(id);
      return;
    }
    if (typeof window.openCustomRequestDetail === 'function') {
      window.openCustomRequestDetail('studio:' + id);
    }
  };

  document.getElementById('studioOfferSendBackSubmit')?.addEventListener('click', async function () {
    if (!pendingSendBackUrl) return;
    var btn = this;
    var err = document.getElementById('studioOfferSendBackError');
    btn.disabled = true;
    err.classList.add('hidden');
    try {
      var res = await fetch(pendingSendBackUrl, {
        method: 'POST',
        headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
        credentials: 'include',
        body: JSON.stringify({ reason: document.getElementById('studioOfferSendBackReason').value || '' })
      });
      var data = await res.json().catch(function () { return {}; });
      if (!res.ok) throw new Error(data.message || 'Unable to send back.');
      if (window.bpToast) bpToast(data.message || 'Sent back to studio');
      location.reload();
    } catch (e) {
      err.textContent = e.message || 'Unable to send back.';
      err.classList.remove('hidden');
      btn.disabled = false;
    }
  });
})();
</script>
