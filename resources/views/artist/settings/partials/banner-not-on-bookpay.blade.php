@php
  $card = $card ?? [];
  $name = $card['name'] ?? 'the studio';
  $linkId = (int) ($card['id'] ?? 0);
  $studioNameValue = $card['studio_name'] ?? $name;
@endphp
<div class="popend" id="nobar" style="margin:0 0 14px;flex-wrap:wrap;align-items:flex-start">
  <span class="ms">storefront</span>
  <div style="flex:1;min-width:220px">
    <b>{{ $name }} isn’t on Bookpay yet</b>
    <div class="faint" style="font-size:12.5px;margin-top:2px;color:#8A5A00">
      Invite them so they can list you on their studio page. No split needed: you keep the full payment.
    </div>
    <div id="nobarForm" hidden style="margin-top:12px">
      <input type="hidden" id="nobarStudioName" value="{{ $studioNameValue }}">
      <div class="row" style="gap:10px;flex-wrap:wrap;align-items:center">
        <input
          class="in"
          type="email"
          id="nobarStudioEmail"
          value=""
          placeholder="studio@example.com"
          autocomplete="email"
          style="flex:1;min-width:220px;max-width:360px;background:#fff"
        >
        <button type="button" class="btn sm" id="nobarSend" data-user-studio-id="{{ $linkId }}" style="background:#1A1A1A;color:#fff;border:0;padding:10px 16px">
          Send invitation
        </button>
      </div>
      <div id="nobarError" class="help" style="color:#C62828;display:none;margin-top:6px"></div>
    </div>
  </div>
  <button type="button" class="btn sm ghost" id="nobarInviteBtn" style="background:#fff">Invite studio</button>
</div>
