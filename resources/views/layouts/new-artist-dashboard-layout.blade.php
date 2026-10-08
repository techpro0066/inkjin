@php
  $authUser = auth()->user();
  $artistName = trim(($authUser?->first_name ?? '') . ' ' . ($authUser?->last_name ?? ''));
  if ($artistName === '') {
    $artistName = $authUser?->email ?? 'Artist';
  }
  $artistEmail = $authUser?->email ?? '';
  $artistInitials = strtoupper(substr(preg_replace('/\s+/', '', $artistName) ?: 'AR', 0, 2));
  $activeNav = $activeNav ?? 'home';

  $avatarUrl = null;
  $avatarPath = trim((string) ($authUser?->userDetail?->avatar ?? ''));
  if ($avatarPath !== '') {
    $avatarUrl = str_starts_with($avatarPath, 'http')
      ? $avatarPath
      : asset(ltrim($avatarPath, '/'));
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
  <title>@yield('title', 'Artist dashboard') — Bookpay</title>
  <link rel="icon" href="{{ asset('design/images/icons/favicon.png') }}">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&display=block" rel="stylesheet">
  <style>
    :root{
      --bg:#FFF6FF;--card:#fff;--line:#E8DFEA;--tint:#FAF0FB;--tintline:#F1E7F3;--ink:#1A1A1A;
      --muted:#6F6874;--faint:#9A929E;--pri:#3E007C;--pril:#F3E8FF;--chip:#F0EDF2;--green:#00A650;--greenl:#E6F8EE;
      --amber:#B86E00;--amberl:#FFF3DE;--red:#C62828;--redl:#FDECEC;--blue:#1E5BD8;--bluel:#E8F0FF
    }
    *{box-sizing:border-box;margin:0;padding:0}
    html{height:100%}
    body{
      font-family:'Plus Jakarta Sans',system-ui,sans-serif;
      background:var(--bg);color:var(--ink);font-size:14px;
      width:100%;display:flex;
      height:100vh;height:100dvh;overflow:hidden;
    }
    a{color:inherit;text-decoration:none}
    .ms{
      font-family:'Material Symbols Outlined';font-weight:normal;font-style:normal;font-size:20px;line-height:1;
      display:inline-block;-webkit-font-smoothing:antialiased;
      font-variation-settings:'FILL' 0,'wght' 400,'GRAD' 0,'opsz' 24;vertical-align:middle
    }

    /* Fixed viewport shell: sidebar stays put; only main scrolls */
    aside{
      width:232px;background:#1A1A1A;color:#DEDEDE;padding:22px 14px 18px;
      display:flex;flex-direction:column;flex-shrink:0;
      height:100%;min-height:0;overflow:hidden;
    }
    .logo{
      font-family:'Space Grotesk',system-ui,sans-serif;font-size:25px;font-weight:700;
      letter-spacing:-.055em;color:#fff;text-decoration:none;padding-left:6px;display:block
    }
    .tag{font-size:8.5px;letter-spacing:1.4px;color:#8C8C8C;padding-left:6px;margin-top:2px;line-height:1.35}
    .topline{display:flex;align-items:center;justify-content:space-between;gap:8px;flex-shrink:0}
    .burger{display:none;background:none;border:0;color:#fff;padding:6px;border-radius:8px;cursor:pointer}
    .burger .ms{font-size:26px}

    aside nav{
      margin-top:26px;display:flex;flex-direction:column;gap:3px;
      flex:1;min-height:0;overflow-x:hidden;overflow-y:auto;overscroll-behavior:contain
    }
    aside nav a,aside nav button.navlink{
      display:flex;align-items:center;gap:12px;padding:9px 12px;border-radius:12px;
      font-family:inherit;font-weight:500;font-size:14.5px;line-height:normal;
      color:#DEDEDE;text-decoration:none;
      border:0;background:none;cursor:pointer;width:100%;text-align:left
    }
    aside nav a .ms,aside nav button.navlink .ms{font-size:21px;color:#DEDEDE}
    aside nav a.on,aside nav button.navlink.on{background:#fff;color:var(--pri);font-weight:600}
    aside nav a.on .ms,aside nav button.navlink.on .ms{color:var(--pri)}
    .badge{background:#fff;color:#1A1A1A;border-radius:6px;font-size:11px;padding:2px 7px;font-weight:600}
    .cnt{margin-left:auto;background:#3A3A3A;color:#fff;border-radius:10px;font-size:11px;padding:1px 8px;font-weight:600}
    aside nav a.on .cnt{background:var(--pri)}
    .sep{height:1px;background:#333;margin:10px 4px;flex-shrink:0}

    .foot{margin-top:auto;flex-shrink:0}
    .foot > nav{margin:0}
    .foot .logout-btn{
      display:flex;align-items:center;gap:12px;width:100%;padding:9px 12px;border:0;border-radius:12px;
      background:none;color:#a8a8a8;font-family:inherit;font-weight:500;font-size:14.5px;line-height:normal;
      cursor:pointer;text-align:left
    }
    .foot .logout-btn .ms{font-size:21px;color:#a8a8a8}
    .user{
      display:flex;gap:10px;align-items:center;padding:14px 8px 0;border-top:1px solid #333;margin-top:10px;
      text-decoration:none;color:inherit
    }
    .av{
      width:36px;height:36px;border-radius:50%;background:var(--pri);color:#fff;
      display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;
      flex:none;overflow:hidden;position:relative
    }
    .av img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;display:block;z-index:2}
    .av .ini{position:relative;z-index:1}
    .av.has-img .ini{display:none}
    .user b{font-size:13px;font-weight:700;color:#fff;display:block}
    .user span{font-size:11.5px;font-weight:400;color:#9a9a9a;display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:140px}

    main{
      flex:1;min-width:0;min-height:0;height:100%;
      overflow-x:hidden;overflow-y:auto;overscroll-behavior:contain;
      padding:36px 48px 56px
    }
    .wrap{max-width:1112px}

    .head{display:flex;align-items:flex-start;justify-content:space-between;gap:24px;flex-wrap:wrap}
    h1{font-size:30px;font-weight:800;letter-spacing:-.6px;margin:0}
    .sub{color:var(--muted);font-size:14.5px;margin-top:4px;line-height:1.45}
    .btn{
      display:inline-flex;align-items:center;justify-content:center;gap:8px;
      background:var(--ink);color:#fff;border:0;border-radius:10px;padding:10px 16px;
      font-family:inherit;font-weight:600;font-size:13.5px;line-height:normal;
      white-space:nowrap;cursor:pointer;text-decoration:none
    }
    .btn .ms{font-size:18px}
    .btn.ghost{background:#fff;color:var(--ink);border:1px solid var(--line)}
    .btn.sm{padding:6px 11px;font-size:12.5px;border-radius:8px}
    .btn.pri{background:var(--pri)}
    .btns{display:flex;gap:10px;flex-wrap:wrap}
    .tabs{display:flex;gap:28px;border-bottom:1px solid var(--line);margin:26px 0 24px;overflow-x:auto;scrollbar-width:none}
    .tabs::-webkit-scrollbar{display:none}
    .tabs a,.tabs div{padding:0 2px 12px;font-weight:600;color:var(--muted);font-size:14.5px;display:flex;gap:7px;align-items:center;white-space:nowrap;flex-shrink:0;cursor:pointer}
    .tabs a.on,.tabs div.on{color:var(--ink);box-shadow:inset 0 -2.5px 0 var(--ink)}
    .tabs .n{background:var(--chip);color:var(--muted);border-radius:10px;font-size:11px;padding:1px 7px}
    .tabs a.on .n,.tabs div.on .n{background:var(--ink);color:#fff}
    .card{background:var(--card);border:1px solid var(--line);border-radius:16px}
    .pad{padding:20px 22px}
    .tint{background:var(--tint);border:1px solid var(--tintline);border-radius:16px}
    .ch{display:flex;align-items:center;justify-content:space-between;padding:18px 22px;border-bottom:1px solid var(--line)}
    .ch h3,h3{font-size:16px;font-weight:700;margin:0}
    .ch .l{font-weight:600;font-size:13px;display:inline-flex;gap:4px;align-items:center;color:var(--pri);text-decoration:none}
    .ch .l:hover{text-decoration:underline}
    .btn.disabled,.btn[aria-disabled="true"]{opacity:.45;pointer-events:none;cursor:not-allowed}
    .bpw-toast{position:fixed;bottom:22px;right:24px;background:#1A1A1A;color:#fff;padding:12px 18px;border-radius:10px;font-weight:600;font-size:14px;z-index:2000;box-shadow:0 10px 30px rgba(0,0,0,.22);display:flex;gap:10px;align-items:center;max-width:calc(100% - 32px);opacity:0;transform:translateY(8px);pointer-events:none;transition:opacity .2s,transform .2s}
    .bpw-toast.on{opacity:1;transform:translateY(0);pointer-events:auto}
    .bpw-toast .ms{color:#3DD68C}
    .muted{color:var(--muted)}.faint{color:var(--faint)}
    .pill{display:inline-flex;align-items:center;gap:5px;border-radius:20px;font-size:11.5px;font-weight:600;padding:3px 9px;white-space:nowrap}
    .pill:before{content:'';width:5px;height:5px;border-radius:50%;background:currentColor}
    .pill.nd:before{display:none}
    .g{background:var(--greenl);color:var(--green)}.p{background:var(--pril);color:#7A1FC4}.a{background:var(--amberl);color:var(--amber)}
    .r{background:var(--redl);color:var(--red)}.b{background:var(--bluel);color:var(--blue)}.k{background:var(--chip);color:var(--muted)}
    .chips{display:flex;gap:8px;flex-wrap:wrap}
    .chip{border:1px solid var(--line);background:#fff;border-radius:20px;padding:6px 13px;font-size:12.5px;font-weight:600;color:var(--muted)}
    .chip.on{background:var(--pri);border-color:var(--pri);color:#fff}
    .lbl{font-size:11px;letter-spacing:.8px;font-weight:700;color:var(--muted);text-transform:uppercase}
    .fl{font-size:13px;font-weight:700;margin-bottom:6px;display:block}
    .help{font-size:12px;color:var(--faint);margin-top:6px}
    .in{
      background:#fff;border:1px solid var(--line);border-radius:10px;padding:10px 13px;
      font-family:inherit;font-size:13.5px;font-weight:400;line-height:normal;
      display:flex;align-items:center;gap:8px;min-height:40px;width:100%;color:var(--ink);outline:none
    }
    input.in{display:block}
    .in.ph{color:var(--faint)}
    input.in:focus,.in:focus-within{border-color:#3E007C;box-shadow:0 0 0 3px #F3E8FF}
    .in .ms{font-size:18px;color:var(--faint)}
    table{width:100%;border-collapse:collapse}
    th{font-size:11px;letter-spacing:.8px;color:var(--muted);text-align:left;font-weight:700;padding:11px 16px;background:#FBF8FC;border-bottom:1px solid var(--line)}
    td{padding:14px 16px;border-bottom:1px solid #F3EEF4;font-size:13.5px;vertical-align:middle}
    tr:last-child td{border-bottom:0}
    .grid{display:grid;gap:16px}
    .ic{width:38px;height:38px;border-radius:10px;background:var(--chip);display:flex;align-items:center;justify-content:center;flex-shrink:0}
    .ic .ms{font-size:20px}
    .ini{width:34px;height:34px;border-radius:50%;background:var(--ink);color:#fff;font-size:12px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0}
    .row{display:flex;align-items:center;gap:12px}
    .note{border:1.5px dashed #B99BE0;background:#FBF5FF;border-radius:12px;padding:10px 14px;font-size:12.5px;color:#5B2A94;display:flex;gap:8px;align-items:center}
    .note .ms{font-size:18px}
    .help-q{display:inline-flex;align-items:center;justify-content:center;width:26px;height:26px;margin-left:8px;border-radius:50%;color:#9A929E;vertical-align:middle;text-decoration:none;position:relative;top:-2px}
    .help-q .ms{font-size:22px}.help-q:hover{color:#3E007C;background:#F3E8FF}
    .help-q:focus-visible{outline:2px solid #3E007C;outline-offset:2px}

    @media (max-width:900px){
      body{flex-direction:column;height:auto;min-height:100vh;min-height:100dvh;overflow:auto}
      aside{width:100%;height:auto;min-height:0;padding:12px 16px;position:sticky;top:0;z-index:20;overflow:visible}
      aside .tag{display:none}
      .burger{display:inline-flex}
      aside nav,.foot{display:none}
      body.navopen aside nav{display:flex;overflow:visible;flex:none}
      body.navopen aside .foot{display:block;margin-top:8px}
      body.navopen aside{max-height:100vh;max-height:100dvh;overflow-y:auto}
      aside nav{margin-top:12px}
      main{height:auto;overflow:visible;padding:22px 16px 48px}
      h1{font-size:25px}
    }
  </style>
  @stack('styles')
  @yield('styles')
</head>
<body>
  <aside>
    <div class="topline">
      <a href="{{ route('artist.dashboard') }}" class="logo">bookpay</a>
      <button class="burger" type="button" aria-label="Open menu" onclick="document.body.classList.toggle('navopen')">
        <span class="ms">menu</span>
      </button>
    </div>
    <div class="tag">TATTOO ARTIST PLATFORM<br>BY INKJIN</div>

    <nav>
      <a href="{{ route('artist.dashboard') }}" class="{{ $activeNav === 'home' || request()->routeIs('artist.dashboard') ? 'on' : '' }}">
        <span class="ms">space_dashboard</span>Home
      </a>
      <a href="{{ route('artist.bookings.index') }}" class="{{ $activeNav === 'bookings' || request()->routeIs('artist.bookings.*') ? 'on' : '' }}">
        <span class="ms">calendar_month</span>Bookings
      </a>
      <a href="{{ route('availability.index') }}" class="{{ $activeNav === 'schedule' || request()->routeIs('availability.*') ? 'on' : '' }}">
        <span class="ms">schedule</span>Schedule
      </a>
      <a href="{{ route('artist.chat.index') }}" class="{{ $activeNav === 'inbox' || request()->routeIs('artist.chat.*') ? 'on' : '' }}">
        <span class="ms">mail</span>Inbox
        <span id="inboxUnreadDot" class="cnt hidden" style="display:none" aria-hidden="true"></span>
      </a>
      <a href="{{ route('artist.clients.index') }}" class="{{ $activeNav === 'clients' || request()->routeIs('artist.clients.*') ? 'on' : '' }}">
        <span class="ms">group</span>Clients
      </a>
      <a href="{{ route('artist.payments.index') }}" class="{{ $activeNav === 'money' || request()->routeIs('artist.payments.*') ? 'on' : '' }}">
        <span class="ms">payments</span>Money
      </a>
      <a href="{{ route('personal-page.index') }}" class="{{ $activeNav === 'my-page' || request()->routeIs('personal-page.*') || request()->routeIs('portfolio.*') || request()->routeIs('artist-designs.*') ? 'on' : '' }}">
        <span class="ms">folder_open</span>My Page
      </a>
      <a href="{{ route('artist.forms.index') }}" class="{{ $activeNav === 'client-flow' || request()->routeIs('artist.forms.*') || request()->routeIs('artist.requests.*') || request()->routeIs('artist.custom-requests.*') || request()->routeIs('artist.guest-requests.*') ? 'on' : '' }}">
        <span class="ms">edit_note</span>Client Flow
      </a>
      <a href="{{ route('profile.edit') }}" class="{{ $activeNav === 'account' || request()->routeIs('profile.*') || request()->routeIs('settings.*') ? 'on' : '' }}">
        <span class="ms">settings</span>Account
      </a>

      <div class="sep"></div>

      <a href="{{ route('artist.refer-earn.index') }}" class="{{ $activeNav === 'referrals' || request()->routeIs('artist.refer-earn.*') ? 'on' : '' }}">
        <span class="ms">redeem</span>Referrals <span class="badge">Earn €20</span>
      </a>
      <a href="https://help.inkjin.com" target="_blank" rel="noopener" data-intercom-get-help>
        <span class="ms">help</span>Get Help
      </a>
      <a href="https://www.instagram.com/direct/t/17845585272021190/" target="_blank" rel="noopener" class="reqf" aria-label="Request feature (opens in a new tab)">
        <span class="ms">lightbulb</span>Request feature
        <span class="ms" aria-hidden="true" style="font-size:15px;margin-left:auto;opacity:.55">open_in_new</span>
      </a>
    </nav>

    <div class="foot">
      <nav>
        <form method="POST" action="{{ route('logout') }}">
          @csrf
          <button type="submit" class="logout-btn">
            <span class="ms">logout</span>Log Out
          </button>
        </form>
      </nav>
      <a href="{{ route('profile.edit') }}" class="user" title="Account">
        <div class="av{{ $avatarUrl ? ' has-img' : '' }}">
          @if ($avatarUrl)
            <img src="{{ $avatarUrl }}" alt="">
          @endif
          <span class="ini">{{ $artistInitials }}</span>
        </div>
        <div>
          <b>{{ $artistName }}</b>
          <span>{{ $artistEmail }}</span>
        </div>
      </a>
    </div>
  </aside>

  <main>
    <div class="wrap">
      @yield('content')
    </div>
  </main>

  @stack('scripts')
  @yield('scripts')
  @include('layouts.partials.intercom-messenger')
  <script src="{{ asset('js/chat-unread-badge.js') }}?v=1" defer data-api-base="{{ url('/api/chat') }}"></script>
  <script>
    (function () {
      var dot = document.getElementById('inboxUnreadDot');
      if (!dot) return;
      var obs = new MutationObserver(function () {
        var has = !dot.classList.contains('hidden') && (dot.textContent || '').trim() !== '';
        dot.style.display = has ? '' : 'none';
      });
      obs.observe(dot, { attributes: true, childList: true, characterData: true, subtree: true });
    })();
  </script>
</body>
</html>
