<!DOCTYPE html>
<html lang="en">
<head>
  @include('layouts.partials.google-analytics')
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="color-scheme" content="only light">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  @php
    $seoTitle = trim($__env->yieldContent('title')) ?: 'Artist onboarding — Bookpay by Inkjin';
    $seoDescription = trim($__env->yieldContent('meta_description')) ?: 'Set up your Bookpay artist profile, studio, payments, and calendar.';
  @endphp
  <title>{{ $seoTitle }}</title>
  <meta name="description" content="{{ $seoDescription }}">
  <meta name="robots" content="@yield('robots', 'noindex, follow')">
  <link rel="icon" href="{{ asset('design/images/icons/favicon.png') }}">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&display=block" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
  <style>:root{--bg:#FFF6FF;--card:#fff;--line:#E8DFEA;--tint:#FAF0FB;--tintline:#F1E7F3;--ink:#1A1A1A;
--muted:#6F6874;--faint:#9A929E;--pri:#3E007C;--pril:#F3E8FF;--chip:#F0EDF2;--green:#00A650;--greenl:#E6F8EE;
--amber:#B86E00;--amberl:#FFF3DE;--red:#C62828;--redl:#FDECEC;--blue:#1E5BD8;--bluel:#E8F0FF}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Plus Jakarta Sans',system-ui,sans-serif;background:var(--bg);color:var(--ink);font-size:14px;width:100%;display:flex;min-height:100vh;height:100vh;overflow:hidden}
.ms{font-family:'Material Symbols Outlined';font-weight:normal;font-style:normal;font-size:20px;line-height:1;letter-spacing:normal;
 text-transform:none;display:inline-block;white-space:nowrap;direction:ltr;-webkit-font-smoothing:antialiased;
 font-variation-settings:'FILL' 0,'wght' 400,'GRAD' 0,'opsz' 24;vertical-align:middle}
aside{width:232px;background:#1A1A1A;color:#DEDEDE;padding:22px 14px 18px;display:flex;flex-direction:column;flex-shrink:0;height:100vh;min-height:0;overflow-y:auto;overscroll-behavior:contain;position:sticky;top:0;align-self:flex-start}
.logo{font-weight:800;font-size:25px;color:#fff;letter-spacing:-1px;padding-left:6px}
.tag{font-size:8.5px;letter-spacing:1.4px;color:#8C8C8C;padding-left:6px;margin-top:2px;line-height:1.35}
nav{margin-top:26px;display:flex;flex-direction:column;gap:3px}
nav a{display:flex;align-items:center;gap:12px;padding:10px 12px;border-radius:12px;font-weight:500;font-size:14.5px;color:#DEDEDE;text-decoration:none}
nav a .ms{font-size:21px;color:#DEDEDE}
nav a.on{background:#fff;color:var(--pri);font-weight:600}
nav a.on .ms{color:var(--pri)}
.badge{background:#fff;color:#1A1A1A;border-radius:6px;font-size:11px;padding:2px 7px;font-weight:600}
.soon{margin-left:auto;border:1px solid #555;color:#BDBDBD;border-radius:6px;font-size:10px;padding:1px 6px;font-weight:600;letter-spacing:.3px}
nav a{padding:9px 12px !important}
.cnt{margin-left:auto;background:#3A3A3A;color:#fff;border-radius:10px;font-size:11px;padding:1px 8px;font-weight:600}
nav a.on .soon{margin-left:auto;border:1px solid #555;color:#BDBDBD;border-radius:6px;font-size:10px;padding:1px 6px;font-weight:600;letter-spacing:.3px}
nav a{padding:9px 12px !important}
.cnt{background:var(--pri)}
.sep{height:1px;background:#333;margin:10px 4px}
.foot{margin-top:auto}
.user{display:flex;gap:10px;align-items:center;padding:14px 8px 0;border-top:1px solid #333;margin-top:10px}
.av{width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#2b6cb0,#e07a3f);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:13px}
.user b{font-size:13px;color:#fff;display:block}.user span{font-size:11.5px;color:#9a9a9a}
main{flex:1;padding:36px 48px 56px;min-width:0;height:100vh;overflow-y:auto;overscroll-behavior:contain}
.wrap{max-width:1112px}
.head{display:flex;align-items:flex-start;justify-content:space-between;gap:24px}
h1{font-size:30px;font-weight:800;letter-spacing:-.6px}
.sub{color:var(--muted);font-size:14.5px;margin-top:4px}
.btn{display:inline-flex;align-items:center;gap:8px;background:var(--ink);color:#fff;border-radius:10px;padding:10px 16px;font-weight:600;font-size:13.5px;white-space:nowrap}
.btn .ms{font-size:18px}
.btn.ghost{background:#fff;color:var(--ink);border:1px solid var(--line)}
.btn.sm{padding:6px 11px;font-size:12.5px;border-radius:8px}
.btn.pri{background:var(--pri)}
.btns{display:flex;gap:10px}
.tabs{display:flex;gap:28px;border-bottom:1px solid var(--line);margin:26px 0 24px}
.tabs div{padding:0 2px 12px;font-weight:600;color:var(--muted);font-size:14.5px;display:flex;gap:7px;align-items:center}
.tabs div.on{color:var(--ink);box-shadow:inset 0 -2.5px 0 var(--ink)}
.tabs .n{background:var(--chip);color:var(--muted);border-radius:10px;font-size:11px;padding:1px 7px}
.tabs .n.zero{background:#FFF3DE;color:#B86E00}
.tabs div.on .n{background:var(--ink);color:#fff}
.tabs div.on .n.zero{background:#FFF3DE;color:#B86E00}
.card{background:var(--card);border:1px solid var(--line);border-radius:16px}
.pad{padding:20px 22px}
.tint{background:var(--tint);border:1px solid var(--tintline);border-radius:16px}
.ch{display:flex;align-items:center;justify-content:space-between;padding:18px 22px;border-bottom:1px solid var(--line)}
.ch h3,h3{font-size:16px;font-weight:700}
.ch .l{font-weight:600;font-size:13px;display:flex;gap:4px;align-items:center}
.muted{color:var(--muted)}.faint{color:var(--faint)}
.pill{display:inline-flex;align-items:center;gap:5px;border-radius:20px;font-size:11.5px;font-weight:600;padding:3px 9px;white-space:nowrap}
.pill:before{content:'';width:5px;height:5px;border-radius:50%;background:currentColor}
.pill.nd:before{display:none}
.g{background:var(--greenl);color:var(--green)}.p{background:var(--pril);color:#7A1FC4}.a{background:var(--amberl);color:var(--amber)}
.r{background:var(--redl);color:var(--red)}.b{background:var(--bluel);color:var(--blue)}.k{background:var(--chip);color:var(--muted)}
.chips{display:flex;gap:8px;flex-wrap:wrap}
.chip{border:1px solid var(--line);background:#fff;border-radius:20px;padding:6px 13px;font-size:12.5px;font-weight:600;color:var(--muted)}
.chip.on{background:var(--pri);border-color:var(--pri);color:#fff}
.chip b{font-weight:700;margin-left:4px;opacity:.7}
.lbl{font-size:11px;letter-spacing:.8px;font-weight:700;color:var(--muted);text-transform:uppercase}
.fl{font-size:13px;font-weight:700;margin-bottom:6px;display:block}
.help{font-size:12px;color:var(--faint);margin-top:6px}
.in{background:#fff;border:1px solid var(--line);border-radius:10px;padding:10px 13px;font-size:13.5px;display:flex;align-items:center;gap:8px;min-height:40px}
.in.ph{color:var(--faint)}
input.in{font:inherit;font-size:13.5px;color:var(--ink);width:100%;outline:none}
input.in:focus,.in:focus-within{border-color:#3E007C;box-shadow:0 0 0 3px #F3E8FF}
.in .ms{font-size:18px;color:var(--faint)}
.tog{width:38px;height:22px;border-radius:12px;background:#D9D2DD;position:relative;flex-shrink:0}
.tog:after{content:'';position:absolute;width:16px;height:16px;border-radius:50%;background:#fff;top:3px;left:3px}
.tog.on{background:var(--pri)}.tog.on:after{left:19px}
table{width:100%;border-collapse:collapse}
th{font-size:11px;letter-spacing:.8px;color:var(--muted);text-align:left;font-weight:700;padding:11px 16px;background:#FBF8FC;border-bottom:1px solid var(--line)}
td{padding:14px 16px;border-bottom:1px solid #F3EEF4;font-size:13.5px;vertical-align:middle}
tr:last-child td{border-bottom:0}
.grid{display:grid;gap:16px}
.ic{width:38px;height:38px;border-radius:10px;background:var(--chip);display:flex;align-items:center;justify-content:center;flex-shrink:0}
.ic .ms{font-size:20px}
.ini{width:34px;height:34px;border-radius:50%;background:var(--ink);color:#fff;font-size:12px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.row{display:flex;align-items:center;gap:12px}
.new{background:#FFE8A3;color:#6B4E00;font-size:10px;font-weight:800;letter-spacing:.6px;border-radius:5px;padding:2px 6px;vertical-align:middle}
.note{border:1.5px dashed #B99BE0;background:#FBF5FF;border-radius:12px;padding:10px 14px;font-size:12.5px;color:#5B2A94;display:flex;gap:8px;align-items:center}
.note .ms{font-size:18px}

.topline{display:flex;align-items:center;justify-content:space-between}
.burger{display:none;background:none;border:0;color:#fff;padding:6px;border-radius:8px;cursor:pointer}
.burger .ms{font-size:26px}
main{min-width:0}
.tabs{overflow-x:auto;scrollbar-width:none}.tabs::-webkit-scrollbar{display:none}.tabs div{flex-shrink:0;white-space:nowrap}
.head{flex-wrap:wrap}.btns{flex-wrap:wrap}
@media (max-width:1180px){
 .grid[style*="repeat(4"],.grid[style*="repeat(5"],.grid[style*="repeat(6"]{grid-template-columns:repeat(2,minmax(0,1fr))!important}
 .grid[style*="1.65fr"],.grid[style*="1.7fr"],.grid[style*="1fr 340px"],.grid[style*="1fr 360px"],.grid[style*="1fr 320px"],.grid[style*="1fr 330px"],.grid[style*="1.2fr 1fr 1fr"],.grid[style*="1.3fr 1fr"]{grid-template-columns:minmax(0,1fr)!important}
 .grid[style*="1fr 340px"]>div[style*="sticky"],.grid[style*="1fr 340px"]>div:last-child{position:static!important}
}
@media (max-width:900px){
 body{flex-direction:column;height:auto;min-height:100vh;overflow:auto}
 aside{width:100%;height:auto;min-height:0;padding:12px 16px;position:sticky;top:0;z-index:20;align-self:stretch;overflow:visible}
 aside .tag{display:none}
 .burger{display:inline-flex}
 aside nav,aside .foot{display:none}
 body.navopen aside nav{display:flex}
 body.navopen aside .foot{display:block;margin-top:8px}
 body.navopen aside{max-height:100vh;overflow-y:auto}
 aside nav{margin-top:12px}
 main{padding:22px 16px 48px;height:auto;overflow:visible}
 h1{font-size:25px}
 .grid{grid-template-columns:minmax(0,1fr)!important}
 .grid[style*="repeat(4"],.grid[style*="repeat(5"],.grid[style*="repeat(6"],.grid[style*="repeat(3"]{grid-template-columns:repeat(2,minmax(0,1fr))!important}
 .card:has(table){overflow-x:auto!important}
 table{min-width:720px}
 .card[style*="grid-template-columns:320px"]{grid-template-columns:1fr!important;height:auto!important}
 .card[style*="grid-template-columns:320px"]>div:first-child{border-right:0!important;border-bottom:1px solid var(--line)}
 .tint .row,.card.row,.row[style*="justify-content:space-between"]{flex-wrap:wrap}
 .tint .row>div[style*="width:"]{width:auto!important;flex:1 1 150px}
 .tint .row>div[style*="flex:1"]{flex:1 1 100%!important}
 .row>div[style*="flex:1;border"]{flex:1 1 100%!important}
 .row:has(>div[style*="flex:1;border"]){flex-wrap:wrap}
 .ov{padding:12px!important}
 .modal,.modal.w,.modal.xl{width:100%!important}
 .mf{flex-wrap:wrap}
 .drawer{width:100%!important}
 div[style^="position:absolute;top"]{display:none}
 .menu{position:fixed!important;left:12px!important;right:12px!important;top:auto!important;bottom:12px!important;width:auto!important}
 .day{min-height:74px!important;padding:4px!important}
 .ev{font-size:10px!important;padding:2px 4px!important}
 .dow{padding:8px 4px!important;font-size:10px!important}
}
@media (max-width:560px){
 .grid[style*="repeat(4"],.grid[style*="repeat(5"],.grid[style*="repeat(6"],.grid[style*="repeat(3"]{grid-template-columns:minmax(0,1fr)!important}
 .grid[style*="repeat(4"]:has(.card.pad){grid-template-columns:repeat(2,minmax(0,1fr))!important}
 .ev b{display:none}
 .day .ev{font-size:9.5px!important}
 .btns .in{max-width:100%}
}

@media (max-width:900px){
 table{min-width:0!important}
 .card:has(table){overflow:visible!important;background:none!important;border:0!important}
 table,tbody,tr,td{display:block;width:100%}
 tr:has(th){display:none}
 tr{position:relative;background:#fff;border:1px solid var(--line);border-radius:14px;padding:12px 14px;margin-bottom:10px}
 td{border:0!important;padding:5px 0!important;display:flex!important;justify-content:space-between;align-items:center;gap:12px;font-size:13.5px;text-align:right}
 td>*{text-align:right}
 td::before{content:attr(data-label);font-size:10.5px;font-weight:700;letter-spacing:.7px;color:var(--muted);text-align:left;flex-shrink:0}
 td:first-child{padding:0 44px 8px 0!important;margin-bottom:4px;border-bottom:1px solid #F3EEF4!important;justify-content:flex-start;text-align:left}
 td:first-child::before{display:none}
 td:first-child>*{text-align:left}
 td:last-child:not([data-label]),td[data-label=""],td[data-label="ACTIONS"]{position:absolute;top:10px;right:10px;width:auto;padding:0!important}
 td:last-child:not([data-label])::before,td[data-label="ACTIONS"]::before{display:none}.row:has(>.tog:last-child){flex-wrap:nowrap!important}.row:has(>.tog:last-child)>:nth-last-child(2){flex:1 1 auto;min-width:0}.row>.tog{flex:none}td:last-child:not([data-label]):has(.btn+.btn),td[data-label=""]:has(.btn+.btn),td[data-label="ACTIONS"]:has(.btn+.btn){position:static!important;padding:10px 0 0!important;margin-top:6px;border-top:1px solid #F3EEF4!important;justify-content:stretch}td:last-child:not([data-label]):has(.btn+.btn) .row,td[data-label=""]:has(.btn+.btn) .row,td[data-label="ACTIONS"]:has(.btn+.btn) .row{width:100%}td:last-child:not([data-label]):has(.btn+.btn) .btn,td[data-label=""]:has(.btn+.btn) .btn,td[data-label="ACTIONS"]:has(.btn+.btn) .btn{flex:1;justify-content:center}tr:has(>td:last-child:not([data-label]) .btn+.btn)>td:first-child,tr:has(>td[data-label="ACTIONS"] .btn+.btn)>td:first-child{padding-right:0!important}
 td[data-label="ACTIONS"]{gap:10px}
 .tint .row>div[style*="width:150px"],.tint .row>div[style*="width:170px"]{display:none}
 .tint .chips{flex-wrap:nowrap;overflow-x:auto;scrollbar-width:none;margin-right:-4px}
 .tint .chips::-webkit-scrollbar{display:none}
 .tint .chips .chip{flex-shrink:0}
 .tint>.row[style*="justify-content:space-between"]{flex-direction:column;align-items:stretch;gap:10px}
 .tint>.row[style*="justify-content:space-between"]>.row{align-self:flex-start}
 .card.row{align-items:flex-start}
 .card.row>.btns{width:100%;justify-content:flex-start;padding-left:54px}
 .card.row>span.faint[style*="width:60px"]{width:auto!important;text-align:left!important;order:3;padding-left:54px;flex-basis:100%}
 .card.row .row{flex-wrap:wrap}
 .card.row>.btns{order:4}
 td .v{display:flex;flex-direction:column;align-items:flex-end;gap:2px}
 td .v>*{text-align:right}
 .card.row{position:relative}
}

@media (max-width:900px){
 .card[data-req]{display:block!important;padding:14px 16px!important;position:relative}
 .card[data-req] .rini{display:none}
 .card[data-req]>div:nth-child(2) .row{padding-right:56px;gap:6px!important}
 .card[data-req] .rdate{position:absolute;top:15px;right:16px;padding:0!important;flex-basis:auto!important;width:auto!important}
 .card[data-req] .rbtns{padding-left:0!important;margin-top:12px;display:flex;gap:8px;width:100%}
 .card[data-req] .rbtns .btn{flex:1;justify-content:center;padding:9px 12px}
 .rf .lbl{display:none}
 .rf>.row:last-child{flex-direction:column;align-items:stretch!important;gap:8px!important;margin-top:10px!important}
 .rf>.row:last-child>div{min-width:0}
 .rf .chips{flex-wrap:nowrap;overflow-x:auto;scrollbar-width:none}
 .rf .chips .chip{flex-shrink:0}
}

@media (max-width:900px){
 .ov{z-index:40!important}
 .drawer{z-index:41!important}
 .modal .grid[style*="column-gap:20px"]{grid-template-columns:repeat(2,minmax(0,1fr))!important}
 .modal .row:has(>.row>.tog){flex-wrap:wrap;gap:12px 18px!important}
 .modal div[style*="aspect-ratio:4/5"]{max-width:200px}
 .modal .row:has(>.btn[style*="margin-left:auto"]){flex-wrap:wrap}
 .modal .row>.btn[style*="margin-left:auto"]{margin-left:0!important}
 .modal .grid[style*="repeat(5,1fr);gap:8px"]{grid-template-columns:repeat(3,minmax(0,1fr))!important}
 .modal .grid[style*="repeat(5,1fr);gap:8px"] .sect{padding:10px 4px!important}
 .modal .faint[style*="margin:6px 0 12px 66px"]{margin-left:0!important}
 .mf>.btn:last-child{flex:1 1 auto;justify-content:center}
}

.ov{padding:24px!important;align-items:center!important;overflow:hidden!important}
.modal{display:flex!important;flex-direction:column;max-height:calc(100vh - 48px);max-height:calc(100dvh - 48px)}
.modal>.mh,.modal>.mf{flex-shrink:0}
.modal>.mh{padding-bottom:14px!important;border-bottom:1px solid #F0EAF2}
.modal>.mb{flex:1 1 auto;min-height:0;overflow-y:auto;overscroll-behavior:contain}
.drawer>div[style*="flex:1"]{overflow-y:auto!important;min-height:0;overscroll-behavior:contain}
@media (max-width:900px){.modal{max-height:calc(100vh - 24px);max-height:calc(100dvh - 24px)}
 .modal .mb .row:has(>.btn){flex-wrap:wrap!important}
 .modal .mb .row>.btn[style*="margin-left:auto"]{margin-left:0!important}
 .modal .mb .row:has(>.btn)>div:not(.ini){flex:1 1 160px;min-width:0}}
.ch .chpo{flex-shrink:0;white-space:nowrap}@media (max-width:900px){.ch:has(.chpo){flex-wrap:wrap;gap:10px}}
.popend{display:flex;gap:12px;align-items:center;background:#FFF7E6;border:1px solid #F3DDB0;border-radius:14px;padding:12px 16px;margin-top:12px;color:#8A5A00}.popend[hidden]{display:none}.popend .ms{color:#B7791F}

.po{display:flex;gap:12px;align-items:flex-start;background:#fff;border:1px solid var(--line);border-radius:14px;padding:16px 18px;cursor:pointer;margin-bottom:10px}
.po.sel{border:2px solid #3E007C;padding:15px 17px;background:#FDFAFF}
.po input{accent-color:#3E007C;width:18px;height:18px;margin-top:2px;flex-shrink:0}
.po b{font-size:14.5px}.po .d{font-size:13px;color:var(--muted);margin-top:3px;line-height:1.5}
.splitbox{background:#FBF7FF;border:1px solid #E4D6F5;border-radius:14px;padding:18px;margin:4px 0 10px}
.stepn{width:26px;height:26px;border-radius:50%;background:#1A1A1A;color:#fff;font-size:12px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.pct{display:flex;align-items:center;gap:8px}.pct input{width:80px;text-align:center}
.lockbar{display:flex;gap:8px;align-items:center;background:#F0EDF2;color:var(--muted);border-radius:10px;padding:9px 12px;font-size:12.5px}
.vbar{display:flex;flex-wrap:wrap;gap:8px;align-items:center;border:1.5px dashed #B99BE0;background:#FBF5FF;border-radius:12px;padding:8px 12px;font-size:12.5px;color:#5B2A94;margin-bottom:18px}
.vbar a{padding:4px 10px;border-radius:14px;font-weight:600;color:#5B2A94;text-decoration:none;border:1px solid #D9C4F2;background:#fff}
.vbar a.on{background:#3E007C;color:#fff;border-color:#3E007C}
.tgrid{display:grid;grid-template-columns:1fr 1fr;gap:10px}
.tgrid .po{margin:0}
.picked{background:#fff;border:2px solid #3E007C;border-radius:14px;padding:16px 18px}
.logo2{width:48px;height:48px;border-radius:12px;background:#EDD9FF;color:#3E007C;font-weight:800;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.kv{display:flex;justify-content:space-between;gap:16px;padding:6px 0;font-size:13.5px}.kv span:first-child{color:var(--muted)}
.results{background:#fff;border:1px solid var(--line);border-radius:12px;padding:6px;margin-top:8px}
.result{display:flex;gap:12px;align-items:center;padding:9px 10px;border-radius:9px;cursor:pointer;text-decoration:none;color:inherit}
.result:hover,.result.hl{background:#F8F0FC}
@media (max-width:700px){.tgrid{grid-template-columns:1fr}}
.bufseg div.on{background:#3E007C!important;color:#fff;border-color:#3E007C!important}
@media (max-width:900px){.grid.bufseg{grid-template-columns:repeat(4,1fr)!important}}
.obfoot{border-top:1px solid var(--line);padding:14px 0;display:flex;justify-content:space-between;align-items:center;margin-top:28px;z-index:5}
.wrap{max-width:920px}

.help-q{display:inline-flex;align-items:center;justify-content:center;width:26px;height:26px;margin-left:8px;border-radius:50%;color:#9A929E;vertical-align:middle;text-decoration:none;position:relative;top:-2px}.help-q .ms{font-size:22px}.help-q:hover{color:#3E007C;background:#F3E8FF}.help-q:focus-visible{outline:2px solid #3E007C;outline-offset:2px}

a.logo:not(:has(b)),.logo>b{font-family:'Space Grotesk',system-ui,sans-serif!important;font-weight:700!important;letter-spacing:-.055em!important}
  </style>
  @stack('styles')
  @stack('head')
</head>
@php
  $activeNav = $activeNav ?? 'profile';
  $navItems = [
    ['key' => 'profile',   'route' => 'onboarding.profile',       'label' => 'Profile',         'icon' => 'person'],
    ['key' => 'styles',    'route' => 'onboarding.styles-social', 'label' => 'Styles & Social', 'icon' => 'brush'],
    ['key' => 'studio',    'route' => 'onboarding.studio',        'label' => 'Studio',          'icon' => 'storefront'],
    ['key' => 'payments',  'route' => 'onboarding.preferences',   'label' => 'Payments',        'icon' => 'tune'],
    ['key' => 'calendar',  'route' => 'onboarding.calendar',      'label' => 'Calendar',        'icon' => 'calendar_month'],
    ['key' => 'payouts',   'route' => 'onboarding.payment',       'label' => 'Payouts',         'icon' => 'payments'],
  ];
  $stepKeys = array_column($navItems, 'key');
  $stepIndex = array_search($activeNav, $stepKeys, true);
  if ($stepIndex === false) { $stepIndex = 0; }
  $stepNumber = $stepIndex + 1;
  $stepTotal = count($navItems);
  $progressPercent = (int) round(($stepNumber / $stepTotal) * 100);
@endphp
<body>
<aside>
  <div class="topline">
    <a href="{{ url('/') }}" class="logo" style="display:block" title="Bookpay">bookpay</a>
    <button class="burger" type="button" aria-label="Open steps" onclick="document.body.classList.toggle('navopen')"><span class="ms">menu</span></button>
  </div>
  <div class="tag">ARTIST ONBOARDING</div>
  <nav>
    @foreach ($navItems as $i => $item)
      @php
        $isActive = $activeNav === $item['key'];
        $isDone = !$isActive && $i < $stepIndex;
      @endphp
      <a href="{{ $item['route'] ? route($item['route']) : '#' }}" class="{{ $isActive ? 'on' : '' }}{{ $isDone ? ' done' : '' }}">
        @if ($isDone)
          <span class="ms nav-done" style="color:#3DD68C;font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24">check_circle</span>
        @else
          <span class="ms">{{ $item['icon'] }}</span>
        @endif
        {{ $item['label'] }}
      </a>
    @endforeach
    <div class="sep"></div>
    <form method="POST" action="{{ route('logout') }}" class="m-0">
      @csrf
      <button type="submit" class="nav-logout">
        <span class="ms">logout</span>Log Out
      </button>
    </form>
  </nav>
  <div class="foot">
    <div style="background:#262626;border-radius:12px;padding:12px 14px">
      <div class="row" style="justify-content:space-between">
        <b style="font-size:12.5px;color:#fff">Setup progress</b>
        <span style="font-size:12px;color:#aaa">Step {{ $stepNumber }} of {{ $stepTotal }}</span>
      </div>
      <div style="height:6px;border-radius:4px;background:#3a3a3a;margin-top:8px">
        <div style="width:{{ $progressPercent }}%;height:6px;border-radius:4px;background:#fff"></div>
      </div>
    </div>
  </div>
</aside>
<main>
  @if (session('success'))
    <div class="wrap" style="padding-bottom:0"><div class="flash flash-ok">{{ session('success') }}</div></div>
  @endif
  @if (session('error'))
    <div class="wrap" style="padding-bottom:0"><div class="flash flash-err" role="alert">{{ session('error') }}</div></div>
  @endif
  @if (session('info'))
    <div class="wrap" style="padding-bottom:0"><div class="flash flash-info">{{ session('info') }}</div></div>
  @endif
  @yield('content')
</main>
  <style>
a{color:inherit;text-decoration:none}
nav a,.tabs div{cursor:pointer}
  .nav-logout{
    display:flex;align-items:center;gap:10px;width:100%;padding:10px 12px;border:0;border-radius:10px;
    background:transparent;color:inherit;font:inherit;cursor:pointer;text-align:left;
  }
  .nav-logout:hover{background:rgba(255,255,255,.08)}
  .nav-logout .ms{font-size:20px;opacity:.85}
  nav a.done .nav-done{color:#3DD68C}
  .flash{margin:16px 0 0;padding:12px 14px;border-radius:12px;font-size:13.5px;font-weight:500}
  .flash-ok{background:#E8F8EF;color:#0B6B3A;border:1px solid #B6E5C8}
  .flash-err{background:#FDECEC;color:#9B1C1C;border:1px solid #F5C2C2}
  .flash-info{background:#FFF8E8;color:#7A5A00;border:1px solid #F0DFA0}

  /* Select2 — shared onboarding look (matches .in fields) */
  .select2-container{width:100%!important;z-index:1;font-family:inherit}
  .select2-container--open{z-index:10060!important}
  .select2-container--default .select2-selection--single{
    min-height:40px;height:auto;padding:0 13px;border-radius:10px;
    border:1px solid var(--line)!important;background:#fff!important;
    display:flex;align-items:center;
  }
  .select2-container--default .select2-selection--single .select2-selection__rendered{
    padding:0;line-height:1.4;color:var(--ink);font-size:13.5px;
  }
  .select2-container--default .select2-selection--single .select2-selection__placeholder{color:var(--faint)}
  .select2-container--default .select2-selection--single .select2-selection__arrow{
    height:100%;top:0;right:8px;width:20px;
  }
  .select2-container--default .select2-selection--single .select2-selection__arrow b{
    border-color:var(--muted) transparent transparent transparent;margin-top:-2px;
  }
  .select2-container--default.select2-container--open .select2-selection--single .select2-selection__arrow b{
    border-color:transparent transparent var(--muted) transparent;margin-top:-6px;
  }
  .select2-container--default.select2-container--focus .select2-selection--single,
  .select2-container--default.select2-container--open .select2-selection--single{
    border-color:#3E007C!important;box-shadow:0 0 0 3px #F3E8FF;
  }
  .select2-container.is-err .select2-selection--single,
  .select2-container--default.is-err .select2-selection--single{
    border-color:#C62828!important;box-shadow:none;
  }
  .select2-dropdown{
    border-radius:12px;border:1px solid var(--line);overflow:hidden;
    box-shadow:0 14px 40px rgba(20,0,40,.14);font-size:13.5px;
  }
  .select2-container--default .select2-results__option--highlighted.select2-results__option--selectable{
    background-color:var(--pri)!important;color:#fff;
  }
  .select2-container--default .select2-results__option--selected{background:#F7F3F9;color:var(--pri);font-weight:600}
  .select2-container--default .select2-search--dropdown .select2-search__field{
    border-radius:8px;border:1px solid var(--line);padding:8px 10px;outline:none;
  }
  .select2-container--default .select2-search--dropdown .select2-search__field:focus{
    border-color:#3E007C;box-shadow:0 0 0 3px #F3E8FF;
  }
  select.js-select2{width:100%}
</style>
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
(function ($) {
  if ($) {
    var csrf = document.querySelector('meta[name="csrf-token"]');
    if (csrf && csrf.getAttribute('content')) {
      $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': csrf.getAttribute('content') } });
    }
  }

  window.scrollToFirstOnboardingError = function (root) {
    var scope = root && root.nodeType ? root : (typeof root === 'string' ? document.querySelector(root) : null);
    if (!scope) scope = document.querySelector('main') || document.body;
    var errs = scope.querySelectorAll('[id$="_error"]');
    for (var i = 0; i < errs.length; i++) {
      var el = errs[i];
      if (el.classList.contains('hidden') || !(el.textContent || '').trim()) continue;
      setTimeout(function (node) { node.scrollIntoView({ behavior: 'smooth', block: 'center' }); }, 0, el);
      return true;
    }
    return false;
  };

  window.clearOnboardingFieldError = function (serverKey) {
    if (!serverKey) return;
    var nested = {
      'social_links.website': 'website',
      'social_links.instagram': 'instagram',
      'social_links.tiktok': 'tiktok',
      'social_links.youtube': 'youtube',
      'social_links.facebook': 'facebook',
    };
    var id = nested[serverKey] || (serverKey.indexOf('.') === -1 ? serverKey : serverKey.split('.').pop());
    var err = document.getElementById(id + '_error') || document.getElementById(serverKey.replace(/\./g, '_') + '_error');
    if (err) { err.textContent = ''; err.classList.add('hidden'); }
    var el = document.getElementById(id);
    if (el) {
      el.classList.remove('is-err');
      if (window.jQuery && el.classList.contains('select2-hidden-accessible')) {
        window.jQuery(el).next('.select2-container').removeClass('is-err');
      }
      if (el.closest) {
        var social = el.closest('.social-in');
        if (social) social.classList.remove('is-err');
      }
    }
    if (serverKey === 'other_styles') {
      var w = document.getElementById('wrap_other_styles');
      if (w) w.classList.remove('is-err');
    }
  };

  /**
   * Init Select2 on selects. Reuse on any onboarding stage:
   *   <select class="js-select2" data-placeholder="Select…">…</select>
   * or call: initOnboardingSelect2('#mySelect', { placeholder: '…' })
   * Pass a selector string, DOM element, or jQuery collection of <select>s (not a container).
   */
  window.initOnboardingSelect2 = function (selector, options) {
    if (!window.jQuery || !window.jQuery.fn.select2) return;
    var $ = window.jQuery;
    var opts = options || {};
    var $els = selector ? $(selector) : $('select.js-select2');
    // If a container was passed, find selects inside it
    if ($els.length && !$els.is('select')) {
      $els = $els.find('select.js-select2');
    }
    $els.each(function () {
      if (this.tagName !== 'SELECT') return;
      var $el = $(this);
      if ($el.hasClass('select2-hidden-accessible')) return;
      var placeholder = opts.placeholder || $el.data('placeholder') || $el.find('option[value=""]').first().text() || 'Select';
      var allowClear = opts.allowClear != null ? opts.allowClear : !!$el.data('allow-clear');
      var searchable = opts.searchable != null ? opts.searchable : $el.data('searchable');
      var $parent = opts.dropdownParent || ($el.closest('.card, .modal, main').length ? $el.closest('.card, .modal, main') : $(document.body));
      var cfg = {
        width: '100%',
        placeholder: placeholder,
        allowClear: allowClear,
        dropdownParent: $parent,
      };
      if (searchable === false || searchable === 0 || searchable === 'false') {
        cfg.minimumResultsForSearch = Infinity;
      }
      $el.select2($.extend(cfg, opts.select2 || {}));
    });
  };

  if ($) {
    $(function () {
      window.initOnboardingSelect2('select.js-select2');
    });
  }
})(window.jQuery);
</script>
@stack('scripts')
</body>
</html>