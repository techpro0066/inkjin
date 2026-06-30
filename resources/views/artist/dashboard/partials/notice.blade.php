@php
  $themes = [
    'amber' => [
      'box' => 'bg-amber-50 border-amber-200/80',
      'iconWrap' => 'bg-amber-100/80 border-amber-200/60',
      'icon' => 'text-amber-800',
      'title' => 'text-amber-950',
      'text' => 'text-amber-900/90',
      'btn' => 'bg-amber-700 hover:bg-amber-800',
    ],
    'blue' => [
      'box' => 'bg-[#EFF6FF] border-blue-200/80',
      'iconWrap' => 'bg-white/70 border-blue-200/60',
      'icon' => 'text-[#1D4ED8]',
      'title' => 'text-blue-900',
      'text' => 'text-blue-800/90',
      'btn' => 'bg-[#1D4ED8] hover:bg-blue-800',
    ],
    'red' => [
      'box' => 'bg-red-50 border-red-200/80',
      'iconWrap' => 'bg-red-100/80 border-red-200/60',
      'icon' => 'text-red-700',
      'title' => 'text-red-900',
      'text' => 'text-red-800/90',
      'btn' => 'bg-red-600 hover:bg-red-700',
    ],
  ];
  $t = $themes[$theme ?? 'amber'] ?? $themes['amber'];
@endphp

<div @if(!empty($id)) id="{{ $id }}" @endif class="dashboard-notice rounded-2xl border shadow-sm {{ $t['box'] }}" role="alert">
  <div class="dashboard-notice__inner flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-4">
    <div class="dashboard-notice__icon-wrap shrink-0 rounded-xl border flex items-center justify-center {{ $t['iconWrap'] }}">
      <span class="material-symbols-outlined dashboard-notice__icon {{ $t['icon'] }}" aria-hidden="true">{{ $icon }}</span>
    </div>
    <div class="dashboard-notice__content min-w-0 flex-1">
      <h3 class="dashboard-notice__title font-bold {{ $t['title'] }}">{{ $title }}</h3>
      <p class="dashboard-notice__text {{ $t['text'] }}">{{ $description }}</p>
    </div>
    <a href="{{ $buttonUrl }}" class="dashboard-notice__btn shrink-0 inline-flex items-center justify-center gap-1.5 whitespace-nowrap text-xs font-bold text-white rounded-xl shadow-sm transition-colors {{ $t['btn'] }}">
      @if(!empty($buttonIcon))
        <span class="material-symbols-outlined text-base" aria-hidden="true">{{ $buttonIcon }}</span>
      @endif
      {{ $buttonText }}
    </a>
  </div>
</div>
