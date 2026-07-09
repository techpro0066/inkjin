@php
  $statusClasses = [
    'Active' => 'bg-green-50 text-green-700',
    'Pending Onboarding' => 'bg-amber-50 text-amber-700',
    'Suspended' => 'bg-red-50 text-red-700',
    'Deactivated' => 'bg-gray-100 text-gray-600',
  ];
  $dotClasses = [
    'Active' => 'bg-green-500',
    'Pending Onboarding' => 'bg-amber-500',
    'Suspended' => 'bg-red-500',
    'Deactivated' => 'bg-gray-400',
  ];
  $badgeClass = $statusClasses[$status] ?? $statusClasses['Active'];
  $dotClass = $dotClasses[$status] ?? $dotClasses['Active'];
@endphp
<span class="inline-flex items-center gap-1.5 {{ $badgeClass }} text-xs font-semibold px-3 py-1 rounded-full">
  <span class="w-1.5 h-1.5 rounded-full {{ $dotClass }}"></span>
  {{ $status }}
</span>
