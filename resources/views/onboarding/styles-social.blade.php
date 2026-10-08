@extends('layouts.artist-onboarding-layout')

@section('title', 'Styles & social — Artist onboarding')

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
  $extraServices = is_array($userDetail->extra_services ?? null) ? $userDetail->extra_services : [];
  $extraServiceList = is_array($extraServices['services'] ?? null) ? $extraServices['services'] : [];
  $extraServiceOther = trim((string) ($extraServices['other'] ?? ''));
  $styleOptions = $styleOptions ?? [];
  if ($primary && !array_key_exists($primary, $styleOptions)) {
    $styleOptions[$primary] = ucwords(str_replace('-', ' ', $primary));
  }
  foreach ($otherList as $otherStyle) {
    if ($otherStyle && !array_key_exists($otherStyle, $styleOptions)) {
      $styleOptions[$otherStyle] = ucwords(str_replace('-', ' ', $otherStyle));
    }
  }
  $otherMax = 2;
@endphp

@push('styles')
<style>
  .field-err{color:#C62828;font-size:12px;margin-top:6px}
  .field-err.hidden{display:none}
  select.in,input.in{font:inherit;font-size:13.5px;color:var(--ink);width:100%;outline:none;background:#fff;border:1px solid var(--line);border-radius:10px;padding:10px 13px;min-height:40px;display:block;box-sizing:border-box}
  select.in{appearance:auto;cursor:pointer}
  select.in.is-err,input.in.is-err{border-color:#C62828}
  .social-in{display:flex;align-items:center;gap:8px;background:#fff;border:1px solid var(--line);border-radius:10px;padding:0 13px;min-height:40px}
  .social-in.is-err{border-color:#C62828}
  .social-in .ms{font-size:20px;flex-shrink:0}
  .social-in input{border:0;outline:none;background:transparent;font:inherit;font-size:13.5px;color:var(--ink);width:100%;min-width:0;padding:10px 0}
  .social-in input::placeholder{color:var(--faint)}
  button.btn{border:0;cursor:pointer;font:inherit;text-decoration:none}
  button.btn:disabled{opacity:.6;cursor:not-allowed}
  a.btn{text-decoration:none}
  #wrap_other_styles.is-err{outline:2px solid #C62828;outline-offset:2px;border-radius:16px}
  .style-search-wrap{position:relative}
  .style-search-wrap .ms.search-ic{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--faint);font-size:20px;pointer-events:none}
  .style-search-wrap input{width:100%;padding:10px 13px 10px 40px;border:1px solid var(--line);border-radius:10px;font:inherit;font-size:13.5px;background:#fff;outline:none;box-sizing:border-box;min-height:40px}
  .style-search-wrap input:focus{border-color:#3E007C;box-shadow:0 0 0 3px #F3E8FF}
  #stylesDropdownList{display:none;position:absolute;top:100%;left:0;right:0;margin-top:4px;background:#fff;border:1px solid var(--line);border-radius:12px;max-height:192px;overflow-y:auto;z-index:20;box-shadow:0 8px 24px rgba(62,0,124,.08)}
  #stylesDropdownList.open{display:block}
  .style-option{padding:10px 16px;cursor:pointer;display:flex;align-items:center;justify-content:space-between;gap:10px;transition:background .15s;font-size:13.5px}
  .style-option:hover{background:#F8F1FB}
  .style-option.selected{background:#F0EAFF}
  .style-option .ms{font-size:20px;color:var(--faint);flex-shrink:0}
  .style-option.selected .ms{color:var(--pri);font-variation-settings:'FILL' 1}
  .style-option.dim{opacity:.45;pointer-events:none}
  #selectedTags{display:flex;flex-wrap:wrap;gap:8px;margin-top:12px}
  .style-tag{display:inline-flex;align-items:center;gap:6px;background:#310F7A;color:#fff;padding:6px 14px;border-radius:20px;font-size:13px;font-weight:500}
  .style-tag button{background:none;border:none;color:#fff;cursor:pointer;font-size:14px;line-height:1;opacity:.8;padding:0;font:inherit}
  .style-tag button:hover{opacity:1}
  .svcs{display:flex;flex-wrap:wrap;gap:8px}
  .svc{display:inline-flex;align-items:center;gap:8px;border:1px solid var(--line);border-radius:12px;padding:9px 13px;font-size:13.5px;font-weight:600;cursor:pointer;background:#fff;margin:0}
  .svc input{accent-color:#3E007C;width:17px;height:17px;margin:0}
  .svc:has(input:checked){border-color:#3E007C;background:#FBF5FF}
  .svc:focus-within{outline:2px solid #3E007C;outline-offset:2px}
  @media (max-width:700px){
    .styles-grid{grid-template-columns:1fr!important}
    .social-grid{grid-template-columns:1fr!important}
  }
</style>
@endpush

@section('content')
<form id="stylesForm">
  @csrf
  <div class="wrap">
    <h1 style="margin-top:6px">Styles & social<a class="help-q" href="https://help.inkjin.com/en/articles/17200633-setup-step-2-styles-social" target="_blank" rel="noopener" data-help-article="O2-styles-social" title="Help with this page" aria-label="Help with this page"><span class="ms">help</span></a></h1>
    <div class="sub" style="max-width:640px;margin-bottom:24px">Your styles help clients find you in the marketplace. Your links show on your booking page.</div>

    <div class="grid styles-grid" style="grid-template-columns:1fr 1fr;gap:16px;align-items:start">
      <div class="card pad">
        <div class="grid" style="gap:14px">
          <div>
            <label class="fl" for="tattooing_since">Tattooing since <span style="color:#C62828">*</span></label>
            <select class="in js-select2" id="tattooing_since" name="tattooing_since" data-placeholder="Select year" data-searchable="true">
              <option value="" {{ !$since ? 'selected' : '' }}></option>
              @for ($y = (int) date('Y'); $y >= 1970; $y--)
                <option value="{{ $y }}" {{ (int) $since === $y ? 'selected' : '' }}>{{ $y }}</option>
              @endfor
            </select>
            <p id="tattooing_since_error" class="field-err hidden" role="alert"></p>
          </div>
          <div>
            <label class="fl" for="primary_style">Primary style <span style="color:#C62828">*</span></label>
            <select class="in js-select2" id="primary_style" name="primary_style" data-placeholder="Select style" data-searchable="true">
              <option value="" {{ !$primary ? 'selected' : '' }}></option>
              @foreach ($styleOptions as $val => $lab)
                <option value="{{ $val }}" {{ ($primary ?? '') === $val ? 'selected' : '' }}>{{ $lab }}</option>
              @endforeach
            </select>
            <p id="primary_style_error" class="field-err hidden" role="alert"></p>
          </div>
        </div>
      </div>

      <div class="card pad" id="wrap_other_styles">
        <div class="row" style="justify-content:space-between;align-items:center;margin-bottom:8px">
          <span class="fl" style="margin:0">Other styles <span class="faint" style="font-weight:500">(optional)</span></span>
          <span class="pill nd k" id="otherStylesCount">0 / {{ $otherMax }}</span>
        </div>
        <div class="relative" id="stylesDropdown" style="position:relative">
          <div class="style-search-wrap">
            <span class="ms search-ic">search</span>
            <input type="text" id="style_search" placeholder="Search styles…" autocomplete="off">
          </div>
          <div id="stylesDropdownList">
            @foreach ($styleOptions as $val => $lab)
              <div class="style-option" data-value="{{ $val }}" role="option" tabindex="0">
                <span class="style-option-label">{{ $lab }}</span>
                <span class="ms">check_box_outline_blank</span>
              </div>
            @endforeach
          </div>
        </div>
        <div id="selectedTags"></div>
        <input type="hidden" id="other_styles" name="other_styles" value="{{ implode(',', $otherList) }}">
        <p id="other_styles_error" class="field-err hidden" role="alert"></p>
        <p class="help">Tip: Search through styles to better define your craft.</p>
      </div>

      <div class="card pad" style="grid-column:1/-1">
        <span class="fl">Services you also offer <span class="faint" style="font-weight:500">(optional)</span></span>
        <div class="svcs" id="svcs" style="margin-top:6px">
          @foreach (['Touch-ups', 'Cover-ups', 'Blackout', 'Other'] as $svc)
            <label class="svc">
              <input
                type="checkbox"
                name="extra_services[]"
                value="{{ $svc }}"
                @if ($svc === 'Other') id="svco" @endif
                @checked(in_array($svc, $extraServiceList, true))
              >
              <span>{{ $svc }}</span>
            </label>
          @endforeach
        </div>
        <div id="svcow" @if (! in_array('Other', $extraServiceList, true)) hidden @endif style="margin-top:10px;max-width:360px">
          <label class="fl" for="svcot">Other service</label>
          <input class="in" type="text" id="svcot" name="extra_service_other" maxlength="40" placeholder="e.g. Scar cover-ups" autocomplete="off" value="{{ $extraServiceOther }}">
        </div>
        <div class="help">Shown on your page under your styles. You can change these later in My Page &gt; About.</div>
      </div>

      <div class="card pad" style="grid-column:1/-1">
        <span class="fl">Social links <span class="faint" style="font-weight:500">(optional)</span></span>
        <div class="grid social-grid" style="grid-template-columns:1fr 1fr;gap:10px;margin-top:6px">
          <div>
            <div class="social-in">
              <span class="ms" style="color:#C13584">photo_camera</span>
              <input type="url" id="instagram" name="social_links[instagram]" value="{{ $sl['instagram'] ?? '' }}" placeholder="instagram.com/yourhandle" autocomplete="off">
            </div>
            <p id="instagram_error" class="field-err hidden" role="alert"></p>
          </div>
          <div>
            <div class="social-in">
              <span class="ms">music_note</span>
              <input type="url" id="tiktok" name="social_links[tiktok]" value="{{ $sl['tiktok'] ?? '' }}" placeholder="tiktok.com/@yourhandle" autocomplete="off">
            </div>
            <p id="tiktok_error" class="field-err hidden" role="alert"></p>
          </div>
          <div>
            <div class="social-in">
              <span class="ms" style="color:#E00">smart_display</span>
              <input type="url" id="youtube" name="social_links[youtube]" value="{{ $sl['youtube'] ?? '' }}" placeholder="youtube.com/@yourchannel" autocomplete="off">
            </div>
            <p id="youtube_error" class="field-err hidden" role="alert"></p>
          </div>
          <div>
            <div class="social-in">
              <span class="ms" style="color:#1877F2">thumb_up</span>
              <input type="url" id="facebook" name="social_links[facebook]" value="{{ $sl['facebook'] ?? '' }}" placeholder="facebook.com/yourpage" autocomplete="off">
            </div>
            <p id="facebook_error" class="field-err hidden" role="alert"></p>
          </div>
          <div style="grid-column:1/-1">
            <div class="social-in">
              <span class="ms">add</span>
              <input type="url" id="website" name="social_links[website]" value="{{ $sl['website'] ?? '' }}" placeholder="Other link" autocomplete="off">
            </div>
            <p id="website_error" class="field-err hidden" role="alert"></p>
          </div>
        </div>
      </div>
    </div>

    <div class="obfoot">
      <a href="{{ route('onboarding.profile') }}" class="btn ghost"><span class="ms">arrow_back</span>Back</a>
      <button type="submit" class="btn" id="stylesNext">Next step<span class="ms">arrow_forward</span></button>
    </div>
  </div>
</form>
@endsection

@push('scripts')
@include('partials.reddit-pixel', ['event' => 'Step_2'])
@include('partials.social-links-validation')
<script>
var OTHER_MAX = {{ (int) $otherMax }};
var selectedStyles = new Set(@json(array_values($otherList)));

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
  $('#stylesForm').find('select.in, input.in').removeClass('is-err');
  $('#stylesForm').find('.select2-container, .social-in').removeClass('is-err');
  $('#wrap_other_styles').removeClass('is-err');
}

function setFieldOutlineError(id, hasError) {
  var $el = $('#' + id);
  if (!$el.length) return;
  if (id === 'wrap_other_styles') {
    $el.toggleClass('is-err', !!hasError);
    return;
  }
  $el.toggleClass('is-err', !!hasError);
  if ($el.hasClass('select2-hidden-accessible')) {
    $el.next('.select2-container').toggleClass('is-err', !!hasError);
  }
  var $social = $el.closest('.social-in');
  if ($social.length) $social.toggleClass('is-err', !!hasError);
}

function showErrorByServerKey(key, message) {
  var id = serverKeyToErrorId(key);
  var $err = $('#' + id);
  if ($err.length) $err.text(message).removeClass('hidden');
  if (key === 'tattooing_since') setFieldOutlineError('tattooing_since', true);
  else if (key === 'primary_style') setFieldOutlineError('primary_style', true);
  else if (key === 'other_styles') setFieldOutlineError('wrap_other_styles', true);
  else if (key === 'social_links.website') setFieldOutlineError('website', true);
  else if (key === 'social_links.instagram') setFieldOutlineError('instagram', true);
  else if (key === 'social_links.tiktok') setFieldOutlineError('tiktok', true);
  else if (key === 'social_links.youtube') setFieldOutlineError('youtube', true);
  else if (key === 'social_links.facebook') setFieldOutlineError('facebook', true);
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
  var webResult = window.SocialLinkValidation.validateWebsite($.trim($('#website').val()));
  if (!webResult.ok) {
    showErrorByServerKey('social_links.website', webResult.message);
    ok = false;
  }
  $.each(
    [
      ['instagram', 'instagram'],
      ['tiktok', 'tiktok'],
      ['youtube', 'youtube'],
      ['facebook', 'facebook'],
    ],
    function (_, pair) {
      var result = window.SocialLinkValidation.validatePlatform(pair[1], $.trim($('#' + pair[0]).val()));
      if (!result.ok) {
        showErrorByServerKey('social_links.' + pair[1], result.message);
        ok = false;
      }
    }
  );
  if (!ok && typeof window.scrollToFirstOnboardingError === 'function') {
    window.scrollToFirstOnboardingError(document.getElementById('stylesForm'));
  }
  return ok;
}

function toggleStylesDropdown(show) {
  $('#stylesDropdownList').toggleClass('open', !!show);
}

function filterStyles() {
  var q = $.trim($('#style_search').val()).toLowerCase();
  $('.style-option').each(function () {
    var label = $.trim($(this).find('.style-option-label').text()).toLowerCase();
    $(this).toggle(!q || label.indexOf(q) !== -1);
  });
  toggleStylesDropdown(true);
}

function updateHiddenInput() {
  $('#other_styles').val(Array.from(selectedStyles).join(','));
}

function updateOtherCount() {
  $('#otherStylesCount').text(selectedStyles.size + ' / ' + OTHER_MAX);
}

function syncOptionStates() {
  var atMax = selectedStyles.size >= OTHER_MAX;
  $('.style-option').each(function () {
    var val = this.getAttribute('data-value');
    var on = selectedStyles.has(val);
    var $el = $(this);
    var $icon = $el.find('.ms').first();
    $el.toggleClass('selected', on);
    $el.toggleClass('dim', !on && atMax);
    $icon.text(on ? 'check_box' : 'check_box_outline_blank');
  });
  updateOtherCount();
  updateHiddenInput();
}

function renderTags() {
  var $container = $('#selectedTags');
  $container.empty();
  selectedStyles.forEach(function (value) {
    var $opt = $('.style-option[data-value="' + value + '"]');
    var label = $opt.length
      ? $.trim($opt.find('.style-option-label').text())
      : value;
    var $tag = $('<span class="style-tag"></span>');
    $tag.append(document.createTextNode(label + ' '));
    var $btn = $('<button type="button" aria-label="Remove">&times;</button>');
    $btn.on('click', function () { removeStyle(value); });
    $tag.append($btn);
    $container.append($tag);
  });
}

function toggleStyle(value) {
  if (selectedStyles.has(value)) {
    selectedStyles.delete(value);
  } else {
    if (selectedStyles.size >= OTHER_MAX) return;
    selectedStyles.add(value);
  }
  syncOptionStates();
  renderTags();
  if (typeof window.clearOnboardingFieldError === 'function') {
    window.clearOnboardingFieldError('other_styles');
  } else {
    setFieldOutlineError('wrap_other_styles', false);
  }
}

function removeStyle(value) {
  selectedStyles.delete(value);
  syncOptionStates();
  renderTags();
  if (typeof window.clearOnboardingFieldError === 'function') {
    window.clearOnboardingFieldError('other_styles');
  } else {
    setFieldOutlineError('wrap_other_styles', false);
  }
}

$(function () {
  if (selectedStyles.size > OTHER_MAX) {
    selectedStyles = new Set(Array.from(selectedStyles).slice(0, OTHER_MAX));
  }
  syncOptionStates();
  renderTags();

  var $svcOtherWrap = $('#svcow');
  var $svcOther = $('#svco');
  function syncSvcOther(focusOther) {
    var on = $svcOther.is(':checked');
    $svcOtherWrap.prop('hidden', !on);
    if (on && focusOther) $('#svcot').trigger('focus');
  }
  $('#svcs').on('change', function () { syncSvcOther(true); });
  syncSvcOther(false);

  $('#style_search').on('focus click', function () {
    toggleStylesDropdown(true);
  });
  $('#style_search').on('input', filterStyles);

  $('#stylesDropdownList').on('click', '.style-option', function () {
    toggleStyle(this.getAttribute('data-value'));
  });
  $('#stylesDropdownList').on('keydown', '.style-option', function (e) {
    if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault();
      toggleStyle(this.getAttribute('data-value'));
    }
  });

  $(document).on('click', function (e) {
    if (!$(e.target).closest('#stylesDropdown').length) {
      toggleStylesDropdown(false);
    }
  });

  $.each(
    [
      ['instagram', 'social_links.instagram'],
      ['tiktok', 'social_links.tiktok'],
      ['youtube', 'social_links.youtube'],
      ['facebook', 'social_links.facebook'],
      ['website', 'social_links.website'],
    ],
    function (_, pair) {
      $('#' + pair[0]).on('input', function () {
        if (typeof window.clearOnboardingFieldError === 'function') window.clearOnboardingFieldError(pair[1]);
        else setFieldOutlineError(pair[0], false);
      });
    }
  );

  $('#tattooing_since').on('change', function () {
    if (typeof window.clearOnboardingFieldError === 'function') window.clearOnboardingFieldError('tattooing_since');
    else setFieldOutlineError('tattooing_since', false);
  });
  $('#primary_style').on('change', function () {
    if (typeof window.clearOnboardingFieldError === 'function') window.clearOnboardingFieldError('primary_style');
    else setFieldOutlineError('primary_style', false);
  });

  $('#stylesForm').on('submit', function (e) {
    e.preventDefault();
    if (!validateStylesFormClient()) return;
    clearStylesErrors();
    var $btn = $('#stylesNext');
    var originalBtnHtml = $btn.html();
    $btn.prop('disabled', true).text('Saving...');
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
            showErrorByServerKey(key, $.isArray(msgs) ? msgs[0] : msgs);
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
            showErrorByServerKey(key, $.isArray(msgs) ? msgs[0] : msgs);
          });
          if (typeof window.scrollToFirstOnboardingError === 'function') {
            window.scrollToFirstOnboardingError(document.getElementById('stylesForm'));
          }
        } else {
          alert('Network error. Please try again.');
        }
      })
      .always(function () {
        $btn.prop('disabled', false).html(originalBtnHtml);
      });
  });
});
</script>
@endpush
