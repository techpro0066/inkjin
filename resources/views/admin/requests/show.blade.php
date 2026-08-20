@extends('layouts.admin_dashboard_layout')

@section('title', 'Request '.$panel['reference'])

@section('content')
@php
  $typeKey = $kind === 'custom' ? 'custom' : 'flash';
  $typeLabel = $kind === 'custom' ? 'Custom' : 'Flash';
  $typeClasses = [
    'flash' => 'bg-sky-50 text-sky-700',
    'custom' => 'bg-violet-50 text-violet-700',
  ];
  $statusClasses = [
    'pending' => 'bg-amber-50 text-amber-700',
    'confirmed' => 'bg-blue-50 text-blue-700',
    'cancelled' => 'bg-red-50 text-red-700',
    'moved_to_booking' => 'bg-green-50 text-green-700',
  ];
  $availability = is_array($panel['availabilityDetails'] ?? null) ? $panel['availabilityDetails'] : [];
  $artistSlots = is_array($panel['artistSessionSlots'] ?? null) ? $panel['artistSessionSlots'] : [];
  $artistConsultSlots = is_array($panel['artistConsultationSlots'] ?? null) ? $panel['artistConsultationSlots'] : [];
  $clientSlots = is_array($panel['clientSessionSlots'] ?? null) ? $panel['clientSessionSlots'] : [];
  $clientConsultSlots = is_array($panel['clientConsultationSlots'] ?? null) ? $panel['clientConsultationSlots'] : [];

  $formatSlots = static function (array $slots): string {
      if ($slots === []) {
          return '—';
      }
      $lines = [];
      foreach ($slots as $slot) {
          $date = (string) ($slot['date'] ?? '');
          $ranges = [];
          foreach ((array) ($slot['ranges'] ?? []) as $range) {
              $ranges[] = ($range['from'] ?? '').'–'.($range['to'] ?? '');
          }
          $lines[] = trim($date.' '.implode(', ', array_filter($ranges)));
      }

      return implode("\n", array_filter($lines)) ?: '—';
  };
@endphp
<main class="main-content flex-1 min-h-screen">
  <div class="p-6 md:p-10 lg:p-12 max-w-5xl">
    <a href="{{ route('admin.requests.index') }}" class="inline-flex items-center gap-1 text-sm text-on-surface-variant hover:text-primary mb-6 transition-colors">
      <span class="material-symbols-outlined text-[18px]">arrow_back</span> Back to requests
    </a>

    <div class="mb-6 flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
      <div>
        <h2 class="text-3xl font-extrabold text-on-surface tracking-tight">{{ $panel['reference'] }}</h2>
        <p class="text-on-surface-variant mt-1">
          @if($kind === 'flash')
            {{ $panel['designTitle'] ?? 'Flash request' }}
          @else
            Custom tattoo request
          @endif
        </p>
        <div class="flex flex-wrap items-center gap-2 mt-3">
          <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold {{ $typeClasses[$typeKey] }}">{{ $typeLabel }}</span>
          <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold {{ $statusClasses[$panel['status'] ?? ''] ?? 'bg-gray-100 text-gray-700' }}">{{ $panel['filterStatus'] ?? ucfirst((string) ($panel['status'] ?? '')) }}</span>
        </div>
      </div>
      <p class="text-sm text-on-surface-variant">Submitted {{ $panel['submittedAt'] ?? '—' }}</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-5">
      @include('admin.partials.detail-card', [
        'title' => 'Client',
        'rows' => [
          ['label' => 'Name', 'value' => $panel['clientName'] ?? null],
          ['label' => 'Email', 'value' => $panel['clientEmail'] ?? null],
          ['label' => 'Phone', 'value' => $panel['clientPhone'] ?? null],
        ],
      ])

      @include('admin.partials.detail-card', [
        'title' => 'Artist',
        'rows' => [
          ['label' => 'Name', 'value' => $requestModel->artist?->name ?: ('Artist #'.$requestModel->artist_id)],
          ['label' => 'Email', 'value' => $requestModel->artist?->email],
          ['label' => 'Phone', 'value' => $requestModel->artist?->phone_number],
          ['label' => 'User ID', 'value' => $requestModel->artist_id],
        ],
      ])
    </div>

    @if($kind === 'flash')
      <div class="mb-5">
        @include('admin.partials.detail-card', [
          'title' => 'Design',
          'rows' => [
            ['label' => 'Title', 'value' => $panel['designTitle'] ?? null],
            ['label' => 'Style', 'value' => $panel['designStyle'] ?? null],
            ['label' => 'Price', 'value' => $panel['priceLabel'] ?? null],
            ['label' => 'Placement', 'value' => $panel['placement'] ?? null],
            ['label' => 'Health / allergies', 'value' => $panel['health'] ?? null],
            ['label' => 'Consultation', 'value' => $panel['consultationLabel'] ?? null],
            ['label' => 'Scheduling', 'value' => $panel['schedulingLabel'] ?? null],
          ],
        ])
      </div>
      @if(!empty($panel['designImage']))
        <div class="mb-5 bg-white rounded-2xl border border-outline-variant/20 p-5">
          <h3 class="text-sm font-bold text-on-surface mb-3">Design image</h3>
          <img src="{{ $panel['designImage'] }}" alt="" class="max-w-xs rounded-xl border border-outline-variant/20">
        </div>
      @endif
    @else
      <div class="mb-5">
        @include('admin.partials.detail-card', [
          'title' => 'Quote & details',
          'rows' => [
            ['label' => 'Request type', 'value' => ucfirst((string) ($panel['type'] ?? 'auto'))],
            ['label' => 'Estimated price', 'value' => $panel['estimatedPriceLabel'] ?? (($panel['estimatedPrice'] ?? null) ? '€'.number_format((float) $panel['estimatedPrice'], 2) : null)],
            ['label' => 'Estimated time', 'value' => $panel['estimatedTime'] ?? null],
            ['label' => 'Sessions', 'value' => $panel['numberOfSessions'] ?? null],
            ['label' => 'Message for client', 'value' => $panel['messageForClient'] ?? null],
            ['label' => 'Scheduling', 'value' => $panel['schedulingLabel'] ?? null],
            ['label' => 'Referral source', 'value' => $panel['referralSource'] ?? null],
          ],
        ])
      </div>
    @endif

    <div class="mb-5">
      @include('admin.partials.detail-card', [
        'title' => 'Availability preferences',
        'rows' => [
          ['label' => 'Preferred dates', 'value' => !empty($availability['preferredDates']) ? implode(', ', (array) $availability['preferredDates']) : null],
          ['label' => 'Preferred days', 'value' => !empty($availability['preferredDays']) ? implode(', ', (array) $availability['preferredDays']) : null],
          ['label' => 'Flexibility', 'value' => $availability['flexibility'] ?? null],
          ['label' => 'Urgency', 'value' => $availability['urgency'] ?? null],
          ['label' => 'Avoid dates', 'value' => $availability['avoidDates'] ?? null],
          ['label' => 'Session gap', 'value' => $availability['sessionGap'] ?? null],
        ],
      ])
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-5">
      @include('admin.partials.detail-card', [
        'title' => 'Artist offered slots',
        'rows' => [
          ['label' => 'Session slots', 'value' => $formatSlots($artistSlots)],
          ['label' => 'Consultation slots', 'value' => $formatSlots($artistConsultSlots)],
          ['label' => 'Artist notes', 'value' => $panel['artistNotesToClient'] ?? null],
        ],
      ])

      @include('admin.partials.detail-card', [
        'title' => 'Client selected slots',
        'rows' => [
          ['label' => 'Session slots', 'value' => $formatSlots($clientSlots)],
          ['label' => 'Consultation slots', 'value' => $formatSlots($clientConsultSlots)],
        ],
      ])
    </div>

    <div class="mb-5">
      @include('admin.partials.question-answers', ['questionsAnswers' => $requestModel->questions_answers])
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-5">
      @include('admin.partials.detail-card', [
        'title' => 'Notes',
        'rows' => [
          ['label' => 'Additional notes', 'value' => $panel['additionalNotes'] ?? ($kind === 'custom' ? ($requestModel->anything_else_notes ?? null) : null)],
          ['label' => 'Decline reason', 'value' => $panel['reasonDecline'] ?? $requestModel->reason_decline],
        ],
      ])

      @include('admin.partials.detail-card', [
        'title' => 'System',
        'rows' => [
          ['label' => 'Request ID', 'value' => $requestModel->id],
          ['label' => 'Status (DB)', 'value' => $requestModel->status],
          ['label' => 'Linked booking ID', 'value' => $requestModel->booking_id],
          ['label' => 'Created', 'value' => $requestModel->created_at?->format('M j, Y · H:i')],
          ['label' => 'Updated', 'value' => $requestModel->updated_at?->format('M j, Y · H:i')],
        ],
      ])
    </div>

    @if($requestModel->booking_id)
      <div class="mt-2">
        <a href="{{ route('admin.bookings.show', $requestModel->booking_id) }}" class="inline-flex items-center gap-2 text-sm font-semibold text-primary hover:underline">
          <span class="material-symbols-outlined text-[18px]">open_in_new</span>
          View linked booking
        </a>
      </div>
    @endif
  </div>
</main>
@endsection
