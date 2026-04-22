@extends('layouts.artist_dashboard_layout')

@section('title', 'Forms')

@section('styles')

<style>
    /* Drag and Drop */
    [draggable] {
      cursor: grab;
    }

    [draggable]:active {
      cursor: grabbing;
    }

    .drag-over-top {
      box-shadow: 0 -2px 0 0 #310f7a inset;
    }

    .drag-over-bottom {
      box-shadow: 0 2px 0 0 #310f7a inset;
    }

    [draggable].dragging {
      opacity: 0.4;
    }

    /* Toggle switch */
    .toggle-switch {
      position: relative;
      width: 40px;
      height: 22px;
      background: #cac4d3;
      border-radius: 11px;
      cursor: pointer;
      transition: background 0.2s;
      flex-shrink: 0;
    }

    .toggle-switch.active {
      background: #310f7a;
    }

    .toggle-switch::after {
      content: '';
      position: absolute;
      top: 2px;
      left: 2px;
      width: 18px;
      height: 18px;
      background: white;
      border-radius: 50%;
      transition: transform 0.2s;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
    }

    .toggle-switch.active::after {
      transform: translateX(18px);
    }

    /* Question row */
    .q-row {
      transition: all 0.15s ease;
      border-bottom: 1px solid rgba(202, 196, 211, 0.15);
    }

    .q-row:hover {
      background: #f8f1fb;
    }

    .q-row:last-child {
      border-bottom: none;
    }

    .q-row.disabled {
      opacity: 0.45;
    }

    .sortable-ghost {
      opacity: 0.45;
      background: #f8f1fb;
    }

    .sortable-chosen {
      cursor: grabbing;
    }

    /* Form type tabs */
    .form-tab {
      padding: 10px 20px;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      border-bottom: 2px solid transparent;
      color: #494552;
      transition: all 0.2s;
      background: none;
      border-top: none;
      border-left: none;
      border-right: none;
    }

    .form-tab.active {
      color: #310f7a;
      border-bottom-color: #310f7a;
    }

    .form-tab:hover:not(.active) {
      color: #1c1b21;
    }

    /* Type badges */
    .badge {
      font-size: 10px;
      font-weight: 600;
      padding: 2px 8px;
      border-radius: 6px;
      white-space: nowrap;
    }

    .badge-select {
      background: #dbeafe;
      color: #1d4ed8;
    }

    .badge-text {
      background: #f3f4f6;
      color: #374151;
    }

    .badge-textarea {
      background: #f3f4f6;
      color: #374151;
    }

    .badge-toggle {
      background: #f3e8ff;
      color: #7c3aed;
    }

    .badge-file {
      background: #fef3c7;
      color: #92400e;
    }

    .badge-system {
      background: #e5e7eb;
      color: #6b7280;
    }

    .badge-custom {
      background: #e8ddff;
      color: #310f7a;
    }

    /* Modal */
    .modal-backdrop {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.6);
      z-index: 200;
      align-items: center;
      justify-content: center;
    }

    .modal-backdrop.open {
      display: flex;
    }

    @media (max-width: 1023px) {
      .main-content {
        overflow-x: hidden;
        padding: 16px;
        padding-top: 70px;
      }

      body {
        overflow-x: hidden;
      }
    }
</style>

@endsection

@section('content')
    <!-- Main Content -->
  <main class="main-content flex-1 min-h-screen">
    <div class="p-6 md:p-10 lg:p-12 max-w-6xl">

      <!-- Content Tabs -->
      <div class="flex items-center gap-1 mb-6 border-b border-outline-variant/20 pb-0 overflow-x-auto">
        <a href="javascript:void(0)" class="px-4 py-3 text-sm font-semibold whitespace-nowrap border-b-2 border-primary text-primary">Forms</a>
        <a href="{{route('artist-designs.index')}}" class="px-4 py-3 text-sm font-semibold whitespace-nowrap border-b-2 border-transparent text-on-surface-variant hover:text-on-surface hover:border-outline-variant transition-all">Available Designs</a>
        <a href="{{route('portfolio.index')}}" class="px-4 py-3 text-sm font-semibold whitespace-nowrap border-b-2 border-transparent text-on-surface-variant hover:text-on-surface hover:border-outline-variant transition-all">Portfolio</a>
        <a href="{{route('personal-page.index')}}" class="px-4 py-3 text-sm font-semibold whitespace-nowrap border-b-2 border-transparent text-on-surface-variant hover:text-on-surface hover:border-outline-variant transition-all">Personal Page</a>
      </div>

      <!-- Page Header -->
      <div class="mb-8">
        <h2 class="text-3xl font-extrabold text-on-surface tracking-tight">Form Builder</h2>
        <p class="text-on-surface-variant mt-1">Customize the questions clients see when booking or requesting a tattoo.
        </p>
      </div>

      <!-- Form Type Tabs -->
      <div class="flex border-b border-outline-variant/20 mb-6">
        <button class="form-tab active" onclick="switchFormType('booking', this)" id="tabBooking">Available
          Design</button>
        <button class="form-tab" onclick="switchFormType('request', this)" id="tabRequest">Custom Request</button>
      </div>

      <div class="max-w-3xl">
        <!-- Form Builder -->
        <div class="w-full">

          <!-- Unified Questions List -->
          <div class="bg-white rounded-2xl shadow-sm border border-outline-variant/20 overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-outline-variant/15 flex items-center justify-between">
              <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center">
                  <span class="material-symbols-outlined text-primary" style="font-size:18px;">reorder</span>
                </div>
                <div>
                  <h3 class="font-bold text-on-surface text-sm">Form Questions</h3>
                  <p class="text-[11px] text-on-surface-variant">Drag to reorder — system &amp; custom questions in one
                    list</p>
                </div>
              </div>
            </div>
            <div id="bookingQuestionsList" class="questions-list">
                @forelse ($default_questions as $question)
                    @php
                        $type = $question->type === 'image' ? 'images' : $question->type;
                        $options = is_array($question->options) ? $question->options : [];
                        $isSystem = in_array((int) $question->id, $system_questions_ids ?? [], true);
                        $isEnabled = (bool) ($question->is_active ?? true);
                        $typeLabel = match ($type) {
                            'input' => 'Input',
                            'textarea' => 'Textarea',
                            'select' => 'Select',
                            'toggle' => 'Toggle',
                            'radio' => 'Radio',
                            'images' => 'Images',
                            default => ucfirst((string) $type),
                        };
                        $icon = match ($type) {
                            'select', 'radio' => 'list',
                            'textarea' => 'notes',
                            'toggle' => 'toggle_on',
                            'images' => 'upload_file',
                            default => 'short_text',
                        };
                        $badgeClass = match ($type) {
                            'select', 'radio' => 'badge-select',
                            'textarea' => 'badge-textarea',
                            'toggle' => 'badge-toggle',
                            'images' => 'badge-file',
                            default => 'badge-text',
                        };
                    @endphp
                    <div
                        class="q-row flex items-center gap-3 px-5 py-3.5 {{ $isEnabled ? '' : 'disabled' }}"
                        draggable="true"
                        data-id="{{ $question->id }}"
                        data-type="{{ $typeLabel }}"
                        data-type-value="{{ $type }}"
                        data-form-context="default"
                        data-system="{{ $isSystem ? 'true' : 'false' }}"
                        data-enabled="{{ $isEnabled ? 'true' : 'false' }}"
                        data-required="{{ $question->is_required ? 'true' : 'false' }}"
                        data-available="{{ $isEnabled ? 'true' : 'false' }}"
                        data-options='@json($options)'
                    >
                        <span class="material-symbols-outlined text-outline" style="font-size:18px;">drag_indicator</span>
                        <span class="material-symbols-outlined text-on-surface-variant" style="font-size:18px;">{{ $icon }}</span>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-on-surface truncate">{{ $question->question }}</p>
                        </div>
                        <span class="badge {{ $badgeClass }}">{{ $typeLabel }}</span>
                        <span class="badge {{ $isSystem ? 'badge-system' : 'badge-custom' }}">{{ $isSystem ? 'SYSTEM' : 'CUSTOM' }}</span>
                        @if($isSystem)
                            <div class="toggle-switch {{ $isEnabled ? 'active' : '' }}" onclick="toggleSystemQuestion(this)" title="Enable/Disable"></div>
                            <span class="material-symbols-outlined text-outline/40" style="font-size:16px;" title="System question">lock</span>
                        @else
                            <div class="flex items-center gap-1">
                                <button type="button" class="js-edit-question w-7 h-7 rounded-lg flex items-center justify-center hover:bg-surface-container-low" title="Edit">
                                    <span class="material-symbols-outlined text-on-surface-variant" style="font-size:16px;">edit</span>
                                </button>
                                <button type="button" class="js-remove-question w-7 h-7 rounded-lg flex items-center justify-center hover:bg-red-50" title="Delete">
                                    <span class="material-symbols-outlined text-red-500" style="font-size:16px;">delete</span>
                                </button>
                            </div>
                        @endif
                    </div>
                @empty
                    <p class="px-5 py-6 text-sm text-on-surface-variant">No default questions found.</p>
                @endforelse
            </div>
            <div id="requestQuestionsList" class="questions-list hidden">
                @forelse ($custom_questions as $question)
                    @php
                        $type = $question->type === 'image' ? 'images' : $question->type;
                        $options = is_array($question->options) ? $question->options : [];
                        $isSystem = in_array((int) $question->id, $system_questions_ids ?? [], true);
                        $isEnabled = (bool) ($question->is_active ?? true);
                        $typeLabel = match ($type) {
                            'input' => 'Input',
                            'textarea' => 'Textarea',
                            'select' => 'Select',
                            'toggle' => 'Toggle',
                            'radio' => 'Radio',
                            'images' => 'Images',
                            default => ucfirst((string) $type),
                        };
                        $icon = match ($type) {
                            'select', 'radio' => 'list',
                            'textarea' => 'notes',
                            'toggle' => 'toggle_on',
                            'images' => 'upload_file',
                            default => 'short_text',
                        };
                        $badgeClass = match ($type) {
                            'select', 'radio' => 'badge-select',
                            'textarea' => 'badge-textarea',
                            'toggle' => 'badge-toggle',
                            'images' => 'badge-file',
                            default => 'badge-text',
                        };
                    @endphp
                    <div
                        class="q-row flex items-center gap-3 px-5 py-3.5 {{ $isEnabled ? '' : 'disabled' }}"
                        draggable="true"
                        data-id="{{ $question->id }}"
                        data-type="{{ $typeLabel }}"
                        data-type-value="{{ $type }}"
                        data-form-context="custom"
                        data-system="{{ $isSystem ? 'true' : 'false' }}"
                        data-enabled="{{ $isEnabled ? 'true' : 'false' }}"
                        data-required="{{ $question->is_required ? 'true' : 'false' }}"
                        data-available="{{ $isEnabled ? 'true' : 'false' }}"
                        data-options='@json($options)'
                    >
                        <span class="material-symbols-outlined text-outline" style="font-size:18px;">drag_indicator</span>
                        <span class="material-symbols-outlined text-on-surface-variant" style="font-size:18px;">{{ $icon }}</span>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-on-surface truncate">{{ $question->question }}</p>
                        </div>
                        <span class="badge {{ $badgeClass }}">{{ $typeLabel }}</span>
                        <span class="badge {{ $isSystem ? 'badge-system' : 'badge-custom' }}">{{ $isSystem ? 'SYSTEM' : 'CUSTOM' }}</span>
                        @if($isSystem)
                            <div class="toggle-switch {{ $isEnabled ? 'active' : '' }}" onclick="toggleSystemQuestion(this)" title="Enable/Disable"></div>
                            <span class="material-symbols-outlined text-outline/40" style="font-size:16px;" title="System question">lock</span>
                        @else
                            <div class="flex items-center gap-1">
                                <button type="button" class="js-edit-question w-7 h-7 rounded-lg flex items-center justify-center hover:bg-surface-container-low" title="Edit">
                                    <span class="material-symbols-outlined text-on-surface-variant" style="font-size:16px;">edit</span>
                                </button>
                                <button type="button" class="js-remove-question w-7 h-7 rounded-lg flex items-center justify-center hover:bg-red-50" title="Delete">
                                    <span class="material-symbols-outlined text-red-500" style="font-size:16px;">delete</span>
                                </button>
                            </div>
                        @endif
                    </div>
                @empty
                    <p class="px-5 py-6 text-sm text-on-surface-variant">No custom questions found.</p>
                @endforelse
            </div>
          </div>

          <!-- Sticky Footer -->
          <div class="sticky bottom-0 bg-surface z-10 py-4 flex flex-col sm:flex-row justify-end gap-3 mt-4">
            <button type="button" id="btnOpenAddQuestionModal"
              class="border border-outline-variant text-on-surface hover:bg-surface-container px-6 py-2.5 rounded-xl text-sm font-semibold flex items-center justify-center gap-2 transition-colors">
              <span class="material-symbols-outlined" style="font-size:18px;">add</span> Add Question
            </button>
            <button onclick="openPreviewModal()"
              class="border border-outline-variant text-on-surface hover:bg-surface-container px-6 py-2.5 rounded-xl text-sm font-semibold flex items-center justify-center gap-2 transition-colors">
              <span class="material-symbols-outlined" style="font-size:18px;">visibility</span> Preview Form
            </button>
            {{-- <button onclick="saveForm()"
              class="bg-primary text-white hover:bg-primary-container shadow-sm px-6 py-2.5 rounded-xl text-sm font-semibold flex items-center justify-center gap-2 transition-colors">
              <span class="material-symbols-outlined" style="font-size:18px;">save</span> Save Changes
            </button> --}}
          </div>
        </div>
      </div>

    </div>
  </main>

  <!-- Add question modal -->
  <div class="modal-backdrop" id="addQuestionModal" aria-hidden="true">
    <div class="add-question-modal-inner bg-white rounded-2xl w-full max-w-lg mx-4 shadow-2xl max-h-[90vh] overflow-y-auto">
      <div class="flex items-center justify-between px-5 py-4 border-b border-outline-variant/15">
      <h3 id="addQuestionModalTitle" class="text-lg font-bold text-on-surface">Add question</h3>
        <button type="button" id="btnCloseAddQuestionModal" class="w-9 h-9 rounded-xl flex items-center justify-center hover:bg-surface-container-low transition-colors" aria-label="Close">
          <span class="material-symbols-outlined text-on-surface-variant">close</span>
        </button>
      </div>
      <div class="p-5 space-y-4">
      <form id="addQuestionForm">
        <input type="hidden" id="editingQuestionId" value="">
        <p id="addQuestionGeneralError" class="hidden text-sm text-error rounded-xl bg-error-container/30 border border-error/20 px-3 py-2"></p>
        <div class="mb-2">
          <label for="newQuestionText" class="block text-xs font-semibold text-on-surface-variant mb-1.5">Question</label>
          <input type="text" id="newQuestionText" name="newQuestionText" placeholder="e.g., Preferred session length" class="w-full text-sm border border-outline-variant/30 rounded-xl px-3 py-2.5 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
          <p id="newQuestionTextError" class="hidden text-sm text-error mt-1"></p>
        </div>
        <div class="mb-2">
          <input type="hidden" id="form-context" name="form_context" value="default">
          <label for="newQuestionType" class="block text-xs font-semibold text-on-surface-variant mb-1.5">Answer type</label>
          <select id="newQuestionType" name="newQuestionType" class="w-full text-sm border border-outline-variant/30 rounded-xl px-3 py-2.5 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
            <option value="input" selected>Input</option>
            <option value="textarea">Textarea</option>
            <option value="select">Select</option>
            <option value="toggle">Toggle</option>
            <option value="images">Images</option>
            <option value="radio">Radio</option>
          </select>
          <p id="newQuestionTypeError" class="hidden text-sm text-error mt-1"></p>
        </div>
        <div class="add-options-div mb-2"></div>
        <div class="flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-6 pt-1">
          <div class="flex items-center justify-between sm:justify-start sm:gap-3">
            <span class="text-sm text-on-surface">Required</span>
            <button type="button" id="newQuestionRequiredToggle" class="toggle-switch active" role="switch" aria-checked="true" aria-label="Toggle required"></button>
            <input type="hidden" id="newQuestionRequired" name="newQuestionRequired" value="true">
          </div>
          <div class="flex items-center justify-between sm:justify-start sm:gap-3">
            <span class="text-sm text-on-surface">Available</span>
            <button type="button" id="newQuestionAvailableToggle" class="toggle-switch active" role="switch" aria-checked="true" aria-label="Toggle available"></button>
            <input type="hidden" id="newQuestionAvailable" name="newQuestionAvailable" value="true">
          </div>
        </div>
      </form>
      </div>
      <div class="px-5 py-4 border-t border-outline-variant/15 flex items-center justify-end gap-3">
        <button type="button" id="btnCancelAddQuestionModal" class="text-sm font-semibold text-on-surface-variant hover:text-on-surface px-4 py-2 rounded-xl transition-colors">Cancel</button>
        <button type="button" id="btnSubmitAddQuestion" class="bg-primary text-white px-5 py-2.5 rounded-xl font-semibold text-sm hover:bg-primary-container transition-colors shadow-sm inline-flex items-center gap-2">
        <span id="btnSubmitAddQuestionIcon" class="material-symbols-outlined text-lg">add</span>
        <span id="btnSubmitAddQuestionText">Add question</span>
      </button>
    </div>
  </div>
</div>

  <!-- Delete question confirmation modal -->
  <div class="modal-backdrop" id="deleteQuestionModal" aria-hidden="true">
    <div class="add-question-modal-inner bg-white rounded-2xl w-full max-w-md mx-4 shadow-2xl">
      <div class="flex items-center justify-between px-5 py-4 border-b border-outline-variant/15">
        <h3 class="text-lg font-bold text-on-surface">Delete question</h3>
        <button type="button" id="btnCloseDeleteQuestionModal" class="w-9 h-9 rounded-xl flex items-center justify-center hover:bg-surface-container-low transition-colors" aria-label="Close">
          <span class="material-symbols-outlined text-on-surface-variant">close</span>
        </button>
      </div>
      <div class="p-5 space-y-2">
        <p class="text-sm text-on-surface">Are you sure you want to delete this question?</p>
        <p class="text-xs text-on-surface-variant">This action cannot be undone.</p>
        <p id="deleteQuestionError" class="hidden text-sm text-error rounded-xl bg-error-container/30 border border-error/20 px-3 py-2"></p>
      </div>
      <div class="px-5 py-4 border-t border-outline-variant/15 flex items-center justify-end gap-3">
        <button type="button" id="btnCancelDeleteQuestionModal" class="text-sm font-semibold text-on-surface-variant hover:text-on-surface px-4 py-2 rounded-xl transition-colors">Cancel</button>
        <button type="button" id="btnConfirmDeleteQuestion" class="bg-red-600 text-white px-5 py-2.5 rounded-xl font-semibold text-sm hover:bg-red-700 transition-colors shadow-sm inline-flex items-center gap-2">
          <span class="material-symbols-outlined text-lg">delete</span>
          Delete
        </button>
      </div>
    </div>
  </div>

  <!-- Preview Modal -->
  <div class="modal-backdrop" id="previewModal" onclick="if(event.target===this){closePreviewModal()}">
    <div class="bg-white rounded-2xl w-full max-w-lg mx-4 shadow-2xl max-h-[90vh] overflow-hidden flex flex-col"
      onclick="event.stopPropagation()">
      <div class="flex items-center justify-between px-6 py-4 border-b border-outline-variant/15">
        <h3 class="text-lg font-bold text-on-surface">Form Preview</h3>
        <button onclick="closePreviewModal()"
          class="w-8 h-8 rounded-lg flex items-center justify-center hover:bg-surface-container-low transition-colors">
          <span class="material-symbols-outlined text-on-surface-variant">close</span>
        </button>
      </div>
      <div class="flex-1 overflow-y-auto" id="previewModalContent"></div>
      <div class="px-6 py-4 border-t border-outline-variant/15 flex justify-end">
        <button onclick="closePreviewModal()"
          class="bg-primary text-white px-5 py-2.5 rounded-xl font-semibold text-sm">Close</button>
      </div>
    </div>
  </div>

  <!-- Toast -->
  <div id="saveToast"
    class="fixed top-6 right-6 z-[300] transform translate-x-full opacity-0 transition-all duration-300">
    <div class="flex items-center gap-3 bg-on-surface text-white px-5 py-3 rounded-xl shadow-lg">
      <span class="material-symbols-outlined text-green-400" style="font-size:20px;">check_circle</span>
      <span class="text-sm font-medium" id="toastMessage">Changes saved successfully</span>
    </div>
  </div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.3/Sortable.min.js"></script>
<script>
  const ARTIST_FORMS_QUESTION_STORE = @json(route('artist.forms.questions.store'));
  const ARTIST_FORMS_QUESTION_UPDATE_URL_TEMPLATE = @json(route('artist.forms.questions.update', ['id' => '__ID__']));
  const ARTIST_FORMS_QUESTION_DELETE_URL_TEMPLATE = @json(route('artist.forms.questions.destroy', ['id' => '__ID__']));
  const ARTIST_FORMS_REORDER_URL = @json(route('artist.forms.questions.reorder'));
  const ARTIST_SYSTEM_QUESTION_STATUS_URL_TEMPLATE = @json(route('artist.forms.questions.status', ['id' => '__ID__']));
  const currentFormToContext = { booking: "default", request: "custom" };
  let currentFormType = "booking";
  let $pendingDeleteRow = null;

  function switchFormType(type, btn) {
    currentFormType = type;
    document.querySelectorAll(".form-tab").forEach(function (tab) {
      tab.classList.remove("active");
    });
    btn.classList.add("active");
    document.getElementById("bookingQuestionsList").classList.toggle("hidden", type !== "booking");
    document.getElementById("requestQuestionsList").classList.toggle("hidden", type !== "request");
    closeAddQuestionModal();
  }

  function toggleSystemQuestion(el) {
    const row = el.closest(".q-row");
    if (!row) return;
    const questionId = row.dataset.id;
    if (!questionId || el.dataset.loading === "true") return;

    const previousEnabled = row.dataset.enabled === "true";
    const nextEnabled = !previousEnabled;

    row.dataset.enabled = nextEnabled ? "true" : "false";
    row.dataset.available = nextEnabled ? "true" : "false";
    el.classList.toggle("active", nextEnabled);
    row.classList.toggle("disabled", !nextEnabled);
    el.dataset.loading = "true";
    el.style.pointerEvents = "none";

    $.ajax({
      url: ARTIST_SYSTEM_QUESTION_STATUS_URL_TEMPLATE.replace("__ID__", String(questionId)),
      method: "PATCH",
      data: JSON.stringify({ is_active: nextEnabled }),
      contentType: "application/json; charset=UTF-8",
      headers: {
        "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
        "Accept": "application/json",
        "X-Requested-With": "XMLHttpRequest"
      }
    }).done(function (response) {
      if (!response || !response.success) {
        row.dataset.enabled = previousEnabled ? "true" : "false";
        row.dataset.available = previousEnabled ? "true" : "false";
        el.classList.toggle("active", previousEnabled);
        row.classList.toggle("disabled", !previousEnabled);
        showToast((response && response.message) || "Failed to update question status.");
      }
    }).fail(function (xhr) {
      row.dataset.enabled = previousEnabled ? "true" : "false";
      row.dataset.available = previousEnabled ? "true" : "false";
      el.classList.toggle("active", previousEnabled);
      row.classList.toggle("disabled", !previousEnabled);
      const msg = (xhr.responseJSON && xhr.responseJSON.message) || "Failed to update question status.";
      showToast(msg);
    }).always(function () {
      delete el.dataset.loading;
      el.style.pointerEvents = "";
    });
  }

  const $addQuestionModal = $("#addQuestionModal");
  const $deleteQuestionModal = $("#deleteQuestionModal");
  const $newQuestionType = $("#newQuestionType");
  const $addOptionsDiv = $(".add-options-div");

  function getOptionRows() {
    return $addOptionsDiv.find(".option-row");
  }

  function toggleRemoveOptionButtons() {
    const showRemove = getOptionRows().length > 2;
    $addOptionsDiv.find(".btn-remove-option").toggleClass("hidden", !showRemove);
  }

  function appendOptionField(value = "") {
    const $optionRow = $(`
      <div class="option-row space-y-1">
        <div class="flex items-center gap-2">
          <input
            type="text"
            name="newQuestionOptions[]"
            value="${value}"
            placeholder="Add option"
            class="option-input w-full text-sm border border-outline-variant/30 rounded-xl px-3 py-2.5 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30"
          >
          <button
            type="button"
            class="btn-remove-option w-9 h-9 rounded-xl flex items-center justify-center hover:bg-red-50 text-red-500 transition-colors hidden"
            aria-label="Remove option"
          >
            <span class="material-symbols-outlined text-base">delete</span>
          </button>
        </div>
        <p class="option-error hidden text-sm text-error"></p>
      </div>
    `);
    $addOptionsDiv.find(".options-list").append($optionRow);
    toggleRemoveOptionButtons();
  }

  function appendFieldsByType(questionType) {
    $addOptionsDiv.empty();

    if (questionType !== "select" && questionType !== "radio") {
      return;
    }

    $addOptionsDiv.append(`
      <div class="space-y-2">
        <div class="flex items-center justify-between gap-2">
          <label class="block text-xs font-semibold text-on-surface-variant">Options</label>
          <button type="button" class="btn-add-option inline-flex items-center gap-1 text-xs font-semibold text-primary hover:text-primary-container transition-colors">
            <span class="material-symbols-outlined text-base">add_circle</span>
            Add more
          </button>
        </div>
        <p class="text-[11px] text-on-surface-variant">Add the choices clients can select.</p>
        <p class="options-general-error hidden text-sm text-error"></p>
        <div class="options-list space-y-2"></div>
      </div>
    `);

    appendOptionField();
    appendOptionField();
  }

  function clearFieldError($input, $error) {
    $input.removeClass("border-error ring-1 ring-error/40");
    $error.addClass("hidden").text("");
  }

  function setFieldError($input, $error, message) {
    $input.addClass("border-error ring-1 ring-error/40");
    $error.removeClass("hidden").text(message);
  }

  function clearOptionErrors() {
    $addOptionsDiv.find(".option-input").removeClass("border-error ring-1 ring-error/40");
    $addOptionsDiv.find(".option-error").addClass("hidden").text("");
    $addOptionsDiv.find(".options-general-error").addClass("hidden").text("");
  }

  function setOptionErrorByIndex(index, message) {
    const $row = getOptionRows().eq(index);
    if (!$row.length) return;
    $row.find(".option-input").addClass("border-error ring-1 ring-error/40");
    $row.find(".option-error").removeClass("hidden").text(message);
  }

  function clearAllAddQuestionErrors() {
    clearFieldError($("#newQuestionText"), $("#newQuestionTextError"));
    clearFieldError($("#newQuestionType"), $("#newQuestionTypeError"));
    clearOptionErrors();
    $("#addQuestionGeneralError").addClass("hidden").text("");
  }

  function resetAddQuestionForm() {
    const form = document.getElementById("addQuestionForm");
    if (form) form.reset();

    $("#newQuestionText").val("");
    $("#newQuestionType").val("input");
    $("#editingQuestionId").val("");
    $("#form-context").val(currentFormToContext[currentFormType] || "default");
    $("#newQuestionRequired").val("true");
    $("#newQuestionAvailable").val("true");
    $("#newQuestionRequiredToggle").addClass("active").attr("aria-checked", "true");
    $("#newQuestionAvailableToggle").addClass("active").attr("aria-checked", "true");
    $("#btnSubmitAddQuestionText").text("Add question");
    $("#btnSubmitAddQuestionIcon").text("add");
    $addOptionsDiv.empty();
    clearAllAddQuestionErrors();
  }

  function openAddQuestionModal() {
    resetAddQuestionForm();
    $addQuestionModal.addClass("open").attr("aria-hidden", "false");
    document.body.style.overflow = "hidden";
  }

  function openEditQuestionModal($row) {
    resetAddQuestionForm();

    const questionId = $row.data("id");
    const questionText = $.trim($row.find("p").first().text() || "");
    const typeValue = String($row.data("type-value") || "input");
    const formContext = String($row.data("form-context") || "default");
    const isRequired = String($row.data("required")) === "true";
    const isAvailable = String($row.data("available")) === "true";
    const options = $row.data("options");
    const optionValues = Array.isArray(options) ? options : [];

    $("#editingQuestionId").val(String(questionId || ""));
    $("#newQuestionText").val(questionText);
    $("#newQuestionType").val(typeValue);
    $("#form-context").val(formContext);
    $("#newQuestionRequired").val(isRequired ? "true" : "false");
    $("#newQuestionAvailable").val(isAvailable ? "true" : "false");
    $("#newQuestionRequiredToggle").toggleClass("active", isRequired).attr("aria-checked", isRequired ? "true" : "false");
    $("#newQuestionAvailableToggle").toggleClass("active", isAvailable).attr("aria-checked", isAvailable ? "true" : "false");
    $("#btnSubmitAddQuestionText").text("Update question");
    $("#btnSubmitAddQuestionIcon").text("save");

    appendFieldsByType(typeValue);
    if ((typeValue === "select" || typeValue === "radio") && optionValues.length) {
      const $list = $addOptionsDiv.find(".options-list");
      $list.empty();
      optionValues.forEach(function (value) {
        appendOptionField(value || "");
      });
      while (getOptionRows().length < 2) {
        appendOptionField("");
      }
      toggleRemoveOptionButtons();
    }

    $addQuestionModal.addClass("open").attr("aria-hidden", "false");
    document.body.style.overflow = "hidden";
  }

  function closeAddQuestionModal() {
    $addQuestionModal.removeClass("open").attr("aria-hidden", "true");
    if (!$("#previewModal").hasClass("open")) {
      document.body.style.overflow = "";
    }
  }

  function openDeleteQuestionModal($row) {
    $pendingDeleteRow = $row;
    $("#deleteQuestionError").addClass("hidden").text("");
    $deleteQuestionModal.addClass("open").attr("aria-hidden", "false");
    document.body.style.overflow = "hidden";
  }

  function closeDeleteQuestionModal() {
    $deleteQuestionModal.removeClass("open").attr("aria-hidden", "true");
    $pendingDeleteRow = null;
    $("#deleteQuestionError").addClass("hidden").text("");
    if (!$addQuestionModal.hasClass("open") && !$("#previewModal").hasClass("open")) {
      document.body.style.overflow = "";
    }
  }

  function getRawOptionValues() {
    return getOptionRows().map(function () {
      return $.trim($(this).find(".option-input").val());
    }).get();
  }

  function showToast(message) {
    const toast = document.getElementById("saveToast");
    document.getElementById("toastMessage").textContent = message;
    toast.classList.remove("translate-x-full", "opacity-0");
    toast.classList.add("translate-x-0", "opacity-100");
    setTimeout(function () {
      toast.classList.add("translate-x-full", "opacity-0");
      toast.classList.remove("translate-x-0", "opacity-100");
    }, 3000);
  }

  function escapeHtml(value) {
    return String(value || "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#39;");
  }

  function openPreviewModal() {
    const currentListId = currentFormType === "booking" ? "bookingQuestionsList" : "requestQuestionsList";
    const currentList = document.getElementById(currentListId);
    if (!currentList) {
      showToast("Could not load preview.");
      return;
    }

    const rows = Array.from(currentList.querySelectorAll(".q-row"));
    const visibleRows = rows.filter(function (row) {
      const isSystem = row.dataset.system === "true";
      return isSystem ? row.dataset.enabled === "true" : row.dataset.available === "true";
    });

    const title = currentFormType === "booking" ? "Available Design Form" : "Custom Request Form";
    let html = `
      <div class="px-6 py-5 bg-primary text-white">
        <h4 class="font-bold text-lg">${title}</h4>
        <p class="text-white/80 text-sm mt-1">Preview of active questions shown to clients</p>
      </div>
    `;

    if (!visibleRows.length) {
      html += `
        <div class="px-6 py-8 text-center">
          <p class="text-sm text-on-surface-variant">No active questions to preview.</p>
        </div>
      `;
    } else {
      html += visibleRows.map(function (row, index) {
        const questionText = escapeHtml($.trim($(row).find("p").first().text()) || "Question");
        const type = String(row.dataset.typeValue || "input");
        const isRequired = row.dataset.required === "true";
        const optionsRaw = row.dataset.options || "[]";
        let options = [];
        try {
          options = JSON.parse(optionsRaw);
        } catch (e) {
          options = [];
        }
        if (!Array.isArray(options)) {
          options = [];
        }

        let inputHtml = "";
        if (type === "textarea") {
          inputHtml = '<textarea rows="3" placeholder="Your answer..." class="w-full text-sm border border-outline-variant/30 rounded-xl px-3 py-2.5 bg-white mt-2 resize-none" disabled></textarea>';
        } else if (type === "select") {
          const optionHtml = options
            .map(function (option) { return `<option>${escapeHtml(option)}</option>`; })
            .join("");
          inputHtml = `<select class="w-full text-sm border border-outline-variant/30 rounded-xl px-3 py-2.5 bg-white mt-2" disabled><option>Select an option</option>${optionHtml}</select>`;
        } else if (type === "radio") {
          const radioHtml = (options.length ? options : ["Option 1", "Option 2"])
            .map(function (option) {
              return `<label class="inline-flex items-center gap-2 mr-4 mt-2"><input type="radio" disabled><span class="text-sm text-on-surface">${escapeHtml(option)}</span></label>`;
            })
            .join("");
          inputHtml = `<div>${radioHtml}</div>`;
        } else if (type === "toggle") {
          inputHtml = '<div class="inline-flex items-center gap-2 mt-2"><span class="toggle-switch"></span><span class="text-sm text-on-surface-variant">Yes / No</span></div>';
        } else if (type === "images" || type === "image") {
          inputHtml = '<div class="mt-2 border-2 border-dashed border-outline-variant/30 rounded-xl px-4 py-6 text-center"><span class="material-symbols-outlined text-outline text-3xl">upload</span><p class="text-sm text-on-surface-variant mt-1">Image upload area</p></div>';
        } else {
          inputHtml = '<input type="text" placeholder="Your answer..." class="w-full text-sm border border-outline-variant/30 rounded-xl px-3 py-2.5 bg-white mt-2" disabled>';
        }

        return `
          <div class="px-6 py-5 ${index ? "border-t border-outline-variant/15" : ""}">
            <label class="text-sm font-semibold text-on-surface">
              ${questionText}${isRequired ? ' <span class="text-red-500">*</span>' : ""}
            </label>
            ${inputHtml}
          </div>
        `;
      }).join("");
    }

    html += `
      <div class="px-6 py-5 border-t border-outline-variant/15">
        <button class="w-full bg-primary text-white py-3 rounded-xl font-semibold text-sm" disabled>Submit</button>
      </div>
    `;

    document.getElementById("previewModalContent").innerHTML = html;
    document.getElementById("previewModal").classList.add("open");
    document.body.style.overflow = "hidden";
  }

  function closePreviewModal() {
    document.getElementById("previewModal").classList.remove("open");
    if (!$("#addQuestionModal").hasClass("open")) {
      document.body.style.overflow = "";
    }
  }

  function saveForm() {
    showToast("Use Add Question modal to save.");
  }

  function initQuestionSorting(listSelector, formContext) {
    const listEl = document.querySelector(listSelector);
    if (!listEl || typeof Sortable === "undefined") {
      return;
    }

    Sortable.create(listEl, {
      draggable: ".q-row",
      animation: 150,
      ghostClass: "sortable-ghost",
      chosenClass: "sortable-chosen",
      onEnd: function () {
        const orderedItems = Array.from(listEl.querySelectorAll(".q-row"))
          .map(function (row, index) {
            const id = Number(row.dataset.id);
            if (!id) return null;
            return {
              id: id,
              order: index + 1
            };
          })
          .filter(Boolean);

        if (!orderedItems.length) {
          return;
        }

        $.ajax({
          url: ARTIST_FORMS_REORDER_URL,
          method: "POST",
          data: JSON.stringify({
            form_context: formContext,
            items: orderedItems
          }),
          contentType: "application/json; charset=UTF-8",
          headers: {
            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            "Accept": "application/json",
            "X-Requested-With": "XMLHttpRequest"
          }
        }).fail(function (xhr) {
          const msg = (xhr.responseJSON && xhr.responseJSON.message) || "Could not save question order.";
          showToast(msg);
        });
      }
    });
  }

  $("#btnOpenAddQuestionModal").on("click", function () {
    openAddQuestionModal();
  });

  $(document).on("click", ".js-edit-question", function () {
    const $row = $(this).closest(".q-row");
    if (!$row.length || String($row.data("system")) === "true") {
      return;
    }
    openEditQuestionModal($row);
  });

  $(document).on("click", ".js-remove-question", function () {
    const $row = $(this).closest(".q-row");
    if (!$row.length || String($row.data("system")) === "true") {
      return;
    }

    openDeleteQuestionModal($row);
  });

  $("#btnCloseAddQuestionModal, #btnCancelAddQuestionModal").on("click", function () {
    closeAddQuestionModal();
  });

  $addQuestionModal.on("click", function (event) {
    if (event.target === this) {
      closeAddQuestionModal();
    }
  });

  $("#btnCloseDeleteQuestionModal, #btnCancelDeleteQuestionModal").on("click", function () {
    closeDeleteQuestionModal();
  });

  $deleteQuestionModal.on("click", function (event) {
    if (event.target === this) {
      closeDeleteQuestionModal();
    }
  });

  $("#btnConfirmDeleteQuestion").on("click", function () {
    if (!$pendingDeleteRow || !$pendingDeleteRow.length) {
      closeDeleteQuestionModal();
      return;
    }

    const questionId = $pendingDeleteRow.data("id");
    if (!questionId) {
      $("#deleteQuestionError").removeClass("hidden").text("Could not determine question id.");
      return;
    }

    const $btn = $(this);
    const originalBtnHtml = $btn.html();
    $btn.prop("disabled", true).html("Deleting...");

    $.ajax({
      url: ARTIST_FORMS_QUESTION_DELETE_URL_TEMPLATE.replace("__ID__", String(questionId)),
      method: "DELETE",
      headers: {
        "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
        "Accept": "application/json",
        "X-Requested-With": "XMLHttpRequest"
      }
    }).done(function (response) {
      if (!response || !response.success) {
        $("#deleteQuestionError").removeClass("hidden").text((response && response.message) || "Failed to delete question.");
        return;
      }

      const $list = $pendingDeleteRow.closest(".questions-list");
      $pendingDeleteRow.remove();
      if ($list.length && !$list.children(".q-row").length) {
        const emptyText = $list.attr("id") === "bookingQuestionsList"
          ? "No default questions found."
          : "No custom questions found.";
        $list.append('<p class="px-5 py-6 text-sm text-on-surface-variant">' + emptyText + "</p>");
      }
      closeDeleteQuestionModal();
      showToast("Question deleted.");
    }).fail(function (xhr) {
      const msg = (xhr.responseJSON && xhr.responseJSON.message) || "Failed to delete question.";
      $("#deleteQuestionError").removeClass("hidden").text(msg);
    }).always(function () {
      $btn.prop("disabled", false).html(originalBtnHtml);
    });
  });

  $("#newQuestionRequiredToggle, #newQuestionAvailableToggle").on("click", function () {
    const $toggle = $(this);
    const isActive = !$toggle.hasClass("active");
    $toggle.toggleClass("active", isActive).attr("aria-checked", isActive ? "true" : "false");
  });

  $("#newQuestionRequiredToggle").on("click", function () {
    $("#newQuestionRequired").val($(this).hasClass("active") ? "true" : "false");
  });

  $("#newQuestionAvailableToggle").on("click", function () {
    $("#newQuestionAvailable").val($(this).hasClass("active") ? "true" : "false");
  });

  $newQuestionType.on("change", function () {
    appendFieldsByType($(this).val());
    clearFieldError($("#newQuestionType"), $("#newQuestionTypeError"));
    clearOptionErrors();
  });

  $("#newQuestionText").on("input", function () {
    clearFieldError($("#newQuestionText"), $("#newQuestionTextError"));
  });

  $addOptionsDiv.on("click", ".btn-add-option", function () {
    appendOptionField();
  });

  $addOptionsDiv.on("input", ".option-input", function () {
    const $row = $(this).closest(".option-row");
    $row.find(".option-input").removeClass("border-error ring-1 ring-error/40");
    $row.find(".option-error").addClass("hidden").text("");
    $addOptionsDiv.find(".options-general-error").addClass("hidden").text("");
  });

  $addOptionsDiv.on("click", ".btn-remove-option", function () {
    if (getOptionRows().length <= 2) {
      return;
    }
    $(this).closest(".option-row").remove();
    toggleRemoveOptionButtons();
  });

  $("#btnSubmitAddQuestion").on("click", function () {
    clearAllAddQuestionErrors();

    const payload = {
      question: $.trim($("#newQuestionText").val()),
      type: $("#newQuestionType").val(),
      form_context: $("#form-context").val() || currentFormToContext[currentFormType] || "default",
      is_required: $("#newQuestionRequired").val() === "true",
      is_active: $("#newQuestionAvailable").val() === "true"
    };

    if (payload.type === "select" || payload.type === "radio") {
      payload.options = getRawOptionValues();
    }

    const $submitBtn = $("#btnSubmitAddQuestion");
    const originalBtnHtml = $submitBtn.html();
    $submitBtn.prop("disabled", true).html("Saving...");
    const editingId = $.trim($("#editingQuestionId").val());
    const isEditing = editingId !== "";

    $.ajax({
      url: isEditing
        ? ARTIST_FORMS_QUESTION_UPDATE_URL_TEMPLATE.replace("__ID__", String(editingId))
        : ARTIST_FORMS_QUESTION_STORE,
      method: isEditing ? "PUT" : "POST",
      data: JSON.stringify(payload),
      contentType: "application/json; charset=UTF-8",
      headers: {
        "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
        "Accept": "application/json",
        "X-Requested-With": "XMLHttpRequest"
      }
    }).done(function (response) {
      if (response && response.success) {
        resetAddQuestionForm();
        closeAddQuestionModal();
        window.location.reload();
        return;
      }
      $("#addQuestionGeneralError").removeClass("hidden").text("Unexpected response from server.");
    }).fail(function (xhr) {
      if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
        const serverErrors = xhr.responseJSON.errors;

        if (serverErrors.question && serverErrors.question[0]) {
          setFieldError($("#newQuestionText"), $("#newQuestionTextError"), serverErrors.question[0]);
        }
        if (serverErrors.type && serverErrors.type[0]) {
          setFieldError($("#newQuestionType"), $("#newQuestionTypeError"), serverErrors.type[0]);
        }

        Object.keys(serverErrors).forEach(function (key) {
          const m = key.match(/^options\.(\d+)$/);
          if (!m) return;
          const idx = parseInt(m[1], 10);
          const msg = Array.isArray(serverErrors[key]) ? serverErrors[key][0] : "This field is required.";
          setOptionErrorByIndex(idx, msg);
        });
      } else {
        const msg = (xhr.responseJSON && xhr.responseJSON.message) || "Something went wrong while saving.";
        $("#addQuestionGeneralError").removeClass("hidden").text(msg);
      }
    }).always(function () {
      $submitBtn.prop("disabled", false).html(originalBtnHtml);
    });
  });

  document.addEventListener("keydown", function (e) {
    if (e.key !== "Escape") return;
    closePreviewModal();
    closeAddQuestionModal();
    closeDeleteQuestionModal();
  });

  initQuestionSorting("#bookingQuestionsList", "default");
  initQuestionSorting("#requestQuestionsList", "custom");
  </script>
@endsection