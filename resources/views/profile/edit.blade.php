@extends('layouts.new-artist-dashboard-layout')

@section('title', 'Account')

@section('styles')
<style>
  .acc-page .fl{font-size:13px;font-weight:700;margin-bottom:6px;display:block}
  .acc-page .help{font-size:12px;color:var(--faint);margin-top:6px;line-height:1.4}
  .acc-page .ch h3{font-size:16px;font-weight:700;margin:0}
  .acc-page .ch .faint{font-size:12.5px;font-weight:400;margin-top:2px;color:var(--faint);line-height:1.4}
  .acc-page .ch .faint a{color:#3E007C;font-weight:600;text-decoration:none}
  .acc-page .in{
    font-family:inherit;font-size:13.5px;font-weight:400;line-height:normal;color:var(--ink)
  }
  .acc-page .in.ro{background:#F5F2F6;color:#6F6874}
  .acc-page .in.ro .ms{margin-left:auto;color:var(--faint);font-size:18px}
  .acc-page .in input{
    border:0;outline:0;flex:1;min-width:0;background:none;padding:10px 0;color:inherit;
    font-family:inherit;font-size:inherit;font-weight:inherit;line-height:inherit
  }
  .acc-page .in.bad{border-color:var(--red)}
  .acc-page .btn{
    display:inline-flex;align-items:center;justify-content:center;gap:8px;
    background:var(--ink);color:#fff;border:0;border-radius:10px;padding:10px 16px;
    font-family:inherit;font-weight:600;font-size:13.5px;line-height:normal;
    white-space:nowrap;cursor:pointer;text-decoration:none;-webkit-appearance:none;appearance:none
  }
  .acc-page .btn .ms{font-size:18px}
  .acc-page .btn.ghost{background:#fff;color:var(--ink);border:1px solid var(--line)}
  .acc-page .btn.sm{padding:6px 11px;font-size:12.5px;font-weight:600;border-radius:8px}
  .acc-page .btn.sm .ms{font-size:16px}
  .acc-page .btn.pgsave{opacity:.4;pointer-events:none}
  .acc-page .btn.pgsave.on{opacity:1;pointer-events:auto}
  .acc-page .btn:disabled{opacity:.55;cursor:not-allowed}
  .acc-err{color:var(--red);font-size:12.5px;font-weight:400;margin-top:6px}
  .acc-err[hidden]{display:none!important}
  .acc-toast{
    position:fixed;top:22px;right:24px;background:#1A1A1A;color:#fff;padding:12px 18px;border-radius:10px;
    font-weight:600;font-size:14px;z-index:400;box-shadow:0 10px 30px rgba(0,0,0,.22);
    display:flex;gap:10px;align-items:center;opacity:0;transform:translateX(calc(100% + 40px));
    transition:opacity .25s,transform .35s cubic-bezier(.2,.8,.2,1);pointer-events:none
  }
  .acc-toast.on{opacity:1;transform:none}
  .acc-rail h3{font-size:14.5px;font-weight:700;margin:0;letter-spacing:normal}
  .acc-rail .muted{font-size:13px;font-weight:400;line-height:1.5;margin-top:6px;color:var(--muted)}
  .acc-rail .muted b{font-weight:700;color:var(--ink)}
  .acc-rail .btn.sm{margin-top:12px}
  .acc-rail .row{
    display:flex;align-items:center;gap:12px;
    font-size:14px;font-weight:400;color:inherit;text-decoration:none;cursor:pointer
  }
  .acc-rail .row .ms{font-size:20px;color:var(--ink);flex-shrink:0}
  .acc-rail .row .ms.faint{color:var(--faint);margin-left:auto}
  .acc-rail .row > span:nth-child(2){flex:1;min-width:0}
  .acc-leave{
    position:fixed;inset:0;background:rgba(20,10,30,.45);display:none;align-items:center;justify-content:center;
    z-index:500;padding:16px
  }
  .acc-leave.on{display:flex}
  .acc-leave-box{
    background:#fff;border-radius:22px;width:420px;max-width:100%;
    box-shadow:0 20px 60px rgba(0,0,0,.25);overflow:hidden
  }
  .acc-leave-box .acc-leave-ico{
    width:48px;height:48px;border-radius:12px;background:#FFF3DC;color:#B7791F;
    display:flex;align-items:center;justify-content:center;margin-bottom:14px
  }
  .acc-leave-box h2{font-size:20px;font-weight:800;margin-bottom:8px}
  .acc-leave-box p{color:#3a3a3a;line-height:1.55;margin:0}
  .acc-leave-f{
    display:flex;gap:10px;padding:16px 24px;background:#FCF6FE;border-top:1px solid #F0E4F5;flex-wrap:wrap
  }
  .acc-leave-f .btn{flex:1;justify-content:center}
  @media (max-width:900px){
    .acc-toast{left:16px;right:16px;top:74px}
  }
</style>
@endsection

@section('content')
@php
  $ud = $userDetail;
  $studioName = $ud?->resolvedStudioName() ?? '';
  $country = trim((string) ($ud?->country ?? ''));
  $dateFmt = trim((string) ($ud?->date_time_format ?? ''));
  $regionalLabel = implode(', ', array_values(array_filter([
      $country !== '' ? $country : null,
      $dateFmt !== '' ? $dateFmt : null,
  ])));
  $username = old('user_name', $ud->user_name ?? '');
@endphp

  <div class="acc-page">
    <div class="head">
      <div>
        <h1>
          Account
          <a class="help-q"
             href="https://help.inkjin.com/en/articles/17201412-dashboard-account-profile"
             target="_blank"
             rel="noopener"
             data-help-article="09-account"
             title="Help with this page"
             aria-label="Help with this page">
            <span class="ms">help</span>
          </a>
        </h1>
        <div class="sub">Your login, contact details, studio and region. Set once, rarely changed.</div>
      </div>
    </div>

    @include('artist.partials.profile-settings-tabs', ['activeProfileTab' => 'profile'])

    <form id="profileForm" method="post" action="{{ route('profile.update') }}" novalidate>
      @csrf
      @method('patch')

      <div class="grid" style="grid-template-columns:1fr 360px;gap:24px;align-items:start">
        <div>
          <div class="card" style="margin-bottom:14px">
            <div class="ch">
              <div>
                <h3>Personal details</h3>
                <div class="faint" style="font-size:12.5px;margin-top:2px">
                  Used for your account and receipts. You can also show your full name on your page
                  (<a href="{{ route('personal-page.index') }}" style="color:#3E007C;font-weight:600;text-decoration:none">My Page → About</a>).
                </div>
              </div>
            </div>
            <div style="padding:18px 22px">
              <div class="grid" style="grid-template-columns:1fr 1fr">
                <div>
                  <span class="fl">First name</span>
                  <input class="in" id="first_name" name="first_name" type="text" value="{{ old('first_name', $user->first_name) }}" autocomplete="given-name">
                  <div class="acc-err" id="first_name_error" hidden></div>
                </div>
                <div>
                  <span class="fl">Last name</span>
                  <input class="in" id="last_name" name="last_name" type="text" value="{{ old('last_name', $user->last_name) }}" autocomplete="family-name">
                  <div class="acc-err" id="last_name_error" hidden></div>
                </div>
              </div>

              <span class="fl" style="margin-top:16px">Username</span>
              <div class="in" id="user_name_wrap" style="padding:0 0 0 13px">
                <span style="color:#9A929E">@</span>
                <input id="user_name" name="user_name" type="text" value="{{ $username }}" maxlength="30" autocomplete="username" style="padding:10px 13px 10px 2px">
              </div>
              <div class="help">
                Match your Instagram handle. Your page link is
                @if ($username !== '')
                  {{ preg_replace('#^https?://#', '', route('public.artist', ['username' => $username])) }}.
                @else
                  inkjin.com/@yourname.
                @endif
                Letters, numbers, periods and underscores, max 30.
              </div>
              <div class="acc-err" id="user_name_error" hidden></div>
            </div>
          </div>

          <div class="card" style="margin-bottom:14px">
            <div class="ch">
              <div>
                <h3>Contact</h3>
                <div class="faint" style="font-size:12.5px;margin-top:2px">How Bookpay and your clients reach you</div>
              </div>
            </div>
            <div style="padding:18px 22px">
              <div class="grid" style="grid-template-columns:1fr 1fr">
                <div>
                  <span class="fl">Mobile number</span>
                  <input class="in" id="mobile_number" name="mobile_number" type="tel" value="{{ old('mobile_number', $ud->mobile_number ?? '') }}" autocomplete="tel">
                  <div class="help">International format, starting with +</div>
                  <div class="acc-err" id="mobile_number_error" hidden></div>
                </div>
                <div>
                  <span class="fl">Email</span>
                  <div class="in ro">
                    {{ $user->email }}
                    <span class="ms">lock</span>
                  </div>
                  <div class="help">Contact support to change your login email</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="acc-rail">
          <div class="card pad">
            <h3 style="font-size:14.5px">Looking for your photo, tagline or bio?</h3>
            <div class="muted" style="font-size:13px;margin-top:6px;line-height:1.5">
              Everything clients see now lives in <b>My Page → About</b>, where you can edit it and choose whether to show it.
            </div>
            <a href="{{ route('personal-page.index') }}" class="btn sm ghost" style="margin-top:12px;text-decoration:none">
              Go to My Page<span class="ms" style="font-size:16px">arrow_forward</span>
            </a>
          </div>

          <div class="card pad" style="margin-top:14px">
            <h3 style="font-size:14.5px">Also in Account</h3>
            <a href="{{ route('profile.password') }}" class="row" style="color:inherit;text-decoration:none;cursor:pointer;padding:10px 0 4px">
              <span class="ms">lock</span>
              <span style="flex:1">Password</span>
              <span class="ms faint">chevron_right</span>
            </a>
            <a href="{{ route('settings.studio') }}" class="row" style="color:inherit;text-decoration:none;cursor:pointer;padding:8px 0">
              <span class="ms">storefront</span>
              <span style="flex:1">Studio{{ $studioName !== '' ? ' · '.$studioName : '' }}</span>
              <span class="ms faint">chevron_right</span>
            </a>
            <a href="{{ route('settings.regional') }}" class="row" style="color:inherit;text-decoration:none;cursor:pointer;padding:4px 0">
              <span class="ms">public</span>
              <span style="flex:1">Regional{{ $regionalLabel !== '' ? ' · '.$regionalLabel : '' }}</span>
              <span class="ms faint">chevron_right</span>
            </a>
          </div>
        </div>
      </div>

      {{-- Template moves Save to bottom of page, left-aligned --}}
      <div class="row pgsaverow" style="justify-content:flex-start;margin-top:16px">
        <button type="submit" class="btn pgsave" id="saveProfileBtn">
          <span class="ms">save</span><span id="saveProfileLabel">Save changes</span>
        </button>
      </div>
    </form>
  </div>

  <div class="acc-toast" id="profileToast" role="status">
    <span class="ms" style="color:#3DD68C;font-size:20px">check_circle</span>
    <span id="profileToastMsg">Profile updated</span>
  </div>

  <div class="acc-leave" id="accLeave" role="dialog" aria-modal="true" aria-labelledby="accLeaveTitle" hidden>
    <div class="acc-leave-box">
      <div style="padding:24px 24px 16px">
        <div class="acc-leave-ico"><span class="ms">warning</span></div>
        <h2 id="accLeaveTitle">Your changes are not saved</h2>
        <p>Save them before you leave, or leave without saving and lose them.</p>
      </div>
      <div class="acc-leave-f">
        <button type="button" class="btn ghost" id="accLeaveDiscard">Leave without saving</button>
        <button type="button" class="btn" id="accLeaveSave"><span class="ms">save</span>Save and leave</button>
      </div>
    </div>
  </div>
@endsection

@section('scripts')
<script>
(function () {
  var USERNAME_PATTERN = /^[A-Za-z0-9._]{1,30}$/;
  var E164_PATTERN = /^\+[1-9]\d{1,14}$/;
  var form = document.getElementById('profileForm');
  var saveBtn = document.getElementById('saveProfileBtn');
  var saveLabel = document.getElementById('saveProfileLabel');
  var toast = document.getElementById('profileToast');
  var toastMsg = document.getElementById('profileToastMsg');
  var leave = document.getElementById('accLeave');
  var toastTimer = null;
  var dirty = false;
  var pendingNav = null;

  function showToast(msg) {
    if (!toast || !toastMsg) return;
    toastMsg.textContent = msg || 'Profile updated';
    toast.classList.add('on');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(function () { toast.classList.remove('on'); }, 2800);
  }

  function setDirty(v) {
    dirty = !!v;
    if (saveBtn) saveBtn.classList.toggle('on', dirty);
  }

  function fieldWrap(name) {
    if (name === 'user_name') return document.getElementById('user_name_wrap');
    return document.getElementById(name);
  }

  function clearError(name) {
    var err = document.getElementById(name + '_error');
    var el = fieldWrap(name);
    if (err) { err.hidden = true; err.textContent = ''; }
    if (el) el.classList.remove('bad');
  }

  function setError(name, message) {
    var err = document.getElementById(name + '_error');
    var el = fieldWrap(name);
    if (err) { err.textContent = message || ''; err.hidden = !message; }
    if (el) el.classList.toggle('bad', !!message);
  }

  function clearAll() {
    ['first_name', 'last_name', 'user_name', 'mobile_number'].forEach(clearError);
  }

  function validate() {
    var ok = true;
    var first = (document.getElementById('first_name').value || '').trim();
    var last = (document.getElementById('last_name').value || '').trim();
    var userName = (document.getElementById('user_name').value || '').trim();
    var mobile = (document.getElementById('mobile_number').value || '').trim();

    if (!first) { setError('first_name', 'First name is required.'); ok = false; }
    if (!last) { setError('last_name', 'Last name is required.'); ok = false; }
    if (!userName) {
      setError('user_name', 'Username is required.');
      ok = false;
    } else if (!USERNAME_PATTERN.test(userName)) {
      setError('user_name', 'Username can only include letters, numbers, periods (.) and underscores (_), max 30.');
      ok = false;
    }
    if (!mobile) {
      setError('mobile_number', 'Mobile number is required.');
      ok = false;
    } else if (!E164_PATTERN.test(mobile)) {
      setError('mobile_number', 'Use E.164 format, e.g. +306947791680 (no spaces).');
      ok = false;
    }
    return ok;
  }

  function closeLeave() {
    if (!leave) return;
    leave.classList.remove('on');
    leave.hidden = true;
  }

  function openLeave(navEl) {
    pendingNav = navEl;
    if (!leave) return;
    leave.hidden = false;
    leave.classList.add('on');
  }

  function followPending() {
    var n = pendingNav;
    pendingNav = null;
    if (!n) return;
    if (n.tagName === 'A' && n.href) {
      window.location.href = n.href;
      return;
    }
    n.click();
  }

  function saveProfile(thenLeave) {
    clearAll();
    if (!validate()) {
      var firstErr = form.querySelector('.acc-err:not([hidden])');
      if (firstErr) firstErr.scrollIntoView({ behavior: 'smooth', block: 'center' });
      return Promise.resolve(false);
    }

    saveBtn.disabled = true;
    if (saveLabel) saveLabel.textContent = 'Saving…';

    return fetch(@json(route('profile.update')), {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
      body: new FormData(form),
    })
      .then(function (res) {
        return res.json().then(function (data) {
          return { status: res.status, ok: res.ok, data: data };
        });
      })
      .then(function (result) {
        if (result.ok && result.data && result.data.success) {
          setDirty(false);
          showToast(result.data.message || 'Profile updated');
          if (thenLeave) followPending();
          return true;
        }
        if (result.status === 422 && result.data && result.data.errors) {
          Object.keys(result.data.errors).forEach(function (field) {
            setError(field, result.data.errors[field][0]);
          });
          var firstErr = form.querySelector('.acc-err:not([hidden])');
          if (firstErr) firstErr.scrollIntoView({ behavior: 'smooth', block: 'center' });
          return false;
        }
        showToast((result.data && result.data.message) || 'Could not save. Try again.');
        return false;
      })
      .catch(function () {
        showToast('Network error. Try again.');
        return false;
      })
      .finally(function () {
        saveBtn.disabled = false;
        if (saveLabel) saveLabel.textContent = 'Save changes';
      });
  }

  ['first_name', 'last_name', 'user_name', 'mobile_number'].forEach(function (name) {
    document.getElementById(name)?.addEventListener('input', function () {
      clearError(name);
      setDirty(true);
    });
  });

  if (!form) return;

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    saveProfile(false);
  });

  document.getElementById('accLeaveDiscard')?.addEventListener('click', function () {
    setDirty(false);
    closeLeave();
    followPending();
  });

  document.getElementById('accLeaveSave')?.addEventListener('click', function () {
    closeLeave();
    saveProfile(true);
  });

  leave?.addEventListener('click', function (e) {
    if (e.target === leave) {
      pendingNav = null;
      closeLeave();
    }
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && leave && leave.classList.contains('on')) {
      pendingNav = null;
      closeLeave();
    }
  });

  document.addEventListener('click', function (e) {
    if (!dirty) return;
    var n = e.target.closest('aside a[href], .tabs a[href], .acc-rail a[href], a.user[href]');
    if (!n || n.closest('.pgsaverow') || n.closest('.help-q') || n.closest('[role=dialog]')) return;
    if (n.getAttribute('target') === '_blank') return;
    e.preventDefault();
    e.stopPropagation();
    openLeave(n);
  }, true);

  window.addEventListener('beforeunload', function (e) {
    if (!dirty) return;
    e.preventDefault();
    e.returnValue = '';
  });

  @if (session('status') === 'profile-updated')
    showToast('Profile updated');
  @endif
})();
</script>
@endsection
