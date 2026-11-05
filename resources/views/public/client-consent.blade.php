<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="color-scheme" content="only light">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Consent Form — {{ $payload['artist']['name'] ?? 'Bookpay' }}</title>
  <link rel="icon" href="{{ asset('assets/img/favicon/favicon.png') }}">
  <style>
@font-face{font-family:'Plus Jakarta Sans';font-weight:400;font-display:swap;src:url('{{ asset('new-ui-design-assets/pjs-400.woff2') }}') format('woff2')}
@font-face{font-family:'Plus Jakarta Sans';font-weight:600;font-display:swap;src:url('{{ asset('new-ui-design-assets/pjs-600.woff2') }}') format('woff2')}
@font-face{font-family:'Plus Jakarta Sans';font-weight:700;font-display:swap;src:url('{{ asset('new-ui-design-assets/pjs-700.woff2') }}') format('woff2')}
@font-face{font-family:'Material Symbols Outlined';font-display:block;src:url('{{ asset('new-ui-design-assets/material-symbols-outlined.woff2') }}') format('woff2')}
:root{--bg:#F5F4F1;--card:#fff;--line:#E7E4DE;--soft:#F0EEEA;--ink:#1A1A1A;--muted:#5E5A53;--faint:#8F8A82;--dark:#1E3B33;--warn:#8A5A00;--warnbg:#FFF6EC;--bad:#B3261E;--badbg:#FCEBEA}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--ink);font-family:'Plus Jakarta Sans',system-ui,-apple-system,sans-serif;font-size:14.5px;line-height:1.5}
.ms{font-family:'Material Symbols Outlined';font-weight:normal;font-style:normal;font-size:20px;line-height:1;display:inline-block;-webkit-font-smoothing:antialiased}
.wrap{max-width:420px;margin:0 auto;padding:28px 14px 90px}
.box{background:var(--card);border:1px solid var(--line);border-radius:14px;margin-bottom:12px}
.bar{display:flex;justify-content:flex-end;align-items:center;gap:4px;padding:10px 14px}
.bar .ms{font-size:17px;color:var(--muted);margin-right:4px}
.bar button{font:inherit;font-size:12.5px;font-weight:700;border:0;background:none;color:var(--muted);padding:5px 10px;border-radius:7px;cursor:pointer;text-transform:uppercase}
.bar button[aria-pressed=true]{background:var(--dark);color:#fff}
.art{display:flex;gap:12px;align-items:center;padding:14px 16px}
.av{width:40px;height:40px;border-radius:50%;background:var(--dark);color:#fff;font-weight:700;display:flex;align-items:center;justify-content:center;font-size:14px;overflow:hidden;flex:none}
.av img{width:100%;height:100%;object-fit:cover;display:block}
.art b{display:block;font-size:14.5px}.art span{font-size:12.5px;color:var(--muted)}
.sec{padding:18px 20px;border-top:1px solid var(--soft)}.sec:first-child{border-top:0}
.sec.warnb{background:var(--warnbg);color:var(--warn);font-size:13px;display:flex;gap:10px}
.sec.badb{background:var(--badbg);color:var(--bad);font-size:13px;display:flex;gap:10px}
.sec.warnb .ms,.sec.badb .ms{font-size:17px;margin-top:1px;flex:none}
h1{font-size:17px;margin:0 0 6px}
.intro{color:#3E3B36;font-size:14.5px}
.cap{font-size:12px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:#4A463F}
.capsub{font-size:12.5px;color:var(--muted);margin-top:3px}
.fl{display:block;font-size:14px;font-weight:600;margin:14px 0 6px}
.fl:first-of-type{margin-top:10px}
.in{width:100%;font:inherit;font-size:14.5px;padding:10px 12px;border:1px solid #D9D5CE;border-radius:9px;background:#fff;color:var(--ink);outline:none}
.in:focus{border-color:var(--dark)}
.ph2{display:grid;grid-template-columns:minmax(132px,150px) 1fr;gap:8px}
select.in{appearance:auto;cursor:pointer}
.field-err{color:var(--bad);font-size:12.5px;margin-top:6px}
.yn{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid var(--soft);font-size:14px}
.yn>span:first-child{min-width:0;line-height:1.35}
.seg{display:inline-flex;flex:none;border:1px solid #D9D5CE;border-radius:8px;overflow:hidden}
.seg button{font:inherit;font-size:13px;border:0;background:#fff;padding:5px 12px;cursor:pointer;color:var(--ink)}
.seg button+button{border-left:1px solid #D9D5CE}
.seg button[aria-pressed=true]{background:var(--dark);color:#fff}
ul.bl{list-style:none;margin:10px 0 0;padding:0}
ul.bl li{position:relative;padding:9px 0 9px 14px;border-bottom:1px solid var(--soft);font-size:14px}
ul.bl li:last-child{border-bottom:0}
ul.bl li:before{content:'';position:absolute;left:0;top:17px;width:4px;height:4px;border-radius:50%;background:#8F8A82}
.ck{display:flex;gap:12px;align-items:flex-start;font-size:14px;cursor:pointer}
.ck input{width:17px;height:17px;margin-top:2px;accent-color:var(--dark);flex:none}
.info{display:flex;gap:10px;font-size:12.5px;color:var(--muted);margin-bottom:10px}.info .ms{font-size:16px;flex:none;margin-top:1px}
.flag{display:flex;gap:10px;font-size:12.5px;color:var(--warn);background:var(--warnbg);border-radius:10px;padding:10px 12px;margin-top:10px}.flag .ms{font-size:16px;flex:none;margin-top:1px}
.foot{font-size:11.5px;color:var(--faint);text-align:center;padding:12px 20px 14px;border-top:1px solid var(--soft)}
.go{display:block;width:100%;font:inherit;font-weight:700;font-size:14.5px;border:0;border-radius:12px;background:var(--dark);color:#fff;padding:13px;cursor:pointer;margin-top:4px}
.go:disabled{background:#E4E2DE;color:#A7A39C;cursor:not-allowed}
.submit-err{text-align:center;color:var(--bad);font-size:12.5px;font-weight:600;margin-top:12px}
.done{min-height:calc(100vh - 40px);display:flex;align-items:center;justify-content:center;padding:16px}
.done .box{max-width:420px;width:100%;text-align:center;padding:32px 30px;margin:0}
.chk{width:48px;height:48px;border-radius:50%;background:#E7EFEA;display:flex;align-items:center;justify-content:center;margin:0 auto 14px}
.chk .ms{color:#1F6B4E;font-size:26px}
.done h1{font-size:19px}.done p{margin:6px 0 0;color:#3E3B36;font-size:14px}.done .small{font-size:12.5px;color:var(--muted);margin-top:14px}
  </style>
</head>
<body>
  <div id="root"></div>

  @include('partials.bookpay-datetime-picker')

  <script>
    window.CLIENT_CONSENT = @json($payload);
    @php
      $consentUiStringsPath = public_path('consent-ui-strings.json');
      $consentUiStrings = is_readable($consentUiStringsPath)
        ? json_decode((string) file_get_contents($consentUiStringsPath), true)
        : [];
      if (! is_array($consentUiStrings)) {
          $consentUiStrings = [];
      }
    @endphp
    window.CONSENT_UI_STRINGS = @json($consentUiStrings);
    window.CONSENT_UI_STRINGS_URL = @json(asset('consent-ui-strings.json'));
  </script>
  <script crossorigin src="https://unpkg.com/react@18.3.1/umd/react.production.min.js"></script>
  <script crossorigin src="https://unpkg.com/react-dom@18.3.1/umd/react-dom.production.min.js"></script>
  <script src="{{ asset('js/client-consent-view.js') }}?v={{ @filemtime(public_path('js/client-consent-view.js')) }}"></script>
</body>
</html>
