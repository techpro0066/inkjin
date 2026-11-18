/* Deactivate account (16 Nov 2026). See 21-ACCOUNT-DEACTIVATION.md.
   Mockup only: the state lives in sessionStorage under bp-deactivated.
   In the product it is users.deactivated_at on the artist's account. */
(function () {
  var K = 'bp-deactivated';
  function isOff() { try { return sessionStorage.getItem(K) === '1'; } catch (e) { return false; } }
  function setOff(v) { try { v ? sessionStorage.setItem(K, '1') : sessionStorage.removeItem(K); } catch (e) {} }
  function flash(m) { try { sessionStorage.setItem('bp-dx-toast', m); } catch (e) {} }
  var path = location.pathname, file = path.split('/').pop() || 'index.html';
  var inStudio = /\/studio\//.test(path), inGuest = /\/guest\//.test(path);
  var ROOT = (inStudio || inGuest || /\/popups\//.test(path)) ? '../' : '';
  var ARTIST = 'Julian Ink', FIRST = 'Julian', HANDLE = '@julianink', STUDIO = 'Open Ink Studio', UPCOMING = 5, SINCE = '25/09/2026';
  var isClient = /^client-(page|page-founding|book)\.html$/.test(file) || inGuest;

  function css(s) { var e = document.createElement('style'); e.textContent = s; document.head.appendChild(e); }
  function toast(m) {
    var t = document.createElement('div');
    t.style.cssText = 'position:fixed;top:22px;right:24px;background:#1A1A1A;color:#fff;padding:12px 18px;border-radius:10px;font-weight:600;font-size:14px;z-index:200;display:flex;gap:10px;align-items:center;box-shadow:0 8px 24px rgba(0,0,0,.18)';
    t.innerHTML = '<span class="ms" style="color:#3DD68C">check_circle</span>' + m;
    document.body.appendChild(t); setTimeout(function () { t.remove(); }, 3200);
  }
  function showFlash() { var m = null; try { m = sessionStorage.getItem('bp-dx-toast'); sessionStorage.removeItem('bp-dx-toast'); } catch (e) {} if (m) toast(m); }

  /* ---------- 1. What a client sees ---------- */
  if (isClient) {
    var forced = location.hash === '#deactivated';
    if (!isOff() && !forced) { window.addEventListener('hashchange', function () { if (location.hash === '#deactivated') location.reload(); }); return; }
    css('html.dx-hide body{visibility:hidden}'); document.documentElement.classList.add('dx-hide');
    var draw = function () {
      var av = document.querySelector('.av'), bg = av ? av.style.backgroundImage : '';
      css('body.dx{margin:0;min-height:100vh;background:#FBF5FF;color:#1A1A1A;font-family:PJS,"Plus Jakarta Sans",system-ui,sans-serif;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:32px 20px;box-sizing:border-box}' +
        '.dx-card{background:#fff;border:1px solid #EBE3F0;border-radius:20px;padding:40px 32px;max-width:440px;width:100%;text-align:center;box-sizing:border-box}' +
        '.dx-av{width:96px;height:96px;border-radius:50%;margin:0 auto 16px;background:#EDE3FA center/cover;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:30px;color:#4B0F8F}' +
        '.dx-card h1{font-size:26px;margin:0;letter-spacing:-.4px}.dx-h{color:#6F6874;font-size:14px;margin-top:4px}' +
        '.dx-line{font-size:17px;font-weight:700;margin:24px 0 0;line-height:1.4}.dx-sub{color:#6F6874;font-size:14px;line-height:1.55;margin:10px 0 0}' +
        '.dx-foot{margin-top:22px;color:#8A8390;font-size:12.5px}.dx-foot b{color:#1A1A1A;font-weight:800;letter-spacing:-.3px}' +
        '.dx-mock{margin-top:26px;border:1.5px dashed #B99BE0;background:#FBF5FF;border-radius:12px;padding:8px 12px;font-size:12.5px;color:#5B2A94;max-width:440px;text-align:center;line-height:1.6}' +
        '.dx-mock a{display:inline-block;margin-left:6px;padding:3px 10px;border-radius:14px;font-weight:600;color:#5B2A94;border:1px solid #D9C4F2;background:#fff;cursor:pointer}');
      css('body.dx>*:not(.dx-root){display:none!important}.dx-root{display:flex;flex-direction:column;align-items:center;width:100%}');
      document.body.classList.add('dx'); document.body.removeAttribute('style');
      var root = document.createElement('div'); root.className = 'dx-root'; document.body.appendChild(root);
      root.innerHTML =
        '<div class="dx-card"><div class="dx-av" role="img" aria-label="' + ARTIST + '">' + (bg ? '' : 'JI') + '</div>' +
        '<h1>' + ARTIST + '</h1><div class="dx-h">' + HANDLE + '</div>' +
        '<p class="dx-line">' + ARTIST + ' isn&rsquo;t taking bookings right now.</p>' +
        '<p class="dx-sub">Already have a booking with ' + FIRST + '? Use the link in your booking email. It still works.</p></div>' +
        '<div class="dx-foot"><b>bookpay</b> by Inkjin</div>' +
        '<div class="dx-mock">Mockup only · what a client sees while the account is deactivated.<a id="dx-live">Show the active page</a></div>';
      if (bg) document.querySelector('.dx-av').style.backgroundImage = bg;
      document.title = ARTIST + ' · Not taking bookings';
      document.getElementById('dx-live').onclick = function () { setOff(false); history.replaceState(null, '', location.pathname); location.reload(); };
      document.documentElement.classList.remove('dx-hide');
    };
    if (document.readyState === 'complete') setTimeout(draw, 0); else window.addEventListener('load', function () { setTimeout(draw, 0); });
    return;
  }

  /* ---------- 2. What the studio sees ---------- */
  if (inStudio) {
    var run = function () {
      var off = isOff();
      var wrap = document.querySelector('main .wrap');
      if (wrap && /^S0(2-artists|6b-page-artists)\.html$/.test(file)) {
        var vb = document.createElement('div'); vb.className = 'vbar';
        vb.innerHTML = '<span class="ms" style="font-size:17px">visibility</span>Mockup only · ' + ARTIST + '&rsquo;s account:<a href="#" data-dx="0">Active</a><a href="#" data-dx="1">Deactivated</a>';
        var first = wrap.querySelector(':scope > .vbar');
        if (first) first.parentNode.insertBefore(vb, first.nextSibling); else wrap.insertBefore(vb, wrap.firstChild);
        [].forEach.call(vb.querySelectorAll('a'), function (a) {
          a.classList.toggle('on', (a.dataset.dx === '1') === off);
          a.addEventListener('click', function (e) { e.preventDefault(); setOff(a.dataset.dx === '1'); location.reload(); });
        });
      }
      if (!off) return;
      if (file === 'S02-artists.html') {
        var row = document.getElementById('row-a1');
        if (row) {
          var st = row.querySelector('.stcell');
          if (st) st.innerHTML = '<span class="pill k">Deactivated</span><div class="faint" style="font-size:11.5px;margin-top:4px">Since ' + SINCE + ' · Not on your studio page</div>';
        }
        [].forEach.call(document.querySelectorAll('main .lbl'), function (l) {
          if (l.textContent.trim() !== 'Recent changes') return;
          var d = document.createElement('div'); d.className = 'row'; d.style.cssText = 'padding:6px 0;font-size:13.5px';
          d.innerHTML = '<span class="ms faint">pause_circle</span><span style="flex:1"><b>' + ARTIST + '</b> deactivated their account. They&rsquo;re off your studio page until they reactivate. Their split and the bookings they already have stay.</span><span class="faint" style="font-size:12px">25/09</span>';
          l.parentNode.insertBefore(d, l.nextSibling);
        });
      }
      if (/^S10/.test(file)) {
        [].forEach.call(document.querySelectorAll('article.sp-card'), function (c) {
          var h = c.querySelector('h3'); if (h && h.textContent.trim() === ARTIST) c.style.display = 'none';
        });
        [].forEach.call(document.querySelectorAll('.wb'), function (w) {
          if (w.textContent.indexOf(ARTIST) < 0) return;
          var a = w.parentNode.querySelector('.wact'); if (a) a.style.display = 'none';
        });
      }
      if (file === 'S06b-page-artists.html') {
        [].forEach.call(document.querySelectorAll('main .row'), function (r) {
          var b = r.querySelector('b'); if (!b || b.textContent.trim() !== ARTIST || !r.querySelector('.tog')) return;
          var tg = r.querySelector('.tog'), lab = tg.previousElementSibling;
          tg.style.display = 'none';
          if (lab) { lab.textContent = 'Deactivated · hidden until they reactivate'; lab.style.fontWeight = '500'; }
          r.style.background = '#FAF8FB';
        });
      }
    };
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', run); else run();
    return;
  }

  /* ---------- 3. The artist's dashboard ---------- */
  function dash() {
    var wrap = document.querySelector('main .wrap'); if (!wrap) return;
    var off = isOff();
    css('#dx-bar{display:flex;gap:14px;align-items:center;flex-wrap:wrap;background:#FFF4E5;border:1px solid #F3C98B;border-radius:14px;padding:12px 16px;margin-bottom:18px;color:#5A3B00}' +
      '#dx-bar>.ms{font-size:24px;color:#B26A00}#dx-bar b{display:block;font-size:14.5px;color:#3D2800}#dx-bar .t{font-size:13px;line-height:1.45}#dx-bar .btn{cursor:pointer;margin-left:auto}' +
      '.dx-ov{position:fixed;inset:0;background:rgba(20,10,30,.45);display:flex;align-items:center;justify-content:center;z-index:150;padding:20px}' +
      '.dx-box{background:#fff;border-radius:18px;width:540px;max-width:100%;max-height:calc(100vh - 40px);overflow:auto}' +
      '.dx-hd{padding:22px 24px 6px;display:flex;gap:14px;align-items:flex-start}.dx-hd .ic2{width:40px;height:40px;border-radius:12px;background:#FDECEC;color:#C62828;display:flex;align-items:center;justify-content:center;flex-shrink:0}' +
      '.dx-hd h2{font-size:20px;margin:0;letter-spacing:-.3px}.dx-hd p{margin:4px 0 0;color:#6F6874;font-size:13.5px;line-height:1.45}.dx-x{margin-left:auto;cursor:pointer;color:#8A8390;background:none;border:0;padding:4px}' +
      '.dx-bd{padding:10px 24px 4px}.dx-lbl{font-size:11px;letter-spacing:.8px;font-weight:700;color:#6F6874;text-transform:uppercase;margin:14px 0 6px}' +
      '.dx-li{display:flex;gap:10px;align-items:flex-start;font-size:13.5px;line-height:1.45;padding:5px 0}.dx-li .ms{font-size:18px;color:#8A8390;margin-top:1px}.dx-li.keep .ms{color:#1F7A45}' +
      '.dx-warn{display:flex;gap:10px;align-items:flex-start;background:#FFF4E5;border:1px solid #F3C98B;border-radius:12px;padding:12px 14px;font-size:13.5px;line-height:1.45;color:#5A3B00;margin-top:14px}.dx-warn .ms{color:#B26A00;font-size:20px}.dx-warn a{color:#3D2800;font-weight:700;text-decoration:underline;cursor:pointer}' +
      '.dx-tip{display:flex;gap:10px;align-items:flex-start;background:#F6F3F8;border-radius:12px;padding:12px 14px;font-size:13.5px;line-height:1.45;margin-top:10px}.dx-tip .ms{color:#6F6874;font-size:20px}.dx-tip a{color:#3E007C;font-weight:700;text-decoration:underline}' +
      '.dx-ft{display:flex;gap:10px;justify-content:flex-end;padding:18px 24px 22px}.dx-ft .btn{cursor:pointer}.dx-red{background:#C62828!important;color:#fff!important;border-color:#C62828!important}');

    if (off) {
      var bar = document.createElement('div'); bar.id = 'dx-bar'; bar.setAttribute('role', 'status');
      var extra = /^09e/.test(file) ? ' Billing for your plan is paused.' : '';
      bar.innerHTML = '<span class="ms">pause_circle</span><div style="flex:1;min-width:220px"><b>Your account is deactivated</b><span class="t">Your page is hidden and clients can&rsquo;t make new bookings. The bookings you already have are not changed.' + extra + '</span></div><a class="btn sm" id="dx-re"><span class="ms" style="font-size:16px">play_circle</span>Reactivate account</a>';
      wrap.insertBefore(bar, wrap.firstChild);
      document.getElementById('dx-re').onclick = reactivate;
      /* nothing that makes a new booking */
      [].forEach.call(document.querySelectorAll('main .btn, main a.btn'), function (b) {
        if (!/New payment link/.test(b.textContent)) return;
        b.style.opacity = '.45'; b.style.pointerEvents = 'none'; b.setAttribute('aria-disabled', 'true'); b.title = 'Reactivate your account to make payment links';
      });
    }
    if (file === '09-account.html') account(off);
    showFlash();
  }
  function reactivate() { setOff(false); flash('Your account is active again. Your page is live.'); location.reload(); }

  function account(off) {
    var card = document.getElementById('dx-card'); if (!card) return;
    var h = card.querySelector('h3'), p = card.querySelector('.dx-p'), btn = document.getElementById('dx-btn');
    if (off) {
      h.textContent = 'Your account is deactivated';
      p.innerHTML = 'Deactivated on ' + SINCE + '. Your page is hidden and clients can&rsquo;t make new bookings. Nothing was deleted.';
      btn.innerHTML = '<span class="ms" style="font-size:16px">play_circle</span>Reactivate account';
      btn.className = 'btn sm'; btn.removeAttribute('style'); btn.style.marginTop = '12px'; btn.style.cursor = 'pointer';
      btn.onclick = reactivate;
    } else {
      btn.onclick = openPop;
      if (location.hash === '#deactivate') openPop();
    }
    var del = document.getElementById('dx-del');
    if (del) del.onclick = function (e) { e.preventDefault(); if (window.bpChat) window.bpChat(); };
  }

  function openPop() {
    if (document.querySelector('.dx-ov')) return;
    var ov = document.createElement('div'); ov.className = 'dx-ov';
    function li(ic, t, keep) { return '<div class="dx-li' + (keep ? ' keep' : '') + '"><span class="ms">' + ic + '</span><span>' + t + '</span></div>'; }
    ov.innerHTML = '<div class="dx-box" role="dialog" aria-modal="true" aria-labelledby="dx-t">' +
      '<div class="dx-hd"><div class="ic2"><span class="ms">pause_circle</span></div><div><h2 id="dx-t">Deactivate your account?</h2><p>This turns your account off. Nothing is deleted, and you can turn it back on any time.</p></div><button class="dx-x" aria-label="Close" data-dxc><span class="ms">close</span></button></div>' +
      '<div class="dx-bd">' +
      '<div class="dx-lbl">What turns off</div>' +
      li('visibility_off', 'Your page <b>inkjin.com/@julianink</b> and your guest spot pages are hidden. Clients see &ldquo;' + ARTIST + ' isn&rsquo;t taking bookings right now.&rdquo;') +
      li('event_busy', 'No new bookings or requests. Payment links that aren&rsquo;t paid yet stop working.') +
      li('storefront', 'You come off ' + STUDIO + '&rsquo;s page. They&rsquo;re told. Your split with them stays.') +
      li('credit_card_off', 'Plan billing pauses. You aren&rsquo;t charged while your account is deactivated.') +
      '<div class="dx-lbl">What stays</div>' +
      li('check_circle', 'You can still log in, see your bookings and message your clients.', 1) +
      li('check_circle', 'Payouts for payments already made arrive as normal.', 1) +
      '<div class="dx-warn"><span class="ms">event</span><span><b>You have ' + UPCOMING + ' upcoming bookings.</b> They are not cancelled, and you are still responsible for them: do the session or refund the client. Their booking links, consent forms and balance payments keep working. <a href="02b-bookings-bookings.html">View bookings</a></span></div>' +
      '<div class="dx-tip"><span class="ms">lightbulb</span><span><b>Just need a break?</b> Close your books instead. Your page stays up and clients can join your waitlist. <a href="03-schedule.html">Go to Schedule</a></span></div>' +
      '</div><div class="dx-ft"><a class="btn ghost" data-dxc>Cancel</a><a class="btn dx-red" id="dx-go"><span class="ms">pause_circle</span>Deactivate account</a></div></div>';
    document.body.appendChild(ov);
    function close() { ov.remove(); if (location.hash === '#deactivate') history.replaceState(null, '', location.pathname); }
    ov.addEventListener('click', function (e) { if (e.target === ov || e.target.closest('[data-dxc]')) close(); });
    document.addEventListener('keydown', function esc(e) { if (e.key === 'Escape') { close(); document.removeEventListener('keydown', esc); } });
    document.getElementById('dx-go').onclick = function () { setOff(true); flash('Account deactivated'); history.replaceState(null, '', location.pathname); location.reload(); };
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', dash); else dash();
})();

/* Dashboard search (17 Nov 2026). See 22-DASHBOARD-SEARCH.md.
   Mockup only: the index is a fixed list here. In the product it is built from the
   dashboard's own pages and settings, and the "Recent" list is stored per artist. */
(function () {
  var aside = document.querySelector('aside'); if (!aside) return;
  var ST = !!aside.querySelector('nav a[href="S01-home.html"]');
  if (!ST && !aside.querySelector('nav a[href="01-home.html"]')) return;
  var tag = aside.querySelector('.tag'); if (!tag) return;
  var KR = ST ? 'bp-search-recent-studio' : 'bp-search-recent', KF = 'bp-search-find';

  /* t = name, g = where it sits, u = page, f = text to scroll to, k = the artist's own words */
  var PA = [
    {t:'Home',g:'Home',u:'01-home.html',k:'dashboard overview start today'},
    {t:'Requests',g:'Bookings',u:'02-bookings.html',k:'enquiries inquiries new request custom pending approve decline quote'},
    {t:'Bookings',g:'Bookings',u:'02b-bookings-bookings.html',k:'appointments sessions upcoming past confirmed cancel reschedule booking number'},
    {t:'Calendar',g:'Bookings',u:'02d-bookings-calendar.html',k:'diary agenda month week view day'},
    {t:'Payment links',g:'Bookings',u:'02c-bookings-payment-links.html',k:'pay link send invoice charge balance take payment'},
    {t:'Availability',g:'Schedule',u:'03-schedule.html',k:'hours working days open times weekly schedule opening'},
    {t:'Blocked dates',g:'Schedule > Availability',u:'03-schedule.html',f:'Blocked',k:'day off holiday vacation time off closed away leave sick block date'},
    {t:'Guest spots',g:'Schedule',u:'03b-schedule-guest-spots.html',k:'guest spot travel visit another studio guesting add guest spot'},
    {t:'Calendar sync',g:'Schedule',u:'03c-schedule-calendar-sync.html',k:'google calendar apple ical outlook connect sync'},
    {t:'Booking rules',g:'Schedule',u:'03d-schedule-booking-rules.html',k:'notice minimum advance cancellation window lead time max per day buffer close books books closed'},
    {t:'Inbox',g:'Inbox',u:'04-inbox.html',k:'messages emails chat replies client messages mail'},
    {t:'Clients',g:'Clients',u:'05-clients.html',k:'customers people list contacts history notes'},
    {t:'Waitlist',g:'Clients',u:'05b-clients-waitlist.html',k:'waiting list queue cancellations fill gaps'},
    {t:'Earnings',g:'Money',u:'06-money.html',k:'income revenue sales totals tax report how much earned'},
    {t:'Deposits & rates',g:'Money',u:'06b-money-deposits-rates.html',k:'price prices pricing rates hourly day rate deposit percent fixed amount charge cost fee'},
    {t:'Payouts',g:'Money',u:'06c-money-payouts.html',k:'stripe bank account transfer paid out when do i get paid payout schedule iban'},
    {t:'About',g:'My Page',u:'07-my-page.html',k:'bio profile photo name handle instagram link location my public page'},
    {t:'Designs',g:'My Page',u:'07b-my-page-designs.html',k:'flash available designs sheets prints shop upload design'},
    {t:'Portfolio',g:'My Page',u:'07c-my-page-portfolio.html',k:'gallery photos work images tattoos healed'},
    {t:'FAQ',g:'My Page',u:'07d-my-page-faq.html',k:'questions answers common questions'},
    {t:'Policies',g:'My Page',u:'07e-my-page-policies.html',k:'refund cancellation policy rules terms touch up no show late'},
    {t:'Look & feel',g:'My Page',u:'07f-my-page-look-feel.html',k:'appearance colours colors theme layout cover banner font design of my page'},
    {t:'Intake forms',g:'Client Flow',u:'08-client-flow.html',k:'questionnaire booking form questions what clients fill in request form fields'},
    {t:'Consent form',g:'Client Flow',u:'08b-client-flow-consent.html',k:'waiver release agreement sign signature legal age id health form'},
    {t:'Aftercare',g:'Client Flow',u:'08c-client-flow-aftercare.html',k:'healing care instructions after tattoo email follow up'},
    {t:'Buzz',g:'Buzz',u:'11-buzz.html',k:'marketing instagram story maker posts promote social'},
    {t:'What worked',g:'Buzz',u:'11b-buzz-what-worked.html',k:'results stats which posts worked'},
    {t:'Brand',g:'Buzz',u:'11c-buzz-brand.html',k:'logo colours fonts brand kit'},
    {t:'Profile',g:'Account',u:'09-account.html',k:'my details name email phone photo deactivate account delete account close account pause'},
    {t:'Deactivate account',g:'Account > Profile',u:'09-account.html',f:'Deactivate',k:'turn off pause close stop bookings hide page leave stop taking bookings'},
    {t:'Password',g:'Account',u:'09b-account-password.html',k:'change password login security sign in forgot two factor'},
    {t:'Studio',g:'Account',u:'09c-account-studio.html',k:'my studio workstation rent revenue split invite studio change studio leave studio'},
    {t:'Regional',g:'Account',u:'09d-account-regional.html',k:'currency language time zone timezone country date format euro pound dollar vat'},
    {t:'Plan',g:'Account',u:'09e-account-plan.html',k:'subscription billing upgrade pro founding invoice card cancel plan price of bookpay'},
    {t:'Notifications',g:'Account',u:'09f-account-notifications.html',k:'emails alerts sound reminders turn off notifications sms'},
    {t:'Referrals',g:'Referrals',u:'10-referrals.html',k:'invite friends earn 20 refer artist reward'}
  ];
  var AA = [
    {t:'New payment link',g:'Action',u:'02c-bookings-payment-links.html',f:'New payment link',k:'create send charge take payment'},
    {t:'Add guest spot',g:'Action',u:'03b-schedule-guest-spots.html',f:'Add guest spot',k:'new guest spot travel'},
    {t:'Close books',g:'Action',u:'03d-schedule-booking-rules.html',f:'Books',k:'stop taking bookings books closed open books pause bookings'},
    {t:'Add a day off',g:'Action',u:'03-schedule.html',f:'Blocked',k:'block a date holiday vacation'},
    {t:'Change my deposit',g:'Action',u:'06b-money-deposits-rates.html',f:'Deposit',k:'deposit amount percent'},
    {t:'Edit consent form',g:'Action',u:'08b-client-flow-consent.html',k:'waiver change wording'}
  ];
  var CA = [
    {t:'Louis Hatzis',g:'Client',u:'05-clients.html',f:'Louis Hatzis',k:'client'},
    {t:'Sean Sale',g:'Client',u:'05-clients.html',f:'Sean Sale',k:'client'},
    {t:'Ilias Hatzis',g:'Client',u:'05-clients.html',f:'Ilias Hatzis',k:'client'},
    {t:'sa ahmad',g:'Client',u:'05-clients.html',f:'sa ahmad',k:'client'},
    {t:'INK-000041-PL · Japanese dragon',g:'Booking · Sep 30, 2026',u:'02b-bookings-bookings.html',f:'INK-000041-PL',k:'ink-000041 japanese dragon'},
    {t:'INK-000035-PL · Baby dragon',g:'Booking',u:'02b-bookings-bookings.html',f:'INK-000035-PL',k:'ink-000035 baby dragon'},
    {t:'INK-000034-PL · Woman',g:'Booking',u:'02b-bookings-bookings.html',f:'INK-000034-PL',k:'ink-000034 woman'},
    {t:'INK-000018-CR · Custom request',g:'Request',u:'02-bookings.html',f:'INK-000018-CR',k:'ink-000018 custom request'}
  ];
  var HA = [
    {t:'How do I refund a client?',g:'Help',k:'refund money back return'},
    {t:'When do I get paid?',g:'Help',k:'payout paid stripe days'},
    {t:'How do I change my deposit?',g:'Help',u:'06b-money-deposits-rates.html',k:'deposit'},
    {t:'How do I take a day off?',g:'Help',u:'03-schedule.html',f:'Blocked',k:'day off holiday vacation'}
  ];
    var PS = [
    {t:'Home',g:'Home',u:'S01-home.html',k:'dashboard overview start today'},
    {t:'Artists',g:'Artists',u:'S02-artists.html',k:'team staff tattooers who works here residents apprentices relationship how they are paid split rent workstation'},
    {t:'Join requests',g:'Artists',u:'S02b-artists-requests.html',k:'artists asking to join approve decline request to join'},
    {t:'Invites',g:'Artists',u:'S02c-artists-invites.html',k:'invitation sent waiting pending resend cancel invite'},
    {t:'Guest artists',g:'Artists',u:'S02d-artists-guests.html',k:'guest spot visiting artists confirm split decline guest dates'},
    {t:'Invite an artist',g:'Action',u:'S02-artists.html',f:'Invite artist',k:'add artist new artist invite tattooer email username'},
    {t:'Revenue split and artist pay',g:'Artists',u:'S02-artists.html',f:'Revenue split',k:'split percent artist studio share how artists are paid change split workstation rent direct payment'},
    {t:'Calendar',g:'Calendar',u:'S03-calendar.html',k:'diary agenda all artists month week busy availability'},
    {t:'Bookings',g:'Bookings',u:'S04-bookings.html',k:'appointments sessions clients list upcoming past export studio share'},
    {t:'New booking',g:'Action',u:'S04-bookings.html',f:'New booking',k:'create booking payment link walk in walk-in qr code pick artist'},
    {t:'Requests',g:'Bookings',u:'S04b-bookings-requests.html',k:'studio request any available artist assign artist enquiries needs an artist sent back'},
    {t:'Payment links',g:'Bookings',u:'S04c-bookings-payment-links.html',k:'pay link send charge take payment open paid expired'},
    {t:'Earnings',g:'Money',u:'S05-money-earnings.html',k:'income revenue studio share split earned history export totals'},
    {t:'Payouts',g:'Money',u:'S05b-money-payouts.html',k:'stripe bank account payout when do i get paid iban disconnect currency'},
    {t:'About',g:'Studio Page',u:'S06-page-about.html',k:'public page studio profile bio photo address opening hours name logo my studio page'},
    {t:'Artists on my page',g:'Studio Page',u:'S06b-page-artists.html',k:'which artists show on studio page order hide artist show artist'},
    {t:'Appearance',g:'Studio Page',u:'S06c-page-look.html',k:'colours colors theme look feel layout design of my page banner'},
    {t:'Portfolio',g:'Studio Page',u:'S06d-page-portfolio.html',k:'gallery photos work images tattoos'},
    {t:'Consent form',g:'Client Flow',u:'S07-client-flow-consent.html',k:'waiver release agreement sign signature legal age id health form artists own consent form'},
    {t:'Intake form',g:'Client Flow',u:'S07b-client-flow-intake.html',k:'questionnaire booking form questions what clients fill in request form fields'},
    {t:'Profile',g:'Account',u:'S08-account-profile.html',k:'my details name email phone owner username'},
    {t:'Password',g:'Account',u:'S08b-account-password.html',k:'change password login security sign in forgot'},
    {t:'Studio details',g:'Account',u:'S08c-account-studio.html',k:'studio name address licence license logo location type details'},
    {t:'Regional',g:'Account',u:'S08d-account-regional.html',k:'country time zone timezone date format currency language units'},
    {t:'Plan',g:'Account',u:'S08e-account-plan.html',k:'subscription billing upgrade pro founding invoice card cancel plan price of bookpay trial artists included'},
    {t:'Notifications',g:'Account',u:'S08f-account-notifications.html',k:'emails alerts reminders turn off notifications'}
  ];
  var CS = [
    {t:'Julian Ink',g:'Artist · Revenue split',u:'S02-artists.html',f:'Julian Ink',k:'artist resident'},
    {t:'Nikos Pappas',g:'Artist · Revenue split',u:'S02-artists.html',f:'Nikos Pappas',k:'artist apprentice'},
    {t:'Maria Kosta',g:'Artist · Workstation rent',u:'S02-artists.html',f:'Maria Kosta',k:'artist rent workstation'},
    {t:'Lena Vogel',g:'Guest artist · Berlin',u:'S02d-artists-guests.html',f:'Lena Vogel',k:'guest artist berlin'},
    {t:'Sam Hartley',g:'Guest artist · London',u:'S02d-artists-guests.html',f:'Sam Hartley',k:'guest artist london'},
    {t:'Ava Lindqvist',g:'Guest artist · Stockholm',u:'S02d-artists-guests.html',f:'Ava Lindqvist',k:'guest artist stockholm'},
    {t:'Louis H. · Japanese dragon',g:'Booking · 29/09/2026',u:'S04-bookings.html',f:'Louis H.',k:'louis japanese dragon julian'},
    {t:'Mia R. · Swallow flash',g:'Booking · 02/10/2026',u:'S04-bookings.html',f:'Mia R.',k:'mia swallow flash nikos'},
    {t:'Kostas T. · Rose',g:'Booking · 03/10/2026',u:'S04-bookings.html',f:'Kostas T.',k:'kostas rose nikos'},
    {t:'Elena M. · Small botanical piece',g:'Request · Needs an artist',u:'S04b-bookings-requests.html',f:'Elena M.',k:'elena botanical any available artist'},
    {t:'George T. · Script lettering',g:'Request · Sent back',u:'S04b-bookings-requests.html',f:'George T.',k:'george script lettering'}
  ];
  var HS = [
    {t:'How do I add an artist?',g:'Help',u:'S02-artists.html',f:'Invite artist',k:'add artist invite new artist team'},
    {t:'How does the revenue split work?',g:'Help',k:'split percent studio share revenue'},
    {t:'When do I get paid?',g:'Help',u:'S05b-money-payouts.html',k:'payout paid stripe days bank'}
  ];
  var P = ST ? PS : PA, A = ST ? [] : AA, C = ST ? CS : CA, H = ST ? HS : HA;
  var ALL = {pages:P.concat(A), people:C, help:H};

  function norm(s) { return (s || '').toLowerCase().replace(/[^a-z0-9€ ]+/g, ' ').replace(/\s+/g, ' ').trim(); }
  function score(it, q) {
    var t = norm(it.t), k = norm(it.k), g = norm(it.g), s = 0, words = q.split(' ');
    if (t === q) s += 100; else if (t.indexOf(q) === 0) s += 60; else if (t.indexOf(q) > -1) s += 40;
    for (var i = 0; i < words.length; i++) {
      var w = words[i]; if (!w) continue;
      if (t.indexOf(w) > -1) s += 12; else if (k.indexOf(' ' + w) > -1 || k.indexOf(w) === 0) s += 9;
      else if (w.length > 3 && k.indexOf(w) > -1) s += 5; else if (g.indexOf(w) > -1) s += 2; else return 0;
    }
    return s;
  }
  function find(list, q, n) {
    var r = []; list.forEach(function (it) { var s = score(it, q); if (s) r.push([s, it]); });
    r.sort(function (a, b) { return b[0] - a[0]; }); return r.slice(0, n).map(function (x) { return x[1]; });
  }
  function recent() { try { return JSON.parse(localStorage.getItem(KR) || '[]'); } catch (e) { return []; } }
  function pushRecent(it) {
    var r = recent().filter(function (x) { return x.t !== it.t; }); r.unshift(it); r = r.slice(0, 4);
    try { localStorage.setItem(KR, JSON.stringify(r)); } catch (e) {}
  }
  function log(q, hit) { try { var l = JSON.parse(sessionStorage.getItem('bp-search-log') || '[]'); l.push({q: q, hit: hit}); sessionStorage.setItem('bp-search-log', JSON.stringify(l)); } catch (e) {} }

  var st = document.createElement('style');
  st.textContent =
    '.ds-box{margin:18px 14px 14px;position:relative}' +
    '.ds-in{width:100%;box-sizing:border-box;height:38px;border-radius:10px;border:1px solid #3A3A3A;background:#262626;color:#fff;font:600 13px PJS,"Plus Jakarta Sans",system-ui,sans-serif;padding:0 8px 0 32px;outline:none}' +
    '.ds-in::placeholder{color:#9a9a9a;font-weight:500}.ds-in:focus{border-color:#B99BE0;background:#2c2c2c}' +
    '.ds-ic{position:absolute;left:9px;top:9px;font-size:19px;color:#9a9a9a;pointer-events:none}' +
    '.ds-k{display:none;position:absolute;right:9px;top:9px;font:700 11px PJS,system-ui,sans-serif;color:#9a9a9a;border:1px solid #444;border-radius:5px;padding:2px 6px;pointer-events:none}' +
    '.ds-pan{position:fixed;left:14px;width:440px;max-width:calc(100vw - 28px);max-height:70vh;overflow:auto;background:#fff;color:#1A1A1A;border-radius:14px;box-shadow:0 18px 50px rgba(0,0,0,.28);border:1px solid #EBE3F0;z-index:400;display:none;padding:6px;font-family:PJS,"Plus Jakarta Sans",system-ui,sans-serif}' +
    '.ds-gh{font-size:11px;font-weight:800;letter-spacing:.6px;text-transform:uppercase;color:#8A8390;padding:10px 10px 4px}' +
    '.ds-it{display:flex;align-items:center;gap:10px;padding:8px 10px;border-radius:9px;cursor:pointer;text-decoration:none;color:#1A1A1A}' +
    '.ds-it.on,.ds-it:hover{background:#F3EAFC}.ds-it .ms{font-size:19px;color:#6F6874}' +
    '.ds-it b{font-size:14px;font-weight:700;display:block}.ds-it span.s{font-size:12px;color:#6F6874;display:block;margin-top:1px}' +
    '.ds-it em{font-style:normal;margin-left:auto;font-size:11px;font-weight:700;color:#5B2A94;background:#EDE3FA;border-radius:10px;padding:2px 8px}' +
    '.ds-no{padding:14px 12px 6px;font-size:14px;font-weight:700}.ds-no p{font-weight:500;color:#6F6874;font-size:13px;margin:4px 0 0}' +
    '.ds-ft{border-top:1px solid #F0E8F5;margin-top:6px;padding:8px 10px;font-size:12px;color:#8A8390;display:flex;gap:14px}' +
    '.ds-hl{outline:3px solid #B99BE0;outline-offset:4px;border-radius:8px;transition:outline-color 1.5s}' +
    '@media(max-width:900px){.ds-k{display:none}}';
  document.head.appendChild(st);

  var box = document.createElement('div'); box.className = 'ds-box';
  box.innerHTML = '<span class="ms ds-ic">search</span><input class="ds-in" type="search" placeholder="Search or jump to…" aria-label="Search the dashboard" autocomplete="off"><span class="ds-k">/</span>';
  tag.parentNode.insertBefore(box, tag.nextSibling);
  var inp = box.querySelector('input');
  var pan = document.createElement('div'); pan.className = 'ds-pan'; pan.setAttribute('role', 'listbox'); document.body.appendChild(pan);
  var items = [], cur = -1;

  function esc(s) { return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;'); }
  function icon(it) { return it.g === 'Action' ? 'bolt' : it.g === 'Help' ? 'help' : /^(Client|Artist ·|Guest artist)/.test(it.g) ? 'person' : /^(Booking|Request)/.test(it.g) ? 'event' : 'arrow_forward'; }
  function row(it, i) {
    return '<a class="ds-it" data-i="' + i + '" href="' + (it.u || '#') + '"><span class="ms">' + icon(it) + '</span><div><b>' + esc(it.t) + '</b><span class="s">' + esc(it.g) + '</span></div>' + (it.g === 'Action' ? '<em>Do it</em>' : '') + '</a>';
  }
  function place() { var r = box.getBoundingClientRect(); pan.style.top = (r.bottom + 6) + 'px'; }
  function render() {
    var q = norm(inp.value), h = '', n = 0; items = [];
    function grp(name, list) { if (!list.length) return; h += '<div class="ds-gh">' + name + '</div>'; list.forEach(function (it) { items.push(it); h += row(it, n++); }); }
    if (!q) {
      var r = recent(); grp('Recent', r);
      grp('Try', ST ? [{t:'Invite an artist',g:'Action',u:'S02-artists.html',f:'Invite artist'},{t:'New booking',g:'Action',u:'S04-bookings.html',f:'New booking'},{t:'Revenue split and artist pay',g:'Artists',u:'S02-artists.html',f:'Revenue split'}] : [{t:'Day off',g:'Schedule > Availability',u:'03-schedule.html',f:'Blocked'},{t:'Deposit',g:'Money > Deposits & rates',u:'06b-money-deposits-rates.html',f:'Deposit'},{t:'Consent form',g:'Client Flow',u:'08b-client-flow-consent.html'}]);
    } else {
      grp('Settings and pages', find(ALL.pages, q, 5)); grp('Clients and bookings', find(ALL.people, q, 4)); grp('Help', find(ALL.help, q, 2));
      if (!items.length) h = '<div class="ds-no">Nothing found for “' + esc(inp.value) + '”<p>Ask us and we will point you to it.</p></div>';
      h += '<a class="ds-it" data-ask="1" href="#"><span class="ms">chat</span><div><b>Ask us about “' + esc(inp.value) + '”</b><span class="s">Opens Get Help with your words filled in</span></div></a>';
    }
    h += '<div class="ds-ft"><span>↑ ↓ to move</span><span>Enter to open</span><span>Esc to close</span></div>';
    pan.innerHTML = h; cur = items.length ? 0 : -1; mark(); place(); pan.style.display = 'block';
  }
  function mark() { [].forEach.call(pan.querySelectorAll('.ds-it[data-i]'), function (e) { e.classList.toggle('on', +e.getAttribute('data-i') === cur); }); }
  function close() { pan.style.display = 'none'; }
  function ask() {
    var q = inp.value; log(q, false); close();
    try { sessionStorage.setItem('bp-chat-prefill', q); } catch (e) {}
    var c = document.querySelector('[data-chat]'); if (c) c.click(); else if (window.bpChat) window.bpChat(q);
    setTimeout(function () { var t = document.querySelector('textarea,input[type=text]:not(.ds-in)'); if (t && !t.value && document.activeElement !== inp) { /* chat box may take the text */ } }, 300);
  }
  function go(it) {
    log(norm(inp.value), true); pushRecent({t: it.t, g: it.g, u: it.u, f: it.f}); close();
    if (!it.u) { ask(); return; }
    try { if (it.f) sessionStorage.setItem(KF, it.f); } catch (e) {}
    var here = location.pathname.split('/').pop() === it.u;
    if (here) { spot(it.f); inp.value = ''; inp.blur(); } else location.href = it.u;
  }
  function spot(text) {
    if (!text) return; var want = norm(text), best = null;
    var els = document.querySelectorAll('main h1,main h2,main h3,main h4,main label,main b,main .btn,main td,main th,main .lbl,main strong,main a');
    for (var i = 0; i < els.length; i++) { var e = els[i]; if (norm(e.textContent).indexOf(want) > -1 && e.textContent.length < 120) { best = e; break; } }
    if (!best) return;
    var t = best.closest('.card,tr,.btn') || best; t.scrollIntoView({block: 'center', behavior: 'smooth'}); t.classList.add('ds-hl');
    setTimeout(function () { t.classList.remove('ds-hl'); }, 2600);
  }
  try { var f = sessionStorage.getItem(KF); if (f) { sessionStorage.removeItem(KF); window.addEventListener('load', function () { setTimeout(function () { spot(f); }, 250); }); } } catch (e) {}

  inp.addEventListener('input', render);
  inp.addEventListener('focus', render);
  inp.addEventListener('keydown', function (e) {
    if (e.key === 'ArrowDown') { e.preventDefault(); if (items.length) { cur = (cur + 1) % items.length; mark(); } }
    else if (e.key === 'ArrowUp') { e.preventDefault(); if (items.length) { cur = (cur - 1 + items.length) % items.length; mark(); } }
    else if (e.key === 'Enter') { e.preventDefault(); if (items[cur]) go(items[cur]); else if (inp.value.trim()) ask(); }
    else if (e.key === 'Escape') { close(); inp.blur(); }
  });
  pan.addEventListener('mousedown', function (e) { e.preventDefault(); });
  pan.addEventListener('click', function (e) {
    var a = e.target.closest('.ds-it'); if (!a) return; e.preventDefault();
    if (a.getAttribute('data-ask')) ask(); else go(items[+a.getAttribute('data-i')]);
  });
  document.addEventListener('click', function (e) { if (!box.contains(e.target) && !pan.contains(e.target)) close(); });
  document.addEventListener('keydown', function (e) {
    if (e.key === '/' && !/^(INPUT|TEXTAREA|SELECT)$/.test((document.activeElement || {}).tagName || '') && !(document.activeElement || {}).isContentEditable) { e.preventDefault(); inp.focus(); }
  });
  window.addEventListener('resize', function () { if (pan.style.display === 'block') place(); });
})();
