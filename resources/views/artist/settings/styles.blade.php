@extends('layouts.artist_dashboard_layout')

@section('title', 'Styles & Social')

@section('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<style>
    .style-tag { display: inline-flex; align-items: center; gap: 6px; background: #310f7a; color: white; padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: 500; }
    .style-tag button { background: none; border: none; color: white; cursor: pointer; font-size: 14px; line-height: 1; opacity: 0.8; }
    .style-tag button:hover { opacity: 1; }
    .style-option { padding: 10px 16px; cursor: pointer; display: flex; align-items: center; justify-content: space-between; transition: background 0.15s; }
    .style-option:hover { background: #f8f1fb; }
    .style-option.selected { background: #f0eaff; }

    .select2-container { width: 100% !important; z-index: 1; }
    .select2-container--open { z-index: 10060 !important; }
    .select2-container--default .select2-selection--single {
      min-height: 48px;
      padding: 6px 12px;
      border-radius: 0.75rem;
      border: 1px solid rgba(202,196,211,0.5) !important;
      background: #fff !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
      line-height: 2.25rem;
      padding-left: 4px;
      color: #1c1b21;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow { height: 46px; }
    .select2-container--default.select2-container--focus .select2-selection--single,
    .select2-container--default.select2-container--open .select2-selection--single {
      border-color: #310f7a !important;
      box-shadow: 0 0 0 2px rgba(49,15,122,0.25);
    }
    .select2-dropdown { border-radius: 0.75rem; border-color: rgba(202,196,211,0.5); overflow: hidden; }
    .select2-container--default .select2-results__option--highlighted[aria-selected] { background-color: #310f7a !important; }
    .select2-container--default .select2-search--dropdown .select2-search__field {
      border-radius: 0.5rem;
      border-color: rgba(202,196,211,0.5);
    }

    @media (max-width: 1023px) {
      .main-content { overflow-x: hidden; padding: 16px; padding-top: 70px; }
      body { overflow-x: hidden; }
    }
</style>
@endsection

@section('content')
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
  $styleOptions = [
    'traditional' => 'Traditional',
    'neo-traditional' => 'Neo Traditional',
    'japanese' => 'Japanese',
    'realism' => 'Realism',
    'blackwork' => 'Blackwork',
    'minimalist' => 'Minimalist',
    'geometric' => 'Geometric',
    'watercolor' => 'Watercolor',
    'tribal' => 'Tribal',
    'dotwork' => 'Dotwork',
    'new-school' => 'New School',
    'illustrative' => 'Illustrative',
  ];
@endphp

<main class="main-content flex-1 min-h-screen flex flex-col">
    <div class="flex-1 p-6 md:p-10 lg:p-12 max-w-4xl">

        <!-- Settings Tabs -->
        <div class="flex items-center gap-1 mb-6 border-b border-outline-variant/20 pb-0 overflow-x-auto">
            <a href="{{ route('profile.edit') }}" class="px-4 py-3 text-sm font-semibold whitespace-nowrap border-b-2 border-transparent text-on-surface-variant hover:text-on-surface hover:border-outline-variant transition-all">Profile</a>
            <a href="javascript:void(0)" class="px-4 py-3 text-sm font-semibold whitespace-nowrap border-b-2 border-primary text-primary hover:text-on-surface hover:border-outline-variant transition-all">Styles &amp; Social</a>
            <a href="{{ route('settings.studio') }}" class="px-4 py-3 text-sm font-semibold whitespace-nowrap border-b-2 border-transparent text-on-surface-variant hover:text-on-surface hover:border-outline-variant transition-all">Studio</a>
            <a href="{{ route('settings.preferences') }}" class="px-4 py-3 text-sm font-semibold whitespace-nowrap border-b-2 border-transparent text-on-surface-variant hover:text-on-surface hover:border-outline-variant transition-all">Preferences</a>
            <a href="{{ route('settings.calendar') }}" class="px-4 py-3 text-sm font-semibold whitespace-nowrap border-b-2 border-transparent text-on-surface-variant hover:text-on-surface hover:border-outline-variant transition-all">Calendar</a>
            <a href="{{ route('settings.payment') }}" class="px-4 py-3 text-sm font-semibold whitespace-nowrap border-b-2 border-transparent text-on-surface-variant hover:text-on-surface hover:border-outline-variant transition-all">Payments</a>
            <a href="{{route('settings.notifications')}}" class="px-4 py-3 text-sm font-semibold whitespace-nowrap border-b-2 border-transparent text-on-surface-variant hover:text-on-surface hover:border-outline-variant transition-all">Notifications</a>
        </div>


      <!-- Page Header -->
      <div class="mb-8">
        <h2 class="text-3xl font-extrabold text-on-surface tracking-tight">Styles & Social Settings</h2>
        <p class="text-on-surface-variant mt-1">Update your artistic identity and social media presence.</p>
      </div>

      <div id="styles-success-alert" class="hidden mb-6 rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-800 px-4 py-3 text-sm"></div>
      <form id="stylesForm">
        @csrf
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
          <div class="bg-surface-container-low rounded-2xl p-6 space-y-6">
            <div>
              <label for="tattooing_since" class="block text-sm font-semibold text-on-surface mb-2">Tattooing Since <span class="text-error">*</span></label>
              <select id="tattooing_since" name="tattooing_since" class="js-style-select2 w-full text-sm border border-outline-variant/30 rounded-xl px-4 py-3 bg-white text-on-surface cursor-pointer focus:outline-none focus:ring-2 focus:ring-primary/30">
                <option value="" {{ !$since ? 'selected' : '' }} disabled>Select year</option>
                @for ($y = (int) date('Y'); $y >= 1970; $y--)
                  <option value="{{ $y }}" {{ (int) $since === $y ? 'selected' : '' }}>{{ $y }}</option>
                @endfor
              </select>
              <p id="tattooing_since_error" class="text-error text-xs mt-1.5 hidden"></p>
            </div>
            <div>
              <label for="primary_style" class="block text-sm font-semibold text-on-surface mb-2">Primary Style <span class="text-error">*</span></label>
              <select id="primary_style" name="primary_style" class="js-style-select2 w-full text-sm border border-outline-variant/30 rounded-xl px-4 py-3 bg-white text-on-surface cursor-pointer focus:outline-none focus:ring-2 focus:ring-primary/30">
                <option value="" disabled {{ !$primary ? 'selected' : '' }}>Select style</option>
                @foreach ($styleOptions as $val => $lab)
                  <option value="{{ $val }}" {{ ($primary ?? '') === $val ? 'selected' : '' }}>{{ $lab }}</option>
                @endforeach
              </select>
              <p id="primary_style_error" class="text-error text-xs mt-1.5 hidden"></p>
            </div>
          </div>

          <div class="bg-surface-container-low rounded-2xl p-6" id="wrap_other_styles">
            <label class="block text-sm font-semibold text-on-surface mb-2">Other Styles</label>
            <div class="relative" id="stylesDropdown">
              <div class="relative">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-lg">search</span>
                <input type="text" id="style_search" placeholder="Search styles..." class="w-full text-sm border border-outline-variant/30 rounded-xl pl-10 pr-4 py-3 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30" autocomplete="off" onclick="toggleStylesDropdown(true)" oninput="filterStyles()">
              </div>
              <div id="stylesDropdownList" class="hidden absolute top-full left-0 right-0 mt-1 bg-white rounded-xl shadow-lg shadow-primary/5 border border-outline-variant/20 max-h-48 overflow-y-auto z-20">
                @foreach ($styleOptions as $val => $lab)
                  <div class="style-option" data-value="{{ $val }}" onclick="toggleStyle(this)">{{ $lab }} <span class="material-symbols-outlined text-lg text-outline-variant">check_box_outline_blank</span></div>
                @endforeach
              </div>
            </div>
            <div id="selectedTags" class="flex flex-wrap gap-2 mt-4"></div>
            <input type="hidden" id="other_styles" name="other_styles" value="{{ implode(',', $otherList) }}">
            <p id="other_styles_error" class="text-error text-xs mt-1.5 hidden"></p>
            <p class="text-on-surface-variant text-xs mt-3">Tip: Search through styles to better define your craft.</p>
          </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <div class="bg-surface-container-low rounded-2xl p-6">
            <label for="website" class="block text-sm font-semibold text-on-surface mb-2">Website <span class="text-on-surface-variant font-normal">(optional)</span></label>
            <div class="relative">
              <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-lg">language</span>
              <input type="url" id="website" name="social_links[website]" placeholder="https://www.inkjin.com" value="{{ $sl['website'] ?? '' }}" class="w-full text-sm border border-outline-variant/30 rounded-xl pl-10 pr-4 py-3 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
            </div>
            <p id="website_error" class="text-error text-xs mt-1.5 hidden"></p>
          </div>

          <div class="bg-surface-container-low rounded-2xl p-6">
            <label class="block text-sm font-semibold text-on-surface mb-4">Social Media <span class="text-on-surface-variant font-normal">(optional)</span></label>
            <div class="space-y-3">
              <div class="relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5" viewBox="0 0 24 24" fill="#E4405F"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                <input type="text" id="instagram" name="social_links[instagram]" placeholder="https://www.instagram.com/inkjin" value="{{ $sl['instagram'] ?? '' }}" class="w-full text-sm border border-outline-variant/30 rounded-xl pl-10 pr-4 py-3 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
                <p id="instagram_error" class="text-error text-xs mt-1.5 hidden"></p>
              </div>
              <div class="relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5" viewBox="0 0 24 24" fill="#000000"><path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-2.88 2.5 2.89 2.89 0 01-2.89-2.89 2.89 2.89 0 012.89-2.89c.28 0 .54.04.79.1v-3.5a6.37 6.37 0 00-.79-.05A6.34 6.34 0 003.15 15.2a6.34 6.34 0 006.34 6.34 6.34 6.34 0 006.34-6.34V8.73a8.19 8.19 0 004.76 1.52v-3.4a4.85 4.85 0 01-1-.16z"/></svg>
                <input type="text" id="tiktok" name="social_links[tiktok]" placeholder="https://www.tiktok.com/@inkjin" value="{{ $sl['tiktok'] ?? '' }}" class="w-full text-sm border border-outline-variant/30 rounded-xl pl-10 pr-4 py-3 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
                <p id="tiktok_error" class="text-error text-xs mt-1.5 hidden"></p>
              </div>
              <div class="relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5" viewBox="0 0 24 24" fill="#FF0000"><path d="M23.498 6.186a3.016 3.016 0 00-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 00.502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 002.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 002.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                <input type="text" id="youtube" name="social_links[youtube]" placeholder="https://www.youtube.com/@inkjin" value="{{ $sl['youtube'] ?? '' }}" class="w-full text-sm border border-outline-variant/30 rounded-xl pl-10 pr-4 py-3 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
                <p id="youtube_error" class="text-error text-xs mt-1.5 hidden"></p>
              </div>
              <div class="relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5" viewBox="0 0 24 24" fill="#1877F2"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                <input type="text" id="facebook" name="social_links[facebook]" placeholder="https://www.facebook.com/inkjin" value="{{ $sl['facebook'] ?? '' }}" class="w-full text-sm border border-outline-variant/30 rounded-xl pl-10 pr-4 py-3 bg-
                <p id="facebook_error" class="text-error text-xs mt-1.5 hidden"></p>
              </div>
            </div>
          </div>
        </div>
      </form>
    </div>

    <!-- Footer: Save Changes -->
    <div class="sticky bottom-0 bg-surface border-t border-outline-variant/10 px-6 md:px-10 lg:px-12 py-5 flex items-center justify-end">
      <button type="button" id="saveStylesBtn" class="inline-flex items-center gap-2 bg-gradient-to-br from-primary to-primary-container text-white font-bold py-3 px-8 rounded-xl shadow-lg shadow-primary/20 hover:opacity-90 transition-all active:scale-[0.98]">
        <span class="material-symbols-outlined text-lg">save</span> Save Changes
      </button>
    </div>
</main>

@endsection

@section('scripts')

<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    const selectedStyles = new Set(@json($otherList));
    const stylesForm = document.getElementById('stylesForm');
    const saveStylesBtn = document.getElementById('saveStylesBtn');
    const successAlert = document.getElementById('styles-success-alert');

    function serverKeyToErrorId(key) {
      const map = {
        'social_links.website': 'website_error',
        'social_links.instagram': 'instagram_error',
        'social_links.tiktok': 'tiktok_error',
        'social_links.youtube': 'youtube_error',
        'social_links.facebook': 'facebook_error',
      };
      return map[key] || key.replace(/\./g, '_') + '_error';
    }

    function clearErrors() {
      document.querySelectorAll('#stylesForm [id$="_error"]').forEach((el) => {
        el.textContent = '';
        el.classList.add('hidden');
      });
      ['tattooing_since', 'primary_style', 'website', 'instagram', 'tiktok', 'youtube', 'facebook'].forEach((id) => {
        const el = document.getElementById(id);
        if (el) el.classList.remove('border-error');
        if (window.jQuery && window.jQuery(el).hasClass('select2-hidden-accessible')) {
          window.jQuery(el).next('.select2-container').find('.select2-selection').removeClass('ring-2 ring-error/40');
        }
      });
      const wrap = document.getElementById('wrap_other_styles');
      if (wrap) wrap.classList.remove('ring-2', 'ring-error/40');
    }

    function showErrorByServerKey(key, message) {
      const errorId = serverKeyToErrorId(key);
      const errorEl = document.getElementById(errorId);
      if (errorEl) {
        errorEl.textContent = message;
        errorEl.classList.remove('hidden');
      }
      if (key === 'other_styles') {
        document.getElementById('wrap_other_styles')?.classList.add('ring-2', 'ring-error/40');
        return;
      }
      const inputMap = {
        'tattooing_since': 'tattooing_since',
        'primary_style': 'primary_style',
        'social_links.website': 'website',
        'social_links.instagram': 'instagram',
        'social_links.tiktok': 'tiktok',
        'social_links.youtube': 'youtube',
        'social_links.facebook': 'facebook',
      };
      const targetId = inputMap[key];
      if (targetId) document.getElementById(targetId)?.classList.add('border-error');
      if (targetId && window.jQuery && window.jQuery('#' + targetId).hasClass('select2-hidden-accessible')) {
        window.jQuery('#' + targetId).next('.select2-container').find('.select2-selection').addClass('ring-2 ring-error/40');
      }
    }

    function validateClient() {
      clearErrors();
      let ok = true;
      const tattooingSince = document.getElementById('tattooing_since').value;
      const primaryStyle = document.getElementById('primary_style').value;
      const website = document.getElementById('website').value.trim();
      if (!tattooingSince) {
        showErrorByServerKey('tattooing_since', 'Please select the year you started tattooing.');
        ok = false;
      }
      if (!primaryStyle) {
        showErrorByServerKey('primary_style', 'Please select your primary style.');
        ok = false;
      }
      if (website && !/^https?:\/\//i.test(website)) {
        showErrorByServerKey('social_links.website', 'Website must start with http:// or https://');
        ok = false;
      }
      return ok;
    }

    function toggleStylesDropdown(show) {
      document.getElementById('stylesDropdownList').classList.toggle('hidden', !show);
    }

    function filterStyles() {
      const query = document.getElementById('style_search').value.toLowerCase();
      const options = document.querySelectorAll('.style-option');
      options.forEach(opt => {
        opt.style.display = opt.textContent.toLowerCase().includes(query) ? '' : 'none';
      });
      toggleStylesDropdown(true);
    }

    function toggleStyle(el) {
      const value = el.dataset.value;
      const icon = el.querySelector('.material-symbols-outlined');
      if (selectedStyles.has(value)) {
        selectedStyles.delete(value);
        el.classList.remove('selected');
        icon.textContent = 'check_box_outline_blank';
        icon.classList.remove('text-primary');
        icon.classList.add('text-outline-variant');
      } else {
        selectedStyles.add(value);
        el.classList.add('selected');
        icon.textContent = 'check_box';
        icon.classList.add('text-primary');
        icon.classList.remove('text-outline-variant');
      }
      renderTags();
      updateHiddenInput();
    }

    function removeStyle(value) {
      selectedStyles.delete(value);
      const opt = document.querySelector(`.style-option[data-value="${value}"]`);
      if (opt) {
        opt.classList.remove('selected');
        const icon = opt.querySelector('.material-symbols-outlined');
        icon.textContent = 'check_box_outline_blank';
        icon.classList.remove('text-primary');
        icon.classList.add('text-outline-variant');
      }
      renderTags();
      updateHiddenInput();
    }

    function renderTags() {
      const container = document.getElementById('selectedTags');
      container.innerHTML = '';
      selectedStyles.forEach(value => {
        const opt = document.querySelector(`.style-option[data-value="${value}"]`);
        const label = opt ? opt.textContent.trim().replace(/check_box.*/, '').trim() : value;
        const tag = document.createElement('span');
        tag.className = 'style-tag';
        tag.innerHTML = `${label} <button onclick="removeStyle('${value}')" type="button">×</button>`;
        container.appendChild(tag);
      });
    }

    function updateHiddenInput() {
      document.getElementById('other_styles').value = Array.from(selectedStyles).join(',');
    }

    function bindLiveErrorClear() {
      const fieldMap = [
        'tattooing_since',
        'primary_style',
        'website',
        'instagram',
        'tiktok',
        'youtube',
        'facebook',
      ];
      fieldMap.forEach((id) => {
        const el = document.getElementById(id);
        if (!el) return;
        const evt = el.tagName === 'SELECT' ? 'change' : 'input';
        el.addEventListener(evt, function () {
          el.classList.remove('border-error');
          const key = id === 'website' ? 'social_links.website' : (id === 'instagram' ? 'social_links.instagram' : (id === 'tiktok' ? 'social_links.tiktok' : (id === 'youtube' ? 'social_links.youtube' : (id === 'facebook' ? 'social_links.facebook' : id))));
          const err = document.getElementById(serverKeyToErrorId(key));
          if (err) {
            err.textContent = '';
            err.classList.add('hidden');
          }
        });
      });
    }

    function submitStylesForm() {
      successAlert.classList.add('hidden');
      if (!validateClient()) return;
      saveStylesBtn.disabled = true;
      saveStylesBtn.innerHTML = '<span class="material-symbols-outlined text-lg">hourglass_top</span> Saving...';
      const fd = new FormData(stylesForm);
      fetch(@json(route('settings.styles.update')), {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': @json(csrf_token()),
          'X-Requested-With': 'XMLHttpRequest',
          Accept: 'application/json'
        },
        body: fd
      })
        .then((response) => response.json().then((data) => ({ status: response.status, ok: response.ok, data })))
        .then((result) => {
          if (result.ok && result.data.success) {
            successAlert.textContent = result.data.message || 'Styles & social updated successfully.';
            showSaveToast();
            successAlert.classList.remove('hidden');

            return;
          }
          if (result.status === 422 && result.data && result.data.errors) {
            clearErrors();
            Object.keys(result.data.errors).forEach((key) => {
              showErrorByServerKey(key, result.data.errors[key][0]);
            });
            return;
          }
          alert((result.data && result.data.message) || 'Could not save settings.');
        })
        .catch(() => {
          alert('Network error. Please try again.');
        })
        .finally(() => {
          saveStylesBtn.disabled = false;
          saveStylesBtn.innerHTML = '<span class="material-symbols-outlined text-lg">save</span> Save Changes';
        });
    }

    document.querySelectorAll('.style-option').forEach((el) => {
      const val = el.dataset.value;
      const icon = el.querySelector('.material-symbols-outlined');
      if (!selectedStyles.has(val)) return;
      el.classList.add('selected');
      icon.textContent = 'check_box';
      icon.classList.add('text-primary');
      icon.classList.remove('text-outline-variant');
    });
    renderTags();
    updateHiddenInput();
    bindLiveErrorClear();
    if (window.jQuery && window.jQuery.fn && window.jQuery.fn.select2) {
      window.jQuery('.js-style-select2').select2({
        width: '100%',
        dropdownParent: window.jQuery('body'),
        minimumResultsForSearch: 8
      });
    }
    saveStylesBtn.addEventListener('click', submitStylesForm);
    stylesForm.addEventListener('submit', function (e) {
      e.preventDefault();
      submitStylesForm();
    });

    document.addEventListener('click', (e) => {
      if (!document.getElementById('stylesDropdown').contains(e.target)) {
        toggleStylesDropdown(false);
      }
    });
</script>

@endsection