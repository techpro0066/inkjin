<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>@yield('title', 'Dashboard') - {{ config('app.name', 'Inkjin') }}</title>
  <meta name="description" content="Your artist dashboard on Inkjin Book & Pay. Manage bookings, payments, and content.">
  <link rel="icon" href="{{ asset('assets/images/favicon.png') }}">
  <link href="{{ asset('assets/css/bookpay.css') }}" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">

  @yield('styles')
</head>
<body class="bg-surface text-on-surface min-h-screen flex">

  <!-- Mobile Header -->
  <div class="mobile-header fixed top-0 left-0 right-0 z-50 bg-primary text-white px-4 py-3 items-center justify-between">
    <div>
      <span class="text-lg font-bold">{{ config('app.name', 'Inkjin') }}</span>
      
    </div>
    <button onclick="var s=document.getElementById('mobileSidebar');var b=document.getElementById('sidebarBackdrop');s.classList.toggle('hidden');s.classList.toggle('flex');b.classList.toggle('hidden')" class="material-symbols-outlined text-white">menu</button>
  </div>

  @include('layouts.components.artist_sidebar')

  <!-- Main Content -->
  @yield('content')

  @yield('scripts')

  <script>
    // Toggle mobile sidebar
    function toggleMobileSidebar() {
      const sidebar = document.getElementById('mobileSidebar');
      const backdrop = document.getElementById('sidebarBackdrop');
      sidebar.classList.toggle('open');
      backdrop.classList.toggle('open');
    }
  </script>
</body>
</html>