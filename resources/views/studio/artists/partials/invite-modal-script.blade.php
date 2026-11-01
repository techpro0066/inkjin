<script>
(function () {
  function $$(sel, root) { return [].slice.call((root || document).querySelectorAll(sel)); }

  function setInviteSending(btn, on) {
    if (!btn) return;
    if (on) {
      if (!btn.dataset.idleHtml) btn.dataset.idleHtml = btn.innerHTML;
      btn.disabled = true;
      btn.innerHTML = '<span class="ms">hourglass_top</span>Sending...';
    } else {
      btn.disabled = false;
      if (btn.dataset.idleHtml) btn.innerHTML = btn.dataset.idleHtml;
    }
  }

  document.addEventListener('click', function (e) {
    var t = e.target.closest('#m-invite .opt');
    if (!t) return;
    var g = t.closest('[data-group]');
    if (!g || !document.getElementById('m-invite') || document.getElementById('m-invite').hidden) return;
    e.preventDefault();
    $$('.opt', g).forEach(function (o) {
      var on = o === t;
      o.classList.toggle('on', on);
      o.setAttribute('aria-checked', on ? 'true' : 'false');
      var r = o.querySelector('.rad');
      if (r) r.textContent = on ? 'radio_button_checked' : 'radio_button_unchecked';
    });
    var v = t.getAttribute('data-v');
    $$('[data-when^="' + g.getAttribute('data-group') + ':"]', g).forEach(function (x) {
      x.hidden = x.getAttribute('data-when').split(':')[1].split('|').indexOf(v) < 0;
    });
  });

  document.addEventListener('click', function (e) {
    var open = e.target.closest('[data-open="m-invite"]');
    if (!open) return;
    setInviteSending(document.getElementById('inv-send'), false);
  });

  window.bpInviteSetSending = setInviteSending;
})();
</script>
