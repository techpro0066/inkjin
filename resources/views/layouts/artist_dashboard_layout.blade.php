<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>Dashboard — Inkjin Book & Pay</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Your artist dashboard on Inkjin Book & Pay. Manage bookings, payments, and content.">
    <link rel="icon" href="{{ asset('assets/img/favicon/favicon.png') }}">
    <link href="{{ asset('assets/css/bookpay.css') }}" rel="stylesheet">
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
            borderRadius: {"DEFAULT": "0.25rem", "lg": "0.5rem", "xl": "0.75rem", "full": "9999px"},
          },
        },
      }
    </script>
  <!-- end of common js -->
  <!-- start of common css -->
  <style>
    body { font-family: 'Plus Jakarta Sans', sans-serif; }
    .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
    /* Sidebar */
    
    .mobile-header { display: none; }
    @media (max-width: 1023px) { .mobile-header { display: flex; } }

    .sidebar { width: 260px; min-height: 100vh; overflow-y: auto; }
    @media (max-width: 1023px) { .sidebar { width: 100vw; height: 100vh; min-height: 100vh; } }
    @media (min-width: 1024px) { .sidebar { display: flex !important; } .main-content { margin-left: 260px; } }
    @media (max-width: 1023px) { .main-content {  padding-top: 70px; } }
    .nav-item { display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 12px; font-size: 14px; font-weight: 500; color: rgba(255,255,255,0.85); transition: all 0.2s; cursor: pointer; text-decoration: none; }
    .nav-item:hover { background: rgba(255,255,255,0.1); }
    .nav-item.active { background: #ffffff; color: #310f7a; font-weight: 600; }
    .nav-item .material-symbols-outlined { font-size: 20px; }
    /* Progress bar */
    .progress-fill { transition: width 0.4s ease; }
    /* Mobile sidebar — handled by Tailwind responsive classes (hidden lg:flex) */
      .sidebar.open { display: flex !important; }
      
      .main-content {  padding-top: 60px !important; }
      .sidebar-backdrop {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.5);
        z-index: 99;
      }
    .sidebar-backdrop.open { display: block; }
  </style>
  <!-- end of common css -->

  @yield('styles')
</head>
<body class="bg-surface text-on-surface min-h-screen flex">

  <!-- Mobile Header -->
  <div class="mobile-header fixed top-0 left-0 right-0 z-50 bg-primary text-white px-4 py-3 items-center justify-between">
    <div>
      <span class="text-lg font-bold">Inkjin</span>
      
    </div>
    <button id="mobileMenuBtn" onclick="var s=document.getElementById('mobileSidebar');var b=document.getElementById('sidebarBackdrop');var isOpen=!s.classList.contains('hidden');if(isOpen){s.classList.add('hidden');s.classList.remove('flex');if(b){b.classList.add('hidden');b.classList.remove('open');}this.textContent='menu';}else{s.classList.remove('hidden');s.classList.add('flex');if(b){b.classList.remove('hidden');b.classList.add('open');}this.textContent='close';}" class="material-symbols-outlined text-white">menu</button>
  </div>

  <!-- Sidebar -->
  @include('layouts.components.artist_sidebar')

  <!-- Main Content -->
  @yield('content')

  

  <div id="saveToast" class="fixed top-6 right-6 z-50 transform translate-x-full opacity-0 transition-all duration-300">
    <div class="flex items-center gap-3 bg-on-surface text-white px-5 py-3 rounded-xl shadow-lg">
      <span class="material-symbols-outlined text-green-400" style="font-size:20px;">check_circle</span>
      <span class="text-sm font-medium">Changes saved successfully</span>
    </div>
  </div>

  {{-- jquery cdn --}}
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script>
    $(document).on('click', '.logout-form', function(e) {
      e.preventDefault();
      $(this).submit();
    });

    

    function showSaveToast() {
      const toast = document.getElementById('saveToast');
      toast.classList.remove('translate-x-full', 'opacity-0');
      toast.classList.add('translate-x-0', 'opacity-100');
      setTimeout(() => {
        toast.classList.add('translate-x-full', 'opacity-0');
        toast.classList.remove('translate-x-0', 'opacity-100');
      }, 3000);
    }
  </script>

  @yield('scripts')
</body>
</html>