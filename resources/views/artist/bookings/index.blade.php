@extends('layouts.new-artist-dashboard-layout')

@section('title', 'Bookings')

@php
  $activeNav = 'bookings';
@endphp

@section('styles')
@include('artist.partials.payment-link-modal-styles')
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<script>
  tailwind.config = {
    darkMode: 'class',
    theme: {
      extend: {
        colors: {
          'surface-container-high': '#ece6ef',
          'surface-container-lowest': '#ffffff',
          'surface-container': '#f2ecf5',
          'background': '#fdf7ff',
          'primary': '#1a1a1a',
          'surface-dim': '#ded8e1',
          'on-surface-variant': '#494552',
          'surface-container-low': '#f8f1fb',
          'surface': '#fdf7ff',
          'outline': '#7a7583',
          'error-container': '#ffdad6',
          'error': '#ba1a1a',
          'on-primary': '#ffffff',
          'primary-container': '#333333',
          'on-surface': '#1c1b21',
          'outline-variant': '#cac4d3',
          'on-background': '#1c1b21',
          'surface-variant': '#e6e0ea'
        }
      }
    }
  };
</script>
<style>
  .material-symbols-outlined{
    font-family:'Material Symbols Outlined';font-weight:normal;font-style:normal;font-size:20px;line-height:1;
    display:inline-block;-webkit-font-smoothing:antialiased;
    font-variation-settings:'FILL' 0,'wght' 400,'GRAD' 0,'opsz' 24;vertical-align:middle
  }
  .artist-bdm { pointer-events: none; opacity: 0; transition: opacity 0.28s cubic-bezier(0.22, 1, 0.36, 1); padding: 24px; align-items: center; justify-content: center; overflow: hidden; }
  .artist-bdm.artist-bdm-open { pointer-events: auto; opacity: 1; }
  .artist-bdm-backdrop {
    opacity: 0;
    transition: opacity 0.32s cubic-bezier(0.22, 1, 0.36, 1);
  }
  .artist-bdm.artist-bdm-open .artist-bdm-backdrop { opacity: 1; }
  .artist-bdm-panel {
    display: flex; flex-direction: column;
    width: 600px; max-width: 100%;
    max-height: calc(100vh - 48px); max-height: calc(100dvh - 48px);
    background: #fff; border-radius: 20px;
    box-shadow: 0 20px 60px rgba(0,0,0,.25); overflow: hidden;
    opacity: 0;
    transform: translateY(1rem) scale(0.97);
    transition: opacity 0.32s cubic-bezier(0.22, 1, 0.36, 1), transform 0.36s cubic-bezier(0.22, 1, 0.36, 1);
  }
  .artist-bdm.artist-bdm-open .artist-bdm-panel {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
  .artist-bdm-backdrop,
  .artist-bdm-panel { pointer-events: none; }
  .artist-bdm.artist-bdm-open .artist-bdm-backdrop,
  .artist-bdm.artist-bdm-open .artist-bdm-panel { pointer-events: auto; }
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
  .artist-msm{pointer-events:none;opacity:0;transition:opacity .28s cubic-bezier(.22,1,.36,1);padding:24px;align-items:center;justify-content:center;overflow:hidden}
  .artist-msm.artist-msm-open{pointer-events:auto;opacity:1}
  .artist-msm-backdrop{opacity:0;transition:opacity .32s cubic-bezier(.22,1,.36,1)}
  .artist-msm.artist-msm-open .artist-msm-backdrop{opacity:1}
  .artist-msm-panel{
    display:flex;flex-direction:column;width:600px;max-width:100%;
    max-height:calc(100vh - 48px);max-height:calc(100dvh - 48px);
    background:#fff;border-radius:20px;box-shadow:0 20px 60px rgba(0,0,0,.25);overflow:hidden;
    opacity:0;transform:translateY(1rem) scale(.97);
    transition:opacity .32s cubic-bezier(.22,1,.36,1),transform .36s cubic-bezier(.22,1,.36,1)
  }
  .artist-msm.artist-msm-open .artist-msm-panel{opacity:1;transform:translateY(0) scale(1)}
  .artist-msm-backdrop,.artist-msm-panel{pointer-events:none}
  .artist-msm.artist-msm-open .artist-msm-backdrop,
  .artist-msm.artist-msm-open .artist-msm-panel{pointer-events:auto}
  .artist-msm .amm-mh{padding:20px 22px 14px;display:flex;justify-content:space-between;align-items:flex-start;border-bottom:1px solid #F0EAF2;flex-shrink:0;gap:12px}
  .artist-msm .amm-mh h2{font-size:18px;font-weight:800;letter-spacing:-.3px;color:#1A1A1A;margin:0}
  .artist-msm .amm-sub{font-size:12.5px;color:#6F6874;margin-top:4px}
  .artist-msm .amm-ini{width:40px;height:40px;border-radius:50%;background:#1A1A1A;color:#fff;font-size:13px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0}
  .artist-msm .amm-mb{padding:16px 20px;flex:1 1 auto;min-height:220px;max-height:360px;overflow-y:auto;overscroll-behavior:contain;display:flex;flex-direction:column;gap:10px;justify-content:flex-end}
  .artist-msm .amm-bubble-other{align-self:flex-start;max-width:78%;background:#F4F0F6;border-radius:14px 14px 14px 4px;padding:10px 14px;font-size:13.5px;color:#1A1A1A;line-height:1.45;white-space:pre-wrap;word-break:break-word}
  .artist-msm .amm-bubble-self{align-self:flex-end;max-width:78%;background:#3E007C;color:#fff;border-radius:14px 14px 4px 14px;padding:10px 14px;font-size:13.5px;line-height:1.45;white-space:pre-wrap;word-break:break-word}
  .artist-msm .amm-empty{align-self:stretch;display:flex;align-items:center;justify-content:center;min-height:160px;color:#6F6874;font-size:13.5px;text-align:center;padding:12px}
  .artist-msm .amm-composer{padding:12px 20px;border-top:1px solid #E8DFEA;display:flex;align-items:center;gap:10px;flex-shrink:0}
  .artist-msm .amm-in{
    flex:1;min-width:0;background:#fff;border:1px solid #E8DFEA;border-radius:10px;padding:10px 13px;
    display:flex;align-items:center;gap:8px;min-height:40px;box-shadow:none
  }
  .artist-msm .amm-in:focus-within{border-color:#3E007C;box-shadow:0 0 0 3px #F3E8FF}
  .artist-msm .amm-in input,
  .artist-msm .amm-in input[type="text"],
  .artist-msm #ammInput{
    appearance:none;-webkit-appearance:none;
    border:0!important;outline:0!important;box-shadow:none!important;
    font:inherit;flex:1;min-width:0;background:transparent!important;color:#1A1A1A;
    padding:0!important;margin:0!important;width:100%;min-height:0;line-height:1.4;border-radius:0
  }
  .artist-msm .amm-in input:focus,
  .artist-msm .amm-in input:focus-visible,
  .artist-msm #ammInput:focus,
  .artist-msm #ammInput:focus-visible{
    border:0!important;outline:0!important;box-shadow:none!important
  }
  .artist-msm .amm-in input:disabled{color:#9A929E}
  .artist-msm .amm-locked{display:none;width:100%;align-items:center;gap:8px;color:#6F6874;font-size:13px}
  .artist-msm.amm-readonly .amm-locked{display:flex}
  .artist-msm.amm-readonly .amm-input-wrap{display:none}
  .artist-msm.amm-readonly #ammSendBtn{display:none!important}
  .artist-msm .amm-mf{padding:14px 22px;border-top:1px solid #F0EAF2;display:flex;align-items:center;justify-content:flex-end;gap:10px;background:#FCFAFD;flex-shrink:0}
  .artist-acm{pointer-events:none;opacity:0;transition:opacity .28s cubic-bezier(.22,1,.36,1);padding:24px;align-items:center;justify-content:center;overflow:hidden}
  .artist-acm.artist-acm-open{pointer-events:auto;opacity:1}
  .artist-acm-backdrop{opacity:0;transition:opacity .32s cubic-bezier(.22,1,.36,1)}
  .artist-acm.artist-acm-open .artist-acm-backdrop{opacity:1}
  .artist-acm-panel{
    display:flex;flex-direction:column;width:560px;max-width:100%;
    max-height:calc(100vh - 48px);max-height:calc(100dvh - 48px);
    background:#fff;border-radius:20px;box-shadow:0 20px 60px rgba(0,0,0,.25);overflow:hidden;
    opacity:0;transform:translateY(1rem) scale(.97);
    transition:opacity .32s cubic-bezier(.22,1,.36,1),transform .36s cubic-bezier(.22,1,.36,1)
  }
  .artist-acm.artist-acm-open .artist-acm-panel{opacity:1;transform:translateY(0) scale(1)}
  .artist-acm-backdrop,.artist-acm-panel{pointer-events:none}
  .artist-acm.artist-acm-open .artist-acm-backdrop,
  .artist-acm.artist-acm-open .artist-acm-panel{pointer-events:auto}
  .artist-acm .acm-mh{padding:22px 24px 12px;display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-shrink:0}
  .artist-acm .acm-mh h2{font-size:20px;font-weight:800;letter-spacing:-.3px;color:#1A1A1A;margin:0}
  .artist-acm .acm-to{font-size:13px;color:#6F6874;margin-top:6px;line-height:1.45;word-break:break-word}
  .artist-acm .acm-mb{padding:8px 24px 18px;flex:1 1 auto;min-height:0;overflow-y:auto;overscroll-behavior:contain}
  .artist-acm .acm-preview{border:1px solid #E8DFEA;border-radius:14px;overflow:hidden;background:#fff}
  .artist-acm .acm-banner{background:#1A1A1A;padding:18px 20px}
  .artist-acm .acm-banner h3{margin:0;font-size:17px;font-weight:700;color:#fff;line-height:1.35}
  .artist-acm .acm-banner p{margin:4px 0 0;font-size:12.5px;color:rgba(255,255,255,.75)}
  .artist-acm .acm-body{padding:16px 20px 20px}
  .artist-acm .acm-sec{margin-top:16px}
  .artist-acm .acm-sec:first-child{margin-top:0}
  .artist-acm .acm-sec h4{margin:0 0 6px;font-size:14.5px;font-weight:800;color:#1A1A1A}
  .artist-acm .acm-sec ul{margin:0;padding-left:18px;font-size:13.5px;color:#3d3942;line-height:1.55}
  .artist-acm .acm-sec li{margin:3px 0}
  .artist-acm .acm-sec p{margin:0;font-size:13.5px;color:#3d3942;line-height:1.55}
  .artist-acm .acm-empty{padding:40px 16px;text-align:center;color:#6F6874;font-size:13.5px}
  .artist-acm .acm-mf{padding:14px 24px;border-top:1px solid #F0EAF2;display:flex;align-items:center;justify-content:flex-end;gap:10px;background:#FCFAFD;flex-shrink:0}
  .artist-acm .acm-btn{
    display:inline-flex;align-items:center;gap:8px;background:#1A1A1A;color:#fff;border-radius:10px;padding:10px 16px;
    font-weight:600;font-size:13.5px;white-space:nowrap;border:0;cursor:pointer;font:inherit
  }
  .artist-acm .acm-btn .material-symbols-outlined{font-size:18px}
  .artist-acm .acm-btn.ghost{background:#fff;color:#1A1A1A;border:1px solid #E8DFEA}
  .artist-acm .acm-btn:disabled{opacity:.45;pointer-events:none;cursor:not-allowed}
  @media (max-width:900px){
    .artist-acm{padding:12px}
    .artist-acm-panel{width:100%;max-height:calc(100dvh - 24px);border-radius:16px}
    .artist-acm .acm-mh,.artist-acm .acm-mb{padding-left:16px;padding-right:16px}
    .artist-acm .acm-mf{padding:12px 14px;gap:8px}
  }
  .artist-msm .amm-btn{display:inline-flex;align-items:center;gap:8px;background:#1A1A1A;color:#fff;border-radius:10px;padding:10px 16px;font-weight:600;font-size:13.5px;white-space:nowrap;border:0;cursor:pointer;font:inherit;text-decoration:none}
  .artist-msm .amm-btn .material-symbols-outlined,.artist-msm .amm-btn .ms{font-size:18px}
  .artist-msm .amm-btn.ghost{background:#fff;color:#1A1A1A;border:1px solid #E8DFEA}
  .artist-msm .amm-btn:disabled{opacity:.45;pointer-events:none;cursor:not-allowed}
  .artist-msm .amm-send-ico{padding:10px 12px}
  .artist-msm .amm-faint{color:#9A929E}
  @media (max-width:900px){
    .artist-msm{padding:12px}
    .artist-msm-panel{width:100%;max-height:calc(100dvh - 24px);border-radius:16px}
    .artist-msm .amm-mb{max-height:none;min-height:180px}
    .artist-msm .amm-mf{padding:12px 14px;gap:8px;flex-wrap:wrap}
  }
  .artist-cbm { pointer-events: none; opacity: 0; transition: opacity 0.28s cubic-bezier(0.22, 1, 0.36, 1); }
  .artist-cbm.artist-cbm-open { pointer-events: auto; opacity: 1; }
  .artist-cbm-backdrop {
    opacity: 0;
    transition: opacity 0.3s cubic-bezier(0.22, 1, 0.36, 1);
  }
  .artist-cbm.artist-cbm-open .artist-cbm-backdrop { opacity: 1; }
  .artist-cbm-panel {
    opacity: 0;
    transform: translateY(1rem) scale(0.97);
    transition: opacity 0.32s cubic-bezier(0.22, 1, 0.36, 1), transform 0.36s cubic-bezier(0.22, 1, 0.36, 1);
  }
  .artist-cbm.artist-cbm-open .artist-cbm-panel {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
  .artist-cbm-backdrop,
  .artist-cbm-panel { pointer-events: none; }
  .artist-cbm.artist-cbm-open .artist-cbm-backdrop,
  .artist-cbm.artist-cbm-open .artist-cbm-panel { pointer-events: auto; }
  .artist-rsm{pointer-events:none;opacity:0;transition:opacity .28s cubic-bezier(.22,1,.36,1);padding:24px;align-items:center;justify-content:center;overflow:hidden}
  .artist-rsm.artist-rsm-open{pointer-events:auto;opacity:1}
  .artist-rsm-backdrop{opacity:0;transition:opacity .32s cubic-bezier(.22,1,.36,1)}
  .artist-rsm.artist-rsm-open .artist-rsm-backdrop{opacity:1}
  .artist-rsm-panel{
    display:flex;flex-direction:column;width:600px;max-width:100%;
    max-height:calc(100vh - 48px);max-height:calc(100dvh - 48px);
    background:#fff;border-radius:20px;box-shadow:0 20px 60px rgba(0,0,0,.25);overflow:hidden;
    opacity:0;transform:translateY(1rem) scale(.97);
    transition:opacity .32s cubic-bezier(.22,1,.36,1),transform .36s cubic-bezier(.22,1,.36,1)
  }
  .artist-rsm.artist-rsm-open .artist-rsm-panel{opacity:1;transform:translateY(0) scale(1)}
  .artist-rsm-backdrop,.artist-rsm-panel{pointer-events:none}
  .artist-rsm.artist-rsm-open .artist-rsm-backdrop,
  .artist-rsm.artist-rsm-open .artist-rsm-panel{pointer-events:auto}
  .artist-rsm .arsm-mh{padding:22px 24px 16px;border-bottom:1px solid #F0EAF2;flex-shrink:0}
  .artist-rsm .arsm-topline{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:14px}
  .artist-rsm .arsm-ic{
    width:38px;height:38px;border-radius:10px;background:#F3E8FF;color:#3E007C;
    display:flex;align-items:center;justify-content:center;flex-shrink:0
  }
  .artist-rsm .arsm-ic .material-symbols-outlined{font-size:22px;color:#3E007C}
  .artist-rsm .arsm-mh h2{font-size:20px;font-weight:800;letter-spacing:-.3px;color:#1A1A1A;margin:0}
  .artist-rsm .arsm-lead{color:#6F6874;font-size:13.5px;line-height:1.5;margin:6px 0 0}
  .artist-rsm .arsm-lead strong{color:#1A1A1A;font-weight:700}
  .artist-rsm .arsm-mb{padding:16px 24px 20px;flex:1 1 auto;min-height:0;overflow-y:auto;overscroll-behavior:contain}
  .artist-rsm .arsm-mf{padding:14px 24px;border-top:1px solid #F0EAF2;display:flex;align-items:center;justify-content:flex-end;gap:10px;background:#FCFAFD;flex-shrink:0}
  .artist-rsm .arsm-btn{display:inline-flex;align-items:center;gap:8px;background:#1A1A1A;color:#fff;border-radius:10px;padding:10px 16px;font-weight:600;font-size:13.5px;white-space:nowrap;border:0;cursor:pointer;font:inherit;text-decoration:none}
  .artist-rsm .arsm-btn .material-symbols-outlined{font-size:18px}
  .artist-rsm .arsm-btn.ghost{background:#fff;color:#1A1A1A;border:1px solid #E8DFEA}
  .artist-rsm .arsm-btn:disabled{opacity:.45;pointer-events:none;cursor:not-allowed}
  .artist-rsm .arsm-faint{color:#9A929E}
  .artist-rsm .arsm-card{
    display:flex;align-items:center;gap:12px;background:#FAF0FB;border:1px solid #F1E7F3;
    border-radius:12px;padding:12px 14px;margin:0 0 18px
  }
  .artist-rsm .arsm-ini{
    width:36px;height:36px;border-radius:50%;background:#1A1A1A;color:#fff;font-size:12px;font-weight:700;
    display:flex;align-items:center;justify-content:center;flex-shrink:0
  }
  .artist-rsm .arsm-card-main{flex:1;min-width:0}
  .artist-rsm .arsm-card-title{font-size:13.5px;font-weight:700;color:#1A1A1A;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  .artist-rsm .arsm-card-meta{font-size:12px;color:#6F6874;margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  .artist-rsm .arsm-pill{display:inline-flex;align-items:center;gap:5px;border-radius:20px;font-size:11.5px;font-weight:600;padding:3px 9px;white-space:nowrap;flex-shrink:0}
  .artist-rsm .arsm-pill:before{content:'';width:5px;height:5px;border-radius:50%;background:currentColor}
  .artist-rsm .arsm-pill.g{background:#E6F8EE;color:#00A650}
  .artist-rsm .arsm-pill.a{background:#FFF3DE;color:#B86E00}
  .artist-rsm .arsm-pill.r{background:#FDECEC;color:#C62828}
  .artist-rsm .arsm-pill.k{background:#F0EDF2;color:#6F6874}
  .artist-rsm .arsm-pill.b{background:#E8F0FF;color:#1E5BD8}
  .artist-rsm .arsm-fl{font-size:13px;font-weight:700;color:#1A1A1A;margin:0 0 6px;display:block}
  .artist-rsm .arsm-ta{
    width:100%;min-height:110px;resize:vertical;box-sizing:border-box;
    background:#fff;border:1px solid #E8DFEA;border-radius:10px;padding:10px 13px;
    font:inherit;font-size:13.5px;color:#1A1A1A;outline:none;line-height:1.45;box-shadow:none
  }
  .artist-rsm .arsm-ta:focus,.artist-rsm .arsm-ta:focus-visible{border-color:#3E007C;box-shadow:0 0 0 3px #F3E8FF;outline:0}
  .artist-rsm .arsm-help{font-size:12px;color:#9A929E;margin:6px 0 0}
  .artist-rsm .arsm-err{display:none;margin-top:8px;font-size:12.5px;font-weight:600;color:#C62828}
  .artist-rsm .arsm-err.on{display:block}
  @media (max-width:900px){
    .artist-rsm{padding:12px}
    .artist-rsm-panel{width:100%;max-height:calc(100dvh - 24px);border-radius:16px}
    .artist-rsm .arsm-mf{padding:12px 14px;gap:8px}
    .artist-rsm .arsm-card{flex-wrap:wrap}
  }
  .artist-complete-modal { pointer-events: none; opacity: 0; transition: opacity 0.28s cubic-bezier(0.22, 1, 0.36, 1); }
  .artist-complete-modal.artist-complete-open { pointer-events: auto; opacity: 1; }
  .artist-complete-backdrop { opacity: 0; transition: opacity 0.3s cubic-bezier(0.22, 1, 0.36, 1); }
  .artist-complete-modal.artist-complete-open .artist-complete-backdrop { opacity: 1; }
  .artist-complete-panel { opacity: 0; transform: translateY(1rem) scale(0.97); transition: opacity 0.32s cubic-bezier(0.22, 1, 0.36, 1), transform 0.36s cubic-bezier(0.22, 1, 0.36, 1); }
  .artist-complete-modal.artist-complete-open .artist-complete-panel { opacity: 1; transform: translateY(0) scale(1); }
  .artist-complete-backdrop, .artist-complete-panel { pointer-events: none; }
  .artist-complete-modal.artist-complete-open .artist-complete-backdrop, .artist-complete-modal.artist-complete-open .artist-complete-panel { pointer-events: auto; }
  .artist-rpm{pointer-events:none;opacity:0;transition:opacity .28s cubic-bezier(.22,1,.36,1);padding:24px;align-items:center;justify-content:center;overflow:hidden}
  .artist-rpm.artist-rpm-open{pointer-events:auto;opacity:1}
  .artist-rpm-backdrop{opacity:0;transition:opacity .32s cubic-bezier(.22,1,.36,1)}
  .artist-rpm.artist-rpm-open .artist-rpm-backdrop{opacity:1}
  .artist-rpm-panel{
    display:flex;flex-direction:column;width:600px;max-width:100%;
    max-height:calc(100vh - 48px);max-height:calc(100dvh - 48px);
    background:#fff;border-radius:20px;box-shadow:0 20px 60px rgba(0,0,0,.25);overflow:hidden;
    opacity:0;transform:translateY(1rem) scale(.97);
    transition:opacity .32s cubic-bezier(.22,1,.36,1),transform .36s cubic-bezier(.22,1,.36,1)
  }
  .artist-rpm.artist-rpm-open .artist-rpm-panel{opacity:1;transform:translateY(0) scale(1)}
  .artist-rpm-backdrop,.artist-rpm-panel{pointer-events:none}
  .artist-rpm.artist-rpm-open .artist-rpm-backdrop,
  .artist-rpm.artist-rpm-open .artist-rpm-panel{pointer-events:auto}
  .artist-rpm .rpm-mh{padding:22px 24px 16px;border-bottom:1px solid #F0EAF2;flex-shrink:0}
  .artist-rpm .rpm-topline{display:flex;align-items:flex-start;justify-content:space-between;gap:12px}
  .artist-rpm .rpm-mh h2{font-size:20px;font-weight:800;letter-spacing:-.3px;color:#1A1A1A;margin:0}
  .artist-rpm .rpm-lead{color:#6F6874;font-size:13.5px;line-height:1.5;margin:6px 0 0}
  .artist-rpm .rpm-mb{padding:16px 24px 20px;flex:1 1 auto;min-height:0;overflow-y:auto;overscroll-behavior:contain}
  .artist-rpm .rpm-mf{padding:14px 24px;border-top:1px solid #F0EAF2;display:flex;align-items:center;justify-content:space-between;gap:12px;background:#FCFAFD;flex-shrink:0}
  .artist-rpm .rpm-footer-hint{flex:1;min-width:0;font-size:12.5px;color:#6F6874;line-height:1.4;max-width:260px}
  .artist-rpm .rpm-mf-actions{display:flex;align-items:center;gap:10px;flex-shrink:0;margin-left:auto}
  .artist-rpm .rpm-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;background:#1A1A1A;color:#fff;border-radius:10px;padding:10px 16px;font-weight:600;font-size:13.5px;white-space:nowrap;border:0;cursor:pointer;font:inherit;text-decoration:none}
  .artist-rpm .rpm-btn .material-symbols-outlined{font-size:18px}
  .artist-rpm .rpm-btn.ghost{background:#fff;color:#1A1A1A;border:1px solid #E8DFEA}
  .artist-rpm .rpm-btn.green{background:#1F6B4E;color:#fff}
  .artist-rpm .rpm-btn:disabled{opacity:.45;pointer-events:none;cursor:not-allowed}
  .artist-rpm .rpm-faint{color:#9A929E}
  .artist-rpm .rpm-card{
    display:flex;align-items:center;gap:12px;background:#FAF0FB;border:1px solid #F1E7F3;
    border-radius:12px;padding:12px 14px;margin:0 0 14px
  }
  .artist-rpm .rpm-ini{
    width:36px;height:36px;border-radius:50%;background:#1A1A1A;color:#fff;font-size:12px;font-weight:700;
    display:flex;align-items:center;justify-content:center;flex-shrink:0
  }
  .artist-rpm .rpm-card-main{flex:1;min-width:0}
  .artist-rpm .rpm-card-title{font-size:13.5px;font-weight:700;color:#1A1A1A;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  .artist-rpm .rpm-card-meta{font-size:12px;color:#6F6874;margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  .artist-rpm .rpm-pill{display:inline-flex;align-items:center;gap:5px;border-radius:20px;font-size:11.5px;font-weight:600;padding:3px 9px;white-space:nowrap;flex-shrink:0}
  .artist-rpm .rpm-pill:before{content:'';width:5px;height:5px;border-radius:50%;background:currentColor}
  .artist-rpm .rpm-pill.g{background:#E6F8EE;color:#00A650}
  .artist-rpm .rpm-pill.a{background:#FFF3DE;color:#B86E00}
  .artist-rpm .rpm-pill.r{background:#FDECEC;color:#C62828}
  .artist-rpm .rpm-pill.k{background:#F0EDF2;color:#6F6874}
  .artist-rpm .rpm-pill.b{background:#E8F0FF;color:#1E5BD8}
  .artist-rpm .rpm-settle{background:#FFF8EE;border:1px solid #F2D39A;border-radius:12px;padding:14px;margin:0 0 14px}
  .artist-rpm .rpm-settle-bal{font-size:13.5px;font-weight:700;color:#8A5A12;margin:0 0 4px}
  .artist-rpm .rpm-settle-q{font-size:13px;color:#8A5A12;margin:0 0 12px}
  .artist-rpm .rpm-opts{display:flex;flex-direction:column;gap:10px}
  .artist-rpm .rpm-opt-row{display:flex;align-items:center;justify-content:space-between;gap:12px}
  .artist-rpm .rpm-option{background:none;border:0;padding:0;cursor:pointer;font:inherit;text-align:left}
  .artist-rpm .rpm-amount-chip{display:inline-flex;align-items:center;gap:6px;background:#fff;border-radius:999px;padding:4px 10px;flex-shrink:0}
  .artist-rpm .rpm-amount-chip.hidden{display:none!important}
  .artist-rpm .rpm-amount-chip .amt{font-size:12px;font-weight:700;color:#1A1A1A;tabular-nums:true}
  .artist-rpm .rpm-amount-chip input{
    width:64px;border:0;outline:0;background:transparent;font:inherit;font-size:12px;font-weight:700;color:#1A1A1A;padding:0;margin:0
  }
  .artist-rpm .rpm-amount-chip .edit{background:none;border:0;padding:0;font:inherit;font-size:11px;font-weight:700;color:#1E5BD8;cursor:pointer}
  .artist-rpm .rpm-hint{font-size:11.5px;color:#8A5A12;white-space:nowrap}
  .artist-rpm .rpm-fl{font-size:13px;font-weight:700;color:#1A1A1A;margin:0 0 6px;display:block}
  .artist-rpm .rpm-help{font-size:13px;color:#6F6874;line-height:1.45;margin:0 0 10px}
  .artist-rpm .rpm-duration-help{font-size:12px;color:#9A929E;margin:8px 0 0;line-height:1.4}
  .artist-rpm .rpm-in{
    width:100%;box-sizing:border-box;background:#fff;border:1px solid #E8DFEA;border-radius:10px;
    padding:10px 13px;font:inherit;font-size:13.5px;color:#1A1A1A;outline:none
  }
  .artist-rpm select.rpm-in{
    display:block;appearance:none;-webkit-appearance:none;-moz-appearance:none;
    background-color:#fff;
    background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%239A929E' stroke-width='2.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
    background-repeat:no-repeat;background-position:right 12px center;padding-right:34px;cursor:pointer
  }
  .artist-rpm .rpm-in:focus{border-color:#3E007C;box-shadow:0 0 0 3px #F3E8FF}
  .artist-rpm .rpm-in.border-error{border-color:#C62828!important;box-shadow:0 0 0 3px #FDECEC}
  .artist-rpm .rpm-grid2{display:grid;grid-template-columns:1fr 1fr;gap:12px}
  .artist-rpm .rpm-muted{font-size:12px;color:#6F6874;margin:0 0 6px;display:block}
  .artist-rpm .rpm-when{display:flex;flex-wrap:wrap;gap:8px}
  .artist-rpm .rpm-when-chip{
    border-radius:999px;padding:6px 12px;font-size:13px;font-weight:600;cursor:pointer;font:inherit;
    background:#fff;border:1px solid #E8DFEA;color:#1A1A1A
  }
  .artist-rpm .rpm-when-chip[aria-pressed="true"]{background:#F3E8FF;border-color:#3E007C;color:#3E007C}
  .artist-rpm .rpm-err{display:none;margin-top:8px;font-size:12.5px;font-weight:600;color:#C62828}
  .artist-rpm .rpm-err:not(.hidden){display:block}
  .artist-rpm .rpm-field-err{display:none;margin-top:6px;font-size:12px;font-weight:600;color:#C62828;text-align:right}
  .artist-rpm .rpm-field-err:not(.hidden){display:block}
  .artist-rpm .rpm-link-meta{font-size:13.5px;color:#6F6874;line-height:1.45;margin:0 0 14px}
  .artist-rpm .rpm-box{border:1px solid #E8DFEA;border-radius:10px;padding:12px 14px;background:#fff;margin:0 0 12px}
  .artist-rpm .rpm-box b{font-size:15px;font-weight:800;color:#1A1A1A}
  .artist-rpm .rpm-msg{border:1px solid #E8DFEA;border-radius:10px;padding:12px 14px;background:#fff;font-size:13.5px;line-height:1.5;color:#1A1A1A;margin:0 0 12px}
  .artist-rpm .rpm-link-actions{display:flex;flex-direction:column;gap:8px;margin:0 0 14px}
  .artist-rpm .rpm-link-row{display:grid;grid-template-columns:1fr 1fr;gap:8px}
  .artist-rpm .rpm-qr{display:none;border:1px solid #E8DFEA;border-radius:12px;padding:16px;background:#fff;flex-direction:column;align-items:center;text-align:center}
  .artist-rpm .rpm-qr:not(.hidden){display:flex}
  .artist-rpm .rpm-qr img{width:180px;height:180px;object-fit:contain;display:block}
  .artist-rpm .rpm-qr p{margin:10px 0 0;font-size:12px;color:#6F6874}
  .artist-rpm .rpm-notes{font-size:13px;color:#6F6874;line-height:1.5}
  .artist-rpm .rpm-notes p{margin:0 0 4px}
  .artist-rpm .rpm-notes .ok{font-weight:700;color:#1F6B4E}
  @media (max-width:900px){
    .artist-rpm{padding:12px}
    .artist-rpm-panel{width:100%;max-height:calc(100dvh - 24px);border-radius:16px}
    .artist-rpm .rpm-mf{padding:12px 14px;flex-wrap:wrap}
    .artist-rpm .rpm-card{flex-wrap:wrap}
    .artist-rpm .rpm-grid2,.artist-rpm .rpm-link-row{grid-template-columns:1fr}
  }
  .artist-urm { pointer-events: none; opacity: 0; transition: opacity 0.28s cubic-bezier(0.22, 1, 0.36, 1); }
  .artist-urm.artist-urm-open { pointer-events: auto; opacity: 1; }
  .artist-urm-backdrop { opacity: 0; transition: opacity 0.3s cubic-bezier(0.22, 1, 0.36, 1); }
  .artist-urm.artist-urm-open .artist-urm-backdrop { opacity: 1; }
  .artist-urm-panel { opacity: 0; transform: translateY(1rem) scale(0.97); transition: opacity 0.32s cubic-bezier(0.22, 1, 0.36, 1), transform 0.36s cubic-bezier(0.22, 1, 0.36, 1); }
  .artist-urm.artist-urm-open .artist-urm-panel { opacity: 1; transform: translateY(0) scale(1); }
  .artist-urm-backdrop, .artist-urm-panel { pointer-events: none; }
  .artist-urm.artist-urm-open .artist-urm-backdrop, .artist-urm.artist-urm-open .artist-urm-panel { pointer-events: auto; }
  .artist-cdm { pointer-events: none; opacity: 0; transition: opacity 0.28s cubic-bezier(0.22, 1, 0.36, 1); }
  .artist-cdm.artist-cdm-open { pointer-events: auto; opacity: 1; }
  .artist-cdm-backdrop { opacity: 0; transition: opacity 0.3s cubic-bezier(0.22, 1, 0.36, 1); }
  .artist-cdm.artist-cdm-open .artist-cdm-backdrop { opacity: 1; }
  .artist-cdm-panel { opacity: 0; transform: translateY(1rem) scale(0.97); transition: opacity 0.32s cubic-bezier(0.22, 1, 0.36, 1), transform 0.36s cubic-bezier(0.22, 1, 0.36, 1); }
  .artist-cdm.artist-cdm-open .artist-cdm-panel { opacity: 1; transform: translateY(0) scale(1); }
  .artist-cdm-backdrop, .artist-cdm-panel { pointer-events: none; }
  .artist-cdm.artist-cdm-open .artist-cdm-backdrop, .artist-cdm.artist-cdm-open .artist-cdm-panel { pointer-events: auto; }
  .artist-cpm{pointer-events:none;opacity:0;transition:opacity .28s cubic-bezier(.22,1,.36,1);padding:24px;align-items:center;justify-content:center;overflow:hidden}
  .artist-cpm.artist-cpm-open{pointer-events:auto;opacity:1}
  .artist-cpm-backdrop{opacity:0;transition:opacity .32s cubic-bezier(.22,1,.36,1)}
  .artist-cpm.artist-cpm-open .artist-cpm-backdrop{opacity:1}
  .artist-cpm-panel{
    display:flex;flex-direction:column;width:600px;max-width:100%;
    max-height:calc(100vh - 48px);max-height:calc(100dvh - 48px);
    background:#fff;border-radius:20px;box-shadow:0 20px 60px rgba(0,0,0,.25);overflow:hidden;
    opacity:0;transform:translateY(1rem) scale(.97);
    transition:opacity .32s cubic-bezier(.22,1,.36,1),transform .36s cubic-bezier(.22,1,.36,1)
  }
  .artist-cpm.artist-cpm-open .artist-cpm-panel{opacity:1;transform:translateY(0) scale(1)}
  .artist-cpm-backdrop,.artist-cpm-panel{pointer-events:none}
  .artist-cpm.artist-cpm-open .artist-cpm-backdrop,
  .artist-cpm.artist-cpm-open .artist-cpm-panel{pointer-events:auto}
  .artist-cpm .acpm-mh{padding:22px 24px 16px;border-bottom:1px solid #F0EAF2;flex-shrink:0}
  .artist-cpm .acpm-topline{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:14px}
  .artist-cpm .acpm-ic{
    width:38px;height:38px;border-radius:10px;background:#FFF3DE;color:#B86E00;
    display:flex;align-items:center;justify-content:center;flex-shrink:0
  }
  .artist-cpm .acpm-ic .material-symbols-outlined{font-size:22px;color:#B86E00}
  .artist-cpm .acpm-mh h2{font-size:20px;font-weight:800;letter-spacing:-.3px;color:#1A1A1A;margin:0}
  .artist-cpm .acpm-lead{color:#6F6874;font-size:13.5px;line-height:1.5;margin:6px 0 0}
  .artist-cpm .acpm-mb{padding:16px 24px 20px;flex:1 1 auto;min-height:0;overflow-y:auto;overscroll-behavior:contain}
  .artist-cpm .acpm-mf{padding:14px 24px;border-top:1px solid #F0EAF2;display:flex;align-items:center;justify-content:flex-end;gap:10px;background:#FCFAFD;flex-shrink:0}
  .artist-cpm .acpm-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;background:#1A1A1A;color:#fff;border-radius:10px;padding:10px 16px;font-weight:600;font-size:13.5px;white-space:nowrap;border:0;cursor:pointer;font:inherit;text-decoration:none}
  .artist-cpm .acpm-btn .material-symbols-outlined{font-size:18px}
  .artist-cpm .acpm-btn.ghost{background:#fff;color:#1A1A1A;border:1px solid #E8DFEA}
  .artist-cpm .acpm-btn.green{background:#1F6B4E;color:#fff}
  .artist-cpm .acpm-btn:disabled{opacity:.45;pointer-events:none;cursor:not-allowed}
  .artist-cpm .acpm-faint{color:#9A929E}
  .artist-cpm .acpm-card{
    display:flex;align-items:center;gap:12px;background:#FAF0FB;border:1px solid #F1E7F3;
    border-radius:12px;padding:12px 14px;margin:0 0 12px
  }
  .artist-cpm .acpm-ini{
    width:36px;height:36px;border-radius:50%;background:#1A1A1A;color:#fff;font-size:12px;font-weight:700;
    display:flex;align-items:center;justify-content:center;flex-shrink:0
  }
  .artist-cpm .acpm-card-main{flex:1;min-width:0}
  .artist-cpm .acpm-card-title{font-size:13.5px;font-weight:700;color:#1A1A1A;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  .artist-cpm .acpm-card-meta{font-size:12px;color:#6F6874;margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  .artist-cpm .acpm-pill{display:inline-flex;align-items:center;gap:5px;border-radius:20px;font-size:11.5px;font-weight:600;padding:3px 9px;white-space:nowrap;flex-shrink:0}
  .artist-cpm .acpm-pill:before{content:'';width:5px;height:5px;border-radius:50%;background:currentColor}
  .artist-cpm .acpm-pill.g{background:#E6F8EE;color:#00A650}
  .artist-cpm .acpm-pill.a{background:#FFF3DE;color:#B86E00}
  .artist-cpm .acpm-pill.r{background:#FDECEC;color:#C62828}
  .artist-cpm .acpm-pill.k{background:#F0EDF2;color:#6F6874}
  .artist-cpm .acpm-pill.b{background:#E8F0FF;color:#1E5BD8}
  .artist-cpm .acpm-note{
    display:none;align-items:center;gap:8px;background:#FFF8EE;border:1px solid #F2D39A;border-radius:10px;
    padding:10px 12px;margin:0 0 16px;font-size:12.5px;font-weight:600;color:#8A5A12;line-height:1.4
  }
  .artist-cpm .acpm-note.on{display:flex}
  .artist-cpm .acpm-note .material-symbols-outlined{font-size:18px;color:#B86E00;flex-shrink:0}
  .artist-cpm .acpm-lbl{font-size:11px;letter-spacing:.8px;font-weight:700;color:#6F6874;text-transform:uppercase;margin:0 0 6px;display:block}
  .artist-cpm .acpm-linkrow{display:flex;align-items:stretch;gap:8px;margin-bottom:12px}
  .artist-cpm .acpm-link{
    flex:1;min-width:0;background:#FBF8FC;border:1px solid #E8DFEA;border-radius:10px;padding:10px 13px;
    font:inherit;font-size:13px;color:#1A1A1A;outline:none;box-shadow:none
  }
  .artist-cpm .acpm-link:focus{border-color:#3E007C;box-shadow:0 0 0 3px #F3E8FF}
  .artist-cpm .acpm-actions{display:flex;gap:10px;margin-top:4px}
  .artist-cpm .acpm-actions .acpm-btn{flex:1}
  .artist-cpm .acpm-qr{display:none;margin-top:12px;border:1px solid #E8DFEA;border-radius:12px;padding:16px;background:#fff;flex-direction:column;align-items:center;justify-content:center;text-align:center}
  .artist-cpm .acpm-qr.on{display:flex}
  .artist-cpm .acpm-qr img{width:180px;height:180px;object-fit:contain;background:#fff;border-radius:8px;display:block;margin:0 auto}
  .artist-cpm .acpm-qr p{margin:10px 0 0;font-size:12px;color:#6F6874;text-align:center;width:100%}
  .artist-cpm .acpm-status{display:none;margin-top:10px;font-size:12.5px;font-weight:600}
  .artist-cpm .acpm-status.on{display:block}
  .artist-cpm .acpm-status.ok{color:#1F6B4E}
  .artist-cpm .acpm-status.err{color:#C62828}
  @media (max-width:900px){
    .artist-cpm{padding:12px}
    .artist-cpm-panel{width:100%;max-height:calc(100dvh - 24px);border-radius:16px}
    .artist-cpm .acpm-mf{padding:12px 14px}
    .artist-cpm .acpm-card{flex-wrap:wrap}
    .artist-cpm .acpm-actions{flex-direction:column}
    .artist-cpm .acpm-linkrow{flex-direction:column}
  }
  .rpm-option-dot { width: 18px; height: 18px; border-radius: 9999px; border: 2px solid #c9c4ce; flex-shrink: 0; display: inline-flex; align-items: center; justify-content: center; }
  .rpm-option-dot::after { content: ''; width: 10px; height: 10px; border-radius: 9999px; background: transparent; }
  .rpm-option .rpm-option-label { font-size: 13.5px; font-weight: 500; color: #1A1A1A; }
  .rpm-option[aria-checked="true"] .rpm-option-dot { border-color: #1F6B4E; }
  .rpm-option[aria-checked="true"] .rpm-option-dot::after { background: #1F6B4E; }
  .rpm-option[aria-checked="true"] .rpm-option-label { font-weight: 700; color: #1A1A1A; }
  .acdm-age-toggle {
    position: relative;
    width: 40px;
    height: 22px;
    background: #cac4d3;
    border-radius: 11px;
    border: none;
    cursor: pointer;
    transition: background 0.2s;
    flex-shrink: 0;
  }
  .acdm-age-toggle::after {
    content: '';
    position: absolute;
    top: 2px;
    left: 2px;
    width: 18px;
    height: 18px;
    background: white;
    border-radius: 50%;
    transition: transform 0.2s;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
  }
  .acdm-age-toggle.active { background: #1b5e4a; }
  .acdm-age-toggle.active::after { transform: translateX(18px); }
  .acdm-guardian-input.is-invalid {
    border-color: #c2410c !important;
    box-shadow: 0 0 0 2px rgba(194, 65, 12, 0.15);
  }
  .acdm-age-verified-wrap.is-verified {
    border-color: rgba(27, 94, 74, 0.25);
    background: rgba(234, 242, 239, 0.55);
  }
  .bk-page .pill{display:inline-flex;align-items:center;gap:5px;border-radius:20px;font-size:11.5px;font-weight:600;padding:3px 9px;white-space:nowrap}
  .bk-page .pill:before{content:'';width:5px;height:5px;border-radius:50%;background:currentColor}
  .bk-page .pill.g{background:#E6F8EE;color:#00A650}
  .bk-page .pill.a{background:#FFF3DE;color:#B86E00}
  .bk-page .pill.r{background:#FDECEC;color:#C62828}
  .bk-page .pill.b{background:#E8F0FF;color:#1E5BD8}
  .bk-page .pill.k{background:#F0EDF2;color:#6F6874}
  .bk-page .chip{border:1px solid #E8DFEA;background:#fff;border-radius:20px;padding:6px 13px;font:inherit;font-size:12.5px;font-weight:600;color:#6F6874;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:4px}
  .bk-page .chip.on{background:#3E007C;border-color:#3E007C;color:#fff}
  .bk-page .chip b{font-weight:700;margin-left:4px;opacity:.7}
  .bk-page .chip.on b{opacity:.85}
  .bk-page .tint{background:#FAF0FB;border:1px solid #F1E7F3;border-radius:16px;padding:16px 18px}
  .bk-page .bk-filters{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
  .bk-page .bk-filters .bk-search{flex:1;min-width:180px}
  .bk-page .bk-filters .bk-date{width:150px;flex:none}
  .bk-page .bk-filters .bk-sort{width:170px;flex:none}
  .bk-page .bk-filters .in{margin:0}
  .bk-page .bk-filters select.in{
    display:block;appearance:none;-webkit-appearance:none;
    background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%239A929E' stroke-width='2.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
    background-repeat:no-repeat;background-position:right 12px center;padding-right:34px;cursor:pointer
  }
  .bk-page .bk-filters .bpx-btn.in{width:100%}
  .bk-page .bk-filters .in input[type=search],
  .bk-page .bk-filters .in input[type=text]{
    border:0;outline:0;font:inherit;flex:1;min-width:0;background:none;color:inherit;padding:0;margin:0;box-shadow:none
  }
  .bk-page .bk-view-toggle{display:flex;gap:0;border:1px solid #E8DFEA;border-radius:10px;overflow:hidden;background:#fff;flex-shrink:0}
  .bk-page .bk-view-toggle button{display:inline-flex;align-items:center;gap:6px;padding:7px 13px;font-weight:600;font-size:13px;border:0;background:transparent;color:#1A1A1A;cursor:pointer;font:inherit}
  .bk-page .bk-view-toggle button .ms,
  .bk-page .bk-view-toggle button .material-symbols-outlined{font-size:17px}
  .bk-page .bk-view-toggle button.on{background:#1A1A1A;color:#fff}
  @media (max-width:900px){
    .bk-page .bk-filters .bk-date,
    .bk-page .bk-filters .bk-sort{width:100%;flex:1 1 100%}
  }
  .bk-page .head{margin:0}
  .bk-page .head .sub{margin-top:4px}
  .bk-page .tabs{margin:26px 0 24px}
  .bk-page .tint{margin:0}
  .bk-page #listView .card{margin-top:16px;overflow:hidden}
  .bk-page table{width:100%;border-collapse:collapse}
  .bk-page table th{font-size:11px;letter-spacing:.8px;color:#6F6874;text-align:left;font-weight:700;padding:11px 16px;background:#FBF8FC;border-bottom:1px solid #E8DFEA}
  .bk-page table td{padding:14px 16px;border-bottom:1px solid #F3EEF4;font-size:13.5px;vertical-align:middle;color:#1A1A1A}
  .bk-page table tr:last-child td{border-bottom:0}
  .bk-page table .faint{color:#9A929E;font-size:11.5px;font-weight:400}
  .bk-cal .cal-day{min-height:108px;padding:8px;border-right:1px solid #F3EEF4;border-bottom:1px solid #F3EEF4}
  .bk-cal .ev-c{background:#E6F8EE;color:#0B6B37;font-size:11.5px;border-radius:6px;padding:3px 6px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;cursor:pointer}
  .bk-cal .ev-p{background:#FFF3DE;color:#8A5200;font-size:11.5px;border-radius:6px;padding:3px 6px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;cursor:pointer}
  .bk-cal .ev-k{background:#F0EDF2;color:#6F6874;font-size:11.5px;border-radius:6px;padding:3px 6px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;cursor:pointer}
  .bk-cal .cal-day-view{display:flex;flex-direction:column;min-height:28rem}
  .bk-cal .cal-day-slot{display:grid;grid-template-columns:64px 1fr;gap:12px;min-height:56px;border-bottom:1px solid #F3EEF4;padding:8px 16px}
  .bk-cal .cal-day-slot:last-child{border-bottom:0}
  .bk-cal .cal-day-hour{font-size:11.5px;font-weight:600;color:#9A929E;padding-top:2px}
  .bk-cal .cal-day-events{display:flex;flex-direction:column;gap:6px}
  .bk-cal .cal-day-card{display:flex;align-items:center;gap:10px;border-radius:10px;padding:8px 10px;cursor:pointer}
  .bk-cal .cal-day-card.ev-c{white-space:normal;overflow:visible;text-overflow:unset}
  .bk-cal .cal-day-card.ev-p{white-space:normal;overflow:visible;text-overflow:unset}
  .bk-cal .cal-day-card.ev-k{white-space:normal;overflow:visible;text-overflow:unset}
  .bk-cal .cal-day-empty{font-size:12.5px;color:#C0B6C4;padding:4px 0}
  .bk-bpm{position:absolute;background:#fff;border:1px solid #E8DFEA;border-radius:14px;box-shadow:0 14px 40px rgba(40,10,60,.18);width:250px;padding:6px;z-index:80}
  .bk-bpm button,.bk-bpm a{display:flex;width:100%;align-items:center;gap:10px;padding:9px 12px;border-radius:9px;font:inherit;font-size:13.5px;font-weight:500;background:none;border:0;cursor:pointer;color:#1A1A1A;text-align:left;text-decoration:none}
  .bk-bpm button:hover,.bk-bpm button:focus-visible,.bk-bpm a:hover,.bk-bpm a:focus-visible{background:#F3E8FF;outline:0}
  .bk-bpm .ms{font-size:19px;color:#6F6874;font-variation-settings:'FILL' 0,'wght' 400,'GRAD' 0,'opsz' 24}
  .bk-bpm .red,.bk-bpm .red .ms{color:#C62828}
  .bk-bpm hr{border:0;border-top:1px solid #F0EAF2;margin:5px 4px}
  .bk-bpm-on{background:#F0EDF2!important;border-radius:8px;box-shadow:0 0 0 5px #F0EDF2}
  .bk-bpm-sh{display:none}
  @media (max-width:900px){
    .bk-bpm{position:fixed!important;left:12px!important;right:12px!important;top:auto!important;bottom:12px!important;width:auto!important}
    .bk-bpm-sh{display:block;position:fixed;inset:0;background:rgba(26,16,32,.35);z-index:79}
  }
  @media (max-width:900px){
    .bk-page #listView .card:has(table){overflow:visible!important;background:none!important;border:0!important}
    .bk-page #listView table,.bk-page #listView tbody,.bk-page #listView tr,.bk-page #listView td{display:block;width:100%}
    .bk-page #listView tr:has(th){display:none}
    .bk-page #listView tr.booking-group{position:relative;background:#fff;border:1px solid #E8DFEA;border-radius:14px;padding:12px 14px;margin-bottom:10px}
    .bk-page #listView td{border:0!important;padding:5px 0!important;display:flex!important;justify-content:space-between;align-items:center;gap:12px;text-align:right}
    .bk-page #listView td::before{content:attr(data-label);font-size:10.5px;font-weight:700;letter-spacing:.7px;color:#6F6874;text-align:left;flex-shrink:0}
    .bk-page #listView td:first-child{padding:0 44px 8px 0!important;margin-bottom:4px;border-bottom:1px solid #F3EEF4!important;justify-content:flex-start;text-align:left}
    .bk-page #listView td:first-child::before{display:none}
    .bk-page #listView td[data-label="ACTIONS"]{position:absolute;top:10px;right:10px;width:auto;padding:0!important}
    .bk-page #listView td[data-label="ACTIONS"]::before{display:none}
  }
</style>
@endsection

@section('content')
@php
  $filter = $filter ?? 'upcoming';
  $sort = $sort ?? 'date_asc';
  $search = $search ?? '';
  $dateFrom = $dateFrom ?? '';
  $dateTo = $dateTo ?? '';
  $filterCounts = $filterCounts ?? ['upcoming' => 0, 'past' => 0, 'pending' => 0, 'cancelled' => 0];
  $requestsCount = $requestsCount ?? 0;
  $paymentLinksCount = $paymentLinksCount ?? 0;
  $bookingsTotalCount = $bookingsTotalCount ?? $bookings->total();
  $calendarBookings = $calendarBookings ?? [];
@endphp
@include('artist.bookings.partials.status-styles')
@include('partials.bookpay-datetime-picker')
<div class="bk-page">
      <div class="head">
          <div>
          <h1>Bookings</h1>
          <div class="sub">Requests, confirmed bookings and payment links in one place.</div>
          </div>
        <button type="button" class="btn" data-open-payment-link>
          <span class="ms">add</span> New payment link
        </button>
      </div>

      @include('artist.bookings.partials.page-tabs', [
        'activeTab' => 'bookings',
        'requestsCount' => $requestsCount,
        'bookingsTotalCount' => $bookingsTotalCount,
        'paymentLinksCount' => $paymentLinksCount,
      ])

      <form method="get" action="{{ route('artist.bookings.index') }}" id="bookingsFilterForm" class="tint">
        <input type="hidden" name="filter" id="filterInput" value="{{ $filter }}">
        <div class="bk-filters">
          <div class="bk-search">
            <div class="in">
              <span class="ms">search</span>
              <input type="search" name="q" id="searchClient" value="{{ $search }}" placeholder="Search client name or booking #" autocomplete="off" aria-label="Search client name or booking #">
          </div>
          </div>
          <div class="bk-date">
            <input class="in" type="date" name="from" id="dateFrom" value="{{ $dateFrom }}" placeholder="From" data-ph="From" aria-label="From">
          </div>
          <div class="bk-date">
            <input class="in" type="date" name="to" id="dateTo" value="{{ $dateTo }}" placeholder="To" data-ph="To" aria-label="To">
            </div>
          <div class="bk-sort">
            <select class="in" name="sort" id="sortBy" aria-label="Sort bookings">
              <option value="date_asc" @selected($sort === 'date_asc')>Date · soonest</option>
              <option value="date_desc" @selected($sort === 'date_desc')>Date · latest</option>
              <option value="booking_desc" @selected($sort === 'booking_desc')>Booking # · newest</option>
              <option value="name_asc" @selected($sort === 'name_asc')>Client A–Z</option>
            </select>
          </div>
        </div>
        <div class="row" style="justify-content:space-between;margin-top:14px;flex-wrap:wrap;gap:12px">
          <div class="chips" id="statusPillsRow">
            @foreach ([
              'upcoming' => 'Upcoming',
              'past' => 'Past',
              'pending' => 'Pending',
              'cancelled' => 'Cancelled',
            ] as $key => $label)
              <button type="button" data-filter-status="{{ $key }}"
                class="chip js-filter-status{{ $filter === $key ? ' on' : '' }}">
                {{ $label }} <b>{{ $filterCounts[$key] ?? 0 }}</b>
          </button>
            @endforeach
          </div>
          <div class="bk-view-toggle">
            <button type="button" id="viewListBtn" class="on"><span class="ms">list</span>List</button>
            <button type="button" id="viewCalendarBtn"><span class="ms">calendar_month</span>Calendar</button>
          </div>
        </div>
      </form>

      <!-- Calendar View -->
      <div id="calendarView" class="hidden mb-6 bk-cal">
        <div class="bg-white rounded-2xl shadow-sm border border-[#E8DFEA] overflow-hidden">
          <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 border-b border-[#F3EEF4]">
            <div class="flex items-center gap-2">
              <button type="button" id="calPrev" class="inline-flex items-center justify-center h-8 w-8 rounded-lg border border-[#E8DFEA] hover:bg-[#FBF8FC] text-on-surface-variant">
                <span class="material-symbols-outlined text-xl">chevron_left</span>
          </button>
              <button type="button" id="calNext" class="inline-flex items-center justify-center h-8 w-8 rounded-lg border border-[#E8DFEA] hover:bg-[#FBF8FC] text-on-surface-variant">
                <span class="material-symbols-outlined text-xl">chevron_right</span>
          </button>
              <h3 id="calTitle" class="text-base font-bold text-on-surface min-w-[10rem]"></h3>
              <button type="button" id="calToday" class="ml-1 text-xs font-semibold px-3 py-1.5 rounded-lg border border-[#E8DFEA] hover:bg-[#FBF8FC] text-on-surface-variant">Today</button>
            </div>
            <div class="flex items-center gap-0 border border-[#E8DFEA] rounded-[10px] overflow-hidden bg-white">
              <button type="button" data-cal-mode="day" class="cal-mode-btn text-xs font-semibold px-3.5 py-1.5 transition-colors text-on-surface-variant">Day</button>
              <button type="button" data-cal-mode="week" class="cal-mode-btn text-xs font-semibold px-3.5 py-1.5 transition-colors text-on-surface-variant">Week</button>
              <button type="button" data-cal-mode="month" class="cal-mode-btn active text-xs font-semibold px-3.5 py-1.5 bg-[#1A1A1A] text-white transition-colors">Month</button>
            </div>
          </div>
          <div class="grid grid-cols-1 xl:grid-cols-[1fr_320px] gap-0 xl:gap-0">
            <div id="calBody" class="p-0 min-h-[28rem]"></div>
            <div id="calDayPanel" class="border-t xl:border-t-0 xl:border-l border-[#E8DFEA] bg-white hidden xl:block">
              <div class="flex items-start justify-between px-[18px] py-[18px] border-b border-[#E8DFEA]">
                <div>
                  <h3 id="calDayTitle" class="text-base font-bold">Select a day</h3>
                  <div id="calDayMeta" class="text-[12.5px] text-[#9A929E] mt-0.5"></div>
                </div>
              </div>
              <div id="calDayList" class="px-[18px] py-2"></div>
              <div class="px-[18px] pb-4">
                <a href="{{ route('availability.index') }}" class="inline-flex w-full items-center justify-center gap-2 rounded-[10px] border border-[#E8DFEA] bg-white px-3 py-2.5 text-[13.5px] font-semibold text-[#1A1A1A] hover:bg-[#FBF8FC]">
                  <span class="material-symbols-outlined text-[18px]">event_busy</span>Block this day
                </a>
              </div>
            </div>
          </div>
          <div class="flex flex-wrap gap-4 px-5 py-3 text-xs text-[#6F6874] border-t border-[#F3EEF4]">
            <span class="inline-flex items-center gap-1.5"><i class="inline-block w-3 h-3 rounded-[3px]" style="background:#E6F8EE;border:1px solid #0B6B37"></i>Confirmed</span>
            <span class="inline-flex items-center gap-1.5"><i class="inline-block w-3 h-3 rounded-[3px]" style="background:#FFF3DE;border:1px solid #8A5200"></i>Pending deposit</span>
          </div>
        </div>
      </div>

      <!-- List View -->
      <div id="listView">
      @if ($bookings->isEmpty())
        <div class="bg-white rounded-2xl border border-[#E8DFEA] p-10 text-center text-on-surface-variant">
          <p class="font-medium text-on-surface mb-1">No bookings in this view</p>
          <p class="text-sm">Try another filter, or wait for clients to book through your page.</p>
        </div>
      @else
      <div class="card" style="overflow:hidden;margin-bottom:24px">
            <table>
            <thead>
              <tr>
                <th>CLIENT</th>
                <th>SERVICE</th>
                <th>DATE</th>
                <th>STATUS</th>
                <th>PAYMENT</th>
                <th>CONSENT</th>
                <th></th>
              </tr>
            </thead>
              <tbody id="bookingsTableBody">
                @foreach ($bookings as $booking)
                  @include('artist.bookings.partials.booking-table-row', [
                    'booking' => $booking,
                    'artistUserDetail' => auth()->user()?->userDetail,
                  ])
                @endforeach
            </tbody>
          </table>
        </div>

        <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
          @if(method_exists($bookings, 'links'))
            {{ $bookings->links() }}
          @endif
        </div>
        <p id="artistBookingsFilteredEmpty" class="hidden mt-4 text-center text-sm text-on-surface-variant">
          No bookings match your filters on this page. Try resetting filters or check another page.
        </p>
      @endif
      </div><!-- /#listView -->
    </div>

<div id="artistBookingDetailModal"
  class="artist-bdm fixed inset-0 z-[100] flex"
  aria-hidden="true"
  aria-modal="true"
  role="dialog"
  aria-labelledby="abdmTitle">
  <div class="artist-bdm-backdrop absolute inset-0" style="background:rgba(20,10,30,.45)" data-close-artist-bdm></div>
  <div class="artist-bdm-panel relative z-10">
    <div class="abdm-mh">
      <div><h2 id="abdmTitle">—</h2></div>
      <button type="button" class="abdm-faint inline-flex" data-close-artist-bdm aria-label="Close" style="background:none;border:0;cursor:pointer;padding:0">
        <span class="material-symbols-outlined" style="font-size:22px">close</span>
      </button>
    </div>
    <div class="abdm-mb">
      <div class="abdm-tabs bdtabs" role="tablist" aria-label="Booking detail sections">
        <button type="button" id="abdmTabDetails" role="tab" aria-selected="true" aria-controls="abdmPanelDetails" data-abdm-tab="details" class="on">Details</button>
        <button type="button" id="abdmTabRequest" role="tab" aria-selected="false" aria-controls="abdmPanelRequest" data-abdm-tab="request">Original request</button>
        </div>

      <div id="abdmPanelDetails" role="tabpanel" aria-labelledby="abdmTabDetails">
        <div class="flex items-center" style="gap:16px;margin-bottom:8px">
          <div id="abdmImageWrap" style="width:84px;height:84px;border-radius:14px;background:#EEE center/cover;flex-shrink:0"></div>
          <div>
            <div id="abdmRef" class="abdm-faint" style="font-size:12.5px;font-weight:600">—</div>
            <span id="abdmStatus" class="abdm-pill g" style="margin:6px 0;display:inline-flex">Confirmed</span>
            <div id="abdmType" class="abdm-muted" style="font-size:13px">—</div>
        </div>
      </div>
        <div class="abdm-grid2">
          <div>
            <div class="abdm-kv"><span>Client</span><span id="abdmClientName">—</span></div>
            <div class="abdm-kv" id="abdmClientEmailRow"><span>Email</span><span id="abdmClientEmail">—</span></div>
            <div class="abdm-kv" id="abdmClientPhoneRow"><span>Mobile</span><span id="abdmClientPhone">—</span></div>
            <div class="abdm-kv"><span>Service</span><span id="abdmService">—</span></div>
            <div class="abdm-kv"><span>Deposit</span><span id="abdmDeposit">—</span></div>
        </div>
          <div>
            <div class="abdm-kv"><span>Date</span><span id="abdmDate">—</span></div>
            <div class="abdm-kv"><span>Time</span><span id="abdmTime">—</span></div>
            <div class="abdm-kv"><span>Duration</span><span id="abdmDuration">—</span></div>
            <div class="abdm-kv"><span>Timezone</span><span id="abdmTimezone">—</span></div>
            <div class="abdm-kv"><span>Balance (est.)</span><span id="abdmBalance">—</span></div>
        </div>
        </div>
        <div class="abdm-sect" id="abdmMoney">
          <div class="flex items-center justify-between">
            <div class="lbl" style="margin:0;font-size:11px;letter-spacing:.8px;font-weight:700;color:#6F6874;text-transform:uppercase">Money for this booking</div>
            <span id="abdmMoneyPill" class="abdm-pill a">Pending</span>
        </div>
          <div class="abdm-kv" style="padding:8px 0;border-bottom:1px solid #F3EEF4"><span id="abdmMoneyDepositLabel">Client paid · deposit</span><span id="abdmMoneyDeposit" style="font-weight:600">—</span></div>
          <div class="abdm-kv" id="abdmPaidRow" style="padding:8px 0;border-bottom:1px solid #F3EEF4;display:none"><span>Paid</span><span id="abdmPaid" style="font-weight:600">—</span></div>
          <div class="abdm-kv" id="abdmBookingFeeRow" style="padding:8px 0;border-bottom:1px solid #F3EEF4;display:none"><span>Service Booking Fee <span id="abdmBookingFeeNote" class="abdm-faint" style="font-size:12px"></span></span><span id="abdmBookingFee" style="font-weight:600">—</span></div>
          <div class="abdm-kv" id="abdmStripeFeeRow" style="padding:8px 0;border-bottom:1px solid #F3EEF4;display:none"><span>Stripe fee <span class="abdm-faint" style="font-size:12px">1.5% + €0.25</span></span><span id="abdmStripeFee" style="font-weight:600">—</span></div>
          <div class="abdm-kv" id="abdmSplitRow" style="padding:8px 0;border-bottom:1px solid #F3EEF4;display:none"><span id="abdmSplitLabel">Split</span><span id="abdmSplitValue" style="font-weight:600">—</span></div>
          <div class="abdm-kv" id="abdmYourShareRow" style="padding:8px 0;border-bottom:1px solid #F3EEF4;display:none"><span>Your share <span id="abdmYourShareNote" class="abdm-faint" style="font-size:12px"></span></span><span id="abdmYourShare" style="font-weight:600">—</span></div>
          <div class="abdm-kv" id="abdmYourStripeRow" style="padding:8px 0;border-bottom:1px solid #F3EEF4;display:none"><span>Your part of the Stripe fee <span id="abdmYourStripeNote" class="abdm-faint" style="font-size:12px"></span></span><span id="abdmYourStripe" style="font-weight:600">—</span></div>
          <div class="abdm-kv" id="abdmYourBookingFeeRow" style="padding:8px 0;border-bottom:1px solid #F3EEF4;display:none"><span>Your part of the booking fee <span id="abdmYourBookingFeeNote" class="abdm-faint" style="font-size:12px"></span></span><span id="abdmYourBookingFee" style="font-weight:600">—</span></div>
          <div class="abdm-kv" id="abdmStudioGetsRow" style="padding:8px 0;border-bottom:1px solid #F3EEF4;display:none"><span id="abdmStudioGetsLabel">Studio gets</span><span id="abdmStudioGets" style="font-weight:600">—</span></div>
          <div class="abdm-kv" style="padding:12px 0 4px;font-size:15px;border-bottom:0"><span style="color:#1A1A1A;font-weight:700">You get</span><span id="abdmYouGet" style="font-weight:800;font-size:18px">—</span></div>
          <div id="abdmMoneyFootnote" class="abdm-faint" style="font-size:12.5px;display:none"></div>
          <div id="abdmMoneyNote" class="abdm-tint" style="margin-top:12px;display:none"></div>
        </div>
        </div>

      <div id="abdmPanelRequest" role="tabpanel" aria-labelledby="abdmTabRequest" hidden>
        <div id="abdmQaList" class="abdm-grid2" style="column-gap:20px"></div>
        <p id="abdmQaEmpty" class="abdm-muted" style="font-size:13.5px;display:none">No intake form answers for this booking.</p>
        </div>
        </div>
    <div class="abdm-mf">
      <button type="button" id="abdmMessageBtn" class="abdm-btn ghost" style="display:none"><span class="material-symbols-outlined">chat_bubble</span>Message</button>
      <button type="button" id="abdmAftercareBtn" class="abdm-btn ghost" style="margin-right:auto" aria-disabled="false">
        <span class="material-symbols-outlined">healing</span><span id="abdmAftercareLabel">Send aftercare</span>
      </button>
      <button type="button" class="abdm-btn ghost" data-close-artist-bdm>Close</button>
      <button type="button" id="abdmConfirmBtn" class="abdm-btn confirm" style="display:none"><span class="material-symbols-outlined">task_alt</span>Confirm booking</button>
        </div>
      </div>
</div>

<div id="artistMessageClientModal"
  class="artist-msm fixed inset-0 z-[120] flex"
  aria-hidden="true"
  aria-modal="true"
  role="dialog"
  aria-labelledby="ammTitle">
  <div class="artist-msm-backdrop absolute inset-0" style="background:rgba(20,10,30,.45)" data-close-artist-msm></div>
  <div class="artist-msm-panel relative z-10">
    <div class="amm-mh">
      <div class="flex items-center" style="gap:12px;min-width:0">
        <div id="ammAvatar" class="amm-ini">?</div>
        <div style="min-width:0">
          <h2 id="ammTitle">Message client</h2>
          <div id="ammSub" class="amm-sub">—</div>
        </div>
      </div>
      <button type="button" class="amm-faint inline-flex" data-close-artist-msm aria-label="Close" style="background:none;border:0;cursor:pointer;padding:0">
        <span class="material-symbols-outlined" style="font-size:22px">close</span>
      </button>
    </div>
    <div id="ammMessages" class="amm-mb" aria-live="polite">
      <div class="amm-empty">Loading conversation…</div>
    </div>
    <div class="amm-composer" id="ammComposer">
      <span class="material-symbols-outlined amm-faint" style="font-size:20px">attach_file</span>
      <div class="amm-in">
        <div class="amm-input-wrap" style="display:flex;flex:1;min-width:0">
          <input type="text" id="ammInput" placeholder="Type a message" autocomplete="off" aria-label="Message">
        </div>
        <div class="amm-locked">
          <span class="material-symbols-outlined" style="font-size:18px;color:#9A929E">lock</span>
          <span id="ammLockedText">This chat is read-only.</span>
        </div>
      </div>
      <button type="button" id="ammSendBtn" class="amm-btn amm-send-ico" aria-label="Send">
        <span class="material-symbols-outlined" style="color:#fff">send</span>
      </button>
    </div>
    <div class="amm-mf">
      <a id="ammOpenInbox" href="#" class="amm-btn ghost"><span class="material-symbols-outlined">mail</span>Open in Inbox</a>
    </div>
        </div>
      </div>

<div id="artistAftercareSendModal"
  class="artist-acm fixed inset-0 z-[120] flex"
  aria-hidden="true"
  aria-modal="true"
  role="dialog"
  aria-labelledby="acmTitle">
  <div class="artist-acm-backdrop absolute inset-0" style="background:rgba(20,10,30,.45)" data-close-artist-acm></div>
  <div class="artist-acm-panel relative z-10">
    <div class="acm-mh">
      <div style="min-width:0">
        <h2 id="acmTitle">Send aftercare</h2>
        <div class="acm-to" id="acmToLine">—</div>
      </div>
      <button type="button" class="inline-flex" data-close-artist-acm aria-label="Close" style="background:none;border:0;cursor:pointer;padding:0;color:#9A929E">
        <span class="material-symbols-outlined" style="font-size:22px">close</span>
      </button>
    </div>
    <div class="acm-mb" id="acmPreviewWrap">
      <div class="acm-empty">Loading preview…</div>
    </div>
    <div class="acm-mf" style="flex-wrap:wrap">
      <div id="acmCooldownNote" class="acm-faint" hidden style="flex:1;min-width:180px;font-size:12.5px;color:#6F6874;margin-right:auto"></div>
      <button type="button" class="acm-btn ghost" data-close-artist-acm>Cancel</button>
      <button type="button" class="acm-btn" id="acmSendBtn">
        <span class="material-symbols-outlined">send</span><span id="acmSendLabel">Send aftercare</span>
      </button>
    </div>
  </div>
</div>

<div id="artistCancelBookingModal"
  class="artist-cbm fixed inset-0 z-[110] flex items-end sm:items-center justify-center p-4 sm:p-6"
  aria-hidden="true"
  aria-modal="true"
  role="dialog"
  aria-labelledby="acbmTitle">
  <div class="artist-cbm-backdrop absolute inset-0 bg-black/45" data-close-artist-cbm></div>
  <div class="artist-cbm-panel relative w-full max-w-lg rounded-2xl bg-white shadow-xl border border-outline-variant/20">
    <div class="px-5 py-4 border-b border-outline-variant/15 flex items-center justify-between">
      <h3 id="acbmTitle" class="text-lg font-bold text-on-surface">Cancel booking</h3>
      <button type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-xl text-on-surface-variant hover:bg-surface-container-low" data-close-artist-cbm aria-label="Close">
        <span class="material-symbols-outlined text-[22px]">close</span>
      </button>
    </div>
    <div class="p-5 space-y-4">
      <div class="rounded-xl border border-green-200 bg-green-50/80 p-4 text-sm">
        <p id="acbmRefundLead" class="font-semibold text-green-900">Full refund will be given to the user.</p>
        <p id="acbmRefundSub" class="text-green-900/80 mt-1">Refund amount: €0.00</p>
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
        <div class="rounded-lg bg-surface-container-low px-3 py-2">
          <p class="text-on-surface-variant text-xs">Booking ID</p>
          <p id="acbmBookingId" class="font-semibold text-on-surface">—</p>
        </div>
        <div class="rounded-lg bg-surface-container-low px-3 py-2">
          <p class="text-on-surface-variant text-xs">Cancellation window</p>
          <p id="acbmWindow" class="font-semibold text-on-surface">—</p>
        </div>
      </div>
      <div>
        <label for="acbmReason" class="block text-sm font-semibold text-on-surface mb-1.5">Reason for cancellation <span class="text-error">*</span></label>
        <textarea id="acbmReason" rows="4" maxlength="1000"
          class="w-full text-sm border border-outline-variant/30 rounded-xl px-3 py-2 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30"
          placeholder="Please tell the client why this booking is being cancelled..."></textarea>
        <p id="acbmReasonError" class="hidden text-xs font-semibold text-error mt-1.5">Reason is required.</p>
      </div>
      <p id="acbmError" class="hidden text-xs font-semibold text-error"></p>
      <div class="flex items-center justify-end gap-2 pt-1">
        <button type="button" class="px-4 py-2.5 rounded-xl text-sm font-semibold border border-outline-variant/40 text-on-surface hover:bg-surface-container-low" data-close-artist-cbm>Back</button>
        <button type="button" id="acbmSubmit" class="px-4 py-2.5 rounded-xl text-sm font-semibold bg-error text-white hover:opacity-95 disabled:opacity-60 disabled:cursor-not-allowed">Confirm cancellation</button>
      </div>
    </div>
        </div>
      </div>

<div id="artistUnsettledReminderModal"
  class="artist-urm fixed inset-0 z-[116] flex items-end sm:items-center justify-center p-4 sm:p-6"
  aria-hidden="true"
  aria-modal="true"
  role="dialog"
  aria-labelledby="urmClient">
  <div class="artist-urm-backdrop absolute inset-0 bg-black/45" data-close-artist-urm></div>
  <div class="artist-urm-panel relative w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-xl border border-outline-variant/20">
    <button type="button" class="absolute top-3 right-3 inline-flex h-9 w-9 items-center justify-center rounded-xl text-on-surface-variant hover:bg-surface-container-low" data-close-artist-urm aria-label="Close">
      <span class="material-symbols-outlined text-[20px]">close</span>
    </button>
    <div class="p-5 space-y-3">
      <div class="flex items-start justify-between gap-3 pr-8">
        <p id="urmClient" class="text-lg font-bold text-on-surface">—</p>
        <span id="urmStatus" class="inline-flex shrink-0 items-center text-xs font-semibold px-2.5 py-1 rounded-full ring-1 ring-inset bg-green-50 text-green-700 ring-green-500/20">Confirmed</span>
      </div>
      <p id="urmBookingRef" class="text-sm font-medium text-on-surface-variant tabular-nums">—</p>
      <p id="urmWhen" class="text-sm text-on-surface">—</p>
      <div class="flex items-center justify-between gap-3 rounded-xl bg-[#f4eee4] px-3.5 py-2.5">
        <p id="urmDueLine" class="text-sm font-bold text-[#8a5a12]">—</p>
        <button type="button" id="urmCollectBtn" class="text-sm font-bold text-[#1b5e4a] underline underline-offset-2 whitespace-nowrap">Collect</button>
      </div>
      <ul id="urmNotes" class="space-y-1 text-sm text-on-surface-variant list-disc pl-5">
        <li id="urmProofNote">Not marked completed — no proof yet</li>
        <li id="urmSavedNote" class="hidden"></li>
        <li id="urmNudgeNote" class="hidden"></li>
      </ul>
    </div>
  </div>
</div>

<div id="artistConsentDetailModal"
  class="artist-cdm fixed inset-0 z-[116] flex items-end sm:items-center justify-center p-4 sm:p-6"
  aria-hidden="true"
  aria-modal="true"
  role="dialog"
  aria-labelledby="acdmTitle">
  <div class="artist-cdm-backdrop absolute inset-0 bg-black/45" data-close-artist-cdm></div>
  <div class="artist-cdm-panel relative w-full max-w-lg max-h-[min(90vh,720px)] overflow-y-auto rounded-2xl bg-white shadow-xl border border-outline-variant/20">
    <div class="sticky top-0 z-10 flex items-center justify-between gap-3 px-5 py-4 border-b border-outline-variant/15 bg-white/95 backdrop-blur-sm rounded-t-2xl">
      <h3 id="acdmTitle" class="text-lg font-bold text-on-surface truncate pr-2">Consent form</h3>
      <button type="button" class="shrink-0 inline-flex h-10 w-10 items-center justify-center rounded-xl text-on-surface-variant hover:bg-surface-container-low" data-close-artist-cdm aria-label="Close">
        <span class="material-symbols-outlined text-[22px]">close</span>
      </button>
    </div>
    <div class="p-5 space-y-4">
      <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
          <p id="acdmClient" class="text-base font-bold text-on-surface truncate">—</p>
          <p id="acdmBookingRef" class="mt-0.5 text-sm font-medium text-on-surface-variant tabular-nums">—</p>
          <p id="acdmWhen" class="mt-1 text-sm text-on-surface">—</p>
      </div>
        <span class="inline-flex shrink-0 items-center text-xs font-semibold px-2.5 py-1 rounded-full ring-1 ring-inset bg-green-50 text-green-700 ring-green-500/20">Submitted</span>
      </div>
      <div class="flex items-center justify-between gap-3 rounded-xl bg-[#f4eee4] px-3.5 py-2.5">
        <p class="text-sm font-bold text-[#8a5a12]">Consent submitted &amp; signed</p>
        <p id="acdmSignedAt" class="text-xs font-semibold text-[#8a5a12]">—</p>
      </div>
      <div id="acdmAgeVerifiedWrap" class="acdm-age-verified-wrap rounded-xl border border-outline-variant/20 bg-white px-3.5 py-3 transition-colors">
        <div class="flex items-center justify-between gap-3">
          <p id="acdmAgeVerifiedLabel" class="text-sm text-on-surface leading-snug">I have verified the client&rsquo;s age with a valid ID</p>
          <button type="button"
            id="acdmAgeVerifiedToggle"
            class="acdm-age-toggle"
            role="switch"
            aria-checked="false"
            aria-labelledby="acdmAgeVerifiedLabel"></button>
        </div>
        <div id="acdmGuardianFields" class="hidden mt-3 pt-3 border-t border-outline-variant/15 space-y-3">
      <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-on-surface-variant">Guardian consent</p>
            <p id="acdmGuardianFormHint" class="mt-1 text-xs text-on-surface-variant leading-relaxed">Complete these with the parent or guardian present in studio. All fields are required.</p>
      </div>
          <div id="acdmGuardianForm" class="space-y-3">
            <div>
              <label for="acdmGuardianName" class="block text-sm font-medium text-on-surface mb-1">Guardian full name</label>
              <input id="acdmGuardianName" type="text" autocomplete="name" required class="acdm-guardian-input w-full rounded-xl border border-outline-variant/40 bg-white px-3 py-2.5 text-sm text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/20" aria-describedby="acdmGuardianNameError">
              <p id="acdmGuardianNameError" class="hidden mt-1 text-xs font-medium text-[#c2410c]" role="alert"></p>
      </div>
            <div>
              <label for="acdmGuardianRelationship" class="block text-sm font-medium text-on-surface mb-1">Relationship to client</label>
              <input id="acdmGuardianRelationship" type="text" placeholder="e.g. Parent, Legal guardian" required class="acdm-guardian-input w-full rounded-xl border border-outline-variant/40 bg-white px-3 py-2.5 text-sm text-on-surface placeholder:text-on-surface-variant focus:outline-none focus:ring-2 focus:ring-primary/20" aria-describedby="acdmGuardianRelationshipError">
              <p id="acdmGuardianRelationshipError" class="hidden mt-1 text-xs font-medium text-[#c2410c]" role="alert"></p>
    </div>
            <div>
              <label for="acdmGuardianIdRef" class="block text-sm font-medium text-on-surface mb-1">Guardian ID reference</label>
              <input id="acdmGuardianIdRef" type="text" placeholder="Passport / ID number" required class="acdm-guardian-input w-full rounded-xl border border-outline-variant/40 bg-white px-3 py-2.5 text-sm text-on-surface placeholder:text-on-surface-variant focus:outline-none focus:ring-2 focus:ring-primary/20" aria-describedby="acdmGuardianIdRefError">
              <p id="acdmGuardianIdRefError" class="hidden mt-1 text-xs font-medium text-[#c2410c]" role="alert"></p>
            </div>
            <div>
              <label for="acdmGuardianSignature" class="block text-sm font-medium text-on-surface mb-1">Guardian signature (typed name)</label>
              <input id="acdmGuardianSignature" type="text" placeholder="Type full name to sign" required class="acdm-guardian-input w-full rounded-xl border border-outline-variant/40 bg-white px-3 py-2.5 text-sm text-on-surface placeholder:text-on-surface-variant focus:outline-none focus:ring-2 focus:ring-primary/20" aria-describedby="acdmGuardianSignatureError">
              <p id="acdmGuardianSignatureError" class="hidden mt-1 text-xs font-medium text-[#c2410c]" role="alert"></p>
            </div>
            <div class="flex items-center justify-between gap-3">
              <p id="acdmGuardianStatus" class="text-xs text-on-surface-variant min-h-[1rem]"></p>
              <button type="button" id="acdmGuardianSaveBtn" class="shrink-0 rounded-xl bg-[#1b5e4a] px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-70 disabled:cursor-wait">
                Save guardian
              </button>
            </div>
          </div>
          <div id="acdmGuardianSummary" class="hidden space-y-3">
            <dl class="text-sm">
              <div class="flex justify-between gap-4 py-2 border-b border-outline-variant/10">
                <dt class="text-on-surface-variant font-medium">Guardian name</dt>
                <dd id="acdmGuardianSummaryName" class="text-on-surface text-right">—</dd>
              </div>
              <div class="flex justify-between gap-4 py-2 border-b border-outline-variant/10">
                <dt class="text-on-surface-variant font-medium">Relationship</dt>
                <dd id="acdmGuardianSummaryRelationship" class="text-on-surface text-right">—</dd>
              </div>
              <div class="flex justify-between gap-4 py-2 border-b border-outline-variant/10">
                <dt class="text-on-surface-variant font-medium">ID reference</dt>
                <dd id="acdmGuardianSummaryIdRef" class="text-on-surface text-right">—</dd>
              </div>
              <div class="flex justify-between gap-4 py-2 border-b border-outline-variant/10">
                <dt class="text-on-surface-variant font-medium">Guardian signature</dt>
                <dd id="acdmGuardianSummarySignature" class="text-on-surface text-right">—</dd>
              </div>
            </dl>
            <p id="acdmGuardianSignedAt" class="text-xs font-medium text-on-surface-variant"></p>
          </div>
        </div>
      </div>
      <div id="acdmBody" class="space-y-4 text-sm"></div>
    </div>
  </div>
</div>

<div id="artistConsentPendingModal"
  class="artist-cpm fixed inset-0 z-[116] flex"
  aria-hidden="true"
  aria-modal="true"
  role="dialog"
  aria-labelledby="acpmTitle">
  <div class="artist-cpm-backdrop absolute inset-0" style="background:rgba(20,10,30,.45)" data-close-artist-cpm></div>
  <div class="artist-cpm-panel relative z-10">
    <div class="acpm-mh">
      <div class="acpm-topline">
        <div class="acpm-ic" aria-hidden="true"><span class="material-symbols-outlined">draw</span></div>
        <button type="button" class="acpm-faint inline-flex" data-close-artist-cpm aria-label="Close" style="background:none;border:0;cursor:pointer;padding:0">
          <span class="material-symbols-outlined" style="font-size:22px">close</span>
        </button>
      </div>
      <h2 id="acpmTitle">Consent pending</h2>
      <p id="acpmLead" class="acpm-lead">Client hasn’t completed the consent form yet.</p>
    </div>
    <div class="acpm-mb">
      <div class="acpm-card">
        <div id="acpmAvatar" class="acpm-ini">?</div>
        <div class="acpm-card-main">
          <div id="acpmCardTitle" class="acpm-card-title">—</div>
          <div id="acpmCardMeta" class="acpm-card-meta">—</div>
        </div>
        <span id="acpmBookingStatus" class="acpm-pill g">Confirmed</span>
      </div>
      <div id="acpmAutoNote" class="acpm-note" role="status">
        <span class="material-symbols-outlined">schedule</span>
        <span id="acpmAutoNoteText"></span>
      </div>
      <label for="acpmConsentLink" class="acpm-lbl">Consent form link</label>
      <div class="acpm-linkrow">
        <input id="acpmConsentLink" class="acpm-link" type="text" readonly placeholder="Preparing link…" aria-label="Consent form link">
        <button type="button" id="acpmCopyLinkBtn" class="acpm-btn ghost" disabled>
          <span class="material-symbols-outlined">content_copy</span><span id="acpmCopyLinkLabel">Copy link</span>
        </button>
      </div>
      <div class="acpm-actions">
        <button type="button" id="acpmResendBtn" class="acpm-btn green">
          <span class="material-symbols-outlined">mail</span><span id="acpmResendLabel">Resend email</span>
        </button>
        <button type="button" id="acpmShowQrBtn" class="acpm-btn ghost" aria-expanded="false" aria-controls="acpmQrWrap">
          <span class="material-symbols-outlined">qr_code_2</span><span id="acpmShowQrLabel">Show QR</span>
        </button>
      </div>
      <div id="acpmQrWrap" class="acpm-qr">
        <img id="acpmQrImage" alt="Consent form QR code">
        <p>Scan to open the consent form</p>
      </div>
      <p id="acpmStatus" class="acpm-status"></p>
    </div>
    <div class="acpm-mf">
      <button type="button" class="acpm-btn ghost" data-close-artist-cpm>Close</button>
    </div>
  </div>
</div>

<div id="artistRequestPaymentModal"
  class="artist-rpm fixed inset-0 z-[117] flex"
  aria-hidden="true"
  aria-modal="true"
  role="dialog"
  aria-labelledby="rpmTitle">
  <div class="artist-rpm-backdrop absolute inset-0" style="background:rgba(20,10,30,.45)" data-close-artist-rpm></div>
  <div class="artist-rpm-panel relative z-10">
    <div class="rpm-mh">
      <div class="rpm-topline">
        <div style="min-width:0">
          <h2 id="rpmTitle">Request payment</h2>
          <p class="rpm-lead">Settle the balance and close the session.</p>
        </div>
        <button type="button" class="rpm-faint inline-flex" data-close-artist-rpm aria-label="Close" style="background:none;border:0;cursor:pointer;padding:0">
          <span class="material-symbols-outlined" style="font-size:22px">close</span>
        </button>
      </div>
    </div>

    <div id="rpmSettleView" style="display:flex;flex-direction:column;min-height:0;flex:1">
      <div class="rpm-mb">
        <div class="rpm-card">
          <div id="rpmAvatar" class="rpm-ini">?</div>
          <div class="rpm-card-main">
            <div id="rpmCardTitle" class="rpm-card-title">—</div>
            <div id="rpmCardMeta" class="rpm-card-meta">—</div>
          </div>
          <span id="rpmBookingStatus" class="rpm-pill g">Confirmed</span>
        </div>
        <p id="rpmBookingLine" class="hidden">—</p>

        <div class="rpm-settle">
          <p id="rpmBalanceLine" class="rpm-settle-bal">Outstanding balance: —</p>
          <p class="rpm-settle-q">How was it settled?</p>
          <div class="rpm-opts" role="radiogroup" aria-label="How was it settled?">
            <div>
              <div class="rpm-opt-row">
                <button type="button" class="rpm-option flex min-w-0 items-center gap-2.5" data-rpm-value="link" aria-checked="true" role="radio">
                  <span class="rpm-option-dot" aria-hidden="true"></span>
                  <span class="rpm-option-label">Client will pay by link</span>
                </button>
                <div id="rpmLinkAmountWrap" class="rpm-amount-chip">
                  <span id="rpmLinkSettleAmountDisplay" class="amt">€ 0</span>
                  <input id="rpmLinkSettleAmountInput" type="text" inputmode="decimal" class="hidden" aria-label="Payment link amount">
                  <button type="button" id="rpmLinkSettleAmountEdit" class="edit">edit</button>
                </div>
              </div>
              <p id="rpmLinkAmountError" class="rpm-field-err hidden"></p>
            </div>
            <div>
              <div class="rpm-opt-row">
                <button type="button" class="rpm-option flex min-w-0 items-center gap-2.5" data-rpm-value="cash" aria-checked="false" role="radio">
                  <span class="rpm-option-dot" aria-hidden="true"></span>
                  <span class="rpm-option-label">Paid in cash</span>
                </button>
                <div id="rpmCashAmountWrap" class="rpm-amount-chip hidden">
                  <span id="rpmCashAmountDisplay" class="amt">€ 0</span>
                  <input id="rpmCashAmountInput" type="text" inputmode="decimal" class="hidden" aria-label="Cash amount">
                  <button type="button" id="rpmCashAmountEdit" class="edit">edit</button>
                </div>
              </div>
              <p id="rpmCashAmountError" class="rpm-field-err hidden"></p>
            </div>
            <button type="button" class="rpm-option rpm-opt-row w-full" data-rpm-value="unsettled" aria-checked="false" role="radio">
              <span class="inline-flex items-center gap-2.5 min-w-0">
                <span class="rpm-option-dot" aria-hidden="true"></span>
                <span class="rpm-option-label">Not settled yet</span>
              </span>
              <span class="rpm-hint">the balance stays due on the booking</span>
            </button>
          </div>
        </div>

        <div id="rpmCashFields" class="hidden" style="margin-bottom:14px">
          <p id="rpmCashHelp" class="rpm-help">Ask the client for their completion code. It’s in their booking confirmation email and confirms the cash payment.</p>
          <label for="rpmCompletionCode" class="rpm-fl">Completion code <span style="color:#C62828">*</span></label>
          <input id="rpmCompletionCode" type="text" maxlength="32" autocomplete="off" class="rpm-in border-outline-variant/40" placeholder="6-digit code">
          <p id="rpmCompletionCodeError" class="rpm-field-err hidden" style="text-align:left"></p>
        </div>

        <div id="rpmUnsettledFields" class="hidden" style="margin-bottom:14px">
          <p class="rpm-fl" style="margin-bottom:8px">When do you expect the payment?</p>
          <div class="rpm-when" role="radiogroup" aria-label="When do you expect the payment?">
            <button type="button" class="rpm-when-chip" data-when-value="3-days" aria-pressed="true">3 days</button>
            <button type="button" class="rpm-when-chip" data-when-value="1-week" aria-pressed="false">1 week</button>
            <button type="button" class="rpm-when-chip" data-when-value="pick-date" aria-pressed="false">Pick date</button>
            <button type="button" class="rpm-when-chip" data-when-value="no-date" aria-pressed="false">No date</button>
          </div>
          <p id="rpmWhenError" class="rpm-field-err hidden" style="text-align:left"></p>
          <input id="rpmUnsettledDate" type="date" class="rpm-in border-outline-variant/40 hidden" style="margin-top:10px">
          <p id="rpmDateError" class="rpm-field-err hidden" style="text-align:left"></p>
          <label for="rpmUnsettledNote" class="rpm-fl" style="margin-top:14px">Note <span style="font-weight:500;color:#9A929E">(optional)</span></label>
          <input id="rpmUnsettledNote" type="text" class="rpm-in border-outline-variant/40" placeholder="e.g. Bank transfer on Friday">
        </div>

        <div id="rpmActualDurationFields">
          <label class="rpm-fl">Actual duration <span style="color:#C62828">*</span></label>
          <div class="rpm-grid2">
            <div>
              <label for="rpmDurationHours" class="rpm-muted">Hours</label>
              <select id="rpmDurationHours" class="rpm-in border-outline-variant/40">
                @for ($h = 0; $h <= 12; $h++)
                  <option value="{{ $h }}" @selected($h === 0)>{{ $h }}</option>
                @endfor
              </select>
            </div>
            <div>
              <label for="rpmDurationMinutes" class="rpm-muted">Minutes</label>
              <select id="rpmDurationMinutes" class="rpm-in border-outline-variant/40">
                @foreach ([0, 15, 30, 45] as $m)
                  <option value="{{ $m }}" @selected($m === 0)>{{ $m }}</option>
                @endforeach
              </select>
            </div>
          </div>
          <p id="rpmDurationHelp" class="rpm-duration-help">Pre-filled from the booking. Change it if the session ran shorter or longer.</p>
          <p id="rpmDurationError" class="rpm-field-err hidden" style="text-align:left"></p>
        </div>
        <p id="rpmError" class="rpm-err hidden"></p>
      </div>
      <div class="rpm-mf">
        <p id="rpmActionHint" class="rpm-footer-hint">Sends the link. The booking completes when the client pays.</p>
        <div class="rpm-mf-actions">
          <button type="button" class="rpm-btn ghost" data-close-artist-rpm>Back</button>
          <button type="button" id="rpmActionBtn" class="rpm-btn green">
            <span id="rpmActionIcon" class="material-symbols-outlined">link</span>
            <span id="rpmActionLabel">Get payment link</span>
          </button>
        </div>
      </div>
    </div>

    <div id="rpmLinkView" class="hidden rpm-mb">
      <div class="rpm-link-meta">
        <p id="rpmLinkClientLine">—</p>
        <p id="rpmLinkTotalsLine">—</p>
      </div>
      <p class="rpm-muted">Amount</p>
      <div class="rpm-box"><b id="rpmLinkAmountDisplay">€ 0</b></div>
      <p class="rpm-muted">Message for your client</p>
      <div id="rpmLinkMessage" class="rpm-msg"></div>
      <div class="rpm-link-actions">
        <button type="button" id="rpmCopyLinkBtn" class="rpm-btn"><span id="rpmCopyLinkLabel">Copy payment link</span></button>
        <div class="rpm-link-row">
          <button type="button" id="rpmCopyMessageBtn" class="rpm-btn ghost"><span id="rpmCopyMessageLabel">Copy message</span></button>
          <button type="button" id="rpmShowQrBtn" class="rpm-btn ghost" aria-expanded="false" aria-controls="rpmQrWrap"><span id="rpmShowQrLabel">Show QR</span></button>
        </div>
        <div id="rpmQrWrap" class="rpm-qr hidden">
          <img id="rpmQrImage" alt="Payment link QR code">
          <p>Scan to open the payment link</p>
        </div>
      </div>
      <div class="rpm-notes">
        <p>Client can pay in full or split with Klarna.</p>
        <p>Balance links don’t expire.</p>
        <p id="rpmLinkStatusLine">Card: — requested · link sent · <span style="color:#1E5BD8;font-weight:600">Resend</span></p>
        <p class="ok">Client pays → booking auto-marked Completed</p>
      </div>
    </div>
  </div>
</div>

<div id="artistRescheduleRequestModal"
  class="artist-rsm fixed inset-0 z-[115] flex"
  aria-hidden="true"
  aria-modal="true"
  role="dialog"
  aria-labelledby="arsmTitle">
  <div class="artist-rsm-backdrop absolute inset-0" style="background:rgba(20,10,30,.45)" data-close-artist-rsm></div>
  <div class="artist-rsm-panel relative z-10">
    <div class="arsm-mh">
      <div class="arsm-topline">
        <div class="arsm-ic" aria-hidden="true"><span class="material-symbols-outlined">event_repeat</span></div>
        <button type="button" class="arsm-faint inline-flex" data-close-artist-rsm aria-label="Close" style="background:none;border:0;cursor:pointer;padding:0">
          <span class="material-symbols-outlined" style="font-size:22px">close</span>
        </button>
      </div>
      <h2 id="arsmTitle">Request reschedule</h2>
      <p class="arsm-lead">
        Send a reschedule request to <strong id="arsmClient">the client</strong>. The client picks a new date and time from their dashboard.
      </p>
    </div>
    <div class="arsm-mb">
      <div class="arsm-card">
        <div id="arsmAvatar" class="arsm-ini">?</div>
        <div class="arsm-card-main">
          <div id="arsmCardTitle" class="arsm-card-title">—</div>
          <div id="arsmCardMeta" class="arsm-card-meta">—</div>
        </div>
        <span id="arsmStatus" class="arsm-pill g">Confirmed</span>
      </div>
      <label for="arsmReason" class="arsm-fl">Reason <span style="color:#C62828">*</span></label>
      <textarea id="arsmReason" class="arsm-ta" rows="4" maxlength="1000"
        placeholder="e.g. Unexpected emergency at the studio, please choose a new slot."></textarea>
      <p class="arsm-help">Sent to the client with the request</p>
      <p id="arsmError" class="arsm-err"></p>
    </div>
    <div class="arsm-mf">
      <button type="button" class="arsm-btn ghost" data-close-artist-rsm>Back</button>
      <button type="button" id="arsmSubmit" class="arsm-btn"><span class="material-symbols-outlined">send</span>Send request</button>
    </div>
  </div>
</div>

<div id="artistMarkCompleteModal"
  class="artist-complete-modal fixed inset-0 z-[116] flex items-end sm:items-center justify-center p-4 sm:p-6"
  aria-hidden="true"
  aria-modal="true"
  role="dialog"
  aria-labelledby="amcTitle">
  <div class="artist-complete-backdrop absolute inset-0 bg-black/45" data-close-artist-complete></div>
  <div class="artist-complete-panel relative w-full max-w-lg rounded-2xl bg-white shadow-xl border border-outline-variant/20">
    <div class="px-5 py-4 border-b border-outline-variant/15 flex items-center justify-between">
      <h3 id="amcTitle" class="text-lg font-bold text-on-surface">Mark Booking Completed</h3>
      <button type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-xl text-on-surface-variant hover:bg-surface-container-low" data-close-artist-complete aria-label="Close">
        <span class="material-symbols-outlined text-[22px]">close</span>
      </button>
    </div>
    <div class="p-5 space-y-4">
      <p class="text-sm text-on-surface-variant">
        Ask <strong id="amcClient" class="text-on-surface">the client</strong> for their completion code (shared in booking confirmation email) and enter it below.
      </p>
      <div class="rounded-lg bg-surface-container-low px-3 py-2 text-sm">
        <p class="text-on-surface-variant text-xs">Booking</p>
        <p id="amcService" class="font-semibold text-on-surface">—</p>
      </div>
      <div>
        <label for="amcCode" class="block text-sm font-semibold text-on-surface mb-1.5">Completion code <span class="text-error">*</span></label>
        <input id="amcCode" type="text" maxlength="32" autocomplete="off"
          class="w-full text-sm border border-outline-variant/30 rounded-xl px-3 py-2 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30"
          placeholder="Enter code from client">
      </div>
      <p id="amcError" class="hidden text-xs font-semibold text-error"></p>
      <div class="flex items-center justify-end gap-2 pt-1">
        <button type="button" class="px-4 py-2.5 rounded-xl text-sm font-semibold border border-outline-variant/40 text-on-surface hover:bg-surface-container-low" data-close-artist-complete>Back</button>
        <button type="button" id="amcSubmit" class="px-4 py-2.5 rounded-xl text-sm font-semibold bg-emerald-700 text-white hover:opacity-95 disabled:opacity-60 disabled:cursor-not-allowed">Confirm completed</button>
      </div>
    </div>
  </div>
</div>

@include('artist.partials.payment-link-modal')
@endsection

@section('scripts')
@include('artist.partials.payment-link-modal-script')
<script src="{{ asset('js/question-answer-display.js') }}"></script>
<script>
(function () {
  var menu = null;
  var shade = null;
  var currentTrigger = null;

  function closeBookingMenu() {
    if (menu) { menu.remove(); menu = null; }
    if (shade) { shade.remove(); shade = null; }
    if (currentTrigger) {
      currentTrigger.classList.remove('bk-bpm-on');
      currentTrigger.setAttribute('aria-expanded', 'false');
      currentTrigger = null;
    }
  }

  function openBookingMenu(trigger) {
    var source = null;
    if (trigger.classList.contains('js-cal-event')) {
      var bid = trigger.getAttribute('data-cal-bid');
      var row = bid ? document.querySelector('[data-booking-row][data-booking-id="' + bid + '"]') : null;
      source = row ? row.querySelector('.js-artist-booking-menu-source') : null;
      if (!source) {
        var viewBtn = bid ? document.querySelector('.js-artist-booking-view[data-booking-id="' + bid + '"]') : null;
        if (viewBtn) viewBtn.click();
        return;
      }
    } else {
      source = trigger.parentElement && trigger.parentElement.querySelector('.js-artist-booking-menu-source');
    }
    if (!source) return;

    closeBookingMenu();
    currentTrigger = trigger;
    trigger.classList.add('bk-bpm-on');
    trigger.setAttribute('aria-expanded', 'true');

    menu = document.createElement('div');
    menu.className = 'bk-bpm';
    menu.setAttribute('role', 'menu');

    var items = Array.prototype.slice.call(source.children).filter(function (el) {
      return !el.classList.contains('hidden') && el.getAttribute('data-menu-label');
    });

    items.forEach(function (src) {
      if (src.getAttribute('data-menu-divider-before') === '1') {
        menu.appendChild(document.createElement('hr'));
      }
      var label = src.getAttribute('data-menu-label') || '';
      var icon = src.getAttribute('data-menu-icon') || 'more_horiz';
      var danger = src.getAttribute('data-menu-danger') === '1';
      var btn = document.createElement('button');
      btn.type = 'button';
      btn.setAttribute('role', 'menuitem');
      if (danger) btn.className = 'red';
      btn.innerHTML = '<span class="material-symbols-outlined ms">' + icon + '</span>' + label;
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        closeBookingMenu();
        if (src.classList.contains('js-artist-message-client')) {
          if (typeof window.openArtistBookingMessageModal === 'function') {
            window.openArtistBookingMessageModal(src);
          } else if (src.getAttribute('href')) {
            window.location.href = src.getAttribute('href');
          }
          return;
        }
        if (src.classList.contains('js-artist-send-aftercare')) {
          if (typeof window.openArtistAftercareSendModal === 'function') {
            window.openArtistAftercareSendModal(src.getAttribute('data-booking-id'), {
              alreadySent: src.getAttribute('data-aftercare-sent') === '1',
              toLine: [
                'To ' + (src.getAttribute('data-client-name') || 'Client'),
                src.getAttribute('data-client-email') || '',
                src.getAttribute('data-service') || '',
                src.getAttribute('data-booking-ref') || ''
              ].filter(Boolean).join(' · ')
            });
          }
          return;
        }
        if (src.tagName === 'A' && src.getAttribute('href')) {
          window.location.href = src.getAttribute('href');
          return;
        }
        src.click();
      });
      menu.appendChild(btn);
    });

    if (!menu.children.length) {
      closeBookingMenu();
      return;
    }

    shade = document.createElement('div');
    shade.className = 'bk-bpm-sh';
    shade.addEventListener('click', closeBookingMenu);
    document.body.appendChild(shade);
    document.body.appendChild(menu);

    var r = trigger.getBoundingClientRect();
    var left = r.right + window.scrollX + 5 - menu.offsetWidth;
    if (left < window.scrollX + 8) left = window.scrollX + 8;
    var top = r.bottom + window.scrollY + 8;
    if (r.bottom + 8 + menu.offsetHeight > window.innerHeight - 8 && r.top - 8 - menu.offsetHeight > 8) {
      top = r.top + window.scrollY - 8 - menu.offsetHeight;
    }
    menu.style.left = left + 'px';
    menu.style.top = top + 'px';
    var first = menu.querySelector('button');
    if (first) first.focus({ preventScroll: true });
  }

  document.addEventListener('click', function (e) {
    if (menu && menu.contains(e.target)) return;
    var trigger = e.target.closest && e.target.closest('.js-artist-booking-menu, .js-cal-event');
    if (trigger) {
      e.preventDefault();
      e.stopPropagation();
      if (currentTrigger === trigger) {
        closeBookingMenu();
        return;
      }
      openBookingMenu(trigger);
      return;
    }
    if (menu) closeBookingMenu();
  }, true);

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeBookingMenu();
  });
  window.addEventListener('resize', closeBookingMenu);
  window.closeArtistBookingMenu = closeBookingMenu;
})();
</script>
<script>
(function () {
  var liveUrl = @json(route('api.bookings.live-status'));
  var completedBadgeClass = 'js-artist-status-badge inline-flex items-center text-xs font-semibold px-2.5 py-1 rounded-full ring-1 ring-inset bg-slate-100 text-slate-700 ring-slate-500/15';

  function markSettled(bookingId, message) {
    var id = String(bookingId || '');
    if (!id) return;
    document.querySelectorAll('[data-booking-row][data-booking-id="' + id + '"]').forEach(function (row) {
      row.setAttribute('data-status', 'completed');
      row.querySelectorAll('.js-artist-collect-wrap').forEach(function (el) {
        el.classList.add('hidden');
      });
      row.querySelectorAll('.js-artist-booking-menu-source > *').forEach(function (child) {
        var href = child.getAttribute('href') || '';
        var keep = child.classList.contains('js-artist-booking-view') || href.indexOf('chat') !== -1;
        if (!keep) {
          child.classList.add('hidden');
        }
      });
      row.querySelectorAll('.js-artist-booking-view').forEach(function (btn) {
        btn.setAttribute('data-booking-status', 'completed');
        btn.setAttribute('data-status-label', 'Completed');
        btn.setAttribute('data-status-badge-class', 'bg-slate-100 text-slate-700 ring-slate-500/15');
      });
      row.querySelectorAll('.js-artist-status-badge').forEach(function (badge) {
        badge.className = completedBadgeClass + (badge.classList.contains('shrink-0') ? ' shrink-0' : '');
        badge.textContent = 'Completed';
      });
    });
    if (message && typeof showSaveToast === 'function') {
      showSaveToast(message);
    }
  }

  function ifSettled(bookingId, onOpen, checkBalanceCollected) {
    var id = String(bookingId || '');
    if (!id) {
      if (onOpen) onOpen();
      return;
    }
    fetch(liveUrl + '?ids=' + encodeURIComponent(id), {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
    })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        var bookings = (data && data.bookings) || [];
        var item = null;
        for (var i = 0; i < bookings.length; i++) {
          if (String(bookings[i].id) === id) {
            item = bookings[i];
            break;
          }
        }
        var alreadyDone = item && (item.completed || item.settled);
        if (checkBalanceCollected && item && item.balance_collected) {
          alreadyDone = true;
        }
        if (alreadyDone) {
          markSettled(id, item.message || 'This booking is already completed.');
          return;
        }
        if (onOpen) onOpen();
      })
      .catch(function () {
        if (onOpen) onOpen();
      });
  }

  window.artistBookingsMarkSettled = markSettled;
  window.artistBookingsIfSettled = ifSettled;
})();
</script>
<script>
(function () {
  var form = document.getElementById('bookingsFilterForm');
  var filterInput = document.getElementById('filterInput');
  var pills = document.querySelectorAll('.js-filter-status');
  var sortBy = document.getElementById('sortBy');
  var dateFrom = document.getElementById('dateFrom');
  var dateTo = document.getElementById('dateTo');
  var searchClient = document.getElementById('searchClient');
  var searchTimer = null;

  function submitForm() {
    if (form) form.submit();
  }

  pills.forEach(function (btn) {
    btn.addEventListener('click', function () {
      var next = btn.getAttribute('data-filter-status') || 'upcoming';
      if (filterInput) filterInput.value = next;
      pills.forEach(function (p) { p.classList.toggle('on', p === btn); });
      submitForm();
    });
  });

  if (sortBy) sortBy.addEventListener('change', submitForm);
  if (dateFrom) dateFrom.addEventListener('change', submitForm);
  if (dateTo) dateTo.addEventListener('change', submitForm);
  if (searchClient) {
    searchClient.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        submitForm();
      }
    });
    searchClient.addEventListener('input', function () {
      clearTimeout(searchTimer);
      searchTimer = setTimeout(submitForm, 450);
    });
  }
})();
</script>
<script>
(function() {
  var listView = document.getElementById('listView');
  var calendarView = document.getElementById('calendarView');
  var listBtn = document.getElementById('viewListBtn');
  var calBtn = document.getElementById('viewCalendarBtn');
  var calBody = document.getElementById('calBody');
  var calTitle = document.getElementById('calTitle');
  var calPrev = document.getElementById('calPrev');
  var calNext = document.getElementById('calNext');
  var calToday = document.getElementById('calToday');
  var modeBtns = document.querySelectorAll('.cal-mode-btn');
  var dayTitle = document.getElementById('calDayTitle');
  var dayMeta = document.getElementById('calDayMeta');
  var dayList = document.getElementById('calDayList');
  var dayPanel = document.getElementById('calDayPanel');

  if (!listView || !calendarView) return;

  function setView(view) {
    if (view === 'calendar') {
      listView.classList.add('hidden');
      calendarView.classList.remove('hidden');
      listBtn.classList.remove('on');
      calBtn.classList.add('on');
      renderCalendar();
    } else {
      listView.classList.remove('hidden');
      calendarView.classList.add('hidden');
      calBtn.classList.remove('on');
      listBtn.classList.add('on');
    }
  }

  listBtn.addEventListener('click', function() { setView('list'); });
  calBtn.addEventListener('click', function() { setView('calendar'); });

  var calMode = 'month';
  var calDate = new Date();
  calDate.setHours(0,0,0,0);
  var bookings = @json($calendarBookings);

  function fmt(d) {
    return d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0');
  }
  var selectedDay = fmt(calDate);

  function setMode(m) {
    calMode = m;
    if (m === 'day' && selectedDay) {
      var parts = selectedDay.split('-');
      if (parts.length === 3) {
        calDate = new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10));
        calDate.setHours(0, 0, 0, 0);
      }
    }
    modeBtns.forEach(function(b) {
      var on = b.getAttribute('data-cal-mode') === m;
      b.classList.toggle('active', on);
      b.classList.toggle('bg-[#1A1A1A]', on);
      b.classList.toggle('text-white', on);
      b.classList.toggle('text-on-surface-variant', !on);
    });
    renderCalendar();
  }

  modeBtns.forEach(function(b) {
    b.addEventListener('click', function() { setMode(b.getAttribute('data-cal-mode')); });
  });

  calPrev.addEventListener('click', function() { navigate(-1); });
  calNext.addEventListener('click', function() { navigate(1); });
  calToday.addEventListener('click', function() {
    calDate = new Date();
    calDate.setHours(0,0,0,0);
    selectedDay = fmt(calDate);
    renderCalendar();
  });

  function navigate(dir) {
    if (calMode === 'month') calDate.setMonth(calDate.getMonth() + dir);
    else if (calMode === 'day') {
      calDate.setDate(calDate.getDate() + dir);
      selectedDay = fmt(calDate);
    } else {
      calDate.setDate(calDate.getDate() + 7 * dir);
    }
    renderCalendar();
  }

  function bookingsFor(dateStr) {
    return bookings.filter(function(b) { return b.date === dateStr; });
  }

  function esc(s) {
    return String(s || '').replace(/[&<>"']/g, function(c) {
      return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]);
    });
  }

  function shortName(name) {
    var parts = String(name || '').trim().split(/\s+/);
    if (parts.length === 1) return parts[0];
    return parts[0] + ' ' + parts[parts.length - 1].charAt(0) + '.';
  }

  function eventChip(b) {
    var cls = b.status === 'pending' ? 'ev-p' : (b.status === 'confirmed' ? 'ev-c' : 'ev-k');
    return '<div class="' + cls + ' js-cal-event" data-cal-bid="' + esc(b.id) + '" title="' + esc(b.client) + ' · ' + esc(b.ref) + '"><b>' + esc(b.time || '') + '</b> ' + esc(shortName(b.client)) + '</div>';
  }

  function renderDayPanel(ds) {
    selectedDay = ds;
    if (!dayTitle || !dayList) return;
    if (dayPanel) dayPanel.classList.remove('hidden');
    var d = new Date(ds + 'T12:00:00');
    dayTitle.textContent = d.toLocaleDateString('en-GB', { weekday: 'short', month: 'short', day: 'numeric' });
    var evts = bookingsFor(ds);
    var hours = 0;
    evts.forEach(function(b) {
      if (b.time && b.end) {
        var a = b.time.split(':'), e = b.end.split(':');
        hours += Math.max(0, (parseInt(e[0],10)*60 + parseInt(e[1]||0,10)) - (parseInt(a[0],10)*60 + parseInt(a[1]||0,10))) / 60;
      }
    });
    dayMeta.textContent = evts.length
      ? (evts.length + ' booking' + (evts.length === 1 ? '' : 's') + (hours ? ' · ' + (Math.round(hours * 10) / 10) + 'h booked' : ''))
      : 'No bookings';
    if (!evts.length) {
      dayList.innerHTML = '<p class="text-sm text-[#9A929E] py-4">Nothing scheduled.</p>';
      return;
    }
    dayList.innerHTML = evts.map(function(b) {
      var ini = shortName(b.client).split(/\s+/).map(function(p){ return p.charAt(0); }).join('').slice(0,2).toUpperCase();
      var range = [b.time, b.end].filter(Boolean).join(' – ');
      var statusPill = b.status === 'pending'
        ? '<span class="pill a">Pending</span>'
        : '<span class="pill g">Confirmed</span>';
      return '<div class="py-3 border-b border-[#F3EEF4]">' +
        '<div class="flex items-center gap-2.5">' +
          '<div class="ini" style="width:30px;height:30px;font-size:11px;background:#1A1A1A;color:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700">' + esc(ini) + '</div>' +
          '<div class="flex-1 min-w-0"><b style="font-size:13.5px">' + esc(b.client) + '</b>' +
            '<div class="text-xs text-[#9A929E]">' + esc(b.service || '') + (range ? ' · ' + esc(range) : '') + '</div></div>' +
          '<button type="button" class="js-cal-event material-symbols-outlined text-[#9A929E]" data-cal-bid="' + esc(b.id) + '" style="font-size:22px;background:none;border:0;cursor:pointer">more_horiz</button>' +
        '</div>' +
        '<div class="flex gap-1.5 mt-2 pl-10 flex-wrap">' + statusPill + '</div>' +
      '</div>';
    }).join('');
  }

  function renderMonth() {
    var y = calDate.getFullYear(), m = calDate.getMonth();
    calTitle.textContent = calDate.toLocaleString('default', { month: 'long', year: 'numeric' });
    var first = new Date(y, m, 1);
    var startDay = (first.getDay() + 6) % 7;
    var daysInMonth = new Date(y, m + 1, 0).getDate();
    var today = fmt(new Date());
    var html = '<div class="grid grid-cols-7 text-center text-[11px] font-bold tracking-wider text-[#6F6874] bg-[#FBF8FC] border-b border-[#E8DFEA]">';
    ['MON','TUE','WED','THU','FRI','SAT','SUN'].forEach(function(d) { html += '<div class="py-2.5 px-2">' + d + '</div>'; });
    html += '</div><div class="grid grid-cols-7">';
    var lead = new Date(y, m, 1 - startDay);
    for (var i = 0; i < startDay; i++) {
      html += '<div class="cal-day bg-[#FCFAFD]"><span class="text-xs text-[#C0B6C4]">' + lead.getDate() + '</span></div>';
      lead.setDate(lead.getDate() + 1);
    }
    for (var d = 1; d <= daysInMonth; d++) {
      var ds = fmt(new Date(y, m, d));
      var isSel = ds === selectedDay;
      var evts = bookingsFor(ds);
      html += '<div class="cal-day js-cal-day' + (isSel ? ' ring-2 ring-inset ring-[#3E007C]' : '') + '" data-day="' + ds + '" style="cursor:pointer">';
      html += '<span class="inline-flex items-center justify-center w-[26px] h-[26px] rounded-full text-[13px] font-semibold ' + (isSel ? 'bg-[#3E007C] text-white' : 'text-[#1A1A1A]') + '">' + d + '</span>';
      html += '<div class="flex flex-col gap-1 mt-1">';
      evts.slice(0, 3).forEach(function(b) { html += eventChip(b); });
      if (evts.length > 3) html += '<div class="text-[10px] text-[#9A929E] px-1">+' + (evts.length - 3) + ' more</div>';
      html += '</div></div>';
    }
    var totalCells = startDay + daysInMonth;
    var remaining = (7 - totalCells % 7) % 7;
    for (var j = 0; j < remaining; j++) {
      html += '<div class="cal-day bg-[#FCFAFD]"><span class="text-xs text-[#C0B6C4]">' + (j + 1) + '</span></div>';
    }
    html += '</div>';
    calBody.innerHTML = html;
    renderDayPanel(selectedDay);
  }

  function renderWeek() {
    var start = new Date(calDate);
    var dow = (start.getDay() + 6) % 7;
    start.setDate(start.getDate() - dow);
    var end = new Date(start); end.setDate(end.getDate() + 6);
    calTitle.textContent = start.toLocaleDateString('default', { month: 'short', day: 'numeric' }) + ' – ' + end.toLocaleDateString('default', { month: 'short', day: 'numeric', year: 'numeric' });
    var html = '<div class="grid grid-cols-7 gap-px bg-[#E8DFEA] rounded-none overflow-hidden">';
    for (var i = 0; i < 7; i++) {
      var d = new Date(start); d.setDate(d.getDate() + i);
      var ds = fmt(d);
      var evts = bookingsFor(ds);
      html += '<div class="bg-white min-h-[14rem] p-2 js-cal-day" data-day="' + ds + '" style="cursor:pointer">';
      html += '<div class="text-xs font-semibold mb-2 text-[#6F6874]">' + d.toLocaleDateString('default', { weekday: 'short', day: 'numeric' }) + '</div>';
      html += '<div class="space-y-1">';
      evts.forEach(function(b) { html += eventChip(b); });
      html += '</div></div>';
    }
    html += '</div>';
    calBody.innerHTML = html;
    renderDayPanel(selectedDay);
  }

  function parseHm(t) {
    if (!t) return null;
    var parts = String(t).split(':');
    var h = parseInt(parts[0], 10);
    var m = parseInt(parts[1] || '0', 10);
    if (isNaN(h)) return null;
    return h * 60 + (isNaN(m) ? 0 : m);
  }

  function renderDay() {
    selectedDay = fmt(calDate);
    calTitle.textContent = calDate.toLocaleDateString('default', {
      weekday: 'long',
      month: 'long',
      day: 'numeric',
      year: 'numeric'
    });
    var evts = bookingsFor(selectedDay).slice().sort(function(a, b) {
      return (parseHm(a.time) ?? 9999) - (parseHm(b.time) ?? 9999);
    });
    var startHour = 8;
    var endHour = 20;
    evts.forEach(function(b) {
      var s = parseHm(b.time);
      var e = parseHm(b.end);
      if (s !== null) startHour = Math.min(startHour, Math.floor(s / 60));
      if (e !== null) endHour = Math.max(endHour, Math.ceil(e / 60));
      else if (s !== null) endHour = Math.max(endHour, Math.ceil(s / 60) + 1);
    });
    startHour = Math.max(0, Math.min(23, startHour));
    endHour = Math.max(startHour + 1, Math.min(24, endHour));

    var byHour = {};
    for (var h = startHour; h < endHour; h++) byHour[h] = [];
    var untimed = [];
    evts.forEach(function(b) {
      var s = parseHm(b.time);
      if (s === null) {
        untimed.push(b);
        return;
      }
      var hour = Math.floor(s / 60);
      if (!byHour[hour]) byHour[hour] = [];
      byHour[hour].push(b);
    });

    var html = '<div class="cal-day-view">';
    if (untimed.length) {
      html += '<div class="cal-day-slot"><div class="cal-day-hour">—</div><div class="cal-day-events">';
      untimed.forEach(function(b) {
        var cls = b.status === 'pending' ? 'ev-p' : (b.status === 'confirmed' ? 'ev-c' : 'ev-k');
        var range = [b.time, b.end].filter(Boolean).join(' – ');
        html += '<div class="cal-day-card js-cal-event ' + cls + '" data-cal-bid="' + esc(b.id) + '">' +
          '<div class="flex-1 min-w-0"><b style="font-size:13px">' + esc(b.client) + '</b>' +
          '<div style="font-size:11.5px;opacity:.85">' + esc(b.service || '') + (range ? ' · ' + esc(range) : '') + '</div></div></div>';
      });
      html += '</div></div>';
    }
    for (var hour = startHour; hour < endHour; hour++) {
      var label = String(hour).padStart(2, '0') + ':00';
      var slotEvts = byHour[hour] || [];
      html += '<div class="cal-day-slot"><div class="cal-day-hour">' + label + '</div><div class="cal-day-events">';
      if (!slotEvts.length) {
        html += '<div class="cal-day-empty"> </div>';
      } else {
        slotEvts.forEach(function(b) {
          var cls = b.status === 'pending' ? 'ev-p' : (b.status === 'confirmed' ? 'ev-c' : 'ev-k');
          var range = [b.time, b.end].filter(Boolean).join(' – ');
          html += '<div class="cal-day-card js-cal-event ' + cls + '" data-cal-bid="' + esc(b.id) + '">' +
            '<div class="flex-1 min-w-0"><b style="font-size:13px">' + esc(b.client) + '</b>' +
            '<div style="font-size:11.5px;opacity:.85">' + esc(b.service || '') + (range ? ' · ' + esc(range) : '') + '</div></div></div>';
        });
      }
      html += '</div></div>';
    }
    if (!evts.length) {
      html += '<div class="px-5 py-10 text-sm text-[#9A929E]">Nothing scheduled this day.</div>';
    }
    html += '</div>';
    calBody.innerHTML = html;
    renderDayPanel(selectedDay);
  }

  function renderCalendar() {
    if (dayPanel) {
      // Keep original mobile hide; only show side panel on xl in week/month.
      dayPanel.classList.add('hidden');
      if (calMode === 'day') dayPanel.classList.remove('xl:block');
      else dayPanel.classList.add('xl:block');
    }
    if (calMode === 'day') renderDay();
    else if (calMode === 'week') renderWeek();
    else renderMonth();
  }

  calBody.addEventListener('click', function(e) {
    if (e.target.closest('.js-cal-event')) return;
    var dayEl = e.target.closest('.js-cal-day');
    if (dayEl && dayEl.getAttribute('data-day')) {
      selectedDay = dayEl.getAttribute('data-day');
      renderCalendar();
    }
  });

  renderCalendar();
})();
</script>
<script>
(function () {
  var modal = document.getElementById('artistRescheduleRequestModal');
  if (!modal) return;

  var clientEl = document.getElementById('arsmClient');
  var avatarEl = document.getElementById('arsmAvatar');
  var cardTitleEl = document.getElementById('arsmCardTitle');
  var cardMetaEl = document.getElementById('arsmCardMeta');
  var statusEl = document.getElementById('arsmStatus');
  var reasonEl = document.getElementById('arsmReason');
  var errorEl = document.getElementById('arsmError');
  var submitBtn = document.getElementById('arsmSubmit');
  var activeBtn = null;

  function csrfToken() {
    var m = document.querySelector('meta[name="csrf-token"]');
    return m ? m.getAttribute('content') : '';
  }

  function openModal(btn) {
    activeBtn = btn;
    var clientName = btn.getAttribute('data-client-name') || 'the client';
    var service = btn.getAttribute('data-service') || 'Booking';
    var initials = btn.getAttribute('data-client-initials') || '?';
    var dateLabel = btn.getAttribute('data-booking-date') || '';
    var timeRange = btn.getAttribute('data-time-range') || '';
    var bookingRef = btn.getAttribute('data-booking-ref') || '';
    var statusLabel = btn.getAttribute('data-status-label') || 'Confirmed';
    var statusPill = btn.getAttribute('data-status-pill') || 'g';

    if (clientEl) clientEl.textContent = clientName;
    if (avatarEl) avatarEl.textContent = initials;
    if (cardTitleEl) cardTitleEl.textContent = clientName + ' · ' + service;
    if (cardMetaEl) {
      cardMetaEl.textContent = [dateLabel, timeRange, bookingRef].filter(Boolean).join(' · ') || '—';
    }
    if (statusEl) {
      statusEl.className = 'arsm-pill ' + statusPill;
      statusEl.textContent = statusLabel;
    }
    if (reasonEl) reasonEl.value = '';
    if (errorEl) {
      errorEl.classList.remove('on');
      errorEl.textContent = '';
    }
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
    requestAnimationFrame(function () {
      modal.classList.add('artist-rsm-open');
      if (reasonEl) reasonEl.focus();
    });
  }

  function closeModal() {
    modal.classList.remove('artist-rsm-open');
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
    activeBtn = null;
  }

  function showError(msg) {
    if (!errorEl) return;
    errorEl.textContent = msg || 'Could not send request.';
    errorEl.classList.add('on');
  }

  function submitRequest() {
    if (!activeBtn) return;
    var url = activeBtn.getAttribute('data-request-url');
    if (!url) return;
    var reason = reasonEl ? reasonEl.value.trim() : '';
    if (reason.length < 3) {
      showError('Reason is required (minimum 3 characters).');
      if (reasonEl) reasonEl.focus();
      return;
    }
    if (errorEl) {
      errorEl.classList.remove('on');
      errorEl.textContent = '';
    }
    submitBtn.disabled = true;
    fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken(),
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: JSON.stringify({ reason: reason }),
    })
      .then(function (r) {
        return r.text().then(function (body) {
          var data = {};
          try {
            data = body ? JSON.parse(body) : {};
          } catch (e) {
            data = { message: body ? body.slice(0, 240) : 'HTTP ' + r.status };
          }
          return { ok: r.ok, data: data };
        });
      })
      .then(function (res) {
        if (res.ok && res.data && res.data.success) {
          var msg = (res.data && res.data.message) || 'Reschedule request sent.';
          if (typeof flashSaveToast === 'function') flashSaveToast(msg);
          else if (typeof showSaveToast === 'function') showSaveToast(msg);
          window.location.reload();
          return;
        }
        showError((res.data && res.data.message) || 'Could not send request.');
      })
      .catch(function () {
        showError('Network error. Try again.');
      })
      .finally(function () {
        submitBtn.disabled = false;
      });
  }

  document.querySelectorAll('.js-artist-reschedule-request').forEach(function (btn) {
    btn.addEventListener('click', function () {
      if (btn.getAttribute('data-pending') === '1' || btn.disabled) return;
      openModal(btn);
    });
  });

  modal.querySelectorAll('[data-close-artist-rsm]').forEach(function (el) {
    el.addEventListener('click', closeModal);
  });
  if (submitBtn) submitBtn.addEventListener('click', submitRequest);

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && modal.classList.contains('artist-rsm-open')) {
      closeModal();
    }
  });
})();
</script>
<script>
(function () {
  var modal = document.getElementById('artistMarkCompleteModal');
  if (!modal) return;

  var clientEl = document.getElementById('amcClient');
  var serviceEl = document.getElementById('amcService');
  var codeEl = document.getElementById('amcCode');
  var errorEl = document.getElementById('amcError');
  var submitBtn = document.getElementById('amcSubmit');
  var activeBtn = null;

  function csrfToken() {
    var m = document.querySelector('meta[name="csrf-token"]');
    return m ? m.getAttribute('content') : '';
  }

  function openModal(btn) {
    activeBtn = btn;
    if (clientEl) clientEl.textContent = btn.getAttribute('data-client-name') || 'the client';
    if (serviceEl) serviceEl.textContent = btn.getAttribute('data-service') || 'Booking';
    if (codeEl) codeEl.value = '';
    if (errorEl) {
      errorEl.classList.add('hidden');
      errorEl.textContent = '';
    }
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
    requestAnimationFrame(function () {
      modal.classList.add('artist-complete-open');
    });
  }

  function closeModal() {
    modal.classList.remove('artist-complete-open');
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
    activeBtn = null;
  }

  function showError(msg) {
    if (!errorEl) return;
    errorEl.textContent = msg || 'Something went wrong.';
    errorEl.classList.remove('hidden');
  }

  function submitComplete() {
    if (!activeBtn) return;
    var url = activeBtn.getAttribute('data-mark-completed-url');
    var code = codeEl ? codeEl.value.trim() : '';
    if (!url) return;
    if (!code) {
      showError('Completion code is required.');
      return;
    }
    if (errorEl) errorEl.classList.add('hidden');
    submitBtn.disabled = true;
    fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken(),
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: JSON.stringify({ completion_code: code, confirmed: true }),
    })
      .then(function (r) {
        return r.text().then(function (body) {
          var data = {};
          try { data = body ? JSON.parse(body) : {}; } catch (e) { data = { message: body ? body.slice(0, 240) : 'HTTP ' + r.status }; }
          return { ok: r.ok, data: data };
        });
      })
      .then(function (res) {
        if (res.ok && res.data && res.data.success) {
          var bookingId = activeBtn ? activeBtn.getAttribute('data-booking-id') : '';
          closeModal();
          if (typeof window.artistBookingsMarkSettled === 'function') {
            window.artistBookingsMarkSettled(bookingId, (res.data && res.data.message) || 'Booking marked as completed.');
          } else if (typeof showSaveToast === 'function') {
            showSaveToast((res.data && res.data.message) || 'Booking marked as completed.');
          }
          return;
        }
        showError((res.data && res.data.message) || 'Could not mark completed.');
      })
      .catch(function () {
        showError('Network error. Try again.');
      })
      .finally(function () {
        submitBtn.disabled = false;
      });
  }

  document.querySelectorAll('.js-artist-mark-complete').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var bookingId = btn.getAttribute('data-booking-id');
      if (btn.getAttribute('data-has-balance') === '1') {
        var collectBtn = document.querySelector('.js-artist-collect-payment[data-booking-id="' + bookingId + '"]');
        if (collectBtn) {
          collectBtn.click();
          return;
        }
      }
      if (typeof window.artistBookingsIfSettled === 'function') {
        window.artistBookingsIfSettled(bookingId, function () {
          openModal(btn);
        });
        return;
      }
      openModal(btn);
    });
  });

  modal.querySelectorAll('[data-close-artist-complete]').forEach(function (el) {
    el.addEventListener('click', closeModal);
  });
  if (submitBtn) submitBtn.addEventListener('click', submitComplete);
  if (codeEl) {
    codeEl.addEventListener('input', function () {
      if (errorEl && codeEl.value.trim()) errorEl.classList.add('hidden');
    });
  }

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && modal.classList.contains('artist-complete-open')) {
      closeModal();
    }
  });
})();
</script>
<script>
(function () {
  var modal = document.getElementById('artistCancelBookingModal');
  if (!modal) return;

  var bookingIdEl = document.getElementById('acbmBookingId');
  var windowEl = document.getElementById('acbmWindow');
  var refundLeadEl = document.getElementById('acbmRefundLead');
  var refundSubEl = document.getElementById('acbmRefundSub');
  var reasonEl = document.getElementById('acbmReason');
  var reasonErrorEl = document.getElementById('acbmReasonError');
  var errorEl = document.getElementById('acbmError');
  var submitBtn = document.getElementById('acbmSubmit');
  var active = null;

  function csrfToken() {
    var m = document.querySelector('meta[name="csrf-token"]');
    return m ? m.getAttribute('content') : '';
  }

  function fmtMoney(n, currency) {
    var v = parseFloat(n);
    if (isNaN(v)) v = 0;
    var cc = (currency || 'EUR').toUpperCase();
    var symbol = cc === 'EUR' ? '€' : (cc === 'USD' ? '$' : cc + ' ');
    return symbol + v.toFixed(2);
  }

  function showError(msg) {
    if (!errorEl) return;
    errorEl.textContent = msg || 'Could not load cancellation info.';
    errorEl.classList.remove('hidden');
  }

  function clearError() {
    if (!errorEl) return;
    errorEl.classList.add('hidden');
    errorEl.textContent = '';
  }

  function openModal() {
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
    requestAnimationFrame(function () {
      modal.classList.add('artist-cbm-open');
    });
  }

  function closeModal() {
    modal.classList.remove('artist-cbm-open');
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
    active = null;
    clearError();
    if (reasonEl) reasonEl.value = '';
    if (reasonErrorEl) reasonErrorEl.classList.add('hidden');
  }

  function setRefundUi(data) {
    var estimated = data && data.estimated_refund ? data.estimated_refund : {};
    var amount = parseFloat(estimated.amount || 0);
    var forfeited = parseFloat(estimated.deposit_forfeited || 0);
    var currency = data && data.currency ? data.currency : 'EUR';
    var eligibility = (data && data.refund_eligibility) ? data.refund_eligibility : '';
    if (eligibility === 'full_refund') {
      refundLeadEl.textContent = 'Full refund will be given to the user.';
      refundSubEl.textContent = 'Refund amount: ' + fmtMoney(amount, currency);
    } else if (eligibility === 'partial_refund') {
      refundLeadEl.textContent = 'A partial refund will be given to the user.';
      refundSubEl.textContent = 'Refund amount: ' + fmtMoney(amount, currency) + ' (deposit forfeited: ' + fmtMoney(forfeited, currency) + ')';
    } else {
      refundLeadEl.textContent = 'No refund will be given to the user.';
      refundSubEl.textContent = 'Deposit forfeited: ' + fmtMoney(forfeited, currency);
    }
  }

  function loadInfoAndOpen(btn) {
    var infoUrl = btn.getAttribute('data-cancel-info-url');
    var bookingId = btn.getAttribute('data-booking-id') || '—';
    var bookingRef = btn.getAttribute('data-booking-ref') || '';
    if (bookingIdEl) bookingIdEl.textContent = bookingRef || ('INK-FL-' + String(bookingId).padStart(5, '0'));
    if (windowEl) windowEl.textContent = 'Loading...';
    if (refundLeadEl) refundLeadEl.textContent = 'Loading refund details...';
    if (refundSubEl) refundSubEl.textContent = '';
    clearError();
    openModal();
    if (!infoUrl) {
      showError('Cancellation info URL is missing.');
      return;
    }
    fetch(infoUrl, {
      method: 'GET',
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
    })
      .then(function (r) {
        return r.json().then(function (data) {
          return { ok: r.ok, data: data };
        });
      })
      .then(function (res) {
        if (!(res.ok && res.data && res.data.success && res.data.data)) {
          throw new Error((res.data && res.data.message) || 'Could not load cancellation info.');
        }
        var info = res.data.data;
        var w = parseInt(info.cancellation_window_hours || 0, 10);
        if (windowEl) windowEl.textContent = (w > 0 ? (w + ' hours') : '—');
        setRefundUi(info);
      })
      .catch(function (err) {
        if (windowEl) windowEl.textContent = '—';
        if (refundLeadEl) refundLeadEl.textContent = 'Could not load refund details.';
        if (refundSubEl) refundSubEl.textContent = '';
        showError(err && err.message ? err.message : 'Network error while loading details.');
      });
  }

  function submitCancellation() {
    if (!active) return;
    var reason = reasonEl ? reasonEl.value.trim() : '';
    if (!reason) {
      if (reasonErrorEl) reasonErrorEl.classList.remove('hidden');
      return;
    }
    if (reasonErrorEl) reasonErrorEl.classList.add('hidden');
    clearError();
    submitBtn.disabled = true;
    fetch(active.getAttribute('data-cancel-url'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken(),
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: JSON.stringify({ confirmed: true, reason: reason }),
    })
      .then(function (r) {
        return r.text().then(function (body) {
          var data = {};
          try {
            data = body ? JSON.parse(body) : {};
          } catch (e) {
            data = { message: body ? body.slice(0, 240) : 'HTTP ' + r.status };
          }
          return { ok: r.ok, data: data };
        });
      })
      .then(function (res) {
        if (res.ok && res.data && res.data.success) {
          if (typeof flashSaveToast === 'function') {
            flashSaveToast((res.data && res.data.message) || 'Booking cancelled.');
          }
          window.location.reload();
          return;
        }
        showError((res.data && (res.data.message || (res.data.errors && JSON.stringify(res.data.errors)))) || 'Could not cancel.');
      })
      .catch(function () {
        showError('Network error. Try again.');
      })
      .finally(function () {
        submitBtn.disabled = false;
      });
  }

  document.querySelectorAll('.js-artist-cancel-booking').forEach(function (btn) {
    btn.addEventListener('click', function () {
      active = btn;
      loadInfoAndOpen(btn);
    });
  });

  modal.querySelectorAll('[data-close-artist-cbm]').forEach(function (el) {
    el.addEventListener('click', closeModal);
  });

  if (reasonEl) {
    reasonEl.addEventListener('input', function () {
      if (reasonErrorEl && reasonEl.value.trim()) reasonErrorEl.classList.add('hidden');
    });
  }
  if (submitBtn) submitBtn.addEventListener('click', submitCancellation);

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && modal.classList.contains('artist-cbm-open')) {
      closeModal();
    }
  });
})();
</script>
<script>
(function () {
  var modal = document.getElementById('artistBookingDetailModal');
  if (!modal) return;

  var titleEl = document.getElementById('abdmTitle');
  var imgWrap = document.getElementById('abdmImageWrap');
  var refEl = document.getElementById('abdmRef');
  var statusEl = document.getElementById('abdmStatus');
  var typeEl = document.getElementById('abdmType');
  var nameEl = document.getElementById('abdmClientName');
  var emailRow = document.getElementById('abdmClientEmailRow');
  var emailEl = document.getElementById('abdmClientEmail');
  var phoneRow = document.getElementById('abdmClientPhoneRow');
  var phoneEl = document.getElementById('abdmClientPhone');
  var serviceEl = document.getElementById('abdmService');
  var dateEl = document.getElementById('abdmDate');
  var timeEl = document.getElementById('abdmTime');
  var durEl = document.getElementById('abdmDuration');
  var tzEl = document.getElementById('abdmTimezone');
  var depEl = document.getElementById('abdmDeposit');
  var balEl = document.getElementById('abdmBalance');
  var moneyPill = document.getElementById('abdmMoneyPill');
  var moneyDepositLabel = document.getElementById('abdmMoneyDepositLabel');
  var moneyDeposit = document.getElementById('abdmMoneyDeposit');
  var moneyNote = document.getElementById('abdmMoneyNote');
  var moneyFootnote = document.getElementById('abdmMoneyFootnote');
  var bookingFeeRow = document.getElementById('abdmBookingFeeRow');
  var bookingFeeNote = document.getElementById('abdmBookingFeeNote');
  var bookingFeeEl = document.getElementById('abdmBookingFee');
  var stripeFeeRow = document.getElementById('abdmStripeFeeRow');
  var stripeFeeEl = document.getElementById('abdmStripeFee');
  var splitRow = document.getElementById('abdmSplitRow');
  var splitLabel = document.getElementById('abdmSplitLabel');
  var splitValue = document.getElementById('abdmSplitValue');
  var yourShareRow = document.getElementById('abdmYourShareRow');
  var yourShareNote = document.getElementById('abdmYourShareNote');
  var yourShareEl = document.getElementById('abdmYourShare');
  var yourStripeRow = document.getElementById('abdmYourStripeRow');
  var yourStripeNote = document.getElementById('abdmYourStripeNote');
  var yourStripeEl = document.getElementById('abdmYourStripe');
  var yourBookingFeeRow = document.getElementById('abdmYourBookingFeeRow');
  var yourBookingFeeNote = document.getElementById('abdmYourBookingFeeNote');
  var yourBookingFeeEl = document.getElementById('abdmYourBookingFee');
  var studioGetsRow = document.getElementById('abdmStudioGetsRow');
  var studioGetsLabel = document.getElementById('abdmStudioGetsLabel');
  var studioGetsEl = document.getElementById('abdmStudioGets');
  var youGetEl = document.getElementById('abdmYouGet');
  var paidRow = document.getElementById('abdmPaidRow');
  var paidEl = document.getElementById('abdmPaid');
  var qaListEl = document.getElementById('abdmQaList');
  var qaEmptyEl = document.getElementById('abdmQaEmpty');
  var panelDetails = document.getElementById('abdmPanelDetails');
  var panelRequest = document.getElementById('abdmPanelRequest');
  var tabButtons = modal.querySelectorAll('.abdm-tabs [data-abdm-tab]');
  var messageBtn = document.getElementById('abdmMessageBtn');
  var aftercareBtn = document.getElementById('abdmAftercareBtn');
  var aftercareLabel = document.getElementById('abdmAftercareLabel');
  var confirmBtn = document.getElementById('abdmConfirmBtn');
  var currentSourceBtn = null;
  var aftercareUrl = @json(route('artist.aftercare.index'));
  var fallbackImg = @json(asset('design/images/icons/avatar.jpg'));

  function statusPillClass(status) {
    var s = String(status || '').toLowerCase();
    if (s === 'confirmed') return 'abdm-pill g';
    if (s === 'pending') return 'abdm-pill a';
    if (s === 'cancelled' || s === 'declined') return 'abdm-pill r';
    if (s === 'rescheduled') return 'abdm-pill b';
    return 'abdm-pill k';
  }

  function setAbdmTab(tab) {
    var isDetails = tab !== 'request';
    tabButtons.forEach(function (btn) {
      var selected = btn.getAttribute('data-abdm-tab') === (isDetails ? 'details' : 'request');
      btn.setAttribute('aria-selected', selected ? 'true' : 'false');
      btn.classList.toggle('on', selected);
    });
    if (panelDetails) panelDetails.hidden = !isDetails;
    if (panelRequest) panelRequest.hidden = isDetails;
  }

  tabButtons.forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.stopPropagation();
      setAbdmTab(btn.getAttribute('data-abdm-tab') || 'details');
    });
  });

  function rowFor(btn) {
    var id = btn && btn.getAttribute('data-booking-id');
    return id ? document.querySelector('[data-booking-row][data-booking-id="' + id + '"]') : null;
  }

  function syncFooter(btn) {
    var row = rowFor(btn);
    var ds = btn && btn.dataset ? btn.dataset : {};
    var status = String(ds.bookingStatus || (row && row.getAttribute('data-status')) || '').toLowerCase();
    var chat = row ? row.querySelector('.js-artist-booking-menu-source .js-artist-message-client') : null;
    var complete = row ? row.querySelector('.js-artist-booking-menu-source .js-artist-mark-complete') : null;
    var aftercareLink = row ? row.querySelector('.js-artist-booking-menu-source .js-artist-send-aftercare') : null;
    var collect = row
      ? (row.querySelector('.js-artist-booking-menu-source .js-artist-collect-payment')
        || row.querySelector('.js-artist-collect-payment'))
      : null;

    if (messageBtn) {
      if (chat) {
        messageBtn.style.display = '';
        messageBtn._chatSource = chat;
      } else {
        messageBtn.style.display = 'none';
        messageBtn._chatSource = null;
      }
    }

    if (aftercareBtn) {
      aftercareBtn.style.display = '';
      aftercareBtn.disabled = false;
      aftercareBtn.setAttribute('aria-disabled', 'false');
      aftercareBtn._source = null;
      aftercareBtn._mode = 'none';
      aftercareBtn.title = '';

      var sent = ds.aftercareSent === '1' || (aftercareLink && /sent/i.test(aftercareLink.getAttribute('data-menu-label') || ''));
      var label = 'Send aftercare';

      if (complete) {
        aftercareBtn._mode = 'send';
        aftercareBtn._source = complete;
        label = complete.getAttribute('data-menu-label') || 'Complete booking';
      } else if (status === 'completed' || aftercareLink) {
        aftercareBtn._mode = 'manage';
        aftercareBtn._source = aftercareLink || null;
        label = (aftercareLink && aftercareLink.getAttribute('data-menu-label'))
          || (sent ? 'Send again' : 'Send aftercare');
      } else if (collect) {
        aftercareBtn._mode = 'collect';
        aftercareBtn._source = collect;
        label = 'Request payment';
        aftercareBtn.title = 'Collect the balance before completing the booking';
      } else {
        aftercareBtn._mode = 'none';
        aftercareBtn.disabled = true;
        aftercareBtn.setAttribute('aria-disabled', 'true');
        aftercareBtn.title = status === 'confirmed'
          ? 'Collect the balance before completing the booking'
          : 'Complete booking is available after the booking is confirmed';
      }

      if (aftercareLabel) aftercareLabel.textContent = label;
    }

    if (confirmBtn) {
      var action = collect || complete;
      confirmBtn.style.display = action ? '' : 'none';
      confirmBtn._source = action || null;
    }
  }

  function openModal(btn) {
    var ds = btn.dataset;
    currentSourceBtn = btn;
    setAbdmTab('details');
    if (titleEl) titleEl.textContent = ds.service || 'Booking';
    if (imgWrap) {
      var src = ds.designImage || fallbackImg;
      imgWrap.style.backgroundImage = "url('" + String(src).replace(/'/g, "\\'") + "')";
    }
    if (refEl) refEl.textContent = ds.bookingRef || '';
    if (statusEl) {
      statusEl.textContent = ds.statusLabel || '—';
      statusEl.className = statusPillClass(ds.bookingStatus || ds.statusLabel);
      statusEl.style.margin = '6px 0';
      statusEl.style.display = 'inline-flex';
    }
    if (typeEl) typeEl.textContent = ds.bookingType ? ds.bookingType + ' booking' : '—';
    if (nameEl) nameEl.textContent = ds.clientName || '—';
    if (emailRow && emailEl) {
      var em = (ds.clientEmail || '').trim();
      emailRow.style.display = em ? '' : 'none';
      emailEl.textContent = em || '—';
    }
    if (phoneRow && phoneEl) {
      var phone = (ds.clientPhone || '').trim();
      phoneRow.style.display = phone ? '' : 'none';
      phoneEl.textContent = phone || '—';
    }
    if (serviceEl) serviceEl.textContent = ds.service || '—';
    if (dateEl) dateEl.textContent = ds.dateDisplay || '—';
    if (timeEl) timeEl.textContent = ds.timeRange || '—';
    if (durEl) durEl.textContent = ds.duration || '—';
    if (tzEl) tzEl.textContent = ds.timezone || 'UTC';
    if (depEl) depEl.textContent = ds.deposit || '—';
    if (balEl) balEl.textContent = ds.balance || '—';

    function parseMoneyPayload(raw) {
      if (!raw) return null;
      if (typeof raw === 'object') return raw;
      var text = String(raw).trim();
      if (!text) return null;
      try {
        if (text.charAt(0) === '{' || text.charAt(0) === '[') {
          return JSON.parse(text);
        }
        var binary = atob(text);
        var jsonText = binary;
        if (typeof TextDecoder !== 'undefined') {
          var bytes = new Uint8Array(binary.length);
          for (var i = 0; i < binary.length; i++) bytes[i] = binary.charCodeAt(i);
          jsonText = new TextDecoder('utf-8').decode(bytes);
        } else {
          jsonText = decodeURIComponent(escape(binary));
        }
        return JSON.parse(jsonText);
      } catch (err) {
        return null;
      }
    }
    var money = parseMoneyPayload(btn.getAttribute('data-money') || ds.money || '');
    if (!money) {
      var moneyRow = rowFor(btn);
      money = parseMoneyPayload(moneyRow ? moneyRow.getAttribute('data-money') : '');
    }

    if (money) {
      if (moneyDeposit) moneyDeposit.textContent = money.deposit || ds.deposit || '—';
      if (moneyDepositLabel) moneyDepositLabel.textContent = money.deposit_label || 'Client paid · deposit';
      if (moneyPill) {
        moneyPill.textContent = money.pill || 'Pending';
        moneyPill.className = 'abdm-pill ' + (money.pill_class || 'a');
      }
      var hasSplit = !!money.has_split;
      var feeNote = String(money.booking_fee_note || '');
      // Match earnings reference: fee + Stripe + split stack, studio share only in footnote.
      if (bookingFeeRow && bookingFeeEl) {
        if (money.booking_fee) {
          bookingFeeRow.style.display = '';
          bookingFeeEl.textContent = money.booking_fee;
          if (bookingFeeNote) bookingFeeNote.textContent = feeNote;
        } else {
          bookingFeeRow.style.display = 'none';
        }
      }
      if (stripeFeeRow && stripeFeeEl) {
        if (money.stripe_fee) {
          stripeFeeRow.style.display = '';
          stripeFeeEl.textContent = String(money.stripe_fee || '—').replace(/^-€/, '−€');
        } else {
          stripeFeeRow.style.display = 'none';
        }
      }
      if (splitRow) {
        splitRow.style.display = '';
        if (splitLabel) splitLabel.textContent = money.split_label || 'Split';
        if (splitValue) splitValue.textContent = money.split_value || '—';
      }
      if (yourShareRow && yourShareEl) {
        if (hasSplit) {
          yourShareRow.style.display = '';
          yourShareEl.textContent = money.your_share || '—';
          if (yourShareNote) yourShareNote.textContent = money.your_share_note || '';
        } else {
          yourShareRow.style.display = 'none';
        }
      }
      if (yourStripeRow && yourStripeEl) {
        if (hasSplit) {
          yourStripeRow.style.display = '';
          yourStripeEl.textContent = String(money.your_stripe || '—').replace(/^-€/, '−€');
          if (yourStripeNote) yourStripeNote.textContent = money.your_stripe_note || '';
        } else {
          yourStripeRow.style.display = 'none';
        }
      }
      if (yourBookingFeeRow && yourBookingFeeEl) {
        if (hasSplit && money.your_booking_fee) {
          yourBookingFeeRow.style.display = '';
          yourBookingFeeEl.textContent = String(money.your_booking_fee || '—').replace(/^-€/, '−€');
          if (yourBookingFeeNote) yourBookingFeeNote.textContent = money.your_booking_fee_note || '';
        } else {
          yourBookingFeeRow.style.display = 'none';
        }
      }
      // Studio share stays in the footnote (reference layout) — not a separate row.
      if (studioGetsRow) studioGetsRow.style.display = 'none';
      if (youGetEl) youGetEl.textContent = money.you_get || '—';
      if (moneyFootnote) {
        if (money.footnote) {
          moneyFootnote.style.display = '';
          moneyFootnote.textContent = money.footnote;
        } else {
          moneyFootnote.style.display = 'none';
          moneyFootnote.textContent = '';
        }
      }
      if (moneyNote) {
        if (money.balance_note_html) {
          moneyNote.style.display = '';
          moneyNote.innerHTML = money.balance_note_html;
        } else {
          moneyNote.style.display = 'none';
          moneyNote.textContent = '';
        }
      }
    } else {
      if (moneyDeposit) moneyDeposit.textContent = ds.deposit || '—';
      if (moneyDepositLabel) moneyDepositLabel.textContent = 'Client paid · deposit';
      if (moneyPill) {
        if (ds.bookingStatus === 'completed' || ds.fullAmountPaid === '1') {
          moneyPill.textContent = 'Settled';
          moneyPill.className = 'abdm-pill g';
        } else if ((ds.balanceDueLabel || '').trim() && ds.balanceDueLabel !== '€0') {
          moneyPill.textContent = 'Pending';
          moneyPill.className = 'abdm-pill a';
        } else {
          moneyPill.textContent = 'Paid';
          moneyPill.className = 'abdm-pill g';
        }
      }
      if (bookingFeeRow) bookingFeeRow.style.display = 'none';
      if (stripeFeeRow) stripeFeeRow.style.display = 'none';
      if (stripeFeeEl) stripeFeeEl.textContent = '—';
      if (splitRow) splitRow.style.display = 'none';
      if (yourShareRow) yourShareRow.style.display = 'none';
      if (yourStripeRow) yourStripeRow.style.display = 'none';
      if (yourBookingFeeRow) yourBookingFeeRow.style.display = 'none';
      if (studioGetsRow) studioGetsRow.style.display = 'none';
      if (youGetEl) youGetEl.textContent = '—';
      if (moneyFootnote) {
        moneyFootnote.style.display = 'none';
        moneyFootnote.textContent = '';
      }
      if (moneyNote) {
        moneyNote.style.display = 'none';
        moneyNote.textContent = '';
      }
    }
    if (paidRow && paidEl) {
      if (ds.bookingStatus === 'completed') {
        var dep = (money && money.deposit) || ds.deposit || '€0';
        var bal = ds.balanceDueLabel || '€0';
        var fullPaid = ds.fullAmountPaid === '1';
        paidRow.style.display = '';
        paidEl.textContent = dep + ' deposit, ' + bal + ' balance' + (fullPaid ? '' : ' (cash)');
      } else {
        paidRow.style.display = 'none';
      }
    }

    if (qaListEl) {
      qaListEl.innerHTML = '';
      var rawQuestions = ds.questions || '';
      var parsedQuestions = null;
      try {
        parsedQuestions = rawQuestions ? JSON.parse(rawQuestions) : null;
      } catch (e) {
        parsedQuestions = null;
      }
      var entries = parsedQuestions && typeof parsedQuestions === 'object'
        ? Object.entries(parsedQuestions)
        : [];
      var hasAnswers = entries.length > 0;
      if (qaEmptyEl) qaEmptyEl.style.display = hasAnswers ? 'none' : '';
      qaListEl.style.display = hasAnswers ? '' : 'none';
      if (hasAnswers) {
        entries.forEach(function (pair) {
          var qKey = String(pair[0] || '').trim();
          var payload = window.QuestionAnswerDisplay
            ? window.QuestionAnswerDisplay.normalizePayload(pair[1], 'Question #' + (qKey || '—'))
            : { question: 'Question #' + (qKey || '—'), type: '', answer: pair[1] };
          var answerText = window.QuestionAnswerDisplay
              ? window.QuestionAnswerDisplay.formatAnswerForReview(payload.answer, payload.type)
              : (Array.isArray(payload.answer)
                ? payload.answer.map(function (_, idx) { return 'Photo ' + (idx + 1); }).join(', ')
                : String(payload.answer ?? '—'));
          var cell = document.createElement('div');
          cell.style.cssText = 'padding:9px 0;border-bottom:1px solid #F3EEF4';
          cell.innerHTML = '<div class="lbl" style="font-size:10.5px;letter-spacing:.8px;font-weight:700;color:#6F6874;text-transform:uppercase"></div>' +
            '<div style="font-size:14px;margin-top:3px;color:#1A1A1A"></div>';
          cell.children[0].textContent = payload.question || '—';
          cell.children[1].textContent = answerText || '—';
          qaListEl.appendChild(cell);
        });
      }
    }

    syncFooter(btn);

    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
    requestAnimationFrame(function () {
      modal.classList.add('artist-bdm-open');
    });
  }

  function closeModal() {
    modal.classList.remove('artist-bdm-open');
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
    currentSourceBtn = null;
  }

  if (messageBtn) {
    messageBtn.addEventListener('click', function () {
      var chat = messageBtn._chatSource;
      if (!chat) return;
      closeModal();
      if (typeof window.openArtistBookingMessageModal === 'function') {
        window.openArtistBookingMessageModal(chat);
      } else if (chat.getAttribute('href')) {
        window.location.href = chat.getAttribute('href');
      }
    });
  }

  if (aftercareBtn) {
    aftercareBtn.addEventListener('click', function () {
      if (aftercareBtn.disabled || aftercareBtn.getAttribute('aria-disabled') === 'true') return;
      var mode = aftercareBtn._mode || 'none';
      var src = aftercareBtn._source;
      if (mode === 'send' || mode === 'collect') {
        closeModal();
        if (src) src.click();
        return;
      }
      if (mode === 'manage' || mode === 'sent') {
        var bookingId = (src && src.getAttribute('data-booking-id'))
          || (currentSourceBtn && currentSourceBtn.getAttribute('data-booking-id'));
        if (typeof window.openArtistAftercareSendModal === 'function' && bookingId) {
          closeModal();
          window.openArtistAftercareSendModal(bookingId, {
            alreadySent: mode === 'sent' || (src && src.getAttribute('data-aftercare-sent') === '1'),
            toLine: src ? [
              'To ' + (src.getAttribute('data-client-name') || 'Client'),
              src.getAttribute('data-client-email') || '',
              src.getAttribute('data-service') || '',
              src.getAttribute('data-booking-ref') || ''
            ].filter(Boolean).join(' · ') : ''
          });
          return;
        }
        closeModal();
        window.location.href = aftercareUrl;
      }
    });
  }
  if (confirmBtn) {
    confirmBtn.addEventListener('click', function () {
      var src = confirmBtn._source;
      closeModal();
      if (src) src.click();
    });
  }

  document.querySelectorAll('.js-artist-booking-view').forEach(function (btn) {
    btn.addEventListener('click', function () {
      openModal(btn);
    });
  });

  var openBookingId = new URLSearchParams(window.location.search).get('booking');
  if (openBookingId) {
    var openRow = document.querySelector('[data-booking-row][data-booking-id="' + openBookingId + '"]');
    var openBtn = openRow ? openRow.querySelector('.js-artist-booking-view') : null;
    if (openBtn) {
      openModal(openBtn);
    }
  }

  modal.querySelectorAll('[data-close-artist-bdm]').forEach(function (el) {
    el.addEventListener('click', closeModal);
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && modal.classList.contains('artist-bdm-open')) {
      closeModal();
    }
  });
})();
</script>
<script>
(function () {
  var modal = document.getElementById('artistRequestPaymentModal');
  if (!modal) return;

  var bookingLine = document.getElementById('rpmBookingLine');
  var balanceLine = document.getElementById('rpmBalanceLine');
  var avatarEl = document.getElementById('rpmAvatar');
  var cardTitleEl = document.getElementById('rpmCardTitle');
  var cardMetaEl = document.getElementById('rpmCardMeta');
  var bookingStatusEl = document.getElementById('rpmBookingStatus');
  var actionBtn = document.getElementById('rpmActionBtn');
  var actionIcon = document.getElementById('rpmActionIcon');
  var actionLabel = document.getElementById('rpmActionLabel');
  var actionHint = document.getElementById('rpmActionHint');
  var durationHelp = document.getElementById('rpmDurationHelp');
  var copyLinkLabel = document.getElementById('rpmCopyLinkLabel');
  var copyMessageLabel = document.getElementById('rpmCopyMessageLabel');
  var showQrLabel = document.getElementById('rpmShowQrLabel');
  var cashFields = document.getElementById('rpmCashFields');
  var cashHelp = document.getElementById('rpmCashHelp');
  var cashAmountWrap = document.getElementById('rpmCashAmountWrap');
  var cashAmountDisplay = document.getElementById('rpmCashAmountDisplay');
  var cashAmountInput = document.getElementById('rpmCashAmountInput');
  var cashAmountEdit = document.getElementById('rpmCashAmountEdit');
  var linkAmountWrap = document.getElementById('rpmLinkAmountWrap');
  var linkSettleAmountDisplay = document.getElementById('rpmLinkSettleAmountDisplay');
  var linkSettleAmountInput = document.getElementById('rpmLinkSettleAmountInput');
  var linkSettleAmountEdit = document.getElementById('rpmLinkSettleAmountEdit');
  var completionCode = document.getElementById('rpmCompletionCode');
  var durationHours = document.getElementById('rpmDurationHours');
  var durationMinutes = document.getElementById('rpmDurationMinutes');
  var unsettledFields = document.getElementById('rpmUnsettledFields');
  var unsettledNote = document.getElementById('rpmUnsettledNote');
  var unsettledDate = document.getElementById('rpmUnsettledDate');
  var whenChips = modal.querySelectorAll('.rpm-when-chip');
  var options = modal.querySelectorAll('.rpm-option');
  var settleView = document.getElementById('rpmSettleView');
  var linkView = document.getElementById('rpmLinkView');
  var linkClientLine = document.getElementById('rpmLinkClientLine');
  var linkTotalsLine = document.getElementById('rpmLinkTotalsLine');
  var linkAmountDisplay = document.getElementById('rpmLinkAmountDisplay');
  var linkMessage = document.getElementById('rpmLinkMessage');
  var linkStatusLine = document.getElementById('rpmLinkStatusLine');
  var copyLinkBtn = document.getElementById('rpmCopyLinkBtn');
  var copyMessageBtn = document.getElementById('rpmCopyMessageBtn');
  var showQrBtn = document.getElementById('rpmShowQrBtn');
  var qrWrap = document.getElementById('rpmQrWrap');
  var qrImage = document.getElementById('rpmQrImage');
  var errorEl = document.getElementById('rpmError');
  var reminderModal = document.getElementById('artistUnsettledReminderModal');
  var reminderCollectBtn = document.getElementById('urmCollectBtn');
  var reminderSourceBtn = null;
  var currentAmountLabel = '€ 0';
  var currentOption = 'link';
  var currentWhenValue = '3-days';
  var currentStoreUrl = '';
  var currentBookingId = '';
  var currentContext = {
    clientName: '',
    firstName: 'there',
    bookingRef: '',
    paidLabel: '€0',
    totalLabel: '€0',
    service: 'session',
    linkUrl: 'bookpay.ink/p/x7Kq2mPa'
  };
  var copy = {
    link: {
      label: 'Get payment link',
      hint: 'Sends the link. The booking completes when the client pays.',
      icon: 'link',
      busy: 'Creating link...',
      busyHint: 'Please wait'
    },
    cash: {
      label: 'Confirm completed',
      hint: 'The code confirms the cash and marks the booking completed.',
      icon: 'check_circle',
      busy: 'Confirming...',
      busyHint: 'Marking booking completed'
    },
    unsettled: {
      label: 'Save',
      hint: 'You get a reminder on Home when it’s due.',
      icon: 'save',
      busy: 'Saving...',
      busyHint: 'Please wait'
    }
  };

  function formatDurationHelpLabel(totalMins) {
    var mins = Math.max(0, parseInt(totalMins, 10) || 0);
    var h = Math.floor(mins / 60);
    var m = mins % 60;
    if (h > 0 && m > 0) return h + 'h ' + m + 'm';
    if (h > 0) return h + 'h';
    return m + 'm';
  }

  function snapDurationMinutes(totalMins) {
    var mins = Math.max(0, parseInt(totalMins, 10) || 0);
    var h = Math.min(12, Math.floor(mins / 60));
    var rem = mins % 60;
    var steps = [0, 15, 30, 45];
    var nearest = steps.reduce(function (best, step) {
      return Math.abs(step - rem) < Math.abs(best - rem) ? step : best;
    }, 0);
    if (nearest === 0 && rem > 52 && h < 12) {
      h += 1;
      nearest = 0;
    }
    return { hours: h, minutes: nearest };
  }

  function applyActionCopy(value, busy) {
    var selected = copy[value] || copy.link;
    if (actionIcon) actionIcon.textContent = selected.icon || 'link';
    if (actionLabel) actionLabel.textContent = busy ? (selected.busy || 'Please wait...') : selected.label;
    if (actionHint) actionHint.textContent = busy ? (selected.busyHint || 'Please wait') : selected.hint;
  }

  function compactEuro(amountLabel) {
    return String(amountLabel || '€0').replace(/^€\s*/, '€');
  }

  function parseAmountValue(label) {
    var cleaned = String(label || '').replace(/[^\d.,]/g, '').replace(',', '.');
    var amount = parseFloat(cleaned);
    return isFinite(amount) ? Math.round(amount * 100) / 100 : 0;
  }

  function firstError(errors, field) {
    if (!errors || !errors[field]) return '';
    return Array.isArray(errors[field]) ? (errors[field][0] || '') : String(errors[field]);
  }

  function setTextError(el, message) {
    if (!el) return;
    el.textContent = message || '';
    el.classList.toggle('hidden', !message);
  }

  function setInputInvalid(el, invalid) {
    if (!el) return;
    el.classList.toggle('border-error', !!invalid);
    el.classList.toggle('border-outline-variant/40', !invalid);
    el.setAttribute('aria-invalid', invalid ? 'true' : 'false');
  }

  function setWrapInvalid(el, invalid) {
    if (!el) return;
    el.classList.toggle('ring-1', !!invalid);
    el.classList.toggle('ring-error', !!invalid);
  }

  function showRpmError(message) {
    setTextError(errorEl, message);
  }

  function clearFieldErrors() {
    setTextError(document.getElementById('rpmLinkAmountError'), '');
    setTextError(document.getElementById('rpmCashAmountError'), '');
    setTextError(document.getElementById('rpmCompletionCodeError'), '');
    setTextError(document.getElementById('rpmDurationError'), '');
    setTextError(document.getElementById('rpmWhenError'), '');
    setTextError(document.getElementById('rpmDateError'), '');
    setInputInvalid(completionCode, false);
    setInputInvalid(durationHours, false);
    setInputInvalid(durationMinutes, false);
    setInputInvalid(unsettledDate, false);
    setWrapInvalid(linkAmountWrap, false);
    setWrapInvalid(cashAmountWrap, false);
    showRpmError('');
  }

  function showAmountError(message) {
    var isCash = currentOption === 'cash';
    var isLink = currentOption === 'link';
    setTextError(document.getElementById('rpmLinkAmountError'), isLink ? message : '');
    setTextError(document.getElementById('rpmCashAmountError'), isCash ? message : '');
    setWrapInvalid(linkAmountWrap, isLink && !!message);
    setWrapInvalid(cashAmountWrap, isCash && !!message);
    if (!isCash && !isLink && message) showRpmError(message);
  }

  function applyFieldErrors(errors) {
    clearFieldErrors();
    var amountMsg = firstError(errors, 'amount');
    var codeMsg = firstError(errors, 'completion_code');
    var durationMsg = firstError(errors, 'actual_duration_minutes') || firstError(errors, 'actual_duration_hours');
    var whenMsg = firstError(errors, 'expected_payment_type');
    var dateMsg = firstError(errors, 'expected_payment_date');
    if (amountMsg) showAmountError(amountMsg);
    if (codeMsg) {
      setTextError(document.getElementById('rpmCompletionCodeError'), codeMsg);
      setInputInvalid(completionCode, true);
    }
    if (durationMsg) {
      setTextError(document.getElementById('rpmDurationError'), durationMsg);
      setInputInvalid(durationHours, true);
      setInputInvalid(durationMinutes, true);
    }
    if (whenMsg) setTextError(document.getElementById('rpmWhenError'), whenMsg);
    if (dateMsg) {
      setTextError(document.getElementById('rpmDateError'), dateMsg);
      setInputInvalid(unsettledDate, true);
    }
    var mapped = {
      amount: true,
      completion_code: true,
      actual_duration_hours: true,
      actual_duration_minutes: true,
      expected_payment_type: true,
      expected_payment_date: true
    };
    var leftover = '';
    Object.keys(errors || {}).forEach(function (key) {
      if (!mapped[key] && !leftover) leftover = firstError(errors, key);
    });
    if (leftover) showRpmError(leftover);
  }

  function csrfToken() {
    var meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
  }

  function submitBalanceCollection() {
    clearFieldErrors();
    if (currentOption === 'cash') commitCashAmount();
    if (currentOption === 'link') commitSettleAmount('link');
    var amount = parseAmountValue(currentAmountLabel);
    if (!(amount > 0)) {
      showAmountError('Amount must be greater than 0.');
      return;
    }

    var typeMap = { link: 'payment_link', cash: 'paid_in_cash', unsettled: 'not_settled_yet' };
    var whenMap = { '3-days': '3_days', '1-week': '1_week', 'pick-date': 'pick_date', 'no-date': 'no_date' };
    var hours = durationHours ? parseInt(durationHours.value, 10) : 0;
    var minutes = durationMinutes ? parseInt(durationMinutes.value, 10) : 0;
    if (!Number.isFinite(hours)) hours = 0;
    if (!Number.isFinite(minutes)) minutes = 0;
    if ((hours * 60) + minutes <= 0) {
      setTextError(document.getElementById('rpmDurationError'), 'Actual duration must be greater than 0.');
      setInputInvalid(durationHours, true);
      setInputInvalid(durationMinutes, true);
      if (durationHours) durationHours.focus();
      return;
    }

    var payload = {
      collection_type: typeMap[currentOption] || 'payment_link',
      amount: amount,
      actual_duration_hours: hours,
      actual_duration_minutes: minutes
    };

    if (currentOption === 'cash') {
      var code = completionCode ? completionCode.value.trim() : '';
      if (!code) {
        setTextError(document.getElementById('rpmCompletionCodeError'), 'Please enter the completion code.');
        setInputInvalid(completionCode, true);
        if (completionCode) completionCode.focus();
        return;
      }
      payload.completion_code = code;
    }

    if (currentOption === 'unsettled') {
      payload.expected_payment_type = whenMap[currentWhenValue] || '3_days';
      payload.note = unsettledNote ? unsettledNote.value.trim() : '';
      if (currentWhenValue === 'pick-date') {
        var picked = unsettledDate ? unsettledDate.value : '';
        if (!picked) {
          setTextError(document.getElementById('rpmDateError'), 'Please pick a date.');
          setInputInvalid(unsettledDate, true);
          if (unsettledDate) unsettledDate.focus();
          return;
        }
        payload.expected_payment_date = picked;
      }
    }

    if (!currentStoreUrl) {
      showRpmError('Could not save this collection.');
      return;
    }

    if (actionBtn) actionBtn.disabled = true;
    applyActionCopy(currentOption, true);

    fetch(currentStoreUrl, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': csrfToken()
      },
      body: JSON.stringify(payload)
    }).then(function (response) {
      return response.json().then(function (data) {
        return { status: response.status, data: data };
      });
    }).then(function (result) {
      if (!result.data || !result.data.success) {
        if (result.data && result.data.errors) {
          applyFieldErrors(result.data.errors);
          return;
        }
        showRpmError((result.data && result.data.message) || 'Could not save.');
        return;
      }
      if (currentOption === 'link') {
        var collection = result.data.collection || {};
        currentContext.linkUrl = collection.payment_link_url || currentContext.linkUrl;
        if (collection.client_message && linkMessage) {
          currentContext.savedMessage = collection.client_message;
        }
        document.querySelectorAll('.js-artist-collect-payment[data-booking-id="' + currentBookingId + '"]').forEach(function (btn) {
          btn.setAttribute('data-unsettled', '0');
        });
        fillLinkView();
        if (collection.client_message && linkMessage) {
          linkMessage.textContent = collection.client_message;
        }
        showLinkView();
        return;
      }
      if (result.data.booking_completed) {
        var bookingId = currentBookingId || '';
        closeModal();
        if (typeof window.artistBookingsMarkSettled === 'function') {
          window.artistBookingsMarkSettled(bookingId, result.data.message || 'Cash payment recorded. Booking marked as completed.');
        } else if (typeof showSaveToast === 'function') {
          showSaveToast(result.data.message || 'Cash payment recorded. Booking marked as completed.');
        }
        return;
      }
      if (currentOption === 'unsettled') {
        markCollectAsUnsettled(currentBookingId, result.data.collection || {});
      }
      closeModal();
      if (typeof showSaveToast === 'function') {
        showSaveToast(result.data.message || 'Saved.');
      }
    }).catch(function () {
      showRpmError('Could not save.');
    }).finally(function () {
      if (actionBtn) actionBtn.disabled = false;
      applyActionCopy(currentOption, false);
    });
  }

  function firstNameFrom(fullName) {
    var name = String(fullName || '').trim();
    if (!name) return 'there';
    return name.split(/\s+/)[0];
  }

  function showSettleView() {
    if (settleView) {
      settleView.classList.remove('hidden');
      settleView.style.display = 'flex';
    }
    if (linkView) linkView.classList.add('hidden');
  }

  function buildLinkMessage(amountLabel) {
    var remaining = compactEuro(amountLabel);
    return 'Hi ' + currentContext.firstName + ' — here’s the payment link for the remaining ' + remaining + ' for your ' + currentContext.service + ' session: ' + currentContext.linkUrl + ' — you can pay in full or split it with Klarna.';
  }

  function fillLinkView() {
    var remaining = compactEuro(currentAmountLabel);
    if (linkClientLine) {
      linkClientLine.textContent = [currentContext.clientName, currentContext.bookingRef].filter(Boolean).join(' · ') || '—';
    }
    if (linkTotalsLine) {
      linkTotalsLine.textContent = currentContext.totalLabel + ' total · ' + currentContext.paidLabel + ' paid';
    }
    if (linkAmountDisplay) linkAmountDisplay.textContent = currentAmountLabel;
    if (linkMessage) linkMessage.textContent = buildLinkMessage(currentAmountLabel);
    if (linkStatusLine) {
      linkStatusLine.innerHTML = 'Card: ' + remaining + ' requested · link sent · <span style="color:#1E5BD8;font-weight:600">Resend</span>';
    }
  }

  function showLinkView() {
    fillLinkView();
    resetCopyButtons();
    hideQr();
    if (settleView) {
      settleView.classList.add('hidden');
      settleView.style.display = 'none';
    }
    if (linkView) linkView.classList.remove('hidden');
  }

  function hideQr() {
    if (qrWrap) qrWrap.classList.add('hidden');
    if (qrImage) qrImage.removeAttribute('src');
    if (showQrBtn) showQrBtn.setAttribute('aria-expanded', 'false');
    if (showQrLabel) showQrLabel.textContent = 'Show QR';
  }

  function showQr() {
    var url = currentContext.linkUrl || '';
    if (!url || !qrWrap || !qrImage) return;
    qrImage.src = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' + encodeURIComponent(url);
    qrWrap.classList.remove('hidden');
    if (showQrBtn) showQrBtn.setAttribute('aria-expanded', 'true');
    if (showQrLabel) showQrLabel.textContent = 'Hide QR';
  }

  function resetCopyButtons() {
    if (copyLinkBtn) clearTimeout(copyLinkBtn._copyTimer);
    if (copyMessageBtn) clearTimeout(copyMessageBtn._copyTimer);
    if (copyLinkLabel) copyLinkLabel.textContent = 'Copy payment link';
    if (copyMessageLabel) copyMessageLabel.textContent = 'Copy message';
  }

  function copyText(text, button, idleLabel, labelEl) {
    if (!text || !button) return;
    var target = labelEl || button;
    var done = function () {
      target.textContent = 'Copied';
      clearTimeout(button._copyTimer);
      button._copyTimer = setTimeout(function () {
        target.textContent = idleLabel;
      }, 1500);
    };
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(text).then(done).catch(function () {
        fallbackCopy(text, done);
      });
      return;
    }
    fallbackCopy(text, done);
  }

  function fallbackCopy(text, done) {
    var temp = document.createElement('textarea');
    temp.value = text;
    temp.setAttribute('readonly', '');
    temp.style.position = 'absolute';
    temp.style.left = '-9999px';
    document.body.appendChild(temp);
    temp.select();
    try {
      document.execCommand('copy');
      done();
    } catch (e) {}
    document.body.removeChild(temp);
  }

  function setWhenChip(value) {
    currentWhenValue = value || '3-days';
    whenChips.forEach(function (chip) {
      var selected = chip.getAttribute('data-when-value') === value;
      chip.setAttribute('aria-pressed', selected ? 'true' : 'false');
    });
    if (unsettledDate) {
      unsettledDate.classList.toggle('hidden', value !== 'pick-date');
      if (value !== 'pick-date') {
        setTextError(document.getElementById('rpmDateError'), '');
        setInputInvalid(unsettledDate, false);
      }
      if (value === 'pick-date') {
        var today = new Date();
        var ymd = today.getFullYear() + '-' + String(today.getMonth() + 1).padStart(2, '0') + '-' + String(today.getDate()).padStart(2, '0');
        unsettledDate.setAttribute('min', ymd);
        unsettledDate.focus();
      }
    }
    setTextError(document.getElementById('rpmWhenError'), '');
  }

  function formatCashAmount(raw) {
    var cleaned = String(raw || '').replace(/[^\d.,]/g, '').replace(',', '.');
    var amount = parseFloat(cleaned);
    if (!isFinite(amount) || amount < 0) return currentAmountLabel;
    var rounded = Math.round(amount * 100) / 100;
    return rounded % 1 === 0 ? '€ ' + String(Math.round(rounded)) : '€ ' + rounded.toFixed(2);
  }

  function outstandingLabel(amountLabel) {
    return 'Outstanding balance: ' + String(amountLabel || '—').replace(/^€\s*/, '€');
  }

  function setOutstandingBalance(amountLabel) {
    if (balanceLine) balanceLine.textContent = outstandingLabel(amountLabel);
  }

  function updateSettleAmountDisplays() {
    if (cashAmountDisplay) cashAmountDisplay.textContent = currentAmountLabel;
    if (linkSettleAmountDisplay) linkSettleAmountDisplay.textContent = currentAmountLabel;
    setOutstandingBalance(currentAmountLabel);
  }

  function setSettleAmountEditing(editing, source) {
    var isLink = source === 'link';
    var display = isLink ? linkSettleAmountDisplay : cashAmountDisplay;
    var input = isLink ? linkSettleAmountInput : cashAmountInput;
    var editBtn = isLink ? linkSettleAmountEdit : cashAmountEdit;
    if (!display || !input || !editBtn) return;
    display.classList.toggle('hidden', editing);
    input.classList.toggle('hidden', !editing);
    editBtn.classList.toggle('hidden', editing);
    if (editing) {
      input.value = currentAmountLabel.replace(/^€\s*/, '');
      input.focus();
      input.select();
    }
  }

  function setCashEditing(editing) {
    setSettleAmountEditing(editing, 'cash');
  }

  function setLinkSettleAmountEditing(editing) {
    setSettleAmountEditing(editing, 'link');
  }

  function commitSettleAmount(source) {
    var input = source === 'link' ? linkSettleAmountInput : cashAmountInput;
    currentAmountLabel = formatCashAmount(input ? input.value : currentAmountLabel);
    updateSettleAmountDisplays();
    setSettleAmountEditing(false, source);
    showAmountError('');
  }

  function commitCashAmount() {
    commitSettleAmount('cash');
  }

  function setOption(value) {
    // Persist any in-progress amount edit before switching (cash ↔ link).
    // Otherwise the outstanding line can show the typed value while generate still uses the old amount.
    if (cashAmountInput && !cashAmountInput.classList.contains('hidden')) {
      commitCashAmount();
    } else if (linkSettleAmountInput && !linkSettleAmountInput.classList.contains('hidden')) {
      commitSettleAmount('link');
    }

    currentOption = value || 'link';
    options.forEach(function (option) {
      option.setAttribute('aria-checked', option.getAttribute('data-rpm-value') === value ? 'true' : 'false');
    });
    var isCash = value === 'cash';
    var isLink = value === 'link';
    var isUnsettled = value === 'unsettled';
    if (cashFields) cashFields.classList.toggle('hidden', !isCash);
    if (cashAmountWrap) cashAmountWrap.classList.toggle('hidden', !isCash);
    if (linkAmountWrap) linkAmountWrap.classList.toggle('hidden', !isLink);
    if (unsettledFields) unsettledFields.classList.toggle('hidden', !isUnsettled);
    if (!isCash) setCashEditing(false);
    if (!isLink) setLinkSettleAmountEditing(false);
    updateSettleAmountDisplays();
    applyActionCopy(value, false);
    clearFieldErrors();
  }

  function openModal(btn) {
    var ds = btn.dataset || {};
    var service = (ds.service || '').trim();
    var client = (ds.clientName || '').trim() || 'the client';
    var first = firstNameFrom(ds.clientName);
    var line = [client, service].filter(Boolean).join(' · ');
    currentAmountLabel = formatCashAmount(ds.balanceLabel || '0');
    currentStoreUrl = ds.storeUrl || '';
    currentBookingId = ds.bookingId || '';
    currentContext = {
      clientName: (ds.clientName || '').trim(),
      firstName: first,
      bookingRef: (ds.bookingRef || '').trim(),
      paidLabel: (ds.paidLabel || '€0').trim() || '€0',
      totalLabel: (ds.totalLabel || '€0').trim() || '€0',
      service: (ds.service || '').trim() || 'session',
      linkUrl: 'bookpay.ink/p/x7Kq2mPa'
    };
    if (bookingLine) bookingLine.textContent = line || 'Booking';
    if (avatarEl) avatarEl.textContent = ds.clientInitials || '?';
    if (cardTitleEl) cardTitleEl.textContent = line || 'Booking';
    if (cardMetaEl) {
      cardMetaEl.textContent = [ds.dateDisplay, ds.timeRange, ds.bookingRef].filter(Boolean).join(' · ') || '—';
    }
    if (bookingStatusEl) {
      bookingStatusEl.className = 'rpm-pill ' + (ds.statusPill || 'g');
      bookingStatusEl.textContent = ds.statusLabel || 'Confirmed';
    }
    setOutstandingBalance(currentAmountLabel);
    if (cashHelp) {
      cashHelp.textContent = 'Ask ' + first + ' for their completion code. It’s in their booking confirmation email and confirms the cash payment.';
    }
    if (cashAmountDisplay) cashAmountDisplay.textContent = currentAmountLabel;
    if (linkSettleAmountDisplay) linkSettleAmountDisplay.textContent = currentAmountLabel;
    if (completionCode) completionCode.value = '';
    var snapped = snapDurationMinutes(ds.durationMinutes || '0');
    if (durationHours) durationHours.value = String(snapped.hours);
    if (durationMinutes) durationMinutes.value = String(snapped.minutes);
    if (durationHelp) {
      var totalMins = Math.max(0, parseInt(ds.durationMinutes || '0', 10) || 0);
      durationHelp.textContent = totalMins > 0
        ? ('Pre-filled from the booking (' + formatDurationHelpLabel(totalMins) + '). Change it if the session ran shorter or longer.')
        : 'Change it if the session ran shorter or longer.';
    }
    if (unsettledNote) unsettledNote.value = '';
    if (unsettledDate) unsettledDate.value = '';
    setWhenChip('3-days');
    setCashEditing(false);
    setLinkSettleAmountEditing(false);
    setOption('link');
    clearFieldErrors();
    showSettleView();
    modal.classList.add('artist-rpm-open');
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
  }

  function closeModal() {
    hideQr();
    modal.classList.remove('artist-rpm-open');
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
  }

  function setNoteText(el, text) {
    if (!el) return;
    var value = String(text || '').trim();
    el.textContent = value;
    el.classList.toggle('hidden', !value);
  }

  function markCollectAsUnsettled(bookingId, collection) {
    var id = String(bookingId || '');
    if (!id) return;
    collection = collection || {};
    var amount = collection.amount_label || '';
    var expected = collection.expected_label || '';
    var note = collection.note || '';
    var nudge = collection.nudge || '';
    var barText = [amount ? amount + ' due' : '', expected].filter(Boolean).join(' · ');
    document.querySelectorAll('.js-artist-collect-payment[data-booking-id="' + id + '"]').forEach(function (btn) {
      btn.setAttribute('data-unsettled', '1');
      if (amount) btn.setAttribute('data-balance-label', amount);
      btn.setAttribute('data-expected-label', expected);
      btn.setAttribute('data-unsettled-note', note);
      btn.setAttribute('data-nudge', nudge);
      var wrap = btn.closest('.js-artist-collect-wrap');
      var label = wrap ? wrap.querySelector('.js-artist-collect-label') : null;
      if (label && barText) label.textContent = barText;
    });
  }

  function closeReminder() {
    if (!reminderModal) return;
    reminderModal.classList.remove('artist-urm-open');
    reminderModal.setAttribute('aria-hidden', 'true');
    if (!modal.classList.contains('artist-rpm-open')) {
      document.body.style.overflow = '';
    }
  }

  function openReminder(btn) {
    if (!reminderModal) {
      openModal(btn);
      return;
    }
    reminderSourceBtn = btn;
    var ds = btn.dataset || {};
    var client = (ds.clientName || '').trim() || 'Client';
    var amount = (ds.balanceLabel || '').trim();
    var expected = (ds.expectedLabel || '').trim();
    var note = (ds.unsettledNote || '').trim();
    var nudge = (ds.nudge || '').trim();
    var dateLine = [(ds.dateDisplay || '').trim(), (ds.timeRange || '').trim()].filter(Boolean).join(' · ');
    var clientEl = document.getElementById('urmClient');
    var refEl = document.getElementById('urmBookingRef');
    var whenEl = document.getElementById('urmWhen');
    var dueEl = document.getElementById('urmDueLine');
    var statusEl = document.getElementById('urmStatus');
    if (clientEl) clientEl.textContent = client;
    if (refEl) refEl.textContent = (ds.bookingRef || '').trim() || '—';
    if (whenEl) whenEl.textContent = dateLine || '—';
    if (dueEl) dueEl.textContent = [amount ? amount + ' due' : '', expected].filter(Boolean).join(' · ') || 'Balance due';
    if (statusEl) {
      statusEl.textContent = (ds.statusLabel || '').trim() || 'Confirmed';
      statusEl.className = 'inline-flex shrink-0 items-center text-xs font-semibold px-2.5 py-1 rounded-full ring-1 ring-inset ' + ((ds.statusBadgeClass || '').trim() || 'bg-green-50 text-green-700 ring-green-500/20');
    }
    setNoteText(document.getElementById('urmSavedNote'), note ? 'Note saved: “' + note + '”' : '');
    setNoteText(document.getElementById('urmNudgeNote'), nudge);
    reminderModal.classList.add('artist-urm-open');
    reminderModal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
  }

  function openCollectFlow(btn) {
    if (btn.getAttribute('data-unsettled') === '1') {
      openReminder(btn);
      return;
    }
    openModal(btn);
  }

  document.querySelectorAll('.js-artist-collect-payment').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var bookingId = btn.getAttribute('data-booking-id');
      if (typeof window.artistBookingsIfSettled === 'function') {
        window.artistBookingsIfSettled(bookingId, function () {
          openCollectFlow(btn);
        }, true);
        return;
      }
      openCollectFlow(btn);
    });
  });

  options.forEach(function (option) {
    option.addEventListener('click', function () {
      setOption(option.getAttribute('data-rpm-value'));
    });
  });

  whenChips.forEach(function (chip) {
    chip.addEventListener('click', function () {
      setWhenChip(chip.getAttribute('data-when-value'));
    });
  });

  if (actionBtn) {
    actionBtn.addEventListener('click', function () {
      submitBalanceCollection();
    });
  }

  if (linkSettleAmountEdit) {
    linkSettleAmountEdit.addEventListener('click', function (e) {
      e.preventDefault();
      e.stopPropagation();
      setLinkSettleAmountEditing(true);
    });
  }
  if (linkSettleAmountInput) {
    linkSettleAmountInput.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        commitSettleAmount('link');
      }
      if (e.key === 'Escape') {
        e.preventDefault();
        setLinkSettleAmountEditing(false);
      }
    });
    linkSettleAmountInput.addEventListener('blur', function () {
      commitSettleAmount('link');
    });
    linkSettleAmountInput.addEventListener('input', function () {
      setOutstandingBalance(formatCashAmount(linkSettleAmountInput.value));
      showAmountError('');
    });
  }

  if (cashAmountEdit) {
    cashAmountEdit.addEventListener('click', function (e) {
      e.preventDefault();
      e.stopPropagation();
      setCashEditing(true);
    });
  }
  if (cashAmountInput) {
    cashAmountInput.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        commitCashAmount();
      }
      if (e.key === 'Escape') {
        e.preventDefault();
        setCashEditing(false);
      }
    });
    cashAmountInput.addEventListener('blur', commitCashAmount);
    cashAmountInput.addEventListener('input', function () {
      setOutstandingBalance(formatCashAmount(cashAmountInput.value));
      showAmountError('');
    });
  }

  if (completionCode) {
    completionCode.addEventListener('input', function () {
      setTextError(document.getElementById('rpmCompletionCodeError'), '');
      setInputInvalid(completionCode, false);
    });
  }

  if (unsettledDate) {
    unsettledDate.addEventListener('change', function () {
      setTextError(document.getElementById('rpmDateError'), '');
      setInputInvalid(unsettledDate, false);
    });
  }

  if (copyLinkBtn) {
    copyLinkBtn.addEventListener('click', function () {
      copyText(currentContext.linkUrl || '', copyLinkBtn, 'Copy payment link', copyLinkLabel);
    });
  }

  if (copyMessageBtn) {
    copyMessageBtn.addEventListener('click', function () {
      var message = linkMessage ? (linkMessage.textContent || '').trim() : '';
      copyText(message, copyMessageBtn, 'Copy message', copyMessageLabel);
    });
  }

  if (showQrBtn) {
    showQrBtn.addEventListener('click', function () {
      if (qrWrap && !qrWrap.classList.contains('hidden')) {
        hideQr();
        return;
      }
      showQr();
    });
  }

  modal.querySelectorAll('[data-close-artist-rpm]').forEach(function (el) {
    el.addEventListener('click', closeModal);
  });

  if (reminderCollectBtn) {
    reminderCollectBtn.addEventListener('click', function () {
      var btn = reminderSourceBtn;
      closeReminder();
      if (btn) openModal(btn);
    });
  }
  if (reminderModal) {
    reminderModal.querySelectorAll('[data-close-artist-urm]').forEach(function (el) {
      el.addEventListener('click', closeReminder);
    });
  }

  document.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape') return;
    if (modal.classList.contains('artist-rpm-open')) {
      closeModal();
      return;
    }
    if (reminderModal && reminderModal.classList.contains('artist-urm-open')) {
      closeReminder();
    }
  });
})();
</script>
<script>
(function () {
  var modal = document.getElementById('artistConsentDetailModal');
  if (!modal) return;

  var clientEl = document.getElementById('acdmClient');
  var refEl = document.getElementById('acdmBookingRef');
  var whenEl = document.getElementById('acdmWhen');
  var signedAtEl = document.getElementById('acdmSignedAt');
  var bodyEl = document.getElementById('acdmBody');
  var ageVerifiedWrap = document.getElementById('acdmAgeVerifiedWrap');
  var ageVerifiedToggle = document.getElementById('acdmAgeVerifiedToggle');
  var guardianFieldsEl = document.getElementById('acdmGuardianFields');
  var guardianFormEl = document.getElementById('acdmGuardianForm');
  var guardianFormHintEl = document.getElementById('acdmGuardianFormHint');
  var guardianSummaryEl = document.getElementById('acdmGuardianSummary');
  var guardianSummaryNameEl = document.getElementById('acdmGuardianSummaryName');
  var guardianSummaryRelationshipEl = document.getElementById('acdmGuardianSummaryRelationship');
  var guardianSummaryIdRefEl = document.getElementById('acdmGuardianSummaryIdRef');
  var guardianSummarySignatureEl = document.getElementById('acdmGuardianSummarySignature');
  var guardianNameEl = document.getElementById('acdmGuardianName');
  var guardianRelationshipEl = document.getElementById('acdmGuardianRelationship');
  var guardianIdRefEl = document.getElementById('acdmGuardianIdRef');
  var guardianSignatureEl = document.getElementById('acdmGuardianSignature');
  var guardianNameErrorEl = document.getElementById('acdmGuardianNameError');
  var guardianRelationshipErrorEl = document.getElementById('acdmGuardianRelationshipError');
  var guardianIdRefErrorEl = document.getElementById('acdmGuardianIdRefError');
  var guardianSignatureErrorEl = document.getElementById('acdmGuardianSignatureError');
  var guardianSignedAtEl = document.getElementById('acdmGuardianSignedAt');
  var guardianSaveBtn = document.getElementById('acdmGuardianSaveBtn');
  var guardianStatusEl = document.getElementById('acdmGuardianStatus');
  var currentViewBtn = null;
  var savingAgeVerified = false;
  var savingGuardian = false;

  var guardianFieldMap = [
    { el: guardianNameEl, errorEl: guardianNameErrorEl, key: 'guardian_name', label: 'Guardian full name', min: 2 },
    { el: guardianRelationshipEl, errorEl: guardianRelationshipErrorEl, key: 'guardian_relationship', label: 'Relationship to client', min: 1 },
    { el: guardianIdRefEl, errorEl: guardianIdRefErrorEl, key: 'guardian_id_reference', label: 'Guardian ID reference', min: 1 },
    { el: guardianSignatureEl, errorEl: guardianSignatureErrorEl, key: 'guardian_signature', label: 'Guardian signature', min: 2 }
  ];

  function csrfToken() {
    var meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
  }

  function ageVerifiedUrl(bookingId) {
    return @json(url('/api/bookings')) + '/' + encodeURIComponent(bookingId) + '/consent/age-verified';
  }

  function guardianUrl(bookingId) {
    return @json(url('/api/bookings')) + '/' + encodeURIComponent(bookingId) + '/consent/guardian';
  }

  function ageFromIso(iso) {
    if (!iso) return null;
    var parts = String(iso).split('-');
    if (parts.length !== 3) return null;
    var y = parseInt(parts[0], 10);
    var m = parseInt(parts[1], 10) - 1;
    var d = parseInt(parts[2], 10);
    if (!y || isNaN(m) || !d) return null;
    var dob = new Date(y, m, d);
    if (isNaN(dob.getTime())) return null;
    var today = new Date();
    var age = today.getFullYear() - dob.getFullYear();
    var md = today.getMonth() - dob.getMonth();
    if (md < 0 || (md === 0 && today.getDate() < dob.getDate())) age -= 1;
    return age;
  }

  function setAgeVerified(active, disabled) {
    if (!ageVerifiedToggle) return;
    ageVerifiedToggle.classList.toggle('active', active);
    ageVerifiedToggle.setAttribute('aria-checked', active ? 'true' : 'false');
    if (ageVerifiedWrap) ageVerifiedWrap.classList.toggle('is-verified', active);
    if (typeof disabled === 'boolean') {
      ageVerifiedToggle.disabled = disabled;
      ageVerifiedToggle.classList.toggle('opacity-60', disabled);
      ageVerifiedToggle.classList.toggle('cursor-wait', disabled);
    }
  }

  function setGuardianStatus(message, isError) {
    if (!guardianStatusEl) return;
    guardianStatusEl.textContent = message || '';
    guardianStatusEl.classList.toggle('text-[#c2410c]', !!isError);
    guardianStatusEl.classList.toggle('font-semibold', !!isError);
    guardianStatusEl.classList.toggle('text-on-surface-variant', !isError);
  }

  function setFieldError(field, message) {
    if (!field || !field.el) return;
    var hasError = !!message;
    field.el.classList.toggle('is-invalid', hasError);
    field.el.setAttribute('aria-invalid', hasError ? 'true' : 'false');
    if (field.errorEl) {
      field.errorEl.textContent = message || '';
      field.errorEl.classList.toggle('hidden', !hasError);
    }
  }

  function clearGuardianErrors() {
    guardianFieldMap.forEach(function (field) {
      setFieldError(field, '');
    });
  }

  function validateGuardianForm() {
    clearGuardianErrors();
    var firstInvalid = null;
    var ok = true;

    guardianFieldMap.forEach(function (field) {
      var value = field.el ? field.el.value.trim() : '';
      if (!value) {
        setFieldError(field, field.label + ' is required.');
        if (!firstInvalid) firstInvalid = field.el;
        ok = false;
        return;
      }
      if (value.length < field.min) {
        setFieldError(field, field.label + ' is too short.');
        if (!firstInvalid) firstInvalid = field.el;
        ok = false;
      }
    });

    if (firstInvalid) firstInvalid.focus();
    return ok;
  }

  function applyServerGuardianErrors(errors) {
    if (!errors || typeof errors !== 'object') return false;
    var applied = false;
    var firstInvalid = null;
    guardianFieldMap.forEach(function (field) {
      var messages = errors[field.key];
      if (Array.isArray(messages) && messages.length) {
        setFieldError(field, messages[0]);
        if (!firstInvalid) firstInvalid = field.el;
        applied = true;
      }
    });
    if (firstInvalid) firstInvalid.focus();
    return applied;
  }

  function isGuardianSubmitted(consent) {
    return !!(consent && consent.guardian_signed_at && consent.guardian_signature);
  }

  function setGuardianFieldsVisible(visible) {
    if (!guardianFieldsEl) return;
    guardianFieldsEl.classList.toggle('hidden', !visible);
  }

  function setGuardianSignedAt(timestamp) {
    if (!guardianSignedAtEl) return;
    guardianSignedAtEl.textContent = timestamp ? ('Signed by guardian: ' + timestamp) : '';
  }

  function setGuardianMode(submitted, consent) {
    var data = consent || {};
    if (guardianFormEl) guardianFormEl.classList.toggle('hidden', !!submitted);
    if (guardianFormHintEl) guardianFormHintEl.classList.toggle('hidden', !!submitted);
    if (guardianSummaryEl) guardianSummaryEl.classList.toggle('hidden', !submitted);

    if (submitted) {
      if (guardianSummaryNameEl) guardianSummaryNameEl.textContent = data.guardian_name || '—';
      if (guardianSummaryRelationshipEl) guardianSummaryRelationshipEl.textContent = data.guardian_relationship || '—';
      if (guardianSummaryIdRefEl) guardianSummaryIdRefEl.textContent = data.guardian_id_reference || '—';
      if (guardianSummarySignatureEl) guardianSummarySignatureEl.textContent = data.guardian_signature || '—';
      setGuardianSignedAt(data.guardian_signed_at || '');
      setGuardianStatus('');
      clearGuardianErrors();
      return;
    }

    if (guardianNameEl) guardianNameEl.value = data.guardian_name || '';
    if (guardianRelationshipEl) guardianRelationshipEl.value = data.guardian_relationship || '';
    if (guardianIdRefEl) guardianIdRefEl.value = data.guardian_id_reference || '';
    if (guardianSignatureEl) guardianSignatureEl.value = data.guardian_signature || '';
    setGuardianSignedAt('');
    clearGuardianErrors();
    setGuardianStatus('');
  }

  function fillGuardianFields(consent) {
    setGuardianMode(isGuardianSubmitted(consent), consent || {});
  }

  function syncConsentPayloadAgeVerified(btn, active) {
    if (!btn || !btn.dataset.consent) return;
    var consent = parseConsent(btn.dataset.consent);
    if (!consent) return;
    consent.age_verified_by_artist = !!active;
    btn.dataset.consent = JSON.stringify(consent);
  }

  function syncConsentPayloadGuardian(btn, data) {
    if (!btn || !btn.dataset.consent) return;
    var consent = parseConsent(btn.dataset.consent);
    if (!consent) return;
    consent.guardian_name = data.guardian_name || null;
    consent.guardian_relationship = data.guardian_relationship || null;
    consent.guardian_id_reference = data.guardian_id_reference || null;
    consent.guardian_signature = data.guardian_signature || null;
    consent.guardian_signed_at = data.guardian_signed_at || null;
    btn.dataset.consent = JSON.stringify(consent);
  }

  function esc(s) {
    return String(s == null ? '' : s)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function row(label, value) {
    if (value == null || String(value).trim() === '') return '';
    return '<div class="flex justify-between gap-4 py-2 border-b border-outline-variant/10">' +
      '<dt class="text-on-surface-variant font-medium">' + esc(label) + '</dt>' +
      '<dd class="text-on-surface text-right">' + esc(value) + '</dd></div>';
  }

  function section(title, html) {
    if (!html) return '';
    return '<div class="rounded-xl border border-outline-variant/20 bg-surface-container-low/40 p-4">' +
      '<p class="text-xs font-semibold uppercase tracking-wide text-on-surface-variant mb-3">' + esc(title) + '</p>' +
      html + '</div>';
  }

  function yesNoBadge(answer) {
    var a = String(answer || '').toLowerCase();
    if (a === 'yes') {
      return '<span class="inline-flex text-xs font-semibold px-2 py-0.5 rounded-full bg-[#FDF6EE] text-[#9A5B13]">Yes</span>';
    }
    if (a === 'no') {
      return '<span class="inline-flex text-xs font-semibold px-2 py-0.5 rounded-full bg-[#EAF2EF] text-[#1E3A34]">No</span>';
    }
    return esc(answer || '—');
  }

  function parseConsent(raw) {
    if (!raw) return null;
    if (typeof raw === 'object') return raw;
    try { return JSON.parse(raw); } catch (e) { return null; }
  }

  function openModal(btn) {
    var ds = btn.dataset || {};
    var consent = parseConsent(ds.consent);
    if (!consent) return;
    currentViewBtn = btn;

    if (clientEl) clientEl.textContent = ds.clientName || '—';
    if (refEl) refEl.textContent = ds.bookingRef || '—';
    if (whenEl) {
      var when = [ds.dateDisplay, ds.timeRange].filter(Boolean).join(' · ');
      whenEl.textContent = when || '—';
    }
    if (signedAtEl) signedAtEl.textContent = consent.completed_at || 'Signed';

    var clientAge = ageFromIso(consent.date_of_birth_iso);
    setGuardianFieldsVisible(clientAge !== null && clientAge < 18);
    fillGuardianFields(consent);

    var parts = [];
    parts.push(section('Client details',
      '<dl>' +
      row('Full name', consent.full_name) +
      row('Date of birth', consent.date_of_birth) +
      row('Phone', consent.phone) +
      row('Emergency contact', consent.emergency_contact) +
      row('Signature', consent.typed_signature) +
      row('Language', consent.form_language) +
      '</dl>'
    ));

    var health = Array.isArray(consent.health) ? consent.health : [];
    if (health.length) {
      var healthHtml = '<div class="space-y-2.5">' + health.map(function (item) {
        return '<div class="flex items-start justify-between gap-3">' +
          '<p class="text-on-surface leading-snug">' + esc(item.text || item.label || '') + '</p>' +
          yesNoBadge(item.answer) +
          '</div>';
      }).join('') + '</div>';
      if (consent.other_health_note) {
        healthHtml += '<p class="mt-3 text-on-surface-variant"><span class="font-medium text-on-surface">Other notes:</span> ' +
          esc(consent.other_health_note) + '</p>';
      }
      parts.push(section('Health screening', healthHtml));
    }

    var risk = Array.isArray(consent.risk) ? consent.risk : [];
    if (risk.length) {
      parts.push(section('Risks accepted',
        '<ul class="space-y-2 list-disc pl-5 text-on-surface">' +
        risk.map(function (item) {
          return '<li>' + esc(item.text || item.label || '') + '</li>';
        }).join('') +
        '</ul>' +
        '<p class="mt-3 text-xs text-on-surface-variant">' +
        (consent.accepted_risks ? 'Client accepted the risks.' : 'Risks acknowledgement missing.') +
        '</p>'
      ));
    }

    var aftercare = Array.isArray(consent.aftercare) ? consent.aftercare : [];
    if (aftercare.length) {
      parts.push(section('Aftercare accepted',
        '<ul class="space-y-2 list-disc pl-5 text-on-surface">' +
        aftercare.map(function (item) {
          return '<li>' + esc(item.text || item.label || '') + '</li>';
        }).join('') +
        '</ul>' +
        '<p class="mt-3 text-xs text-on-surface-variant">' +
        (consent.accepted_aftercare ? 'Client agreed to follow aftercare.' : 'Aftercare acknowledgement missing.') +
        '</p>'
      ));
    }

    parts.push(section('Acknowledgements',
      '<ul class="space-y-1.5 text-on-surface">' +
      '<li>Health data consent: <strong>' + (consent.accepted_health_consent ? 'Yes' : 'No') + '</strong></li>' +
      '<li>Data protection notice: <strong>' + (consent.accepted_data_notice ? 'Yes' : 'No') + '</strong></li>' +
      '<li>Photo consent: <strong>' + (consent.accepted_photo ? 'Yes' : 'No') + '</strong></li>' +
      '</ul>'
    ));

    if (bodyEl) bodyEl.innerHTML = parts.filter(Boolean).join('');
    setAgeVerified(!!consent.age_verified_by_artist, false);

    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
    requestAnimationFrame(function () {
      modal.classList.add('artist-cdm-open');
    });
  }

  function closeModal() {
    modal.classList.remove('artist-cdm-open');
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
    currentViewBtn = null;
    savingAgeVerified = false;
    savingGuardian = false;
    setAgeVerified(false, false);
    setGuardianFieldsVisible(false);
    fillGuardianFields({});
    if (guardianSaveBtn) guardianSaveBtn.disabled = false;
  }

  if (ageVerifiedToggle) {
    ageVerifiedToggle.addEventListener('click', function () {
      if (savingAgeVerified || !currentViewBtn || !currentViewBtn.dataset.bookingId) return;

      var bookingId = currentViewBtn.dataset.bookingId;
      var next = !ageVerifiedToggle.classList.contains('active');
      var previous = !next;
      setAgeVerified(next, true);
      savingAgeVerified = true;

      fetch(ageVerifiedUrl(bookingId), {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken(),
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ age_verified_by_artist: next })
      })
        .then(function (res) { return res.json().then(function (data) { return { ok: res.ok, data: data }; }); })
        .then(function (result) {
          if (!result.ok || !result.data.success) {
            setAgeVerified(previous, false);
            return;
          }
          var saved = !!result.data.age_verified_by_artist;
          setAgeVerified(saved, false);
          syncConsentPayloadAgeVerified(currentViewBtn, saved);
        })
        .catch(function () {
          setAgeVerified(previous, false);
        })
        .finally(function () {
          savingAgeVerified = false;
          if (ageVerifiedToggle) ageVerifiedToggle.disabled = false;
        });
    });
  }

  if (guardianSaveBtn) {
    guardianSaveBtn.addEventListener('click', function () {
      if (savingGuardian || !currentViewBtn || !currentViewBtn.dataset.bookingId) return;
      if (guardianFormEl && guardianFormEl.classList.contains('hidden')) return;

      if (!validateGuardianForm()) {
        setGuardianStatus('Please complete all required guardian fields.', true);
        return;
      }

      var bookingId = currentViewBtn.dataset.bookingId;
      var payload = {
        guardian_name: guardianNameEl ? guardianNameEl.value.trim() : '',
        guardian_relationship: guardianRelationshipEl ? guardianRelationshipEl.value.trim() : '',
        guardian_id_reference: guardianIdRefEl ? guardianIdRefEl.value.trim() : '',
        guardian_signature: guardianSignatureEl ? guardianSignatureEl.value.trim() : ''
      };

      savingGuardian = true;
      guardianSaveBtn.disabled = true;
      setGuardianStatus('Saving…');

      fetch(guardianUrl(bookingId), {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken(),
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(payload)
      })
        .then(function (res) { return res.json().then(function (data) { return { ok: res.ok, data: data }; }); })
        .then(function (result) {
          if (!result.ok || !result.data.success) {
            clearGuardianErrors();
            if (applyServerGuardianErrors(result.data && result.data.errors)) {
              setGuardianStatus('Please fix the highlighted fields.', true);
            } else {
              setGuardianStatus((result.data && result.data.message) || 'Could not save. Try again.', true);
            }
            return;
          }
          syncConsentPayloadGuardian(currentViewBtn, result.data);
          fillGuardianFields(result.data);
        })
        .catch(function () {
          setGuardianStatus('Could not save. Try again.', true);
        })
        .finally(function () {
          savingGuardian = false;
          if (guardianSaveBtn) guardianSaveBtn.disabled = false;
        });
    });
  }

  guardianFieldMap.forEach(function (field) {
    if (!field.el) return;
    field.el.addEventListener('input', function () {
      setFieldError(field, '');
      if (guardianStatusEl && guardianStatusEl.classList.contains('text-[#c2410c]')) {
        setGuardianStatus('');
      }
    });
  });

  document.querySelectorAll('.js-artist-consent-view').forEach(function (btn) {
    btn.addEventListener('click', function () {
      openModal(btn);
    });
  });

  modal.querySelectorAll('[data-close-artist-cdm]').forEach(function (el) {
    el.addEventListener('click', closeModal);
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && modal.classList.contains('artist-cdm-open')) {
      closeModal();
    }
  });
})();
</script>
<script>
(function () {
  var modal = document.getElementById('artistConsentPendingModal');
  if (!modal) return;

  var leadEl = document.getElementById('acpmLead');
  var avatarEl = document.getElementById('acpmAvatar');
  var cardTitleEl = document.getElementById('acpmCardTitle');
  var cardMetaEl = document.getElementById('acpmCardMeta');
  var bookingStatusEl = document.getElementById('acpmBookingStatus');
  var autoNoteEl = document.getElementById('acpmAutoNote');
  var autoNoteTextEl = document.getElementById('acpmAutoNoteText');
  var resendBtn = document.getElementById('acpmResendBtn');
  var resendLabel = document.getElementById('acpmResendLabel');
  var showQrBtn = document.getElementById('acpmShowQrBtn');
  var showQrLabel = document.getElementById('acpmShowQrLabel');
  var qrWrap = document.getElementById('acpmQrWrap');
  var qrImage = document.getElementById('acpmQrImage');
  var statusEl = document.getElementById('acpmStatus');
  var consentLinkInput = document.getElementById('acpmConsentLink');
  var copyLinkBtn = document.getElementById('acpmCopyLinkBtn');
  var copyLinkLabel = document.getElementById('acpmCopyLinkLabel');

  var currentResendUrl = '';
  var currentConsentUrl = '';
  var currentBookingId = '';
  var currentPendingBtn = null;
  var sending = false;
  var loadingConsentLink = false;

  function csrfToken() {
    var meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
  }

  function setStatus(msg, isError) {
    if (!statusEl) return;
    if (!msg) {
      statusEl.classList.remove('on', 'ok', 'err');
      statusEl.textContent = '';
      return;
    }
    statusEl.textContent = msg;
    statusEl.classList.add('on');
    statusEl.classList.toggle('err', !!isError);
    statusEl.classList.toggle('ok', !isError);
  }

  function hideQr() {
    if (qrWrap) qrWrap.classList.remove('on');
    if (showQrBtn) showQrBtn.setAttribute('aria-expanded', 'false');
    if (showQrLabel) showQrLabel.textContent = 'Show QR';
  }

  function consentLinkUrl(bookingId) {
    return @json(url('/api/bookings')) + '/' + encodeURIComponent(bookingId) + '/consent/link';
  }

  function setConsentLink(url, loading) {
    currentConsentUrl = url || '';
    if (consentLinkInput) {
      consentLinkInput.value = url || '';
      consentLinkInput.placeholder = loading ? 'Preparing link…' : 'Link unavailable';
    }
    if (copyLinkBtn) {
      copyLinkBtn.disabled = loading || !url;
    }
    if (copyLinkLabel && !loading) copyLinkLabel.textContent = 'Copy link';
    if (currentPendingBtn) {
      currentPendingBtn.setAttribute('data-consent-url', url || '');
    }
  }

  function syncPendingConsentUrl(url) {
    if (!currentPendingBtn || !url) return;
    currentPendingBtn.setAttribute('data-consent-url', url);
    document.querySelectorAll('.js-artist-consent-pending[data-resend-url="' + currentResendUrl + '"]').forEach(function (btn) {
      btn.setAttribute('data-consent-url', url);
    });
  }

  function loadConsentLink(bookingId) {
    if (!bookingId) {
      setConsentLink('', false);
      return;
    }

    if (currentConsentUrl) {
      setConsentLink(currentConsentUrl, false);
      return;
    }

    loadingConsentLink = true;
    setConsentLink('', true);

    fetch(consentLinkUrl(bookingId), {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
      .then(function (res) { return res.json().then(function (data) { return { ok: res.ok, data: data }; }); })
      .then(function (result) {
        if (!result.ok || !result.data.success || !result.data.consent_url) {
          setConsentLink('', false);
          if (!currentConsentUrl) {
            setStatus((result.data && result.data.message) || 'Consent link is not available yet.', true);
          }
          return;
        }
        setConsentLink(result.data.consent_url, false);
        syncPendingConsentUrl(result.data.consent_url);
      })
      .catch(function () {
        setConsentLink('', false);
        setStatus('Could not load consent link. Please try again.', true);
      })
      .finally(function () {
        loadingConsentLink = false;
      });
  }

  function copyConsentLink() {
    if (!currentConsentUrl || !copyLinkBtn) return;
    var done = function () {
      if (copyLinkLabel) copyLinkLabel.textContent = 'Copied';
      clearTimeout(copyLinkBtn._copyTimer);
      copyLinkBtn._copyTimer = setTimeout(function () {
        if (copyLinkLabel) copyLinkLabel.textContent = 'Copy link';
      }, 1500);
    };
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(currentConsentUrl).then(done).catch(function () {
        if (consentLinkInput) {
          consentLinkInput.focus();
          consentLinkInput.select();
          try { document.execCommand('copy'); done(); } catch (e) {}
        }
      });
      return;
    }
    if (consentLinkInput) {
      consentLinkInput.focus();
      consentLinkInput.select();
      try { document.execCommand('copy'); done(); } catch (e) {}
    }
  }

  function openModal(btn) {
    var ds = btn.dataset || {};
    currentPendingBtn = btn;
    currentBookingId = ds.bookingId || '';
    currentResendUrl = ds.resendUrl || '';
    currentConsentUrl = ds.consentUrl || '';
    sending = false;

    var clientName = ds.clientName || 'Client';
    var firstName = ds.clientFirstName || clientName.split(/\s+/)[0] || 'Client';
    var service = ds.service || 'Booking';

    if (leadEl) leadEl.textContent = firstName + " hasn't completed the consent form yet.";
    if (avatarEl) avatarEl.textContent = ds.clientInitials || '?';
    if (cardTitleEl) cardTitleEl.textContent = clientName + ' · ' + service;
    if (cardMetaEl) {
      cardMetaEl.textContent = [ds.dateDisplay, ds.timeRange, ds.bookingRef].filter(Boolean).join(' · ') || '—';
    }
    if (bookingStatusEl) {
      bookingStatusEl.className = 'acpm-pill ' + (ds.statusPill || 'g');
      bookingStatusEl.textContent = ds.statusLabel || 'Confirmed';
    }
    if (autoNoteEl && autoNoteTextEl) {
      var note = (ds.autoNote || '').trim();
      autoNoteTextEl.textContent = note;
      autoNoteEl.classList.toggle('on', !!note);
    }

    if (resendBtn) resendBtn.disabled = false;
    if (resendLabel) resendLabel.textContent = 'Resend email';
    hideQr();
    setStatus('');
    setConsentLink(currentConsentUrl, !!currentBookingId && !currentConsentUrl);
    loadConsentLink(currentBookingId);

    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
    requestAnimationFrame(function () {
      modal.classList.add('artist-cpm-open');
    });
  }

  function closeModal() {
    modal.classList.remove('artist-cpm-open');
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
    hideQr();
    currentPendingBtn = null;
    currentBookingId = '';
    loadingConsentLink = false;
  }

  if (copyLinkBtn) {
    copyLinkBtn.addEventListener('click', copyConsentLink);
  }

  if (showQrBtn) {
    showQrBtn.addEventListener('click', function () {
      if (!currentConsentUrl) {
        setStatus('Consent link is not ready yet. Try again in a moment.', true);
        if (currentBookingId && !loadingConsentLink) loadConsentLink(currentBookingId);
        return;
      }
      var open = showQrBtn.getAttribute('aria-expanded') === 'true';
      if (open) {
        hideQr();
        return;
      }
      if (qrImage) {
        qrImage.src = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' + encodeURIComponent(currentConsentUrl);
      }
      if (qrWrap) qrWrap.classList.add('on');
      showQrBtn.setAttribute('aria-expanded', 'true');
      if (showQrLabel) showQrLabel.textContent = 'Hide QR';
    });
  }

  if (resendBtn) {
    resendBtn.addEventListener('click', function () {
      if (sending || !currentResendUrl) return;
      sending = true;
      resendBtn.disabled = true;
      if (resendLabel) resendLabel.textContent = 'Sending…';
      setStatus('');

      fetch(currentResendUrl, {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken(),
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({})
      })
        .then(function (res) { return res.json().then(function (data) { return { ok: res.ok, data: data }; }); })
        .then(function (result) {
          if (!result.ok || !result.data.success) {
            setStatus((result.data && result.data.message) || 'Could not send email.', true);
            return;
          }
          if (result.data.consent_url) {
            setConsentLink(result.data.consent_url, false);
            syncPendingConsentUrl(result.data.consent_url);
          }
          setStatus(result.data.message || 'Consent form email sent.', false);
        })
        .catch(function () {
          setStatus('Could not send email. Please try again.', true);
        })
        .finally(function () {
          sending = false;
          resendBtn.disabled = false;
          if (resendLabel) resendLabel.textContent = 'Resend email';
        });
    });
  }

  document.querySelectorAll('.js-artist-consent-pending').forEach(function (btn) {
    btn.addEventListener('click', function () {
      openModal(btn);
    });
  });

  modal.querySelectorAll('[data-close-artist-cpm]').forEach(function (el) {
    el.addEventListener('click', closeModal);
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && modal.classList.contains('artist-cpm-open')) {
      closeModal();
    }
  });
})();
</script>
<script>
  window.artistBookingMessageModal = {
    apiBase: @json(url('/api/chat')),
    csrf: @json(csrf_token()),
    userId: @json((string) auth()->id()),
    inboxUrl: @json(route('artist.chat.index')),
  };
  window.artistAftercareSendModal = {
    csrf: @json(csrf_token()),
    previewUrlTemplate: @json(url('/api/bookings/__ID__/aftercare-preview')),
    sendUrlTemplate: @json(url('/api/bookings/__ID__/aftercare-send')),
  };
</script>
<script src="{{ asset('js/artist-booking-message-modal.js') }}?v=4"></script>
<script src="{{ asset('js/artist-aftercare-send-modal.js') }}?v=2"></script>
@endsection
