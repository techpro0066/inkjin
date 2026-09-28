<!DOCTYPE html>
<html lang="en">
<head>
  @include('layouts.partials.google-analytics')
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="color-scheme" content="only light">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  @php
    $seoTitle = trim($__env->yieldContent('title')) ?: 'Bookpay by Inkjin';
    $seoDescription = trim($__env->yieldContent('meta_description')) ?: 'Bookpay by Inkjin — bookings and payments built for tattoo artists.';
    $seoOgTitle = trim($__env->yieldContent('og_title')) ?: $seoTitle;
    $seoOgDescription = trim($__env->yieldContent('og_description')) ?: $seoDescription;
    $seoOgImage = trim($__env->yieldContent('og_image')) ?: asset('design/images/bookpay-og.jpeg');
    $seoTwitterTitle = trim($__env->yieldContent('twitter_title')) ?: $seoOgTitle;
    $seoTwitterDescription = trim($__env->yieldContent('twitter_description')) ?: $seoOgDescription;
    $seoTwitterImage = trim($__env->yieldContent('twitter_image')) ?: $seoOgImage;
  @endphp
  <title>{{ $seoTitle }}</title>
  <meta name="description" content="{{ $seoDescription }}">
  @hasSection('canonical')
  <link rel="canonical" href="@yield('canonical')">
  @endif
  <meta name="robots" content="@yield('robots', 'noindex, follow')">

  <meta property="og:title" content="{{ $seoOgTitle }}">
  <meta property="og:description" content="{{ $seoOgDescription }}">
  <meta property="og:type" content="website">
  @hasSection('og_url')
  <meta property="og:url" content="@yield('og_url')">
  @endif
  <meta property="og:image" content="{{ $seoOgImage }}">
  <meta property="og:site_name" content="Bookpay by Inkjin">

  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="{{ $seoTwitterTitle }}">
  <meta name="twitter:description" content="{{ $seoTwitterDescription }}">
  <meta name="twitter:image" content="{{ $seoTwitterImage }}">

  <link rel="icon" href="{{ asset('design/images/icons/favicon.png') }}">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&display=block" rel="stylesheet">

  <style>
    * { box-sizing: border-box; }
    body {
      margin: 0;
      min-height: 100vh;
      background: #FFF6FF;
      color: #1A1A1A;
      font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
      font-size: 14.5px;
      display: flex;
      flex-direction: column;
      align-items: center;
      padding: 44px 16px 24px;
    }
    .ms {
      font-family: 'Material Symbols Outlined';
      font-weight: normal;
      font-style: normal;
      font-size: 20px;
      line-height: 1;
      display: inline-block;
      -webkit-font-smoothing: antialiased;
    }
    .logo {
      text-align: center;
      margin-bottom: 28px;
      text-decoration: none;
      color: inherit;
    }
    .logo b {
      display: block;
      font-family: 'Space Grotesk', system-ui, sans-serif;
      font-size: 30px;
      font-weight: 700;
      letter-spacing: -0.055em;
      line-height: 1;
    }
    .logo span {
      display: block;
      font-size: 8.5px;
      letter-spacing: 1.2px;
      margin-top: 6px;
      line-height: 1.3;
    }
    .card {
      background: #fff;
      border: 1px solid #F0E4F5;
      border-radius: 16px;
      width: 100%;
      max-width: 400px;
      padding: 34px 34px 30px;
      box-shadow: 0 12px 40px rgba(62, 0, 124, .05);
    }
    h1 {
      font-size: 26px;
      font-weight: 800;
      letter-spacing: -.6px;
      text-align: center;
      margin: 0 0 8px;
    }
    .sub {
      text-align: center;
      color: #3F3A43;
      line-height: 1.5;
      margin: 0 0 24px;
    }
    .fl {
      display: flex;
      justify-content: space-between;
      align-items: baseline;
      font-size: 13px;
      font-weight: 600;
      margin: 16px 0 7px;
    }
    .fl a {
      font-size: 12px;
      font-weight: 700;
      color: #1A1A1A;
      text-decoration: none;
    }
    .fl a:hover { text-decoration: underline; }
    .in {
      display: flex;
      align-items: center;
      border: 1px solid #DCD2E0;
      border-radius: 10px;
      background: #fff;
    }
    .in:focus-within {
      border-color: #3E007C;
      box-shadow: 0 0 0 3px #EEE3FA;
    }
    .in.bad { border-color: #C62828; }
    .in input {
      flex: 1;
      border: 0;
      outline: 0;
      font: inherit;
      font-size: 14.5px;
      padding: 12px 14px;
      background: none;
      min-width: 0;
      color: inherit;
    }
    .in .eye {
      border: 0;
      background: none;
      padding: 0 12px;
      cursor: pointer;
      color: #9A929E;
    }
    .err {
      color: #C62828;
      font-size: 12.5px;
      margin-top: 6px;
      display: none;
    }
    .err.on { display: block; }
    .ck {
      display: flex;
      gap: 10px;
      align-items: center;
      font-size: 13px;
      margin-top: 16px;
      cursor: pointer;
    }
    .ck input {
      -webkit-appearance: none;
      appearance: none;
      width: 18px;
      height: 18px;
      min-width: 18px;
      margin: 0;
      border: 1.5px solid #CFC6D4;
      background: #fff;
      border-radius: 5px;
      display: inline-grid;
      place-content: center;
      vertical-align: middle;
      cursor: pointer;
      flex: none;
      box-sizing: border-box;
      transition: background .12s, border-color .12s;
    }
    .ck input:hover { border-color: #3E007C; }
    .ck input:checked {
      border-color: #3E007C;
      background: #3E007C url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23fff' stroke-width='3.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M5 12.5l4.5 4.5L19 7.5'/%3E%3C/svg%3E") center/13px 13px no-repeat;
    }
    .ck input:focus-visible {
      outline: 2px solid #3E007C;
      outline-offset: 2px;
    }
    .btn {
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 8px;
      width: 100%;
      font: inherit;
      font-weight: 700;
      font-size: 15px;
      border: 0;
      border-radius: 12px;
      background: #1A1A1A;
      color: #fff;
      padding: 14px;
      cursor: pointer;
      margin-top: 22px;
      text-decoration: none;
    }
    .btn .ms { font-size: 19px; }
    .btn.ghost {
      background: #fff;
      color: #1A1A1A;
      border: 1px solid #E8DFEA;
    }
    .btn:disabled {
      opacity: .5;
      cursor: not-allowed;
    }
    .back {
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 8px;
      margin-top: 22px;
      font-weight: 600;
      color: #1A1A1A;
      text-decoration: none;
    }
    .back .ms { font-size: 18px; }
    .alt {
      text-align: center;
      font-size: 13px;
      margin-top: 20px;
      padding-top: 18px;
      border-top: 1px solid #F0E4F5;
    }
    .alt a {
      color: #1A1A1A;
      font-weight: 700;
      text-decoration: none;
    }
    .banner {
      display: flex;
      gap: 10px;
      align-items: flex-start;
      border-radius: 10px;
      padding: 11px 13px;
      font-size: 13px;
      line-height: 1.45;
      margin-bottom: 6px;
    }
    .banner.bad { background: #FDECEC; color: #B3261E; }
    .banner.ok { background: #E6F8EE; color: #1F6B4E; }
    .banner .ms { font-size: 18px; flex: none; }
    .icon {
      width: 54px;
      height: 54px;
      border-radius: 50%;
      background: #F3E8FF;
      color: #3E007C;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 16px;
    }
    .icon .ms { font-size: 26px; }
    .small {
      font-size: 13px;
      color: #6F6874;
      text-align: center;
      line-height: 1.5;
    }
    .link {
      background: none;
      border: 0;
      font: inherit;
      font-weight: 700;
      color: #1A1A1A;
      cursor: pointer;
      padding: 0;
      text-decoration: underline;
    }
    .link:disabled {
      color: #9A929E;
      cursor: default;
      text-decoration: none;
    }
    .rules {
      list-style: none;
      padding: 0;
      margin: 10px 0 0;
      font-size: 12.5px;
      color: #6F6874;
    }
    .rules li {
      display: flex;
      gap: 6px;
      align-items: center;
      margin: 4px 0;
    }
    .rules .ms { font-size: 16px; }
    .rules li.ok { color: #1F6B4E; }
    .meter {
      display: flex;
      gap: 4px;
      margin-top: 10px;
    }
    .meter i {
      flex: 1;
      height: 4px;
      border-radius: 2px;
      background: #EDE6F0;
    }
    .help {
      margin-top: 22px;
      font-size: 13px;
      color: #3F3A43;
    }
    .help a {
      color: #1A1A1A;
      font-weight: 600;
    }
    footer {
      margin-top: auto;
      padding-top: 40px;
      text-align: center;
      font-size: 13px;
    }
    footer nav {
      display: flex;
      gap: 28px;
      justify-content: center;
      flex-wrap: wrap;
    }
    footer a {
      color: #1A1A1A;
      text-decoration: none;
    }
    footer a:hover { text-decoration: underline; }
    footer small {
      display: block;
      color: #6F6874;
      margin-top: 12px;
      font-size: 12.5px;
    }
    @media (max-width: 480px) {
      .card { padding: 26px 20px 22px; }
      h1 { font-size: 23px; }
      body { padding-top: 32px; padding-bottom: 48px; }
    }
  </style>
  @stack('styles')
</head>
<body>
  <a class="logo" href="{{ url('/') }}" title="Bookpay by Inkjin">
    <b>bookpay</b>
    <span>FOR TATTOO ARTISTS AND STUDIOS<br>BY INKJIN</span>
  </a>

  <main class="card">
    @yield('content')
  </main>

  @php $helpContent = trim($__env->yieldContent('help')); @endphp
  @if ($__env->hasSection('help'))
    @if ($helpContent !== '')
      <p class="help">{!! $helpContent !!}</p>
    @endif
  @else
    <p class="help">Having trouble? <a href="mailto:artists@inkjin.com">Contact Support</a></p>
  @endif

  <footer>
    <nav>
      <a href="https://inkjin.com/en/privacy">Privacy Policy</a>
      <a href="https://inkjin.com/en/artist-terms">Terms of Service</a>
      <a href="https://help.inkjin.com">Help Center</a>
    </nav>
    <small>© {{ date('Y') }} Inkjin. All rights reserved.</small>
  </footer>

  <script>
    (function () {
      document.querySelectorAll('.eye').forEach(function (btn) {
        btn.addEventListener('click', function () {
          var input = btn.parentNode.querySelector('input');
          if (!input) return;
          var show = input.type === 'password';
          input.type = show ? 'text' : 'password';
          var icon = btn.querySelector('.ms');
          if (icon) icon.textContent = show ? 'visibility_off' : 'visibility';
          btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
        });
      });
    })();
  </script>
  @stack('scripts')
</body>
</html>
