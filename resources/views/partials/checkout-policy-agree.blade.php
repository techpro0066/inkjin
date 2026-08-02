@php
  $agreeCheckboxId = $agreeCheckboxId ?? 'agreePolicy';
  $agreeOnChange = $agreeOnChange ?? null;
@endphp

<label class="flex items-start gap-2 mb-4 cursor-pointer">
  <input
    type="checkbox"
    id="{{ $agreeCheckboxId }}"
    class="mt-0.5 accent-primary"
    @if($agreeOnChange) onchange="{{ $agreeOnChange }}" @endif
  >
  <span class="text-xs text-on-surface-variant leading-relaxed">
    I agree to the
    <a href="javascript:void(0)" onclick="event.preventDefault(); expandCancellationPolicy();" class="text-primary underline font-semibold">artist's cancellation policy</a>
    (governing my tattoo appointment) and
    <a href="https://inkjin.com/en/terms" target="_blank" rel="noopener noreferrer" class="text-primary underline font-semibold">Inkjin's terms of service</a>
    (governing the booking platform and fee).
  </span>
</label>
