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
              <option value="rescheduled" {{ request('status') === 'rescheduled' ? 'selected' : '' }}>Rescheduled</option>
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
                  <span class="badge {{ $statusColors[$booking->status] ?? 'bg-label-secondary' }}">
                    {{ ucfirst($booking->status) }}
                  </span>
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
                  <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-label-info" data-bs-toggle="modal" data-bs-target="#bookingModal{{ $booking->id }}">
                      <i class="ti ti-eye"></i>
                    </button>
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
@endsection

