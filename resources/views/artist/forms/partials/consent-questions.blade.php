{{-- Artist consent questions — same row pattern as Forms (Available Design / Custom Request) --}}
@php
  $typeMeta = [
    'health' => [
      'label' => 'Health conditions',
      'hint' => 'Clients answer yes/no to each item. Drag to reorder.',
      'icon' => 'health_and_safety',
      'typeBadge' => 'Health',
      'typeBadgeClass' => 'badge-toggle',
      'typeIcon' => 'toggle_on',
    ],
    'risk' => [
      'label' => 'Risks & consent',
      'hint' => 'Risk acknowledgements shown on the consent form. Drag to reorder.',
      'icon' => 'gavel',
      'typeBadge' => 'Risk',
      'typeBadgeClass' => 'badge-select',
      'typeIcon' => 'list',
    ],
    'aftercare' => [
      'label' => 'Aftercare',
      'hint' => 'Aftercare steps included in the signed record. Drag to reorder.',
      'icon' => 'spa',
      'typeBadge' => 'Aftercare',
      'typeBadgeClass' => 'badge-textarea',
      'typeIcon' => 'notes',
    ],
  ];
@endphp

<div class="max-w-3xl mt-6 space-y-6" id="artistConsentQuestionsPanel">
  @foreach($typeMeta as $typeKey => $meta)
    @php $items = $consentQuestionsByType[$typeKey] ?? collect(); @endphp
    <div class="bg-white rounded-2xl shadow-sm border border-outline-variant/20 overflow-hidden" data-consent-section="{{ $typeKey }}">
      <div class="px-6 py-4 border-b border-outline-variant/15 flex items-center justify-between gap-3">
        <div class="flex items-center gap-3 min-w-0">
          <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center shrink-0">
            <span class="material-symbols-outlined text-primary" style="font-size:18px;">{{ $meta['icon'] }}</span>
          </div>
          <div class="min-w-0">
            <h3 class="font-bold text-on-surface text-sm">{{ $meta['label'] }}</h3>
            <p class="text-[11px] text-on-surface-variant">{{ $meta['hint'] }}</p>
          </div>
        </div>
        <button
          type="button"
          class="js-artist-consent-reset shrink-0 inline-flex items-center gap-1 border border-outline-variant/30 text-on-surface-variant px-3 py-1.5 rounded-xl font-semibold text-xs hover:bg-surface-container-low transition-colors"
          data-question-type="{{ $typeKey }}"
          title="Reset this section to last saved"
        >
          <span class="material-symbols-outlined" style="font-size:14px;">restart_alt</span> Reset
        </button>
      </div>
      <div class="questions-list-scroll">
        <div
          class="js-artist-consent-list questions-list"
          data-question-type="{{ $typeKey }}"
          id="artistConsentList-{{ $typeKey }}"
        >
          @forelse($items as $question)
            @php
              $en = (string) (($question['translations']['en'] ?? '') ?: 'Untitled question');
              $enabled = !empty($question['enabled']);
              $questionWords = preg_split('/\s+/u', trim($en), -1, PREG_SPLIT_NO_EMPTY) ?: [];
              $questionWrapped = implode("\n", array_map(
                static fn (array $chunk): string => implode(' ', $chunk),
                array_chunk($questionWords, 15)
              ));
            @endphp
            <div
              class="js-artist-consent-row q-row flex gap-3 px-5 py-3.5 {{ $enabled ? '' : 'disabled' }}"
              data-id="{{ $question['id'] }}"
              data-question-type="{{ $question['question_type'] }}"
              data-system="0"
              data-enabled="{{ $enabled ? '1' : '0' }}"
              data-question-en="{{ e($en) }}"
              data-translations="{{ base64_encode(json_encode($question['translations'] ?? [], JSON_UNESCAPED_UNICODE)) }}"
            >
              <span class="q-drag-handle material-symbols-outlined text-outline js-artist-consent-drag" style="font-size:18px;" title="Drag to reorder">drag_indicator</span>
              <span class="material-symbols-outlined text-on-surface-variant" style="font-size:18px;">{{ $meta['typeIcon'] }}</span>
              <div class="q-text">
                <p class="text-sm font-medium text-on-surface">{{ $questionWrapped }}</p>
              </div>
              <div class="q-meta">
                <span class="badge {{ $meta['typeBadgeClass'] }}">{{ $meta['typeBadge'] }}</span>
                <button
                  type="button"
                  class="js-artist-consent-toggle toggle-switch {{ $enabled ? 'active' : '' }}"
                  role="switch"
                  aria-checked="{{ $enabled ? 'true' : 'false' }}"
                  title="Enable/Disable"
                ></button>
                <div class="flex items-center gap-1">
                  <button type="button" class="js-artist-consent-edit w-7 h-7 rounded-lg flex items-center justify-center hover:bg-surface-container-low" title="Edit">
                    <span class="material-symbols-outlined text-on-surface-variant" style="font-size:16px;">edit</span>
                  </button>
                  <button type="button" class="js-artist-consent-delete w-7 h-7 rounded-lg flex items-center justify-center hover:bg-red-50" title="Delete">
                    <span class="material-symbols-outlined text-red-500" style="font-size:16px;">delete</span>
                  </button>
                </div>
              </div>
            </div>
          @empty
            <p class="js-artist-consent-empty px-5 py-6 text-sm text-on-surface-variant">No {{ strtolower($meta['label']) }} yet.</p>
          @endforelse
        </div>
      </div>
      <div class="px-6 py-3 border-t border-outline-variant/15">
        <button
          type="button"
          class="js-artist-consent-add w-full inline-flex items-center justify-center gap-1 bg-primary text-white px-3 py-2 rounded-xl font-semibold text-xs hover:bg-primary-container transition-colors"
          data-question-type="{{ $typeKey }}"
        >
          <span class="material-symbols-outlined" style="font-size:14px;">add</span> Add
        </button>
      </div>
    </div>
  @endforeach
</div>

{{-- Add/Edit modal --}}
<div class="modal-backdrop" id="artistConsentQuestionModal" aria-hidden="true" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.55); z-index:200; align-items:center; justify-content:center; opacity:0; transition:opacity .3s ease;">
  <div class="bg-white rounded-2xl w-full max-w-lg mx-4 shadow-2xl max-h-[90vh] overflow-y-auto" style="transform:scale(.96) translateY(10px); opacity:0; transition:transform .32s cubic-bezier(.22,1,.36,1),opacity .28s ease;">
    <div class="flex items-center justify-between px-5 py-4 border-b border-outline-variant/15">
      <h3 id="artistConsentModalTitle" class="text-lg font-bold text-on-surface">Add consent question</h3>
      <button type="button" id="btnCloseArtistConsentModal" class="w-9 h-9 rounded-xl flex items-center justify-center hover:bg-surface-container-low" aria-label="Close">
        <span class="material-symbols-outlined text-on-surface-variant">close</span>
      </button>
    </div>
    <div class="p-5 space-y-4">
      <input type="hidden" id="artistConsentEditingId" value="">
      <p id="artistConsentGeneralError" class="hidden text-sm text-error rounded-xl bg-error-container/30 border border-error/20 px-3 py-2"></p>
      <div>
        <label class="block text-xs font-semibold text-on-surface-variant mb-1.5">Question type</label>
        <select id="artistConsentType" class="w-full text-sm border border-outline-variant/30 rounded-xl px-3 py-2.5 bg-white">
          <option value="health">Health</option>
          <option value="risk">Risk</option>
          <option value="aftercare">Aftercare</option>
        </select>
      </div>
      <div>
        <label class="block text-xs font-semibold text-on-surface-variant mb-1.5">Question (English)</label>
        <textarea id="artistConsentTextEn" rows="3" class="w-full text-sm border border-outline-variant/30 rounded-xl px-3 py-2.5 resize-none" placeholder="Enter question in English"></textarea>
        <p id="artistConsentTextEnError" class="hidden text-sm text-error mt-1"></p>
      </div>
      <div id="artistConsentLangSection">
        <div class="flex items-center justify-between mb-1.5">
          <label id="artistConsentLangSectionLabel" class="text-xs font-semibold text-on-surface-variant">Studio language</label>
          <span id="artistConsentLangSectionCode" class="text-[10px] font-medium text-on-surface-variant uppercase tracking-wide"></span>
        </div>
        <div id="artistConsentLangFields" style="max-height:220px; overflow-y:auto; border:1px solid rgba(202,196,211,.35); border-radius:.75rem; background:#faf8fc;"></div>
      </div>
      <div class="flex items-center gap-3">
        <span class="text-sm text-on-surface">Available</span>
        <button type="button" id="artistConsentEnabledToggle" class="toggle-switch active" role="switch" aria-checked="true"></button>
        <input type="hidden" id="artistConsentEnabled" value="true">
      </div>
    </div>
    <div class="px-5 py-4 border-t border-outline-variant/15 flex justify-end gap-3">
      <button type="button" id="btnCancelArtistConsentModal" class="text-sm font-semibold text-on-surface-variant px-4 py-2 rounded-xl">Cancel</button>
      <button type="button" id="btnSaveArtistConsent" class="bg-primary text-white px-5 py-2.5 rounded-xl font-semibold text-sm inline-flex items-center gap-2">
        <span class="material-symbols-outlined text-lg" id="btnSaveArtistConsentIcon">add</span>
        <span id="btnSaveArtistConsentText">Add question</span>
      </button>
    </div>
  </div>
</div>

{{-- Delete confirmation modal --}}
<div class="modal-backdrop" id="artistConsentDeleteModal" aria-hidden="true" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.55); z-index:210; align-items:center; justify-content:center; opacity:0; transition:opacity .3s ease;">
  <div class="bg-white rounded-2xl w-full max-w-md mx-4 shadow-2xl" style="transform:scale(.96) translateY(10px); opacity:0; transition:transform .32s cubic-bezier(.22,1,.36,1),opacity .28s ease;">
    <div class="flex items-center justify-between px-5 py-4 border-b border-outline-variant/15">
      <h3 class="text-lg font-bold text-on-surface">Delete question</h3>
      <button type="button" id="btnCloseArtistConsentDeleteModal" class="w-9 h-9 rounded-xl flex items-center justify-center hover:bg-surface-container-low transition-colors" aria-label="Close">
        <span class="material-symbols-outlined text-on-surface-variant">close</span>
      </button>
    </div>
    <div class="p-5 space-y-2">
      <p class="text-sm text-on-surface">Are you sure you want to delete this question?</p>
      <p class="text-xs text-on-surface-variant">This action cannot be undone.</p>
      <p id="artistConsentDeleteError" class="hidden text-sm text-error rounded-xl bg-error-container/30 border border-error/20 px-3 py-2"></p>
    </div>
    <div class="px-5 py-4 border-t border-outline-variant/15 flex items-center justify-end gap-3">
      <button type="button" id="btnCancelArtistConsentDelete" class="text-sm font-semibold text-on-surface-variant hover:text-on-surface px-4 py-2 rounded-xl transition-colors">Cancel</button>
      <button type="button" id="btnConfirmArtistConsentDelete" class="bg-red-600 text-white px-5 py-2.5 rounded-xl font-semibold text-sm hover:bg-red-700 transition-colors shadow-sm inline-flex items-center gap-2">
        <span class="material-symbols-outlined text-lg">delete</span>
        Delete
      </button>
    </div>
  </div>
</div>
