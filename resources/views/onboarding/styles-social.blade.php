@extends('layouts.onboarding_bookpay')

@section('title', 'Styles & Social')

@php
  $ts = $userDetail->tattoo_styles ?? null;
  $since = is_array($ts) ? ($ts['tattooing_since'] ?? null) : null;
  $primary = is_array($ts) ? ($ts['primary_style'] ?? null) : null;
  $otherList = [];
  if (is_array($ts) && isset($ts['other_styles']) && is_array($ts['other_styles'])) {
    $otherList = $ts['other_styles'];
  } elseif (is_array($ts) && array_is_list($ts)) {
    $otherList = $ts;
  }
  $sl = $userDetail->social_links ?? [];
@endphp

@section('content')
<form id="stylesForm" class="contents">
  @csrf
  <div class="flex-1 p-8 md:p-12 max-w-4xl">
    <div class="mb-10">
      <h2 class="text-3xl font-extrabold text-on-surface tracking-tight">Styles & Social</h2>
      <p class="text-on-surface-variant mt-2 max-w-lg">Define your artistic identity and digital footprint. This information helps clients find your unique work in the marketplace.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
      <div class="bg-surface-container-low rounded-2xl p-6 space-y-6">
        <div>
          <label for="tattooing_since" class="block text-sm font-semibold text-on-surface mb-2">Tattooing Since <span class="text-error">*</span></label>
          <select id="tattooing_since" name="tattooing_since" class="select w-full px-4 py-3 rounded-xl border border-outline-variant/30 bg-white focus:ring-2 focus:ring-primary/40 transition-all text-on-surface">
            <option value="" {{ !$since ? 'selected' : '' }} disabled>Select year</option>
            @for ($y = (int) date('Y'); $y >= 1970; $y--)
              <option value="{{ $y }}" {{ (int) $since === $y ? 'selected' : '' }}>{{ $y }}</option>
            @endfor
          </select>
          <p id="tattooing_since_error" class="text-error text-xs mt-1.5 hidden" role="alert"></p>
        </div>
        <div>
          <label for="primary_style" class="block text-sm font-semibold text-on-surface mb-2">Primary Style <span class="text-error">*</span></label>
          <select id="primary_style" name="primary_style" class="select w-full px-4 py-3 rounded-xl border border-outline-variant/30 bg-white focus:ring-2 focus:ring-primary/40 transition-all text-on-surface">
            <option value="" disabled {{ !$primary ? 'selected' : '' }}>Select style</option>
            @foreach (['traditional'=>'Traditional','neo-traditional'=>'Neo Traditional','japanese'=>'Japanese','realism'=>'Realism','blackwork'=>'Blackwork','minimalist'=>'Minimalist','geometric'=>'Geometric','watercolor'=>'Watercolor','tribal'=>'Tribal','dotwork'=>'Dotwork','new-school'=>'New School','illustrative'=>'Illustrative'] as $val => $lab)
              <option value="{{ $val }}" {{ ($primary ?? '') === $val ? 'selected' : '' }}>{{ $lab }}</option>
            @endforeach
          </select>
          <p id="primary_style_error" class="text-error text-xs mt-1.5 hidden" role="alert"></p>
        </div>
      </div>

      <div class="bg-surface-container-low rounded-2xl p-6" id="wrap_other_styles">
        <label class="block text-sm font-semibold text-on-surface mb-2">Other Styles</label>
        <div class="relative" id="stylesDropdown">
          <div class="relative">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-lg">search</span>
            <input type="text" id="style_search" placeholder="Search styles..." autocomplete="off"
              class="w-full px-4 py-3 rounded-xl border border-outline-variant/30 bg-white focus:ring-2 focus:ring-primary/40 transition-all text-on-surface placeholder:text-outline/50 pl-10"
              onclick="toggleStylesDropdown(true)" oninput="filterStyles()">
          </div>
          <div id="stylesDropdownList" class="hidden absolute top-full left-0 right-0 mt-1 bg-white rounded-xl shadow-lg shadow-primary/5 border border-outline-variant/20 max-h-48 overflow-y-auto z-20">
            @foreach (['neo-traditional'=>'Neo Traditional','japanese'=>'Japanese','geometric'=>'Geometric','watercolor'=>'Watercolor','minimalist'=>'Minimalist','realism'=>'Realism','blackwork'=>'Blackwork','dotwork'=>'Dotwork','tribal'=>'Tribal','traditional'=>'Traditional','new-school'=>'New School','illustrative'=>'Illustrative'] as $val => $lab)
              <div class="style-option" data-value="{{ $val }}" onclick="toggleStyle(this)">{{ $lab }} <span class="material-symbols-outlined text-lg text-outline-variant">check_box_outline_blank</span></div>
            @endforeach
          </div>
        </div>
        <div id="selectedTags" class="flex flex-wrap gap-2 mt-4"></div>
        <input type="hidden" id="other_styles" name="other_styles" value="{{ implode(',', $otherList) }}">
        <p id="other_styles_error" class="text-error text-xs mt-1.5 hidden" role="alert"></p>
        <p class="text-on-surface-variant text-xs mt-3">Tip: Search through styles to better define your craft.</p>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <div class="bg-surface-container-low rounded-2xl p-6">
        <label for="website" class="block text-sm font-semibold text-on-surface mb-2">Website <span class="text-on-surface-variant font-normal">(optional)</span></label>
        <div class="relative">
          <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-lg">language</span>
          <input type="url" id="website" name="social_links[website]" value="{{ $sl['website'] ?? '' }}" placeholder="https://yourportfolio.com"
            class="w-full px-4 py-3 rounded-xl border border-outline-variant/30 bg-white focus:ring-2 focus:ring-primary/40 transition-all text-on-surface placeholder:text-outline/50 pl-10">
          <p id="website_error" class="text-error text-xs mt-1.5 hidden" role="alert"></p>
        </div>
      </div>
      <div class="bg-surface-container-low rounded-2xl p-6">
        <label class="block text-sm font-semibold text-on-surface mb-4">Social Media <span class="text-on-surface-variant font-normal">(optional)</span></label>
        <div class="space-y-3">
          <div class="relative">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5" viewBox="0 0 24 24" fill="#E4405F"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
            <input type="text" id="instagram" name="social_links[instagram]" value="{{ $sl['instagram'] ?? '' }}" placeholder="Instagram handle" class="w-full px-4 py-3 rounded-xl border border-outline-variant/30 bg-white focus:ring-2 focus:ring-primary/40 transition-all text-on-surface placeholder:text-outline/50 pl-10">
            <p id="instagram_error" class="text-error text-xs mt-1.5 hidden" role="alert"></p>
          </div>
          <div class="relative">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5" viewBox="0 0 24 24" fill="#000000"><path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-2.88 2.5 2.89 2.89 0 01-2.89-2.89 2.89 2.89 0 012.89-2.89c.28 0 .54.04.79.1v-3.5a6.37 6.37 0 00-.79-.05A6.34 6.34 0 003.15 15.2a6.34 6.34 0 006.34 6.34 6.34 6.34 0 006.34-6.34V8.73a8.19 8.19 0 004.76 1.52v-3.4a4.85 4.85 0 01-1-.16z"/></svg>
            <input type="text" id="tiktok" name="social_links[tiktok]" value="{{ $sl['tiktok'] ?? '' }}" placeholder="@TikTok handle" class="w-full px-4 py-3 rounded-xl border border-outline-variant/30 bg-white focus:ring-2 focus:ring-primary/40 transition-all text-on-surface placeholder:text-outline/50 pl-10">
            <p id="tiktok_error" class="text-error text-xs mt-1.5 hidden" role="alert"></p>
          </div>
          <div class="relative">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5" viewBox="0 0 24 24" fill="#FF0000"><path d="M23.498 6.186a3.016 3.016 0 00-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 00.502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 002.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 002.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
            <input type="text" id="youtube" name="social_links[youtube]" value="{{ $sl['youtube'] ?? '' }}" placeholder="YouTube channel URL or handle" class="w-full px-4 py-3 rounded-xl border border-outline-variant/30 bg-white focus:ring-2 focus:ring-primary/40 transition-all text-on-surface placeholder:text-outline/50 pl-10">
            <p id="youtube_error" class="text-error text-xs mt-1.5 hidden" role="alert"></p>
          </div>
          <div class="relative">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5" viewBox="0 0 24 24" fill="#1877F2"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
            <input type="text" id="facebook" name="social_links[facebook]" value="{{ $sl['facebook'] ?? '' }}" placeholder="Facebook profile link" class="w-full px-4 py-3 rounded-xl border border-outline-variant/30 bg-white focus:ring-2 focus:ring-primary/40 transition-all text-on-surface placeholder:text-outline/50 pl-10">
            <p id="facebook_error" class="text-error text-xs mt-1.5 hidden" role="alert"></p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="sticky bottom-0 bg-surface border-t border-outline-variant/10 px-8 md:px-12 py-5 flex items-center justify-between mt-auto">
    <a href="{{ route('onboarding.profile') }}" class="inline-flex items-center gap-1 text-on-surface font-semibold hover:text-primary transition-colors">
      <span class="material-symbols-outlined text-lg">arrow_back</span> Back
    </a>
    <button type="submit" class="inline-flex items-center gap-2 bg-gradient-to-br from-primary to-primary-container text-white font-bold py-3 px-8 rounded-xl shadow-lg shadow-primary/20 hover:opacity-90 transition-all active:scale-[0.98]">
      Next Step <span class="material-symbols-outlined text-lg">arrow_forward</span>
    </button>
  </div>
</form>
@endsection

@push('scripts')
<script>
var selectedStyles = new Set(@json($otherList));

function serverKeyToErrorId(key) {
  var map = {
    'social_links.website': 'website_error',
    'social_links.instagram': 'instagram_error',
    'social_links.tiktok': 'tiktok_error',
    'social_links.youtube': 'youtube_error',
    'social_links.facebook': 'facebook_error',
  };
  if (map[key]) return map[key];
  return key.replace(/\./g, '_') + '_error';
}

function clearStylesErrors() {
  $('#stylesForm').find('[id$="_error"]').text('').addClass('hidden');
  $.each(['tattooing_since', 'primary_style', 'website', 'instagram', 'tiktok', 'youtube', 'facebook'], function (_, id) {
    setFieldOutlineError(id, false);
  });
  setFieldOutlineError('wrap_other_styles', false);
}

function setFieldOutlineError(idOrEl, hasError) {
  var $el = typeof idOrEl === 'string' ? $('#' + idOrEl) : $(idOrEl);
  if (!$el.length) return;
  var el = $el[0];
  if (window.jQuery && el.tagName === 'SELECT' && el.classList.contains('select2-hidden-accessible')) {
    window.jQuery(el).next('.select2-container').toggleClass('ring-2 ring-error/40 rounded-xl', !!hasError);
    return;
  }
  if (el.classList && el.classList.contains('bg-surface-container-low')) {
    $el.toggleClass('ring-2', !!hasError).toggleClass('ring-error/40', !!hasError);
    return;
  }
  $el.toggleClass('border-error', !!hasError).toggleClass('ring-2', !!hasError).toggleClass('ring-error/40', !!hasError);
}

function showErrorByServerKey(key, message) {
  var id = serverKeyToErrorId(key);
  var $err = $('#' + id);
  if ($err.length) {
    $err.text(message).removeClass('hidden');
  }
  if (key === 'tattooing_since') setFieldOutlineError('tattooing_since', true);
  else if (key === 'primary_style') setFieldOutlineError('primary_style', true);
  else if (key === 'other_styles') setFieldOutlineError('wrap_other_styles', true);
  else if (key === 'social_links.website') setFieldOutlineError('website', true);
  else if (key === 'social_links.instagram') setFieldOutlineError('instagram', true);
  else if (key === 'social_links.tiktok') setFieldOutlineError('tiktok', true);
  else if (key === 'social_links.youtube') setFieldOutlineError('youtube', true);
  else if (key === 'social_links.facebook') setFieldOutlineError('facebook', true);
}

function isValidUrlWithScheme(val) {
  var t = String(val || '').trim();
  if (!t) return true;
  if (!/^https?:\/\//i.test(t)) return false;
  try {
    var u = new URL(t);
    return u.protocol === 'http:' || u.protocol === 'https:';
  } catch (e) {
    return false;
  }
}

function validateStylesFormClient() {
  clearStylesErrors();
  var ok = true;
  if (!$('#tattooing_since').val()) {
    showErrorByServerKey('tattooing_since', 'Please select the year you started tattooing.');
    ok = false;
  }
  if (!$('#primary_style').val()) {
    showErrorByServerKey('primary_style', 'Please select your primary style.');
    ok = false;
  }
  var web = $.trim($('#website').val());
  if (web && !isValidUrlWithScheme(web)) {
    showErrorByServerKey('social_links.website', 'Enter a valid URL starting with http:// or https://');
    ok = false;
  }
  if (!ok && typeof window.scrollToFirstOnboardingError === 'function') {
    window.scrollToFirstOnboardingError(document.getElementById('stylesForm'));
  }
  return ok;
}

function toggleStylesDropdown(show) {
  $('#stylesDropdownList').toggleClass('hidden', !show);
}
function filterStyles() {
  var q = $('#style_search').val().toLowerCase();
  $('.style-option').each(function () {
    $(this).css('display', $(this).text().toLowerCase().indexOf(q) !== -1 ? '' : 'none');
  });
  toggleStylesDropdown(true);
}
function toggleStyle(el) {
  var value = el.getAttribute('data-value');
  var $el = $(el);
  var $icon = $el.find('.material-symbols-outlined').first();
  if (selectedStyles.has(value)) {
    selectedStyles.delete(value);
    $el.removeClass('selected');
    $icon.text('check_box_outline_blank').removeClass('text-primary').addClass('text-outline-variant');
  } else {
    selectedStyles.add(value);
    $el.addClass('selected');
    $icon.text('check_box').addClass('text-primary').removeClass('text-outline-variant');
  }
  renderTags();
  updateHiddenInput();
  if (typeof window.clearOnboardingFieldError === 'function') window.clearOnboardingFieldError('other_styles');
}
function removeStyle(value) {
  selectedStyles.delete(value);
  var $opt = $('.style-option[data-value="' + value + '"]');
  if ($opt.length) {
    $opt.removeClass('selected');
    var $icon = $opt.find('.material-symbols-outlined').first();
    $icon.text('check_box_outline_blank').removeClass('text-primary').addClass('text-outline-variant');
  }
  renderTags();
  updateHiddenInput();
  if (typeof window.clearOnboardingFieldError === 'function') window.clearOnboardingFieldError('other_styles');
}
function renderTags() {
  var $container = $('#selectedTags');
  $container.empty();
  selectedStyles.forEach(function (value) {
    var $opt = $('.style-option[data-value="' + value + '"]');
    var label = $.trim($opt.text().replace(/check_box.*/, ''));
    var $tag = $('<span class="style-tag"></span>');
    $tag.html(label + ' <button type="button">&times;</button>');
    $tag.find('button').on('click', function () { removeStyle(value); });
    $container.append($tag);
  });
}
function updateHiddenInput() {
  $('#other_styles').val(Array.from(selectedStyles).join(','));
}

$(function () {
  $.each(
    [
      ['instagram', 'social_links.instagram'],
      ['tiktok', 'social_links.tiktok'],
      ['youtube', 'social_links.youtube'],
      ['facebook', 'social_links.facebook'],
    ],
    function (_, pair) {
      $('#' + pair[0]).on('input', function () {
        if (typeof window.clearOnboardingFieldError === 'function') window.clearOnboardingFieldError(pair[1]);
        else setFieldOutlineError(pair[0], false);
      });
    }
  );

  $(document).on('click', function (e) {
    if (!$(e.target).closest('#stylesDropdown').length) toggleStylesDropdown(false);
  });

  $('.style-option').each(function () {
    var el = this;
    if (selectedStyles.has(el.getAttribute('data-value'))) {
      $(el).addClass('selected');
      var $icon = $(el).find('.material-symbols-outlined').first();
      $icon.text('check_box').addClass('text-primary').removeClass('text-outline-variant');
    }
  });
  renderTags();
  updateHiddenInput();

  $('#tattooing_since').on('change', function () {
    if (typeof window.clearOnboardingFieldError === 'function') window.clearOnboardingFieldError('tattooing_since');
    else setFieldOutlineError('tattooing_since', false);
  });
  $('#primary_style').on('change', function () {
    if (typeof window.clearOnboardingFieldError === 'function') window.clearOnboardingFieldError('primary_style');
    else setFieldOutlineError('primary_style', false);
  });
  $('#website').on('input', function () {
    if (typeof window.clearOnboardingFieldError === 'function') window.clearOnboardingFieldError('social_links.website');
    else setFieldOutlineError('website', false);
  });

  $('#stylesForm').on('submit', function (e) {
    e.preventDefault();
    if (!validateStylesFormClient()) return;
    clearStylesErrors();
    var $btn = $(this).find('button[type="submit"]');
    $btn.prop('disabled', true);
    var fd = new FormData(this);
    $.ajax({
      url: @json(route('onboarding.styles-social.save')),
      type: 'POST',
      data: fd,
      processData: false,
      contentType: false,
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
        Accept: 'application/json',
      },
    })
      .done(function (data) {
        if (data.success && data.redirect) {
          window.location.href = data.redirect;
          return;
        }
        if (data.errors && typeof data.errors === 'object') {
          $.each(data.errors, function (key, msgs) {
            var msg = $.isArray(msgs) ? msgs[0] : msgs;
            showErrorByServerKey(key, msg);
          });
          if (typeof window.scrollToFirstOnboardingError === 'function') {
            window.scrollToFirstOnboardingError(document.getElementById('stylesForm'));
          }
        } else {
          alert(data.message || 'Could not save');
        }
      })
      .fail(function (xhr) {
        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
          $.each(xhr.responseJSON.errors, function (key, msgs) {
            var msg = $.isArray(msgs) ? msgs[0] : msgs;
            showErrorByServerKey(key, msg);
          });
          if (typeof window.scrollToFirstOnboardingError === 'function') {
            window.scrollToFirstOnboardingError(document.getElementById('stylesForm'));
          }
        } else {
          alert('Network error. Please try again.');
        }
      })
      .always(function () {
        $btn.prop('disabled', false);
      });
  });
});
</script>
@endpush
