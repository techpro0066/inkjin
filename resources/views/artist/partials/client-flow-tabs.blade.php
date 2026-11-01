@php
  $clientFlowTab = $clientFlowTab ?? '';
  $tabClass = fn (string $key) => $clientFlowTab === $key ? 'on' : '';
@endphp
<div class="tabs">
  <a href="{{ route('artist.forms.index', ['tab' => 'booking']) }}" class="{{ $tabClass('intake') }}">Intake forms</a>
  <a href="{{ route('artist.forms.consent') }}" class="{{ $tabClass('consent') }}">Consent form</a>
  <a href="{{ route('artist.aftercare.index') }}" class="{{ $tabClass('aftercare') }}">Aftercare</a>
</div>
