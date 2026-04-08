(function() {
  var selTz = @json($userDetail->timezone ?? '');
  var selCur = @json($userDetail->currency ?? '');

  var allCurrencies = [
    { code: 'USD', name: 'United States Dollar', symbol: '$' },
    { code: 'EUR', name: 'Euro', symbol: '€' },
    { code: 'GBP', name: 'British Pound Sterling', symbol: '£' },
    { code: 'AUD', name: 'Australian Dollar', symbol: 'A$' },
    { code: 'CAD', name: 'Canadian Dollar', symbol: 'C$' },
    { code: 'JPY', name: 'Japanese Yen', symbol: '¥' },
    { code: 'CHF', name: 'Swiss Franc', symbol: 'Fr' },
    { code: 'INR', name: 'Indian Rupee', symbol: '₹' },
    { code: 'BRL', name: 'Brazilian Real', symbol: 'R$' },
    { code: 'MXN', name: 'Mexican Peso', symbol: '$' },
    { code: 'NZD', name: 'New Zealand Dollar', symbol: 'NZ$' },
    { code: 'SEK', name: 'Swedish Krona', symbol: 'kr' },
    { code: 'NOK', name: 'Norwegian Krone', symbol: 'kr' },
    { code: 'DKK', name: 'Danish Krone', symbol: 'kr' },
    { code: 'PLN', name: 'Polish Zloty', symbol: 'zł' },
    { code: 'ZAR', name: 'South African Rand', symbol: 'R' },
    { code: 'SGD', name: 'Singapore Dollar', symbol: 'S$' },
    { code: 'HKD', name: 'Hong Kong Dollar', symbol: 'HK$' },
    { code: 'AED', name: 'UAE Dirham', symbol: 'د.إ' },
    { code: 'SAR', name: 'Saudi Riyal', symbol: '﷼' },
  ];

  function getAllTimezones() {
    var timezones = [];
    var timezoneNames = Intl.supportedValuesOf('timeZone');
    timezoneNames.forEach(function (tz) {
      var date = new Date();
      var formatter = new Intl.DateTimeFormat('en', { timeZone: tz, timeZoneName: 'short' });
      var parts = formatter.formatToParts(date);
      var timeZoneName = '';
      for (var i = 0; i < parts.length; i++) {
        if (parts[i].type === 'timeZoneName') timeZoneName = parts[i].value;
      }
      var displayName = tz.replace(/_/g, ' ').split('/').map(function (p) {
        return p.charAt(0).toUpperCase() + p.slice(1).toLowerCase();
      }).join(' / ');
      timezones.push({ value: tz, name: displayName, offset: timeZoneName });
    });
    return timezones.sort(function (a, b) { return a.name.localeCompare(b.name); });
  }

  function initSelect2() {
    var $c = $('#currency');
    if ($c.length && !$c.hasClass('select2-hidden-accessible')) {
      allCurrencies.forEach(function (c) {
        var ok = c.code === selCur;
        $c.append(new Option(c.code + ' (' + c.symbol + ') — ' + c.name, c.code, ok, ok));
      });
      $c.select2({ placeholder: 'Currency', width: '100%' });
    }
    var $t = $('#timezone');
    if ($t.length && !$t.hasClass('select2-hidden-accessible')) {
      getAllTimezones().forEach(function (tz) {
        var ok = tz.value === selTz;
        $t.append(new Option(tz.name + ' (' + tz.offset + ')', tz.value, ok, ok));
      });
      $t.select2({ placeholder: 'Timezone', width: '100%' });
    }
  }

  window.toggleSessionFields = function() {
    var req = document.getElementById('require_consultation');
    var show = req && req.checked;
    var st = $('#session_type_container');
    var ct = $('#consultation_timing_container');
    if (st.length) st.css('display', show ? 'grid' : 'none');
    if (ct.length) ct.css('display', show ? 'block' : 'none');
    if (!show) {
      if (window.toggleGapFields) window.toggleGapFields();
    }
  };

  window.toggleGapFields = function() {
    var req = $('#require_consultation').prop('checked');
    var ct = $('#consultation_timing').val();
    var box = $('#gap_fields_container');
    if (!box.length) return;
    box.css('display', (req && ct === 'separate') ? 'flex' : 'none');
    if (!(req && ct === 'separate')) {
      var g = $('#require_gap_between_consultation_tattoo');
      if (g.length) g.prop('checked', false);
      if (window.toggleGapDurationFields) window.toggleGapDurationFields();
    }
  };

  window.toggleGapDurationFields = function() {
    var on = $('#require_gap_between_consultation_tattoo').prop('checked');
    var d = $('#gap_duration_container');
    if (d.length) d.css('display', on ? 'grid' : 'none');
  };

  function showFieldErrors(errors) {
    $('[id$="_error"]').addClass('hidden').text('');
    if (!errors) return;
    $.each(errors, function (k, v) {
      var $el = $('#' + k + '_error');
      if ($el.length) {
        $el.text($.isArray(v) ? v[0] : v).removeClass('hidden');
      }
    });
  }

  function validateClient() {
    var errors = {};
    var currency = $('#currency').val();
    var timezone = $('#timezone').val();
    if (!currency) errors.currency = ['Required'];
    if (!timezone) errors.timezone = ['Required'];
    if (!$('#date_time_format').val()) errors.date_time_format = ['Required'];
    if (!$('#minimum_deposit_type').val()) errors.minimum_deposit_type = ['Required'];
    var mda = $.trim($('#minimum_deposit_amount').val());
    if (!mda) errors.minimum_deposit_amount = ['Required'];
    if (!$('input[name="booking_fee_type"]:checked').length) errors.booking_fee_type = ['Required'];
    if (!$('#reschedule_times').val()) errors.reschedule_times = ['Required'];
    if (!$('#cancellation_window').val()) errors.cancellation_window = ['Required'];
    var sb = $('#session_buffer_period').val();
    if (sb === '' || isNaN(sb)) errors.session_buffer_period = ['Required'];

    var rc = $('#require_consultation').prop('checked');
    if (rc) {
      if (!$('#session_type').val()) errors.session_type = ['Required'];
      var sd = $('#session_duration_minutes').val();
      if (!sd) errors.session_duration_minutes = ['Required'];
      if (!$('#consultation_timing').val()) errors.consultation_timing = ['Required'];
      if ($('#consultation_timing').val() === 'separate' && $('#require_gap_between_consultation_tattoo').prop('checked')) {
        if (!$('#consultation_tattoo_gap_value').val()) errors.consultation_tattoo_gap_value = ['Required'];
        if (!$('#consultation_tattoo_gap_unit').val()) errors.consultation_tattoo_gap_unit = ['Required'];
      }
    }
    return errors;
  }

  $(function () {
    initSelect2();
    window.toggleSessionFields();
    window.toggleGapFields();
    window.toggleGapDurationFields();

    $('#preferencesForm').on('submit', function (e) {
      e.preventDefault();
      var $alertEl = $('#prefAlert');
      var $btn = $('#prefSubmit');
      var clientErr = validateClient();
      if (Object.keys(clientErr).length) {
        showFieldErrors(clientErr);
        return;
      }
      var fd = new FormData(this);
      var rc = $('#require_consultation').prop('checked');
      if (!rc) {
        fd.set('session_type', '');
        fd.set('session_duration_minutes', '');
        fd.set('consultation_timing', '');
      }
      $btn.prop('disabled', true);
      $.ajax({
        url: @json(route('onboarding.preferences.save')),
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
          showFieldErrors(data.errors);
          $alertEl.attr('class', 'rounded-xl px-4 py-3 text-sm bg-red-50 text-red-800 border border-red-200');
          $alertEl.text(data.message || 'Could not save').removeClass('hidden');
        })
        .fail(function (xhr) {
          if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
            showFieldErrors(xhr.responseJSON.errors);
          }
          $alertEl.attr('class', 'rounded-xl px-4 py-3 text-sm bg-red-50 text-red-800 border border-red-200');
          $alertEl.text((xhr.responseJSON && xhr.responseJSON.message) || 'Network error').removeClass('hidden');
        })
        .always(function () {
          $btn.prop('disabled', false);
        });
    });
  });
})();
