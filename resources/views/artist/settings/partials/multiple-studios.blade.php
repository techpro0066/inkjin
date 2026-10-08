@php $multiCards = $multiCards ?? []; @endphp

<div class="card" style="margin-bottom:14px">
  <div class="ch">
    <div>
      <h3>Your studios</h3>
      <div class="faint" style="font-size:12.5px;margin-top:2px">Places where clients come for their tattoo</div>
    </div>
    <a class="btn sm addst" href="{{ route('settings.studio.change', ['mode' => 'add']) }}">
      <span class="ms" style="font-size:17px">add</span>Add studio
    </a>
  </div>
  <div style="padding:18px 22px">
    <div class="mstud">
      @foreach($multiCards as $i => $card)
        <div class="picked" style="margin-bottom:{{ $loop->last ? '0' : '12px' }};{{ !empty($card['dashed']) ? 'border-style:dashed' : '' }}">
          <div class="row" style="gap:14px;flex-wrap:wrap">
            <div class="logo2">{{ $card['ini'] }}</div>
            <div style="flex:1;min-width:200px">
              <div class="row" style="gap:8px;flex-wrap:wrap">
                <b style="font-size:15px">{{ $card['name'] }}</b>
                <span class="pill {{ $card['bp_pill']['class'] }}">{{ $card['bp_pill']['label'] }}</span>
                @if(!empty($card['relationship_label']))
                  <span class="pill nd k">{{ $card['relationship_label'] }}</span>
                @endif
                @if(!empty($card['status_pill']))
                  <span class="pill {{ $card['status_pill']['class'] }}">{{ $card['status_pill']['label'] }}</span>
                @endif
              </div>
              <div class="faint" style="font-size:12.5px;margin-top:3px">{{ $card['multi_subtitle'] }}</div>
            </div>
            <div class="row" style="gap:8px;flex-wrap:wrap">
              @if(!empty($card['dashed']) && ($card['status'] ?? null) === \App\Models\UserStudio::STATUS_JOIN_PENDING)
                <button type="button" class="btn sm ghost js-studio-resend-join" data-user-studio-id="{{ $card['id'] }}">Resend email</button>
                <button type="button" class="btn sm ghost js-studio-cancel-join" data-user-studio-id="{{ $card['id'] }}" style="color:#C62828;border-color:#F3C9C9">Cancel request</button>
              @else
                <button type="button" class="btn sm ghost js-studio-leave" data-user-studio-id="{{ $card['id'] }}" data-studio-name="{{ $card['name'] }}" style="color:#C62828;border-color:#F3C9C9">Leave studio</button>
              @endif
              <a class="btn sm ghost" href="{{ $card['update_url'] ?? route('settings.studio.change', ['mode' => 'update', 'from_link' => $card['id'] ?? null]) }}">Update studio</a>
            </div>
          </div>
        </div>
      @endforeach

      <div class="twonote2">
        <span class="ms">calendar_month</span>
        <span style="flex:1">Clients only see times at the studio you're at that day. Pick which days you work at each studio in Schedule.</span>
        <a href="{{ route('settings.calendar') }}" class="btn sm ghost" style="text-decoration:none">
          Set days per studio<span class="ms" style="font-size:16px">arrow_forward</span>
        </a>
      </div>
    </div>
  </div>
</div>
