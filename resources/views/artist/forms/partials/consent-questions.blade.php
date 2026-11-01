{{-- Artist consent questions — Client Flow / Bookpay styling --}}
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

<div id="artistConsentQuestionsPanel" style="margin-top:4px">
  @foreach($typeMeta as $typeKey => $meta)
    @php $items = $consentQuestionsByType[$typeKey] ?? collect(); @endphp
    <div class="card sec" data-consent-section="{{ $typeKey }}" style="margin-bottom:14px">
      <div class="sech">
        <div class="ic"><span class="ms">{{ $meta['icon'] }}</span></div>
        <div style="flex:1;min-width:0">
          <h3>{{ $meta['label'] }}</h3>
          <div class="faint">{{ $meta['hint'] }}</div>
        </div>
        <button
          type="button"
          class="js-artist-consent-reset"
          data-question-type="{{ $typeKey }}"
          title="Reset this section to last saved"
        >
          <span class="ms" style="font-size:16px">restart_alt</span> Reset
        </button>
      </div>

      <div
        class="js-artist-consent-list questions-list"
        data-question-type="{{ $typeKey }}"
        id="artistConsentList-{{ $typeKey }}"
      >
        @forelse($items as $question)
          @php
            $en = (string) (($question['translations']['en'] ?? '') ?: 'Untitled question');
            $enabled = !empty($question['enabled']);
          @endphp
          <div
            class="js-artist-consent-row q-row {{ $enabled ? '' : 'disabled' }}"
            data-id="{{ $question['id'] }}"
            data-question-type="{{ $question['question_type'] }}"
            data-system="0"
            data-enabled="{{ $enabled ? '1' : '0' }}"
            data-question-en="{{ e($en) }}"
            data-translations="{{ base64_encode(json_encode($question['translations'] ?? [], JSON_UNESCAPED_UNICODE)) }}"
          >
            <span class="ms faint q-drag-handle js-artist-consent-drag" title="Drag to reorder">drag_indicator</span>
            <span class="ms faint" style="font-size:18px;flex-shrink:0">{{ $meta['typeIcon'] }}</span>
            <div class="q-text">
              <p>{{ $en }}</p>
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
              <button type="button" class="cf-icon-btn js-artist-consent-edit" title="Edit" aria-label="Edit">
                <span class="ms">edit</span>
              </button>
              <button type="button" class="cf-icon-btn del js-artist-consent-delete" title="Delete" aria-label="Delete">
                <span class="ms">delete</span>
              </button>
            </div>
          </div>
        @empty
          <p class="js-artist-consent-empty faint" style="padding:22px 20px;text-align:center;font-size:13px;margin:0">No {{ strtolower($meta['label']) }} yet.</p>
        @endforelse
      </div>

      <button
        type="button"
        class="js-artist-consent-add"
        data-question-type="{{ $typeKey }}"
      >
        <span class="ms" style="font-size:18px">add</span> Add
      </button>
    </div>
  @endforeach
</div>

{{-- Add/Edit modal --}}
<div class="modal-backdrop" id="artistConsentQuestionModal" aria-hidden="true" style="display:none;opacity:0">
  <div class="cs-modal-panel" style="transform:scale(.96) translateY(10px);opacity:0;transition:transform .32s cubic-bezier(.22,1,.36,1),opacity .28s ease;max-width:520px">
    <div class="row" style="justify-content:space-between;padding:18px 22px 14px;border-bottom:1px solid #F0EAF2">
      <h3 id="artistConsentModalTitle" style="font-size:18px;font-weight:800;margin:0">Add consent question</h3>
      <button type="button" id="btnCloseArtistConsentModal" class="cf-icon-btn" aria-label="Close"><span class="ms">close</span></button>
    </div>
    <div style="padding:18px 22px">
      <input type="hidden" id="artistConsentEditingId" value="">
      <p id="artistConsentGeneralError" class="field-err hidden"></p>

      <label class="fl" for="artistConsentType">Question type</label>
      <select id="artistConsentType" class="in">
        <option value="health">Health</option>
        <option value="risk">Risk</option>
        <option value="aftercare">Aftercare</option>
      </select>

      <label class="fl" for="artistConsentTextEn" style="margin-top:12px;display:block">Question (English)</label>
      <textarea id="artistConsentTextEn" class="in" rows="3" placeholder="Enter question in English"></textarea>
      <p id="artistConsentTextEnError" class="field-err hidden"></p>

      <div id="artistConsentLangSection" style="margin-top:12px">
        <div class="row" style="justify-content:space-between;margin-bottom:6px">
          <label id="artistConsentLangSectionLabel" class="fl" style="margin:0">Studio language</label>
          <span id="artistConsentLangSectionCode" style="font-size:11px;font-weight:600;color:var(--muted);text-transform:uppercase"></span>
        </div>
        <div id="artistConsentLangFields" style="max-height:220px;overflow-y:auto;border:1px solid var(--line);border-radius:12px;background:#FBF7FC"></div>
      </div>

      <div class="row" style="gap:10px;margin-top:16px;align-items:center">
        <span style="font-size:13.5px">Available</span>
        <button type="button" id="artistConsentEnabledToggle" class="toggle-switch active" role="switch" aria-checked="true"></button>
        <input type="hidden" id="artistConsentEnabled" value="true">
      </div>
    </div>
    <div class="row" style="justify-content:flex-end;gap:10px;padding:14px 22px;border-top:1px solid #F0E9F2">
      <button type="button" class="btn ghost" id="btnCancelArtistConsentModal">Cancel</button>
      <button type="button" class="btn" id="btnSaveArtistConsent">
        <span class="ms" id="btnSaveArtistConsentIcon">add</span>
        <span id="btnSaveArtistConsentText">Add question</span>
      </button>
    </div>
  </div>
</div>

{{-- Delete confirmation --}}
<div class="modal-backdrop" id="artistConsentDeleteModal" aria-hidden="true" style="display:none;opacity:0;z-index:310">
  <div class="cs-modal-panel" style="transform:scale(.96) translateY(10px);opacity:0;transition:transform .32s cubic-bezier(.22,1,.36,1),opacity .28s ease;max-width:420px">
    <div class="row" style="justify-content:space-between;padding:18px 22px 14px;border-bottom:1px solid #F0EAF2">
      <h3 style="font-size:18px;font-weight:800;margin:0">Delete question</h3>
      <button type="button" id="btnCloseArtistConsentDeleteModal" class="cf-icon-btn" aria-label="Close"><span class="ms">close</span></button>
    </div>
    <div style="padding:18px 22px">
      <p style="margin:0;font-size:13.5px;line-height:1.5">Are you sure you want to delete this question?</p>
      <p class="faint" style="font-size:12.5px;margin:8px 0 0">This action cannot be undone.</p>
      <p id="artistConsentDeleteError" class="field-err hidden"></p>
    </div>
    <div class="row" style="justify-content:flex-end;gap:10px;padding:14px 22px;border-top:1px solid #F0E9F2">
      <button type="button" class="btn ghost" id="btnCancelArtistConsentDelete">Cancel</button>
      <button type="button" class="btn" id="btnConfirmArtistConsentDelete" style="background:#C62828">
        <span class="ms">delete</span>Delete
      </button>
    </div>
  </div>
</div>
