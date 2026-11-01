@php
  $inviteCards = collect($inviteCards ?? []);
@endphp

@if($inviteCards->isNotEmpty())
  <div class="card" style="margin-bottom:14px">
    <div class="ch">
      <div>
        <h3>Studio invitations</h3>
        <div class="faint" style="font-size:12.5px;margin-top:2px">Studios that invited you to join on Bookpay.</div>
      </div>
    </div>
    <div style="padding:18px 22px">
      <div class="plist">
        @foreach($inviteCards as $card)
          <div class="prow">
            <div class="logo2" style="width:40px;height:40px">{{ $card['ini'] }}</div>
            <div>
              <div class="row" style="gap:8px;flex-wrap:wrap">
                <b style="font-size:14.5px">{{ $card['name'] }}</b>
                @if(!empty($card['relationship_label']))
                  <span class="pill nd k">{{ $card['relationship_label'] }}</span>
                @endif
                <span class="pill g">On Bookpay</span>
              </div>
              <div class="faint" style="font-size:12.5px;margin-top:3px">{{ $card['invite_line'] }}</div>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;margin-left:auto">
              <form method="post" action="{{ route('settings.studio.decline-invite', $card['id']) }}">
                @csrf
                <button type="submit" class="btn sm ghost">Decline</button>
              </form>
              <form method="post" action="{{ route('settings.studio.accept-invite', $card['id']) }}">
                @csrf
                <button type="submit" class="btn sm"><span class="ms" style="font-size:16px">check</span>Accept</button>
              </form>
            </div>
          </div>
        @endforeach
      </div>
    </div>
  </div>
@endif
