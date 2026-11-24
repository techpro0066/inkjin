@extends('layouts.admin_dashboard_layout')

@section('title', 'Plans')

@section('styles')
  <style>
    html, body { overflow-x: clip; }
    .plans-page { max-width: 1440px; padding: 32px; }
    .plans-page, .plans-page * { box-sizing: border-box; }
    .plans-page { color: #24202b; }
    .plans-page [hidden] { display: none !important; }
    .plans-page h1, .plans-page h2, .plans-page p { margin: 0; }
    .plans-heading { display: flex; justify-content: space-between; align-items: flex-start; gap: 20px; margin-bottom: 24px; }
    .plans-heading h1 { font-size: 30px; line-height: 1.2; font-weight: 800; letter-spacing: -.03em; }
    .plans-heading p { color: #716b79; font-size: 14px; margin-top: 6px; }
    .plans-notice { color: #665781; background: #f3effa; border: 1px solid #e6def2; border-radius: 12px; padding: 10px 14px; font-size: 12px; }
    .plan-tabs { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; margin-bottom: 20px; }
    .plan-tab { border: 1px solid #e5e0e9; border-radius: 999px; background: #fff; padding: 9px 15px; color: #625a6b; font: inherit; font-size: 13px; font-weight: 700; cursor: pointer; }
    .plan-tab.active { color: #fff; border-color: #310f7a; background: #310f7a; }
    .plan-tab.add { border-style: dashed; color: #310f7a; }
    .plans-grid { display: grid; grid-template-columns: minmax(0, 1.1fr) minmax(320px, .9fr); gap: 20px; align-items: start; padding-bottom: 88px; }
    .plans-form { display: grid; gap: 16px; }
    .plan-section, .plan-preview { background: #fff; border: 1px solid #e9e5ed; border-radius: 18px; box-shadow: 0 3px 14px rgba(38, 24, 58, .04); }
    .plan-section { padding: 20px; }
    .plan-section h2 { font-size: 15px; font-weight: 800; margin-bottom: 16px; }
    .plan-field { min-width: 0; margin-bottom: 16px; }
    .plan-field:last-child { margin-bottom: 0; }
    .plan-label { display: block; color: #393342; font-size: 12px; font-weight: 700; margin-bottom: 7px; }
    .plan-input, .plan-select, .plan-textarea { width: 100%; min-height: 42px; border: 1px solid #ded9e3; border-radius: 9px; background: #fff; color: #292431; padding: 9px 11px; font: inherit; font-size: 13px; }
    .plan-textarea { min-height: 74px; resize: vertical; }
    .plan-input:focus, .plan-select:focus, .plan-textarea:focus { outline: 2px solid #d5c7f5; border-color: #7a5bc0; }
    .plan-row { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
    .plan-radio { display: flex; flex-wrap: wrap; gap: 8px; }
    .plan-radio label { display: inline-flex; align-items: center; gap: 7px; min-height: 38px; border: 1px solid #e4dfea; border-radius: 9px; padding: 7px 11px; color: #5f5867; font-size: 12px; cursor: pointer; }
    .plan-radio input { accent-color: #5c36a5; }
    .plan-help { color: #817a89; font-size: 11px; line-height: 1.5; margin-top: 6px; }
    .plan-prices-wrap { overflow-x: auto; }
    .plan-prices { width: 100%; min-width: 440px; border-collapse: collapse; }
    .plan-prices th { color: #817a89; text-align: left; font-size: 10px; text-transform: uppercase; letter-spacing: .08em; padding: 0 8px 8px; }
    .plan-prices td { border-top: 1px solid #f0edf3; padding: 7px 8px; font-size: 12px; }
    .plan-prices .currency { font-weight: 800; }
    .plan-prices input { width: 100%; min-width: 82px; height: 34px; border: 1px solid #e1dce6; border-radius: 7px; padding: 5px 8px; font: inherit; }
    .plan-prices .yearly { color: #716b79; white-space: nowrap; }
    .plan-features { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 9px 14px; }
    .plan-feature { display: flex; align-items: flex-start; gap: 8px; color: #514a5a; font-size: 12px; line-height: 1.4; }
    .plan-feature input { accent-color: #5c36a5; margin-top: 2px; }
    .plan-preview { position: sticky; top: 20px; overflow: hidden; }
    .preview-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 16px 18px; border-bottom: 1px solid #eeeaf2; }
    .preview-head strong { font-size: 13px; }
    .preview-toggle { display: flex; gap: 4px; background: #f4f1f7; border-radius: 8px; padding: 3px; }
    .preview-toggle button { border: 0; background: transparent; color: #6d6575; border-radius: 6px; padding: 6px 9px; font: inherit; font-size: 11px; cursor: pointer; }
    .preview-toggle button.active { color: #392263; background: #fff; box-shadow: 0 1px 3px #2014371a; }
    .preview-body { padding: 20px; }
    .preview-banner { display: none; background: #f5f0ff; color: #54348c; padding: 10px 14px; font-size: 12px; font-weight: 700; }
    .preview-banner.visible { display: block; }
    .preview-card { border: 1px solid #e8e2ee; border-radius: 16px; padding: 22px; background: linear-gradient(160deg, #fff, #fbf9fe); }
    .preview-badge { display: inline-block; border-radius: 999px; background: #eee7fb; color: #563394; padding: 5px 9px; font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: .06em; }
    .preview-title { font-size: 24px; font-weight: 800; letter-spacing: -.03em; margin-top: 12px; }
    .preview-tagline { color: #716b79; font-size: 12px; line-height: 1.5; margin-top: 5px; }
    .preview-price { display: flex; align-items: baseline; gap: 5px; margin: 20px 0 3px; }
    .preview-price strong { font-size: 36px; line-height: 1; letter-spacing: -.04em; }
    .preview-price span, .preview-yearly { color: #817a89; font-size: 11px; }
    .preview-items { display: grid; gap: 10px; margin: 20px 0; padding: 0; list-style: none; }
    .preview-items li { display: flex; gap: 9px; color: #514a5a; font-size: 12px; line-height: 1.4; }
    .preview-items li:before { content: 'check'; font-family: 'Material Symbols Outlined'; color: #4a9b70; font-size: 16px; }
    .preview-cta { width: 100%; border: 0; border-radius: 9px; background: #310f7a; color: #fff; padding: 12px; font: inherit; font-size: 12px; font-weight: 800; }
    .preview-fine { color: #8a8391; text-align: center; font-size: 10px; line-height: 1.5; margin-top: 9px !important; }
    .preview-terms { display: grid; gap: 0; margin-top: 16px; border-top: 1px solid #ebe6ef; }
    .preview-term { display: flex; justify-content: space-between; gap: 12px; padding: 10px 0; border-bottom: 1px solid #ebe6ef; color: #595261; font-size: 11px; }
    .preview-term strong { color: #30283a; text-align: right; }
    .preview-feature-list { display: none; gap: 8px; padding: 18px 20px; }
    .preview-feature-list.visible { display: grid; }
    .preview-feature-list span { display: flex; align-items: center; gap: 8px; color: #514a5a; font-size: 12px; }
    .preview-feature-list span:before { content: 'check_circle'; font-family: 'Material Symbols Outlined'; color: #4a9b70; font-size: 17px; }
    .preview-currency { border-top: 1px solid #eeeaf2; padding: 14px 18px; }
    .preview-currency .plan-label { display: inline; margin-right: 8px; }
    .preview-currency .plan-select { width: auto; min-height: 34px; padding: 5px 9px; }
    .plans-savebar { position: fixed; z-index: 20; left: 260px; right: 0; bottom: 0; display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 13px 28px; background: rgba(255,255,255,.96); border-top: 1px solid #e8e3ec; box-shadow: 0 -5px 18px #24143a0d; }
    .plans-savebar span { color: #77707f; font-size: 12px; }
    .plans-savebar button { border: 0; border-radius: 9px; background: #310f7a; color: #fff; padding: 10px 18px; font: inherit; font-size: 12px; font-weight: 800; cursor: pointer; }
    .plans-main { background: #f6f2f8; overflow: visible; }
    .plans-page { max-width: none; padding: 28px 32px 110px; color: #1d1a21; font-size: 14px; }
    .plans-heading { margin-bottom: 18px; }
    .plans-heading h1 { margin: 0; font-size: 28px; letter-spacing: -.02em; }
    .plan-tabs { margin-bottom: 22px; }
    .plan-tab { border-color: #e3d8e8; border-radius: 12px; padding: 9px 14px; color: #1d1a21; text-align: left; }
    .plan-tab.active { border: 2px solid #3e007c; padding: 8px 13px; color: #1d1a21; background: #faf3fd; }
    .plan-tab.add { border: 1px dashed #e3d8e8; background: transparent; color: #3e007c; }
    .plan-tab small { display: flex; align-items: center; gap: 6px; margin-top: 2px; color: #7a7280; font-size: 11.5px; font-weight: 500; }
    .plan-tab .plan-dot { width: 7px; height: 7px; flex: 0 0 7px; border-radius: 50%; background: #1b9a4b; }
    .plan-tab .plan-dot.off { background: #c9c1cc; }
    .plan-tab .plan-dot.draft { background: #e0a100; }
    .plans-grid { grid-template-columns: minmax(0, 1fr) 380px; gap: 24px; }
    .plans-form { display: block; }
    .plan-section { margin-bottom: 14px; padding: 20px 22px; border-color: #ece0f0; border-radius: 16px; box-shadow: none; }
    .plan-section h2 { margin-bottom: 14px; font-size: 16px; }
    .plan-field { margin-bottom: 16px; }
    .plan-label { margin-bottom: 6px; color: #4b4452; font-size: 12.5px; }
    .plans-page input[type="text"], .plans-page input[type="number"], .plans-page input[type="date"], .plans-page textarea, .plans-page select {
      width: 100%; padding: 10px 12px; border: 1px solid #ddd2e3; border-radius: 10px; background: #fff; color: #1d1a21; font: inherit; font-size: 14px;
    }
    .plans-page input:focus, .plans-page textarea:focus, .plans-page select:focus { outline: 2px solid #3e007c; outline-offset: -1px; border-color: #3e007c; }
    .plan-radio { gap: 6px; }
    .plan-radio label { position: relative; display: inline-flex; min-height: 0; gap: 0; padding: 0; border: 0; border-radius: 99px; background: transparent; cursor: pointer; }
    .plan-radio input { position: absolute; width: 1px; height: 1px; opacity: 0; pointer-events: none; }
    .plan-radio label span { display: inline-flex; align-items: center; gap: 7px; padding: 9px 15px; border: 1.5px solid #ddd2e3; border-radius: 99px; background: #fff; color: #4b4452; font-size: 13.5px; font-weight: 700; }
    .plan-radio input:checked + span { border-color: #3e007c; background: #3e007c; color: #fff; }
    .plan-radio input:focus-visible + span { outline: 2px solid #3e007c; outline-offset: 2px; }
    .plan-help { color: #7a7280; font-size: 12.5px; }
    .plan-prices { border-collapse: collapse; }
    .plan-prices th { font-size: 11.5px; letter-spacing: 0; text-transform: none; }
    .plan-prices td { border-color: #f4edf6; }
    .plan-prices input { max-width: 150px; border-radius: 10px; padding: 7px 10px; }
    .plan-prices tr.base td { background: #faf3fd; }
    .plan-prices tr.base td:first-child { padding-left: 8px; border-radius: 8px 0 0 8px; }
    .plan-prices .price-source { color: #9a93a0; font-size: 11px; font-weight: 700; }
    .plan-prices .price-source.custom { color: #3e007c; }
    .plan-prices .price-source button { border: 0; background: none; color: #3e007c; font: inherit; font-size: 12px; font-weight: 700; cursor: pointer; }
    .commission-control { position: relative; width: 176px; }
    .plans-page .commission-control input[type="number"] { width: 100%; height: 48px; padding: 10px 40px 10px 14px; border-radius: 12px; }
    .commission-control span { position: absolute; top: 50%; right: 15px; color: #7a7280; font-size: 16px; font-weight: 600; transform: translateY(-50%); pointer-events: none; }
    .plan-preview { position: sticky; top: 20px; overflow: visible; border: 0; border-radius: 0; background: transparent; box-shadow: none; }
    .preview-head { padding: 0; margin-bottom: 12px; border: 0; }
    .preview-head strong { color: #7a7280; font-size: 12px; letter-spacing: .05em; text-transform: uppercase; }
    .preview-toggle { gap: 6px; padding: 0; background: transparent; }
    .preview-toggle label span { padding: 5px 11px; font-size: 12px; }
    .preview-banner { margin-bottom: 12px; border-radius: 12px; }
    .preview-currency { margin-top: 12px; padding: 0; border: 0; }
    .preview-currency label span { padding: 6px 10px; font-size: 11px; }
    .plan { position: relative; display: flex; flex-direction: column; padding: 1.6rem 1.4rem 1.4rem; border: 2px solid #3e007c; border-radius: 20px; background: #fff; }
    .plan.pro { border: 2px solid #3e007c; box-shadow: 0 6px 24px rgba(62,0,124,.08); }
    .plan .badge { position: absolute; top: -12px; left: 1.4rem; padding: .3rem .7rem; border-radius: 999px; background: #3e007c; color: #fff; font-size: 11px; font-weight: 700; letter-spacing: .03em; }
    .plan .name { font-size: 20px; font-weight: 800; }
    .plan .tagline { margin-top: .3rem; color: #4b4452; font-size: 13px; }
    .plan .price { display: flex; align-items: baseline; gap: .35rem; margin: 1.2rem 0 .25rem; }
    .plan .amount { font-size: 34px; font-weight: 800; letter-spacing: -.02em; }
    .plan .per { color: #7a7280; font-size: 14px; font-weight: 500; }
    .plan .pn { min-height: 1.4em; color: #4b4452; font-size: 13px; }
    .plan .pn b { color: #3e007c; }
    .plan .fees { margin: 1.1rem 0 1.2rem; padding: .75rem .9rem; border-radius: 10px; background: #faf0fb; color: #4b4452; font-size: 12px; line-height: 1.55; }
    .plan .fees b, .plan .fees .k { color: #1d1a21; }
    .plan .fees .k { display: block; margin-bottom: .15rem; font-weight: 700; }
    .plan .lt { margin-bottom: .7rem; color: #7a7280; font-size: 12px; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; }
    .plan ul { display: flex; flex-direction: column; gap: .6rem; margin: 0 0 1.4rem; padding: 0; list-style: none; }
    .plan li { display: flex; align-items: center; gap: .6rem; font-size: 14px; line-height: 1.4; }
    .plan .tick { display: flex; flex: 0 0 22px; align-items: center; justify-content: center; width: 22px; height: 22px; border-radius: 50%; background: #edd9ff; }
    .plan .tick::before { width: 6px; height: 10px; margin-top: -2px; border: solid #3e007c; border-width: 0 2px 2px 0; content: ""; transform: rotate(45deg); }
    .plan .b { padding: .85rem 1rem; border-radius: 10px; font-size: 15px; font-weight: 700; text-align: center; }
    .plan.pro .b { background: #3e007c; color: #fff; }
    .plan .b.trial { border: 0; background: #3e007c; color: #fff; }
    .plan:not(.pro) .b { border: 1.5px solid #d9d2dc; }
    .plan .b.ghost { border: 1.5px solid #d9d2dc; background: #fff; color: #7a7280; }
    .plan .fine { margin: .7rem 0 0; color: #7a7280; font-size: 12px; line-height: 1.5; text-align: center; }
    .plan .yl { margin-bottom: 6px; color: #3e007c; font-size: 11px; font-weight: 800; letter-spacing: .06em; }
    .plan .tr1 { display: flex; justify-content: space-between; gap: 10px; padding: 11px 0; border-top: 1px solid #f4edf6; font-size: 14px; }
    .plan-features { grid-template-columns: 1fr 1fr; gap: 2px 18px; }
    .plan-feature { align-items: center; gap: 16px; padding: 13px 5px; border-top: 1px solid #f4edf6; font-size: 15.5px; }
    .plan-feature input {
      display: grid; flex: 0 0 22px; place-content: center; width: 22px; height: 22px; margin: 0;
      appearance: none; border: 1px solid #cfc3d8; border-radius: 5px; background: #fff; cursor: pointer;
    }
    .plan-feature input::before {
      width: 6px; height: 11px; margin-top: -2px; border: solid #fff; border-width: 0 2px 2px 0;
      content: ""; transform: rotate(45deg) scale(0); transition: transform .12s ease-in-out;
    }
    .plan-feature input:checked { border-color: #3e007c; background: #3e007c; }
    .plan-feature input:checked,
    .plan-feature input:checked:hover,
    .plan-feature input:checked:focus,
    .plan-feature input:checked:focus-visible,
    .plan-feature input:checked:active { border-color: #3e007c; background-color: #3e007c; }
    .plan-feature input:checked::before { transform: rotate(45deg) scale(1); }
    .plan-feature input:focus-visible { outline: 2px solid #3e007c; outline-offset: 2px; }
    .plan-row { grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 14px; }
    .plan-help { font-size: 12.5px; }
    .plan-choice-group { min-width: 0; border: 0; padding: 0; }
    .included-item-row { display: flex; align-items: center; gap: 10px; margin-bottom: 8px; }
    .plans-page .included-item-row input[type="text"] { flex: 1; min-width: 0; height: 48px; }
    .included-item-remove { flex: 0 0 28px; border: 0; background: transparent; color: #9a8da4; font: inherit; font-size: 22px; cursor: pointer; }
    .included-item-add { border: 0; padding: 0; background: transparent; color: #3e007c; font: inherit; font-size: 14px; font-weight: 700; cursor: pointer; }
    .plan-optional { color: #8f8793; font-weight: 400; }
    .feature-add-row { display: grid; grid-template-columns: minmax(0, 1fr) auto; align-items: center; gap: 10px; margin-top: 14px; }
    .feature-add-button { min-width: 82px; height: 48px; padding: 0 18px; border: 1px solid #ddd2e3; border-radius: 12px; background: #fff; color: #1d1a21; font: inherit; font-size: 14px; font-weight: 700; cursor: pointer; }
    .feature-add-button:disabled { cursor: wait; opacity: .65; }
    .feature-add-help { margin-top: 6px; color: #8a8391; font-size: 14px; }
    .cmp { overflow: hidden; border: 1px solid #ece0f0; border-radius: 16px; background: #fff; }
    .cmp .g { padding: 12px 14px 4px; color: #7a7280; font-size: 11px; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; }
    .cmp .r { display: flex; justify-content: space-between; gap: 10px; padding: 8px 14px; border-top: 1px solid #f4edf6; font-size: 13px; }
    .cmp .tick { color: #3e007c; font-weight: 700; }
    .plans-savebar { left: 260px; padding: 12px 32px; box-shadow: none; }
    .plans-savebar span { flex: 1; color: #7a7280; font-size: 13px; }
    .plans-savebar button { border-radius: 10px; padding: 10px 18px; background: #3e007c; font-size: 14px; }
    @media (max-width: 1023px) { .plans-page { padding: 20px; } .plans-savebar { left: 0; padding: 12px 20px; } }
    @media (max-width: 1150px) { .plans-grid { grid-template-columns: 1fr; } .plan-preview { position: static; } }
    @media (max-width: 800px) { .plan-features { grid-template-columns: 1fr; } }
    @media (max-width: 520px) { .plans-page { padding: 16px; } .plan-section { padding: 16px; } .plan-row { grid-template-columns: 1fr; gap: 0; } .plan-features { grid-template-columns: 1fr; } .plans-heading h1 { font-size: 26px; } }
  </style>
@endsection

@section('content')
<main class="main-content min-h-screen plans-main">
  <div class="plans-page">
    <header class="plans-heading">
      <h1>Plans</h1>
    </header>

    <div class="plan-tabs" id="planTabs" aria-label="Select a sample plan"></div>

    <div class="plans-grid">
      <form class="plans-form" id="plansForm">
        <section class="plan-section">
          <h2>Plan</h2>
          <div class="plan-field">
            <span class="plan-label">Status</span>
            <div class="plan-radio">
              <label><input type="radio" name="status" value="On" checked><span>On</span></label>
              <label><input type="radio" name="status" value="Off"><span>Off</span></label>
              <label><input type="radio" name="status" value="Draft"><span>Draft</span></label>
            </div>
            <p class="plan-help" id="statusHelp">Live. People who can see it can choose it.</p>
          </div>
          <div class="plan-field">
            <label class="plan-label" for="planName">Name</label>
            <input class="plan-input" id="planName" type="text" value="Pro" data-field="name">
          </div>
          <div class="plan-row">
            <div class="plan-field">
              <span class="plan-label">For</span>
              <div class="plan-radio">
                <label><input type="radio" name="audience" value="Artist" checked><span>Artists</span></label>
                <label><input type="radio" name="audience" value="Studio"><span>Studios</span></label>
              </div>
            </div>
            <div class="plan-field">
              <span class="plan-label">Show on the Plan tab</span>
              <div class="plan-radio">
                <label><input type="radio" name="visibility" value="Public" checked><span>Yes</span></label>
                <label><input type="radio" name="visibility" value="Hidden"><span>No, only if they have it</span></label>
              </div>
            </div>
          </div>
          <div class="plan-field">
            <label class="plan-label" for="planBadge">Badge (optional)</label>
            <input class="plan-input" id="planBadge" type="text" value="RECOMMENDED" data-field="badge" placeholder="RECOMMENDED">
          </div>
        </section>

        <section class="plan-section">
          <h2>Price and fees</h2>
          <div class="plan-field">
            <span class="plan-label">Prices</span>
            <div class="plan-prices-wrap">
              <table class="plan-prices">
                <thead><tr><th>Currency</th><th>Monthly</th><th>Yearly</th><th></th></tr></thead>
                <tbody id="priceRows"></tbody>
              </table>
            </div>
            <p class="plan-help">Set the euro price; other currencies are converted and rounded. You can override any price. Yearly is 20% less. Use 0 for a free plan.</p>
          </div>
          <div class="plan-row">
            <div class="plan-field">
              <label class="plan-label" for="commission">Commission per booking</label>
              <div class="commission-control">
                <input class="plan-input" id="commission" type="number" min="0" step="0.1" value="6" data-field="commission" aria-describedby="commissionUnit">
                <span id="commissionUnit" aria-hidden="true">%</span>
              </div>
            </div>
            <div class="plan-field">
              <span class="plan-label">Client booking fee</span>
              <div class="plan-radio">
                <label><input type="radio" name="bookingFee" value="0" checked><span>None</span></label>
                <label><input type="radio" name="bookingFee" value="1"><span>Yes</span></label>
              </div>
              <p class="plan-help">Amounts come from Booking fee rates.</p>
            </div>
          </div>
        </section>

        <section class="plan-section" id="studioSection" hidden>
          <h2>Studio</h2>
          <div class="plan-row">
            <div class="plan-field">
              <label class="plan-label" for="includedArtists">Artists included</label>
              <input class="plan-input" id="includedArtists" type="number" min="1" value="3" data-field="includedArtists">
            </div>
            <div class="plan-field">
              <label class="plan-label" for="extraArtist">Each extra artist, monthly</label>
              <input class="plan-input" id="extraArtist" type="number" min="0" value="8" data-field="extraArtist">
              <p class="plan-help">EUR; other currencies are converted the same way.</p>
            </div>
          </div>
          <p class="plan-help">Guest artists do not count.</p>
        </section>

        <section class="plan-section">
          <h2>Free trial</h2>
          <div class="plan-field">
            <label class="plan-label" for="trialDays">Days</label>
            <input class="plan-input" id="trialDays" type="number" min="0" value="0" data-field="trialDays" style="max-width: 160px">
            <p class="plan-help">0 means no trial. No card is needed to start.</p>
          </div>
          <fieldset class="plan-field plan-choice-group" id="trialFallbackField" hidden>
            <legend class="plan-label">If they don't upgrade, move them to</legend>
            <div class="plan-radio">
              <label><input type="radio" name="trialFallback" value="Previous plan"><span>The plan they had before</span></label>
              <label><input type="radio" name="trialFallback" value="Founding"><span>Founding</span></label>
              <label><input type="radio" name="trialFallback" value="Standard"><span>Standard</span></label>
            </div>
          </fieldset>
        </section>

        <section class="plan-section">
          <h2>Who can get it</h2>
          <div class="plan-field">
            <label class="plan-label" for="cutoffDate">Sign-up cutoff date (optional, the day is included)</label>
            <input class="plan-input" id="cutoffDate" type="date" data-field="cutoffDate" style="max-width: 220px">
            <p class="plan-help">No cutoff date set.</p>
          </div>
          <div class="plan-field">
            <label class="plan-label" for="afterCutoff">After the cutoff, new sign-ups start on</label>
            <select class="plan-select" id="afterCutoff" data-field="afterCutoff">
              <option>Standard</option><option>Pro</option><option>Studio Standard</option><option>Studio Pro</option><option>Previous plan</option>
            </select>
            <p class="plan-help">Accounts already on this plan keep it.</p>
          </div>
        </section>

        <section class="plan-section">
          <h2>What artists see</h2>
          <div class="plan-field">
            <span class="plan-label">Look</span>
            <div class="plan-radio">
              <label><input type="radio" name="layout" value="card" checked><span>Price card</span></label>
              <label><input type="radio" name="layout" value="terms"><span>List of terms</span></label>
            </div>
          </div>
          <div id="cardCopy">
            <div class="plan-field">
              <label class="plan-label" for="tagline">Tagline</label>
              <input class="plan-input" id="tagline" type="text" value="Everything you need to grow your bookings." data-field="tagline">
            </div>
            <div class="plan-field">
              <label class="plan-label" for="priceNote">Price note (optional)</label>
              <input class="plan-input" id="priceNote" type="text" data-field="priceNote" placeholder="No monthly fee.">
            </div>
            <div class="plan-field">
              <label class="plan-label" for="listTitle">List title</label>
              <input class="plan-input" id="listTitle" type="text" value="Includes" data-field="listTitle">
            </div>
            <div class="plan-field">
              <div id="includedItemsEditor" role="group" aria-label="Included items"></div>
              <button type="button" class="included-item-add" id="addIncludedItem">+ Add line</button>
            </div>
            <div class="plan-field">
              <label class="plan-label" for="buttonLabel">Button</label>
              <input class="plan-input" id="buttonLabel" type="text" value="Choose Pro" data-field="buttonLabel">
            </div>
            <div class="plan-field">
              <label class="plan-label" for="buttonNote">Small text under the button <span class="plan-optional">(optional)</span></label>
              <input class="plan-input" id="buttonNote" type="text" value="Cancel anytime." data-field="buttonNote">
            </div>
          </div>
          <div id="termsCopy" hidden>
            <div class="plan-field">
              <label class="plan-label" for="termsIntro">Line under the name</label>
              <input class="plan-input" id="termsIntro" type="text" data-field="termsIntro" placeholder="Your plan details">
            </div>
            <div class="plan-field">
              <label class="plan-label" for="termsList">Terms (one per line: label | value)</label>
              <textarea class="plan-textarea" id="termsList" data-field="termsList" placeholder="No subscription | €0/month&#10;Commission | 0% per booking"></textarea>
            </div>
            <div class="plan-field">
              <label class="plan-label" for="termsNote">Note (optional)</label>
              <textarea class="plan-textarea" id="termsNote" data-field="termsNote"></textarea>
            </div>
            <div class="plan-field">
              <label class="plan-label" for="trialButton">Trial button (optional)</label>
              <input class="plan-input" id="trialButton" type="text" data-field="trialButton">
            </div>
          </div>
        </section>

        <section class="plan-section">
          <h2>Features</h2>
          <div class="plan-features" id="featureList"></div>
          <div class="feature-add-row">
            <input class="plan-input" id="newFeature" type="text" placeholder="Add a feature">
            <button type="button" id="addFeature" class="feature-add-button">Add</button>
          </div>
          <p class="feature-add-help">New features are added to the catalog and selected for this plan. Save to persist the selection.</p>
        </section>
      </form>

      <aside class="plan-preview" aria-label="Plan preview">
        <div class="preview-head">
          <strong>Preview</strong>
          <div class="plan-radio preview-toggle">
            <label><input type="radio" name="preview" value="card" checked><span>Card</span></label>
            <label><input type="radio" name="preview" value="features"><span>Features</span></label>
          </div>
        </div>
        <div id="previewBanner" class="preview-banner"></div>
        <div id="previewCard"></div>
        <div id="previewFeatures" style="display:none"></div>
        <div class="preview-currency plan-radio" id="previewCurrencies" aria-label="Preview currency"></div>
      </aside>
    </div>
  </div>
</main>
<div class="plans-savebar">
  <span id="designState">No changes</span>
  <button type="button" id="saveDesign">Save</button>
</div>
@endsection

@section('scripts')
  <script>
    (function () {
      var currencies = ['EUR', 'CAD', 'CHF', 'CZK', 'DKK', 'GBP', 'HUF', 'NOK', 'PLN', 'RON', 'SEK', 'USD'];
      var symbols = { EUR: '€', CAD: 'CA$', CHF: 'CHF ', CZK: '', DKK: '', GBP: '£', HUF: '', NOK: '', PLN: '', RON: '', SEK: '', USD: '$' };
      var features = [
        'Your own booking page and link', 'Custom intake forms', 'Booking page FAQ', 'Guest spot listings',
        'Waitlist when your books are closed', 'Merch link on your booking page', 'Custom domain for your booking page',
        'Merch store synced with Big Cartel', 'Flash upload', 'Smart pricing', 'Portfolio and Instagram import',
        'Deposits', 'Payment links and balance collection', 'Local payment methods', 'Payouts in your currency',
        'Studio revenue split', 'Payment terminal', 'Client list and history', 'Digital consent forms',
        'Google Calendar sync', 'Apple and Outlook calendar sync', 'Refer & Earn', 'Hardy, your AI assistant for Instagram DMs',
        'Priority placement on Inkjin', 'Earnings and insights dashboard', 'Monthly reports', 'Weekly Ask & Ink sessions',
        'Studio booking page', 'Invite artists', 'Shop-wide calendar blocking', 'Pro for every artist in the studio'
      ];
      var storedFeatures = @js($features);
      storedFeatures.forEach(function (feature) {
        if (features.indexOf(feature) === -1) features.push(feature);
      });
      var plans = {};
      var planOrder = [];
      var storedPlans = @js($plans);
      storedPlans.forEach(function (plan) {
        var key = String(plan.id);
        plans[key] = plan;
        planOrder.push(key);
      });
      var activePlan = planOrder.length ? planOrder[0] : 'new';
      if (planOrder.length === 0) {
        plans.new = {
          id: null, slug: null, name: 'New plan', audience: 'Artist', status: 'Draft', visibility: 'Hidden',
          badge: 'RECOMMENDED', prices: { EUR: 0 }, commission: 6, bookingFee: '0', includedArtists: null,
          extraArtist: null, trialDays: 0, trialFallback: 'Previous plan', cutoffDate: '',
          afterCutoff: '', layout: 'card', tagline: '', priceNote: '', listTitle: 'Includes',
          includedItems: '', buttonLabel: '', buttonNote: '', termsIntro: '', termsList: '',
          termsNote: '', trialButton: '', features: [], count: 0
        };
      }
      var activeCurrency = 'EUR';
      var previewMode = 'card';
      var form = document.getElementById('plansForm');
      var tabs = document.getElementById('planTabs');
      var state = document.getElementById('designState');
      var rates = { CAD: 1.62, CHF: 0.94, CZK: 25.2, DKK: 7.46, GBP: 0.86, HUF: 395, NOK: 11.7, PLN: 4.25, RON: 5, SEK: 11.3, USD: 1.16 };
      var storeUrl = @json(route('admin.plans.store'));
      var updateUrl = @json(route('admin.plans.update', ['plan' => '__PLAN__']));
      var featureUrl = @json(route('admin.plans.features.store'));

      function escapeHtml(value) {
        return String(value == null ? '' : value).replace(/[&<>"']/g, function (char) {
          return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[char];
        });
      }

      function convertedAmountFor(plan, currency) {
        var amount = (Number(plan.prices.EUR) || 0) * (rates[currency] || 1);
        if (currency === 'HUF') return Math.round(amount / 100) * 100;
        if (currency === 'CZK') return Math.round(amount / 10) * 10;
        return Math.round(amount);
      }

      function amountFor(plan, currency) {
        if (plan.prices[currency] !== undefined) return Number(plan.prices[currency]) || 0;
        return convertedAmountFor(plan, currency);
      }

      function removeLegacyConvertedPrices(plan) {
        currencies.forEach(function (currency) {
          if (currency === 'EUR' || plan.prices[currency] === undefined || plan.prices[currency] === '') return;
          if (Number(plan.prices[currency]) === convertedAmountFor(plan, currency)) {
            delete plan.prices[currency];
          }
        });
      }

      planOrder.forEach(function (key) {
        removeLegacyConvertedPrices(plans[key]);
      });

      function money(amount, currency) {
        var value = Number(amount) || 0;
        return (symbols[currency] || '') + (value % 1 ? value.toFixed(2) : value) + ({ CZK: ' Kč', DKK: ' kr', HUF: ' Ft', NOK: ' kr', PLN: ' zł', RON: ' lei', SEK: ' kr' }[currency] || '');
      }

      function selectValue(name, value) {
        var input = form.querySelector('[name="' + name + '"][value="' + value + '"]');
        if (input) input.checked = true;
      }

      function selectedValue(name) {
        var input = form.querySelector('[name="' + name + '"]:checked');
        return input ? input.value : '';
      }

      function renderTabs() {
        tabs.innerHTML = planOrder.map(function (key) {
          var plan = plans[key];
          var dotClass = plan.status === 'Off' ? ' off' : (plan.status === 'Draft' ? ' draft' : '');
          var audience = plan.audience === 'Studio' ? 'Studios' : 'Artists';
          return '<button type="button" class="plan-tab' + (key === activePlan ? ' active' : '') + '" data-plan="' + escapeHtml(key) + '">' +
            escapeHtml(plan.name) + '<small><i class="plan-dot' + dotClass + '"></i>' + escapeHtml(plan.status) + ' · ' + audience + ' · ' + (Number(plan.count) || 0) + '</small></button>';
        }).join('') + '<button type="button" class="plan-tab add" id="addPlan">+ New plan</button>';
      }

      function readForm() {
        var plan = plans[activePlan];
        form.querySelectorAll('[data-field]').forEach(function (input) {
          plan[input.dataset.field] = input.value;
        });
        plan.includedItems = Array.from(document.querySelectorAll('[data-included-item]'))
          .map(function (input) { return input.value.trim(); })
          .filter(Boolean)
          .join('\n');
        plan.trialFallback = selectedValue('trialFallback');
        plan.status = selectedValue('status');
        plan.audience = selectedValue('audience');
        plan.visibility = selectedValue('visibility');
        plan.bookingFee = selectedValue('bookingFee');
        plan.layout = selectedValue('layout');
      }

      function renderIncludedItems(items) {
        var editor = document.getElementById('includedItemsEditor');
        editor.innerHTML = items.map(function (item, index) {
          return '<div class="included-item-row"><input type="text" value="' + escapeHtml(item) + '" data-included-item aria-label="Included item ' + (index + 1) + '">' +
            '<button type="button" class="included-item-remove" data-remove-included="' + index + '" aria-label="Remove included item ' + (index + 1) + '">&times;</button></div>';
        }).join('');
      }

      document.getElementById('addIncludedItem').addEventListener('click', function () {
        readForm();
        var items = Array.from(document.querySelectorAll('[data-included-item]')).map(function (input) {
          return input.value;
        });
        items.push('');
        renderIncludedItems(items);
        var inputs = document.querySelectorAll('[data-included-item]');
        inputs[inputs.length - 1].focus();
        state.textContent = 'Unsaved changes';
      });

      document.getElementById('includedItemsEditor').addEventListener('click', function (event) {
        var removeButton = event.target.closest('[data-remove-included]');
        if (!removeButton) return;
        var items = Array.from(document.querySelectorAll('[data-included-item]')).map(function (input) {
          return input.value;
        });
        items.splice(Number(removeButton.dataset.removeIncluded), 1);
        plans[activePlan].includedItems = items.join('\n');
        renderIncludedItems(items);
        renderPreview();
        state.textContent = 'Unsaved changes';
      });

      function renderPrices() {
        var plan = plans[activePlan];
        document.getElementById('priceRows').innerHTML = currencies.map(function (currency) {
          var monthly = amountFor(plan, currency);
          var yearly = Math.round(monthly * 12 * 0.8 * 100) / 100;
          var isBase = currency === 'EUR';
          var isCustom = !isBase && plan.prices[currency] !== undefined && plan.prices[currency] !== '';
          var source = isBase
            ? 'Base price'
            : isCustom
              ? 'Set by you <button type="button" data-reset="' + currency + '">Reset</button>'
              : 'Converted';
          return '<tr class="' + (isBase ? 'base' : '') + '" data-currency-row="' + currency + '">' +
            '<td class="currency">' + currency + '</td><td><input type="number" min="0" step="0.01" value="' + monthly + '" data-currency="' + currency + '" aria-label="' + currency + ' monthly price"></td>' +
            '<td class="yearly">' + escapeHtml(money(yearly, currency)) + '</td><td class="price-source' + (isCustom ? ' custom' : '') + '">' + source + '</td></tr>';
        }).join('');
        document.getElementById('priceRows').querySelectorAll('[data-currency]').forEach(function (input) {
          input.addEventListener('input', function () {
            var currency = input.dataset.currency;
            plan.prices[currency] = input.value;
            state.textContent = 'Unsaved changes';
            currencies.forEach(function (rowCurrency) {
              var row = document.querySelector('[data-currency-row="' + rowCurrency + '"]');
              var rowInput = row.querySelector('[data-currency]');
              var isCustom = rowCurrency !== 'EUR' && plan.prices[rowCurrency] !== undefined && plan.prices[rowCurrency] !== '';
              if (currency === 'EUR' && !isCustom && rowCurrency !== 'EUR') {
                rowInput.value = amountFor(plan, rowCurrency);
              }
              var amount = Number(rowInput.value) || 0;
              row.querySelector('.yearly').textContent = money(Math.round(amount * 12 * 0.8 * 100) / 100, rowCurrency);
              if (rowCurrency === currency && rowCurrency !== 'EUR') {
                row.querySelector('.price-source').classList.add('custom');
                row.querySelector('.price-source').innerHTML = 'Set by you <button type="button" data-reset="' + rowCurrency + '">Reset</button>';
              }
            });
            renderPreview();
          });
        });
      }

      document.getElementById('priceRows').addEventListener('click', function (event) {
        var reset = event.target.closest('[data-reset]');
        if (!reset) return;
        delete plans[activePlan].prices[reset.dataset.reset];
        renderPrices();
        renderPreview();
        state.textContent = 'Unsaved changes';
      });

      function renderFeatures() {
        var plan = plans[activePlan];
        document.getElementById('featureList').innerHTML = features.map(function (feature, index) {
          var checked = plan.features.indexOf(feature) !== -1;
          return '<label class="plan-feature"><input type="checkbox" data-feature="' + index + '"' + (checked ? ' checked' : '') + '>' + escapeHtml(feature) + '</label>';
        }).join('');
        document.getElementById('featureList').querySelectorAll('[data-feature]').forEach(function (input) {
          input.addEventListener('change', function () {
            var feature = features[Number(input.dataset.feature)];
            var list = plan.features;
            if (input.checked && list.indexOf(feature) === -1) list.push(feature);
            if (!input.checked) plan.features = list.filter(function (item) { return item !== feature; });
            state.textContent = 'Unsaved changes';
            renderPreview();
          });
        });
        document.getElementById('previewFeatures').innerHTML = plan.features.map(function (feature) {
          return '<span>' + escapeHtml(feature) + '</span>';
        }).join('');
      }

      function setField(id, value) {
        var field = document.getElementById(id);
        if (field) field.value = value == null ? '' : value;
      }

      function loadPlan() {
        var plan = plans[activePlan];
        ['status', 'audience', 'visibility', 'bookingFee', 'layout'].forEach(function (key) {
          selectValue(key, plan[key] || ({ status: 'On', audience: 'Artist', visibility: 'Public', bookingFee: '0', layout: 'card' }[key]));
        });
        selectValue('trialFallback', plan.trialFallback || 'Previous plan');
        ['name', 'badge', 'commission', 'includedArtists', 'extraArtist', 'trialDays', 'cutoffDate', 'afterCutoff', 'tagline', 'priceNote', 'listTitle', 'buttonLabel', 'buttonNote', 'termsIntro', 'termsList', 'termsNote', 'trialButton'].forEach(function (key) {
          setField(({ name: 'planName', badge: 'planBadge', commission: 'commission', includedArtists: 'includedArtists', extraArtist: 'extraArtist', trialDays: 'trialDays', cutoffDate: 'cutoffDate', afterCutoff: 'afterCutoff', tagline: 'tagline', priceNote: 'priceNote', listTitle: 'listTitle', buttonLabel: 'buttonLabel', buttonNote: 'buttonNote', termsIntro: 'termsIntro', termsList: 'termsList', termsNote: 'termsNote', trialButton: 'trialButton' })[key], plan[key]);
        });
        renderIncludedItems((plan.includedItems || '').split('\n').filter(Boolean));
        document.getElementById('studioSection').hidden = plan.audience !== 'Studio';
        document.getElementById('cardCopy').hidden = plan.layout === 'terms';
        document.getElementById('termsCopy').hidden = plan.layout !== 'terms';
        document.getElementById('trialFallbackField').hidden = !(Number(plan.trialDays) > 0);
        document.getElementById('statusHelp').textContent = plan.status === 'On' ? 'Live. People who can see it can choose it.' : (plan.status === 'Off' ? 'No one new can choose it. The ' + plan.count + ' accounts on it keep it.' : '');
        renderTabs();
        renderPrices();
        renderFeatures();
        renderPreview();
      }

      function renderPreview() {
        var plan = plans[activePlan];
        var currency = activeCurrency;
        var price = amountFor(plan, currency);
        var extraArtistPrice = currency === 'EUR'
          ? Number(plan.extraArtist) || 0
          : Math.round((Number(plan.extraArtist) || 0) * (rates[currency] || 1));
        var fees = '<b>' + escapeHtml(plan.commission) + '% commission</b> per booking' +
          (plan.audience === 'Studio' ? ' for each artist' : '') +
          '. You pay Stripe’s card fees. ' +
          (plan.bookingFee === '1' ? 'A booking fee is added for your clients.' : 'No booking fee for clients.');
        var amount = Number(plan.trialDays) > 0 ? money(0, currency) : money(price, currency);
        var period = Number(plan.trialDays) > 0 ? 'for ' + escapeHtml(plan.trialDays) + ' days' : '/month';
        var priceNote = Number(plan.trialDays) > 0
          ? (plan.priceNote ? escapeHtml(plan.priceNote) + ' ' : '') +
            'Then <b>' + money(price, currency) + '/month</b>' +
            (plan.audience === 'Studio' ? ' for ' + escapeHtml(plan.includedArtists) + ' artists, <b>' + money(extraArtistPrice, currency) + ' per extra artist</b>.' : '.')
          : escapeHtml(plan.priceNote || '');
        var card = '';

        if (plan.layout === 'terms') {
          var terms = (plan.termsList || '').split('\n').filter(Boolean).map(function (item) {
            var parts = item.split('|');
            return '<div class="tr1"><span>' + escapeHtml(parts[0].trim()) + '</span><b>' + escapeHtml((parts[1] || '').trim()) + '</b></div>';
          }).join('');
          card = '<div class="plan pro"><div class="yl">YOUR PLAN</div><div class="name">' + escapeHtml(plan.name) + '</div>' +
            (plan.badge ? '<div class="tagline">' + escapeHtml(plan.badge) + '</div>' : '') +
            '<div style="margin:14px 0 4px;font-size:13px;color:#4b4452">' + escapeHtml(plan.termsIntro || '') + '</div>' +
            terms + '<div class="b ghost" style="margin-top:14px">Current plan</div>' +
            (plan.trialButton ? '<div class="b" style="margin-top:10px">' + escapeHtml(plan.trialButton) + '</div>' : '') +
            '<p class="fine" style="text-align:left">' + escapeHtml(plan.termsNote || '') + '</p></div>';
        } else {
          var items = (plan.includedItems || '').split('\n').filter(Boolean).map(function (item) {
            return '<li><span class="tick" aria-hidden="true"></span>' + escapeHtml(item) + '</li>';
          }).join('');
          var buttonText = Number(plan.trialDays) > 0
            ? 'Start ' + plan.trialDays + '-day free trial'
            : (plan.buttonLabel || 'Choose plan');
          var buttonClass = Number(plan.trialDays) > 0 ? 'b trial' : 'b';
          card = '<div class="plan' + (plan.badge ? ' pro' : '') + '">' +
            (plan.badge ? '<span class="badge">' + escapeHtml(plan.badge) + '</span>' : '') +
            '<div class="name">' + escapeHtml(plan.name) + '</div>' +
            '<div class="tagline">' + escapeHtml(plan.tagline || '') + '</div>' +
            '<div class="price"><span class="amount">' + amount + '</span><span class="per">' + period + '</span></div>' +
            '<div class="pn">' + priceNote + '</div>' +
            '<div class="fees"><span class="k">Fees</span>' + fees + '</div>' +
            '<div class="lt">' + escapeHtml(plan.listTitle || 'Includes') + '</div>' +
            '<ul>' + items + '</ul><div class="' + buttonClass + '">' + escapeHtml(buttonText) + '</div>' +
            (plan.buttonNote ? '<p class="fine">' + escapeHtml(plan.buttonNote) + '</p>' : '') + '</div>';
        }
        document.getElementById('previewCard').innerHTML = card;

        var comparison = '<div class="cmp"><div class="g">Fees</div>' +
          '<div class="r"><span>Monthly price</span><b>' + escapeHtml(Number(plan.trialDays) > 0 ? money(price, currency) + ' after ' + plan.trialDays + ' days' : money(price, currency)) + '</b></div>' +
          '<div class="r"><span>Client booking fee</span><b>' + (plan.bookingFee === '1' ? 'Yes' : 'None') + '</b></div>' +
          '<div class="r"><span>Commission</span><b>' + escapeHtml(plan.commission) + '%</b></div><div class="g">Features</div>';
        features.forEach(function (feature) {
          if (plan.features.indexOf(feature) !== -1) {
            comparison += '<div class="r"><span>' + escapeHtml(feature) + '</span><span class="tick">✓</span></div>';
          }
        });
        document.getElementById('previewFeatures').innerHTML = comparison + '</div>';
        document.getElementById('previewCard').style.display = previewMode === 'card' ? '' : 'none';
        document.getElementById('previewFeatures').style.display = previewMode === 'features' ? '' : 'none';
        var banner = document.getElementById('previewBanner');
        banner.textContent = plan.status === 'Off' ? 'No one new can choose this plan.' : '';
        banner.style.display = plan.status === 'Off' ? 'block' : 'none';
        document.querySelectorAll('[name="preview"]').forEach(function (input) {
          input.checked = input.value === previewMode;
        });
        document.querySelectorAll('[name="previewCurrency"]').forEach(function (input) {
          input.checked = input.value === activeCurrency;
        });
      }

      function planPayload(plan) {
        var terms = (plan.termsList || '').split('\n').map(function (line) {
          var parts = line.split('|');
          return { label: (parts.shift() || '').trim(), value: parts.join('|').trim() };
        }).filter(function (term) {
          return term.label !== '';
        });
        var prices = { EUR: amountFor(plan, 'EUR') };
        currencies.forEach(function (currency) {
          if (currency !== 'EUR' && plan.prices[currency] !== undefined && plan.prices[currency] !== '') {
            prices[currency] = amountFor(plan, currency);
          }
        });

        return {
          name: plan.name,
          status: plan.status,
          audience: plan.audience,
          visibility: plan.visibility,
          badge: plan.badge || null,
          prices: prices,
          commission: Number(plan.commission) || 0,
          bookingFee: plan.bookingFee || '0',
          includedArtists: plan.includedArtists === '' || plan.includedArtists == null ? null : Number(plan.includedArtists),
          extraArtist: plan.extraArtist === '' || plan.extraArtist == null ? null : Number(plan.extraArtist),
          trialDays: Number(plan.trialDays) || 0,
          trialFallback: plan.trialFallback || null,
          cutoffDate: plan.cutoffDate || null,
          afterCutoff: plan.afterCutoff || null,
          layout: plan.layout,
          tagline: plan.tagline || null,
          priceNote: plan.priceNote || null,
          listTitle: plan.listTitle || null,
          includedItems: (plan.includedItems || '').split('\n').map(function (item) { return item.trim(); }).filter(Boolean),
          buttonLabel: plan.buttonLabel || null,
          buttonNote: plan.buttonNote || null,
          termsIntro: plan.termsIntro || null,
          terms: terms,
          termsNote: plan.termsNote || null,
          trialButton: plan.trialButton || null,
          features: plan.features
        };
      }

      function savePlan() {
        readForm();
        var plan = plans[activePlan];
        var isNew = !plan.id;
        var button = document.getElementById('saveDesign');
        button.disabled = true;
        state.textContent = 'Saving…';

        fetch(isNew ? storeUrl : updateUrl.replace('__PLAN__', plan.id), {
          method: isNew ? 'POST' : 'PUT',
          headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
          },
          body: JSON.stringify(planPayload(plan))
        }).then(function (response) {
          return response.json().then(function (body) {
            if (!response.ok) {
              var validationMessage = body.errors ? Object.keys(body.errors).map(function (key) {
                return body.errors[key][0];
              })[0] : null;
              throw new Error(validationMessage || body.message || 'The plan could not be saved.');
            }
            return body;
          });
        }).then(function (body) {
          if (!body.plan || !body.plan.id) throw new Error('The server did not return the saved plan.');
          var oldKey = activePlan;
          var savedKey = String(body.plan.id);
          if (isNew) {
            planOrder.push(savedKey);
            delete plans[oldKey];
            activePlan = savedKey;
          }
          plans[activePlan] = body.plan;
          loadPlan();
          state.textContent = 'Saved';
        }).catch(function (error) {
          state.textContent = 'Save failed: ' + error.message;
        }).finally(function () {
          button.disabled = false;
        });
      }

      function markChanged() {
        readForm();
        state.textContent = 'Unsaved changes';
        document.getElementById('studioSection').hidden = plans[activePlan].audience !== 'Studio';
        document.getElementById('cardCopy').hidden = plans[activePlan].layout === 'terms';
        document.getElementById('termsCopy').hidden = plans[activePlan].layout !== 'terms';
        document.getElementById('trialFallbackField').hidden = !(Number(plans[activePlan].trialDays) > 0);
        document.getElementById('statusHelp').textContent = plans[activePlan].status === 'On' ? 'Live. People who can see it can choose it.' : (plans[activePlan].status === 'Off' ? 'No one new can choose it. The ' + plans[activePlan].count + ' accounts on it keep it.' : '');
        renderTabs();
        renderPreview();
      }

      form.addEventListener('input', markChanged);
      form.addEventListener('change', markChanged);
      form.addEventListener('submit', function (event) { event.preventDefault(); });
      tabs.addEventListener('click', function (event) {
        var tab = event.target.closest('[data-plan]');
        if (event.target.closest('#addPlan')) {
          readForm();
          if (!plans[activePlan].id) {
            state.textContent = 'Save this plan before creating another.';
            return;
          }
          var key = 'new-' + Date.now();
          plans[key] = { name: 'New plan', audience: 'Artist', status: 'Draft', visibility: 'Hidden', badge: 'RECOMMENDED', prices: { EUR: 0 }, commission: 6, bookingFee: '0', trialDays: 0, trialFallback: 'Previous plan', cutoffDate: '', afterCutoff: 'Standard', layout: 'card', tagline: '', priceNote: '', listTitle: 'Includes', includedItems: '', buttonLabel: 'Choose plan', buttonNote: '', features: [], count: 0 };
          activePlan = key;
          loadPlan();
          state.textContent = 'Unsaved changes';
          return;
        }
        if (!tab) return;
        readForm();
        activePlan = tab.dataset.plan;
        loadPlan();
      });
      document.getElementById('previewCurrencies').innerHTML = currencies.map(function (currency, index) {
        return '<label><input type="radio" name="previewCurrency" value="' + currency + '"' + (index === 0 ? ' checked' : '') + '><span>' + currency + '</span></label>';
      }).join('');
      document.getElementById('previewCurrencies').addEventListener('change', function (event) {
        if (event.target.name === 'previewCurrency') {
          activeCurrency = event.target.value;
          renderPrices();
          renderPreview();
        }
      });
      document.querySelector('.preview-toggle').addEventListener('change', function (event) {
        if (event.target.name === 'preview') {
          previewMode = event.target.value;
          renderPreview();
        }
      });
      document.getElementById('addFeature').addEventListener('click', function () {
        var input = document.getElementById('newFeature');
        var label = input.value.trim();
        if (!label) return;
        if (features.indexOf(label) !== -1) {
          state.textContent = 'That feature already exists.';
          return;
        }
        var featureButton = document.getElementById('addFeature');
        featureButton.disabled = true;
        fetch(featureUrl, {
          method: 'POST',
          headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
          },
          body: JSON.stringify({ name: label })
        }).then(function (response) {
          return response.json().then(function (body) {
            if (!response.ok) {
              var validationMessage = body.errors ? Object.keys(body.errors).map(function (key) {
                return body.errors[key][0];
              })[0] : null;
              throw new Error(validationMessage || body.message || 'The feature could not be added.');
            }
            return body;
          });
        }).then(function (body) {
          if (!body.feature) throw new Error('The server did not return the added feature.');
          features.push(body.feature);
          var plan = plans[activePlan];
          if (plan.features.indexOf(body.feature) === -1) plan.features.push(body.feature);
          input.value = '';
          renderFeatures();
          renderPreview();
          state.textContent = 'Feature added. Save the plan to persist its selection.';
        }).catch(function (error) {
          state.textContent = 'Feature error: ' + error.message;
        }).finally(function () {
          featureButton.disabled = false;
        });
      });
      document.getElementById('saveDesign').addEventListener('click', function () {
        savePlan();
      });
      loadPlan();
    })();
  </script>
@endsection
