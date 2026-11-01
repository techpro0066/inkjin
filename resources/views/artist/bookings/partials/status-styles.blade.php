@php
  $statusStyles = [
      'confirmed' => 'g',
      'pending' => 'a',
      'cancelled' => 'r',
      'declined' => 'r',
      'completed' => 'k',
      'no_show' => 'a',
      'rescheduled' => 'b',
  ];
  $statusBadgeRing = [
      'confirmed' => 'bg-green-50 text-green-700 ring-green-500/20',
      'pending' => 'bg-amber-50 text-amber-900 ring-amber-500/20',
      'cancelled' => 'bg-red-50 text-red-700 ring-red-500/20',
      'declined' => 'bg-red-50 text-red-700 ring-red-500/20',
      'completed' => 'bg-slate-100 text-slate-700 ring-slate-500/15',
      'no_show' => 'bg-orange-50 text-orange-900 ring-orange-500/20',
      'rescheduled' => 'bg-blue-50 text-blue-800 ring-blue-500/15',
  ];
@endphp
