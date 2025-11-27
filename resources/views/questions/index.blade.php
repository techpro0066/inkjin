@extends('layouts.dashboard_layout')

@section('title', 'My Questions')

@push('styles')
<!-- DataTables CSS -->
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />

<style>
  .question-text {
    max-width: 600px;
    word-wrap: break-word;
  }
  .option-item {
    position: relative;
  }
  .option-item .form-control {
    flex: 1;
  }
  .option-item .remove-option-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }
  #optionsContainer {
    border: 1px solid #e0e0e0;
    border-radius: 0.375rem;
    padding: 1rem;
    background-color: #f8f9fa;
  }
  #optionsList {
    min-height: 50px;
  }
</style>
@endpush

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold py-3 mb-0">
      <span class="text-muted fw-light">Questions /</span> My Questions
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
            <th width="60%">Question</th>
            <th width="15%">Type</th>
            <th width="10%">Status</th>
            <th width="10%">Actions</th>
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
        <label for="type" class="form-label">Question Type <span class="text-danger">*</span></label>
        <select class="form-select @error('type') is-invalid @enderror" id="type" name="type">
          <option value="free">Free Text</option>
          <option value="select">Select (Dropdown)</option>
          <option value="radio">Radio Buttons</option>
          <option value="image">Image Upload</option>
        </select>
        <small class="text-muted">Choose how users will answer this question</small>
        <div class="invalid-feedback" id="type_error"></div>
      </div>

      <div class="mb-3" id="optionsContainer" style="display: none;">
        <label class="form-label">Options <span class="text-danger">*</span></label>
        <small class="text-muted d-block mb-2">Add at least 2 options for select/radio questions</small>
        <div id="optionsList"></div>
        <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="addOptionBtn">
          <i class="ti ti-plus me-1"></i>Add Option
        </button>
        <div class="invalid-feedback" id="options_error"></div>
      </div>

      <div class="mb-3" id="maxImagesContainer" style="display: none;">
        <label for="max_images" class="form-label">Maximum Images Allowed <span class="text-danger">*</span></label>
        <input 
          type="number" 
          class="form-control @error('max_images') is-invalid @enderror" 
          id="max_images" 
          name="max_images" 
          min="1" 
          max="20" 
          value="1"
          placeholder="Enter maximum number of images">
        <small class="text-muted">Specify how many images users can upload (1-20)</small>
        <div class="invalid-feedback" id="max_images_error"></div>
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
          data: 'type', 
          name: 'type',
          render: function(data, type, row) {
            const typeLabels = {
              'free': 'Free Text',
              'select': 'Select',
              'radio': 'Radio',
              'image': 'Image'
            };
            const typeColors = {
              'free': 'info',
              'select': 'primary',
              'radio': 'warning',
              'image': 'success'
            };
            const label = typeLabels[data] || data;
            const color = typeColors[data] || 'secondary';
            return '<span class="badge bg-' + color + '">' + label + '</span>';
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
            const questionEscaped = (row.question || '').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
            const optionsJson = row.options ? JSON.stringify(row.options) : '[]';
            const maxImages = row.max_images || '';
            return `
              <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-label-primary edit-question-btn" 
                  data-id="${row.id}" 
                  data-question="${questionEscaped}" 
                  data-type="${row.type || 'free'}"
                  data-options='${optionsJson}'
                  data-max-images="${maxImages}"
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
      const type = $(this).data('type') || 'free';
      const options = $(this).data('options') || [];
      const maxImages = $(this).data('max-images') || '';
      const status = $(this).data('status');
      editQuestion(id, question, type, options, maxImages, status);
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

  // Type change handler - show/hide options container and max images container
  document.getElementById('type').addEventListener('change', function() {
    const type = this.value;
    const optionsContainer = document.getElementById('optionsContainer');
    const maxImagesContainer = document.getElementById('maxImagesContainer');
    
    if (type === 'select' || type === 'radio') {
      optionsContainer.style.display = 'block';
      maxImagesContainer.style.display = 'none';
      // Initialize with 2 empty options if none exist
      if (document.querySelectorAll('.option-item').length === 0) {
        addOption();
        addOption();
      }
    } else if (type === 'image') {
      optionsContainer.style.display = 'none';
      maxImagesContainer.style.display = 'block';
      // Clear options when switching to image type
      document.getElementById('optionsList').innerHTML = '';
      // Set default max_images to 1 if not set
      const maxImagesInput = document.getElementById('max_images');
      if (maxImagesInput && !maxImagesInput.value) {
        maxImagesInput.value = 1;
      }
    } else {
      optionsContainer.style.display = 'none';
      maxImagesContainer.style.display = 'none';
      // Clear options when switching to free type
      document.getElementById('optionsList').innerHTML = '';
    }
  });

  // Add option
  function addOption(value = '') {
    const optionsList = document.getElementById('optionsList');
    const optionIndex = optionsList.querySelectorAll('.option-item').length;
    const optionHtml = `
      <div class="option-item mb-2 d-flex gap-2 align-items-start">
        <input 
          type="text" 
          class="form-control option-input" 
          placeholder="Enter option text..."
          value="${value.replace(/"/g, '&quot;')}"
          maxlength="255">
        <button type="button" class="btn btn-sm btn-label-danger remove-option-btn" ${optionIndex < 2 ? 'disabled' : ''}>
          <i class="ti ti-trash"></i>
        </button>
      </div>
    `;
    optionsList.insertAdjacentHTML('beforeend', optionHtml);
    
    // Update remove button states
    updateRemoveButtonStates();
  }

  // Remove option
  $(document).on('click', '.remove-option-btn', function() {
    $(this).closest('.option-item').remove();
    updateRemoveButtonStates();
  });

  // Update remove button states (disable if only 2 options remain)
  function updateRemoveButtonStates() {
    const optionItems = document.querySelectorAll('.option-item');
    optionItems.forEach((item, index) => {
      const removeBtn = item.querySelector('.remove-option-btn');
      if (removeBtn) {
        removeBtn.disabled = optionItems.length <= 2;
      }
    });
  }

  // Add option button handler
  document.getElementById('addOptionBtn').addEventListener('click', function() {
    addOption();
  });

  // Open form for new question
  function openQuestionForm() {
    currentQuestionId = null;
    document.getElementById('questionOffcanvasLabel').textContent = 'Add Question';
    document.getElementById('questionForm').reset();
    document.getElementById('question_id').value = '';
    document.getElementById('type').value = 'free';
    document.getElementById('status').checked = true;
    document.getElementById('statusLabel').textContent = 'Active';
    document.getElementById('submitBtn').innerHTML = '<i class="ti ti-device-floppy me-2"></i>Save Question';
    
    // Hide options container and max images container
    document.getElementById('optionsContainer').style.display = 'none';
    document.getElementById('optionsList').innerHTML = '';
    document.getElementById('maxImagesContainer').style.display = 'none';
    
    // Clear validation errors
    document.getElementById('question').classList.remove('is-invalid');
    document.getElementById('type').classList.remove('is-invalid');
    const optionsContainer = document.getElementById('optionsContainer');
    if (optionsContainer) optionsContainer.classList.remove('is-invalid');
    document.getElementById('max_images').classList.remove('is-invalid');
    document.getElementById('status').classList.remove('is-invalid');
    document.getElementById('question_error').textContent = '';
    document.getElementById('type_error').textContent = '';
    document.getElementById('options_error').textContent = '';
    document.getElementById('max_images_error').textContent = '';
    document.getElementById('status_error').textContent = '';
  }

  // Edit question
  function editQuestion(id, question, type, options, maxImages, status) {
    currentQuestionId = id;
    
    // Show offcanvas first
    questionOffcanvas.show();
    
    // Wait for offcanvas to be fully shown before accessing elements
    const offcanvasElement = document.getElementById('questionOffcanvas');
    const handleShown = () => {
      // Set form values
      const questionLabel = document.getElementById('questionOffcanvasLabel');
      const questionIdInput = document.getElementById('question_id');
      const questionTextarea = document.getElementById('question');
      const typeSelect = document.getElementById('type');
      const statusCheckbox = document.getElementById('status');
      const statusLabel = document.getElementById('statusLabel');
      const submitBtn = document.getElementById('submitBtn');
      const optionsContainer = document.getElementById('optionsContainer');
      const optionsList = document.getElementById('optionsList');
      const maxImagesContainer = document.getElementById('maxImagesContainer');
      const maxImagesInput = document.getElementById('max_images');
      
      if (questionLabel) questionLabel.textContent = 'Edit Question';
      if (questionIdInput) questionIdInput.value = id;
      
    // Decode HTML entities
      if (questionTextarea) {
    const textarea = document.createElement('textarea');
    textarea.innerHTML = question;
        questionTextarea.value = textarea.value;
      }
      
      // Set type and trigger change event
      if (typeSelect) {
        typeSelect.value = type || 'free';
        // Manually trigger change to show/hide options container and max images container
        typeSelect.dispatchEvent(new Event('change'));
      }
      
      // Handle options
      if (type === 'select' || type === 'radio') {
        if (optionsList) {
          // Clear existing options first
          optionsList.innerHTML = '';
          if (options && Array.isArray(options) && options.length > 0) {
            options.forEach(option => {
              addOption(option);
            });
          } else {
            // Initialize with 2 empty options if none exist
            addOption();
            addOption();
          }
        }
      }
      
      // Handle max_images for image type
      if (type === 'image' && maxImagesInput) {
        maxImagesInput.value = maxImages || 1;
      }
      
      if (statusCheckbox) statusCheckbox.checked = status === 'active';
      if (statusLabel) statusLabel.textContent = status === 'active' ? 'Active' : 'Inactive';
      if (submitBtn) submitBtn.innerHTML = '<i class="ti ti-device-floppy me-2"></i>Update Question';
    
    // Clear validation errors
      if (questionTextarea) questionTextarea.classList.remove('is-invalid');
      if (typeSelect) typeSelect.classList.remove('is-invalid');
      if (optionsContainer) optionsContainer.classList.remove('is-invalid');
      if (maxImagesInput) maxImagesInput.classList.remove('is-invalid');
      if (statusCheckbox) statusCheckbox.classList.remove('is-invalid');
      
      const questionError = document.getElementById('question_error');
      const typeError = document.getElementById('type_error');
      const optionsError = document.getElementById('options_error');
      const maxImagesError = document.getElementById('max_images_error');
      const statusError = document.getElementById('status_error');
      
      if (questionError) questionError.textContent = '';
      if (typeError) typeError.textContent = '';
      if (optionsError) optionsError.textContent = '';
      if (maxImagesError) maxImagesError.textContent = '';
      if (statusError) statusError.textContent = '';
      
      // Remove event listener after first use
      offcanvasElement.removeEventListener('shown.bs.offcanvas', handleShown);
    };
    
    // Add event listener for when offcanvas is fully shown
    offcanvasElement.addEventListener('shown.bs.offcanvas', handleShown);
    
    // If offcanvas is already shown, call handler immediately
    if (offcanvasElement.classList.contains('show')) {
      handleShown();
    }
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
      const response = await fetch(`/questions/${currentQuestionId}`, {
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
    const type = document.getElementById('type').value;
    const status = document.getElementById('status').checked ? 'active' : 'inactive';

    // Get options if type is select or radio
    let options = null;
    if (type === 'select' || type === 'radio') {
      const optionInputs = document.querySelectorAll('.option-input');
      options = Array.from(optionInputs)
        .map(input => input.value.trim())
        .filter(value => value !== '');
      
      // Validate options
      if (options.length < 2) {
        document.getElementById('optionsContainer').classList.add('is-invalid');
        document.getElementById('options_error').textContent = 'At least 2 options are required for select and radio types.';
        return;
      }
    }
    
    // Get max_images if type is image
    let maxImages = null;
    if (type === 'image') {
      const maxImagesInput = document.getElementById('max_images');
      maxImages = maxImagesInput ? parseInt(maxImagesInput.value) : null;
      
      // Validate max_images
      if (!maxImages || maxImages < 1 || maxImages > 20) {
        if (maxImagesInput) {
          maxImagesInput.classList.add('is-invalid');
          document.getElementById('max_images_error').textContent = 'Please enter a valid number between 1 and 20.';
        }
        return;
      }
    }

    // Clear previous validation errors
    document.getElementById('question').classList.remove('is-invalid');
    document.getElementById('type').classList.remove('is-invalid');
    document.getElementById('optionsContainer').classList.remove('is-invalid');
    document.getElementById('max_images').classList.remove('is-invalid');
    document.getElementById('status').classList.remove('is-invalid');
    document.getElementById('question_error').textContent = '';
    document.getElementById('type_error').textContent = '';
    document.getElementById('options_error').textContent = '';
    document.getElementById('max_images_error').textContent = '';
    document.getElementById('status_error').textContent = '';

    const submitBtn = document.getElementById('submitBtn');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';

    try {
      const url = questionId ? `/questions/${questionId}` : '/questions';
      const method = questionId ? 'PUT' : 'POST';

      const requestBody = {
        question: question,
        type: type,
        status: status
      };

      if (options !== null) {
        requestBody.options = options;
      }
      
      if (maxImages !== null) {
        requestBody.max_images = maxImages;
      }

      const response = await fetch(url, {
        method: method,
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || document.querySelector('input[name="_token"]').value,
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(requestBody)
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
            let input = document.getElementById(field);
            let errorDiv = document.getElementById(field + '_error');
            
            // Handle options field specially
            if (field === 'options') {
              input = document.getElementById('optionsContainer');
              errorDiv = document.getElementById('options_error');
            }
            
            // Handle max_images field specially
            if (field === 'max_images') {
              input = document.getElementById('max_images');
              errorDiv = document.getElementById('max_images_error');
            }
            
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
    document.getElementById('type').value = 'free';
    document.getElementById('optionsContainer').style.display = 'none';
    document.getElementById('optionsList').innerHTML = '';
    document.getElementById('maxImagesContainer').style.display = 'none';
    currentQuestionId = null;
    
    // Clear validation errors
    document.getElementById('question').classList.remove('is-invalid');
    document.getElementById('type').classList.remove('is-invalid');
    document.getElementById('optionsContainer').classList.remove('is-invalid');
    document.getElementById('max_images').classList.remove('is-invalid');
    document.getElementById('status').classList.remove('is-invalid');
    document.getElementById('question_error').textContent = '';
    document.getElementById('type_error').textContent = '';
    document.getElementById('options_error').textContent = '';
    document.getElementById('max_images_error').textContent = '';
    document.getElementById('status_error').textContent = '';
  });
</script>
@endpush

