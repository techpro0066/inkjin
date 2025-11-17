@extends('layouts.dashboard_layout')

@section('title', 'Users Management')

@push('styles')
<!-- DataTables CSS -->
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />

<style>
  .avatar-initial {
    width: 38px;
    height: 38px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.875rem;
  }
  .user-detail-label {
    font-weight: 600;
    color: #697a8d;
    min-width: 150px;
  }
  .user-detail-value {
    color: #566a7f;
  }
  #userDetailsContent .avatar-initial {
    display: inline-flex !important;
    align-items: center;
    justify-content: center;
  }
</style>
@endpush

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold py-3 mb-0">
      <span class="text-muted fw-light">Admin /</span> Users Management
    </h4>
  </div>

  <!-- Success/Error Alerts -->
  <div id="alertContainer"></div>

  <!-- Users Table -->
  <div class="card">
    <div class="card-header">
      <div class="row align-items-center">
        <div class="col-md-6">
          <h5 class="card-title mb-0">Users List</h5>
        </div>
        <div class="col-md-6 text-end">
          <select id="roleFilter" class="form-select form-select-sm" style="max-width: 200px; display: inline-block;">
            <option value="all" {{ $roleFilter === 'all' ? 'selected' : '' }}>All Users</option>
            <option value="user" {{ $roleFilter === 'user' ? 'selected' : '' }}>Users</option>
            <option value="artist" {{ $roleFilter === 'artist' ? 'selected' : '' }}>Artists</option>
          </select>
        </div>
      </div>
    </div>
    <div class="card-datatable table-responsive">
      <table class="datatables-users table">
        <thead>
          <tr>
            <th>User</th>
            <th>Email</th>
            <th>Role</th>
            <th>Email Verified</th>
            <th>Onboarding</th>
            <th>Actions</th>
          </tr>
        </thead>
      </table>
    </div>
  </div>
</div>

<!-- User Details Modal -->
<div class="modal fade" id="userDetailsModal" tabindex="-1" aria-labelledby="userDetailsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="userDetailsModalLabel">
          <i class="ti ti-user me-2"></i>
          User Details
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="userDetailsContent">
        <div class="text-center py-4">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
          Close
        </button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<!-- DataTables JS -->
<script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>

<script>
  let usersTable;
  const userDetailsModal = new bootstrap.Modal(document.getElementById('userDetailsModal'));
  
  // Initialize DataTable
  $(document).ready(function() {
    // Get users data from server
    const usersData = @json($users);
    
    usersTable = $('.datatables-users').DataTable({
      data: usersData,
      columns: [
        { 
          data: null,
          name: 'user',
          render: function(data, type, row) {
            const name = row.name || 'N/A';
            const email = row.email || '';
            const initials = name.match(/\b\w/g) || [];
            const initialsText = (initials.shift() || '') + (initials.pop() || '');
            const avatarBg = ['success', 'danger', 'warning', 'info', 'primary', 'secondary'][row.id % 6];
            
            let avatar = '';
            if (row.user_detail && row.user_detail.avatar) {
              const avatarPath = row.user_detail.avatar.startsWith('/') ? row.user_detail.avatar.substring(1) : row.user_detail.avatar;
              avatar = `<img src="{{ url('/') }}/${avatarPath}" alt="Avatar" class="rounded-circle" style="width: 38px; height: 38px; object-fit: cover;">`;
            } else {
              avatar = `<span class="avatar-initial rounded-circle bg-label-${avatarBg}">${initialsText.toUpperCase()}</span>`;
            }
            
            return `
              <div class="d-flex justify-content-start align-items-center">
                <div class="avatar-wrapper me-3">
                  <div class="avatar">${avatar}</div>
                </div>
                <div class="d-flex flex-column">
                  <span class="fw-medium text-body">${name}</span>
                  <small class="text-muted">${email}</small>
                </div>
              </div>
            `;
          }
        },
        { 
          data: 'email', 
          name: 'email',
          visible: false // Hide as it's shown in user column
        },
        { 
          data: 'role', 
          name: 'role',
          render: function(data, type, row) {
            const roleColors = {
              'admin': 'danger',
              'artist': 'primary',
              'user': 'secondary'
            };
            const color = roleColors[data] || 'secondary';
            return `<span class="badge bg-${color}">${data.charAt(0).toUpperCase() + data.slice(1)}</span>`;
          }
        },
        { 
          data: 'email_verified_at', 
          name: 'email_verified_at',
          render: function(data, type, row) {
            if (data) {
              return '<span class="badge bg-success"><i class="ti ti-check me-1"></i>Verified</span>';
            } else {
              return '<span class="badge bg-warning"><i class="ti ti-x me-1"></i>Unverified</span>';
            }
          }
        },
        { 
          data: 'on_boarding', 
          name: 'on_boarding',
          render: function(data, type, row) {
            if (data === 'yes') {
              return '<span class="badge bg-success"><i class="ti ti-check me-1"></i>Completed</span>';
            } else {
              return '<span class="badge bg-secondary"><i class="ti ti-clock me-1"></i>Pending</span>';
            }
          }
        },
        { 
          data: null,
          name: 'actions',
          orderable: false,
          searchable: false,
          render: function(data, type, row) {
            return `
              <button type="button" class="btn btn-sm btn-label-primary view-user-btn" data-id="${row.id}">
                <i class="ti ti-eye me-1"></i>
                View Details
              </button>
            `;
          }
        }
      ],
      order: [[0, 'asc']], // Order by name
      pageLength: 10,
      lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
      dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-center justify-content-md-end"f>>' +
           '<"row"<"col-sm-12"t>>' +
           '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
      language: {
        search: '',
        searchPlaceholder: 'Search users...',
        lengthMenu: '_MENU_',
        paginate: {
          previous: '<i class="ti ti-chevron-left"></i>',
          next: '<i class="ti ti-chevron-right"></i>'
        }
      },
      responsive: true
    });
    
    // Event delegation for view details button
    $(document).on('click', '.view-user-btn', function() {
      const userId = $(this).data('id');
      viewUserDetails(userId);
    });
    
    // Role filter change handler
    $('#roleFilter').on('change', function() {
      const role = $(this).val();
      window.location.href = '{{ route("admin.users.index") }}?role=' + role;
    });
  });
  
  // View user details
  function viewUserDetails(userId) {
    const modalContent = document.getElementById('userDetailsContent');
    modalContent.innerHTML = `
      <div class="text-center py-4">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
      </div>
    `;
    
    userDetailsModal.show();
    
    fetch(`/admin/users/${userId}`, {
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || document.querySelector('input[name="_token"]').value,
        'Accept': 'application/json',
      }
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        displayUserDetails(data.user, data.userDetail, data.availabilities);
      } else {
        modalContent.innerHTML = '<div class="alert alert-danger">Failed to load user details.</div>';
      }
    })
    .catch(error => {
      console.error('Error:', error);
      modalContent.innerHTML = '<div class="alert alert-danger">An error occurred while loading user details.</div>';
    });
  }
  
    // Display user details in modal
  function displayUserDetails(user, userDetail, availabilities) {
    const modalContent = document.getElementById('userDetailsContent');
    
    // Handle null userDetail
    if (!userDetail) {
      userDetail = {};
    }
    if (!availabilities) {
      availabilities = [];
    }
    
    const formatDate = (date) => {
      if (!date) return 'N/A';
      try {
        return new Date(date).toLocaleString();
      } catch (e) {
        return date;
      }
    };
    
    const formatCurrency = (amount, currency) => {
      if (!amount) return 'N/A';
      const currencySymbols = {
        'USD': '$', 'EUR': '€', 'GBP': '£', 'INR': '₹'
      };
      const symbol = currencySymbols[currency] || currency || '$';
      return `${symbol}${parseFloat(amount).toFixed(2)}`;
    };
    
    // Generate avatar
    const initials = (user.name || 'N/A').match(/\b\w/g) || [];
    const initialsText = (initials.shift() || '') + (initials.pop() || '');
    const avatarBg = ['success', 'danger', 'warning', 'info', 'primary', 'secondary'][user.id % 6];
    let avatarHtml = '';
    if (userDetail && userDetail.avatar) {
      const avatarPath = userDetail.avatar.startsWith('/') ? userDetail.avatar.substring(1) : userDetail.avatar;
      avatarHtml = `<img src="{{ url('/') }}/${avatarPath}" alt="Avatar" class="rounded-circle mx-auto d-block" style="width: 80px; height: 80px; object-fit: cover;">`;
    } else {
      avatarHtml = `<span class="avatar-initial rounded-circle bg-label-${avatarBg} mx-auto d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px; font-size: 2rem;">${initialsText.toUpperCase()}</span>`;
    }
    
    let html = `
      <div class="row">
        <!-- User Header -->
        <div class="col-12 mb-4 text-center">
          <div class="mb-3 d-flex justify-content-center">${avatarHtml}</div>
          <h5 class="mb-1">${user.name || 'N/A'}</h5>
          <p class="text-muted mb-0">${user.email || 'N/A'}</p>
        </div>
        
        <!-- Basic Information -->
        <div class="col-12 mb-4">
          <h6 class="mb-3 text-primary"><i class="ti ti-user me-2"></i>Basic Information</h6>
          <div class="table-responsive">
            <table class="table table-borderless">
              <tbody>
                <tr>
                  <td class="user-detail-label">Name:</td>
                  <td class="user-detail-value">${user.name || 'N/A'}</td>
                </tr>
                <tr>
                  <td class="user-detail-label">Email:</td>
                  <td class="user-detail-value">${user.email || 'N/A'}</td>
                </tr>
                <tr>
                  <td class="user-detail-label">Role:</td>
                  <td class="user-detail-value">
                    <span class="badge bg-${user.role === 'admin' ? 'danger' : user.role === 'artist' ? 'primary' : 'secondary'}">
                      ${user.role ? user.role.charAt(0).toUpperCase() + user.role.slice(1) : 'N/A'}
                    </span>
                  </td>
                </tr>
                <tr>
                  <td class="user-detail-label">Email Verified:</td>
                  <td class="user-detail-value">
                    ${user.email_verified_at ? 
                      '<span class="badge bg-success">Verified</span>' : 
                      '<span class="badge bg-warning">Unverified</span>'}
                  </td>
                </tr>
                <tr>
                  <td class="user-detail-label">Onboarding Status:</td>
                  <td class="user-detail-value">
                    ${user.on_boarding === 'yes' ? 
                      '<span class="badge bg-success">Completed</span>' : 
                      '<span class="badge bg-secondary">Pending</span>'}
                  </td>
                </tr>
                <tr>
                  <td class="user-detail-label">Registered:</td>
                  <td class="user-detail-value">${formatDate(user.created_at)}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
    `;
    
    // Studio Information and Preferences (only for artists)
    if (user.role === 'artist') {
      html += `
        <!-- Studio Information -->
        <div class="col-12 mb-4">
          <h6 class="mb-3 text-primary"><i class="ti ti-building me-2"></i>Studio Information</h6>
          <div class="table-responsive">
            <table class="table table-borderless">
              <tbody>
                <tr>
                  <td class="user-detail-label">Studio Name:</td>
                  <td class="user-detail-value">${userDetail && userDetail.studio_name ? userDetail.studio_name : 'N/A'}</td>
                </tr>
                <tr>
                  <td class="user-detail-label">Studio Address:</td>
                  <td class="user-detail-value">${userDetail && userDetail.studio_address ? userDetail.studio_address : 'N/A'}</td>
                </tr>
                <tr>
                  <td class="user-detail-label">Google Maps Link:</td>
                  <td class="user-detail-value">
                    ${userDetail && userDetail.google_maps_link ? 
                      `<a href="${userDetail.google_maps_link}" target="_blank">${userDetail.google_maps_link}</a>` : 
                      'N/A'}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
        
        <!-- Preferences -->
        <div class="col-12 mb-4">
          <h6 class="mb-3 text-primary"><i class="ti ti-adjustments me-2"></i>Preferences</h6>
          <div class="table-responsive">
            <table class="table table-borderless">
              <tbody>
                <tr>
                  <td class="user-detail-label">Currency:</td>
                  <td class="user-detail-value">${userDetail && userDetail.currency ? userDetail.currency : 'N/A'}</td>
                </tr>
                <tr>
                  <td class="user-detail-label">Timezone:</td>
                  <td class="user-detail-value">${userDetail && userDetail.timezone ? userDetail.timezone : 'N/A'}</td>
                </tr>
                <tr>
                  <td class="user-detail-label">Date Format:</td>
                  <td class="user-detail-value">${userDetail && userDetail.date_time_format ? userDetail.date_time_format : 'N/A'}</td>
                </tr>
                <tr>
                  <td class="user-detail-label">Minimum Deposit:</td>
                  <td class="user-detail-value">
                    ${userDetail && userDetail.minimum_deposit_amount ? 
                      `${formatCurrency(userDetail.minimum_deposit_amount, userDetail.currency || 'USD')} (${userDetail.minimum_deposit_type || 'N/A'})` : 
                      'N/A'}
                  </td>
                </tr>
                <tr>
                  <td class="user-detail-label">Cancellation Window:</td>
                  <td class="user-detail-value">${userDetail && userDetail.cancellation_window ? userDetail.cancellation_window : 'N/A'}</td>
                </tr>
                <tr>
                  <td class="user-detail-label">Reschedule Times:</td>
                  <td class="user-detail-value">${userDetail && userDetail.reschedule_times ? userDetail.reschedule_times.charAt(0).toUpperCase() + userDetail.reschedule_times.slice(1) : 'N/A'}</td>
                </tr>
                <tr>
                  <td class="user-detail-label">Google Calendar:</td>
                  <td class="user-detail-value">
                    ${userDetail && userDetail.google_calendar_token ? 
                      '<span class="badge bg-success">Connected</span>' : 
                      '<span class="badge bg-secondary">Not Connected</span>'}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      `;
    }
    
    // Availability (for artists)
    if (user.role === 'artist') {
      if (availabilities && availabilities.length > 0) {
        html += `
          <!-- Availability -->
          <div class="col-12 mb-4">
            <h6 class="mb-3 text-primary"><i class="ti ti-calendar me-2"></i>Availability</h6>
            <div class="table-responsive">
              <table class="table table-sm">
                <thead>
                  <tr>
                    <th>Day</th>
                    <th>Start Time</th>
                    <th>End Time</th>
                  </tr>
                </thead>
                <tbody>
        `;
        
        const days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        const dayNames = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        
        days.forEach((day, index) => {
          const dayAvailabilities = availabilities.filter(a => a && a.day_of_week === day);
          if (dayAvailabilities.length > 0) {
            dayAvailabilities.forEach(avail => {
              html += `
                <tr>
                  <td>${dayNames[index]}</td>
                  <td>${avail.start_time || 'N/A'}</td>
                  <td>${avail.end_time || 'N/A'}</td>
                </tr>
              `;
            });
          }
        });
        
        html += `
                </tbody>
              </table>
            </div>
          </div>
        `;
      } else {
        html += `
          <!-- Availability -->
          <div class="col-12 mb-4">
            <h6 class="mb-3 text-primary"><i class="ti ti-calendar me-2"></i>Availability</h6>
            <div class="alert alert-info">
              <i class="ti ti-info-circle me-2"></i>
              No availability schedule set.
            </div>
          </div>
        `;
      }
    }
    
    html += `</div>`;
    
    modalContent.innerHTML = html;
  }
</script>
@endpush

