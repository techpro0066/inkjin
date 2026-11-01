{{-- Payment link create modal script --}}
<script>
(function () {
  const modal = document.getElementById('m-pl');
  if (!modal) return;

  const canPayLinks = @json((bool) ($canCreatePaymentLinks ?? false));
  const isAuto = @json((bool) ($isAutoScheduling ?? false));
  const reloadOnDone = @json((bool) ($paymentLinkModalReloadOnDone ?? false));
  const validateUrl = @json(route('artist.payment-link.validate'));
  const storeUrl = @json(route('artist.payment-link.store'));
  const cur = @json(($currencySymbol ?? $currency ?? ($dashboardStats['currency_symbol'] ?? '€')));

  const totalWrap = document.getElementById('pl-totw');
  const sumEl = document.getElementById('pl-sum');
  const totField = document.getElementById('pl-totf');
  const amtField = document.getElementById('pl-amtf');
  const titleField = document.getElementById('pl-titlef');
  const calEl = document.getElementById('pl-cal');
  const dateBtn = document.getElementById('pl-date');
  const dateText = document.getElementById('pl-datet');
  const dateErr = document.getElementById('pl-e-date');
  const amtInput = document.getElementById('pl-amt');
  const totInput = document.getElementById('pl-tot');
  const titleInput = document.getElementById('pl-title');
  const goBtn = document.getElementById('pl-go');
  const formView = document.getElementById('pl-form');
  const doneView = document.getElementById('pl-done');
  const formFooter = document.getElementById('pl-ff');
  const doneFooter = document.getElementById('pl-df');
  const headTitle = document.getElementById('pl-t');
  const headSub = document.getElementById('pl-sub');
  const headIcon = document.getElementById('pl-ic');
  const urlEl = document.getElementById('pl-url');
  const qrEl = document.getElementById('pl-qr');
  const dinfoEl = document.getElementById('pl-dinfo');

  const DOW = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
  const MN = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
  const HRS = [];
  for (var _h = 600; _h <= 1140; _h += 30) HRS.push(_h);

  var now = new Date();
  var TODAY = new Date(now.getFullYear(), now.getMonth(), now.getDate());
  var S = {};
  var bad = false;
  var busy = false;
  var lastLinkUrl = '';
  var createdThisOpen = false;

  const errMap = {
    amount: { el: 'pl-e-amt', field: amtField },
    payment_type: { el: 'pl-e-payment_type' },
    title: { el: 'pl-e-title', field: titleField },
    date_time: { el: 'pl-e-date', field: dateBtn },
    session_duration: { el: 'pl-e-dur' },
    total_price: { el: 'pl-e-tot', field: totField },
    expires: { el: 'pl-e-exp' },
  };

  function csrf() {
    var m = document.querySelector('meta[name="csrf-token"]');
    return m ? m.getAttribute('content') : '';
  }

  function segVal(id) {
    var b = document.querySelector('#' + id + ' button.pl-on');
    return b ? (b.getAttribute('data-v') || '') : '';
  }

  function setSeg(id, v) {
    var w = document.getElementById(id);
    if (!w) return;
    w.querySelectorAll('button').forEach(function (b) {
      b.classList.toggle('pl-on', (b.getAttribute('data-v') || '') === v);
    });
  }

  function selectInGroup(group, btn) {
    if (!group || !btn || btn.disabled) return;
    group.querySelectorAll('button').forEach(function (b) {
      b.classList.toggle('pl-on', b === btn);
    });
  }

  function isFull() {
    return segVal('pl-type') === 'full';
  }

  function num(v) {
    var s = String(v || '').replace(/[^0-9.,]/g, '').replace(/,(?=\d{3}\b)/g, '').replace(',', '.');
    var n = parseFloat(s);
    return isNaN(n) ? 0 : n;
  }

  function money(n) {
    n = Number(n) || 0;
    return cur + n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  function dd(d) {
    return ('0' + d.getDate()).slice(-2) + '/' + ('0' + (d.getMonth() + 1)).slice(-2);
  }

  function hm(m) {
    return ('0' + Math.floor(m / 60)).slice(-2) + ':' + ('0' + (m % 60)).slice(-2);
  }

  function range() {
    var mx = new Date(TODAY);
    mx.setMonth(mx.getMonth() + 12);
    return [new Date(TODAY), mx];
  }

  function dayOk(d) {
    var r = range();
    return d >= r[0] && d <= r[1];
  }

  function dateTimeValue() {
    if (!S.day || S.hour == null) return '';
    var d = S.day;
    var y = d.getFullYear();
    var m = ('0' + (d.getMonth() + 1)).slice(-2);
    var day = ('0' + d.getDate()).slice(-2);
    return y + '-' + m + '-' + day + ' ' + hm(S.hour);
  }

  function setDate(d, h) {
    S.day = d;
    S.hour = h;
    if (!dateText) return;
    if (d) {
      dateText.textContent = DOW[d.getDay()] + ', ' + dd(d) + '/' + d.getFullYear() + ' · ' + hm(h);
      dateText.className = '';
    } else {
      dateText.textContent = 'Select date and time';
      dateText.className = 'pl-ph0';
    }
  }

  function resetDate() {
    var kept = { mode: S.mode };
    S = kept;
    setDate(null);
  }

  function drawCal() {
    if (!calEl) return;
    var r = range();
    if (!S.pday) S.pday = new Date(TODAY);
    if (!S.pmon) {
      var bb = S.pday || S.day || r[0];
      S.pmon = new Date(bb.getFullYear(), bb.getMonth(), 1);
    }

    var y = S.pmon.getFullYear();
    var mo = S.pmon.getMonth();
    var first = new Date(y, mo, 1);
    var off = (first.getDay() + 6) % 7;
    var start = new Date(y, mo, 1 - off);
    var prevOk = new Date(y, mo, 0) >= r[0];
    var nextOk = new Date(y, mo + 1, 1) <= r[1];

    var h = '<div class="pl-calh"><span>Pick a date and time</span><a data-nodate>No date yet</a></div>'
      + '<div class="pl-mh"><button type="button" data-mnav="-1" aria-label="Previous month"' + (prevOk ? '' : ' disabled') + '><span class="ms">chevron_left</span></button><b>' + MN[mo] + ' ' + y + '</b><button type="button" data-mnav="1" aria-label="Next month"' + (nextOk ? '' : ' disabled') + '><span class="ms">chevron_right</span></button></div>'
      + '<div class="pl-mg">' + ['Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa', 'Su'].map(function (x) { return '<i>' + x + '</i>'; }).join('');

    for (var i = 0; i < 42; i++) {
      var d = new Date(start);
      d.setDate(start.getDate() + i);
      var inm = d.getMonth() === mo;
      var ok = inm && dayOk(d);
      h += '<button type="button" data-iso="' + d.getFullYear() + '-' + (d.getMonth() + 1) + '-' + d.getDate() + '"'
        + (ok ? '' : ' disabled')
        + ' class="' + (inm ? '' : 'pl-out ') + (+d === +TODAY ? 'pl-td ' : '') + (S.pday && +d === +S.pday ? 'pl-on' : '') + '">'
        + d.getDate() + '</button>';
    }
    h += '</div>';

    if (S.pday) {
      var sel = S.pday;
      h += '<div class="pl-tlab">' + DOW[sel.getDay()] + ' ' + sel.getDate() + ' ' + MN[sel.getMonth()].slice(0, 3) + '</div>'
        + '<div class="pl-times">' + HRS.map(function (x) {
          var on = S.day && +S.day === +sel && S.hour === x;
          return '<button type="button" data-h="' + x + '"' + (on ? ' class="pl-on"' : '') + '>' + hm(x) + '</button>';
        }).join('') + '</div>';
    } else {
      h += '<div class="pl-tlab" style="color:#8F8A95;font-weight:500">Pick a day to see times.</div>';
    }

    calEl.innerHTML = h;
  }

  function syncSum() {
    if (!sumEl) return;
    var full = isFull();
    var amt = num(amtInput?.value);
    var tot = num(totInput?.value);
    bad = false;

    if (full) {
      if (amt) {
        sumEl.hidden = false;
        sumEl.innerHTML =
          '<div class="pl-r"><span>Client pays now</span><b>' + money(amt) + '</b></div>' +
          '<div class="pl-r"><span>Balance due at the session</span><b>' + money(0) + '</b></div>';
      } else {
        sumEl.hidden = false;
        sumEl.innerHTML = '<div class="pl-hint">The client pays the full amount now.</div>';
      }
    } else if (amt && tot) {
      sumEl.hidden = false;
      if (amt >= tot) {
        sumEl.innerHTML = '<div class="pl-r" style="color:#C62828"><span>Total price must be greater than the amount.</span></div>';
        bad = true;
      } else {
        sumEl.innerHTML =
          '<div class="pl-r"><span>Client pays now</span><b>' + money(amt) + '</b></div>' +
          '<div class="pl-r"><span>Balance due at the session</span><b>' + money(tot - amt) + '</b></div>';
      }
    } else {
      sumEl.hidden = false;
      sumEl.innerHTML = '<div class="pl-hint">The balance is worked out once you enter both amounts.</div>';
    }

    totField?.classList.toggle('pl-bad', !!bad);
  }

  function clearErrors() {
    Object.keys(errMap).forEach(function (key) {
      var cfg = errMap[key];
      var el = document.getElementById(cfg.el);
      if (el) { el.hidden = true; el.textContent = ''; }
      if (cfg.field) cfg.field.classList.remove('pl-bad');
    });
    var formErr = document.getElementById('pl-e-form');
    if (formErr) { formErr.hidden = true; formErr.textContent = ''; }
  }

  function showErrors(errors) {
    clearErrors();
    if (!errors) return;
    var first = null;
    Object.keys(errMap).forEach(function (key) {
      if (!errors[key]) return;
      var msg = Array.isArray(errors[key]) ? errors[key][0] : errors[key];
      var cfg = errMap[key];
      var el = document.getElementById(cfg.el);
      if (el) {
        el.textContent = msg || '';
        el.hidden = !msg;
        if (!first) first = el;
      }
      if (cfg.field) cfg.field.classList.add('pl-bad');
    });
    if (first && typeof first.scrollIntoView === 'function') {
      first.scrollIntoView({ block: 'center', behavior: 'smooth' });
    }
  }

  function buildFormData() {
    var fd = new FormData();
    fd.append('amount', (amtInput?.value || '').trim());
    fd.append('payment_type', isFull() ? 'full' : 'deposit');
    fd.append('title', (titleInput?.value || '').trim());
    fd.append('session_duration', segVal('pl-dur') || '3h');
    fd.append('expires', segVal('pl-exp') || '7 days');
    if (!isFull()) fd.append('total_price', (totInput?.value || '').trim());
    if (!isAuto) {
      var dt = dateTimeValue();
      if (dt) fd.append('date_time', dt);
    }
    return fd;
  }

  function setHead(title, sub, icon) {
    if (headTitle) headTitle.textContent = title;
    if (headSub) {
      headSub.textContent = sub || '';
      headSub.hidden = !sub;
    }
    if (headIcon) headIcon.textContent = icon || 'link';
  }

  function showStep(step) {
    if (formView) formView.hidden = step !== 'form';
    if (doneView) doneView.hidden = step !== 'done';
    if (formFooter) formFooter.hidden = step !== 'form';
    if (doneFooter) doneFooter.hidden = step !== 'done';
    var body = modal.querySelector('.pl-pbd:not([hidden])');
    if (body) body.scrollTop = 0;
  }

  function resetForm() {
    if (amtInput) amtInput.value = '';
    if (totInput) totInput.value = '';
    if (titleInput) titleInput.value = '';
    setSeg('pl-type', 'dep');
    setSeg('pl-dur', '3h');
    setSeg('pl-exp', '7 days');
    if (totalWrap) totalWrap.hidden = false;
    resetDate();
    if (calEl) calEl.hidden = true;
    clearErrors();
    lastLinkUrl = '';
    syncSum();
  }

  function openModal() {
    resetForm();
    createdThisOpen = false;
    setHead('New payment link', 'Takes the payment and creates the booking once the client pays.', 'link');
    showStep('form');
    modal.hidden = false;
    document.body.style.overflow = 'hidden';
    setTimeout(function () {
      try { amtInput?.focus(); } catch (e) {}
    }, 30);
  }

  function closeModal() {
    modal.hidden = true;
    document.body.style.overflow = '';
    if (calEl) calEl.hidden = true;
    if (reloadOnDone && createdThisOpen) {
      window.location.reload();
    }
  }

  function setLoading(loading) {
    if (!goBtn) return;
    goBtn.disabled = loading || !canPayLinks;
    var label = document.getElementById('pl-got');
    if (label) label.textContent = loading ? 'Generating…' : 'Generate link';
  }

  function showDone(link) {
    lastLinkUrl = link.url || '';
    if (urlEl) urlEl.textContent = link.display_url || link.url || '';
    if (qrEl && link.url) {
      qrEl.src = 'https://api.qrserver.com/v1/create-qr-code/?size=168x168&data=' + encodeURIComponent(link.url);
      qrEl.alt = 'QR code';
    }
    var dtxt = document.getElementById('pl-dtxt');
    if (dtxt) dtxt.textContent = 'Send the link to the client, or show the QR code.';
    if (dinfoEl) {
      dinfoEl.hidden = false;
      var infoText = dinfoEl.querySelector('span:last-child');
      if (infoText) {
        infoText.textContent = 'The booking is created automatically once the client pays. You’ll find this link under Bookings > Payment links.';
      }
    }
    var kind = isFull() ? 'full payment' : 'deposit';
    setHead('Payment link ready', (link.amount_formatted || '') + ' ' + kind, 'check_circle');
    showStep('done');
  }

  function copyLink(btn) {
    if (!lastLinkUrl) return;
    var done = function () {
      if (!btn) return;
      if (!btn.getAttribute('data-label')) {
        btn.setAttribute('data-label', btn.textContent.replace(/content_copy|check/g, '').trim() || 'Copy');
      }
      var ms = btn.querySelector('.ms');
      if (ms) ms.textContent = 'check';
      Array.prototype.slice.call(btn.childNodes).forEach(function (n) {
        if (n.nodeType === 3) n.textContent = ' Copied';
      });
      setTimeout(function () {
        if (ms) ms.textContent = 'content_copy';
        Array.prototype.slice.call(btn.childNodes).forEach(function (n) {
          if (n.nodeType === 3) n.textContent = ' ' + (btn.getAttribute('data-label') || 'Copy');
        });
      }, 1500);
      };

      if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(lastLinkUrl).then(done).catch(function () {});
      return;
    }
    var temp = document.createElement('textarea');
    temp.value = lastLinkUrl;
    document.body.appendChild(temp);
    temp.select();
    try { document.execCommand('copy'); } catch (e) {}
    document.body.removeChild(temp);
    done();
  }

  function postJson(url, body) {
    return fetch(url, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': csrf(),
      },
      body: body,
    }).then(function (response) {
      return response.json().then(function (data) {
        return { status: response.status, data: data };
      }).catch(function () {
        return { status: response.status, data: {} };
      });
    });
  }

  document.querySelectorAll('[data-open-payment-link]').forEach(function (el) {
    el.addEventListener('click', function (e) {
      e.preventDefault();
      openModal();
    });
  });

  modal.querySelectorAll('[data-plx]').forEach(function (el) {
    el.addEventListener('click', closeModal);
  });

  modal.querySelectorAll('[data-pldone]').forEach(function (el) {
    el.addEventListener('click', closeModal);
  });

  modal.querySelectorAll('[data-plcopy]').forEach(function (el) {
    el.addEventListener('click', function (e) {
      e.preventDefault();
      copyLink(el);
    });
  });

  modal.addEventListener('click', function (e) {
    if (e.target === modal) {
      closeModal();
      return;
    }

    if (e.target.closest('#pl-date')) {
      if (!canPayLinks || !calEl) return;
      clearErrors();
      calEl.hidden = !calEl.hidden;
      if (!calEl.hidden) {
        if (S.day) {
          S.pday = S.day;
          S.pmon = new Date(S.day.getFullYear(), S.day.getMonth(), 1);
        }
        drawCal();
      }
      return;
    }

    var mn = e.target.closest('[data-mnav]');
    if (mn && !mn.disabled) {
      S.pmon = new Date(S.pmon.getFullYear(), S.pmon.getMonth() + +mn.getAttribute('data-mnav'), 1);
      drawCal();
      return;
    }

    var dayBtnEl = e.target.closest('.pl-mg button');
    if (dayBtnEl && !dayBtnEl.disabled) {
      var q = dayBtnEl.getAttribute('data-iso').split('-');
      S.pday = new Date(+q[0], +q[1] - 1, +q[2]);
      drawCal();
      return;
    }

    var timeBtn = e.target.closest('.pl-times button');
    if (timeBtn && !timeBtn.disabled) {
      setDate(S.pday, +timeBtn.getAttribute('data-h'));
      if (calEl) calEl.hidden = true;
      if (dateErr) { dateErr.hidden = true; dateErr.textContent = ''; }
      dateBtn?.classList.remove('pl-bad');
      return;
    }

    if (e.target.closest('[data-nodate]')) {
      setDate(null);
      if (calEl) calEl.hidden = true;
    }
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && !modal.hidden) closeModal();
    if (e.key === 'Enter' && e.target && e.target.id === 'pl-date') {
      e.preventDefault();
      dateBtn?.click();
    }
  });

  document.getElementById('pl-type')?.addEventListener('click', function (e) {
    const btn = e.target.closest('button[data-v]');
    if (!btn) return;
    selectInGroup(this, btn);
    if (totalWrap) totalWrap.hidden = btn.getAttribute('data-v') !== 'dep';
    syncSum();
  });

  document.getElementById('pl-dur')?.addEventListener('click', function (e) {
    const btn = e.target.closest('button[data-v]');
    if (!btn) return;
    selectInGroup(this, btn);
    var err = document.getElementById('pl-e-dur');
    if (err) { err.hidden = true; err.textContent = ''; }
  });

  document.getElementById('pl-exp')?.addEventListener('click', function (e) {
    const btn = e.target.closest('button[data-v]');
    if (!btn) return;
    selectInGroup(this, btn);
  });

  amtInput?.addEventListener('input', function () {
    amtField?.classList.remove('pl-bad');
    var err = document.getElementById('pl-e-amt');
    if (err) { err.hidden = true; err.textContent = ''; }
    syncSum();
  });
  totInput?.addEventListener('input', function () {
    totField?.classList.remove('pl-bad');
    var err = document.getElementById('pl-e-tot');
    if (err) { err.hidden = true; err.textContent = ''; }
    syncSum();
  });
  titleInput?.addEventListener('input', function () {
    titleField?.classList.remove('pl-bad');
    var err = document.getElementById('pl-e-title');
    if (err) { err.hidden = true; err.textContent = ''; }
  });

  goBtn?.addEventListener('click', function (e) {
    e.preventDefault();
    if (!canPayLinks || busy) return;
    clearErrors();
    syncSum();
    if (bad) {
      showErrors({ total_price: ['Total price must be greater than the amount.'] });
      return;
    }

    var fd = buildFormData();
    busy = true;
    setLoading(true);

    postJson(validateUrl, fd)
      .then(function (result) {
        if (result.status === 422 && result.data && result.data.errors) {
          showErrors(result.data.errors);
          return null;
        }
        if (!(result.status === 200 && result.data && result.data.success)) {
          var formErr = document.getElementById('pl-e-form');
          if (formErr) {
            formErr.textContent = (result.data && result.data.message) || 'Could not validate the payment link.';
            formErr.hidden = false;
          }
          return null;
        }
        // Same controller store endpoint after validation — then show template done screen
        return postJson(storeUrl, buildFormData());
      })
      .then(function (result) {
        if (!result) return;
        if (result.status === 422 && result.data && result.data.errors) {
          showErrors(result.data.errors);
          return;
        }
        if (result.status === 200 && result.data && result.data.success && result.data.payment_link) {
          createdThisOpen = true;
          showDone(result.data.payment_link);
          return;
        }
        var formErr = document.getElementById('pl-e-form');
        if (formErr) {
          formErr.textContent = (result.data && result.data.message) || 'Could not generate the link.';
          formErr.hidden = false;
        }
      })
      .catch(function () {
        var formErr = document.getElementById('pl-e-form');
        if (formErr) {
          formErr.textContent = 'Could not generate the link. Please try again.';
          formErr.hidden = false;
        }
      })
      .finally(function () {
        busy = false;
        setLoading(false);
      });
    });
  })();
</script>
