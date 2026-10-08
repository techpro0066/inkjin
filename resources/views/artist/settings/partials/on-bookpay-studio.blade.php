@php $card = $card ?? null; @endphp
@if($card)
  <div class="card" style="margin-bottom:14px">
    <div class="ch">
      <div>
        <h3>Your studio</h3>
        <div class="faint" style="font-size:12.5px;margin-top:2px">Where clients come for their tattoo</div>
      </div>
      <a class="btn sm addst" href="{{ route('settings.studio.change', ['mode' => 'add']) }}">
        <span class="ms" style="font-size:17px">add</span>Add studio
      </a>
    </div>
    <div style="padding:18px 22px">
      <div class="picked">
        <div class="row" style="gap:14px;padding-bottom:12px;border-bottom:1px solid #F0EAF2">
          <div class="logo2">{{ $card['ini'] }}</div>
          <div style="flex:1">
            <div class="row" style="gap:8px">
              <b style="font-size:16px">{{ $card['name'] }}</b>
              <span class="pill {{ $card['bp_pill']['class'] }}">{{ $card['bp_pill']['label'] }}</span>
            </div>
            <div class="faint" style="font-size:12.5px">{{ $card['workspace_label'] }} · Managed by {{ $card['managed_by'] }}</div>
          </div>
        </div>
        @if($card['address'] !== '')
          <div class="kv" style="margin-top:8px"><span>Address</span><span>{{ $card['address'] }}</span></div>
        @endif
        @if(!empty($card['maps_url']))
          <div class="kv">
            <span>Map</span>
            <span>
              <span class="mlinline">
                <a class="mlk" href="{{ $card['maps_url'] }}" target="_blank" rel="noopener">Open in Google Maps</a>
                <button type="button" class="mlcopy"><span class="ms">content_copy</span>Copy link</button>
              </span>
            </span>
          </div>
        @endif
      </div>
      <div class="lockbar" style="margin-top:12px">
        <span class="ms" style="font-size:17px">lock</span>
        The studio manages these details. Ask {{ $card['managed_by'] }} to update them.
      </div>
    </div>
  </div>

  <div class="card" style="margin-bottom:14px">
    <div class="ch">
      <div>
        <h3>Your relationship</h3>
        <div class="faint" style="font-size:12.5px;margin-top:2px">
          Confirmed by {{ $card['managed_by'] }}@if($card['confirmed_at']) on {{ $card['confirmed_at'] }}@endif
        </div>
      </div>
    </div>
    <div style="padding:18px 22px">
      <div class="row" style="gap:12px">
        <span class="pill g">Accepted</span>
        @if($card['relationship_label'])
          <b>{{ $card['relationship_label'] }}</b>
        @endif
        @if($card['relationship_hint'])
          <span class="faint" style="font-size:13px">{{ $card['relationship_hint'] }}</span>
        @endif
      </div>
      <div class="row" style="gap:10px;margin-top:14px">
        <a class="btn sm ghost" href="{{ route('settings.studio.change') }}">Change studio</a>
      </div>
      <div class="help">Your revenue split is in Money &gt; Payouts.</div>
    </div>
  </div>
@endif
