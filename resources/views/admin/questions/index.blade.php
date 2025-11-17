@extends('layouts.dashboard_layout')

@section('title', 'Default Questions')

@push('styles')
<!-- DataTables CSS -->
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />

<style>
  .question-text {
    max-width: 600px;
    word-wrap: break-word;
  }
</style>
@endpush

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold py-3 mb-0">
      <span class="text-muted fw-light">Admin /</span> Default Questions
    </h4>
    <button type="button" class="btn btn-primary" data-bs-toggle="offcanvas" data-bs-target="#questionOffcanvas" onclick="openQuestionForm()">
      <i class="ti ti-plus me-2"></i>
      Add Question
    </button>
  </div>

  <!-- Success/Error Alerts -->
  <div id="alertContainer"></div>

  <!-- Questions Table -->
  <div class="card">
    <div class="card-datatable table-responsive">
      <table class="datatables-questions table">
        <thead>
          <tr>
            <th width="5%">ID</th>
            <th width="80%">Question</th>
            <th width="10%">Status</th>
            <th width="5%">Actions</th>
          </tr>
        </thead>
      </table>
    </div>
  </div>
</div>

<!-- Offcanvas Form -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="questionOffcanvas" aria-labelledby="questionOffcanvasLabel">
  <div class="offcanvas-header">
    <h5 id="questionOffcanvasLabel" class="offcanvas-title">Add Question</h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body">
    <form id="questionForm">
      <input type="hidden" id="question_id" name="question_id">
      
      <div class="mb-3">
        <label for="question" class="form-label">Question <span class="text-danger">*</span></label>
        <textarea 
          class="form-control @error('question') is-invalid @enderror" 
          id="question" 
          name="question" 
          rows="4" 
          placeholder="Enter your question here..."></textarea>
        <small class="text-muted">Minimum 10 characters, maximum 500 characters</small>
        <div class="invalid-feedback" id="question_error"></div>
      </div>

      <div class="mb-3">
        <label class="form-label">Status <span class="text-danger">*</span></label>
        <div class="form-check form-switch">
          <input 
            class="form-check-input" 
            type="checkbox" 
            id="status" 
            name="status"
            checked>
          <label class="form-check-label" for="status">
            <span id="statusLabel">Active</span>
          </label>
        </div>
        <small class="text-muted">Toggle to set question as active or inactive</small>
        <div class="invalid-feedback" id="status_error"></div>
      </div>

      <div class="d-flex justify-content-end gap-2">
        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="offcanvas">
          Cancel
        </button>
        <button type="submit" class="btn btn-primary" id="submitBtn">
          <i class="ti ti-device-floppy me-2"></i>
          Save Question
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="deleteModalLabel">
          <i class="ti ti-alert-triangle text-danger me-2"></i>
          Delete Question
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p class="mb-0">Are you sure you want to delete this question? This action cannot be undone.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
          Cancel
        </button>
        <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
          <i class="ti ti-trash me-2"></i>
          Delete
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
  let currentQuestionId = null;
  let questionsTable;
  const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
  const questionOffcanvas = new bootstrap.Offcanvas(document.getElementById('questionOffcanvas'));
  
  // Initialize DataTable
  $(document).ready(function() {
    // Get questions data from server
    const questionsData = @json($questions);
    
    questionsTable = $('.datatables-questions').DataTable({
      data: questionsData,
      columns: [
        { data: 'id', name: 'id' },
        { 
          data: 'question', 
          name: 'question',
          render: function(data, type, row) {
            return '<div class="question-text">' + data + '</div>';
          }
        },
        { 
          data: 'status', 
          name: 'status',
          render: function(data, type, row) {
            const badgeClass = data === 'active' ? 'success' : 'secondary';
            return '<span class="badge bg-' + badgeClass + '">' + data.charAt(0).toUpperCase() + data.slice(1) + '</span>';
          }
        },
        { 
          data: null,
          name: 'actions',
          orderable: false,
          searchable: false,
          render: function(data, type, row) {
            return `
              <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-label-primary edit-question-btn" 
                  data-id="${row.id}" 
                  data-question="${row.question.replace(/"/g, '&quot;')}" 
                  data-status="${row.status}">
                  <i class="ti ti-edit me-1"></i>
                </button>
                <button type="button" class="btn btn-sm btn-label-danger delete-question-btn" data-id="${row.id}">
                  <i class="ti ti-trash me-1"></i>
                </button>
              </div>
            `;
          }
        }
      ],
      order: [[0, 'desc']], // Order by ID descending
      pageLength: 10,
      lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
      dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-center justify-content-md-end"f>>' +
           '<"row"<"col-sm-12"t>>' +
           '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
      language: {
        search: '',
        searchPlaceholder: 'Search questions...',
        lengthMenu: '_MENU_',
        paginate: {
          previous: '<i class="ti ti-chevron-left"></i>',
          next: '<i class="ti ti-chevron-right"></i>'
        }
      },
      responsive: true
    });
    
    // Event delegation for edit and delete buttons
    $(document).on('click', '.edit-question-btn', function() {
      const id = $(this).data('id');
      const question = $(this).data('question');
      const status = $(this).data('status');
      editQuestion(id, question, status);
    });
    
    $(document).on('click', '.delete-question-btn', function() {
      const id = $(this).data('id');
      deleteQuestion(id);
    });
  });

  // Status toggle handler
  document.getElementById('status').addEventListener('change', function() {
    document.getElementById('statusLabel').textContent = this.checked ? 'Active' : 'Inactive';
  });

  // Open form for new question
  function openQuestionForm() {
    currentQuestionId = null;
    document.getElementById('questionOffcanvasLabel').textContent = 'Add Question';
    document.getElementById('questionForm').reset();
    document.getElementById('question_id').value = '';
    document.getElementById('status').checked = true;
    document.getElementById('statusLabel').textContent = 'Active';
    document.getElementById('submitBtn').innerHTML = '<i class="ti ti-device-floppy me-2"></i>Save Question';
    
    // Clear validation errors
    document.getElementById('question').classList.remove('is-invalid');
    document.getElementById('status').classList.remove('is-invalid');
    document.getElementById('question_error').textContent = '';
    document.getElementById('status_error').textContent = '';
  }

  // Edit question
  function editQuestion(id, question, status) {
    currentQuestionId = id;
    document.getElementById('questionOffcanvasLabel').textContent = 'Edit Question';
    document.getElementById('question_id').value = id;
    // Decode HTML entities
    const textarea = document.createElement('textarea');
    textarea.innerHTML = question;
    document.getElementById('question').value = textarea.value;
    document.getElementById('status').checked = status === 'active';
    document.getElementById('statusLabel').textContent = status === 'active' ? 'Active' : 'Inactive';
    document.getElementById('submitBtn').innerHTML = '<i class="ti ti-device-floppy me-2"></i>Update Question';
    
    // Clear validation errors
    document.getElementById('question').classList.remove('is-invalid');
    document.getElementById('status').classList.remove('is-invalid');
    document.getElementById('question_error').textContent = '';
    document.getElementById('status_error').textContent = '';
    
    questionOffcanvas.show();
  }

  // Delete question
  function deleteQuestion(id) {
    currentQuestionId = id;
    deleteModal.show();
  }

  // Confirm delete
  document.getElementById('confirmDeleteBtn').addEventListener('click', async function() {
    if (!currentQuestionId) return;

    const btn = this;
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Deleting...';

    try {
      const response = await fetch(`/admin/questions/${currentQuestionId}`, {
        method: 'DELETE',
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || document.querySelector('input[name="_token"]').value,
          'Content-Type': 'application/json',
        }
      });

      const data = await response.json();

      if (data.success) {
        // Remove row from DataTable
        if (typeof questionsTable !== 'undefined') {
          questionsTable.rows().every(function() {
            const rowData = this.data();
            if (rowData.id === currentQuestionId) {
              this.remove();
              return false; // Stop iteration
            }
          });
          questionsTable.draw();
        }
        
        showAlert('success', data.message || 'Question deleted successfully');
        deleteModal.hide();
      } else {
        showAlert('danger', data.message || 'Failed to delete question');
      }
    } catch (error) {
      showAlert('danger', 'An error occurred. Please try again.');
    } finally {
      btn.disabled = false;
      btn.innerHTML = originalText;
      currentQuestionId = null;
    }
  });

  // Form submission
  document.getElementById('questionForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const questionId = formData.get('question_id');
    const question = formData.get('question');
    const status = document.getElementById('status').checked ? 'active' : 'inactive';

    // Clear previous validation errors
    document.getElementById('question').classList.remove('is-invalid');
    document.getElementById('status').classList.remove('is-invalid');
    document.getElementById('question_error').textContent = '';
    document.getElementById('status_error').textContent = '';

    const submitBtn = document.getElementById('submitBtn');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';

    try {
      const url = questionId ? `/admin/questions/${questionId}` : '/admin/questions';
      const method = questionId ? 'PUT' : 'POST';

      const response = await fetch(url, {
        method: method,
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || document.querySelector('input[name="_token"]').value,
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          question: question,
          status: status
        })
      });

      const data = await response.json();

      if (data.success) {
        showAlert('success', data.message || (questionId ? 'Question updated successfully' : 'Question created successfully'));
        
        // Reload DataTable after 1 second
        setTimeout(() => {
          window.location.reload();
        }, 1000);
      } else {
        // Handle validation errors
        if (data.errors) {
          Object.keys(data.errors).forEach(field => {
            const input = document.getElementById(field);
            const errorDiv = document.getElementById(field + '_error');
            
            if (input) {
              input.classList.add('is-invalid');
            }
            
            if (errorDiv) {
              errorDiv.textContent = Array.isArray(data.errors[field]) ? data.errors[field][0] : data.errors[field];
            }
          });
        } else {
          showAlert('danger', data.message || 'Failed to save question');
        }
      }
    } catch (error) {
      showAlert('danger', 'An error occurred. Please try again.');
    } finally {
      submitBtn.disabled = false;
      submitBtn.innerHTML = originalText;
    }
  });

  // Show alert
  function showAlert(type, message) {
    const alertHtml = `
      <div class="alert alert-${type} alert-dismissible fade show" role="alert">
        <i class="ti ti-${type === 'success' ? 'check-circle' : 'alert-circle'} me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    `;
    
    const container = document.getElementById('alertContainer');
    container.innerHTML = alertHtml;
    
    // Auto dismiss after 5 seconds
    setTimeout(() => {
      const alert = container.querySelector('.alert');
      if (alert) {
        const bsAlert = new bootstrap.Alert(alert);
        bsAlert.close();
      }
    }, 5000);
  }

  // Clear form when offcanvas is hidden
  document.getElementById('questionOffcanvas').addEventListener('hidden.bs.offcanvas', function() {
    document.getElementById('questionForm').reset();
    document.getElementById('question_id').value = '';
    currentQuestionId = null;
    
    // Clear validation errors
    document.getElementById('question').classList.remove('is-invalid');
    document.getElementById('status').classList.remove('is-invalid');
    document.getElementById('question_error').textContent = '';
    document.getElementById('status_error').textContent = '';
  });
</script>
@endpush

