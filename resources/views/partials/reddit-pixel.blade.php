@php
  $redditPixelId = $redditPixelId ?? 'a2_jj24o3t8ehig';
  $redditEvent = $event ?? 'PageVisit';
  $redditCustomEvents = ['Step_1', 'Step_2', 'Step_3', 'Step_4', 'Step_5', 'Step_6', 'Active'];
  $redditUseCustomEvent = in_array($redditEvent, $redditCustomEvents, true);
@endphp
<script>
!function(w,d){if(!w.rdt){var p=w.rdt=function(){p.sendEvent?p.sendEvent.apply(p,arguments):p.callQueue.push(arguments)};p.callQueue=[];var t=d.createElement("script");t.src="https://www.redditstatic.com/ads/pixel.js?pixel_id={{ $redditPixelId }}",t.async=!0;var s=d.getElementsByTagName("script")[0];s.parentNode.insertBefore(t,s)}}(window,document);
rdt('init','{{ $redditPixelId }}');
@if ($redditUseCustomEvent)
rdt('track', 'Custom', { customEventName: '{{ $redditEvent }}' });
@else
rdt('track', '{{ $redditEvent }}');
@endif
</script>
