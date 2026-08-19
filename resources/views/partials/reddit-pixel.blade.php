@php
  $redditPixelId = $redditPixelId ?? 'a2_jj24o3t8ehig';
  $redditEvent = $event ?? 'PageVisit';
@endphp
<script>
!function(w,d){if(!w.rdt){var p=w.rdt=function(){p.sendEvent?p.sendEvent.apply(p,arguments):p.callQueue.push(arguments)};p.callQueue=[];var t=d.createElement("script");t.src="https://www.redditstatic.com/ads/pixel.js?pixel_id={{ $redditPixelId }}",t.async=!0;var s=d.getElementsByTagName("script")[0];s.parentNode.insertBefore(t,s)}}(window,document);
rdt('init','{{ $redditPixelId }}');
rdt('track', '{{ $redditEvent }}');
</script>
