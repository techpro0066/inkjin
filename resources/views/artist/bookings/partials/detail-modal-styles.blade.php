<style>
  /* Self-contained overlay — works without Tailwind (Inbox has no Tailwind CDN). */
  .artist-bdm{
    position:fixed!important;inset:0!important;z-index:560;
    width:100vw!important;height:100vh!important;height:100dvh!important;
    margin:0!important;flex:none!important;align-self:auto!important;
    display:none;align-items:center;justify-content:center;
    padding:24px;overflow:hidden;
    pointer-events:none;opacity:0;
    transition:opacity .28s cubic-bezier(.22,1,.36,1);
  }
  /* Mobile chat thread is position:fixed;z-index:40 — keep detail above it. */
  body.inbox-detail-open #chatView.mobile-open{z-index:1!important}
  .artist-bdm.artist-bdm-open{
    display:flex!important;pointer-events:auto;opacity:1;
  }
  .artist-bdm-backdrop{
    position:absolute;inset:0;
    background:rgba(20,10,30,.45);
    opacity:0;transition:opacity .32s cubic-bezier(.22,1,.36,1);
  }
  .artist-bdm.artist-bdm-open .artist-bdm-backdrop{opacity:1;pointer-events:auto}
  .artist-bdm-panel{
    position:relative;z-index:10;
    display:flex;flex-direction:column;
    width:600px;max-width:100%;
    max-height:calc(100vh - 48px);max-height:calc(100dvh - 48px);
    background:#fff;border-radius:20px;
    box-shadow:0 20px 60px rgba(0,0,0,.25);overflow:hidden;
    opacity:0;transform:translateY(1rem) scale(.97);
    transition:opacity .32s cubic-bezier(.22,1,.36,1),transform .36s cubic-bezier(.22,1,.36,1);
    pointer-events:none;
  }
  .artist-bdm.artist-bdm-open .artist-bdm-panel{opacity:1;transform:translateY(0) scale(1);pointer-events:auto}
  .artist-bdm .abdm-mh{padding:22px 24px 14px;display:flex;justify-content:space-between;align-items:flex-start;border-bottom:1px solid #F0EAF2;flex-shrink:0}
  .artist-bdm .abdm-mh h2{font-size:20px;font-weight:800;letter-spacing:-.3px;color:#1A1A1A;margin:0}
  .artist-bdm .abdm-mb{padding:16px 24px 20px;flex:1 1 auto;min-height:0;overflow-y:auto;overscroll-behavior:contain}
  .artist-bdm .abdm-mf{padding:14px 24px;border-top:1px solid #F0EAF2;display:flex;align-items:center;justify-content:flex-end;gap:10px;background:#FCFAFD;flex-shrink:0;flex-wrap:nowrap}
  .artist-bdm .abdm-tabs{display:flex;gap:28px;border-bottom:1px solid #E8DFEA;margin:0 0 16px;overflow-x:auto}
  .artist-bdm .abdm-tabs button{padding:0 2px 12px;font-weight:600;color:#6F6874;font-size:14.5px;background:none;border:0;cursor:pointer;flex-shrink:0;white-space:nowrap}
  .artist-bdm .abdm-tabs button.on{color:#1A1A1A;box-shadow:inset 0 -2.5px 0 #1A1A1A}
  .artist-bdm .abdm-kv{display:flex;justify-content:space-between;gap:12px;padding:10px 0;border-bottom:1px solid #F3EEF4;font-size:13.5px}
  .artist-bdm .abdm-kv > span:first-child{color:#6F6874}
  .artist-bdm .abdm-kv > span:last-child{font-weight:600;text-align:right;color:#1A1A1A}
  .artist-bdm .abdm-btn{display:inline-flex;align-items:center;gap:8px;background:#1A1A1A;color:#fff;border-radius:10px;padding:10px 16px;font-weight:600;font-size:13.5px;white-space:nowrap;border:0;cursor:pointer;font:inherit;text-decoration:none}
  .artist-bdm .abdm-btn .material-symbols-outlined{font-size:18px}
  .artist-bdm .abdm-btn.ghost{background:#fff;color:#1A1A1A;border:1px solid #E8DFEA}
  .artist-bdm .abdm-btn.confirm{background:#1F6B4E}
  .artist-bdm .abdm-btn:disabled,.artist-bdm .abdm-btn[aria-disabled="true"]{opacity:.45;pointer-events:none;cursor:not-allowed}
  .artist-bdm .abdm-pill{display:inline-flex;align-items:center;gap:5px;border-radius:20px;font-size:11.5px;font-weight:600;padding:3px 9px;white-space:nowrap}
  .artist-bdm .abdm-pill:before{content:'';width:5px;height:5px;border-radius:50%;background:currentColor}
  .artist-bdm .abdm-pill.g{background:#E6F8EE;color:#00A650}
  .artist-bdm .abdm-pill.a{background:#FFF3DE;color:#B86E00}
  .artist-bdm .abdm-pill.r{background:#FDECEC;color:#C62828}
  .artist-bdm .abdm-pill.k{background:#F0EDF2;color:#6F6874}
  .artist-bdm .abdm-pill.b{background:#E8F0FF;color:#1E5BD8}
  .artist-bdm .abdm-grid2{display:grid;grid-template-columns:1fr 1fr;column-gap:24px}
  .artist-bdm .abdm-sect{margin:18px 0 0;background:#fff}
  .artist-bdm .abdm-tint{background:#FAF0FB;border:1px solid #F1E7F3;border-radius:16px;padding:10px 12px;font-size:12.5px;line-height:1.6;color:#5B2A94}
  .artist-bdm .abdm-faint{color:#9A929E}
  .artist-bdm .abdm-muted{color:#6F6874}
  @media (max-width:900px){
    .artist-bdm{padding:12px}
    .artist-bdm-panel{width:100%;max-height:calc(100dvh - 24px);border-radius:16px}
    .artist-bdm .abdm-grid2{grid-template-columns:1fr}
    .artist-bdm .abdm-mf{padding:12px 14px;gap:8px;overflow-x:auto;-webkit-overflow-scrolling:touch}
    .artist-bdm .abdm-mf .abdm-btn{flex:0 0 auto;padding:9px 12px;font-size:12.5px}
  }
</style>
