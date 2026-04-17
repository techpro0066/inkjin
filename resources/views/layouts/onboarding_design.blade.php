<!DOCTYPE html>
<html class="light" lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="csrf-token" content="{{ csrf_token() }}" />
  <title>@yield('title', 'Onboarding') — {{ config('app.name', 'Inkjin') }}</title>
  <link rel="stylesheet" href="{{ asset('design/css/inkjin_main.css') }}" />
  <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
  <script>
    tailwind.config = {
      darkMode: 'class',
      theme: {
        extend: {
          colors: {
            background: '#fdf7ff',
            primary: '#310f7a',
            'on-surface': '#1c1b21',
            'surface-container-low': '#f8f1fb',
            outline: '#7a7583',
          },
          fontFamily: { sans: ['Plus Jakarta Sans', 'sans-serif'] },
        },
      },
    };
  </script>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
  @stack('head')
</head>
<body class="bg-background text-on-surface min-h-screen">
  <div id="sidebarBackdrop" class="sidebar-backdrop lg:hidden" onclick="closeSidebar()"></div>

  <aside id="onboardingSidebar" class="sidebar brand-gradient fixed left-0 top-0 z-[100] flex flex-col py-6 px-4 text-white lg:translate-x-0 -translate-x-full transition-transform duration-200">
    <div class="mb-8 px-2">
      <a href="{{ route('dashboard') }}" class="flex items-center gap-2 text-white no-underline font-bold text-lg">
        <span class="material-symbols-outlined">ink_pen</span>
        {{ config('app.name', 'Inkjin') }}
      </a>
      <p class="text-white/70 text-xs mt-1">Artist onboarding</p>
    </div>
    <nav class="flex flex-col gap-1 flex-1">
      @php
        $items = [
          ['key' => 'profile', 'route' => 'onboarding.profile', 'label' => 'Profile', 'icon' => 'person'],
          ['key' => 'styles-social', 'route' => 'onboarding.styles-social', 'label' => 'Styles & social', 'icon' => 'palette'],
          ['key' => 'studio', 'route' => 'onboarding.studio', 'label' => 'Studio', 'icon' => 'storefront'],
          ['key' => 'preferences', 'route' => 'onboarding.preferences', 'label' => 'Preferences', 'icon' => 'tune'],
          ['key' => 'calendar', 'route' => 'onboarding.calendar', 'label' => 'Calendar', 'icon' => 'calendar_month'],
          ['key' => 'payment', 'route' => 'onboarding.payment', 'label' => 'Payment', 'icon' => 'payments'],
        ];
      @endphp
      @foreach ($items as $item)
        <a href="{{ route($item['route']) }}"
           class="nav-item {{ ($activeNav ?? '') === $item['key'] ? 'active' : '' }}">
          <span class="material-symbols-outlined">{{ $item['icon'] }}</span>
          {{ $item['label'] }}
        </a>
      @endforeach
    </nav>
    <form method="POST" action="{{ route('logout') }}" class="mt-auto pt-4">
      @csrf
      <button type="submit" class="nav-item w-full border-0 bg-transparent text-left text-white/90">
        <span class="material-symbols-outlined">logout</span>
        Log out
      </button>
    </form>
  </aside>

  <header class="mobile-header fixed top-0 left-0 right-0 z-[101] flex items-center justify-between px-4 py-3 brand-gradient text-white lg:hidden">
    <button type="button" class="p-2 rounded-lg bg-white/10" onclick="openSidebar()" aria-label="Open menu">
      <span class="material-symbols-outlined">menu</span>
    </button>
    <span class="font-semibold">@yield('mobile_title', 'Onboarding')</span>
    <span class="w-10"></span>
  </header>

  <main class="main-content min-h-screen lg:ml-[260px] p-4 md:p-8 lg:p-10">
    <div class="max-w-3xl mx-auto">
      @if (session('success'))
        <div class="mb-4 rounded-xl bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm">{{ session('success') }}</div>
      @endif
      @if (session('error'))
        <div class="mb-4 rounded-xl bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">{{ session('error') }}</div>
      @endif
      @if (session('info'))
        <div class="mb-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 px-4 py-3 text-sm">{{ session('info') }}</div>
      @endif
      @yield('content')
    </div>
  </main>

  <script>
    function openSidebar() {
      document.getElementById('onboardingSidebar').classList.add('open');
      document.getElementById('sidebarBackdrop').classList.add('open');
    }
    function closeSidebar() {
      document.getElementById('onboardingSidebar').classList.remove('open');
      document.getElementById('sidebarBackdrop').classList.remove('open');
    }
  </script>
  @stack('scripts')
</body>
</html>
