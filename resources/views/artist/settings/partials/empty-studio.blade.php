@php $emptyMeta = $emptyMeta ?? ['left_name' => null, 'left_on' => null, 'upcoming' => 0]; @endphp

<div class="dwarn" style="display:flex;gap:12px;align-items:center;background:#FDECEC;border:1px solid #F3C9C9;border-radius:14px;padding:14px 18px;color:#8E1B1B;margin-bottom:14px;flex-wrap:wrap">
  <span class="ms" style="color:#C62828;font-size:26px;font-variation-settings:'FILL' 1">pause_circle</span>
  <div style="flex:1">
    <b>New bookings are paused</b>
    <div style="font-size:13px;margin-top:3px;line-height:1.5">You're not linked to a studio or your own space, so clients have no address to book you at. Add where you work to take bookings again.</div>
  </div>
</div>

<div class="card" style="margin-bottom:14px">
  <div class="ch">
    <div>
      <h3>Where you work</h3>
      <div class="faint" style="font-size:12.5px;margin-top:2px">Where clients come for their tattoo</div>
    </div>
  </div>
  <div style="padding:18px 22px">
    <div style="text-align:center;padding:26px 10px">
      <div class="ic" style="margin:0 auto;width:56px;height:56px;border-radius:50%">
        <span class="ms" style="font-size:26px">storefront</span>
      </div>
      <h3 style="margin-top:14px">You're not linked to a studio</h3>
      <div class="muted" style="max-width:440px;margin:6px auto 0;line-height:1.5">
        @if(!empty($emptyMeta['left_name']) && !empty($emptyMeta['left_on']))
          You left {{ $emptyMeta['left_name'] }} on {{ $emptyMeta['left_on'] }}.
          @if(($emptyMeta['upcoming'] ?? 0) > 0)
            Your {{ $emptyMeta['upcoming'] }} upcoming booking{{ $emptyMeta['upcoming'] === 1 ? '' : 's' }} there stay as they are.
          @else
            Your upcoming bookings there stay as they are.
          @endif
        @else
          Add where you work so clients know where to find you.
        @endif
      </div>
      <div class="row" style="justify-content:center;margin-top:18px">
        <a class="btn" href="{{ route('settings.studio.change') }}">
          <span class="ms">add</span>Add where you work
        </a>
      </div>
      <div class="help" style="margin-top:10px">Find a studio on Bookpay, add one that isn't, or add your own space.</div>
    </div>
  </div>
</div>
