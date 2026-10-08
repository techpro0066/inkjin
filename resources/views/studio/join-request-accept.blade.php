<!DOCTYPE html>
<html lang="en">
<head>
  @include('layouts.partials.google-analytics')
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Accept invitation — Bookpay</title>
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
    .card{background:#fff;border:1px solid #F0E4F5;border-radius:16px;width:100%;max-width:480px;padding:34px 34px 30px;box-shadow:0 12px 40px rgba(62,0,124,.05)}
    h1{font-size:26px;font-weight:800;letter-spacing:-.6px;text-align:center;margin:0 0 8px}
    .sub{text-align:center;color:#3F3A43;line-height:1.5;margin:0 0 24px}
    .box{border:1px solid #F0E4F5;border-radius:14px;padding:14px 16px;margin-top:14px}
    .kv{display:flex;justify-content:space-between;align-items:center;gap:10px;font-size:14px;margin-top:8px}.kv span{color:#6F6874}
    .btn{display:flex;justify-content:center;align-items:center;gap:8px;width:100%;font:inherit;font-weight:700;font-size:15px;border:0;border-radius:12px;background:#1A1A1A;color:#fff;padding:14px;cursor:pointer;margin-top:12px;text-decoration:none}
    .btn.ghost{background:#fff;color:#1A1A1A;border:1px solid #E8DFEA}
    .two{display:flex;gap:10px;margin-top:22px}.two .btn{margin-top:0}
    footer{margin-top:auto;padding-top:40px;text-align:center;font-size:13px;color:#6F6874}
  </style>
</head>
<body>
  <a class="logo" href="{{ url('/') }}">
    <b>bookpay</b>
    <span>BY INKJIN</span>
  </a>

  <div class="card">
    <h1>Accept invitation</h1>
    <p class="sub"><strong>{{ $artistName }}</strong> asked to join <strong>{{ $studioName }}</strong> on Bookpay.</p>

    <div class="box">
      @if($relationshipLabel)
        <div class="kv"><span>Relationship</span><b>{{ $relationshipLabel }}</b></div>
      @endif
      @if($artistPercent !== null)
        <div class="kv"><span>Proposed split</span><b>You {{ 100 - (int) $artistPercent }}% · Artist {{ (int) $artistPercent }}%</b></div>
      @else
        <div class="kv"><span>Payout</span><b>Artist paid directly</b></div>
      @endif
    </div>

    <div class="two">
      <form method="post" action="{{ $declineUrl }}" style="flex:1">
        @csrf
        <button type="submit" class="btn ghost">Decline</button>
      </form>
      <form method="post" action="{{ $acceptUrl }}" style="flex:1">
        @csrf
        <button type="submit" class="btn">Accept invitation</button>
      </form>
    </div>
  </div>

  <footer>© {{ date('Y') }} Bookpay by Inkjin</footer>
</body>
</html>
