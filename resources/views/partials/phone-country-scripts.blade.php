{{-- Shared helpers for country-code + national phone inputs (Select2) --}}
<style>
  .phone-country-field .select2-container { width: 100% !important; max-width: 100%; }
  .phone-country-field .select2-container--default .select2-selection--single {
    height: 58px;
    border: 1px solid rgba(122, 117, 131, 0.35);
    border-radius: 1rem;
    background: #ffffff;
    display: flex;
    align-items: center;
    box-shadow: 0 1px 4px rgba(49, 15, 122, 0.04);
  }
  .phone-country-field .select2-container--default.select2-container--focus .select2-selection--single,
  .phone-country-field .select2-container--default.select2-container--open .select2-selection--single {
    border-color: rgba(49, 15, 122, 0.55);
    box-shadow: 0 0 0 3px rgba(49, 15, 122, 0.14);
  }
  .phone-country-field .select2-container--default .select2-selection--single .select2-selection__rendered {
    color: #1c1b21;
    line-height: 56px;
    font-size: 0.95rem;
    padding-left: 0.85rem;
    padding-right: 2rem;
  }
  .phone-country-field .select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 56px;
    right: 8px;
  }
  .phone-country-field .select2-dropdown {
    border: 1px solid rgba(122, 117, 131, 0.35);
    border-radius: 0.85rem;
    overflow: hidden;
  }
  .phone-country-field .select2-container--default .select2-results__option {
    padding: 0.55rem 0.85rem;
    font-size: 0.9rem;
  }
  .phone-country-field .select2-container--default .select2-results__option--highlighted.select2-results__option--selectable {
    background: #310f7a;
    color: #fff;
  }
</style>
<script>
(function (window) {
  'use strict';

  function dialFromSelect(selectEl) {
    if (!selectEl) return '';
    var opt = selectEl.options[selectEl.selectedIndex];
    var dial = opt ? String(opt.getAttribute('data-dial') || '') : '';
    return dial.replace(/\D/g, '');
  }

  function nationalDigits(value) {
    var digits = String(value || '').replace(/\D/g, '');
    return digits.replace(/^0+/, '');
  }

  function getFullPhone(prefix) {
    prefix = prefix || 'bd';
    var countryEl = document.getElementById(prefix + 'PhoneCountry');
    var phoneEl = document.getElementById(prefix + 'Phone');
    var dial = dialFromSelect(countryEl);
    var national = nationalDigits(phoneEl ? phoneEl.value : '');
    if (!dial || !national) return '';
    return '+' + dial + national;
  }

  function getSelectedIso(prefix) {
    prefix = prefix || 'bd';
    var countryEl = document.getElementById(prefix + 'PhoneCountry');
    return countryEl ? String(countryEl.value || '').toUpperCase() : '';
  }

  function setCountryValue(countryEl, iso) {
    if (!countryEl || !iso) return;
    countryEl.value = iso;
    if (window.jQuery && window.jQuery.fn && window.jQuery.fn.select2) {
      window.jQuery(countryEl).val(iso).trigger('change.select2');
    }
  }

  function setFullPhone(prefix, e164) {
    prefix = prefix || 'bd';
    var countryEl = document.getElementById(prefix + 'PhoneCountry');
    var phoneEl = document.getElementById(prefix + 'Phone');
    if (!countryEl || !phoneEl) return;

    var normalized = String(e164 || '').trim().replace(/[\s\-().]/g, '').replace(/^00/, '+');
    if (!normalized) {
      phoneEl.value = '';
      return;
    }

    if (normalized.charAt(0) !== '+') {
      phoneEl.value = nationalDigits(normalized);
      return;
    }

    var digits = normalized.slice(1);
    var bestIso = null;
    var bestDialLen = 0;
    for (var i = 0; i < countryEl.options.length; i++) {
      var opt = countryEl.options[i];
      var dial = String(opt.getAttribute('data-dial') || '').replace(/\D/g, '');
      if (!dial) continue;
      if (digits.indexOf(dial) === 0 && dial.length > bestDialLen) {
        bestDialLen = dial.length;
        bestIso = String(opt.value || '');
      }
    }

    if (bestIso) {
      setCountryValue(countryEl, bestIso);
      phoneEl.value = digits.slice(bestDialLen);
    } else {
      phoneEl.value = digits;
    }
  }

  function isValidFullPhone(prefix) {
    var full = getFullPhone(prefix);
    return /^\+[1-9]\d{7,14}$/.test(full);
  }

  function initSelect2(prefix) {
    if (!window.jQuery || !window.jQuery.fn || !window.jQuery.fn.select2) return;
    var $ = window.jQuery;
    var $el = $('#' + prefix + 'PhoneCountry');
    if (!$el.length || $el.hasClass('select2-hidden-accessible')) return;
    var $wrap = $el.closest('[data-phone-country-field]');
    $el.select2({
      width: '100%',
      dropdownParent: $wrap.length ? $wrap : $(document.body),
      minimumResultsForSearch: 0
    });
  }

  function initAll() {
    document.querySelectorAll('[data-phone-country-field][data-prefix]').forEach(function (el) {
      initSelect2(String(el.getAttribute('data-prefix') || ''));
    });
  }

  window.InkjinPhoneCountry = {
    getFullPhone: getFullPhone,
    getSelectedIso: getSelectedIso,
    setFullPhone: setFullPhone,
    isValidFullPhone: isValidFullPhone,
    nationalDigits: nationalDigits,
    initSelect2: initSelect2,
    initAll: initAll
  };

  if (window.jQuery) {
    window.jQuery(function () {
      initAll();
    });
  } else if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAll);
  } else {
    initAll();
  }
})(window);
</script>
