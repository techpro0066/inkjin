@php
  $activeTab = $activeTab ?? 'design';
@endphp

<!-- Page Header -->
<div class="mb-6">
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
      <h2 class="text-3xl font-extrabold text-on-surface tracking-tight">Requests</h2>
      <p class="text-on-surface-variant mt-1">Review client requests for available designs and customs jobs.</p>
    </div>
    @isset($customPendingCount)
      <span id="customPendingBadge" class="inline-flex items-center gap-2 bg-primary/10 text-primary text-sm font-semibold px-4 py-2 rounded-full {{ $customPendingCount > 0 ? '' : 'hidden' }}">
        <span class="material-symbols-outlined text-[18px]">inbox</span>
        <span id="customPendingBadgeText">{{ $customPendingCount }} pending</span>
      </span>
    @endisset
  </div>
</div>

<!-- Request Type Tabs -->
<div class="flex items-center gap-1 mb-6 border-b border-outline-variant/20 pb-0 overflow-x-auto">
  <a href="{{ route('artist.requests.index') }}" class="px-4 py-3 text-sm font-semibold whitespace-nowrap border-b-2 {{ $activeTab === 'design' ? 'border-primary text-primary' : 'border-transparent text-on-surface-variant hover:text-on-surface hover:border-outline-variant' }} transition-all">Available Design Requests</a>
  <a href="{{ route('artist.custom-requests.index') }}" class="px-4 py-3 text-sm font-semibold whitespace-nowrap border-b-2 {{ $activeTab === 'custom' ? 'border-primary text-primary' : 'border-transparent text-on-surface-variant hover:text-on-surface hover:border-outline-variant' }} transition-all">Custom Requests</a>
</div>
