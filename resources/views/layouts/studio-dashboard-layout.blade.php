@php
  $authUser = auth()->user();
  $studioName = $studioName ?? ($authUser?->userDetail?->resolvedStudioName() ?: 'Studio');
  $studioEmail = $studioEmail ?? ($authUser?->email ?? '');
  $studioInitials = $studioInitials ?? strtoupper(substr(preg_replace('/\s+/', '', (string) $studioName) ?: 'ST', 0, 2));
  $activeNav = $activeNav ?? 'home';
  $profileComplete = $profileComplete ?? ((string) ($authUser?->on_boarding ?? '') === 'yes');

  if (! isset($ownerAvatarUrl)) {
    $ownerAvatarUrl = null;
    $sidebarStudio = $studio ?? null;
    if (! $sidebarStudio && $authUser) {
      $sidebarStudio = \App\Models\Studio::query()
        ->when($authUser->id, fn ($q) => $q->where('user_id', $authUser->id))
        ->when($authUser->email, function ($q) use ($authUser) {
          $q->orWhereRaw('LOWER(email) = ?', [strtolower(trim((string) $authUser->email))]);
        })
        ->first();
    }
    $imagePath = trim((string) ($sidebarStudio?->image_url ?? ''));
    if ($imagePath !== '') {
      $ownerAvatarUrl = asset(ltrim($imagePath, '/'));
    }
  }
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
  @include('layouts.partials.google-analytics')
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="color-scheme" content="only light">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'Studio dashboard') — Bookpay</title>
  <link rel="icon" href="{{ asset('design/images/icons/favicon.png') }}">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&display=block" rel="stylesheet">
  <style>
    :root{--pri:#3E007C;--ink:#1A1A1A;--line:#E8DFEA;--muted:#6F6874;--faint:#9A929E;--bg:#FFF6FF;--chip:#F0EDF2}
    *{box-sizing:border-box}
    html{height:100%}
    body{margin:0;height:100vh;height:100dvh;overflow:hidden;background:var(--bg);color:var(--ink);font-family:'Plus Jakarta Sans',system-ui,sans-serif;font-size:14px;display:flex}
    .ms{font-family:'Material Symbols Outlined';font-weight:normal;font-style:normal;font-size:20px;line-height:1;display:inline-block;-webkit-font-smoothing:antialiased;font-variation-settings:'FILL' 0,'wght' 400,'GRAD' 0,'opsz' 24;vertical-align:middle}
    aside{width:232px;background:#1A1A1A;color:#DEDEDE;padding:22px 14px 18px;display:flex;flex-direction:column;flex-shrink:0;height:100%;min-height:0;overflow-x:hidden;overflow-y:auto;overscroll-behavior:contain}
    .logo{font-family:'Space Grotesk',system-ui,sans-serif;font-size:25px;font-weight:700;letter-spacing:-.055em;color:#fff;text-decoration:none;padding-left:6px;display:block}
    .tag{font-size:8.5px;letter-spacing:1.4px;margin-top:2px;line-height:1.35;color:#8C8C8C;padding-left:6px}
    .topline{display:flex;align-items:center;justify-content:space-between;gap:8px;flex-shrink:0}
    .burger{display:none;border:0;background:none;color:#fff;cursor:pointer;padding:6px;border-radius:8px}
    .burger .ms{font-size:26px}
    aside nav{margin-top:26px;display:flex;flex-direction:column;gap:3px;flex:1;min-height:0}
    aside nav a{display:flex;align-items:center;gap:12px;padding:9px 12px;border-radius:12px;font-weight:500;font-size:14.5px;color:#DEDEDE;text-decoration:none}
    aside nav a .ms{font-size:21px;color:#DEDEDE}
    aside nav a.on{background:#fff;color:var(--pri);font-weight:600}
    aside nav a.on .ms{color:var(--pri)}
    aside nav a .cnt{margin-left:auto;background:#3A3A3A;color:#fff;border-radius:10px;font-size:11px;padding:1px 8px;font-weight:600}
    aside nav a.on .cnt{background:var(--pri)}
    aside nav a .lk{margin-left:auto;font-size:16px!important;opacity:.7}
    aside nav a[data-locked]{opacity:.8}
    .sep{height:1px;background:#333;margin:10px 4px}
    .foot{margin-top:auto;flex-shrink:0}
    .foot .user{display:flex;gap:10px;align-items:center;padding:14px 8px 0;border-top:1px solid #333;margin-top:10px;text-decoration:none;color:inherit}
    .foot .user .av{width:36px;height:36px;border-radius:50%;background:var(--pri);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;flex:none;overflow:hidden;position:relative}
    .foot .user .av img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;display:block;z-index:2}
    .foot .user .av .ini{position:relative;z-index:1}
    .foot .user .av.has-img .ini{display:none}
    .foot .user b{display:block;font-size:13px;color:#fff}.foot .user span{display:block;font-size:11.5px;color:#9a9a9a;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:140px}
    main{flex:1;min-width:0;min-height:0;height:100%;overflow-x:hidden;overflow-y:auto;overscroll-behavior:contain;padding:36px 48px 56px}
    .wrap{max-width:1112px}
    h1{font-size:30px;font-weight:800;letter-spacing:-.6px;margin:0}
    .sub{color:var(--muted);font-size:14.5px;margin-top:4px;line-height:1.45}
    .head{display:flex;justify-content:space-between;align-items:flex-start;gap:24px;flex-wrap:wrap}
    .help-q{display:inline-flex;align-items:center;justify-content:center;width:26px;height:26px;margin-left:8px;border-radius:50%;color:#9A929E;vertical-align:middle;text-decoration:none;position:relative;top:-2px}
    .help-q .ms{font-size:22px}.help-q:hover{color:#3E007C;background:#F3E8FF}
    .btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;background:var(--ink);color:#fff;border:0;border-radius:10px;padding:10px 16px;font:inherit;font-weight:600;font-size:13.5px;text-decoration:none;cursor:pointer;white-space:nowrap}
    .btn .ms{font-size:18px}
    .btn.ghost{background:#fff;color:var(--ink);border:1px solid var(--line)}
    .btn.pri{background:var(--pri)}
    .btn.sm{padding:6px 11px;font-size:12.5px;border-radius:8px}
    .card{background:#fff;border:1px solid var(--line);border-radius:16px;overflow:hidden}
    .ch{display:flex;justify-content:space-between;align-items:center;padding:18px 22px;border-bottom:1px solid var(--line)}
    .ch h3{margin:0;font-size:16px;font-weight:700}
    .faint{color:var(--faint)}.muted{color:var(--muted)}
    .row{display:flex;align-items:center;gap:12px}
    .grid{display:grid}
    .avatar{width:36px;height:36px;border-radius:50%;background:#EDE6F0 center/cover;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:#1A1A1A;flex-shrink:0}
    table{width:100%;border-collapse:collapse}
    th{text-align:left;font-size:11px;letter-spacing:.8px;text-transform:uppercase;color:var(--muted);font-weight:700;padding:11px 16px;background:#FBF8FC;border-bottom:1px solid var(--line)}
    td{padding:14px 16px;border-bottom:1px solid #F3EEF4;font-size:13.5px;vertical-align:middle}
    tr:last-child td{border-bottom:0}
    .pill{display:inline-flex;align-items:center;border-radius:20px;padding:3px 9px;font-size:11.5px;font-weight:600}
    .pill.a{background:#FFF3DE;color:#B86E00}.pill.p{background:#F3E8FF;color:#7A1FC4}.pill.g{background:#E6F8EE;color:#00A650}.pill.k{background:var(--chip);color:var(--muted)}
    .tabs{display:flex;gap:28px;border-bottom:1px solid var(--line);margin:26px 0 24px;overflow-x:auto;scrollbar-width:none}
    .tabs::-webkit-scrollbar{display:none}
    .tabs a{padding:0 2px 12px;font-weight:600;color:var(--muted);font-size:14.5px;text-decoration:none;white-space:nowrap;flex-shrink:0}
    .tabs a.on{color:var(--ink);box-shadow:inset 0 -2.5px 0 var(--ink)}
    .fl{display:block;font-size:13px;font-weight:700;margin:0 0 6px}
    .help{font-size:12px;color:var(--faint);margin-top:6px;line-height:1.45}
    .formgrid{display:grid;grid-template-columns:1fr 1fr;gap:14px}
    .formgrid .full{grid-column:1 / -1}
    .in{width:100%;border:1px solid var(--line);border-radius:10px;background:#fff;font:inherit;font-size:13.5px;padding:10px 13px;color:var(--ink);outline:0;min-height:40px}
    input.in:focus,.in:focus-within{border-color:#3E007C;box-shadow:0 0 0 3px #F3E8FF}
    div.in{display:flex;align-items:center;gap:8px}
    div.in input{flex:1;border:0;outline:0;background:none;font:inherit;font-size:13.5px;padding:0;min-width:0;color:inherit}
    .pgsaverow{margin-top:18px}
    .pgsave{min-width:160px}
    .note{display:flex;gap:10px;align-items:flex-start;background:#F7F3F9;border:1px solid var(--line);border-radius:12px;padding:12px 14px;font-size:13.5px;line-height:1.45;color:#3F3A45}
    .note .ms{color:var(--pri);flex:none;margin-top:1px}
    .stype-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:4px}
    .opt{display:flex;gap:10px;align-items:flex-start;text-align:left;border:1.5px solid #E8DFEA;border-radius:12px;padding:12px 13px;background:#fff;cursor:pointer;font:inherit;color:inherit}
    .opt .rad{color:#9A929E;font-size:20px;flex:none;margin-top:1px}
    .opt b{display:block;font-size:14px}.opt small{display:block;color:var(--muted);font-size:12.5px;line-height:1.4;margin-top:2px}
    .opt.on{border-color:#3E007C;background:#FBF7FE}.opt.on .rad{color:#3E007C}
    .mlbox{width:100%;max-width:100%;min-width:0;display:grid;grid-template-columns:auto minmax(0,1fr) auto;align-items:center;column-gap:8px;border:1px solid var(--line,#E7E1EA);background:#FAF8FB;border-radius:10px;padding:8px 8px 8px 12px;box-sizing:border-box}
    .mlbox>.ms{font-size:18px;color:#3E007C}
    .mlk{min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:13px;color:#3E007C;font-weight:600;text-decoration:none}
    .mlbox a.mlk:hover{text-decoration:underline}
    .mlbox a.mlk.is-empty{color:var(--faint);font-weight:500;pointer-events:none}
    .mlcopy{border:1px solid #D9D2DC;background:#fff;border-radius:8px;padding:5px 10px;font:inherit;font-size:12.5px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:4px;color:var(--ink);white-space:nowrap}
    .mlcopy .ms{font-size:16px;color:inherit;line-height:1}
    @media (max-width:700px){.formgrid,.stype-grid{grid-template-columns:minmax(0,1fr)}}
    @media (max-width:420px){
      .mlbox{grid-template-columns:auto minmax(0,1fr);row-gap:8px}
      .mlcopy{grid-column:1 / -1;justify-self:end}
    }
    .lp{position:fixed;inset:0;background:rgba(20,10,30,.5);display:flex;align-items:center;justify-content:center;padding:16px;z-index:200}
    .lp[hidden]{display:none}
    .lp .lb{background:#fff;border-radius:18px;max-width:460px;width:100%;padding:26px 26px 22px;max-height:92vh;overflow:auto;position:relative;box-shadow:0 20px 60px rgba(0,0,0,.25)}
    .lp h2{font-size:21px;font-weight:800;margin:0 0 4px;letter-spacing:-.3px}
    .lp .ls{color:#6F6874;font-size:14px}
    .lp .lf{margin-top:10px;font-size:13px;color:#1F6B4E;background:#E6F8EE;border-radius:10px;padding:10px 12px;line-height:1.45;font-weight:600}
    .lp .lt{margin:16px 0 8px;font-size:13px;font-weight:700;color:#3F3A43}
    .lp ul{list-style:none;padding:0;margin:0}
    .lp li{display:flex;gap:10px;align-items:flex-start;padding:7px 0;font-size:13.5px;line-height:1.4}
    .lp li .ms{color:#00A650;font-variation-settings:'FILL' 1;font-size:20px;flex:none;margin-top:1px}
    .lp li b{font-weight:700}
    .lp .lm{margin-top:14px;font-size:12.5px;color:#6F6874}
    .lp .lbt{display:flex;gap:10px;margin-top:18px;flex-wrap:wrap}
    .lp .lbt .btn{flex:1;justify-content:center;margin:0}
    .lp .lr[hidden]{display:none}
    .lp .lr{background:#F3E8FF;color:#3E007C;border-radius:10px;padding:8px 12px;font-size:13px;margin-bottom:14px;display:flex;gap:8px;align-items:center}
    .lp .lx{position:absolute;top:14px;right:14px;width:34px;height:34px;border-radius:50%;border:0;background:none;cursor:pointer;color:#6F6874;display:flex;align-items:center;justify-content:center}
    .lp .lx:hover{background:#F4F0F6}
    .bpw-toast{position:fixed;top:22px;right:24px;background:#1A1A1A;color:#fff;padding:12px 18px;border-radius:10px;font-weight:600;font-size:14px;z-index:2000;box-shadow:0 10px 30px rgba(0,0,0,.22);display:flex;gap:10px;align-items:center;font-family:inherit;max-width:calc(100% - 32px)}
    .bpw-toast.bad{background:#B3261E}
    @media(max-width:600px){.bpw-toast{left:16px;right:16px;top:74px}}
    @media (max-width:600px){
      .lp{align-items:flex-end;padding:0}
      .lp .lb{border-radius:18px 18px 0 0;max-height:92vh}
      .lp .lbt .btn{flex:1 1 100%}
    }
    @media (max-width:900px){
      body{flex-direction:column;height:auto;min-height:100vh;min-height:100dvh;overflow:auto}
      aside{width:100%;height:auto;min-height:0;padding:12px 16px;position:sticky;top:0;z-index:20;overflow:visible}
      aside .tag{display:none}
      .burger{display:inline-flex}
      aside nav,.foot{display:none}
      body.navopen aside nav{display:flex}
      body.navopen aside .foot{display:block;margin-top:8px}
      body.navopen aside{max-height:100vh;max-height:100dvh;overflow-y:auto}
      aside nav{margin-top:12px;flex:none}
      main{height:auto;overflow:visible;padding:22px 16px 48px}
      h1{font-size:25px}
      .grid[style*="repeat(3"]{grid-template-columns:1fr!important}
      .grid[style*="1fr 1.5fr"]{grid-template-columns:1fr!important}
      table{display:block;overflow-x:auto}
    }
  </style>
  @stack('styles')
</head>
<body>
  <aside>
    <div class="topline">
      <a href="{{ route('studio.dashboard') }}" class="logo">bookpay</a>
      <button class="burger" type="button" aria-label="Open menu" onclick="document.body.classList.toggle('navopen')"><span class="ms">menu</span></button>
    </div>
    <div class="tag">STUDIO DASHBOARD<br>BY INKJIN</div>
    <nav>
      <a href="{{ route('studio.dashboard') }}" class="{{ $activeNav === 'home' ? 'on' : '' }}"><span class="ms">space_dashboard</span>Home</a>
      @if ($profileComplete)
        <a href="#"><span class="ms">groups</span>Artists</a>
        <a href="#"><span class="ms">calendar_month</span>Calendar</a>
        <a href="#"><span class="ms">event_note</span>Bookings</a>
        <a href="#"><span class="ms">payments</span>Money</a>
        <a href="#"><span class="ms">storefront</span>Studio Page</a>
        <a href="#"><span class="ms">edit_note</span>Client Flow</a>
      @else
        <a href="#" data-locked="Artists"><span class="ms">groups</span>Artists<span class="ms lk">lock</span></a>
        <a href="#" data-locked="Calendar"><span class="ms">calendar_month</span>Calendar<span class="ms lk">lock</span></a>
        <a href="#" data-locked="Bookings"><span class="ms">event_note</span>Bookings<span class="ms lk">lock</span></a>
        <a href="#" data-locked="Money"><span class="ms">payments</span>Money<span class="ms lk">lock</span></a>
        <a href="#" data-locked="Studio Page"><span class="ms">storefront</span>Studio Page<span class="ms lk">lock</span></a>
        <a href="#" data-locked="Client Flow"><span class="ms">edit_note</span>Client Flow<span class="ms lk">lock</span></a>
      @endif
      <a href="{{ route('studio.account.profile') }}" class="{{ $activeNav === 'account' ? 'on' : '' }}"><span class="ms">settings</span>Account</a>
      <div class="sep"></div>
      <a href="https://help.inkjin.com" target="_blank" rel="noopener"><span class="ms">help</span>Get Help</a>
    </nav>
    <div class="foot">
      <nav style="margin:0">
        <form method="POST" action="{{ route('logout') }}">
          @csrf
          <button type="submit" style="display:flex;align-items:center;gap:12px;width:100%;padding:10px 12px;border:0;border-radius:12px;background:none;color:#a8a8a8;font:inherit;font-weight:500;cursor:pointer">
            <span class="ms" style="color:#a8a8a8">logout</span>Log Out
          </button>
        </form>
      </nav>
      <a href="{{ route('studio.account.profile') }}" class="user" title="Account">
        <div class="av{{ !empty($ownerAvatarUrl) ? ' has-img' : '' }}" style="background:#3E007C">
          @if (!empty($ownerAvatarUrl))
            <img src="{{ $ownerAvatarUrl }}" alt="">
          @endif
          <span class="ini">{{ $studioInitials }}</span>
        </div>
        <div>
          <b>{{ $studioName }}</b>
          <span>{{ $studioEmail }}</span>
        </div>
      </a>
    </div>
  </aside>

  <main>
    <div class="wrap">
      @yield('content')
    </div>
  </main>

  <div class="lp" id="litePrompt" hidden role="dialog" aria-modal="true" aria-labelledby="litePromptTitle">
    <div class="lb">
      <button class="lx" type="button" aria-label="Close" data-lpx><span class="ms">close</span></button>
      <div class="lr" id="litePromptLock" hidden>
        <span class="ms" style="font-size:18px">lock</span>
        <span id="litePromptLockText"></span>
      </div>
      <h2 id="litePromptTitle">Complete your studio profile</h2>
      <div class="ls">Get full access to your studio dashboard.</div>
      <div class="lf">Free for studios. No monthly fee, no setup fee, no commission.</div>
      <div class="lt">With a complete profile, you can:</div>
      <ul>
        <li><span class="ms">check_circle</span><span><b>Get your own studio page</b></span></li>
        <li><span class="ms">check_circle</span><span><b>Show all your artists' work on one page</b></span></li>
        <li><span class="ms">check_circle</span><span><b>Take bookings as a studio:</b> clients book with you, and you pick the artist</span></li>
        <li><span class="ms">check_circle</span><span><b>Manage the relationships with all your artists:</b> invite them, accept join requests, set splits or rent</span></li>
        <li><span class="ms">check_circle</span><span><b>See all your artists' bookings</b> in one calendar</span></li>
        <li><span class="ms">check_circle</span><span><b>Use your studio's consent form</b> for every booking at your studio</span></li>
      </ul>
      <div class="lm">Takes about 5 minutes.</div>
      <div class="lbt">
        <button type="button" class="btn" id="litePromptComplete">Complete profile</button>
        <button type="button" class="btn ghost" data-lpx>Do this later</button>
      </div>
    </div>
  </div>

  <script>
  (function () {
    function toast(msg, bad) {
      var t = document.createElement('div');
      t.className = 'bpw-toast' + (bad ? ' bad' : '');
      t.setAttribute('role', 'status');
      t.innerHTML = (bad ? '' : '<span class="ms" style="color:#3DD68C">check_circle</span>') + String(msg || '');
      document.body.appendChild(t);
      setTimeout(function () { t.remove(); }, 2600);
    }
    window.bpToast = toast;
    try {
      var queued = sessionStorage.getItem('bp-toast');
      if (queued) {
        sessionStorage.removeItem('bp-toast');
        setTimeout(function () { toast(queued); }, 150);
      }
    } catch (e) {}
  })();
  </script>
  <script>
  (function () {
    const prompt = document.getElementById('litePrompt');
    const lock = document.getElementById('litePromptLock');
    const lockText = document.getElementById('litePromptLockText');

    function openLitePrompt(name) {
      if (!prompt) return;
      if (name && lock && lockText) {
        lockText.innerHTML = '<b>' + String(name).replace(/[&<>"]/g, (c) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c])) + '</b>&nbsp;is part of the full dashboard.';
        lock.hidden = false;
      } else if (lock) {
        lock.hidden = true;
      }
      prompt.hidden = false;
    }

    function closeLitePrompt() {
      if (prompt) prompt.hidden = true;
    }

    window.bpLitePrompt = openLitePrompt;

    prompt?.addEventListener('click', function (e) {
      if (e.target === prompt || e.target.closest('[data-lpx]')) {
        e.preventDefault();
        closeLitePrompt();
      }
    });

    document.getElementById('litePromptComplete')?.addEventListener('click', function () {
      closeLitePrompt();
      window.location.href = @json(route('studio.onboarding.profile'));
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && prompt && !prompt.hidden) closeLitePrompt();
    });

    document.addEventListener('click', function (e) {
      const a = e.target.closest('[data-locked]');
      if (!a) return;
      e.preventDefault();
      e.stopPropagation();
      openLitePrompt(a.getAttribute('data-locked'));
    }, true);

    document.addEventListener('click', function (e) {
      const a = e.target.closest('[data-open-profile-prompt]');
      if (!a) return;
      e.preventDefault();
      openLitePrompt();
    });

    if (location.hash === '#prompt' || @json(! $profileComplete)) {
      setTimeout(function () { openLitePrompt(); }, 200);
    }
  })();
  </script>
  @stack('scripts')
</body>
</html>
