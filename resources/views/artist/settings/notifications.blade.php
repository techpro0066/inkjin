@extends('layouts.new-artist-dashboard-layout')

@section('title', 'Account')

@section('styles')
<style>
  .nf-page{max-width:620px}
  .nf-page .ch h3{font-size:16px;font-weight:700;margin:0}
  .nf-page .ch .faint{font-size:12.5px;font-weight:400;margin-top:2px;color:var(--faint)}
  .nf-group{font-size:11px;letter-spacing:.8px;font-weight:700;color:var(--muted);text-transform:uppercase;margin:18px 0 2px}
  .nf-group:first-child{margin-top:0}
  .nf-row{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:12px 0;border-bottom:1px solid #F3EEF4}
  .nf-row b{display:block;font-size:14px;font-weight:600}
  .nf-row small{display:block;font-size:12.5px;color:#6F6874;margin-top:2px}
  .nf-switch{width:38px;height:22px;padding:0;border:0;border-radius:12px;background:#D9D2DD;position:relative;flex-shrink:0;cursor:pointer}
  .nf-switch:after{content:'';position:absolute;width:16px;height:16px;border-radius:50%;background:#fff;top:3px;left:3px;transition:transform .18s}
  .nf-switch[aria-checked="true"]{background:var(--pri)}
  .nf-switch[aria-checked="true"]:after{transform:translateX(16px)}
  .nf-switch:focus-visible{outline:2px solid #3E007C;outline-offset:2px}
  .nf-always{display:flex;gap:10px;align-items:flex-start;margin-top:16px;padding:12px 14px;border-radius:12px;background:#F6F3F8;font-size:13px;color:#3F3A44;line-height:1.5}
  .nf-always .ms{font-size:18px;color:#6F6874}
  .nf-page .nf-save{display:inline-flex;align-items:center;justify-content:center;gap:8px;background:var(--ink);color:#fff;border:0;border-radius:10px;padding:10px 16px;font-family:inherit;font-weight:600;font-size:13.5px;line-height:normal;white-space:nowrap;cursor:pointer}
  .nf-page .nf-save .ms{font-size:18px}
  .nf-toast{position:fixed;top:22px;right:24px;background:#1A1A1A;color:#fff;padding:12px 18px;border-radius:10px;font-weight:600;font-size:14px;z-index:400;box-shadow:0 10px 30px rgba(0,0,0,.22);display:flex;gap:10px;align-items:center;opacity:0;transform:translateX(calc(100% + 40px));transition:opacity .25s,transform .35s;pointer-events:none}
  .nf-toast.on{opacity:1;transform:none}
  .nf-toast .ms{color:#3DD68C;font-size:20px}
  .nf-toast.error .ms{color:#FF8A80}
  @media(max-width:900px){.nf-toast{left:16px;right:16px;top:74px}}
</style>
@endsection

@section('content')
  @php($disabledNotifications = $notAllowedNotifications ?? [])
  <div class="head">
    <div>
      <h1>Account</h1>
      <div class="sub">Your login, contact details, studio and region. Set once, rarely changed.</div>
    </div>
  </div>

  @include('artist.partials.profile-settings-tabs', ['activeProfileTab' => 'notifications'])

  <div class="nf-page">
    <div class="card">
      <div class="ch">
        <div>
          <h3>Email notifications</h3>
          <div class="faint">Choose which emails your account gets from Bookpay. They go to the email in Account &gt; Profile.</div>
        </div>
      </div>
      <div style="padding:18px 22px">
        <div class="nf-group">Bookings and requests</div>
        <div class="nf-row">
          <div><b id="nf-bookings">New bookings</b><small>A client books and pays</small></div>
          <button type="button" class="nf-switch" role="switch" data-notification="new_bookings" aria-checked="{{ in_array('new_bookings', $disabledNotifications, true) ? 'false' : 'true' }}" aria-labelledby="nf-bookings"></button>
        </div>
        <div class="nf-row">
          <div><b id="nf-requests">New requests</b><small>A client sends a custom request</small></div>
          <button type="button" class="nf-switch" role="switch" data-notification="new_requests" aria-checked="{{ in_array('new_requests', $disabledNotifications, true) ? 'false' : 'true' }}" aria-labelledby="nf-requests"></button>
        </div>
        <div class="nf-row">
          <div><b id="nf-consent">Consent form signed</b><small>A client signs before their session</small></div>
          <button type="button" class="nf-switch" role="switch" data-notification="consent_form_signed" aria-checked="{{ in_array('consent_form_signed', $disabledNotifications, true) ? 'false' : 'true' }}" aria-labelledby="nf-consent"></button>
        </div>

        <div class="nf-group">Messages</div>
        <div class="nf-row">
          <div><b id="nf-messages">New messages from clients</b><small>Several messages close together come as one email</small></div>
          <button type="button" class="nf-switch" role="switch" data-notification="new_messages" aria-checked="{{ in_array('new_messages', $disabledNotifications, true) ? 'false' : 'true' }}" aria-labelledby="nf-messages"></button>
        </div>

        <div class="nf-group">Money</div>
        <div class="nf-row">
          <div><b id="nf-payouts">Payouts sent</b><small>When money is on its way to your bank</small></div>
          <button type="button" class="nf-switch" role="switch" data-notification="payouts_sent" aria-checked="{{ in_array('payouts_sent', $disabledNotifications, true) ? 'false' : 'true' }}" aria-labelledby="nf-payouts"></button>
        </div>

        <div class="nf-group">Feedback</div>
        <div class="nf-row">
          <div><b id="nf-feedback">Ask me about each session</b><small>The day after a session: how it went with the client</small></div>
          <button type="button" class="nf-switch" role="switch" data-notification="session_feedback" aria-checked="{{ in_array('session_feedback', $disabledNotifications, true) ? 'false' : 'true' }}" aria-labelledby="nf-feedback"></button>
        </div>

        <div class="nf-group">Studio and splits</div>
        <div class="nf-row">
          <div><b id="nf-studio">Studio answers</b><small>Join requests and split requests accepted or declined, studio joined Bookpay</small></div>
          <button type="button" class="nf-switch" role="switch" data-notification="studio_answers" aria-checked="{{ in_array('studio_answers', $disabledNotifications, true) ? 'false' : 'true' }}" aria-labelledby="nf-studio"></button>
        </div>

        <div class="nf-always">
          <span class="ms">lock</span>
          <span><b>Always sent:</b> password reset, sign-in and security alerts, payment and payout problems, and changes to our terms. You can&rsquo;t turn these off.</span>
        </div>
        <div class="row" style="justify-content:flex-end;margin-top:16px">
          <button type="button" class="nf-save" id="nfSave"><span class="ms">save</span>Save changes</button>
        </div>
      </div>
    </div>
  </div>

  <div class="nf-toast" id="nfToast" role="status" aria-live="polite">
    <span class="ms" id="nfToastIcon">check_circle</span>
    <span>Notification settings saved</span>
  </div>
@endsection

@section('scripts')
<script>
(function () {
  document.querySelectorAll('.nf-switch').forEach(function (toggle) {
    toggle.addEventListener('click', function () {
      toggle.setAttribute('aria-checked', toggle.getAttribute('aria-checked') !== 'true');
    });
  });

  var saveButton = document.getElementById('nfSave');
  var toast = document.getElementById('nfToast');
  var toastIcon = document.getElementById('nfToastIcon');
  var toastText = toast ? toast.querySelector('span:last-child') : null;
  var toastTimer;
  if (saveButton && toast) {
    saveButton.addEventListener('click', function () {
      var disabled = Array.prototype.slice.call(document.querySelectorAll('.nf-switch[aria-checked="false"]'))
        .map(function (toggle) { return toggle.getAttribute('data-notification'); });
      var csrf = document.querySelector('meta[name="csrf-token"]');
      var originalHtml = saveButton.innerHTML;
      saveButton.disabled = true;
      saveButton.innerHTML = '<span class="ms">hourglass_top</span>Saving…';
      fetch(@json(route('settings.notifications.update')), {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': csrf ? csrf.content : ''
        },
        body: JSON.stringify({ not_allowed_notification: disabled })
      })
        .then(function (response) {
          return response.json().then(function (data) {
            return { ok: response.ok, data: data };
          });
        })
        .then(function (result) {
          if (!result.ok || !result.data.success) {
            throw new Error(result.data.message || 'Could not save notification preferences.');
          }
          showToast(result.data.message, false);
        })
        .catch(function (error) {
          showToast(error.message || 'Could not save notification preferences.', true);
        })
        .finally(function () {
          saveButton.disabled = false;
          saveButton.innerHTML = originalHtml;
        });
    });
  }

  function showToast(message, isError) {
    if (!toast || !toastIcon || !toastText) return;
    toastText.textContent = message;
    toastIcon.textContent = isError ? 'error' : 'check_circle';
    toast.classList.toggle('error', isError);
    toast.classList.add('on');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(function () { toast.classList.remove('on'); }, 2400);
  }
})();
</script>
@endsection
