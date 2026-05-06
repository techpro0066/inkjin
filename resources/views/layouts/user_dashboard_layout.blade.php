<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'Dashboard') - {{ config('app.name', 'Inkjin') }}</title>
  <meta name="description" content="Your client dashboard on Inkjin Book & Pay. Manage bookings, messages, and payments.">
  <link rel="icon" href="{{ asset('design/images/icons/favicon.png') }}">
  <link href="{{ asset('design/css/inkjin_bookpay.css') }}" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
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
            "primary": "#310f7a",
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
            "on-primary-container": "#b69fff",
            "secondary": "#625881",
            "inverse-primary": "#cebdff",
            "primary-fixed": "#e8ddff",
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
            "on-primary-fixed": "#21005e",
            "primary-fixed-dim": "#cebdff",
            "on-tertiary-container": "#e49e62",
            "on-primary-fixed-variant": "#4e3397",
            "primary-container": "#482d91",
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
          borderRadius: {"DEFAULT": "0.25rem", "lg": "0.5rem", "xl": "0.75rem", "full": "9999px"},
        },
      },
    }
  </script>
  <style>
    body { font-family: 'Plus Jakarta Sans', sans-serif; }
    .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
    .mobile-header { display: none; }
    @media (max-width: 1023px) { .mobile-header { display: flex; } }
    .sidebar { width: 260px; min-height: 100vh; }
    @media (min-width: 1024px) { .sidebar { display: flex !important; } .main-content { margin-left: 260px; } }
    @media (max-width: 1023px) { .main-content { padding-top: 70px; } }
    .nav-item { display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 12px; font-size: 14px; font-weight: 500; color: rgba(255,255,255,0.85); transition: all 0.2s; cursor: pointer; text-decoration: none; }
    .nav-item:hover { background: rgba(255,255,255,0.1); }
    .nav-item.active { background: #ffffff; color: #310f7a; font-weight: 600; }
    .nav-item .material-symbols-outlined { font-size: 20px; }
    .stat-card { transition: transform 0.2s ease, box-shadow 0.2s ease; }
    .stat-card:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(49,15,122,0.1); }
    .quick-action { transition: transform 0.2s ease, box-shadow 0.2s ease; }
    .quick-action:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(49,15,122,0.1); }
    .guide-card { transition: transform 0.2s ease, box-shadow 0.2s ease; }
    .guide-card:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(49,15,122,0.08); }
    .sidebar.open { display: flex !important; }
    .sidebar-backdrop { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.45); z-index: 90; }
    .sidebar-backdrop.open { display: block; }
    @media (max-width: 1023px) {
      .main-content { overflow-x: hidden; padding: 16px; padding-top: 70px; }
      body { overflow-x: hidden; }
      #mobileSidebar.flex {
        width: 100% !important;
        min-width: 100%;
        left: 0;
        right: 0;
        top: 0;
        bottom: 0;
        min-height: 100vh;
        min-height: 100dvh;
        z-index: 100;
        justify-content: space-between;
      }
    }
  </style>
  @yield('styles')
</head>
<body class="bg-surface text-on-surface min-h-screen flex">

  <!-- Mobile Header -->
  <div class="mobile-header fixed top-0 left-0 right-0 z-[110] bg-primary text-white px-4 py-3 items-center justify-between">
    <div>
      <span class="text-lg font-bold">Inkjin</span>
    </div>
    <button type="button" id="mobileMenuBtn" onclick="toggleMobileNav()" class="material-symbols-outlined text-white p-1 rounded-lg hover:bg-white/10 transition-colors" aria-expanded="false" aria-label="Open menu">menu</button>
  </div>

  <!-- Sidebar Backdrop -->
  <div id="sidebarBackdrop" class="sidebar-backdrop hidden" onclick="closeMobileNav()"></div>

  <!-- Sidebar -->
  @include('layouts.components.user_sidebar')

  <!-- Main Content -->
  @yield('content')

  @yield('modals')
  
  {{-- jquery cdn --}}
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script>
    function closeMobileNav() {
      var sidebar = document.getElementById('mobileSidebar');
      var backdrop = document.getElementById('sidebarBackdrop');
      var btn = document.getElementById('mobileMenuBtn');
      if (sidebar) {
        sidebar.classList.add('hidden');
        sidebar.classList.remove('flex');
      }
      if (backdrop) backdrop.classList.add('hidden');
      if (btn) {
        btn.textContent = 'menu';
        btn.setAttribute('aria-expanded', 'false');
        btn.setAttribute('aria-label', 'Open menu');
      }
      document.body.style.overflow = '';
    }
    function toggleMobileNav() {
      var sidebar = document.getElementById('mobileSidebar');
      var backdrop = document.getElementById('sidebarBackdrop');
      var btn = document.getElementById('mobileMenuBtn');
      if (!sidebar || window.matchMedia('(min-width: 1024px)').matches) return;
      var open = sidebar.classList.contains('hidden');
      if (open) {
        sidebar.classList.remove('hidden');
        sidebar.classList.add('flex');
        if (backdrop) backdrop.classList.remove('hidden');
        if (btn) {
          btn.textContent = 'close';
          btn.setAttribute('aria-expanded', 'true');
          btn.setAttribute('aria-label', 'Close menu');
        }
        document.body.style.overflow = 'hidden';
      } else {
        closeMobileNav();
      }
    }
  </script>

  @yield('scripts')

</body>
</html>