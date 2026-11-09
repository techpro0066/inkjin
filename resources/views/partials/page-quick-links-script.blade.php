<script>
(function () {
  var wrap = document.getElementById('quickLinks');
  var btn = document.getElementById('quickLinksBtn');
  var menu = document.getElementById('quickLinksMenu');
  if (!wrap || !btn || !menu) return;

  function toast(msg) {
    if (typeof window.bpToast === 'function') {
      window.bpToast(msg);
      return;
    }
    if (typeof window.toast === 'function') {
      window.toast(msg);
      return;
    }
    var t = document.createElement('div');
    t.setAttribute('role', 'status');
    t.className = 'bpw-toast';
    t.style.cssText = 'position:fixed;top:22px;right:24px;background:#1A1A1A;color:#fff;padding:12px 18px;border-radius:10px;font-weight:600;font-size:14px;z-index:2000;box-shadow:0 10px 30px rgba(0,0,0,.22);display:flex;gap:10px;align-items:center;font-family:inherit;max-width:calc(100% - 32px)';
    t.innerHTML = '<span class="ms" style="color:#3DD68C">check_circle</span>' + (msg || '');
    document.body.appendChild(t);
    setTimeout(function () { t.remove(); }, 2400);
  }

  function openQl(show) {
    menu.hidden = !show;
    btn.setAttribute('aria-expanded', show ? 'true' : 'false');
    var cv = btn.querySelector('.cv');
    if (cv) cv.textContent = show ? 'expand_less' : 'expand_more';
  }

  btn.addEventListener('click', function (e) {
    e.stopPropagation();
    openQl(menu.hidden);
  });

  menu.addEventListener('click', function (e) {
    e.stopPropagation();
    var c = e.target.closest('[data-ql-copy]');
    if (!c) return;
    var u = c.getAttribute('data-ql-copy') || '';
    var label = c.getAttribute('data-ql-label') || u;
    try { navigator.clipboard.writeText(u).catch(function () {}); } catch (x) {}
    c.classList.add('done');
    var icon = c.querySelector('.ms');
    if (icon) icon.textContent = 'check';
    setTimeout(function () {
      c.classList.remove('done');
      if (icon) icon.textContent = 'content_copy';
    }, 1600);
    toast('Link copied: ' + label);
  });

  document.addEventListener('click', function (e) {
    if (!menu.hidden && !wrap.contains(e.target)) openQl(false);
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && !menu.hidden) {
      openQl(false);
      btn.focus();
    }
  });
})();
</script>
