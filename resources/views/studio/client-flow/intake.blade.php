@extends('layouts.studio-dashboard-layout')

@section('title', 'Client Flow · Intake form')

@push('styles')
<style>
  .cf-page .tabs a.is-soon{opacity:.55;cursor:default}
  .cf-page .cfrow{display:flex;align-items:center;gap:12px;padding:11px 18px;border-bottom:1px solid #F3EEF4}
  .cf-page .cfrow.off{opacity:.55}
  .cf-page .cfrow .qt{flex:1;font-size:13.5px;min-width:0;line-height:1.4}
  .cf-page .cfrow .qt[role="button"]{cursor:pointer}
  .cf-page .cfrow .qt[role="button"]:hover{text-decoration:underline}
  .cf-page .cfrow .drag{cursor:grab;flex-shrink:0}
  .cf-page .cfrow .cf-actions{display:flex;align-items:center;gap:2px;flex-shrink:0}
  .cf-page .cfrow .cf-icon-btn{width:28px;height:28px;border:0;background:none;border-radius:8px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;color:var(--faint);padding:0}
  .cf-page .cfrow .cf-icon-btn .ms{font-size:18px}
  .cf-page .cfrow .cf-icon-btn:hover{background:#F7F3F9;color:var(--ink)}
  .cf-page .cfrow .cf-icon-btn.del:hover{background:#FDECEC;color:#C62828}
  .cf-page .cfrow.sortable-ghost{opacity:.45;background:#FBF7FC}
  .cf-page .cfrow.sortable-chosen{cursor:grabbing}
  .cf-page .cf-fixed{display:flex;align-items:center;gap:12px;padding:11px 18px;border-bottom:1px solid #F3EEF4;background:#FBF7FC}
  .cf-page .tog{width:38px;height:22px;border-radius:12px;background:#D9D2DD;position:relative;flex-shrink:0;cursor:pointer;border:0}
  .cf-page .tog:after{content:'';position:absolute;width:16px;height:16px;border-radius:50%;background:#fff;top:3px;left:3px;transition:left .15s}
  .cf-page .tog.on,.cf-page .tog.active{background:var(--pri)}
  .cf-page .tog.on:after,.cf-page .tog.active:after{left:19px}
  .cf-page .modal-backdrop{display:none;position:fixed;inset:0;background:rgba(26,26,26,.45);z-index:300;align-items:center;justify-content:center;padding:24px}
  .cf-page .modal-backdrop.open{display:flex}
  .cf-page .cfm{background:#fff;border-radius:16px;border:1px solid var(--line);width:100%;max-width:520px;max-height:calc(100vh - 48px);display:flex;flex-direction:column;box-shadow:0 18px 50px rgba(0,0,0,.18)}
  .cf-page .cfm.sm{max-width:420px}
  .cf-page .cfmh{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:18px 22px 14px;border-bottom:1px solid #F0EAF2;flex-shrink:0}
  .cf-page .cfmh h3{font-size:18px;font-weight:800;margin:0}
  .cf-page .cfmh .x{cursor:pointer;color:var(--faint);font-size:22px}
  .cf-page .cfmb{padding:18px 22px;overflow-y:auto;min-height:0;flex:1}
  .cf-page .cfmf{display:flex;justify-content:flex-end;align-items:center;gap:10px;padding:14px 22px;border-top:1px solid #F0E9F2;flex-shrink:0}
  .cf-page .cfmb .fl{margin-top:12px;display:block}
  .cf-page .cfmb .fl:first-child{margin-top:0}
  .cf-page .cfmb textarea.in{min-height:72px;resize:vertical;width:100%;font:inherit;font-size:13.5px}
  .cf-page .cfmb select.in{width:100%;font:inherit;font-size:13.5px;color:var(--ink);appearance:auto}
  .cf-page .field-err{color:#C62828;font-size:12px;margin-top:6px}
  .cf-page .field-err.hidden,.cf-page .hidden{display:none!important}
  .cf-page .cfopt{display:flex;gap:8px;align-items:center;margin-top:6px;flex-wrap:wrap}
  .cf-page .cfopt input{flex:1;min-width:120px}
  .cf-page .cfopt .ms{cursor:pointer;color:var(--faint)}
  .cf-page .cfadd{display:inline-flex;gap:6px;align-items:center;font-weight:600;font-size:13px;margin-top:8px;cursor:pointer;color:var(--pri)}
  .cf-page .mp-toast{position:fixed;top:22px;right:24px;background:#1A1A1A;color:#fff;padding:12px 18px;border-radius:10px;font-weight:600;font-size:14px;z-index:400;box-shadow:0 10px 30px rgba(0,0,0,.22);display:flex;gap:10px;align-items:center;opacity:0;transform:translateX(calc(100% + 40px));transition:opacity .25s,transform .35s;pointer-events:none}
  .cf-page .mp-toast.on{opacity:1;transform:none}
  .cf-page .mp-toast .ms{color:#3DD68C;font-size:20px}
  .cf-page .card{max-width:820px}
  .cf-page .pill.nd:before{display:none}
  .cf-page .pill.b{background:#E8F0FF;color:#1E5BD8}
  .cf-page .btns{display:flex;gap:10px;flex-wrap:wrap;align-items:center}
  .cf-page .chips{display:flex;gap:8px;flex-wrap:wrap}
  .cf-page .chip{border:1px solid var(--line);background:#fff;border-radius:20px;padding:6px 13px;font-size:12.5px;font-weight:600;color:var(--muted)}
  @media (max-width:900px){.cf-page .mp-toast{left:16px;right:16px;top:74px}}
</style>
@endpush

@section('content')
<div class="cf-page">
  <div class="head">
    <div>
      <h1>
        Client Flow
        <a class="help-q" href="https://help.inkjin.com/en/articles/17338864-studio-dashboard-studio-client-flow-intake-form" target="_blank" rel="noopener" title="Help with this page" aria-label="Help with this page"><span class="ms">help</span></a>
      </h1>
      <div class="sub">Forms clients get before sessions at your studio.</div>
    </div>
  </div>

  @include('studio.client-flow.partials.tabs', ['clientFlowTab' => 'intake'])

  <div class="note" style="margin-bottom:14px;max-width:820px">
    <span class="ms">info</span>
    Asked when a client uses “Book with us” on your studio page. Requests sent to one of your artists directly use that artist’s own forms.
  </div>

  <div class="card" id="cfcard">
    <div class="ch">
      <div>
        <h3>Studio request form</h3>
        <div class="faint" style="font-size:12.5px;margin-top:2px">Asked when a client uses Book with us on your studio page · drag to reorder</div>
      </div>
      <div class="btns">
        <button type="button" class="btn sm ghost" id="btnPreviewForm"><span class="ms" style="font-size:16px">visibility</span>Preview</button>
        <button type="button" class="btn sm" id="btnOpenAddQuestionModal"><span class="ms" style="font-size:16px">add</span>Add question</button>
      </div>
    </div>

    <div class="cf-fixed">
      <span class="ms faint" title="Always first">lock</span>
      <span style="flex:1;font-size:13.5px">
        Which artist?
        <div class="faint" style="font-size:12px">Any available artist, or one of your artists. Always the first question.</div>
      </span>
      <span class="pill nd b">Select</span>
      <span class="pill nd k" style="font-size:10.5px">FIXED</span>
    </div>

    <div id="studioQuestionsList" class="questions-list">
      @forelse ($questions as $question)
        @include('artist.forms.partials.intake-question-row', [
          'question' => $question,
          'formContext' => 'custom',
          'system_questions_ids' => $system_questions_ids ?? [],
        ])
      @empty
        <p class="cf-empty faint" style="padding:26px 18px;text-align:center;font-size:13px">No questions yet. Add your first one.</p>
      @endforelse
    </div>
  </div>

  <div class="modal-backdrop" id="addQuestionModal" aria-hidden="true">
    <div class="cfm" role="dialog" aria-modal="true" aria-labelledby="addQuestionModalTitle">
      <div class="cfmh">
        <h3 id="addQuestionModalTitle">Add question</h3>
        <span class="ms x" id="btnCloseAddQuestionModal" aria-label="Close">close</span>
      </div>
      <div class="cfmb">
        <form id="addQuestionForm">
          <input type="hidden" id="editingQuestionId" value="">
          <input type="hidden" id="form-context" name="form_context" value="custom">
          <p id="addQuestionGeneralError" class="field-err hidden"></p>

          <label class="fl" for="newQuestionText">Question</label>
          <input class="in" type="text" id="newQuestionText" name="newQuestionText" placeholder="e.g., Preferred session length">
          <p id="newQuestionTextError" class="field-err hidden"></p>

          <label class="fl" for="newQuestionDescription">Description (optional)</label>
          <textarea class="in" id="newQuestionDescription" name="newQuestionDescription" placeholder="Add helper text for clients (optional)"></textarea>
          <p id="newQuestionDescriptionError" class="field-err hidden"></p>

          <label class="fl" for="newQuestionPlaceholder">Placeholder (optional)</label>
          <input class="in" type="text" id="newQuestionPlaceholder" name="newQuestionPlaceholder" placeholder="e.g., Tell us your tattoo idea...">
          <p id="newQuestionPlaceholderError" class="field-err hidden"></p>

          <label class="fl" for="newQuestionSemanticType">Question type <span style="color:#C62828">*</span></label>
          <select class="in" id="newQuestionSemanticType" name="newQuestionSemanticType" required>
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
          <p id="newQuestionSemanticTypeError" class="field-err hidden"></p>

          <label class="fl" for="newQuestionType">Answer type</label>
          <select class="in" id="newQuestionType" name="newQuestionType">
            <option value="" selected disabled>Select answer type</option>
            <option value="input">Input (a single-line field for short answers)</option>
            <option value="textarea">Textarea (a multi-line field for longer responses)</option>
            <option value="select">Select (a list of options where the user picks one)</option>
            <option value="toggle">Toggle (a yes/no question)</option>
            <option value="images">Images (a field for uploading images)</option>
            <option value="radio">Radio (a list of options where the user picks only one)</option>
          </select>
          <p id="newQuestionTypeError" class="field-err hidden"></p>

          <div class="add-options-div"></div>

          <div class="row" style="gap:26px;margin-top:18px">
            <label class="row" style="gap:10px;font-size:13.5px;cursor:pointer">
              Required
              <button type="button" id="newQuestionRequiredToggle" class="tog on active" role="switch" aria-checked="true" aria-label="Toggle required"></button>
              <input type="hidden" id="newQuestionRequired" name="newQuestionRequired" value="true">
            </label>
            <label class="row" style="gap:10px;font-size:13.5px;cursor:pointer">
              Available
              <button type="button" id="newQuestionAvailableToggle" class="tog on active" role="switch" aria-checked="true" aria-label="Toggle available"></button>
              <input type="hidden" id="newQuestionAvailable" name="newQuestionAvailable" value="true">
            </label>
          </div>
        </form>
      </div>
      <div class="cfmf">
        <button type="button" class="btn ghost" id="btnCancelAddQuestionModal">Cancel</button>
        <button type="button" class="btn" id="btnSubmitAddQuestion">
          <span class="ms" id="btnSubmitAddQuestionIcon">add</span>
          <span id="btnSubmitAddQuestionText">Add question</span>
        </button>
      </div>
    </div>
  </div>

  <div class="modal-backdrop" id="deleteQuestionModal" aria-hidden="true">
    <div class="cfm sm" role="dialog" aria-modal="true">
      <div class="cfmh">
        <h3>Delete question</h3>
        <span class="ms x" id="btnCloseDeleteQuestionModal" aria-label="Close">close</span>
      </div>
      <div class="cfmb">
        <p style="margin:0;font-size:13.5px;line-height:1.5">Are you sure you want to delete this question?</p>
        <p class="faint" style="font-size:12.5px;margin:8px 0 0">This action cannot be undone.</p>
        <p id="deleteQuestionError" class="field-err hidden"></p>
      </div>
      <div class="cfmf">
        <button type="button" class="btn ghost" id="btnCancelDeleteQuestionModal">Cancel</button>
        <button type="button" class="btn" id="btnConfirmDeleteQuestion" style="background:#C62828">
          <span class="ms">delete</span>Delete
        </button>
      </div>
    </div>
  </div>

  <div class="modal-backdrop" id="previewModal" aria-hidden="true">
    <div class="cfm" role="dialog" aria-modal="true">
      <div class="cfmh">
        <h3>Form Preview</h3>
        <span class="ms x" onclick="closePreviewModal()" aria-label="Close">close</span>
      </div>
      <div class="cfmb" id="previewModalContent" style="padding:0"></div>
      <div class="cfmf">
        <button type="button" class="btn" onclick="closePreviewModal()">Close</button>
      </div>
    </div>
  </div>

  <div class="mp-toast" id="saveToast" role="status"><span class="ms">check_circle</span><span id="toastMessage">Saved</span></div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.3/Sortable.min.js"></script>
<script>
(function () {
  const STORE_URL = @json(route('studio.client-flow.questions.store'));
  const UPDATE_URL = @json(route('studio.client-flow.questions.update', ['id' => '__ID__']));
  const DELETE_URL = @json(route('studio.client-flow.questions.destroy', ['id' => '__ID__']));
  const REORDER_URL = @json(route('studio.client-flow.questions.reorder'));
  const STATUS_URL = @json(route('studio.client-flow.questions.status', ['id' => '__ID__']));
  const FORM_CONTEXT = 'custom';
  let $pendingDeleteRow = null;

  function showToast(message) {
    const $t = $('#saveToast');
    $('#toastMessage').text(message);
    $t.addClass('on');
    clearTimeout($t.data('t'));
    $t.data('t', setTimeout(function () { $t.removeClass('on'); }, 2600));
  }

  window.toggleQuestionStatus = function (el) {
    const row = el.closest('.q-row');
    if (!row) return;
    const questionId = row.dataset.id;
    if (!questionId || el.dataset.loading === 'true') return;
    const previousEnabled = row.dataset.enabled === 'true';
    const nextEnabled = !previousEnabled;
    row.dataset.enabled = nextEnabled ? 'true' : 'false';
    row.dataset.available = nextEnabled ? 'true' : 'false';
    el.classList.toggle('active', nextEnabled);
    el.classList.toggle('on', nextEnabled);
    el.setAttribute('aria-checked', nextEnabled ? 'true' : 'false');
    row.classList.toggle('off', !nextEnabled);
    el.dataset.loading = 'true';
    el.style.pointerEvents = 'none';
    $.ajax({
      url: STATUS_URL.replace('__ID__', String(questionId)),
      method: 'PATCH',
      data: JSON.stringify({ is_active: nextEnabled }),
      contentType: 'application/json; charset=UTF-8',
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    }).fail(function (xhr) {
      row.dataset.enabled = previousEnabled ? 'true' : 'false';
      row.dataset.available = previousEnabled ? 'true' : 'false';
      el.classList.toggle('active', previousEnabled);
      el.classList.toggle('on', previousEnabled);
      el.setAttribute('aria-checked', previousEnabled ? 'true' : 'false');
      row.classList.toggle('off', !previousEnabled);
      showToast((xhr.responseJSON && xhr.responseJSON.message) || 'Failed to update question status.');
    }).always(function () {
      delete el.dataset.loading;
      el.style.pointerEvents = '';
    });
  };

  const $addQuestionModal = $('#addQuestionModal');
  const $deleteQuestionModal = $('#deleteQuestionModal');
  const $newQuestionType = $('#newQuestionType');
  const $addOptionsDiv = $('.add-options-div');

  function getOptionRows() { return $addOptionsDiv.find('.option-row'); }
  function toggleRemoveOptionButtons() {
    const showRemove = getOptionRows().length > 2;
    $addOptionsDiv.find('.btn-remove-option').toggleClass('hidden', !showRemove);
  }
  function appendOptionField(value) {
    value = value || '';
    const $optionRow = $(`
      <div class="option-row cfopt">
        <input type="text" name="newQuestionOptions[]" value="${$('<div>').text(value).html()}" placeholder="Option" class="in option-input">
        <span class="ms btn-remove-option hidden" aria-label="Remove option">close</span>
        <p class="option-error field-err hidden" style="flex-basis:100%;margin:0"></p>
      </div>
    `);
    $addOptionsDiv.find('.options-list').append($optionRow);
    toggleRemoveOptionButtons();
  }
  function appendFieldsByType(questionType) {
    $addOptionsDiv.empty();
    if (questionType !== 'select' && questionType !== 'radio') return;
    $addOptionsDiv.append(`
      <div>
        <span class="fl">Options</span>
        <p class="options-general-error field-err hidden"></p>
        <div class="options-list"></div>
        <div class="cfadd btn-add-option"><span class="ms" style="font-size:17px">add</span>Add option</div>
      </div>
    `);
    appendOptionField();
    appendOptionField();
  }
  function clearFieldError($input, $error) {
    $input.css({ borderColor: '', boxShadow: '' });
    $error.addClass('hidden').text('');
  }
  function setFieldError($input, $error, message) {
    $input.css({ borderColor: '#C62828', boxShadow: '0 0 0 3px #FDECEC' });
    $error.removeClass('hidden').text(message);
  }
  function clearOptionErrors() {
    $addOptionsDiv.find('.option-input').css({ borderColor: '', boxShadow: '' });
    $addOptionsDiv.find('.option-error').addClass('hidden').text('');
    $addOptionsDiv.find('.options-general-error').addClass('hidden').text('');
  }
  function setOptionErrorByIndex(index, message) {
    const $row = getOptionRows().eq(index);
    if (!$row.length) return;
    $row.find('.option-input').css({ borderColor: '#C62828', boxShadow: '0 0 0 3px #FDECEC' });
    $row.find('.option-error').removeClass('hidden').text(message);
  }
  function clearAllAddQuestionErrors() {
    clearFieldError($('#newQuestionText'), $('#newQuestionTextError'));
    clearFieldError($('#newQuestionDescription'), $('#newQuestionDescriptionError'));
    clearFieldError($('#newQuestionPlaceholder'), $('#newQuestionPlaceholderError'));
    clearFieldError($('#newQuestionSemanticType'), $('#newQuestionSemanticTypeError'));
    clearFieldError($('#newQuestionType'), $('#newQuestionTypeError'));
    clearOptionErrors();
    $('#addQuestionGeneralError').addClass('hidden').text('');
  }
  function resetAddQuestionForm() {
    const form = document.getElementById('addQuestionForm');
    if (form) form.reset();
    $('#newQuestionText').val('');
    $('#newQuestionDescription').val('');
    $('#newQuestionPlaceholder').val('');
    $('#newQuestionSemanticType').val('other');
    $('#newQuestionType').val('');
    $('#editingQuestionId').val('');
    $('#form-context').val(FORM_CONTEXT);
    $('#newQuestionRequired').val('true');
    $('#newQuestionAvailable').val('true');
    $('#newQuestionRequiredToggle').addClass('on active').attr('aria-checked', 'true');
    $('#newQuestionAvailableToggle').addClass('on active').attr('aria-checked', 'true');
    $('#btnSubmitAddQuestionText').text('Add question');
    $('#btnSubmitAddQuestionIcon').text('add');
    $('#addQuestionModalTitle').text('Add question');
    $addOptionsDiv.empty();
    clearAllAddQuestionErrors();
  }
  function openAddQuestionModal() {
    resetAddQuestionForm();
    $addQuestionModal.addClass('open').attr('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
  }
  function openEditQuestionModal($row) {
    resetAddQuestionForm();
    const questionId = $row.data('id');
    const typeValue = String($row.data('type-value') || 'input');
    const options = $row.data('options');
    const optionValues = Array.isArray(options) ? options : [];
    const isRequired = String($row.data('required')) === 'true';
    const isAvailable = String($row.data('available')) === 'true';
    $('#editingQuestionId').val(String(questionId || ''));
    $('#newQuestionText').val(String($row.attr('data-question') || ''));
    $('#newQuestionDescription').val(String($row.data('description') || ''));
    $('#newQuestionPlaceholder').val(String($row.data('placeholder') || ''));
    $('#newQuestionSemanticType').val(String($row.data('question-type') || 'other'));
    $('#newQuestionType').val(typeValue);
    $('#form-context').val(FORM_CONTEXT);
    $('#newQuestionRequired').val(isRequired ? 'true' : 'false');
    $('#newQuestionAvailable').val(isAvailable ? 'true' : 'false');
    $('#newQuestionRequiredToggle').toggleClass('on active', isRequired).attr('aria-checked', isRequired ? 'true' : 'false');
    $('#newQuestionAvailableToggle').toggleClass('on active', isAvailable).attr('aria-checked', isAvailable ? 'true' : 'false');
    $('#btnSubmitAddQuestionText').text('Save question');
    $('#btnSubmitAddQuestionIcon').text('check');
    $('#addQuestionModalTitle').text('Edit question');
    appendFieldsByType(typeValue);
    if ((typeValue === 'select' || typeValue === 'radio') && optionValues.length) {
      const $list = $addOptionsDiv.find('.options-list');
      $list.empty();
      optionValues.forEach(function (value) { appendOptionField(value || ''); });
      while (getOptionRows().length < 2) appendOptionField('');
      toggleRemoveOptionButtons();
    }
    $addQuestionModal.addClass('open').attr('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
  }
  function closeAddQuestionModal() {
    $addQuestionModal.removeClass('open').attr('aria-hidden', 'true');
    if (!$('#previewModal').hasClass('open') && !$deleteQuestionModal.hasClass('open')) document.body.style.overflow = '';
  }
  function openDeleteQuestionModal($row) {
    $pendingDeleteRow = $row;
    $('#deleteQuestionError').addClass('hidden').text('');
    $deleteQuestionModal.addClass('open').attr('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
  }
  function closeDeleteQuestionModal() {
    $deleteQuestionModal.removeClass('open').attr('aria-hidden', 'true');
    $pendingDeleteRow = null;
    if (!$addQuestionModal.hasClass('open') && !$('#previewModal').hasClass('open')) document.body.style.overflow = '';
  }
  function getRawOptionValues() {
    return getOptionRows().map(function () { return $.trim($(this).find('.option-input').val()); }).get();
  }
  function escapeHtml(value) {
    return String(value || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }
  window.closePreviewModal = function () {
    document.getElementById('previewModal').classList.remove('open');
    document.getElementById('previewModal').setAttribute('aria-hidden', 'true');
    if (!$addQuestionModal.hasClass('open') && !$deleteQuestionModal.hasClass('open')) document.body.style.overflow = '';
  };
  function openPreviewModal() {
    const list = document.getElementById('studioQuestionsList');
    const visibleRows = Array.from(list.querySelectorAll('.q-row')).filter(function (row) {
      return row.dataset.enabled === 'true';
    });
    let html = `<div style="padding:16px 20px;background:#1A1A1A;color:#fff"><div style="font-weight:800;font-size:16px">Studio request form</div><div style="font-size:12.5px;opacity:.75;margin-top:2px">Preview of active questions shown to clients</div></div>`;
    html += `<div style="padding:14px 20px;border-bottom:1px solid #F3EEF4;background:#FBF7FC"><b style="font-size:14px">Which artist? <span style="color:#C62828">*</span></b><div class="faint" style="font-size:12px;margin-top:4px">Any available artist, or one of your artists.</div><div class="chips" style="margin-top:8px"><span class="chip">Any available artist</span><span class="chip">Artist name</span></div></div>`;
    if (!visibleRows.length) {
      html += `<div style="padding:28px 20px;text-align:center" class="faint">No other active questions to preview.</div>`;
    } else {
      html += `<div style="padding:6px 20px 16px">` + visibleRows.map(function (row) {
        const questionText = escapeHtml(row.getAttribute('data-question') || 'Question');
        const questionDescription = escapeHtml(row.dataset.description || '');
        const questionPlaceholder = escapeHtml(row.dataset.placeholder || '');
        const type = String(row.dataset.typeValue || 'input');
        const isRequired = row.dataset.required === 'true';
        let options = [];
        try { options = JSON.parse(row.getAttribute('data-options') || '[]'); } catch (e) { options = []; }
        if (!Array.isArray(options)) options = [];
        let inputHtml = '';
        if (type === 'textarea') inputHtml = `<div class="in" style="margin-top:8px;color:var(--faint)">${questionPlaceholder || 'Your answer...'}</div>`;
        else if (type === 'select' || type === 'radio' || type === 'style' || type === 'placement' || type === 'sizes') {
          inputHtml = `<div class="chips" style="margin-top:8px">${(options.length ? options : ['Option']).map(function (o) {
            return `<span class="chip">${escapeHtml(o)}</span>`;
          }).join('')}</div>`;
        } else if (type === 'toggle') inputHtml = `<div class="chips" style="margin-top:8px"><span class="chip">Yes</span><span class="chip">No</span></div>`;
        else if (type === 'images' || type === 'image') inputHtml = `<div style="margin-top:8px;border:1.5px dashed #D6C9DB;border-radius:12px;padding:16px;text-align:center;color:#6F6874;font-size:13px">Image upload area</div>`;
        else inputHtml = `<div class="in" style="margin-top:8px;color:var(--faint)">${questionPlaceholder || 'Your answer...'}</div>`;
        return `<div style="padding:14px 0;border-bottom:1px solid #F3EEF4"><b style="font-size:14px">${questionText}${isRequired ? ' <span style="color:#C62828">*</span>' : ''}</b>${questionDescription ? `<div class="faint" style="font-size:12px;margin-top:4px">${questionDescription}</div>` : ''}${inputHtml}</div>`;
      }).join('') + `</div>`;
    }
    document.getElementById('previewModalContent').innerHTML = html;
    document.getElementById('previewModal').classList.add('open');
    document.getElementById('previewModal').setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
  }

  function initQuestionSorting() {
    const listEl = document.querySelector('#studioQuestionsList');
    if (!listEl || typeof Sortable === 'undefined') return;
    Sortable.create(listEl, {
      draggable: '.q-row',
      handle: '.q-drag-handle',
      animation: 150,
      ghostClass: 'sortable-ghost',
      chosenClass: 'sortable-chosen',
      onEnd: function () {
        const orderedItems = Array.from(listEl.querySelectorAll('.q-row')).map(function (row, index) {
          const id = Number(row.dataset.id);
          return id ? { id: id, order: index + 1 } : null;
        }).filter(Boolean);
        if (!orderedItems.length) return;
        $.ajax({
          url: REORDER_URL,
          method: 'POST',
          data: JSON.stringify({ form_context: FORM_CONTEXT, items: orderedItems }),
          contentType: 'application/json; charset=UTF-8',
          headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          }
        }).fail(function (xhr) {
          showToast((xhr.responseJSON && xhr.responseJSON.message) || 'Could not save question order.');
        });
      }
    });
  }

  $('#btnOpenAddQuestionModal').on('click', openAddQuestionModal);
  $('#btnPreviewForm').on('click', openPreviewModal);
  $(document).on('click', '.js-edit-question, .q-row .qt[role="button"]', function () {
    const $row = $(this).closest('.q-row');
    if (!$row.length) return;
    openEditQuestionModal($row);
  });
  $(document).on('click', '.js-remove-question', function () {
    const $row = $(this).closest('.q-row');
    if (!$row.length) return;
    openDeleteQuestionModal($row);
  });
  $('#btnCloseAddQuestionModal, #btnCancelAddQuestionModal').on('click', closeAddQuestionModal);
  $addQuestionModal.on('click', function (e) { if (e.target === this) closeAddQuestionModal(); });
  $('#btnCloseDeleteQuestionModal, #btnCancelDeleteQuestionModal').on('click', closeDeleteQuestionModal);
  $deleteQuestionModal.on('click', function (e) { if (e.target === this) closeDeleteQuestionModal(); });
  $('#previewModal').on('click', function (e) { if (e.target === this) closePreviewModal(); });

  $('#btnConfirmDeleteQuestion').on('click', function () {
    if (!$pendingDeleteRow || !$pendingDeleteRow.length) { closeDeleteQuestionModal(); return; }
    const questionId = $pendingDeleteRow.data('id');
    if (!questionId) { $('#deleteQuestionError').removeClass('hidden').text('Could not determine question id.'); return; }
    const $btn = $(this);
    const original = $btn.html();
    $btn.prop('disabled', true).html('Deleting...');
    $.ajax({
      url: DELETE_URL.replace('__ID__', String(questionId)),
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    }).done(function (response) {
      if (!response || !response.success) {
        $('#deleteQuestionError').removeClass('hidden').text((response && response.message) || 'Failed to delete question.');
        return;
      }
      const $list = $pendingDeleteRow.closest('.questions-list');
      $pendingDeleteRow.remove();
      if ($list.length && !$list.children('.q-row').length) {
        $list.append('<p class="cf-empty faint" style="padding:26px 18px;text-align:center;font-size:13px">No questions yet. Add your first one.</p>');
      }
      closeDeleteQuestionModal();
      showToast('Question deleted.');
    }).fail(function (xhr) {
      $('#deleteQuestionError').removeClass('hidden').text((xhr.responseJSON && xhr.responseJSON.message) || 'Failed to delete question.');
    }).always(function () { $btn.prop('disabled', false).html(original); });
  });

  $('#newQuestionRequiredToggle, #newQuestionAvailableToggle').on('click', function (e) {
    e.preventDefault();
    const $toggle = $(this);
    const isActive = !$toggle.hasClass('on');
    $toggle.toggleClass('on active', isActive).attr('aria-checked', isActive ? 'true' : 'false');
  });
  $('#newQuestionRequiredToggle').on('click', function () {
    $('#newQuestionRequired').val($(this).hasClass('on') ? 'true' : 'false');
  });
  $('#newQuestionAvailableToggle').on('click', function () {
    $('#newQuestionAvailable').val($(this).hasClass('on') ? 'true' : 'false');
  });
  $newQuestionType.on('change', function () {
    appendFieldsByType($(this).val());
    clearFieldError($('#newQuestionType'), $('#newQuestionTypeError'));
    clearOptionErrors();
  });
  $addOptionsDiv.on('click', '.btn-add-option', function () {
    appendOptionField();
    $addOptionsDiv.find('.option-input').last().focus();
  });
  $addOptionsDiv.on('click', '.btn-remove-option', function () {
    if (getOptionRows().length <= 2) return;
    $(this).closest('.option-row').remove();
    toggleRemoveOptionButtons();
  });

  $('#btnSubmitAddQuestion').on('click', function () {
    clearAllAddQuestionErrors();
    const payload = {
      question: $.trim($('#newQuestionText').val()),
      description: $.trim($('#newQuestionDescription').val()),
      placeholder: $.trim($('#newQuestionPlaceholder').val()),
      question_type: $('#newQuestionSemanticType').val() || 'other',
      type: $('#newQuestionType').val(),
      form_context: FORM_CONTEXT,
      is_required: $('#newQuestionRequired').val() === 'true',
      is_active: $('#newQuestionAvailable').val() === 'true'
    };
    if (payload.type === 'select' || payload.type === 'radio') payload.options = getRawOptionValues();
    const $submitBtn = $('#btnSubmitAddQuestion');
    const originalBtnHtml = $submitBtn.html();
    $submitBtn.prop('disabled', true).html('Saving...');
    const editingId = $.trim($('#editingQuestionId').val());
    const isEditing = editingId !== '';
    $.ajax({
      url: isEditing ? UPDATE_URL.replace('__ID__', String(editingId)) : STORE_URL,
      method: isEditing ? 'PUT' : 'POST',
      data: JSON.stringify(payload),
      contentType: 'application/json; charset=UTF-8',
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    }).done(function (response) {
      if (response && response.success) {
        closeAddQuestionModal();
        window.location.reload();
        return;
      }
      $('#addQuestionGeneralError').removeClass('hidden').text('Unexpected response from server.');
    }).fail(function (xhr) {
      if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
        const serverErrors = xhr.responseJSON.errors;
        if (serverErrors.question) setFieldError($('#newQuestionText'), $('#newQuestionTextError'), serverErrors.question[0]);
        if (serverErrors.type) setFieldError($('#newQuestionType'), $('#newQuestionTypeError'), serverErrors.type[0]);
        Object.keys(serverErrors).forEach(function (key) {
          const m = key.match(/^options\.(\d+)$/);
          if (m) setOptionErrorByIndex(parseInt(m[1], 10), serverErrors[key][0]);
        });
      } else {
        $('#addQuestionGeneralError').removeClass('hidden').text((xhr.responseJSON && xhr.responseJSON.message) || 'Something went wrong while saving.');
      }
    }).always(function () { $submitBtn.prop('disabled', false).html(originalBtnHtml); });
  });

  document.addEventListener('click', function (e) {
    const soon = e.target.closest('.tabs a.is-soon');
    if (soon) { e.preventDefault(); }
  });
  document.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape') return;
    closePreviewModal();
    closeAddQuestionModal();
    closeDeleteQuestionModal();
  });

  initQuestionSorting();
})();
</script>
@endpush
