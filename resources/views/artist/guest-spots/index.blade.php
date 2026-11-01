@extends('layouts.new-artist-dashboard-layout')

@section('title', 'Schedule')

@php
  $bookingPageUsername = $bookingPageUsername ?? (Auth::user()->userDetail->user_name ?? null);
  $displayGuestSpots = (bool) ($displayGuestSpots ?? false);
@endphp

@section('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css">
@if(config('services.google.place_api_key'))
<script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google.place_api_key') }}&libraries=places"></script>
@endif
@include('partials.bookpay-datetime-picker')
<style>
  .gs-page .radio-card {
    border: 1.5px solid var(--line); border-radius: 14px; padding: 14px 16px; cursor: pointer; transition: all .15s; background:#fff;
  }
  .gs-page .radio-card:hover { border-color:#C9B8D8; }
  .gs-page .radio-card.selected { border:2px solid #3E007C; padding:13px 15px; background:#FDFAFF; }
  .gs-page .radio-card.selected .radio-dot { background:#3E007C; border-color:#3E007C; }
  .gs-page .radio-card.selected .radio-dot::after {
    content:'';display:block;width:6px;height:6px;background:#fff;border-radius:50%;
  }
  .gs-page .radio-dot {
    width:18px;height:18px;border:2px solid #D5CDD9;border-radius:50%;
    display:flex;align-items:center;justify-content:center;flex-shrink:0;
  }

  .gs-page .gshead{
    display:grid;grid-template-columns:28px 120px 1.2fr 1.2fr 1fr 1fr 70px;gap:12px;
    padding:11px 22px;background:#FBF8FC;border-bottom:1px solid var(--line);
    font-size:11px;font-weight:700;letter-spacing:.8px;color:var(--muted)
  }
  .gs-page .guest-spot-row,.gs-page .gsrow{
    padding:14px 22px;border-bottom:1px solid #F3EEF4;background:#fff;cursor:grab;
    transition:background .15s,box-shadow .15s,opacity .15s
  }
  .gs-page .guest-spot-row:last-child{border-bottom:0}
  .gs-page .guest-spot-row:hover{background:#FBF7FC}
  .gs-page .guest-spot-row.dragging,.gs-page .guest-spot-row.drag{opacity:.4}
  .gs-page .guest-spot-row.drag-over-top{box-shadow:inset 0 2px 0 #3E007C}
  .gs-page .guest-spot-row.drag-over-bottom{box-shadow:inset 0 -2px 0 #3E007C}
  .gs-page .guest-spot-row.is-editing{background:#FDFAFF;box-shadow:inset 3px 0 0 #3E007C}
  .gs-page .gsmain{
    display:grid;grid-template-columns:28px 120px 1.2fr 1.2fr 1fr 1fr 70px;gap:12px;
    align-items:center;font-size:14px
  }
  .gs-page .gscity{font-size:15px;font-weight:700}
  .gs-page .gsdrag{cursor:grab;color:#B7AEBB}
  .gs-page .gsact{display:flex;gap:8px;justify-content:flex-end;color:var(--muted)}
  .gs-page .guest-spot-action{
    width:32px;height:32px;border:0;background:none;border-radius:8px;cursor:pointer;
    display:inline-flex;align-items:center;justify-content:center;color:var(--muted);padding:0
  }
  .gs-page .guest-spot-edit:hover{background:#F3E8FF;color:var(--pri)}
  .gs-page .guest-spot-delete:hover{background:#FDECEC;color:var(--red)}
  .gs-page .gsdet,.gs-page .guest-spot-details{
    display:flex;flex-wrap:wrap;gap:8px 18px;align-items:center;margin:10px 0 0 40px;
    padding-top:10px;border-top:1px dashed #EDE6F0;font-size:13px;color:#4B4452
  }
  .gs-page .guest-spot-details.hidden{display:none!important}
  .gs-page .gsd,.gs-page .guest-spot-detail-item{display:inline-flex;gap:6px;align-items:center;max-width:100%}
  .gs-page .gsd .ms,.gs-page .guest-spot-detail-item .ms,
  .gs-page .guest-spot-detail-item .material-symbols-outlined{font-size:17px;color:var(--faint);flex-shrink:0}
  .gs-page .status-badge,.gs-page .pill.nd{border-radius:20px;font-size:11.5px;font-weight:600;padding:3px 9px;white-space:nowrap;display:inline-flex}
  .gs-page .status-badge.available,.gs-page .pill.g{background:var(--greenl);color:var(--green)}
  .gs-page .status-badge.planned,.gs-page .pill.b{background:var(--bluel);color:var(--blue)}
  .gs-page .status-badge.full,.gs-page .pill.a{background:var(--amberl);color:var(--amber)}
  .gs-page .status-badge.completed,.gs-page .pill.k{background:var(--chip);color:var(--muted)}
  .gs-page .guest-spot-copy-link,.gs-page .btn.sm.ghost.js-copy-guest-spot-link,
  .gs-page .gsdet .btn.sm.ghost{
    display:inline-flex;align-items:center;gap:6px;border:1px solid var(--line);background:#fff;
    color:var(--ink);border-radius:8px;padding:6px 11px;font-size:12.5px;font-weight:600;cursor:pointer;
    text-decoration:none;line-height:1.2
  }
  .gs-page .guest-spot-copy-link:hover,.gs-page .gsdet .btn.sm.ghost:hover{background:#FBF7FC;border-color:#D8CBE0}
  .gs-page .guest-spot-copy-link.is-copied,.gs-page .btn.sm.ghost.is-copied{border-color:#B7E4CB;color:#1B5E4A;background:#E6F8EE}
  .gs-page .gsdet .btn.sm.ghost .ms{font-size:16px;color:inherit}
  .gs-page .gsacts{display:inline-flex;flex-wrap:wrap;gap:8px;align-items:center}
  .gs-page .gsurl{display:flex;gap:6px;align-items:center;font-size:12.5px;color:var(--muted);width:100%}
  .gs-page .gsurl .ms{font-size:16px;color:var(--faint);flex-shrink:0}
  .gs-page .gsnote{display:flex;gap:6px;align-items:flex-start;font-size:12.5px;color:var(--muted);width:100%}
  .gs-page .gsnote .ms{font-size:16px;flex-shrink:0;margin-top:1px}
  .gs-page .tog,#guestSpotFormCard .tog{
    width:38px;height:22px;border-radius:12px;background:#D9D2DD;position:relative;flex-shrink:0;cursor:pointer
  }
  .gs-page .tog:after,#guestSpotFormCard .tog:after{content:'';position:absolute;width:16px;height:16px;border-radius:50%;background:#fff;top:3px;left:3px;transition:left .15s}
  .gs-page .tog.on,#guestSpotFormCard .tog.on{background:var(--pri)}
  .gs-page .tog.on:after,#guestSpotFormCard .tog.on:after{left:19px}
  .gs-page .info-tooltip{position:relative;display:inline-flex;cursor:help;vertical-align:middle}
  .gs-page .info-tooltip .tooltip-text{
    visibility:hidden;opacity:0;position:absolute;bottom:calc(100% + 8px);left:50%;transform:translateX(-50%);
    background:#322f36;color:#fff;padding:8px 12px;border-radius:8px;font-size:12px;font-weight:500;width:260px;
    text-align:left;transition:opacity .2s;z-index:20;line-height:1.4;pointer-events:none
  }
  .gs-page .info-tooltip:hover .tooltip-text,.gs-page .info-tooltip:focus-within .tooltip-text{visibility:visible;opacity:1}
  .gs-page .pac-container,.pac-container{z-index:10060!important}
  .gs-page .mp-toast{
    position:fixed;top:22px;right:24px;background:#1A1A1A;color:#fff;padding:12px 18px;border-radius:10px;
    font-weight:600;font-size:14px;z-index:500;box-shadow:0 10px 30px rgba(0,0,0,.22);
    display:flex;gap:10px;align-items:center;opacity:0;transform:translateX(calc(100% + 40px));
    transition:opacity .25s,transform .35s;pointer-events:none
  }
  .gs-page .mp-toast.show{opacity:1;transform:none}
  .gs-page #guestSpotAlert{margin-bottom:14px}
  .gs-page .hidden,.gs-page #guestSpotAlert.hidden,#guestSpotsListWrap.hidden,#guestSpotsEmpty.hidden{display:none!important}
  .gs-page .gsb{
    display:flex;align-items:center;gap:16px;flex-wrap:wrap;
    background:linear-gradient(135deg,#F3E8FF,#FFF6FF);border:1px solid #E6D6F2;border-radius:16px;
    padding:16px 18px;margin-bottom:14px
  }
  .gs-page .gsb .gi{
    width:44px;height:44px;border-radius:12px;background:#3E007C;color:#fff;
    display:flex;align-items:center;justify-content:center;flex-shrink:0
  }
  .gs-page .gsb .gi .ms{font-size:24px}
  .gs-page .gsb .gt{flex:1;min-width:220px}
  .gs-page .gsb .gt b{display:block;font-size:15px}
  .gs-page .gsb .gt>span{display:block;font-size:13px;color:#6F6874;margin-top:2px;line-height:1.45}
  .gs-page .gsb a.gb{
    display:inline-flex;align-items:center;gap:6px;background:#1A1A1A;color:#fff;
    font-weight:700;font-size:13.5px;padding:10px 14px;border-radius:10px;text-decoration:none;white-space:nowrap
  }
  .gs-page .gsb a.gb .ms{font-size:16px}
  .gs-page .gsb a.gb:focus-visible{outline:2px solid #3E007C;outline-offset:2px}
  @media (max-width:600px){.gs-page .gsb a.gb{width:100%;justify-content:center}}

  #guestSpotFormModal,#deleteGuestSpotModal{
    display:none;position:fixed;inset:0;background:rgba(26,26,26,.45);z-index:400;
    align-items:center;justify-content:center;padding:24px;opacity:0;transition:opacity .25s
  }
  #guestSpotFormModal.modal-visible,#deleteGuestSpotModal.modal-visible{display:flex}
  #guestSpotFormModal.modal-open,#deleteGuestSpotModal.modal-open{opacity:1}
  #guestSpotFormCard.gs-modal,#guestSpotFormCard.modal{
    background:#fff;border-radius:16px;border:1px solid var(--line);width:100%;max-width:1040px;
    height:calc(100vh - 48px);max-height:calc(100vh - 48px);max-height:calc(100dvh - 48px);
    display:flex;flex-direction:column;overflow:hidden;
    box-shadow:0 18px 50px rgba(0,0,0,.18);padding:0
  }
  #guestSpotFormCard .mh{padding:22px 24px 0;display:flex;justify-content:space-between;align-items:flex-start;flex-shrink:0}
  #guestSpotFormCard .mh.gx{border-bottom:0;padding-bottom:10px}
  #guestSpotFormCard .mh h2{font-size:20px;font-weight:800;letter-spacing:-.3px;margin:0}
  #guestSpotFormCard .gs-x{border:0;background:none;cursor:pointer;padding:4px;line-height:1}
  #guestSpotFormCard .gtabs{display:flex;gap:22px;padding:0 24px;border-bottom:1px solid #F0EAF2;flex-shrink:0;overflow-x:auto}
  #guestSpotFormCard .gtabs button{
    display:flex;gap:7px;align-items:center;padding:10px 2px 12px;font:inherit;font-weight:600;font-size:14px;
    color:var(--muted);border:0;background:none;border-bottom:2px solid transparent;cursor:pointer;white-space:nowrap
  }
  #guestSpotFormCard .gtabs button .ms{font-size:18px}
  #guestSpotFormCard .gtabs button.on{color:#1A1A1A;border-bottom-color:#1A1A1A}
  #guestSpotFormCard .gs-form{
    flex:1 1 auto;min-height:0;display:flex;flex-direction:column;overflow:hidden
  }
  #guestSpotFormCard .mb{padding:16px 24px 20px;flex:1 1 auto;min-height:0;overflow-y:auto;overscroll-behavior:contain;-webkit-overflow-scrolling:touch}
  #guestSpotFormCard .mf{padding:14px 24px;border-top:1px solid #F0EAF2;display:flex;justify-content:flex-end;gap:10px;background:#FCFAFD;flex-shrink:0}
  #guestSpotFormCard .fl{font-size:13px;font-weight:700;margin-bottom:6px;display:block}
  #guestSpotFormCard .help{font-size:12px;color:var(--faint);margin-top:6px}
  #guestSpotFormCard .in,#guestSpotFormCard input.in,#guestSpotFormCard select.in{
    width:100%;font:inherit;font-size:13.5px;color:var(--ink);background:#fff;
    border:1px solid var(--line);border-radius:10px;padding:10px 13px;outline:none;box-sizing:border-box;min-height:40px
  }
  #guestSpotFormCard input.in:focus,#guestSpotFormCard select.in:focus,
  #guestSpotFormCard .bpx-btn.in:focus,#guestSpotFormCard .bpx-btn.in:focus-visible{
    border-color:#3E007C;box-shadow:0 0 0 3px #F3E8FF
  }
  #guestSpotFormCard .border-error,#guestSpotFormCard .bpx-btn.border-error{border-color:#C62828!important;box-shadow:none}
  #guestSpotFormCard .gs-2col>div{position:relative}
  #guestSpotFormCard .text-error{color:#C62828;font-size:12px;margin-top:6px}
  #guestSpotFormCard .text-error.hidden,#guestSpotFormCard .hidden,.gtp[hidden]{display:none!important}
  #guestSpotFormCard .po{
    display:flex;gap:12px;align-items:flex-start;background:#fff;border:1px solid var(--line);
    border-radius:14px;padding:16px 18px;cursor:pointer;margin-bottom:10px
  }
  #guestSpotFormCard .po.sel{border:2px solid #3E007C;padding:15px 17px;background:#FDFAFF}
  #guestSpotFormCard .po input{accent-color:#3E007C;width:18px;height:18px;margin-top:2px;flex-shrink:0}
  #guestSpotFormCard .po b{font-size:14.5px;display:block}
  #guestSpotFormCard .po .d{font-size:13px;color:var(--muted);margin-top:3px;line-height:1.5}
  #guestSpotFormCard .tgrid{display:grid;grid-template-columns:1fr 1fr;gap:10px}
  #guestSpotFormCard .tgrid .po{margin:0}
  #guestSpotFormCard .gs-details-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;align-items:start}
  #guestSpotFormCard .gs-span-full{grid-column:1 / -1}
  #guestSpotFormCard .gs-look-grid{display:grid;grid-template-columns:1fr 300px;gap:20px;align-items:start}
  #guestSpotFormCard .selall{border:0;background:none;font:inherit;font-size:12.5px;font-weight:700;color:#3E007C;text-decoration:underline;text-underline-offset:2px;cursor:pointer;padding:0}
  #guestSpotFormCard .pfg{display:grid;grid-template-columns:repeat(6,1fr);gap:10px}
  #guestSpotFormCard .pfp{
    aspect-ratio:1;border-radius:12px;border:2px solid transparent;background:#EEE center/cover no-repeat;
    position:relative;cursor:pointer;padding:0
  }
  #guestSpotFormCard .pfp .pfk{
    position:absolute;top:8px;right:8px;width:24px;height:24px;border-radius:50%;
    background:rgba(255,255,255,.9);border:1.5px solid #fff;display:flex;align-items:center;justify-content:center;
    box-shadow:0 1px 4px rgba(0,0,0,.25)
  }
  #guestSpotFormCard .pfp .pfk .ms{font-size:16px;color:transparent}
  #guestSpotFormCard .pfp[aria-pressed="true"]{border-color:#3E007C;box-shadow:0 0 0 2px #3E007C}
  #guestSpotFormCard .pfp[aria-pressed="true"] .pfk{background:#3E007C;border-color:#3E007C}
  #guestSpotFormCard .pfp[aria-pressed="true"] .pfk .ms{color:#fff}
  #guestSpotFormCard .dsl{
    position:absolute;left:8px;bottom:8px;background:#fff;border-radius:20px;font-size:10.5px;
    font-weight:700;padding:2px 8px;color:#6F6874
  }
  #guestSpotFormCard .bnprev .bnimg{
    height:96px;border-radius:12px;position:relative;overflow:hidden;
    background:linear-gradient(180deg,#0B0B0D,#1C1C20) center/cover no-repeat
  }
  #guestSpotFormCard .bnup{
    width:100%;height:96px;border:1.5px dashed #D6C9DB;border-radius:12px;display:flex;align-items:center;
    justify-content:center;gap:10px;color:#6F6874;background:#FDFAFD;font:inherit;cursor:pointer;text-align:left
  }
  #guestSpotFormCard .bnup .ms{font-size:28px;color:#9A929E}
  #guestSpotFormCard .bnup b{display:block;color:#1A1A1A;font-size:13.5px}
  #guestSpotFormCard .bnup small{font-size:12px;display:block;margin-top:2px;line-height:1.4}
  #guestSpotFormCard .bnup[hidden],#guestSpotFormCard .bnprev[hidden]{display:none!important}
  #guestSpotFormCard .lfgrid{display:grid;grid-template-columns:repeat(3,1fr);gap:12px}
  #guestSpotFormCard .lf{
    border:1px solid var(--line);border-radius:14px;background:#fff;overflow:hidden;padding:0;
    text-align:left;font:inherit;color:inherit;cursor:pointer;width:100%
  }
  #guestSpotFormCard .lf:hover{border-color:#B99BE0}
  #guestSpotFormCard .lf.on{border:2px solid #3E007C}
  #guestSpotFormCard .lfb{display:block;height:56px;position:relative}
  #guestSpotFormCard .lfn{display:block;padding:9px 12px;font-weight:600;font-size:13px}
  #guestSpotFormCard .swmark{
    position:absolute;top:8px;right:8px;width:22px;height:22px;border-radius:50%;background:#fff;
    display:flex;align-items:center;justify-content:center;box-shadow:0 1px 3px rgba(0,0,0,.18)
  }
  #guestSpotFormCard .swmark .ms{font-size:22px;color:#B9AFBE;font-variation-settings:"FILL" 0}
  #guestSpotFormCard .lf.on .swmark .ms{color:#3E007C;font-variation-settings:"FILL" 1}
  #guestSpotFormCard .lfc{
    margin-top:12px;padding:12px 14px;display:flex;align-items:center;gap:12px;flex-wrap:wrap;
    border:1px solid var(--line);border-radius:14px
  }
  #guestSpotFormCard .lfsw{width:44px;height:30px;border-radius:8px;background:#7A1FC4;flex-shrink:0}
  #guestSpotFormCard .lfc input[type=color]{width:42px;height:32px;border:0;background:none;padding:0;cursor:pointer}
  #guestSpotFormCard .gpv{position:sticky;top:0}
  #guestSpotFormCard .gpvc{border:1px solid var(--line);border-radius:16px;overflow:hidden;background:#FBF9FC}
  #guestSpotFormCard .gpvb{
    height:84px;position:relative;overflow:hidden;
    background:linear-gradient(120deg,var(--g1,#4B0F8F),var(--g2,#7B3FC4))
  }
  #guestSpotFormCard .gpvb.img{background-size:cover;background-position:center;background-repeat:no-repeat}
  #guestSpotFormCard .gsky{position:absolute;inset:0;width:100%;height:100%}
  #guestSpotFormCard .gpva{
    width:52px;height:52px;border-radius:50%;background:#C9B7A6;border:3px solid #FBF9FC;
    margin:-26px 0 0 16px;position:relative;display:flex;align-items:center;justify-content:center;
    font-weight:800;color:#fff;font-size:15px
  }
  #guestSpotFormCard .gpvt{padding:8px 16px 16px}
  #guestSpotFormCard .gpvs{
    display:inline-block;margin-top:4px;font-size:11px;font-weight:700;background:#E4F5EA;color:#1F8A4C;
    border-radius:20px;padding:2px 8px
  }
  #guestSpotFormCard .gpvl{font-size:12.5px;font-weight:700;margin-top:8px}
  #guestSpotFormCard .gpvbtn{
    margin-top:12px;border-radius:10px;padding:10px;text-align:center;font-weight:700;
    font-size:12.5px;color:#fff;background:var(--g1,#4B0F8F)
  }
  #guestSpotFormCard .gs-2col{display:grid;grid-template-columns:1fr 1fr;gap:12px}
  #guestSpotFormCard .gs-3col{display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px}
  #guestSpotFormCard .gs-stack{display:grid;gap:12px}
  #guestSpotFormCard .sect{border:1px solid #EFE8F1;border-radius:14px;padding:14px 16px;background:#FDFBFD}
  #guestSpotFormCard .sect .lbl{margin-bottom:8px}
  #guestSpotFormCard .splitbox{background:#FBF7FF;border:1px solid #E4D6F5;border-radius:14px;padding:18px;margin:4px 0 10px}
  #guestSpotFormCard .pct{display:flex;align-items:center;gap:8px}
  #guestSpotFormCard .pct input{width:80px;text-align:center}
  #guestSpotFormCard .lockbar{display:flex;gap:8px;align-items:center;background:#F0EDF2;color:var(--muted);border-radius:10px;padding:9px 12px;font-size:12.5px}
  #guestSpotFormCard .gscur .ms{flex-shrink:0}
  @media (max-width:900px){
    #guestSpotFormCard.gs-modal{max-width:100%}
    #guestSpotFormCard .gs-details-grid,
    #guestSpotFormCard .gs-look-grid,
    #guestSpotFormCard .gs-2col,
    #guestSpotFormCard .gs-3col,
    #guestSpotFormCard .tgrid{grid-template-columns:1fr}
    #guestSpotFormCard .gpv{position:static}
    #guestSpotFormCard .pfg{grid-template-columns:repeat(3,1fr)}
    #guestSpotFormCard .lfgrid{grid-template-columns:repeat(2,1fr)}
    #guestSpotFormCard .mf{flex-wrap:wrap}
    #guestSpotFormCard .mf .btn:last-child{flex:1 1 auto;justify-content:center}
  }
  .delete-guest-modal-inner{
    background:#fff;border-radius:16px;border:1px solid var(--line);width:100%;max-width:440px;
    box-shadow:0 18px 50px rgba(0,0,0,.18);overflow:hidden;
    transform:scale(.96) translateY(10px);opacity:0;
    transition:transform .32s cubic-bezier(.22,1,.36,1),opacity .28s
  }
  #deleteGuestSpotModal.modal-open .delete-guest-modal-inner{transform:scale(1) translateY(0);opacity:1}

  /* Banner cropper (above guest spot form modal) */
  #gsBannerCropModal.crop-modal-backdrop{
    display:none;position:fixed;inset:0;z-index:600;background:rgba(0,0,0,.78);
    align-items:center;justify-content:center;padding:16px
  }
  #gsBannerCropModal.crop-modal-backdrop.is-open{display:flex}
  #gsBannerCropModal .crop-modal-inner{
    width:100%;max-width:720px;max-height:min(92vh,900px);display:flex;flex-direction:column;
    background:#fff;border-radius:22px;box-shadow:0 20px 60px rgba(0,0,0,.25);overflow:hidden
  }
  #gsBannerCropModal .mh{
    padding:22px 24px 14px;border-bottom:1px solid #F0EAF2;
    display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-shrink:0
  }
  #gsBannerCropModal .mh h2{font-size:18px;font-weight:800;margin:0}
  #gsBannerCropModal .mx{
    border:0;background:none;cursor:pointer;color:var(--muted);
    width:36px;height:36px;border-radius:10px;display:inline-flex;align-items:center;justify-content:center
  }
  #gsBannerCropModal .mx:hover{background:#F7F3F9;color:var(--ink)}
  #gsBannerCropModal .mf{
    display:flex;gap:10px;justify-content:flex-end;padding:16px 24px;
    background:#FCF6FE;border-top:1px solid #F0E4F5;flex-shrink:0
  }
  #gsBannerCropModal .mf .btn{min-width:120px;justify-content:center}
  #gsBannerCropModal .cropper-stage-wrap{
    flex:1;min-height:200px;max-height:min(52vh,480px);background:#1a1a1a;
    border-radius:12px;overflow:hidden;margin:12px 16px
  }
  #gsBannerCropModal .cropper-stage-wrap img{max-height:min(52vh,480px);display:block;max-width:100%}
  #gsBannerCropModal .cropper-stage-wrap .cropper-container,
  #gsBannerCropModal .cropper-stage-wrap .cropper-wrap-box,
  #gsBannerCropModal .cropper-stage-wrap .cropper-canvas img{max-height:min(52vh,480px)}

  @media (max-width:900px){
    .gs-page .gshead{display:none}
    .gs-page .gsmain{grid-template-columns:24px 1fr auto;row-gap:4px}
    .gs-page .gsmain .gscell:nth-child(2){grid-column:2}
    .gs-page .gscity{grid-column:2}
    .gs-page .gsmain .gscell:nth-child(4),
    .gs-page .gsmain .gscell:nth-child(5),
    .gs-page .gsmain .gscell:nth-child(6){grid-column:2;font-size:13px;color:var(--muted)}
    .gs-page .gsact{grid-column:3;grid-row:1}
    .gs-page .gsdet,.gs-page .guest-spot-details{margin-left:0}
    .gs-page .mp-toast{left:16px;right:16px;top:74px}
  }
</style>
@endsection

@section('content')
<div class="gs-page">
  <div class="head">
    <div>
      <h1>
        Schedule
        <a class="help-q" href="https://help.inkjin.com/en/articles/17201045-dashboard-schedule-guest-spots" target="_blank" rel="noopener" title="Help with this page" aria-label="Help with this page">
          <span class="ms">help</span>
        </a>
      </h1>
      <div class="sub">When and how clients can book you.</div>
    </div>
  </div>

  @include('artist.partials.schedule-tabs', ['scheduleTab' => 'guest-spots'])

  <div class="row" style="justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px">
    <div class="muted">Tell clients where you'll be tattooing next.</div>
    <div class="row" style="gap:16px;flex-wrap:wrap">
      <div class="row" style="gap:8px">
        <span class="faint" style="font-size:12.5px;font-weight:600">Show on my page</span>
        <div
          id="toggleDisplayGuestSpots"
          class="tog {{ $displayGuestSpots ? 'on' : '' }}"
          role="switch"
          aria-checked="{{ $displayGuestSpots ? 'true' : 'false' }}"
          title="Show guest spots on your page"
        ></div>
      </div>
      <button type="button" class="btn" id="btnOpenAddGuestSpot"><span class="ms">add</span>Add guest spot</button>
    </div>
  </div>

  <div id="guestSpotAlert" class="hidden card" style="padding:12px 16px;font-size:13.5px;font-weight:600"></div>
  <div class="mp-toast" id="gsToast" role="status"><span class="ms">check_circle</span><span id="gsToastText">Saved</span></div>

  @include('artist.guest-spots.partials.form-modal')

  <div class="gsb" role="region" aria-label="Guest Spot Board">
    <div class="gi" aria-hidden="true"><span class="ms">flight_takeoff</span></div>
    <div class="gt">
      <b>Looking for a guest spot?</b>
      <span>Find studios with open chairs on the Guest Spot Board. It’s free, and it matches you with studios by city, dates and style.</span>
    </div>
    <a class="gb" href="https://inkjin.com/en/bookpay/guest-spot-board/" target="_blank" rel="noopener" aria-label="Open the board (opens the Guest Spot Board in a new tab)">
      Open the board<span class="ms" aria-hidden="true">open_in_new</span>
    </a>
  </div>

  <!-- Guest locations list -->
  <div class="card" style="overflow:hidden">
    <div class="ch">
      <div class="row">
        <div class="ic"><span class="ms">reorder</span></div>
        <div>
          <h3>Guest locations</h3>
          <div class="faint" style="font-size:12.5px;margin-top:2px">Drag to reorder how they appear on your page</div>
        </div>
      </div>
      <span class="pill nd k" id="guestSpotCount">{{ $guestSpots->count() }}</span>
    </div>

    <div id="guestSpotsEmpty" class="{{ $guestSpots->isEmpty() ? '' : 'hidden' }}" style="padding:42px 26px;text-align:center">
      <div class="ic" style="margin:0 auto 12px;width:52px;height:52px;border-radius:14px"><span class="ms">flight_takeoff</span></div>
      <b style="font-size:15px">No guest locations yet</b>
      <div class="faint" style="font-size:13px;margin-top:6px">Add your first guest spot to tell clients where you'll be next.</div>
    </div>

    <div id="guestSpotsListWrap" class="{{ $guestSpots->isEmpty() ? 'hidden' : '' }}">
      <div class="gshead">
        <span></span>
        <span>STATUS</span>
        <span>CITY</span>
        <span>COUNTRY</span>
        <span>FROM</span>
        <span>TO</span>
        <span></span>
      </div>

      <div id="guestSpotsList">
        @foreach ($guestSpots as $spot)
          @php $statusKey = $spot->effectiveStatusKey(); @endphp
          <div
            class="guest-spot-row gsrow"
            draggable="true"
            data-id="{{ $spot->id }}"
            data-status="{{ $spot->status }}"
            data-city="{{ $spot->city }}"
            data-country="{{ $spot->country }}"
            data-from="{{ $spot->from_date->format('Y-m-d') }}"
            data-to="{{ $spot->to_date->format('Y-m-d') }}"
            data-spot='@json($spot->toFormArray())'
            data-update-url="{{ route('guest-spots.update', $spot) }}"
            data-delete-url="{{ route('guest-spots.destroy', $spot) }}"
          >
            <div class="gsmain">
              <span class="ms gsdrag" title="Drag to reorder">drag_indicator</span>
              <span class="gscell"><span class="status-badge pill nd {{ $statusKey === 'available' ? 'g' : ($statusKey === 'planned' ? 'b' : ($statusKey === 'full' ? 'a' : 'k')) }} {{ $statusKey }}">{{ $spot->publicStatusLabel() }}</span></span>
              <b class="gscell gscity guest-spot-city">{{ $spot->city }}</b>
              <span class="gscell guest-spot-country">{{ $spot->country }}</span>
              <span class="gscell guest-spot-from">{{ $spot->from_date->format('M j, Y') }}</span>
              <span class="gscell guest-spot-to">{{ $spot->to_date->format('M j, Y') }}</span>
              <span class="gsact guest-spot-actions">
                @if ($statusKey !== 'completed')
                <button type="button" class="guest-spot-action guest-spot-edit" title="Edit" aria-label="Edit guest location"><span class="ms">edit</span></button>
                <button type="button" class="guest-spot-action guest-spot-delete" title="Delete" aria-label="Remove guest location"><span class="ms">delete</span></button>
                @endif
              </span>
            </div>
            @if ($spot->hasListDetails())
            @php
              $pageUrl = ($statusKey === 'available' && $bookingPageUsername)
                ? url('/@'.$bookingPageUsername.'/request/custom').'?guest_spot='.$spot->id
                : null;
              $pageUrlDisplay = $pageUrl
                ? preg_replace('#^https?://#', '', $pageUrl)
                : null;
              $bookingsUrl = route('artist.bookings.index');
            @endphp
            <div class="guest-spot-details gsdet">
              @if ($spot->listAvailabilityTimeLabel())
              <span class="guest-spot-detail-item gsd"><span class="ms">schedule</span><span class="guest-spot-hours">{{ $spot->listAvailabilityTimeLabel() }}</span></span>
              @endif
              @if ($spot->listStudioLabel())
              <span class="guest-spot-detail-item gsd"><span class="ms">storefront</span><b class="guest-spot-studio">{{ $spot->listStudioLabel() }}</b></span>
              @endif
              @if ($spot->listLocationLabel())
              <span class="guest-spot-detail-item gsd"><span class="ms">location_on</span><span class="guest-spot-location">{{ $spot->listLocationLabel() }}</span></span>
              @endif
              @if ($spot->listBufferLabel())
              <span class="guest-spot-detail-item gsd"><span class="ms">event_busy</span><span class="guest-spot-buffer">{{ $spot->listBufferLabel() }}</span></span>
              @endif
              @if ($spot->listRemainingSpotsLabel())
              <span class="guest-spot-detail-item gsd"><span class="ms">event_seat</span><span class="guest-spot-remaining">{{ $spot->listRemainingSpotsLabel() }}</span></span>
              @endif
              @if ($spot->listPayoutLabel())
              <span class="guest-spot-detail-item gsd"><span class="ms">payments</span><span class="guest-spot-payout">{{ $spot->listPayoutLabel() }}</span></span>
              @endif
              @if ($statusKey === 'available' && $pageUrl)
              <span class="gsacts">
                <a class="btn sm ghost gsview" href="{{ $pageUrl }}" target="_blank" rel="noopener">
                  <span class="ms">open_in_new</span>View page
                </a>
                <button type="button" class="btn sm ghost gscopy js-copy-guest-spot-link" data-url="{{ $pageUrl }}">
                  <span class="ms">link</span><span class="guest-spot-copy-label">Copy link</span>
                </button>
                <a class="btn sm ghost gsbk" href="{{ $bookingsUrl }}">
                  <span class="ms">calendar_month</span>View bookings
                </a>
              </span>
              @if ($pageUrlDisplay)
              <span class="gsurl"><span class="ms">public</span>{{ $pageUrlDisplay }}</span>
              @endif
              @elseif (in_array($statusKey, ['completed', 'full'], true) || $spot->status === 'completed')
              <span class="gsacts">
                <a class="btn sm ghost gsbk" href="{{ $bookingsUrl }}">
                  <span class="ms">calendar_month</span>View bookings
                </a>
              </span>
              @endif
              @if ($statusKey === 'planned')
              <span class="gsnote"><span class="ms">info</span>Planned guest spots have no page yet. Clients can tap Notify on your page to hear when {{ $spot->city }} opens.</span>
              @endif
            </div>
            @else
            <div class="guest-spot-details gsdet hidden"></div>
            @endif
          </div>
        @endforeach
      </div>
    </div>
  </div>

  <!-- Banner crop (same as personal-page appearance: 1600 × 230) -->
  <div id="gsBannerCropModal" class="crop-modal-backdrop" aria-hidden="true">
    <div class="crop-modal-inner" onclick="event.stopPropagation()">
      <div class="mh">
        <div>
          <h2>Crop banner</h2>
          <div class="muted" style="font-size:13px;margin-top:4px">Recommended <b style="color:var(--ink);font-weight:700">1600 × 230</b> · keep the main subject near the centre.</div>
        </div>
        <button type="button" class="mx" id="gsBannerCropClose" aria-label="Close cropper"><span class="ms">close</span></button>
      </div>
      <div class="cropper-stage-wrap">
        <img id="gsBannerCropperImg" src="" alt="">
      </div>
      <div class="mf">
        <button type="button" class="btn ghost" id="gsBannerCropCancel">Cancel</button>
        <button type="button" class="btn" id="gsBannerCropApply"><span class="ms">check</span>Apply crop</button>
      </div>
    </div>
  </div>

  <!-- Delete guest location confirmation -->
  <div class="modal-backdrop" id="deleteGuestSpotModal" aria-hidden="true">
    <div class="delete-guest-modal-inner" onclick="event.stopPropagation()">
      <div style="padding:22px 24px">
        <div class="row" style="align-items:flex-start;gap:14px">
          <div class="ic" style="background:var(--redl);color:var(--red)"><span class="ms">delete_forever</span></div>
          <div style="flex:1;min-width:0">
            <h3 style="font-size:16px;font-weight:800;margin:0">Delete this guest location?</h3>
            <p class="muted" style="font-size:13.5px;margin-top:8px;line-height:1.5">
              This will permanently remove <span id="deleteGuestSpotLabel" style="font-weight:700;color:var(--ink)"></span> from your list. You cannot undo this.
            </p>
            <p id="deleteGuestSpotError" class="hidden" style="margin-top:10px;color:var(--red);font-size:12.5px;font-weight:600"></p>
          </div>
        </div>
      </div>
      <div class="row" style="justify-content:flex-end;gap:10px;padding:14px 22px;border-top:1px solid #F0E9F2;background:#FCF6FE">
        <button type="button" id="btnDeleteGuestSpotCancel" class="btn ghost">Cancel</button>
        <button type="button" id="btnDeleteGuestSpotConfirm" class="btn" style="background:var(--red)">
          <span class="ms confirm-delete-icon">delete</span>
          <span class="confirm-delete-label">Delete</span>
        </button>
      </div>
    </div>
  </div>
</div>

@include('components.waitlist-notify-modal')
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js"></script>
<script src="{{ asset('js/waitlist-notify.js') }}?v=7"></script>
<script>
function selectGuestStatus(card) {
  var $card = $(card);
  $('#guestStatusCards .po').removeClass('sel');
  $card.addClass('sel');
  $card.find('input[type="radio"]').prop('checked', true);
  $('#guest_status').val($card.data('status') || 'available');
  $('#status_error').addClass('hidden').text('');
  if (typeof window.syncGuestAvailableFields === 'function') {
    window.syncGuestAvailableFields();
  }
}

(function () {
  var storeUrl = @json(route('guest-spots.store'));
  var reorderUrl = @json(route('guest-spots.reorder'));
  var guestSpotLinkBase = @json(
    ($bookingPageUsername ?? null)
      ? url('/@'.$bookingPageUsername.'/request/custom')
      : null
  );
  var guestSpotBookingsUrl = @json(route('artist.bookings.index'));
  var csrf = $('meta[name="csrf-token"]').attr('content');
  var $list = $('#guestSpotsList');
  var $listWrap = $('#guestSpotsListWrap');
  var $empty = $('#guestSpotsEmpty');
  var $count = $('#guestSpotCount');
  var $alert = $('#guestSpotAlert');
  var $formCard = $('#guestSpotFormCard');
  var $formModal = $('#guestSpotFormModal');
  var $availableFields = $('#guestAvailableFields');
  var $toast = $('#gsToast');
  var $toastText = $('#gsToastText');
  var displayGuestSpotsUrl = @json(route('personal-page.display-guest-spots'));
  var dragSrc = null;
  var reorderTimer = null;
  var FORM_MODAL_MS = 250;

  function showToast(message) {
    $toastText.text(message || 'Saved');
    $toast.addClass('show');
    clearTimeout($toast.data('timer'));
    $toast.data('timer', setTimeout(function () { $toast.removeClass('show'); }, 2600));
  }

  function showAlert(message, type) {
    var ok = type === 'success';
    if (ok) {
      $alert.addClass('hidden').text('');
      showToast(message);
      return;
    }
    $alert
      .removeClass('hidden')
      .css({ background: '#FDECEC', color: '#C62828', border: '1px solid #F5C2C2' })
      .text(message);
    clearTimeout($alert.data('timer'));
    $alert.data('timer', setTimeout(function () {
      $alert.addClass('hidden').text('');
    }, 4000));
  }

  function openFormModal() {
    clearTimeout($formModal.data('closeTimer'));
    $formModal.addClass('modal-visible').attr('aria-hidden', 'false');
    requestAnimationFrame(function () { $formModal.addClass('modal-open'); });
    document.body.style.overflow = 'hidden';
  }

  function closeFormModal() {
    $formModal.removeClass('modal-open');
    clearTimeout($formModal.data('closeTimer'));
    $formModal.data('closeTimer', setTimeout(function () {
      $formModal.removeClass('modal-visible').attr('aria-hidden', 'true');
      document.body.style.overflow = '';
    }, FORM_MODAL_MS));
  }

  function clearFieldErrors() {
    [
      'status', 'city', 'country', 'from_date', 'to_date', 'start_time', 'end_time', 'response_deadline', 'buffer_days_before', 'buffer_days_after', 'number_of_spots',
      'guest_studio_name', 'guest_studio_address', 'guest_street_number', 'guest_street_name',
      'guest_studio_city', 'guest_studio_state', 'guest_postal_code', 'guest_studio_country', 'guest_google_maps_link',
    ].forEach(function (field) {
      $('#' + field + '_error').addClass('hidden').text('');
    });
    $('#guestSpotForm input, #guestSpotForm select, #guestSpotForm .bpx-btn').removeClass('border-error');
  }

  function markInputError($input) {
    if (!$input || !$input.length) return;
    $input.addClass('border-error');
    var el = $input.get(0);
    if (el && el.__bpx) {
      $(el.__bpx).addClass('border-error');
    } else {
      $input.next('.bpx-btn').addClass('border-error');
    }
  }

  var fieldErrorMap = {
    status: { error: '#status_error', input: '#guest_status' },
    city: { error: '#city_error', input: '#guest_city' },
    country: { error: '#country_error', input: '#guest_country' },
    from_date: { error: '#from_date_error', input: '#guest_from' },
    to_date: { error: '#to_date_error', input: '#guest_to' },
    start_time: { error: '#start_time_error', input: '#guest_start_time' },
    end_time: { error: '#end_time_error', input: '#guest_end_time' },
    response_deadline: { error: '#response_deadline_error', input: '#guest_response_deadline' },
    response_deadline_unit: { error: '#response_deadline_error', input: '#guest_response_deadline_unit' },
    buffer_days_before: { error: '#buffer_days_before_error', input: '#guest_buffer_days_before' },
    buffer_days_after: { error: '#buffer_days_after_error', input: '#guest_buffer_days_after' },
    number_of_spots: { error: '#number_of_spots_error', input: '#guest_number_of_spots' },
    guest_studio_name: { error: '#guest_studio_name_error', input: '#guest_studio_name' },
    studio_name: { error: '#guest_studio_name_error', input: '#guest_studio_name' },
    guest_studio_address: { error: '#guest_studio_address_error', input: '#guest_address_search' },
    studio_address: { error: '#guest_studio_address_error', input: '#guest_address_search' },
    guest_address_search: { error: '#guest_studio_address_error', input: '#guest_address_search' },
    guest_street_number: { error: '#guest_street_number_error', input: '#guest_street_number' },
    street_number: { error: '#guest_street_number_error', input: '#guest_street_number' },
    guest_street_name: { error: '#guest_street_name_error', input: '#guest_street_name' },
    street_name: { error: '#guest_street_name_error', input: '#guest_street_name' },
    guest_studio_city: { error: '#guest_studio_city_error', input: '#guest_studio_city' },
    guest_studio_state: { error: '#guest_studio_state_error', input: '#guest_studio_state' },
    state: { error: '#guest_studio_state_error', input: '#guest_studio_state' },
    guest_postal_code: { error: '#guest_postal_code_error', input: '#guest_postal_code' },
    postal_code: { error: '#guest_postal_code_error', input: '#guest_postal_code' },
    guest_studio_country: { error: '#guest_studio_country_error', input: '#guest_studio_country' },
    guest_google_maps_link: { error: '#guest_google_maps_link_error', input: '#guest_google_maps_link' },
    google_maps_link: { error: '#guest_google_maps_link_error', input: '#guest_google_maps_link' },
    guest_studio_invite_email: { error: '#guest_studio_invite_email_error', input: '#guest_studio_invite_email' },
    artist_percent: { error: '#guest_you_pct', input: '#guest_you_pct' },
  };

  var fieldErrorOrder = [
    'status', 'city', 'country', 'from_date', 'to_date', 'start_time', 'end_time',
    'guest_studio_name', 'studio_name',
    'guest_studio_address', 'studio_address', 'guest_address_search',
    'guest_street_number', 'street_number', 'guest_street_name', 'street_name',
    'guest_studio_city', 'guest_studio_state', 'state', 'guest_postal_code', 'postal_code', 'guest_studio_country',
    'guest_google_maps_link', 'google_maps_link',
    'buffer_days_before', 'buffer_days_after', 'number_of_spots',
    'response_deadline', 'response_deadline_unit',
    'guest_studio_invite_email', 'artist_percent',
  ];

  function setFieldErrors(errors) {
    clearFieldErrors();
    var firstInput = null;

    fieldErrorOrder.forEach(function (field) {
      if (!errors || !errors[field]) return;
      var msg = (errors[field] && errors[field][0]) || '';
      if (!msg) return;

      var map = fieldErrorMap[field] || {
        error: '#' + field + '_error',
        input: '#' + field,
      };
      var $err = $(map.error);
      var $input = $(map.input);

      if ($err.length) {
        $err.removeClass('hidden').text(msg);
      }
      if ($input.length) {
        markInputError($input);
        if (!firstInput) firstInput = $input;
      }
    });

    // Any remaining unmapped keys
    Object.keys(errors || {}).forEach(function (field) {
      if (fieldErrorOrder.indexOf(field) !== -1) return;
      var msg = (errors[field] && errors[field][0]) || '';
      if (!msg) return;
      var $err = $('#' + field + '_error');
      var $input = $('#' + field).length ? $('#' + field) : $('#guest_' + field);
      if ($err.length) $err.removeClass('hidden').text(msg);
      if ($input.length) {
        markInputError($input);
        if (!firstInput) firstInput = $input;
      }
    });

    scrollToFirstError(firstInput);
  }

  function scrollToFirstError($input) {
    var $target = $input && $input.length
      ? $input
      : $('#guestSpotForm [id$="_error"]').filter(function () {
          return !$(this).hasClass('hidden') && $.trim($(this).text()) !== '';
        }).first();

    if (!$target || !$target.length) return;

    var focusEl = null;
    if ($input && $input.length) {
      var el0 = $input.get(0);
      if (el0 && el0.__bpx) {
        focusEl = el0.__bpx;
        $target = $(focusEl);
      }
    }

    var mb = document.querySelector('#guestSpotFormCard .mb');
    if (mb && $target.length) {
      var box = mb.getBoundingClientRect();
      var el = $target.get(0).getBoundingClientRect();
      mb.scrollTop += (el.top - box.top) - 24;
    }
    try {
      if (focusEl) focusEl.focus();
      else if ($input && $input.length && $input.is('input, select, textarea')) $input.trigger('focus');
    } catch (e) {}
  }

  function syncGuestAvailableFields() {
    var isAvailable = ($('#guest_status').val() || 'available') === 'available';
    // Keep studio / hours / rules visible for Planned; only Available marks them required.
    $availableFields.removeClass('hidden');
    $('#guestStudioCard, #guestRulesCard').removeClass('hidden');
    $('#guestSpotFormCard .gs-req').toggle(isAvailable);
    if (!isAvailable) {
      $('#response_deadline_error, #start_time_error, #end_time_error, #buffer_days_before_error, #buffer_days_after_error, #number_of_spots_error, #guest_studio_name_error, #guest_studio_address_error, #guest_street_number_error, #guest_street_name_error, #guest_studio_city_error, #guest_studio_state_error, #guest_postal_code_error, #guest_studio_country_error, #guest_google_maps_link_error').addClass('hidden').text('');
    }
  }
  window.syncGuestAvailableFields = syncGuestAvailableFields;

  function refreshListVisibility() {
    var n = $list.find('.guest-spot-row').length;
    $count.text(n);
    if (n === 0) {
      $listWrap.addClass('hidden');
      $empty.removeClass('hidden');
    } else {
      $empty.addClass('hidden');
      $listWrap.removeClass('hidden');
    }
  }

  function resourceUrl(id) {
    return storeUrl.replace(/\/?$/, '') + '/' + id;
  }

  function setStatusCards(status) {
    status = status || 'available';
    $('#guestStatusCards .po').removeClass('sel');
    var $card = $('#guestStatusCards .po[data-status="' + status + '"]').addClass('sel');
    $card.find('input[type="radio"]').prop('checked', true);
    $('#guest_status').val(status);
    syncGuestAvailableFields();
  }

  function setFormMode(mode) {
    var editing = mode === 'edit';
    $('#guestFormTitle').text(editing ? 'Edit guest spot' : 'Add guest spot');
    $('#guestFormSubtitle').text(editing ? 'Update this guest spot' : 'New guest spot');
    $('#guestFormIcon').text('flight_takeoff');
    $('#btnCancelGuestSpotEdit').removeClass('hidden');
    $('#btnAddGuestSpot .btn-icon').text('save');
    $('#btnAddGuestSpot .btn-label').text('Save guest spot');
  }

  function showGuestTab(tab) {
    $('#guestSpotTabs button').each(function () {
      var on = $(this).data('gt') === tab;
      $(this).toggleClass('on', on).attr('aria-selected', on ? 'true' : 'false');
    });
    $('#guestSpotFormCard .gtp').each(function () {
      this.hidden = $(this).data('gp') !== tab;
    });
    var mb = document.querySelector('#guestSpotFormCard .mb');
    if (mb) mb.scrollTop = 0;
    if (tab === 'l') syncGuestPreviewLocation();
  }

  function setGuestToggle($el, on) {
    $el.toggleClass('on', !!on).attr('aria-checked', on ? 'true' : 'false');
  }

  function bindGuestPickGrid(opts) {
    var $grid = $(opts.grid);
    var $cnt = $(opts.count);
    var $none = $(opts.none);
    var $all = $(opts.selectAll);
    if (!$grid.length) return;

    function refresh() {
      var $items = $grid.find('.pfp');
      var n = $items.filter('[aria-pressed="true"]').length;
      if ($cnt.length) $cnt.text(n + ' selected');
      if ($none.length) $none.prop('hidden', n > 0);
      if ($all.length) {
        $all.text(n === $items.length && $items.length ? 'Clear all' : 'Select all');
      }
    }

    $grid.on('click', '.pfp', function () {
      var pressed = $(this).attr('aria-pressed') === 'true';
      $(this).attr('aria-pressed', pressed ? 'false' : 'true');
      refresh();
    });

    if ($all.length) {
      $all.on('click', function () {
        var $items = $grid.find('.pfp');
        var allOn = $items.length && $items.filter('[aria-pressed="true"]').length === $items.length;
        $items.attr('aria-pressed', allOn ? 'false' : 'true');
        refresh();
      });
    }

    refresh();
  }

  function resetGuestSectionsUi() {
    setGuestToggle($('#gsTogDesigns'), false);
    setGuestToggle($('#gsTogPortfolio'), false);
    setGuestToggle($('#gsTogRates'), false);
    setGuestToggle($('#gsTogPolicies'), true);
    setGuestToggle($('#gsTogFaq'), true);
    $('#gsDesignsWrap, #gsPortfolioWrap').prop('hidden', true);
    $('#gsDesignsGrid .pfp, #gsPortfolioGrid .pfp').attr('aria-pressed', 'false');
    $('#gsDesignsCnt').text('0 selected');
    $('#gsPortfolioCnt').text('0 selected');
    $('#gsDesignsNone, #gsPortfolioNone').prop('hidden', true);
    $('#gsDesignsSelectAll').text('Select all');
    $('#gsPortfolioSelectAll').text('Select all');
  }

  var GS_DEFAULT_SCHEME = '#4B0F8F,#7B3FC4,#F3E8FF';
  var gsBannerObjectUrl = null;
  var gsBannerDataUrl = null;
  var gsRemoveBanner = false;
  var gsColorScheme = GS_DEFAULT_SCHEME;
  var gsBannerCropper = null;
  var GS_BANNER_W = 1600;
  var GS_BANNER_H = 230;
  var GS_BANNER_RATIO = 1600 / 230;
  var GS_BANNER_MAX_BYTES = 10 * 1024 * 1024;
  var $gsBannerCropModal = $('#gsBannerCropModal');
  var $gsBannerCropperImg = $('#gsBannerCropperImg');

  function applyGuestScheme(scheme) {
    gsColorScheme = String(scheme || GS_DEFAULT_SCHEME);
    var parts = gsColorScheme.split(',');
    var g1 = parts[0] || '#4B0F8F';
    var g2 = parts[1] || g1;
    var root = document.getElementById('guestSpotFormCard');
    if (root) {
      root.style.setProperty('--g1', g1);
      root.style.setProperty('--g2', g2);
    }
    $('#gsCustomSwatch').css('background', g1);
    $('#gsSchemeGrid .lf').each(function () {
      var on = String($(this).data('sch') || '') === gsColorScheme;
      $(this).toggleClass('on', on).attr('aria-pressed', on ? 'true' : 'false');
    });
  }

  function setGuestBannerPreview(url) {
    var $has = $('#gsBannerHas');
    var $none = $('#gsBannerNone');
    var $img = $('#gsBannerImg');
    var $pv = $('#gsPreviewBanner');
    if (url) {
      $img.css('background-image', "url('" + url + "')");
      $has.prop('hidden', false);
      $none.prop('hidden', true);
      $pv.addClass('img').css('background-image', "url('" + url + "')");
      $pv.find('.gsky').hide();
    } else {
      $img.css('background-image', '');
      $has.prop('hidden', true);
      $none.prop('hidden', false);
      $pv.removeClass('img').css('background-image', '');
      $pv.find('.gsky').show();
    }
  }

  function clearGuestBannerObjectUrl() {
    if (gsBannerObjectUrl) {
      try { URL.revokeObjectURL(gsBannerObjectUrl); } catch (e) {}
      gsBannerObjectUrl = null;
    }
  }

  function destroyGuestBannerCropper() {
    if (gsBannerCropper) {
      gsBannerCropper.destroy();
      gsBannerCropper = null;
    }
  }

  function revokeGuestBannerCropBlob() {
    var u = $gsBannerCropperImg.data('blob-url');
    if (u) {
      try { URL.revokeObjectURL(u); } catch (e) {}
      $gsBannerCropperImg.removeData('blob-url');
    }
  }

  function closeGuestBannerCropModal() {
    destroyGuestBannerCropper();
    revokeGuestBannerCropBlob();
    $gsBannerCropperImg.attr('src', '');
    $gsBannerCropModal.removeClass('is-open').attr('aria-hidden', 'true');
    $('#gsBannerCropApply').prop('disabled', false);
  }

  function openGuestBannerCropModal(file) {
    if (!file || !/^image\//.test(file.type || '')) {
      alert('Please choose an image file (JPG, PNG or WebP).');
      return;
    }
    if (file.size > GS_BANNER_MAX_BYTES) {
      alert('Image must be 10MB or smaller.');
      return;
    }
    if (typeof Cropper === 'undefined') {
      alert('Cropper failed to load. Refresh the page and try again.');
      return;
    }
    destroyGuestBannerCropper();
    revokeGuestBannerCropBlob();
    var url = URL.createObjectURL(file);
    $gsBannerCropperImg.data('blob-url', url);
    $gsBannerCropModal.addClass('is-open').attr('aria-hidden', 'false');

    function initCropperOnImg() {
      var img = $gsBannerCropperImg[0];
      if (!img) return;
      destroyGuestBannerCropper();
      gsBannerCropper = new Cropper(img, {
        aspectRatio: GS_BANNER_RATIO,
        viewMode: 1,
        dragMode: 'move',
        autoCropArea: 1,
        responsive: true,
        restore: false,
        guides: true,
        center: true,
        highlight: true,
        background: false,
        movable: true,
        zoomable: true,
        scalable: false,
        rotatable: false
      });
    }

    var cropInitDone = false;
    function startCropperOnce() {
      if (cropInitDone) return;
      cropInitDone = true;
      $gsBannerCropperImg.off('load.gsbcrop error.gsbcrop');
      initCropperOnImg();
    }

    $gsBannerCropperImg.off('load.gsbcrop error.gsbcrop');
    $gsBannerCropperImg.on('load.gsbcrop', startCropperOnce);
    $gsBannerCropperImg.on('error.gsbcrop', function () {
      $gsBannerCropperImg.off('load.gsbcrop error.gsbcrop');
      closeGuestBannerCropModal();
      alert('Could not open that image. Try another file.');
    });
    $gsBannerCropperImg.attr('src', url);
    var imgEl = $gsBannerCropperImg[0];
    if (imgEl && imgEl.complete && imgEl.naturalWidth > 0) {
      startCropperOnce();
    }
  }

  function applyGuestBannerCrop() {
    if (!gsBannerCropper) return;
    var $btn = $('#gsBannerCropApply');
    $btn.prop('disabled', true);
    var canvas = gsBannerCropper.getCroppedCanvas({
      width: GS_BANNER_W,
      height: GS_BANNER_H,
      fillColor: '#ffffff',
      imageSmoothingEnabled: true,
      imageSmoothingQuality: 'high'
    });
    if (!canvas) {
      $btn.prop('disabled', false);
      alert('Could not read the crop. Try again.');
      return;
    }
    var dataUrl = canvas.toDataURL('image/jpeg', 0.92);
    canvas.toBlob(function (blob) {
      $btn.prop('disabled', false);
      if (!blob) {
        alert('Could not crop this image. Try another one.');
        return;
      }
      clearGuestBannerObjectUrl();
      gsBannerObjectUrl = URL.createObjectURL(blob);
      gsBannerDataUrl = dataUrl;
      gsRemoveBanner = false;
      setGuestBannerPreview(gsBannerObjectUrl);
      closeGuestBannerCropModal();
      $('#gsBannerFile').val('');
    }, 'image/jpeg', 0.92);
  }

  function resetGuestAppearanceUi() {
    closeGuestBannerCropModal();
    clearGuestBannerObjectUrl();
    gsBannerDataUrl = null;
    gsRemoveBanner = false;
    $('#gsBannerFile').val('');
    setGuestBannerPreview(null);
    applyGuestScheme(GS_DEFAULT_SCHEME);
    $('#gsCustomColor').val('#7A1FC4');
    $('#gsCustomSwatch').css('background', '#7A1FC4');
    syncGuestPreviewLocation();
  }

  function collectGuestExtendedPayload() {
    var payoutMode = $('input[name="guest_po"]:checked').val() === 'split' ? 'split' : 'direct';
    var showDesigns = $('#gsTogDesigns').hasClass('on');
    var showPortfolio = $('#gsTogPortfolio').hasClass('on');
    var designIds = [];
    var portfolioIds = [];
    if (showDesigns) {
      $('#gsDesignsGrid .pfp[aria-pressed="true"]').each(function () {
        var id = parseInt($(this).data('id'), 10);
        if (id) designIds.push(id);
      });
    }
    if (showPortfolio) {
      $('#gsPortfolioGrid .pfp[aria-pressed="true"]').each(function () {
        var id = parseInt($(this).data('id'), 10);
        if (id) portfolioIds.push(id);
      });
    }

    var payload = {
      payout_mode: payoutMode,
      artist_percent: payoutMode === 'split' ? String(parsePositiveInt($('#guest_you_pct').val(), 60)) : '',
      guest_studio_invite_email: payoutMode === 'split' ? $.trim($('#guest_studio_invite_email').val()) : '',
      show_designs: showDesigns ? 1 : 0,
      show_portfolio: showPortfolio ? 1 : 0,
      show_rates: $('#gsTogRates').hasClass('on') ? 1 : 0,
      show_policies: $('#gsTogPolicies').hasClass('on') ? 1 : 0,
      show_faq: $('#gsTogFaq').hasClass('on') ? 1 : 0,
      selected_design_ids: designIds,
      selected_portfolio_ids: portfolioIds,
      color_scheme: gsColorScheme || GS_DEFAULT_SCHEME,
      remove_banner: gsRemoveBanner ? 1 : 0,
      banner: gsBannerDataUrl || '',
    };

    return payload;
  }

  function applyGuestExtendedFields(spot) {
    if (!spot) return;

    var payoutMode = spot.payout_mode === 'split' ? 'split' : 'direct';
    setGuestPayoutMode(payoutMode);
    if (spot.artist_percent != null) {
      $('#guest_you_pct').val(spot.artist_percent);
      syncGuestThemPct();
    }
    $('#guest_studio_invite_email').val(spot.guest_studio_invite_email || '');

    setGuestToggle($('#gsTogDesigns'), !!spot.show_designs);
    setGuestToggle($('#gsTogPortfolio'), !!spot.show_portfolio);
    setGuestToggle($('#gsTogRates'), !!spot.show_rates);
    setGuestToggle($('#gsTogPolicies'), spot.show_policies !== false);
    setGuestToggle($('#gsTogFaq'), spot.show_faq !== false);
    $('#gsDesignsWrap').prop('hidden', !spot.show_designs);
    $('#gsPortfolioWrap').prop('hidden', !spot.show_portfolio);

    var designIds = (spot.selected_design_ids || []).map(Number);
    var portfolioIds = (spot.selected_portfolio_ids || []).map(Number);
    $('#gsDesignsGrid .pfp').each(function () {
      var id = parseInt($(this).data('id'), 10);
      $(this).attr('aria-pressed', designIds.indexOf(id) > -1 ? 'true' : 'false');
    });
    $('#gsPortfolioGrid .pfp').each(function () {
      var id = parseInt($(this).data('id'), 10);
      $(this).attr('aria-pressed', portfolioIds.indexOf(id) > -1 ? 'true' : 'false');
    });
    var dSel = $('#gsDesignsGrid .pfp[aria-pressed="true"]').length;
    var pSel = $('#gsPortfolioGrid .pfp[aria-pressed="true"]').length;
    var dTotal = $('#gsDesignsGrid .pfp').length;
    var pTotal = $('#gsPortfolioGrid .pfp').length;
    $('#gsDesignsCnt').text(dSel + ' selected');
    $('#gsPortfolioCnt').text(pSel + ' selected');
    $('#gsDesignsNone').prop('hidden', dSel > 0);
    $('#gsPortfolioNone').prop('hidden', pSel > 0);
    $('#gsDesignsSelectAll').text(dSel === dTotal && dTotal ? 'Clear all' : 'Select all');
    $('#gsPortfolioSelectAll').text(pSel === pTotal && pTotal ? 'Clear all' : 'Select all');

    clearGuestBannerObjectUrl();
    gsBannerDataUrl = null;
    gsRemoveBanner = false;
    if (spot.banner_url) {
      setGuestBannerPreview(spot.banner_url);
    } else {
      setGuestBannerPreview(null);
    }

    var scheme = spot.color_scheme || GS_DEFAULT_SCHEME;
    applyGuestScheme(scheme);
    var first = String(scheme).split(',')[0] || '#7A1FC4';
    if (/^#[0-9A-Fa-f]{6}$/.test(first)) {
      $('#gsCustomColor').val(first);
      $('#gsCustomSwatch').css('background', first);
    }
    syncGuestPreviewLocation();
  }

  function syncGuestPreviewLocation() {
    var city = $.trim($('#guest_city').val() || '');
    var from = $('#guest_from').val() || '';
    var to = $('#guest_to').val() || '';
    var label = 'Guest spot in your city, with your dates';
    if (city && from && to) {
      label = 'Guest spot in ' + city + ', ' + formatPreviewDate(from) + ' – ' + formatPreviewDate(to);
    } else if (city) {
      label = 'Guest spot in ' + city + ', with your dates';
    }
    $('#gsPreviewLocation').text(label);
  }

  function formatPreviewDate(iso) {
    var m = /^(\d{4})-(\d\d)-(\d\d)$/.exec(iso || '');
    if (!m) return iso;
    var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    return months[+m[2] - 1] + ' ' + (+m[3]) + ', ' + m[1];
  }

  $('#guestSpotFormCard').on('click', '.tog[role="switch"]', function (e) {
    e.preventDefault();
    var $tog = $(this);
    var on = !$tog.hasClass('on');
    setGuestToggle($tog, on);
    if ($tog.attr('id') === 'gsTogDesigns') $('#gsDesignsWrap').prop('hidden', !on);
    if ($tog.attr('id') === 'gsTogPortfolio') $('#gsPortfolioWrap').prop('hidden', !on);
  }).on('keydown', '.tog[role="switch"]', function (e) {
    if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault();
      $(this).trigger('click');
    }
  });

  bindGuestPickGrid({
    grid: '#gsDesignsGrid',
    count: '#gsDesignsCnt',
    none: '#gsDesignsNone',
    selectAll: '#gsDesignsSelectAll',
  });
  bindGuestPickGrid({
    grid: '#gsPortfolioGrid',
    count: '#gsPortfolioCnt',
    none: '#gsPortfolioNone',
    selectAll: '#gsPortfolioSelectAll',
  });

  $('#gsSchemeGrid').on('click', '.lf', function () {
    applyGuestScheme(String($(this).data('sch') || GS_DEFAULT_SCHEME));
  });

  $('#gsCustomColor').on('input change', function () {
    $('#gsCustomSwatch').css('background', this.value);
  });

  $('#gsUseCustomColor').on('click', function () {
    var hex = $('#gsCustomColor').val() || '#7A1FC4';
    applyGuestScheme(hex + ',' + hex + ',#F3E8FF');
    $('#gsSchemeGrid .lf').removeClass('on').attr('aria-pressed', 'false');
  });

  $('#gsResetLook').on('click', function () {
    resetGuestAppearanceUi();
  });

  $('#gsBannerNone, #gsBannerChange').on('click', function () {
    $('#gsBannerFile').trigger('click');
  });

  $('#gsBannerRemove').on('click', function () {
    clearGuestBannerObjectUrl();
    gsBannerDataUrl = null;
    gsRemoveBanner = true;
    $('#gsBannerFile').val('');
    setGuestBannerPreview(null);
  });

  $('#gsBannerFile').on('change', function () {
    var file = this.files && this.files[0];
    this.value = '';
    if (!file) return;
    openGuestBannerCropModal(file);
  });

  $('#gsBannerCropClose, #gsBannerCropCancel').on('click', closeGuestBannerCropModal);
  $('#gsBannerCropApply').on('click', applyGuestBannerCrop);
  $gsBannerCropModal.on('click', function (e) {
    if (e.target === this) closeGuestBannerCropModal();
  });
  $(document).on('keydown.gsBannerCrop', function (e) {
    if (e.key === 'Escape' && $gsBannerCropModal.hasClass('is-open')) {
      e.preventDefault();
      closeGuestBannerCropModal();
    }
  });

  $('#guest_city, #guest_from, #guest_to').on('input change', syncGuestPreviewLocation);

  applyGuestScheme(GS_DEFAULT_SCHEME);
  syncGuestPreviewLocation();

  function guestStudioLabel() {
    var name = $.trim($('#guest_studio_name').val() || '');
    return name || 'the guest studio';
  }

  function syncGuestPayoutStudioNames() {
    var name = guestStudioLabel();
    var cap = name.charAt(0).toUpperCase() + name.slice(1);
    $('#guestSplitHelpOn').html(
      '<span class="gs-studio-name">' + $('<div>').text(name).html() + '</span> is on Bookpay, so they confirm the split in their dashboard. No invite needed.'
    );
    $('#guestSplitCurText').html(
      'If <span class="gs-studio-name">' + $('<div>').text(name).html() + '</span> is paid in another currency, their share is converted when it’s paid out. They pay the conversion fee.'
    );
    $('#guestSplitInvite .help').html(
      '<span class="gs-studio-name">' + $('<div>').text(cap === 'The guest studio' ? cap : name).html() + '</span> isn’t on Bookpay yet — we’ll email them an invite to join. You’re paid directly until they join and confirm the split.'
    );
  }

  function syncGuestThemPct() {
    var v = Math.max(0, Math.min(100, parseInt($('#guest_you_pct').val(), 10) || 0));
    $('#guest_them_pct').text((100 - v) + '%');
  }

  function setGuestPayoutMode(mode) {
    mode = mode === 'split' ? 'split' : 'direct';
    $('#guestPayoutBlock .po[data-po]').each(function () {
      var on = $(this).data('po') === mode;
      $(this).toggleClass('sel', on);
      $(this).find('input[type="radio"]').prop('checked', on);
    });
    var box = document.getElementById('guestSplitbox');
    if (box) box.hidden = mode !== 'split';
    // Ask for studio email whenever revenue split is selected (invite flow).
    var invite = document.getElementById('guestSplitInvite');
    if (invite) invite.hidden = mode !== 'split';
    var onBookpay = document.getElementById('guestSplitHelpOn');
    if (onBookpay) onBookpay.hidden = true;
    if (mode === 'split') {
      syncGuestThemPct();
      syncGuestPayoutStudioNames();
    } else {
      $('#guest_studio_invite_email_error').addClass('hidden').text('');
      $('#guest_studio_invite_email').removeClass('border-error');
    }
  }

  function resetGuestPayoutUi() {
    setGuestPayoutMode('direct');
    $('#guest_you_pct').val(60);
    $('#guest_them_pct').text('40%');
    $('#guest_studio_invite_email').val('');
    $('#guest_studio_invite_email_error').addClass('hidden').text('');
    $('#guest_studio_invite_email').removeClass('border-error');
    syncGuestPayoutStudioNames();
  }

  $('#guestPayoutBlock').on('change', 'input[name="guest_po"]', function () {
    setGuestPayoutMode(this.value);
  });

  $('#guestPayoutBlock').on('click', '.po[data-po]', function () {
    setGuestPayoutMode($(this).data('po'));
  });

  $('#guest_you_pct').on('input', syncGuestThemPct);
  $('#guest_studio_name').on('input', syncGuestPayoutStudioNames);
  $('#guest_studio_invite_email').on('input', function () {
    $('#guest_studio_invite_email_error').addClass('hidden').text('');
    $(this).removeClass('border-error');
  });

  function statusPillClass(displayKey) {
    if (displayKey === 'available') return 'g available';
    if (displayKey === 'planned') return 'b planned';
    if (displayKey === 'full') return 'a full';
    return 'k completed';
  }

  function fillRow($row, spot) {
    var displayKey = spot.display_status || spot.status || '';
    var statusLabel = spot.display_status_label
      || (spot.status ? spot.status.charAt(0).toUpperCase() + spot.status.slice(1) : '');
    $row.attr({
      'data-id': spot.id,
      'data-status': spot.status,
      'data-city': spot.city,
      'data-country': spot.country,
      'data-from': spot.from_date,
      'data-to': spot.to_date,
      'data-spot': JSON.stringify(spot),
      'data-update-url': resourceUrl(spot.id),
      'data-delete-url': resourceUrl(spot.id),
    });
    $row.find('.status-badge')
      .removeClass('available planned full completed g b a k')
      .addClass(statusPillClass(displayKey))
      .text(statusLabel);
    $row.find('.guest-spot-city').text(spot.city);
    $row.find('.guest-spot-country').text(spot.country);
    $row.find('.guest-spot-from').text(spot.from_label);
    $row.find('.guest-spot-to').text(spot.to_label);
    syncRowActions($row, displayKey);
    fillRowDetails($row, spot);
  }

  function syncRowActions($row, displayKey) {
    var $actions = $row.find('.guest-spot-actions');
    if (!$actions.length) return;
    if (displayKey === 'completed') {
      $actions.empty();
      return;
    }
    if ($actions.find('.guest-spot-edit').length) return;
    $actions.html(
      '<button type="button" class="guest-spot-action guest-spot-edit" title="Edit" aria-label="Edit guest location"><span class="ms">edit</span></button>' +
      '<button type="button" class="guest-spot-action guest-spot-delete" title="Delete" aria-label="Remove guest location"><span class="ms">delete</span></button>'
    );
  }

  function detailItemHtml(icon, text, bold) {
    return (
      '<span class="guest-spot-detail-item gsd">' +
        '<span class="ms">' + icon + '</span>' +
        (bold ? '<b>' : '<span>') + $('<div>').text(text).html() + (bold ? '</b>' : '</span>') +
      '</span>'
    );
  }

  function guestSpotPageUrl(spotId) {
    if (!guestSpotLinkBase || !spotId) return '';
    return guestSpotLinkBase + '?guest_spot=' + encodeURIComponent(spotId);
  }

  function guestSpotPageUrlDisplay(url) {
    return String(url || '').replace(/^https?:\/\//, '');
  }

  function guestSpotActionsHtml(spot) {
    var displayKey = (spot && (spot.display_status || spot.status)) || '';
    var status = (spot && spot.status) || '';
    var html = [];

    if (displayKey === 'available' || status === 'available') {
      var pageUrl = guestSpotPageUrl(spot.id);
      if (pageUrl) {
        var safeUrl = $('<div>').text(pageUrl).html();
        var display = $('<div>').text(guestSpotPageUrlDisplay(pageUrl)).html();
        html.push(
          '<span class="gsacts">' +
            '<a class="btn sm ghost gsview" href="' + safeUrl + '" target="_blank" rel="noopener">' +
              '<span class="ms">open_in_new</span>View page' +
            '</a>' +
            '<button type="button" class="btn sm ghost gscopy js-copy-guest-spot-link" data-url="' + safeUrl + '">' +
              '<span class="ms">link</span><span class="guest-spot-copy-label">Copy link</span>' +
            '</button>' +
            '<a class="btn sm ghost gsbk" href="' + $('<div>').text(guestSpotBookingsUrl).html() + '">' +
              '<span class="ms">calendar_month</span>View bookings' +
            '</a>' +
          '</span>'
        );
        html.push('<span class="gsurl"><span class="ms">public</span>' + display + '</span>');
      }
    } else if (displayKey === 'completed' || displayKey === 'full' || status === 'completed') {
      html.push(
        '<span class="gsacts">' +
          '<a class="btn sm ghost gsbk" href="' + $('<div>').text(guestSpotBookingsUrl).html() + '">' +
            '<span class="ms">calendar_month</span>View bookings' +
          '</a>' +
        '</span>'
      );
    }

    return html.join('');
  }

  function fillRowDetails($row, spot) {
    var $details = $row.find('.guest-spot-details');
    if (!$details.length) return;

    var displayKey = (spot && (spot.display_status || spot.status)) || '';
    var status = (spot && spot.status) || '';
    var canShowDetails = spot && (
      status === 'available' ||
      status === 'planned' ||
      status === 'completed' ||
      displayKey === 'completed' ||
      displayKey === 'full' ||
      !!spot.has_list_details
    );

    if (!canShowDetails) {
      $details.addClass('hidden').empty();
      return;
    }

    var html = [];
    if (spot.list_availability_time) {
      html.push(detailItemHtml('schedule', spot.list_availability_time));
    }
    if (spot.list_studio) {
      html.push(detailItemHtml('storefront', spot.list_studio, true));
    }
    if (spot.list_location) {
      html.push(detailItemHtml('location_on', spot.list_location));
    }
    if (spot.list_buffer) {
      html.push(detailItemHtml('event_busy', spot.list_buffer));
    }
    if (spot.list_remaining_spots) {
      html.push(detailItemHtml('event_seat', spot.list_remaining_spots));
    }
    if (spot.list_payout) {
      html.push(detailItemHtml('payments', spot.list_payout));
    }

    html.push(guestSpotActionsHtml(spot));

    if (status === 'planned' || displayKey === 'planned') {
      var city = spot.city || 'this city';
      html.push(
        '<span class="gsnote"><span class="ms">info</span>Planned guest spots have no page yet. Clients can tap Notify on your page to hear when ' +
        $('<div>').text(city).html() + ' opens.</span>'
      );
    }

    if (!html.join('').trim()) {
      $details.addClass('hidden').empty();
      return;
    }

    $details.removeClass('hidden').addClass('guest-spot-details gsdet').html(html.join(''));
  }

  function buildRow(spot) {
    var displayKey = spot.display_status || spot.status || '';
    var actionsHtml = displayKey === 'completed'
      ? ''
      : (
        '<button type="button" class="guest-spot-action guest-spot-edit" title="Edit" aria-label="Edit guest location"><span class="ms">edit</span></button>' +
        '<button type="button" class="guest-spot-action guest-spot-delete" title="Delete" aria-label="Remove guest location"><span class="ms">delete</span></button>'
      );
    var $row = $(
      '<div class="guest-spot-row gsrow" draggable="true">' +
        '<div class="gsmain">' +
          '<span class="ms gsdrag" title="Drag to reorder">drag_indicator</span>' +
          '<span class="gscell"><span class="status-badge pill nd"></span></span>' +
          '<b class="gscell gscity guest-spot-city"></b>' +
          '<span class="gscell guest-spot-country"></span>' +
          '<span class="gscell guest-spot-from"></span>' +
          '<span class="gscell guest-spot-to"></span>' +
          '<span class="gsact guest-spot-actions">' + actionsHtml + '</span>' +
        '</div>' +
        '<div class="guest-spot-details gsdet hidden"></div>' +
      '</div>'
    );
    fillRow($row, spot);
    return $row;
  }

  function populateAvailableFields(spot) {
    if (!spot) return;
    $('#guest_response_deadline').val(spot.response_deadline != null ? spot.response_deadline : '');
    $('#guest_response_deadline_unit').val(spot.response_deadline_unit || 'hours');
    $('#guest_start_time').val(spot.start_time || '');
    $('#guest_end_time').val(spot.end_time || '');
    $('#guest_buffer_days_before').val(formatBufferDaysLabel(spot.buffer_days_before));
    $('#guest_buffer_days_after').val(formatBufferDaysLabel(spot.buffer_days_after));
    $('#guest_number_of_spots').val(
      spot.number_of_spots != null && Number(spot.number_of_spots) > 0
        ? spot.number_of_spots
        : ''
    );
    $('#guest_studio_name').val(spot.guest_studio_name || '');
    $('#guest_address_search').val(spot.guest_studio_address || '');
    $('#guest_studio_address').val(spot.guest_studio_address || '');
    $('#guest_street_number').val(spot.guest_street_number || '');
    $('#guest_street_name').val(spot.guest_street_name || '');
    $('#guest_studio_city').val(spot.guest_studio_city || '');
    $('#guest_studio_state').val(spot.guest_studio_state || '');
    $('#guest_postal_code').val(spot.guest_postal_code || '');
    $('#guest_studio_country').val(spot.guest_studio_country || '');
    $('#guest_google_maps_link').val(spot.guest_google_maps_link || '');
    $('#guest_latitude').val(spot.guest_latitude != null ? spot.guest_latitude : '');
    $('#guest_longitude').val(spot.guest_longitude != null ? spot.guest_longitude : '');
  }

  function resetAvailableFields() {
    $('#guest_response_deadline').val('');
    $('#guest_response_deadline_unit').val('hours');
    $('#guest_start_time, #guest_end_time').val('');
    $('#guest_buffer_days_before').val('');
    $('#guest_buffer_days_after').val('');
    $('#guest_number_of_spots').val('');
    $('#guest_studio_name').val('');
    $('#guest_address_search').val('').removeClass('border-error');
    $('#guest_studio_address').val('');
    $('#guest_latitude, #guest_longitude').val('');
    $('#guest_street_number, #guest_street_name, #guest_studio_city, #guest_studio_state, #guest_postal_code, #guest_studio_country, #guest_google_maps_link').val('');
    $('#response_deadline_error, #start_time_error, #end_time_error, #buffer_days_before_error, #buffer_days_after_error, #number_of_spots_error, #guest_studio_name_error, #guest_studio_address_error, #guest_street_number_error, #guest_street_name_error, #guest_studio_city_error, #guest_studio_state_error, #guest_postal_code_error, #guest_studio_country_error, #guest_google_maps_link_error').addClass('hidden').text('');
  }

  function resetForm(options) {
    options = options || {};
    $('#guest_editing_id').val('');
    $('#guest_city').val('');
    $('#guest_country').val('');
    $('#guest_from').val('');
    $('#guest_to').val('');
    resetAvailableFields();
    resetGuestPayoutUi();
    resetGuestSectionsUi();
    resetGuestAppearanceUi();
    setStatusCards('available');
    clearFieldErrors();
    setFormMode('add');
    $list.find('.guest-spot-row').removeClass('is-editing');
    if (options.close !== false) {
      closeFormModal();
    }
  }

  function enterEditMode($row) {
    var id = String($row.data('id') || '');
    var status = $row.attr('data-status') || 'available';
    var spot = null;
    try {
      spot = JSON.parse($row.attr('data-spot') || 'null');
    } catch (e) {
      spot = null;
    }
    $('#guest_editing_id').val(id);
    $('#guest_city').val($row.attr('data-city') || '');
    $('#guest_country').val($row.attr('data-country') || '');
    $('#guest_from').val($row.attr('data-from') || '');
    $('#guest_to').val($row.attr('data-to') || '');
    resetAvailableFields();
    resetGuestPayoutUi();
    resetGuestSectionsUi();
    resetGuestAppearanceUi();
    setStatusCards(status);
    if (spot) {
      populateAvailableFields(spot);
    }
    applyGuestExtendedFields(spot || {});
    syncGuestPayoutStudioNames();
    syncGuestPreviewLocation();
    clearFieldErrors();
    setFormMode('edit');
    $list.find('.guest-spot-row').removeClass('is-editing');
    $row.addClass('is-editing');
    showGuestTab('d');
    openFormModal();
    setTimeout(function () { $('#guest_city').trigger('focus'); }, 50);
  }

  function persistOrder() {
    var ids = $list.find('.guest-spot-row').map(function () {
      return parseInt($(this).data('id'), 10);
    }).get().filter(Boolean);

    if (!ids.length) return;

    $.ajax({
      url: reorderUrl,
      method: 'POST',
      data: { ids: ids, _token: csrf },
      error: function (xhr) {
        var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'Could not save order.';
        showAlert(msg, 'error');
      }
    });
  }

  function scheduleReorderPersist() {
    clearTimeout(reorderTimer);
    reorderTimer = setTimeout(persistOrder, 250);
  }

  $list.on('dragstart', '.guest-spot-row', function (e) {
    dragSrc = this;
    $(this).addClass('dragging');
    e.originalEvent.dataTransfer.effectAllowed = 'move';
    e.originalEvent.dataTransfer.setData('text/plain', '');
  });

  $list.on('dragend', '.guest-spot-row', function () {
    $(this).removeClass('dragging');
    $list.find('.guest-spot-row').removeClass('drag-over-top drag-over-bottom');
    dragSrc = null;
  });

  $list.on('dragover', '.guest-spot-row', function (e) {
    e.preventDefault();
    if (!dragSrc || dragSrc === this) return;
    var rect = this.getBoundingClientRect();
    var mid = rect.top + rect.height / 2;
    var before = e.originalEvent.clientY < mid;
    $(this).toggleClass('drag-over-top', before).toggleClass('drag-over-bottom', !before);
  });

  $list.on('dragleave', '.guest-spot-row', function () {
    $(this).removeClass('drag-over-top drag-over-bottom');
  });

  $list.on('drop', '.guest-spot-row', function (e) {
    e.preventDefault();
    if (!dragSrc || dragSrc === this) return;
    var rect = this.getBoundingClientRect();
    var mid = rect.top + rect.height / 2;
    var before = e.originalEvent.clientY < mid;
    if (before) {
      $(this).before(dragSrc);
    } else {
      $(this).after(dragSrc);
    }
    $list.find('.guest-spot-row').removeClass('drag-over-top drag-over-bottom');
    scheduleReorderPersist();
  });

  var $deleteModal = $('#deleteGuestSpotModal');
  var MODAL_MS = 350;
  var pendingDelete = null;

  function openDeleteModal($row) {
    var city = $row.attr('data-city') || '';
    var country = $row.attr('data-country') || '';
    var label = [city, country].filter(Boolean).join(', ') || 'this guest location';
    pendingDelete = {
      url: $row.attr('data-delete-url'),
      id: String($row.data('id') || ''),
      $row: $row,
    };
    $('#deleteGuestSpotLabel').text(label);
    $('#deleteGuestSpotError').addClass('hidden').text('');
    $('#btnDeleteGuestSpotConfirm').prop('disabled', false);
    $('#btnDeleteGuestSpotConfirm .confirm-delete-icon').text('delete');
    $('#btnDeleteGuestSpotConfirm .confirm-delete-label').text('Delete');
    clearTimeout($deleteModal.data('closeTimer'));
    $deleteModal.addClass('modal-visible').attr('aria-hidden', 'false');
    requestAnimationFrame(function () {
      $deleteModal.addClass('modal-open');
    });
  }

  function closeDeleteModal() {
    $deleteModal.removeClass('modal-open');
    clearTimeout($deleteModal.data('closeTimer'));
    var t = setTimeout(function () {
      $deleteModal.removeClass('modal-visible').attr('aria-hidden', 'true');
      pendingDelete = null;
    }, MODAL_MS);
    $deleteModal.data('closeTimer', t);
  }

  $list.on('click', '.guest-spot-edit', function () {
    enterEditMode($(this).closest('.guest-spot-row'));
  });

  $list.on('click', '.guest-spot-delete', function () {
    var $row = $(this).closest('.guest-spot-row');
    if (!$row.attr('data-delete-url')) return;
    openDeleteModal($row);
  });

  $('#btnDeleteGuestSpotCancel').on('click', closeDeleteModal);
  $deleteModal.on('click', function (e) {
    if (e.target === this) closeDeleteModal();
  });
  $deleteModal.find('.delete-guest-modal-inner').on('click', function (e) {
    e.stopPropagation();
  });

  $('#btnDeleteGuestSpotConfirm').on('click', function () {
    if (!pendingDelete || !pendingDelete.url) return;
    var $btn = $(this);
    var url = pendingDelete.url;
    var id = pendingDelete.id;
    var $row = pendingDelete.$row;
    var editingId = String($('#guest_editing_id').val() || '');

    $btn.prop('disabled', true);
    $btn.find('.confirm-delete-label').text('Deleting…');
    $('#deleteGuestSpotError').addClass('hidden').text('');

    $.ajax({
      url: url,
      method: 'POST',
      data: { _token: csrf, _method: 'DELETE' },
      success: function (res) {
        if (editingId && editingId === id) {
          resetForm();
        }
        if ($row && $row.length) {
          $row.remove();
        }
        refreshListVisibility();
        closeDeleteModal();
        showAlert((res && res.message) || 'Guest location removed.', 'success');
      },
      error: function (xhr) {
        $btn.prop('disabled', false);
        $btn.find('.confirm-delete-icon').text('delete');
        $btn.find('.confirm-delete-label').text('Delete');
        var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'Could not remove location.';
        $('#deleteGuestSpotError').removeClass('hidden').text(msg);
      }
    });
  });

  $(document).on('keydown', function (e) {
    if (e.key !== 'Escape') return;
    if ($deleteModal.hasClass('modal-open')) {
      closeDeleteModal();
      return;
    }
    if ($formModal.hasClass('modal-open')) {
      resetForm();
    }
  });

  $('#btnOpenAddGuestSpot').on('click', openAddForm);
  $('#btnCancelGuestSpotEdit, #btnCloseGuestSpotForm').on('click', function () {
    resetForm();
  });
  $formModal.on('click', function (e) {
    if (e.target === this) resetForm();
  });

  $('#toggleDisplayGuestSpots').on('click', function () {
    var $t = $(this);
    var wasOn = $t.hasClass('on');
    $t.toggleClass('on');
    var nowOn = $t.hasClass('on');
    $t.attr('aria-checked', nowOn ? 'true' : 'false');
    $t.css({ opacity: 0.6, pointerEvents: 'none' });
    $.ajax({
      url: displayGuestSpotsUrl,
      method: 'POST',
      data: {
        _token: csrf,
        display_guest_spots: nowOn ? 1 : 0,
      },
      success: function (res) {
        showToast((res && res.message) || 'Saved.');
      },
      error: function () {
        if (wasOn) $t.addClass('on');
        else $t.removeClass('on');
        $t.attr('aria-checked', wasOn ? 'true' : 'false');
        showAlert('Could not update “Show on my page”. Please try again.', 'error');
      },
      complete: function () {
        $t.css({ opacity: '', pointerEvents: '' });
      }
    });
  });

  $('#guestSpotForm').on('submit', function (e) {
    e.preventDefault();
    clearFieldErrors();

    var $btn = $('#btnAddGuestSpot');
    if ($btn.prop('disabled')) return;

    var editingId = $.trim($('#guest_editing_id').val());
    var isEdit = !!editingId;
    var payload = {
      _token: csrf,
      status: $('#guest_status').val(),
      city: $.trim($('#guest_city').val()),
      country: $.trim($('#guest_country').val()),
      from_date: $('#guest_from').val(),
      to_date: $('#guest_to').val(),
    };

    if (!$.trim($('#guest_studio_address').val())) {
      $('#guest_studio_address').val($.trim($('#guest_address_search').val()));
    }
    Object.assign(payload, {
      start_time: $('#guest_start_time').val(),
      end_time: $('#guest_end_time').val(),
      response_deadline: (function () {
        var raw = $.trim($('#guest_response_deadline').val());
        if (!raw) return '';
        var n = parsePositiveInt(raw, NaN);
        return isNaN(n) ? raw : String(n);
      })(),
      response_deadline_unit: $('#guest_response_deadline_unit').val(),
      buffer_days_before: String(parseBufferDays($('#guest_buffer_days_before').val())),
      buffer_days_after: String(parseBufferDays($('#guest_buffer_days_after').val())),
      number_of_spots: String(parseBufferDays($('#guest_number_of_spots').val())),
      guest_studio_name: $.trim($('#guest_studio_name').val()),
      guest_studio_address: $.trim($('#guest_studio_address').val()),
      guest_street_number: $.trim($('#guest_street_number').val()),
      guest_street_name: $.trim($('#guest_street_name').val()),
      guest_studio_city: $.trim($('#guest_studio_city').val()),
      guest_studio_state: $.trim($('#guest_studio_state').val()),
      guest_postal_code: $.trim($('#guest_postal_code').val()),
      guest_studio_country: $.trim($('#guest_studio_country').val()),
      guest_google_maps_link: $.trim($('#guest_google_maps_link').val()),
      guest_latitude: $.trim($('#guest_latitude').val()),
      guest_longitude: $.trim($('#guest_longitude').val()),
    });

    Object.assign(payload, collectGuestExtendedPayload());

    if (payload.payout_mode === 'split' && !$.trim(payload.guest_studio_invite_email || '')) {
      setFieldErrors({
        guest_studio_invite_email: ['Enter the guest studio email to invite them for a revenue split.'],
      });
      showGuestTab('d');
      return;
    }

    if (isEdit) {
      payload._method = 'PUT';
    }

    $btn.prop('disabled', true);
    $btn.find('.btn-label').text(isEdit ? 'Saving…' : 'Saving…');

    $.ajax({
      url: isEdit ? resourceUrl(editingId) : storeUrl,
      method: 'POST',
      data: payload,
      success: function (res) {
        if (!(res && res.guest_spot)) return;
        if (isEdit) {
          var $row = $list.find('.guest-spot-row[data-id="' + editingId + '"]');
          if ($row.length) {
            fillRow($row, res.guest_spot);
          }
          showAlert(res.message || 'Guest location updated.', 'success');
          promptGuestWaitlistNotify(res.guest_waitlist_notify);
        } else {
          $list.append(buildRow(res.guest_spot));
          refreshListVisibility();
          showAlert(res.message || 'Guest location added.', 'success');
        }
        resetForm();
      },
      error: function (xhr) {
        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
          setFieldErrors(xhr.responseJSON.errors);
        } else {
          var msg = (xhr.responseJSON && xhr.responseJSON.message) || (isEdit ? 'Could not update location.' : 'Could not add location.');
          showAlert(msg, 'error');
        }
      },
      complete: function () {
        $btn.prop('disabled', false);
        var stillEditing = !!$.trim($('#guest_editing_id').val());
        $btn.find('.btn-label').text('Save guest spot');
      }
    });
  });

  $('#guestSpotTabs').on('click', 'button[data-gt]', function () {
    showGuestTab(String($(this).data('gt') || 'd'));
  });

  function openAddForm() {
    resetForm({ close: false });
    showGuestTab('d');
    openFormModal();
    setTimeout(function () { $('#guest_city').trigger('focus'); }, 50);
  }

  syncGuestAvailableFields();

@if(config('services.google.place_api_key'))
  (function initGuestStudioAddressAutocomplete() {
    var input = document.getElementById('guest_address_search');
    if (!input || typeof google === 'undefined' || !google.maps || !google.maps.places) return;
    var ac = new google.maps.places.Autocomplete(input, {
      types: ['address'],
      fields: ['address_components', 'formatted_address', 'place_id', 'geometry'],
    });
    ac.addListener('place_changed', function () {
      var place = ac.getPlace();
      if (!place.address_components) return;
      if (place.geometry && place.geometry.location) {
        $('#guest_latitude').val(place.geometry.location.lat());
        $('#guest_longitude').val(place.geometry.location.lng());
      }
      var sn = '', st = '', city = '', state = '', zip = '', country = '';
      for (var i = 0; i < place.address_components.length; i++) {
        var c = place.address_components[i];
        var t = c.types;
        if (t.indexOf('street_number') !== -1) sn = c.long_name;
        if (t.indexOf('route') !== -1) st = c.long_name;
        if (t.indexOf('locality') !== -1) city = c.long_name;
        else if (t.indexOf('postal_town') !== -1 && !city) city = c.long_name;
        if (t.indexOf('administrative_area_level_1') !== -1) state = c.short_name || c.long_name;
        if (t.indexOf('postal_code') !== -1) zip = c.long_name;
        if (t.indexOf('country') !== -1) country = c.long_name;
      }
      $('#guest_street_number').val(sn);
      $('#guest_street_name').val(st);
      $('#guest_studio_city').val(city);
      $('#guest_studio_state').val(state);
      $('#guest_postal_code').val(zip);
      $('#guest_studio_country').val(country);
      $('#guest_studio_address').val(place.formatted_address || '');
      $('#guest_address_search').val(place.formatted_address || '').removeClass('border-error');
      $('#guest_studio_address_error').addClass('hidden').text('');
      if (place.place_id) {
        $('#guest_google_maps_link').val('https://www.google.com/maps/place/?q=place_id:' + place.place_id);
      }
    });
  })();
@endif

  $('#guest_address_search').on('input', function () {
    $('#guest_studio_address').val($(this).val());
    $('#guest_studio_address_error').text('').addClass('hidden');
    $(this).removeClass('border-error');
  });

  $.each([
    'guest_studio_name', 'guest_street_number', 'guest_street_name', 'guest_studio_city',
    'guest_studio_state', 'guest_postal_code', 'guest_studio_country', 'guest_google_maps_link',
    'guest_number_of_spots', 'guest_response_deadline', 'guest_buffer_days_before', 'guest_buffer_days_after',
  ], function (_, id) {
    $('#' + id).on('input', function () {
      $(this).removeClass('border-error');
      $('#' + id + '_error').text('').addClass('hidden');
      if (id === 'guest_number_of_spots') {
        $('#number_of_spots_error').text('').addClass('hidden');
      }
      if (id === 'guest_response_deadline') {
        $('#response_deadline_error').text('').addClass('hidden');
      }
      if (id === 'guest_buffer_days_before') {
        $('#buffer_days_before_error').text('').addClass('hidden');
      }
      if (id === 'guest_buffer_days_after') {
        $('#buffer_days_after_error').text('').addClass('hidden');
      }
    });
  });

  function formatBufferDaysLabel(days) {
    var n = parseInt(days, 10);
    if (isNaN(n) || n < 0) n = 0;
    return n === 1 ? '1 day' : n + ' days';
  }

  function parseBufferDays(raw) {
    var m = String(raw == null ? '' : raw).match(/(\d+)/);
    if (!m) return 0;
    var n = parseInt(m[1], 10);
    return isNaN(n) || n < 0 ? 0 : n;
  }

  function parsePositiveInt(raw, fallback) {
    var m = String(raw == null ? '' : raw).match(/(\d+)/);
    if (!m) return fallback;
    var n = parseInt(m[1], 10);
    return isNaN(n) ? fallback : n;
  }

  $('#guest_buffer_days_before, #guest_buffer_days_after').on('blur', function () {
    var $el = $(this);
    var raw = $.trim($el.val());
    if (!raw) {
      $el.val('');
      return;
    }
    $el.val(formatBufferDaysLabel(parseBufferDays(raw)));
  });

  $('#guest_number_of_spots').on('blur', function () {
    var $el = $(this);
    var raw = $.trim($el.val());
    if (!raw) {
      $el.val('');
      return;
    }
    $el.val(String(parseBufferDays(raw)));
  });

  $('#guest_response_deadline').on('blur', function () {
    var $el = $(this);
    var raw = $.trim($el.val());
    if (!raw) {
      $el.val('');
      return;
    }
    var n = parsePositiveInt(raw, NaN);
    if (!isNaN(n)) $el.val(String(n));
  });

  function fallbackCopyText(text, onSuccess, onFail) {
    try {
      var ta = document.createElement('textarea');
      ta.value = text;
      ta.setAttribute('readonly', '');
      ta.style.position = 'fixed';
      ta.style.left = '-9999px';
      document.body.appendChild(ta);
      ta.select();
      var ok = document.execCommand('copy');
      document.body.removeChild(ta);
      if (ok) onSuccess();
      else if (onFail) onFail();
    } catch (e) {
      if (onFail) onFail();
    }
  }

  function promptGuestWaitlistNotify(notify) {
    if (!notify || !notify.pending_count || !notify.notify_url || !window.InkjinWaitlistNotify) {
      return;
    }
    var count = Number(notify.pending_count) || 0;
    if (count <= 0) return;
    var city = notify.city || 'this city';
    var people = count === 1 ? 'person' : 'people';
    window.setTimeout(function () {
      window.InkjinWaitlistNotify.showPrompt({
        notifyUrl: notify.notify_url,
        pendingCount: count,
        ids: Array.isArray(notify.ids) ? notify.ids : [],
        guest_spot_id: notify.guest_spot_id || null,
        title: 'Notify guest waitlist?',
        bodyHtml: 'We&rsquo;ll email <b>' + count + '</b> ' + people
          + ' waiting for your guest spot in <b>' + String(city).replace(/[&<>"]/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c];
          }) + '</b> that it&rsquo;s now open to book.',
        hideNote: true,
        hideAlreadyNotifiedNote: true,
      });
    }, 250);
  }

  function copyGuestSpotLink(url, $btn) {
    if (!url) {
      showAlert('Set up your username first to share a guest spot link.', 'error');
      return;
    }
    var $label = $btn.find('.guest-spot-copy-label');
    var original = $label.text() || 'Copy guest spot link';
    var done = function () {
      $btn.addClass('is-copied');
      $label.text('Copied');
      clearTimeout($btn.data('copyTimer'));
      $btn.data('copyTimer', setTimeout(function () {
        $btn.removeClass('is-copied');
        $label.text(original);
      }, 1800));
    };
    var fail = function () {
      showAlert('Could not copy link. Please copy it manually.', 'error');
    };
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(url).then(done).catch(function () {
        fallbackCopyText(url, done, fail);
      });
      return;
    }
    fallbackCopyText(url, done, fail);
  }

  $(document).on('click', '.js-copy-guest-spot-link', function (e) {
    e.preventDefault();
    e.stopPropagation();
    var $btn = $(this);
    copyGuestSpotLink($btn.attr('data-url') || '', $btn);
  });

  $(document).on('mousedown', '.js-copy-guest-spot-link', function (e) {
    e.stopPropagation();
  });
})();
</script>
@endsection
