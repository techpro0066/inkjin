@php
  $displayName = $userDetail->publicDisplayName();
  $username = trim((string) ($userDetail->user_name ?? ''));
  $handle = $username !== '' ? '@'.$username : '';
  $firstName = trim((string) ($userDetail->user?->first_name ?? ''));
  if ($firstName === '') {
      $parts = preg_split('/\s+/u', $displayName, -1, PREG_SPLIT_NO_EMPTY) ?: [];
      $firstName = $parts[0] ?? $displayName;
  }
  $avatarPath = trim((string) ($userDetail->avatar ?? ''));
  $avatarUrl = $avatarPath !== ''
      ? (str_starts_with($avatarPath, 'http') ? $avatarPath : asset(ltrim($avatarPath, '/')))
      : null;
  $initials = $userDetail->publicDisplayInitials();
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="color-scheme" content="only light">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="robots" content="noindex,nofollow">
  @include('layouts.partials.google-analytics')
  <title>{{ $displayName }} isn’t taking bookings right now</title>
  <meta name="description" content="{{ $displayName }} isn’t taking bookings right now.">
  <link rel="icon" href="{{ asset('design/images/icons/favicon.png') }}">
  <style>
    @font-face{font-family:'PJS';font-weight:400;src:url('{{ asset('new-ui-design-assets/pjs-400.woff2') }}') format('woff2')}
    @font-face{font-family:'PJS';font-weight:500;src:url('{{ asset('new-ui-design-assets/pjs-500.woff2') }}') format('woff2')}
    @font-face{font-family:'PJS';font-weight:600;src:url('{{ asset('new-ui-design-assets/pjs-600.woff2') }}') format('woff2')}
    @font-face{font-family:'PJS';font-weight:700;src:url('{{ asset('new-ui-design-assets/pjs-700.woff2') }}') format('woff2')}
    @font-face{font-family:'PJS';font-weight:800;src:url('{{ asset('new-ui-design-assets/pjs-800.woff2') }}') format('woff2')}
    @font-face{font-family:'Space Grotesk';font-weight:700;font-display:swap;src:url('{{ asset('new-ui-design-assets/space-grotesk-700.woff2') }}') format('woff2')}
    *{box-sizing:border-box;margin:0;padding:0}
    html,body{min-height:100%;min-height:100dvh}
    body{
      font-family:'PJS',system-ui,sans-serif;
      background:#FBF9FC;color:#17141A;
      display:flex;flex-direction:column;align-items:center;justify-content:center;
      padding:32px 20px 28px;-webkit-font-smoothing:antialiased
    }
    .card{
      width:100%;max-width:440px;background:#fff;border:1px solid #ECE6EF;border-radius:28px;
      box-shadow:0 18px 50px rgba(30,10,40,.08);padding:40px 36px 36px;text-align:center
    }
    .av{
      width:112px;height:112px;border-radius:50%;margin:0 auto 18px;
      background:#1A1A2E center/cover no-repeat;overflow:hidden;
      display:flex;align-items:center;justify-content:center;
      box-shadow:0 8px 24px rgba(20,10,30,.14)
    }
    .av.ini{
      color:#fff;font-weight:800;font-size:34px;letter-spacing:-.5px;
      background:linear-gradient(135deg,#1A1A2E,#3A3A5C)
    }
    .name{font-size:28px;font-weight:800;letter-spacing:-.7px;line-height:1.15;margin:0}
    .handle{margin-top:6px;font-size:15px;font-weight:600;color:#9A93A0}
    .msg{margin-top:22px;font-size:18px;font-weight:700;letter-spacing:-.3px;line-height:1.35}
    .sub{margin-top:10px;font-size:14.5px;font-weight:400;color:#6B6470;line-height:1.55}
    .brand{
      margin-top:28px;display:flex;align-items:baseline;justify-content:center;gap:6px;
      font-size:13px;color:#9A93A0
    }
    .brand b{
      font-family:'Space Grotesk',system-ui,sans-serif;font-weight:700;color:#17141A;
      letter-spacing:-.055em;font-size:15px
    }
    @media (max-width:480px){
      .card{padding:32px 22px 28px;border-radius:22px}
      .name{font-size:24px}
      .msg{font-size:16.5px}
      .av{width:96px;height:96px}
      .av.ini{font-size:30px}
    }
  </style>
</head>
<body>
  <div class="card">
    @if ($avatarUrl)
      <div class="av" style="background-image:url('{{ $avatarUrl }}')" role="img" aria-label="{{ $displayName }}"></div>
    @else
      <div class="av ini" aria-hidden="true">{{ $initials }}</div>
    @endif

    <h1 class="name">{{ $displayName }}</h1>
    @if ($handle !== '')
      <div class="handle">{{ $handle }}</div>
    @endif

    <p class="msg">{{ $displayName }} isn’t taking bookings right now.</p>
    <p class="sub">Already have a booking with {{ $firstName }}? Use the link in your booking email. It still works.</p>
  </div>

  <div class="brand"><b>bookpay</b> by Inkjin</div>
</body>
</html>
