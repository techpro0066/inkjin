@extends('layouts.artist_dashboard_layout')

@section('title', 'FAQ')

@section('styles')
<style>
  .faq-row {
    transition: background 0.15s ease, box-shadow 0.15s ease, opacity 0.15s ease;
    border-bottom: 1px solid rgba(202, 196, 211, 0.15);
    cursor: grab;
  }
  .faq-row:last-child { border-bottom: none; }
  .faq-row:hover { background: #f8f1fb; }
  .faq-row.dragging { opacity: 0.45; cursor: grabbing; }
  .faq-row.drag-over-top { box-shadow: 0 -2px 0 0 #310f7a inset; }
  .faq-row.drag-over-bottom { box-shadow: 0 2px 0 0 #310f7a inset; }
  .faq-row.inactive { opacity: 0.65; }

  .faq-action {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #7a7583;
    transition: background 0.15s, color 0.15s;
  }
  .faq-edit:hover { background: #f2ecf5; color: #310f7a; }
  .faq-delete:hover { background: #fce8e8; color: #ba1a1a; }

  .toggle-switch {
    width: 40px;
    height: 22px;
    border-radius: 11px;
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
    width: 16px;
    height: 16px;
    border-radius: 50%;
    background: white;
    transition: transform 0.3s;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.15);
  }
  .toggle-switch.active::after { transform: translateX(18px); }
  .toggle-switch:disabled { opacity: 0.55; cursor: not-allowed; }

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
  .modal-inner {
    transform: scale(0.96) translateY(10px);
    opacity: 0;
    transition: transform 0.32s cubic-bezier(0.22, 1, 0.36, 1), opacity 0.28s ease;
  }
  .modal-backdrop.modal-open .modal-inner {
    transform: scale(1) translateY(0);
    opacity: 1;
  }

  @media (max-width: 1023px) {
    .main-content { overflow-x: hidden; padding: 16px; padding-top: 70px; }
    body { overflow-x: hidden; }
  }
</style>
@endsection

@section('content')
<main class="main-content flex-1 min-h-screen">
  <div class="p-6 md:p-10 lg:p-12 max-w-6xl">

    @php
      $bookingPageUsername = Auth::user()->userDetail->user_name ?? null;
      $bookingPageUrl = $bookingPageUsername ? 'https://inkjin.com/@'.$bookingPageUsername : null;
    @endphp
    <div class="mb-8">
      <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
        <div>
          <h2 class="text-3xl font-extrabold text-on-surface tracking-tight">Booking Page</h2>
          <p class="text-on-surface-variant mt-1">Manage your intake forms, available designs, portfolio and the style of your page</p>
        </div>
        @if ($bookingPageUrl)
        <a href="{{ $bookingPageUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 text-sm font-semibold text-primary hover:underline bg-primary/5 px-4 py-2 rounded-xl transition-colors shrink-0">
          <span class="material-symbols-outlined text-lg">open_in_new</span> Open your booking page
        </a>
        @endif
      </div>
    </div>

    @include('artist.partials.booking-page-tabs', ['activeTab' => 'faq'])

    <div class="mb-6 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 max-w-3xl">
      <div>
        <h3 class="text-xl font-bold text-on-surface tracking-tight">FAQ</h3>
        <p class="text-on-surface-variant mt-1">Create your own FAQ and answer the questions clients always ask, before they ask them.</p>
      </div>
      <button type="button" id="btnOpenFaqForm" class="inline-flex items-center gap-2 bg-gradient-to-br from-primary to-primary-container text-white font-bold py-3 px-5 rounded-xl shadow-lg shadow-primary/20 hover:opacity-90 transition-all active:scale-[0.98] shrink-0">
        <span class="material-symbols-outlined text-lg">add</span>
        Add FAQ
      </button>
    </div>

    <div id="faqAlert" class="hidden mb-6 max-w-3xl rounded-xl px-4 py-3 text-sm"></div>

    <!-- FAQ list -->
    <div class="bg-white rounded-2xl shadow-sm border border-outline-variant/20 overflow-hidden max-w-3xl">
      <div class="px-6 py-4 border-b border-outline-variant/15 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center">
            <span class="material-symbols-outlined text-primary" style="font-size:18px;">reorder</span>
          </div>
          <div>
            <h3 class="font-bold text-on-surface text-sm">Your FAQs</h3>
            <p class="text-[11px] text-on-surface-variant">Drag to reorder · Toggle to show or hide</p>
          </div>
        </div>
        <span id="faqCount" class="text-xs font-semibold text-on-surface-variant bg-surface-container-high px-2.5 py-1 rounded-lg">{{ $faqs->count() }}</span>
      </div>

      <div id="faqsEmpty" class="{{ $faqs->isEmpty() ? '' : 'hidden' }} px-6 py-12 text-center">
        <span class="material-symbols-outlined text-4xl text-outline/40 mb-3 block">quiz</span>
        <p class="font-semibold text-sm text-on-surface">No FAQs yet</p>
        <p class="text-xs text-on-surface-variant mt-1">Click “Add FAQ” to create your first question and answer.</p>
      </div>

      <div id="faqsListWrap" class="{{ $faqs->isEmpty() ? 'hidden' : '' }}">
        <div id="faqsList">
          @foreach ($faqs as $faq)
            <div
              class="faq-row px-5 sm:px-6 py-4 {{ $faq->is_active ? '' : 'inactive' }}"
              draggable="true"
              data-id="{{ $faq->id }}"
              data-question="{{ e($faq->question) }}"
              data-answer="{{ e($faq->answer) }}"
              data-active="{{ $faq->is_active ? '1' : '0' }}"
              data-update-url="{{ route('artist.faq.update', $faq) }}"
              data-delete-url="{{ route('artist.faq.destroy', $faq) }}"
            >
              <div class="flex items-start gap-3">
                <span class="material-symbols-outlined text-outline/50 text-xl mt-0.5 select-none shrink-0" style="font-size:20px;">drag_indicator</span>
                <div class="min-w-0 flex-1">
                  <p class="text-sm font-semibold text-on-surface faq-question-text">{{ $faq->question }}</p>
                  <p class="text-xs text-on-surface-variant mt-1 leading-relaxed faq-answer-text line-clamp-2">{{ $faq->answer }}</p>
                </div>
                <div class="flex items-center gap-1 shrink-0 pt-0.5">
                  <button type="button" class="toggle-switch js-faq-active-toggle {{ $faq->is_active ? 'active' : '' }}" aria-label="Toggle FAQ visibility" aria-checked="{{ $faq->is_active ? 'true' : 'false' }}" title="{{ $faq->is_active ? 'Visible on public page' : 'Hidden from public page' }}"></button>
                  <button type="button" class="faq-action faq-edit" title="Edit" aria-label="Edit FAQ">
                    <span class="material-symbols-outlined text-[18px]">edit</span>
                  </button>
                  <button type="button" class="faq-action faq-delete" title="Delete" aria-label="Delete FAQ">
                    <span class="material-symbols-outlined text-[18px]">delete</span>
                  </button>
                </div>
              </div>
            </div>
          @endforeach
        </div>
      </div>
    </div>

  </div>
</main>

<!-- Add / Edit FAQ modal -->
<div class="modal-backdrop p-4" id="faqFormModal" role="dialog" aria-modal="true" aria-labelledby="faqFormTitle" aria-hidden="true">
  <div class="modal-inner bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
    <div class="px-6 py-5 border-b border-outline-variant/15 flex items-center justify-between">
      <div class="flex items-center gap-3 min-w-0">
        <div class="w-9 h-9 rounded-xl bg-primary/10 flex items-center justify-center shrink-0">
          <span class="material-symbols-outlined text-primary text-lg" id="faqFormIcon">help</span>
        </div>
        <div class="min-w-0">
          <h3 id="faqFormTitle" class="text-lg font-bold text-on-surface">Add FAQ</h3>
          <p class="text-xs text-on-surface-variant" id="faqFormSubtitle">Write a question and the answer clients should see.</p>
        </div>
      </div>
      <button type="button" id="btnCloseFaqFormModal" class="w-8 h-8 rounded-lg hover:bg-surface-container-low flex items-center justify-center shrink-0" aria-label="Close">
        <span class="material-symbols-outlined text-on-surface-variant">close</span>
      </button>
    </div>
    <form id="faqForm" class="p-6 space-y-5">
      @csrf
      <input type="hidden" id="faq_editing_id" value="">
      <input type="hidden" id="faq_is_active" name="is_active" value="1">

      <div>
        <label for="faq_question" class="block text-sm font-semibold text-on-surface mb-2">Question <span class="text-red-600">*</span></label>
        <input type="text" id="faq_question" name="question" maxlength="500" placeholder="e.g. How old do I need to be?" class="w-full text-sm border border-outline-variant/30 rounded-xl px-4 py-3 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
        <p id="question_error" class="text-error text-xs mt-1 hidden"></p>
      </div>

      <div>
        <label for="faq_answer" class="block text-sm font-semibold text-on-surface mb-2">Answer <span class="text-red-600">*</span></label>
        <textarea id="faq_answer" name="answer" rows="4" maxlength="5000" placeholder="Write a clear answer for your clients…" class="w-full text-sm border border-outline-variant/30 rounded-xl px-4 py-3 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30 resize-none"></textarea>
        <p id="answer_error" class="text-error text-xs mt-1 hidden"></p>
      </div>

      <div class="flex gap-3 pt-1">
        <button type="button" id="btnCancelFaqEdit" class="flex-1 py-2.5 rounded-xl border border-outline-variant/30 text-sm font-semibold text-on-surface-variant hover:bg-surface-container transition-colors">Cancel</button>
        <button type="submit" id="btnSaveFaq" class="flex-1 py-2.5 rounded-xl bg-primary text-white text-sm font-bold hover:bg-primary-container transition-colors inline-flex items-center justify-center gap-2">
          <span class="material-symbols-outlined text-lg btn-icon">add</span>
          <span class="btn-label">Add FAQ</span>
        </button>
      </div>
    </form>
  </div>
</div>

<div class="modal-backdrop" id="deleteFaqModal" aria-hidden="true">
  <div class="modal-inner bg-white rounded-2xl w-full max-w-md mx-4 shadow-2xl overflow-hidden">
    <div class="p-6">
      <div class="flex items-start gap-4">
        <div class="w-12 h-12 rounded-2xl bg-error-container flex items-center justify-center flex-shrink-0">
          <span class="material-symbols-outlined text-error text-2xl">delete_forever</span>
        </div>
        <div class="min-w-0 flex-1">
          <h3 class="text-lg font-bold text-on-surface tracking-tight">Delete this FAQ?</h3>
          <p class="text-sm text-on-surface-variant mt-2 leading-relaxed">
            This will permanently remove <span id="deleteFaqLabel" class="font-semibold text-on-surface"></span>. You cannot undo this.
          </p>
          <p id="deleteFaqError" class="hidden mt-3 text-xs text-error font-semibold leading-snug"></p>
        </div>
      </div>
    </div>
    <div class="px-6 py-4 border-t border-outline-variant/15 flex items-center justify-end gap-3 bg-surface-container-low/30">
      <button type="button" id="btnDeleteFaqCancel" class="text-sm font-semibold text-on-surface-variant hover:text-on-surface px-4 py-2.5 rounded-xl transition-colors">Cancel</button>
      <button type="button" id="btnDeleteFaqConfirm" class="bg-error text-on-error px-5 py-2.5 rounded-xl font-semibold text-sm hover:opacity-95 transition-opacity shadow-sm flex items-center gap-2">
        <span class="material-symbols-outlined text-lg">delete</span>
        Delete
      </button>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script>
(function ($) {
  'use strict';

  const csrfToken = $('meta[name="csrf-token"]').attr('content');
  const routes = {
    store: @json(route('artist.faq.store')),
    reorder: @json(route('artist.faq.reorder')),
  };

  const $formModal = $('#faqFormModal');
  const $form = $('#faqForm');
  const $list = $('#faqsList');
  const $listWrap = $('#faqsListWrap');
  const $empty = $('#faqsEmpty');
  const $alert = $('#faqAlert');
  const $count = $('#faqCount');
  const $deleteModal = $('#deleteFaqModal');
  let deletingRow = null;
  let dragRow = null;

  function showAlert(message, type) {
    $alert
      .removeClass('hidden bg-red-50 text-red-800 bg-emerald-50 text-emerald-800')
      .addClass(type === 'error' ? 'bg-red-50 text-red-800' : 'bg-emerald-50 text-emerald-800')
      .text(message);
    window.setTimeout(function () { $alert.addClass('hidden'); }, 4000);
  }

  function clearErrors() {
    $('#question_error, #answer_error').addClass('hidden').text('');
    $('#faq_question, #faq_answer').removeClass('border-error');
  }

  function setFieldError(field, message) {
    $('#' + field + '_error').removeClass('hidden').text(message);
    $('#faq_' + field).addClass('border-error');
  }

  function updateCount() {
    $count.text($list.find('.faq-row').length);
  }

  function syncEmptyState() {
    const hasRows = $list.find('.faq-row').length > 0;
    $empty.toggleClass('hidden', hasRows);
    $listWrap.toggleClass('hidden', !hasRows);
    updateCount();
  }

  function openModal($modal) {
    $modal.addClass('modal-visible').attr('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
    window.requestAnimationFrame(function () { $modal.addClass('modal-open'); });
  }

  function closeModal($modal) {
    $modal.removeClass('modal-open');
    window.setTimeout(function () {
      $modal.removeClass('modal-visible').attr('aria-hidden', 'true');
      if (!$('.modal-backdrop.modal-visible').length) {
        document.body.style.overflow = '';
      }
    }, 280);
  }

  function resetForm() {
    $('#faq_editing_id').val('');
    $('#faq_question').val('');
    $('#faq_answer').val('');
    $('#faq_is_active').val('1');
    $('#faqFormTitle').text('Add FAQ');
    $('#faqFormSubtitle').text('Write a question and the answer clients should see.');
    $('#faqFormIcon').text('help');
    $('#btnSaveFaq .btn-icon').text('add');
    $('#btnSaveFaq .btn-label').text('Add FAQ');
    clearErrors();
  }

  function openFaqFormModal(mode) {
    if (mode !== 'edit') resetForm();
    openModal($formModal);
    window.setTimeout(function () { $('#faq_question').trigger('focus'); }, 50);
  }

  function closeFaqFormModal() {
    closeModal($formModal);
    resetForm();
  }

  function buildRowHtml(faq) {
    const question = $('<div>').text(faq.question).html();
    const answer = $('<div>').text(faq.answer).html();
    const active = !!faq.is_active;
    return `
      <div class="faq-row px-5 sm:px-6 py-4 ${active ? '' : 'inactive'}"
        draggable="true"
        data-id="${faq.id}"
        data-question="${question}"
        data-answer="${answer}"
        data-active="${active ? '1' : '0'}"
        data-update-url="${faq.update_url}"
        data-delete-url="${faq.delete_url}">
        <div class="flex items-start gap-3">
          <span class="material-symbols-outlined text-outline/50 text-xl mt-0.5 select-none shrink-0" style="font-size:20px;">drag_indicator</span>
          <div class="min-w-0 flex-1">
            <p class="text-sm font-semibold text-on-surface faq-question-text">${question}</p>
            <p class="text-xs text-on-surface-variant mt-1 leading-relaxed faq-answer-text line-clamp-2">${answer}</p>
          </div>
          <div class="flex items-center gap-1 shrink-0 pt-0.5">
            <button type="button" class="toggle-switch js-faq-active-toggle ${active ? 'active' : ''}" aria-label="Toggle FAQ visibility" aria-checked="${active ? 'true' : 'false'}" title="${active ? 'Visible on public page' : 'Hidden from public page'}"></button>
            <button type="button" class="faq-action faq-edit" title="Edit" aria-label="Edit FAQ">
              <span class="material-symbols-outlined text-[18px]">edit</span>
            </button>
            <button type="button" class="faq-action faq-delete" title="Delete" aria-label="Delete FAQ">
              <span class="material-symbols-outlined text-[18px]">delete</span>
            </button>
          </div>
        </div>
      </div>`;
  }

  function updateRow($row, faq) {
    $row
      .attr('data-question', faq.question)
      .attr('data-answer', faq.answer)
      .attr('data-active', faq.is_active ? '1' : '0')
      .toggleClass('inactive', !faq.is_active);
    $row.find('.faq-question-text').text(faq.question);
    $row.find('.faq-answer-text').text(faq.answer);
    $row.find('.js-faq-active-toggle')
      .toggleClass('active', !!faq.is_active)
      .attr('aria-checked', faq.is_active ? 'true' : 'false')
      .attr('title', faq.is_active ? 'Visible on public page' : 'Hidden from public page');
  }

  $('#btnOpenFaqForm').on('click', function () {
    openFaqFormModal('add');
  });

  $('#btnCancelFaqEdit, #btnCloseFaqFormModal').on('click', function () {
    closeFaqFormModal();
  });

  $formModal.on('click', function (e) {
    if (e.target === this) closeFaqFormModal();
  });

  $(document).on('keydown', function (e) {
    if (e.key !== 'Escape') return;
    if ($formModal.hasClass('modal-visible')) closeFaqFormModal();
    else if ($deleteModal.hasClass('modal-visible')) {
      closeModal($deleteModal);
      deletingRow = null;
    }
  });

  $form.on('submit', function (e) {
    e.preventDefault();
    clearErrors();

    const id = $('#faq_editing_id').val();
    const payload = {
      question: $.trim($('#faq_question').val()),
      answer: $.trim($('#faq_answer').val()),
      is_active: $('#faq_is_active').val() === '1',
    };

    const $row = id ? $list.find('.faq-row[data-id="' + id + '"]') : null;
    const url = id ? $row.data('update-url') : routes.store;
    const method = id ? 'PUT' : 'POST';

    $('#btnSaveFaq').prop('disabled', true);

    $.ajax({
      url: url,
      method: method,
      headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
      contentType: 'application/json',
      data: JSON.stringify(payload),
      success: function (res) {
        if (id) {
          updateRow($row, res.faq);
        } else {
          $list.append(buildRowHtml(res.faq));
        }
        closeFaqFormModal();
        syncEmptyState();
        showAlert(res.message || 'Saved.', 'success');
      },
      error: function (xhr) {
        const data = xhr.responseJSON || {};
        if (data.errors) {
          if (data.errors.question) setFieldError('question', data.errors.question[0]);
          if (data.errors.answer) setFieldError('answer', data.errors.answer[0]);
        } else {
          showAlert(data.message || 'Unable to save FAQ.', 'error');
        }
      },
      complete: function () {
        $('#btnSaveFaq').prop('disabled', false);
      }
    });
  });

  $(document).on('click', '.faq-edit', function (e) {
    e.stopPropagation();
    const $row = $(this).closest('.faq-row');
    resetForm();
    $('#faq_editing_id').val($row.data('id'));
    $('#faq_question').val($row.attr('data-question'));
    $('#faq_answer').val($row.attr('data-answer'));
    $('#faq_is_active').val(String($row.data('active')) === '1' ? '1' : '0');
    $('#faqFormTitle').text('Edit FAQ');
    $('#faqFormSubtitle').text('Update this question and answer.');
    $('#faqFormIcon').text('edit');
    $('#btnSaveFaq .btn-icon').text('save');
    $('#btnSaveFaq .btn-label').text('Update FAQ');
    openFaqFormModal('edit');
  });

  $(document).on('click', '.js-faq-active-toggle', function (e) {
    e.stopPropagation();
    const $toggle = $(this);
    if ($toggle.prop('disabled')) return;

    const $row = $toggle.closest('.faq-row');
    const wasActive = String($row.data('active')) === '1';
    const nowActive = !wasActive;

    $toggle.toggleClass('active', nowActive).attr('aria-checked', nowActive ? 'true' : 'false').prop('disabled', true);
    $row.data('active', nowActive ? '1' : '0').toggleClass('inactive', !nowActive);

    $.ajax({
      url: $row.data('update-url'),
      method: 'PUT',
      headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
      contentType: 'application/json',
      data: JSON.stringify({ is_active: nowActive }),
      success: function (res) {
        if (res.faq) updateRow($row, res.faq);
      },
      error: function (xhr) {
        $toggle.toggleClass('active', wasActive).attr('aria-checked', wasActive ? 'true' : 'false');
        $row.data('active', wasActive ? '1' : '0').toggleClass('inactive', !wasActive);
        const data = xhr.responseJSON || {};
        showAlert(data.message || 'Unable to update FAQ.', 'error');
      },
      complete: function () {
        $toggle.prop('disabled', false);
      }
    });
  });

  $(document).on('click', '.faq-delete', function (e) {
    e.stopPropagation();
    deletingRow = $(this).closest('.faq-row');
    $('#deleteFaqLabel').text(deletingRow.attr('data-question') || 'this FAQ');
    $('#deleteFaqError').addClass('hidden').text('');
    openModal($deleteModal);
  });

  $('#btnDeleteFaqCancel').on('click', function () {
    closeModal($deleteModal);
    deletingRow = null;
  });
  $deleteModal.on('click', function (e) {
    if (e.target === this) {
      closeModal($deleteModal);
      deletingRow = null;
    }
  });

  $('#btnDeleteFaqConfirm').on('click', function () {
    if (!deletingRow) return;
    const $btn = $(this);
    $btn.prop('disabled', true);

    $.ajax({
      url: deletingRow.data('delete-url'),
      method: 'DELETE',
      headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
      success: function (res) {
        deletingRow.remove();
        deletingRow = null;
        closeModal($deleteModal);
        syncEmptyState();
        showAlert(res.message || 'Deleted.', 'success');
      },
      error: function (xhr) {
        const data = xhr.responseJSON || {};
        $('#deleteFaqError').removeClass('hidden').text(data.message || 'Unable to delete FAQ.');
      },
      complete: function () {
        $btn.prop('disabled', false);
      }
    });
  });

  function persistOrder() {
    const ids = [];
    $list.find('.faq-row').each(function () {
      ids.push(Number($(this).data('id')));
    });
    if (!ids.length) return;

    $.ajax({
      url: routes.reorder,
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
      contentType: 'application/json',
      data: JSON.stringify({ ids: ids }),
      error: function () {
        showAlert('Unable to save FAQ order.', 'error');
      }
    });
  }

  $list.on('dragstart', '.faq-row', function (e) {
    if ($(e.target).closest('.js-faq-active-toggle, .faq-edit, .faq-delete').length) {
      e.preventDefault();
      return;
    }
    dragRow = this;
    $(this).addClass('dragging');
    e.originalEvent.dataTransfer.effectAllowed = 'move';
  });

  $list.on('dragend', '.faq-row', function () {
    $(this).removeClass('dragging');
    $list.find('.faq-row').removeClass('drag-over-top drag-over-bottom');
    if (dragRow) persistOrder();
    dragRow = null;
  });

  $list.on('dragover', '.faq-row', function (e) {
    e.preventDefault();
    if (!dragRow || dragRow === this) return;
    const rect = this.getBoundingClientRect();
    const before = (e.originalEvent.clientY - rect.top) < rect.height / 2;
    $list.find('.faq-row').removeClass('drag-over-top drag-over-bottom');
    $(this).addClass(before ? 'drag-over-top' : 'drag-over-bottom');
    if (before) this.parentNode.insertBefore(dragRow, this);
    else this.parentNode.insertBefore(dragRow, this.nextSibling);
  });

  syncEmptyState();
})(jQuery);
</script>
@endsection
