@php
  $name = $name ?? 'the studio';
@endphp
<div class="popend" id="invbar" style="margin:0 0 14px">
  <span class="ms">mail</span>
  <div style="flex:1">
    <b>Invitation sent to {{ $name }}</b>
    <div class="faint" style="font-size:12.5px;margin-top:2px">You're paid directly until they join Bookpay and confirm your split.</div>
  </div>
</div>
