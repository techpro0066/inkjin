@extends('layouts.admin_dashboard_layout')

@section('title', 'Forms')

@section('styles')
<style>
    .question-row { transition: background 0.15s; }
    .question-row:hover { background: #f8f1fb; }
  .sortable-ghost { opacity: 0.45; background: #f8f1fb; }
  .sortable-chosen { cursor: grabbing; }
  .form-tab {
    padding: 0.75rem 1.25rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: #494552;
    border-bottom: 2px solid transparent;
    margin-bottom: -1px;
    transition: all 0.15s ease;
    background: none;
    border-top: none;
    border-left: none;
    border-right: none;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
  }
  .form-tab:hover:not(.active) { color: #1c1b21; }
  .form-tab.active {
    color: #310f7a;
    border-bottom-color: #310f7a;
  }
  .consent-type-badge-health { background: #ecfdf5; color: #047857; }
  .consent-type-badge-risk { background: #fff7ed; color: #c2410c; }
  .consent-type-badge-aftercare { background: #eff6ff; color: #1d4ed8; }
  .consent-lang-scroll {
    max-height: 240px;
    overflow-y: auto;
    border: 1px solid rgba(202, 196, 211, 0.35);
    border-radius: 0.75rem;
    background: #faf8fc;
  }
  .consent-lang-row {
    padding: 0.75rem 0.875rem;
    border-bottom: 1px solid rgba(202, 196, 211, 0.2);
  }
  .consent-lang-row:last-child { border-bottom: none; }
  .consent-lang-row label {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    margin-bottom: 0.35rem;
  }
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
      @php
        $formsTab = request('tab', 'booking');
        if (! in_array($formsTab, ['booking', 'custom', 'consent'], true)) {
          $formsTab = 'booking';
        }
      @endphp

      <div class="mb-8">
        <h2 class="text-3xl font-extrabold text-on-surface tracking-tight">Form Management</h2>
        <p class="text-on-surface-variant mt-1">Manage default questions for booking forms, custom requests, and consent.</p>
      </div>

      <div class="flex border-b border-outline-variant/20 mb-6 overflow-x-auto">
        <a href="{{ route('admin.forms.index', ['tab' => 'booking']) }}" class="form-tab {{ $formsTab === 'booking' ? 'active' : '' }}">Available design</a>
        <a href="{{ route('admin.forms.index', ['tab' => 'custom']) }}" class="form-tab {{ $formsTab === 'custom' ? 'active' : '' }}">Custom</a>
        <a href="{{ route('admin.forms.index', ['tab' => 'consent']) }}" class="form-tab {{ $formsTab === 'consent' ? 'active' : '' }}">Consent</a>
      </div>

      @if($formsTab === 'booking')
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
              'style' => ['bg-rose-50 text-rose-800', 'Style'],
              'placement' => ['bg-amber-50 text-amber-800', 'Placement'],
              'sizes' => ['bg-cyan-50 text-cyan-800', 'Sizes'],
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
            data-question-semantic-type="{{ $question->question_type ?? 'other' }}"
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
              @elseif($type === 'sizes')
                <p class="text-xs text-outline mt-0.5 js-question-sub">Uses Admin → Sizes (cm/in from artist preference)</p>
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
      @endif

      @if($formsTab === 'custom')
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
              'style' => ['bg-rose-50 text-rose-800', 'Style'],
              'placement' => ['bg-amber-50 text-amber-800', 'Placement'],
              'sizes' => ['bg-cyan-50 text-cyan-800', 'Sizes'],
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
            data-question-semantic-type="{{ $question->question_type ?? 'other' }}"
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
              @elseif($type === 'sizes')
                <p class="text-xs text-outline mt-0.5 js-question-sub">Uses Admin → Sizes (cm/in from artist preference)</p>
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
      @endif

      @if($formsTab === 'consent')
      @php
        $consentTypeBadge = [
          'health' => ['consent-type-badge-health', 'Health'],
          'risk' => ['consent-type-badge-risk', 'Risk'],
          'aftercare' => ['consent-type-badge-aftercare', 'Aftercare'],
        ];
      @endphp
      <!-- Section 3: Consent questions -->
      <div class="bg-white rounded-2xl shadow-sm border border-outline-variant/20 mb-8 overflow-hidden">
        <div class="px-6 py-5 border-b border-outline-variant/15">
          <h3 class="text-lg font-bold text-on-surface">Consent form questions</h3>
          <p class="text-xs text-on-surface-variant mt-1">Default health, risk, and aftercare questions for artist consent forms.</p>
        </div>
        <div class="divide-y divide-outline-variant/10" id="consentQuestions">
          @forelse($consentQuestions ?? [] as $question)
            @php
              $translations = is_array($question->translations) ? $question->translations : [];
              $en = (string) ($translations['en'] ?? '');
              $otherCount = count(array_filter($translations, fn ($v, $k) => $k !== 'en' && filled($v), ARRAY_FILTER_USE_BOTH));
              $badge = $consentTypeBadge[$question->question_type] ?? ['bg-gray-100 text-gray-700', ucfirst($question->question_type)];
            @endphp
            <div
              class="question-row px-6 py-4 flex items-center gap-4"
              data-consent-id="{{ $question->id }}"
              data-question-type="{{ $question->question_type }}"
              data-enabled="{{ $question->enabled ? '1' : '0' }}"
              data-translations='@json($translations)'
            >
              <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-on-surface">{{ $en !== '' ? $en : 'Untitled question' }}</p>
                <p class="text-xs text-outline mt-0.5">
                  @if($otherCount > 0)
                    {{ $otherCount }} translation{{ $otherCount === 1 ? '' : 's' }} filled
                  @else
                    English only
                  @endif
                </p>
              </div>
              <span class="text-[10px] font-semibold px-2.5 py-0.5 rounded-full shrink-0 {{ $badge[0] }}">{{ $badge[1] }}</span>
              <div class="flex flex-col items-center gap-1 shrink-0">
                <button
                  type="button"
                  class="js-consent-enabled-toggle toggle-switch {{ $question->enabled ? 'active' : '' }}"
                  role="switch"
                  aria-checked="{{ $question->enabled ? 'true' : 'false' }}"
                  title="Enable/Disable"
                  aria-label="Toggle active"
                ></button>
                <span class="js-consent-enabled-label text-[10px] font-medium {{ $question->enabled ? 'text-emerald-700' : 'text-gray-500' }}">
                  {{ $question->enabled ? 'Active' : 'Inactive' }}
                </span>
              </div>
              <div class="flex gap-1">
                <button type="button" class="js-edit-consent-question w-7 h-7 rounded-lg flex items-center justify-center hover:bg-surface-container-low" aria-label="Edit">
                  <span class="material-symbols-outlined text-on-surface-variant" style="font-size:16px;">edit</span>
                </button>
                <button type="button" class="js-remove-consent-question w-7 h-7 rounded-lg flex items-center justify-center hover:bg-red-50" aria-label="Delete">
                  <span class="material-symbols-outlined text-red-500" style="font-size:16px;">delete</span>
                </button>
              </div>
            </div>
          @empty
            <p class="js-consent-empty-msg px-6 py-8 text-sm text-on-surface-variant text-center">No consent questions yet. Add one to get started.</p>
          @endforelse
        </div>
        <div class="px-6 py-4 border-t border-outline-variant/15">
          <button type="button" id="btnAddConsentQuestion" class="inline-flex items-center gap-2 bg-primary text-white px-4 py-2 rounded-xl font-semibold text-xs hover:bg-primary-container transition-colors">
            <span class="material-symbols-outlined" style="font-size:16px;">add</span> Add Question
          </button>
        </div>
      </div>
      @endif

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
          <label for="newQuestionSemanticType" class="block text-xs font-semibold text-on-surface-variant mb-1.5">Question type <span class="text-error">*</span></label>
          <select id="newQuestionSemanticType" name="newQuestionSemanticType" required class="w-full text-sm border border-outline-variant/30 rounded-xl px-3 py-2.5 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
            <option value="description">Description</option>
            <option value="style">Style</option>
            <option value="color">Color</option>
            <option value="size">Size</option>
            <option value="placement">Placement</option>
            <option value="reference_image">Reference image</option>
            <option value="placement_photo">Placement photo</option>
            <option value="coverup_photo">Cover-up photo</option>
            <option value="other" selected>Other</option>
          </select>
          <p id="newQuestionSemanticTypeError" class="hidden text-sm text-error mt-1"></p>
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
            <option value="style">Style (tattoo style selection)</option>
            <option value="placement">Placement (body placement selection)</option>
            <option value="sizes">Sizes (catalog size ranges; cm/in by artist unit)</option>
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

<!-- Consent question modal -->
<div class="modal-backdrop" id="consentQuestionModal" aria-hidden="true">
  <div class="add-question-modal-inner bg-white rounded-2xl w-full max-w-lg mx-4 shadow-2xl max-h-[90vh] overflow-y-auto">
    <div class="flex items-center justify-between px-5 py-4 border-b border-outline-variant/15">
      <h3 id="consentQuestionModalTitle" class="text-lg font-bold text-on-surface">Add consent question</h3>
      <button type="button" id="btnCloseConsentQuestionModal" class="w-9 h-9 rounded-xl flex items-center justify-center hover:bg-surface-container-low transition-colors" aria-label="Close">
        <span class="material-symbols-outlined text-on-surface-variant">close</span>
      </button>
    </div>
    <div class="p-5 space-y-4">
      <input type="hidden" id="editingConsentQuestionId" value="">
      <p id="consentQuestionGeneralError" class="hidden text-sm text-error rounded-xl bg-error-container/30 border border-error/20 px-3 py-2"></p>
      <p class="text-xs text-on-surface-variant rounded-xl bg-surface-container-low/60 border border-outline-variant/20 px-3 py-2">
        Enter the English question, then add translations for other languages below as needed.
      </p>
      <div>
        <label for="consentQuestionType" class="block text-xs font-semibold text-on-surface-variant mb-1.5">Question type</label>
        <select id="consentQuestionType" class="w-full text-sm border border-outline-variant/30 rounded-xl px-3 py-2.5 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
          <option value="health">Health</option>
          <option value="risk">Risk</option>
          <option value="aftercare">Aftercare</option>
        </select>
        <p id="consentQuestionTypeError" class="hidden text-sm text-error mt-1"></p>
      </div>
      <div>
        <label for="consentQuestionTextEn" class="block text-xs font-semibold text-on-surface-variant mb-1.5">Question (English)</label>
        <textarea id="consentQuestionTextEn" rows="3" placeholder="e.g. Diabetes (Type 1 or Type 2)" class="w-full text-sm border border-outline-variant/30 rounded-xl px-3 py-2.5 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30 resize-none"></textarea>
        <p id="consentQuestionTextEnError" class="hidden text-sm text-error mt-1"></p>
      </div>
      <div>
        <div class="flex items-center justify-between gap-2 mb-1.5">
          <label class="block text-xs font-semibold text-on-surface-variant">Other languages</label>
          <span class="text-[10px] font-medium text-on-surface-variant uppercase tracking-wide">Scroll for all</span>
        </div>
        <div id="consentLangFields" class="consent-lang-scroll" aria-label="Translations for other languages"></div>
      </div>
      <div class="flex items-center justify-between sm:justify-start sm:gap-3 pt-1">
        <span class="text-sm text-on-surface">Available</span>
        <button type="button" id="consentQuestionEnabledToggle" class="toggle-switch active" role="switch" aria-checked="true" aria-label="Toggle available"></button>
        <input type="hidden" id="consentQuestionEnabled" value="true">
      </div>
    </div>
    <div class="px-5 py-4 border-t border-outline-variant/15 flex items-center justify-end gap-3">
      <button type="button" id="btnCancelConsentQuestionModal" class="text-sm font-semibold text-on-surface-variant hover:text-on-surface px-4 py-2 rounded-xl transition-colors">Cancel</button>
      <button type="button" id="btnSubmitConsentQuestion" class="bg-primary text-white px-5 py-2.5 rounded-xl font-semibold text-sm hover:bg-primary-container transition-colors shadow-sm inline-flex items-center gap-2">
        <span id="btnSubmitConsentQuestionIcon" class="material-symbols-outlined text-lg">add</span>
        <span id="btnSubmitConsentQuestionText">Add question</span>
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
  const CONSENT_STORE_URL = @json(route('admin.forms.consent-questions.store'));
  const CONSENT_UPDATE_URL_TEMPLATE = @json(route('admin.forms.consent-questions.update', ['id' => '__ID__']));
  const CONSENT_STATUS_URL_TEMPLATE = @json(route('admin.forms.consent-questions.status', ['id' => '__ID__']));
  const CONSENT_DELETE_URL_TEMPLATE = @json(route('admin.forms.consent-questions.destroy', ['id' => '__ID__']));
  // ===== Cached DOM references =====
  const $addQuestionModal = $("#addQuestionModal");
  const $deleteQuestionModal = $("#deleteQuestionModal");
  const $newQuestionType = $("#newQuestionType");
  const $addOptionsDiv = $(".add-options-div");
  let $pendingDeleteRow = null;
  let $pendingConsentDeleteRow = null;

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
    $pendingConsentDeleteRow = null;
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
      $pendingConsentDeleteRow = null;
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
    clearFieldError($("#newQuestionSemanticType"), $("#newQuestionSemanticTypeError"));
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
    $("#newQuestionSemanticType").val("other");
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
    const questionType = $row.data("question-semantic-type") || "other";
    const formContext = $row.data("form-context") || "default";
    const isRequired = String($row.data("is-required")) === "1";
    const isActive = String($row.data("is-active")) === "1";
    const options = $row.data("options");
    const optionValues = Array.isArray(options) ? options : [];

    $("#editingQuestionId").val(id);
    $("#newQuestionText").val(text);
    $("#newQuestionDescription").val(description);
    $("#newQuestionPlaceholder").val(placeholder);
    $("#newQuestionSemanticType").val(questionType);
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
    $pendingConsentDeleteRow = null;
    openDeleteQuestionModal($row);
  });

  $("#btnConfirmDeleteQuestion").on("click", function () {
    // Consent form question delete
    if ($pendingConsentDeleteRow && $pendingConsentDeleteRow.length) {
      const consentId = $pendingConsentDeleteRow.data("consent-id");
      if (!consentId) {
        $("#deleteQuestionError").removeClass("hidden").text("Could not determine question id.");
        return;
      }

      const $consentBtn = $(this);
      const consentOriginal = $consentBtn.html();
      $consentBtn.prop("disabled", true).html("Deleting...");

      $.ajax({
        url: CONSENT_DELETE_URL_TEMPLATE.replace("__ID__", String(consentId)),
        method: "DELETE",
        headers: {
          "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
          "Accept": "application/json",
          "X-Requested-With": "XMLHttpRequest"
        }
      }).done(function () {
        const $list = $("#consentQuestions");
        $pendingConsentDeleteRow.remove();
        $pendingConsentDeleteRow = null;
        if ($list.length && !$list.find(".question-row").length) {
          $list.append('<p class="js-consent-empty-msg px-6 py-8 text-sm text-on-surface-variant text-center">No consent questions yet. Add one to get started.</p>');
        }
        closeDeleteQuestionModal();
      }).fail(function (xhr) {
        const msg = (xhr.responseJSON && xhr.responseJSON.message) || "Something went wrong while deleting.";
        $("#deleteQuestionError").removeClass("hidden").text(msg);
      }).always(function () {
        $consentBtn.prop("disabled", false).html(consentOriginal);
      });
      return;
    }

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
      question_type: $("#newQuestionSemanticType").val() || "other",
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
        const tab = ($("#form-context").val() === "custom") ? "custom" : "booking";
        window.location.href = @json(route('admin.forms.index')) + "?tab=" + tab;
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
        if (serverErrors.question_type && serverErrors.question_type[0]) {
          setFieldError($("#newQuestionSemanticType"), $("#newQuestionSemanticTypeError"), serverErrors.question_type[0]);
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

  // ===== Consent questions (DB-backed) =====
  const $consentQuestionModal = $("#consentQuestionModal");
  const consentTypeLabels = { health: "Health", risk: "Risk", aftercare: "Aftercare" };
  const CONSENT_LANGUAGE_LABELS = {
    de: "German",
    nl: "Dutch",
    fr: "French",
    es: "Spanish",
    it: "Italian",
    el: "Greek",
    pt: "Portuguese",
    sv: "Swedish",
    da: "Danish",
    fi: "Finnish",
    no: "Norwegian",
    pl: "Polish",
    cs: "Czech",
    sk: "Slovak",
    hu: "Hungarian",
    ro: "Romanian",
    bg: "Bulgarian",
    hr: "Croatian",
    sl: "Slovenian",
    et: "Estonian",
    lv: "Latvian",
    lt: "Lithuanian"
  };

  function buildConsentLangFields() {
    const $wrap = $("#consentLangFields");
    if (!$wrap.length || $wrap.children().length) return;
    Object.keys(CONSENT_LANGUAGE_LABELS).forEach(function (code) {
      const label = CONSENT_LANGUAGE_LABELS[code];
      $wrap.append(
        '<div class="consent-lang-row">' +
          '<label for="consentLang_' + code + '">' +
            '<span class="text-xs font-semibold text-on-surface">' + label + '</span>' +
            '<span class="text-[10px] font-medium uppercase tracking-wide text-on-surface-variant">' + code + "</span>" +
          "</label>" +
          '<textarea id="consentLang_' + code + '" data-locale="' + code + '" rows="2" class="js-consent-lang-field w-full text-sm border border-outline-variant/30 rounded-xl px-3 py-2 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30 resize-none" placeholder="' + label + ' translation"></textarea>' +
        "</div>"
      );
    });
  }

  function clearConsentLangFields() {
    $("#consentLangFields .js-consent-lang-field").val("");
  }

  function fillConsentLangFields(translations) {
    const map = translations && typeof translations === "object" ? translations : {};
    $("#consentLangFields .js-consent-lang-field").each(function () {
      const locale = String($(this).data("locale") || "");
      $(this).val(map[locale] ? String(map[locale]) : "");
    });
  }

  function collectConsentTranslations() {
    const translations = { en: $.trim($("#consentQuestionTextEn").val() || "") };
    $("#consentLangFields .js-consent-lang-field").each(function () {
      const locale = String($(this).data("locale") || "");
      const text = $.trim($(this).val() || "");
      if (locale && text) translations[locale] = text;
    });
    return translations;
  }

  function setConsentEnabledToggle(isEnabled) {
    $("#consentQuestionEnabled").val(isEnabled ? "true" : "false");
    $("#consentQuestionEnabledToggle")
      .toggleClass("active", !!isEnabled)
      .attr("aria-checked", isEnabled ? "true" : "false");
  }

  function resetConsentQuestionForm() {
    $("#editingConsentQuestionId").val("");
    $("#consentQuestionType").val("health");
    $("#consentQuestionTextEn").val("");
    clearConsentLangFields();
    setConsentEnabledToggle(true);
    $("#consentQuestionTypeError, #consentQuestionTextEnError").addClass("hidden").text("");
    $("#consentQuestionGeneralError").addClass("hidden").text("");
    $("#consentQuestionModalTitle").text("Add consent question");
    $("#btnSubmitConsentQuestionText").text("Add question");
    $("#btnSubmitConsentQuestionIcon").text("add");
  }

  function openConsentQuestionModal() {
    buildConsentLangFields();
    resetConsentQuestionForm();
    $consentQuestionModal.addClass("modal-visible");
    requestAnimationFrame(function () {
      $consentQuestionModal.addClass("modal-open").attr("aria-hidden", "false");
    });
  }

  function openEditConsentQuestionModal($row) {
    buildConsentLangFields();
    resetConsentQuestionForm();

    const id = $row.data("consent-id");
    const type = $row.data("question-type") || "health";
    const enabled = String($row.data("enabled")) === "1";
    let translations = $row.data("translations");
    if (typeof translations === "string") {
      try { translations = JSON.parse(translations); } catch (e) { translations = {}; }
    }
    if (!translations || typeof translations !== "object") translations = {};

    const en = String(translations.en || "");
    $("#editingConsentQuestionId").val(id);
    $("#consentQuestionType").val(type);
    $("#consentQuestionTextEn").val(en);
    fillConsentLangFields(translations);
    setConsentEnabledToggle(enabled);
    $("#consentQuestionModalTitle").text("Edit consent question");
    $("#btnSubmitConsentQuestionText").text("Update question");
    $("#btnSubmitConsentQuestionIcon").text("save");

    $consentQuestionModal.addClass("modal-visible");
    requestAnimationFrame(function () {
      $consentQuestionModal.addClass("modal-open").attr("aria-hidden", "false");
    });
  }

  function closeConsentQuestionModal() {
    $consentQuestionModal.removeClass("modal-open").attr("aria-hidden", "true");
    setTimeout(function () {
      $consentQuestionModal.removeClass("modal-visible");
    }, 280);
  }

  function escapeHtml(str) {
    return String(str)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#39;");
  }

  function consentTranslationCount(translations) {
    return Object.keys(translations || {}).filter(function (k) {
      return k !== "en" && String(translations[k] || "").trim() !== "";
    }).length;
  }

  function buildConsentRowHtml(question) {
    const type = question.question_type;
    const translations = question.translations || {};
    const en = String(translations.en || "Untitled question");
    const otherCount = consentTranslationCount(translations);
    const label = consentTypeLabels[type] || type;
    const badgeClass = "consent-type-badge-" + type;
    const enabled = !!question.enabled;
    const sub = otherCount
      ? otherCount + " translation" + (otherCount === 1 ? "" : "s") + " filled"
      : "English only";
    const status =
      '<div class="flex flex-col items-center gap-1 shrink-0">' +
        '<button type="button" class="js-consent-enabled-toggle toggle-switch' + (enabled ? ' active' : '') + '" role="switch" aria-checked="' + (enabled ? 'true' : 'false') + '" title="Enable/Disable" aria-label="Toggle active"></button>' +
        '<span class="js-consent-enabled-label text-[10px] font-medium ' + (enabled ? 'text-emerald-700' : 'text-gray-500') + '">' + (enabled ? 'Active' : 'Inactive') + '</span>' +
      '</div>';

    const $row = $(
      '<div class="question-row px-6 py-4 flex items-center gap-4">' +
        '<div class="flex-1 min-w-0">' +
          '<p class="text-sm font-semibold text-on-surface">' + escapeHtml(en) + "</p>" +
          '<p class="text-xs text-outline mt-0.5">' + escapeHtml(sub) + "</p>" +
        "</div>" +
        '<span class="text-[10px] font-semibold px-2.5 py-0.5 rounded-full shrink-0 ' + badgeClass + '">' + escapeHtml(label) + "</span>" +
        status +
        '<div class="flex gap-1">' +
          '<button type="button" class="js-edit-consent-question w-7 h-7 rounded-lg flex items-center justify-center hover:bg-surface-container-low" aria-label="Edit">' +
            '<span class="material-symbols-outlined text-on-surface-variant" style="font-size:16px;">edit</span>' +
          "</button>" +
          '<button type="button" class="js-remove-consent-question w-7 h-7 rounded-lg flex items-center justify-center hover:bg-red-50" aria-label="Delete">' +
            '<span class="material-symbols-outlined text-red-500" style="font-size:16px;">delete</span>' +
          "</button>" +
        "</div>" +
      "</div>"
    );
    $row.attr("data-consent-id", question.id);
    $row.attr("data-question-type", type);
    $row.attr("data-enabled", enabled ? "1" : "0");
    $row.attr("data-translations", JSON.stringify(translations));
    return $row;
  }

  $("#btnAddConsentQuestion").on("click", openConsentQuestionModal);
  $("#btnCloseConsentQuestionModal, #btnCancelConsentQuestionModal").on("click", closeConsentQuestionModal);
  $consentQuestionModal.on("click", function (event) {
    if (event.target === this) closeConsentQuestionModal();
  });

  $("#consentQuestionEnabledToggle").on("click", function () {
    const next = !$(this).hasClass("active");
    setConsentEnabledToggle(next);
  });

  $(document).on("click", ".js-edit-consent-question", function () {
    const $row = $(this).closest(".question-row");
    if (!$row.length) return;
    openEditConsentQuestionModal($row);
  });

  $(document).on("click", ".js-consent-enabled-toggle", function (e) {
    e.preventDefault();
    e.stopPropagation();
    const $toggle = $(this);
    const $row = $toggle.closest(".question-row");
    const id = $row.data("consent-id");
    if (!id || $toggle.data("busy")) return;

    const nextEnabled = !$toggle.hasClass("active");
    $toggle.data("busy", true);

    $.ajax({
      url: CONSENT_STATUS_URL_TEMPLATE.replace("__ID__", String(id)),
      method: "PATCH",
      data: JSON.stringify({ enabled: nextEnabled }),
      contentType: "application/json; charset=UTF-8",
      headers: {
        "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
        "Accept": "application/json",
        "X-Requested-With": "XMLHttpRequest"
      }
    }).done(function (response) {
      const enabled = !!(response && response.question ? response.question.enabled : nextEnabled);
      $toggle.toggleClass("active", enabled).attr("aria-checked", enabled ? "true" : "false");
      $row.attr("data-enabled", enabled ? "1" : "0");
      $row.find(".js-consent-enabled-label")
        .text(enabled ? "Active" : "Inactive")
        .toggleClass("text-emerald-700", enabled)
        .toggleClass("text-gray-500", !enabled);
      if (response && response.question && response.question.translations) {
        $row.attr("data-translations", JSON.stringify(response.question.translations));
      }
    }).fail(function () {
      // leave UI unchanged on failure
    }).always(function () {
      $toggle.data("busy", false);
    });
  });

  $("#btnSubmitConsentQuestion").on("click", function () {
    const type = String($("#consentQuestionType").val() || "").trim();
    const translations = collectConsentTranslations();
    const en = translations.en || "";
    const editingId = $("#editingConsentQuestionId").val();
    const isEditing = !!editingId;
    let valid = true;

    $("#consentQuestionTypeError, #consentQuestionTextEnError").addClass("hidden").text("");
    $("#consentQuestionGeneralError").addClass("hidden").text("");
    if (!["health", "risk", "aftercare"].includes(type)) {
      $("#consentQuestionTypeError").removeClass("hidden").text("Select a question type.");
      valid = false;
    }
    if (!en) {
      $("#consentQuestionTextEnError").removeClass("hidden").text("English question is required.");
      valid = false;
    }
    if (!valid) return;

    const payload = {
      question_type: type,
      translations: translations,
      enabled: $("#consentQuestionEnabled").val() === "true"
    };

    const $submitBtn = $("#btnSubmitConsentQuestion");
    const originalBtnHtml = $submitBtn.html();
    $submitBtn.prop("disabled", true).html("Saving...");

    $.ajax({
      url: isEditing ? CONSENT_UPDATE_URL_TEMPLATE.replace("__ID__", String(editingId)) : CONSENT_STORE_URL,
      method: isEditing ? "PUT" : "POST",
      data: JSON.stringify(payload),
      contentType: "application/json; charset=UTF-8",
      headers: {
        "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
        "Accept": "application/json",
        "X-Requested-With": "XMLHttpRequest"
      }
    }).done(function (response) {
      if (!(response && response.success && response.question)) {
        $("#consentQuestionGeneralError").removeClass("hidden").text("Unexpected response from server.");
        return;
      }
      const $list = $("#consentQuestions");
      $list.find(".js-consent-empty-msg").remove();
      const $row = buildConsentRowHtml(response.question);
      if (isEditing) {
        const $existing = $list.find('.question-row[data-consent-id="' + editingId + '"]');
        if ($existing.length) $existing.replaceWith($row);
        else $list.append($row);
      } else {
        $list.append($row);
      }
      closeConsentQuestionModal();
    }).fail(function (xhr) {
      if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
        const errors = xhr.responseJSON.errors;
        if (errors.question_type && errors.question_type[0]) {
          $("#consentQuestionTypeError").removeClass("hidden").text(errors.question_type[0]);
        }
        if (errors["translations.en"] && errors["translations.en"][0]) {
          $("#consentQuestionTextEnError").removeClass("hidden").text(errors["translations.en"][0]);
        }
        const first = Object.values(errors)[0];
        if (first && first[0]) {
          $("#consentQuestionGeneralError").removeClass("hidden").text(first[0]);
        }
        return;
      }
      const msg = (xhr.responseJSON && xhr.responseJSON.message) || "Something went wrong while saving.";
      $("#consentQuestionGeneralError").removeClass("hidden").text(msg);
    }).always(function () {
      $submitBtn.prop("disabled", false).html(originalBtnHtml);
    });
  });

  $(document).on("click", ".js-remove-consent-question", function () {
    const $row = $(this).closest(".question-row");
    if (!$row.length || !$row.data("consent-id")) return;
    $pendingDeleteRow = null;
    $pendingConsentDeleteRow = $row;
    $("#deleteQuestionError").addClass("hidden").text("");
    $deleteQuestionModal.addClass("modal-visible");
    requestAnimationFrame(function () {
      $deleteQuestionModal.addClass("modal-open").attr("aria-hidden", "false");
    });
  });

  // Prefetch language fields when Consent tab is open
  if ($("#btnAddConsentQuestion").length) {
    buildConsentLangFields();
  }
  </script>

@endsection