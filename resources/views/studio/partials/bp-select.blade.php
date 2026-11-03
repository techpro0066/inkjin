{{-- Bookpay custom select (matches S04b BP-SELECT dropdown design) --}}
<script>
(function () {
  if (window.__bps) return;
  window.__bps = 1;

  var css = document.createElement('style');
  css.textContent =
    'select.bps-native{position:absolute!important;opacity:0!important;pointer-events:none!important;width:1px!important;height:1px!important;min-height:0!important;padding:0!important;margin:0!important;border:0!important;overflow:hidden!important}'
    + '.bps-btn{display:flex!important;align-items:center;justify-content:space-between;gap:8px;text-align:left;cursor:pointer;font:inherit;color:inherit;box-sizing:border-box;-webkit-tap-highlight-color:transparent}'
    + '.bps-btn.bps-plain{border:0;background:none;padding:10px 0;width:100%;font-size:14px;color:#1A1A1A;min-width:0}'
    + '.bps-btn.in{width:100%;background:#fff;border:1px solid var(--line,#E8DFEA);border-radius:10px;padding:10px 13px;font-size:13.5px;color:#1A1A1A;min-height:40px}'
    + '.bps-btn.in:focus-visible{outline:0;border-color:#3E007C;box-shadow:0 0 0 3px #F3E8FF}'
    + '.bps-btn .bps-t{flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}'
    + '.bps-btn .bps-t.bps-ph{color:#8F8A95}'
    + '.bps-btn .bps-c{flex:none;width:18px;height:18px;opacity:.6;transition:transform .15s}'
    + '.bps-btn[aria-expanded="true"] .bps-c{transform:rotate(180deg)}'
    + '.bps-btn:disabled,.bps-btn[aria-disabled="true"]{cursor:default;opacity:.6}'
    + '.bps-btn:focus-visible{outline:2px solid #3E007C;outline-offset:2px}'
    + '.bps-menu{position:fixed;z-index:1200;background:#fff;border:1px solid #EAE2EE;border-radius:12px;box-shadow:0 14px 40px rgba(20,0,40,.18);padding:6px;overflow:auto;font-size:14px;color:#1A1A1A;-webkit-overflow-scrolling:touch}'
    + '.bps-menu[hidden]{display:none!important}'
    + '.bps-o{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:8px;cursor:pointer;line-height:1.3}'
    + '.bps-o:hover,.bps-o.bps-a{background:#F7F3F9}'
    + '.bps-o.bps-s{background:#F3E8FF;color:#3E007C;font-weight:700}'
    + '.bps-o.bps-s:hover,.bps-o.bps-s.bps-a{background:#F3E8FF}'
    + '.bps-o .bps-k{margin-left:auto;flex:none;width:18px;height:18px;visibility:hidden}'
    + '.bps-o.bps-s .bps-k{visibility:visible}'
    + '.bps-o.bps-d{opacity:.4;cursor:default}.bps-o.bps-d:hover{background:none}'
    + '@media(max-width:600px){.bps-o{padding:12px 12px;font-size:15px}}';
  document.head.appendChild(css);

  var CHEV = '<svg class="bps-c" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>';
  var CHECK = '<svg class="bps-k" viewBox="0 0 24 24" fill="none" stroke="#3E007C" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>';

  var menu = document.createElement('div');
  menu.className = 'bps-menu';
  menu.hidden = true;
  menu.setAttribute('role', 'listbox');

  function ready(f) {
    if (document.body) f();
    else document.addEventListener('DOMContentLoaded', f);
  }
  ready(function () { document.body.appendChild(menu); });

  var cur = null;
  var act = -1;

  function esc(s) {
    return String(s).replace(/[&<>"]/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c];
    });
  }

  function sync(sel) {
    var b = sel.__bps;
    if (!b) return;
    var o = sel.options[sel.selectedIndex];
    var t = o ? o.text : '';
    var ph = !o || (o.value === '' && sel.selectedIndex === 0 && sel.options.length > 1);
    var sp = b.querySelector('.bps-t');
    sp.textContent = t;
    sp.classList.toggle('bps-ph', ph);
    b.disabled = sel.disabled;
    b.setAttribute('aria-label', sel.getAttribute('aria-label') || t);
    b.hidden = sel.hidden;
    if (sel.style.display === 'none') b.style.display = 'none';
  }

  function enhance(sel) {
    if (sel.__bps || sel.multiple || (sel.size && sel.size > 1) || sel.closest('.bps-skip')) return;
    var nx = sel.nextElementSibling;
    if (nx && nx.classList.contains('bps-btn') && !nx.__sel) nx.remove();
    sel.classList.remove('bps-native');

    var b = document.createElement('button');
    b.type = 'button';
    var cls = (sel.getAttribute('class') || '').trim();
    b.className = (cls ? cls + ' ' : 'bps-plain ') + 'bps-btn';
    var st = sel.getAttribute('style');
    if (st) b.setAttribute('style', st.replace(/appearance:[^;]*;?/g, ''));
    if (sel.id) b.setAttribute('data-for', sel.id);
    b.setAttribute('aria-haspopup', 'listbox');
    b.setAttribute('aria-expanded', 'false');
    b.innerHTML = '<span class="bps-t"></span>' + CHEV;

    sel.parentNode.insertBefore(b, sel.nextSibling);
    sel.classList.add('bps-native');
    sel.setAttribute('tabindex', '-1');
    sel.setAttribute('aria-hidden', 'true');
    sel.__bps = b;
    b.__sel = sel;
    sync(sel);

    new MutationObserver(function () { sync(sel); }).observe(sel, {
      attributes: true,
      attributeFilter: ['disabled', 'hidden', 'style', 'aria-label'],
      childList: true,
      subtree: true,
      characterData: true
    });
    sel.addEventListener('change', function () { sync(sel); });
    sel.addEventListener('focus', function () { b.focus(); });
  }

  function place() {
    if (!cur) return;
    var b = cur.__bps;
    var r = b.getBoundingClientRect();
    var vw = innerWidth;
    var vh = innerHeight;
    var w = Math.max(r.width, 180);
    w = Math.min(w, vw - 16);
    menu.style.minWidth = w + 'px';
    menu.style.maxWidth = (vw - 16) + 'px';
    var below = vh - r.bottom - 12;
    var above = r.top - 12;
    var h = Math.min(menu.scrollHeight, 320);
    var up = below < Math.min(h, 200) && above > below;
    var mh = Math.min(320, up ? above : below);
    menu.style.maxHeight = Math.max(120, mh) + 'px';
    var left = Math.min(Math.max(8, r.left), vw - 8 - Math.max(w, menu.offsetWidth));
    menu.style.left = left + 'px';
    if (up) {
      menu.style.top = 'auto';
      menu.style.bottom = (vh - r.top + 6) + 'px';
    } else {
      menu.style.bottom = 'auto';
      menu.style.top = (r.bottom + 6) + 'px';
    }
  }

  function open(sel) {
    if (sel.disabled) return;
    close();
    cur = sel;
    var b = sel.__bps;
    b.setAttribute('aria-expanded', 'true');
    menu.innerHTML = [].map.call(sel.options, function (o, i) {
      return '<div class="bps-o'
        + (i === sel.selectedIndex ? ' bps-s' : '')
        + (o.disabled ? ' bps-d' : '')
        + '" role="option" data-i="' + i + '" aria-selected="' + (i === sel.selectedIndex) + '">'
        + '<span>' + esc(o.text) + '</span>' + CHECK + '</div>';
    }).join('');
    menu.hidden = false;
    act = sel.selectedIndex;
    hl();
    place();
    var s = menu.querySelector('.bps-s');
    if (s) s.scrollIntoView({ block: 'nearest' });
  }

  function close() {
    if (!cur) return;
    cur.__bps.setAttribute('aria-expanded', 'false');
    cur = null;
    menu.hidden = true;
    menu.innerHTML = '';
  }

  function pick(i) {
    var sel = cur;
    if (!sel) return;
    var o = sel.options[i];
    if (!o || o.disabled) return;
    var ch = sel.selectedIndex !== i;
    sel.selectedIndex = i;
    sync(sel);
    close();
    sel.__bps.focus();
    if (ch) {
      sel.dispatchEvent(new Event('input', { bubbles: true }));
      sel.dispatchEvent(new Event('change', { bubbles: true }));
    }
  }

  function hl() {
    [].forEach.call(menu.children, function (c, i) {
      c.classList.toggle('bps-a', i === act);
    });
    var a = menu.children[act];
    if (a) a.scrollIntoView({ block: 'nearest' });
  }

  document.addEventListener('click', function (e) {
    var b = e.target.closest('.bps-btn');
    if (b && b.__sel) {
      e.preventDefault();
      if (cur === b.__sel) close();
      else open(b.__sel);
      return;
    }
    var o = e.target.closest('.bps-o');
    if (o && menu.contains(o)) {
      e.preventDefault();
      e.stopPropagation();
      pick(+o.getAttribute('data-i'));
      return;
    }
    if (cur && !menu.contains(e.target)) close();
  }, true);

  document.addEventListener('keydown', function (e) {
    var b = e.target.closest && e.target.closest('.bps-btn');
    if (!cur) {
      if (b && b.__sel && (e.key === 'ArrowDown' || e.key === 'ArrowUp' || e.key === ' ' || e.key === 'Enter')) {
        e.preventDefault();
        open(b.__sel);
      }
      return;
    }
    var n = cur.options.length;
    if (e.key === 'Escape') {
      e.preventDefault();
      e.stopPropagation();
      var s = cur;
      close();
      s.__bps.focus();
    } else if (e.key === 'ArrowDown') {
      e.preventDefault();
      do { act = Math.min(n - 1, act + 1); } while (act < n - 1 && cur.options[act].disabled);
      hl();
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      do { act = Math.max(0, act - 1); } while (act > 0 && cur.options[act].disabled);
      hl();
    } else if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault();
      pick(act);
    } else if (e.key === 'Tab') {
      close();
    }
  }, true);

  addEventListener('resize', function () { if (cur) place(); });
  addEventListener('scroll', function (e) {
    if (cur && !menu.contains(e.target)) place();
  }, true);

  ['value', 'selectedIndex'].forEach(function (p) {
    var d = Object.getOwnPropertyDescriptor(HTMLSelectElement.prototype, p);
    if (!d || !d.set) return;
    Object.defineProperty(HTMLSelectElement.prototype, p, {
      configurable: true,
      enumerable: d.enumerable,
      get: d.get,
      set: function (v) {
        d.set.call(this, v);
        if (this.__bps) sync(this);
      }
    });
  });

  function scan(root) {
    (root || document).querySelectorAll('select:not(.bps-skip)').forEach(enhance);
  }

  ready(function () {
    scan(document);
    if (window.MutationObserver) {
      new MutationObserver(function (muts) {
        muts.forEach(function (m) {
          m.addedNodes && m.addedNodes.forEach(function (n) {
            if (n.nodeType !== 1) return;
            if (n.matches && n.matches('select')) enhance(n);
            else if (n.querySelectorAll) scan(n);
          });
        });
      }).observe(document.documentElement, { childList: true, subtree: true });
    }
  });

  window.bpSelectEnhance = scan;
  window.bpSelectClose = close;
})();
</script>
