@extends('layouts.artist_dashboard_layout')

@section('title', 'Aftercare')

@section('styles')
<style>
  .toggle-switch {
    width: 48px;
    height: 26px;
    border-radius: 13px;
    background: #cac4d3;
    cursor: pointer;
    position: relative;
    transition: background 0.3s;
    flex-shrink: 0;
  }
  .toggle-switch.active { background: #310f7a; }
  .toggle-switch::after {
    content: '';
    position: absolute;
    top: 3px;
    left: 3px;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: white;
    transition: transform 0.3s;
    box-shadow: 0 1px 3px rgba(0,0,0,0.15);
  }
  .toggle-switch.active::after { transform: translateX(22px); }

  .included-item-row { display: flex; align-items: center; gap: 0.5rem; }
  .included-item-row input { flex: 1; min-width: 0; }
  .included-item-remove {
    width: 2rem;
    height: 2rem;
    border-radius: 0.5rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #7a7583;
    flex-shrink: 0;
    transition: background 0.15s, color 0.15s;
  }
  .included-item-remove:hover { background: #fce8e8; color: #ba1a1a; }
  .included-item-remove.is-hidden { visibility: hidden; pointer-events: none; }

  .aftercare-textarea {
    width: 100%;
    min-height: 110px;
    resize: vertical;
    border: 1px solid rgba(202, 196, 211, 0.45);
    border-radius: 0.75rem;
    padding: 0.75rem 1rem;
    font-size: 0.875rem;
    line-height: 1.6;
    color: #1c1b21;
    background: #fff;
  }
  .aftercare-textarea:focus {
    outline: none;
    box-shadow: 0 0 0 2px rgba(49, 15, 122, 0.25);
    border-color: rgba(49, 15, 122, 0.35);
  }
</style>
@endsection

@section('content')
@php
  $username = $userDetail?->user_name ?? '';
  $bookingPageUrl = !empty($username) ? 'https://inkjin.com/@'.$username : null;
  $sendAutomatically = (bool) ($sendAutomatically ?? false);
@endphp
<main class="main-content flex-1 min-h-screen">
  <div class="p-6 md:p-10 lg:p-12 max-w-6xl">
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

    @include('artist.partials.booking-page-tabs', ['activeTab' => 'aftercare'])

    <div class="mb-6 max-w-3xl">
      <h3 class="text-xl font-bold text-on-surface tracking-tight">Tattoo Aftercare Instructions</h3>
      <p class="text-on-surface-variant mt-1">Edit the aftercare guidance clients receive after their appointment.</p>
    </div>

    <!-- Auto-send toggle -->
    <div class="bg-white rounded-2xl p-5 md:p-6 mb-6 border border-outline-variant/20 max-w-3xl" id="aftercareAutoSendPanel">
      <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
        <div class="flex items-start gap-3 min-w-0 flex-1">
          <div class="w-9 h-9 rounded-xl bg-primary/10 flex items-center justify-center shrink-0">
            <span class="material-symbols-outlined text-primary text-lg">healing</span>
          </div>
          <div class="min-w-0">
            <h3 class="font-bold text-on-surface">Send aftercare instructions automatically</h3>
            <p class="text-xs text-on-surface-variant mt-1 max-w-xl">If on, these instructions will be sent automatically to the client right after the appointment ends.</p>
          </div>
        </div>
        <div class="flex items-center gap-3 shrink-0">
          <div
            id="toggleAftercareAutoSend"
            class="toggle-switch {{ $sendAutomatically ? 'active' : '' }}"
            role="switch"
            aria-checked="{{ $sendAutomatically ? 'true' : 'false' }}"
            title="Send aftercare instructions automatically"
          ></div>
          <span id="toggleAftercareAutoSendLabel" class="text-xs font-semibold {{ $sendAutomatically ? 'text-primary' : 'text-on-surface-variant' }} min-w-[1.75rem]">{{ $sendAutomatically ? 'On' : 'Off' }}</span>
        </div>
      </div>
    </div>

    @foreach($sections as $section)
      <div class="bg-white rounded-2xl p-5 md:p-6 mb-6 border border-outline-variant/20 max-w-3xl aftercare-section" data-section-key="{{ $section['key'] }}">
        <div class="flex items-start gap-3 mb-5">
          <div class="w-9 h-9 rounded-xl bg-primary/10 flex items-center justify-center shrink-0">
            <span class="material-symbols-outlined text-primary text-lg">checklist</span>
          </div>
          <div>
            <h3 class="font-bold text-on-surface">{{ $section['title'] }}</h3>
            <p class="text-xs text-on-surface-variant mt-1">Add, edit, or remove bullet points for this section.</p>
          </div>
        </div>

        <div class="aftercare-item-list space-y-2.5" aria-label="{{ $section['title'] }} items">
          @foreach($section['items'] as $item)
            <div class="included-item-row">
              <input type="text" class="included-item-input w-full text-sm border border-outline-variant/30 rounded-xl px-3 py-2.5 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30" maxlength="500" value="{{ $item }}">
              <button type="button" class="included-item-remove" title="Remove item" aria-label="Remove item"><span class="material-symbols-outlined text-[18px]">close</span></button>
            </div>
          @endforeach
        </div>

        <button type="button" class="btn-add-aftercare-item inline-flex items-center gap-1 text-sm font-semibold text-primary hover:text-primary-container transition-colors mt-4">
          <span class="material-symbols-outlined text-[18px]">add</span> Add item
        </button>
      </div>
    @endforeach

    @foreach($textBlocks as $key => $block)
      <div class="bg-white rounded-2xl p-5 md:p-6 mb-6 border border-outline-variant/20 max-w-3xl" data-text-key="{{ $key }}">
        <div class="flex items-start gap-3 mb-4">
          <div class="w-9 h-9 rounded-xl bg-primary/10 flex items-center justify-center shrink-0">
            <span class="material-symbols-outlined text-primary text-lg">notes</span>
          </div>
          <div>
            <h3 class="font-bold text-on-surface">{{ $block['title'] }}</h3>
          </div>
        </div>
        <textarea class="aftercare-textarea" rows="4" maxlength="2000">{{ $block['body'] }}</textarea>
      </div>
    @endforeach

    <div class="flex flex-col sm:flex-row sm:items-center gap-3 pt-2 max-w-3xl">
      <button type="button" id="btnSaveAftercare" class="inline-flex items-center justify-center gap-2 bg-primary text-white px-5 py-2.5 rounded-xl font-semibold text-sm hover:bg-primary-container transition-colors shadow-sm">
        <span class="material-symbols-outlined text-lg">save</span> Save
      </button>
      <p id="aftercareSaveStatus" class="hidden text-sm font-medium text-green-700"></p>
    </div>
  </div>
</main>
@endsection

@section('scripts')
<script>
  (function () {
    var AFTERCARE_UPDATE_URL = @json(route('artist.aftercare.update'));
    var $toggle = $('#toggleAftercareAutoSend');
    var $toggleLabel = $('#toggleAftercareAutoSendLabel');
    var $saveStatus = $('#aftercareSaveStatus');

    function syncToggleUi() {
      var on = $toggle.hasClass('active');
      $toggle.attr('aria-checked', on ? 'true' : 'false');
      $toggleLabel.text(on ? 'On' : 'Off')
        .toggleClass('text-primary', on)
        .toggleClass('text-on-surface-variant', !on);
    }

    function syncRemoveButtons($list) {
      $list.find('.included-item-row').each(function () {
        var hasText = $.trim($(this).find('.included-item-input').val()) !== '';
        $(this).find('.included-item-remove').toggleClass('is-hidden', !hasText);
      });
    }

    function buildRow(value) {
      var $row = $('<div class="included-item-row"></div>');
      var $input = $('<input type="text" class="included-item-input w-full text-sm border border-outline-variant/30 rounded-xl px-3 py-2.5 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30" maxlength="500" placeholder="e.g. Add an aftercare tip">');
      if (value) $input.val(value);
      var $remove = $('<button type="button" class="included-item-remove is-hidden" title="Remove item" aria-label="Remove item"><span class="material-symbols-outlined text-[18px]">close</span></button>');
      $row.append($input, $remove);
      return $row;
    }

    function collectPayload(includeContent) {
      var payload = {
        send_automatically: $toggle.hasClass('active')
      };

      if (!includeContent) {
        return payload;
      }

      var sections = {};
      $('.aftercare-section').each(function () {
        var key = String($(this).data('section-key') || '');
        if (!key) return;
        var items = [];
        $(this).find('.included-item-input').each(function () {
          var v = $.trim($(this).val());
          if (v) items.push(v);
        });
        sections[key] = items;
      });

      var textBlocks = {};
      $('[data-text-key]').each(function () {
        var key = String($(this).data('text-key') || '');
        if (!key) return;
        textBlocks[key] = $.trim($(this).find('textarea').val() || '');
      });

      payload.sections = sections;
      payload.text_blocks = textBlocks;
      return payload;
    }

    function saveAftercare(payload) {
      return fetch(AFTERCARE_UPDATE_URL, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || '',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(payload),
        credentials: 'same-origin'
      }).then(function (res) {
        var ct = res.headers.get('content-type') || '';
        if (ct.indexOf('application/json') !== -1) {
          return res.json().then(function (data) {
            return { ok: res.ok, status: res.status, data: data };
          });
        }
        return res.text().then(function () {
          return { ok: false, status: res.status, data: {} };
        });
      });
    }

    function showStatus(message, isError) {
      $saveStatus.removeClass('hidden text-green-700 text-error')
        .addClass(isError ? 'text-error' : 'text-green-700')
        .text(message || '');
      clearTimeout($saveStatus.data('timer'));
      if (message) {
        var t = setTimeout(function () { $saveStatus.addClass('hidden').text(''); }, 3500);
        $saveStatus.data('timer', t);
      }
    }

    $('.aftercare-section').each(function () {
      syncRemoveButtons($(this).find('.aftercare-item-list'));
    });

    $toggle.on('click', function () {
      var $t = $(this);
      var wasActive = $t.hasClass('active');
      $t.toggleClass('active');
      syncToggleUi();
      var isActive = $t.hasClass('active');
      $t.addClass('opacity-60 pointer-events-none');

      saveAftercare({ send_automatically: isActive }).then(function (result) {
        if (result.ok && result.data && result.data.success) {
          if (typeof showSaveToast === 'function') {
            showSaveToast(result.data.message || 'Saved.');
          }
        } else {
          if (wasActive) $t.addClass('active');
          else $t.removeClass('active');
          syncToggleUi();
          showStatus((result.data && result.data.message) || 'Could not update auto-send. Please try again.', true);
        }
      }).catch(function () {
        if (wasActive) $t.addClass('active');
        else $t.removeClass('active');
        syncToggleUi();
        showStatus('Could not update auto-send. Please try again.', true);
      }).finally(function () {
        $t.removeClass('opacity-60 pointer-events-none');
      });
    });

    $(document).on('click', '.btn-add-aftercare-item', function () {
      var $section = $(this).closest('.aftercare-section');
      var $list = $section.find('.aftercare-item-list');
      $list.append(buildRow(''));
      syncRemoveButtons($list);
      $list.find('.included-item-row:last .included-item-input').trigger('focus');
    });

    $(document).on('input', '.aftercare-item-list .included-item-input', function () {
      syncRemoveButtons($(this).closest('.aftercare-item-list'));
    });

    $(document).on('click', '.aftercare-item-list .included-item-remove', function () {
      var $row = $(this).closest('.included-item-row');
      var $list = $row.closest('.aftercare-item-list');
      if ($list.find('.included-item-row').length <= 1) {
        $row.find('.included-item-input').val('');
        syncRemoveButtons($list);
        return;
      }
      $row.remove();
      syncRemoveButtons($list);
    });

    $('#btnSaveAftercare').on('click', function () {
      var $btn = $(this);
      var btnHtml = $btn.html();
      $btn.prop('disabled', true).html('<span class="material-symbols-outlined text-[16px] animate-pulse">hourglass_empty</span> Saving…');

      saveAftercare(collectPayload(true)).then(function (result) {
        if (result.ok && result.data && result.data.success) {
          showStatus(result.data.message || 'Saved.', false);
          if (typeof showSaveToast === 'function') {
            showSaveToast(result.data.message || 'Saved.');
          }
        } else {
          showStatus((result.data && result.data.message) || 'Could not save. Please try again.', true);
        }
      }).catch(function () {
        showStatus('Could not save. Please try again.', true);
      }).finally(function () {
        $btn.prop('disabled', false).html(btnHtml);
      });
    });

    syncToggleUi();
  })();
</script>
@endsection
