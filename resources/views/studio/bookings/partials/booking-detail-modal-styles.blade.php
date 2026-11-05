<style>
  .smodal{position:fixed;inset:0;background:rgba(20,10,30,.45);display:flex;align-items:center;justify-content:center;padding:24px;z-index:400}
  .smodal[hidden]{display:none!important}
  .smodal .smbox{background:#fff;border-radius:22px;width:min(720px,100%);max-height:calc(100vh - 48px);max-height:calc(100dvh - 48px);display:flex;flex-direction:column;box-shadow:0 20px 60px rgba(0,0,0,.25);overflow:hidden}
  .smodal .smh{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;padding:22px 24px 14px;border-bottom:1px solid #F0EAF2;flex-shrink:0}
  .smodal .smh h2{font-size:20px;font-weight:800;margin:0;letter-spacing:-.3px;color:#1A1A1A}
  .smodal .smb{padding:16px 24px 20px;overflow:auto;min-height:0;flex:1 1 auto}
  .smodal .smf{padding:14px 24px;border-top:1px solid #F0EAF2;display:flex;align-items:center;justify-content:flex-end;gap:10px;background:#FCFAFD;flex-shrink:0}
  .smodal .x{border:0;background:none;cursor:pointer;color:#9A929E;width:34px;height:34px;border-radius:50%;display:flex;align-items:center;justify-content:center}
  .smodal .x:hover{background:#F4F0F6;color:#1A1A1A}
  .smodal .sbdm-tabs{display:flex;gap:28px;border-bottom:1px solid #E8DFEA;margin:0 0 16px;overflow-x:auto}
  .smodal .sbdm-tabs button{padding:0 2px 12px;font-weight:600;color:#6F6874;font-size:14.5px;background:none;border:0;cursor:pointer;flex-shrink:0;white-space:nowrap;font:inherit}
  .smodal .sbdm-tabs button.on{color:#1A1A1A;box-shadow:inset 0 -2.5px 0 #1A1A1A}
  .smodal .sbdm-kv{display:flex;justify-content:space-between;gap:12px;padding:10px 0;border-bottom:1px solid #F3EEF4;font-size:13.5px}
  .smodal .sbdm-kv > span:first-child{color:#6F6874}
  .smodal .sbdm-kv > span:last-child{font-weight:600;text-align:right;color:#1A1A1A}
  .smodal .sbdm-grid2{display:grid;grid-template-columns:1fr 1fr;column-gap:24px}
  .smodal .sbdm-pill{display:inline-flex;align-items:center;gap:5px;border-radius:20px;font-size:11.5px;font-weight:600;padding:3px 9px;white-space:nowrap}
  .smodal .sbdm-pill:before{content:'';width:5px;height:5px;border-radius:50%;background:currentColor}
  .smodal .sbdm-pill.g{background:#E6F8EE;color:#00A650}
  .smodal .sbdm-pill.a{background:#FFF3DE;color:#B86E00}
  .smodal .sbdm-pill.r{background:#FDECEC;color:#C62828}
  .smodal .sbdm-pill.k{background:#F0EDF2;color:#6F6874}
  .smodal .sbdm-pill.b{background:#E8F0FF;color:#1E5BD8}
  .smodal .sbdm-faint{color:#9A929E}
  .smodal .sbdm-muted{color:#6F6874}
  .smodal .sbdm-tint{background:#FAF0FB;border:1px solid #F1E7F3;border-radius:16px;padding:10px 12px;font-size:12.5px;line-height:1.6;color:#5B2A94}
  .smodal .sbdm-btn{display:inline-flex;align-items:center;gap:8px;background:#1A1A1A;color:#fff;border-radius:10px;padding:10px 16px;font-weight:600;font-size:13.5px;white-space:nowrap;border:0;cursor:pointer;font:inherit;text-decoration:none}
  .smodal .sbdm-btn.ghost{background:#fff;color:#1A1A1A;border:1px solid #E8DFEA}
  .smodal .sbdm-btn .ms{font-size:18px}
  .smodal .sbdm-ans{border:1px solid #E8DFEA;border-radius:12px;padding:10px 12px;margin-top:8px;font-size:13px}
  .smodal .sbdm-ans b{display:block;font-size:12px;color:#6F6874;font-weight:600;margin-bottom:2px}
  .smodal .sbdm-req-banner{display:flex;align-items:center;gap:12px;background:#FAF0FB;border:1px solid #F1E7F3;border-radius:14px;padding:12px 14px}
  .smodal .sbdm-req-ic{width:38px;height:38px;border-radius:50%;background:#fff;color:#3E007C;display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 0 0 1px #F1E7F3}
  .smodal .sbdm-req-ic .ms{font-size:20px}
  .smodal .sbdm-req-banner b{display:block;font-size:14.5px;font-weight:700;color:#1A1A1A}
  .smodal .sbdm-req-banner .sbdm-faint{font-size:12.5px;margin-top:2px}
  .smodal .sbdm-req-qa{display:grid;grid-template-columns:1fr 1fr;column-gap:20px;margin-top:14px}
  .smodal .sbdm-req-qa-item{padding:12px 0;border-bottom:1px solid #F3EEF4;min-width:0}
  .smodal .sbdm-req-qa-item .lbl{font-size:10.5px;letter-spacing:.8px;font-weight:700;color:#6F6874;text-transform:uppercase;margin:0}
  .smodal .sbdm-req-qa-item .val{font-size:14px;font-weight:600;margin-top:4px;color:#1A1A1A;word-break:break-word;line-height:1.4}
  .smodal .sbdm-req-caption{font-size:12.5px;color:#9A929E;font-style:italic;margin:14px 0 0;line-height:1.45}
  @media (max-width:900px){
    .smodal .sbdm-grid2{grid-template-columns:1fr}
    .smodal .sbdm-req-qa{grid-template-columns:1fr}
    .smodal .smf{flex-wrap:wrap}
    .smodal .sbdm-btn{flex:1 1 auto;justify-content:center}
  }
</style>
