<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Book Your Tattoo | Inkjin</title>
  <meta name="description" content="Book and pay for your tattoo design — select a date, enter your details, and secure your appointment.">
  <link rel="icon" href="images/favicon.png">
  <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
  <link href="css/bookpay.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/Dropify/0.2.2/css/dropify.min.css" rel="stylesheet">
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            "primary": "#310f7a",
            "primary-container": "#482d91",
            "on-primary": "#ffffff",
            "on-primary-container": "#b69fff",
            "surface": "#fdf7ff",
            "surface-container": "#f2ecf5",
            "surface-container-high": "#ece6ef",
            "surface-container-highest": "#e6e0ea",
            "surface-container-low": "#f8f1fb",
            "on-surface": "#1c1b21",
            "on-surface-variant": "#494552",
            "outline": "#7a7583",
            "outline-variant": "#cac4d3",
            "secondary": "#625881",
            "secondary-container": "#ddd0ff",
            "inverse-surface": "#322f36",
            "inverse-on-surface": "#f5eff8",
            "error": "#ba1a1a",
          },
          fontFamily: {
            "sans": ["Plus Jakarta Sans", "system-ui", "sans-serif"],
          },
        },
      },
    }
  </script>
  <style>
    body { font-family: 'Plus Jakarta Sans', sans-serif; }
    .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
    .material-symbols-outlined.filled { font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
    .tf-progress { position: fixed; top: 0; left: 0; height: 3px; background: #310f7a; transition: width 0.4s ease; z-index: 100; }
    .step-panel { display: none; }
    .step-panel.active { display: block; animation: fadeUp 0.35s ease-out; }
    .step-panel.active.reverse { animation: fadeDown 0.35s ease-out; }
    @keyframes fadeUp { from { opacity: 0; transform: translateY(24px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes fadeDown { from { opacity: 0; transform: translateY(-24px); } to { opacity: 1; transform: translateY(0); } }
    .question-div { display: none; min-height: 60vh; align-items: center; justify-content: center; padding: 2rem 0; }
    .question-div.active { display: flex; animation: tfSlideIn 0.4s ease-out; }
    .question-div.active.reverse { animation: tfSlideInReverse 0.4s ease-out; }
    @keyframes tfSlideIn { from { opacity: 0; transform: translateY(40px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes tfSlideInReverse { from { opacity: 0; transform: translateY(-40px); } to { opacity: 1; transform: translateY(0); } }
    .cal-card { background: white; border-radius: 1rem; border: 1px solid #e6e0ea; overflow: hidden; box-shadow: 0 4px 24px rgba(49,15,122,0.06); }
    .cal-day { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.875rem; cursor: pointer; transition: all 0.15s; }
    .cal-day.available:hover { background: #ece6ef; }
    .cal-day.available { color: #1c1b21; font-weight: 600; }
    .cal-day.unavailable { color: #cac4d3; cursor: default; pointer-events: none; }
    .cal-day.unavailable-future {
      color: #ba1a1a;
      background: #fff1f1;
      text-decoration: line-through;
      text-decoration-thickness: 2px;
      text-decoration-color: #ba1a1a;
      cursor: default;
      pointer-events: none;
      font-weight: 600;
    }
    .cal-day.blocked-by-artist {
      color: #5c4033;
      background: #f4ebe4;
      cursor: default;
      pointer-events: none;
      font-weight: 600;
      font-size: 0.72rem;
    }
    .cal-day.fully-booked-day {
      color: #494552;
      background: #ece8f0;
      cursor: default;
      pointer-events: none;
      font-weight: 600;
      font-size: 0.72rem;
    }
    .cal-day.selected { background: #310f7a; color: white; font-weight: 700; }
    .cal-day.today { border: 2px solid #310f7a; }
    .cal-day.empty { pointer-events: none; }
    .time-slot-card { padding: 0.75rem 1.25rem; border-radius: 0.75rem; border: 1.5px solid #cac4d3; font-size: 0.9rem; font-weight: 600; cursor: pointer; transition: all 0.15s; text-align: center; color: #310f7a; background: white; }
    .time-slot-card:hover { border-color: #310f7a; background: #f8f1fb; }
    .time-slot-card.selected { background: #310f7a; color: white; border-color: #310f7a; }
    .time-slot-card.booked { background: #f2ecf5; color: #cac4d3; cursor: default; border-color: transparent; pointer-events: none; }
    .time-slot-wrap { display: flex; gap: 0.5rem; align-items: stretch; }
    .time-slot-confirm { display: none; padding: 0.75rem 1.25rem; border-radius: 0.75rem; background: #310f7a; color: white; font-weight: 700; font-size: 0.85rem; cursor: pointer; transition: all 0.15s; white-space: nowrap; }
    .time-slot-wrap.selected .time-slot-confirm { display: flex; align-items: center; gap: 0.35rem; animation: tfSlideIn 0.2s ease-out; }
    .single-choice-radio-button { padding: 0.75rem 1.5rem; border-radius: 9999px; border: 2px solid #cac4d3; font-size: 0.95rem; font-weight: 600; color: #494552; cursor: pointer; transition: all 0.15s; background: white; }
    .single-choice-radio-button:hover { border-color: #310f7a; color: #310f7a; }
    .single-choice-radio-button.selected { background: #310f7a; color: white; border-color: #310f7a; }
    .pref-block { background: white; border: 1px solid #e6e0ea; border-radius: 1rem; padding: 1.25rem; }
    .day-pill { padding: 0.5rem 1rem; border-radius: 9999px; border: 1.5px solid #cac4d3; font-size: 0.8rem; font-weight: 600; cursor: pointer; transition: all 0.15s; background: white; }
    .day-pill:hover { border-color: #310f7a; color: #310f7a; }
    .day-pill.selected { background: #310f7a; color: white; border-color: #310f7a; }
    .time-pref-pill { padding: 0.5rem 1rem; border-radius: 9999px; border: 1.5px solid #cac4d3; font-size: 0.8rem; font-weight: 600; cursor: pointer; transition: all 0.15s; background: white; }
    .time-pref-pill:hover { border-color: #310f7a; color: #310f7a; }
    .time-pref-pill.selected { background: #310f7a; color: white; border-color: #310f7a; }
    .social-btn { display: flex; align-items: center; justify-content: center; gap: 0.75rem; padding: 0.875rem 1.5rem; border-radius: 0.75rem; font-weight: 600; font-size: 0.95rem; border: 2px solid #e6e0ea; cursor: pointer; transition: all 0.15s; background: white; width: 100%; }
    .social-btn:hover { border-color: #cac4d3; background: #f8f1fb; }
    .auth-toggle { color: #310f7a; cursor: pointer; font-weight: 600; text-decoration: underline; }
    .mode-toggle { display: inline-flex; background: #f2ecf5; border-radius: 9999px; padding: 3px; gap: 2px; }
    .mode-toggle-btn { padding: 6px 16px; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; cursor: pointer; transition: all 0.15s; color: #494552; }
    .mode-toggle-btn.active { background: #310f7a; color: white; }
    @keyframes checkDraw { from { stroke-dashoffset: 48; } to { stroke-dashoffset: 0; } }
    @keyframes circleDraw { from { stroke-dashoffset: 200; } to { stroke-dashoffset: 0; } }
    @keyframes scaleBounce { 0% { transform: scale(0.5); opacity: 0; } 60% { transform: scale(1.15); } 100% { transform: scale(1); opacity: 1; } }
    .check-circle { animation: circleDraw 0.6s ease-out forwards, scaleBounce 0.5s ease-out; stroke-dasharray: 200; }
    .check-mark { animation: checkDraw 0.4s ease-out 0.4s forwards; stroke-dasharray: 48; stroke-dashoffset: 48; }
    @keyframes spin { to { transform: rotate(360deg); } }
    .spinner { width: 32px; height: 32px; border: 3px solid #ece6ef; border-top-color: #310f7a; border-radius: 50%; animation: spin 0.8s linear infinite; }
    @keyframes shake { 0%,100%{transform:translateX(0)} 20%,60%{transform:translateX(-6px)} 40%,80%{transform:translateX(6px)} }
    @keyframes slideInRight { from { opacity: 0; transform: translateX(20px); } to { opacity: 1; transform: translateX(0); } }
    .slide-in-right { animation: slideInRight 0.3s ease-out; }
    .progress-step .step-dot { width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; font-weight: 700; border: 2px solid #cac4d3; color: #cac4d3; transition: all 0.3s; }
    .progress-step.active .step-dot, .progress-step.completed .step-dot { border-color: #310f7a; background: #310f7a; color: white; }
    .progress-step .step-label { font-size: 0.7rem; color: #7a7583; margin-top: 4px; transition: color 0.3s; white-space: nowrap; }
    .progress-step.active .step-label { color: #310f7a; font-weight: 600; }
    .progress-step.completed .step-label { color: #310f7a; }
    .progress-line { height: 2px; background: #cac4d3; flex: 1; margin: 0 4px; margin-top: -12px; transition: background 0.3s; min-width: 12px; }
    .progress-line.completed { background: #310f7a; }
    .info-tooltip { position: relative; display: inline-flex; cursor: help; }
    .info-tooltip .tooltip-text { visibility: hidden; opacity: 0; position: absolute; bottom: calc(100% + 8px); left: 50%; transform: translateX(-50%); background: #322f36; color: white; padding: 8px 12px; border-radius: 8px; font-size: 0.75rem; width: 220px; text-align: center; transition: opacity 0.2s; z-index: 10; line-height: 1.4; }
    .info-tooltip:hover .tooltip-text { visibility: visible; opacity: 1; }
    .card-type-icon { width: 32px; height: 20px; border-radius: 3px; background: #f2ecf5; display: inline-flex; align-items: center; justify-content: center; font-size: 0.6rem; font-weight: 700; color: #494552; }
    .card-type-icon.active { background: #310f7a; color: white; }
    .consult-type-card { padding: 1.25rem; border-radius: 1rem; border: 2px solid #cac4d3; cursor: pointer; transition: all 0.2s; background: white; text-align: left; }
    .consult-type-card:hover { border-color: #310f7a; background: #f8f1fb; }
    .consult-type-card.selected { border-color: #310f7a; background: #f8f1fb; box-shadow: 0 0 0 1px #310f7a; }
    .consult-type-card .ct-icon { width: 44px; height: 44px; border-radius: 12px; background: #f2ecf5; display: flex; align-items: center; justify-content: center; transition: all 0.2s; }
    .consult-type-card.selected .ct-icon { background: #310f7a; }
    .consult-type-card .ct-icon .material-symbols-outlined { color: #310f7a; font-size: 22px; }
    .consult-type-card.selected .ct-icon .material-symbols-outlined { color: white; }
    .confirm-chip { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; border-radius: 9999px; font-size: 0.85rem; font-weight: 600; background: #310f7a; color: white; }
    .section-disabled { opacity: 0.4; pointer-events: none; filter: grayscale(0.3); }
    .question-kicker {
      display: inline-flex;
      align-items: center;
      gap: 0.4rem;
      padding: 0.35rem 0.85rem;
      border-radius: 9999px;
      border: 1px solid #ddd0ff;
      background: linear-gradient(135deg, #f8f1fb 0%, #f2ecf5 100%);
      color: #310f7a;
      font-size: 0.78rem;
      font-weight: 700;
      letter-spacing: 0.02em;
      margin-bottom: 0.75rem;
      box-shadow: 0 2px 8px rgba(49, 15, 122, 0.08);
    }
    .question-kicker .dot {
      width: 0.45rem;
      height: 0.45rem;
      border-radius: 9999px;
      background: #310f7a;
      opacity: 0.9;
    }
    .select2-container--default .select2-selection--single {
      height: 58px;
      border: 1px solid rgba(122, 117, 131, 0.35);
      border-radius: 1rem;
      background: #ffffff;
      display: flex;
      align-items: center;
      box-shadow: 0 1px 4px rgba(49, 15, 122, 0.04);
      transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }
    .select2-container--default.select2-container--focus .select2-selection--single {
      border-color: rgba(49, 15, 122, 0.55);
      box-shadow: 0 0 0 3px rgba(49, 15, 122, 0.14);
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
      color: #1c1b21;
      line-height: 58px;
      font-size: 1rem;
      padding-left: 1rem;
      padding-right: 2.2rem;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
      height: 56px;
      right: 10px;
    }
    .select2-dropdown {
      border: 1px solid #ddd0ff;
      border-radius: 0.9rem;
      box-shadow: 0 12px 28px rgba(49, 15, 122, 0.12);
      overflow: hidden;
    }
    .select2-container--default .select2-results__option {
      padding: 0.7rem 0.9rem;
      font-size: 0.95rem;
    }
    .select2-container--default .select2-results__option--highlighted.select2-results__option--selectable {
      background: #310f7a;
      color: #fff;
    }
    .dropify-wrapper .dropify-message p {
      font-size: 0.78rem;
      line-height: 1.2rem;
    }
    .dropify-wrapper .dropify-message span.file-icon {
      font-size: 36px;
    }
    .dropify-wrapper .dropify-message {
      text-align: center;
    }
    .dropify-wrapper .dropify-preview .dropify-render {
      text-align: center;
    }
    .dropify-wrapper .dropify-preview .dropify-render img {
      margin-left: auto;
      margin-right: auto;
      display: inline-block;
      float: none;
    }
    .q-toggle-row {
      display: flex;
      align-items: flex-start;
      gap: 0.85rem;
      padding: 1rem;
      border: 1px solid rgba(122, 117, 131, 0.32);
      border-radius: 0.9rem;
      background: #ffffff;
    }
    .q-toggle-control {
      position: relative;
      display: inline-flex;
      width: 54px;
      min-width: 54px;
      height: 31px;
      margin-top: 1px;
      flex-shrink: 0;
    }
    .q-toggle-label {
      font-size: 0.95rem;
      color: #1c1b21;
      line-height: 1.45;
      font-weight: 500;
      flex: 1;
      min-width: 0;
    }
    .q-toggle-input {
      position: absolute;
      opacity: 0;
      width: 0;
      height: 0;
      pointer-events: none;
    }
    .q-toggle-ui {
      position: relative;
      display: inline-block;
      width: 54px;
      height: 31px;
      border-radius: 9999px;
      background: #a8c7ff;
      transition: all 0.2s ease;
      flex-shrink: 0;
      box-shadow: inset 0 0 0 1px rgba(0, 0, 0, 0.05);
      cursor: pointer;
    }
    .q-toggle-ui::after {
      content: "";
      position: absolute;
      top: 3px;
      left: 3px;
      width: 25px;
      height: 25px;
      border-radius: 50%;
      background: #ffffff;
      box-shadow: 0 2px 7px rgba(0, 0, 0, 0.2);
      transition: transform 0.2s ease;
    }
    .q-toggle-input:checked + .q-toggle-ui {
      background: linear-gradient(90deg, #1e6bff 0%, #3f86ff 100%);
    }
    .q-toggle-input:checked + .q-toggle-ui::after {
      transform: translateX(23px);
    }
    .q-toggle-input:focus-visible + .q-toggle-ui {
      box-shadow: 0 0 0 3px rgba(30, 107, 255, 0.22);
    }
  </style>
</head>
<body class="bg-surface text-on-surface min-h-screen">
  <div class="tf-progress" id="topProgressBar" style="width: 0%"></div>

  <!-- HEADER -->
  <header class="border-b border-outline-variant/20 bg-white/70 backdrop-blur-md sticky top-0 z-50">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between">
      <a href="{{route('public.artist', ['username' => $userDetail->user_name])}}" class="flex items-center gap-2 text-primary font-extrabold text-xl tracking-tight">
        <img src="{{ asset('design/images/inkjin_logo-p-500.png') }}" alt="inkjin" class="h-7">
      </a>
      <div class="flex items-center gap-3 flex-wrap justify-end">
        <a href="{{route('public.artist', ['username' => $userDetail->user_name])}}" class="flex items-center gap-1 text-sm text-on-surface-variant hover:text-primary transition-colors">
          <span class="material-symbols-outlined text-[18px]">arrow_back</span> Back to artist
        </a>
      </div>
    </div>
  </header>

  <!-- BOOKINGS CLOSED OVERLAY (hidden by default) -->
  <div id="bookingsClosedOverlay" class="hidden">
    <div class="min-h-screen flex items-center justify-center p-6">
      <div class="text-center max-w-md">
        <span class="material-symbols-outlined text-6xl text-on-surface-variant mb-4">event_busy</span>
        <h2 class="text-2xl font-bold text-on-surface mb-2">Bookings Are Closed</h2>
        <p class="text-on-surface-variant mb-6">This artist is currently not accepting new bookings. Check back soon or browse their portfolio.</p>
        <a href="artist-page.html" class="inline-flex items-center gap-2 px-6 py-3 bg-primary text-on-primary rounded-full font-semibold text-sm hover:bg-primary-container transition-colors">
          <span class="material-symbols-outlined text-lg">arrow_back</span> Back to Artist Page
        </a>
      </div>
    </div>
  </div>

  <main class="max-w-4xl mx-auto px-4 sm:px-6 py-8" id="bookingMainContent">

    <!-- STEP DOTS -->
    <div class="flex items-start justify-center mb-10" id="progressDots">
      <div class="progress-step active text-center" data-step="1"><div class="step-dot mx-auto">1</div><div class="step-label">Questions</div></div>
      <div class="progress-line mt-4" data-line="1"></div>
      <div class="progress-step text-center" data-step="2"><div class="step-dot mx-auto">2</div><div class="step-label" id="step2Label">Schedule</div></div>
      <div class="progress-line mt-4" data-line="2"></div>
      <div class="progress-step text-center" data-step="3"><div class="step-dot mx-auto">3</div><div class="step-label">Register</div></div>
      <div class="progress-line mt-4" data-line="3"></div>
      <div class="progress-step text-center" data-step="4"><div class="step-dot mx-auto">4</div><div class="step-label" id="step4Label">Payment</div></div>
      <div class="progress-line mt-4" data-line="4"></div>
      <div class="progress-step text-center" data-step="5"><div class="step-dot mx-auto">5</div><div class="step-label" id="step5Label">Confirmed</div></div>
    </div>

      <div class="bg-white rounded-2xl border border-outline-variant/20 p-4 sm:p-5 mb-6 flex flex-col sm:flex-row sm:items-center gap-4 sm:gap-5">
        <div class="w-full sm:w-24 h-24 sm:h-24 rounded-xl bg-surface-container flex items-center justify-center flex-shrink-0">
          <img src="{{asset($tattoo->image)}}" alt="{{ $tattoo->title }}" class="w-full h-full object-cover">
        </div>
        <div class="flex-1 min-w-0">
          <h2 class="text-base sm:text-lg font-bold text-on-surface mb-1 break-words cc-designTitle">{{ $tattoo->title }}</h2>
          <div class="flex flex-wrap gap-x-3 sm:gap-x-4 gap-y-1 text-xs sm:text-sm text-on-surface-variant">
            <span class="flex items-center gap-1"><span class="material-symbols-outlined text-[16px]">brush</span> <span class="cc-designStyle">{{ ucwords(str_replace('-', ' ', $tattoo->primary_style)) }}</span></span>
            <span class="flex items-center gap-1"> <span class="cc-designPrice">€{{ $tattoo->min_price }} — €{{ $tattoo->max_price }}</span></span>
            <span class="flex items-center gap-1"><span class="material-symbols-outlined text-[16px]">schedule</span> <span class="cc-designTime">{{ $tattoo->session_duration }} hours</span></span>
        </div>
          <div class="flex items-start sm:items-center gap-2 mt-2 text-xs sm:text-sm text-on-surface-variant">
            <div class="w-6 h-6 rounded-full bg-gradient-to-br from-primary to-primary-container flex items-center justify-center flex-shrink-0"><span class="text-white text-[10px] font-bold cc-artistAvatar">{{ strtoupper($userDetail->user->first_name[0]) }}{{ strtoupper($userDetail->user->last_name[0]) }}</span></div>
            <span class="leading-relaxed break-words">with <strong class="cc-artistName">{{ $userDetail->user->first_name }} {{ $userDetail->user->last_name }}</strong> at <strong class="cc-studioName">{{ $userDetail->studio_name }}</strong></span>
      </div>
          </div>
        </div>

    <!-- ══════════════════════════════════ -->
    <!-- STEP 1: QUESTIONS (Typeform-style) -->
    <!-- ══════════════════════════════════ -->
    <div class="step-panel active" id="stepQuestions">
      <div id="questionsMount"></div>
    </div>

    <!-- ══════════════════════════════════════ -->
    <!-- STEP 2A: CALENDAR (no consultation)   -->
    <!-- ══════════════════════════════════════ -->
    <div class="step-panel" id="step2Calendar">
      <button class="js-back-to-questions flex items-center gap-1 text-sm text-on-surface-variant hover:text-primary mb-4 transition-colors" onclick="goToStep(1, true)"><span class="material-symbols-outlined text-[18px]">arrow_back</span> Back to Questions</button>
      <div class="cal-card">
        <div class="flex flex-col md:flex-row">
          <div class="flex-1 p-6 border-b md:border-b-0 md:border-r border-outline-variant/20">
            <div class="flex items-center justify-between mb-5">
              <button id="calPrev" class="p-1.5 rounded-lg hover:bg-surface-container transition-colors"><span class="material-symbols-outlined text-on-surface-variant">chevron_left</span></button>
              <span class="font-bold text-base" id="calMonth"></span>
              <button id="calNext" class="p-1.5 rounded-lg hover:bg-surface-container transition-colors"><span class="material-symbols-outlined text-on-surface-variant">chevron_right</span></button>
            </div>
            <div class="grid grid-cols-7 gap-1 text-center mb-2"><div class="text-xs font-semibold text-on-surface-variant py-1">Mon</div><div class="text-xs font-semibold text-on-surface-variant py-1">Tue</div><div class="text-xs font-semibold text-on-surface-variant py-1">Wed</div><div class="text-xs font-semibold text-on-surface-variant py-1">Thu</div><div class="text-xs font-semibold text-on-surface-variant py-1">Fri</div><div class="text-xs font-semibold text-on-surface-variant py-1">Sat</div><div class="text-xs font-semibold text-on-surface-variant py-1">Sun</div></div>
            <div class="grid grid-cols-7 gap-1 justify-items-center" id="calGrid"></div>
          </div>
          <div class="md:w-[280px] p-6" id="timeSlotsPanel">
            <div id="timeSlotsEmpty" class="flex flex-col items-center justify-center h-full min-h-[200px] text-center"><span class="material-symbols-outlined text-outline-variant text-4xl mb-2">calendar_today</span><p class="text-sm text-on-surface-variant">Select a date to see<br>available times</p></div>
            <div id="timeSlotsContent" class="hidden slide-in-right">
              <h3 class="font-bold text-base mb-1" id="selectedDateLabel">—</h3>
              <p class="text-xs text-on-surface-variant mb-4">Choose a time slot</p>
              <div class="space-y-2 max-h-[320px] overflow-y-auto pr-1" id="timeSlots"></div>
              <p class="text-xs text-on-surface-variant mt-4 flex items-center gap-1"><span class="material-symbols-outlined text-[14px]">public</span> {{$userDetail->timezone}}</p>
            </div>
          </div>
        </div>
      </div>
      <div class="flex items-start gap-3 mt-6 p-4 bg-surface-container-low rounded-xl">
        <span class="material-symbols-outlined text-primary mt-0.5">location_on</span>
        <div>
          <p class="text-sm font-semibold text-on-surface">{{$userDetail->studio_name}}</p>
          <p class="text-xs text-on-surface-variant"> {{$userDetail->studio_address}} </p>
          <a href="{{$userDetail->google_maps_link}}" target="_blank" class="text-xs text-primary font-medium hover:underline mt-1 inline-block">Get Directions →</a>
        </div>
      </div>
      <div id="confirmBar" class="hidden mt-6 bg-white rounded-2xl border border-primary/20 p-4 flex flex-col sm:flex-row items-center justify-between gap-3 shadow-sm">
        <div class="flex items-center gap-3"><span class="material-symbols-outlined text-primary">event_available</span><span class="text-sm font-semibold" id="confirmBarText">—</span></div>
        <button id="btnContinue" onclick="goToStep(3)" class="px-6 py-2.5 bg-primary text-on-primary rounded-full font-bold text-sm hover:bg-primary-container transition-colors shadow-md shadow-primary/20 flex items-center gap-2">Continue <span class="material-symbols-outlined text-[18px]">arrow_forward</span></button>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════ -->
    <!-- STEP 2B: CALENDAR + CONSULTATION            -->
    <!-- ═══════════════════════════════════════════ -->
    <div class="step-panel" id="step2CalendarConsult">
      <button class="js-back-to-questions flex items-center gap-1 text-sm text-on-surface-variant hover:text-primary mb-4 transition-colors" onclick="goToStep(1, true)"><span class="material-symbols-outlined text-[18px]">arrow_back</span> Back to Questions</button>
      <!-- Consultation banner -->
      <div class="bg-gradient-to-r from-primary/5 to-secondary-container/30 rounded-2xl border border-primary/10 p-5 mb-6">
        <div class="flex items-start gap-3">
          <span class="material-symbols-outlined text-primary text-2xl mt-0.5">video_camera_front</span>
          <div>
            <h3 class="text-base font-bold text-on-surface mb-1"><span class="cc-artistName">Julian Ink</span> includes a free consultation before your tattoo session</h3>
            <p class="text-sm text-on-surface-variant">You'll have a 15-minute call to discuss your design, placement, and any questions.</p>
          </div>
        </div>
      </div>
      <!-- Consultation type selector -->
      <div class="mb-6" id="ccTypeSection">
        <h3 class="text-lg font-bold text-on-surface mb-1">How would you like to have your consultation?</h3>
        <p class="text-sm text-on-surface-variant mb-4">Choose the format that works best for you.</p>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3" id="ccConsultTypeCards">
          <div class="consult-type-card" data-type="video" onclick="selectConsultType(this,'video')">
            <div class="ct-icon mb-3"><span class="material-symbols-outlined">videocam</span></div>
            <h4 class="font-bold text-sm text-on-surface mb-0.5">📹 Video Call</h4>
            <p class="text-xs text-on-surface-variant">15-minute call on Inkjin</p>
            <p class="text-xs text-on-surface-variant mt-1">Convenient — join from anywhere</p>
          </div>
          <div class="consult-type-card" data-type="phone" onclick="selectConsultType(this,'phone')">
            <div class="ct-icon mb-3"><span class="material-symbols-outlined">call</span></div>
            <h4 class="font-bold text-sm text-on-surface mb-0.5">📞 Phone Call</h4>
            <p class="text-xs text-on-surface-variant">15-minute phone consultation</p>
            <p class="text-xs text-on-surface-variant mt-1">Quick and easy</p>
          </div>
          <div class="consult-type-card" data-type="studio" onclick="selectConsultType(this,'studio')">
            <div class="ct-icon mb-3"><span class="material-symbols-outlined">storefront</span></div>
            <h4 class="font-bold text-sm text-on-surface mb-0.5">🏠 In-Studio Visit</h4>
            <p class="text-xs text-on-surface-variant">Visit <span class="cc-studioName">Black Lotus Studio</span> in person</p>
            <p class="text-xs text-on-surface-variant mt-1">Meet your artist and see the space</p>
            <p class="text-xs text-primary font-medium mt-1 cc-studioAddress">Athens, Greece</p>
          </div>
        </div>
        <p id="ccConsultTypeError" class="hidden text-sm text-error mt-3">Please select a consultation type before continuing.</p>
      </div>
      <!-- Section 1: Schedule Consultation (hidden until type selected) -->
      <div id="ccConsultSection" class="mb-6 hidden">
        <div class="flex items-center gap-2 mb-1"><span class="text-lg" id="ccConsultEmoji">📹</span><h3 class="text-lg font-bold text-on-surface" id="ccConsultTitle">Schedule Your Consultation</h3></div>
        <p class="text-sm text-on-surface-variant mb-4" id="ccConsultSubtitle">15-minute video call on Inkjin</p>
        <div class="cal-card mb-4">
          <div class="flex flex-col md:flex-row">
            <div class="flex-1 p-6 border-b md:border-b-0 md:border-r border-outline-variant/20">
              <div class="flex items-center justify-between mb-5">
                <button class="p-1.5 rounded-lg hover:bg-surface-container transition-colors" onclick="ccCalNav(-1)"><span class="material-symbols-outlined text-on-surface-variant">chevron_left</span></button>
                <span class="font-bold text-base" id="ccCalMonth"></span>
                <button class="p-1.5 rounded-lg hover:bg-surface-container transition-colors" onclick="ccCalNav(1)"><span class="material-symbols-outlined text-on-surface-variant">chevron_right</span></button>
              </div>
              <div class="grid grid-cols-7 gap-1 text-center mb-2"><div class="text-xs font-semibold text-on-surface-variant py-1">Mon</div><div class="text-xs font-semibold text-on-surface-variant py-1">Tue</div><div class="text-xs font-semibold text-on-surface-variant py-1">Wed</div><div class="text-xs font-semibold text-on-surface-variant py-1">Thu</div><div class="text-xs font-semibold text-on-surface-variant py-1">Fri</div><div class="text-xs font-semibold text-on-surface-variant py-1">Sat</div><div class="text-xs font-semibold text-on-surface-variant py-1">Sun</div></div>
              <div class="grid grid-cols-7 gap-1 justify-items-center" id="ccCalGrid"></div>
            </div>
            <div class="md:w-[280px] p-6">
              <div id="ccTimeSlotsEmpty" class="flex flex-col items-center justify-center h-full min-h-[200px] text-center"><span class="material-symbols-outlined text-outline-variant text-4xl mb-2">calendar_today</span><p class="text-sm text-on-surface-variant">Select a date to see<br>available times</p></div>
              <div id="ccTimeSlotsContent" class="hidden slide-in-right">
                <h3 class="font-bold text-base mb-1" id="ccSelectedDateLabel">—</h3>
                <p class="text-xs text-on-surface-variant mb-4">Choose a time (15 min slots)</p>
                <div class="space-y-2 max-h-[300px] overflow-y-auto" id="ccTimeSlots"></div>
                <p class="text-xs text-on-surface-variant mt-4 flex items-center gap-1"><span class="material-symbols-outlined text-[14px]">public</span> Central European Time (CET)</p>
              </div>
            </div>
          </div>
        </div>
        <div id="ccConsultChip" class="hidden mb-2"><div class="confirm-chip" id="ccConsultChipText">📹 Consultation: —</div></div>
      </div>
      <!-- Section 2: Schedule Tattoo (disabled until consultation picked) -->
      <div id="ccTattooSection" class="mb-6 hidden">
        <div class="flex items-center gap-2 mb-1"><span class="text-lg">🎨</span><h3 class="text-lg font-bold text-on-surface">Schedule Your Tattoo Session</h3></div>
        <p class="text-sm text-on-surface-variant mb-4">Choose a date after your consultation</p>
        <div id="ccTattooCalWrap">
          <div class="cal-card mb-4">
            <div class="flex flex-col md:flex-row">
              <div class="flex-1 p-6 border-b md:border-b-0 md:border-r border-outline-variant/20">
                <div class="flex items-center justify-between mb-5">
                  <button class="p-1.5 rounded-lg hover:bg-surface-container transition-colors" onclick="ccTatCalNav(-1)"><span class="material-symbols-outlined text-on-surface-variant">chevron_left</span></button>
                  <span class="font-bold text-base" id="ccTatCalMonth"></span>
                  <button class="p-1.5 rounded-lg hover:bg-surface-container transition-colors" onclick="ccTatCalNav(1)"><span class="material-symbols-outlined text-on-surface-variant">chevron_right</span></button>
                </div>
                <div class="grid grid-cols-7 gap-1 text-center mb-2"><div class="text-xs font-semibold text-on-surface-variant py-1">Mon</div><div class="text-xs font-semibold text-on-surface-variant py-1">Tue</div><div class="text-xs font-semibold text-on-surface-variant py-1">Wed</div><div class="text-xs font-semibold text-on-surface-variant py-1">Thu</div><div class="text-xs font-semibold text-on-surface-variant py-1">Fri</div><div class="text-xs font-semibold text-on-surface-variant py-1">Sat</div><div class="text-xs font-semibold text-on-surface-variant py-1">Sun</div></div>
                <div class="grid grid-cols-7 gap-1 justify-items-center" id="ccTatCalGrid"></div>
              </div>
              <div class="md:w-[280px] p-6">
                <div id="ccTatTimeSlotsEmpty" class="flex flex-col items-center justify-center h-full min-h-[200px] text-center"><span class="material-symbols-outlined text-outline-variant text-4xl mb-2">calendar_today</span><p class="text-sm text-on-surface-variant">Select a date to see<br>available times</p></div>
                <div id="ccTatTimeSlotsContent" class="hidden slide-in-right">
                  <h3 class="font-bold text-base mb-1" id="ccTatSelectedDateLabel">—</h3>
                  <p class="text-xs text-on-surface-variant mb-4">Choose a time slot</p>
                  <div class="space-y-2 max-h-[300px] overflow-y-auto pr-1" id="ccTatTimeSlots"></div>
                  <p class="text-xs text-on-surface-variant mt-4 flex items-center gap-1"><span class="material-symbols-outlined text-[14px]">public</span> Central European Time (CET)</p>
                </div>
              </div>
            </div>
          </div>
          <div id="ccTattooChip" class="hidden mb-2"><div class="confirm-chip" id="ccTattooChipText">🎨 Tattoo Session: —</div></div>
        </div>
      </div>
      <div class="flex items-start gap-3 mt-6 p-4 bg-surface-container-low rounded-xl">
        <span class="material-symbols-outlined text-primary mt-0.5">location_on</span>
        <div>
          <p class="text-sm font-semibold text-on-surface">Ink & Soul Tattoo Studio</p>
          <p class="text-xs text-on-surface-variant">742 Evergreen Terrace, Athens, 10001, Greece</p>
          <a href="https://maps.google.com/?q=Ink+Soul+Tattoo+Studio+Athens" target="_blank" class="text-xs text-primary font-medium hover:underline mt-1 inline-block">Get Directions →</a>
        </div>
      </div>
      <!-- Bottom summary -->
      <div id="ccBottomSummary" class="hidden mt-4 bg-white rounded-2xl border border-primary/20 p-5 shadow-sm">
        <div class="space-y-2 mb-4">
          <p class="text-sm font-semibold" id="ccSumConsult">📹 Consultation: —</p>
          <p class="text-sm font-semibold" id="ccSumTattoo">🎨 Tattoo Session: —</p>
        </div>
        <button onclick="goToStep(3)" class="w-full py-3 bg-primary text-on-primary rounded-full font-bold text-sm hover:bg-primary-container transition-colors shadow-md shadow-primary/20 flex items-center justify-center gap-2">Continue to Registration <span class="material-symbols-outlined text-[18px]">arrow_forward</span></button>
      </div>
    </div>

    <!-- ═══════════════════════════════════════ -->
    <!-- STEP 2C: MANAGED (no consultation)      -->
    <!-- ═══════════════════════════════════════ -->
    <div class="step-panel" id="step2Managed">
      <button class="js-back-to-questions flex items-center gap-1 text-sm text-on-surface-variant hover:text-primary mb-4 transition-colors" onclick="goToStep(1, true)"><span class="material-symbols-outlined text-[18px]">arrow_back</span> Back to Questions</button>
      <div class="bg-white rounded-2xl border border-outline-variant/20 p-6 mb-6">
        <div class="mb-6">
          <h3 class="text-xl font-bold text-on-surface mb-1">When are you available?</h3>
          <p class="text-sm text-on-surface-variant"><span id="managedArtistHint">Julian Ink</span> will confirm a time that works for both of you.</p>
        </div>
        <div id="prefBlocks" class="space-y-4 mb-6">
          <div class="pref-block" data-pref="0">
            <p class="text-xs font-bold text-primary uppercase tracking-wider mb-3">Preference 1 <span class="text-error">*</span></p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div><label class="text-xs font-semibold text-on-surface-variant mb-1 block">Date</label><input type="date" class="pref-date w-full border border-outline-variant/30 bg-white rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30"></div>
              <div><label class="text-xs font-semibold text-on-surface-variant mb-1 block">Time of day</label><div class="flex flex-wrap gap-1.5"><button class="time-pref-pill" data-value="Morning" onclick="toggleTimePref(this)">Morning</button><button class="time-pref-pill" data-value="Afternoon" onclick="toggleTimePref(this)">Afternoon</button><button class="time-pref-pill" data-value="Evening" onclick="toggleTimePref(this)">Evening</button></div></div>
            </div>
          </div>
          <div class="pref-block" data-pref="1">
            <p class="text-xs font-bold text-primary uppercase tracking-wider mb-3">Preference 2 <span class="text-error">*</span></p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div><label class="text-xs font-semibold text-on-surface-variant mb-1 block">Date</label><input type="date" class="pref-date w-full border border-outline-variant/30 bg-white rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30"></div>
              <div><label class="text-xs font-semibold text-on-surface-variant mb-1 block">Time of day</label><div class="flex flex-wrap gap-1.5"><button class="time-pref-pill" data-value="Morning" onclick="toggleTimePref(this)">Morning</button><button class="time-pref-pill" data-value="Afternoon" onclick="toggleTimePref(this)">Afternoon</button><button class="time-pref-pill" data-value="Evening" onclick="toggleTimePref(this)">Evening</button></div></div>
            </div>
          </div>
        </div>
        <button id="addPrefBtn" onclick="addPreference()" class="text-sm text-primary font-semibold flex items-center gap-1 hover:underline mb-6"><span class="material-symbols-outlined text-[18px]">add</span> Add another preference</button>
        <div class="space-y-4">
          <div><label class="text-xs font-semibold text-on-surface-variant mb-2 block">Preferred days of the week</label><div class="flex flex-wrap gap-1.5" id="dayPills"><button class="day-pill" data-value="Mon" onclick="this.classList.toggle('selected')">Mon</button><button class="day-pill" data-value="Tue" onclick="this.classList.toggle('selected')">Tue</button><button class="day-pill" data-value="Wed" onclick="this.classList.toggle('selected')">Wed</button><button class="day-pill" data-value="Thu" onclick="this.classList.toggle('selected')">Thu</button><button class="day-pill" data-value="Fri" onclick="this.classList.toggle('selected')">Fri</button><button class="day-pill" data-value="Sat" onclick="this.classList.toggle('selected')">Sat</button><button class="day-pill" data-value="Sun" onclick="this.classList.toggle('selected')">Sun</button></div></div>
          <div><label class="text-xs font-semibold text-on-surface-variant mb-1 block">Any dates to avoid?</label><input type="text" id="managedAvoid" placeholder="e.g., April 10-15, May 1st" class="w-full border border-outline-variant/30 bg-white rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30"></div>
          <div><label class="text-xs font-semibold text-on-surface-variant mb-2 block">How flexible are you?</label><div class="flex flex-wrap gap-2" id="flexPills"><button class="single-choice-radio-button text-sm !py-2 !px-4" data-value="Very flexible" onclick="selectPill(this,'flexPills')">Very flexible</button><button class="single-choice-radio-button text-sm !py-2 !px-4" data-value="Somewhat flexible" onclick="selectPill(this,'flexPills')">Somewhat flexible</button><button class="single-choice-radio-button text-sm !py-2 !px-4" data-value="These are my only options" onclick="selectPill(this,'flexPills')">These are my only options</button></div></div>
          <div><label class="text-xs font-semibold text-on-surface-variant mb-2 block">Urgency</label><div class="flex flex-wrap gap-2" id="urgencyPills"><button class="single-choice-radio-button text-sm !py-2 !px-4" data-value="No rush" onclick="selectPill(this,'urgencyPills')">No rush</button><button class="single-choice-radio-button text-sm !py-2 !px-4" data-value="Within 2 weeks" onclick="selectPill(this,'urgencyPills')">Within 2 weeks</button><button class="single-choice-radio-button text-sm !py-2 !px-4" data-value="Within a month" onclick="selectPill(this,'urgencyPills')">Within a month</button><button class="single-choice-radio-button text-sm !py-2 !px-4" data-value="ASAP" onclick="selectPill(this,'urgencyPills')">ASAP</button></div></div>
        </div>
      </div>
      <div class="flex items-start gap-3 mt-6 p-4 bg-surface-container-low rounded-xl">
        <span class="material-symbols-outlined text-primary mt-0.5">location_on</span>
        <div>
          <p class="text-sm font-semibold text-on-surface">Ink & Soul Tattoo Studio</p>
          <p class="text-xs text-on-surface-variant">742 Evergreen Terrace, Athens, 10001, Greece</p>
          <a href="https://maps.google.com/?q=Ink+Soul+Tattoo+Studio+Athens" target="_blank" class="text-xs text-primary font-medium hover:underline mt-1 inline-block">Get Directions →</a>
        </div>
      </div>
      <button onclick="goToStep(3)" class="w-full py-3.5 rounded-xl font-bold text-white bg-primary hover:opacity-90 transition-all text-sm mt-4">Continue to Your Details</button>
    </div>

    <!-- ═══════════════════════════════════════════ -->
    <!-- STEP 2D: MANAGED + CONSULTATION             -->
    <!-- ═══════════════════════════════════════════ -->
    <div class="step-panel" id="step2ManagedConsult">
      <button class="js-back-to-questions flex items-center gap-1 text-sm text-on-surface-variant hover:text-primary mb-4 transition-colors" onclick="goToStep(1, true)"><span class="material-symbols-outlined text-[18px]">arrow_back</span> Back to Questions</button>
      <!-- Design card -->
      <!-- Consultation banner -->
      <div class="bg-gradient-to-r from-primary/5 to-secondary-container/30 rounded-2xl border border-primary/10 p-5 mb-6">
        <div class="flex items-start gap-3">
          <span class="material-symbols-outlined text-primary text-2xl mt-0.5">video_camera_front</span>
          <div>
            <h3 class="text-base font-bold text-on-surface mb-1"><span class="mc-artistName">Julian Ink</span> includes a free consultation before your tattoo session</h3>
            <p class="text-sm text-on-surface-variant">You'll have a 15-minute call to discuss your design, placement, and any questions.</p>
          </div>
        </div>
      </div>
      <!-- Consultation type selector -->
      <div class="mb-6">
        <h3 class="text-lg font-bold text-on-surface mb-1">How would you like to have your consultation?</h3>
        <p class="text-sm text-on-surface-variant mb-4">Choose the format that works best for you.</p>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3" id="mcConsultTypeCards">
          <div class="consult-type-card" data-type="video" onclick="selectMcConsultType(this,'video')"><div class="ct-icon mb-3"><span class="material-symbols-outlined">videocam</span></div><h4 class="font-bold text-sm text-on-surface mb-0.5">📹 Video Call</h4><p class="text-xs text-on-surface-variant">15-minute call on Inkjin</p><p class="text-xs text-on-surface-variant mt-1">Convenient — join from anywhere</p></div>
          <div class="consult-type-card" data-type="phone" onclick="selectMcConsultType(this,'phone')"><div class="ct-icon mb-3"><span class="material-symbols-outlined">call</span></div><h4 class="font-bold text-sm text-on-surface mb-0.5">📞 Phone Call</h4><p class="text-xs text-on-surface-variant">15-minute phone consultation</p><p class="text-xs text-on-surface-variant mt-1">Quick and easy</p></div>
          <div class="consult-type-card" data-type="studio" onclick="selectMcConsultType(this,'studio')"><div class="ct-icon mb-3"><span class="material-symbols-outlined">storefront</span></div><h4 class="font-bold text-sm text-on-surface mb-0.5">🏠 In-Studio Visit</h4><p class="text-xs text-on-surface-variant">Visit <span class="mc-studioName">Black Lotus Studio</span> in person</p><p class="text-xs text-on-surface-variant mt-1">Meet your artist and see the space</p><p class="text-xs text-primary font-medium mt-1 mc-studioAddress">Athens, Greece</p></div>
        </div>
      </div>
      <!-- Single availability block (shown after type selected) -->
      <div id="mcAvailSection" class="hidden">
        <div class="bg-white rounded-2xl border border-outline-variant/20 p-6 mb-6">
          <div class="mb-6"><h3 class="text-xl font-bold text-on-surface mb-1">Share your availability</h3><p class="text-sm text-on-surface-variant"><span class="mc-artistName">Julian Ink</span> will schedule both your consultation and tattoo session.</p></div>
          <div id="mcPrefBlocks" class="space-y-4 mb-6">
            <div class="pref-block" data-pref="0">
              <p class="text-xs font-bold text-primary uppercase tracking-wider mb-3">Preference 1 <span class="text-error">*</span></p>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3"><div><label class="text-xs font-semibold text-on-surface-variant mb-1 block">Date</label><input type="date" class="mc-pref-date w-full border border-outline-variant/30 bg-white rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30"></div><div><label class="text-xs font-semibold text-on-surface-variant mb-1 block">Time of day</label><div class="flex flex-wrap gap-1.5"><button class="time-pref-pill" data-value="Morning" onclick="toggleTimePref(this)">Morning</button><button class="time-pref-pill" data-value="Afternoon" onclick="toggleTimePref(this)">Afternoon</button><button class="time-pref-pill" data-value="Evening" onclick="toggleTimePref(this)">Evening</button></div></div></div>
            </div>
            <div class="pref-block" data-pref="1">
              <p class="text-xs font-bold text-primary uppercase tracking-wider mb-3">Preference 2 <span class="text-error">*</span></p>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3"><div><label class="text-xs font-semibold text-on-surface-variant mb-1 block">Date</label><input type="date" class="mc-pref-date w-full border border-outline-variant/30 bg-white rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30"></div><div><label class="text-xs font-semibold text-on-surface-variant mb-1 block">Time of day</label><div class="flex flex-wrap gap-1.5"><button class="time-pref-pill" data-value="Morning" onclick="toggleTimePref(this)">Morning</button><button class="time-pref-pill" data-value="Afternoon" onclick="toggleTimePref(this)">Afternoon</button><button class="time-pref-pill" data-value="Evening" onclick="toggleTimePref(this)">Evening</button></div></div></div>
            </div>
          </div>
          <button id="mcAddPrefBtn" onclick="addMcPreference()" class="text-sm text-primary font-semibold flex items-center gap-1 hover:underline mb-6"><span class="material-symbols-outlined text-[18px]">add</span> Add another preference</button>
          <div class="space-y-4">
            <div><label class="text-xs font-semibold text-on-surface-variant mb-2 block">Preferred days of the week</label><div class="flex flex-wrap gap-1.5" id="mcDayPills"><button class="day-pill" data-value="Mon" onclick="this.classList.toggle('selected')">Mon</button><button class="day-pill" data-value="Tue" onclick="this.classList.toggle('selected')">Tue</button><button class="day-pill" data-value="Wed" onclick="this.classList.toggle('selected')">Wed</button><button class="day-pill" data-value="Thu" onclick="this.classList.toggle('selected')">Thu</button><button class="day-pill" data-value="Fri" onclick="this.classList.toggle('selected')">Fri</button><button class="day-pill" data-value="Sat" onclick="this.classList.toggle('selected')">Sat</button><button class="day-pill" data-value="Sun" onclick="this.classList.toggle('selected')">Sun</button></div></div>
            <div><label class="text-xs font-semibold text-on-surface-variant mb-2 block">How flexible are you?</label><div class="flex flex-wrap gap-2" id="mcFlexPills"><button class="single-choice-radio-button text-sm !py-2 !px-4" data-value="Very flexible" onclick="selectPill(this,'mcFlexPills')">Very flexible</button><button class="single-choice-radio-button text-sm !py-2 !px-4" data-value="Somewhat flexible" onclick="selectPill(this,'mcFlexPills')">Somewhat flexible</button><button class="single-choice-radio-button text-sm !py-2 !px-4" data-value="These are my only options" onclick="selectPill(this,'mcFlexPills')">These are my only options</button></div></div>
            <div><label class="text-xs font-semibold text-on-surface-variant mb-2 block">How soon after the consultation would you like your tattoo session?</label><div class="flex flex-wrap gap-2" id="mcGapPills"><button class="single-choice-radio-button text-sm !py-2 !px-4" data-value="Same week" onclick="selectPill(this,'mcGapPills')">Same week</button><button class="single-choice-radio-button text-sm !py-2 !px-4" data-value="1-2 weeks after" onclick="selectPill(this,'mcGapPills')">1-2 weeks after</button><button class="single-choice-radio-button text-sm !py-2 !px-4" data-value="2-4 weeks after" onclick="selectPill(this,'mcGapPills')">2-4 weeks after</button><button class="single-choice-radio-button text-sm !py-2 !px-4" data-value="I'm flexible" onclick="selectPill(this,'mcGapPills')">I'm flexible</button></div></div>
          </div>
        </div>
        <div class="bg-surface-container-low rounded-2xl border border-outline-variant/20 p-5 mb-6">
          <p class="text-sm text-on-surface-variant mb-3"><span class="mc-artistName">Julian Ink</span> will review your availability and schedule:</p>
          <div class="space-y-2 mb-1">
            <p class="text-sm font-semibold text-on-surface" id="mcSumLine1">📹 A consultation (Video Call)</p>
            <p class="text-sm font-semibold text-on-surface">🎨 Your tattoo session</p>
          </div>
        </div>
        <div class="flex items-start gap-3 mt-6 p-4 bg-surface-container-low rounded-xl">
          <span class="material-symbols-outlined text-primary mt-0.5">location_on</span>
          <div>
            <p class="text-sm font-semibold text-on-surface">Ink & Soul Tattoo Studio</p>
            <p class="text-xs text-on-surface-variant">742 Evergreen Terrace, Athens, 10001, Greece</p>
            <a href="https://maps.google.com/?q=Ink+Soul+Tattoo+Studio+Athens" target="_blank" class="text-xs text-primary font-medium hover:underline mt-1 inline-block">Get Directions →</a>
          </div>
        </div>
        <button onclick="goToStep(3)" class="w-full py-3.5 rounded-xl font-bold text-white bg-primary hover:opacity-90 transition-all text-sm flex items-center justify-center gap-2 mt-4">Continue <span class="material-symbols-outlined text-[18px]">arrow_forward</span></button>
      </div>
    </div>

    <!-- ═══════════════════════════ -->
    <!-- STEP 3: REGISTER / LOGIN   -->
    <!-- ═══════════════════════════ -->
    <div class="step-panel" id="stepRegister">
      <button class="flex items-center gap-1 text-sm text-on-surface-variant hover:text-primary mb-4 transition-colors" onclick="goToStep(2, true)"><span class="material-symbols-outlined text-[18px]">arrow_back</span> Back</button>
      <!-- Name -->
      <div class="question-div active" data-reg="0" id="reg-0">
        <div class="w-full max-w-xl mx-auto">
          <p class="text-sm font-semibold text-primary mb-2">1 →</p>
          <h2 class="text-2xl sm:text-3xl font-bold text-on-surface mb-2">What's your name?</h2>
          <p class="text-on-surface-variant mb-6">So the artist knows who to expect.</p>
          <input type="text" id="bdName" placeholder="Your full name" class="w-full border border-outline-variant/30 bg-white rounded-2xl px-6 py-4 text-lg text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
          <p id="bdNameError" class="text-sm text-error mt-2 hidden">This field is required.</p>
          <div class="flex items-center justify-between mt-6"><button onclick="nextReg()" class="inline-flex items-center gap-2 px-6 py-3 bg-primary text-on-primary rounded-full font-bold text-sm hover:bg-primary-container transition-colors">Next <span class="material-symbols-outlined text-[18px]">arrow_forward</span></button><span class="text-sm text-on-surface-variant">press <strong>Enter ↵</strong></span></div>
        </div>
      </div>
      <!-- Email -->
      <div class="question-div" data-reg="1" id="reg-1">
        <div class="w-full max-w-xl mx-auto">
          <button onclick="prevReg()" class="flex items-center gap-1 text-sm text-on-surface-variant hover:text-primary mb-4 transition-colors"><span class="material-symbols-outlined text-[18px]">arrow_back</span> Back</button>
          <p class="text-sm font-semibold text-primary mb-2">2 →</p>
          <h2 class="text-2xl sm:text-3xl font-bold text-on-surface mb-2">What's your email?</h2>
          <p class="text-on-surface-variant mb-6">We'll send your booking confirmation here.</p>
          <input type="email" id="bdEmail" placeholder="you@example.com" class="w-full border border-outline-variant/30 bg-white rounded-2xl px-6 py-4 text-lg text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
          <p id="bdEmailError" class="text-sm text-error mt-2 hidden">This field is required.</p>
          <div class="flex items-center justify-between mt-6"><button onclick="nextReg()" class="inline-flex items-center gap-2 px-6 py-3 bg-primary text-on-primary rounded-full font-bold text-sm hover:bg-primary-container transition-colors">Next <span class="material-symbols-outlined text-[18px]">arrow_forward</span></button><span class="text-sm text-on-surface-variant">press <strong>Enter ↵</strong></span></div>
        </div>
      </div>
      <!-- Phone -->
      <div class="question-div" data-reg="2" id="reg-2">
        <div class="w-full max-w-xl mx-auto">
          <button onclick="prevReg()" class="flex items-center gap-1 text-sm text-on-surface-variant hover:text-primary mb-4 transition-colors"><span class="material-symbols-outlined text-[18px]">arrow_back</span> Back</button>
          <p class="text-sm font-semibold text-primary mb-2">3 →</p>
          <h2 class="text-2xl sm:text-3xl font-bold text-on-surface mb-2">Your phone number?</h2>
          <p class="text-on-surface-variant mb-6">In case the artist needs to reach you.</p>
          <input type="tel" id="bdPhone" placeholder="+30 694 123 4567" class="w-full border border-outline-variant/30 bg-white rounded-2xl px-6 py-4 text-lg text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
          <p id="bdPhoneError" class="text-sm text-error mt-2 hidden">This field is required.</p>
          <div class="flex items-center justify-between mt-6"><button onclick="nextReg()" class="inline-flex items-center gap-2 px-6 py-3 bg-primary text-on-primary rounded-full font-bold text-sm hover:bg-primary-container transition-colors">Next <span class="material-symbols-outlined text-[18px]">arrow_forward</span></button><span class="text-sm text-on-surface-variant">press <strong>Enter ↵</strong></span></div>
        </div>
      </div>
      <!-- Auth -->
      <div class="question-div" data-reg="3" id="reg-3">
        <div class="w-full max-w-md mx-auto">
          <div id="bdAuthCreate">
            <div class="text-center mb-6"><span class="material-symbols-outlined text-primary text-4xl mb-2">mark_email_read</span><h2 class="text-2xl sm:text-3xl font-bold text-on-surface mb-2">Verify your email</h2><p class="text-on-surface-variant">We will send a secure 4-digit code to connect your booking.</p></div>
            <div class="mb-4">
              <label class="text-sm font-semibold text-on-surface-variant ml-1 mb-1 inline-block" for="bdOtpEmail">Email</label>
              <input type="email" id="bdOtpEmail" placeholder="you@example.com" class="w-full border border-outline-variant/30 bg-white rounded-2xl px-6 py-4 text-base text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30" readonly>
            </div>
            <p id="bdOtpStatus" class="hidden items-center gap-2 text-sm text-green-700 bg-green-50 border border-green-200 rounded-xl px-3 py-2 mb-3"></p>
            <div class="mb-4">
              <label class="text-sm font-semibold text-on-surface-variant ml-1 mb-1 inline-block" for="bdOtpCode">4-digit code</label>
              <input type="text" id="bdOtpCode" maxlength="4" inputmode="numeric" placeholder="1234" class="w-full border border-outline-variant/30 bg-white rounded-2xl px-6 py-4 text-lg tracking-[0.3em] text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
              <p id="bdOtpError" class="text-sm text-error mt-2 hidden">Please enter a valid 4-digit code.</p>
            </div>
            <div class="mb-5">
              <label class="text-sm font-semibold text-on-surface-variant ml-1" for="bd_referral_source">How did you hear about us? <span class="text-xs text-on-surface-variant font-normal">(optional)</span></label>
              <select id="bd_referral_source" name="referral_source" class="w-full text-sm border border-outline-variant/30 rounded-xl px-4 py-3 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30 mt-1.5">
                <option value="">Select...</option>
                <option value="instagram">Instagram</option>
                <option value="tiktok">TikTok</option>
                <option value="google">Google Search</option>
                <option value="friend">Friend / Referral</option>
                <option value="convention">Tattoo Convention</option>
                <option value="blog">Blog / Article</option>
                <option value="other">Other</option>
              </select>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-4">
              <button id="bdSendOtpBtn" onclick="sendBookingOtp()" class="w-full py-3.5 bg-surface-container-high text-on-surface rounded-full font-bold text-sm hover:bg-surface-container transition-colors">Send email code</button>
              <button id="bdVerifyOtpBtn" onclick="verifyBookingOtp()" class="w-full py-3.5 bg-primary text-on-primary rounded-full font-bold text-sm hover:bg-primary-container transition-colors shadow-lg shadow-primary/20">Verify & Continue</button>
            </div>
            <p id="bdConnectedUser" class="hidden text-center text-sm text-green-600 mb-4">Already connected user.</p>
            <div class="flex items-center gap-3 mb-4"><div class="flex-1 h-px bg-outline-variant/30"></div><span class="text-sm text-on-surface-variant">or</span><div class="flex-1 h-px bg-outline-variant/30"></div></div>
            <div class="space-y-2 mb-5 hidden">
              <button class="social-btn" onclick="finishRegister()"><svg class="w-5 h-5" viewBox="0 0 24 24"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 01-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg> Continue with Google</button>
              <button class="social-btn" onclick="finishRegister()"><svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.8-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19-.69.85-1.83 1.51-2.95 1.42-.15-1.15.41-2.35 1.05-3.11z"/></svg> Continue with Apple</button>
            </div>
            <p class="text-center text-sm text-on-surface-variant">Email verified once will stay connected for this booking session.</p>
          </div>
          <div id="bdAuthLogin" class="hidden">
            <div class="text-center mb-6"><span class="material-symbols-outlined text-primary text-4xl mb-2">waving_hand</span><h2 class="text-2xl sm:text-3xl font-bold text-on-surface mb-2">Welcome back!</h2><p class="text-on-surface-variant">Log in to continue with your booking.</p></div>
            <div class="flex items-center gap-2 bg-surface-container rounded-xl px-4 py-3 mb-5"><span class="material-symbols-outlined text-primary text-[18px]">mail</span><span class="text-sm text-on-surface" id="bdAuthLoginEmail">you@example.com</span><span class="material-symbols-outlined text-green-500 text-[16px] ml-auto">check_circle</span></div>
            <div class="mb-5"><input type="password" id="bdLoginPassword" placeholder="Enter your password" class="w-full border border-outline-variant/30 bg-white rounded-2xl px-6 py-4 text-lg text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30"></div>
            <button onclick="finishRegister()" class="w-full py-3.5 bg-primary text-on-primary rounded-full font-bold text-sm hover:bg-primary-container transition-colors shadow-lg shadow-primary/20 mb-3">Log In & Continue</button>
            <p class="text-center text-sm text-primary font-medium cursor-pointer mb-5">Forgot password?</p>
            <div class="flex items-center gap-3 mb-4"><div class="flex-1 h-px bg-outline-variant/30"></div><span class="text-sm text-on-surface-variant">or</span><div class="flex-1 h-px bg-outline-variant/30"></div></div>
            <div class="space-y-2 mb-5">
              <button class="social-btn" onclick="finishRegister()"><svg class="w-5 h-5" viewBox="0 0 24 24"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 01-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg> Continue with Google</button>
              <button class="social-btn" onclick="finishRegister()"><svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.8-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19-.69.85-1.83 1.51-2.95 1.42-.15-1.15.41-2.35 1.05-3.11z"/></svg> Continue with Apple</button>
            </div>
            <p class="text-center text-sm text-on-surface-variant">Don't have an account? <span class="auth-toggle" onclick="toggleBdAuth()">Sign up</span></p>
          </div>
        </div>
      </div>
    </div>

    <!-- ═══════════════════ -->
    <!-- STEP 4: PAYMENT    -->
    <!-- ═══════════════════ -->
    <div class="step-panel" id="stepPayment">
      <button class="flex items-center gap-1 text-sm text-on-surface-variant hover:text-primary mb-6 transition-colors" onclick="goToStep(3, true)"><span class="material-symbols-outlined text-[18px]">arrow_back</span> Back</button>
      <!-- Calendar mode payment -->
      <div id="paymentCalendarMode">
        <div class="flex flex-col lg:flex-row gap-6">
          <div class="lg:w-[340px] lg:order-2">
            <div class="bg-white rounded-2xl border border-outline-variant/20 p-5 lg:sticky lg:top-24">
              <h3 class="text-sm font-bold text-on-surface-variant uppercase tracking-wider mb-4">Booking Summary</h3>
              <div class="space-y-3 text-sm">
                <div class="flex justify-between"><span class="text-on-surface-variant">Design</span><span class="font-semibold" id="payDesign">—</span></div>
                <div class="flex justify-between"><span class="text-on-surface-variant">Artist</span><span class="font-semibold" id="payArtist">—</span></div>
                <div class="flex justify-between hidden" id="payConsultRow"><span class="text-on-surface-variant">Consultation</span><span class="font-semibold" id="payConsultDateTime">—</span></div>
                <div class="flex justify-between"><span class="text-on-surface-variant" id="payDateTimeLabel">Date & Time</span><span class="font-semibold" id="payDateTime">—</span></div>
                <div class="flex justify-between"><span class="text-on-surface-variant">Duration</span><span class="font-semibold" id="payDuration">—</span></div>
                <div class="flex justify-between"><span class="text-on-surface-variant">Size</span><span class="font-semibold" id="paySize">—</span></div>
                <div class="flex justify-between"><span class="text-on-surface-variant">Location</span><span class="font-semibold text-xs text-right" id="payLocation">—</span></div>
              </div>
              <hr class="border-outline-variant/20 my-4">
              <div class="space-y-2 text-sm mb-3"><div class="flex justify-between"><span class="font-semibold text-on-surface">Price Estimate</span><span class="font-semibold text-on-surface" id="payPriceEstimate">—</span></div></div>
              <div class="bg-surface-container-low rounded-xl p-3 mb-3">
                <p class="text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2">Due Now</p>
                <div class="space-y-1.5 text-sm">
                  <div class="flex justify-between hidden" id="payConsultFeeRow"><span class="text-on-surface-variant">Consultation</span><span class="font-semibold text-green-600">Free</span></div>
                  <div class="flex justify-between"><span class="text-on-surface-variant" id="payDepositLabel">Deposit</span><span class="font-semibold" id="payDeposit">—</span></div>
                  <div class="flex justify-between items-center"><span class="text-on-surface-variant flex items-center gap-1">Inkjin Booking Fee <span class="info-tooltip"><span class="material-symbols-outlined text-[14px] text-outline">info</span><span class="tooltip-text">This fee helps us maintain the platform, provide secure payments, and offer customer support.</span></span></span><span class="font-semibold" id="payBookingFee">—</span></div>
                  <hr class="border-outline-variant/20">
                  <div class="flex justify-between"><span class="font-bold text-on-surface">Total Due Now</span><span class="font-bold text-primary text-lg" id="payTotal">—</span></div>
                </div>
              </div>
              <div class="bg-surface-container-low rounded-xl p-3"><p class="text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2">Due at Studio</p><div class="space-y-1.5 text-sm"><div class="flex justify-between"><span class="text-on-surface-variant">Remaining Balance</span><span class="font-semibold" id="payBalance">—</span></div><p class="text-xs text-on-surface-variant italic mt-1">If you get this design as-is (original size, no modifications), expect to pay the minimum. Final price confirmed by the artist based on size, placement, and any customizations.</p></div></div>
            </div>
          </div>
          <div class="flex-1 lg:order-1">
            <h2 class="text-xl font-bold mb-1 flex items-center gap-2"><span class="material-symbols-outlined text-[22px] text-primary">lock</span> Secure Payment</h2>
            <p class="text-sm text-on-surface-variant mb-6">Your payment is securely processed. You won't be charged until you confirm.</p>
            <div class="bg-white rounded-2xl border border-outline-variant/20 p-6 mb-6">
              <div class="space-y-4">
                <div>
                  <label class="text-xs font-semibold text-on-surface-variant mb-1.5 block">Card Number</label>
                  <div class="relative">
                    <div id="stripeCardNumber" class="w-full border border-outline-variant/30 bg-white rounded-xl px-4 py-3 pr-24 text-sm"></div>
                    <div class="absolute right-3 top-1/2 -translate-y-1/2 flex gap-1"><span class="card-type-icon" id="iconVisa">VISA</span><span class="card-type-icon" id="iconMC">MC</span><span class="card-type-icon" id="iconAmex">AMEX</span></div>
                  </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                  <div><label class="text-xs font-semibold text-on-surface-variant mb-1.5 block">Expiry</label><div id="stripeCardExpiry" class="w-full border border-outline-variant/30 bg-white rounded-xl px-4 py-3 text-sm"></div></div>
                  <div><label class="text-xs font-semibold text-on-surface-variant mb-1.5 block">CVV</label><div id="stripeCardCvc" class="w-full border border-outline-variant/30 bg-white rounded-xl px-4 py-3 text-sm"></div></div>
                </div>
                <div><label class="text-xs font-semibold text-on-surface-variant mb-1.5 block">Cardholder Name</label><input type="text" id="inputCardName" placeholder="Name on card" class="w-full border border-outline-variant/30 bg-white rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30"></div>
                <p class="text-xs text-on-surface-variant flex items-center gap-2">Accepted: <strong>Visa</strong> · <strong>Mastercard</strong> · <strong>Amex</strong></p>
              </div>
              {{-- <label class="flex items-center gap-2 mt-5 cursor-pointer"><input type="checkbox" id="saveCard" class="accent-primary"><span class="text-sm text-on-surface-variant">Save this card for future bookings</span></label> --}}
            </div>
            {{-- <div class="flex items-center gap-3 mb-6"><div class="flex-1 h-px bg-outline-variant/30"></div><span class="text-sm text-on-surface-variant font-medium">— or pay with —</span><div class="flex-1 h-px bg-outline-variant/30"></div></div> --}}
            {{-- <div class="space-y-3 mb-6">
              <button id="applePayBtn" class="w-full py-3.5 rounded-xl font-bold text-white bg-black hover:bg-gray-800 transition-colors text-sm flex items-center justify-center gap-2 shadow-sm" style="display:none;"><svg class="w-5 h-5" fill="white" viewBox="0 0 24 24"><path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.8-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19-.69.85-1.83 1.51-2.95 1.42-.15-1.15.41-2.35 1.05-3.11z"/></svg> Pay with  Pay</button>
              <button id="googlePayBtn" class="w-full py-3.5 rounded-xl font-bold text-on-surface bg-white border-2 border-outline-variant/40 hover:border-outline-variant hover:bg-surface-container-low transition-colors text-sm flex items-center justify-center gap-2 shadow-sm"><svg class="w-5 h-5" viewBox="0 0 24 24"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 01-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg> Pay with Google Pay</button>
            </div>
            <div class="rounded-2xl border border-[#FFB3C7]/40 bg-gradient-to-br from-[#FFF0F5] to-[#FFE8EF] p-5 mb-6">
              <div class="flex items-center gap-2 mb-2"><span class="text-lg font-extrabold text-[#17120F]">Klarna.</span><span class="text-xs font-semibold text-[#17120F]/60 bg-[#FFB3C7]/30 px-2 py-0.5 rounded-full">Buy now, pay later</span></div>
              <p class="text-sm font-semibold text-[#17120F] mb-1">Pay in 3 interest-free installments</p>
              <p class="text-lg font-bold text-[#17120F] mb-1" id="klarnaAmount">3 payments of €XX.XX/mo</p>
              <p class="text-xs text-[#17120F]/70 mb-4">No interest. No fees. Split your payment automatically.</p>
              <button class="w-full py-3 rounded-xl font-bold text-[#17120F] bg-[#FFB3C7] hover:bg-[#FF9CB8] transition-colors text-sm shadow-sm">Select Klarna</button>
              <p class="text-[10px] text-[#17120F]/50 text-center mt-2">You'll be redirected to Klarna to complete your payment</p>
              <p class="text-[10px] text-[#17120F]/40 text-center">Subject to approval. 18+ only.</p>
            </div> --}}
            @php
              $cwRaw = strtolower(trim((string) ($userDetail->cancellation_window ?? '48h')));
              if (str_contains($cwRaw, 'w')) {
                  preg_match('/(\d+)/', $cwRaw, $m);
                  $n = (int) ($m[1] ?? 1);
                  $cancelWindowHuman = $n === 1 ? '1 week' : $n.' weeks';
              } elseif (str_contains($cwRaw, 'day')) {
                  preg_match('/(\d+)/', $cwRaw, $m);
                  $n = (int) ($m[1] ?? 1);
                  $cancelWindowHuman = $n === 1 ? '1 day' : $n.' days';
              } else {
                  preg_match('/(\d+)/', $cwRaw, $m);
                  $n = (int) ($m[1] ?? 48);
                  $cancelWindowHuman = $n === 1 ? '1 hour' : $n.' hours';
              }

              $reschedulePolicy = strtolower((string) ($userDetail->reschedule_times ?? 'never'));
              $rescheduleText = match ($reschedulePolicy) {
                  'once' => 'The artist allows you to reschedule your appointment once',
                  'twice' => 'The artist allows you to reschedule your appointment twice',
                  'unlimited' => 'The artist allows unlimited reschedules before the cancellation deadline',
                  default => 'Rescheduling is not allowed for this artist',
              };
            @endphp
            <div class="bg-surface-container-low rounded-2xl border border-outline-variant/20 mb-4" id="cancellationPolicySection">
              <button onclick="toggleCancellationPolicy()" class="w-full flex items-center justify-between p-4 text-left"><span class="text-sm font-semibold text-on-surface flex items-center gap-2">📋 Cancellation Policy</span><span class="material-symbols-outlined text-on-surface-variant text-[20px] transition-transform" id="cancPolicyArrow" style="transition: transform 0.2s ease;">expand_more</span></button>
              <div class="hidden px-4 pb-4" id="cancellationPolicyContent"><div class="text-sm text-on-surface-variant space-y-1.5"><p class="font-semibold text-on-surface mb-2">Artist's Cancellation Policy:</p><p>• Full refund if canceled at least {{ $cancelWindowHuman }} before your appointment</p><p>• No refund if canceled less than {{ $cancelWindowHuman }} before your appointment</p><p>• {{ $rescheduleText }}</p><p>• No-shows forfeit the full deposit</p></div></div>
            </div>
            <label class="flex items-start gap-2 mb-4 cursor-pointer"><input type="checkbox" id="agreePolicy" class="mt-0.5 accent-primary" onchange="checkPayReady()"><span class="text-xs text-on-surface-variant">I agree to the <a href="javascript:void(0)" onclick="event.preventDefault(); expandCancellationPolicy();" class="text-primary underline">cancellation policy</a> and <a href="#" class="text-primary underline">terms of service</a>.</span></label>
            <p class="text-sm text-error hidden mb-3" id="formError"></p>
            <button id="btnConfirmPay" disabled onclick="confirmBooking()" class="w-full py-4 rounded-xl font-bold text-white bg-primary disabled:opacity-40 disabled:cursor-not-allowed hover:opacity-90 transition-all text-base shadow-lg shadow-primary/20">Confirm & Pay <span id="btnPayTotalAmount">€250</span></button>
          </div>
        </div>
      </div>
      <!-- Managed mode -->
      <div id="paymentManagedMode" class="hidden">
        <div class="max-w-xl mx-auto text-center py-12">
          <span class="material-symbols-outlined text-primary text-5xl mb-4">info</span>
          <h2 class="text-2xl font-bold text-on-surface mb-3">No payment required yet</h2>
          <p class="text-on-surface-variant mb-8">You'll be asked to pay a deposit once <strong id="payManagedArtist">Julian Ink</strong> confirms your appointment.</p>
          <div class="bg-white rounded-2xl border border-outline-variant/20 p-5 mb-8 text-left" id="managedReview"></div>
          <button onclick="confirmBooking()" class="w-full py-3.5 bg-primary text-on-primary rounded-xl font-bold text-sm hover:bg-primary-container transition-colors shadow-lg shadow-primary/20">Submit Booking Request</button>
        </div>
      </div>
    </div>

    <!-- ═══════════════════════ -->
    <!-- STEP 5: CONFIRMATION   -->
    <!-- ═══════════════════════ -->
    <div class="step-panel" id="stepConfirmation">
      <div class="flex flex-col items-center justify-center py-16" id="processingView"><div class="spinner mb-4"></div><p class="text-sm text-on-surface-variant" id="processingText">Processing your payment…</p></div>
      <!-- Calendar confirmation -->
      <div class="hidden" id="confirmationCalendar">
        <div class="flex justify-center mb-6"><svg width="80" height="80" viewBox="0 0 80 80" fill="none"><circle cx="40" cy="40" r="36" stroke="#310f7a" stroke-width="3" fill="none" class="check-circle"/><path d="M24 42 L34 52 L56 30" stroke="#310f7a" stroke-width="3" fill="none" stroke-linecap="round" stroke-linejoin="round" class="check-mark"/></svg></div>
        <h2 class="text-2xl font-extrabold text-center mb-2">Booking Confirmed!</h2>
        <p class="text-sm text-on-surface-variant text-center mb-8">Your deposit has been received and your appointment is secured.</p>
        <div class="bg-white rounded-2xl border border-outline-variant/20 p-5 mb-8">
          <div class="space-y-2.5 text-sm">
            <div class="flex justify-between"><span class="text-on-surface-variant">Booking Ref</span><span class="font-bold text-primary" id="confRef">#INK-000000</span></div>
            <div class="flex justify-between"><span class="text-on-surface-variant">Design</span><span class="font-semibold" id="confDesign">—</span></div>
            <div class="flex justify-between hidden" id="confConsultRow"><span class="text-on-surface-variant">Consultation</span><span class="font-semibold" id="confConsultDateTime">—</span></div>
            <div class="flex justify-between"><span class="text-on-surface-variant" id="confDateTimeLabel">Date & Time</span><span class="font-semibold" id="confDateTime">—</span></div>
            <div class="flex justify-between"><span class="text-on-surface-variant">Artist</span><span class="font-semibold" id="confArtist">—</span></div>
            <div class="flex justify-between"><span class="text-on-surface-variant">Studio</span><span class="font-semibold" id="confStudio">—</span></div>
            <div class="flex justify-between"><span class="text-on-surface-variant">Location</span><span class="font-semibold" id="confLocationName">—</span></div>
            <div class="flex justify-between"><span class="text-on-surface-variant"></span><span class="text-xs text-on-surface-variant" id="confLocationAddress">—</span></div>
            <div class="flex justify-between"><span></span><a href="#" id="confDirectionsLink" target="_blank" class="text-xs text-primary font-medium hover:underline">Get Directions →</a></div>
            <div class="flex justify-between"><span class="text-on-surface-variant">Placement</span><span class="font-semibold" id="confPlacement">—</span></div>
            <div class="flex justify-between"><span class="text-on-surface-variant">Size</span><span class="font-semibold" id="confSize">—</span></div>
            <hr class="border-outline-variant/20">
            <div class="flex justify-between"><span class="text-on-surface-variant">Price Estimate</span><span class="font-semibold" id="confPriceEstimate">—</span></div>
            <div class="flex justify-between hidden" id="confConsultFeeRow"><span class="text-on-surface-variant">Consultation</span><span class="font-semibold text-green-600">Free</span></div>
            <div class="flex justify-between"><span class="text-on-surface-variant" id="confDepositLabel">Deposit</span><span class="font-semibold" id="confDeposit">—</span></div>
            <div class="flex justify-between"><span class="text-on-surface-variant">Inkjin Booking Fee</span><span class="font-semibold" id="confBookingFee">—</span></div>
            <div class="flex justify-between"><span class="font-bold text-on-surface">Total Paid</span><span class="font-bold text-primary" id="confTotalPaid">—</span></div>
            <hr class="border-outline-variant/10">
            <div class="flex justify-between"><span class="text-on-surface-variant">Remaining Balance</span><span class="font-semibold" id="confBalance">—</span></div>
            <p class="text-xs text-on-surface-variant italic">If you get this design as-is (original size, no modifications), expect to pay the minimum. Final price confirmed by the artist based on size, placement, and any customizations.</p>
          </div>
          <div class="mt-3 pt-3 border-t border-outline-variant/20"><a href="javascript:void(0)" onclick="scrollToCancellationPolicy()" class="text-xs text-primary font-medium hover:underline">View cancellation policy →</a></div>
        </div>
        <div class="bg-surface-container-low rounded-2xl p-5 mb-8">
          <h3 class="text-sm font-bold mb-3">What's next?</h3>
          <ul class="space-y-2 text-sm text-on-surface-variant" id="confWhatsNext">
            <li class="flex items-start gap-2"><span class="text-primary mt-0.5">✦</span> You'll receive a confirmation email</li>
            <li class="flex items-start gap-2"><span class="text-primary mt-0.5">✦</span> The artist may reach out about design details</li>
            <li class="flex items-start gap-2"><span class="text-primary mt-0.5">✦</span> Arrive 10 minutes early on your appointment day</li>
            <li class="flex items-start gap-2"><span class="text-primary mt-0.5">✦</span> Remember to bring a valid photo ID</li>
          </ul>
        </div>
        <div class="flex flex-col sm:flex-row gap-3">
          <a href="{{ route('user.bookings.index') }}" class="flex-1 py-3.5 rounded-xl font-bold text-white bg-primary hover:opacity-90 transition-all text-sm text-center">View My Booking</a>
          <a href="{{ route('public.artist', ['username' => $userDetail->user_name]) }}" class="flex-1 py-3.5 rounded-xl font-bold text-primary border-2 border-primary hover:bg-primary/5 transition-all text-sm text-center">Back to Artist Page</a>
        </div>
      </div>
      <!-- Managed confirmation -->
      <div class="hidden" id="confirmationManaged">
        <div class="flex justify-center mb-6"><svg width="80" height="80" viewBox="0 0 80 80" fill="none"><circle cx="40" cy="40" r="36" stroke="#22c55e" stroke-width="3" fill="none" class="check-circle"/><path d="M24 42 L34 52 L56 30" stroke="#22c55e" stroke-width="3" fill="none" stroke-linecap="round" stroke-linejoin="round" class="check-mark"/></svg></div>
        <h2 class="text-2xl font-extrabold text-center mb-2" id="confManagedTitle">Availability Submitted! 🎉</h2>
        <p class="text-sm text-on-surface-variant text-center mb-8" id="confManagedDesc"><span id="confManagedArtist">Julian Ink</span> will review your preferred times and confirm an appointment. You'll receive an email once your booking is confirmed.</p>
        <div class="bg-surface-container-low rounded-2xl p-5 mb-8">
          <h3 class="text-sm font-bold mb-3">What happens next?</h3>
          <ul class="space-y-2 text-sm text-on-surface-variant" id="confManagedWhatsNext">
            <li class="flex items-start gap-2"><span class="text-primary mt-0.5">✦</span> The artist will review your availability</li>
            <li class="flex items-start gap-2"><span class="text-primary mt-0.5">✦</span> You'll receive an email with the confirmed date & time</li>
            <li class="flex items-start gap-2"><span class="text-primary mt-0.5">✦</span> A deposit may be required to secure your spot</li>
            <li class="flex items-start gap-2"><span class="text-primary mt-0.5">✦</span> You can message the artist if anything changes</li>
          </ul>
        </div>
        <div class="flex flex-col sm:flex-row gap-3">
          <a href="artist-page.html" class="flex-1 py-3.5 rounded-xl font-bold text-primary border-2 border-primary hover:bg-primary/5 transition-all text-sm text-center">Back to Artist Page</a>
        </div>
      </div>
    </div>
  </main>

  <!-- jquery-cdn -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/Dropify/0.2.2/js/dropify.min.js"></script>
  <script src="https://js.stripe.com/v3/"></script>
  <script>
    var currentStep = 1;
    var currentQuestionIndex = 0;
    var currentRegIndex = 0;
    var questionAnswers = {};
    var bookingOtpVerified = false;
    var bookingConnectedEmail = '';
    var bookingConnectedName = '';
    var bookingOtpResendRemaining = 0;
    var bookingOtpResendEmail = '';
    var bookingOtpResendTimer = null;
    var csrfToken = @json(csrf_token());
    var stripePublishableKey = @json($stripePublishableKey ?? '');
    var minimumDepositType = @json($minimumDepositType ?? 'percentage');
    var minimumDepositAmount = parseFloat(@json($minimumDepositAmount ?? 30)) || 0;
    var bookingFeeType = @json($bookingFeeType ?? 'client');
    var bookingArtistUsername = @json($userDetail->user_name ?? '');
    var bookingTattooSlug = @json($tattoo->slug ?? '');
    var stripe = null;
    var stripeElements = null;
    var stripeCardNumber = null;
    var stripeCardExpiry = null;
    var stripeCardCvc = null;
    var isStripeMounted = false;
    var stripeCardComplete = { number: false, expiry: false, cvc: false };
    var serverQuestions = @json($requiredBookingQuestions ?? $questions ?? []);
    var questionDefinitions = (Array.isArray(serverQuestions) ? serverQuestions : []).map(function(q) {
      var typeMap = { text: 'input', free: 'input', images: 'image', checkbox: 'toggle' };
      var normalizedType = typeMap[q.type] || q.type || 'input';
      var opts = Array.isArray(q.options) ? q.options : [];
      if (normalizedType === 'toggle' && !opts.length) opts = ['Yes', 'No'];
      return {
        id: q.id,
        title: q.question || 'Question',
        subtitle: q.description || 'Please answer this question.',
        type: normalizedType,
        options: opts,
        placeholder: q.placeholder || '',
        required: !!q.is_required
      };
    });

    function getBookingDeposit() {
      var minPrice = parseFloat(@json($tattoo->min_price ?? 0)) || 0;
      if (minimumDepositType === 'amount') {
        return Math.min(minPrice, Math.max(0, minimumDepositAmount));
      }
      var percentage = Math.max(0, minimumDepositAmount);
      return Math.round((minPrice * (percentage / 100)) * 100) / 100;
    }

    function getDepositLabel() {
      if (minimumDepositType === 'amount') {
        return 'Deposit (Fixed)';
      }
      var pct = Number(minimumDepositAmount || 0);
      return 'Deposit (' + (Number.isInteger(pct) ? String(pct) : pct.toFixed(2).replace(/\.00$/, '')) + '%)';
    }

    function getBookingFee() {
      var baseFee = 10.00;
      if (bookingFeeType === 'artist') return 0.00;
      if (bookingFeeType === 'split') return baseFee / 2;
      return baseFee;
    }

    function getDueNow() {
      return Math.round((getBookingDeposit() + getBookingFee()) * 100) / 100;
    }

    function formatEUR(amount) {
      return '€' + Number(amount || 0).toFixed(2);
    }

    function formatDateTimeLabel(dateObj, timeLabel) {
      if (!(dateObj instanceof Date) || !timeLabel) return '—';
      return dateObj.toLocaleDateString('en-GB', { weekday: 'short', day: 'numeric', month: 'short' }) + ', ' + timeLabel;
    }

    function formatDurationLabel(minutes) {
      var mins = Math.max(0, parseInt(minutes || 0, 10) || 0);
      var hrs = Math.floor(mins / 60);
      var rem = mins % 60;
      if (hrs > 0 && rem > 0) return hrs + 'h ' + rem + 'm';
      if (hrs > 0) return hrs + 'h';
      return rem + 'm';
    }

    function getAnswerByKeywords(keywords) {
      var defs = Array.isArray(questionDefinitions) ? questionDefinitions : [];
      for (var i = 0; i < defs.length; i++) {
        var q = defs[i] || {};
        var title = String(q.title || '').toLowerCase();
        var subtitle = String(q.subtitle || '').toLowerCase();
        var matched = keywords.some(function(k) {
          return title.indexOf(k) !== -1 || subtitle.indexOf(k) !== -1;
        });
        if (matched) {
          var val = questionAnswers[q.id];
          if (typeof val === 'string' && val.trim()) return val.trim();
          if (typeof val === 'number' || typeof val === 'boolean') return String(val);
        }
      }
      return '';
    }

    function updatePaymentSummary() {
      var minPrice = parseFloat(@json($tattoo->min_price ?? 0)) || 0;
      var maxPrice = parseFloat(@json($tattoo->max_price ?? 0)) || 0;
      var deposit = getBookingDeposit();
      var total = getDueNow();
      var minBalance = Math.max(0, minPrice - deposit);
      var maxBalance = Math.max(0, maxPrice - deposit);
      var balanceLabel = formatEUR(minBalance) + ' - ' + formatEUR(maxBalance);
      var artistName = @json(($userDetail->user->first_name ?? '') . ' ' . ($userDetail->user->last_name ?? ''));
      var studioName = @json($userDetail->studio_name ?? 'Studio');
      var studioAddress = @json($userDetail->studio_address ?? '');
      var mapsLink = @json($userDetail->google_maps_link ?? '');

      var mainDateTime = '—';
      var consultDateTime = '—';
      var durationLabel = formatDurationLabel(tattooDurationMinutes);

      if (consultationRequired) {
        if (ccConsultDate && ccConsultTime) {
          consultDateTime = formatDateTimeLabel(ccConsultDate, ccConsultTime);
        }

        if (consultationTiming === 'combined') {
          mainDateTime = consultDateTime;
          durationLabel = formatDurationLabel(tattooDurationMinutes + consultDurationMinutes);
          $('#payDateTimeLabel').text('Date & Time');
          $('#payConsultRow').addClass('hidden');
        } else {
          mainDateTime = (ccTattooDate && ccTattooTime) ? formatDateTimeLabel(ccTattooDate, ccTattooTime) : '—';
          durationLabel = formatDurationLabel(tattooDurationMinutes + consultDurationMinutes) + ' total';
          $('#payDateTimeLabel').text('Tattoo Date & Time');
          $('#payConsultDateTime').text(consultDateTime);
          $('#payConsultRow').removeClass('hidden');
        }
      } else {
        mainDateTime = (selectedDate && selectedTime) ? formatDateTimeLabel(selectedDate, selectedTime) : '—';
        $('#payDateTimeLabel').text('Date & Time');
        $('#payConsultRow').addClass('hidden');
      }

      var placement = getAnswerByKeywords(['placement', 'body part', 'where']);
      var requestedSize = getAnswerByKeywords(['size', 'cm', 'inch']);
      var sizeLabel = requestedSize || ((parseInt(@json($tattoo->min_size ?? 0), 10) || 0) + ' - ' + (parseInt(@json($tattoo->max_size ?? 0), 10) || 0) + ' cm');
      var locationLabel = studioAddress ? (studioName + ', ' + studioAddress) : studioName;

      $('#payDesign').text(@json($tattoo->title ?? '—'));
      $('#payArtist').text(artistName);
      $('#payDateTime').text(mainDateTime);
      $('#payDuration').text(durationLabel);
      $('#payPlacement').text(placement || 'To be confirmed');
      $('#paySize').text(sizeLabel);
      $('#payLocation').text(locationLabel || '—');
      $('#payPriceEstimate').text(formatEUR(minPrice) + ' - ' + formatEUR(maxPrice));
      $('#payDepositLabel').text(getDepositLabel());
      $('#payDeposit').text(formatEUR(deposit));
      $('#payBookingFee').text(formatEUR(getBookingFee()));
      $('#payTotal').text(formatEUR(total));
      $('#payBalance').text(balanceLabel);
      $('#btnPayTotalAmount').text(formatEUR(total));
      $('#confPriceEstimate').text(formatEUR(minPrice) + ' - ' + formatEUR(maxPrice));
      $('#confDepositLabel').text(getDepositLabel());
      $('#confDeposit').text(formatEUR(deposit));
      $('#confBookingFee').text(formatEUR(getBookingFee()));
      $('#confTotalPaid').text(formatEUR(total));
      $('#confBalance').text(balanceLabel);
      $('#confDesign').text(@json($tattoo->title ?? '—'));
      $('#confArtist').text(artistName);
      $('#confDateTime').text(mainDateTime);
      $('#confPlacement').text(placement || 'To be confirmed');
      $('#confSize').text(sizeLabel);
      $('#confStudio').text(studioName);
      $('#confLocationName').text(studioName || '—');
      $('#confLocationAddress').text(studioAddress || '—');
      if (mapsLink) {
        $('#confDirectionsLink').attr('href', mapsLink).removeClass('pointer-events-none opacity-50');
      } else {
        $('#confDirectionsLink').attr('href', '#').addClass('pointer-events-none opacity-50');
      }
      $('#payManagedArtist, #confManagedArtist').text(artistName);

      if (consultationRequired) {
        if (consultationTiming === 'separate') {
          $('#confDateTimeLabel').text('Tattoo Date & Time');
          $('#confConsultDateTime').text(consultDateTime);
          $('#confConsultRow').removeClass('hidden');
        } else {
          $('#confDateTimeLabel').text('Date & Time');
          $('#confConsultRow').addClass('hidden');
        }
      } else {
        $('#confDateTimeLabel').text('Date & Time');
        $('#confConsultRow').addClass('hidden');
      }
    }

    function mountStripeElements() {
      if (!stripePublishableKey || isStripeMounted || typeof Stripe === 'undefined') return;

      stripe = Stripe(stripePublishableKey);
      stripeElements = stripe.elements();
      var baseStyle = {
        base: {
          color: '#1c1b21',
          fontFamily: 'Plus Jakarta Sans, system-ui, sans-serif',
          fontSize: '14px',
          '::placeholder': { color: '#7a7583' }
        },
        invalid: { color: '#ba1a1a' }
      };

      stripeCardNumber = stripeElements.create('cardNumber', { style: baseStyle });
      stripeCardExpiry = stripeElements.create('cardExpiry', { style: baseStyle });
      stripeCardCvc = stripeElements.create('cardCvc', { style: baseStyle });

      stripeCardNumber.mount('#stripeCardNumber');
      stripeCardExpiry.mount('#stripeCardExpiry');
      stripeCardCvc.mount('#stripeCardCvc');
      isStripeMounted = true;

      stripeCardNumber.on('change', function(event) {
        stripeCardComplete.number = !!event.complete;
        highlightCardBrand(event.brand || '');
        if (event.error) $('#formError').removeClass('hidden').text(event.error.message);
        else $('#formError').addClass('hidden').text('');
        checkPayReady();
      });
      stripeCardExpiry.on('change', function(event) {
        stripeCardComplete.expiry = !!event.complete;
        if (event.error) $('#formError').removeClass('hidden').text(event.error.message);
        else $('#formError').addClass('hidden').text('');
        checkPayReady();
      });
      stripeCardCvc.on('change', function(event) {
        stripeCardComplete.cvc = !!event.complete;
        if (event.error) $('#formError').removeClass('hidden').text(event.error.message);
        else $('#formError').addClass('hidden').text('');
        checkPayReady();
      });
    }

    function highlightCardBrand(brand) {
      $('#iconVisa, #iconMC, #iconAmex').removeClass('active');
      if (brand === 'visa') $('#iconVisa').addClass('active');
      if (brand === 'mastercard') $('#iconMC').addClass('active');
      if (brand === 'amex') $('#iconAmex').addClass('active');
    }

    function isPaymentCardReady() {
      return !!(stripeCardComplete.number && stripeCardComplete.expiry && stripeCardComplete.cvc);
    }

    function checkPayReady() {
      var agreed = $('#agreePolicy').is(':checked');
      var hasCardName = String($('#inputCardName').val() || '').trim().length > 1;
      var ready = agreed && hasCardName && isPaymentCardReady();
      $('#btnConfirmPay').prop('disabled', !ready);
    }
    window.checkPayReady = checkPayReady;

    function toggleCancellationPolicy() {
      var $content = $('#cancellationPolicyContent');
      var $arrow = $('#cancPolicyArrow');
      var isOpen = !$content.hasClass('hidden');
      if (isOpen) {
        $content.addClass('hidden');
        $arrow.css('transform', 'rotate(0deg)');
      } else {
        $content.removeClass('hidden');
        $arrow.css('transform', 'rotate(180deg)');
      }
    }
    window.toggleCancellationPolicy = toggleCancellationPolicy;

    function expandCancellationPolicy() {
      if ($('#cancellationPolicyContent').hasClass('hidden')) {
        toggleCancellationPolicy();
      }
      var el = document.getElementById('cancellationPolicySection');
      if (el) el.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
    window.expandCancellationPolicy = expandCancellationPolicy;

    async function createBookingPaymentIntent() {
      var response = await fetch('/api/public/create-booking-payment-intent', {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({
          artist_username: bookingArtistUsername,
          tattoo_slug: bookingTattooSlug,
          cardholder_name: String($('#inputCardName').val() || '').trim()
        })
      });
      var data = await response.json();
      if (!response.ok || !data || !data.client_secret) {
        throw new Error((data && data.message) || 'Unable to initialize payment.');
      }
      return data.client_secret;
    }

    function formatDateToIso(dateObj) {
      if (!(dateObj instanceof Date)) return '';
      // Match slot / blocked-day logic (artist timezone calendar day).
      return formatYmdArtistLocal(dateObj);
    }

    function buildStructuredQuestionAnswers() {
      var defs = Array.isArray(questionDefinitions) ? questionDefinitions : [];
      var output = {};
      defs.forEach(function(q) {
        if (!q || typeof q.id === 'undefined' || q.id === null) return;
        var qId = String(q.id);
        var rawAnswer = questionAnswers[q.id];
        var answer = rawAnswer;
        if (typeof answer === 'string') answer = answer.trim();
        if (typeof answer === 'undefined' || answer === null || answer === '') return;
        output[qId] = {
          id: q.id,
          question: String(q.title || ''),
          type: String(q.type || 'input'),
          answer: answer,
        };
      });
      return output;
    }

    function buildBookingPayload() {
      var isConsultRequired = !!consultationRequired;
      var timing = consultationTiming === 'separate' ? 'separate' : 'combined';
      var payload = {
        email: String(bookingConnectedEmail || $('#bdEmail').val() || '').trim(),
        phone: String($('#bdPhone').val() || '').trim(),
        name: String($('#bdName').val() || '').trim(),
        consultation_required: isConsultRequired,
        consultation_timing: timing,
        consult_duration_minutes: parseInt(consultDurationMinutes || 30, 10) || 30,
        tattoo_duration_minutes: parseInt(tattooDurationMinutes || 120, 10) || 120,
        questions_answers: buildStructuredQuestionAnswers(),
        notes: [
          getAnswerByKeywords(['placement', 'body part', 'where']),
          getAnswerByKeywords(['size', 'cm', 'inch'])
        ].filter(Boolean).join(' | ')
      };

      if (isConsultRequired) {
        if (timing === 'separate') {
          payload.consultation_date = formatDateToIso(ccConsultDate);
          payload.consultation_time = String(ccConsultTime || '');
          payload.tattoo_date = formatDateToIso(ccTattooDate);
          payload.tattoo_time = String(ccTattooTime || '');
        } else {
          payload.date = formatDateToIso(ccConsultDate);
          payload.time = String(ccConsultTime || '');
        }
      } else {
        payload.date = formatDateToIso(selectedDate);
        payload.time = String(selectedTime || '');
      }

      return payload;
    }

    async function persistBookingRecord(paymentIntentId) {
      var response = await fetch('/api/public/confirm-booking-payment', {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({
          artist_username: bookingArtistUsername,
          tattoo_slug: bookingTattooSlug,
          payment_intent_id: paymentIntentId,
          booking_payload: buildBookingPayload()
        })
      });
      var data = await response.json();
      if (!response.ok || !data || !data.saved) {
        throw new Error((data && data.message) || 'Payment succeeded but booking could not be saved.');
      }
      return data;
    }

    async function uploadQuestionImage(file, questionId) {
      var formData = new FormData();
      formData.append('image', file);
      formData.append('question_id', String(questionId || ''));
      formData.append('artist_username', bookingArtistUsername);
      formData.append('tattoo_slug', bookingTattooSlug);

      var response = await fetch('/api/public/upload-booking-question-image', {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'X-CSRF-TOKEN': csrfToken
        },
        body: formData
      });
      var data = await response.json();
      if (!response.ok || !data || !data.success || !data.file_url) {
        throw new Error((data && data.message) || 'Unable to upload image.');
      }
      return data.file_url;
    }

    async function confirmBooking() {
      $('#formError').addClass('hidden').text('');
      if (!$('#paymentManagedMode').hasClass('hidden')) {
        goToStep(5);
        $('#processingView').addClass('hidden');
        $('#confirmationCalendar').addClass('hidden');
        $('#confirmationManaged').removeClass('hidden');
        return;
      }
      if (!$('#agreePolicy').is(':checked')) {
        $('#formError').removeClass('hidden').text('Please accept the cancellation policy and terms.');
        return;
      }
      var cardholderName = String($('#inputCardName').val() || '').trim();
      if (!cardholderName) {
        $('#formError').removeClass('hidden').text('Please enter cardholder name.');
        return;
      }
      if (!isPaymentCardReady()) {
        $('#formError').removeClass('hidden').text('Please complete card details.');
        return;
      }

      $('#btnConfirmPay').prop('disabled', true).text('Processing...');
      goToStep(5);
      $('#processingView').removeClass('hidden');
      $('#confirmationCalendar, #confirmationManaged').addClass('hidden');
      $('#processingText').text('Processing your payment...');

      try {
        var clientSecret = await createBookingPaymentIntent();
        var confirmResult = await stripe.confirmCardPayment(clientSecret, {
          payment_method: {
            card: stripeCardNumber,
            billing_details: { name: cardholderName }
          }
        });
        if (confirmResult.error) {
          throw new Error(confirmResult.error.message || 'Payment failed.');
        }
        if (!confirmResult.paymentIntent || confirmResult.paymentIntent.status !== 'succeeded') {
          throw new Error('Payment was not completed. Please try again.');
        }

        var savedBooking = await persistBookingRecord(confirmResult.paymentIntent.id);
        $('#confRef').text(savedBooking.booking_reference || ('#INK-' + String(confirmResult.paymentIntent.id || '').replace('pi_', '').slice(-6).toUpperCase()));
        $('#processingView').addClass('hidden');
        $('#confirmationCalendar').removeClass('hidden');
      } catch (error) {
        goToStep(4);
        $('#formError').removeClass('hidden').text(error.message || 'Payment failed. Please try again.');
      } finally {
        $('#btnConfirmPay').prop('disabled', false).html('Confirm & Pay <span id="btnPayTotalAmount">' + formatEUR(getDueNow()) + '</span>');
        checkPayReady();
      }
    }
    window.confirmBooking = confirmBooking;

    function renderQuestions() {
      var html = '';

      questionDefinitions.forEach(function(q, idx) {
        var isFirst = idx === 0;
        var isLast = idx === questionDefinitions.length - 1;
        var body = '';

        if (q.type === 'radio') {
          var radioButtons = q.options.map(function(opt) {
            return '<button class="single-choice-radio-button" data-value="' + opt + '">' + opt + '</button>';
          }).join('');
          body = '<div class="flex flex-wrap gap-2 single-choice-group">' + radioButtons + '</div>';
        } else if (q.type === 'select') {
          var selectOptions = '<option value="">Choose an option</option>' + q.options.map(function(opt) {
            return '<option value="' + opt + '">' + opt + '</option>';
          }).join('');
          body = '<select class="w-full js-select2-question" data-question-id="' + q.id + '">' + selectOptions + '</select>';
        } else if (q.type === 'input') {
          body = '<input type="text" placeholder="' + q.placeholder + '" data-question-id="' + q.id + '" class="js-question-input w-full border border-outline-variant/30 bg-white rounded-2xl px-6 py-4 text-lg text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">';
        } else if (q.type === 'textarea') {
          body = '<textarea rows="4" placeholder="' + q.placeholder + '" data-question-id="' + q.id + '" class="js-question-input w-full border border-outline-variant/30 bg-white rounded-2xl px-6 py-4 text-lg text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30 resize-none"></textarea>';
        } else if (q.type === 'image') {
          body = '<div class="border-2 border-dashed border-outline-variant/40 rounded-2xl p-6 bg-white"><input type="file" accept="image/*" data-question-id="' + q.id + '" class="dropify js-question-file" data-allowed-file-extensions="jpg jpeg png webp" data-max-file-size="5M" data-show-remove="true"></div>';
        } else if (q.type === 'toggle') {
          body = '<label class="q-toggle-row"><span class="q-toggle-control"><input type="checkbox" data-question-id="' + q.id + '" class="q-toggle-input js-question-toggle"><span class="q-toggle-ui"></span></span><span class="q-toggle-label">' + q.subtitle + '</span></label>';
        }

        var navButton = isLast
          ? '<button class="js-continue-scheduling inline-flex items-center gap-2 px-6 py-3 bg-primary text-on-primary rounded-full font-bold text-sm hover:bg-primary-container transition-colors">Continue to Scheduling <span class="material-symbols-outlined text-[18px]">arrow_forward</span></button>'
          : '<button class="js-next-question inline-flex items-center gap-2 px-6 py-3 bg-primary text-on-primary rounded-full font-bold text-sm hover:bg-primary-container transition-colors">Next <span class="material-symbols-outlined text-[18px]">arrow_forward</span></button>';

        html +=
          '<div class="question-div' + (isFirst ? ' active' : '') + '" data-q="' + idx + '" data-question-id="' + q.id + '" data-question-type="' + q.type + '" data-required="' + (q.required ? '1' : '0') + '">' +
            '<div class="w-full max-w-xl mx-auto">' +
              (isFirst ? '' : '<button class="js-prev-question flex items-center gap-1 text-sm text-on-surface-variant hover:text-primary mb-4 transition-colors"><span class="material-symbols-outlined text-[18px]">arrow_back</span> Back</button>') +
              '<p class="question-kicker"><span class="dot"></span>Question ' + (idx + 1) + ':</p>' +
              '<h2 class="text-2xl sm:text-3xl font-bold text-on-surface mb-2">' + q.title + '</h2>' +
              '<p class="text-on-surface-variant mb-6">' + q.subtitle + (q.required ? ' <span class="text-error">*</span>' : '') + '</p>' +
              body +
              '<p class="text-sm text-error hidden mt-3 js-question-error">Please answer this required question.</p>' +
              '<div class="flex items-center justify-end mt-6">' + navButton + '</div>' +
            '</div>' +
          '</div>';
      });

      $('#questionsMount').html(html);
    }

    function getCurrentQuestionDiv() {
      return $('div.question-div.active[data-q]');
    }

    function validateActiveQuestion() {
      var $active = getCurrentQuestionDiv();
      if (!$active.length) return true;

      var isRequired = String($active.data('required')) === '1';
      if (!isRequired) {
        $active.find('.js-question-error').addClass('hidden');
        return true;
      }

      var qType = String($active.data('question-type') || '');
      var qId = $active.data('question-id');
      var hasValue = false;

      if (qType === 'radio') {
        hasValue = !!$active.find('.single-choice-radio-button.selected').length;
      } else if (qType === 'select') {
        hasValue = !!String($active.find('.js-select2-question').val() || '').trim();
      } else if (qType === 'input' || qType === 'textarea') {
        hasValue = !!String($active.find('.js-question-input').val() || '').trim();
      } else if (qType === 'image') {
        hasValue = !!String(questionAnswers[qId] || '').trim();
      } else if (qType === 'toggle') {
        hasValue = $active.find('.js-question-toggle').is(':checked');
      } else {
        hasValue = !!questionAnswers[qId];
      }

      $active.find('.js-question-error').toggleClass('hidden', hasValue);
      return hasValue;
    }

    function goToStep(step) {
      if (step === 3 && currentStep === 2 && consultationRequired && !ccConsultType) {
        $('#ccConsultTypeError').removeClass('hidden');
        var typeSection = document.getElementById('ccTypeSection');
        if (typeSection) typeSection.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return;
      }

      currentStep = step;
      $('.step-panel').removeClass('active');

      if (step === 1) $('#stepQuestions').addClass('active');
      if (step === 2) {
        if (consultationRequired) {
          $('#step2CalendarConsult').addClass('active');
          if (consultationTiming === 'combined') {
            $('#ccTattooSection, #ccTattooChip, #ccBottomSummary').addClass('hidden');
          }
          renderCcConsultCal();
      } else {
          $('#step2Calendar').addClass('active');
          renderMainCal();
        }
      }
      if (step === 3) $('#stepRegister').addClass('active');
      if (step === 4) {
        $('#stepPayment').addClass('active');
        updatePaymentSummary();
      }
      if (step === 5) $('#stepConfirmation').addClass('active');
      updateProgressDots();
    }
    window.goToStep = goToStep;

    function updateProgressDots() {
      document.querySelectorAll('.progress-step').forEach(function(el) {
        var stepNum = parseInt(el.getAttribute('data-step') || '0', 10);
        el.classList.remove('active', 'completed');
        if (stepNum === currentStep) {
          el.classList.add('active');
        } else if (stepNum < currentStep) {
          el.classList.add('completed');
        }
      });
      document.querySelectorAll('.progress-line').forEach(function(el) {
        var lineNum = parseInt(el.getAttribute('data-line') || '0', 10);
        el.classList.toggle('completed', lineNum < currentStep);
      });
    }

    var monthNames = ['January','February','March','April','May','June','July','August','September','October','November','December'];
    var today = new Date();
    var todayStart = new Date(today.getFullYear(), today.getMonth(), today.getDate(), 0, 0, 0, 0);
    var calYear = today.getFullYear();
    var calMonth = today.getMonth();
    var selectedDate = null;
    var selectedTime = null;

    var artistAvailabilitySchedule = @json($artistAvailabilitySchedule ?? []);
    var artistTimezone = @json($artistTimezone ?? 'UTC');
    var artistBlockedPeriods = @json($artistBlockedPeriods ?? []);
    var artistBusyIntervalsByDate = @json($artistBusyIntervalsByDate ?? []);
    var tattooDurationMinutes = parseInt(@json($tattooDurationMinutes ?? 120), 10) || 120;
    var artistConsultationSettings = @json($artistConsultationSettings ?? null) || {};
    var consultationRequired = !!artistConsultationSettings.required;
    var consultationTiming = String(artistConsultationSettings.timing || 'combined').trim().toLowerCase();
    if (consultationTiming !== 'separate' && consultationTiming !== 'combined') {
      consultationTiming = 'combined';
    }
    var consultationSessionType = String(artistConsultationSettings.session_type || 'both').trim().toLowerCase();
    if (consultationSessionType !== 'online' && consultationSessionType !== 'physical' && consultationSessionType !== 'both') {
      consultationSessionType = 'both';
    }
    var requireConsultGap = !!artistConsultationSettings.require_gap;
    // Gap is handled as day-based for separate consultation flow.
    var consultGapValue = parseInt(artistConsultationSettings.gap_value || 0, 10) || 0;
    var consultGapUnit = 'days';
    var consultDurationMinutes = parseInt(artistConsultationSettings.session_duration_minutes || 30, 10) || 30;
    var weekdayKeys = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
    var ccConsultDate = null;
    var ccConsultTime = null;
    var ccTattooDate = null;
    var ccTattooTime = null;
    var ccConsultType = null;
    var ccCalYear = today.getFullYear();
    var ccCalMonth = today.getMonth();
    var ccTatCalYear = today.getFullYear();
    var ccTatCalMonth = today.getMonth();

    function formatTo12Hour(hour, minute) {
      var suffix = hour >= 12 ? 'PM' : 'AM';
      var h = hour % 12;
      if (h === 0) h = 12;
      var mm = String(minute).padStart(2, '0');
      return h + ':' + mm + ' ' + suffix;
    }

    function formatYmdArtistLocal(dateObj) {
      if (!(dateObj instanceof Date)) return '';
      try {
        return new Intl.DateTimeFormat('en-CA', { timeZone: artistTimezone, year: 'numeric', month: '2-digit', day: '2-digit' }).format(dateObj);
      } catch (e) {
        var y = dateObj.getFullYear();
        var m = String(dateObj.getMonth() + 1).padStart(2, '0');
        var d = String(dateObj.getDate()).padStart(2, '0');
        return y + '-' + m + '-' + d;
      }
    }

    function isArtistDateBlocked(ymd) {
      if (!ymd || !Array.isArray(artistBlockedPeriods) || !artistBlockedPeriods.length) return false;
      for (var i = 0; i < artistBlockedPeriods.length; i++) {
        var p = artistBlockedPeriods[i];
        if (!p) continue;
        var s = String(p.start_date || '');
        var e = String(p.end_date || '');
        if (ymd >= s && ymd <= e) return true;
      }
      return false;
    }

    function slotOverlapsExistingBooking(ymd, slotStartMin, requiredMinutes) {
      var slotEndMin = slotStartMin + requiredMinutes;
      var list = artistBusyIntervalsByDate[ymd];
      if (!Array.isArray(list) || !list.length) return false;
      for (var i = 0; i < list.length; i++) {
        var b = list[i];
        var bs = parseInt(b.start, 10);
        var be = parseInt(b.end, 10);
        if (isNaN(bs) || isNaN(be)) continue;
        if (slotStartMin < be && slotEndMin > bs) return true;
      }
      return false;
    }

    function canNavigateToMonth(year, month) {
      var firstOfTarget = new Date(year, month, 1, 0, 0, 0, 0);
      var firstOfCurrent = new Date(todayStart.getFullYear(), todayStart.getMonth(), 1, 0, 0, 0, 0);
      return firstOfTarget >= firstOfCurrent;
    }

    function buildSlotsFromRanges(ranges, requiredMinutes) {
      var slots = [];
      if (!Array.isArray(ranges)) return slots;
      var minRequired = Math.max(0, parseInt(requiredMinutes || 0, 10) || 0);

      ranges.forEach(function(range) {
        if (!range || !range.start || !range.end) return;
        var startParts = String(range.start).split(':');
        var endParts = String(range.end).split(':');
        var startMinutes = (parseInt(startParts[0] || '0', 10) * 60) + parseInt(startParts[1] || '0', 10);
        var endMinutes = (parseInt(endParts[0] || '0', 10) * 60) + parseInt(endParts[1] || '0', 10);
        if (isNaN(startMinutes) || isNaN(endMinutes) || endMinutes <= startMinutes) return;

        for (var m = startMinutes; m < endMinutes; m += 30) {
          if (m + minRequired > endMinutes) break;
          var hour = Math.floor(m / 60);
          var minute = m % 60;
          slots.push({
            time: formatTo12Hour(hour, minute),
            booked: false
          });
        }
      });

      return slots;
    }

    function getSlotsForDate(dateObj, requiredMinutes) {
      if (!(dateObj instanceof Date)) return [];
      var dayStart = new Date(dateObj.getFullYear(), dateObj.getMonth(), dateObj.getDate(), 0, 0, 0, 0);
      if (dayStart < todayStart) return [];

      var ymdArtist = formatYmdArtistLocal(dateObj);
      if (ymdArtist && isArtistDateBlocked(ymdArtist)) {
        return [];
      }

      var weekdayKey = weekdayKeys[dateObj.getDay()];
      var dayRanges = artistAvailabilitySchedule[weekdayKey];
      if (!Array.isArray(dayRanges) || !dayRanges.length) return [];
      var slots = buildSlotsFromRanges(dayRanges, requiredMinutes);

      // For today, only show slots strictly after current time.
      if (dayStart.getTime() === todayStart.getTime()) {
        var now = new Date();
        var nowMinutes = now.getHours() * 60 + now.getMinutes();
        slots = slots.filter(function(slot) {
          return parseTime12hToMinutes(slot.time) > nowMinutes;
        });
      }

      var minRequired = Math.max(0, parseInt(requiredMinutes || 0, 10) || 0);
      if (ymdArtist && minRequired > 0) {
        slots = slots.filter(function(slot) {
          var sm = parseTime12hToMinutes(slot.time);
          return !slotOverlapsExistingBooking(ymdArtist, sm, minRequired);
        });
      }

      return slots;
    }

    /** Slots that could exist from weekly hours only (ignores existing bookings). Same “today” filter as getSlotsForDate. */
    function getHypotheticalSlotsForDate(dateObj, requiredMinutes) {
      if (!(dateObj instanceof Date)) return [];
      var dayStart = new Date(dateObj.getFullYear(), dateObj.getMonth(), dateObj.getDate(), 0, 0, 0, 0);
      if (dayStart < todayStart) return [];

      var ymdArtist = formatYmdArtistLocal(dateObj);
      if (ymdArtist && isArtistDateBlocked(ymdArtist)) {
        return [];
      }

      var weekdayKey = weekdayKeys[dateObj.getDay()];
      var dayRanges = artistAvailabilitySchedule[weekdayKey];
      if (!Array.isArray(dayRanges) || !dayRanges.length) return [];
      var slots = buildSlotsFromRanges(dayRanges, requiredMinutes);

      if (dayStart.getTime() === todayStart.getTime()) {
        var now = new Date();
        var nowMinutes = now.getHours() * 60 + now.getMinutes();
        slots = slots.filter(function(slot) {
          return parseTime12hToMinutes(slot.time) > nowMinutes;
        });
      }

      return slots;
    }

    /** Weekly hours allow at least one slot, but none remain after bookings + buffer. */
    function isDateFullyBookedOut(dateObj, requiredMinutes) {
      if (!(dateObj instanceof Date)) return false;
      var dayStart = new Date(dateObj.getFullYear(), dateObj.getMonth(), dateObj.getDate(), 0, 0, 0, 0);
      if (dayStart < todayStart) return false;
      var ymd = formatYmdArtistLocal(dateObj);
      if (ymd && isArtistDateBlocked(ymd)) return false;
      var hypo = getHypotheticalSlotsForDate(dateObj, requiredMinutes);
      if (!hypo.length) return false;
      return getSlotsForDate(dateObj, requiredMinutes).length === 0;
    }

    function getMainRequiredMinutes() {
      if (!consultationRequired) return tattooDurationMinutes;
      if (consultationTiming === 'combined') return tattooDurationMinutes + consultDurationMinutes;
      return tattooDurationMinutes;
    }

    function getConsultSelectionRequiredMinutes() {
      if (consultationTiming === 'combined') return tattooDurationMinutes + consultDurationMinutes;
      return consultDurationMinutes;
    }

    function renderMainCal() {
      var grid = document.getElementById('calGrid');
      var label = document.getElementById('calMonth');
      if (!grid || !label) return;

      grid.innerHTML = '';
      label.textContent = monthNames[calMonth] + ' ' + calYear;
      var prevBtn = document.getElementById('calPrev');
      if (prevBtn) {
        var allowPrev = canNavigateToMonth(calYear, calMonth - 1);
        prevBtn.disabled = !allowPrev;
        prevBtn.classList.toggle('opacity-40', !allowPrev);
        prevBtn.classList.toggle('cursor-not-allowed', !allowPrev);
      }

      var firstDay = new Date(calYear, calMonth, 1).getDay();
      var startOffset = (firstDay + 6) % 7;
      var daysInMonth = new Date(calYear, calMonth + 1, 0).getDate();

      for (var i = 0; i < startOffset; i++) {
        var empty = document.createElement('div');
        empty.className = 'cal-day empty';
        grid.appendChild(empty);
      }

      for (var d = 1; d <= daysInMonth; d++) {
        (function(day) {
          var dt = new Date(calYear, calMonth, day);
          var div = document.createElement('div');
          div.textContent = day;

          var ymdCell = formatYmdArtistLocal(dt);
          var isBlockedDay = !!(ymdCell && isArtistDateBlocked(ymdCell));
          var reqMinMain = getMainRequiredMinutes();
          var isAvail = getSlotsForDate(dt, reqMinMain).length > 0;
          var isFullyBooked = !isAvail && !isBlockedDay && (isFuture || isToday) && isDateFullyBookedOut(dt, reqMinMain);
          var isSel = selectedDate && dt.toDateString() === selectedDate.toDateString();
          var isToday = dt.toDateString() === today.toDateString();
          var isFuture = dt > today;

          var cls = 'cal-day';
          if (isSel) cls += ' selected';
          else if (isAvail) cls += ' available';
          else if (isBlockedDay && (isFuture || isToday)) {
            cls += ' blocked-by-artist';
            div.title = 'Artist unavailable';
          } else if (isFullyBooked) {
            cls += ' fully-booked-day';
            div.title = 'Fully booked';
          } else if (isFuture || isToday) cls += ' unavailable-future';
          else cls += ' unavailable';
          if (isToday && !isSel) cls += ' today';
          div.className = cls;

          if (isAvail) {
            div.addEventListener('click', function() {
              selectedDate = dt;
              selectedTime = null;
              renderMainCal();
              showMainTimeSlots();
            });
          }
          grid.appendChild(div);
        })(d);
      }
    }

    function showMainTimeSlots() {
      if (!selectedDate) return;
      $('#timeSlotsEmpty').addClass('hidden');
      $('#timeSlotsContent').removeClass('hidden');
      $('#selectedDateLabel').text(selectedDate.toLocaleDateString('en-GB', { weekday: 'long', day: 'numeric', month: 'long' }));

      var slots = getSlotsForDate(selectedDate, getMainRequiredMinutes());
      var html = '';
      slots.forEach(function(slot) {
        var bookedClass = slot.booked ? ' booked' : '';
        var bookedText = slot.booked ? ' — Booked' : '';
        if (slot.booked) {
          html += '<button class="time-slot-card w-full' + bookedClass + '" data-time="' + slot.time + '" disabled>' + slot.time + bookedText + '</button>';
          } else {
          html += '<div class="time-slot-wrap">' +
            '<button class="time-slot-card flex-1" data-time="' + slot.time + '">' + slot.time + '</button>' +
            '<button class="time-slot-confirm js-time-slot-continue">Continue <span class="material-symbols-outlined text-[16px]">arrow_forward</span></button>' +
          '</div>';
        }
      });
      $('#timeSlots').html(html);
      $('#confirmBar').addClass('hidden');
    }

    function parseTime12hToMinutes(timeLabel) {
      var match = String(timeLabel || '').trim().match(/^(\d{1,2}):(\d{2})\s*(AM|PM)$/i);
      if (!match) return 0;
      var h = parseInt(match[1], 10);
      var m = parseInt(match[2], 10);
      var meridiem = match[3].toUpperCase();
      if (meridiem === 'PM' && h !== 12) h += 12;
      if (meridiem === 'AM' && h === 12) h = 0;
      return h * 60 + m;
    }

    function buildDateTime(dateObj, timeLabel) {
      var mins = parseTime12hToMinutes(timeLabel);
      var dt = new Date(dateObj.getFullYear(), dateObj.getMonth(), dateObj.getDate(), 0, 0, 0, 0);
      dt.setMinutes(mins);
      return dt;
    }

    function addGapToDateTime(dateObj, value, unit) {
      var dt = new Date(dateObj.getTime());
      if (!value || value <= 0) return dt;
      if (unit === 'minutes') dt.setMinutes(dt.getMinutes() + value);
      else if (unit === 'hours') dt.setHours(dt.getHours() + value);
      else if (unit === 'days') dt.setDate(dt.getDate() + value);
      return dt;
    }

    function renderCalendarInto(gridId, labelId, year, month, selectedDateObj, minDateObj, clickCb, requiredMinutes) {
      var grid = document.getElementById(gridId);
      var label = document.getElementById(labelId);
      if (!grid || !label) return;
      grid.innerHTML = '';
      label.textContent = monthNames[month] + ' ' + year;

      var firstDay = new Date(year, month, 1).getDay();
      var startOffset = (firstDay + 6) % 7;
      var daysInMonth = new Date(year, month + 1, 0).getDate();
      for (var i = 0; i < startOffset; i++) {
        var empty = document.createElement('div');
        empty.className = 'cal-day empty';
        grid.appendChild(empty);
      }

      var minDay = null;
      if (minDateObj instanceof Date) {
        minDay = new Date(minDateObj.getFullYear(), minDateObj.getMonth(), minDateObj.getDate(), 0, 0, 0, 0);
      }

      for (var d = 1; d <= daysInMonth; d++) {
        (function(day) {
          var dt = new Date(year, month, day);
          var div = document.createElement('div');
          div.textContent = day;
          var ymdCell = formatYmdArtistLocal(dt);
          var isBlockedDay = !!(ymdCell && isArtistDateBlocked(ymdCell));
          var isSel = selectedDateObj && dt.toDateString() === selectedDateObj.toDateString();
          var isToday = dt.toDateString() === today.toDateString();
          var isFuture = dt > today;
          var isBeforeMin = minDay && dt < minDay;
          var isAvail = getSlotsForDate(dt, requiredMinutes).length > 0;
          var isFullyBooked = !isBeforeMin && !isAvail && !isBlockedDay && (isFuture || isToday) && isDateFullyBookedOut(dt, requiredMinutes);

          var cls = 'cal-day';
        if (isSel) cls += ' selected';
          else if (isAvail && !isBeforeMin) cls += ' available';
          else if (!isBeforeMin && isBlockedDay && (isFuture || isToday)) {
            cls += ' blocked-by-artist';
            div.title = 'Artist unavailable';
          } else if (isFullyBooked) {
            cls += ' fully-booked-day';
            div.title = 'Fully booked';
          } else if (isFuture || isToday) cls += ' unavailable-future';
        else cls += ' unavailable';
        if (isToday && !isSel) cls += ' today';
        div.className = cls;

          if (isAvail && !isBeforeMin) {
            div.addEventListener('click', function() { clickCb(dt); });
          }
        grid.appendChild(div);
        })(d);
      }
    }

    function renderCcConsultCal() {
      renderCalendarInto('ccCalGrid', 'ccCalMonth', ccCalYear, ccCalMonth, ccConsultDate, null, function(dt) {
        ccConsultDate = dt;
        ccConsultTime = null;
        ccTattooDate = null;
        ccTattooTime = null;
        $('#ccTattooChip, #ccBottomSummary').addClass('hidden');
        renderCcConsultCal();
        showCcConsultSlots();
      }, getConsultSelectionRequiredMinutes());
    }

    function showCcConsultSlots() {
      if (!ccConsultDate) return;
      $('#ccTimeSlotsEmpty').addClass('hidden');
      $('#ccTimeSlotsContent').removeClass('hidden');
      $('#ccSelectedDateLabel').text(ccConsultDate.toLocaleDateString('en-GB', { weekday: 'long', day: 'numeric', month: 'long' }));

      var slots = getSlotsForDate(ccConsultDate, getConsultSelectionRequiredMinutes());
      var html = '';
      slots.forEach(function(slot) {
        if (slot.booked) return;
        html += '<button class="time-slot-card w-full js-cc-consult-slot" data-time="' + slot.time + '">' + slot.time + '</button>';
      });
      $('#ccTimeSlots').html(html);
    }

    function getTattooMinDateTime() {
      if (!ccConsultDate || !ccConsultTime) return null;
      // Separate mode: disable consultation day plus full gap days after it.
      // Example: consultation 1-May, gap=2 => earliest tattoo date is 4-May.
      if (consultationTiming === 'separate') {
        var gapDays = Math.max(0, consultGapValue);
        var minDate = new Date(ccConsultDate.getFullYear(), ccConsultDate.getMonth(), ccConsultDate.getDate(), 0, 0, 0, 0);
        minDate.setDate(minDate.getDate() + gapDays + 1);
        return minDate;
      }

      var base = buildDateTime(ccConsultDate, ccConsultTime);
      if (requireConsultGap && consultGapValue > 0) {
        return addGapToDateTime(base, consultGapValue, consultGapUnit);
      }
      return base;
    }

    function renderCcTattooCal() {
      var minDt = getTattooMinDateTime();
      renderCalendarInto('ccTatCalGrid', 'ccTatCalMonth', ccTatCalYear, ccTatCalMonth, ccTattooDate, minDt, function(dt) {
        ccTattooDate = dt;
        ccTattooTime = null;
        renderCcTattooCal();
        showCcTattooSlots();
      }, tattooDurationMinutes);
    }

    function showCcTattooSlots() {
      if (!ccTattooDate) return;
      $('#ccTatTimeSlotsEmpty').addClass('hidden');
      $('#ccTatTimeSlotsContent').removeClass('hidden');
      $('#ccTatSelectedDateLabel').text(ccTattooDate.toLocaleDateString('en-GB', { weekday: 'long', day: 'numeric', month: 'long' }));

      var minDt = getTattooMinDateTime();
      var slots = getSlotsForDate(ccTattooDate, tattooDurationMinutes);
      var html = '';
      slots.forEach(function(slot) {
        if (slot.booked) return;
        var slotDt = buildDateTime(ccTattooDate, slot.time);
        if (minDt && slotDt < minDt) return;
        html += '<button class="time-slot-card w-full js-cc-tattoo-slot" data-time="' + slot.time + '">' + slot.time + '</button>';
      });
      $('#ccTatTimeSlots').html(html);
    }

    function showQuestion(index) {
      var questions = $('div.question-div[data-q]');
      if (!questions.length) return;

      if (index < 0) index = 0;
      if (index >= questions.length) index = questions.length - 1;

      questions.removeClass('active');
      questions.filter('[data-q="' + index + '"]').addClass('active');
      currentQuestionIndex = index;
    }

    function moveQuestion(step) {
      var nextIndex = currentQuestionIndex + step;
      var questions = $('div.question-div[data-q]');

      if (nextIndex < 0) nextIndex = 0;
      if (nextIndex >= questions.length) {
        goToStep(2);
        return;
      }

      showQuestion(nextIndex);
    }

    function nextQuestion(current_index) {
      if (!isNaN(current_index)) {
        currentQuestionIndex = current_index;
      } else {
        var activeIndex = parseInt($('div.question-div.active[data-q]').data('q'), 10);
        if (!isNaN(activeIndex)) currentQuestionIndex = activeIndex;
      }
      if (!validateActiveQuestion()) return;
      moveQuestion(1);
    }
    window.nextQuestion = nextQuestion;

    function prevQuestion() {
      var activeIndex = parseInt($('div.question-div.active[data-q]').data('q'), 10);
      if (!isNaN(activeIndex)) currentQuestionIndex = activeIndex;
      moveQuestion(-1);
    }
    window.prevQuestion = prevQuestion;

    function showReg(index) {
      var regs = $('div.question-div[data-reg]');
      if (!regs.length) return;

      if (index < 0) index = 0;
      if (index >= regs.length) index = regs.length - 1;

      regs.removeClass('active');
      regs.filter('[data-reg="' + index + '"]').addClass('active');
      currentRegIndex = index;

      if (index === 3) {
        var currentEmail = String($('#bdEmail').val() || '').trim();
        if (currentEmail && !$('#bdOtpEmail').val().trim()) {
          $('#bdOtpEmail').val(currentEmail);
        }
        updateConnectedUi();
      }
    }

    function clearRegError(inputId, errorId) {
      $('#' + inputId).removeClass('border-error ring-1 ring-error/40');
      $('#' + errorId).addClass('hidden').text('This field is required.');
    }

    function setRegError(inputId, errorId, message) {
      $('#' + inputId).addClass('border-error ring-1 ring-error/40');
      $('#' + errorId).removeClass('hidden').text(message);
    }

    function isValidEmail(email) {
      return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(email || '').trim());
    }

    function isValidPhoneWithCountryCode(phone) {
      return /^\+[0-9][0-9\s\-()]{5,}$/.test(String(phone || '').trim());
    }

    async function validateBookingEmailRole(email) {
      var res = await fetch('/api/public/check-email-availability?email=' + encodeURIComponent(email), {
        method: 'GET',
        headers: { 'Accept': 'application/json' }
      });

      if (!res.ok) {
        throw new Error('Unable to validate email right now. Please try again.');
      }

      var data = await res.json();
      // Allowed when email does not exist OR exists with role user.
      if (typeof data.allowed === 'boolean') return data.allowed;
      if (!data.exists) return true;
      return !!data.is_user;
    }

    async function nextReg() {
      var regs = $('div.question-div[data-reg]');
      var activeIndex = parseInt($('div.question-div.active[data-reg]').data('reg'), 10);
      if (!isNaN(activeIndex)) currentRegIndex = activeIndex;

      clearRegError('bdName', 'bdNameError');
      clearRegError('bdEmail', 'bdEmailError');
      clearRegError('bdPhone', 'bdPhoneError');

      if (currentRegIndex === 0) {
        var nameVal = $('#bdName').val().trim();
        if (!nameVal) {
          setRegError('bdName', 'bdNameError', 'This field is required.');
          return;
        }
      }

      if (currentRegIndex === 1) {
        var emailVal = $('#bdEmail').val().trim();
        if (!emailVal) {
          setRegError('bdEmail', 'bdEmailError', 'This field is required.');
          return;
        }
        if (!isValidEmail(emailVal)) {
          setRegError('bdEmail', 'bdEmailError', 'Please enter a valid email address.');
          return;
        }
        try {
          var allowed = await validateBookingEmailRole(emailVal);
          if (!allowed) {
            setRegError('bdEmail', 'bdEmailError', 'Please use another email.');
            return;
          }
        } catch (err) {
          setRegError('bdEmail', 'bdEmailError', err.message || 'Unable to validate email right now. Please try again.');
          return;
        }
      }

      if (currentRegIndex === 2) {
        var phoneVal = $('#bdPhone').val().trim();
        if (!phoneVal) {
          setRegError('bdPhone', 'bdPhoneError', 'This field is required.');
          return;
        }
        if (!isValidPhoneWithCountryCode(phoneVal)) {
          setRegError('bdPhone', 'bdPhoneError', 'Phone must start with country code, e.g. +30 694 123 4567.');
          return;
        }
      }

      var nextIndex = currentRegIndex + 1;
      if (nextIndex >= regs.length) {
        goToStep(4);
        return;
      }
      showReg(nextIndex);
    }
    window.nextReg = nextReg;

    function prevReg() {
      var activeIndex = parseInt($('div.question-div.active[data-reg]').data('reg'), 10);
      if (!isNaN(activeIndex)) currentRegIndex = activeIndex;

      if (currentRegIndex <= 0) {
        goToStep(2);
        return;
      }
      showReg(currentRegIndex - 1);
    }
    window.prevReg = prevReg;

    function updateConnectedUi() {
      if (bookingOtpVerified) {
        var label = bookingConnectedName ? bookingConnectedName + ' (' + bookingConnectedEmail + ')' : bookingConnectedEmail;
        $('#bdConnectedUser').removeClass('hidden').text('Already connected user: ' + label);
        $('#bdOtpStatus').removeClass('hidden').addClass('flex').html('<span class="material-symbols-outlined text-[18px] text-green-600">verified</span><span>Email already verified for this booking.</span>');
        $('#bdOtpCode').closest('.mb-4').addClass('hidden');
        $('#bdSendOtpBtn').addClass('hidden');
        $('#bdVerifyOtpBtn').text('Continue').prop('disabled', false);
      } else {
        $('#bdConnectedUser').addClass('hidden').text('Already connected user.');
        $('#bdOtpCode').closest('.mb-4').removeClass('hidden');
        $('#bdSendOtpBtn').removeClass('hidden');
        $('#bdVerifyOtpBtn').text('Verify & Continue');
      }
    }

    function formatSecondsToMMSS(seconds) {
      var s = Math.max(0, parseInt(seconds || 0, 10) || 0);
      var mm = String(Math.floor(s / 60)).padStart(2, '0');
      var ss = String(s % 60).padStart(2, '0');
      return mm + ':' + ss;
    }

    function applyOtpResendUi() {
      var currentEmail = String($('#bdOtpEmail').val() || '').trim().toLowerCase();
      if (bookingOtpResendRemaining > 0 && bookingOtpResendEmail && bookingOtpResendEmail === currentEmail) {
        $('#bdSendOtpBtn').prop('disabled', true).text('Resend in ' + formatSecondsToMMSS(bookingOtpResendRemaining));
      } else {
        $('#bdSendOtpBtn').prop('disabled', false).text('Send email code');
      }
    }

    function startOtpResendCountdown(seconds) {
      bookingOtpResendRemaining = Math.max(0, parseInt(seconds || 0, 10) || 0);
      if (bookingOtpResendTimer) {
        clearInterval(bookingOtpResendTimer);
        bookingOtpResendTimer = null;
      }
      applyOtpResendUi();
      if (bookingOtpResendRemaining <= 0) return;

      bookingOtpResendTimer = setInterval(function() {
        bookingOtpResendRemaining = Math.max(0, bookingOtpResendRemaining - 1);
        applyOtpResendUi();
        if (bookingOtpResendRemaining <= 0 && bookingOtpResendTimer) {
          clearInterval(bookingOtpResendTimer);
          bookingOtpResendTimer = null;
        }
      }, 1000);
    }

    async function sendBookingOtp() {
      var email = String($('#bdOtpEmail').val() || '').trim();
      $('#bdOtpError').addClass('hidden');
      if (bookingOtpResendRemaining > 0 && bookingOtpResendEmail === email.toLowerCase()) {
        $('#bdOtpError').removeClass('hidden').text('Please wait ' + formatSecondsToMMSS(bookingOtpResendRemaining) + ' before requesting another code.');
        return;
      }
      if (!isValidEmail(email)) {
        $('#bdOtpError').removeClass('hidden').text('Please enter a valid email first.');
        return;
      }

      $('#bdSendOtpBtn').prop('disabled', true).text('Sending...');
      try {
        var res = await fetch('/api/public/send-booking-otp', {
          method: 'POST',
          headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
          },
          body: JSON.stringify({ email: email })
        });
        var data = await res.json();
        if (!res.ok) {
          if (data && data.resend_available_in_seconds) {
            bookingOtpResendEmail = email.toLowerCase();
            startOtpResendCountdown(data.resend_available_in_seconds);
          }
          throw new Error((data && data.message) || 'Could not send verification code.');
        }
        $('#bdOtpStatus').removeClass('hidden').addClass('flex').html('<span class="material-symbols-outlined text-[18px] text-green-600">mark_email_read</span><span>4-digit code sent to ' + email + '.</span>');
        bookingOtpResendEmail = email.toLowerCase();
        startOtpResendCountdown(data && data.resend_available_in_seconds ? data.resend_available_in_seconds : 60);
      } catch (err) {
        $('#bdOtpError').removeClass('hidden').text(err.message || 'Could not send verification code.');
      } finally {
        if (bookingOtpResendRemaining <= 0) {
          $('#bdSendOtpBtn').prop('disabled', false).text('Send email code');
        } else {
          applyOtpResendUi();
        }
      }
    }
    window.sendBookingOtp = sendBookingOtp;

    async function verifyBookingOtp() {
      if (bookingOtpVerified) {
        finishRegister();
        return;
      }

      var email = String($('#bdOtpEmail').val() || '').trim();
      var code = String($('#bdOtpCode').val() || '').trim();
      var name = String($('#bdName').val() || '').trim();
      $('#bdOtpError').addClass('hidden');

      if (!isValidEmail(email)) {
        $('#bdOtpError').removeClass('hidden').text('Please enter a valid email.');
        return;
      }
      if (!/^\d{4}$/.test(code)) {
        $('#bdOtpError').removeClass('hidden').text('Please enter a valid 4-digit code.');
        return;
      }

      $('#bdVerifyOtpBtn').prop('disabled', true).text('Verifying...');
      try {
        var res = await fetch('/api/public/verify-booking-otp', {
          method: 'POST',
          headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
          },
          body: JSON.stringify({ email: email, code: code, name: name })
        });
        var data = await res.json();
        if (!res.ok || !data || !data.verified) {
          throw new Error((data && data.message) || 'Verification failed.');
        }

        bookingOtpVerified = true;
        bookingConnectedEmail = (data.user && data.user.email) ? data.user.email : email;
        bookingConnectedName = (data.user && data.user.name) ? data.user.name : '';
        $('#bdEmail').val(bookingConnectedEmail);
        updateConnectedUi();
        finishRegister();
      } catch (err) {
        $('#bdOtpError').removeClass('hidden').text(err.message || 'Verification failed.');
      } finally {
        $('#bdVerifyOtpBtn').prop('disabled', false).text('Verify & Continue');
      }
    }
    window.verifyBookingOtp = verifyBookingOtp;

    function finishRegister() {
      if (!bookingOtpVerified) {
        $('#bdOtpError').removeClass('hidden').text('Please verify your email to continue.');
        return;
      }
      goToStep(4);
    }
    window.finishRegister = finishRegister;

    function toggleBdAuth() {}
    window.toggleBdAuth = toggleBdAuth;

    $(document).on('click', '.single-choice-radio-button', function() {
      var choice_group = $(this).closest('div.single-choice-group');
      choice_group.find('.single-choice-radio-button').removeClass('selected');

      var main_div = $(this).closest('div.question-div');
      var current_question = parseInt(main_div.data('q'), 10);

      $(this).addClass('selected');
      main_div.find('.js-question-error').addClass('hidden');

      if (isNaN(current_question)) return;

      // Move to the next question with a small delay.
      setTimeout(function() {
        nextQuestion(current_question);
      }, 180);
    });

    $(document).on('click', '.js-prev-question', function() {
      prevQuestion();
    });

    $(document).on('click', '.js-next-question', function() {
      nextQuestion();
    });

    $(document).on('click', '.js-continue-scheduling', function() {
      if (!validateActiveQuestion()) return;
      goToStep(2);
    });

    $(document).on('change', '.js-select2-question, .js-question-file, .js-question-toggle', async function() {
      var $question = $(this).closest('.question-div');
      var qId = $question.data('question-id');
      if (!qId) return;
      if ($(this).hasClass('js-question-toggle')) {
        questionAnswers[qId] = $(this).is(':checked');
      } else if ($(this).hasClass('js-question-file')) {
        var file = this.files && this.files.length ? this.files[0] : null;
        questionAnswers[qId] = '';
        if (file) {
          try {
            var imageUrl = await uploadQuestionImage(file, qId);
            questionAnswers[qId] = imageUrl;
          } catch (error) {
            $question.find('.js-question-error').removeClass('hidden').text(error.message || 'Image upload failed. Please try again.');
            return;
          }
        }
      } else {
        questionAnswers[qId] = String($(this).val() || '').trim();
      }
      $question.find('.js-question-error').addClass('hidden');
    });

    $(document).on('input', '.js-question-input', function() {
      var $question = $(this).closest('.question-div');
      var qId = $question.data('question-id');
      if (!qId) return;
      questionAnswers[qId] = String($(this).val() || '').trim();
      $question.find('.js-question-error').addClass('hidden');
    });
    $('#bdName').on('input', function() { clearRegError('bdName', 'bdNameError'); });
    $('#bdEmail').on('input', function() { clearRegError('bdEmail', 'bdEmailError'); });
    $('#bdPhone').on('input', function() { clearRegError('bdPhone', 'bdPhoneError'); });
    $('#bdOtpEmail').on('input', function() {
      $('#bdOtpError').addClass('hidden');
      $('#bdOtpStatus').text('').addClass('hidden').removeClass('flex');
      applyOtpResendUi();
      if (String($(this).val() || '').trim().toLowerCase() !== String(bookingConnectedEmail || '').toLowerCase()) {
        bookingOtpVerified = false;
        bookingConnectedEmail = '';
        bookingConnectedName = '';
      }
      updateConnectedUi();
    });
    $('#bdOtpCode').on('input', function() {
      this.value = String(this.value || '').replace(/\D/g, '').slice(0, 4);
      $('#bdOtpError').addClass('hidden');
    });

    $(function() {
      renderQuestions();
      updateProgressDots();

      if (!Array.isArray(questionDefinitions) || questionDefinitions.length === 0) {
        $('.js-back-to-questions').addClass('hidden');
        goToStep(2);
      }

      $('.js-select2-question').select2({
        width: '100%',
        minimumResultsForSearch: Infinity
      });
      $('.dropify').dropify();
      updatePaymentSummary();
      mountStripeElements();
      checkPayReady();
      $('#inputCardName').on('input', checkPayReady);

      if (consultationRequired) {
        // Phone option is not part of current artist preference matrix.
        $('#ccConsultTypeCards .consult-type-card[data-type="phone"]').addClass('hidden');

        var allowedTypes = [];
        if (consultationSessionType === 'online') {
          allowedTypes = ['video'];
        } else if (consultationSessionType === 'physical') {
          allowedTypes = ['studio'];
            } else {
          allowedTypes = ['video', 'studio'];
        }

        $('#ccConsultTypeCards .consult-type-card').each(function() {
          var type = String($(this).data('type') || '');
          if (allowedTypes.indexOf(type) === -1) {
            $(this).addClass('hidden');
          }
        });

        if (allowedTypes.length === 1) {
          var onlyType = allowedTypes[0];
          var onlyCard = $('#ccConsultTypeCards .consult-type-card[data-type="' + onlyType + '"]').get(0);
          if (onlyCard) {
            selectConsultType(onlyCard, onlyType);
          }
        }
      }

      var q0 = parseInt($('div.question-div.active[data-q]').data('q'), 10);
      if (!isNaN(q0)) currentQuestionIndex = q0;

      var r0 = parseInt($('div.question-div.active[data-reg]').data('reg'), 10);
      if (!isNaN(r0)) currentRegIndex = r0;
      if ($('#bdEmail').length && $('#bdOtpEmail').length) {
        $('#bdOtpEmail').val(String($('#bdEmail').val() || '').trim());
        updateConnectedUi();
      }

      $('#calPrev').on('click', function() {
        if (!canNavigateToMonth(calYear, calMonth - 1)) return;
        calMonth--;
        if (calMonth < 0) { calMonth = 11; calYear--; }
        renderMainCal();
      });
      $('#calNext').on('click', function() {
        calMonth++;
        if (calMonth > 11) { calMonth = 0; calYear++; }
        renderMainCal();
      });
      $(document).on('click', '#timeSlots .time-slot-card:not(.booked)', function() {
        $('#timeSlots .time-slot-card').removeClass('selected');
        $('#timeSlots .time-slot-wrap').removeClass('selected');
        $(this).addClass('selected');
        $(this).closest('.time-slot-wrap').addClass('selected');
        selectedTime = $(this).data('time');
        $('#confirmBarText').text('📅 ' + selectedDate.toLocaleDateString('en-GB', { weekday: 'long', day: 'numeric', month: 'long' }) + ' at ' + selectedTime);
        $('#confirmBar').removeClass('hidden');
      });
      $(document).on('click', '#timeSlots .js-time-slot-continue', function() {
        if (!selectedDate || !selectedTime) return;
        goToStep(3);
      });
    function selectConsultType(card, type) {
      ccConsultType = type;
      $('#ccConsultTypeError').addClass('hidden');
      $('#ccConsultTypeCards .consult-type-card').removeClass('selected');
      $(card).addClass('selected');
      $('#ccConsultSection').removeClass('hidden');
      if (consultationTiming === 'separate') {
        $('#ccTattooSection').addClass('hidden');
      }
      $('#ccConsultChip, #ccTattooChip, #ccBottomSummary').addClass('hidden');
      ccConsultDate = null;
      ccConsultTime = null;
      ccTattooDate = null;
      ccTattooTime = null;
      renderCcConsultCal();
    }
    window.selectConsultType = selectConsultType;
    window.ccCalNav = function(dir) {
      if (dir < 0 && !canNavigateToMonth(ccCalYear, ccCalMonth - 1)) return;
      ccCalMonth += dir;
      if (ccCalMonth < 0) { ccCalMonth = 11; ccCalYear--; }
      if (ccCalMonth > 11) { ccCalMonth = 0; ccCalYear++; }
      renderCcConsultCal();
    };
    window.ccTatCalNav = function(dir) {
      if (dir < 0 && !canNavigateToMonth(ccTatCalYear, ccTatCalMonth - 1)) return;
      ccTatCalMonth += dir;
      if (ccTatCalMonth < 0) { ccTatCalMonth = 11; ccTatCalYear--; }
      if (ccTatCalMonth > 11) { ccTatCalMonth = 0; ccTatCalYear++; }
      renderCcTattooCal();
    };
    $(document).on('click', '.js-cc-consult-slot', function() {
      $('.js-cc-consult-slot').removeClass('selected');
      $(this).addClass('selected');
      ccConsultTime = $(this).data('time');
      $('#ccConsultChip').removeClass('hidden');
      $('#ccConsultChipText').text('📹 Consultation: ' + ccConsultDate.toLocaleDateString('en-GB', { weekday: 'short', day: 'numeric', month: 'short' }) + ' at ' + ccConsultTime);
      if (consultationTiming === 'separate') {
        $('#ccTattooSection').removeClass('hidden');
        ccTatCalYear = ccConsultDate.getFullYear();
        ccTatCalMonth = ccConsultDate.getMonth();
        renderCcTattooCal();
    } else {
        var consultStartDt = buildDateTime(ccConsultDate, ccConsultTime);
        var tattooStartDt = addGapToDateTime(consultStartDt, consultDurationMinutes, 'minutes');
        ccTattooDate = new Date(tattooStartDt.getFullYear(), tattooStartDt.getMonth(), tattooStartDt.getDate());
        ccTattooTime = formatTo12Hour(tattooStartDt.getHours(), tattooStartDt.getMinutes());
        $('#ccTattooSection, #ccTattooChip').addClass('hidden');
        $('#ccSumConsult').text('📹 Consultation: ' + ccConsultDate.toLocaleDateString('en-GB', { weekday: 'short', day: 'numeric', month: 'short' }) + ' at ' + ccConsultTime + ' (' + consultDurationMinutes + ' min)');
        $('#ccSumTattoo').text('🎨 Tattoo Session: ' + ccTattooDate.toLocaleDateString('en-GB', { weekday: 'short', day: 'numeric', month: 'short' }) + ' at ' + ccTattooTime);
        $('#ccBottomSummary').removeClass('hidden');
      }
    });
    $(document).on('click', '.js-cc-tattoo-slot', function() {
      $('.js-cc-tattoo-slot').removeClass('selected');
      $(this).addClass('selected');
      ccTattooTime = $(this).data('time');
      $('#ccTattooChip').removeClass('hidden');
      $('#ccTattooChipText').text('🎨 Tattoo Session: ' + ccTattooDate.toLocaleDateString('en-GB', { weekday: 'short', day: 'numeric', month: 'short' }) + ' at ' + ccTattooTime);
      $('#ccSumConsult').text('📹 Consultation: ' + ccConsultDate.toLocaleDateString('en-GB', { weekday: 'short', day: 'numeric', month: 'short' }) + ' at ' + ccConsultTime + (ccConsultType ? ' (' + ccConsultType + ')' : ''));
      $('#ccSumTattoo').text('🎨 Tattoo Session: ' + ccTattooDate.toLocaleDateString('en-GB', { weekday: 'short', day: 'numeric', month: 'short' }) + ' at ' + ccTattooTime);
      $('#ccBottomSummary').removeClass('hidden');
    });

      renderMainCal();
    });
  </script>
</body>
</html>
