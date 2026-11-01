@extends('layouts.new-artist-dashboard-layout')

@section('title', 'Client Flow')

@php
  $defaultCount = $default_questions->count();
  $customCount = $custom_questions->count();
  $studioCount = (int) (auth()->user()?->userStudios()->count() ?? 0);
  $showStudioPicker = $studioCount >= 2;
@endphp

@section('styles')
<style>
  .cf-page .cfform{
    cursor:pointer;padding:13px 14px;border-radius:12px;border:1px solid transparent;margin-bottom:6px;
    display:flex;align-items:center;gap:12px;transition:background .15s,border-color .15s,padding .15s
  }
  .cf-page .cfform:hover{background:#FBF7FC}
  .cf-page .cfform.on{background:#fff;border:2px solid #3E007C;padding:12px 13px}
  .cf-page .cfrow{
    display:flex;align-items:center;gap:12px;padding:11px 18px;border-bottom:1px solid #F3EEF4
  }
  .cf-page .cfrow.off{opacity:.55}
  .cf-page .cfrow .qt{flex:1;font-size:13.5px;min-width:0;line-height:1.4}
  .cf-page .cfrow .qt[role="button"]{cursor:pointer}
  .cf-page .cfrow .qt[role="button"]:hover{text-decoration:underline}
  .cf-page .cfrow .drag{cursor:grab;flex-shrink:0}
  .cf-page .cfrow .cf-actions{display:flex;align-items:center;gap:2px;flex-shrink:0}
  .cf-page .cfrow .cf-icon-btn{
    width:28px;height:28px;border:0;background:none;border-radius:8px;cursor:pointer;
    display:inline-flex;align-items:center;justify-content:center;color:var(--faint);padding:0
  }
  .cf-page .cfrow .cf-icon-btn .ms{font-size:18px}
  .cf-page .cfrow .cf-icon-btn:hover{background:#F7F3F9;color:var(--ink)}
  .cf-page .cfrow .cf-icon-btn.del:hover{background:#FDECEC;color:#C62828}
  .cf-page .cfrow.sortable-ghost{opacity:.45;background:#FBF7FC}
  .cf-page .cfrow.sortable-chosen{cursor:grabbing}
  .cf-page .cf-fixed{
    display:flex;align-items:center;gap:12px;padding:11px 18px;border-bottom:1px solid #F3EEF4;background:#FBF7FC
  }
  .cf-page .tog{
    width:38px;height:22px;border-radius:12px;background:#D9D2DD;position:relative;flex-shrink:0;cursor:pointer;border:0
  }
  .cf-page .tog:after{
    content:'';position:absolute;width:16px;height:16px;border-radius:50%;background:#fff;top:3px;left:3px;transition:left .15s
  }
  .cf-page .tog.on,.cf-page .tog.active{background:var(--pri)}
  .cf-page .tog.on:after,.cf-page .tog.active:after{left:19px}
  .cf-page .intake-grid{display:grid;grid-template-columns:270px 1fr;gap:20px;align-items:start}
  .cf-page .modal-backdrop{
    display:none;position:fixed;inset:0;background:rgba(26,26,26,.45);z-index:300;
    align-items:center;justify-content:center;padding:24px
  }
  .cf-page .modal-backdrop.open{display:flex}
  .cf-page .cfm{
    background:#fff;border-radius:16px;border:1px solid var(--line);width:100%;max-width:520px;
    max-height:calc(100vh - 48px);display:flex;flex-direction:column;box-shadow:0 18px 50px rgba(0,0,0,.18)
  }
  .cf-page .cfm.sm{max-width:420px}
  .cf-page .cfmh{
    display:flex;align-items:center;justify-content:space-between;gap:12px;
    padding:18px 22px 14px;border-bottom:1px solid #F0EAF2;flex-shrink:0
  }
  .cf-page .cfmh h3{font-size:18px;font-weight:800;margin:0}
  .cf-page .cfmh .x{cursor:pointer;color:var(--faint);font-size:22px}
  .cf-page .cfmb{padding:18px 22px;overflow-y:auto;min-height:0;flex:1}
  .cf-page .cfmf{
    display:flex;justify-content:flex-end;align-items:center;gap:10px;
    padding:14px 22px;border-top:1px solid #F0E9F2;flex-shrink:0
  }
  .cf-page .cfmb .fl{margin-top:12px}
  .cf-page .cfmb .fl:first-child{margin-top:0}
  .cf-page .cfmb textarea.in{min-height:72px;resize:vertical;width:100%;font:inherit;font-size:13.5px}
  .cf-page .cfmb select.in{width:100%;font:inherit;font-size:13.5px;color:var(--ink);appearance:auto}
  .cf-page .cfmb select.in:disabled{background:#F5F2F6;color:#6F6874;cursor:not-allowed}
  .cf-page .field-err{color:#C62828;font-size:12px;margin-top:6px}
  .cf-page .field-err.hidden,.cf-page .hidden{display:none!important}
  .cf-page .cfopt{display:flex;gap:8px;align-items:center;margin-top:6px}
  .cf-page .cfopt input{flex:1}
  .cf-page .cfopt .ms{cursor:pointer;color:var(--faint)}
  .cf-page .cfadd{
    display:inline-flex;gap:6px;align-items:center;font-weight:600;font-size:13px;margin-top:8px;cursor:pointer;color:var(--pri)
  }
  .cf-page .mp-toast{
    position:fixed;top:22px;right:24px;background:#1A1A1A;color:#fff;padding:12px 18px;border-radius:10px;
    font-weight:600;font-size:14px;z-index:400;box-shadow:0 10px 30px rgba(0,0,0,.22);
    display:flex;gap:10px;align-items:center;opacity:0;transform:translateX(calc(100% + 40px));
    transition:opacity .25s,transform .35s;pointer-events:none
  }
  .cf-page .mp-toast.on{opacity:1;transform:none}
  .cf-page .mp-toast .ms{color:#3DD68C;font-size:20px}
  @media (max-width:900px){
    .cf-page .intake-grid{grid-template-columns:1fr}
    .cf-page .mp-toast{left:16px;right:16px;top:74px}
    }
</style>
@endsection

@section('content')
<div class="cf-page">
  <div class="head">
          <div>
      <h1>
        Client Flow
        <a class="help-q" href="https://help.inkjin.com/en/articles/17201374-dashboard-client-flow-intake-forms" target="_blank" rel="noopener" title="Help with this page" aria-label="Help with this page">
          <span class="ms">help</span>
        </a>
      </h1>
      <div class="sub">What clients fill in and receive, from first request to healed tattoo.</div>
        </div>
      </div>

  @include('artist.partials.client-flow-journey', ['activeStep' => 'intake'])
  @include('artist.partials.client-flow-tabs', ['clientFlowTab' => 'intake'])

  <div class="intake-grid">
    <div>
      <div class="lbl" style="margin-bottom:10px">Forms</div>
      <div id="cfforms">
        <div class="cfform on" data-form="booking" id="tabBooking" role="button" tabindex="0" aria-current="true">
          <span class="ms">palette</span>
          <div style="flex:1">
            <b style="font-size:13.5px">Flash design</b>
            <div class="faint cfcnt" id="flashCount" style="font-size:12px">{{ $defaultCount }} question{{ $defaultCount === 1 ? '' : 's' }}</div>
      </div>
          <span class="ms faint">chevron_right</span>
      </div>
        <div class="cfform" data-form="request" id="tabRequest" role="button" tabindex="0" aria-current="false">
          <span class="ms">edit_note</span>
          <div style="flex:1">
            <b style="font-size:13.5px">Custom request</b>
            <div class="faint cfcnt" id="customCount" style="font-size:12px">{{ $customCount }} question{{ $customCount === 1 ? '' : 's' }}</div>
                </div>
          <span class="ms faint">chevron_right</span>
                </div>
              </div>
            </div>

    <div class="card" id="cfcard">
      <div class="ch">
        <div>
          <h3 id="cfTitle">Flash design form</h3>
          <div class="faint" id="cfSub" style="font-size:12.5px;margin-top:2px">Asked when a client books one of your flash designs · drag to reorder</div>
                        </div>
        <div class="btns">
          <button type="button" class="btn sm ghost" id="btnPreviewForm"><span class="ms" style="font-size:16px">visibility</span>Preview</button>
          <button type="button" class="btn sm" id="btnOpenAddQuestionModal"><span class="ms" style="font-size:16px">add</span>Add question</button>
                            </div>
                    </div>

      @if($showStudioPicker)
        <div class="cf-fixed" id="studioPickerRow">
          <span class="ms faint" title="Always first">lock</span>
          <span style="flex:1;font-size:13.5px">
            Where do you want to get tattooed?
            <div class="faint" style="font-size:12px">Your active studios, with their addresses. Shows only when you work at 2 or more studios. Skipped when the client comes from a studio’s page. Always the first question.</div>
          </span>
          <span class="pill nd b">Select</span>
          <span class="pill nd k" style="font-size:10.5px">FIXED</span>
        </div>
      @endif

      <div id="bookingQuestionsList" class="questions-list">
        @forelse ($default_questions as $question)
          @include('artist.forms.partials.intake-question-row', ['question' => $question, 'formContext' => 'default', 'system_questions_ids' => $system_questions_ids])
                @empty
          <p class="cf-empty faint" style="padding:26px 18px;text-align:center;font-size:13px">No questions yet. Add your first one.</p>
                @endforelse
            </div>

            <div id="requestQuestionsList" class="questions-list hidden">
                @forelse ($custom_questions as $question)
          @include('artist.forms.partials.intake-question-row', ['question' => $question, 'formContext' => 'custom', 'system_questions_ids' => $system_questions_ids])
                @empty
          <p class="cf-empty faint" style="padding:26px 18px;text-align:center;font-size:13px">No questions yet. Add your first one.</p>
                @endforelse
          </div>
        </div>
      </div>

  {{-- Add / Edit question modal --}}
  <div class="modal-backdrop" id="addQuestionModal" aria-hidden="true">
    <div class="cfm" role="dialog" aria-modal="true" aria-labelledby="addQuestionModalTitle">
      <div class="cfmh">
        <h3 id="addQuestionModalTitle">Add question</h3>
        <span class="ms x" id="btnCloseAddQuestionModal" aria-label="Close">close</span>
      </div>
      <div class="cfmb">
      <form id="addQuestionForm">
        <input type="hidden" id="editingQuestionId" value="">
          <input type="hidden" id="form-context" name="form_context" value="default">
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

  {{-- Delete confirmation --}}
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

  {{-- Preview --}}
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

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.3/Sortable.min.js"></script>
<script>
  const ARTIST_FORMS_QUESTION_STORE = @json(route('artist.forms.questions.store'));
  const ARTIST_FORMS_QUESTION_UPDATE_URL_TEMPLATE = @json(route('artist.forms.questions.update', ['id' => '__ID__']));
  const ARTIST_FORMS_QUESTION_DELETE_URL_TEMPLATE = @json(route('artist.forms.questions.destroy', ['id' => '__ID__']));
  const ARTIST_FORMS_REORDER_URL = @json(route('artist.forms.questions.reorder'));
  const ARTIST_SYSTEM_QUESTION_STATUS_URL_TEMPLATE = @json(route('artist.forms.questions.status', ['id' => '__ID__']));
  const ARTIST_FORMS_TAB_STORAGE_KEY = "artistFormsActiveTab";
  const FORM_META = {
    booking: {
      context: "default",
      title: "Flash design form",
      sub: "Asked when a client books one of your flash designs · drag to reorder",
      countId: "flashCount"
    },
    request: {
      context: "custom",
      title: "Custom request form",
      sub: "Asked when a client requests a custom tattoo · drag to reorder",
      countId: "customCount"
    }
  };
  const currentFormToContext = { booking: "default", request: "custom" };
  let currentFormType = "booking";
  let $pendingDeleteRow = null;

  function countLabel(n) {
    return n + " question" + (n === 1 ? "" : "s");
  }

  function refreshFormCounts() {
    $("#flashCount").text(countLabel($("#bookingQuestionsList .q-row").length));
    $("#customCount").text(countLabel($("#requestQuestionsList .q-row").length));
  }

  function persistActiveFormTab(type) {
    try { sessionStorage.setItem(ARTIST_FORMS_TAB_STORAGE_KEY, type); } catch (e) {}
    const url = new URL(window.location.href);
    url.searchParams.set("tab", type === "request" ? "custom" : "booking");
    window.history.replaceState({}, "", url);
  }

  function switchFormType(type, options) {
    options = options || {};
    currentFormType = type;
    if (options.persist !== false) persistActiveFormTab(type);

    $(".cfform").each(function () {
      const on = this.dataset.form === type;
      $(this).toggleClass("on", on).attr("aria-current", on ? "true" : "false");
    });

    $("#bookingQuestionsList").toggleClass("hidden", type !== "booking");
    $("#requestQuestionsList").toggleClass("hidden", type !== "request");
    $("#studioPickerRow").toggle(type === "booking" || type === "request");

    const meta = FORM_META[type] || FORM_META.booking;
    $("#cfTitle").text(meta.title);
    $("#cfSub").text(meta.sub);
    $("#form-context").val(meta.context);
    closeAddQuestionModal();
  }

  function restoreActiveFormTab() {
    const params = new URLSearchParams(window.location.search);
    const tabParam = params.get("tab");
    // Default to Flash design; only open Custom when ?tab=custom|request
    const type = tabParam === "custom" || tabParam === "request" ? "request" : "booking";
    switchFormType(type, { persist: tabParam == null });
  }

  function toggleQuestionStatus(el) {
    const row = el.closest(".q-row");
    if (!row) return;
    const questionId = row.dataset.id;
    if (!questionId || el.dataset.loading === "true") return;

    const previousEnabled = row.dataset.enabled === "true";
    const nextEnabled = !previousEnabled;

    row.dataset.enabled = nextEnabled ? "true" : "false";
    row.dataset.available = nextEnabled ? "true" : "false";
    el.classList.toggle("active", nextEnabled);
    el.classList.toggle("on", nextEnabled);
    el.setAttribute("aria-checked", nextEnabled ? "true" : "false");
    row.classList.toggle("off", !nextEnabled);
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
        el.classList.toggle("on", previousEnabled);
        el.setAttribute("aria-checked", previousEnabled ? "true" : "false");
        row.classList.toggle("off", !previousEnabled);
        showToast((response && response.message) || "Failed to update question status.");
      }
    }).fail(function (xhr) {
      row.dataset.enabled = previousEnabled ? "true" : "false";
      row.dataset.available = previousEnabled ? "true" : "false";
      el.classList.toggle("active", previousEnabled);
      el.classList.toggle("on", previousEnabled);
      el.setAttribute("aria-checked", previousEnabled ? "true" : "false");
      row.classList.toggle("off", !previousEnabled);
      showToast((xhr.responseJSON && xhr.responseJSON.message) || "Failed to update question status.");
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

  function appendOptionField(value) {
    value = value || "";
    const $optionRow = $(`
      <div class="option-row cfopt">
        <input type="text" name="newQuestionOptions[]" value="${$("<div>").text(value).html()}" placeholder="Option" class="in option-input">
        <span class="ms btn-remove-option hidden" aria-label="Remove option">close</span>
        <p class="option-error field-err hidden" style="flex-basis:100%;margin:0"></p>
      </div>
    `);
    $addOptionsDiv.find(".options-list").append($optionRow);
    toggleRemoveOptionButtons();
  }

  function appendFieldsByType(questionType) {
    $addOptionsDiv.empty();
    if (questionType !== "select" && questionType !== "radio") return;
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
    $input.css({ borderColor: "", boxShadow: "" });
    $error.addClass("hidden").text("");
  }

  function setFieldError($input, $error, message) {
    $input.css({ borderColor: "#C62828", boxShadow: "0 0 0 3px #FDECEC" });
    $error.removeClass("hidden").text(message);
  }

  function clearOptionErrors() {
    $addOptionsDiv.find(".option-input").css({ borderColor: "", boxShadow: "" });
    $addOptionsDiv.find(".option-error").addClass("hidden").text("");
    $addOptionsDiv.find(".options-general-error").addClass("hidden").text("");
  }

  function setOptionErrorByIndex(index, message) {
    const $row = getOptionRows().eq(index);
    if (!$row.length) return;
    $row.find(".option-input").css({ borderColor: "#C62828", boxShadow: "0 0 0 3px #FDECEC" });
    $row.find(".option-error").removeClass("hidden").text(message);
  }

  function clearAllAddQuestionErrors() {
    clearFieldError($("#newQuestionText"), $("#newQuestionTextError"));
    clearFieldError($("#newQuestionDescription"), $("#newQuestionDescriptionError"));
    clearFieldError($("#newQuestionPlaceholder"), $("#newQuestionPlaceholderError"));
    clearFieldError($("#newQuestionSemanticType"), $("#newQuestionSemanticTypeError"));
    clearFieldError($("#newQuestionType"), $("#newQuestionTypeError"));
    clearOptionErrors();
    $("#addQuestionGeneralError").addClass("hidden").text("");
  }

  function resetAddQuestionForm() {
    const form = document.getElementById("addQuestionForm");
    if (form) form.reset();
    $("#newQuestionText").val("");
    $("#newQuestionDescription").val("");
    $("#newQuestionPlaceholder").val("");
    $("#newQuestionSemanticType").val("other");
    $("#newQuestionType").val("");
    $("#editingQuestionId").val("");
    $("#form-context").val(currentFormToContext[currentFormType] || "default");
    $("#newQuestionRequired").val("true");
    $("#newQuestionAvailable").val("true");
    $("#newQuestionRequiredToggle").addClass("on active").attr("aria-checked", "true");
    $("#newQuestionAvailableToggle").addClass("on active").attr("aria-checked", "true");
    $("#btnSubmitAddQuestionText").text("Add question");
    $("#btnSubmitAddQuestionIcon").text("add");
    $("#addQuestionModalTitle").text("Add question");
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
    const questionText = String($row.attr("data-question") || "");
    const questionDescription = String($row.data("description") || "");
    const questionPlaceholder = String($row.data("placeholder") || "");
    const typeValue = String($row.data("type-value") || "input");
    const questionType = String($row.data("question-type") || "other");
    const formContext = String($row.data("form-context") || "default");
    const isRequired = String($row.data("required")) === "true";
    const isAvailable = String($row.data("available")) === "true";
    const options = $row.data("options");
    const optionValues = Array.isArray(options) ? options : [];

    $("#editingQuestionId").val(String(questionId || ""));
    $("#newQuestionText").val(questionText);
    $("#newQuestionDescription").val(questionDescription);
    $("#newQuestionPlaceholder").val(questionPlaceholder);
    $("#newQuestionSemanticType").val(questionType);
    $("#newQuestionType").val(typeValue);
    $("#form-context").val(formContext);
    $("#newQuestionRequired").val(isRequired ? "true" : "false");
    $("#newQuestionAvailable").val(isAvailable ? "true" : "false");
    $("#newQuestionRequiredToggle").toggleClass("on active", isRequired).attr("aria-checked", isRequired ? "true" : "false");
    $("#newQuestionAvailableToggle").toggleClass("on active", isAvailable).attr("aria-checked", isAvailable ? "true" : "false");
    $("#btnSubmitAddQuestionText").text("Save question");
    $("#btnSubmitAddQuestionIcon").text("check");
    $("#addQuestionModalTitle").text("Edit question");

    appendFieldsByType(typeValue);
    if ((typeValue === "select" || typeValue === "radio") && optionValues.length) {
      const $list = $addOptionsDiv.find(".options-list");
      $list.empty();
      optionValues.forEach(function (value) { appendOptionField(value || ""); });
      while (getOptionRows().length < 2) appendOptionField("");
      toggleRemoveOptionButtons();
    }

    $addQuestionModal.addClass("open").attr("aria-hidden", "false");
    document.body.style.overflow = "hidden";
  }

  function closeAddQuestionModal() {
    $addQuestionModal.removeClass("open").attr("aria-hidden", "true");
    if (!$("#previewModal").hasClass("open") && !$deleteQuestionModal.hasClass("open")) {
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
    const $t = $("#saveToast");
    $("#toastMessage").text(message);
    $t.addClass("on");
    clearTimeout($t.data("t"));
    $t.data("t", setTimeout(function () { $t.removeClass("on"); }, 2600));
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
      return row.dataset.enabled === "true" || row.dataset.available === "true";
    }).filter(function (row) {
      return row.dataset.enabled === "true";
    });

    const title = currentFormType === "booking" ? "Flash design form" : "Custom request form";
    let html = `
      <div style="padding:16px 20px;background:#1A1A1A;color:#fff">
        <div style="font-weight:800;font-size:16px">${escapeHtml(title)}</div>
        <div style="font-size:12.5px;opacity:.75;margin-top:2px">Preview of active questions shown to clients</div>
      </div>
    `;

    if (!visibleRows.length) {
      html += `<div style="padding:28px 20px;text-align:center" class="faint">No active questions to preview.</div>`;
    } else {
      html += `<div style="padding:6px 20px 16px">` + visibleRows.map(function (row) {
        const questionText = escapeHtml(row.getAttribute("data-question") || "Question");
        const questionDescription = escapeHtml(row.dataset.description || "");
        const questionPlaceholder = escapeHtml(row.dataset.placeholder || "");
        const type = String(row.dataset.typeValue || "input");
        const isRequired = row.dataset.required === "true";
        let options = [];
        try { options = JSON.parse(row.dataset.options || "[]"); } catch (e) { options = []; }
        if (!Array.isArray(options)) options = [];

        let inputHtml = "";
        if (type === "textarea") {
          inputHtml = `<div class="in" style="margin-top:8px;color:var(--faint)">${questionPlaceholder || "Your answer..."}</div>`;
        } else if (type === "select") {
          inputHtml = `<div class="chips" style="margin-top:8px">${(options.length ? options : ["Option"]).map(function (o) {
            return `<span class="chip">${escapeHtml(o)}</span>`;
          }).join("")}</div>`;
        } else if (type === "radio" || type === "style" || type === "placement" || type === "sizes") {
          inputHtml = `<div class="chips" style="margin-top:8px">${(options.length ? options : ["Option 1", "Option 2"]).map(function (o) {
            return `<span class="chip">${escapeHtml(o)}</span>`;
          }).join("")}</div>`;
        } else if (type === "toggle") {
          inputHtml = `<div class="chips" style="margin-top:8px"><span class="chip">Yes</span><span class="chip">No</span></div>`;
        } else if (type === "images" || type === "image") {
          inputHtml = `<div style="margin-top:8px;border:1.5px dashed #D6C9DB;border-radius:12px;padding:16px;text-align:center;color:#6F6874;font-size:13px">Image upload area</div>`;
        } else {
          inputHtml = `<div class="in" style="margin-top:8px;color:var(--faint)">${questionPlaceholder || "Your answer..."}</div>`;
        }

        return `<div style="padding:14px 0;border-bottom:1px solid #F3EEF4">
          <b style="font-size:14px">${questionText}${isRequired ? ' <span style="color:#C62828">*</span>' : ""}</b>
          ${questionDescription ? `<div class="faint" style="font-size:12px;margin-top:4px">${questionDescription}</div>` : ""}
            ${inputHtml}
        </div>`;
      }).join("") + `</div>`;
    }

    document.getElementById("previewModalContent").innerHTML = html;
    document.getElementById("previewModal").classList.add("open");
    document.getElementById("previewModal").setAttribute("aria-hidden", "false");
    document.body.style.overflow = "hidden";
  }

  function closePreviewModal() {
    document.getElementById("previewModal").classList.remove("open");
    document.getElementById("previewModal").setAttribute("aria-hidden", "true");
    if (!$("#addQuestionModal").hasClass("open") && !$deleteQuestionModal.hasClass("open")) {
      document.body.style.overflow = "";
    }
  }

  function initQuestionSorting(listSelector, formContext) {
    const listEl = document.querySelector(listSelector);
    if (!listEl || typeof Sortable === "undefined") return;

    Sortable.create(listEl, {
      draggable: ".q-row",
      handle: ".q-drag-handle",
      animation: 150,
      ghostClass: "sortable-ghost",
      chosenClass: "sortable-chosen",
      onEnd: function () {
        const orderedItems = Array.from(listEl.querySelectorAll(".q-row"))
          .map(function (row, index) {
            const id = Number(row.dataset.id);
            if (!id) return null;
            return { id: id, order: index + 1 };
          })
          .filter(Boolean);

        if (!orderedItems.length) return;

        $.ajax({
          url: ARTIST_FORMS_REORDER_URL,
          method: "POST",
          data: JSON.stringify({ form_context: formContext, items: orderedItems }),
          contentType: "application/json; charset=UTF-8",
          headers: {
            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            "Accept": "application/json",
            "X-Requested-With": "XMLHttpRequest"
          }
        }).fail(function (xhr) {
          showToast((xhr.responseJSON && xhr.responseJSON.message) || "Could not save question order.");
        });
      }
    });
  }

  $(".cfform").on("click", function () {
    switchFormType(this.dataset.form);
  }).on("keydown", function (e) {
    if (e.key === "Enter" || e.key === " ") {
      e.preventDefault();
      switchFormType(this.dataset.form);
    }
  });

  $("#btnOpenAddQuestionModal").on("click", openAddQuestionModal);
  $("#btnPreviewForm").on("click", openPreviewModal);

  $(document).on("click", ".js-edit-question, .q-row .qt[role='button']", function () {
    const $row = $(this).closest(".q-row");
    if (!$row.length || String($row.data("system")) === "true") return;
    openEditQuestionModal($row);
  });

  $(document).on("keydown", ".q-row .qt[role='button']", function (e) {
    if (e.key !== "Enter" && e.key !== " ") return;
    e.preventDefault();
    $(this).trigger("click");
  });

  $(document).on("click", ".js-remove-question", function () {
    const $row = $(this).closest(".q-row");
    if (!$row.length || String($row.data("system")) === "true") return;
    openDeleteQuestionModal($row);
  });

  $("#btnCloseAddQuestionModal, #btnCancelAddQuestionModal").on("click", closeAddQuestionModal);
  $addQuestionModal.on("click", function (event) {
    if (event.target === this) closeAddQuestionModal();
  });

  $("#btnCloseDeleteQuestionModal, #btnCancelDeleteQuestionModal").on("click", closeDeleteQuestionModal);
  $deleteQuestionModal.on("click", function (event) {
    if (event.target === this) closeDeleteQuestionModal();
  });

  $("#previewModal").on("click", function (event) {
    if (event.target === this) closePreviewModal();
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
        $list.append('<p class="cf-empty faint" style="padding:26px 18px;text-align:center;font-size:13px">No questions yet. Add your first one.</p>');
      }
      refreshFormCounts();
      closeDeleteQuestionModal();
      showToast("Question deleted.");
    }).fail(function (xhr) {
      $("#deleteQuestionError").removeClass("hidden").text((xhr.responseJSON && xhr.responseJSON.message) || "Failed to delete question.");
    }).always(function () {
      $btn.prop("disabled", false).html(originalBtnHtml);
    });
  });

  $("#newQuestionRequiredToggle, #newQuestionAvailableToggle").on("click", function (e) {
    e.preventDefault();
    const $toggle = $(this);
    const isActive = !$toggle.hasClass("on");
    $toggle.toggleClass("on active", isActive).attr("aria-checked", isActive ? "true" : "false");
  });
  $("#newQuestionRequiredToggle").on("click", function () {
    $("#newQuestionRequired").val($(this).hasClass("on") ? "true" : "false");
  });
  $("#newQuestionAvailableToggle").on("click", function () {
    $("#newQuestionAvailable").val($(this).hasClass("on") ? "true" : "false");
  });

  $newQuestionType.on("change", function () {
    appendFieldsByType($(this).val());
    clearFieldError($("#newQuestionType"), $("#newQuestionTypeError"));
    clearOptionErrors();
  });

  $("#newQuestionText").on("input", function () {
    clearFieldError($("#newQuestionText"), $("#newQuestionTextError"));
  });
  $("#newQuestionDescription").on("input", function () {
    clearFieldError($("#newQuestionDescription"), $("#newQuestionDescriptionError"));
  });
  $("#newQuestionPlaceholder").on("input", function () {
    clearFieldError($("#newQuestionPlaceholder"), $("#newQuestionPlaceholderError"));
  });

  $addOptionsDiv.on("click", ".btn-add-option", function () {
    appendOptionField();
    const $inputs = $addOptionsDiv.find(".option-input");
    $inputs.last().focus();
  });
  $addOptionsDiv.on("input", ".option-input", function () {
    const $row = $(this).closest(".option-row");
    $row.find(".option-input").css({ borderColor: "", boxShadow: "" });
    $row.find(".option-error").addClass("hidden").text("");
    $addOptionsDiv.find(".options-general-error").addClass("hidden").text("");
  });
  $addOptionsDiv.on("click", ".btn-remove-option", function () {
    if (getOptionRows().length <= 2) return;
    $(this).closest(".option-row").remove();
    toggleRemoveOptionButtons();
  });

  $("#btnSubmitAddQuestion").on("click", function () {
    clearAllAddQuestionErrors();
    const payload = {
      question: $.trim($("#newQuestionText").val()),
      description: $.trim($("#newQuestionDescription").val()),
      placeholder: $.trim($("#newQuestionPlaceholder").val()),
      question_type: $("#newQuestionSemanticType").val() || "other",
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
        persistActiveFormTab(currentFormType);
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
        if (serverErrors.question_type && serverErrors.question_type[0]) {
          setFieldError($("#newQuestionSemanticType"), $("#newQuestionSemanticTypeError"), serverErrors.question_type[0]);
        }
        if (serverErrors.type && serverErrors.type[0]) {
          setFieldError($("#newQuestionType"), $("#newQuestionTypeError"), serverErrors.type[0]);
        }
        Object.keys(serverErrors).forEach(function (key) {
          const m = key.match(/^options\.(\d+)$/);
          if (!m) return;
          setOptionErrorByIndex(parseInt(m[1], 10), Array.isArray(serverErrors[key]) ? serverErrors[key][0] : "This field is required.");
        });
      } else {
        $("#addQuestionGeneralError").removeClass("hidden").text((xhr.responseJSON && xhr.responseJSON.message) || "Something went wrong while saving.");
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
  restoreActiveFormTab();
  refreshFormCounts();
  </script>
@endsection
