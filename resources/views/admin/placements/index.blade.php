@extends('layouts.admin_dashboard_layout')

@section('title', 'Placements')

@section('styles')
<style>
  .placement-row { transition: background 0.15s; }
  .placement-row:hover { background: #f8f1fb; }
  .sortable-ghost { opacity: 0.45; background: #f8f1fb; }
  .sortable-chosen { cursor: grabbing; }
  @media (max-width: 1023px) {
    .main-content { overflow-x: hidden; padding: 16px; padding-top: 70px; }
    body { overflow-x: hidden; }
  }
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
  .toggle-switch--table {
    width: 40px;
    height: 22px;
  }
  .toggle-switch--table::after {
    width: 16px;
    height: 16px;
    top: 3px;
    left: 3px;
  }
  .toggle-switch--table.active::after { transform: translateX(18px); }
  .toggle-switch:disabled {
    opacity: 0.55;
    cursor: not-allowed;
  }
</style>
@endsection

@section('content')
<main class="main-content flex-1 min-h-screen">
  <div class="p-6 md:p-10 lg:p-12 max-w-5xl">

    <div class="mb-8 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
      <div>
        <h2 class="text-3xl font-extrabold text-on-surface tracking-tight">Placements</h2>
        <p class="text-on-surface-variant mt-1">Manage body placements used across the platform. Drag rows to change display order.</p>
      </div>
      <button type="button" id="btnAddPlacement" class="inline-flex items-center gap-2 bg-primary text-white px-4 py-2.5 rounded-xl font-semibold text-sm hover:bg-primary-container transition-colors shrink-0">
        <span class="material-symbols-outlined text-[18px]">add</span> Add Placement
      </button>
    </div>

    <div id="placementsAlert" class="hidden mb-4 rounded-xl border px-4 py-3 text-sm font-medium"></div>

    <div class="bg-white rounded-2xl shadow-sm border border-outline-variant/20 overflow-hidden">
      <div class="px-6 py-5 border-b border-outline-variant/15 hidden sm:grid sm:grid-cols-[auto_1fr_150px_150px_auto] sm:gap-4 sm:items-center text-[10px] font-bold uppercase tracking-wider text-on-surface-variant">
        <span></span>
        <span>Name</span>
        <span>Active</span>
        <span>Show on Question</span>
        <span class="text-right">Actions</span>
      </div>
      <div class="divide-y divide-outline-variant/10" id="placementsList">
        @forelse($placements as $placement)
          <div
            class="placement-row px-4 sm:px-6 py-4 flex flex-col sm:grid sm:grid-cols-[auto_1fr_150px_150px_auto] sm:gap-4 sm:items-center gap-3"
            draggable="true"
            data-placement-id="{{ $placement->id }}"
            data-placement-name="{{ e($placement->name) }}"
            data-placement-status="{{ $placement->status }}"
            data-placement-sort-order="{{ $placement->sort_order }}"
            data-placement-appear-on-question="{{ $placement->appear_on_question ? '1' : '0' }}"
          >
            <span class="material-symbols-outlined text-outline cursor-grab" style="font-size:20px;">drag_indicator</span>
            <div class="min-w-0">
              <p class="text-sm font-semibold text-on-surface js-placement-name">{{ $placement->name }}</p>
            </div>
            <div class="flex items-center justify-between sm:justify-start gap-2">
              <span class="text-[10px] font-semibold uppercase tracking-wide text-on-surface-variant sm:hidden">Active</span>
              <span class="text-xs font-medium min-w-[52px] js-row-status-label {{ $placement->status === 'active' ? 'text-emerald-700' : 'text-on-surface-variant' }}">{{ $placement->status === 'active' ? 'Active' : 'Inactive' }}</span>
              <button type="button" class="toggle-switch toggle-switch--table js-row-status-toggle {{ $placement->status === 'active' ? 'active' : '' }}" aria-label="Toggle active status" aria-checked="{{ $placement->status === 'active' ? 'true' : 'false' }}"></button>
            </div>
            <div class="flex items-center justify-between sm:justify-start gap-2">
              <span class="text-[10px] font-semibold uppercase tracking-wide text-on-surface-variant sm:hidden">Show on Question</span>
              <span class="text-xs font-medium min-w-[28px] js-row-question-label {{ $placement->appear_on_question ? 'text-blue-700' : 'text-on-surface-variant' }}">{{ $placement->appear_on_question ? 'Yes' : 'No' }}</span>
              <button type="button" class="toggle-switch toggle-switch--table js-row-question-toggle {{ $placement->appear_on_question ? 'active' : '' }}" aria-label="Toggle show on question" aria-checked="{{ $placement->appear_on_question ? 'true' : 'false' }}"></button>
            </div>
            <div class="flex gap-1 justify-end">
              <button type="button" class="js-edit-placement w-8 h-8 rounded-lg flex items-center justify-center hover:bg-surface-container-low" title="Edit">
                <span class="material-symbols-outlined text-on-surface-variant" style="font-size:16px;">edit</span>
              </button>
              <button type="button" class="js-delete-placement w-8 h-8 rounded-lg flex items-center justify-center hover:bg-red-50" title="Delete">
                <span class="material-symbols-outlined text-red-500" style="font-size:16px;">delete</span>
              </button>
            </div>
          </div>
        @empty
          <p id="placementsEmptyMsg" class="px-6 py-10 text-sm text-on-surface-variant text-center">No placements yet. Add your first placement to get started.</p>
        @endforelse
      </div>
    </div>
  </div>
</main>

<!-- Add / Edit modal -->
<div id="placementModal" class="modal-backdrop p-4" role="dialog" aria-modal="true" aria-labelledby="placementModalTitle">
  <div class="modal-inner bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
    <div class="px-6 py-5 border-b border-outline-variant/15 flex items-center justify-between">
      <h3 id="placementModalTitle" class="text-lg font-bold text-on-surface">Add Placement</h3>
      <button type="button" id="btnClosePlacementModal" class="w-8 h-8 rounded-lg hover:bg-surface-container-low flex items-center justify-center">
        <span class="material-symbols-outlined text-on-surface-variant">close</span>
      </button>
    </div>
    <form id="placementForm" class="p-6 space-y-5">
      <input type="hidden" id="editingPlacementId" value="">
      <div>
        <label for="placementName" class="block text-xs font-semibold text-on-surface-variant mb-1.5">Name <span class="text-red-600">*</span></label>
        <input type="text" id="placementName" name="name" maxlength="255" class="w-full text-sm border border-outline-variant/30 rounded-xl px-3 py-2.5 bg-white focus:outline-none focus:ring-2 focus:ring-primary/30" placeholder="e.g. Forearm">
        <p id="placementNameError" class="text-error text-xs mt-1 hidden"></p>
      </div>
      <div class="flex items-center justify-between gap-4">
        <div>
          <p class="text-sm font-semibold text-on-surface">Status</p>
          <p class="text-xs text-on-surface-variant">Inactive placements are hidden from pickers.</p>
        </div>
        <div class="flex items-center gap-2">
          <span class="text-xs text-on-surface-variant" id="placementStatusLabel">Active</span>
          <button type="button" id="placementStatusToggle" class="toggle-switch active" aria-checked="true" data-value="active"></button>
          <input type="hidden" id="placementStatus" name="status" value="active">
        </div>
      </div>
      <div class="flex items-center justify-between gap-4">
        <div>
          <p class="text-sm font-semibold text-on-surface">Appear on question</p>
          <p class="text-xs text-on-surface-variant">Show this placement as an option in booking questions.</p>
        </div>
        <div class="flex items-center gap-2">
          <span class="text-xs text-on-surface-variant" id="placementQuestionLabel">No</span>
          <button type="button" id="placementQuestionToggle" class="toggle-switch" aria-checked="false" data-value="0"></button>
          <input type="hidden" id="placementAppearOnQuestion" name="appear_on_question" value="0">
        </div>
      </div>
      <p id="placementFormError" class="text-error text-sm hidden"></p>
      <div class="flex gap-3 pt-2">
        <button type="button" id="btnCancelPlacementModal" class="flex-1 py-2.5 rounded-xl border border-outline-variant/30 text-sm font-semibold text-on-surface-variant hover:bg-surface-container transition-colors">Cancel</button>
        <button type="submit" id="btnSubmitPlacement" class="flex-1 py-2.5 rounded-xl bg-primary text-white text-sm font-bold hover:bg-primary-container transition-colors">Save Placement</button>
      </div>
    </form>
  </div>
</div>

<!-- Delete modal -->
<div id="deletePlacementModal" class="modal-backdrop p-4" role="dialog" aria-modal="true">
  <div class="modal-inner bg-white rounded-2xl shadow-2xl w-full max-w-md p-6">
    <h3 class="text-lg font-bold text-on-surface mb-2">Delete placement?</h3>
    <p class="text-sm text-on-surface-variant mb-6">This will permanently remove <strong id="deletePlacementName">this placement</strong>. Existing artist data using this placement name will not be changed.</p>
    <input type="hidden" id="deletingPlacementId" value="">
    <div class="flex gap-3">
      <button type="button" id="btnCancelDeletePlacement" class="flex-1 py-2.5 rounded-xl border border-outline-variant/30 text-sm font-semibold text-on-surface-variant">Cancel</button>
      <button type="button" id="btnConfirmDeletePlacement" class="flex-1 py-2.5 rounded-xl bg-red-600 text-white text-sm font-bold hover:bg-red-700">Delete</button>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.3/Sortable.min.js"></script>
<script>
(function ($) {
  'use strict';

  const csrfToken = $('meta[name="csrf-token"]').attr('content');
  const routes = {
    store: @json(route('admin.placements.store')),
    update: @json(url('/admin/placements/__ID__')),
    destroy: @json(url('/admin/placements/__ID__')),
    reorder: @json(route('admin.placements.reorder')),
  };

  const $placementModal = $('#placementModal');
  const $deleteModal = $('#deletePlacementModal');
  const $placementsList = $('#placementsList');
  const $placementsAlert = $('#placementsAlert');

  function showAlert(message, type) {
    $placementsAlert
      .removeClass('hidden bg-red-50 border-red-200 text-red-800 bg-emerald-50 border-emerald-200 text-emerald-800')
      .addClass(type === 'error' ? 'bg-red-50 border-red-200 text-red-800' : 'bg-emerald-50 border-emerald-200 text-emerald-800')
      .text(message);
    window.setTimeout(function () { $placementsAlert.addClass('hidden'); }, 4000);
  }

  function openModal($modal) {
    $modal.addClass('modal-visible');
    window.requestAnimationFrame(function () { $modal.addClass('modal-open'); });
  }

  function closeModal($modal) {
    $modal.removeClass('modal-open');
    window.setTimeout(function () { $modal.removeClass('modal-visible'); }, 280);
  }

  function clearFormErrors() {
    $('#placementNameError, #placementFormError').addClass('hidden').text('');
    $('#placementName').removeClass('border-error ring-1 ring-error/40');
  }

  function setToggle($toggle, $hidden, $label, active, activeLabel, inactiveLabel) {
    $toggle.toggleClass('active', active).attr('aria-checked', active ? 'true' : 'false');
    $hidden.val(active ? ($toggle.attr('id') === 'placementStatusToggle' ? 'active' : '1') : ($toggle.attr('id') === 'placementStatusToggle' ? 'inactive' : '0'));
    $label.text(active ? activeLabel : inactiveLabel);
  }

  function resetPlacementForm() {
    $('#editingPlacementId').val('');
    $('#placementModalTitle').text('Add Placement');
    $('#btnSubmitPlacement').text('Save Placement');
    $('#placementName').val('');
    setToggle($('#placementStatusToggle'), $('#placementStatus'), $('#placementStatusLabel'), true, 'Active', 'Inactive');
    setToggle($('#placementQuestionToggle'), $('#placementAppearOnQuestion'), $('#placementQuestionLabel'), false, 'Yes', 'No');
    clearFormErrors();
  }

  function setRowStatusToggle($row, isActive) {
    const $toggle = $row.find('.js-row-status-toggle');
    const $label = $row.find('.js-row-status-label');
    $toggle.toggleClass('active', isActive).attr('aria-checked', isActive ? 'true' : 'false');
    $label
      .text(isActive ? 'Active' : 'Inactive')
      .toggleClass('text-emerald-700', isActive)
      .toggleClass('text-on-surface-variant', !isActive);
    $row.data('placement-status', isActive ? 'active' : 'inactive');
  }

  function setRowQuestionToggle($row, isOn) {
    const $toggle = $row.find('.js-row-question-toggle');
    const $label = $row.find('.js-row-question-label');
    $toggle.toggleClass('active', isOn).attr('aria-checked', isOn ? 'true' : 'false');
    $label
      .text(isOn ? 'Yes' : 'No')
      .toggleClass('text-blue-700', isOn)
      .toggleClass('text-on-surface-variant', !isOn);
    $row.data('placement-appear-on-question', isOn ? '1' : '0');
  }

  function setRowToggleLock($row, savingField) {
    const $status = $row.find('.js-row-status-toggle');
    const $question = $row.find('.js-row-question-toggle');
    if (savingField === 'status') {
      $status.prop('disabled', false);
      $question.prop('disabled', true);
    } else if (savingField === 'question') {
      $status.prop('disabled', true);
      $question.prop('disabled', false);
    } else {
      $status.prop('disabled', false);
      $question.prop('disabled', false);
    }
  }

  function savePlacementField($row, payload, savingField, revertFn) {
    const id = $row.data('placement-id');
    setRowToggleLock($row, savingField);

    $.ajax({
      url: routes.update.replace('__ID__', id),
      method: 'PUT',
      headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
      contentType: 'application/json',
      data: JSON.stringify(payload),
      success: function (res) {
        if (!res.placement) return;
        if (savingField === 'status') {
          setRowStatusToggle($row, res.placement.status === 'active');
        } else if (savingField === 'question') {
          setRowQuestionToggle($row, !!res.placement.appear_on_question);
        }
      },
      error: function (xhr) {
        if (typeof revertFn === 'function') revertFn();
        const data = xhr.responseJSON || {};
        showAlert(data.message || 'Unable to update placement.', 'error');
      },
      complete: function () {
        setRowToggleLock($row, null);
      }
    });
  }

  function statusToggleCellHtml(isActive) {
    const labelClass = isActive ? 'text-emerald-700' : 'text-on-surface-variant';
    const activeClass = isActive ? 'active' : '';
    const checked = isActive ? 'true' : 'false';
    return `
      <div class="flex items-center justify-between sm:justify-start gap-2">
        <span class="text-[10px] font-semibold uppercase tracking-wide text-on-surface-variant sm:hidden">Active</span>
        <span class="text-xs font-medium min-w-[52px] js-row-status-label ${labelClass}">${isActive ? 'Active' : 'Inactive'}</span>
        <button type="button" class="toggle-switch toggle-switch--table js-row-status-toggle ${activeClass}" aria-label="Toggle active status" aria-checked="${checked}"></button>
      </div>`;
  }

  function questionToggleCellHtml(isOn) {
    const labelClass = isOn ? 'text-blue-700' : 'text-on-surface-variant';
    const activeClass = isOn ? 'active' : '';
    const checked = isOn ? 'true' : 'false';
    return `
      <div class="flex items-center justify-between sm:justify-start gap-2">
        <span class="text-[10px] font-semibold uppercase tracking-wide text-on-surface-variant sm:hidden">Show on Question</span>
        <span class="text-xs font-medium min-w-[28px] js-row-question-label ${labelClass}">${isOn ? 'Yes' : 'No'}</span>
        <button type="button" class="toggle-switch toggle-switch--table js-row-question-toggle ${activeClass}" aria-label="Toggle show on question" aria-checked="${checked}"></button>
      </div>`;
  }

  function buildRowHtml(placement) {
    const appear = placement.appear_on_question ? '1' : '0';
    const name = $('<div>').text(placement.name).html();
    const isActive = placement.status === 'active';
    return `
      <div class="placement-row px-4 sm:px-6 py-4 flex flex-col sm:grid sm:grid-cols-[auto_1fr_150px_150px_auto] sm:gap-4 sm:items-center gap-3"
        draggable="true"
        data-placement-id="${placement.id}"
        data-placement-name="${name}"
        data-placement-status="${placement.status}"
        data-placement-sort-order="${placement.sort_order}"
        data-placement-appear-on-question="${appear}">
        <span class="material-symbols-outlined text-outline cursor-grab" style="font-size:20px;">drag_indicator</span>
        <div class="min-w-0">
          <p class="text-sm font-semibold text-on-surface js-placement-name">${name}</p>
        </div>
        ${statusToggleCellHtml(isActive)}
        ${questionToggleCellHtml(!!placement.appear_on_question)}
        <div class="flex gap-1 justify-end">
          <button type="button" class="js-edit-placement w-8 h-8 rounded-lg flex items-center justify-center hover:bg-surface-container-low" title="Edit">
            <span class="material-symbols-outlined text-on-surface-variant" style="font-size:16px;">edit</span>
          </button>
          <button type="button" class="js-delete-placement w-8 h-8 rounded-lg flex items-center justify-center hover:bg-red-50" title="Delete">
            <span class="material-symbols-outlined text-red-500" style="font-size:16px;">delete</span>
          </button>
        </div>
      </div>`;
  }

  function updateRow($row, placement) {
    $row
      .data('placement-name', placement.name)
      .data('placement-sort-order', placement.sort_order);
    $row.find('.js-placement-name').text(placement.name);
    setRowStatusToggle($row, placement.status === 'active');
    setRowQuestionToggle($row, !!placement.appear_on_question);
  }

  $('#btnAddPlacement').on('click', function () {
    resetPlacementForm();
    openModal($placementModal);
  });

  $('#btnClosePlacementModal, #btnCancelPlacementModal').on('click', function () { closeModal($placementModal); });
  $placementModal.on('click', function (e) { if (e.target === this) closeModal($placementModal); });

  $('#btnCancelDeletePlacement').on('click', function () { closeModal($deleteModal); });
  $deleteModal.on('click', function (e) { if (e.target === this) closeModal($deleteModal); });

  $('#placementStatusToggle').on('click', function () {
    const active = !$(this).hasClass('active');
    setToggle($(this), $('#placementStatus'), $('#placementStatusLabel'), active, 'Active', 'Inactive');
  });

  $('#placementQuestionToggle').on('click', function () {
    const active = !$(this).hasClass('active');
    setToggle($(this), $('#placementAppearOnQuestion'), $('#placementQuestionLabel'), active, 'Yes', 'No');
  });

  $(document).on('click', '.js-row-status-toggle', function (e) {
    e.stopPropagation();
    const $toggle = $(this);
    if ($toggle.prop('disabled')) return;

    const $row = $toggle.closest('.placement-row');
    const wasActive = String($row.data('placement-status')) === 'active';
    const nowActive = !wasActive;

    setRowStatusToggle($row, nowActive);
    savePlacementField($row, { status: nowActive ? 'active' : 'inactive' }, 'status', function () {
      setRowStatusToggle($row, wasActive);
    });
  });

  $(document).on('click', '.js-row-question-toggle', function (e) {
    e.stopPropagation();
    const $toggle = $(this);
    if ($toggle.prop('disabled')) return;

    const $row = $toggle.closest('.placement-row');
    const wasOn = String($row.data('placement-appear-on-question')) === '1';
    const nowOn = !wasOn;

    setRowQuestionToggle($row, nowOn);
    savePlacementField($row, { appear_on_question: nowOn }, 'question', function () {
      setRowQuestionToggle($row, wasOn);
    });
  });

  $(document).on('click', '.js-edit-placement', function () {
    const $row = $(this).closest('.placement-row');
    resetPlacementForm();
    $('#editingPlacementId').val($row.data('placement-id'));
    $('#placementModalTitle').text('Edit Placement');
    $('#btnSubmitPlacement').text('Update Placement');
    $('#placementName').val($row.data('placement-name'));
    const isActive = String($row.data('placement-status')) === 'active';
    const onQuestion = String($row.data('placement-appear-on-question')) === '1';
    setToggle($('#placementStatusToggle'), $('#placementStatus'), $('#placementStatusLabel'), isActive, 'Active', 'Inactive');
    setToggle($('#placementQuestionToggle'), $('#placementAppearOnQuestion'), $('#placementQuestionLabel'), onQuestion, 'Yes', 'No');
    openModal($placementModal);
  });

  $(document).on('click', '.js-delete-placement', function () {
    const $row = $(this).closest('.placement-row');
    $('#deletingPlacementId').val($row.data('placement-id'));
    $('#deletePlacementName').text($row.data('placement-name'));
    openModal($deleteModal);
  });

  $('#placementForm').on('submit', function (e) {
    e.preventDefault();
    clearFormErrors();

    const id = $('#editingPlacementId').val();
    const payload = {
      name: $.trim($('#placementName').val()),
      status: $('#placementStatus').val(),
      appear_on_question: $('#placementAppearOnQuestion').val() === '1',
    };

    const url = id ? routes.update.replace('__ID__', id) : routes.store;
    const method = id ? 'PUT' : 'POST';

    $('#btnSubmitPlacement').prop('disabled', true);

    $.ajax({
      url: url,
      method: method,
      headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
      contentType: 'application/json',
      data: JSON.stringify(payload),
      success: function (res) {
        const placement = res.placement;
        if (id) {
          updateRow($placementsList.find(`[data-placement-id="${id}"]`), placement);
        } else {
          $('#placementsEmptyMsg').remove();
          $placementsList.append(buildRowHtml(placement));
        }
        closeModal($placementModal);
        showAlert(res.message || 'Saved.', 'success');
      },
      error: function (xhr) {
        const data = xhr.responseJSON || {};
        if (data.errors) {
          if (data.errors.name) {
            $('#placementNameError').removeClass('hidden').text(data.errors.name[0]);
            $('#placementName').addClass('border-error ring-1 ring-error/40');
          }
        } else {
          $('#placementFormError').removeClass('hidden').text(data.message || 'Unable to save placement.');
        }
      },
      complete: function () {
        $('#btnSubmitPlacement').prop('disabled', false);
      }
    });
  });

  $('#btnConfirmDeletePlacement').on('click', function () {
    const id = $('#deletingPlacementId').val();
    if (!id) return;

    $(this).prop('disabled', true);

    $.ajax({
      url: routes.destroy.replace('__ID__', id),
      method: 'DELETE',
      headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
      success: function (res) {
        $placementsList.find(`[data-placement-id="${id}"]`).remove();
        if (!$placementsList.find('.placement-row').length) {
          $placementsList.html('<p id="placementsEmptyMsg" class="px-6 py-10 text-sm text-on-surface-variant text-center">No placements yet. Add your first placement to get started.</p>');
        }
        closeModal($deleteModal);
        showAlert(res.message || 'Deleted.', 'success');
      },
      error: function (xhr) {
        const data = xhr.responseJSON || {};
        showAlert(data.message || 'Unable to delete placement.', 'error');
      },
      complete: function () {
        $('#btnConfirmDeletePlacement').prop('disabled', false);
      }
    });
  });

  if (typeof Sortable !== 'undefined' && $placementsList.length) {
    Sortable.create($placementsList[0], {
      handle: '.material-symbols-outlined',
      animation: 150,
      ghostClass: 'sortable-ghost',
      chosenClass: 'sortable-chosen',
      draggable: '.placement-row',
      filter: '.js-row-status-toggle, .js-row-question-toggle, .js-edit-placement, .js-delete-placement',
      preventOnFilter: true,
      onEnd: function () {
        const items = [];
        $placementsList.find('.placement-row').each(function (index) {
          const id = $(this).data('placement-id');
          const order = index + 1;
          items.push({ id: id, sort_order: order });
          $(this).data('placement-sort-order', order);
        });

        $.ajax({
          url: routes.reorder,
          method: 'POST',
          headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
          contentType: 'application/json',
          data: JSON.stringify({ items: items }),
          error: function () {
            showAlert('Unable to save sort order.', 'error');
          }
        });
      }
    });
  }
})(jQuery);
</script>
@endsection
