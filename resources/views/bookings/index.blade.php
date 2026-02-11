@extends('layouts.dashboard_layout')

@section('title', auth()->user()->role === 'artist' ? 'Bookings Received' : 'My Bookings')

@section('content')
@if (session('success'))
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="ti ti-check-circle me-2"></i>
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
@endif

@if (session('error'))
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="ti ti-alert-circle me-2"></i>
    {{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
@endif

<h4 class="fw-bold py-3 mb-4">
  <span class="text-muted fw-light">Dashboard /</span> My Bookings
</h4>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">
      @if(auth()->user()->role === 'artist')
        Bookings Received
      @else
        My Bookings
      @endif
    </h5>
  </div>
  <div class="card-body">
    <!-- Filters -->
    <div class="row mb-4">
      <div class="col-md-12">
        <form method="GET" action="{{ route('bookings.index') }}" class="row g-3">
          <div class="col-md-3">
            <label for="status" class="form-label">Status</label>
            <select name="status" id="status" class="form-select">
              <option value="">All Statuses</option>
              <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
              <option value="confirmed" {{ request('status') === 'confirmed' ? 'selected' : '' }}>Confirmed</option>
              <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
              <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
              <option value="no_show" {{ request('status') === 'no_show' ? 'selected' : '' }}>No Show</option>
            </select>
          </div>
          <div class="col-md-3">
            <label for="payment_status" class="form-label">Payment Status</label>
            <select name="payment_status" id="payment_status" class="form-select">
              <option value="">All Payment Statuses</option>
              <option value="pending" {{ request('payment_status') === 'pending' ? 'selected' : '' }}>Pending</option>
              <option value="paid" {{ request('payment_status') === 'paid' ? 'selected' : '' }}>Paid</option>
              <option value="refunded" {{ request('payment_status') === 'refunded' ? 'selected' : '' }}>Refunded</option>
              <option value="failed" {{ request('payment_status') === 'failed' ? 'selected' : '' }}>Failed</option>
            </select>
          </div>
          <div class="col-md-2">
            <label for="date_from" class="form-label">From Date</label>
            <input type="date" name="date_from" id="date_from" class="form-control" value="{{ request('date_from') }}">
          </div>
          <div class="col-md-2">
            <label for="date_to" class="form-label">To Date</label>
            <input type="date" name="date_to" id="date_to" class="form-control" value="{{ request('date_to') }}">
          </div>
          <div class="col-md-2 d-flex align-items-end">
            <button type="submit" class="btn btn-primary w-100">
              <i class="ti ti-filter me-1"></i> Filter
            </button>
          </div>
          @if(request()->hasAny(['status', 'payment_status', 'date_from', 'date_to']))
            <div class="col-md-12">
              <a href="{{ route('bookings.index') }}" class="btn btn-label-secondary btn-sm">
                <i class="ti ti-x me-1"></i> Clear Filters
              </a>
            </div>
          @endif
        </form>
      </div>
    </div>
    
    <!-- Summary Stats -->
    <div class="row mb-4">
      <div class="col-md-3">
        <div class="card bg-label-primary">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="flex-grow-1">
                <h6 class="mb-0">Total Bookings</h6>
                <h3 class="mb-0">{{ $stats['total'] }}</h3>
              </div>
              <div class="avatar">
                <div class="avatar-initial bg-primary rounded">
                  <i class="ti ti-calendar-check"></i>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card bg-label-success">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="flex-grow-1">
                <h6 class="mb-0">Confirmed</h6>
                <h3 class="mb-0">{{ $stats['confirmed'] }}</h3>
              </div>
              <div class="avatar">
                <div class="avatar-initial bg-success rounded">
                  <i class="ti ti-check"></i>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card bg-label-warning">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="flex-grow-1">
                <h6 class="mb-0">Pending</h6>
                <h3 class="mb-0">{{ $stats['pending'] }}</h3>
              </div>
              <div class="avatar">
                <div class="avatar-initial bg-warning rounded">
                  <i class="ti ti-clock"></i>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card bg-label-info">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="flex-grow-1">
                <h6 class="mb-0">Upcoming</h6>
                <h3 class="mb-0">{{ $stats['upcoming'] }}</h3>
              </div>
              <div class="avatar">
                <div class="avatar-initial bg-info rounded">
                  <i class="ti ti-calendar-up"></i>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    @if($bookings->count() > 0)
      <div class="table-responsive">
        <table class="table table-hover">
          <thead>
            <tr>
              <th>Date</th>
              <th>Time</th>
              @if(auth()->user()->role === 'artist')
                <th>Customer</th>
              @else
                <th>Artist</th>
              @endif
              <th>Tattoo</th>
              <th>Status</th>
              <th>Payment</th>
              <th>Amount</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            @foreach($bookings as $booking)
              @php
                // Convert UTC times to user's timezone or booking timezone
                $timezone = $booking->timezone ?? 'UTC';
                $bookingDateTime = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $booking->booking_date->format('Y-m-d') . ' ' . $booking->start_time_utc, 'UTC');
                $bookingDateTimeLocal = $bookingDateTime->setTimezone($timezone);
                $endDateTime = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $booking->booking_date->format('Y-m-d') . ' ' . $booking->end_time_utc, 'UTC');
                $endDateTimeLocal = $endDateTime->setTimezone($timezone);
                
                // Status badge colors
                $statusColors = [
                  'pending' => 'bg-label-warning',
                  'confirmed' => 'bg-label-success',
                  'cancelled' => 'bg-label-danger',
                  'completed' => 'bg-label-info',
                  'rescheduled' => 'bg-label-secondary',
                  'no_show' => 'bg-label-dark',
                ];
                
                // Payment status colors
                $paymentColors = [
                  'pending' => 'bg-label-warning',
                  'paid' => 'bg-label-success',
                  'refunded' => 'bg-label-danger',
                  'failed' => 'bg-label-danger',
                ];
              @endphp
              <tr>
                <td>
                  <div class="d-flex flex-column">
                    <span class="fw-semibold">{{ $bookingDateTimeLocal->format('M d, Y') }}</span>
                    <small class="text-muted">{{ $bookingDateTimeLocal->format('l') }}</small>
                  </div>
                </td>
                <td>
                  <div class="d-flex flex-column">
                    <span class="fw-semibold">{{ $bookingDateTimeLocal->format('g:i A') }}</span>
                    <small class="text-muted">{{ $endDateTimeLocal->format('g:i A') }}</small>
                  </div>
                </td>
                <td>
                  @if(auth()->user()->role === 'artist')
                    <div class="d-flex flex-column">
                      <span class="fw-semibold">{{ $booking->user->name ?? 'N/A' }}</span>
                      <small class="text-muted">{{ $booking->user->email ?? '' }}</small>
                    </div>
                  @else
                    <div class="d-flex flex-column">
                      <span class="fw-semibold">{{ $booking->artist->name ?? 'N/A' }}</span>
                      @if($booking->tattoo && $booking->tattoo->artist)
                        <small class="text-muted">{{ $booking->tattoo->artist->display_name ?? '' }}</small>
                      @endif
                    </div>
                  @endif
                </td>
                <td>
                  @if($booking->tattoo)
                    <div class="d-flex flex-column">
                      <span class="fw-semibold">{{ \Illuminate\Support\Str::limit($booking->tattoo->title ?? 'Custom Tattoo', 30) }}</span>
                      @if($booking->booking_type === 'custom')
                        <small class="text-muted">Custom Design</small>
                      @endif
                    </div>
                  @else
                    <span class="text-muted">N/A</span>
                  @endif
                </td>
                <td>
                  <div class="d-flex flex-column gap-1">
                    <span class="badge {{ $statusColors[$booking->status] ?? 'bg-label-secondary' }}">
                      {{ ucfirst($booking->status) }}
                    </span>
                    @if($booking->reschedule_status === 'pending' && $booking->reschedule_requested_by === 'artist')
                      <span class="badge bg-label-warning mt-1">
                        <i class="ti ti-clock me-1"></i>Reschedule Requested
                      </span>
                    @endif
                  </div>
                </td>
                <td>
                  <span class="badge {{ $paymentColors[$booking->payment_status] ?? 'bg-label-secondary' }}">
                    {{ ucfirst($booking->payment_status) }}
                  </span>
                </td>
                <td>
                  <div class="d-flex flex-column">
                    <span class="fw-semibold">{{ $booking->currency ?? 'USD' }} {{ number_format($booking->total_amount_paid ?? 0, 2) }}</span>
                    @if($booking->full_amount_paid)
                      <small class="text-success">Full Payment</small>
                    @else
                      <small class="text-muted">Deposit: {{ $booking->currency ?? 'USD' }} {{ number_format($booking->deposit_amount ?? 0, 2) }}</small>
                    @endif
                  </div>
                </td>
                <td>
                  <div class="d-flex gap-2 flex-wrap">
                    <button type="button" class="btn btn-sm btn-label-info" data-bs-toggle="modal" data-bs-target="#bookingModal{{ $booking->id }}">
                      <i class="ti ti-eye"></i>
                    </button>
                    @if($booking->status === 'confirmed')
                      @if(auth()->user()->role === 'artist' && $booking->artist_user_id === auth()->id())
                        @if($booking->status === 'confirmed' && $booking->reschedule_status !== 'pending')
                          <button type="button" class="btn btn-sm btn-label-warning" onclick="showArtistRescheduleModal({{ $booking->id }})">
                            <i class="ti ti-calendar-event"></i> Request Reschedule
                          </button>
                        @elseif($booking->reschedule_status === 'pending' && $booking->reschedule_requested_by === 'artist')
                          <span class="badge bg-label-warning">
                            <i class="ti ti-clock me-1"></i>Pending Client Response
                          </span>
                        @endif
                        <button type="button" class="btn btn-sm btn-label-danger" onclick="showCancelModal({{ $booking->id }})">
                          <i class="ti ti-x"></i> Cancel
                        </button>
                        <button type="button" class="btn btn-sm btn-label-warning" onclick="showNoShowModal({{ $booking->id }})">
                          <i class="ti ti-user-off"></i> No-Show
                        </button>
                      @elseif($booking->user_id === auth()->id())
                        @if($booking->status === 'confirmed')
                          @if($booking->reschedule_status === 'pending' && $booking->reschedule_requested_by === 'artist')
                            <a href="{{ route('bookings.reschedule-flow', $booking->id) }}" class="btn btn-sm btn-warning">
                              <i class="ti ti-calendar-event me-1"></i>Select New Time
                            </a>
                            <button type="button" class="btn btn-sm btn-label-danger" onclick="declineRescheduleRequest({{ $booking->id }})">
                              <i class="ti ti-x"></i> Decline
                            </button>
                          @else
                            <a href="{{ route('bookings.reschedule-flow', $booking->id) }}" class="btn btn-sm btn-label-primary">
                              <i class="ti ti-calendar-event"></i> Reschedule
                            </a>
                          @endif
                        @endif
                        <button type="button" class="btn btn-sm btn-label-danger" onclick="showCancelModal({{ $booking->id }})">
                          <i class="ti ti-x"></i> Cancel
                        </button>
                      @endif
                    @endif
                  </div>
                </td>
              </tr>
              
              <!-- Booking Detail Modal -->
              <div class="modal fade" id="bookingModal{{ $booking->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title">Booking Details</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                      <div class="row mb-3">
                        <div class="col-md-6">
                          <strong>Booking Date:</strong>
                          <p>{{ $bookingDateTimeLocal->format('l, F d, Y') }}</p>
                        </div>
                        <div class="col-md-6">
                          <strong>Time:</strong>
                          <p>{{ $bookingDateTimeLocal->format('g:i A') }} - {{ $endDateTimeLocal->format('g:i A') }}</p>
                        </div>
                      </div>
                      
                      <div class="row mb-3">
                        <div class="col-md-6">
                          <strong>Status:</strong>
                          <p><span class="badge {{ $statusColors[$booking->status] ?? 'bg-label-secondary' }}">{{ ucfirst($booking->status) }}</span></p>
                        </div>
                        <div class="col-md-6">
                          <strong>Payment Status:</strong>
                          <p><span class="badge {{ $paymentColors[$booking->payment_status] ?? 'bg-label-secondary' }}">{{ ucfirst($booking->payment_status) }}</span></p>
                        </div>
                      </div>
                      
                      @if(auth()->user()->role === 'artist')
                        <div class="row mb-3">
                          <div class="col-md-6">
                            <strong>Customer Name:</strong>
                            <p>{{ $booking->user->name ?? 'N/A' }}</p>
                          </div>
                          <div class="col-md-6">
                            <strong>Customer Email:</strong>
                            <p>{{ $booking->user->email ?? 'N/A' }}</p>
                          </div>
                        </div>
                      @else
                        <div class="row mb-3">
                          <div class="col-md-6">
                            <strong>Artist:</strong>
                            <p>{{ $booking->artist->name ?? 'N/A' }}</p>
                          </div>
                          @if($booking->tattoo && $booking->tattoo->artist)
                            <div class="col-md-6">
                              <strong>Studio:</strong>
                              <p>{{ $booking->tattoo->artist->display_name ?? 'N/A' }}</p>
                            </div>
                          @endif
                        </div>
                      @endif
                      
                      @if($booking->tattoo)
                        <div class="row mb-3">
                          <div class="col-12">
                            <strong>Tattoo:</strong>
                            <p>{{ $booking->tattoo->title ?? 'Custom Tattoo' }}</p>
                          </div>
                        </div>
                      @endif
                      
                      <div class="row mb-3">
                        <div class="col-md-6">
                          <strong>Total Amount:</strong>
                          <p>{{ $booking->currency ?? 'USD' }} {{ number_format($booking->total_amount_paid ?? 0, 2) }}</p>
                        </div>
                        <div class="col-md-6">
                          <strong>Payment Type:</strong>
                          <p>{{ $booking->full_amount_paid ? 'Full Payment' : 'Deposit Only' }}</p>
                        </div>
                      </div>
                      
                      @if($booking->deposit_amount > 0 && !$booking->full_amount_paid)
                        <div class="row mb-3">
                          <div class="col-12">
                            <strong>Deposit Amount:</strong>
                            <p>{{ $booking->currency ?? 'USD' }} {{ number_format($booking->deposit_amount, 2) }}</p>
                          </div>
                        </div>
                      @endif
                      
                      @if($booking->platform_fee > 0)
                        <div class="row mb-3">
                          <div class="col-12">
                            <strong>Platform Fee:</strong>
                            <p>{{ $booking->currency ?? 'USD' }} {{ number_format($booking->platform_fee, 2) }}</p>
                          </div>
                        </div>
                      @endif
                      
                      @if($booking->google_meet_link)
                        <div class="row mb-3">
                          <div class="col-12">
                            <div class="card border-success">
                              <div class="card-body">
                                <h6 class="card-title text-success">
                                  <i class="ti ti-video me-2"></i>Video Meeting Link
                                </h6>
                                <p class="text-muted mb-2">
                                  Join your 30-minute consultation meeting
                                </p>
                                <a href="{{ $booking->google_meet_link }}" 
                                   target="_blank" 
                                   class="btn btn-success btn-sm">
                                  <i class="ti ti-external-link me-1"></i>Join Google Meet
                                </a>
                                <small class="text-muted d-block mt-2">
                                  <i class="ti ti-clock me-1"></i>
                                  Meeting scheduled for: 
                                  {{ $bookingDateTimeLocal->format('M j, Y') }} 
                                  at {{ $bookingDateTimeLocal->format('g:i A') }}
                                </small>
                              </div>
                            </div>
                          </div>
                        </div>
                      @endif
                      
                      @if($booking->questions_answers && count($booking->questions_answers) > 0)
                        <div class="row mb-3">
                          <div class="col-12">
                            <strong>Question Answers:</strong>
                            <div class="mt-2">
                              @foreach($booking->questions_answers as $questionId => $answer)
                                <div class="mb-2 p-2 bg-light rounded">
                                  <small class="text-muted">Question ID: {{ $questionId }}</small>
                                  <p class="mb-0">{{ is_array($answer) ? json_encode($answer) : $answer }}</p>
                                </div>
                              @endforeach
                            </div>
                          </div>
                        </div>
                      @endif
                      
                      @if($booking->notes)
                        <div class="row mb-3">
                          <div class="col-12">
                            <strong>Notes:</strong>
                            <p>{{ $booking->notes }}</p>
                          </div>
                        </div>
                      @endif
                      
                      @if($booking->cancelled_at)
                        <div class="row mb-3">
                          <div class="col-12">
                            <strong>Cancelled At:</strong>
                            <p>{{ $booking->cancelled_at->format('M d, Y g:i A') }}</p>
                          </div>
                          @if($booking->cancellation_reason)
                            <div class="col-12">
                              <strong>Cancellation Reason:</strong>
                              <p>{{ $booking->cancellation_reason }}</p>
                            </div>
                          @endif
                        </div>
                      @endif
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                  </div>
                </div>
              </div>
            @endforeach
          </tbody>
        </table>
      </div>
      
      <!-- Pagination -->
      <div class="d-flex justify-content-center mt-4">
        {{ $bookings->links() }}
      </div>
    @else
      <div class="text-center py-5">
        <i class="ti ti-calendar-x ti-5x text-muted mb-3"></i>
        <h5 class="text-muted">No bookings found</h5>
        <p class="text-muted">
          @if(auth()->user()->role === 'artist')
            You haven't received any bookings yet.
          @else
            You haven't made any bookings yet.
          @endif
        </p>
      </div>
    @endif
  </div>
</div>

<!-- Cancellation Modal -->
<div class="modal fade" id="cancelBookingModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Cancel Booking</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="cancelBookingModalBody">
        <!-- Content will be loaded dynamically -->
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-danger" id="confirmCancelBtn">Confirm Cancellation</button>
      </div>
    </div>
  </div>
</div>

<!-- No-Show Modal -->
<div class="modal fade" id="noShowModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Mark as No-Show</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>Are you sure you want to mark this booking as a no-show? The customer did not attend the scheduled appointment.</p>
        <p class="text-muted"><small>The customer's deposit will be forfeited as per the cancellation policy.</small></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-warning" id="confirmNoShowBtn">Mark as No-Show</button>
      </div>
    </div>
  </div>
</div>

<!-- Success Modal -->
<div class="modal fade" id="successModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title"><i class="ti ti-check-circle me-2"></i>Success</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="successModalBody">
        <!-- Content will be loaded dynamically -->
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-success" data-bs-dismiss="modal" onclick="location.reload()">OK</button>
      </div>
    </div>
  </div>
</div>

<!-- Error Modal -->
<div class="modal fade" id="errorModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title"><i class="ti ti-alert-circle me-2"></i>Error</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="errorModalBody">
        <!-- Content will be loaded dynamically -->
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
let currentBookingId = null;

function showCancelModal(bookingId) {
    currentBookingId = bookingId;
    const modal = new bootstrap.Modal(document.getElementById('cancelBookingModal'));
    const body = document.getElementById('cancelBookingModalBody');
    
    // Fetch cancellation info
    fetch(`/api/bookings/${bookingId}/cancellation-info`, {
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const info = data.data;
            const isBeforeDeadline = info.is_before_deadline;
            const refundAmount = info.estimated_refund.amount;
            const depositForfeited = info.estimated_refund.deposit_forfeited;
            const refundEligibility = info.refund_eligibility;
            
            let html = '';
            
            if (isBeforeDeadline) {
                html = `
                    <div class="alert alert-info">
                        <h6 class="mb-2"><i class="ti ti-info-circle me-2"></i>Cancellation Before Deadline</h6>
                        <p class="mb-0">You are cancelling before the cancellation deadline.</p>
                    </div>
                    <div class="alert alert-success">
                        <strong>✅ You will receive a full refund of ${info.estimated_refund.amount > 0 ? '$' + parseFloat(refundAmount).toFixed(2) : 'your payment'}</strong><br>
                        <small>Refund will be processed within 5-10 business days.</small>
                    </div>
                    <p><strong>Cancellation Deadline:</strong> ${new Date(info.cancellation_deadline).toLocaleString()}</p>
                    <p><strong>Current Time:</strong> ${new Date().toLocaleString()}</p>
                `;
            } else {
                if (refundEligibility === 'no_refund') {
                    html = `
                        <div class="alert alert-warning">
                            <h6 class="mb-2"><i class="ti ti-alert-triangle me-2"></i>Cancellation After Deadline</h6>
                            <p class="mb-0">You are cancelling after the cancellation deadline.</p>
                        </div>
                        <div class="alert alert-danger">
                            <strong>❌ Your deposit of $${parseFloat(depositForfeited).toFixed(2)} will be forfeited</strong><br>
                            <small>No refund will be issued.</small>
                        </div>
                        <p><strong>Cancellation Deadline:</strong> ${new Date(info.cancellation_deadline).toLocaleString()} <span class="text-danger">(Passed)</span></p>
                        <p><strong>Current Time:</strong> ${new Date().toLocaleString()}</p>
                    `;
                } else {
                    html = `
                        <div class="alert alert-warning">
                            <h6 class="mb-2"><i class="ti ti-alert-triangle me-2"></i>Cancellation After Deadline</h6>
                            <p class="mb-0">You are cancelling after the cancellation deadline.</p>
                        </div>
                        <div class="alert alert-info">
                            <strong>💰 Your deposit of $${parseFloat(depositForfeited).toFixed(2)} will be forfeited</strong><br>
                            <strong>✅ Remaining balance of $${parseFloat(refundAmount).toFixed(2)} will be refunded</strong><br>
                            <small>Refund will be processed within 5-10 business days.</small>
                        </div>
                        <p><strong>Cancellation Deadline:</strong> ${new Date(info.cancellation_deadline).toLocaleString()} <span class="text-danger">(Passed)</span></p>
                        <p><strong>Current Time:</strong> ${new Date().toLocaleString()}</p>
                    `;
                }
            }
            
            html += `
                <div class="mb-3">
                    <label for="cancellationReason" class="form-label">Reason (optional):</label>
                    <textarea class="form-control" id="cancellationReason" rows="3" placeholder="Please provide a reason for cancellation..."></textarea>
                </div>
            `;
            
            body.innerHTML = html;
            modal.show();
        } else {
            // Show error modal
            const errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
            const errorBody = document.getElementById('errorModalBody');
            errorBody.innerHTML = '<p>Failed to load cancellation information. Please try again.</p>';
            errorModal.show();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        // Show error modal
        const errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
        const errorBody = document.getElementById('errorModalBody');
        errorBody.innerHTML = '<p>An error occurred while loading cancellation information. Please try again.</p>';
        errorModal.show();
    });
}

function showNoShowModal(bookingId) {
    currentBookingId = bookingId;
    const modal = new bootstrap.Modal(document.getElementById('noShowModal'));
    modal.show();
}

document.getElementById('confirmCancelBtn')?.addEventListener('click', function() {
    if (!currentBookingId) return;
    
    const reason = document.getElementById('cancellationReason')?.value || '';
    const btn = this;
    btn.disabled = true;
    btn.textContent = 'Processing...';
    
    fetch(`/api/bookings/${currentBookingId}/cancel`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            reason: reason,
            confirmed: true
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Hide cancellation modal
            bootstrap.Modal.getInstance(document.getElementById('cancelBookingModal')).hide();
            
            // Show success modal
            const successModal = new bootstrap.Modal(document.getElementById('successModal'));
            const successBody = document.getElementById('successModalBody');
            const booking = data.booking;
            
            let successHtml = '<p class="mb-3">Your booking has been cancelled successfully.</p>';
            successHtml += '<div class="card border-success">';
            successHtml += '<div class="card-body">';
            successHtml += '<h6 class="card-title text-success">Cancellation Details</h6>';
            
            if (booking.refund_amount > 0) {
                successHtml += `<p class="mb-2"><strong>Refund Amount:</strong> $${parseFloat(booking.refund_amount).toFixed(2)}</p>`;
                successHtml += `<p class="mb-2"><strong>Refund Status:</strong> <span class="badge bg-info">${booking.refund_status || 'Processing'}</span></p>`;
                successHtml += '<p class="mb-0 text-muted"><small>The refund will be processed within 5-10 business days and will appear in your original payment method.</small></p>';
            } else if (booking.deposit_forfeited > 0) {
                successHtml += `<p class="mb-2"><strong>Deposit Forfeited:</strong> $${parseFloat(booking.deposit_forfeited).toFixed(2)}</p>`;
                successHtml += '<p class="mb-0 text-muted"><small>Your deposit has been forfeited as per the cancellation policy.</small></p>';
            } else {
                successHtml += '<p class="mb-0 text-muted"><small>No refund will be issued.</small></p>';
            }
            
            successHtml += '</div></div>';
            successHtml += '<p class="mt-3 mb-0"><small>You will receive a confirmation email shortly.</small></p>';
            
            successBody.innerHTML = successHtml;
            successModal.show();
            
            // Reload page after modal is closed
            document.getElementById('successModal').addEventListener('hidden.bs.modal', function() {
                location.reload();
            }, { once: true });
        } else {
            // Show error modal
            const errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
            const errorBody = document.getElementById('errorModalBody');
            errorBody.innerHTML = `<p>Failed to cancel booking: ${data.message || 'Unknown error'}</p>`;
            errorModal.show();
            
            btn.disabled = false;
            btn.textContent = 'Confirm Cancellation';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        
        // Show error modal
        const errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
        const errorBody = document.getElementById('errorModalBody');
        errorBody.innerHTML = '<p>An error occurred while cancelling the booking. Please try again.</p>';
        errorModal.show();
        
        btn.disabled = false;
        btn.textContent = 'Confirm Cancellation';
    });
});

document.getElementById('confirmNoShowBtn')?.addEventListener('click', function() {
    if (!currentBookingId) return;
    
    const btn = this;
    btn.disabled = true;
    btn.textContent = 'Processing...';
    
    fetch(`/api/bookings/${currentBookingId}/mark-no-show`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            confirmed: true
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Hide no-show modal
            bootstrap.Modal.getInstance(document.getElementById('noShowModal')).hide();
            
            // Show success modal
            const successModal = new bootstrap.Modal(document.getElementById('successModal'));
            const successBody = document.getElementById('successModalBody');
            const booking = data.booking;
            
            let successHtml = '<p class="mb-3">Booking has been marked as no-show successfully.</p>';
            successHtml += '<div class="card border-warning">';
            successHtml += '<div class="card-body">';
            successHtml += '<h6 class="card-title text-warning">No-Show Details</h6>';
            
            if (booking.deposit_forfeited > 0) {
                successHtml += `<p class="mb-2"><strong>Deposit Forfeited:</strong> $${parseFloat(booking.deposit_forfeited).toFixed(2)}</p>`;
                successHtml += '<p class="mb-0 text-muted"><small>The customer\'s deposit has been forfeited as per the cancellation policy.</small></p>';
            } else {
                successHtml += '<p class="mb-0 text-muted"><small>The customer will not receive a refund.</small></p>';
            }
            
            successHtml += '</div></div>';
            successHtml += '<p class="mt-3 mb-0"><small>Both you and the customer will receive notification emails.</small></p>';
            
            successBody.innerHTML = successHtml;
            successModal.show();
            
            // Reload page after modal is closed
            document.getElementById('successModal').addEventListener('hidden.bs.modal', function() {
                location.reload();
            }, { once: true });
        } else {
            // Show error modal
            const errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
            const errorBody = document.getElementById('errorModalBody');
            errorBody.innerHTML = `<p>Failed to mark as no-show: ${data.message || 'Unknown error'}</p>`;
            errorModal.show();
            
            btn.disabled = false;
            btn.textContent = 'Mark as No-Show';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        
        // Show error modal
        const errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
        const errorBody = document.getElementById('errorModalBody');
        errorBody.innerHTML = '<p>An error occurred while marking the booking as no-show. Please try again.</p>';
        errorModal.show();
        
        btn.disabled = false;
        btn.textContent = 'Mark as No-Show';
    });
});

// Rescheduling functions
function checkRescheduleEligibility(bookingId) {
    fetch(`/api/bookings/${bookingId}/can-reschedule`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data.can_reschedule) {
                // Redirect to reschedule page
                window.location.href = `/bookings/${bookingId}/reschedule`;
            } else {
                // Show message that reschedule is not available, offer cancellation
                const message = data.data?.message || 'Cannot reschedule this booking. This will be treated as cancellation.';
                if (confirm(message + '\n\nWould you like to cancel instead?')) {
                    showCancelModal(bookingId);
                }
            }
        })
        .catch(error => {
            console.error('Error checking reschedule eligibility:', error);
            alert('An error occurred. Please try again.');
        });
}

function showArtistRescheduleModal(bookingId) {
    // Use SweetAlert2 for input
    Swal.fire({
        title: 'Request Reschedule',
        html: `
            <p class="mb-3">You are requesting to reschedule this booking. The client will be notified and can select a new date/time.</p>
            <div class="form-group">
                <label for="reschedule-reason" class="form-label">Reason (optional):</label>
                <textarea id="reschedule-reason" class="form-control" rows="3" placeholder="e.g., Emergency conflict, scheduling issue, etc."></textarea>
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#ffc107',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Send Request',
        cancelButtonText: 'Cancel',
        preConfirm: () => {
            return document.getElementById('reschedule-reason').value || null;
        },
        didOpen: () => {
            // Focus on textarea
            document.getElementById('reschedule-reason').focus();
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const reason = result.value;
            
            // Show loading
            Swal.fire({
                title: 'Sending Request...',
                text: 'Please wait while we send the reschedule request.',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            fetch(`/api/bookings/${bookingId}/artist-request-reschedule`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    reason: reason || null
                })
            })
            .then(response => response.json())
            .then(data => {
                Swal.close();
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Request Sent!',
                        html: 'The reschedule request has been sent to the client. They will be notified via email and can select a new date/time.',
                        confirmButtonColor: '#28a745',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        location.reload(); // Refresh to show updated status
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Request Failed',
                        text: data.message || 'Failed to send reschedule request. Please try again.',
                        confirmButtonColor: '#dc3545'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.close();
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred. Please try again.',
                    confirmButtonColor: '#dc3545'
                });
            });
        }
    });
}

// Decline artist's reschedule request
function declineRescheduleRequest(bookingId) {
    Swal.fire({
        title: 'Decline Reschedule Request?',
        html: 'Are you sure you want to decline the artist\'s reschedule request?<br><br>You can still reschedule later if needed.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, decline',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Processing...',
                text: 'Please wait...',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            fetch(`/api/bookings/${bookingId}/decline-reschedule`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    reason: null
                })
            })
            .then(response => response.json())
            .then(data => {
                Swal.close();
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Request Declined',
                        text: 'The reschedule request has been declined. The booking remains at its original date/time.',
                        confirmButtonColor: '#28a745',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Failed',
                        text: data.message || 'Failed to decline reschedule request. Please try again.',
                        confirmButtonColor: '#dc3545'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.close();
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred. Please try again.',
                    confirmButtonColor: '#dc3545'
                });
            });
        }
    });
}

</script>
@endpush

@endsection

