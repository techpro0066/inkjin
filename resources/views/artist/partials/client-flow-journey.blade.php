@php
  $activeStep = $activeStep ?? 'intake'; // intake | consent | aftercare
@endphp
<div class="card flow-card" style="padding:20px 26px;margin-top:22px">
  <div class="row" style="align-items:flex-start;gap:0">
    <div class="flow-step {{ $activeStep === 'intake' ? 'on' : '' }}">
      <a href="{{ route('artist.forms.index', ['tab' => 'booking']) }}" title="Open Intake form">
        <div class="ic" @if($activeStep !== 'intake') style="background:#3E007C;color:#fff" @endif><span class="ms">edit_note</span></div>
        <span class="lab">Request</span>
        <div class="sub" @if($activeStep !== 'intake') style="color:#3E007C;font-weight:700" @endif>Intake form</div>
      </a>
    </div>
    <div class="flow-line"></div>
    <div class="flow-step">
      <div class="ic"><span class="ms">event_available</span></div>
      <span class="lab">Booking</span>
      <div class="sub">Deposit paid</div>
    </div>
    <div class="flow-line"></div>
    <div class="flow-step {{ $activeStep === 'consent' ? 'on' : '' }}">
      <a href="{{ route('artist.forms.consent') }}" title="Open Consent form">
        <div class="ic" @if($activeStep !== 'consent') style="background:#3E007C;color:#fff" @endif><span class="ms">draw</span></div>
        <span class="lab">48h before</span>
        <div class="sub" @if($activeStep !== 'consent') style="color:#3E007C;font-weight:700" @endif>Consent form</div>
      </a>
    </div>
    <div class="flow-line"></div>
    <div class="flow-step">
      <div class="ic"><span class="ms">brush</span></div>
      <span class="lab">Session</span>
      <div class="sub">&nbsp;</div>
    </div>
    <div class="flow-line"></div>
    <div class="flow-step {{ $activeStep === 'aftercare' ? 'on' : '' }}">
      <a href="{{ route('artist.aftercare.index') }}" title="Open Aftercare">
        <div class="ic" @if($activeStep !== 'aftercare') style="background:#3E007C;color:#fff" @endif><span class="ms">healing</span></div>
        <span class="lab">After session</span>
        <div class="sub" @if($activeStep !== 'aftercare') style="color:#3E007C;font-weight:700" @endif>Aftercare</div>
      </a>
    </div>
  </div>
</div>
<style>
  .flow-step{flex:1;text-align:center}
  .flow-step a{display:block;color:inherit;text-decoration:none}
  .flow-step .ic{margin:0 auto;width:44px;height:44px;border-radius:50%}
  .flow-step.on .ic{background:#3E007C;color:#fff}
  .flow-step .lab{display:block;font-size:13px;margin-top:8px;font-weight:700}
  .flow-step .sub{font-size:12px;color:#9A929E}
  .flow-step.on .sub{color:#3E007C;font-weight:700}
  .flow-line{flex:.5;height:2px;background:#E1D5E6;margin-top:22px}
  @media (max-width:900px){
    .flow-card .row{flex-wrap:wrap;gap:10px 0}
    .flow-line{display:none}
    .flow-step{flex:1 1 30%;min-width:90px}
  }
</style>
