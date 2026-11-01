<!DOCTYPE html>
<html lang="en">
<head>
  @include('layouts.partials.google-analytics')
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Studio invite — Bookpay</title>
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
    .err{background:#FDECEC;color:#C62828;border-radius:10px;padding:10px 12px;font-size:13.5px;margin-bottom:14px}
    footer{margin-top:auto;padding-top:40px;text-align:center;font-size:13px;color:#6F6874}
  </style>
</head>
<body>
  <a class="logo" href="{{ url('/') }}">
    <b>bookpay</b>
    <span>BY INKJIN</span>
  </a>

  <div class="card">
    <h1>Join {{ $studioName }}?</h1>
    <p class="sub"><strong>{{ $studioName }}</strong> invited you to work with them on Bookpay.</p>

    @if ($errors->any())
      <div class="err">{{ $errors->first() }}</div>
    @endif

    @if (!empty($wrongAccount))
      <div class="err">Sign in with <b>{{ $invite->email }}</b> to accept this invite.</div>
    @endif

    <div class="box">
      @if($relationshipLabel)
        <div class="kv"><span>Relationship</span><b>{{ $relationshipLabel }}</b></div>
      @endif
      @if($artistPercent !== null)
        <div class="kv"><span>Offered split</span><b>Artist {{ (int) $artistPercent }}% · Studio {{ 100 - (int) $artistPercent }}%</b></div>
      @else
        <div class="kv"><span>Payout</span><b>Workstation rent (no split)</b></div>
      @endif
    </div>

    @if (!empty($needsAuth))
      <a class="btn" href="{{ route('login') }}">Log in to accept</a>
      <a class="btn ghost" href="{{ route('register', ['invite' => $invite->token, 'as' => 'artist']) }}">Create Bookpay account</a>
      <form method="post" action="{{ route('studio.artist-invite.decline', ['token' => $invite->token]) }}">
        @csrf
        <button type="submit" class="btn ghost" style="color:#C62828;margin-top:10px">Decline</button>
      </form>
    @elseif (!empty($wrongAccount))
      <a class="btn" href="{{ route('logout') }}" onclick="event.preventDefault();document.getElementById('logout-form').submit();">Switch account</a>
      <form id="logout-form" method="post" action="{{ route('logout') }}" style="display:none">@csrf</form>
    @else
      <div class="two">
        <form method="post" action="{{ route('studio.artist-invite.decline', ['token' => $invite->token]) }}" style="flex:1">
          @csrf
          <button type="submit" class="btn ghost">Decline</button>
        </form>
        <form method="post" action="{{ route('studio.artist-invite.accept', ['token' => $invite->token]) }}" style="flex:1">
          @csrf
          <button type="submit" class="btn">Accept invite</button>
        </form>
      </div>
    @endif
  </div>

  <footer>Bookpay by Inkjin</footer>
</body>
</html>
