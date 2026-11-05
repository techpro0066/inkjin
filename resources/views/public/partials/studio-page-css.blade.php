<style>
@font-face{font-family:'PJS';font-weight:400;src:url('{{ asset('new-ui-design-assets/pjs-400.woff2') }}') format('woff2')}
@font-face{font-family:'PJS';font-weight:500;src:url('{{ asset('new-ui-design-assets/pjs-500.woff2') }}') format('woff2')}
@font-face{font-family:'PJS';font-weight:600;src:url('{{ asset('new-ui-design-assets/pjs-600.woff2') }}') format('woff2')}
@font-face{font-family:'PJS';font-weight:700;src:url('{{ asset('new-ui-design-assets/pjs-700.woff2') }}') format('woff2')}
@font-face{font-family:'PJS';font-weight:800;src:url('{{ asset('new-ui-design-assets/pjs-800.woff2') }}') format('woff2')}
@font-face{font-family:'MS';font-display:block;src:url('{{ asset('new-ui-design-assets/material-symbols-outlined.woff2') }}') format('woff2')}
@font-face{font-family:'Space Grotesk';font-weight:700;font-display:swap;src:url('{{ asset('new-ui-design-assets/space-grotesk-700.woff2') }}') format('woff2')}
.flogo b{font-family:'Space Grotesk',system-ui,sans-serif!important;font-weight:700!important;letter-spacing:-.055em!important}
:root{--a1:#1A1A2E;--a2:#3A3A5C;--acc:#1A1A2E;--accl:#ECEBF3;--ink:#17141A;--muted:#6B6470;--faint:#9A93A0;--line:#ECE6EF;--bg:#FBF9FC;--card:#fff}
*{box-sizing:border-box;margin:0}
html{margin:0;padding:0;background:var(--a1)}
body{margin:0!important;padding:0!important;font-family:'PJS',system-ui,sans-serif;background:var(--bg);color:var(--ink);font-size:15px;line-height:1.5;-webkit-font-smoothing:antialiased;display:block}
button{font:inherit;color:inherit;cursor:pointer;border:0;background:none}
a{color:inherit;text-decoration:none}
.ms{font-family:'MS';font-weight:normal;font-style:normal;font-size:20px;line-height:1;display:inline-block;letter-spacing:normal;text-transform:none;white-space:nowrap;-webkit-font-feature-settings:'liga';font-feature-settings:'liga'}
.sp-ban{height:276px;background:linear-gradient(120deg,var(--a1),var(--a2));position:relative;background-position:center top;background-size:cover;background-repeat:no-repeat}
.sp-wrap{max-width:1080px;margin:0 auto;padding:0 24px}
.sp-logo{width:108px;height:108px;border-radius:24px;background:#3E007C;color:#fff;font-weight:800;font-size:30px;display:flex;align-items:center;justify-content:center;border:5px solid var(--bg);flex-shrink:0;background-position:center;background-size:cover;background-repeat:no-repeat}
.sp-desc{max-width:62ch;color:var(--muted);line-height:1.65;margin-top:14px}
.sp-sec{margin-top:40px}.sp-sec h2{font-size:22px;letter-spacing:-.4px;margin-bottom:4px}.sp-sec p.s{color:var(--muted);font-size:14px;margin-bottom:18px}
.sp-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:18px}
.sp-card{background:#fff;border:1px solid #ECE6EF;border-radius:20px;padding:22px;text-align:center;display:flex;flex-direction:column;align-items:center}
.sp-av{width:88px;height:88px;border-radius:50%;background:#EDE6F0 center/cover;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:24px;color:var(--ink)}
.sp-card h3{font-size:18px;margin-top:12px}.sp-h{color:var(--faint);font-size:13px;margin-top:2px}
.sp-tags{display:flex;flex-wrap:wrap;gap:6px;justify-content:center;margin:12px 0 16px}.sp-tags span{background:#F4F1F6;border-radius:6px;padding:3px 9px;font-size:12px;font-weight:600;color:var(--muted)}
.sp-btns{display:flex;flex-direction:column;gap:8px;width:100%;margin-top:auto}
.sp-b{display:block;padding:12px;border-radius:12px;font-weight:700;font-size:14px;border:1.5px solid #17141A;text-align:center}
.sp-b.pri{background:var(--acc);border-color:var(--acc);color:#fff}
.sp-guest{display:flex;gap:16px;align-items:center;background:#fff;border:1px solid #ECE6EF;border-radius:18px;padding:16px 20px;margin-bottom:12px}
.sp-date{width:64px;text-align:center;border-radius:14px;background:var(--accl);color:var(--a1);padding:8px 0}.sp-date small{display:block;font-size:11px;font-weight:700;letter-spacing:.6px}.sp-date b{font-size:24px;font-weight:800}
.sp-empty{background:#fff;border:1px dashed #E4DDE8;border-radius:18px;padding:28px 22px;text-align:center;color:var(--muted);font-size:14px}
@media(max-width:780px){.sp-grid{grid-template-columns:1fr}.sp-logo{width:88px;height:88px}.sp-ban{height:150px}}
.wf{display:flex;gap:8px;flex-wrap:wrap;margin:4px 0 16px}.wf button{font:inherit;font-weight:600;font-size:13px;border:1.5px solid #ECE6EF;background:#fff;border-radius:20px;padding:7px 14px;cursor:pointer}.wf button.on{background:var(--acc);border-color:var(--acc);color:#fff}
.wg{display:grid;grid-template-columns:repeat(4,1fr);gap:14px}.wk{display:block;background:#fff;border:1px solid #ECE6EF;border-radius:16px;overflow:hidden;color:inherit;text-decoration:none;position:relative}.wk[hidden]{display:none}
.wi{display:block;aspect-ratio:4/5;background:#F3F0F5 center/contain no-repeat}.wt{display:block;padding:10px 12px 12px}.wt b{display:block;font-size:14px}
.wb{display:flex;align-items:center;gap:6px;font-size:12.5px;color:var(--muted);margin-top:4px}.wb .ms{font-size:16px}
.wav{width:20px;height:20px;border-radius:50%;background:var(--accl) center/cover no-repeat;color:var(--a1);font-size:9px;font-weight:800;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;overflow:hidden}
.wp{display:block;font-size:12px;font-weight:700;color:var(--ink);margin-top:4px}
.wact{display:inline-flex;align-items:center;gap:5px;margin-top:8px;font-size:12.5px;font-weight:700;color:var(--ink);border:1.5px solid var(--ink);border-radius:20px;padding:5px 11px;background:#fff}
.wact .ms{font-size:15px}.wact.pri{background:var(--acc);border-color:var(--acc);color:#fff}
@media(max-width:780px){.wg{grid-template-columns:1fr 1fr}}
.sp-footer{border-top:1px solid #ECE6EF;margin-top:56px;padding:26px 0 40px;display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap}
.sp-footer nav{display:flex;flex-direction:row;padding:0;margin:0;gap:18px;font-size:13px;color:var(--muted)}.sp-footer nav a{color:inherit;text-decoration:none}
.flogo{display:flex;align-items:center;gap:10px}.flogo b{font-weight:800;font-size:19px;letter-spacing:-.6px;color:#17141A}.flogo small{font-size:9px;letter-spacing:1.2px;color:var(--muted);line-height:1.2;text-transform:uppercase}
@media(max-width:780px){.sp-footer{flex-direction:column;align-items:flex-start}}
.sp-hero{display:grid;grid-template-columns:1fr 340px;gap:40px;align-items:start}
.sp-hero .sp-logo{margin-top:-54px;position:relative;box-shadow:0 8px 24px rgba(20,10,30,.18)}
.sp-name{font-size:36px;font-weight:800;letter-spacing:-1.1px;line-height:1.1;margin:14px 0 0}
.sp-handle{color:var(--faint);font-weight:600;font-size:14px;margin-top:4px}
.sp-hero .sp-desc{margin-top:14px}
.sp-mi-list{display:flex;flex-direction:column;gap:10px;margin-top:22px}
.sp-mi{display:flex;gap:10px;align-items:flex-start;font-size:14px}.sp-mi b{font-weight:700}.sp-mi .ms{font-size:20px;color:var(--muted)}
.sp-sub{color:var(--muted);font-size:13.5px}
.chips{display:flex;flex-wrap:wrap;gap:6px}
.chip{border:1px solid var(--line);background:#fff;border-radius:20px;padding:4px 11px;font-size:12.5px;font-weight:600;color:var(--muted)}
.chip.pri{background:var(--acc);border-color:var(--acc);color:#fff}
.sp-soc{display:flex;gap:8px;margin-top:20px}
.sp-soc a{width:38px;height:38px;border-radius:50%;border:1px solid #EAE4EC;background:#fff;display:flex;align-items:center;justify-content:center;color:#17141A}
.sp-soc svg{width:17px;height:17px}
.sp-book{background:#fff;border:1px solid #EAE4EC;border-radius:20px;padding:22px;margin-top:-70px;position:relative;z-index:2;box-shadow:0 18px 50px rgba(30,15,45,.10)}
.sp-bk-h{font-size:18px;font-weight:800;letter-spacing:-.3px}
.sp-book p{color:var(--muted);font-size:14px;line-height:1.55;margin:6px 0 16px}
.sp-bk-n{display:flex;gap:8px;align-items:center;justify-content:center;font-size:13px;color:var(--muted);margin-top:12px}.sp-bk-n .ms{font-size:18px}
@media(max-width:900px){.sp-hero{grid-template-columns:1fr;gap:0}.sp-book{margin-top:24px;box-shadow:none}.sp-name{font-size:28px}.sp-hero .sp-logo{margin-top:-44px}}
.fu{display:grid;grid-template-columns:1.6fr 1fr;border:1px solid #EAE4EC;border-radius:18px;overflow:hidden;background:#fff;margin-top:14px}
.fu-map{min-height:280px;background:#EEF0EC}
.fu-info{padding:22px;display:flex;flex-direction:column;gap:14px}
.fu-r{display:flex;gap:10px;align-items:flex-start;font-size:14px}.fu-r .ms{font-size:20px;color:var(--muted)}.fu-r b{font-weight:700}
.fu-dir{margin-top:auto}
.fu.nomap{grid-template-columns:1fr}.fu.nomap .fu-map{display:none}
@media(max-width:780px){.fu{grid-template-columns:1fr}.fu-map{min-height:200px;height:200px}}
body.cst{background-attachment:scroll}
body.cst .sp-wrap{position:relative;z-index:1}
body.cst .sp-sec{position:relative;z-index:1;background:var(--bx);backdrop-filter:var(--bxf);-webkit-backdrop-filter:var(--bxf);border:1px solid var(--bxb);border-radius:var(--bxr);padding:24px;color:var(--bxt)}
body.cst .sp-sec h2{color:var(--bxtt)}body.cst .sp-sec p.s{color:var(--bxs)}
body.cst .sp-footer{position:relative;z-index:1;color:var(--bxs);background:transparent;border-top-color:var(--bxl,rgba(255,255,255,.18))}
body.cst .sp-book .sp-bk-n,body.cst .sp-book p{color:var(--bxs)}
.cst-hero{padding-top:150px;position:relative;z-index:1}
body.cst .cst-side .sp-book{margin-top:0!important;top:0!important;position:relative;z-index:2}
</style>
