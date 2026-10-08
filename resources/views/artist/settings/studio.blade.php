@extends('layouts.new-artist-dashboard-layout')

@section('title', 'Account')

@section('styles')
<style>
  .st-hub .btn{
    display:inline-flex;align-items:center;justify-content:center;gap:8px;
    background:var(--ink);color:#fff;border:0;border-radius:10px;padding:10px 16px;
    font-family:inherit;font-weight:600;font-size:13.5px;text-decoration:none;cursor:pointer;
    white-space:nowrap;-webkit-appearance:none;appearance:none
  }
  .st-hub .btn .ms{font-size:18px}
  .st-hub .btn.ghost{background:#fff;color:var(--ink);border:1px solid var(--line)}
  .st-hub .btn.sm{padding:6px 11px;font-size:12.5px;border-radius:8px}
  .st-hub .btn.sm .ms{font-size:16px}
  .st-hub .fl{font-size:13px;font-weight:700;margin-bottom:6px;display:block}
  .st-hub .help{font-size:12px;color:var(--faint);margin-top:6px}
  .st-hub .in{
    background:#fff;border:1px solid var(--line);border-radius:10px;padding:10px 13px;
    font:inherit;font-size:13.5px;color:var(--ink);display:flex;align-items:center;gap:8px;
    min-height:40px;width:100%;outline:none;box-sizing:border-box
  }
  .st-hub input.in{font:inherit;font-size:13.5px;color:var(--ink);width:100%;outline:none}
  .st-hub input.in:focus,.st-hub .in:focus-within{border-color:#3E007C;box-shadow:0 0 0 3px #F3E8FF}
  .st-hub .in .ms{font-size:18px;color:var(--faint)}
  .st-hub .grid{display:grid;gap:16px}
  .popend{display:flex;gap:12px;align-items:center;background:#FFF7E6;border:1px solid #F3DDB0;border-radius:14px;padding:12px 16px;color:#8A5A00}
  .popend[hidden]{display:none}
  .popend .ms{color:#B7791F}
  .po{display:flex;gap:12px;align-items:flex-start;background:#fff;border:1px solid var(--line);border-radius:14px;padding:16px 18px;cursor:pointer;margin-bottom:10px}
  .po.sel{border:2px solid #3E007C;padding:15px 17px;background:#FDFAFF}
  .po input{accent-color:#3E007C;width:18px;height:18px;margin-top:2px;flex-shrink:0}
  .po b{font-size:14.5px}
  .po .d{font-size:13px;color:var(--muted);margin-top:3px;line-height:1.5}
  .lockbar{display:flex;gap:8px;align-items:center;background:#F0EDF2;color:var(--muted);border-radius:10px;padding:9px 12px;font-size:12.5px}
  .tgrid{display:grid;grid-template-columns:1fr 1fr;gap:10px}
  .tgrid .po{margin:0}
  .picked{background:#fff;border:2px solid #3E007C;border-radius:14px;padding:16px 18px}
  .logo2{width:48px;height:48px;border-radius:12px;background:#EDD9FF;color:#3E007C;font-weight:800;display:flex;align-items:center;justify-content:center;flex-shrink:0}
  .kv{display:flex;justify-content:space-between;gap:16px;padding:6px 0;font-size:13.5px}
  .kv span:first-child{color:var(--muted)}
  .plist .prow{display:flex;gap:14px;align-items:center;padding:14px 0;border-bottom:1px solid #F3EEF4;flex-wrap:wrap}
  .plist .prow:last-child{border-bottom:0}
  .plist .prow>div:nth-child(2){flex:1;min-width:220px}
  .mlbox{display:flex;align-items:center;gap:8px;border:1px solid var(--line,#E7E1EA);background:#FAF8FB;border-radius:10px;padding:8px 8px 8px 12px;min-width:0}
  .mlbox>.ms{font-size:18px;color:#3E007C;flex:none}
  .mlbox a.mlk{flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#3E007C;font-weight:600;font-size:13px;text-decoration:none}
  .mlbox a.mlk:hover{text-decoration:underline}
  .mlcopy{flex:none;border:1px solid #D9D2DC;background:#fff;border-radius:8px;padding:5px 10px;font:inherit;font-size:12.5px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:4px}
  .mlcopy .ms{font-size:15px}
  .mlinline{display:inline-flex;align-items:center;gap:10px;flex-wrap:wrap}
  .mlinline a{color:#3E007C;font-weight:600;text-decoration:none}
  .twonote2{display:flex;gap:10px;align-items:center;flex-wrap:wrap;background:#EAF7F4;border:1px solid #BFE5DC;color:#0E5C50;border-radius:12px;padding:10px 12px;font-size:13px;margin-top:14px}
  .st-toast{
    position:fixed;top:22px;right:24px;background:#1A1A1A;color:#fff;padding:12px 18px;border-radius:10px;
    font-weight:600;font-size:14px;z-index:400;box-shadow:0 10px 30px rgba(0,0,0,.22);
    display:flex;gap:10px;align-items:center;opacity:0;transform:translateX(calc(100% + 40px));
    transition:opacity .25s,transform .35s;pointer-events:none
  }
  .st-toast.on{opacity:1;transform:none}
  .st-toast .ms{color:#3DD68C;font-size:20px}
  @media (max-width:700px){.tgrid{grid-template-columns:1fr}}
  @media (max-width:900px){.st-toast{left:16px;right:16px;top:74px}}
</style>
@endsection

@section('content')
@php
  $viewState = $viewState ?? 'none';
  $primaryCard = $primaryCard ?? null;
  $pendingCard = $pendingCard ?? null;
  $splitCard = $splitCard ?? null;
  $multiCards = $multiCards ?? [];
  $pastCards = $pastCards ?? collect();
  $emptyMeta = $emptyMeta ?? ['left_name' => null, 'left_on' => null, 'upcoming' => 0];

  $bodyState = $viewState;
  if (count($multiCards) > 1) {
    $bodyState = 'multiple';
  } elseif (in_array($viewState, ['join_pending', 'split_proposed'], true)) {
    if (! $primaryCard) {
      $bodyState = 'none';
    } elseif (! empty($primaryCard['on_bookpay'])) {
      $bodyState = 'on_bookpay';
    } elseif (! empty($primaryCard['relationship'])) {
      $bodyState = 'not_on_bookpay';
    } else {
      $bodyState = 'own_space';
    }
  }
@endphp

<div class="st-hub">
  <div class="head">
          <div>
      <h1>
        Account
        <a class="help-q"
           href="https://help.inkjin.com/en/articles/17201426-dashboard-account-studio"
           target="_blank"
           rel="noopener"
           data-help-article="09c-account-studio"
           title="Help with this page"
           aria-label="Help with this page">
          <span class="ms">help</span>
        </a>
      </h1>
      <div class="sub">Your login, contact details, studio and region. Set once, rarely changed.</div>
            </div>
          </div>

  @include('artist.partials.profile-settings-tabs', ['activeProfileTab' => 'studio'])

  @if($viewState === 'join_pending' && $pendingCard)
    @include('artist.settings.partials.banner-join-pending', ['pendingCard' => $pendingCard])
  @endif

  @if($viewState === 'split_proposed' && $splitCard)
    @include('artist.settings.partials.banner-split-proposed', ['splitCard' => $splitCard, 'primaryCard' => $primaryCard])
  @endif

  @if($viewState === 'not_on_bookpay' && $primaryCard && ($primary?->connection_type ?? null) === \App\Models\UserStudio::CONNECTION_NOT_CONNECTED)
    @include('artist.settings.partials.banner-invite', ['name' => $primaryCard['name']])
  @endif

  @if($bodyState === 'none')
    @include('artist.settings.partials.empty-studio', ['emptyMeta' => $emptyMeta])
  @elseif($bodyState === 'multiple')
    @include('artist.settings.partials.multiple-studios', ['multiCards' => $multiCards])
  @elseif($bodyState === 'not_on_bookpay' && $primaryCard)
    @include('artist.settings.partials.editable-not-on-bookpay', [
      'card' => $primaryCard,
      'relationshipOptions' => $relationshipOptions,
      'workspaceOptions' => $workspaceOptions,
    ])
  @elseif($bodyState === 'own_space' && $primaryCard)
    @include('artist.settings.partials.editable-own-space', [
      'card' => $primaryCard,
      'ownSpaceWorkspaceOptions' => $ownSpaceWorkspaceOptions,
    ])
  @elseif($bodyState === 'on_bookpay' && $primaryCard)
    @include('artist.settings.partials.on-bookpay-studio', ['card' => $primaryCard])
  @endif

  @include('artist.settings.partials.past-studios', ['pastCards' => $pastCards])
    </div>

<div class="st-toast" id="studioToast" role="status">
  <span class="ms">check_circle</span>
  <span id="studioToastMsg">Studio updated</span>
    </div>
@endsection

@section('scripts')
<script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google.place_api_key') }}&libraries=places"></script>
<script>
(function () {
  @if (session('success') || session('status') === 'studio-changed' || session('error'))
    var t = document.getElementById('studioToast');
    var m = document.getElementById('studioToastMsg');
    if (t && m) {
      m.textContent = @json(session('error') ?: (session('success') ?: 'Studio updated'));
      var icon = t.querySelector('.ms');
      if (icon) {
        icon.style.color = @json(session('error') ? '#C62828' : '#3DD68C');
        icon.textContent = @json(session('error') ? 'error' : 'check_circle');
      }
      t.classList.add('on');
      setTimeout(function () { t.classList.remove('on'); }, 2800);
    }
  @endif

  document.querySelectorAll('.po').forEach(function (label) {
    var input = label.querySelector('input[type="radio"]');
    if (!input) return;
    function sync() {
      document.querySelectorAll('input[name="' + input.name + '"]').forEach(function (r) {
        var p = r.closest('.po');
        if (p) p.classList.toggle('sel', r.checked);
      });
    }
    input.addEventListener('change', sync);
    sync();
  });

  function copyText(btn, text) {
    if (!text) return;
    var done = function () {
      var label = btn.querySelector('span:not(.ms)') || btn;
      var prev = btn.getAttribute('data-prev') || btn.textContent;
      btn.setAttribute('data-prev', prev);
      if (btn.querySelector('.ms')) {
        btn.innerHTML = '<span class="ms">check</span>Copied';
      } else {
        btn.textContent = 'Copied';
      }
      setTimeout(function () {
        if (btn.querySelector('.ms')) {
          btn.innerHTML = '<span class="ms">content_copy</span>' + (prev.indexOf('Copy link') >= 0 ? 'Copy link' : 'Copy');
        } else {
          btn.textContent = prev;
        }
      }, 1400);
    };
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(text).then(done).catch(function () {});
    }
  }

  document.addEventListener('click', function (e) {
    var b = e.target.closest('.mlcopy');
    if (!b) return;
    e.preventDefault();
    var a = b.parentNode.querySelector('a.mlk');
    copyText(b, a ? a.href : '');
  });

  function mapsHref(parts) {
    var q = parts.filter(Boolean).join(', ');
    if (!q) return '#';
    return 'https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(q);
  }

  function mapsLabel(parts) {
    var q = parts.filter(Boolean).join(', ');
    return q ? ('google.com/maps · ' + q) : 'google.com/maps';
  }

  function wireMapsAuto(form) {
    if (!form) return;
    var box = form.querySelector('.mlbox[data-auto]');
    if (!box) return;
    var link = box.querySelector('a.mlk');
    var fields = ['street_number', 'street_name', 'city', 'state', 'postal_code', 'country'];
    function refresh() {
      var parts = fields.map(function (name) {
        var el = form.querySelector('[name="' + name + '"]');
        return el ? String(el.value || '').trim() : '';
      });
      var href = mapsHref(parts);
      var hidden = form.querySelector('[name="google_maps_link"]');
      if (link) {
        link.href = href;
        link.textContent = mapsLabel(parts);
      }
      if (hidden) hidden.value = href === '#' ? '' : href;
      var addr = form.querySelector('[name="studio_address"]');
      var search = form.querySelector('[data-address-search]');
      if (addr && search) {
        var line = [parts[0], parts[1]].filter(Boolean).join(' ');
        var full = [line, parts[2], parts[4], parts[5]].filter(Boolean).join(', ');
        if (full) {
          addr.value = full;
          if (!search.matches(':focus')) search.value = full;
        }
      }
    }
    fields.forEach(function (name) {
      var el = form.querySelector('[name="' + name + '"]');
      if (el) el.addEventListener('input', refresh);
    });
    refresh();
  }

  document.querySelectorAll('form[data-studio-edit]').forEach(wireMapsAuto);

  function fillFromPlace(form, place) {
    if (!form || !place || !place.address_components) return;
    var map = {};
    place.address_components.forEach(function (c) {
      c.types.forEach(function (t) { map[t] = c.long_name; });
    });
    var set = function (name, val) {
      var el = form.querySelector('[name="' + name + '"]');
      if (el) el.value = val || '';
    };
    set('street_number', map.street_number || '');
    set('street_name', map.route || '');
    set('city', map.locality || map.postal_town || map.administrative_area_level_2 || '');
    set('state', map.administrative_area_level_1 || '');
    set('postal_code', map.postal_code || '');
    set('country', map.country || '');
    var formatted = place.formatted_address || '';
    var addr = form.querySelector('[name="studio_address"]');
    var search = form.querySelector('[data-address-search]');
    if (addr) addr.value = formatted;
    if (search) search.value = formatted;
    wireMapsAuto(form);
  }

  function initAutocomplete() {
    if (typeof google === 'undefined' || !google.maps || !google.maps.places) return;
    document.querySelectorAll('[data-address-search]').forEach(function (input) {
      var form = input.closest('form');
      var ac = new google.maps.places.Autocomplete(input, { types: ['address'], fields: ['address_components', 'formatted_address', 'geometry'] });
      ac.addListener('place_changed', function () {
        fillFromPlace(form, ac.getPlace());
      });
  });
  }

  if (document.readyState === 'complete') initAutocomplete();
  else window.addEventListener('load', initAutocomplete);

  function showStudioToast(message, isError) {
    var toast = document.getElementById('studioToast');
    var msg = document.getElementById('studioToastMsg');
    if (!toast || !msg) return;
    msg.textContent = message || (isError ? 'Something went wrong.' : 'Studio updated');
    var icon = toast.querySelector('.ms');
    if (icon) {
      icon.style.color = isError ? '#C62828' : '#3DD68C';
      icon.textContent = isError ? 'error' : 'check_circle';
    }
    toast.classList.add('on');
    setTimeout(function () { toast.classList.remove('on'); }, 2800);
  }

  function escapeHtml(str) {
    return String(str || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function confirmLeaveStudio(studioName, onConfirm) {
    var name = (studioName || '').trim() || 'this studio';
    var ov = document.createElement('div');
    ov.className = 'st-leave-ov';
    ov.style.cssText = 'position:fixed;inset:0;background:rgba(20,10,30,.45);display:flex;align-items:center;justify-content:center;z-index:500;padding:16px';
    ov.innerHTML =
      '<div role="dialog" aria-modal="true" aria-labelledby="stLeaveTitle" style="background:#fff;border-radius:22px;width:440px;max-width:100%;box-shadow:0 20px 60px rgba(0,0,0,.25);overflow:hidden">' +
        '<div style="padding:24px 24px 18px">' +
          '<div style="width:48px;height:48px;border-radius:12px;background:#FDECEC;color:#C62828;display:flex;align-items:center;justify-content:center;margin-bottom:14px"><span class="ms">logout</span></div>' +
          '<h2 id="stLeaveTitle" style="font-size:20px;font-weight:800;margin:0 0 8px">Leave ' + escapeHtml(name) + '?</h2>' +
          '<p style="color:#3a3a3a;line-height:1.55;margin:0">You\'ll stop working at this studio on Bookpay. Existing bookings stay. You can join again later.</p>' +
        '</div>' +
        '<div style="display:flex;gap:12px;padding:16px 24px;background:#FCF6FE;border-top:1px solid #F0E4F5;flex-wrap:wrap">' +
          '<button type="button" class="btn ghost" data-leave-cancel style="flex:1;justify-content:center;padding:13px;font:inherit;font-weight:600;cursor:pointer;border:1px solid #E8DFEA;background:#fff;color:var(--ink,#1A1A1A);border-radius:10px">Cancel</button>' +
          '<button type="button" class="btn" data-leave-ok style="flex:1;justify-content:center;padding:13px;font:inherit;font-weight:600;cursor:pointer;border:0;background:#C62828;color:#fff;border-radius:10px">Leave studio</button>' +
        '</div>' +
      '</div>';
    document.body.appendChild(ov);

    function close() {
      ov.remove();
      document.removeEventListener('keydown', onKey, true);
    }
    function onKey(ev) {
      if (ev.key === 'Escape') {
        ev.stopPropagation();
        ev.preventDefault();
        close();
      }
    }
    document.addEventListener('keydown', onKey, true);
    ov.addEventListener('click', function (ev) {
      if (ev.target === ov || ev.target.closest('[data-leave-cancel]')) {
        close();
        return;
      }
      if (ev.target.closest('[data-leave-ok]')) {
        close();
        if (typeof onConfirm === 'function') onConfirm();
      }
    });
  }

  function postStudioAction(url, userStudioId, btn, loadingText) {
    var body = new URLSearchParams();
    body.set('_token', @json(csrf_token()));
    if (userStudioId) body.set('user_studio_id', String(userStudioId));

    var prevHtml = btn ? btn.innerHTML : '';
    if (btn) {
      btn.disabled = true;
      btn.textContent = loadingText || 'Sending…';
    }

    return fetch(url, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': @json(csrf_token()),
      },
      body: body,
      credentials: 'same-origin',
    }).then(function (res) {
      return res.json().then(function (data) {
        return { ok: res.ok, data: data || {} };
      }).catch(function () {
        return { ok: res.ok, data: {} };
      });
    }).finally(function () {
      if (btn) {
        btn.disabled = false;
        btn.innerHTML = prevHtml;
      }
    });
  }

  document.addEventListener('click', function (e) {
    var resend = e.target.closest('.js-studio-resend-join');
    if (resend) {
      e.preventDefault();
      postStudioAction(
        @json(route('settings.studio.resend-join')),
        resend.getAttribute('data-user-studio-id'),
        resend,
        'Sending…'
      ).then(function (result) {
        showStudioToast(
          (result.data && result.data.message) || (result.ok ? 'Join request email resent.' : 'Could not resend the email.'),
          !(result.ok && result.data && result.data.success)
        );
      }).catch(function () {
        showStudioToast('Could not resend the email.', true);
      });
      return;
    }

    var cancel = e.target.closest('.js-studio-cancel-join');
    if (cancel) {
      e.preventDefault();
      postStudioAction(
        @json(route('settings.studio.cancel-join')),
        cancel.getAttribute('data-user-studio-id'),
        cancel,
        'Cancelling…'
      ).then(function (result) {
        var ok = result.ok && result.data && result.data.success;
        showStudioToast(
          (result.data && result.data.message) || (ok ? 'Join request cancelled.' : 'Could not cancel the request.'),
          !ok
        );
        if (!ok) return;

        var bar = document.getElementById('joinbar');
        if (bar) bar.remove();

        var card = cancel.closest('.picked');
        if (card && card.style && String(card.style.borderStyle || '').indexOf('dashed') !== -1) {
          card.remove();
        } else if (card && card.getAttribute('style') && card.getAttribute('style').indexOf('dashed') !== -1) {
          card.remove();
        }
      }).catch(function () {
        showStudioToast('Could not cancel the request.', true);
      });
      return;
    }

    var leave = e.target.closest('.js-studio-leave');
    if (leave) {
      e.preventDefault();
      confirmLeaveStudio(leave.getAttribute('data-studio-name'), function () {
        postStudioAction(
          @json(route('settings.studio.leave')),
          leave.getAttribute('data-user-studio-id'),
          leave,
          'Leaving…'
        ).then(function (result) {
          var ok = result.ok && result.data && result.data.success;
          showStudioToast(
            (result.data && result.data.message) || (ok ? 'You left the studio.' : 'Could not leave the studio.'),
            !ok
          );
          if (!ok) return;
          window.location.reload();
        }).catch(function () {
          showStudioToast('Could not leave the studio.', true);
        });
      });
    }
  });
})();
</script>
@endsection
