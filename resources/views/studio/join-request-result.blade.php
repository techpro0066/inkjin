<!DOCTYPE html>
<html lang="en">
<head>
  @include('layouts.partials.google-analytics')
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Invitation — Bookpay</title>
  <link rel="icon" href="{{ asset('design/images/icons/favicon.png') }}">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@700&display=swap" rel="stylesheet">
  <style>
    *{box-sizing:border-box}
    body{margin:0;min-height:100vh;background:#FFF6FF;color:#1A1A1A;font-family:'Plus Jakarta Sans',system-ui,sans-serif;font-size:14.5px;display:flex;flex-direction:column;align-items:center;padding:44px 16px 40px}
    .logo{text-align:center;margin-bottom:28px;text-decoration:none;color:inherit}
    .logo b{display:block;font-family:'Space Grotesk',system-ui,sans-serif;font-size:30px;font-weight:700;letter-spacing:-.055em;line-height:1}
    .logo span{display:block;font-size:8.5px;letter-spacing:1.2px;margin-top:6px;line-height:1.3;color:#6F6874}
    .card{background:#fff;border:1px solid #F0E4F5;border-radius:16px;width:100%;max-width:480px;padding:34px 34px 30px;box-shadow:0 12px 40px rgba(62,0,124,.05);text-align:center}
    h1{font-size:26px;font-weight:800;letter-spacing:-.6px;margin:0 0 8px}
    .sub{color:#3F3A43;line-height:1.5;margin:0 0 8px}
    footer{margin-top:auto;padding-top:40px;text-align:center;font-size:13px;color:#6F6874}
  </style>
</head>
<body>
  <a class="logo" href="{{ url('/') }}">
    <b>bookpay</b>
    <span>BY INKJIN</span>
  </a>

  <div class="card">
    @if($status === 'accepted')
      <h1>Invitation accepted</h1>
      <p class="sub"><strong>{{ $artistName }}</strong> is now linked to <strong>{{ $studioName }}</strong>.</p>
    @elseif($status === 'declined')
      <h1>Invitation declined</h1>
      <p class="sub">You declined <strong>{{ $artistName }}</strong>’s request to join <strong>{{ $studioName }}</strong>.</p>
    @else
      <h1>Already handled</h1>
      <p class="sub">This invitation for <strong>{{ $artistName }}</strong> is no longer pending.</p>
    @endif
  </div>

  <footer>© {{ date('Y') }} Bookpay by Inkjin</footer>
</body>
</html>
