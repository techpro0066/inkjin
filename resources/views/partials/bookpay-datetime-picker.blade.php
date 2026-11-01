{{-- Bookpay date/time pickers (F01 BP-DT) — enhances input[type=date|time] --}}
<style>
  input.bpx-native{
    position:absolute!important;opacity:0!important;pointer-events:none!important;
    width:1px!important;height:1px!important;min-height:0!important;padding:0!important;
    margin:0!important;border:0!important
  }
  .bps-btn{
    display:flex!important;align-items:center;justify-content:space-between;gap:8px;
    text-align:left;cursor:pointer;font:inherit;color:inherit;box-sizing:border-box;
    -webkit-tap-highlight-color:transparent
  }
  .bps-btn.bps-plain{border:0;background:none;padding:10px 0;width:100%;font-size:14px;color:#1A1A1A;min-width:0}
  .bps-btn .bps-t{flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
  .bps-btn .bps-t.bps-ph{color:#8F8A95}
  .bps-btn:disabled,.bps-btn[aria-disabled="true"]{cursor:default;opacity:.6}
  .bps-btn:focus-visible{outline:2px solid #3E007C;outline-offset:2px}
  .bpx-btn.in{width:100%}
  .bpx-btn .bpx-i{flex:none;width:18px;height:18px;opacity:.55}
  .bpx-btn.border-error,.bpx-btn.err,.bpx-btn.bad{border-color:#C62828!important;box-shadow:none}
  .bpx-pop{
    position:fixed;z-index:10050;background:#fff;border:1px solid #EAE2EE;border-radius:14px;
    box-shadow:0 14px 40px rgba(20,0,40,.18);padding:12px;font-size:14px;color:#1A1A1A;font-family:inherit
  }
  .bpx-pop[hidden]{display:none!important}
  .bpx-h{display:flex;align-items:center;justify-content:space-between;margin-bottom:8px}
  .bpx-h button{border:0;background:none;font:inherit;cursor:pointer;border-radius:8px;padding:6px 8px;color:#1A1A1A}
  .bpx-h button:hover{background:#F7F3F9}
  .bpx-h .bpx-ttl{font-weight:800;font-size:14.5px}
  .bpx-h .bpx-nav{width:34px;height:34px;display:flex;align-items:center;justify-content:center;padding:0}
  .bpx-h .bpx-nav:disabled{opacity:.3;cursor:default;background:none}
  .bpx-g{display:grid;grid-template-columns:repeat(7,1fr);gap:2px}
  .bpx-w{font-size:11.5px;color:#8F8A95;text-align:center;padding:4px 0;font-weight:700}
  .bpx-d{border:0;background:none;font:inherit;font-size:13.5px;height:36px;border-radius:9px;cursor:pointer;color:#1A1A1A}
  .bpx-d:hover{background:#F3E8FF}
  .bpx-d.bpx-o{color:#C8C1CC}
  .bpx-d.bpx-t{box-shadow:inset 0 0 0 1.5px #3E007C}
  .bpx-d.bpx-s{background:#3E007C;color:#fff;font-weight:700}
  .bpx-d:disabled{color:#D6D0DA;cursor:default;background:none;text-decoration:line-through}
  .bpx-yg{display:grid;grid-template-columns:repeat(3,1fr);gap:6px}
  .bpx-yg button{border:1px solid #EFE8F1;background:#fff;font:inherit;padding:10px 0;border-radius:9px;cursor:pointer}
  .bpx-yg button:hover{background:#F3E8FF}
  .bpx-yg button.bpx-s{background:#3E007C;color:#fff;border-color:#3E007C}
  .bpx-f{display:flex;justify-content:space-between;margin-top:8px;padding-top:8px;border-top:1px solid #F3EEF4}
  .bpx-f button{border:0;background:none;font:inherit;font-weight:700;color:#3E007C;cursor:pointer;padding:6px 8px;border-radius:8px}
  .bpx-f button:hover{background:#F7F3F9}
  .bpx-tl{max-height:280px;overflow:auto;margin:-6px;padding:6px}
  .bpx-to{padding:10px 12px;border-radius:8px;cursor:pointer;display:flex;justify-content:space-between}
  .bpx-to:hover,.bpx-to.bpx-a{background:#F7F3F9}
  .bpx-to.bpx-s{color:#3E007C;font-weight:700;background:#F3E8FF}
  .bpx-to.bpx-dis{color:#D6D0DA;cursor:default;background:none}
</style>
<script>
(function () {
  if (window.__bpDtPicker) return;
  window.__bpDtPicker = true;

  var CAL = '<svg class="bpx-i" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3.5" y="5" width="17" height="15" rx="2.5"/><path d="M3.5 10h17M8 3v4M16 3v4"/></svg>';
  var CLK = '<svg class="bpx-i" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/></svg>';
  var ARL = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 6l-6 6 6 6"/></svg>';
  var ARR = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg>';
  var MON = ['January','February','March','April','May','June','July','August','September','October','November','December'];

  var pop = document.createElement('div');
  pop.className = 'bpx-pop';
  pop.hidden = true;
  pop.setAttribute('role', 'dialog');

  function ready(fn) {
    if (document.readyState !== 'loading') setTimeout(fn, 0);
    else document.addEventListener('DOMContentLoaded', function () { setTimeout(fn, 0); });
  }

  ready(function () { document.body.appendChild(pop); });

  var dcur = null, view = null, mode = 'd';

  function p2(n) { return ('0' + n).slice(-2); }
  function iso(d) { return d.getFullYear() + '-' + p2(d.getMonth() + 1) + '-' + p2(d.getDate()); }
  function pd(v) {
    var m = /^(\d{4})-(\d\d)-(\d\d)$/.exec(v || '');
    return m ? new Date(+m[1], +m[2] - 1, +m[3]) : null;
  }
  function fmtD(v) {
    var d = pd(v);
    return d ? p2(d.getDate()) + '/' + p2(d.getMonth() + 1) + '/' + d.getFullYear() : '';
  }
  function today() {
    var t = new Date();
    t.setHours(0, 0, 0, 0);
    return t;
  }

  function dsync(inp) {
    var b = inp.__bpx;
    if (!b) return;
    var t = inp.type === 'time' || inp.__bpxT === 'time';
    var v = inp.value;
    var sp = b.querySelector('.bps-t');
    var txt = t ? (v ? v.slice(0, 5) : '') : fmtD(v);
    var ph = inp.getAttribute('placeholder') || inp.getAttribute('data-ph') || (t ? 'Select time' : 'Select date');
    sp.textContent = txt || ph;
    sp.classList.toggle('bps-ph', !txt);
    b.disabled = inp.disabled;
    b.hidden = inp.hidden;
    b.classList.toggle('err', inp.classList.contains('err'));
    b.classList.toggle('bad', inp.classList.contains('bad'));
    b.classList.toggle('border-error', inp.classList.contains('border-error'));
  }

  function denh(inp) {
    if (inp.__bpx || inp.closest('.bps-skip')) return;
    var ty = inp.getAttribute('type');
    if (ty !== 'date' && ty !== 'time') return;
    var nx = inp.nextElementSibling;
    if (nx && nx.classList.contains('bpx-btn') && !nx.__inp) nx.remove();
    inp.classList.remove('bpx-native');
    inp.__bpxT = ty;
    var b = document.createElement('button');
    b.type = 'button';
    var cls = (inp.getAttribute('class') || '').replace(/\berr\b|\bbad\b|\bborder-error\b/g, '').trim();
    b.className = (cls ? cls + ' ' : 'bps-plain ') + 'bps-btn bpx-btn';
    var st = inp.getAttribute('style');
    if (st) b.setAttribute('style', st);
    if (inp.id) b.setAttribute('data-for', inp.id);
    b.setAttribute('aria-haspopup', 'dialog');
    b.setAttribute('aria-expanded', 'false');
    b.setAttribute('aria-label', inp.getAttribute('aria-label') || (ty === 'time' ? 'Time' : 'Date'));
    b.innerHTML = '<span class="bps-t"></span>' + (ty === 'time' ? CLK : CAL);
    if (inp.parentNode && getComputedStyle(inp.parentNode).position === 'static') {
      inp.parentNode.style.position = 'relative';
    }
    inp.parentNode.insertBefore(b, inp.nextSibling);
    inp.classList.add('bpx-native');
    inp.setAttribute('tabindex', '-1');
    inp.setAttribute('aria-hidden', 'true');
    inp.__bpx = b;
    b.__inp = inp;
    dsync(inp);
    new MutationObserver(function () { dsync(inp); }).observe(inp, {
      attributes: true,
      attributeFilter: ['disabled', 'hidden', 'class', 'min', 'max', 'placeholder'],
    });
    inp.addEventListener('change', function () { dsync(inp); });
    inp.addEventListener('input', function () { dsync(inp); });
    inp.addEventListener('focus', function () { b.focus(); });
  }

  function placePop() {
    if (!dcur) return;
    var b = dcur.__bpx, r = b.getBoundingClientRect(), vw = innerWidth, vh = innerHeight;
    var w = dcur.__bpxT === 'time' ? Math.max(r.width, 160) : 300;
    w = Math.min(w, vw - 16);
    pop.style.width = w + 'px';
    var h = pop.offsetHeight, below = vh - r.bottom - 12, above = r.top - 12, up = below < h && above > below;
    var left = Math.min(Math.max(8, r.left), vw - 8 - w);
    pop.style.left = left + 'px';
    if (up) {
      pop.style.top = 'auto';
      pop.style.bottom = (vh - r.top + 6) + 'px';
    } else {
      pop.style.bottom = 'auto';
      pop.style.top = (r.bottom + 6) + 'px';
    }
  }

  function lim(inp) {
    return { min: pd(inp.getAttribute('min')), max: pd(inp.getAttribute('max')) };
  }

  function drawD() {
    var inp = dcur, L = lim(inp), sel = pd(inp.value), t = today(), y = view.getFullYear(), m = view.getMonth();
    var h = '';
    if (mode === 'd') {
      var first = new Date(y, m, 1), off = (first.getDay() + 6) % 7, start = new Date(y, m, 1 - off);
      var prevOk = !L.min || new Date(y, m, 0) >= L.min, nextOk = !L.max || new Date(y, m + 1, 1) <= L.max;
      h += '<div class="bpx-h"><button type="button" class="bpx-nav" data-nav="-1" aria-label="Previous month"' + (prevOk ? '' : ' disabled') + '>' + ARL + '</button><button type="button" class="bpx-ttl" data-mode="y">' + MON[m] + ' ' + y + '</button><button type="button" class="bpx-nav" data-nav="1" aria-label="Next month"' + (nextOk ? '' : ' disabled') + '>' + ARR + '</button></div><div class="bpx-g">' + ['Mo','Tu','We','Th','Fr','Sa','Su'].map(function (x) { return '<div class="bpx-w">' + x + '</div>'; }).join('');
      for (var i = 0; i < 42; i++) {
        var d = new Date(start);
        d.setDate(start.getDate() + i);
        var dis = (L.min && d < L.min) || (L.max && d > L.max);
        h += '<button type="button" class="bpx-d' + (d.getMonth() !== m ? ' bpx-o' : '') + (+d === +t ? ' bpx-t' : '') + (sel && +d === +sel ? ' bpx-s' : '') + '" data-day="' + iso(d) + '"' + (dis ? ' disabled' : '') + '>' + d.getDate() + '</button>';
      }
      h += '</div><div class="bpx-f"><button type="button" data-today>Today</button>' + (inp.required ? '' : '<button type="button" data-clear>Clear</button>') + '</div>';
    } else if (mode === 'y') {
      var y0 = y - (y % 12);
      h += '<div class="bpx-h"><button type="button" class="bpx-nav" data-ynav="-12">' + ARL + '</button><span class="bpx-ttl">' + y0 + ' – ' + (y0 + 11) + '</span><button type="button" class="bpx-nav" data-ynav="12">' + ARR + '</button></div><div class="bpx-yg">';
      for (var k = 0; k < 12; k++) h += '<button type="button" data-year="' + (y0 + k) + '"' + ((sel ? sel.getFullYear() : t.getFullYear()) === y0 + k ? ' class="bpx-s"' : '') + '>' + (y0 + k) + '</button>';
      h += '</div>';
    } else {
      h += '<div class="bpx-h"><span></span><button type="button" class="bpx-ttl" data-mode="y">' + y + '</button><span></span></div><div class="bpx-yg">';
      for (var j = 0; j < 12; j++) h += '<button type="button" data-month="' + j + '"' + (sel && sel.getFullYear() === y && sel.getMonth() === j ? ' class="bpx-s"' : '') + '>' + MON[j].slice(0, 3) + '</button>';
      h += '</div>';
    }
    pop.innerHTML = h;
    placePop();
  }

  function tmin(v) {
    var m = /^(\d\d):(\d\d)/.exec(v || '');
    return m ? (+m[1]) * 60 + (+m[2]) : null;
  }

  function drawT() {
    var inp = dcur, v = tmin(inp.value), mn = tmin(inp.getAttribute('min')), mx = tmin(inp.getAttribute('max'));
    var L = [];
    for (var k = 0; k < 48; k++) L.push(k * 30);
    if (v != null && L.indexOf(v) < 0) { L.push(v); L.sort(function (a, b) { return a - b; }); }
    pop.innerHTML = '<div class="bpx-tl" role="listbox">' + L.map(function (x) {
      var s = p2(Math.floor(x / 60)) + ':' + p2(x % 60);
      var dis = (mn != null && x < mn) || (mx != null && x > mx);
      return '<div class="bpx-to' + (x === v ? ' bpx-s' : '') + (dis ? ' bpx-dis' : '') + '" role="option" data-time="' + s + '">' + s + '</div>';
    }).join('') + '</div>';
    placePop();
    var s = pop.querySelector('.bpx-s') || pop.querySelector('[data-time="09:00"]');
    if (s) {
      var tl = pop.querySelector('.bpx-tl');
      tl.scrollTop = s.offsetTop - tl.clientHeight / 2 + s.offsetHeight / 2;
    }
  }

  function dopen(inp) {
    if (inp.disabled) return;
    dclose();
    dcur = inp;
    inp.__bpx.setAttribute('aria-expanded', 'true');
    pop.hidden = false;
    if (inp.__bpxT === 'time') {
      drawT();
    } else {
      mode = 'd';
      var s = pd(inp.value), L = lim(inp);
      var base = s || today();
      if (!s && L.min && base < L.min) base = L.min;
      view = new Date(base.getFullYear(), base.getMonth(), 1);
      drawD();
    }
  }

  function dclose() {
    if (!dcur) return;
    dcur.__bpx.setAttribute('aria-expanded', 'false');
    dcur = null;
    pop.hidden = true;
    pop.innerHTML = '';
  }

  function dset(v) {
    var inp = dcur;
    if (!inp) return;
    var ch = inp.value !== v;
    inp.value = v;
    dsync(inp);
    dclose();
    inp.__bpx.focus();
    if (ch) {
      inp.dispatchEvent(new Event('input', { bubbles: true }));
      inp.dispatchEvent(new Event('change', { bubbles: true }));
    }
  }

  document.addEventListener('click', function (e) {
    var b = e.target.closest('.bpx-btn');
    if (b && b.__inp) {
      e.preventDefault();
      e.stopPropagation();
      if (dcur === b.__inp) dclose();
      else dopen(b.__inp);
      return;
    }
    if (pop.contains(e.target)) {
      e.preventDefault();
      e.stopPropagation();
      var t = e.target.closest('button,[data-time]');
      if (!t || t.disabled) return;
      if (t.hasAttribute('data-time')) {
        if (!t.classList.contains('bpx-dis')) dset(t.getAttribute('data-time'));
        return;
      }
      if (t.hasAttribute('data-day')) { dset(t.getAttribute('data-day')); return; }
      if (t.hasAttribute('data-nav')) {
        view = new Date(view.getFullYear(), view.getMonth() + +t.getAttribute('data-nav'), 1);
        drawD();
        return;
      }
      if (t.hasAttribute('data-ynav')) {
        view = new Date(view.getFullYear() + +t.getAttribute('data-ynav'), view.getMonth(), 1);
        drawD();
        return;
      }
      if (t.getAttribute('data-mode') === 'y') { mode = 'y'; drawD(); return; }
      if (t.hasAttribute('data-year')) {
        view = new Date(+t.getAttribute('data-year'), view.getMonth(), 1);
        mode = 'm';
        drawD();
        return;
      }
      if (t.hasAttribute('data-month')) {
        view = new Date(view.getFullYear(), +t.getAttribute('data-month'), 1);
        mode = 'd';
        drawD();
        return;
      }
      if (t.hasAttribute('data-today')) {
        var L = lim(dcur), d = today();
        if ((L.min && d < L.min) || (L.max && d > L.max)) {
          view = new Date(d.getFullYear(), d.getMonth(), 1);
          mode = 'd';
          drawD();
          return;
        }
        dset(iso(d));
        return;
      }
      if (t.hasAttribute('data-clear')) { dset(''); return; }
      return;
    }
    if (dcur) dclose();
  }, true);

  document.addEventListener('keydown', function (e) {
    if (!dcur) {
      var b = e.target.closest && e.target.closest('.bpx-btn');
      if (b && b.__inp && (e.key === 'ArrowDown' || e.key === 'Enter' || e.key === ' ')) {
        e.preventDefault();
        dopen(b.__inp);
      }
      return;
    }
    if (e.key === 'Escape') {
      e.preventDefault();
      e.stopPropagation();
      var i = dcur;
      dclose();
      i.__bpx.focus();
    }
  }, true);

  addEventListener('resize', function () { if (dcur) placePop(); });
  addEventListener('scroll', function (e) {
    if (dcur && !pop.contains(e.target)) placePop();
  }, true);

  (function () {
    var d = Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'value');
    if (!d || !d.set) return;
    Object.defineProperty(HTMLInputElement.prototype, 'value', {
      configurable: true,
      enumerable: d.enumerable,
      get: d.get,
      set: function (v) {
        d.set.call(this, v);
        if (this.__bpx) dsync(this);
      },
    });
  })();

  function dscan(root) {
    (root.querySelectorAll ? root : document).querySelectorAll('input[type=date],input[type=time]').forEach(denh);
    if (root.tagName === 'INPUT') denh(root);
  }

  window.bpDtScan = dscan;

  ready(function () {
    dscan(document);
    new MutationObserver(function (ms) {
      ms.forEach(function (m) {
        [].forEach.call(m.addedNodes, function (n) {
          if (n.nodeType === 1) dscan(n);
        });
      });
    }).observe(document.documentElement, { childList: true, subtree: true });
  });
})();
</script>
