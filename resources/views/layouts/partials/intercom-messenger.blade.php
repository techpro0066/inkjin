@php
  $intercomSettings = \App\Support\IntercomMessenger::artistBootSettings();
@endphp

@if ($intercomSettings)
<style>
  #intercomCustomCloseBtn {
    display: none;
    position: fixed;
    bottom: 100px;
    right: 24px;
    z-index: 2147483001;
    background: #ffffff;
    color: #1c1b21;
    border: 1px solid rgba(202, 196, 211, 0.5);
    border-radius: 9999px;
    width: 40px;
    height: 40px;
    font-size: 20px;
    line-height: 1;
    cursor: pointer;
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.12);
  }
  #intercomCustomCloseBtn:hover {
    background: #f8f1fb;
  }
</style>

<button type="button" id="intercomCustomCloseBtn" title="Close chat" aria-label="Close chat">&times;</button>

<script>
  window.intercomSettings = @json($intercomSettings);

  (function () {
    var w = window;
    var ic = w.Intercom;
    if (typeof ic === 'function') {
      ic('reattach_activator');
      ic('update', w.intercomSettings);
    } else {
      var d = document;
      var i = function () { i.c(arguments); };
      i.q = [];
      i.c = function (args) { i.q.push(args); };
      w.Intercom = i;
      var l = function () {
        var s = d.createElement('script');
        s.type = 'text/javascript';
        s.async = true;
        s.src = 'https://widget.intercom.io/widget/' + encodeURIComponent(window.intercomSettings.app_id);
        var x = d.getElementsByTagName('script')[0];
        x.parentNode.insertBefore(s, x);
      };
      if (document.readyState === 'complete') {
        l();
      } else if (w.attachEvent) {
        w.attachEvent('onload', l);
      } else {
        w.addEventListener('load', l, false);
      }
    }
  })();

  (function () {
    var getHelpBtns = document.querySelectorAll('[data-intercom-get-help]');
    var closeBtn = document.getElementById('intercomCustomCloseBtn');

    function setHelpActive(isActive) {
      getHelpBtns.forEach(function (btn) {
        btn.classList.toggle('active', !!isActive);
        btn.setAttribute('aria-expanded', isActive ? 'true' : 'false');
      });
    }

    function showMessenger(e) {
      if (e) e.preventDefault();
      if (typeof window.Intercom !== 'function') return;
      window.Intercom('show');
      if (typeof closeMobileNav === 'function' && window.matchMedia('(max-width: 1023px)').matches) {
        closeMobileNav();
      }
    }

    function hideMessenger(e) {
      if (e) e.preventDefault();
      if (typeof window.Intercom !== 'function') return;
      window.Intercom('hide');
      if (closeBtn) closeBtn.style.display = 'none';
      setHelpActive(false);
    }

    getHelpBtns.forEach(function (btn) {
      btn.addEventListener('click', showMessenger);
    });

    if (closeBtn) {
      closeBtn.addEventListener('click', hideMessenger);
    }

    // Queued on the Intercom stub until the real widget loads.
    window.Intercom('onShow', function () {
      if (closeBtn) closeBtn.style.display = 'block';
      setHelpActive(true);
    });
    window.Intercom('onHide', function () {
      if (closeBtn) closeBtn.style.display = 'none';
      setHelpActive(false);
    });
  })();
</script>
@endif
