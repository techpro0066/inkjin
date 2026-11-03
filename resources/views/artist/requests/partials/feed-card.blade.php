@php
  $kind = $item['kind'] ?? 'design';
  $openAs = $item['openAs'] ?? ($kind === 'design' ? 'design' : 'custom');
  $needsReply = !empty($item['needsReply']);
  $statusKey = $item['statusKey'] ?? 'new';
  $statusPillClass = match ($statusKey) {
      'quote_sent' => 'b',
      'booked' => 'g',
      'declined' => 'r',
      default => 'p',
  };
  $viaStudio = trim((string) ($item['viaStudio'] ?? ''));
  $canSendBack = !empty($item['canSendBack']) && !empty($item['sendBackUrl']);
  $sendBackUrl = (string) ($item['sendBackUrl'] ?? '');

  if ($openAs === 'studio_offer') {
      $ctaLabel = 'View details & quote';
      $openFn = 'openStudioOfferDetail';
  } elseif ($needsReply) {
      $ctaLabel = $openAs === 'design' ? 'View details & times' : 'View details & quote';
      $openFn = $openAs === 'design' ? 'openRequestDetail' : 'openCustomRequestDetail';
  } else {
      $ctaLabel = 'View details';
      $openFn = $openAs === 'design' ? 'openRequestDetail' : 'openCustomRequestDetail';
  }
@endphp
<div
  class="card row"
  data-req
  data-kind="{{ $kind }}"
  data-open-as="{{ $openAs }}"
  data-id="{{ $item['id'] }}"
  data-status-key="{{ $statusKey }}"
  data-needs-reply="{{ $needsReply ? '1' : '0' }}"
  data-search="{{ $item['searchText'] ?? '' }}"
  data-date-sort="{{ $item['dateSort'] ?? '' }}"
  data-chat-url="{{ $item['chatUrl'] ?? '' }}"
  data-via-studio="{{ $viaStudio }}"
  data-can-send-back="{{ $canSendBack ? '1' : '0' }}"
  data-send-back-url="{{ $sendBackUrl }}"
  style="padding:16px 20px;gap:14px;cursor:pointer;{{ $needsReply ? 'border-left:3px solid #7A1FC4' : '' }}"
  onclick="{{ $openFn }}({{ (int) $item['id'] }})"
>
  <div class="ini rini" style="width:40px;height:40px;background:#EDE6F0;color:#1A1A1A">{{ $item['clientInitials'] ?? 'CL' }}</div>
  <div style="flex:1;min-width:0">
    <div class="row" style="gap:8px;flex-wrap:wrap">
      <b style="font-size:14.5px">{{ $item['clientName'] ?? 'Client' }}</b>
      <span class="pill nd k">{{ $item['typeLabel'] ?? ucfirst($kind) }}</span>
      <span class="pill {{ $statusPillClass }}" data-status-pill>{{ $item['statusLabel'] ?? 'New request' }}</span>
      @if($viaStudio !== '')
        <span class="pill nd" style="background:#F3E8FF;color:#3E007C">Via {{ $viaStudio }}</span>
      @endif
      <span class="faint" style="font-size:12px">{{ $item['reference'] ?? '' }}</span>
    </div>
    <div class="muted" style="font-size:13px;margin-top:4px">{{ $item['subtitle'] ?? '' }}</div>
  </div>
  <span class="faint rdate" style="font-size:12.5px;width:60px;text-align:right">{{ $item['dateLabel'] ?? '' }}</span>
  <div class="btns rbtns" style="gap:8px" data-card-actions>
    @if ($canSendBack)
      <button
        type="button"
        class="btn sm ghost js-send-back-studio"
        data-url="{{ $sendBackUrl }}"
        data-studio="{{ $viaStudio }}"
        onclick="event.stopPropagation(); window.sendBackStudioRequest && window.sendBackStudioRequest(this)"
      >Send back to studio</button>
    @endif
    @if (!$needsReply && !empty($item['chatUrl']))
      <a href="{{ $item['chatUrl'] }}" class="btn sm ghost" onclick="event.stopPropagation();" style="text-decoration:none">
        <span class="ms" style="font-size:16px">chat_bubble</span>Message
      </a>
    @endif
    <button type="button" class="btn sm {{ $needsReply ? 'pri' : 'ghost' }}" onclick="event.stopPropagation(); {{ $openFn }}({{ (int) $item['id'] }})">
      {{ $ctaLabel }}
    </button>
  </div>
</div>
