@php
  /** @var array<int, array{label:string,value:mixed}> $rows */
@endphp
<div class="bg-white rounded-2xl border border-outline-variant/20 p-5">
  @if(!empty($title))
    <h3 class="text-sm font-bold text-on-surface mb-4">{{ $title }}</h3>
  @endif
  <div class="space-y-0">
    @foreach($rows as $row)
      @php
        $value = $row['value'] ?? null;
        if ($value === null || $value === '') {
          $value = '—';
        }
      @endphp
      <div class="flex justify-between gap-4 py-2.5 {{ ! $loop->last ? 'border-b border-outline-variant/10' : '' }}">
        <span class="text-sm text-on-surface-variant shrink-0">{{ $row['label'] }}</span>
        <span class="text-sm font-semibold text-on-surface text-right break-words whitespace-pre-line">{{ $value }}</span>
      </div>
    @endforeach
  </div>
</div>
