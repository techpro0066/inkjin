@extends('layouts.admin_dashboard_layout')

@section('title', 'Forms')

@section('styles')
<style>
    .question-row { transition: background 0.15s; }
    .question-row:hover { background: #f8f1fb; }
  .sortable-ghost { opacity: 0.45; background: #f8f1fb; }
  .sortable-chosen { cursor: grabbing; }
    @media (max-width: 1023px) {
      .main-content { overflow-x: hidden; padding: 16px; padding-top: 70px; }
      body { overflow-x: hidden; }
      #mobileSidebar.mobile-menu-open {
        display: flex !important;
        width: 100% !important;
        max-width: 100vw;
        left: 0;
        right: 0;
        top: 0;
        bottom: 0;
        min-height: 100vh;
        min-height: 100dvh;
        z-index: 45;
        padding-top: 4.5rem;
        padding-bottom: env(safe-area-inset-bottom, 0);
      }
    }
    body.admin-mobile-nav-open {
      overflow: hidden;
    }

    /* Add question modal */
    .modal-backdrop {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.55);
      z-index: 200;
      align-items: center;
      justify-content: center;
      opacity: 0;
      transition: opacity 0.3s ease;
    }
    .modal-backdrop.modal-visible { display: flex; }
    .modal-backdrop.modal-visible:not(.modal-open) { pointer-events: none; }
    .modal-backdrop.modal-open { opacity: 1; pointer-events: auto; }
    .add-question-modal-inner {
      transform: scale(0.96) translateY(10px);
      opacity: 0;
      transition: transform 0.32s cubic-bezier(0.22, 1, 0.36, 1), opacity 0.28s ease;
    }
    .modal-backdrop.modal-open .add-question-modal-inner {
      transform: scale(1) translateY(0);
      opacity: 1;
    }
    .toggle-switch {
      width: 48px;
      height: 26px;
      border-radius: 13px;
      background: #cac4d3;
      cursor: pointer;
      position: relative;
      transition: background 0.3s;
      border: none;
      flex-shrink: 0;
    }
    .toggle-switch.active { background: #310f7a; }
    .toggle-switch::after {
      content: "";
      position: absolute;
      top: 3px;
      left: 3px;
      width: 20px;
      height: 20px;
      border-radius: 50%;
      background: white;
      transition: transform 0.3s;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.15);
    }
    .toggle-switch.active::after { transform: translateX(22px); }
</style>
@endsection

@section('content')

<main class="main-content flex-1 min-h-screen">
    <div class="p-6 md:p-10 lg:p-12 max-w-5xl">

      <div class="mb-8">
        <h2 class="text-3xl font-extrabold text-on-surface tracking-tight">Form Management</h2>
        <p class="text-on-surface-variant mt-1">Manage default questions for booking forms and custom requests.</p>
      </div>

      <!-- Section 1: Available Design Bookings -->
      <div class="bg-white rounded-2xl shadow-sm border border-outline-variant/20 mb-8 overflow-hidden">
        <div class="px-6 py-5 border-b border-outline-variant/15">
          <h3 class="text-lg font-bold text-on-surface">Default Questions for Available Design Bookings</h3>
          <p class="text-xs text-on-surface-variant mt-1">These questions appear on every design booking form.</p>
        </div>
        <div class="divide-y divide-outline-variant/10" id="bookingQuestions">
        @forelse($defaultQuestions ?? [] as $question)
          @php
            $type = $question->type === 'image' ? 'images' : $question->type;
            $badgeMap = [
              'select' => ['bg-blue-50 text-blue-700', 'Select'],
              'radio' => ['bg-teal-50 text-teal-800', 'Radio'],
              'toggle' => ['bg-purple-50 text-purple-700', 'Toggle'],
              'input' => ['bg-sky-50 text-sky-800', 'Input'],
              'textarea' => ['bg-indigo-50 text-indigo-800', 'Textarea'],
              'images' => ['bg-amber-50 text-amber-900', 'Images'],
            ];
            $badge = $badgeMap[$type] ?? ['bg-gray-100 text-gray-700', ucfirst($type)];
            $options = is_array($question->options) ? $question->options : [];
          @endphp
          <div
            class="question-row px-6 py-4 flex items-center gap-4"
            draggable="true"
            data-question-id="{{ $question->id }}"
            data-question-text="{{ e($question->question) }}"
            data-question-description="{{ e($question->description ?? '') }}"
            data-question-placeholder="{{ e($question->placeholder ?? '') }}"
            data-question-type="{{ $type }}"
            data-form-context="default"
            data-is-required="{{ $question->is_required ? '1' : '0' }}"
            data-is-active="{{ $question->is_active ? '1' : '0' }}"
            data-options='@json($options)'
          >
            <span class="material-symbols-outlined text-outline" style="font-size:20px;">drag_indicator</span>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-semibold text-on-surface">{{ $question->question }}</p>
              @if(in_array($type, ['select', 'radio'], true) && count($options))
                <p class="text-xs text-outline mt-0.5 js-question-sub">{{ implode(' · ', $options) }}</p>
              @endif
            </div>
            <span class="text-[10px] font-semibold px-2.5 py-0.5 rounded-full shrink-0 {{ $badge[0] }}">{{ $badge[1] }}</span>
            <div class="flex flex-col items-end gap-1 shrink-0">
            <div class="flex items-center gap-1.5">
                @if($question->is_required)
                  <span class="w-2 h-2 rounded-full bg-green-500"></span><span class="text-xs font-medium text-green-700">Required</span>
                @else
                  <span class="w-2 h-2 rounded-full bg-gray-300"></span><span class="text-xs font-medium text-gray-500">Optional</span>
                @endif
              </div>
              @if($question->is_active)
                <span class="text-[10px] font-semibold text-emerald-800 bg-emerald-50 px-2 py-0.5 rounded-full">Active</span>
              @else
                <span class="text-[10px] font-semibold text-gray-600 bg-gray-100 px-2 py-0.5 rounded-full">Inactive</span>
              @endif
            </div>
            <div class="flex gap-1">
              <button type="button" class="js-edit-question w-7 h-7 rounded-lg flex items-center justify-center hover:bg-surface-container-low"><span class="material-symbols-outlined text-on-surface-variant" style="font-size:16px;">edit</span></button>
              <button type="button" class="js-remove-question w-7 h-7 rounded-lg flex items-center justify-center hover:bg-red-50"><span class="material-symbols-outlined text-red-500" style="font-size:16px;">delete</span></button>
            </div>
          </div>
        @empty
          <p class="js-forms-empty-msg px-6 py-8 text-sm text-on-surface-variant text-center">No default questions found.</p>
        @endforelse
        </div>
        <div class="px-6 py-4 border-t border-outline-variant/15">
        <button type="button" id="btnAddBookingQuestion" data-question-list="default" class="inline-flex items-center gap-2 bg-primary text-white px-4 py-2 rounded-xl font-semibold text-xs hover:bg-primary-container transition-colors">
            <span class="material-symbols-outlined" style="font-size:16px;">add</span> Add Question
          </button>
        </div>
      </div>

      <!-- Section 2: Custom Requests -->
      <div class="bg-white rounded-2xl shadow-sm border border-outline-variant/20 mb-8 overflow-hidden">
        <div class="px-6 py-5 border-b border-outline-variant/15">
          <h3 class="text-lg font-bold text-on-surface">Default Questions for Custom Requests</h3>
          <p class="text-xs text-on-surface-variant mt-1">These questions appear on the custom tattoo request form.</p>
        </div>
        <div class="px-6 py-3 bg-surface-container-low/50 border-b border-outline-variant/15">
          <span class="text-[10px] font-bold uppercase tracking-wider text-on-surface-variant">Custom Questions</span>
        </div>
      <div class="divide-y divide-outline-variant/10" id="customQuestions">
        @forelse($customQuestions ?? [] as $question)
          @php
            $type = $question->type === 'image' ? 'images' : $question->type;
            $badgeMap = [
              'select' => ['bg-blue-50 text-blue-700', 'Select'],
              'radio' => ['bg-teal-50 text-teal-800', 'Radio'],
              'toggle' => ['bg-purple-50 text-purple-700', 'Toggle'],
              'input' => ['bg-sky-50 text-sky-800', 'Input'],
              'textarea' => ['bg-indigo-50 text-indigo-800', 'Textarea'],
              'images' => ['bg-amber-50 text-amber-900', 'Images'],
            ];
            $badge = $badgeMap[$type] ?? ['bg-gray-100 text-gray-700', ucfirst($type)];
            $options = is_array($question->options) ? $question->options : [];
          @endphp
          <div
            class="question-row px-6 py-4 flex items-center gap-4"
            draggable="true"
            data-question-id="{{ $question->id }}"
            data-question-text="{{ e($question->question) }}"
            data-question-description="{{ e($question->description ?? '') }}"
            data-question-placeholder="{{ e($question->placeholder ?? '') }}"
            data-question-type="{{ $type }}"
            data-form-context="custom"
            data-is-required="{{ $question->is_required ? '1' : '0' }}"
            data-is-active="{{ $question->is_active ? '1' : '0' }}"
            data-options='@json($options)'
          >
            <span class="material-symbols-outlined text-outline" style="font-size:20px;">drag_indicator</span>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-semibold text-on-surface">{{ $question->question }}</p>
              @if(in_array($type, ['select', 'radio'], true) && count($options))
                <p class="text-xs text-outline mt-0.5 js-question-sub">{{ implode(' · ', $options) }}</p>
              @endif
            </div>
            <span class="text-[10px] font-semibold px-2.5 py-0.5 rounded-full shrink-0 {{ $badge[0] }}">{{ $badge[1] }}</span>
            <div class="flex flex-col items-end gap-1 shrink-0">
              <div class="flex items-center gap-1.5">
                @if($question->is_required)
                  <span class="w-2 h-2 rounded-full bg-green-500"></span><span class="text-xs font-medium text-green-700">Required</span>
                @else
                  <span class="w-2 h-2 rounded-full bg-gray-300"></span><span class="text-xs font-medium text-gray-500">Optional</span>
                @endif
              </div>
              @if($question->is_active)
                <span class="text-[10px] font-semibold text-emerald-800 bg-emerald-50 px-2 py-0.5 rounded-full">Active</span>
              @else
                <span class="text-[10px] font-semibold text-gray-600 bg-gray-100 px-2 py-0.5 rounded-full">Inactive</span>
              @endif
            </div>
            <div class="flex gap-1">
              <button type="button" class="js-edit-question w-7 h-7 rounded-lg flex items-center justify-center hover:bg-surface-container-low"><span class="material-symbols-outlined text-on-surface-variant" style="font-size:16px;">edit</span></button>
              <button type="button" class="js-remove-question w-7 h-7 rounded-lg flex items-center justify-center hover:bg-red-50"><span class="material-symbols-outlined text-red-500" style="font-size:16px;">delete</span></button>
            </div>
          </div>
        @empty
          <p class="js-forms-empty-msg px-6 py-8 text-sm text-on-surface-variant text-center">No custom questions found.</p>
        @endforelse
      </div>
        <div class="px-6 py-4 border-t border-outline-variant/15">
          <button type="button" id="btnAddCustomQuestion" data-question-list="custom" class="inline-flex items-center gap-2 bg-primary text-white px-4 py-2 rounded-xl font-semibold text-xs hover:bg-primary-container transition-colors">
            <span class="material-symbols-outlined" style="font-size:16px;">add</span> Add Question
          </button>
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
        <div class="mb-3">
          <label for="newQuestionText" class="block text-xs font-semibold text-on-surface-variant mb-1.5">Question</label>
          <input type="text" id="newQuestionText" name="newQuestionText" placeholder="e.g., Preferred session length" class="w-full text-sm border border-outline-variant/30 rounded-xl px-3 py-2.5 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
          <p id="newQuestionTextError" class="hidden text-sm text-error mt-1"></p>
        </div>
        <div class="mb-3">
          <label for="newQuestionDescription" class="block text-xs font-semibold text-on-surface-variant mb-1.5">Description (optional)</label>
          <textarea id="newQuestionDescription" name="newQuestionDescription" rows="2" placeholder="Add helper text for users (optional)" class="w-full text-sm border border-outline-variant/30 rounded-xl px-3 py-2.5 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30 resize-none"></textarea>
          <p id="newQuestionDescriptionError" class="hidden text-sm text-error mt-1"></p>
        </div>
        <div class="mb-3">
          <label for="newQuestionPlaceholder" class="block text-xs font-semibold text-on-surface-variant mb-1.5">Placeholder (optional)</label>
          <input type="text" id="newQuestionPlaceholder" name="newQuestionPlaceholder" placeholder="e.g., Enter your answer..." class="w-full text-sm border border-outline-variant/30 rounded-xl px-3 py-2.5 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
          <p id="newQuestionPlaceholderError" class="hidden text-sm text-error mt-1"></p>
        </div>
        <div class="mb-3">
          <input type="hidden" id="form-context" name="form_context" value="default">
          <label for="newQuestionType" class="block text-xs font-semibold text-on-surface-variant mb-1.5">Answer type</label>
          <select id="newQuestionType" name="newQuestionType" class="w-full text-sm border border-outline-variant/30 rounded-xl px-3 py-2.5 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
            <option value="" selected disabled>Select answer type</option>
            <option value="input">Input (a single-line field for short answers)</option>
            <option value="textarea">Textarea (a multi-line field for longer responses)</option>
            <option value="select">Select (a list of options where the user picks one)</option>
            <option value="toggle">Toggle (a yes/no question)</option>
            <option value="images">Images (a field for uploading images)</option>
            <option value="radio">Radio (a list of options where the user picks only one)</option>
          </select>
          <p id="newQuestionTypeError" class="hidden text-sm text-error mt-1"></p>
        </div>
        <div class="add-options-div"></div>
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

@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.3/Sortable.min.js"></script>
<script>
  const QUESTION_DELETE_URL_TEMPLATE = @json(route('admin.forms.questions.destroy', ['id' => '__ID__']));
  const QUESTION_UPDATE_URL_TEMPLATE = @json(route('admin.forms.questions.update', ['id' => '__ID__']));
  const REORDER_URL = @json(route('admin.forms.questions.reorder'));
  // ===== Cached DOM references =====
  const $addQuestionModal = $("#addQuestionModal");
  const $deleteQuestionModal = $("#deleteQuestionModal");
  const $newQuestionType = $("#newQuestionType");
  const $addOptionsDiv = $(".add-options-div");
  let $pendingDeleteRow = null;

  // ===== Option field helpers =====
  function getOptionRows() {
    return $addOptionsDiv.find(".option-row");
  }

  function toggleRemoveOptionButtons() {
    const optionCount = getOptionRows().length;
    const showRemove = optionCount > 2;
    $addOptionsDiv.find(".btn-remove-option").toggleClass("hidden", !showRemove);
  }

  // Build and append a single option row.
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

  // Render dynamic inputs for answer type (only select/radio need options).
  function appendFieldsByType(questionType) {
    $addOptionsDiv.empty();

    if (questionType !== "select" && questionType !== "radio") {
      return;
    }

    $addOptionsDiv.append(`
      <div class="space-y-2">
        <div class="flex items-center justify-between gap-2 class="mb-3"">
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

  // ===== SortableJS initialization =====
  function initQuestionSorting(listSelector) {
    const el = document.querySelector(listSelector);
    if (!el || typeof Sortable === "undefined") return;
    Sortable.create(el, {
      draggable: ".question-row",
      animation: 150,
      ghostClass: "sortable-ghost",
      chosenClass: "sortable-chosen",
      onEnd: function () {
        const orderedRows = Array.from(el.querySelectorAll(".question-row"))
          .map(function (row, index) {
            const id = row.getAttribute("data-question-id");
            if (id === null || id === "") return null;
            return { order: index + 1, id: Number(id) };
          })
          .filter(Boolean);

        console.log("[Forms Sort Order]", listSelector, orderedRows);
        console.table(orderedRows);

        if (!orderedRows.length) return;

        const formContext = listSelector === "#bookingQuestions" ? "default" : "custom";
        $.ajax({
          url: REORDER_URL,
          method: "POST",
          data: JSON.stringify({
            form_context: formContext,
            items: orderedRows
          }),
          contentType: "application/json; charset=UTF-8",
          headers: {
            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            "Accept": "application/json",
            "X-Requested-With": "XMLHttpRequest"
          }
        }).done(function (res) {
          if (res && res.success) {
            console.log("[Forms Sort Saved]", formContext, orderedRows);
        } else {
            console.warn("[Forms Sort Save Failed]", res);
          }
        }).fail(function (xhr) {
          console.error("[Forms Sort Save Error]", xhr.responseJSON || xhr.statusText);
        });
      }
    });
  }

  // ===== Modal open/close =====
  function openAddQuestionModal() {
    $addQuestionModal.attr("aria-hidden", "false").addClass("modal-visible");
    requestAnimationFrame(function () {
      $addQuestionModal.addClass("modal-open");
    });
  }

  function closeAddQuestionModal() {
    $addQuestionModal.removeClass("modal-open");
    setTimeout(function () {
      $addQuestionModal.removeClass("modal-visible").attr("aria-hidden", "true");
    }, 300);
  }

  function openDeleteQuestionModal($row) {
    $pendingDeleteRow = $row;
    $("#deleteQuestionError").addClass("hidden").text("");
    $deleteQuestionModal.attr("aria-hidden", "false").addClass("modal-visible");
    requestAnimationFrame(function () {
      $deleteQuestionModal.addClass("modal-open");
    });
  }

  function closeDeleteQuestionModal() {
    $deleteQuestionModal.removeClass("modal-open");
    setTimeout(function () {
      $deleteQuestionModal.removeClass("modal-visible").attr("aria-hidden", "true");
      $pendingDeleteRow = null;
      $("#deleteQuestionError").addClass("hidden").text("");
    }, 300);
  }

  // ===== Inline error display helpers =====
  function clearFieldError($input, $error) {
    $input.removeClass("border-error ring-1 ring-error/40");
    $error.addClass("hidden").text("");
  }

  function setFieldError($input, $error, message) {
    $input.addClass("border-error ring-1 ring-error/40");
    $error.removeClass("hidden").text(message);
  }

  // Clear all option-level errors in modal.
  function clearOptionErrors() {
    $addOptionsDiv.find(".option-input").removeClass("border-error ring-1 ring-error/40");
    $addOptionsDiv.find(".option-error").addClass("hidden").text("");
    $addOptionsDiv.find(".options-general-error").addClass("hidden").text("");
  }

  // Show error for a specific option row by index.
  function setOptionErrorByIndex(index, message) {
    const $row = getOptionRows().eq(index);
    if (!$row.length) return;
    const $input = $row.find(".option-input");
    const $error = $row.find(".option-error");
    $input.addClass("border-error ring-1 ring-error/40");
    $error.removeClass("hidden").text(message);
  }

  // Reset all field and general error messages.
  function clearAllAddQuestionErrors() {
    clearFieldError($("#newQuestionText"), $("#newQuestionTextError"));
    clearFieldError($("#newQuestionDescription"), $("#newQuestionDescriptionError"));
    clearFieldError($("#newQuestionPlaceholder"), $("#newQuestionPlaceholderError"));
    clearFieldError($("#newQuestionType"), $("#newQuestionTypeError"));
    clearOptionErrors();
    $("#addQuestionGeneralError").addClass("hidden").text("");
  }

  // ===== Full modal form reset =====
  // Used before opening modal and after successful save.
  function resetAddQuestionForm() {
    const form = document.getElementById("addQuestionForm");
    if (form) {
      form.reset();
    }

    $("#newQuestionText").val("");
    $("#newQuestionDescription").val("");
    $("#newQuestionPlaceholder").val("");
    $("#newQuestionType").val("");
    $("#editingQuestionId").val("");
    $("#addQuestionModalTitle").text("Add question");
    $("#btnSubmitAddQuestionText").text("Add question");
    $("#btnSubmitAddQuestionIcon").text("add");
    $("#newQuestionRequired").val("true");
    $("#newQuestionAvailable").val("true");
    $("#newQuestionRequiredToggle")
      .addClass("active")
      .attr("aria-checked", "true");
    $("#newQuestionAvailableToggle")
      .addClass("active")
      .attr("aria-checked", "true");
    $addOptionsDiv.empty();
    clearAllAddQuestionErrors();
  }

  function openEditQuestionModal($row) {
    resetAddQuestionForm();

    const id = $row.data("question-id");
    const text = $row.data("question-text") || "";
    const description = $row.data("question-description") || "";
    const placeholder = $row.data("question-placeholder") || "";
    const type = $row.data("question-type") || "";
    const formContext = $row.data("form-context") || "default";
    const isRequired = String($row.data("is-required")) === "1";
    const isActive = String($row.data("is-active")) === "1";
    const options = $row.data("options");
    const optionValues = Array.isArray(options) ? options : [];

    $("#editingQuestionId").val(id);
    $("#newQuestionText").val(text);
    $("#newQuestionDescription").val(description);
    $("#newQuestionPlaceholder").val(placeholder);
    $("#newQuestionType").val(type);
    $("#form-context").val(formContext);
    $("#newQuestionRequired").val(isRequired ? "true" : "false");
    $("#newQuestionAvailable").val(isActive ? "true" : "false");
    $("#newQuestionRequiredToggle").toggleClass("active", isRequired).attr("aria-checked", isRequired ? "true" : "false");
    $("#newQuestionAvailableToggle").toggleClass("active", isActive).attr("aria-checked", isActive ? "true" : "false");
    $("#addQuestionModalTitle").text("Edit question");
    $("#btnSubmitAddQuestionText").text("Update question");
    $("#btnSubmitAddQuestionIcon").text("save");

    appendFieldsByType(type);
    if ((type === "select" || type === "radio") && optionValues.length) {
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

    openAddQuestionModal();
  }

  // Return all option input values (trimmed) as array for request payload.
  function getRawOptionValues() {
    return getOptionRows().map(function () {
      return $.trim($(this).find(".option-input").val());
    }).get();
  }

  // ===== Modal trigger buttons =====
  $("#btnAddBookingQuestion, #btnAddCustomQuestion").on("click", function () {
    resetAddQuestionForm();
    openAddQuestionModal();
  });

  $(document).on("click", ".js-edit-question", function () {
    const $row = $(this).closest(".question-row");
    if (!$row.length) return;
    openEditQuestionModal($row);
  });

  // Close modal using close/cancel buttons.
  $("#btnCloseAddQuestionModal, #btnCancelAddQuestionModal").on("click", function () {
        closeAddQuestionModal();
      });

  // Close modal on backdrop click.
  $addQuestionModal.on("click", function (event) {
    if (event.target === this) {
          closeAddQuestionModal();
        }
      });

  // Close delete modal using close/cancel buttons.
  $("#btnCloseDeleteQuestionModal, #btnCancelDeleteQuestionModal").on("click", function () {
    closeDeleteQuestionModal();
  });

  // Close delete modal on backdrop click.
  $deleteQuestionModal.on("click", function (event) {
    if (event.target === this) {
      closeDeleteQuestionModal();
    }
  });

  // ===== Required/Available toggle controls =====
  $("#newQuestionRequiredToggle, #newQuestionAvailableToggle").on("click", function () {
    const $toggle = $(this);
    const isActive = !$toggle.hasClass("active");
    $toggle.toggleClass("active", isActive).attr("aria-checked", isActive ? "true" : "false");
  });

  // Sync hidden "required" field with toggle state.
  $("#newQuestionRequiredToggle").on("click", function () {
    $("#newQuestionRequired").val($(this).hasClass("active") ? "true" : "false");
  });

  // Sync hidden "available" field with toggle state.
  $("#newQuestionAvailableToggle").on("click", function () {
    $("#newQuestionAvailable").val($(this).hasClass("active") ? "true" : "false");
  });

  // ===== Field change handlers =====
  // Rebuild type-dependent fields and clear related errors.
  $newQuestionType.on("change", function () {
    appendFieldsByType($(this).val());
    clearFieldError($("#newQuestionType"), $("#newQuestionTypeError"));
    clearOptionErrors();
  });

  // Clear question error while typing.
  $("#newQuestionText").on("input", function () {
    clearFieldError($("#newQuestionText"), $("#newQuestionTextError"));
  });
  $("#newQuestionDescription").on("input", function () {
    clearFieldError($("#newQuestionDescription"), $("#newQuestionDescriptionError"));
  });
  $("#newQuestionPlaceholder").on("input", function () {
    clearFieldError($("#newQuestionPlaceholder"), $("#newQuestionPlaceholderError"));
  });

  // Add a new option row.
  $addOptionsDiv.on("click", ".btn-add-option", function () {
    appendOptionField();
  });

  // Clear option row error as user types.
  $addOptionsDiv.on("input", ".option-input", function () {
    const $row = $(this).closest(".option-row");
    $row.find(".option-input").removeClass("border-error ring-1 ring-error/40");
    $row.find(".option-error").addClass("hidden").text("");
    $addOptionsDiv.find(".options-general-error").addClass("hidden").text("");
  });

  // Remove option row (minimum 2 rows stay visible).
  $addOptionsDiv.on("click", ".btn-remove-option", function () {
    if (getOptionRows().length <= 2) {
      return;
    }
    $(this).closest(".option-row").remove();
    toggleRemoveOptionButtons();
  });

  // ===== Enable sorting per list =====
  initQuestionSorting("#bookingQuestions");
  initQuestionSorting("#customQuestions");

  // Set modal context when opening from booking/custom section.
  $("#btnAddBookingQuestion").on("click", function () {
    $('#form-context').val("default");
  });

  $("#btnAddCustomQuestion").on("click", function () {
    $('#form-context').val("custom");
  });

  // ===== Delete flow (confirmation modal + AJAX delete) =====
  $(document).on("click", ".js-remove-question", function () {
    const $row = $(this).closest(".question-row");
    if (!$row.length) return;
    openDeleteQuestionModal($row);
  });

  $("#btnConfirmDeleteQuestion").on("click", function () {
    if (!$pendingDeleteRow || !$pendingDeleteRow.length) {
      closeDeleteQuestionModal();
      return;
    }

    const questionId = $pendingDeleteRow.data("question-id");
    if (!questionId) {
      $("#deleteQuestionError").removeClass("hidden").text("Could not determine question id.");
      return;
    }

    const $btn = $(this);
    const original = $btn.html();
    $btn.prop("disabled", true).html("Deleting...");

    $.ajax({
      url: QUESTION_DELETE_URL_TEMPLATE.replace("__ID__", String(questionId)),
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

      const $container = $pendingDeleteRow.closest("#bookingQuestions, #customQuestions");
      $pendingDeleteRow.remove();
      if ($container.length && $container.children(".question-row").length === 0) {
        const msg = $container.attr("id") === "bookingQuestions"
          ? "No default questions found."
          : "No custom questions found.";
        $container.append('<p class="js-forms-empty-msg px-6 py-8 text-sm text-on-surface-variant text-center">' + msg + "</p>");
      }
      closeDeleteQuestionModal();
    }).fail(function (xhr) {
      const msg = (xhr.responseJSON && xhr.responseJSON.message) || "Something went wrong while deleting.";
      $("#deleteQuestionError").removeClass("hidden").text(msg);
    }).always(function () {
      $btn.prop("disabled", false).html(original);
    });
  });

  // ===== Submit flow (AJAX save) =====
  $("#btnSubmitAddQuestion").on("click", function () {
    clearAllAddQuestionErrors();

    const payload = {
      question: $.trim($("#newQuestionText").val()),
      description: $.trim($("#newQuestionDescription").val()),
      placeholder: $.trim($("#newQuestionPlaceholder").val()),
      type: $("#newQuestionType").val(),
      form_context: $("#form-context").val(),
      is_required: $("#newQuestionRequired").val() === "true",
      is_active: $("#newQuestionAvailable").val() === "true"
    };
    const editingId = $("#editingQuestionId").val();
    const isEditing = !!editingId;

    if (payload.type === "select" || payload.type === "radio") {
      payload.options = getRawOptionValues();
    }

    const $submitBtn = $("#btnSubmitAddQuestion");
    const originalBtnHtml = $submitBtn.html();
    $submitBtn.prop("disabled", true).html("Saving...");

    // Send request to backend and map validation errors back to fields.
    $.ajax({
      url: isEditing ? QUESTION_UPDATE_URL_TEMPLATE.replace("__ID__", String(editingId)) : @json(route('admin.forms.questions.store')),
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
        // On success, reset modal and refresh list data from server.
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
        if (serverErrors.description && serverErrors.description[0]) {
          setFieldError($("#newQuestionDescription"), $("#newQuestionDescriptionError"), serverErrors.description[0]);
        }
        if (serverErrors.placeholder && serverErrors.placeholder[0]) {
          setFieldError($("#newQuestionPlaceholder"), $("#newQuestionPlaceholderError"), serverErrors.placeholder[0]);
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
  </script>

@endsection