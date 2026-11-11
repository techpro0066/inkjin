<style>
@font-face{font-family:'PJS';font-weight:400;src:url('{{ asset('new-ui-design-assets/pjs-400.woff2') }}') format('woff2')}
@font-face{font-family:'PJS';font-weight:500;src:url('{{ asset('new-ui-design-assets/pjs-500.woff2') }}') format('woff2')}
@font-face{font-family:'PJS';font-weight:600;src:url('{{ asset('new-ui-design-assets/pjs-600.woff2') }}') format('woff2')}
@font-face{font-family:'PJS';font-weight:700;src:url('{{ asset('new-ui-design-assets/pjs-700.woff2') }}') format('woff2')}
@font-face{font-family:'PJS';font-weight:800;src:url('{{ asset('new-ui-design-assets/pjs-800.woff2') }}') format('woff2')}
@font-face{font-family:'MS';font-display:block;src:url('{{ asset('new-ui-design-assets/material-symbols-outlined.woff2') }}') format('woff2')}
@font-face{font-family:'Space Grotesk';font-weight:700;font-display:swap;src:url('{{ asset('new-ui-design-assets/space-grotesk-700.woff2') }}') format('woff2')}
.flogo b{font-family:'Space Grotesk',system-ui,sans-serif!important;font-weight:700!important;letter-spacing:-.055em!important}
.av.av-initials{display:flex;align-items:center;justify-content:center;font-weight:800;font-size:36px;color:#fff;background:linear-gradient(135deg,var(--a1),var(--a2))}
.tab-panel{display:none}.tab-panel.active{display:block}
.border-error{border-color:#C62828!important}
:root{--a1:#1A1A2E;--a2:#3A3A5C;--acc:#1A1A2E;--accl:#ECEBF3;--ink:#17141A;--muted:#6B6470;--faint:#9A93A0;--line:#ECE6EF;--bg:#FBF9FC;--card:#fff;--green:#1F8A4C;--greenl:#E4F5EA}
*{box-sizing:border-box;margin:0}
html{margin:0;padding:0;min-height:100%;background:var(--bg)}
body{margin:0!important;padding:0!important;min-height:100vh;min-height:100dvh;display:flex;flex-direction:column;font-family:'PJS',system-ui,sans-serif;background:var(--bg);color:var(--ink);font-size:15px;line-height:1.5;-webkit-font-smoothing:antialiased}
button{font:inherit;color:inherit;cursor:pointer;border:0;background:none}
a{color:inherit;text-decoration:none}
.ms{font-family:'MS';font-weight:normal;font-style:normal;font-size:20px;line-height:1;display:inline-block;letter-spacing:normal;text-transform:none;white-space:nowrap;-webkit-font-feature-settings:'liga';font-feature-settings:'liga'}
/* width:100% required: body is a column flex (sticky footer); auto side margins would otherwise shrink-wrap & center children */
.wrap{width:100%;max-width:1120px;margin:0 auto;padding:0 24px;box-sizing:border-box}
/* Same element as .wrap on public pages — do not set max-width:100% or it beats .wrap's 1120px */
#panels{width:100%;min-width:0;box-sizing:border-box}
/* banner — flush to top of viewport (same as template) */
.banner{height:276px;margin:0!important;padding:0!important;width:100%;background-color:var(--a1);background-image:linear-gradient(120deg,var(--a1),var(--a2));background-position:center center;background-size:contain;background-repeat:no-repeat;position:relative;overflow:hidden;transition:background .25s;display:block}
.banner:after{content:'';position:absolute;inset:0;background-image:radial-gradient(rgba(255,255,255,.14) 1.2px,transparent 1.4px);background-size:18px 18px;mask-image:linear-gradient(90deg,transparent,#000 60%)}
/* hero */
.hero{display:grid;grid-template-columns:1fr 360px;gap:40px;align-items:start}
.av{width:124px;height:124px;border-radius:50%;border:5px solid var(--bg);background:#ddd center/cover;margin-top:-62px;position:relative;box-shadow:0 8px 24px rgba(20,10,30,.18)}
h1{font-size:38px;font-weight:800;letter-spacing:-1.2px;line-height:1.1;margin-top:14px}
.handle{color:var(--faint);font-weight:600;font-size:14px;margin-top:4px}
.tagline{font-size:18px;font-weight:600;margin-top:14px;letter-spacing:-.2px}
.bio{color:var(--muted);margin-top:8px;max-width:60ch;line-height:1.65}
.meta{display:flex;flex-direction:column;gap:10px;margin-top:22px}
.mi{display:flex;gap:10px;align-items:flex-start;font-size:14px}.mi .ms{color:var(--faint);font-size:19px;margin-top:1px}
.mi b{font-weight:700}
.stl{display:flex;flex-direction:column;gap:8px;margin-top:8px;max-width:460px}
.stl>div{display:grid;grid-template-columns:1fr auto;gap:2px 12px;border:1px solid var(--line);background:#fff;border-radius:12px;padding:10px 14px}
.stl b{font-size:14px}.stl span{font-size:12.5px;font-weight:700;color:var(--acc);background:var(--accl);border-radius:20px;padding:2px 9px;align-self:center}
.stl small{grid-column:1/-1;color:var(--muted);font-size:13px}
.stnote{display:flex;gap:8px;font-size:12.5px;color:var(--muted);margin-top:8px;line-height:1.45}.stnote .ms{font-size:17px;color:var(--faint)}.stlnote{display:flex;gap:6px;align-items:center;font-size:12.5px;color:var(--muted);margin-top:8px}.stlnote .ms{font-size:16px;color:var(--faint)}.stlnote[hidden]{display:none}.stl span u{text-underline-offset:2px}
.chips{display:flex;flex-wrap:wrap;gap:6px}
.chip{border:1px solid var(--line);background:#fff;border-radius:20px;padding:4px 11px;font-size:12.5px;font-weight:600;color:var(--muted)}
.chip.pri{background:var(--acc);border-color:var(--acc);color:#fff}
.soc{display:flex;gap:8px;margin-top:20px}
.soc a{width:38px;height:38px;border-radius:50%;border:1px solid var(--line);background:#fff;display:flex;align-items:center;justify-content:center;transition:border-color .15s,transform .15s}
.soc a:hover{border-color:var(--acc);transform:translateY(-1px)}
.soc svg{width:17px;height:17px}
/* book card */
.book{background:var(--card);border:1px solid var(--line);border-radius:20px;padding:22px;margin-top:-70px;position:sticky;top:76px;box-shadow:0 18px 50px rgba(30,15,45,.10);z-index:2}
.status{display:inline-flex;align-items:center;gap:7px;font-size:12.5px;font-weight:700;border-radius:20px;padding:4px 11px;background:var(--greenl);color:var(--green)}
.status:before{content:'';width:7px;height:7px;border-radius:50%;background:currentColor}
.status.closed{background:#F1EEF3;color:var(--muted)}
.rates{margin-top:16px;border-top:1px solid var(--line)}
.rate{display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--line);font-size:14px}.rate span{color:var(--muted)}.rate b{font-variant-numeric:tabular-nums}
.dep{display:flex;gap:8px;font-size:12.5px;color:var(--muted);margin-top:12px;line-height:1.45}.dep .ms{font-size:17px;color:var(--faint)}
.bbtn{display:flex;align-items:center;justify-content:center;gap:8px;width:100%;padding:14px;border-radius:12px;font-weight:700;font-size:14.5px;margin-top:10px;transition:filter .15s,background .15s}
.bbtn .ms{font-size:19px}
.bbtn.pri{background:var(--acc);color:#fff;margin-top:18px}.bbtn.pri:hover{filter:brightness(1.15)}
.bbtn.sec{border:1.5px solid var(--ink);background:#fff}.bbtn.sec:hover{background:#F6F3F8}
.next[hidden]{display:none}#gcta .next{margin-top:12px}.next{display:flex;gap:10px;align-items:center;margin-top:16px;padding:12px;border-radius:12px;background:var(--accl);font-size:13px;width:100%;text-align:left}
.next .ms{color:var(--acc)}.next b{display:block;font-size:13px}.next small{color:var(--muted);font-size:12px}
.closedbox{margin-top:16px;border-radius:14px;background:#F6F3F8;padding:16px;text-align:center}
.closedbox .ms{font-size:28px;color:var(--muted)}
.closedbox b{display:block;margin-top:6px}.closedbox p{color:var(--muted);font-size:13px;margin-top:4px}
/* tabs */
.tabbar{position:sticky;top:0;z-index:5;background:rgba(251,249,252,.9);backdrop-filter:blur(10px);-webkit-backdrop-filter:blur(10px);border-bottom:1px solid var(--line);margin-top:44px;width:100%}
.tabs{display:flex;justify-content:flex-start;align-items:stretch;gap:6px;overflow-x:auto;scrollbar-width:none;width:100%;margin:0;padding:0;text-align:left}.tabs::-webkit-scrollbar{display:none}
.tab{padding:18px 6px 16px;margin:0 18px 0 0;font-weight:600;font-size:14.5px;color:var(--muted);border-bottom:2.5px solid transparent;white-space:nowrap;display:flex;gap:7px;align-items:center;flex:0 0 auto}
.tab:hover{color:var(--ink)}
.tab[aria-selected=true]{color:var(--ink);border-bottom-color:var(--acc)}
.tab .n{font-size:11.5px;font-weight:700;background:#F1EEF3;color:var(--muted);border-radius:10px;padding:1px 7px}
.tab[aria-selected=true] .n{background:var(--acc);color:#fff}
.tab:focus-visible,.bbtn:focus-visible,.soc a:focus-visible,.pc:focus-visible{outline:2px solid var(--acc);outline-offset:3px}
.panel{padding:34px 0 60px;min-width:0}
.ph{display:flex;justify-content:space-between;align-items:flex-end;gap:16px;margin-bottom:20px}
.ph h2{font-size:22px;font-weight:800;letter-spacing:-.5px}.ph p{color:var(--muted);font-size:14px;margin-top:2px}
/* designs */
.dgrid{display:grid;grid-template-columns:repeat(4,1fr);gap:18px;width:100%;min-width:0}
.dc{background:#fff;border:1px solid var(--line);border-radius:18px;overflow:hidden;display:flex;flex-direction:column;width:100%;min-width:0;max-width:none;transition:box-shadow .2s,transform .2s}
.dc:hover{box-shadow:0 14px 34px rgba(30,15,45,.10);transform:translateY(-2px)}
.dimg{aspect-ratio:1;background:#F3F0F5 center/cover no-repeat;position:relative;border-bottom:1px solid var(--line)}
.dimg .pill{position:absolute;top:12px;left:12px}
.pill{display:inline-flex;align-items:center;gap:5px;font-size:11.5px;font-weight:700;border-radius:20px;padding:3px 10px;background:#fff;box-shadow:0 1px 4px rgba(0,0,0,.08)}
.pill.g{color:var(--green)}.pill.k{color:var(--muted)}.pill.r{color:#C62828}
.dc.off .dimg{filter:grayscale(1);opacity:.6}
.db{padding:14px 16px 16px;display:flex;flex-direction:column;flex:1}
.db h3{font-size:15.5px;font-weight:700;letter-spacing:-.2px}
.tags{display:flex;flex-wrap:wrap;gap:5px;margin-top:8px}
.tg{font-size:11.5px;font-weight:600;border-radius:6px;padding:2px 8px;background:#F4F1F6;color:var(--muted)}
.tg.st{background:var(--accl);color:var(--acc)}
.price{font-weight:800;font-size:16px;margin-top:12px;font-variant-numeric:tabular-nums}
.price small{font-weight:500;color:var(--muted);font-size:12px;margin-left:4px}
.lim{margin-top:10px;font-size:12px;color:var(--muted)}.lim b{color:var(--ink)}
.bar{height:5px;border-radius:4px;background:#EFEAF2;margin-top:5px;overflow:hidden}.bar i{display:block;height:100%;background:var(--acc);border-radius:4px}
.db .bbtn{margin-top:auto;padding:11px;font-size:13.5px}
.db .sp{flex:1;min-height:14px}
.bbtn.dis{background:#F1EEF3;color:var(--faint);cursor:default}
/* portfolio */
.pgrid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px}
.pc{position:relative;aspect-ratio:1;border-radius:16px;overflow:hidden;background:#EEE center/cover;display:block;width:100%;text-align:left}
.pc .ov{position:absolute;inset:0;background:linear-gradient(180deg,transparent 45%,rgba(10,5,15,.78));opacity:0;transition:opacity .2s;display:flex;flex-direction:column;justify-content:flex-end;padding:14px;color:#fff}
.pc:hover .ov,.pc:focus-visible .ov{opacity:1}
.pc .ov b{font-size:14.5px}.pc .ov small{font-size:12px;opacity:.85}
.pc .req{margin-top:10px;background:#fff;color:var(--ink);border-radius:10px;padding:8px 10px;font-size:12.5px;font-weight:700;display:flex;gap:6px;align-items:center;justify-content:center}
.pc .req .ms{font-size:16px}
/* guest */
.gl{display:flex;flex-direction:column;gap:12px}
.gs{display:flex;gap:18px;align-items:center;background:#fff;border:1px solid var(--line);border-radius:18px;padding:18px 20px}
.gd{width:64px;flex-shrink:0;text-align:center;border-radius:14px;background:var(--accl);color:var(--acc);padding:8px 0}
.gd small{display:block;font-size:11px;font-weight:700;letter-spacing:.6px;text-transform:uppercase}.gd b{font-size:24px;font-weight:800;line-height:1.1}
.gi{flex:1;min-width:0}.gi h3{font-size:16px;font-weight:700}.gi p{color:var(--muted);font-size:13.5px;margin-top:2px}
.gi .left{color:var(--green);font-weight:700;font-size:12.5px;margin-top:4px}
.gs .act{display:flex;flex-direction:column;align-items:flex-end;gap:8px}
.gs .bbtn{width:auto;margin:0;padding:10px 18px;font-size:13.5px}
.pill.pl{background:var(--accl);color:var(--acc);box-shadow:none}
.pill.av2{background:var(--greenl);color:var(--green);box-shadow:none}
/* policies */
.pg{background:#fff;border:1px solid var(--line);border-radius:18px;overflow:hidden}
.pr{display:grid;grid-template-columns:230px 1fr;gap:20px;padding:16px 20px;border-bottom:1px solid var(--line);align-items:baseline}.pr:last-child{border-bottom:0}
.pr b{font-size:14.5px;font-weight:700}.pr p{color:var(--muted);font-size:14.5px;line-height:1.6}
.inc{background:#fff;border:1px solid var(--line);border-radius:18px;padding:20px;margin-bottom:14px}
.inc h3{font-size:15px;font-weight:700}.inc p.s{color:var(--muted);font-size:13.5px;margin-top:2px}
.incl{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-top:14px}
.incl div{display:flex;gap:8px;align-items:center;font-size:14px;font-weight:600;background:#FAF8FB;border-radius:12px;padding:12px}
.incl .ms{color:var(--green);font-size:19px;font-variation-settings:'FILL' 1}
.pnote{background:#fff;border:1px solid var(--line);border-radius:18px;padding:18px 20px;margin-top:14px;font-size:14px;color:var(--muted)}
/* faq */
.faq{background:#fff;border:1px solid var(--line);border-radius:18px;padding:4px 22px;max-width:820px}
.fq{border-bottom:1px solid var(--line)}.fq:last-child{border-bottom:0}
.fq button{width:100%;display:flex;justify-content:space-between;align-items:center;gap:16px;padding:17px 0;text-align:left;font-weight:700;font-size:15px}
.fq button .ms{color:var(--ink);font-size:20px}
.fq .fa{padding:0 40px 18px 0;color:var(--muted);line-height:1.65}
/* footer — stick to bottom when page content is short */
footer{border-top:1px solid var(--line);padding:26px 0 40px;margin-top:auto;width:100%;align-self:stretch;background:var(--bg)}
/* custom appearance: let page bg / design show through (match studio .sp-footer) */
body.cst footer{background:transparent;border-top-color:var(--bxl,rgba(255,255,255,.18))}
/* No designs/portfolio/etc — don't show an empty frosted panels box in Custom look */
body.cst #panels.is-empty,#panels.is-empty{display:none!important;margin:0!important;padding:0!important;border:0!important;background:none!important;backdrop-filter:none!important;-webkit-backdrop-filter:none!important}
footer .wrap{display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap}
.flogo{display:flex;align-items:center;gap:10px}.flogo b{font-weight:800;font-size:19px;letter-spacing:-.6px}.flogo small{font-size:9px;letter-spacing:1.2px;color:var(--muted);line-height:1.2;text-transform:uppercase}
footer nav{display:flex;gap:18px;font-size:13px;color:var(--muted)}
/* modal */
.mb{position:fixed;inset:0;background:rgba(15,8,22,.72);z-index:50;display:flex;align-items:center;justify-content:center;padding:20px}
.mb[hidden]{display:none}
.lb{background:#fff;border-radius:22px;overflow:hidden;display:grid;grid-template-columns:minmax(0,1.25fr) minmax(0,1fr);max-width:980px;width:100%;max-height:calc(100vh - 40px);position:relative}
.lbimg{background:#111 center/contain no-repeat;min-height:520px}
.lbb{padding:28px;display:flex;flex-direction:column;overflow:auto}
.lbb h2{font-size:24px;font-weight:800;letter-spacing:-.5px;padding-right:30px}
.facts{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-top:18px}
.facts small{display:block;font-size:11px;font-weight:700;letter-spacing:.6px;text-transform:uppercase;color:var(--faint)}.facts b{font-size:14px}
.lbb .tags{margin-top:18px}
.lbb .bbtn{margin-top:auto}
.lbnav{display:flex;justify-content:space-between;align-items:center;margin-top:14px;font-size:13px;color:var(--muted)}
.lbnav button{width:40px;height:40px;border-radius:50%;border:1px solid var(--line);display:flex;align-items:center;justify-content:center}
.lbnav button:hover{border-color:var(--ink)}
.x{position:absolute;top:14px;right:14px;width:36px;height:36px;border-radius:50%;background:#fff;display:flex;align-items:center;justify-content:center;box-shadow:0 2px 8px rgba(0,0,0,.15);z-index:2}
.nm{background:#fff;border-radius:22px;max-width:440px;width:100%;padding:28px;position:relative}
.nm h2{font-size:22px;font-weight:800;letter-spacing:-.4px}.nm p{color:var(--muted);margin-top:6px;font-size:14px;line-height:1.55}
.nm label{display:block;font-size:13px;font-weight:700;margin-top:16px}
.nm input{width:100%;border:1px solid var(--line);border-radius:12px;padding:12px 14px;font:inherit;margin-top:6px}
.nm input:focus{outline:2px solid var(--acc);border-color:transparent}
/* mobile cta */
.mcta{display:none}
/* toast */
.toast{position:fixed;top:20px;left:50%;transform:translateX(-50%);background:#17141A;color:#fff;padding:12px 18px;border-radius:12px;font-weight:600;font-size:14px;z-index:80;display:flex;gap:10px;align-items:center;box-shadow:0 10px 30px rgba(0,0,0,.25)}
.toast .ms{color:#3DD68C}
/* mockup panel */
.mk{position:fixed;left:16px;bottom:16px;z-index:60;font-size:12.5px;color:#5B2A94}
.mkb{background:#3E007C;color:#fff;border-radius:20px;padding:8px 14px;font-weight:700;display:flex;gap:6px;align-items:center;box-shadow:0 6px 20px rgba(62,0,124,.35)}
.mkb .ms{font-size:17px}
.mkp{position:absolute;bottom:46px;left:0;width:300px;background:#FBF5FF;border:1.5px dashed #B99BE0;border-radius:14px;padding:12px 14px;box-shadow:0 12px 36px rgba(40,10,60,.18)}
.mkp[hidden]{display:none}
.mkp .r{margin-top:10px}.mkp .r:first-child{margin-top:0}.mkp .r>div{font-weight:700;margin-bottom:5px}
.mkp .o{display:flex;flex-wrap:wrap;gap:5px}
.mkp .o button{padding:4px 10px;border-radius:14px;font-weight:600;color:#5B2A94;border:1px solid #D9C4F2;background:#fff;font-size:12px}
.mkp .o button.on{background:#3E007C;color:#fff;border-color:#3E007C}
.mkp .sws button{width:26px;height:26px;border-radius:50%;padding:0;border:2px solid #fff;box-shadow:0 0 0 1px #D9C4F2}
.mkp .sws button.on{box-shadow:0 0 0 2px #3E007C}
.custc{display:flex;gap:8px;align-items:center;margin-top:8px;font-weight:600}.custc input{width:30px;height:26px;border:1px solid #D9C4F2;border-radius:6px;padding:1px;background:#fff;cursor:pointer}
[hidden]{display:none!important}
body:has(.mb:not([hidden])) .mk{display:none}
/* responsive */
@media(max-width:1000px){.dgrid{grid-template-columns:repeat(3,1fr)}.pgrid{grid-template-columns:repeat(3,1fr)}.hero{grid-template-columns:1fr 320px;gap:28px}}
@media(max-width:780px){
 .wrap{padding:0 16px}.banner{height:150px}
 .hero{grid-template-columns:1fr;gap:0}
 .av{width:96px;height:96px;margin-top:-48px}
 h1{font-size:30px}.tagline{font-size:16px}
 .book{position:static;margin-top:24px;box-shadow:none}
 .tabbar{margin-top:28px}
 .dgrid{grid-template-columns:1fr 1fr;gap:12px}.pgrid{grid-template-columns:1fr 1fr;gap:8px}
 .pc .ov{opacity:1;background:linear-gradient(180deg,transparent 60%,rgba(10,5,15,.7))}.pc .req{display:none}
 .pr{grid-template-columns:1fr;gap:4px}.incl{grid-template-columns:1fr 1fr}
 .gs{flex-wrap:wrap}.gs .act{flex-direction:row;width:100%;justify-content:space-between;align-items:center}
 .lb{grid-template-columns:1fr;max-height:calc(100vh - 24px);overflow:auto}.lbimg{min-height:0;aspect-ratio:4/5}.lbb{padding:20px}
 .mb{padding:12px}
 .mcta{display:flex;gap:8px;position:fixed;left:0;right:0;bottom:0;z-index:20;transform:translateY(110%);transition:transform .25s ease;background:rgba(255,255,255,.95);backdrop-filter:blur(10px);border-top:1px solid var(--line);padding:10px 16px calc(10px + env(safe-area-inset-bottom))}
 .mcta .bbtn{margin:0;padding:13px 10px;font-size:14px}
 .mcta.show{transform:none}
 body{padding-bottom:78px}
 .mk{bottom:84px;left:12px}.mkp{max-height:calc(100vh - 160px);overflow:auto;width:280px}.mkb{padding:7px 12px}
 footer .wrap{flex-direction:column;align-items:flex-start}
}
@media(max-width:420px){.incl{grid-template-columns:1fr}.db{padding:12px}.db h3{font-size:14px}.db .price{font-size:14px}.price small{display:block;margin:2px 0 0}.db .bbtn{font-size:12.5px;padding:10px 6px}}
@media(max-width:340px){.dgrid{grid-template-columns:1fr}}
@media(prefers-reduced-motion:reduce){*{transition:none!important}}
body{position:relative}
.cst-svc{margin-top:10px;font-size:14px;color:var(--bxs)}.cst-svc b{color:var(--bxt)}
</style>

