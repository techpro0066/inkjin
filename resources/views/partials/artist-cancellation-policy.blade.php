@php
  $cwRaw = strtolower(trim((string) ($userDetail->cancellation_window ?? '48h')));
  if (str_contains($cwRaw, 'w')) {
      preg_match('/(\d+)/', $cwRaw, $m);
      $n = (int) ($m[1] ?? 1);
      $cancelWindowHuman = $n === 1 ? '1 week' : $n.' weeks';
  } elseif (str_contains($cwRaw, 'day')) {
      preg_match('/(\d+)/', $cwRaw, $m);
      $n = (int) ($m[1] ?? 1);
      $cancelWindowHuman = $n === 1 ? '1 day' : $n.' days';
  } else {
      preg_match('/(\d+)/', $cwRaw, $m);
      $n = (int) ($m[1] ?? 48);
      $cancelWindowHuman = $n === 1 ? '1 hour' : $n.' hours';
  }

  $reschedulePolicy = strtolower((string) ($userDetail->reschedule_times ?? 'never'));
  $rescheduleText = match ($reschedulePolicy) {
      'once' => 'The artist allows you to reschedule your appointment once',
      'twice' => 'The artist allows you to reschedule your appointment twice',
      'unlimited' => 'The artist allows unlimited reschedules before the cancellation deadline',
      default => 'Rescheduling is not allowed for this artist',
  };
@endphp

<div class="bg-surface-container-low rounded-2xl border border-outline-variant/20 mb-4" id="cancellationPolicySection">
  <button type="button" onclick="toggleCancellationPolicy()" class="w-full flex items-center justify-between p-4 text-left">
    <span class="text-sm font-semibold text-on-surface flex items-center gap-2">📋 Cancellation Policy</span>
    <span class="material-symbols-outlined text-on-surface-variant text-[20px] transition-transform" id="cancPolicyArrow" style="transition: transform 0.2s ease;">expand_more</span>
  </button>
  <div class="hidden px-4 pb-4" id="cancellationPolicyContent">
    <div class="text-sm text-on-surface-variant space-y-1.5">
      <p class="font-semibold text-on-surface mb-2">Artist's Cancellation Policy:</p>
      <p>• Full refund if canceled at least {{ $cancelWindowHuman }} before your appointment</p>
      <p>• No refund if canceled less than {{ $cancelWindowHuman }} before your appointment</p>
      <p>• {{ $rescheduleText }}</p>
      <p>• No-shows forfeit the full deposit</p>
    </div>
  </div>
</div>
