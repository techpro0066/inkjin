@extends('layouts.new-artist-dashboard-layout')

@section('title', 'My Page')

@php
  $userDetail = $userDetail ?? auth()->user()?->userDetail;
  $username = trim((string) ($userDetail?->user_name ?? ''));
  $publicPageUrl = $username !== '' ? url('/@'.$username) : null;
  $publicPageHost = 'inkjin.com/@'.($username !== '' ? $username : 'username');
  $quickLinkRows = $username !== '' ? [
    ['suffix' => '', 'label' => $publicPageHost],
    ['suffix' => '#designs', 'label' => $publicPageHost.'#designs'],
    ['suffix' => '#portfolio', 'label' => $publicPageHost.'#portfolio'],
    ['suffix' => '#guest-spots', 'label' => $publicPageHost.'#guest-spots'],
    ['suffix' => '#policies', 'label' => $publicPageHost.'#policies'],
    ['suffix' => '#faq', 'label' => $publicPageHost.'#faq'],
  ] : [];
  $displayFaq = (bool) ($userDetail?->display_faq ?? false);
  $suggestions = [
    'Do you do cover-ups?',
    'Can I change the date?',
    'Is there an age limit?',
    'Do you tattoo over scars?',
    'What should I eat before?',
  ];
@endphp

@section('styles')
<style>
  .mp-faq .tog{width:38px;height:22px;border-radius:12px;background:#D9D2DD;position:relative;flex-shrink:0;cursor:pointer;border:0}
  .mp-faq .tog:after{content:'';position:absolute;width:16px;height:16px;border-radius:50%;background:#fff;top:3px;left:3px;transition:left .15s}
  .mp-faq .tog.on{background:var(--pri)}.mp-faq .tog.on:after{left:19px}
  .mp-faq .tog:disabled{opacity:.55;cursor:not-allowed}
  .mp-faq .faq-row{padding:14px 20px;border-bottom:1px solid #F3EEF4;display:flex;align-items:flex-start;gap:12px;cursor:grab;background:#fff}
  .mp-faq .faq-row:last-child{border-bottom:0}
  .mp-faq .faq-row.dragging{opacity:.45;cursor:grabbing}
  .mp-faq .faq-row.drag-over-top{box-shadow:inset 0 2px 0 0 #3E007C}
  .mp-faq .faq-row.drag-over-bottom{box-shadow:inset 0 -2px 0 0 #3E007C}
  .mp-faq .faq-row.inactive{opacity:.65}
  .mp-faq .faq-row .drag{color:var(--faint);margin-top:2px;user-select:none}
  .mp-faq .faq-act{border:0;background:none;cursor:pointer;color:var(--faint);padding:4px;border-radius:8px;display:inline-flex;align-items:center;justify-content:center}
  .mp-faq .faq-act:hover{background:#F3E8FF;color:var(--ink)}
  .mp-faq .faq-act.del:hover{background:#FDECEC;color:#C62828}
  .mp-faq .faq-act .ms{font-size:20px}
  .mp-faq .sug{border:1px dashed var(--line);background:#fff;border-radius:20px;padding:6px 13px;font:inherit;font-size:12.5px;font-weight:600;color:var(--ink);cursor:pointer;display:inline-flex}
  .mp-faq .sug:hover{border-color:#3E007C;background:#FBF5FF;color:#3E007C}
  .mp-faq .sug.used{opacity:.4;cursor:default;pointer-events:none}
  .mp-faq .ql{position:relative}
  .mp-faq .ql-b{display:inline-flex;align-items:center;gap:8px;font:inherit;font-weight:600;font-size:13.5px;padding:9px 12px 9px 13px;border-radius:12px;border:1px solid var(--line);background:#fff;color:var(--ink);cursor:pointer;white-space:nowrap}
  .mp-faq .ql-b:hover{background:#F7F3F9}
  .mp-faq .ql-b .ms{font-size:18px}.mp-faq .ql-b .cv{font-size:20px}
  .mp-faq .ql-p{position:absolute;right:0;top:calc(100% + 8px);width:min(400px,calc(100vw - 32px));background:#fff;border:1px solid #EAE2EE;border-radius:16px;box-shadow:0 18px 44px rgba(20,0,40,.18);padding:6px;z-index:80}
  .mp-faq .ql-p[hidden]{display:none}
  .mp-faq .ql-r{display:flex;align-items:center;gap:6px;padding:4px 4px 4px 12px;border-radius:10px}
  .mp-faq .ql-r:hover{background:#F7F3F9}
  .mp-faq .ql-u{flex:1;min-width:0;font-size:13.5px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
  .mp-faq .ql-u b{font-weight:700}
  .mp-faq .ql-i{width:34px;height:34px;border-radius:9px;border:0;background:none;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;color:var(--ink);flex-shrink:0;text-decoration:none}
  .mp-faq .ql-i:hover{background:#EFE7F3}
  .mp-faq .ql-i .ms{font-size:18px}.mp-faq .ql-i.done .ms{color:#1F8A4C}
  .mp-faq .mp-toast{position:fixed;top:22px;right:24px;background:#1A1A1A;color:#fff;padding:12px 18px;border-radius:10px;font-weight:600;font-size:14px;z-index:400;box-shadow:0 10px 30px rgba(0,0,0,.22);display:flex;gap:10px;align-items:center;opacity:0;transform:translateX(calc(100% + 40px));transition:opacity .25s,transform .35s;pointer-events:none}
  .mp-faq .mp-toast.on{opacity:1;transform:none}
  .mp-faq .mp-toast .ms{color:#3DD68C;font-size:20px}
  .mp-faq .ov{position:fixed;inset:0;background:rgba(20,10,30,.45);display:none;align-items:center;justify-content:center;z-index:500;padding:16px}
  .mp-faq .ov.on{display:flex}
  .mp-faq .modal{background:#fff;border-radius:22px;width:440px;max-width:100%;box-shadow:0 20px 60px rgba(0,0,0,.25);overflow:hidden;position:relative}
  .mp-faq .modal .mh{padding:22px 24px 14px;border-bottom:1px solid #F0EAF2;display:flex;align-items:flex-start;justify-content:space-between;gap:12px}
  .mp-faq .modal .mh h2{font-size:18px;font-weight:800;margin:0}
  .mp-faq .modal .mh .sub{font-size:13px;color:var(--muted);margin-top:4px}
  .mp-faq .modal .mx{border:0;background:none;cursor:pointer;color:var(--muted);width:36px;height:36px;border-radius:10px;display:inline-flex;align-items:center;justify-content:center}
  .mp-faq .modal .mx:hover{background:#F7F3F9;color:var(--ink)}
  .mp-faq .modal .mb{padding:18px 24px}
  .mp-faq .modal .mf{display:flex;gap:10px;padding:16px 24px;background:#FCF6FE;border-top:1px solid #F0E4F5}
  .mp-faq .modal .mf .btn{flex:1;justify-content:center}
  .mp-faq .hidden{display:none!important}
  .mp-faq .field-err{color:#C62828;font-size:12px;margin-top:6px}
  .mp-faq .field-err[hidden]{display:none!important}
  .mp-faq textarea.in{display:block;min-height:96px;resize:vertical;padding:10px 13px;line-height:1.45}
  @media (max-width:1180px){.mp-faq-grid{grid-template-columns:minmax(0,1fr)!important}}
  @media (max-width:900px){.mp-faq .mp-toast{left:16px;right:16px;top:74px}.mp-faq .ql-p{right:auto;left:0}}
</style>
@endsection

@section('content')
<div class="mp-faq">
  <div class="head">
    <div>
      <h1>
        My Page
        <a class="help-q" href="https://help.inkjin.com/en/articles/17201351-dashboard-my-page-faq" target="_blank" rel="noopener" title="Help with this page" aria-label="Help with this page">
          <span class="ms">help</span>
        </a>
      </h1>
      <div class="sub">Everything clients see on your booking page.</div>
    </div>
    <div class="btns">
      @if ($publicPageUrl)
        <div class="ql" id="quickLinks">
          <button type="button" class="ql-b" id="quickLinksBtn" aria-haspopup="true" aria-expanded="false" aria-controls="quickLinksMenu">
            <span class="ms">link</span>Quick links<span class="ms cv">expand_more</span>
          </button>
          <div class="ql-p" id="quickLinksMenu" hidden role="menu" aria-label="Quick links">
            @foreach ($quickLinkRows as $row)
              <div class="ql-r" role="none">
                <div class="ql-u" title="{{ $row['label'] }}">
                  {{ $publicPageHost }}@if($row['suffix'] !== '')<b>{{ $row['suffix'] }}</b>@endif
                </div>
                <button type="button" class="ql-i" data-ql-copy="{{ $publicPageUrl.$row['suffix'] }}" data-ql-label="{{ $row['label'] }}" role="menuitem" title="Copy link">
                  <span class="ms">content_copy</span>
                </button>
                <a class="ql-i" href="{{ $publicPageUrl.$row['suffix'] }}" target="_blank" rel="noopener" role="menuitem" title="Open">
                  <span class="ms">open_in_new</span>
                </a>
              </div>
            @endforeach
          </div>
        </div>
        <a class="btn" href="{{ $publicPageUrl }}#faq" target="_blank" rel="noopener">
          <span class="ms">open_in_new</span>Open my page
        </a>
      @endif
    </div>
  </div>

  @include('artist.partials.my-page-tabs', [
    'myPageTab' => 'faq',
    'designsCount' => $designsCount ?? 0,
    'portfolioCount' => $portfolioCount ?? 0,
    'faqCount' => $faqCount ?? $faqs->count(),
  ])

  <div class="grid mp-faq-grid" style="grid-template-columns:1fr 320px;gap:24px;align-items:start">
    <div class="card">
      <div class="ch">
        <div>
          <h3>Your FAQs</h3>
          <div class="faint" style="font-size:12.5px;margin-top:2px">Drag to reorder · toggle to show or hide each one</div>
        </div>
        <div class="row" style="gap:16px">
          <div class="row" style="gap:8px">
            <span class="faint" style="font-size:12.5px;font-weight:600">Show FAQ section</span>
            <button type="button" class="tog {{ $displayFaq ? 'on' : '' }}" id="displayFaqTog" data-own="1" role="switch" aria-checked="{{ $displayFaq ? 'true' : 'false' }}" title="Show or hide FAQ section on your page"></button>
          </div>
          <button type="button" class="btn sm" id="btnOpenFaqForm">
            <span class="ms" style="font-size:16px">add</span>Add FAQ
          </button>
        </div>
      </div>

      <div id="faqsEmpty" class="{{ $faqs->isEmpty() ? '' : 'hidden' }}" style="padding:40px 22px;text-align:center">
        <span class="ms" style="font-size:36px;color:var(--faint)">quiz</span>
        <div style="font-weight:700;margin-top:10px">No FAQs yet</div>
        <div class="faint" style="font-size:12.5px;margin-top:4px">Add a question, or tap a suggestion on the right.</div>
      </div>

      <div id="faqsListWrap" class="{{ $faqs->isEmpty() ? 'hidden' : '' }}">
        <div id="faqsList">
          @foreach ($faqs as $faq)
            <div
              class="faq-row {{ $faq->is_active ? '' : 'inactive' }}"
              draggable="true"
              data-id="{{ $faq->id }}"
              data-question="{{ $faq->question }}"
              data-answer="{{ $faq->answer }}"
              data-active="{{ $faq->is_active ? '1' : '0' }}"
              data-update-url="{{ route('artist.faq.update', $faq) }}"
              data-delete-url="{{ route('artist.faq.destroy', $faq) }}"
            >
              <span class="ms faint drag">drag_indicator</span>
              <div style="flex:1;min-width:0">
                <b class="faq-q" style="font-size:14px;display:block">{{ $faq->question }}</b>
                <div class="muted faq-a" style="font-size:13px;margin-top:3px">{{ $faq->answer }}</div>
              </div>
              <button type="button" class="tog js-faq-active {{ $faq->is_active ? 'on' : '' }}" data-own="1" role="switch" aria-checked="{{ $faq->is_active ? 'true' : 'false' }}" title="{{ $faq->is_active ? 'Visible' : 'Hidden' }}"></button>
              <button type="button" class="faq-act faq-edit" title="Edit" aria-label="Edit FAQ"><span class="ms">edit</span></button>
              <button type="button" class="faq-act del faq-delete" title="Delete" aria-label="Delete FAQ"><span class="ms">delete</span></button>
            </div>
          @endforeach
        </div>
      </div>
    </div>

    <div class="card pad">
      <h3 style="font-size:14.5px">Questions clients often ask</h3>
      <div class="faint" style="font-size:12.5px;margin:4px 0 12px">Tap one to add it and write your answer</div>
      <div class="chips" id="faqSuggestions" style="gap:8px;flex-wrap:wrap">
        @foreach ($suggestions as $sug)
          <button type="button" class="sug" data-q="{{ $sug }}">+ {{ $sug }}</button>
        @endforeach
      </div>
    </div>
  </div>

  <div class="mp-toast" id="mpToast" role="status"><span class="ms">check_circle</span><span id="mpToastText">Saved</span></div>

  {{-- Add / Edit modal --}}
  <div class="ov" id="faqFormModal" role="dialog" aria-modal="true" aria-labelledby="faqFormTitle">
    <div class="modal">
      <div class="mh">
        <div>
          <h2 id="faqFormTitle">Add FAQ</h2>
          <div class="sub" id="faqFormSubtitle">Write a question and the answer clients should see.</div>
        </div>
        <button type="button" class="mx" id="btnCloseFaqForm" aria-label="Close"><span class="ms">close</span></button>
      </div>
      <form id="faqForm" class="mb">
        <input type="hidden" id="faq_editing_id" value="">
        <input type="hidden" id="faq_is_active" value="1">
        <label class="fl" for="faq_question">Question</label>
        <input class="in" type="text" id="faq_question" maxlength="500" placeholder="e.g. How long does a session take?" autocomplete="off">
        <p class="field-err" id="question_error" hidden></p>
        <label class="fl" for="faq_answer" style="margin-top:14px">Answer</label>
        <textarea class="in" id="faq_answer" maxlength="5000" placeholder="Your answer"></textarea>
        <p class="field-err" id="answer_error" hidden></p>
      </form>
      <div class="mf">
        <button type="button" class="btn ghost" id="btnCancelFaq">Cancel</button>
        <button type="button" class="btn" id="btnSaveFaq"><span class="ms" id="btnSaveFaqIcon">add</span><span id="btnSaveFaqLabel">Add FAQ</span></button>
      </div>
    </div>
  </div>

  {{-- Delete modal --}}
  <div class="ov" id="deleteFaqModal" role="dialog" aria-modal="true">
    <div class="modal" style="width:420px">
      <button type="button" class="mx" id="btnDeleteFaqClose" aria-label="Close" style="position:absolute;top:14px;right:14px"><span class="ms">close</span></button>
      <div style="padding:24px 24px 16px">
        <div style="width:48px;height:48px;border-radius:12px;background:#FDECEC;color:#C62828;display:flex;align-items:center;justify-content:center;margin-bottom:14px">
          <span class="ms">delete</span>
        </div>
        <h2 style="font-size:20px;font-weight:800;margin-bottom:8px;padding-right:28px">Delete this FAQ?</h2>
        <p style="color:#3a3a3a;line-height:1.55;margin:0">This will permanently remove <b id="deleteFaqLabel"></b>. You cannot undo this.</p>
        <p class="field-err" id="deleteFaqError" hidden style="margin-top:10px"></p>
      </div>
      <div class="mf">
        <button type="button" class="btn ghost" id="btnDeleteFaqCancel">Cancel</button>
        <button type="button" class="btn" id="btnDeleteFaqConfirm" style="background:#C62828"><span class="ms">delete</span>Delete</button>
      </div>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script>
(function ($) {
  var csrf = $('meta[name="csrf-token"]').attr('content');
  var routes = {
    store: @json(route('artist.faq.store')),
    reorder: @json(route('artist.faq.reorder')),
    displayFaq: @json(route('personal-page.display-faq')),
  };
  var $list = $('#faqsList');
  var $listWrap = $('#faqsListWrap');
  var $empty = $('#faqsEmpty');
  var $formModal = $('#faqFormModal');
  var $deleteModal = $('#deleteFaqModal');
  var $toast = $('#mpToast');
  var deletingRow = null;
  var dragRow = null;

  function toast(msg) {
    $('#mpToastText').text(msg || 'Saved');
    $toast.addClass('on');
    clearTimeout($toast[0]._t);
    $toast[0]._t = setTimeout(function () { $toast.removeClass('on'); }, 2600);
  }

  function syncEmpty() {
    var n = $list.find('.faq-row').length;
    $empty.toggleClass('hidden', n > 0);
    $listWrap.toggleClass('hidden', n === 0);
    var tabN = document.querySelector('.tabs a.on .n');
    if (tabN) {
      tabN.textContent = String(n);
      tabN.classList.toggle('zero', n === 0);
    }
    syncSuggestions();
  }

  function syncSuggestions() {
    var qs = {};
    $list.find('.faq-row').each(function () {
      qs[String($(this).attr('data-question') || '').trim().toLowerCase()] = true;
    });
    $('#faqSuggestions .sug').each(function () {
      var q = String($(this).data('q') || '').trim().toLowerCase();
      $(this).toggleClass('used', !!qs[q]);
    });
  }

  function openOv($ov) { $ov.addClass('on'); document.body.style.overflow = 'hidden'; }
  function closeOv($ov) { $ov.removeClass('on'); if (!$('.ov.on').length) document.body.style.overflow = ''; }

  function clearErrors() {
    $('#question_error, #answer_error').prop('hidden', true).text('');
  }

  function resetForm() {
    $('#faq_editing_id').val('');
    $('#faq_question').val('');
    $('#faq_answer').val('');
    $('#faq_is_active').val('1');
    $('#faqFormTitle').text('Add FAQ');
    $('#faqFormSubtitle').text('Write a question and the answer clients should see.');
    $('#btnSaveFaqIcon').text('add');
    $('#btnSaveFaqLabel').text('Add FAQ');
    clearErrors();
  }

  function openForm(prefillQ) {
    if (!prefillQ) resetForm();
    if (prefillQ) {
      resetForm();
      $('#faq_question').val(prefillQ);
    }
    openOv($formModal);
    setTimeout(function () { $('#faq_question').trigger('focus'); }, 40);
  }

  function closeForm() { closeOv($formModal); resetForm(); }

  function escAttr(s) {
    return String(s).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;');
  }

  function buildRow(faq) {
    var active = !!faq.is_active;
    return $(
      '<div class="faq-row' + (active ? '' : ' inactive') + '" draggable="true"' +
      ' data-id="' + faq.id + '"' +
      ' data-question="' + escAttr(faq.question) + '"' +
      ' data-answer="' + escAttr(faq.answer) + '"' +
      ' data-active="' + (active ? '1' : '0') + '"' +
      ' data-update-url="' + escAttr(faq.update_url) + '"' +
      ' data-delete-url="' + escAttr(faq.delete_url) + '">' +
      '<span class="ms faint drag">drag_indicator</span>' +
      '<div style="flex:1;min-width:0"><b class="faq-q" style="font-size:14px;display:block"></b><div class="muted faq-a" style="font-size:13px;margin-top:3px"></div></div>' +
      '<button type="button" class="tog js-faq-active' + (active ? ' on' : '') + '" data-own="1" role="switch" aria-checked="' + (active ? 'true' : 'false') + '"></button>' +
      '<button type="button" class="faq-act faq-edit" title="Edit"><span class="ms">edit</span></button>' +
      '<button type="button" class="faq-act del faq-delete" title="Delete"><span class="ms">delete</span></button>' +
      '</div>'
    ).each(function () {
      $(this).find('.faq-q').text(faq.question);
      $(this).find('.faq-a').text(faq.answer);
    });
  }

  function updateRow($row, faq) {
    $row.attr({
      'data-question': faq.question,
      'data-answer': faq.answer,
      'data-active': faq.is_active ? '1' : '0'
    }).toggleClass('inactive', !faq.is_active);
    $row.find('.faq-q').text(faq.question);
    $row.find('.faq-a').text(faq.answer);
    $row.find('.js-faq-active').toggleClass('on', !!faq.is_active).attr('aria-checked', faq.is_active ? 'true' : 'false');
  }

  // Quick links
  var $ql = $('#quickLinks'), $qlBtn = $('#quickLinksBtn'), $qlMenu = $('#quickLinksMenu');
  if ($ql.length) {
    function openQl(show) {
      $qlMenu.prop('hidden', !show);
      $qlBtn.attr('aria-expanded', show ? 'true' : 'false');
      $qlBtn.find('.cv').text(show ? 'expand_less' : 'expand_more');
    }
    $qlBtn.on('click', function (e) { e.stopPropagation(); openQl($qlMenu.prop('hidden')); });
    $qlMenu.on('click', function (e) {
      e.stopPropagation();
      var $c = $(e.target).closest('[data-ql-copy]');
      if (!$c.length) return;
      var u = $c.attr('data-ql-copy') || '';
      try { navigator.clipboard.writeText(u); } catch (x) {}
      $c.addClass('done').find('.ms').text('check');
      setTimeout(function () { $c.removeClass('done').find('.ms').text('content_copy'); }, 1600);
      toast('Link copied: ' + ($c.attr('data-ql-label') || u));
    });
    $(document).on('click', function (e) {
      if (!$qlMenu.prop('hidden') && !$(e.target).closest('#quickLinks').length) openQl(false);
    });
  }

  // Section visibility
  $('#displayFaqTog').on('click', function () {
    var $t = $(this);
    var on = !$t.hasClass('on');
    $t.toggleClass('on', on).attr('aria-checked', on ? 'true' : 'false').prop('disabled', true);
    $.ajax({
      url: routes.displayFaq,
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
      data: { display_faq: on ? 1 : 0 },
      success: function (res) { toast(res.message || (on ? 'FAQ section shown' : 'FAQ section hidden')); },
      error: function () {
        $t.toggleClass('on', !on).attr('aria-checked', !on ? 'true' : 'false');
        toast('Could not update FAQ visibility');
      },
      complete: function () { $t.prop('disabled', false); }
    });
  });

  $('#btnOpenFaqForm').on('click', function () { openForm(); });
  $('#btnCancelFaq, #btnCloseFaqForm').on('click', closeForm);
  $formModal.on('click', function (e) { if (e.target === this) closeForm(); });

  $('#faqSuggestions').on('click', '.sug:not(.used)', function () {
    openForm(String($(this).data('q') || ''));
  });

  $('#btnSaveFaq').on('click', function () {
    clearErrors();
    var id = $('#faq_editing_id').val();
    var payload = {
      question: $.trim($('#faq_question').val()),
      answer: $.trim($('#faq_answer').val()),
      is_active: $('#faq_is_active').val() === '1',
    };
    var $row = id ? $list.find('.faq-row[data-id="' + id + '"]') : $();
    var url = id ? $row.attr('data-update-url') : routes.store;
    var $btn = $(this).prop('disabled', true);

    $.ajax({
      url: url,
      method: id ? 'PUT' : 'POST',
      headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
      contentType: 'application/json',
      data: JSON.stringify(payload),
      success: function (res) {
        if (id) updateRow($row, res.faq);
        else $list.append(buildRow(res.faq));
        closeForm();
        syncEmpty();
        toast(res.message || 'Saved');
      },
      error: function (xhr) {
        var data = xhr.responseJSON || {};
        if (data.errors) {
          if (data.errors.question) $('#question_error').prop('hidden', false).text(data.errors.question[0]);
          if (data.errors.answer) $('#answer_error').prop('hidden', false).text(data.errors.answer[0]);
        } else toast(data.message || 'Unable to save FAQ');
      },
      complete: function () { $btn.prop('disabled', false); }
    });
  });

  $(document).on('click', '.faq-edit', function (e) {
    e.stopPropagation();
    var $row = $(this).closest('.faq-row');
    resetForm();
    $('#faq_editing_id').val($row.data('id'));
    $('#faq_question').val($row.attr('data-question'));
    $('#faq_answer').val($row.attr('data-answer'));
    $('#faq_is_active').val(String($row.attr('data-active')) === '1' ? '1' : '0');
    $('#faqFormTitle').text('Edit FAQ');
    $('#faqFormSubtitle').text('Update this question and answer.');
    $('#btnSaveFaqIcon').text('save');
    $('#btnSaveFaqLabel').text('Save');
    openOv($formModal);
  });

  $(document).on('click', '.js-faq-active', function (e) {
    e.stopPropagation();
    var $t = $(this);
    if ($t.prop('disabled')) return;
    var $row = $t.closest('.faq-row');
    var was = String($row.attr('data-active')) === '1';
    var now = !was;
    $t.toggleClass('on', now).attr('aria-checked', now ? 'true' : 'false').prop('disabled', true);
    $row.attr('data-active', now ? '1' : '0').toggleClass('inactive', !now);
    $.ajax({
      url: $row.attr('data-update-url'),
      method: 'PUT',
      headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
      contentType: 'application/json',
      data: JSON.stringify({ is_active: now }),
      success: function (res) { if (res.faq) updateRow($row, res.faq); },
      error: function () {
        $t.toggleClass('on', was).attr('aria-checked', was ? 'true' : 'false');
        $row.attr('data-active', was ? '1' : '0').toggleClass('inactive', !was);
        toast('Unable to update FAQ');
      },
      complete: function () { $t.prop('disabled', false); }
    });
  });

  $(document).on('click', '.faq-delete', function (e) {
    e.stopPropagation();
    deletingRow = $(this).closest('.faq-row');
    $('#deleteFaqLabel').text(deletingRow.attr('data-question') || 'this FAQ');
    $('#deleteFaqError').prop('hidden', true).text('');
    openOv($deleteModal);
  });

  function closeDelete() { closeOv($deleteModal); deletingRow = null; }
  $('#btnDeleteFaqCancel, #btnDeleteFaqClose').on('click', closeDelete);
  $deleteModal.on('click', function (e) { if (e.target === this) closeDelete(); });

  $('#btnDeleteFaqConfirm').on('click', function () {
    if (!deletingRow) return;
    var $btn = $(this).prop('disabled', true);
    $.ajax({
      url: deletingRow.attr('data-delete-url'),
      method: 'DELETE',
      headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
      success: function (res) {
        deletingRow.remove();
        deletingRow = null;
        closeOv($deleteModal);
        syncEmpty();
        toast(res.message || 'Deleted');
      },
      error: function (xhr) {
        var data = xhr.responseJSON || {};
        $('#deleteFaqError').prop('hidden', false).text(data.message || 'Unable to delete FAQ.');
      },
      complete: function () { $btn.prop('disabled', false); }
    });
  });

  function persistOrder() {
    var ids = [];
    $list.find('.faq-row').each(function () { ids.push(Number($(this).data('id'))); });
    if (!ids.length) return;
    $.ajax({
      url: routes.reorder,
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
      contentType: 'application/json',
      data: JSON.stringify({ ids: ids }),
      error: function () { toast('Unable to save FAQ order'); }
    });
  }

  $list.on('dragstart', '.faq-row', function (e) {
    if ($(e.target).closest('.js-faq-active, .faq-edit, .faq-delete').length) {
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
    var rect = this.getBoundingClientRect();
    var before = (e.originalEvent.clientY - rect.top) < rect.height / 2;
    $list.find('.faq-row').removeClass('drag-over-top drag-over-bottom');
    $(this).addClass(before ? 'drag-over-top' : 'drag-over-bottom');
    if (before) this.parentNode.insertBefore(dragRow, this);
    else this.parentNode.insertBefore(dragRow, this.nextSibling);
  });

  $(document).on('keydown', function (e) {
    if (e.key !== 'Escape') return;
    if ($formModal.hasClass('on')) closeForm();
    else if ($deleteModal.hasClass('on')) closeDelete();
  });

  syncEmpty();
})(jQuery);
</script>
@endsection
