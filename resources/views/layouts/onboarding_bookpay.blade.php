<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Profile — Artist Onboarding | Inkjin Book & Pay</title>
  <meta name="description"
    content="Set up your professional profile on Inkjin Book & Pay. Add your name, username, and photo.">
  <link rel="icon" href="images/favicon.png">
  <link href="{{ asset('design/css/inkjin_main.css') }}" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap"
    rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap"
    rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
    rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
  <script>
    tailwind.config = {
      darkMode: "class",
      theme: {
        extend: {
          colors: {
            "surface-container-high": "#ece6ef",
            "surface-container-lowest": "#ffffff",
            "surface-container": "#f2ecf5",
            "background": "#fdf7ff",
            "primary": "#1a1a1a",
            "surface-dim": "#ded8e1",
            "on-surface-variant": "#494552",
            "secondary-fixed": "#e8ddff",
            "on-secondary-fixed-variant": "#4a4168",
            "inverse-surface": "#322f36",
            "error-container": "#ffdad6",
            "inverse-on-surface": "#f5eff8",
            "tertiary": "#452200",
            "surface-container-low": "#f8f1fb",
            "surface": "#fdf7ff",
            "secondary-fixed-dim": "#ccc0ee",
            "on-tertiary-fixed": "#2e1500",
            "on-error": "#ffffff",
            "on-primary-container": "#e0e0e0",
            "secondary": "#625881",
            "inverse-primary": "#cebdff",
            "primary-fixed": "#e0e0e0",
            "outline": "#7a7583",
            "tertiary-fixed": "#ffdcc2",
            "tertiary-container": "#653500",
            "on-secondary": "#ffffff",
            "on-primary": "#ffffff",
            "on-tertiary-fixed-variant": "#6c3a04",
            "error": "#ba1a1a",
            "tertiary-fixed-dim": "#ffb77b",
            "surface-bright": "#fdf7ff",
            "surface-tint": "#664db1",
            "on-error-container": "#93000a",
            "on-primary-fixed": "#111111",
            "primary-fixed-dim": "#cebdff",
            "on-tertiary-container": "#e49e62",
            "on-primary-fixed-variant": "#444444",
            "primary-container": "#333333",
            "on-surface": "#1c1b21",
            "outline-variant": "#cac4d3",
            "on-tertiary": "#ffffff",
            "surface-container-highest": "#e6e0ea",
            "on-background": "#1c1b21",
            "secondary-container": "#ddd0ff",
            "on-secondary-fixed": "#1e1539",
            "surface-variant": "#e6e0ea",
            "on-secondary-container": "#615780"
          },
          fontFamily: {
            "headline": ["Plus Jakarta Sans"],
            "body": ["Plus Jakarta Sans"],
            "label": ["Plus Jakarta Sans"]
          },
          borderRadius: { "DEFAULT": "0.25rem", "lg": "0.5rem", "xl": "0.75rem", "full": "9999px" },
        },
      },
    }
  </script>
  <style>
    body {
      font-family: 'Plus Jakarta Sans', sans-serif;
    }

    .material-symbols-outlined {
      font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
    }

    /* Sidebar */

    .mobile-header {
      display: none;
    }

    @media (max-width: 1023px) {
      .mobile-header {
        display: flex;
      }
    }

    @media (min-width: 1024px) {}

    .sidebar {
      width: 260px;
      min-height: 100vh;
    }

    @media (min-width: 1024px) {
      .sidebar {
        display: flex !important;
      }

      .main-content {
        margin-left: 260px;
      }
    }

    @media (max-width: 1023px) {
      .main-content {
        padding-top: 70px;
      }
    }

    .nav-item {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 12px 16px;
      border-radius: 12px;
      font-size: 14px;
      font-weight: 500;
      color: rgba(255, 255, 255, 0.85);
      transition: all 0.2s;
      cursor: pointer;
      text-decoration: none;
    }

    .nav-item:hover {
      background: rgba(255, 255, 255, 0.1);
    }

    .nav-item.active {
      background: #ffffff;
      color: #310f7a;
      font-weight: 600;
    }

    .nav-item .material-symbols-outlined {
      font-size: 20px;
    }

    /* Progress bar */
    .progress-fill {
      transition: width 0.4s ease;
    }

    /* Mobile sidebar — handled by Tailwind responsive classes (hidden lg:flex) */
    .sidebar.open {
      display: flex !important;
    }

    .main-content {
      padding-top: 60px !important;
    }

    .sidebar-backdrop {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.5);
      z-index: 99;
    }

    .sidebar-backdrop.open {
      display: block;
    }

    .style-tag { display: inline-flex; align-items: center; gap: 6px; background: #310f7a; color: white; padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: 500; }
    .style-tag button { background: none; border: none; color: white; cursor: pointer; font-size: 14px; line-height: 1; opacity: 0.8; }
    .style-tag button:hover { opacity: 1; }
    .style-option { padding: 10px 16px; cursor: pointer; display: flex; align-items: center; justify-content: space-between; transition: background 0.15s; }
    .style-option:hover { background: #f8f1fb; }
    .style-option.selected { background: #f0eaff; }
    .radio-card { border: 1.5px solid #cac4d3; border-radius: 12px; padding: 16px; cursor: pointer; transition: all 0.2s; position: relative; }
    .radio-card.selected { border-color: #310f7a; background: #fdf7ff; }
    .radio-card .radio-dot { width: 18px; height: 18px; border-radius: 50%; border: 2px solid #cac4d3; transition: all 0.2s; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .radio-card.selected .radio-dot { border-color: #310f7a; background: #310f7a; }
    .radio-card.selected .radio-dot::after { content: ''; width: 6px; height: 6px; background: white; border-radius: 50%; }
    .address-dropdown { display: none; position: absolute; top: 100%; left: 0; right: 0; z-index: 50; margin-top: 4px; }
    .address-dropdown.show { display: block; }
    .address-item { display: flex; align-items: center; gap: 10px; padding: 12px 16px; cursor: pointer; transition: background 0.15s; }
    .address-item:hover { background: #f8f1fb; }
    .schedule-card { border: 1.5px solid #cac4d3; border-radius: 16px; padding: 32px; cursor: pointer; transition: all 0.2s; background: white; position: relative; }
    .schedule-card.selected { border-color: #310f7a; border-width: 2px; }
    .schedule-card .radio-indicator { width: 20px; height: 20px; border-radius: 50%; border: 2px solid #cac4d3; position: absolute; top: 20px; right: 20px; transition: all 0.2s; display: flex; align-items: center; justify-content: center; }
    .schedule-card.selected .radio-indicator { border-color: #310f7a; background: #310f7a; }
    .schedule-card.selected .radio-indicator::after { content: ''; width: 6px; height: 6px; background: white; border-radius: 50%; }
    .payout-card { border: 1.5px solid #cac4d3; border-radius: 16px; padding: 32px; cursor: pointer; transition: all 0.2s; background: white; position: relative; }
    .payout-card.selected { border-color: #310f7a; border-width: 2px; }
    .payout-card .radio-indicator { width: 20px; height: 20px; border-radius: 50%; border: 2px solid #cac4d3; position: absolute; top: 20px; right: 20px; transition: all 0.2s; display: flex; align-items: center; justify-content: center; }
    .payout-card.selected .radio-indicator { border-color: #310f7a; background: #310f7a; }
    .payout-card.selected .radio-indicator::after { content: ''; width: 6px; height: 6px; background: white; border-radius: 50%; }
    .toggle-segment { padding: 8px 20px; border-radius: 20px; font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.2s; }
    .toggle-segment.active { background: #310f7a; color: white; }
    .toggle-segment:not(.active) { color: #494552; }
    .buffer-btn { padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.2s; border: 1.5px solid #cac4d3; background: white; color: #494552; }
    .buffer-btn.active { background: #310f7a; color: white; border-color: #310f7a; }
    .toggle-switch { width: 48px; height: 26px; border-radius: 13px; background: #cac4d3; cursor: pointer; position: relative; transition: background 0.3s; flex-shrink: 0; }
    .toggle-switch.active { background: #310f7a; }
    .toggle-switch::after { content: ''; position: absolute; top: 3px; left: 3px; width: 20px; height: 20px; border-radius: 50%; background: white; transition: transform 0.3s; box-shadow: 0 1px 3px rgba(0,0,0,0.15); }
    .toggle-switch.active::after { transform: translateX(22px); }
    @media (max-width: 1023px) {
      .sidebar.open { display: flex !important; }
      .main-content { padding-top: 60px !important; }
      .sidebar-backdrop { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 99; }
      .sidebar-backdrop.open { display: block; }
    }
    .form-input { width: 100%; padding: 0.75rem 1rem; border-radius: 0.75rem; border: 1px solid rgba(202,196,211,0.5); background: #fff; color: #1c1b21; }
    .form-input:focus { outline: none; ring: 2px; box-shadow: 0 0 0 2px rgba(49,15,122,0.25); }
    .select2-container { width: 100% !important; max-width: 100%; z-index: 1; }
    .select2-container--open { z-index: 10060 !important; }
    .select2-container--default .select2-selection--single {
      min-height: 48px;
      padding: 6px 12px;
      border-radius: 0.75rem;
      border: 1px solid rgba(202,196,211,0.5) !important;
      background: #fff !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
      line-height: 2.25rem;
      padding-left: 4px;
      padding-right: 2rem;
      color: #1c1b21;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow { height: 46px; right: 8px; }
    .select2-container--default.select2-container--focus .select2-selection--single,
    .select2-container--default.select2-container--open .select2-selection--single {
      border-color: #310f7a !important;
      box-shadow: 0 0 0 2px rgba(49,15,122,0.25);
    }
    .select2-dropdown {
      border-radius: 0.75rem;
      border-color: rgba(202,196,211,0.5);
      overflow: hidden;
      box-shadow: 0 16px 40px -12px rgba(49, 15, 122, 0.12);
      z-index: 10060;
    }
    .select2-container--default .select2-results > .select2-results__options {
      max-height: min(60vh, 320px);
    }
    .select2-container--default .select2-results__option {
      padding: 0.5rem 0.75rem;
      font-size: 0.875rem;
    }
    .select2-container--default .select2-results__option--highlighted[aria-selected] { background-color: #310f7a !important; color: #fff !important; }
    .select2-container--default .select2-results__option[aria-selected="true"] { background-color: #f2ecf5; color: #1c1b21; }
    .select2-container--default .select2-search--dropdown {
      padding: 8px;
      border-bottom: 1px solid rgba(202,196,211,0.35);
    }
    .select2-container--default .select2-search--dropdown .select2-search__field {
      border-radius: 0.5rem;
      border-color: rgba(202,196,211,0.5);
      padding: 0.5rem 0.75rem;
    }
  </style>
  {{-- Page-specific CSS/JS (e.g. Cropper on profile, Maps on studio): @push('head') --}}
  @stack('head')
</head>
@php
  $hideSidebar = $hideSidebar ?? false;
  $navKeys = ['profile','styles-social','studio','preferences','calendar','payment'];
  $stepIndex = array_search($activeNav ?? 'profile', $navKeys, true);
  if ($stepIndex === false) { $stepIndex = 0; }
  $progressPercent = round((($stepIndex + 1) / 6) * 100, 2);
  $progressLabel = ($activeNav ?? '') === 'payment' ? 'Artist Setup' : 'Setup Progress';
@endphp
<body class="bg-surface text-on-surface min-h-screen {{ $hideSidebar ? 'flex flex-col' : 'flex' }}">
  @if($hideSidebar)
  <header class="bg-primary text-white px-6 py-4 flex items-center justify-center gap-3 shadow-md shrink-0">
    <img src="{{ asset('assets/img/branding/logo.png') }}" alt="{{ config('app.name', 'Inkjin') }}" class="h-10 w-auto" width="160" height="40" />
    <span class="text-xl font-bold tracking-tight">{{ config('app.name', 'Inkjin') }}</span>
  </header>
  @else
  <div id="sidebarBackdrop" class="sidebar-backdrop lg:hidden" onclick="document.getElementById('mobileSidebar').classList.remove('open','flex'); document.getElementById('mobileSidebar').classList.add('hidden'); this.classList.remove('open');"></div>

  <div class="mobile-header fixed top-0 left-0 right-0 z-50 bg-primary text-white px-4 py-3 items-center justify-between">
    <div><span class="text-lg font-bold">{{ config('app.name', 'Inkjin') }}</span></div>
    <button type="button" onclick="(function(){ var s=document.getElementById('mobileSidebar'); var b=document.getElementById('sidebarBackdrop'); s.classList.toggle('hidden'); s.classList.toggle('flex'); s.classList.add('open'); b.classList.toggle('hidden'); b.classList.add('open'); })()" class="material-symbols-outlined text-white border-0 bg-transparent cursor-pointer">menu</button>
  </div>

  <aside class="sidebar hidden lg:flex fixed top-0 left-0 bg-primary flex-col justify-between p-6 z-40 open:shadow-xl" id="mobileSidebar">
    <div>
      {{-- <div class="mb-10">
        <h1 class="text-white text-xl font-bold">{{ config('app.name', 'Inkjin') }}</h1>
        <p class="text-white/50 text-[10px] uppercase tracking-[2px] mt-1">Artist Onboarding</p>
      </div> --}}

      <div class="mb-6 flex flex-col gap-1">
        <span class="text-white text-2xl font-bold tracking-tighter leading-none"
          style="font-family: 'Space Grotesk', sans-serif;">bookpay</span>
        <span class="text-white/40 text-[8px] uppercase tracking-widest font-medium leading-tight">Tattoo artist
          platform<br>by Inkjin</span>
      </div>

      <nav class="flex flex-col gap-1">
        @php
          $items = [
            ['key'=>'profile','route'=>'onboarding.profile','label'=>'Profile','icon'=>'person'],
            ['key'=>'styles-social','route'=>'onboarding.styles-social','label'=>'Styles & Social','icon'=>'brush'],
            ['key'=>'studio','route'=>'onboarding.studio','label'=>'Studio','icon'=>'storefront'],
            ['key'=>'preferences','route'=>'onboarding.preferences','label'=>'Preferences','icon'=>'tune'],
            ['key'=>'calendar','route'=>'onboarding.calendar','label'=>'Calendar','icon'=>'calendar_today'],
            ['key'=>'payment','route'=>'onboarding.payment','label'=>'Payments','icon'=>'payments'],
          ];
        @endphp
        @foreach ($items as $item)
          <a href="{{ route($item['route']) }}" class="nav-item {{ ($activeNav ?? '') === $item['key'] ? 'active' : '' }}">
            <span class="material-symbols-outlined">{{ $item['icon'] }}</span> {{ $item['label'] }}
          </a>
        @endforeach
        <div class="mt-4 pt-4 border-t border-white/10">
          <form method="POST" action="{{ route('logout') }}" class="m-0">
            @csrf
            <button type="submit" class="nav-item text-white/60 hover:text-white w-full text-left border-0 bg-transparent cursor-pointer font-[inherit]">
              <span class="material-symbols-outlined">logout</span> Log Out
            </button>
          </form>
        </div>
      </nav>
    </div>
    <div class="bg-white/10 rounded-xl p-3 flex items-center gap-3">
      <div class="flex-1 min-w-0">
        <p class="text-white text-xs font-medium">{{ $progressLabel }}</p>
        <div class="w-full h-1.5 bg-white/20 rounded-full mt-1.5">
          <div class="progress-fill h-full bg-white rounded-full" style="width: {{ $progressPercent }}%"></div>
        </div>
      </div>
    </div>
  </aside>
  @endif

  <main class="{{ $hideSidebar ? 'flex-1 min-h-0 w-full flex flex-col' : 'main-content flex-1 min-h-screen flex flex-col' }}">
    @if (session('success'))
      <div class="mx-8 mt-4 md:mx-12 rounded-xl bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm">{{ session('success') }}</div>
    @endif
    @if (session('error'))
      <div id="onboarding-flash-error" class="mx-8 mt-4 md:mx-12 rounded-xl bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm" role="alert">{{ session('error') }}</div>
    @endif
    @if (session('info'))
      <div class="mx-8 mt-4 md:mx-12 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 px-4 py-3 text-sm">{{ session('info') }}</div>
    @endif
    @yield('content')
  </main>
  <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
  <script src="{{ asset('design/js/currencies.js') }}"></script>
  <script>
  (function ($) {
    if (!$) return;
    var csrf = document.querySelector('meta[name="csrf-token"]');
    if (csrf && csrf.getAttribute('content')) {
      $.ajaxSetup({
        headers: {
          'X-CSRF-TOKEN': csrf.getAttribute('content'),
        },
      });
    }
  })(window.jQuery);
  </script>
  <script>
  (function () {
    function visible(el) {
      if (!el) return false;
      if (el.classList && el.classList.contains('hidden')) return false;
      var st = window.getComputedStyle(el);
      if (st.display === 'none' || st.visibility === 'hidden') return false;
      return true;
    }
    window.scrollToFirstOnboardingError = function (root) {
      var scope = root;
      if (typeof root === 'string') scope = document.querySelector(root);
      else if (root && root.nodeType) scope = root;
      else scope = document.querySelector('main');
      if (!scope) scope = document.body;
      var errs = scope.querySelectorAll('[id$="_error"]');
      for (var i = 0; i < errs.length; i++) {
        var el = errs[i];
        if (!visible(el)) continue;
        if (!(el.textContent || '').trim()) continue;
        setTimeout(function (node) {
          node.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }, 0, el);
        return true;
      }
      var alertIds = ['calAlert', 'payAlert', 'prefAlert', 'scheduling_type_error', 'onboarding-flash-error'];
      for (var j = 0; j < alertIds.length; j++) {
        var a = document.getElementById(alertIds[j]);
        if (!a || !visible(a)) continue;
        if (!(a.textContent || '').trim()) continue;
        setTimeout(function (node) {
          node.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }, 0, a);
        return true;
      }
      return false;
    };

    var NESTED_ERR = {
      'social_links.website': 'website_error',
      'social_links.instagram': 'instagram_error',
      'social_links.tiktok': 'tiktok_error',
      'social_links.youtube': 'youtube_error',
      'social_links.facebook': 'facebook_error',
    };
    var NESTED_IN = {
      'social_links.website': 'website',
      'social_links.instagram': 'instagram',
      'social_links.tiktok': 'tiktok',
      'social_links.youtube': 'youtube',
      'social_links.facebook': 'facebook',
    };

    window.clearOnboardingFieldError = function (serverKey) {
      if (!serverKey) return;
      var errId = NESTED_ERR[serverKey];
      if (!errId) errId = serverKey.indexOf('.') === -1 ? serverKey + '_error' : serverKey.replace(/\./g, '_') + '_error';
      var err = document.getElementById(errId);
      if (err) { err.textContent = ''; err.classList.add('hidden'); }

      var inputId = NESTED_IN[serverKey];
      if (!inputId) inputId = serverKey.indexOf('.') !== -1 ? serverKey.split('.').pop() : serverKey;
      var el = document.getElementById(inputId);
      if (el && window.jQuery && el.classList.contains('select2-hidden-accessible')) {
        window.jQuery(el).next('.select2-container').removeClass('ring-2 ring-error/40 rounded-xl');
      } else if (el) {
        el.classList.remove('border-error', 'ring-2', 'ring-error/40');
      }
      if (serverKey === 'other_styles') {
        var w = document.getElementById('wrap_other_styles');
        if (w) w.classList.remove('ring-2', 'ring-error/40');
      }
    };

    window.clearOnboardingAlert = function (elementId) {
      var a = document.getElementById(elementId);
      if (!a) return;
      a.textContent = '';
      a.classList.add('hidden');
    };
    document.addEventListener('DOMContentLoaded', function () {
      var flash = document.getElementById('onboarding-flash-error');
      if (flash && (flash.textContent || '').trim()) {
        setTimeout(function () {
          flash.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }, 100);
      }
    });
  })();
  </script>
  @stack('scripts')
  <script>
  (function () {
    function select2Opts($el) {
      var $body = window.jQuery('body');
      var n = $el.find('option').not('[value=""]').length;
      if (n < 1) {
        n = $el.find('option').length;
      }
      return {
        width: '100%',
        dropdownParent: $body,
        minimumResultsForSearch: n > 14 ? 0 : Infinity,
      };
    }
    function initSelect2($ctx) {
      if (!window.jQuery || !window.jQuery.fn || !window.jQuery.fn.select2) return;
      var $ = window.jQuery;
      $ctx.find('select.select').each(function () {
        var $el = $(this);
        if ($el.hasClass('no-select2')) return;
        if ($el.hasClass('select2-hidden-accessible')) return;
        if (!$el.closest('body').length) return;
        if (!$el.is(':visible')) return;
        if (!$el.find('option').length) return;
        try {
          $el.select2(select2Opts($el));
        } catch (e) {
          if (window.console && console.warn) console.warn('Select2 init skipped', e);
        }
      });
    }
    window.initOnboardingSelect2 = function ($root) {
      initSelect2($root && $root.length ? $root : window.jQuery('main'));
    };
    window.destroyOnboardingSelect2 = function ($root) {
      if (!window.jQuery || !window.jQuery.fn || !window.jQuery.fn.select2) return;
      var $ = window.jQuery;
      var $ctx = $root && $root.length ? $root : $('main');
      $ctx.find('select.select2-hidden-accessible').each(function () {
        try {
          $(this).select2('destroy');
        } catch (e) { /* ignore */ }
      });
    };
    $(function () {
      window.requestAnimationFrame(function () {
        initSelect2(window.jQuery('main'));
      });
    });
  })();
  </script>
</body>
</html>
