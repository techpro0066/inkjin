@extends('layouts.admin_dashboard_layout')

@section('title', 'Sizes')

@section('styles')
<style>
  .size-row { transition: background 0.15s; }
  .size-row:hover { background: #f8f1fb; }
  .sortable-ghost { opacity: 0.45; background: #f8f1fb; }
  .sortable-chosen { cursor: grabbing; }
  .unit-chip {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 12px;
    font-weight: 600;
    padding: 4px 10px;
    border-radius: 8px;
    background: #f2ecf5;
    color: #494552;
    white-space: nowrap;
  }
  .unit-chip--cm { background: rgba(49, 15, 122, 0.08); color: #310f7a; }
  .unit-chip--in { background: rgba(25, 118, 210, 0.08); color: #1565c0; }
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
  @media (max-width: 1023px) {
    .main-content { overflow-x: hidden; padding: 16px; padding-top: 70px; }
    body { overflow-x: hidden; }
  }
</style>
@endsection

@section('content')
<main class="main-content flex-1 min-h-screen">
  <div class="p-6 md:p-10 lg:p-12 max-w-5xl">

    <div class="mb-8 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
      <div>
        <h2 class="text-3xl font-extrabold text-on-surface tracking-tight">Sizes</h2>
        <p class="text-on-surface-variant mt-1">Define tattoo size ranges in centimeters and their matching inch values. Drag rows to change display order.</p>
      </div>
      <button type="button" id="btnAddSize" class="inline-flex items-center gap-2 bg-primary text-white px-4 py-2.5 rounded-xl font-semibold text-sm hover:bg-primary-container transition-colors shrink-0">
        <span class="material-symbols-outlined text-[18px]">add</span> Add Size
      </button>
    </div>

    <div id="sizesAlert" class="hidden mb-4 rounded-xl border px-4 py-3 text-sm font-medium"></div>

    <div class="bg-white rounded-2xl shadow-sm border border-outline-variant/20 overflow-hidden">
      <div class="px-6 py-5 border-b border-outline-variant/15 hidden lg:grid lg:grid-cols-[auto_minmax(0,1.1fr)_minmax(0,1fr)_minmax(0,1fr)_120px_auto] lg:gap-4 lg:items-center text-[10px] font-bold uppercase tracking-wider text-on-surface-variant">
        <span></span>
        <span>Label</span>
        <span>Size (cm)</span>
        <span>Size (in)</span>
        <span>Active</span>
        <span class="text-right">Actions</span>
      </div>
      <div class="divide-y divide-outline-variant/10" id="sizesList">
        @forelse($sizes as $size)
          <div
            class="size-row px-4 sm:px-6 py-4 flex flex-col lg:grid lg:grid-cols-[auto_minmax(0,1.1fr)_minmax(0,1fr)_minmax(0,1fr)_120px_auto] lg:gap-4 lg:items-center gap-3"
            data-size-id="{{ $size->id }}"
            data-size-label="{{ e($size->label) }}"
            data-cm-min="{{ $size->cm_min }}"
            data-cm-max="{{ $size->cm_max ?? '' }}"
            data-in-min="{{ $size->in_min }}"
            data-in-max="{{ $size->in_max ?? '' }}"
            data-size-status="{{ $size->status }}"
            data-size-sort-order="{{ $size->sort_order }}"
          >
            <span class="material-symbols-outlined text-outline cursor-grab" style="font-size:20px;">drag_indicator</span>
            <div class="min-w-0">
              <p class="text-[10px] font-semibold uppercase tracking-wide text-on-surface-variant lg:hidden mb-1">Label</p>
              <p class="text-sm font-semibold text-on-surface js-size-label">{{ $size->label }}</p>
            </div>
            <div>
              <p class="text-[10px] font-semibold uppercase tracking-wide text-on-surface-variant lg:hidden mb-1">Size (cm)</p>
              <span class="unit-chip unit-chip--cm js-size-cm">{{ $size->cmRangeLabel() }}</span>
            </div>
            <div>
              <p class="text-[10px] font-semibold uppercase tracking-wide text-on-surface-variant lg:hidden mb-1">Size (in)</p>
              <span class="unit-chip unit-chip--in js-size-in">{{ $size->inRangeLabel() }}</span>
            </div>
            <div class="flex items-center justify-between lg:justify-start gap-2">
              <span class="text-[10px] font-semibold uppercase tracking-wide text-on-surface-variant lg:hidden">Active</span>
              <span class="text-xs font-medium min-w-[52px] js-row-status-label {{ $size->status === 'active' ? 'text-emerald-700' : 'text-on-surface-variant' }}">
                {{ $size->status === 'active' ? 'Active' : 'Inactive' }}
              </span>
              <button type="button" class="toggle-switch toggle-switch--table js-row-status-toggle {{ $size->status === 'active' ? 'active' : '' }}" aria-label="Toggle active status" aria-checked="{{ $size->status === 'active' ? 'true' : 'false' }}"></button>
            </div>
            <div class="flex gap-1 justify-end">
              <button type="button" class="js-edit-size w-8 h-8 rounded-lg flex items-center justify-center hover:bg-surface-container-low" title="Edit">
                <span class="material-symbols-outlined text-on-surface-variant" style="font-size:16px;">edit</span>
              </button>
              <button type="button" class="js-delete-size w-8 h-8 rounded-lg flex items-center justify-center hover:bg-red-50" title="Delete">
                <span class="material-symbols-outlined text-red-500" style="font-size:16px;">delete</span>
              </button>
            </div>
          </div>
        @empty
          <p id="sizesEmptyMsg" class="px-6 py-10 text-sm text-on-surface-variant text-center">No sizes yet. Add your first size range to get started.</p>
        @endforelse
      </div>
    </div>
  </div>
</main>

<!-- Add / Edit modal -->
<div id="sizeModal" class="modal-backdrop p-4" role="dialog" aria-modal="true" aria-labelledby="sizeModalTitle">
  <div class="modal-inner bg-white rounded-2xl shadow-2xl w-full max-w-xl max-h-[90vh] overflow-y-auto">
    <div class="px-6 py-5 border-b border-outline-variant/15 flex items-center justify-between">
      <h3 id="sizeModalTitle" class="text-lg font-bold text-on-surface">Add Size</h3>
      <button type="button" id="btnCloseSizeModal" class="w-8 h-8 rounded-lg hover:bg-surface-container-low flex items-center justify-center">
        <span class="material-symbols-outlined text-on-surface-variant">close</span>
      </button>
    </div>
    <form id="sizeForm" class="p-6 space-y-5">
      <input type="hidden" id="editingSizeId" value="">

      <div>
        <label for="sizeLabel" class="block text-xs font-semibold text-on-surface-variant mb-1.5">Label <span class="text-red-600">*</span></label>
        <input type="text" id="sizeLabel" name="label" maxlength="100" class="w-full text-sm border border-outline-variant/30 rounded-xl px-3 py-2.5 bg-white focus:outline-none focus:ring-2 focus:ring-primary/30" placeholder="e.g. Small">
        <p id="sizeLabelError" class="text-error text-xs mt-1 hidden"></p>
        <p class="text-[11px] text-on-surface-variant mt-1">Shown to artists and clients when picking a size.</p>
      </div>

      <div class="rounded-xl border border-outline-variant/20 p-4 space-y-3 bg-surface-container-low/40">
        <div class="flex items-center gap-2">
          <span class="material-symbols-outlined text-primary text-[18px]">straighten</span>
          <p class="text-sm font-semibold text-on-surface">Centimeters (cm)</p>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label for="sizeCmMin" class="block text-xs font-semibold text-on-surface-variant mb-1.5">Min cm</label>
            <input type="number" id="sizeCmMin" name="cm_min" min="0" step="0.1" class="w-full text-sm border border-outline-variant/30 rounded-xl px-3 py-2.5 bg-white focus:outline-none focus:ring-2 focus:ring-primary/30" placeholder="empty = &lt;max">
            <p id="sizeCmMinError" class="text-error text-xs mt-1 hidden"></p>
          </div>
          <div>
            <label for="sizeCmMax" class="block text-xs font-semibold text-on-surface-variant mb-1.5">Max cm</label>
            <input type="number" id="sizeCmMax" name="cm_max" min="0" step="0.1" class="w-full text-sm border border-outline-variant/30 rounded-xl px-3 py-2.5 bg-white focus:outline-none focus:ring-2 focus:ring-primary/30" placeholder="empty = min+">
            <p id="sizeCmMaxError" class="text-error text-xs mt-1 hidden"></p>
          </div>
        </div>
        <p class="text-[11px] text-on-surface-variant">Leave Min empty for <strong>&lt;Max</strong> (e.g. Tiny). Leave Max empty for <strong>Min+</strong> (e.g. Extra Large). Fill both for a range.</p>
      </div>

      <div class="rounded-xl border border-outline-variant/20 p-4 space-y-3 bg-surface-container-low/40">
        <div class="flex items-center gap-2">
          <span class="material-symbols-outlined text-blue-700 text-[18px]">square_foot</span>
          <p class="text-sm font-semibold text-on-surface">Inches (in)</p>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label for="sizeInMin" class="block text-xs font-semibold text-on-surface-variant mb-1.5">Min in</label>
            <input type="number" id="sizeInMin" name="in_min" min="0" step="0.1" class="w-full text-sm border border-outline-variant/30 rounded-xl px-3 py-2.5 bg-white focus:outline-none focus:ring-2 focus:ring-primary/30" placeholder="empty = &lt;max">
            <p id="sizeInMinError" class="text-error text-xs mt-1 hidden"></p>
          </div>
          <div>
            <label for="sizeInMax" class="block text-xs font-semibold text-on-surface-variant mb-1.5">Max in</label>
            <input type="number" id="sizeInMax" name="in_max" min="0" step="0.1" class="w-full text-sm border border-outline-variant/30 rounded-xl px-3 py-2.5 bg-white focus:outline-none focus:ring-2 focus:ring-primary/30" placeholder="empty = min+">
            <p id="sizeInMaxError" class="text-error text-xs mt-1 hidden"></p>
          </div>
        </div>
        <p class="text-[11px] text-on-surface-variant">Same rules as cm — corresponding inch bounds for this size.</p>
      </div>

      <div class="flex items-center justify-between gap-4">
        <div>
          <p class="text-sm font-semibold text-on-surface">Status</p>
          <p class="text-xs text-on-surface-variant">Inactive sizes are hidden from pickers.</p>
        </div>
        <div class="flex items-center gap-2">
          <span class="text-xs text-on-surface-variant" id="sizeStatusLabel">Active</span>
          <button type="button" id="sizeStatusToggle" class="toggle-switch active" aria-checked="true" data-value="active"></button>
          <input type="hidden" id="sizeStatus" name="status" value="active">
        </div>
      </div>

      <p id="sizeFormError" class="text-error text-sm hidden"></p>
      <div class="flex gap-3 pt-2">
        <button type="button" id="btnCancelSizeModal" class="flex-1 py-2.5 rounded-xl border border-outline-variant/30 text-sm font-semibold text-on-surface-variant hover:bg-surface-container transition-colors">Cancel</button>
        <button type="submit" id="btnSubmitSize" class="flex-1 py-2.5 rounded-xl bg-primary text-white text-sm font-bold hover:bg-primary-container transition-colors">Save Size</button>
      </div>
    </form>
  </div>
</div>

<!-- Delete modal -->
<div id="deleteSizeModal" class="modal-backdrop p-4" role="dialog" aria-modal="true">
  <div class="modal-inner bg-white rounded-2xl shadow-2xl w-full max-w-md p-6">
    <h3 class="text-lg font-bold text-on-surface mb-2">Delete size?</h3>
    <p class="text-sm text-on-surface-variant mb-6">This will permanently remove <strong id="deleteSizeName">this size</strong>.</p>
    <input type="hidden" id="deletingSizeId" value="">
    <div class="flex gap-3">
      <button type="button" id="btnCancelDeleteSize" class="flex-1 py-2.5 rounded-xl border border-outline-variant/30 text-sm font-semibold text-on-surface-variant hover:bg-surface-container">Cancel</button>
      <button type="button" id="btnConfirmDeleteSize" class="flex-1 py-2.5 rounded-xl bg-red-600 text-white text-sm font-bold hover:bg-red-700">Delete</button>
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
    store: @json(route('admin.sizes.store')),
    update: @json(url('/admin/sizes/__ID__')),
    destroy: @json(url('/admin/sizes/__ID__')),
    reorder: @json(route('admin.sizes.reorder')),
  };

  const $sizeModal = $('#sizeModal');
  const $deleteModal = $('#deleteSizeModal');
  const $sizesList = $('#sizesList');
  const $sizesAlert = $('#sizesAlert');

  function showAlert(message, type) {
    $sizesAlert
      .removeClass('hidden bg-red-50 border-red-200 text-red-800 bg-emerald-50 border-emerald-200 text-emerald-800')
      .addClass(type === 'error' ? 'bg-red-50 border-red-200 text-red-800' : 'bg-emerald-50 border-emerald-200 text-emerald-800')
      .text(message);
    window.setTimeout(function () { $sizesAlert.addClass('hidden'); }, 4000);
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
    $('#sizeLabelError, #sizeCmMinError, #sizeCmMaxError, #sizeInMinError, #sizeInMaxError, #sizeFormError')
      .addClass('hidden')
      .text('');
    $('#sizeLabel, #sizeCmMin, #sizeCmMax, #sizeInMin, #sizeInMax')
      .removeClass('border-error ring-1 ring-error/40');
  }

  function setStatusToggle(active) {
    $('#sizeStatusToggle')
      .toggleClass('active', active)
      .attr('aria-checked', active ? 'true' : 'false');
    $('#sizeStatus').val(active ? 'active' : 'inactive');
    $('#sizeStatusLabel').text(active ? 'Active' : 'Inactive');
  }

  function resetSizeForm() {
    $('#editingSizeId').val('');
    $('#sizeModalTitle').text('Add Size');
    $('#btnSubmitSize').text('Save Size');
    $('#sizeLabel').val('');
    $('#sizeCmMin').val('');
    $('#sizeCmMax').val('');
    $('#sizeInMin').val('');
    $('#sizeInMax').val('');
    setStatusToggle(true);
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
    $row.data('size-status', isActive ? 'active' : 'inactive');
    $row.attr('data-size-status', isActive ? 'active' : 'inactive');
  }

  function emptyNullable(val) {
    if (val === null || val === undefined || val === '') return '';
    return val;
  }

  function formatAttrNumber(val) {
    if (val === null || val === undefined || val === '') return '';
    return String(val);
  }

  function escapeHtml(text) {
    return $('<div>').text(text == null ? '' : String(text)).html();
  }

  function statusToggleCellHtml(isActive) {
    const labelClass = isActive ? 'text-emerald-700' : 'text-on-surface-variant';
    const activeClass = isActive ? 'active' : '';
    const checked = isActive ? 'true' : 'false';
    return `
      <div class="flex items-center justify-between lg:justify-start gap-2">
        <span class="text-[10px] font-semibold uppercase tracking-wide text-on-surface-variant lg:hidden">Active</span>
        <span class="text-xs font-medium min-w-[52px] js-row-status-label ${labelClass}">${isActive ? 'Active' : 'Inactive'}</span>
        <button type="button" class="toggle-switch toggle-switch--table js-row-status-toggle ${activeClass}" aria-label="Toggle active status" aria-checked="${checked}"></button>
      </div>`;
  }

  function buildRowHtml(size) {
    const label = escapeHtml(size.label);
    const isActive = size.status === 'active';
    const cmLabel = escapeHtml(size.cm_range_label || '');
    const inLabel = escapeHtml(size.in_range_label || '');
    return `
      <div class="size-row px-4 sm:px-6 py-4 flex flex-col lg:grid lg:grid-cols-[auto_minmax(0,1.1fr)_minmax(0,1fr)_minmax(0,1fr)_120px_auto] lg:gap-4 lg:items-center gap-3"
        data-size-id="${size.id}"
        data-size-label="${label}"
        data-cm-min="${formatAttrNumber(size.cm_min)}"
        data-cm-max="${formatAttrNumber(size.cm_max)}"
        data-in-min="${formatAttrNumber(size.in_min)}"
        data-in-max="${formatAttrNumber(size.in_max)}"
        data-size-status="${size.status}"
        data-size-sort-order="${size.sort_order}">
        <span class="material-symbols-outlined text-outline cursor-grab" style="font-size:20px;">drag_indicator</span>
        <div class="min-w-0">
          <p class="text-[10px] font-semibold uppercase tracking-wide text-on-surface-variant lg:hidden mb-1">Label</p>
          <p class="text-sm font-semibold text-on-surface js-size-label">${label}</p>
        </div>
        <div>
          <p class="text-[10px] font-semibold uppercase tracking-wide text-on-surface-variant lg:hidden mb-1">Size (cm)</p>
          <span class="unit-chip unit-chip--cm js-size-cm">${cmLabel}</span>
        </div>
        <div>
          <p class="text-[10px] font-semibold uppercase tracking-wide text-on-surface-variant lg:hidden mb-1">Size (in)</p>
          <span class="unit-chip unit-chip--in js-size-in">${inLabel}</span>
        </div>
        ${statusToggleCellHtml(isActive)}
        <div class="flex gap-1 justify-end">
          <button type="button" class="js-edit-size w-8 h-8 rounded-lg flex items-center justify-center hover:bg-surface-container-low" title="Edit">
            <span class="material-symbols-outlined text-on-surface-variant" style="font-size:16px;">edit</span>
          </button>
          <button type="button" class="js-delete-size w-8 h-8 rounded-lg flex items-center justify-center hover:bg-red-50" title="Delete">
            <span class="material-symbols-outlined text-red-500" style="font-size:16px;">delete</span>
          </button>
        </div>
      </div>`;
  }

  function updateRow($row, size) {
    $row
      .attr('data-size-label', size.label)
      .attr('data-cm-min', formatAttrNumber(size.cm_min))
      .attr('data-cm-max', formatAttrNumber(size.cm_max))
      .attr('data-in-min', formatAttrNumber(size.in_min))
      .attr('data-in-max', formatAttrNumber(size.in_max))
      .attr('data-size-sort-order', size.sort_order)
      .data('size-label', size.label)
      .data('cm-min', emptyNullable(size.cm_min))
      .data('cm-max', emptyNullable(size.cm_max))
      .data('in-min', emptyNullable(size.in_min))
      .data('in-max', emptyNullable(size.in_max))
      .data('size-sort-order', size.sort_order);

    $row.find('.js-size-label').text(size.label);
    $row.find('.js-size-cm').text(size.cm_range_label || '');
    $row.find('.js-size-in').text(size.in_range_label || '');
    setRowStatusToggle($row, size.status === 'active');
  }

  function saveStatus($row, payload, revertFn) {
    const id = $row.data('size-id');
    const $toggle = $row.find('.js-row-status-toggle');
    $toggle.prop('disabled', true);

    $.ajax({
      url: routes.update.replace('__ID__', id),
      method: 'PUT',
      headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
      contentType: 'application/json',
      data: JSON.stringify(payload),
      success: function (res) {
        if (res.size) setRowStatusToggle($row, res.size.status === 'active');
      },
      error: function (xhr) {
        if (typeof revertFn === 'function') revertFn();
        const data = xhr.responseJSON || {};
        showAlert(data.message || 'Unable to update size.', 'error');
      },
      complete: function () {
        $toggle.prop('disabled', false);
      }
    });
  }

  function showFieldErrors(errors) {
    const map = {
      label: '#sizeLabel',
      cm_min: '#sizeCmMin',
      cm_max: '#sizeCmMax',
      in_min: '#sizeInMin',
      in_max: '#sizeInMax',
    };
    Object.keys(map).forEach(function (key) {
      if (!errors[key]) return;
      const inputSel = map[key];
      const errSel = inputSel + 'Error';
      $(errSel).removeClass('hidden').text(errors[key][0]);
      $(inputSel).addClass('border-error ring-1 ring-error/40');
    });
  }

  $('#btnAddSize').on('click', function () {
    resetSizeForm();
    openModal($sizeModal);
  });

  $('#btnCloseSizeModal, #btnCancelSizeModal').on('click', function () { closeModal($sizeModal); });
  $sizeModal.on('click', function (e) { if (e.target === this) closeModal($sizeModal); });

  $('#btnCancelDeleteSize').on('click', function () { closeModal($deleteModal); });
  $deleteModal.on('click', function (e) { if (e.target === this) closeModal($deleteModal); });

  $('#sizeStatusToggle').on('click', function () {
    setStatusToggle(!$(this).hasClass('active'));
  });

  $(document).on('click', '.js-row-status-toggle', function (e) {
    e.stopPropagation();
    const $toggle = $(this);
    if ($toggle.prop('disabled')) return;

    const $row = $toggle.closest('.size-row');
    const wasActive = String($row.attr('data-size-status')) === 'active';
    const nowActive = !wasActive;

    setRowStatusToggle($row, nowActive);
    saveStatus($row, { status: nowActive ? 'active' : 'inactive' }, function () {
      setRowStatusToggle($row, wasActive);
    });
  });

  $(document).on('click', '.js-edit-size', function () {
    const $row = $(this).closest('.size-row');
    resetSizeForm();
    $('#editingSizeId').val($row.data('size-id'));
    $('#sizeModalTitle').text('Edit Size');
    $('#btnSubmitSize').text('Update Size');
    $('#sizeLabel').val($row.attr('data-size-label') || '');
    $('#sizeCmMin').val($row.attr('data-cm-min') || '');
    $('#sizeCmMax').val($row.attr('data-cm-max') || '');
    $('#sizeInMin').val($row.attr('data-in-min') || '');
    $('#sizeInMax').val($row.attr('data-in-max') || '');
    setStatusToggle(String($row.attr('data-size-status')) === 'active');
    openModal($sizeModal);
  });

  $(document).on('click', '.js-delete-size', function () {
    const $row = $(this).closest('.size-row');
    $('#deletingSizeId').val($row.data('size-id'));
    $('#deleteSizeName').text($row.attr('data-size-label') || 'this size');
    openModal($deleteModal);
  });

  $('#sizeForm').on('submit', function (e) {
    e.preventDefault();
    clearFormErrors();

    const id = $('#editingSizeId').val();
    const cmMax = $.trim($('#sizeCmMax').val());
    const inMax = $.trim($('#sizeInMax').val());
    const cmMin = $.trim($('#sizeCmMin').val());
    const inMin = $.trim($('#sizeInMin').val());
    const payload = {
      label: $.trim($('#sizeLabel').val()),
      cm_min: cmMin === '' ? null : cmMin,
      cm_max: cmMax === '' ? null : cmMax,
      in_min: inMin === '' ? null : inMin,
      in_max: inMax === '' ? null : inMax,
      status: $('#sizeStatus').val(),
    };

    const url = id ? routes.update.replace('__ID__', id) : routes.store;
    const method = id ? 'PUT' : 'POST';

    $('#btnSubmitSize').prop('disabled', true);

    $.ajax({
      url: url,
      method: method,
      headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
      contentType: 'application/json',
      data: JSON.stringify(payload),
      success: function (res) {
        const size = res.size;
        if (id) {
          updateRow($sizesList.find(`[data-size-id="${id}"]`), size);
        } else {
          $('#sizesEmptyMsg').remove();
          $sizesList.append(buildRowHtml(size));
        }
        closeModal($sizeModal);
        showAlert(res.message || 'Saved.', 'success');
      },
      error: function (xhr) {
        const data = xhr.responseJSON || {};
        if (data.errors) {
          showFieldErrors(data.errors);
          if (!data.errors.label && !data.errors.cm_min && !data.errors.cm_max && !data.errors.in_min && !data.errors.in_max) {
            $('#sizeFormError').removeClass('hidden').text(data.message || 'Unable to save size.');
          }
        } else {
          $('#sizeFormError').removeClass('hidden').text(data.message || 'Unable to save size.');
        }
      },
      complete: function () {
        $('#btnSubmitSize').prop('disabled', false);
      }
    });
  });

  $('#btnConfirmDeleteSize').on('click', function () {
    const id = $('#deletingSizeId').val();
    if (!id) return;

    $(this).prop('disabled', true);

    $.ajax({
      url: routes.destroy.replace('__ID__', id),
      method: 'DELETE',
      headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
      success: function (res) {
        $sizesList.find(`[data-size-id="${id}"]`).remove();
        if (!$sizesList.find('.size-row').length) {
          $sizesList.html('<p id="sizesEmptyMsg" class="px-6 py-10 text-sm text-on-surface-variant text-center">No sizes yet. Add your first size range to get started.</p>');
        }
        closeModal($deleteModal);
        showAlert(res.message || 'Deleted.', 'success');
      },
      error: function (xhr) {
        const data = xhr.responseJSON || {};
        showAlert(data.message || 'Unable to delete size.', 'error');
      },
      complete: function () {
        $('#btnConfirmDeleteSize').prop('disabled', false);
      }
    });
  });

  if (typeof Sortable !== 'undefined' && $sizesList.length) {
    Sortable.create($sizesList[0], {
      handle: '.material-symbols-outlined',
      animation: 150,
      ghostClass: 'sortable-ghost',
      chosenClass: 'sortable-chosen',
      draggable: '.size-row',
      filter: '.js-row-status-toggle, .js-edit-size, .js-delete-size',
      preventOnFilter: true,
      onEnd: function () {
        const items = [];
        $sizesList.find('.size-row').each(function (index) {
          const id = $(this).data('size-id');
          const order = index + 1;
          items.push({ id: id, sort_order: order });
          $(this).attr('data-size-sort-order', order).data('size-sort-order', order);
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
