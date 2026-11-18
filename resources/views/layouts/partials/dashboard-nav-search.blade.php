@php
  $navSearchItems = $navSearchItems ?? [];
  $navSearchTry = $navSearchTry ?? [];
  $navSearchRecentKey = $navSearchRecentKey ?? 'bp-nav-search-recent';
  $navSearchAccountDeactivated = (bool) ($navSearchAccountDeactivated ?? false);
@endphp
<div class="navsearch" data-deactivated="{{ $navSearchAccountDeactivated ? '1' : '0' }}" data-recent-key="{{ $navSearchRecentKey }}">
  <label class="navsearch-box">
    <span class="ms" aria-hidden="true">search</span>
    <input type="search" placeholder="Search or jump to…" aria-label="Search the dashboard" autocomplete="off" spellcheck="false" maxlength="100">
  </label>
  <div class="navsearch-panel" hidden role="listbox" aria-label="Search results">
    <div class="navsearch-results"></div>
    <div class="navsearch-foot" aria-hidden="true">
      <span><span class="kbd">↑</span><span class="kbd">↓</span> to move</span>
      <span><span class="kbd">Enter</span> to open</span>
      <span><span class="kbd">Esc</span> to close</span>
    </div>
  </div>
</div>
<style>
  .navsearch{position:relative;margin:16px 0 0;flex-shrink:0;z-index:60}
  .navsearch-box{display:flex;align-items:center;gap:8px;background:#2A2A2A;border:1px solid #3A3A3A;border-radius:10px;padding:0 10px;min-height:38px;position:relative;z-index:2}
  .navsearch-box:focus-within{border-color:#6B4A9A;box-shadow:0 0 0 3px rgba(62,0,124,.35)}
  .navsearch-box .ms{font-size:18px;color:#9A929E}
  .navsearch-box input{flex:1;min-width:0;border:0;outline:0;background:transparent;color:#fff;font:inherit;font-size:13.5px;padding:8px 0}
  .navsearch-box input::placeholder{color:#8C8C8C}
  .navsearch-box input::-webkit-search-cancel-button{cursor:pointer}
  .navsearch-panel{
    position:fixed;width:min(440px,calc(100vw - 24px));
    background:#fff;color:#1A1A1A;border-radius:14px;box-shadow:0 16px 42px rgba(20,0,40,.28);
    z-index:1200;overflow:hidden;display:flex;flex-direction:column;max-height:min(420px,70vh)
  }
  .navsearch-panel[hidden]{display:none!important}
  .navsearch-results{overflow:auto;padding:8px;flex:1;min-height:0;background:#fff}
  .navsearch-group{font-size:10.5px;font-weight:700;letter-spacing:.8px;text-transform:uppercase;color:#9A929E;padding:8px 10px 4px}
  .navsearch-results a,.navsearch-results button.navsearch-row{
    display:flex;align-items:flex-start;gap:10px;width:100%;padding:9px 10px;border-radius:10px;
    text-decoration:none;color:#1A1A1A;border:0;background:transparent;font:inherit;text-align:left;cursor:pointer
  }
  .navsearch-results a.on,.navsearch-results a:hover,
  .navsearch-results button.navsearch-row.on,.navsearch-results button.navsearch-row:hover{background:#F3E8FF}
  .navsearch-results .ico{
    width:22px;height:22px;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;color:#6F6874
  }
  .navsearch-results .ico .ms{font-size:18px}
  .navsearch-results .txt{min-width:0;flex:1}
  .navsearch-results b{display:block;font-size:13.5px;font-weight:700;line-height:1.25}
  .navsearch-results small{display:block;color:#6F6874;font-size:11.5px;margin-top:2px;line-height:1.35}
  .navsearch-results a.on small,.navsearch-results button.navsearch-row.on small{color:#7A5A9A}
  .navsearch-tag{
    flex-shrink:0;margin-top:1px;font-size:10.5px;font-weight:700;color:#7A1FC4;background:#F3E8FF;
    border-radius:999px;padding:2px 7px
  }
  .navsearch-empty{padding:14px 12px 8px}
  .navsearch-empty b{display:block;font-size:13.5px;font-weight:700;margin-bottom:4px}
  .navsearch-empty p{margin:0;color:#6F6874;font-size:12.5px;line-height:1.45}
  .navsearch-foot{
    display:flex;flex-wrap:nowrap;gap:12px;align-items:center;justify-content:flex-start;
    padding:9px 12px;border-top:1px solid #EFE8F2;background:#FAF7FB;
    font-size:11.5px;color:#8A8490;white-space:nowrap;flex-shrink:0
  }
  .navsearch-foot .kbd{
    display:inline-flex;align-items:center;justify-content:center;min-width:18px;height:18px;
    padding:0 5px;margin-right:2px;border:1px solid #E0D6E6;border-radius:5px;
    background:#fff;color:#5A5460;font-size:10.5px;font-weight:700;line-height:1
  }
  @media (max-width:900px){
    .navsearch{z-index:30}
    .navsearch-panel{
      position:static;width:100%;max-width:none;margin-top:8px;max-height:min(360px,50vh);
      box-shadow:none;border:1px solid #E8E1EC;z-index:auto;left:auto!important;top:auto!important
    }
    .navsearch-foot{flex-wrap:wrap;white-space:normal}
  }
</style>
<script>
(function () {
  var items = @json($navSearchItems);
  var tryItems = @json($navSearchTry);
  var root = document.currentScript.previousElementSibling;
  while (root && !(root.classList && root.classList.contains('navsearch'))) {
    root = root.previousElementSibling;
  }
  if (!root) return;

  var input = root.querySelector('input');
  var panel = root.querySelector('.navsearch-panel');
  var list = root.querySelector('.navsearch-results');
  var recentKey = root.getAttribute('data-recent-key') || 'bp-nav-search-recent';
  var deactivated = root.getAttribute('data-deactivated') === '1';
  var active = 0;
  var rows = [];

  function esc(s) {
    return String(s == null ? '' : s)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }

  function norm(value) {
    return String(value || '')
      .toLowerCase()
      .replace(/[^a-z0-9\s€]/g, ' ')
      .replace(/\s+/g, ' ')
      .trim();
  }

  function visible(item) {
    var when = item.when || '';
    if (when === 'account_active' && deactivated) return false;
    if (when === 'account_deactivated' && !deactivated) return false;
    return true;
  }

  function loadRecent() {
    try {
      var raw = JSON.parse(localStorage.getItem(recentKey) || '[]');
      if (!Array.isArray(raw)) return [];
      return raw.filter(function (r) { return r && r.label && r.url; }).slice(0, 4);
    } catch (e) {
      return [];
    }
  }

  function saveRecent(item) {
    if (!item || item.type === 'live' || item.type === 'ask') return;
    var next = [{ label: item.label, section: item.section || '', url: item.url, icon: item.icon || 'arrow_forward' }];
    loadRecent().forEach(function (r) {
      if (r.label !== item.label) next.push(r);
    });
    try { localStorage.setItem(recentKey, JSON.stringify(next.slice(0, 4))); } catch (e) {}
  }

  function scoreItem(item, q) {
    // Match account-state.js scoring so artist words like "google", "rules", "waitlist" hit.
    var name = norm(item.label);
    var where = norm(item.section || '');
    var keys = norm(item.keys || '');
    if (!q) return 0;
    var score = 0;
    if (name === q) score += 100;
    else if (name.indexOf(q) === 0) score += 60;
    else if (name.indexOf(q) !== -1) score += 40;

    var words = q.split(' ').filter(Boolean);
    for (var i = 0; i < words.length; i++) {
      var w = words[i];
      if (!w) continue;
      if (name.indexOf(w) !== -1) score += 12;
      else if (keys.indexOf(' ' + w) !== -1 || keys.indexOf(w) === 0) score += 9;
      else if (w.length > 3 && keys.indexOf(w) !== -1) score += 5;
      else if (where.indexOf(w) !== -1) score += 2;
      else return 0;
    }
    return score;
  }

  function iconFor(item) {
    if (item.icon) return item.icon;
    if (item.type === 'action') return 'arrow_forward';
    if (item.type === 'help') return 'help';
    if (item.type === 'ask') return 'support_agent';
    return 'description';
  }

  function rowHtml(item, i) {
    var tag = item.type === 'action' ? '<span class="navsearch-tag">Do it</span>' : '';
    var section = item.section ? '<small>' + esc(item.section) + '</small>' : '';
    var ask = item.type === 'ask';
    var tagName = ask ? 'button' : 'a';
    var attrs = ask
      ? 'type="button" class="navsearch-row' + (i === 0 ? ' on' : '') + '" data-ask="1"'
      : 'href="' + esc(item.url) + '" class="' + (i === 0 ? 'on' : '') + '"';
    return '<' + tagName + ' ' + attrs + ' role="option" data-i="' + i + '">' +
      '<span class="ico"><span class="ms">' + esc(iconFor(item)) + '</span></span>' +
      '<span class="txt"><b>' + esc(item.label) + '</b>' + section + '</span>' +
      tag +
      '</' + tagName + '>';
  }

  function groupHtml(title, groupRows, startIndex) {
    if (!groupRows.length) return { html: '', next: startIndex };
    var html = '<div class="navsearch-group">' + esc(title) + '</div>';
    groupRows.forEach(function (item, idx) {
      html += rowHtml(item, startIndex + idx);
    });
    return { html: html, next: startIndex + groupRows.length };
  }

  function openAsk(text) {
    var prefill = text ? ('I need help with: ' + text) : '';
    if (typeof window.Intercom === 'function' && window.intercomSettings) {
      if (prefill) window.Intercom('showNewMessage', prefill);
      else window.Intercom('show');
      return;
    }
    var help = document.querySelector('[data-intercom-get-help]');
    if (help) help.click();
    else window.open('https://help.inkjin.com', '_blank', 'noopener');
  }

  function closePanel() {
    panel.hidden = true;
    rows = [];
    active = 0;
  }

  function positionPanel() {
    if (window.matchMedia('(max-width:900px)').matches) {
      panel.style.left = '';
      panel.style.top = '';
      panel.style.width = '';
      return;
    }
    var box = root.querySelector('.navsearch-box');
    if (!box) return;
    var rect = box.getBoundingClientRect();
    var width = Math.min(440, window.innerWidth - 24);
    var left = Math.min(rect.left, window.innerWidth - width - 12);
    left = Math.max(12, left);
    var top = rect.bottom + 6;
    var maxH = Math.min(420, window.innerHeight - top - 12);
    panel.style.left = left + 'px';
    panel.style.top = top + 'px';
    panel.style.width = width + 'px';
    panel.style.maxHeight = Math.max(180, maxH) + 'px';
  }

  function openPanel() {
    panel.hidden = false;
    positionPanel();
  }

  function setActive(i) {
    rows = Array.prototype.slice.call(list.querySelectorAll('a, button.navsearch-row'));
    if (!rows.length) { active = 0; return; }
    active = (i + rows.length) % rows.length;
    rows.forEach(function (el, idx) { el.classList.toggle('on', idx === active); });
    rows[active].scrollIntoView({ block: 'nearest' });
  }

  function choose(el) {
    if (!el) return;
    if (el.getAttribute('data-ask') === '1') {
      closePanel();
      openAsk(norm(input.value));
      input.value = '';
      return;
    }
    var href = el.getAttribute('href');
    if (!href) return;
    var labelEl = el.querySelector('b');
    var sectionEl = el.querySelector('small');
    saveRecent({
      label: labelEl ? labelEl.textContent : '',
      section: sectionEl ? sectionEl.textContent : '',
      url: href,
      icon: (el.querySelector('.ico .ms') || {}).textContent || 'arrow_forward'
    });
    input.value = '';
    closePanel();
    window.location.href = href;
  }

  function renderEmpty() {
    rows = [];
    var html = '';
    var idx = 0;
    var recent = loadRecent();
    var g = groupHtml('Recent', recent, idx);
    html += g.html; idx = g.next;

    var tryRows = (tryItems || []).filter(visible);
    g = groupHtml('Try', tryRows, idx);
    html += g.html; idx = g.next;

    if (!html) {
      html = '<div class="navsearch-empty"><b>Search the dashboard</b><p>Jump to pages, settings and actions.</p></div>';
    }
    list.innerHTML = html;
    openPanel();
    setActive(0);
  }

  function renderQuery(q) {
    var scored = items
      .filter(visible)
      .map(function (item) { return { item: item, score: scoreItem(item, q) }; })
      .filter(function (x) { return x.score > 0; })
      .sort(function (a, b) { return b.score - a.score; });

    var pages = [];
    var help = [];
    scored.forEach(function (x) {
      if (x.item.type === 'help') help.push(x.item);
      else pages.push(x.item);
    });
    pages = pages.slice(0, 5);
    help = help.slice(0, 2);

    var html = '';
    var idx = 0;
    if (!pages.length && !help.length) {
      html += '<div class="navsearch-empty"><b>Nothing found for “' + esc(q) + '”</b><p>Ask us and we will point you to it.</p></div>';
    } else {
      var g = groupHtml('Settings and pages', pages, idx);
      html += g.html; idx = g.next;
      g = groupHtml('Help', help, idx);
      html += g.html; idx = g.next;
    }

    var ask = {
      type: 'ask',
      label: 'Ask us about “' + q + '”',
      section: 'Opens Get Help with your words filled in',
      icon: 'support_agent'
    };
    html += rowHtml(ask, idx);
    list.innerHTML = html;
    openPanel();
    setActive(0);
  }

  function render() {
    var q = norm(input.value).slice(0, 100);
    if (!q) renderEmpty();
    else renderQuery(q);
  }

  input.addEventListener('input', render);
  input.addEventListener('focus', function () { render(); });
  input.addEventListener('keydown', function (e) {
    if (panel.hidden && (e.key === 'ArrowDown' || e.key === 'Enter')) {
      render();
    }
    if (e.key === 'ArrowDown') { e.preventDefault(); setActive(active + 1); }
    else if (e.key === 'ArrowUp') { e.preventDefault(); setActive(active - 1); }
    else if (e.key === 'Escape') {
      e.preventDefault();
      input.value = '';
      closePanel();
      input.blur();
    } else if (e.key === 'Enter') {
      e.preventDefault();
      choose(rows[active] || list.querySelector('a, button.navsearch-row'));
    }
  });

  list.addEventListener('mousemove', function (e) {
    var row = e.target.closest('a, button.navsearch-row');
    if (!row || !list.contains(row)) return;
    var i = rows.indexOf(row);
    if (i >= 0) setActive(i);
  });
  list.addEventListener('click', function (e) {
    var row = e.target.closest('a, button.navsearch-row');
    if (!row || !list.contains(row)) return;
    e.preventDefault();
    choose(row);
  });

  document.addEventListener('click', function (e) {
    if (!root.contains(e.target) && !panel.contains(e.target)) closePanel();
  });

  window.addEventListener('resize', function () {
    if (!panel.hidden) positionPanel();
  });
  window.addEventListener('scroll', function () {
    if (!panel.hidden) positionPanel();
  }, true);

  document.addEventListener('keydown', function (e) {
    if (e.key !== '/' || e.ctrlKey || e.metaKey || e.altKey) return;
    var tag = (e.target && e.target.tagName || '').toLowerCase();
    if (tag === 'input' || tag === 'textarea' || tag === 'select' || (e.target && e.target.isContentEditable)) return;
    e.preventDefault();
    input.focus();
    render();
  });
})();
</script>
