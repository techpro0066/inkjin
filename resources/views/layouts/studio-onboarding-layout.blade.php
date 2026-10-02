@php
  $onboardingStep = $onboardingStep ?? 'profile';
  $onboardingStepNumber = $onboardingStepNumber ?? 1;
  $onboardingStepTotal = $onboardingStepTotal ?? 5;
  $progressPct = max(0, min(100, (int) round(($onboardingStepNumber / $onboardingStepTotal) * 100)));
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
  @include('layouts.partials.google-analytics')
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="color-scheme" content="only light">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'Studio onboarding') — Bookpay</title>
  <link rel="icon" href="{{ asset('design/images/icons/favicon.png') }}">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0..1,0&display=block" rel="stylesheet">
  <style>
    :root{--bg:#FFF6FF;--card:#fff;--line:#E8DFEA;--tint:#FAF0FB;--tintline:#F1E7F3;--ink:#1A1A1A;--muted:#6F6874;--faint:#9A929E;--pri:#3E007C;--pril:#F3E8FF;--chip:#F0EDF2;--green:#00A650;--red:#C62828}
    *{box-sizing:border-box;margin:0;padding:0}
    html{height:100%}
    body{font-family:'Plus Jakarta Sans',system-ui,sans-serif;background:var(--bg);color:var(--ink);font-size:14px;display:flex;height:100vh;height:100dvh;overflow:hidden;width:100%;max-width:100%}
    .ms{font-family:'Material Symbols Outlined';font-weight:normal;font-style:normal;font-size:20px;line-height:1;letter-spacing:normal;text-transform:none;display:inline-block;white-space:nowrap;direction:ltr;-webkit-font-smoothing:antialiased;font-variation-settings:'FILL' 0,'wght' 400,'GRAD' 0,'opsz' 24;vertical-align:middle}
    aside{width:232px;background:#1A1A1A;color:#DEDEDE;padding:22px 14px 18px;display:flex;flex-direction:column;flex-shrink:0;height:100%;min-height:0;overflow-x:hidden;overflow-y:auto;overscroll-behavior:contain}
    .logo{font-family:'Space Grotesk',system-ui,sans-serif;font-size:25px;font-weight:700;letter-spacing:-.055em;color:#fff;text-decoration:none;padding-left:6px;display:block}
    .tag{font-size:8.5px;letter-spacing:1.4px;color:#8C8C8C;padding-left:6px;margin-top:2px;line-height:1.35}
    .topline{display:flex;align-items:center;justify-content:space-between;gap:8px;flex-shrink:0}
    .burger{display:none;border:0;background:none;color:#fff;cursor:pointer;padding:6px;border-radius:8px}
    .burger .ms{font-size:26px}
    aside nav{margin-top:26px;display:flex;flex-direction:column;gap:3px;flex:1;min-height:0}
    aside nav a{display:flex;align-items:center;gap:12px;padding:9px 12px;border-radius:12px;font-weight:500;font-size:14.5px;color:#DEDEDE;text-decoration:none}
    aside nav a .ms{font-size:21px;color:#DEDEDE}
    aside nav a .ms.done{color:#3DD68C;font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24}
    aside nav a.on{background:#fff;color:var(--pri);font-weight:600}
    aside nav a.on .ms{color:var(--pri)}
    aside nav a.on .ms.done{color:#3DD68C}
    aside nav button.nav-logout{display:flex;align-items:center;gap:12px;width:100%;padding:9px 12px;border:0;border-radius:12px;background:none;color:#DEDEDE;font:inherit;font-weight:500;font-size:14.5px;cursor:pointer;text-align:left}
    aside nav button.nav-logout .ms{font-size:21px;color:#DEDEDE}
    .sep{height:1px;background:#333;margin:10px 4px}
    .foot{margin-top:auto;flex-shrink:0}
    main{flex:1;min-width:0;min-height:0;height:100%;overflow-x:hidden;overflow-y:auto;overscroll-behavior:contain;padding:36px 48px 56px;width:100%}
    .wrap{max-width:920px;width:100%;min-width:0}
    form{width:100%;max-width:100%;min-width:0}
    h1{font-size:30px;font-weight:800;letter-spacing:-.6px;margin:0}
    .sub{color:var(--muted);font-size:14.5px;margin-top:4px;line-height:1.45}
    .help-q{display:inline-flex;align-items:center;justify-content:center;width:26px;height:26px;margin-left:8px;border-radius:50%;color:#9A929E;vertical-align:middle;text-decoration:none;position:relative;top:-2px}
    .help-q .ms{font-size:22px}.help-q:hover{color:#3E007C;background:#F3E8FF}
    .btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;background:var(--ink);color:#fff;border:0;border-radius:10px;padding:10px 16px;font:inherit;font-weight:600;font-size:13.5px;text-decoration:none;cursor:pointer;white-space:nowrap}
    .btn .ms{font-size:18px}
    .btn.ghost{background:#fff;color:var(--ink);border:1px solid var(--line)}
    .btn.sm{padding:6px 11px;font-size:12.5px;border-radius:8px}
    .btn.pri{background:var(--pri)}
    .card{background:var(--card);border:1px solid var(--line);border-radius:16px;overflow:hidden;width:100%;max-width:100%;min-width:0}
    .card-pad{padding:18px 22px;min-width:0;max-width:100%}
    .ch{display:flex;justify-content:space-between;align-items:center;padding:18px 22px;border-bottom:1px solid var(--line);gap:12px;min-width:0}
    .ch h3{margin:0;font-size:16px;font-weight:700}
    .faint{color:var(--faint)}.muted{color:var(--muted)}
    .row{display:flex;align-items:center;gap:12px;min-width:0}
    .fl{display:block;font-size:13px;font-weight:700;margin:0 0 6px}
    .help{font-size:12px;color:var(--faint);margin-top:6px;line-height:1.45;overflow-wrap:anywhere;word-break:break-word}
    .formgrid{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:14px;width:100%;min-width:0}
    .formgrid>div{min-width:0;max-width:100%}
    .formgrid .full{grid-column:1 / -1}
    .in{width:100%;max-width:100%;border:1px solid var(--line);border-radius:10px;background:#fff;font:inherit;font-size:13.5px;padding:10px 13px;color:var(--ink);outline:0;min-height:40px}
    input.in{min-width:0}
    input.in:focus,.in:focus-within{border-color:#3E007C;box-shadow:0 0 0 3px #F3E8FF}
    .obfoot{border-top:1px solid var(--line);padding:14px 0;display:flex;justify-content:space-between;align-items:center;gap:10px;margin-top:28px;min-width:0}
    .obfoot>.row{display:flex;align-items:center;gap:0;margin-left:auto}
    .obfoot .btn{white-space:nowrap;flex:none}
    .ic{width:38px;height:38px;border-radius:10px;background:var(--chip);display:flex;align-items:center;justify-content:center;flex-shrink:0;overflow:hidden;position:relative}
    .ic img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover}
    .avatar{width:36px;height:36px;border-radius:50%;background:#EDE6F0;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:#1A1A1A;flex-shrink:0;overflow:hidden;position:relative}
    .avatar img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover}
    .opt{display:flex;gap:12px;align-items:flex-start;border:1px solid var(--line);border-radius:14px;padding:14px 16px;cursor:pointer;background:#fff;min-width:0}
    .opt.on{border:2px solid #3E007C;padding:13px 15px}
    .opt .rad{color:#C8BFCC;flex:none}.opt.on .rad{color:#3E007C;font-variation-settings:'FILL' 1}
    .opt b{display:block;font-size:14px}.opt small{display:block;color:var(--muted);font-size:12.5px;margin-top:3px;line-height:1.45}
    .note{display:flex;gap:10px;align-items:flex-start;border:1.5px dashed #B99BE0;background:#FBF5FF;border-radius:12px;padding:10px 14px;font-size:12.5px;color:#5B2A94;line-height:1.45;min-width:0}
    .note .ms{font-size:18px;flex:none;margin-top:1px}
    .note[hidden],[data-when][hidden]{display:none!important}
    .grid{display:grid;gap:16px;min-width:0;max-width:100%}
    .addr-in{display:flex;align-items:center;gap:8px;padding:0 13px;min-height:40px;width:100%;max-width:100%;min-width:0}
    .addr-in .ms{color:var(--faint);flex:0 0 auto}
    .addr-in input{border:0;outline:0;font:inherit;flex:1 1 auto;background:none;min-width:0;width:100%;padding:10px 0;font-size:13.5px;color:var(--ink)}
    .mlbox{width:100%;max-width:100%;min-width:0;display:grid;grid-template-columns:auto minmax(0,1fr) auto;align-items:center;column-gap:8px;border:1px solid var(--line,#E7E1EA);background:#FAF8FB;border-radius:10px;padding:8px 8px 8px 12px}
    .mlbox>.ms{font-size:18px;color:#3E007C}
    .mlbox a.mlk{min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#3E007C;font-weight:600;font-size:13px;text-decoration:none}
    .mlbox a.mlk:hover{text-decoration:underline}
    .mlbox a.mlk.is-empty{color:var(--faint);font-weight:500;pointer-events:none}
    .mlcopy{border:1px solid #D9D2DC;background:#fff;border-radius:8px;padding:5px 10px;font:inherit;font-size:12.5px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:4px;color:var(--ink);white-space:nowrap}
    .mlcopy .ms{font-size:16px;color:inherit;line-height:1}
    .bpw-toast{position:fixed;top:22px;right:24px;background:#1A1A1A;color:#fff;padding:12px 18px;border-radius:10px;font-weight:600;font-size:14px;z-index:2000;box-shadow:0 10px 30px rgba(0,0,0,.22);display:flex;gap:10px;align-items:center;font-family:inherit;max-width:calc(100% - 32px)}
    .bpw-toast.bad{background:#B3261E}
    @media(max-width:600px){.bpw-toast{left:16px;right:16px;top:74px}}
    @media (max-width:700px){
      .formgrid{grid-template-columns:minmax(0,1fr)}
      .grid[style*="1fr 1fr"]{grid-template-columns:minmax(0,1fr)!important}
      .ch,.card-pad{padding-left:16px;padding-right:16px}
    }
    @media (max-width:900px){
      html{height:auto;overflow-x:clip;overflow-y:auto}
      body{flex-direction:column;height:auto;min-height:100vh;min-height:100dvh;overflow-x:clip;overflow-y:visible;-webkit-overflow-scrolling:touch}
      aside{width:100%;max-width:100%;height:auto;min-height:0;padding:12px 16px;position:sticky;top:0;z-index:20;overflow:visible}
      aside .tag{display:none}
      .burger{display:inline-flex}
      aside nav,.foot{display:none}
      body.navopen aside nav{display:flex}
      body.navopen aside .foot{display:block;margin-top:8px}
      body.navopen aside{max-height:100vh;max-height:100dvh;overflow-y:auto}
      aside nav{margin-top:12px;flex:none}
      main{flex:none;height:auto;min-height:0;overflow:visible;padding:22px 16px 48px;max-width:100%}
      h1{font-size:25px}
      .wrap{max-width:100%}
      .obfoot{margin-top:22px;padding-top:16px}
    }
    @media (max-width:420px){
      .mlbox{grid-template-columns:auto minmax(0,1fr);row-gap:8px}
      .mlcopy{grid-column:1 / -1;justify-self:end}
    }
  </style>
  @stack('styles')
</head>
<body>
  <aside>
    <div class="topline">
      <a href="{{ route('studio.dashboard') }}" class="logo">bookpay</a>
      <button class="burger" type="button" aria-label="Open steps" onclick="document.body.classList.toggle('navopen')"><span class="ms">menu</span></button>
    </div>
    <div class="tag">STUDIO ONBOARDING</div>
    <nav>
      <a href="{{ route('studio.onboarding.profile') }}" class="{{ $onboardingStep === 'profile' ? 'on' : '' }}">
        @if ($onboardingStepNumber > 1)
          <span class="ms done">check_circle</span>
        @else
          <span class="ms">storefront</span>
        @endif
        Studio profile
      </a>
      <a href="{{ route('studio.onboarding.owner') }}" class="{{ $onboardingStep === 'owner' ? 'on' : '' }}">
        @if ($onboardingStepNumber > 2)
          <span class="ms done">check_circle</span>
        @else
          <span class="ms">person</span>
        @endif
        Your details
      </a>
      <a href="{{ route('studio.onboarding.location') }}" class="{{ $onboardingStep === 'location' ? 'on' : '' }}">
        @if ($onboardingStepNumber > 3)
          <span class="ms done">check_circle</span>
        @else
          <span class="ms">location_on</span>
        @endif
        Location &amp; type
      </a>
      <a href="{{ route('studio.onboarding.terms') }}" class="{{ $onboardingStep === 'terms' ? 'on' : '' }}">
        @if ($onboardingStepNumber > 4)
          <span class="ms done">check_circle</span>
        @else
          <span class="ms">handshake</span>
        @endif
        How you work with artists
      </a>
      <a href="{{ route('studio.onboarding.payouts') }}" class="{{ $onboardingStep === 'payouts' ? 'on' : '' }}">
        @if ($onboardingStepNumber > 5)
          <span class="ms done">check_circle</span>
        @else
          <span class="ms">payments</span>
        @endif
        Payouts
      </a>
      <div class="sep"></div>
      <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="nav-logout"><span class="ms">logout</span>Log Out</button>
      </form>
    </nav>
    <div class="foot">
      <div style="background:#262626;border-radius:12px;padding:12px 14px">
        <div class="row" style="justify-content:space-between">
          <b style="font-size:12.5px;color:#fff">Setup progress</b>
          <span style="font-size:12px;color:#aaa">Step {{ $onboardingStepNumber }} of {{ $onboardingStepTotal }}</span>
        </div>
        <div style="height:6px;border-radius:4px;background:#3a3a3a;margin-top:8px">
          <div style="width:{{ $progressPct }}%;height:6px;border-radius:4px;background:#fff"></div>
        </div>
      </div>
    </div>
  </aside>

  <main>
    <div class="wrap">
      @yield('content')
    </div>
  </main>

  <script>
    window.bpToast = function (message, isError) {
      document.querySelectorAll('.bpw-toast').forEach(function (n) { n.remove(); });
      var t = document.createElement('div');
      t.className = 'bpw-toast' + (isError ? ' bad' : '');
      t.setAttribute('role', 'status');
      t.innerHTML = '<span class="ms" style="color:' + (isError ? '#fff' : '#3DD68C') + '">' + (isError ? 'error' : 'check_circle') + '</span>' + (message || '');
      document.body.appendChild(t);
      setTimeout(function () { t.remove(); }, 2600);
    };
  </script>
  @stack('scripts')
</body>
</html>
