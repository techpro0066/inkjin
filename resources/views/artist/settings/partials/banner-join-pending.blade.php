@if(($pendingCard ?? null))
  @php
    $bits = [];
    if (! empty($pendingCard['relationship_label'])) {
      $bits[] = $pendingCard['relationship_label'];
    }
    if ($pendingCard['revenue_split'] !== null) {
      $you = (int) $pendingCard['revenue_split'];
      $bits[] = 'You '.$you.'% · Studio '.(100 - $you).'%';
    }
    $detail = $bits ? implode(' · ', $bits).'. ' : '';
  @endphp
  <div class="popend" id="joinbar" style="margin:0 0 14px">
    <span class="ms">hourglass_top</span>
    <div style="flex:1">
      <b>Join request sent to {{ $pendingCard['name'] }}</b>
      <div class="faint" style="font-size:12.5px;margin-top:2px">
        Sent on {{ $pendingCard['created_at'] }}: {{ $detail }}Waiting for them to accept you. Until then nothing changes: your current studio, split and address stay as they are.
      </div>
    </div>
    <div class="row" style="gap:8px;flex-wrap:wrap">
      <button type="button" class="btn sm ghost js-studio-resend-join" data-user-studio-id="{{ $pendingCard['id'] }}">Resend email</button>
      <button type="button" class="btn sm ghost js-studio-cancel-join" id="joincancel" data-user-studio-id="{{ $pendingCard['id'] }}">Cancel request</button>
    </div>
  </div>
@endif
