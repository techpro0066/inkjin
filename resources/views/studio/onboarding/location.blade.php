@extends('layouts.studio-onboarding-layout')

@section('title', 'Location & type')

@push('styles')
<style>
  .field-err{display:none;color:#C62828;font-size:12px;margin-top:6px}
  .in.err{border-color:#C62828!important;box-shadow:0 0 0 3px #FDECEC}
</style>
@endpush

@section('content')
  <h1 style="margin-top:6px">Studio location &amp; type<a class="help-q" href="https://help.inkjin.com" target="_blank" rel="noopener" title="Help with this page" aria-label="Help with this page"><span class="ms">help</span></a></h1>
  <div class="sub" style="max-width:660px;margin-bottom:24px">Tell clients where to find you. The address shows on your studio page and in booking emails.</div>

  <form id="studioOnboardingLocationForm" method="POST" action="{{ route('studio.onboarding.location.update') }}" novalidate>
    @csrf
    <input type="hidden" name="studio_type" id="studio_type" value="{{ $studioType }}">
    <input type="hidden" name="address" id="studio_address" value="{{ $addressValue }}">
    <input type="hidden" name="google_maps_link" id="google_maps_link" value="{{ $mapsHref }}">

    <div class="card" style="margin-bottom:14px">
      <div class="ch">
        <div>
          <h3>Address</h3>
          <div class="faint" style="font-size:12.5px;margin-top:2px">Start typing and pick the address. The fields fill in.</div>
        </div>
      </div>
      <div class="card-pad">
        <div class="in addr-in">
          <span class="ms">search</span>
          <input type="text" id="address_search" value="{{ $addressValue }}" placeholder="Start typing your address" autocomplete="off">
        </div>
        <div class="formgrid" style="margin-top:14px">
          <div>
            <span class="fl">Street <span style="color:#C62828">*</span></span>
            <input class="in" id="street_name" name="street_name" type="text" value="{{ old('street_name', $studio?->street_name ?? '') }}" placeholder="">
            <div id="street_name_error" class="field-err"></div>
          </div>
          <div>
            <span class="fl">Street number</span>
            <input class="in" id="street_number" name="street_number" type="text" value="{{ old('street_number', $studio?->street_number ?? '') }}" placeholder="">
            <div id="street_number_error" class="field-err"></div>
          </div>
          <div>
            <span class="fl">City <span style="color:#C62828">*</span></span>
            <input class="in" id="city" name="city" type="text" value="{{ old('city', $studio?->city ?? '') }}" placeholder="">
            <div id="city_error" class="field-err"></div>
          </div>
          <div>
            <span class="fl">State or region</span>
            <input class="in" id="state" name="state" type="text" value="{{ old('state', $studio?->state ?? '') }}" placeholder="">
            <div id="state_error" class="field-err"></div>
          </div>
          <div>
            <span class="fl">Postal code</span>
            <input class="in" id="postal_code" name="postal_code" type="text" value="{{ old('postal_code', $studio?->postal_code ?? '') }}" placeholder="">
            <div id="postal_code_error" class="field-err"></div>
          </div>
          <div>
            <span class="fl">Country <span style="color:#C62828">*</span></span>
            <input class="in" id="country" name="country" type="text" value="{{ old('country', $studio?->country ?? '') }}" placeholder="" autocomplete="country-name">
            <div id="country_error" class="field-err"></div>
          </div>
          <div class="full">
            <span class="fl">Google Maps link</span>
            <div class="mlbox" id="mapsBox">
              <span class="ms">map</span>
              <a class="mlk{{ $mapsHref ? '' : ' is-empty' }}" id="mapsLink" href="{{ $mapsHref ?: '#' }}" target="_blank" rel="noopener">{{ $mapsLabel }}</a>
              <button type="button" class="mlcopy" id="mapsCopy" aria-label="Copy maps link"><span class="ms">content_copy</span>Copy</button>
            </div>
            <div class="help">Made from the address, so it updates when you change the address. Clients see it on your studio page and in booking emails. Copy it to share.</div>
            <div id="google_maps_link_error" class="field-err"></div>
          </div>
        </div>
      </div>
    </div>

    <div class="card" style="margin-bottom:14px">
      <div class="ch">
        <div>
          <h3>Studio type</h3>
          <div class="faint" style="font-size:12.5px;margin-top:2px">What best describes your space?</div>
        </div>
      </div>
      <div class="card-pad">
        <div class="grid" data-group="stype" style="grid-template-columns:1fr 1fr;gap:10px">
          @foreach ([
            'private' => ['Private Studio', 'A private space, by appointment'],
            'tattoo' => ['Tattoo Shop', 'Street shop with walk-ins and several artists'],
            'home' => ['Home Studio', 'Working from home. Address shared only after booking'],
            'collective' => ['Collective', 'A shared space run together by a group of artists'],
          ] as $type => $meta)
            <div class="opt{{ $studioType === $type ? ' on' : '' }}" data-v="{{ $type }}" role="radio" aria-checked="{{ $studioType === $type ? 'true' : 'false' }}" tabindex="0">
              <span class="ms rad">{{ $studioType === $type ? 'radio_button_checked' : 'radio_button_unchecked' }}</span>
              <div style="flex:1"><b>{{ $meta[0] }}</b><small>{{ $meta[1] }}</small></div>
            </div>
          @endforeach
        </div>
        <div id="studio_type_error" class="field-err"></div>
      </div>
    </div>

    <div class="obfoot">
      <a class="btn ghost" href="{{ route('studio.onboarding.owner') }}"><span class="ms">arrow_back</span>Back</a>
      <div class="row" style="gap:0">
        <button type="submit" class="btn" id="nextStepBtn">Next step<span class="ms">arrow_forward</span></button>
      </div>
    </div>
  </form>
@endsection

@push('scripts')
@if (config('services.google.place_api_key'))
<script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google.place_api_key') }}&libraries=places"></script>
@endif
<script>
(function () {
  var form = document.getElementById('studioOnboardingLocationForm');
  var btn = document.getElementById('nextStepBtn');
  var defaultBtnHtml = btn ? btn.innerHTML : '';
  var fields = ['street_name', 'street_number', 'city', 'state', 'postal_code', 'country', 'studio_type', 'google_maps_link'];

  function val(id) {
    var el = document.getElementById(id);
    return el ? (el.value || '').trim() : '';
  }
  function setVal(id, value) {
    var el = document.getElementById(id);
    if (el) el.value = value == null ? '' : value;
  }
  function clearFieldError(name) {
    var el = document.getElementById(name + '_error');
    if (el) {
      el.textContent = '';
      el.style.display = 'none';
    }
    var input = document.getElementById(name);
    if (input && input.classList) input.classList.remove('err');
  }
  function setFieldError(name, message) {
    var el = document.getElementById(name + '_error');
    if (el) {
      el.textContent = message || '';
      el.style.display = message ? 'block' : 'none';
    }
    var input = document.getElementById(name);
    if (input && input.classList && name !== 'studio_type') {
      if (message) input.classList.add('err');
      else input.classList.remove('err');
    }
  }
  function clearAllErrors() {
    fields.forEach(clearFieldError);
  }
  function fieldErrorMessage(errors, name) {
    if (!errors) return '';
    if (errors[name] && errors[name][0]) return errors[name][0];
    return '';
  }
  function updateMapsBox(link, label) {
    var a = document.getElementById('mapsLink');
    var hidden = document.getElementById('google_maps_link');
    if (hidden) hidden.value = link || '';
    if (!a) return;
    if (link) {
      a.href = link;
      a.textContent = label || link;
      a.classList.remove('is-empty');
    } else {
      a.href = '#';
      a.textContent = 'Maps link will appear here';
      a.classList.add('is-empty');
    }
  }
  function buildMapsLinkFromFields() {
    var parts = [val('street_number'), val('street_name'), val('city'), val('state'), val('postal_code'), val('country')].filter(Boolean);
    if (!parts.length) return '';
    return 'https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(parts.join(', '));
  }
  function syncAddressFromFields() {
    var parts = [val('street_number'), val('street_name'), val('city'), val('state'), val('postal_code'), val('country')].filter(Boolean);
    var addr = parts.join(', ');
    setVal('studio_address', addr);
    var search = document.getElementById('address_search');
    if (search && document.activeElement !== search) search.value = addr;
    var link = buildMapsLinkFromFields();
    updateMapsBox(link, link ? ('google.com/maps · ' + addr) : '');
  }

  document.querySelectorAll('[data-group="stype"] .opt').forEach(function (opt) {
    opt.addEventListener('click', function () {
      var group = opt.closest('[data-group]');
      if (!group) return;
      group.querySelectorAll('.opt').forEach(function (item) {
        var on = item === opt;
        item.classList.toggle('on', on);
        item.setAttribute('aria-checked', on ? 'true' : 'false');
        var rad = item.querySelector('.rad');
        if (rad) rad.textContent = on ? 'radio_button_checked' : 'radio_button_unchecked';
      });
      setVal('studio_type', opt.getAttribute('data-v') || '');
      clearFieldError('studio_type');
    });
  });

  ['street_name', 'street_number', 'city', 'state', 'postal_code', 'country'].forEach(function (id) {
    var el = document.getElementById(id);
    if (!el) return;
    el.addEventListener('input', function () {
      clearFieldError(id);
      syncAddressFromFields();
    });
  });

  document.getElementById('mapsCopy')?.addEventListener('click', function () {
    var link = val('google_maps_link') || buildMapsLinkFromFields();
    if (!link) {
      if (window.bpToast) window.bpToast('Add an address first', true);
      return;
    }
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(link).then(function () {
        if (window.bpToast) window.bpToast('Copied');
      }).catch(function () {
        if (window.bpToast) window.bpToast('Could not copy', true);
      });
    }
  });

  function initPlaces() {
    var input = document.getElementById('address_search');
    if (!input || typeof google === 'undefined' || !google.maps || !google.maps.places) return;
    var ac = new google.maps.places.Autocomplete(input, {
      fields: ['address_components', 'formatted_address', 'place_id', 'url'],
      types: ['address']
    });
    ac.addListener('place_changed', function () {
      var place = ac.getPlace() || {};
      var comps = place.address_components || [];
      var sn = '', st = '', city = '', state = '', zip = '', country = '';
      comps.forEach(function (c) {
        var t = (c.types || []).join(' ');
        if (t.indexOf('street_number') !== -1) sn = c.long_name;
        if (t.indexOf('route') !== -1) st = c.long_name;
        if (t.indexOf('locality') !== -1 || t.indexOf('postal_town') !== -1) city = c.long_name;
        if (t.indexOf('administrative_area_level_1') !== -1) state = c.long_name;
        if (t.indexOf('postal_code') !== -1) zip = c.long_name;
        if (t.indexOf('country') !== -1) country = c.long_name;
      });
      setVal('street_number', sn);
      setVal('street_name', st);
      setVal('city', city);
      setVal('state', state);
      setVal('postal_code', zip);
      setVal('country', country);
      ['street_name', 'city', 'country'].forEach(clearFieldError);
      var formatted = place.formatted_address || input.value || '';
      setVal('studio_address', formatted);
      input.value = formatted;
      var link = place.url
        || (place.place_id ? ('https://www.google.com/maps/place/?q=place_id:' + place.place_id) : '')
        || ('https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(formatted || ''));
      updateMapsBox(link, 'google.com/maps · ' + formatted);
    });
  }

  if (document.readyState === 'complete') initPlaces();
  else window.addEventListener('load', initPlaces);

  if (!form) return;

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    clearAllErrors();
    syncAddressFromFields();

    if (btn) {
      btn.disabled = true;
      btn.innerHTML = '<span class="ms">hourglass_top</span>Saving…';
    }

    fetch(form.action, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || form.querySelector('input[name="_token"]').value,
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json'
      },
      body: new FormData(form)
    })
      .then(function (response) {
        return response.json().then(function (data) {
          return { ok: response.ok, status: response.status, data: data };
        }).catch(function () {
          return { ok: false, status: response.status, data: {} };
        });
      })
      .then(function (result) {
        if (result.ok && result.data && result.data.success) {
          if (window.bpToast) window.bpToast('Changes saved');
          if (result.data.studio && result.data.studio.google_maps_link) {
            updateMapsBox(
              result.data.studio.google_maps_link,
              'google.com/maps · ' + (result.data.studio.address || '')
            );
          }
          window.location.href = @json(route('studio.onboarding.terms'));
          return;
        }

        var errors = (result.data && result.data.errors) || {};
        var shown = false;
        fields.forEach(function (name) {
          var msg = fieldErrorMessage(errors, name);
          if (msg) {
            setFieldError(name, msg);
            shown = true;
          }
        });

        if (!shown && window.bpToast) {
          window.bpToast((result.data && result.data.message) || 'Could not save location.', true);
        }
      })
      .catch(function () {
        if (window.bpToast) window.bpToast('Something went wrong. Please try again.', true);
      })
      .finally(function () {
        if (btn) {
          btn.disabled = false;
          btn.innerHTML = defaultBtnHtml;
        }
      });
  });
})();
</script>
@endpush
