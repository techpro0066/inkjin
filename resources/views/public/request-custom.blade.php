<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Request a Custom Tattoo | Inkjin</title>
  <meta name="description" content="Request a custom tattoo design — describe your vision, upload references, and submit your request.">
  <link rel="icon" href="{{ asset('design/images/icons/favicon.png') }}">
  <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
  <link href="{{ asset('design/css/inkjin_bookpay.css') }}" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
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

    /* Progress bar */
    .tf-progress { position: fixed; top: 0; left: 0; height: 3px; background: #310f7a; transition: width 0.4s ease; z-index: 100; }

    @keyframes tfSlideIn {
      from { opacity: 0; transform: translateY(40px); }
      to { opacity: 1; transform: translateY(0); }
    }
    @keyframes tfSlideInReverse {
      from { opacity: 0; transform: translateY(-40px); }
      to { opacity: 1; transform: translateY(0); }
    }

    /* Pill button */
    .pill-btn {
      padding: 0.75rem 1.5rem;
      border-radius: 9999px;
      border: 2px solid #cac4d3;
      font-size: 0.95rem;
      font-weight: 600;
      color: #494552;
      cursor: pointer;
      transition: all 0.15s;
      background: white;
    }
    .pill-btn:hover { border-color: #310f7a; color: #310f7a; }
    .pill-btn.selected { background: #310f7a; color: white; border-color: #310f7a; }

    /* Big button pair */
    .big-choice {
      padding: 1.25rem 2.5rem;
      border-radius: 1rem;
      border: 2px solid #cac4d3;
      font-size: 1.1rem;
      font-weight: 700;
      cursor: pointer;
      transition: all 0.15s;
      background: white;
      min-width: 140px;
      text-align: center;
    }
    .big-choice:hover { border-color: #310f7a; color: #310f7a; }
    .big-choice.selected { background: #310f7a; color: white; border-color: #310f7a; }

    /* Upload zone */
    .upload-zone {
      border: 2px dashed #cac4d3;
      border-radius: 1rem;
      padding: 2.5rem;
      text-align: center;
      cursor: pointer;
      transition: all 0.2s;
    }
    .upload-zone:hover { border-color: #310f7a; background: #f8f1fb; }
    .upload-zone.dragover { border-color: #310f7a; background: #f2ecf5; }

    /* Toggle for auth */
    .auth-toggle { color: #310f7a; cursor: pointer; font-weight: 600; text-decoration: underline; }

    /* Review item */
    .review-row { display: flex; justify-content: space-between; padding: 0.75rem 0; border-bottom: 1px solid #ece6ef; }
    .review-row:last-child { border-bottom: none; }

    /* Checkmark animation */
    @keyframes checkDraw { from { stroke-dashoffset: 48; } to { stroke-dashoffset: 0; } }
    @keyframes circleDraw { from { stroke-dashoffset: 200; } to { stroke-dashoffset: 0; } }
    @keyframes scaleBounce { 0% { transform: scale(0.5); opacity: 0; } 60% { transform: scale(1.15); } 100% { transform: scale(1); opacity: 1; } }
    .check-circle { animation: circleDraw 0.6s ease-out forwards, scaleBounce 0.5s ease-out; stroke-dasharray: 200; }
    .check-mark { animation: checkDraw 0.4s ease-out 0.4s forwards; stroke-dasharray: 48; stroke-dashoffset: 48; }

    /* Spinner */
    @keyframes spin { to { transform: rotate(360deg); } }
    .spinner { width: 32px; height: 32px; border: 3px solid #ece6ef; border-top-color: #310f7a; border-radius: 50%; animation: spin 0.8s linear infinite; }

    .question-div { display: none; min-height: 50vh; align-items: center; justify-content: center; padding: 1rem 0 2rem; width: 100%; }
    .question-div.active { display: flex; animation: tfSlideIn 0.4s ease-out; }
    .question-div.active.reverse { animation: tfSlideInReverse 0.4s ease-out; }
    .single-choice-radio-button { padding: 0.75rem 1.5rem; border-radius: 9999px; border: 2px solid #cac4d3; font-size: 0.95rem; font-weight: 600; color: #494552; cursor: pointer; transition: all 0.15s; background: white; }
    .single-choice-radio-button:hover { border-color: #310f7a; color: #310f7a; }
    .single-choice-radio-button.selected { background: #310f7a; color: white; border-color: #310f7a; }
    .single-choice-radio-button.option-other { background: #f2ecf5; border-color: #b69fff; color: #310f7a; }
    .single-choice-radio-button.option-other:hover { background: #e8ddff; border-color: #664db1; color: #21005e; }
    .single-choice-radio-button.option-other.selected { background: #310f7a; border-color: #310f7a; color: #ffffff; }
    .style-other-picker { margin-top: 1rem; }
    .style-other-search { width: 100%; border: 1px solid rgba(122, 117, 131, 0.35); background: #fdf7ff; border-radius: 1rem; padding: 0.875rem 1rem; font-size: 1rem; color: #1c1b21; }
    .style-other-search:focus { outline: none; box-shadow: 0 0 0 2px rgba(49, 15, 122, 0.25); border-color: #310f7a; }
    .style-other-results { margin-top: 0.75rem; height: calc(0.75rem * 2 + 2.75rem * 3 + 0.5rem * 2); max-height: calc(0.75rem * 2 + 2.75rem * 3 + 0.5rem * 2); overflow-y: auto; border: 1px solid #ece6ef; border-radius: 1rem; background: white; padding: 0.75rem; display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 0.5rem; align-content: start; }
    .style-other-results .style-other-empty { grid-column: 1 / -1; }
    .style-other-result-item { display: flex; align-items: center; justify-content: center; width: 100%; min-height: 2.75rem; text-align: center; padding: 0.65rem 0.5rem; font-size: 0.82rem; font-weight: 600; color: #494552; background: white; border: 2px solid #cac4d3; border-radius: 9999px; cursor: pointer; transition: background 0.15s, color 0.15s, border-color 0.15s; line-height: 1.2; word-break: break-word; }
    .style-other-result-item:hover { background: #f8f1fb; color: #310f7a; border-color: #310f7a; }
    .style-other-result-item.selected { background: #310f7a; color: #ffffff; border-color: #310f7a; }
    .question-kicker {
      display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.35rem 0.85rem; border-radius: 9999px;
      border: 1px solid #ddd0ff; background: linear-gradient(135deg, #f8f1fb 0%, #f2ecf5 100%);
      color: #310f7a; font-size: 0.78rem; font-weight: 700; letter-spacing: 0.02em; margin-bottom: 0.75rem;
    }
    .question-kicker .dot { width: 0.45rem; height: 0.45rem; border-radius: 9999px; background: #310f7a; opacity: 0.9; }
    .select2-container--default .select2-selection--single {
      height: 58px; border: 1px solid rgba(122, 117, 131, 0.35); border-radius: 1rem; background: #ffffff;
      display: flex; align-items: center;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
      color: #1c1b21; line-height: 58px; font-size: 1rem; padding-left: 1rem;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow { height: 56px; right: 10px; }
    .q-toggle-row { display: flex; align-items: flex-start; gap: 0.85rem; padding: 1rem; border: 1px solid rgba(122, 117, 131, 0.32); border-radius: 0.9rem; background: #ffffff; }
    .q-toggle-control { position: relative; display: inline-flex; width: 54px; min-width: 54px; height: 31px; flex-shrink: 0; }
    .q-toggle-label { font-size: 0.95rem; color: #1c1b21; line-height: 1.45; font-weight: 500; flex: 1; }
    .q-toggle-input { position: absolute; opacity: 0; width: 0; height: 0; pointer-events: none; }
    .q-toggle-ui { position: relative; display: inline-block; width: 54px; height: 31px; border-radius: 9999px; background: #a8c7ff; transition: all 0.2s ease; cursor: pointer; }
    .q-toggle-ui::after { content: ""; position: absolute; top: 3px; left: 3px; width: 25px; height: 25px; border-radius: 50%; background: #ffffff; box-shadow: 0 2px 7px rgba(0, 0, 0, 0.2); transition: transform 0.2s ease; }
    .q-toggle-input:checked + .q-toggle-ui { background: linear-gradient(90deg, #1e6bff 0%, #3f86ff 100%); }
    .q-toggle-input:checked + .q-toggle-ui::after { transform: translateX(23px); }

    /* Social buttons */
    .social-btn {
      display: flex; align-items: center; justify-content: center; gap: 0.75rem;
      padding: 0.875rem 1.5rem; border-radius: 0.75rem; font-weight: 600; font-size: 0.95rem;
      border: 2px solid #e6e0ea; cursor: pointer; transition: all 0.15s; background: white; width: 100%;
    }
    .social-btn:hover { border-color: #cac4d3; background: #f8f1fb; }

    .pref-block { background: white; border: 1px solid #e6e0ea; border-radius: 1rem; padding: 1.25rem; }
    .pref-block-header { display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; margin-bottom: 0.75rem; }
    .pref-remove-btn { display: inline-flex; align-items: center; gap: 0.25rem; font-size: 0.75rem; font-weight: 600; color: #ba1a1a; cursor: pointer; background: none; border: none; padding: 0; }
    .day-pill { padding: 0.5rem 1rem; border-radius: 9999px; border: 1.5px solid #cac4d3; font-size: 0.8rem; font-weight: 600; cursor: pointer; transition: all 0.15s; background: white; }
    .day-pill:hover { border-color: #310f7a; color: #310f7a; }
    .day-pill.selected { background: #310f7a; color: white; border-color: #310f7a; }
    .time-pref-pill { padding: 0.5rem 1rem; border-radius: 9999px; border: 1.5px solid #cac4d3; font-size: 0.8rem; font-weight: 600; cursor: pointer; transition: all 0.15s; background: white; }
    .time-pref-pill:hover { border-color: #310f7a; color: #310f7a; }
    .time-pref-pill.selected { background: #310f7a; color: white; border-color: #310f7a; }
    .progress-step .step-dot { width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; font-weight: 700; border: 2px solid #cac4d3; color: #cac4d3; transition: all 0.3s; }
    .progress-step.active .step-dot, .progress-step.completed .step-dot { border-color: #310f7a; background: #310f7a; color: white; }
    .progress-step .step-label { font-size: 0.7rem; color: #7a7583; margin-top: 4px; transition: color 0.3s; white-space: nowrap; }
    .progress-step.active .step-label { color: #310f7a; font-weight: 600; }
    .progress-step.completed .step-label { color: #310f7a; }
    .progress-line { height: 2px; background: #cac4d3; flex: 1; margin: 0 4px; margin-top: -12px; transition: background 0.3s; min-width: 12px; }
    .progress-line.completed { background: #310f7a; }
    .tf-screen { display: none; width: 100%; }
    .tf-screen.active { display: block; animation: fadeUp 0.35s ease-out; }
    .tf-screen.active.reverse { animation: fadeDown 0.35s ease-out; }
    .tf-screen[data-screen="0"].active { display: flex; min-height: calc(100vh - 64px); align-items: center; justify-content: center; padding: 2rem 1rem; animation: tfSlideIn 0.4s ease-out; }
    @keyframes fadeUp { from { opacity: 0; transform: translateY(24px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes fadeDown { from { opacity: 0; transform: translateY(-24px); } to { opacity: 1; transform: translateY(0); } }
  </style>
</head>
<body class="bg-surface text-on-surface min-h-screen">

  <!-- CUSTOM CLOSED OVERLAY (hidden by default) -->
  <div id="customClosedOverlay" class="hidden">
    <div class="min-h-screen flex items-center justify-center p-6">
      <div class="text-center max-w-md">
        <span class="material-symbols-outlined text-6xl text-on-surface-variant mb-4">event_busy</span>
        <h2 class="text-2xl font-bold text-on-surface mb-2" id="customClosedTitle">Not Accepting Custom Requests</h2>
        <p class="text-on-surface-variant mb-6" id="customClosedDesc">This artist is not accepting custom requests right now. Check back soon or browse their available designs.</p>
        <a href="{{ $artistProfileUrl }}" class="inline-flex items-center gap-2 px-6 py-3 bg-primary text-on-primary rounded-full font-semibold text-sm hover:bg-primary-container transition-colors">
          <span class="material-symbols-outlined text-lg">arrow_back</span> Back to Artist Page
        </a>
      </div>
    </div>
  </div>

  <!-- Progress bar -->
  <div class="tf-progress" id="progressBar" style="width: 0%"></div>

  <!-- Header -->
  <header class="border-b border-outline-variant/20 bg-white/70 backdrop-blur-md sticky top-0 z-50">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between">
      <a href="{{ $artistProfileUrl }}" class="flex items-center gap-2 text-primary font-extrabold text-xl tracking-tight">
        <img src="{{ asset('design/images/logo-blue.png') }}" alt="inkjin" class="h-7">
      </a>
      <div class="flex items-center gap-3 flex-wrap justify-end">
        <a href="{{ $artistProfileUrl }}" class="flex items-center gap-1 text-sm text-on-surface-variant hover:text-primary transition-colors">
          <span class="material-symbols-outlined text-[18px]">arrow_back</span> Back to {{ $artistName }}
        </a>
      </div>
    </div>
  </header>

  @php
    $rcArtistInitials = strtoupper(substr($userDetail->user->first_name ?? 'A', 0, 1) . substr($userDetail->user->last_name ?? 'A', 0, 1));
  @endphp

  <main class="max-w-4xl mx-auto px-4 sm:px-6 py-8" id="rcMainContent">

    <div id="rcBookingChrome" class="hidden">
      <div class="flex items-start justify-center mb-10" id="progressDots">
        <div class="progress-step text-center" data-step="1"><div class="step-dot mx-auto">1</div><div class="step-label">Questions</div></div>
        <div class="progress-line mt-4" data-line="1"></div>
        @if(!empty($isManagedScheduling))
        <div class="progress-step text-center" data-step="2"><div class="step-dot mx-auto">2</div><div class="step-label">Availability</div></div>
        <div class="progress-line mt-4" data-line="2"></div>
        <div class="progress-step text-center" data-step="3"><div class="step-dot mx-auto">3</div><div class="step-label">Register</div></div>
        <div class="progress-line mt-4" data-line="3"></div>
        <div class="progress-step text-center" data-step="4"><div class="step-dot mx-auto">4</div><div class="step-label">Review</div></div>
        <div class="progress-line mt-4" data-line="4"></div>
        <div class="progress-step text-center" data-step="5"><div class="step-dot mx-auto">5</div><div class="step-label">Submitted</div></div>
        @else
        <div class="progress-step text-center" data-step="2"><div class="step-dot mx-auto">2</div><div class="step-label">Register</div></div>
        <div class="progress-line mt-4" data-line="2"></div>
        <div class="progress-step text-center" data-step="3"><div class="step-dot mx-auto">3</div><div class="step-label">Review</div></div>
        <div class="progress-line mt-4" data-line="3"></div>
        <div class="progress-step text-center" data-step="4"><div class="step-dot mx-auto">4</div><div class="step-label">Submitted</div></div>
        @endif
      </div>

      <div class="bg-white rounded-2xl border border-outline-variant/20 p-4 sm:p-5 mb-6 flex flex-col sm:flex-row sm:items-center gap-4 sm:gap-5">
        <div class="w-full sm:w-24 h-24 sm:h-24 rounded-xl bg-gradient-to-br from-primary to-primary-container flex items-center justify-center flex-shrink-0 overflow-hidden">
          @if($userDetail->avatar && $userDetail->avatar != '')
            <img src="{{ asset($userDetail->avatar) }}" alt="{{ $artistName }}" class="w-full h-full object-cover">
          @else
            <span class="text-white text-2xl font-bold">{{ $rcArtistInitials }}</span>
          @endif
        </div>
        <div class="flex-1 min-w-0">
          <h2 class="text-base sm:text-lg font-bold text-on-surface mb-1">Custom Tattoo Request</h2>
          <div class="flex flex-wrap gap-x-3 sm:gap-x-4 gap-y-1 text-xs sm:text-sm text-on-surface-variant">
            <span class="flex items-center gap-1"><span class="material-symbols-outlined text-[16px]">brush</span> Custom design</span>
            <span class="flex items-center gap-1"><span class="material-symbols-outlined text-[16px]">schedule</span> Artist will review &amp; reply</span>
          </div>
          <div class="flex items-start sm:items-center gap-2 mt-2 text-xs sm:text-sm text-on-surface-variant">
            <div class="w-6 h-6 rounded-full bg-gradient-to-br from-primary to-primary-container flex items-center justify-center flex-shrink-0">
              <span class="text-white text-[10px] font-bold">{{ $rcArtistInitials }}</span>
            </div>
            <span class="leading-relaxed break-words">with <strong>{{ $artistName }}</strong>@if($userDetail->studio_name) at <strong>{{ $userDetail->studio_name }}</strong>@endif</span>
          </div>
        </div>
      </div>
    </div>

  <!-- Screen 0: Intro / Artist Card -->
  <div class="tf-screen active" data-screen="0">
    <div class="w-full max-w-xl text-center">
      <div class="inline-flex items-center gap-3 bg-white rounded-2xl border border-outline-variant/30 px-5 py-4 mb-8 shadow-sm">
        <div class="w-12 h-12 rounded-full bg-gradient-to-br from-primary to-primary-container flex items-center justify-center flex-shrink-0">
          @if($userDetail->avatar && $userDetail->avatar != '')
            <img src="{{ asset($userDetail->avatar) }}" alt="Avatar" class="w-full h-full object-cover rounded-full">
          @else
            <span class="text-white text-lg font-bold">{{strtoupper($userDetail->user->first_name[0])}}{{strtoupper($userDetail->user->last_name[0])}}</span>
          @endif
        </div>
        <div class="text-left">
          <p class="text-sm text-on-surface-variant">You're requesting a custom tattoo from</p>
          <p class="font-bold text-on-surface text-lg" id="artistNameDisplay">{{$artistName}}</p>
        </div>
      </div>
      <h1 class="text-3xl sm:text-4xl font-extrabold text-on-surface mb-4">Let's bring your<br>vision to life ✨</h1>
      <p class="text-on-surface-variant text-lg mb-8">We'll ask a few questions to help the artist understand exactly what you want.@if(!empty($isManagedScheduling)) You'll also share your preferred dates so {{ $artistName }} can confirm a time that works for both of you.@endif</p>
      <button onclick="nextScreen()" class="inline-flex items-center gap-2 px-8 py-4 bg-primary text-on-primary rounded-full font-bold text-base hover:bg-primary-container transition-colors shadow-lg shadow-primary/20">
        Get Started
        <span class="material-symbols-outlined text-[20px]">arrow_forward</span>
      </button>
      <p class="text-sm text-on-surface-variant mt-4">Takes about 2-3 minutes</p>
    </div>
  </div>

  <!-- Dynamic artist questions (same engine as managed booking) -->
  <div class="tf-screen" data-screen="questions" id="questionsStep">
    <div id="questionsMount" class="w-full max-w-xl mx-auto"></div>
  </div>

  @if(!empty($isManagedScheduling))
  <!-- Screen 9: Availability (managed scheduling) -->
  <div class="tf-screen" data-screen="9">
    <div class="w-full max-w-xl">
      <button type="button" onclick="prevScreen()" class="flex items-center gap-1 text-sm text-on-surface-variant hover:text-primary mb-4 transition-colors">
        <span class="material-symbols-outlined text-[18px]">arrow_back</span> Back to Questions
      </button>
      <h2 class="text-2xl sm:text-3xl font-bold text-on-surface mb-2">When are you available?</h2>
      <p class="text-on-surface-variant mb-6"><span id="rcManagedArtistHint">{{ $artistName }}</span> will confirm a time that works for both of you.</p>

      <div class="bg-white rounded-2xl border border-outline-variant/20 p-5 mb-6">
        <div id="rcPrefBlocks" class="space-y-4 mb-4">
          <div class="pref-block" data-pref="0">
            <div class="pref-block-header">
              <p class="text-xs font-bold text-primary uppercase tracking-wider pref-block-label">Preference 1 <span class="text-error">*</span></p>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label class="text-xs font-semibold text-on-surface-variant mb-1 block">Date</label>
                <input type="date" class="rc-pref-date w-full border border-outline-variant/30 bg-white rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30">
              </div>
              <div>
                <label class="text-xs font-semibold text-on-surface-variant mb-1 block">Time of day</label>
                <div class="flex flex-wrap gap-1.5">
                  <button type="button" class="time-pref-pill" data-value="Morning" onclick="rcToggleTimePref(this)">Morning</button>
                  <button type="button" class="time-pref-pill" data-value="Afternoon" onclick="rcToggleTimePref(this)">Afternoon</button>
                  <button type="button" class="time-pref-pill" data-value="Evening" onclick="rcToggleTimePref(this)">Evening</button>
                </div>
              </div>
            </div>
          </div>
        </div>
        <button type="button" id="rcAddPrefBtn" onclick="rcAddPreference()" class="text-sm text-primary font-semibold flex items-center gap-1 hover:underline mb-6">
          <span class="material-symbols-outlined text-[18px]">add</span> Add another preference
        </button>

        <div class="space-y-4">
          <div data-rc-field="days">
            <label class="text-xs font-semibold text-on-surface-variant mb-2 block">Preferred days of the week <span class="text-error">*</span></label>
            <div class="flex flex-wrap gap-1.5" id="rcDayPills">
              @foreach(['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $day)
              <button type="button" class="day-pill" data-value="{{ $day }}" onclick="rcToggleDayPref(this)">{{ $day }}</button>
              @endforeach
            </div>
            <p id="rcManagedDayError" class="hidden text-sm text-error mt-2">Please select at least one preferred day.</p>
          </div>
          <div>
            <label class="text-xs font-semibold text-on-surface-variant mb-1 block">Any dates to avoid?</label>
            <input type="text" id="rcManagedAvoid" placeholder="e.g., April 10-15, May 1st" class="w-full border border-outline-variant/30 bg-white rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30">
          </div>
          <div data-rc-field="flex">
            <label class="text-xs font-semibold text-on-surface-variant mb-2 block">How flexible are you? <span class="text-error">*</span></label>
            <div class="flex flex-wrap gap-2" id="rcFlexPills">
              <button type="button" class="pill-btn text-sm !py-2 !px-4" data-value="Very flexible" onclick="rcSelectPill(this,'rcFlexPills')">Very flexible</button>
              <button type="button" class="pill-btn text-sm !py-2 !px-4" data-value="Somewhat flexible" onclick="rcSelectPill(this,'rcFlexPills')">Somewhat flexible</button>
              <button type="button" class="pill-btn text-sm !py-2 !px-4" data-value="These are my only options" onclick="rcSelectPill(this,'rcFlexPills')">These are my only options</button>
            </div>
            <p id="rcManagedFlexError" class="hidden text-sm text-error mt-2">Please select how flexible you are.</p>
          </div>
          <div data-rc-field="urgency">
            <label class="text-xs font-semibold text-on-surface-variant mb-2 block">Urgency <span class="text-error">*</span></label>
            <div class="flex flex-wrap gap-2" id="rcUrgencyPills">
              <button type="button" class="pill-btn text-sm !py-2 !px-4" data-value="No rush" onclick="rcSelectPill(this,'rcUrgencyPills')">No rush</button>
              <button type="button" class="pill-btn text-sm !py-2 !px-4" data-value="Within 2 weeks" onclick="rcSelectPill(this,'rcUrgencyPills')">Within 2 weeks</button>
              <button type="button" class="pill-btn text-sm !py-2 !px-4" data-value="Within a month" onclick="rcSelectPill(this,'rcUrgencyPills')">Within a month</button>
              <button type="button" class="pill-btn text-sm !py-2 !px-4" data-value="ASAP" onclick="rcSelectPill(this,'rcUrgencyPills')">ASAP</button>
            </div>
            <p id="rcManagedUrgencyError" class="hidden text-sm text-error mt-2">Please select your urgency.</p>
          </div>
        </div>
      </div>

      <div class="flex items-center justify-between mt-6">
        <button type="button" onclick="nextScreen()" class="inline-flex items-center gap-2 px-6 py-3 bg-primary text-on-primary rounded-full font-bold text-sm hover:bg-primary-container transition-colors">
          Continue <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
        </button>
      </div>
    </div>
  </div>
  @endif

  <!-- Screen 10: Name -->
  <div class="tf-screen" data-screen="10">
    <div class="w-full max-w-xl">
      <h2 class="text-2xl sm:text-3xl font-bold text-on-surface mb-2">What's your name?</h2>
      <p class="text-on-surface-variant mb-6">So the artist knows who they're working with.</p>
      <input type="text" id="tfName" placeholder="Your full name"
        class="w-full border border-outline-variant/30 bg-white rounded-2xl px-6 py-4 text-lg text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
      <p id="tfNameError" class="text-sm text-error mt-2 hidden">This field is required.</p>
      <div class="flex items-center justify-between mt-6">
        <button onclick="nextScreen()" class="inline-flex items-center gap-2 px-6 py-3 bg-primary text-on-primary rounded-full font-bold text-sm hover:bg-primary-container transition-colors">
          Next <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
        </button>
        <span class="text-sm text-on-surface-variant">press <strong>Enter ↵</strong></span>
      </div>
    </div>
  </div>

  <!-- Screen 11: Email -->
  <div class="tf-screen" data-screen="11">
    <div class="w-full max-w-xl">
      <h2 class="text-2xl sm:text-3xl font-bold text-on-surface mb-2">What's your email?</h2>
      <p class="text-on-surface-variant mb-6">We'll send request updates and artist replies here.</p>
      <input type="email" id="tfEmail" placeholder="you@example.com"
        class="w-full border border-outline-variant/30 bg-white rounded-2xl px-6 py-4 text-lg text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
      <p id="tfEmailError" class="text-sm text-error mt-2 hidden">This field is required.</p>
      <div class="flex items-center justify-between mt-6">
        <button onclick="nextScreen()" class="inline-flex items-center gap-2 px-6 py-3 bg-primary text-on-primary rounded-full font-bold text-sm hover:bg-primary-container transition-colors">
          Next <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
        </button>
        <span class="text-sm text-on-surface-variant">press <strong>Enter ↵</strong></span>
      </div>
    </div>
  </div>

  <!-- Screen 12: Phone -->
  <div class="tf-screen" data-screen="12">
    <div class="w-full max-w-xl">
      <h2 class="text-2xl sm:text-3xl font-bold text-on-surface mb-2">What's your phone number?</h2>
      <p class="text-on-surface-variant mb-6">In case the artist needs to reach you quickly.</p>
      <input type="tel" id="tfPhone" placeholder="+30 694 123 4567"
        class="w-full border border-outline-variant/30 bg-white rounded-2xl px-6 py-4 text-lg text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
      <p id="tfPhoneError" class="text-sm text-error mt-2 hidden">This field is required.</p>
      <div class="flex items-center justify-between mt-6">
        <button onclick="nextScreen()" class="inline-flex items-center gap-2 px-6 py-3 bg-primary text-on-primary rounded-full font-bold text-sm hover:bg-primary-container transition-colors">
          Next <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
        </button>
        <span class="text-sm text-on-surface-variant">press <strong>Enter ↵</strong></span>
      </div>
    </div>
  </div>

  <!-- Screen 13: Verify email (same flow as managed booking) -->
  <div class="tf-screen" data-screen="13">
    <div class="w-full max-w-md mx-auto">
      <div id="rcAuthCreate">
        <div class="text-center mb-6">
          <span class="material-symbols-outlined text-primary text-4xl mb-2">mark_email_read</span>
          <h2 class="text-2xl sm:text-3xl font-bold text-on-surface mb-2">Verify your email</h2>
          <p class="text-on-surface-variant">We are sending a secure 4-digit code to your email—check your inbox (and spam). You can resend below if you need a new code.</p>
        </div>
        <div class="mb-4 hidden">
          <label class="text-sm font-semibold text-on-surface-variant ml-1 mb-1 inline-block" for="rcOtpEmail">Email</label>
          <input type="email" id="rcOtpEmail" placeholder="you@example.com" class="w-full border border-outline-variant/30 bg-white rounded-2xl px-6 py-4 text-base text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30" readonly>
        </div>
        <div class="mb-4" id="rcOtpCodeWrap">
          <label class="text-sm font-semibold text-on-surface-variant ml-1 mb-1 inline-block" for="rcOtpCode">4-digit code</label>
          <input type="text" id="rcOtpCode" maxlength="4" inputmode="numeric" placeholder="1234" class="w-full border border-outline-variant/30 bg-white rounded-2xl px-6 py-4 text-lg tracking-[0.3em] text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
          <p id="rcOtpError" class="text-sm text-error mt-2 hidden">Please enter a valid 4-digit code.</p>
        </div>
        <p id="rcOtpStatus" class="hidden items-center gap-2 text-sm text-green-700 bg-green-50 border border-green-200 rounded-xl px-3 py-2 mb-3"></p>
        <div class="mb-5">
          <label class="text-sm font-semibold text-on-surface-variant ml-1" for="rc_referral_source">How did you hear about us? <span class="text-xs text-on-surface-variant font-normal">(optional)</span></label>
          <select id="rc_referral_source" name="referral_source" class="w-full text-sm border border-outline-variant/30 rounded-xl px-4 py-3 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30 mt-1.5">
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
          <button id="rcSendOtpBtn" type="button" onclick="sendRcOtp()" class="w-full py-3.5 bg-surface-container-high text-on-surface rounded-full font-bold text-sm hover:bg-surface-container transition-colors">Resend code</button>
          <button id="rcVerifyOtpBtn" type="button" onclick="verifyRcOtp()" class="w-full py-3.5 bg-primary text-on-primary rounded-full font-bold text-sm hover:bg-primary-container transition-colors shadow-lg shadow-primary/20">Verify & Continue</button>
        </div>
        <p id="rcConnectedUser" class="hidden text-center text-sm text-green-600 mb-4">Already connected user.</p>
        <p class="text-center text-sm text-on-surface-variant">Email verified once will stay connected for this request session.</p>
      </div>
      <div id="rcAuthLogin" class="hidden">
        <div class="text-center mb-6">
          <span class="material-symbols-outlined text-primary text-4xl mb-2">waving_hand</span>
          <h2 class="text-2xl sm:text-3xl font-bold text-on-surface mb-2">Welcome back!</h2>
          <p class="text-on-surface-variant">Log in to continue with your request.</p>
        </div>
        <div class="flex items-center gap-2 bg-surface-container rounded-xl px-4 py-3 mb-5">
          <span class="material-symbols-outlined text-primary text-[18px]">mail</span>
          <span class="text-sm text-on-surface" id="rcAuthLoginEmail">you@example.com</span>
          <span class="material-symbols-outlined text-green-500 text-[16px] ml-auto">check_circle</span>
        </div>
        <div class="mb-5">
          <input type="password" id="rcLoginPassword" placeholder="Enter your password" class="w-full border border-outline-variant/30 bg-white rounded-2xl px-6 py-4 text-lg text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
        </div>
        <button type="button" onclick="finishRcAuth()" class="w-full py-3.5 bg-primary text-on-primary rounded-full font-bold text-sm hover:bg-primary-container transition-colors shadow-lg shadow-primary/20 mb-3">Log In & Continue</button>
        <p class="text-center text-sm text-primary font-medium cursor-pointer mb-5">Forgot password?</p>
        <div class="flex items-center gap-3 mb-4">
          <div class="flex-1 h-px bg-outline-variant/30"></div>
          <span class="text-sm text-on-surface-variant">or</span>
          <div class="flex-1 h-px bg-outline-variant/30"></div>
        </div>
        <div class="space-y-2 mb-5">
          <button type="button" class="social-btn" onclick="finishRcAuth()"><svg class="w-5 h-5" viewBox="0 0 24 24"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 01-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg> Continue with Google</button>
          <button type="button" class="social-btn" onclick="finishRcAuth()"><svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.8-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19-.69.85-1.83 1.51-2.95 1.42-.15-1.15.41-2.35 1.05-3.11z"/></svg> Continue with Apple</button>
        </div>
        <p class="text-center text-sm text-on-surface-variant">Don't have an account? <span class="auth-toggle" onclick="toggleRcAuth()">Sign up</span></p>
      </div>
    </div>
  </div>

  <!-- Screen 14: Additional Notes -->
  <div class="tf-screen" data-screen="14">
    <div class="w-full max-w-xl">
      <h2 class="text-2xl sm:text-3xl font-bold text-on-surface mb-2">Anything else?</h2>
      <p class="text-on-surface-variant mb-6">Any final thoughts, questions, or details for the artist.</p>
      <textarea id="tfNotes" rows="4" placeholder="e.g., I'd love to see a sketch before we start…"
        class="w-full border border-outline-variant/30 bg-white rounded-2xl px-6 py-4 text-lg text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30 resize-none"></textarea>
      <div class="flex items-center justify-between mt-6">
        <button onclick="nextScreen()" class="inline-flex items-center gap-2 px-6 py-3 bg-primary text-on-primary rounded-full font-bold text-sm hover:bg-primary-container transition-colors">
          Review your request <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
        </button>
        <button onclick="nextScreen()" class="text-sm text-on-surface-variant hover:text-primary transition-colors font-medium">Skip →</button>
      </div>
    </div>
  </div>

  <!-- Screen 15: Review -->
  <div class="tf-screen" data-screen="15">
    <div class="w-full max-w-xl">
      <div class="text-center mb-6">
        <span class="material-symbols-outlined text-primary text-4xl mb-2">fact_check</span>
        <h2 class="text-2xl sm:text-3xl font-bold text-on-surface mb-2">Review your request</h2>
        <p class="text-on-surface-variant">Make sure everything looks good before submitting.</p>
      </div>
      <div class="bg-white rounded-2xl border border-outline-variant/20 p-5 mb-6">
        <div id="reviewContent">
          <!-- Populated by JS -->
        </div>
      </div>
      <p id="customSubmitError" class="hidden text-sm text-error mb-3 text-center"></p>
      <button type="button" onclick="submitRequest()" id="btnSubmit" class="w-full py-3.5 bg-primary text-on-primary rounded-full font-bold text-sm hover:bg-primary-container transition-colors shadow-lg shadow-primary/20 mb-3">
        Submit Request
      </button>
      <button type="button" onclick="prevScreen()" class="w-full mt-2 py-3 rounded-xl font-semibold text-sm text-on-surface-variant border border-outline-variant/30 hover:bg-surface-container transition-colors">
        Back to edit your answers
      </button>
    </div>
  </div>

  <!-- Screen 16: Success -->
  <div class="tf-screen" data-screen="16">
    <div class="w-full max-w-xl text-center">
      <!-- Loading state -->
      <div id="submitLoading">
        <div class="flex justify-center mb-4"><div class="spinner"></div></div>
        <p class="text-on-surface-variant">Submitting your request…</p>
      </div>
      <!-- Success state -->
      <div id="submitSuccess" class="hidden">
        <div class="flex justify-center mb-6">
          <svg width="80" height="80" viewBox="0 0 80 80" fill="none">
            <circle cx="40" cy="40" r="36" stroke="#22c55e" stroke-width="3" fill="none" class="check-circle"/>
            <path d="M24 42 L34 52 L56 30" stroke="#22c55e" stroke-width="3" fill="none" stroke-linecap="round" stroke-linejoin="round" class="check-mark"/>
          </svg>
        </div>
        <h2 class="text-2xl sm:text-3xl font-extrabold text-on-surface mb-3">Request Submitted! 🎉</h2>
        <p class="text-sm text-on-surface-variant mb-2">Reference: <strong id="successRequestRef">—</strong></p>
        <p class="text-on-surface-variant text-lg mb-8">
          <span id="successArtistName">{{ $artistName }}</span> will review your request @if(!empty($isManagedScheduling)) and confirm a time that works for both of you @endif. You'll receive updates via email.
        </p>
        <div class="bg-surface-container-low rounded-2xl p-5 mb-8 text-left">
          <h3 class="text-sm font-bold mb-3">What happens next?</h3>
          <ul class="space-y-2 text-sm text-on-surface-variant">
            <li class="flex items-start gap-2"><span class="text-primary mt-0.5">✦</span> The artist will review your request and references</li>
            <li class="flex items-start gap-2"><span class="text-primary mt-0.5">✦</span> They may reach out with questions or a custom quote</li>
            <li class="flex items-start gap-2"><span class="text-primary mt-0.5">✦</span> Once you agree, you'll schedule a session together</li>
            <li class="flex items-start gap-2"><span class="text-primary mt-0.5">✦</span> Check your email for updates and messages</li>
          </ul>
        </div>
        <a href="{{ $artistProfileUrl }}" class="inline-flex items-center gap-2 px-8 py-3.5 bg-primary text-on-primary rounded-full font-bold text-sm hover:bg-primary-container transition-colors shadow-lg shadow-primary/20">
          <span class="material-symbols-outlined text-[18px]">arrow_back</span>
          Back to Artist Page
        </a>
      </div>
    </div>
  </div>

  </main>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
  @include('public.partials.question-image-upload')
  <script src="{{ asset('js/question-answer-display.js') }}"></script>
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
  <script>
  (function($) {
    'use strict';
    var csrfToken = @json(csrf_token());
    var artistUsername = @json($artistUsername ?? '');
    var fallbackTattooSlug = @json($fallbackTattooSlug ?? '');
    var serverQuestions = @json($requiredBookingQuestions ?? $questions ?? []);
    var styleQuestionId = @json($styleQuestionId ?? 0);
    var hiddenStyleOptions = @json($hiddenStyleOptions ?? []);
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
        if (Array.isArray(answer) && answer.length === 0) return;
        output[String(q.id)] = { id: q.id, question: String(q.title || ''), type: String(q.type || 'input'), answer: answer };
      });
      return output;
    }

    async function uploadQuestionImage(file, questionId) {
      if (!fallbackTattooSlug) throw new Error('Image upload is not available for this artist.');
      var formData = new FormData();
      formData.append('image', file);
      formData.append('question_id', String(questionId || ''));
      formData.append('artist_username', artistUsername);
      formData.append('tattoo_slug', fallbackTattooSlug);
      var response = await fetch('/api/public/upload-booking-question-image', {
        method: 'POST',
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: formData
      });
      var data = await response.json();
      if (!response.ok || !data || !data.success) throw new Error((data && data.message) || 'Image upload failed.');
      return data.file_url || data.file_path || '';
    }

    if (window.QuestionImageField) {
      window.QuestionImageField.setUploadHandler(uploadQuestionImage);
    }

    $(document).on('qimages:updated', '.q-image-upload', function(_event, questionId, urls) {
      if (!questionId) return;
      questionAnswers[questionId] = Array.isArray(urls) ? urls.slice() : [];
    });

    function isStyleQuestionId(qId) {
      return styleQuestionId > 0 && String(qId) === String(styleQuestionId);
    }

    function buildStyleOtherPickerHtml() {
      return '<div class="js-style-other-picker style-other-picker hidden">' +
        '<input type="text" class="js-style-other-search style-other-search" placeholder="Choose style from this list" autocomplete="off">' +
        '<div class="js-style-other-results style-other-results"></div>' +
        '</div>';
    }

    function renderStyleOtherResults($container, query) {
      var needle = String(query || '').trim().toLowerCase();
      var filtered = (Array.isArray(hiddenStyleOptions) ? hiddenStyleOptions : []).filter(function(name) {
        return !needle || String(name).toLowerCase().indexOf(needle) !== -1;
      });
      var $results = $container.find('.js-style-other-results');
      if (!filtered.length) {
        $results.html('<p class="style-other-empty text-sm text-on-surface-variant py-3 px-2 text-center">No matching styles found.</p>');
        return;
      }
      var currentAnswer = String(questionAnswers[$container.data('question-id')] || '').trim();
      $results.html(filtered.map(function(name) {
        var selectedClass = name === currentAnswer ? ' selected' : '';
        return '<button type="button" class="js-style-other-result-item style-other-result-item' + selectedClass + '" data-value="' + escapeHtml(name) + '">' + escapeHtml(name) + '</button>';
      }).join(''));
    }

    function restoreStyleQuestionUi($div) {
      if (!$div.length || !isStyleQuestionId($div.data('question-id'))) return;
      var qId = $div.data('question-id');
      var answer = String(questionAnswers[qId] || '').trim();
      var $buttons = $div.find('.single-choice-radio-button');
      if (!answer) {
        $div.find('.js-style-other-picker').addClass('hidden');
        return;
      }
      var isHiddenStyle = (hiddenStyleOptions || []).indexOf(answer) !== -1;
      if (isHiddenStyle) {
        $buttons.removeClass('selected');
        $buttons.filter(function() {
          return String($(this).data('value') || '').trim().toLowerCase() === 'other';
        }).addClass('selected');
        $div.find('.js-style-other-picker').removeClass('hidden');
        $div.find('.js-style-other-search').val(answer);
        renderStyleOtherResults($div, answer);
      } else {
        $div.find('.js-style-other-picker').addClass('hidden');
        $buttons.removeClass('selected');
        $buttons.filter(function() {
          return String($(this).data('value') || '') === answer;
        }).addClass('selected');
      }
    }

    function renderQuestions() {
      var html = '';
      questionDefinitions.forEach(function(q, idx) {
        var isFirst = idx === 0;
        var isLast = idx === questionDefinitions.length - 1;
        var body = '';
        if (q.type === 'radio') {
          body = '<div class="flex flex-wrap gap-2 single-choice-group">' + q.options.map(function(opt) {
            var isOther = String(opt || '').trim().toLowerCase() === 'other';
            var optionClass = (isStyleQuestionId(q.id) && isOther) ? ' option-other' : '';
            return '<button type="button" class="single-choice-radio-button' + optionClass + '" data-value="' + escapeHtml(opt) + '">' + escapeHtml(opt) + '</button>';
          }).join('') + '</div>';
          if (isStyleQuestionId(q.id)) body += buildStyleOtherPickerHtml();
        } else if (q.type === 'select') {
          body = '<select class="w-full js-select2-question" data-question-id="' + q.id + '"><option value="">Choose an option</option>' +
            q.options.map(function(opt) { return '<option value="' + escapeHtml(opt) + '">' + escapeHtml(opt) + '</option>'; }).join('') + '</select>';
        } else if (q.type === 'input') {
          body = '<input type="text" placeholder="' + escapeHtml(q.placeholder) + '" data-question-id="' + q.id + '" class="js-question-input w-full border border-outline-variant/30 bg-white rounded-2xl px-6 py-4 text-lg text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">';
        } else if (q.type === 'textarea') {
          body = '<textarea rows="4" placeholder="' + escapeHtml(q.placeholder) + '" data-question-id="' + q.id + '" class="js-question-input w-full border border-outline-variant/30 bg-white rounded-2xl px-6 py-4 text-lg text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30 resize-none"></textarea>';
        } else if (q.type === 'image') {
          body = window.QuestionImageField.buildHtml(q.id);
        } else if (q.type === 'toggle') {
          body = '<label class="q-toggle-row"><span class="q-toggle-control"><input type="checkbox" data-question-id="' + q.id + '" class="q-toggle-input js-question-toggle"><span class="q-toggle-ui"></span></span><span class="q-toggle-label">' + escapeHtml(q.subtitle) + '</span></label>';
        }
        var navButton = isLast
          ? '<button type="button" class="js-continue-contact inline-flex items-center gap-2 px-6 py-3 bg-primary text-on-primary rounded-full font-bold text-sm hover:bg-primary-container transition-colors">Continue <span class="material-symbols-outlined text-[18px]">arrow_forward</span></button>'
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
      if (window.QuestionImageField) {
        window.QuestionImageField.initIn($('#questionsMount'));
      }
    }

    function validateActiveQuestion() {
      var $active = $('div.question-div.active[data-q]');
      if (!$active.length) return true;
      if (String($active.data('required')) !== '1') { $active.find('.js-question-error').addClass('hidden'); return true; }
      var qType = String($active.data('question-type') || '');
      var qId = $active.data('question-id');
      var hasValue = false;
      if (qType === 'radio') {
        var $selected = $active.find('.single-choice-radio-button.selected');
        hasValue = $selected.length > 0;
        if (hasValue && isStyleQuestionId(qId)) {
          var selectedVal = String($selected.data('value') || '').trim().toLowerCase();
          if (selectedVal === 'other') {
            var pickedStyle = String(questionAnswers[qId] || '').trim();
            hasValue = pickedStyle !== '' && pickedStyle.toLowerCase() !== 'other';
          }
        }
      }
      else if (qType === 'select') hasValue = !!String($active.find('.js-select2-question').val() || '').trim();
      else if (qType === 'input' || qType === 'textarea') hasValue = !!String($active.find('.js-question-input').val() || '').trim();
      else if (qType === 'image') {
        var imageAnswer = questionAnswers[qId];
        hasValue = Array.isArray(imageAnswer) ? imageAnswer.length > 0 : !!String(imageAnswer || '').trim();
      }
      else if (qType === 'toggle') hasValue = $active.find('.js-question-toggle').is(':checked');
      else hasValue = !!questionAnswers[qId];
      $active.find('.js-question-error').toggleClass('hidden', hasValue);
      return hasValue;
    }

    function showQuestion(index) {
      var questions = $('div.question-div[data-q]');
      if (!questions.length) return;
      index = Math.max(0, Math.min(index, questions.length - 1));
      questions.removeClass('active reverse');
      questions.filter('[data-q="' + index + '"]').addClass('active');
      currentQuestionIndex = index;
      restoreStyleQuestionUi(questions.filter('[data-q="' + index + '"]'));
      if (typeof window.rcSyncQuestionProgress === 'function') window.rcSyncQuestionProgress(index);
    }

    function moveQuestion(step) {
      var nextIndex = currentQuestionIndex + step;
      if (nextIndex < 0) {
        if (typeof window.rcLeaveQuestionsToIntro === 'function') window.rcLeaveQuestionsToIntro();
        return;
      }
      if (nextIndex >= $('div.question-div[data-q]').length) {
        if (typeof window.rcLeaveQuestionsToContact === 'function') window.rcLeaveQuestionsToContact();
        return;
      }
      showQuestion(nextIndex);
    }

    window.rcBuildStructuredQuestionAnswers = buildStructuredQuestionAnswers;
    window.rcGetTotalQuestions = function() { return questionDefinitions.length; };
    window.rcPrevQuestion = function() { moveQuestion(-1); };

    $(document).on('click', '.single-choice-radio-button', function() {
      var $btn = $(this);
      var main_div = $btn.closest('div.question-div');
      var current_question = parseInt(main_div.data('q'), 10);
      var qId = main_div.data('question-id');
      var value = String($btn.data('value') || '');
      var isOther = value.trim().toLowerCase() === 'other';
      var isStyleQuestion = isStyleQuestionId(qId);

      main_div.find('.single-choice-radio-button').removeClass('selected');
      $btn.addClass('selected');
      main_div.find('.js-question-error').addClass('hidden');

      if (isStyleQuestion && isOther) {
        main_div.find('.js-style-other-picker').removeClass('hidden');
        main_div.find('.js-style-other-search').val('').trigger('focus');
        delete questionAnswers[qId];
        renderStyleOtherResults(main_div, '');
        return;
      }

      main_div.find('.js-style-other-picker').addClass('hidden');
      if (qId) questionAnswers[qId] = value;
      if (!isNaN(current_question)) setTimeout(function() { moveQuestion(1); }, 180);
    });
    $(document).on('input', '.js-style-other-search', function() {
      var main_div = $(this).closest('.question-div');
      renderStyleOtherResults(main_div, $(this).val());
    });
    $(document).on('click', '.js-style-other-result-item', function() {
      var styleName = String($(this).data('value') || '');
      var main_div = $(this).closest('.question-div');
      var current_question = parseInt(main_div.data('q'), 10);
      var qId = main_div.data('question-id');
      if (!styleName || !qId) return;
      questionAnswers[qId] = styleName;
      main_div.find('.js-style-other-search').val(styleName);
      main_div.find('.js-style-other-result-item').removeClass('selected');
      $(this).addClass('selected');
      main_div.find('.js-question-error').addClass('hidden');
      if (!isNaN(current_question)) setTimeout(function() { moveQuestion(1); }, 180);
    });
    $(document).on('click', '.js-prev-question', function() { moveQuestion(-1); });
    $(document).on('click', '.js-next-question', function() {
      if (!validateActiveQuestion()) return;
      moveQuestion(1);
    });
    $(document).on('click', '.js-continue-contact', function() {
      if (!validateActiveQuestion()) return;
      if (typeof window.rcLeaveQuestionsToContact === 'function') window.rcLeaveQuestionsToContact();
    });
    $(document).on('change', '.js-select2-question, .js-question-toggle', async function() {
      var $question = $(this).closest('.question-div');
      var qId = $question.data('question-id');
      if (!qId) return;
      if ($(this).hasClass('js-question-toggle')) questionAnswers[qId] = $(this).is(':checked');
      else questionAnswers[qId] = String($(this).val() || '').trim();
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
      $('.js-select2-question').select2({ width: '100%', minimumResultsForSearch: Infinity });
    });
  })(jQuery);
  </script>

  <script>
  (function() {
    'use strict';

    var artistName = @json($artistName ?? 'Artist');
    var isManagedScheduling = @json(!empty($isManagedScheduling));
    var questionCount = (typeof window.rcGetTotalQuestions === 'function') ? window.rcGetTotalQuestions() : 0;
    var postScreens = isManagedScheduling ? [9, 10, 11, 12, 13, 14, 15, 16] : [10, 11, 12, 13, 14, 15, 16];
    var rcMaxStep = isManagedScheduling ? 5 : 4;
    var rcCurrentQuestion = 0;
    var rcPrefCount = 1;
    var RC_PREF_TIME_PILLS = '<div class="flex flex-wrap gap-1.5"><button type="button" class="time-pref-pill" data-value="Morning" onclick="rcToggleTimePref(this)">Morning</button><button type="button" class="time-pref-pill" data-value="Afternoon" onclick="rcToggleTimePref(this)">Afternoon</button><button type="button" class="time-pref-pill" data-value="Evening" onclick="rcToggleTimePref(this)">Evening</button></div>';

    document.getElementById('artistNameDisplay').textContent = artistName;
    document.getElementById('successArtistName').textContent = artistName;
    document.title = 'Request Custom Tattoo — ' + artistName + ' | Inkjin';

    var current = 0;
    var inQuestions = false;

    var data = { name: '', email: '', phone: '', notes: '' };
    var rcCsrfToken = @json(csrf_token());
    var rcOtpVerified = false;
    var rcConnectedEmail = '';
    var rcConnectedName = '';
    var rcOtpResendRemaining = 0;
    var rcOtpResendEmail = '';
    var rcOtpResendTimer = null;

    function rcResolveStep() {
      if (current === 0) return 0;
      if (inQuestions) return 1;
      if (isManagedScheduling && current === 9) return 2;
      if (current >= 10 && current <= 13) return isManagedScheduling ? 3 : 2;
      if (current === 14 || current === 15) return isManagedScheduling ? 4 : 3;
      if (current === 16) return isManagedScheduling ? 5 : 4;
      return 0;
    }

    function updateProgressDots() {
      var step = rcResolveStep();
      document.querySelectorAll('.progress-step').forEach(function(el) {
        var s = parseInt(el.getAttribute('data-step') || '0', 10);
        el.classList.remove('active', 'completed');
        if (s === step) el.classList.add('active');
        else if (s < step) el.classList.add('completed');
      });
      document.querySelectorAll('.progress-line').forEach(function(el) {
        var lineNum = parseInt(el.getAttribute('data-line') || '0', 10);
        el.classList.toggle('completed', lineNum < step);
      });
    }

    function updateTopProgress() {
      var step = rcResolveStep();
      var pct = 0;
      if (step === 0) {
        pct = 0;
      } else if (step === 1 && inQuestions) {
        var totalQ = Math.max(1, questionCount);
        pct = 5 + (rcCurrentQuestion / totalQ) * 15;
      } else if (step >= 1 && rcMaxStep > 1) {
        pct = ((step - 1) / (rcMaxStep - 1)) * 100;
      }
      document.getElementById('progressBar').style.width = Math.min(pct, 100) + '%';
    }

    function updateBookingChrome() {
      var chrome = document.getElementById('rcBookingChrome');
      var step = rcResolveStep();
      if (chrome) {
        chrome.classList.toggle('hidden', step === 0);
      }
      updateProgressDots();
      updateTopProgress();
    }

    function showScreen(screenId, reverse) {
      document.querySelectorAll('.tf-screen').forEach(function(s) {
        s.classList.remove('active', 'reverse');
      });
      inQuestions = false;
      current = screenId;
      var target = document.querySelector('[data-screen="' + screenId + '"]');
      if (target) {
        target.classList.add('active');
        if (reverse) target.classList.add('reverse');
      }
      updateBookingChrome();
      window.scrollTo({ top: 0 });
    }

    function showQuestionsPhase(reverse) {
      document.querySelectorAll('.tf-screen').forEach(function(s) {
        s.classList.remove('active', 'reverse');
      });
      inQuestions = true;
      current = 'questions';
      var step = document.getElementById('questionsStep');
      if (step) {
        step.classList.add('active');
        if (reverse) step.classList.add('reverse');
      }
      if (typeof window.rcGetTotalQuestions === 'function' && window.rcGetTotalQuestions() > 0) {
        var lastIdx = window.rcGetTotalQuestions() - 1;
        if (reverse && typeof jQuery !== 'undefined') {
          jQuery('div.question-div[data-q]').removeClass('active reverse');
          jQuery('div.question-div[data-q="' + lastIdx + '"]').addClass('active reverse');
        }
      }
      updateBookingChrome();
      window.scrollTo({ top: 0 });
    }

    window.rcLeaveQuestionsToIntro = function() {
      showScreen(0, true);
    };

    window.rcLeaveQuestionsToContact = function() {
      if (isManagedScheduling) {
        showScreen(9, false);
        current = 9;
      } else {
        showScreen(10, false);
        current = 10;
      }
    };

    window.rcSyncQuestionProgress = function(questionIndex) {
      rcCurrentQuestion = Math.max(0, parseInt(questionIndex, 10) || 0);
      updateTopProgress();
    };

    function clearRcError(inputId, errorId) {
      var input = document.getElementById(inputId);
      var err = document.getElementById(errorId);
      if (input) { input.classList.remove('border-error'); input.style.borderColor = ''; }
      if (err) { err.classList.add('hidden'); err.textContent = 'This field is required.'; }
    }

    function setRcError(inputId, errorId, message) {
      var input = document.getElementById(inputId);
      var err = document.getElementById(errorId);
      if (input) { input.style.borderColor = '#ba1a1a'; }
      if (err) { err.classList.remove('hidden'); err.textContent = message; }
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
      if (!res.ok) throw new Error('Unable to validate email right now. Please try again.');
      var payload = await res.json();
      if (typeof payload.allowed === 'boolean') return payload.allowed;
      if (!payload.exists) return true;
      return !!payload.is_user;
    }

    function syncRcOtpEmailFromForm() {
      var emailVal = String(document.getElementById('tfEmail')?.value || '').trim();
      var otpEmail = document.getElementById('rcOtpEmail');
      var loginEmail = document.getElementById('rcAuthLoginEmail');
      if (otpEmail) otpEmail.value = emailVal;
      if (loginEmail) loginEmail.textContent = emailVal || 'you@example.com';
      if (typeof window.rcUpdateConnectedUi === 'function') window.rcUpdateConnectedUi();
    }

    function formatSecondsToMMSS(seconds) {
      var s = Math.max(0, parseInt(seconds || 0, 10) || 0);
      var mm = String(Math.floor(s / 60)).padStart(2, '0');
      var ss = String(s % 60).padStart(2, '0');
      return mm + ':' + ss;
    }

    function applyRcOtpResendUi() {
      var sendBtn = document.getElementById('rcSendOtpBtn');
      if (!sendBtn) return;
      var currentEmail = String(document.getElementById('rcOtpEmail')?.value || '').trim().toLowerCase();
      if (rcOtpResendRemaining > 0 && rcOtpResendEmail && rcOtpResendEmail === currentEmail) {
        sendBtn.disabled = true;
        sendBtn.textContent = 'Resend in ' + formatSecondsToMMSS(rcOtpResendRemaining);
      } else {
        sendBtn.disabled = false;
        sendBtn.textContent = 'Resend code';
      }
    }

    function startRcOtpResendCountdown(seconds) {
      rcOtpResendRemaining = Math.max(0, parseInt(seconds || 0, 10) || 0);
      if (rcOtpResendTimer) { clearInterval(rcOtpResendTimer); rcOtpResendTimer = null; }
      applyRcOtpResendUi();
      if (rcOtpResendRemaining <= 0) return;
      rcOtpResendTimer = setInterval(function() {
        rcOtpResendRemaining = Math.max(0, rcOtpResendRemaining - 1);
        applyRcOtpResendUi();
        if (rcOtpResendRemaining <= 0 && rcOtpResendTimer) {
          clearInterval(rcOtpResendTimer);
          rcOtpResendTimer = null;
        }
      }, 1000);
    }

    window.rcUpdateConnectedUi = function() {
      var connected = document.getElementById('rcConnectedUser');
      var status = document.getElementById('rcOtpStatus');
      var codeWrap = document.getElementById('rcOtpCodeWrap');
      var sendBtn = document.getElementById('rcSendOtpBtn');
      var verifyBtn = document.getElementById('rcVerifyOtpBtn');
      if (rcOtpVerified) {
        var label = rcConnectedName ? rcConnectedName + ' (' + rcConnectedEmail + ')' : rcConnectedEmail;
        if (connected) { connected.classList.remove('hidden'); connected.textContent = 'Already connected user: ' + label; }
        if (status) {
          status.classList.remove('hidden');
          status.classList.add('flex');
          status.innerHTML = '<span class="material-symbols-outlined text-[18px] text-green-600">verified</span><span>Email already verified for this request.</span>';
        }
        if (codeWrap) codeWrap.classList.add('hidden');
        if (sendBtn) sendBtn.classList.add('hidden');
        if (verifyBtn) { verifyBtn.textContent = 'Continue'; verifyBtn.disabled = false; }
      } else {
        if (connected) { connected.classList.add('hidden'); connected.textContent = 'Already connected user.'; }
        if (codeWrap) codeWrap.classList.remove('hidden');
        if (sendBtn) sendBtn.classList.remove('hidden');
        if (verifyBtn) verifyBtn.textContent = 'Verify & Continue';
      }
    };

    window.sendRcOtp = async function() {
      var email = String(document.getElementById('rcOtpEmail')?.value || '').trim();
      var otpError = document.getElementById('rcOtpError');
      var otpStatus = document.getElementById('rcOtpStatus');
      var sendBtn = document.getElementById('rcSendOtpBtn');
      if (otpError) otpError.classList.add('hidden');
      if (rcOtpResendRemaining > 0 && rcOtpResendEmail === email.toLowerCase()) {
        if (otpError) { otpError.classList.remove('hidden'); otpError.textContent = 'Please wait ' + formatSecondsToMMSS(rcOtpResendRemaining) + ' before requesting another code.'; }
        return;
      }
      if (!isValidEmail(email)) {
        if (otpError) { otpError.classList.remove('hidden'); otpError.textContent = 'Please enter a valid email first.'; }
        return;
      }
      if (sendBtn) { sendBtn.disabled = true; sendBtn.textContent = 'Sending...'; }
      try {
        var res = await fetch('/api/public/send-booking-otp', {
          method: 'POST',
          headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': rcCsrfToken },
          body: JSON.stringify({ email: email })
        });
        var payload = await res.json();
        if (!res.ok) {
          if (payload && payload.resend_available_in_seconds) {
            rcOtpResendEmail = email.toLowerCase();
            startRcOtpResendCountdown(payload.resend_available_in_seconds);
          }
          throw new Error((payload && payload.message) || 'Could not send verification code.');
        }
        if (otpStatus) {
          otpStatus.classList.remove('hidden');
          otpStatus.classList.add('flex');
          otpStatus.innerHTML = '<span class="material-symbols-outlined text-[18px] text-green-600">mark_email_read</span><span>4-digit code sent to your email.</span>';
        }
        rcOtpResendEmail = email.toLowerCase();
        startRcOtpResendCountdown(payload && payload.resend_available_in_seconds ? payload.resend_available_in_seconds : 60);
      } catch (err) {
        if (otpError) { otpError.classList.remove('hidden'); otpError.textContent = err.message || 'Could not send verification code.'; }
      } finally {
        if (rcOtpResendRemaining <= 0 && sendBtn) { sendBtn.disabled = false; sendBtn.textContent = 'Resend code'; }
        else applyRcOtpResendUi();
      }
    };

    window.verifyRcOtp = async function() {
      if (rcOtpVerified) { window.finishRcAuth(); return; }
      var email = String(document.getElementById('rcOtpEmail')?.value || '').trim();
      var code = String(document.getElementById('rcOtpCode')?.value || '').trim();
      var name = String(document.getElementById('tfName')?.value || '').trim();
      var otpError = document.getElementById('rcOtpError');
      var verifyBtn = document.getElementById('rcVerifyOtpBtn');
      if (otpError) otpError.classList.add('hidden');
      if (!isValidEmail(email)) {
        if (otpError) { otpError.classList.remove('hidden'); otpError.textContent = 'Please enter a valid email.'; }
        return;
      }
      if (!/^\d{4}$/.test(code)) {
        if (otpError) { otpError.classList.remove('hidden'); otpError.textContent = 'Please enter a valid 4-digit code.'; }
        return;
      }
      if (verifyBtn) { verifyBtn.disabled = true; verifyBtn.textContent = 'Verifying...'; }
      try {
        var res = await fetch('/api/public/verify-booking-otp', {
          method: 'POST',
          headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': rcCsrfToken },
          body: JSON.stringify({ email: email, code: code, name: name })
        });
        var payload = await res.json();
        if (!res.ok || !payload || !payload.verified) throw new Error((payload && payload.message) || 'Verification failed.');
        rcOtpVerified = true;
        rcConnectedEmail = (payload.user && payload.user.email) ? payload.user.email : email;
        rcConnectedName = (payload.user && payload.user.name) ? payload.user.name : '';
        var tfEmail = document.getElementById('tfEmail');
        if (tfEmail) tfEmail.value = rcConnectedEmail;
        data.email = rcConnectedEmail;
        window.rcUpdateConnectedUi();
        window.finishRcAuth();
      } catch (err) {
        if (otpError) { otpError.classList.remove('hidden'); otpError.textContent = err.message || 'Verification failed.'; }
      } finally {
        if (verifyBtn) { verifyBtn.disabled = false; verifyBtn.textContent = rcOtpVerified ? 'Continue' : 'Verify & Continue'; }
      }
    };

    window.finishRcAuth = function() {
      if (!rcOtpVerified) {
        var otpError = document.getElementById('rcOtpError');
        if (otpError) { otpError.classList.remove('hidden'); otpError.textContent = 'Please verify your email to continue.'; }
        return;
      }
      collectData();
      showScreen(14, false);
      current = 14;
    };

    window.toggleRcAuth = function() {
      document.getElementById('rcAuthCreate')?.classList.toggle('hidden');
      document.getElementById('rcAuthLogin')?.classList.toggle('hidden');
    };

    function setRcManagedError(errorId, show) {
      var el = document.getElementById(errorId);
      if (!el) return null;
      el.classList.toggle('hidden', !show);
      return show ? el : null;
    }

    window.rcSelectPill = function(btn, cid) {
      document.querySelectorAll('#' + cid + ' .pill-btn').forEach(function(b) { b.classList.remove('selected'); });
      btn.classList.add('selected');
      var errorMap = { rcFlexPills: 'rcManagedFlexError', rcUrgencyPills: 'rcManagedUrgencyError' };
      if (errorMap[cid]) setRcManagedError(errorMap[cid], false);
    };

    window.rcToggleTimePref = function(btn) { btn.classList.toggle('selected'); };

    window.rcToggleDayPref = function(btn) {
      btn.classList.toggle('selected');
      if (document.querySelector('#rcDayPills .day-pill.selected')) {
        setRcManagedError('rcManagedDayError', false);
      }
    };

    function rcPrefRemoveBtnHtml() {
      return '<button type="button" class="pref-remove-btn" onclick="rcRemovePreferenceBlock(this)" aria-label="Remove preference"><span class="material-symbols-outlined text-[16px]">close</span> Remove</button>';
    }

    function rcBuildPreferenceBlockHtml(num, deletable) {
      var req = num === 1 ? ' <span class="text-error">*</span>' : '';
      var html = '<div class="pref-block-header"><p class="text-xs font-bold text-primary uppercase tracking-wider pref-block-label">Preference ' + num + req + '</p>';
      if (deletable) html += rcPrefRemoveBtnHtml();
      html += '</div><div class="grid grid-cols-1 sm:grid-cols-2 gap-3"><div><label class="text-xs font-semibold text-on-surface-variant mb-1 block">Date</label><input type="date" class="rc-pref-date w-full border border-outline-variant/30 bg-white rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30"></div><div><label class="text-xs font-semibold text-on-surface-variant mb-1 block">Time of day</label>' + RC_PREF_TIME_PILLS + '</div></div>';
      return html;
    }

    function rcRenumberPreferenceBlocks() {
      var blocks = document.querySelectorAll('#rcPrefBlocks .pref-block');
      blocks.forEach(function(block, index) {
        block.dataset.pref = String(index);
        var label = block.querySelector('.pref-block-label');
        if (label) {
          label.innerHTML = 'Preference ' + (index + 1) + (index === 0 ? ' <span class="text-error">*</span>' : '');
        }
        var removeBtn = block.querySelector('.pref-remove-btn');
        if (index > 0 && !removeBtn) {
          var header = block.querySelector('.pref-block-header');
          if (header) header.insertAdjacentHTML('beforeend', rcPrefRemoveBtnHtml());
        } else if (index === 0 && removeBtn) {
          removeBtn.remove();
        }
      });
      return blocks.length;
    }

    window.rcRemovePreferenceBlock = function(btn) {
      var block = btn.closest('.pref-block');
      if (!block) return;
      var blocks = document.querySelectorAll('#rcPrefBlocks .pref-block');
      var index = Array.prototype.indexOf.call(blocks, block);
      if (index <= 0) return;
      block.remove();
      rcPrefCount = rcRenumberPreferenceBlocks();
      var addBtn = document.getElementById('rcAddPrefBtn');
      if (addBtn) addBtn.classList.remove('hidden');
    };

    window.rcAddPreference = function() {
      if (rcPrefCount >= 5) return;
      rcPrefCount++;
      var block = document.createElement('div');
      block.className = 'pref-block';
      block.dataset.pref = String(rcPrefCount - 1);
      block.innerHTML = rcBuildPreferenceBlockHtml(rcPrefCount, true);
      document.getElementById('rcPrefBlocks').appendChild(block);
      if (rcPrefCount >= 5) document.getElementById('rcAddPrefBtn').classList.add('hidden');
    };

    function collectRcPreferredDateBlocks() {
      var prefs = [];
      document.querySelectorAll('#rcPrefBlocks .pref-block').forEach(function(block, index) {
        var date = block.querySelector('.rc-pref-date')?.value || '';
        var times = [];
        block.querySelectorAll('.time-pref-pill.selected').forEach(function(p) { times.push(p.dataset.value); });
        if (date) prefs.push({ preference: index + 1, date: date, times_of_day: times });
      });
      return prefs;
    }

    function collectRcManagedAvailabilityPayload() {
      var days = [];
      document.querySelectorAll('#rcDayPills .day-pill.selected').forEach(function(d) { days.push(d.dataset.value); });
      return {
        preferences: collectRcPreferredDateBlocks(),
        preferred_days: days,
        how_much_flexible: document.querySelector('#rcFlexPills .pill-btn.selected')?.dataset.value || '',
        avoid_dates: String(document.getElementById('rcManagedAvoid')?.value || '').trim(),
        urgency: document.querySelector('#rcUrgencyPills .pill-btn.selected')?.dataset.value || '',
      };
    }

    function validateRcManagedAvailability() {
      if (!isManagedScheduling) return true;
      ['rcManagedDayError', 'rcManagedFlexError', 'rcManagedUrgencyError'].forEach(function(id) {
        setRcManagedError(id, false);
      });
      var valid = true;
      var firstInvalid = null;
      function fail(errorId) {
        var el = setRcManagedError(errorId, true);
        valid = false;
        if (!firstInvalid && el) firstInvalid = el;
      }
      if (!document.querySelector('#rcDayPills .day-pill.selected')) fail('rcManagedDayError');
      if (!document.querySelector('#rcFlexPills .pill-btn.selected')) fail('rcManagedFlexError');
      if (!document.querySelector('#rcUrgencyPills .pill-btn.selected')) fail('rcManagedUrgencyError');
      if (!valid && firstInvalid) {
        var field = firstInvalid.closest('[data-rc-field]') || firstInvalid;
        field.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
      return valid;
    }

    function buildRcManagedAvailabilityReviewLines() {
      if (!isManagedScheduling) return [];
      var avail = collectRcManagedAvailabilityPayload();
      var lines = [];
      (avail.preferences || []).forEach(function(pref, idx) {
        var times = (pref.times_of_day || []).join(', ');
        lines.push({ label: 'Preferred date ' + (pref.preference || (idx + 1)), value: pref.date + (times ? ' (' + times + ')' : '') });
      });
      if ((avail.preferred_days || []).length) {
        lines.push({ label: 'Preferred days', value: avail.preferred_days.join(', ') });
      }
      if (avail.how_much_flexible) lines.push({ label: 'Flexibility', value: avail.how_much_flexible });
      if (avail.urgency) lines.push({ label: 'Urgency', value: avail.urgency });
      if (avail.avoid_dates) lines.push({ label: 'Dates to avoid', value: avail.avoid_dates });
      return lines;
    }

    window.nextScreen = async function() {
      if (inQuestions) return;
      if (current === 13) return;

      collectData();
      clearRcError('tfName', 'tfNameError');
      clearRcError('tfEmail', 'tfEmailError');
      clearRcError('tfPhone', 'tfPhoneError');

      if (current === 0) {
        if (questionCount > 0) {
          showQuestionsPhase(false);
          return;
        }
        if (isManagedScheduling) {
          showScreen(9, false);
          current = 9;
        } else {
          showScreen(10, false);
          current = 10;
        }
        return;
      }

      if (current === 9) {
        if (!validateRcManagedAvailability()) return;
        showScreen(10, false);
        current = 10;
        return;
      }

      if (current === 10) {
        var nameVal = String(document.getElementById('tfName')?.value || '').trim();
        if (!nameVal) { setRcError('tfName', 'tfNameError', 'This field is required.'); shakeInput(document.getElementById('tfName')); return; }
        data.name = nameVal;
        showScreen(11, false);
        current = 11;
        return;
      }

      if (current === 11) {
        var emailVal = String(document.getElementById('tfEmail')?.value || '').trim();
        if (!emailVal) { setRcError('tfEmail', 'tfEmailError', 'This field is required.'); shakeInput(document.getElementById('tfEmail')); return; }
        if (!isValidEmail(emailVal)) { setRcError('tfEmail', 'tfEmailError', 'Please enter a valid email address.'); shakeInput(document.getElementById('tfEmail')); return; }
        try {
          var allowed = await validateBookingEmailRole(emailVal);
          if (!allowed) { setRcError('tfEmail', 'tfEmailError', 'Please use another email.'); shakeInput(document.getElementById('tfEmail')); return; }
        } catch (err) {
          setRcError('tfEmail', 'tfEmailError', err.message || 'Unable to validate email right now. Please try again.');
          shakeInput(document.getElementById('tfEmail'));
          return;
        }
        data.email = emailVal;
        showScreen(12, false);
        current = 12;
        return;
      }

      if (current === 12) {
        var phoneVal = String(document.getElementById('tfPhone')?.value || '').trim();
        if (!phoneVal) { setRcError('tfPhone', 'tfPhoneError', 'This field is required.'); shakeInput(document.getElementById('tfPhone')); return; }
        if (!isValidPhoneWithCountryCode(phoneVal)) {
          setRcError('tfPhone', 'tfPhoneError', 'Phone must start with country code, e.g. +30 694 123 4567.');
          shakeInput(document.getElementById('tfPhone'));
          return;
        }
        data.phone = phoneVal;
        syncRcOtpEmailFromForm();
        showScreen(13, false);
        current = 13;
        if (!rcOtpVerified) await window.sendRcOtp();
        return;
      }

      if (current === 14) buildReview();

      if (typeof current === 'number') {
        var idx = postScreens.indexOf(current);
        if (idx >= 0 && idx < postScreens.length - 1) {
          var nextId = postScreens[idx + 1];
          showScreen(nextId, false);
          current = nextId;
          return;
        }
      }
    };

    window.prevScreen = function() {
      if (inQuestions) {
        if (typeof window.rcPrevQuestion === 'function') window.rcPrevQuestion();
        return;
      }

      collectData();

      if (current === 10) {
        if (isManagedScheduling) {
          showScreen(9, true);
          current = 9;
          return;
        }
        if (questionCount > 0) {
          showQuestionsPhase(true);
          return;
        }
        showScreen(0, true);
        current = 0;
        return;
      }

      if (current === 9) {
        if (questionCount > 0) {
          showQuestionsPhase(true);
          return;
        }
        showScreen(0, true);
        current = 0;
        return;
      }

      var idx = postScreens.indexOf(current);
      if (idx > 0) {
        showScreen(postScreens[idx - 1], true);
        current = postScreens[idx - 1];
        return;
      }

      if (current > 0) {
        showScreen(0, true);
        current = 0;
      }
    };

    function shakeInput(el) {
      el.style.animation = 'none';
      el.offsetHeight;
      el.style.animation = 'shake 0.4s ease';
      el.style.borderColor = '#ba1a1a';
      setTimeout(function() { el.style.borderColor = ''; el.style.animation = ''; }, 800);
    }

    var style = document.createElement('style');
    style.textContent = '@keyframes shake { 0%,100%{transform:translateX(0)} 20%,60%{transform:translateX(-6px)} 40%,80%{transform:translateX(6px)} }';
    document.head.appendChild(style);

    function collectData() {
      data.name = document.getElementById('tfName')?.value.trim() || data.name;
      data.email = document.getElementById('tfEmail')?.value.trim() || data.email;
      data.phone = document.getElementById('tfPhone')?.value.trim() || data.phone;
      data.notes = document.getElementById('tfNotes')?.value.trim() || data.notes;
    }

    function formatAnswer(val, type) {
      if (window.QuestionAnswerDisplay) {
        return window.QuestionAnswerDisplay.formatAnswerForReview(val, type);
      }
      if (typeof val === 'boolean') return val ? 'Yes' : 'No';
      if (Array.isArray(val)) return val.length === 1 ? '1 photo' : (val.length > 1 ? val.length + ' photos' : '—');
      return String(val || '—');
    }

    function buildReview() {
      collectData();
      var items = [];
      if (typeof window.rcBuildStructuredQuestionAnswers === 'function') {
        var qa = window.rcBuildStructuredQuestionAnswers();
        Object.keys(qa).forEach(function(key) {
          var entry = qa[key];
          if (!entry) return;
          items.push({ label: entry.question || 'Question', value: formatAnswer(entry.answer, entry.type) });
        });
      }
      items.push(
        { label: 'Name', value: data.name || '—' },
        { label: 'Email', value: data.email || '—' },
        { label: 'Phone', value: data.phone || '—' }
      );
      buildRcManagedAvailabilityReviewLines().forEach(function(line) { items.push(line); });
      items.push({ label: 'Additional Notes', value: data.notes || 'None' });
      document.getElementById('reviewContent').innerHTML = items.map(function(i) {
        var v = i.value.length > 80 ? i.value.slice(0, 80) + '…' : i.value;
        return '<div class="review-row"><span class="text-sm text-on-surface-variant">' + i.label + '</span>' +
          '<span class="text-sm font-semibold text-on-surface text-right max-w-[60%] break-words">' + v + '</span></div>';
      }).join('');
    }

    window.submitRequest = async function() {
      var errEl = document.getElementById('customSubmitError');
      var submitBtn = document.getElementById('btnSubmit');
      if (errEl) { errEl.classList.add('hidden'); errEl.textContent = ''; }

      if (!rcOtpVerified) {
        syncRcOtpEmailFromForm();
        showScreen(13, false);
        current = 13;
        var otpError = document.getElementById('rcOtpError');
        if (otpError) { otpError.classList.remove('hidden'); otpError.textContent = 'Please verify your email before submitting.'; }
        return;
      }

      collectData();
      if (submitBtn) { submitBtn.disabled = true; submitBtn.textContent = 'Submitting…'; }

      showScreen(16, false);
      current = 16;
      updateBookingChrome();
      document.getElementById('submitLoading').classList.remove('hidden');
      document.getElementById('submitSuccess').classList.add('hidden');

      try {
        var requestPayload = {
            email: data.email,
            name: data.name,
            phone: data.phone,
            notes: data.notes,
            referral_source: String(document.getElementById('rc_referral_source')?.value || '').trim(),
            questions_answers: (typeof window.rcBuildStructuredQuestionAnswers === 'function')
              ? window.rcBuildStructuredQuestionAnswers()
              : {}
          };
        if (isManagedScheduling) {
          var managedAvailability = collectRcManagedAvailabilityPayload();
          requestPayload.preferences = managedAvailability.preferences;
          requestPayload.preferred_days = managedAvailability.preferred_days;
          requestPayload.how_much_flexible = managedAvailability.how_much_flexible;
          requestPayload.avoid_dates = managedAvailability.avoid_dates;
          requestPayload.urgency = managedAvailability.urgency;
        }
        var payload = {
          artist_username: @json($artistUsername ?? ''),
          request_payload: requestPayload
        };
        var res = await fetch('/api/public/submit-custom-request', {
          method: 'POST',
          headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': rcCsrfToken
          },
          credentials: 'same-origin',
          body: JSON.stringify(payload)
        });
        var result = await res.json();
        if (!res.ok || !result || !result.saved) {
          throw new Error((result && result.message) || 'Unable to submit your request. Please try again.');
        }
        var refEl = document.getElementById('successRequestRef');
        if (refEl) refEl.textContent = result.request_reference || '—';
        document.getElementById('submitLoading').classList.add('hidden');
        document.getElementById('submitSuccess').classList.remove('hidden');
      } catch (err) {
        showScreen(15, false);
        current = 15;
        document.getElementById('submitLoading').classList.add('hidden');
        document.getElementById('submitSuccess').classList.add('hidden');
        if (errEl) {
          errEl.textContent = err.message || 'Unable to submit your request.';
          errEl.classList.remove('hidden');
        }
      } finally {
        if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = 'Submit Request'; }
      }
    };

    document.addEventListener('keydown', function(e) {
      if (inQuestions) return;
      if (e.key === 'Enter' && !e.shiftKey) {
        var active = document.querySelector('.tf-screen.active');
        if (!active) return;
        var screen = active.dataset.screen;
        if (document.activeElement && document.activeElement.tagName === 'TEXTAREA') return;
        if (screen === 'questions' || screen === '13' || screen === '15' || screen === '16') return;
        e.preventDefault();
        if (screen === '13') {
          if (rcOtpVerified) finishRcAuth();
          else verifyRcOtp();
          return;
        }
        nextScreen();
      }
      if (e.key === 'ArrowUp') {
        if (document.activeElement && (document.activeElement.tagName === 'INPUT' || document.activeElement.tagName === 'TEXTAREA')) return;
        prevScreen();
      }
    });

    var params = new URLSearchParams(window.location.search);
    var statusParam = params.get('status');
    if (statusParam === 'closed' || statusParam === 'flash') {
      document.getElementById('progressBar').style.display = 'none';
      document.querySelector('header').style.display = 'none';
      var rcMain = document.getElementById('rcMainContent');
      if (rcMain) rcMain.style.display = 'none';
      document.querySelectorAll('.tf-screen').forEach(function(s) {
        s.classList.remove('active');
        s.style.display = 'none';
      });
      document.getElementById('customClosedOverlay').classList.remove('hidden');
      if (statusParam === 'flash') {
        document.getElementById('customClosedTitle').textContent = 'Not Accepting Custom Requests';
        document.getElementById('customClosedDesc').textContent = 'This artist is currently only accepting design bookings. Browse their available designs instead!';
      }
    }

    ['tfName', 'tfEmail', 'tfPhone'].forEach(function(id) {
      var el = document.getElementById(id);
      if (!el) return;
      el.addEventListener('input', function() {
        clearRcError(id, id + 'Error');
      });
    });

    var otpEmailEl = document.getElementById('rcOtpEmail');
    if (otpEmailEl) {
      otpEmailEl.addEventListener('input', function() {
        var otpError = document.getElementById('rcOtpError');
        var otpStatus = document.getElementById('rcOtpStatus');
        if (otpError) otpError.classList.add('hidden');
        if (otpStatus) { otpStatus.textContent = ''; otpStatus.classList.add('hidden'); otpStatus.classList.remove('flex'); }
        applyRcOtpResendUi();
        if (String(this.value || '').trim().toLowerCase() !== String(rcConnectedEmail || '').toLowerCase()) {
          rcOtpVerified = false;
          rcConnectedEmail = '';
          rcConnectedName = '';
        }
        window.rcUpdateConnectedUi();
      });
    }

    var otpCodeEl = document.getElementById('rcOtpCode');
    if (otpCodeEl) {
      otpCodeEl.addEventListener('input', function() {
        this.value = String(this.value || '').replace(/\D/g, '').slice(0, 4);
        var otpError = document.getElementById('rcOtpError');
        if (otpError) otpError.classList.add('hidden');
      });
    }

    syncRcOtpEmailFromForm();

    updateBookingChrome();
  })();
  </script>

</body>
</html>
