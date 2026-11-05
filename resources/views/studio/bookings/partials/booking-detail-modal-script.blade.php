<script>
(function () {
  var modal = document.getElementById('m-booking');
  if (!modal) return;
  if (modal.parentNode !== document.body) document.body.appendChild(modal);

  function esc(s) {
    return String(s == null ? '' : s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }
  function text(id, v) {
    var el = document.getElementById(id);
    if (el) el.textContent = v == null || v === '' ? '—' : String(v);
  }
  function show(id, on) {
    var el = document.getElementById(id);
    if (el) el.style.display = on ? '' : 'none';
  }
  function loadModal(id) {
    var el = document.getElementById('bk-data-' + id);
    if (!el) return null;
    try { return JSON.parse(el.textContent || '{}'); } catch (e) { return null; }
  }

  function setTab(name) {
    modal.querySelectorAll('[data-sbdm-tab]').forEach(function (btn) {
      var on = btn.getAttribute('data-sbdm-tab') === name;
      btn.classList.toggle('on', on);
      btn.setAttribute('aria-selected', on ? 'true' : 'false');
    });
    document.getElementById('sbdmPanelDetails').hidden = name !== 'details';
    document.getElementById('sbdmPanelRequest').hidden = name !== 'request';
  }

  function open(btn) {
    var data = loadModal(btn.getAttribute('data-id'));
    if (!data) return;

    document.getElementById('m-booking-t').textContent = data.title || 'Booking';
    text('sbdmRef', data.reference);
    var status = document.getElementById('sbdmStatus');
    status.className = 'sbdm-pill ' + (data.status_pill || 'k');
    status.style.margin = '6px 0';
    status.style.display = 'inline-flex';
    status.textContent = data.status || '—';
    text('sbdmType', data.type_label);
    text('sbdmClient', data.client);
    text('sbdmArtist', data.artist);
    text('sbdmService', data.service);
    text('sbdmThrough', data.through);
    text('sbdmDeposit', data.deposit);
    text('sbdmDate', data.date);
    text('sbdmTime', data.time);
    text('sbdmDuration', data.duration);
    text('sbdmTimezone', data.timezone);
    text('sbdmBalance', data.balance);

    var img = document.getElementById('sbdmImage');
    if (data.image_url) {
      img.style.backgroundImage = 'url("' + String(data.image_url).replace(/"/g, '\\"') + '")';
    } else {
      img.style.backgroundImage = '';
    }

    var moneyPill = document.getElementById('sbdmMoneyPill');
    moneyPill.className = 'sbdm-pill ' + (data.money_pill_class || 'a');
    moneyPill.textContent = data.money_pill || 'Pending';

    text('sbdmMoneyDepositLabel', data.deposit_money_label || 'Client paid · deposit');
    text('sbdmMoneyDeposit', data.deposit_money);
    text('sbdmStripeFee', data.stripe_fee);

    if (data.booking_fee) {
      show('sbdmBookingFeeRow', true);
      text('sbdmBookingFee', data.booking_fee);
      text('sbdmBookingFeeNote', data.booking_fee_note ? '(' + data.booking_fee_note + ')' : '');
    } else {
      show('sbdmBookingFeeRow', false);
    }

    var hasSplit = !!data.has_split;
    show('sbdmSplitRow', hasSplit);
    show('sbdmArtistGetsRow', hasSplit);
    show('sbdmStudioShareRow', hasSplit);
    show('sbdmStudioStripeRow', hasSplit);
    if (hasSplit) {
      text('sbdmSplitValue', data.split_value);
      text('sbdmArtistGetsLabel', data.artist_gets_label || 'Artist gets');
      text('sbdmArtistGetsNote', data.artist_gets_note ? '(' + data.artist_gets_note + ')' : '');
      text('sbdmArtistGets', data.artist_gets);
      text('sbdmStudioShareNote', data.studio_share_note ? '(' + data.studio_share_note + ')' : '');
      text('sbdmStudioShare', data.studio_share);
      text('sbdmStudioStripeNote', data.studio_stripe_note ? '(' + data.studio_stripe_note + ')' : '');
      text('sbdmStudioStripe', data.studio_stripe);
    }
    if (data.studio_booking_fee) {
      show('sbdmStudioBookingFeeRow', true);
      text('sbdmStudioBookingFee', data.studio_booking_fee);
      text('sbdmStudioBookingFeeNote', data.studio_booking_fee_note ? '(' + data.studio_booking_fee_note + ')' : '');
    } else {
      show('sbdmStudioBookingFeeRow', false);
    }
    text('sbdmStudioGets', data.studio_gets);

    if (data.footnote) {
      show('sbdmFootnote', true);
      text('sbdmFootnote', data.footnote);
    } else {
      show('sbdmFootnote', false);
    }
    if (data.balance_note) {
      show('sbdmBalanceNote', true);
      text('sbdmBalanceNote', data.balance_note);
    } else {
      show('sbdmBalanceNote', false);
    }

    var req = data.request || {};
    text('sbdmReqTitle', req.title || 'Original request');
    text('sbdmReqMeta', req.meta || '—');
    var reqIcon = document.getElementById('sbdmReqIcon');
    if (reqIcon) reqIcon.textContent = req.icon || 'brush';

    var qa = Array.isArray(req.fields) && req.fields.length
      ? req.fields
      : (Array.isArray(data.answers) ? data.answers : []);
    var qaList = document.getElementById('sbdmQaList');
    var qaEmpty = document.getElementById('sbdmQaEmpty');
    var reqCaption = document.getElementById('sbdmReqCaption');
    qaList.innerHTML = '';
    if (!qa.length) {
      qaEmpty.style.display = '';
      qaList.style.display = 'none';
    } else {
      qaEmpty.style.display = 'none';
      qaList.style.display = '';
      qa.forEach(function (a) {
        var label = a.label || a.q || '';
        var value = a.value || a.a || '—';
        qaList.innerHTML += '<div class="sbdm-req-qa-item"><div class="lbl">' + esc(label) + '</div><div class="val">' + esc(value) + '</div></div>';
      });
    }
    if (req.caption) {
      reqCaption.style.display = '';
      reqCaption.textContent = req.caption;
    } else {
      reqCaption.style.display = 'none';
      reqCaption.textContent = '';
    }

    var msg = document.getElementById('sbdmMessage');
    if (data.message_artist_id && data.message_booking_id) {
      msg.style.display = '';
      document.getElementById('sbdmMessageLabel').textContent = data.message_label || 'Message';
      msg._chatContext = {
        artistId: data.message_artist_id,
        bookingId: data.message_booking_id,
        artistName: data.message_artist_name || 'Artist',
        artistInitials: data.message_artist_initials || '?',
        bookingRef: data.message_booking_ref || '',
        bookingMeta: data.message_booking_meta || '',
        canChat: data.message_can_chat !== false,
        inboxHref: data.message_url || '#'
      };
    } else {
      msg.style.display = 'none';
      msg._chatContext = null;
    }

    setTab('details');
    modal.hidden = false;
  }

  modal.querySelectorAll('[data-sbdm-tab]').forEach(function (btn) {
    btn.addEventListener('click', function () { setTab(btn.getAttribute('data-sbdm-tab')); });
  });
  document.querySelectorAll('.js-view-booking').forEach(function (btn) {
    btn.addEventListener('click', function () { open(btn); });
  });
  modal.querySelectorAll('[data-close]').forEach(function (el) {
    el.addEventListener('click', function () { modal.hidden = true; });
  });
  modal.addEventListener('click', function (e) {
    if (e.target === modal) modal.hidden = true;
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && !modal.hidden) {
      var msgModal = document.getElementById('studioMessageArtistModal');
      if (msgModal && msgModal.classList.contains('studio-msm-open')) return;
      modal.hidden = true;
    }
  });

  var msgBtn = document.getElementById('sbdmMessage');
  if (msgBtn) {
    msgBtn.addEventListener('click', function (e) {
      e.preventDefault();
      var ctx = msgBtn._chatContext;
      if (!ctx) return;
      modal.hidden = true;
      if (typeof window.openStudioBookingMessageModal === 'function') {
        window.openStudioBookingMessageModal(ctx);
      } else if (ctx.inboxHref) {
        window.location.href = ctx.inboxHref;
      }
    });
  }
})();
</script>
