@php
  $navSearchItems = $navSearchItems ?? [];
@endphp
<div class="navsearch">
  <label class="navsearch-box">
    <span class="ms" aria-hidden="true">search</span>
    <input type="search" placeholder="Search" aria-label="Search the dashboard" autocomplete="off" spellcheck="false">
  </label>
  <div class="navsearch-results" hidden></div>
</div>
<style>
  .navsearch{position:relative;margin:16px 0 0;flex-shrink:0}
  .navsearch-box{display:flex;align-items:center;gap:8px;background:#2A2A2A;border:1px solid #3A3A3A;border-radius:10px;padding:0 10px;min-height:38px}
  .navsearch-box:focus-within{border-color:#6B4A9A;box-shadow:0 0 0 3px rgba(62,0,124,.35)}
  .navsearch-box .ms{font-size:18px;color:#9A929E}
  .navsearch-box input{flex:1;min-width:0;border:0;outline:0;background:transparent;color:#fff;font:inherit;font-size:13.5px;padding:8px 0}
  .navsearch-box input::placeholder{color:#8C8C8C}
  .navsearch-box input::-webkit-search-cancel-button{cursor:pointer}
  .navsearch-results{position:absolute;left:0;right:0;top:calc(100% + 6px);background:#fff;color:#1A1A1A;border-radius:12px;box-shadow:0 14px 36px rgba(0,0,0,.28);max-height:min(320px,50vh);overflow:auto;z-index:50;padding:6px}
  .navsearch-results[hidden]{display:none}
  .navsearch-results a{display:block;padding:8px 10px;border-radius:8px;text-decoration:none;color:#1A1A1A}
  .navsearch-results a.on,.navsearch-results a:hover{background:#F3E8FF}
  .navsearch-results b{display:block;font-size:13.5px;font-weight:700}
  .navsearch-results small{display:block;color:#6F6874;font-size:11.5px;margin-top:1px}
  .navsearch-empty{padding:10px;color:#6F6874;font-size:13px}
  aside nav a.nav-hide,aside nav button.navlink.nav-hide{display:none!important}
</style>
<script>
(function () {
  var items = @json($navSearchItems);
  var root = document.currentScript.previousElementSibling;
  while (root && !(root.classList && root.classList.contains('navsearch'))) {
    root = root.previousElementSibling;
  }
  if (!root) return;
  var input = root.querySelector('input');
  var list = root.querySelector('.navsearch-results');
  var active = 0;

  function sidebarNav() {
    var aside = root.closest('aside');
    return aside ? aside.querySelector('nav') : null;
  }

  function norm(value) {
    return String(value || '').toLowerCase().replace(/\s+/g, ' ').trim();
  }

  function matches(item, q) {
    return norm(item.label + ' ' + (item.section || '') + ' ' + (item.keys || '')).indexOf(q) !== -1;
  }

  function filterNav(q) {
    var nav = sidebarNav();
    if (!nav) return;
    nav.querySelectorAll(':scope > a, :scope > button.navlink').forEach(function (link) {
      var text = norm(link.textContent);
      link.classList.toggle('nav-hide', !!q && text.indexOf(q) === -1);
    });
  }

  function render(q) {
    list.innerHTML = '';
    if (!q) {
      list.hidden = true;
      filterNav('');
      return;
    }
    filterNav(q);
    var hits = items.filter(function (item) { return matches(item, q); }).slice(0, 8);
    if (!hits.length) {
      list.hidden = false;
      list.innerHTML = '<div class="navsearch-empty">No matches</div>';
      return;
    }
    active = 0;
    hits.forEach(function (item, i) {
      var a = document.createElement('a');
      a.href = item.url;
      if (i === 0) a.className = 'on';
      var title = document.createElement('b');
      title.textContent = item.label;
      a.appendChild(title);
      if (item.section) {
        var sub = document.createElement('small');
        sub.textContent = item.section;
        a.appendChild(sub);
      }
      list.appendChild(a);
    });
    list.hidden = false;
  }

  function move(step) {
    var links = list.querySelectorAll('a');
    if (!links.length) return;
    active = (active + step + links.length) % links.length;
    links.forEach(function (link, i) { link.classList.toggle('on', i === active); });
    links[active].scrollIntoView({ block: 'nearest' });
  }

  input.addEventListener('input', function () { render(norm(input.value)); });
  input.addEventListener('keydown', function (e) {
    if (e.key === 'ArrowDown') { e.preventDefault(); move(1); }
    else if (e.key === 'ArrowUp') { e.preventDefault(); move(-1); }
    else if (e.key === 'Escape') { input.value = ''; render(''); input.blur(); }
    else if (e.key === 'Enter') {
      var current = list.querySelector('a.on') || list.querySelector('a');
      if (current) { e.preventDefault(); window.location.href = current.href; }
    }
  });
  document.addEventListener('click', function (e) {
    if (!root.contains(e.target)) list.hidden = true;
  });
  input.addEventListener('focus', function () {
    if (norm(input.value)) render(norm(input.value));
  });
})();
</script>
