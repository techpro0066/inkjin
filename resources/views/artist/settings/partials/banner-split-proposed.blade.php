@if(($splitCard ?? null))
  @php
    $asked = $splitCard['revenue_split'] ?? 60;
    $proposed = $splitCard['proposed_revenue_split'] ?? $asked;
    $rel = $splitCard['relationship_label'] ?? 'Resident';
  @endphp
  <div class="popend" id="propbar" style="margin:0 0 14px;flex-wrap:wrap">
    <span class="ms">handshake</span>
    <div style="flex:1;min-width:240px">
      <b>{{ $splitCard['name'] }} accepted you with a different split</b>
      <div class="faint" style="font-size:12.5px;margin-top:2px">
        You asked for You {{ $asked }}% · Studio {{ 100 - $asked }}%.
        They propose <b style="color:#1A1A1A">You {{ $proposed }}% · Studio {{ 100 - $proposed }}%</b>, {{ $rel }}.
        Accept to join {{ $splitCard['name'] }}. Until you do, nothing changes: your current studio, split and address stay as they are.
      </div>
    </div>
    <div class="row" style="gap:8px">
      <form method="post" action="{{ route('settings.studio.decline-split') }}">
        @csrf
        <button type="submit" class="btn sm ghost" id="propdecline">Decline</button>
      </form>
      <form method="post" action="{{ route('settings.studio.accept-split') }}">
        @csrf
        <input type="hidden" name="mode" value="change">
        <button type="submit" class="btn sm" id="propaccept">Accept and join</button>
      </form>
    </div>
  </div>
@endif
