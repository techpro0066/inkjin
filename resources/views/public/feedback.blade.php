@php
  $demo = (bool) ($demo ?? true);
  $mode = $mode ?? 'form';
  $readonly = $mode === 'readonly';
  $clientFirstName = $clientFirstName ?? 'Louis';
  $artistName = $artistName ?? 'Julian Ink';
  $artistFirstName = $artistFirstName ?? 'Julian';
  $artistInitials = $artistInitials ?? 'JI';
  $artistAvatarUrl = $artistAvatarUrl ?? null;
  $sessionMeta = $sessionMeta ?? 'Japanese dragon · Tue 29/09/2026 · Open Ink Studio';
  $submitUrl = $submitUrl ?? null;
  $preselectArtist = $preselectArtist ?? null;
  $preselectBookpay = $preselectBookpay ?? null;
  $savedAnswers = $savedAnswers ?? null;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Session feedback — Bookpay</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&family=Space+Grotesk:wght@700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" rel="stylesheet">
  <style>
    :root{--bg:#FFF6FF;--card:#fff;--line:#E8DFEA;--ink:#1A1A1A;--muted:#6F6874;--faint:#9A929E;--acc:#3E007C;--accbg:#F3E8FF;color-scheme:light}
    *{box-sizing:border-box}
    body{margin:0;background:var(--bg);color:var(--ink);font:400 15px/1.55 'Plus Jakarta Sans',system-ui,sans-serif}
    .ms{font-family:'Material Symbols Outlined';font-weight:normal;font-style:normal;font-size:20px;line-height:1;display:inline-block;vertical-align:middle}
    .wrap{max-width:560px;margin:0 auto;padding:22px 16px 60px}
    .brand{font-family:'Space Grotesk',sans-serif;font-weight:700;font-size:26px;letter-spacing:-.055em;margin:0 0 18px;display:block;color:var(--ink);text-decoration:none}
    .card{background:var(--card);border:1px solid var(--line);border-radius:18px;padding:22px}
    .card+.card{margin-top:14px}
    h1{font-size:24px;letter-spacing:-.4px;margin:0 0 4px}
    h2{font-size:17px;margin:0}
    .sub{color:var(--muted);font-size:14px;margin:0}
    .who{display:flex;gap:12px;align-items:center;margin:16px 0 0;padding:12px;border:1px solid #EFE8F1;border-radius:12px}
    .av{width:46px;height:46px;border-radius:50%;background:#F3E8FF;color:var(--acc);display:flex;align-items:center;justify-content:center;font-weight:800;flex-shrink:0;background-size:cover;background-position:center}
    .who b{display:block}.who span{font-size:13px;color:var(--muted)}
    .faces{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:8px;margin-top:12px}
    .face{display:flex;flex-direction:column;align-items:center;gap:4px;padding:10px 2px;border-radius:12px;border:1.5px solid #EEE9F0;background:#FCFAFD;font:inherit;font-size:12.5px;font-weight:600;color:#3d3942;cursor:pointer}
    .face span{font-size:30px;line-height:1;filter:grayscale(.35);opacity:.8}
    .face:hover{border-color:#CDB6DF}
    .face[aria-checked=true]{border-color:var(--acc);background:var(--accbg);color:var(--acc)}
    .face[aria-checked=true] span{filter:none;opacity:1}
    .face:focus-visible,.th:focus-visible,.btn:focus-visible,textarea:focus-visible{outline:2px solid var(--acc);outline-offset:2px}
    .lbl{font-size:11px;letter-spacing:.8px;font-weight:700;color:var(--muted);text-transform:uppercase;margin:20px 0 4px}
    .crit{display:flex;align-items:center;gap:10px;padding:10px 0;border-bottom:1px solid #F3EEF4}
    .crit:last-child{border-bottom:0}
    .crit div{flex:1;min-width:0}.crit b{display:block;font-size:14px}.crit small{display:block;font-size:12.5px;color:var(--muted)}
    .th{width:44px;height:40px;border-radius:10px;border:1.5px solid #E8DFEA;background:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#6F6874}
    .th[aria-pressed=true].up{background:#E7F6EC;border-color:#1F8A4C;color:#1F8A4C}
    .th[aria-pressed=true].dn{background:#FDECEC;border-color:#C62828;color:#C62828}
    textarea{width:100%;min-height:92px;border:1px solid #E1D8E4;border-radius:12px;padding:11px 12px;font:inherit;font-size:14px;resize:vertical;margin-top:6px}
    textarea[readonly]{background:#F7F5F8;color:#3d3942;resize:none;cursor:default}
    .fl{display:block;font-weight:700;font-size:14px;margin-top:18px}
    .hint{font-size:12.5px;color:var(--muted);margin-top:6px}
    .err{font-size:13px;color:#C62828;margin-top:10px;display:none}
    .btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;border:0;border-radius:12px;padding:14px 22px;background:#1A1A1A;color:#fff;font:inherit;font-weight:700;font-size:15px;cursor:pointer;width:100%;margin-top:18px;text-decoration:none}
    .btn:disabled{opacity:.45;cursor:not-allowed}
    .done{text-align:center;padding:34px 22px}
    .done .ic{width:58px;height:58px;border-radius:50%;background:#E7F6EC;color:#1F8A4C;display:flex;align-items:center;justify-content:center;margin:0 auto 14px}
    .done .ic .ms{font-size:32px}
    .foot{text-align:center;font-size:12px;color:var(--faint);margin-top:22px}
    .banner{display:flex;gap:8px;align-items:center;font-size:13px;color:#1F6B3E;background:#E7F6EC;border-radius:10px;padding:9px 12px;margin-top:14px}
    .banner .ms{font-size:18px}
    .is-readonly .face,.is-readonly .th{pointer-events:none;cursor:default}
    .is-readonly .face:hover{border-color:#EEE9F0}
    .is-readonly .face:not([aria-checked=true]){opacity:.55}
    [hidden]{display:none!important}
    @media(max-width:420px){.face{font-size:11px;padding:8px 0}.face span{font-size:26px}.card{padding:18px 16px}h1{font-size:21px}}
  </style>
</head>
<body>
<div class="wrap">
  <a class="brand" href="{{ url('/') }}" title="Bookpay">bookpay</a>

  <main id="form" class="{{ $readonly ? 'is-readonly' : '' }}" @if ($mode === 'thanks') hidden @endif>
    <div class="card">
      <h1>How was your tattoo session?</h1>
      <p class="sub" id="formSub">
        @if ($readonly)
          Thanks, {{ $clientFirstName }}. Here’s what you submitted.
        @else
          Thanks, {{ $clientFirstName }}. Tap a face to continue.
        @endif
      </p>
      @if ($readonly)
        <div class="banner"><span class="ms">check_circle</span><span>Feedback already submitted.</span></div>
      @endif
      <div class="who">
        @if ($artistAvatarUrl)
          <div class="av" style="background-image:url('{{ $artistAvatarUrl }}')" role="img" aria-label="{{ $artistName }}"></div>
        @else
          <div class="av" aria-hidden="true">{{ $artistInitials }}</div>
        @endif
        <div>
          <b>{{ $artistName }}</b>
          <span>{{ $sessionMeta }}</span>
        </div>
      </div>

      <h2 style="margin-top:20px" id="qa">How was your session with {{ $artistFirstName }}?</h2>
      <div class="faces" role="radiogroup" aria-labelledby="qa" data-q="a"></div>

      <div id="artistMore" @if (! $readonly) hidden @endif>
        <div class="lbl" id="clbl">What went well? (optional)</div>
        <div id="crits"></div>

        <label class="fl" for="ca" id="cal">Anything you want {{ $artistFirstName }} to know?</label>
        <textarea id="ca" placeholder="Write a comment for {{ $artistFirstName }}" @if ($readonly) readonly @endif></textarea>
        <div class="hint">{{ $artistFirstName }} sees your answers and comment, with your first name and the booking.</div>
      </div>
    </div>

    <div class="card" id="bookpayCard" @if (! $readonly) hidden @endif>
      <h2 id="qb">How was booking and paying with Bookpay?</h2>
      <p class="sub" style="margin-top:4px">This is about Bookpay, not your artist. Your answer isn’t shared with {{ $artistFirstName }}.</p>
      <div class="faces" role="radiogroup" aria-labelledby="qb" data-q="b"></div>
      <div id="bmore" @if (! $readonly) hidden @endif>
        <label class="fl" for="cb" id="cbl">Anything we could do better?</label>
        <textarea id="cb" placeholder="Write a comment for Bookpay" @if ($readonly) readonly @endif></textarea>
      </div>
      <div class="err" id="formError"></div>
      <button type="button" class="btn" id="send" @if ($readonly) hidden @endif disabled>Send feedback</button>
    </div>
  </main>

  <main id="thanks" @if ($mode !== 'thanks') hidden @endif>
    <div class="card done">
      <div class="ic"><span class="ms">favorite</span></div>
      <h1>Thanks, {{ $clientFirstName }}</h1>
      <p class="sub" style="max-width:380px;margin:0 auto">Your feedback helps {{ $artistFirstName }} and helps us make Bookpay better.</p>
      <a class="btn" href="{{ url('/') }}" style="max-width:280px;margin-left:auto;margin-right:auto">Back to Bookpay</a>
    </div>
  </main>

  <p class="foot">Bookpay by Inkjin</p>
</div>

<script>
(function () {
  var demo = @json($demo);
  var readonly = @json($readonly);
  var submitUrl = @json($submitUrl);
  var artistFirst = @json($artistFirstName);
  var preA = @json($preselectArtist);
  var preB = @json($preselectBookpay);
  var saved = @json($savedAnswers);
  var csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

  var F = [
    ['1', '😠', 'Terrible'],
    ['2', '🙁', 'Bad'],
    ['3', '😐', 'Okay'],
    ['4', '🙂', 'Good'],
    ['5', '😍', 'Great!']
  ];
  var C = [
    ['communication', 'Communication', 'Replies, and explaining the design, price and aftercare'],
    ['hygiene', 'Hygiene and cleanliness', 'Studio, station, gloves, new needles'],
    ['tattoo', 'The tattoo', 'Happy with the result'],
    ['comfort', 'Comfort', 'Felt listened to and at ease'],
    ['on_time', 'On time', 'The session started when it should']
  ];
  var st = { a: null, b: null, crit: {} };
  var $ = function (id) { return document.getElementById(id); };
  var sending = false;

  function syncSend() {
    if (readonly) return;
    $('send').disabled = !(st.a && st.b) || sending;
  }

  document.querySelectorAll('.faces').forEach(function (g) {
    var q = g.dataset.q;
    g.innerHTML = F.map(function (f) {
      return '<button type="button" class="face" role="radio" aria-checked="false" tabindex="' + (f[0] === '3' ? '0' : '-1') + '" data-v="' + f[0] + '"' + (readonly ? ' disabled' : '') + '><span aria-hidden="true">' + f[1] + '</span>' + f[2] + '</button>';
    }).join('');
    if (readonly) return;
    g.addEventListener('click', function (e) {
      var b = e.target.closest('.face');
      if (!b) return;
      pick(q, b.dataset.v);
    });
    g.addEventListener('keydown', function (e) {
      if (!/Arrow(Left|Right)/.test(e.key)) return;
      e.preventDefault();
      var v = +(st[q] || 3) + (e.key === 'ArrowRight' ? 1 : -1);
      v = Math.max(1, Math.min(5, v));
      pick(q, String(v));
      g.querySelector('[data-v="' + v + '"]').focus();
    });
  });

  function showForm() {
    $('artistMore').hidden = false;
    $('bookpayCard').hidden = false;
  }

  function pick(q, v, opts) {
    opts = opts || {};
    st[q] = String(v);
    document.querySelectorAll('.faces[data-q="' + q + '"] .face').forEach(function (b) {
      var on = b.dataset.v === String(v);
      b.setAttribute('aria-checked', on);
      b.tabIndex = on ? 0 : -1;
    });

    if (q === 'a') {
      showForm();
      var low = +v <= 2;
      $('clbl').textContent = low ? 'What could have been better? (optional)' : 'What went well? (optional)';
      $('cal').textContent = low
        ? 'Sorry it wasn’t great. What happened?'
        : ('Anything you want ' + artistFirst + ' to know?');
    }

    if (q === 'b') {
      $('bmore').hidden = false;
      $('cbl').textContent = +v <= 2 ? 'Sorry about that. What went wrong?' : 'Anything we could do better?';
    }

    if (!opts.skipSync) syncSend();
  }

  $('crits').innerHTML = C.map(function (c) {
    return '<div class="crit"><div><b>' + c[1] + '</b><small>' + c[2] + '</small></div>'
      + '<button type="button" class="th dn" aria-pressed="false" aria-label="' + c[1] + ': not good" data-k="' + c[0] + '" data-v="dn"' + (readonly ? ' disabled' : '') + '><span class="ms">thumb_down</span></button>'
      + '<button type="button" class="th up" aria-pressed="false" aria-label="' + c[1] + ': good" data-k="' + c[0] + '" data-v="up"' + (readonly ? ' disabled' : '') + '><span class="ms">thumb_up</span></button></div>';
  }).join('');

  if (!readonly) {
    $('crits').addEventListener('click', function (e) {
      var b = e.target.closest('.th');
      if (!b) return;
      var k = b.dataset.k;
      var was = b.getAttribute('aria-pressed') === 'true';
      b.parentNode.querySelectorAll('.th').forEach(function (x) { x.setAttribute('aria-pressed', 'false'); });
      if (!was) {
        b.setAttribute('aria-pressed', 'true');
        st.crit[k] = b.dataset.v;
      } else {
        delete st.crit[k];
      }
    });
  }

  function applyCriteria(map) {
    map = map || {};
    Object.keys(map).forEach(function (k) {
      var v = map[k];
      if (v !== 'up' && v !== 'dn') return;
      st.crit[k] = v;
      var btn = document.querySelector('.th[data-k="' + k + '"][data-v="' + v + '"]');
      if (!btn) return;
      btn.parentNode.querySelectorAll('.th').forEach(function (x) { x.setAttribute('aria-pressed', 'false'); });
      btn.setAttribute('aria-pressed', 'true');
    });
  }

  function showThanks() {
    $('form').hidden = true;
    $('thanks').hidden = false;
    window.scrollTo(0, 0);
  }

  function showError(msg) {
    var el = $('formError');
    el.style.display = 'block';
    el.textContent = msg || 'Could not save feedback.';
  }

  if (!readonly) {
    $('send').addEventListener('click', function () {
      if (!(st.a && st.b) || sending) return;
      $('formError').style.display = 'none';

      if (demo || !submitUrl) {
        showThanks();
        return;
      }

      sending = true;
      syncSend();
      $('send').textContent = 'Sending…';

      fetch(submitUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': csrf,
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
          artist_score: parseInt(st.a, 10),
          bookpay_score: parseInt(st.b, 10),
          artist_comment: ($('ca').value || '').trim(),
          bookpay_comment: ($('cb').value || '').trim(),
          criteria: st.crit
        })
      }).then(function (r) {
        return r.json().then(function (d) { return { ok: r.ok, d: d }; });
      }).then(function (res) {
        if (!res.ok || !res.d.success) {
          throw new Error((res.d && (res.d.message || (res.d.errors && Object.values(res.d.errors).flat()[0]))) || 'Could not save feedback.');
        }
        showThanks();
      }).catch(function (err) {
        sending = false;
        $('send').textContent = 'Send feedback';
        syncSend();
        showError(err.message || 'Could not save feedback.');
      });
    });
  }

  if (readonly && saved) {
    if (saved.artist_score) pick('a', String(saved.artist_score), { skipSync: true });
    if (saved.bookpay_score) pick('b', String(saved.bookpay_score), { skipSync: true });
    applyCriteria(saved.criteria || {});
    if (saved.artist_comment) $('ca').value = saved.artist_comment;
    if (saved.bookpay_comment) {
      $('bmore').hidden = false;
      $('cb').value = saved.bookpay_comment;
    }
    showForm();
  } else {
    if (preA) pick('a', String(preA));
    if (preB) pick('b', String(preB));
  }
})();
</script>
</body>
</html>
