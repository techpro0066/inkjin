<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Book Your Tattoo | Inkjin</title>
  <meta name="description" content="Book a tattoo with managed scheduling and a free consultation — share your availability and complete your details.">
  <link rel="icon" href="{{asset('design/images/icons/favicon.png')}}">
  <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
  <link href="{{asset('design/css/inkjin_bookpay.css')}}" rel="stylesheet">
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
    .tf-screen { display: none; min-height: 60vh; align-items: center; justify-content: center; padding: 2rem 0; }
    .tf-screen.active { display: flex; animation: tfSlideIn 0.4s ease-out; }
    .tf-screen.active.reverse { animation: tfSlideInReverse 0.4s ease-out; }
    .question-div { display: none; min-height: 60vh; align-items: center; justify-content: center; padding: 2rem 0; }
    .question-div.active { display: flex; animation: tfSlideIn 0.4s ease-out; }
    .question-div.active.reverse { animation: tfSlideInReverse 0.4s ease-out; }
    .single-choice-radio-button { padding: 0.75rem 1.5rem; border-radius: 9999px; border: 2px solid #cac4d3; font-size: 0.95rem; font-weight: 600; color: #494552; cursor: pointer; transition: all 0.15s; background: white; }
    .single-choice-radio-button:hover { border-color: #310f7a; color: #310f7a; }
    .single-choice-radio-button.selected { background: #310f7a; color: white; border-color: #310f7a; }
    .question-kicker {
      display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.35rem 0.85rem; border-radius: 9999px;
      border: 1px solid #ddd0ff; background: linear-gradient(135deg, #f8f1fb 0%, #f2ecf5 100%);
      color: #310f7a; font-size: 0.78rem; font-weight: 700; letter-spacing: 0.02em; margin-bottom: 0.75rem;
      box-shadow: 0 2px 8px rgba(49, 15, 122, 0.08);
    }
    .question-kicker .dot { width: 0.45rem; height: 0.45rem; border-radius: 9999px; background: #310f7a; opacity: 0.9; }
    .select2-container--default .select2-selection--single {
      height: 58px; border: 1px solid rgba(122, 117, 131, 0.35); border-radius: 1rem; background: #ffffff;
      display: flex; align-items: center; box-shadow: 0 1px 4px rgba(49, 15, 122, 0.04);
    }
    .select2-container--default.select2-container--focus .select2-selection--single {
      border-color: rgba(49, 15, 122, 0.55); box-shadow: 0 0 0 3px rgba(49, 15, 122, 0.14);
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
      color: #1c1b21; line-height: 58px; font-size: 1rem; padding-left: 1rem; padding-right: 2.2rem;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow { height: 56px; right: 10px; }
    .select2-dropdown { border: 1px solid #ddd0ff; border-radius: 0.9rem; box-shadow: 0 12px 28px rgba(49, 15, 122, 0.12); overflow: hidden; }
    .select2-container--default .select2-results__option--highlighted.select2-results__option--selectable { background: #310f7a; color: #fff; }
    .q-toggle-row { display: flex; align-items: flex-start; gap: 0.85rem; padding: 1rem; border: 1px solid rgba(122, 117, 131, 0.32); border-radius: 0.9rem; background: #ffffff; }
    .q-toggle-control { position: relative; display: inline-flex; width: 54px; min-width: 54px; height: 31px; margin-top: 1px; flex-shrink: 0; }
    .q-toggle-label { font-size: 0.95rem; color: #1c1b21; line-height: 1.45; font-weight: 500; flex: 1; min-width: 0; }
    .q-toggle-input { position: absolute; opacity: 0; width: 0; height: 0; pointer-events: none; }
    .q-toggle-ui { position: relative; display: inline-block; width: 54px; height: 31px; border-radius: 9999px; background: #a8c7ff; transition: all 0.2s ease; cursor: pointer; }
    .q-toggle-ui::after { content: ""; position: absolute; top: 3px; left: 3px; width: 25px; height: 25px; border-radius: 50%; background: #ffffff; box-shadow: 0 2px 7px rgba(0, 0, 0, 0.2); transition: transform 0.2s ease; }
    .q-toggle-input:checked + .q-toggle-ui { background: linear-gradient(90deg, #1e6bff 0%, #3f86ff 100%); }
    .q-toggle-input:checked + .q-toggle-ui::after { transform: translateX(23px); }
    @keyframes tfSlideIn { from { opacity: 0; transform: translateY(40px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes tfSlideInReverse { from { opacity: 0; transform: translateY(-40px); } to { opacity: 1; transform: translateY(0); } }
    .cal-card { background: white; border-radius: 1rem; border: 1px solid #e6e0ea; overflow: hidden; box-shadow: 0 4px 24px rgba(49,15,122,0.06); }
    .cal-day { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.875rem; cursor: pointer; transition: all 0.15s; }
    .cal-day.available:hover { background: #ece6ef; }
    .cal-day.available { color: #1c1b21; font-weight: 600; }
    .cal-day.unavailable { color: #cac4d3; cursor: default; pointer-events: none; }
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
    .pill-btn { padding: 0.75rem 1.5rem; border-radius: 9999px; border: 2px solid #cac4d3; font-size: 0.95rem; font-weight: 600; color: #494552; cursor: pointer; transition: all 0.15s; background: white; }
    .pill-btn:hover { border-color: #310f7a; color: #310f7a; }
    .pill-btn.selected { background: #310f7a; color: white; border-color: #310f7a; }
    .big-choice-btn { padding: 1.25rem 2rem; border-radius: 1rem; border: 2px solid #cac4d3; font-size: 1.1rem; font-weight: 700; color: #494552; cursor: pointer; transition: all 0.15s; background: white; min-width: 140px; text-align: center; }
    .big-choice-btn:hover { border-color: #310f7a; color: #310f7a; background: #f8f1fb; }
    .big-choice-btn.selected { background: #310f7a; color: white; border-color: #310f7a; }
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
  </style>
</head>
<body class="bg-surface text-on-surface min-h-screen">
  <div class="tf-progress" id="topProgressBar" style="width: 0%"></div>

  <!-- HEADER -->
  <header class="border-b border-outline-variant/20 bg-white/70 backdrop-blur-md sticky top-0 z-50">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between">
      <a href="{{route('public.artist', ['username' => $userDetail->user_name])}}" class="flex items-center gap-2 text-primary font-extrabold text-xl tracking-tight">
        <img src="{{asset('design/images/inkjin_logo-p-500.png')}}" alt="inkjin" class="h-7">
      </a>
      <div class="flex items-center gap-3 flex-wrap justify-end">
        <a href="{{route('public.artist', ['username' => $userDetail->user_name])}}" class="flex items-center gap-1 text-sm text-on-surface-variant hover:text-primary transition-colors">
          <span class="material-symbols-outlined text-[18px]">arrow_back</span> Back to {{ $userDetail->user->first_name }} {{ $userDetail->user->last_name }}
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
        <a href="{{route('public.artist', ['username' => $userDetail->user_name])}}" class="inline-flex items-center gap-2 px-6 py-3 bg-primary text-on-primary rounded-full font-semibold text-sm hover:bg-primary-container transition-colors">
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
      <div class="progress-step text-center" data-step="2"><div class="step-dot mx-auto">2</div><div class="step-label" id="step2Label">Availability</div></div>
      <div class="progress-line mt-4" data-line="2"></div>
      <div class="progress-step text-center" data-step="3"><div class="step-dot mx-auto">3</div><div class="step-label">Register</div></div>
      <div class="progress-line mt-4" data-line="3"></div>
      <div class="progress-step text-center" data-step="4"><div class="step-dot mx-auto">4</div><div class="step-label" id="step4Label">Review</div></div>
      <div class="progress-line mt-4" data-line="4"></div>
      <div class="progress-step text-center" data-step="5"><div class="step-dot mx-auto">5</div><div class="step-label" id="step5Label">Submitted</div></div>
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

    <!-- ═══════════════════════════════════════════ -->
    <!-- STEP 2D: MANAGED + CONSULTATION             -->
    <!-- ═══════════════════════════════════════════ -->
    <div class="step-panel" id="step2ManagedConsult">
      <button class="js-back-to-questions flex items-center gap-1 text-sm text-on-surface-variant hover:text-primary mb-4 transition-colors" onclick="goToStep(1, true)"><span class="material-symbols-outlined text-[18px]">arrow_back</span> Back to Questions</button>
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
            <div><label class="text-xs font-semibold text-on-surface-variant mb-2 block">How flexible are you?</label><div class="flex flex-wrap gap-2" id="mcFlexPills"><button class="pill-btn text-sm !py-2 !px-4" data-value="Very flexible" onclick="selectPill(this,'mcFlexPills')">Very flexible</button><button class="pill-btn text-sm !py-2 !px-4" data-value="Somewhat flexible" onclick="selectPill(this,'mcFlexPills')">Somewhat flexible</button><button class="pill-btn text-sm !py-2 !px-4" data-value="These are my only options" onclick="selectPill(this,'mcFlexPills')">These are my only options</button></div></div>
            <div><label class="text-xs font-semibold text-on-surface-variant mb-2 block">How soon after the consultation would you like your tattoo session?</label><div class="flex flex-wrap gap-2" id="mcGapPills"><button class="pill-btn text-sm !py-2 !px-4" data-value="Same week" onclick="selectPill(this,'mcGapPills')">Same week</button><button class="pill-btn text-sm !py-2 !px-4" data-value="1-2 weeks after" onclick="selectPill(this,'mcGapPills')">1-2 weeks after</button><button class="pill-btn text-sm !py-2 !px-4" data-value="2-4 weeks after" onclick="selectPill(this,'mcGapPills')">2-4 weeks after</button><button class="pill-btn text-sm !py-2 !px-4" data-value="I'm flexible" onclick="selectPill(this,'mcGapPills')">I'm flexible</button></div></div>
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
      <div class="tf-screen active" data-reg="0" id="reg-0">
        <div class="w-full max-w-xl mx-auto">
          <p class="text-sm font-semibold text-primary mb-2">1 →</p>
          <h2 class="text-2xl sm:text-3xl font-bold text-on-surface mb-2">What's your name?</h2>
          <p class="text-on-surface-variant mb-6">So the artist knows who to expect.</p>
          <input type="text" id="bdName" placeholder="Your full name" class="w-full border border-outline-variant/30 bg-white rounded-2xl px-6 py-4 text-lg text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
          <div class="flex items-center justify-between mt-6"><button onclick="nextReg()" class="inline-flex items-center gap-2 px-6 py-3 bg-primary text-on-primary rounded-full font-bold text-sm hover:bg-primary-container transition-colors">Next <span class="material-symbols-outlined text-[18px]">arrow_forward</span></button><span class="text-sm text-on-surface-variant">press <strong>Enter ↵</strong></span></div>
        </div>
      </div>
      <!-- Email -->
      <div class="tf-screen" data-reg="1" id="reg-1">
        <div class="w-full max-w-xl mx-auto">
          <button onclick="prevReg()" class="flex items-center gap-1 text-sm text-on-surface-variant hover:text-primary mb-4 transition-colors"><span class="material-symbols-outlined text-[18px]">arrow_back</span> Back</button>
          <p class="text-sm font-semibold text-primary mb-2">2 →</p>
          <h2 class="text-2xl sm:text-3xl font-bold text-on-surface mb-2">What's your email?</h2>
          <p class="text-on-surface-variant mb-6">We'll send your booking confirmation here.</p>
          <input type="email" id="bdEmail" placeholder="you@example.com" class="w-full border border-outline-variant/30 bg-white rounded-2xl px-6 py-4 text-lg text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
          <div class="flex items-center justify-between mt-6"><button onclick="nextReg()" class="inline-flex items-center gap-2 px-6 py-3 bg-primary text-on-primary rounded-full font-bold text-sm hover:bg-primary-container transition-colors">Next <span class="material-symbols-outlined text-[18px]">arrow_forward</span></button><span class="text-sm text-on-surface-variant">press <strong>Enter ↵</strong></span></div>
        </div>
      </div>
      <!-- Phone -->
      <div class="tf-screen" data-reg="2" id="reg-2">
        <div class="w-full max-w-xl mx-auto">
          <button onclick="prevReg()" class="flex items-center gap-1 text-sm text-on-surface-variant hover:text-primary mb-4 transition-colors"><span class="material-symbols-outlined text-[18px]">arrow_back</span> Back</button>
          <p class="text-sm font-semibold text-primary mb-2">3 →</p>
          <h2 class="text-2xl sm:text-3xl font-bold text-on-surface mb-2">Your phone number?</h2>
          <p class="text-on-surface-variant mb-6">In case the artist needs to reach you.</p>
          <input type="tel" id="bdPhone" placeholder="+30 694 123 4567" class="w-full border border-outline-variant/30 bg-white rounded-2xl px-6 py-4 text-lg text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
          <div class="flex items-center justify-between mt-6"><button onclick="nextReg()" class="inline-flex items-center gap-2 px-6 py-3 bg-primary text-on-primary rounded-full font-bold text-sm hover:bg-primary-container transition-colors">Next <span class="material-symbols-outlined text-[18px]">arrow_forward</span></button><span class="text-sm text-on-surface-variant">press <strong>Enter ↵</strong></span></div>
        </div>
      </div>
      <!-- Auth -->
      <div class="tf-screen" data-reg="3" id="reg-3">
        <div class="w-full max-w-md mx-auto">
          <div id="bdAuthCreate">
            <div class="text-center mb-6"><span class="material-symbols-outlined text-primary text-4xl mb-2">person_add</span><h2 class="text-2xl sm:text-3xl font-bold text-on-surface mb-2">Create your free account</h2><p class="text-on-surface-variant">Track your bookings, message artists, and manage appointments.</p></div>
            <div class="flex items-center gap-2 bg-surface-container rounded-xl px-4 py-3 mb-5"><span class="material-symbols-outlined text-primary text-[18px]">mail</span><span class="text-sm text-on-surface" id="bdAuthEmail">you@example.com</span><span class="material-symbols-outlined text-green-500 text-[16px] ml-auto">check_circle</span></div>
            <div class="mb-2"><input type="password" id="bdPassword" placeholder="Create a password" class="w-full border border-outline-variant/30 bg-white rounded-2xl px-6 py-4 text-lg text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30"></div>
            <p class="text-xs text-on-surface-variant mb-3">At least 8 characters</p>
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
            <button onclick="finishRegister()" class="w-full py-3.5 bg-primary text-on-primary rounded-full font-bold text-sm hover:bg-primary-container transition-colors shadow-lg shadow-primary/20 mb-4">Create Account & Continue</button>
            <div class="flex items-center gap-3 mb-4"><div class="flex-1 h-px bg-outline-variant/30"></div><span class="text-sm text-on-surface-variant">or</span><div class="flex-1 h-px bg-outline-variant/30"></div></div>
            <div class="space-y-2 mb-5">
              <button class="social-btn" onclick="finishRegister()"><svg class="w-5 h-5" viewBox="0 0 24 24"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 01-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg> Continue with Google</button>
              <button class="social-btn" onclick="finishRegister()"><svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.8-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19-.69.85-1.83 1.51-2.95 1.42-.15-1.15.41-2.35 1.05-3.11z"/></svg> Continue with Apple</button>
            </div>
            <p class="text-center text-sm text-on-surface-variant">Already have an account? <span class="auth-toggle" onclick="toggleBdAuth()">Log in</span></p>
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
      <div class="flex flex-col items-center justify-center py-16" id="processingView"><div class="spinner mb-4"></div><p class="text-sm text-on-surface-variant" id="processingText">Submitting your booking request…</p></div>
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

  <!-- JAVASCRIPT -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/Dropify/0.2.2/js/dropify.min.js"></script>
  <script>
  (function($) {
    'use strict';
    var csrfToken = @json(csrf_token());
    var bookingArtistUsername = @json($userDetail->user_name ?? '');
    var bookingTattooSlug = @json($tattoo->slug ?? '');
    var serverQuestions = @json($requiredBookingQuestions ?? $questions ?? []);
    var questionAnswers = {};
    var currentQuestionIndex = 0;
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

    function escapeHtml(str) {
      return String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function buildStructuredQuestionAnswers() {
      var output = {};
      questionDefinitions.forEach(function(q) {
        if (!q || q.id == null) return;
        var answer = questionAnswers[q.id];
        if (typeof answer === 'string') answer = answer.trim();
        if (answer === undefined || answer === null || answer === '') return;
        output[String(q.id)] = { id: q.id, question: String(q.title || ''), type: String(q.type || 'input'), answer: answer };
      });
      return output;
    }

    function getAnswerByKeywords(keywords) {
      for (var i = 0; i < questionDefinitions.length; i++) {
        var q = questionDefinitions[i] || {};
        var title = String(q.title || '').toLowerCase();
        var subtitle = String(q.subtitle || '').toLowerCase();
        if (!keywords.some(function(k) { return title.indexOf(k) !== -1 || subtitle.indexOf(k) !== -1; })) continue;
        var val = questionAnswers[q.id];
        if (typeof val === 'string' && val.trim()) return val.trim();
        if (typeof val === 'number' || typeof val === 'boolean') return String(val);
      }
      return '';
    }

    async function uploadQuestionImage(file, questionId) {
      var formData = new FormData();
      formData.append('image', file);
      formData.append('question_id', String(questionId || ''));
      formData.append('artist_username', bookingArtistUsername);
      formData.append('tattoo_slug', bookingTattooSlug);
      var response = await fetch('/api/public/upload-booking-question-image', {
        method: 'POST',
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: formData
      });
      var data = await response.json();
      if (!response.ok || !data || !data.success) throw new Error((data && data.message) || 'Image upload failed.');
      return data.file_url || data.file_path || '';
    }

    function renderQuestions() {
      var html = '';
      questionDefinitions.forEach(function(q, idx) {
        var isFirst = idx === 0;
        var isLast = idx === questionDefinitions.length - 1;
        var body = '';
        if (q.type === 'radio') {
          body = '<div class="flex flex-wrap gap-2 single-choice-group">' + q.options.map(function(opt) {
            return '<button type="button" class="single-choice-radio-button" data-value="' + escapeHtml(opt) + '">' + escapeHtml(opt) + '</button>';
          }).join('') + '</div>';
        } else if (q.type === 'select') {
          body = '<select class="w-full js-select2-question" data-question-id="' + q.id + '"><option value="">Choose an option</option>' +
            q.options.map(function(opt) { return '<option value="' + escapeHtml(opt) + '">' + escapeHtml(opt) + '</option>'; }).join('') + '</select>';
        } else if (q.type === 'input') {
          body = '<input type="text" placeholder="' + escapeHtml(q.placeholder) + '" data-question-id="' + q.id + '" class="js-question-input w-full border border-outline-variant/30 bg-white rounded-2xl px-6 py-4 text-lg text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">';
        } else if (q.type === 'textarea') {
          body = '<textarea rows="4" placeholder="' + escapeHtml(q.placeholder) + '" data-question-id="' + q.id + '" class="js-question-input w-full border border-outline-variant/30 bg-white rounded-2xl px-6 py-4 text-lg text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30 resize-none"></textarea>';
        } else if (q.type === 'image') {
          body = '<div class="border-2 border-dashed border-outline-variant/40 rounded-2xl p-6 bg-white"><input type="file" accept="image/*" data-question-id="' + q.id + '" class="dropify js-question-file" data-allowed-file-extensions="jpg jpeg png webp" data-max-file-size="5M" data-show-remove="true"></div>';
        } else if (q.type === 'toggle') {
          body = '<label class="q-toggle-row"><span class="q-toggle-control"><input type="checkbox" data-question-id="' + q.id + '" class="q-toggle-input js-question-toggle"><span class="q-toggle-ui"></span></span><span class="q-toggle-label">' + escapeHtml(q.subtitle) + '</span></label>';
        }
        var navButton = isLast
          ? '<button type="button" class="js-continue-scheduling inline-flex items-center gap-2 px-6 py-3 bg-primary text-on-primary rounded-full font-bold text-sm hover:bg-primary-container transition-colors">Continue to Availability <span class="material-symbols-outlined text-[18px]">arrow_forward</span></button>'
          : '<button type="button" class="js-next-question inline-flex items-center gap-2 px-6 py-3 bg-primary text-on-primary rounded-full font-bold text-sm hover:bg-primary-container transition-colors">Next <span class="material-symbols-outlined text-[18px]">arrow_forward</span></button>';
        html += '<div class="question-div' + (isFirst ? ' active' : '') + '" data-q="' + idx + '" data-question-id="' + q.id + '" data-question-type="' + q.type + '" data-required="' + (q.required ? '1' : '0') + '"><div class="w-full max-w-xl mx-auto">' +
          (isFirst ? '' : '<button type="button" class="js-prev-question flex items-center gap-1 text-sm text-on-surface-variant hover:text-primary mb-4 transition-colors"><span class="material-symbols-outlined text-[18px]">arrow_back</span> Back</button>') +
          '<p class="question-kicker"><span class="dot"></span>Question ' + (idx + 1) + ':</p>' +
          '<h2 class="text-2xl sm:text-3xl font-bold text-on-surface mb-2">' + escapeHtml(q.title) + '</h2>' +
          '<p class="text-on-surface-variant mb-6">' + escapeHtml(q.subtitle) + (q.required ? ' <span class="text-error">*</span>' : '') + '</p>' +
          body + '<p class="text-sm text-error hidden mt-3 js-question-error">Please answer this required question.</p>' +
          '<div class="flex items-center justify-end mt-6">' + navButton + '</div></div></div>';
      });
      $('#questionsMount').html(html);
    }

    function validateActiveQuestion() {
      var $active = $('div.question-div.active[data-q]');
      if (!$active.length) return true;
      if (String($active.data('required')) !== '1') { $active.find('.js-question-error').addClass('hidden'); return true; }
      var qType = String($active.data('question-type') || '');
      var qId = $active.data('question-id');
      var hasValue = false;
      if (qType === 'radio') hasValue = !!$active.find('.single-choice-radio-button.selected').length;
      else if (qType === 'select') hasValue = !!String($active.find('.js-select2-question').val() || '').trim();
      else if (qType === 'input' || qType === 'textarea') hasValue = !!String($active.find('.js-question-input').val() || '').trim();
      else if (qType === 'image') hasValue = !!String(questionAnswers[qId] || '').trim();
      else if (qType === 'toggle') hasValue = $active.find('.js-question-toggle').is(':checked');
      else hasValue = !!questionAnswers[qId];
      $active.find('.js-question-error').toggleClass('hidden', hasValue);
      return hasValue;
    }

    function showQuestion(index) {
      var questions = $('div.question-div[data-q]');
      if (!questions.length) return;
      index = Math.max(0, Math.min(index, questions.length - 1));
      questions.removeClass('active');
      questions.filter('[data-q="' + index + '"]').addClass('active');
      currentQuestionIndex = index;
      if (typeof window.mbSyncQuestionProgress === 'function') window.mbSyncQuestionProgress(index);
    }

    function moveQuestion(step) {
      var nextIndex = currentQuestionIndex + step;
      if (nextIndex < 0) { showQuestion(0); return; }
      if (nextIndex >= $('div.question-div[data-q]').length) {
        if (typeof window.goToStep === 'function') window.goToStep(2);
        return;
      }
      showQuestion(nextIndex);
    }

    function nextQuestion(current_index) {
      if (!isNaN(current_index)) currentQuestionIndex = current_index;
      else {
        var activeIndex = parseInt($('div.question-div.active[data-q]').data('q'), 10);
        if (!isNaN(activeIndex)) currentQuestionIndex = activeIndex;
      }
      if (!validateActiveQuestion()) return;
      moveQuestion(1);
    }

    function prevQuestion() {
      var activeIndex = parseInt($('div.question-div.active[data-q]').data('q'), 10);
      if (!isNaN(activeIndex)) currentQuestionIndex = activeIndex;
      moveQuestion(-1);
    }

    window.mbBuildStructuredQuestionAnswers = buildStructuredQuestionAnswers;
    window.mbGetAnswerByKeywords = getAnswerByKeywords;
    window.mbGetTotalQuestions = function() { return questionDefinitions.length; };
    window.nextQuestion = nextQuestion;
    window.prevQuestion = prevQuestion;

    $(document).on('click', '.single-choice-radio-button', function() {
      $(this).closest('div.single-choice-group').find('.single-choice-radio-button').removeClass('selected');
      var main_div = $(this).closest('div.question-div');
      var current_question = parseInt(main_div.data('q'), 10);
      var qId = main_div.data('question-id');
      $(this).addClass('selected');
      if (qId) questionAnswers[qId] = String($(this).data('value') || '');
      main_div.find('.js-question-error').addClass('hidden');
      if (!isNaN(current_question)) setTimeout(function() { nextQuestion(current_question); }, 180);
    });
    $(document).on('click', '.js-prev-question', prevQuestion);
    $(document).on('click', '.js-next-question', function() { nextQuestion(); });
    $(document).on('click', '.js-continue-scheduling', function() {
      if (!validateActiveQuestion()) return;
      if (typeof window.goToStep === 'function') window.goToStep(2);
    });
    $(document).on('change', '.js-select2-question, .js-question-file, .js-question-toggle', async function() {
      var $question = $(this).closest('.question-div');
      var qId = $question.data('question-id');
      if (!qId) return;
      if ($(this).hasClass('js-question-toggle')) questionAnswers[qId] = $(this).is(':checked');
      else if ($(this).hasClass('js-question-file')) {
        var file = this.files && this.files.length ? this.files[0] : null;
        questionAnswers[qId] = '';
        if (file) {
          try { questionAnswers[qId] = await uploadQuestionImage(file, qId); }
          catch (error) {
            $question.find('.js-question-error').removeClass('hidden').text(error.message || 'Image upload failed.');
            return;
          }
        }
      } else questionAnswers[qId] = String($(this).val() || '').trim();
      $question.find('.js-question-error').addClass('hidden');
    });
    $(document).on('input', '.js-question-input', function() {
      var $question = $(this).closest('.question-div');
      var qId = $question.data('question-id');
      if (!qId) return;
      questionAnswers[qId] = String($(this).val() || '').trim();
      $question.find('.js-question-error').addClass('hidden');
    });

    $(function() {
      renderQuestions();
      if (!questionDefinitions.length) {
        $('.js-back-to-questions').addClass('hidden');
        if (typeof window.mbOnQuestionsReady === 'function') window.mbOnQuestionsReady(true);
        return;
      }
      $('.js-select2-question').select2({ width: '100%', minimumResultsForSearch: Infinity });
      $('.dropify').dropify();
      if (typeof window.mbOnQuestionsReady === 'function') window.mbOnQuestionsReady(false);
    });
  })(jQuery);
  </script>

  <script>
  (function() {
    'use strict';

    // ── Design data ──
    const designs = [
      { title: "Dragon Sleeve", style: "Japanese", price: "€800 — €1,200", time: "12-16 hours", sessions: "3-4", lowerPrice: 800, size: "Full Sleeve" },
      { title: "Rose Mandala", style: "Geometric", price: "€300 — €500", time: "4-5 hours", sessions: "1-2", lowerPrice: 300, size: "Medium (10-20cm)" },
      { title: "Skull & Serpent", style: "Neo-Traditional", price: "€450 — €700", time: "6-8 hours", sessions: "2", lowerPrice: 450, size: "Large (20-35cm)" },
      { title: "Ocean Waves", style: "Watercolor", price: "€350 — €550", time: "5-6 hours", sessions: "1-2", lowerPrice: 350, size: "Medium (10-20cm)" },
      { title: "Botanical Forearm", style: "Fine Line", price: "€250 — €400", time: "3-4 hours", sessions: "1", lowerPrice: 250, size: "Small (5-10cm)" },
      { title: "Celestial Back Piece", style: "Illustrative", price: "€1,000 — €1,800", time: "15-20 hours", sessions: "4-5", lowerPrice: 1000, size: "Full Back" },
    ];

    const params = new URLSearchParams(window.location.search);
    const designIdx = parseInt(params.get('design') || '0', 10);
    let design;
    if (params.get('title')) {
      const priceStr = params.get('price') || '€300 — €500';
      const lp = parseInt(priceStr.replace(/[^0-9]/g, ''), 10) || 300;
      design = { title: params.get('title'), style: params.get('style') || 'Custom', price: priceStr, time: params.get('time') || '—', sessions: params.get('sessions') || '—', lowerPrice: lp, size: params.get('size') || 'Medium (10-20cm)' };
    } else {
      design = designs[designIdx] || designs[0];
    }

    const artistName = params.get('artist') || 'Julian Ink';
    const studioName = 'Black Lotus Studio';
    const studioAddress = 'Athens, Greece';
    const initials = artistName.split(' ').map(w => w[0]).join('').toUpperCase().slice(0,2);

    let currentStep = 1;
    let currentQuestion = 0;
    function getTotalQuestions() {
      return (typeof window.mbGetTotalQuestions === 'function') ? window.mbGetTotalQuestions() : 0;
    }
    let currentReg = 0;
    const totalRegs = 4;
    let mcConsultType = null;

    const consultTypeLabels = { video: { emoji: '📹', label: 'Video Call', desc: 'Video Call on Inkjin' }, phone: { emoji: '📞', label: 'Phone Call', desc: 'Phone Call' }, studio: { emoji: '🏠', label: 'In-Studio Visit', desc: 'At ' + studioName + ', ' + studioAddress } };

    // ── Populate design info ──
    function populateDesignInfo() {
      const setText = (sel, text) => document.querySelectorAll(sel).forEach(el => el.textContent = text);
      setText('.mc-designTitle, #qDesignTitle', design.title);
      setText('.mc-designStyle', design.style);
      setText('.mc-designPrice, #qDesignPrice', design.price);
      setText('#confManagedArtist, #payManagedArtist, .mc-artistName', artistName);
      setText('.mc-artistAvatar', initials);
      setText('.mc-studioName', studioName);
      setText('.mc-studioAddress', studioAddress);
      document.title = 'Book ' + design.title + ' | Inkjin';
    }
    populateDesignInfo();

    function showStep2() {
      const el = document.getElementById('step2ManagedConsult');
      if (el) el.classList.add('active');
    }

    // ── Step Navigation ──
    window.goToStep = function(step, reverse) {
      document.querySelectorAll('.step-panel').forEach(p => p.classList.remove('active','reverse'));
      currentStep = step;
      if (step === 1) { const p = document.getElementById('stepQuestions'); if(reverse) p.classList.add('reverse'); p.classList.add('active'); }
      else if (step === 2) showStep2();
      else if (step === 3) { const p = document.getElementById('stepRegister'); if(reverse) p.classList.add('reverse'); p.classList.add('active'); currentReg = 0; showRegScreen(0); }
      else if (step === 4) { const p = document.getElementById('stepPayment'); if(reverse) p.classList.add('reverse'); p.classList.add('active'); populatePaymentStep(); }
      else if (step === 5) document.getElementById('stepConfirmation').classList.add('active');
      updateProgressDots(); updateTopProgress();
      window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    function updateProgressDots() {
      document.querySelectorAll('.progress-step').forEach(el => {
        const s = parseInt(el.dataset.step);
        el.classList.remove('active','completed');
        if (s === currentStep) el.classList.add('active');
        else if (s < currentStep) el.classList.add('completed');
      });
      document.querySelectorAll('.progress-line').forEach(el => {
        el.classList.toggle('completed', parseInt(el.dataset.line) < currentStep);
      });
    }

    function updateTopProgress() {
      let pct = 0;
      if (currentStep === 1) {
        var totalQ = Math.max(1, getTotalQuestions());
        pct = 5 + (currentQuestion / totalQ) * 15;
      }
      else if (currentStep === 2) pct = 25;
      else if (currentStep === 3) pct = 40 + (currentReg / totalRegs) * 20;
      else if (currentStep === 4) pct = 70;
      else if (currentStep === 5) pct = 100;
      document.getElementById('topProgressBar').style.width = pct + '%';
    }

    window.mbSyncQuestionProgress = function(idx) { currentQuestion = idx; updateTopProgress(); };
    window.mbOnQuestionsReady = function(skipQuestions) {
      if (skipQuestions) goToStep(2);
    };

    // ── Register ──
    function showRegScreen(idx) {
      document.querySelectorAll('#stepRegister .tf-screen').forEach(s => s.classList.remove('active','reverse'));
      currentReg = idx;
      const t = document.getElementById('reg-' + idx);
      if (t) t.classList.add('active');
      if (idx === 3) { const e = document.getElementById('bdEmail').value.trim(); document.getElementById('bdAuthEmail').textContent = e; document.getElementById('bdAuthLoginEmail').textContent = e; }
      updateTopProgress();
    }
    window.nextReg = function() {
      if (currentReg === 0 && !document.getElementById('bdName').value.trim()) { shakeInput(document.getElementById('bdName')); return; }
      if (currentReg === 1) { const e = document.getElementById('bdEmail').value.trim(); if (!e || !e.includes('@')) { shakeInput(document.getElementById('bdEmail')); return; } }
      if (currentReg === 2 && !document.getElementById('bdPhone').value.trim()) { shakeInput(document.getElementById('bdPhone')); return; }
      if (currentReg + 1 < totalRegs) showRegScreen(currentReg + 1);
    };
    window.prevReg = function() { if (currentReg <= 0) { goToStep(2, true); return; } showRegScreen(currentReg - 1); };
    window.finishRegister = function() { goToStep(4); };
    function shakeInput(el) { el.style.animation = 'none'; el.offsetHeight; el.style.animation = 'shake 0.4s ease'; el.style.borderColor = '#ba1a1a'; setTimeout(() => { el.style.borderColor = ''; el.style.animation = ''; }, 800); }
    window.toggleBdAuth = function() { document.getElementById('bdAuthCreate').classList.toggle('hidden'); document.getElementById('bdAuthLogin').classList.toggle('hidden'); };
    window.selectPill = function(btn, cid) { document.querySelectorAll('#' + cid + ' .pill-btn').forEach(b => b.classList.remove('selected')); btn.classList.add('selected'); };
    window.toggleTimePref = function(btn) { btn.classList.toggle('selected'); };


    // ══════════════════════════════
    // MANAGED + CONSULTATION
    // ══════════════════════════════
    window.selectMcConsultType = function(card, type) {
      document.querySelectorAll('#mcConsultTypeCards .consult-type-card').forEach(c => c.classList.remove('selected'));
      card.classList.add('selected');
      mcConsultType = type;
      const info = consultTypeLabels[type];
      document.getElementById('mcSumLine1').textContent = info.emoji + ' A consultation (' + info.label + ')';
      document.getElementById('mcAvailSection').classList.remove('hidden');
    };

    let mcPrefCount = 2;
    window.addMcPreference = function() {
      if (mcPrefCount >= 5) return;
      mcPrefCount++;
      const block = document.createElement('div');
      block.className = 'pref-block'; block.dataset.pref = mcPrefCount - 1;
      block.innerHTML = '<p class="text-xs font-bold text-primary uppercase tracking-wider mb-3">Preference ' + mcPrefCount + '</p><div class="grid grid-cols-1 sm:grid-cols-2 gap-3"><div><label class="text-xs font-semibold text-on-surface-variant mb-1 block">Date</label><input type="date" class="mc-pref-date w-full border border-outline-variant/30 bg-white rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30"></div><div><label class="text-xs font-semibold text-on-surface-variant mb-1 block">Time of day</label><div class="flex flex-wrap gap-1.5"><button class="time-pref-pill" data-value="Morning" onclick="toggleTimePref(this)">Morning</button><button class="time-pref-pill" data-value="Afternoon" onclick="toggleTimePref(this)">Afternoon</button><button class="time-pref-pill" data-value="Evening" onclick="toggleTimePref(this)">Evening</button></div></div></div>';
      document.getElementById('mcPrefBlocks').appendChild(block);
      if (mcPrefCount >= 5) document.getElementById('mcAddPrefBtn').classList.add('hidden');
    };

    // ── Payment Step ──
    function populatePaymentStep() {
      document.getElementById('paymentManagedMode').classList.remove('hidden');
      buildManagedReview();
    }

    function buildManagedReview() {
      const prefs = [];
      document.querySelectorAll('#mcPrefBlocks .pref-block').forEach(block => {
        const date = block.querySelector('.mc-pref-date')?.value || '';
        const times = [];
        block.querySelectorAll('.time-pref-pill.selected').forEach(p => times.push(p.dataset.value));
        if (date) prefs.push(date + (times.length ? ' (' + times.join(', ') + ')' : ''));
      });
      const days = [];
      document.querySelectorAll('#mcDayPills .day-pill.selected').forEach(d => days.push(d.dataset.value));
      const flex = document.querySelector('#mcFlexPills .pill-btn.selected')?.dataset.value || '—';
      const name = document.getElementById('bdName').value.trim() || '—';
      const email = document.getElementById('bdEmail').value.trim() || '—';
      const phone = document.getElementById('bdPhone').value.trim() || '—';

      let html = '<div class="space-y-2 text-sm">' +
        '<div class="flex justify-between"><span class="text-on-surface-variant">Design</span><span class="font-semibold">' + design.title + '</span></div>' +
        '<div class="flex justify-between"><span class="text-on-surface-variant">Placement</span><span class="font-semibold">' + ((typeof window.mbGetAnswerByKeywords === 'function' && window.mbGetAnswerByKeywords(['placement', 'body part', 'where'])) || '—') + '</span></div>' +
        '<div class="flex justify-between"><span class="text-on-surface-variant">Size</span><span class="font-semibold">' + ((typeof window.mbGetAnswerByKeywords === 'function' && window.mbGetAnswerByKeywords(['size', 'cm', 'inch'])) || '—') + '</span></div>';

      var structuredAnswers = (typeof window.mbBuildStructuredQuestionAnswers === 'function') ? window.mbBuildStructuredQuestionAnswers() : {};
      Object.keys(structuredAnswers).forEach(function(key) {
        var item = structuredAnswers[key];
        if (!item || !item.question) return;
        var answerText = item.answer;
        if (typeof answerText === 'boolean') answerText = answerText ? 'Yes' : 'No';
        if (Array.isArray(answerText)) answerText = answerText.join(', ');
        html += '<div class="flex justify-between gap-4"><span class="text-on-surface-variant shrink-0">' + item.question + '</span><span class="font-semibold text-right">' + (answerText || '—') + '</span></div>';
      });

      if (mcConsultType) {
        const info = consultTypeLabels[mcConsultType];
        html += '<div class="flex justify-between"><span class="text-on-surface-variant">Consultation Type</span><span class="font-semibold">' + info.emoji + ' ' + info.label + '</span></div>';
        const gap = document.querySelector('#mcGapPills .pill-btn.selected')?.dataset.value || '—';
        html += '<div class="flex justify-between"><span class="text-on-surface-variant">Session Gap</span><span class="font-semibold">' + gap + '</span></div>';
      }

      html += '<div class="flex justify-between"><span class="text-on-surface-variant">Preferred Dates</span><span class="font-semibold text-right">' + (prefs.join('<br>') || '—') + '</span></div>' +
        '<div class="flex justify-between"><span class="text-on-surface-variant">Preferred Days</span><span class="font-semibold">' + (days.join(', ') || '—') + '</span></div>' +
        '<div class="flex justify-between"><span class="text-on-surface-variant">Flexibility</span><span class="font-semibold">' + flex + '</span></div>' +
        '<hr class="border-outline-variant/20">' +
        '<div class="flex justify-between"><span class="text-on-surface-variant">Name</span><span class="font-semibold">' + name + '</span></div>' +
        '<div class="flex justify-between"><span class="text-on-surface-variant">Email</span><span class="font-semibold">' + email + '</span></div>' +
        '<div class="flex justify-between"><span class="text-on-surface-variant">Phone</span><span class="font-semibold">' + phone + '</span></div>' +
        '</div>';
      document.getElementById('managedReview').innerHTML = html;
    }

    // ── Confirm Booking ──
    window.confirmBooking = function() {
      document.getElementById('processingView').classList.remove('hidden');
      document.getElementById('confirmationManaged').classList.add('hidden');
      document.getElementById('processingText').textContent = 'Submitting your booking request…';
      goToStep(5);

      setTimeout(() => {
        document.getElementById('processingView').classList.add('hidden');
        document.getElementById('confManagedTitle').textContent = 'Availability Submitted! 🎉';
        document.getElementById('confManagedDesc').innerHTML = artistName + ' will review your availability and confirm both your consultation and tattoo session times. You\'ll receive an email once both appointments are confirmed.';
        const info = consultTypeLabels[mcConsultType] || consultTypeLabels.video;
        document.getElementById('confManagedWhatsNext').innerHTML =
          '<li class="flex items-start gap-2"><span class="text-primary mt-0.5">✦</span> The artist will review your availability and schedule both appointments</li>' +
          '<li class="flex items-start gap-2"><span class="text-primary mt-0.5">✦</span> You\'ll receive an email with both confirmed dates & times</li>' +
          '<li class="flex items-start gap-2"><span class="text-primary mt-0.5">✦</span> Your ' + info.label.toLowerCase() + ' consultation will be scheduled first</li>' +
          '<li class="flex items-start gap-2"><span class="text-primary mt-0.5">✦</span> A deposit may be required after consultation to secure your tattoo session</li>' +
          '<li class="flex items-start gap-2"><span class="text-primary mt-0.5">✦</span> You can message the artist if anything changes</li>';
        document.getElementById('confirmationManaged').classList.remove('hidden');
      }, 2000);
    };

    // Keyboard
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' && !e.shiftKey && document.activeElement?.tagName !== 'TEXTAREA') { e.preventDefault(); if (currentStep === 3) nextReg(); }
    });

    // Init — keep URL clean (no step hashes)
    if (window.location.hash) {
      history.replaceState(null, '', window.location.pathname + window.location.search);
    }

    // ── Booking Status Check ──
    const statusParam = params.get('status');
    if (statusParam === 'closed') {
      document.getElementById('bookingMainContent').classList.add('hidden');
      document.querySelector('header').classList.add('hidden');
      document.getElementById('topProgressBar').classList.add('hidden');
      const overlay = document.getElementById('bookingsClosedOverlay');
      overlay.classList.remove('hidden');
    }

  })();
  </script>
</body>
</html>
