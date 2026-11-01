@php
  $clientFlowTab = $clientFlowTab ?? 'intake';
@endphp
<div class="tabs">
  <a href="{{ route('studio.client-flow.intake') }}" class="{{ $clientFlowTab === 'intake' ? 'on' : '' }}">Intake form</a>
  <a href="{{ route('studio.client-flow.consent') }}" class="{{ $clientFlowTab === 'consent' ? 'on' : '' }}">Consent form</a>
</div>
