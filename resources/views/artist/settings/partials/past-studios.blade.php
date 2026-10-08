@php
  $pastCards = collect($pastCards ?? []);
@endphp

@if($pastCards->isNotEmpty())
  <div class="card" style="margin-bottom:14px">
    <div class="ch">
      <div>
        <h3>Studios you've worked with</h3>
        <div class="faint" style="font-size:12.5px;margin-top:2px">Past studios and guest spots. Change back to one without searching.</div>
      </div>
    </div>
    <div style="padding:18px 22px">
      <div class="plist">
        @foreach($pastCards as $card)
          <div class="prow">
            <div class="logo2" style="width:40px;height:40px">{{ $card['ini'] }}</div>
            <div>
              <div class="row" style="gap:8px;flex-wrap:wrap">
                <b style="font-size:14.5px">{{ $card['name'] }}</b>
                @if(!empty($card['relationship_label']))
                  <span class="pill nd k">{{ $card['relationship_label'] }}</span>
                @elseif(!empty($card['workspace_type']) && in_array($card['workspace_type'], ['private', 'home'], true))
                  <span class="pill nd k">{{ $card['workspace_label'] ?? 'Own space' }}</span>
                @else
                  <span class="pill nd b">Guest spot</span>
                @endif
                <span class="pill {{ $card['bp_pill']['class'] }}">{{ $card['bp_pill']['label'] }}</span>
              </div>
              <div class="faint" style="font-size:12.5px;margin-top:3px">{{ $card['past_line'] }}</div>
            </div>
            <a class="btn sm ghost" href="{{ $card['change_url'] }}">
              <span class="ms" style="font-size:16px">add</span>Add this studio
            </a>
          </div>
        @endforeach
      </div>
    </div>
  </div>
@endif
