@php
  $name = $name ?? 'the studio';
  $linkId = (int) ($linkId ?? 0);
  $noSplit = (bool) ($noSplit ?? true);
  $sentOn = $sentOn ?? null;
@endphp
<div class="popend" id="invbar" style="margin:0 0 14px;align-items:center;flex-wrap:wrap;gap:12px 16px">
  <span class="ms">hourglass_top</span>
  <div style="flex:1;min-width:220px">
    <b>Invitation sent to {{ $name }}</b>
    <div class="faint" style="font-size:12.5px;margin-top:2px;color:#8A5A00;line-height:1.45">
      @if($sentOn)
        Sent on {{ $sentOn }}.
      @endif
      @if($noSplit)
        You're paid directly. No split. Waiting for them to join Bookpay. Until then nothing changes: your current studio and address stay as they are.
      @else
        You're paid directly until they join Bookpay and confirm your split. Until then nothing changes: your current studio, split and address stay as they are.
      @endif
    </div>
  </div>
  <div class="row" style="gap:8px;flex-wrap:wrap;margin-left:auto">
    <button type="button" class="btn sm ghost js-studio-resend-workplace-invite" data-user-studio-id="{{ $linkId }}" style="background:#fff">Resend email</button>
    <button type="button" class="btn sm ghost js-studio-cancel-workplace-invite" data-user-studio-id="{{ $linkId }}" style="background:#fff">Cancel request</button>
  </div>
</div>
