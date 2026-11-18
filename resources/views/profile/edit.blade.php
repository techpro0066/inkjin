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
  .acc-page{min-width:0;max-width:100%;overflow-x:hidden}
  .acc-page .acc-layout{
    display:grid;grid-template-columns:minmax(0,1fr) 360px;gap:24px;align-items:start
  }
  .acc-page .acc-layout > *{min-width:0}
  .acc-page .acc-fields{display:grid;grid-template-columns:1fr 1fr;gap:16px}
  .acc-deact{margin-top:14px}
  .acc-deact h3{font-size:14.5px;font-weight:700;margin:0}
  .acc-deact .acc-deact-copy{font-size:13px;font-weight:400;line-height:1.5;margin-top:8px;color:var(--muted)}
  .acc-deact .btn-deact{
    display:inline-flex;align-items:center;justify-content:center;gap:8px;margin-top:14px;
    background:#fff;color:var(--red);border:1px solid #F0B0B0;border-radius:10px;padding:9px 14px;
    font-family:inherit;font-weight:600;font-size:13.5px;line-height:normal;cursor:pointer;width:100%
  }
  .acc-deact .btn-deact .ms{font-size:18px;color:var(--red)}
  .acc-deact .btn-deact:hover{background:#FFF5F5}
  .acc-deact .btn-react{
    display:inline-flex;align-items:center;justify-content:center;gap:8px;margin-top:14px;
    background:var(--ink);color:#fff;border:0;border-radius:10px;padding:9px 14px;
    font-family:inherit;font-weight:600;font-size:13.5px;line-height:normal;cursor:pointer;width:100%
  }
  .acc-deact .btn-react .ms{font-size:18px}
  .acc-deact .btn-react:disabled,.acc-deact .btn-deact:disabled{opacity:.55;cursor:not-allowed}
  .acc-deact .acc-deact-foot{
    margin-top:14px;padding-top:12px;border-top:1px solid var(--line);
    font-size:12.5px;color:var(--muted);line-height:1.45
  }
  .acc-deact .acc-deact-foot a,.acc-deact .acc-deact-foot button.linkish{
    color:var(--ink);font-weight:700;text-decoration:underline;background:none;border:0;
    padding:0;font:inherit;cursor:pointer
  }
  .acc-deact .acc-deact-email{margin-top:8px;font-size:12.5px;color:var(--muted);line-height:1.45}
  .acc-deact .acc-deact-email a{color:var(--ink);font-weight:700;text-decoration:underline}
  .acc-deact-modal{
    position:fixed;inset:0;background:rgba(20,10,30,.45);display:none;align-items:flex-start;justify-content:center;
    z-index:600;padding:24px 16px;overflow-y:auto
  }
  .acc-deact-modal.on{display:flex}
  .acc-deact-box{
    background:#fff;border-radius:20px;width:560px;max-width:100%;margin:auto;
    box-shadow:0 20px 60px rgba(0,0,0,.25);overflow:hidden
  }
  .acc-deact-hd{padding:22px 22px 8px;position:relative}
  .acc-deact-hd .acc-deact-x{
    position:absolute;top:16px;right:16px;width:34px;height:34px;border:0;border-radius:10px;
    background:transparent;color:var(--muted);cursor:pointer;display:inline-flex;align-items:center;justify-content:center
  }
  .acc-deact-hd .acc-deact-x:hover{background:#F5F2F6;color:var(--ink)}
  .acc-deact-ico{
    width:44px;height:44px;border-radius:50%;background:var(--redl);color:var(--red);
    display:flex;align-items:center;justify-content:center;margin-bottom:14px
  }
  .acc-deact-ico .ms{font-size:24px}
  .acc-deact-hd h2{font-size:20px;font-weight:800;margin:0 0 6px;letter-spacing:-.3px}
  .acc-deact-hd p{margin:0;color:var(--muted);font-size:13.5px;line-height:1.5;padding-right:28px}
  .acc-deact-body{padding:8px 22px 6px}
  .acc-deact-sec{margin:14px 0 6px}
  .acc-deact-sec .lbl{margin-bottom:10px}
  .acc-deact-item{display:flex;gap:12px;align-items:flex-start;margin-bottom:12px}
  .acc-deact-item .ms{font-size:20px;color:var(--ink);flex-shrink:0;margin-top:1px}
  .acc-deact-item .ms.ok{color:var(--green)}
  .acc-deact-item div{font-size:13.5px;line-height:1.5;color:var(--ink)}
  .acc-deact-item b{font-weight:700}
  .acc-deact-callout{
    border-radius:12px;padding:12px 14px;display:flex;gap:10px;align-items:flex-start;
    font-size:13px;line-height:1.5;margin-top:10px
  }
  .acc-deact-callout .ms{font-size:20px;flex-shrink:0;margin-top:1px}
  .acc-deact-callout.warn{background:#FFF3DE;color:#7A4A00}
  .acc-deact-callout.warn .ms{color:#B86E00}
  .acc-deact-callout.tip{background:#F5F0F8;color:#4A3A55}
  .acc-deact-callout.tip .ms{color:#7A1FC4}
  .acc-deact-callout a{color:inherit;font-weight:700;text-decoration:underline}
  .acc-deact-ft{
    display:flex;gap:10px;padding:16px 22px;background:#FCF6FE;border-top:1px solid #F0E4F5;flex-wrap:wrap
  }
  .acc-deact-ft .btn{flex:1;justify-content:center;min-width:140px}
  .acc-deact-ft .btn.danger{background:var(--red);color:#fff}
  .acc-deact-ft .btn.danger .ms{color:#fff}
  @media (max-width:900px){
    .acc-toast{left:16px;right:16px;top:74px}
    .acc-page .acc-layout{grid-template-columns:1fr;gap:14px}
    .acc-page .acc-fields{grid-template-columns:1fr}
    .acc-page .pgsaverow{margin-top:8px}
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
  $isDeactivated = $user->isDeactivated();
  $pageHostPath = $username !== ''
      ? preg_replace('#^https?://#', '', route('public.artist', ['username' => $username]))
      : 'inkjin.com/@yourname';
  $displayName = $ud?->publicDisplayName() ?: trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: 'Your page';
  $upcomingCount = (int) ($upcomingBookingsCount ?? 0);
  $phpDateFmt = match ($dateFmt) {
      'MM/DD/YYYY' => 'm/d/Y',
      'YYYY-MM-DD' => 'Y-m-d',
      default => 'd/m/Y',
  };
  $deactivatedOn = $user->deactivated_at?->format($phpDateFmt);
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

      <div class="acc-layout">
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
              <div class="acc-fields">
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
              <div class="acc-fields">
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

          <div class="card pad acc-also" style="margin-top:14px">
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

          <div class="card pad acc-deact" id="accDeactCard">
            @if ($isDeactivated)
              <h3>Your account is deactivated</h3>
              <p class="acc-deact-copy">
                @if ($deactivatedOn)
                  Deactivated on {{ $deactivatedOn }}.
                @endif
                Your page is hidden and clients can’t make new bookings. Nothing was deleted.
              </p>
              <button type="button" class="btn-react" id="accReactivateBtn">
                <span class="ms">play_circle</span>Reactivate account
              </button>
            @else
              <h3>Deactivate account</h3>
              <p class="acc-deact-copy">
                Turn your account off. Your page is hidden and clients can’t make new bookings. Nothing is deleted, and you can turn it back on any time.
              </p>
              <button type="button" class="btn-deact" id="accDeactivateOpenBtn">
                <span class="ms">pause_circle</span>Deactivate account
              </button>
            @endif
            <div class="acc-deact-foot">
              To delete your account,
              <button type="button" class="linkish" id="accDeleteMessageUs" data-intercom-get-help>message us</button>.
              We do it for you.
              <div class="acc-deact-email">
                Or email <a href="mailto:support@inkjin.com">support@inkjin.com</a>
              </div>
            </div>
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

  @unless ($isDeactivated)
  <div class="acc-deact-modal" id="accDeactModal" role="dialog" aria-modal="true" aria-labelledby="accDeactTitle" hidden>
    <div class="acc-deact-box">
      <div class="acc-deact-hd">
        <button type="button" class="acc-deact-x" id="accDeactClose" aria-label="Close">
          <span class="ms">close</span>
        </button>
        <div class="acc-deact-ico"><span class="ms">pause_circle</span></div>
        <h2 id="accDeactTitle">Deactivate your account?</h2>
        <p>This turns your account off. Nothing is deleted, and you can turn it back on any time.</p>
      </div>
      <div class="acc-deact-body">
        <div class="acc-deact-sec">
          <div class="lbl">What turns off</div>
          <div class="acc-deact-item">
            <span class="ms">visibility_off</span>
            <div>
              Your page <b>{{ $pageHostPath }}</b> and your guest spot pages are hidden. Clients see “{{ $displayName }} isn’t taking bookings right now.”
            </div>
          </div>
          <div class="acc-deact-item">
            <span class="ms">event_busy</span>
            <div>No new bookings or requests. You can’t create new payment links; unpaid links you already sent still work.</div>
          </div>
          @if ($studioName !== '')
          <div class="acc-deact-item">
            <span class="ms">storefront</span>
            <div>You come off {{ $studioName }}’s page. They’re told. Your split with them stays.</div>
          </div>
          @endif
          <div class="acc-deact-item">
            <span class="ms">credit_card_off</span>
            <div>Plan billing pauses. You aren’t charged while your account is deactivated.</div>
          </div>
        </div>

        <div class="acc-deact-sec">
          <div class="lbl">What stays</div>
          <div class="acc-deact-item">
            <span class="ms ok">check_circle</span>
            <div>You can still log in, see your bookings and message your clients.</div>
          </div>
          <div class="acc-deact-item">
            <span class="ms ok">check_circle</span>
            <div>Payouts for payments already made arrive as normal.</div>
          </div>
        </div>

        @if ($upcomingCount > 0)
        <div class="acc-deact-callout warn">
          <span class="ms">calendar_month</span>
          <div>
            <b>You have {{ $upcomingCount }} upcoming {{ $upcomingCount === 1 ? 'booking' : 'bookings' }}.</b>
            They are not cancelled, and you are still responsible for them: do the session or refund the client.
            Their booking links, consent forms and balance payments keep working.
            <a href="{{ route('artist.bookings.index') }}">View bookings</a>
          </div>
        </div>
        @endif

        <div class="acc-deact-callout tip">
          <span class="ms">lightbulb</span>
          <div>
            <b>Just need a break?</b> Close your books instead. Your page stays up and clients can join your waitlist.
            <a href="{{ route('availability.index') }}">Go to Schedule</a>
          </div>
        </div>
      </div>
      <div class="acc-deact-ft">
        <button type="button" class="btn ghost" id="accDeactCancel">Cancel</button>
        <button type="button" class="btn danger" id="accDeactConfirm">
          <span class="ms">pause_circle</span>Deactivate account
        </button>
      </div>
    </div>
  </div>
  @endunless
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

  var deactModal = document.getElementById('accDeactModal');
  var deactOpen = document.getElementById('accDeactivateOpenBtn');
  var deactClose = document.getElementById('accDeactClose');
  var deactCancel = document.getElementById('accDeactCancel');
  var deactConfirm = document.getElementById('accDeactConfirm');
  var reactivateBtn = document.getElementById('accReactivateBtn');
  var csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

  function openDeactModal() {
    if (!deactModal) return;
    deactModal.hidden = false;
    deactModal.classList.add('on');
  }

  function closeDeactModal() {
    if (!deactModal) return;
    deactModal.classList.remove('on');
    deactModal.hidden = true;
  }

  function postAccountStatus(url, btn, busyLabel) {
    if (!btn || btn.disabled) return;
    var original = btn.innerHTML;
    btn.disabled = true;
    if (busyLabel) btn.innerHTML = busyLabel;

    fetch(url, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': csrf,
        'Content-Type': 'application/json'
      },
      body: '{}'
    })
      .then(function (res) { return res.json().then(function (data) { return { ok: res.ok, data: data }; }); })
      .then(function (result) {
        if (!result.ok || !result.data || !result.data.success) {
          throw new Error((result.data && result.data.message) || 'Something went wrong.');
        }
        window.location.reload();
      })
      .catch(function (err) {
        btn.disabled = false;
        btn.innerHTML = original;
        showToast(err.message || 'Something went wrong.');
      });
  }

  deactOpen?.addEventListener('click', openDeactModal);
  deactClose?.addEventListener('click', closeDeactModal);
  deactCancel?.addEventListener('click', closeDeactModal);
  deactModal?.addEventListener('click', function (e) {
    if (e.target === deactModal) closeDeactModal();
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && deactModal && deactModal.classList.contains('on')) {
      closeDeactModal();
    }
  });
  deactConfirm?.addEventListener('click', function () {
    postAccountStatus(
      @json(route('profile.deactivate')),
      deactConfirm,
      '<span class="ms">hourglass_empty</span>Deactivating…'
    );
  });
  reactivateBtn?.addEventListener('click', function () {
    postAccountStatus(
      @json(route('profile.reactivate')),
      reactivateBtn,
      '<span class="ms">hourglass_empty</span>Reactivating…'
    );
  });

  document.getElementById('accDeleteMessageUs')?.addEventListener('click', function (e) {
    // Same empty messenger as Get Help when Intercom is available.
    if (typeof window.Intercom === 'function' && window.intercomSettings) {
      return;
    }

    e.preventDefault();
    e.stopPropagation();

    var existing = document.getElementById('bpchat');
    if (existing) {
      existing.remove();
      return;
    }

    var d = document.createElement('div');
    d.id = 'bpchat';
    d.setAttribute('role', 'dialog');
    d.setAttribute('aria-label', 'Chat with Bookpay support');
    d.style.cssText = 'position:fixed;right:20px;bottom:20px;width:340px;max-width:calc(100% - 32px);background:#fff;border-radius:16px;box-shadow:0 18px 50px rgba(20,0,40,.28);z-index:2500;overflow:hidden;font-family:inherit';
    d.innerHTML =
      '<div style="background:#3E007C;color:#fff;padding:16px 18px;display:flex;align-items:flex-start;gap:10px">' +
        '<div style="flex:1"><b style="font-size:16px">Hi there 👋</b>' +
        '<div style="font-size:13px;opacity:.85;margin-top:3px">Ask us anything. We usually reply within a few hours.</div></div>' +
        '<button type="button" aria-label="Close" style="background:none;border:0;color:#fff;font-size:20px;cursor:pointer;line-height:1" onclick="document.getElementById(&quot;bpchat&quot;).remove()">×</button>' +
      '</div>' +
      '<div style="padding:16px 18px;font-size:13px;color:#3F3A45">' +
        '<div style="background:#F4F0F6;border-radius:12px;padding:10px 12px;margin-bottom:12px">Intercom chat widget (mockup). In the product this is the real Intercom Messenger.</div>' +
        '<div style="display:flex;gap:8px">' +
          '<input placeholder="Write a message..." style="flex:1;font:inherit;font-size:13px;padding:9px 12px;border:1px solid #E0D6E6;border-radius:10px">' +
          '<button type="button" style="background:#3E007C;color:#fff;border:0;border-radius:10px;padding:0 14px;font-weight:700;cursor:pointer">Send</button>' +
        '</div>' +
        '<div style="font-size:12px;color:#8F8A95;margin-top:10px">Or email <a href="mailto:support@inkjin.com" style="color:inherit;text-decoration:underline">support@inkjin.com</a></div>' +
      '</div>';
    document.body.appendChild(d);
  });
    })();
  </script>
@endsection
