@extends('layouts.studio-dashboard-layout')

@section('title', 'Account · Studio details')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/cropperjs@1.6.2/dist/cropper.min.css">
<style>
  .addr-in{display:flex;align-items:center;gap:8px;padding:0 13px;min-height:40px;width:100%}
  .addr-in .ms{color:var(--faint);flex:none}
  .addr-in input{border:0;outline:0;font:inherit;flex:1;background:none;min-width:0;padding:10px 0;font-size:13.5px;color:var(--ink)}
  .mlbox a.mlk{color:#3E007C;text-decoration:none;font-weight:600;font-size:13px}
  .mlbox a.mlk:hover{text-decoration:underline}
  .mlbox a.mlk.is-empty{color:var(--faint);font-weight:500;pointer-events:none}
  .pac-container{z-index:400!important;border-radius:10px;border:1px solid var(--line);box-shadow:0 10px 30px rgba(0,0,0,.12);font-family:inherit;margin-top:4px}
  .field-err{display:none;color:#C62828;font-size:12px;margin-top:6px}
  .studio-logo{width:56px;height:56px;border-radius:14px;background:#F3E8FF;color:#3E007C;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:16px;overflow:hidden;flex:none;position:relative}
  .studio-logo img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;display:block;z-index:2}
  .studio-logo .ini{position:relative;z-index:1}
  .studio-logo.has-img .ini{display:none!important}
  .crop-ov{display:none;position:fixed;inset:0;z-index:300;background:rgba(0,0,0,.7);align-items:center;justify-content:center;padding:16px}
  .crop-ov.open{display:flex}
  .crop-box{width:100%;max-width:560px;background:#fff;border-radius:16px;padding:20px 22px;border:1px solid var(--line);box-shadow:0 20px 60px rgba(0,0,0,.25)}
  .crop-box h3{margin:0 0 4px;font-size:17px;font-weight:800}
  .crop-stage{width:100%;height:360px;background:#F7F2F8;border-radius:12px;overflow:hidden;margin-top:14px}
  .crop-stage img{display:block;max-width:100%}
  .crop-actions{display:flex;justify-content:flex-end;gap:10px;margin-top:16px}
</style>
@endpush

@section('content')
  @php
    $mapsHref = trim((string) ($studio?->google_maps_link ?? ''));
    $addressValue = trim((string) ($studio?->address ?? ''));
    $mapsLabel = $mapsHref
      ? ((parse_url($mapsHref, PHP_URL_HOST) ?: 'google.com/maps').' · '.($addressValue !== '' ? $addressValue : 'Open map'))
      : 'Add an address to generate a Maps link';
    $studioType = trim((string) ($studio?->studio_type ?? 'tattoo'));
    if ($studioType === '') {
      $studioType = 'tattoo';
    }
  @endphp

  <div class="head">
    <div>
      <h1>Account</h1>
      <div class="sub">Your login and your studio's details.</div>
    </div>
  </div>

  @include('studio.account._tabs')

  <div class="note" style="margin-bottom:14px">
    <span class="ms">info</span>
    Artists see these details read-only. They print on consent forms and booking emails for sessions at your studio.
  </div>

  <form id="studioDetailsForm" method="POST" action="{{ route('studio.account.studio.update') }}" enctype="multipart/form-data" novalidate>
    @csrf
    <input type="hidden" name="studio_type" id="studio_type" value="{{ $studioType }}">
    <input type="hidden" name="address" id="studio_address" value="{{ $addressValue }}">
    <input type="hidden" name="google_maps_link" id="google_maps_link" value="{{ $mapsHref }}">

    <div class="card" style="margin-bottom:14px">
      <div class="ch"><div><h3>Studio</h3></div></div>
      <div style="padding:18px 22px">
        <div class="formgrid">
          <div class="full">
            <label class="fl" for="name">Studio name <span style="color:#C62828">*</span></label>
            <input class="in" id="name" name="name" type="text" value="{{ old('name', $studioName) }}">
            <div id="name_error" class="field-err"></div>
          </div>
        </div>

        <div class="fl" style="margin-top:14px">Studio type</div>
        <div class="stype-grid" id="studioTypeGroup">
          @foreach ([
            'private' => ['Private Studio', 'A private space, by appointment'],
            'tattoo' => ['Tattoo Shop', 'Street shop with walk-ins and several artists'],
            'home' => ['Home Studio', 'Working from home. Address shared only after booking'],
            'collective' => ['Collective', 'A shared space run together by a group of artists'],
          ] as $type => $meta)
            <button type="button" class="opt{{ $studioType === $type ? ' on' : '' }}" data-v="{{ $type }}" aria-pressed="{{ $studioType === $type ? 'true' : 'false' }}">
              <span class="ms rad">{{ $studioType === $type ? 'radio_button_checked' : 'radio_button_unchecked' }}</span>
              <span><b>{{ $meta[0] }}</b><small>{{ $meta[1] }}</small></span>
            </button>
          @endforeach
        </div>
        <div id="studio_type_error" class="field-err"></div>
      </div>
    </div>

    <div class="card" style="margin-bottom:14px">
      <div class="ch"><div><h3>Address</h3></div></div>
      <div style="padding:18px 22px">
        <div style="margin-bottom:14px">
          <label class="fl" for="address_search">Find your address</label>
          <div class="in addr-in">
            <span class="ms">location_on</span>
            <input type="text" id="address_search" value="{{ $addressValue }}" placeholder="Start typing your address" autocomplete="off">
          </div>
          <div class="help">Start typing and pick from Google suggestions to fill the fields below.</div>
        </div>

        <div class="formgrid">
          <div>
            <label class="fl" for="street_name">Street <span style="color:#C62828">*</span></label>
            <input class="in" id="street_name" name="street_name" type="text" value="{{ old('street_name', $studio?->street_name ?? '') }}">
            <div id="street_name_error" class="field-err"></div>
          </div>
          <div>
            <label class="fl" for="street_number">Street number</label>
            <input class="in" id="street_number" name="street_number" type="text" value="{{ old('street_number', $studio?->street_number ?? '') }}">
            <div id="street_number_error" class="field-err"></div>
          </div>
          <div>
            <label class="fl" for="city">City <span style="color:#C62828">*</span></label>
            <input class="in" id="city" name="city" type="text" value="{{ old('city', $studio?->city ?? '') }}">
            <div id="city_error" class="field-err"></div>
          </div>
          <div>
            <label class="fl" for="state">State or region</label>
            <input class="in" id="state" name="state" type="text" value="{{ old('state', $studio?->state ?? '') }}">
            <div id="state_error" class="field-err"></div>
          </div>
          <div>
            <label class="fl" for="postal_code">Postal code</label>
            <input class="in" id="postal_code" name="postal_code" type="text" value="{{ old('postal_code', $studio?->postal_code ?? '') }}">
            <div id="postal_code_error" class="field-err"></div>
          </div>
          <div>
            <label class="fl" for="country">Country <span style="color:#C62828">*</span></label>
            <input class="in" id="country" name="country" type="text" value="{{ old('country', $studio?->country ?? '') }}" autocomplete="country-name">
            <div id="country_error" class="field-err"></div>
          </div>
          <div class="full">
            <label class="fl">Google Maps link</label>
            <div class="mlbox" id="mapsBox">
              <span class="ms">map</span>
              <a class="mlk{{ $mapsHref ? '' : ' is-empty' }}" id="mapsLink" href="{{ $mapsHref ?: '#' }}" target="_blank" rel="noopener">{{ $mapsLabel }}</a>
              <button type="button" class="mlcopy" id="mapsCopy"><span class="ms">content_copy</span>Copy</button>
            </div>
            <div class="help">Made from the address, so it updates when you change the address. Clients see it on your studio page and in booking emails. Copy it to share.</div>
            <div id="google_maps_link_error" class="field-err"></div>
          </div>
        </div>
      </div>
    </div>

    <div class="card" style="margin-bottom:14px">
      <div class="ch"><div><h3>Contact and logo</h3></div></div>
      <div style="padding:18px 22px">
        <div class="row" style="gap:16px;margin-bottom:16px">
          <div id="studioLogo" class="studio-logo{{ !empty($studioLogoUrl) ? ' has-img' : '' }}">
            <img id="studioLogoImg" src="{{ $studioLogoUrl ?? '' }}" alt="" @unless($studioLogoUrl) style="display:none" @endunless>
            <span class="ini" id="studioLogoInitials">{{ $studioInitials }}</span>
          </div>
          <div>
            <button type="button" class="btn ghost sm" id="changeLogoBtn"><span class="ms">upload</span>Change logo</button>
            <input id="logoImageInput" type="file" accept="image/*" hidden>
            <div id="logo_error" class="field-err"></div>
          </div>
        </div>
        <div class="formgrid">
          <div>
            <label class="fl" for="business_phone">Business phone <span style="color:#C62828">*</span></label>
            <input class="in" id="business_phone" name="business_phone" type="text" value="{{ old('business_phone', $studio?->business_phone ?? '') }}">
            <div id="business_phone_error" class="field-err"></div>
          </div>
          <div>
            <label class="fl" for="contact_email">Contact email</label>
            <input class="in" id="contact_email" name="contact_email" type="email" value="{{ old('contact_email', $studio?->contact_email ?? $studioEmail) }}">
            <div id="contact_email_error" class="field-err"></div>
          </div>
          <div>
            <label class="fl" for="tattooing_since">Tattooing since</label>
            <input class="in" id="tattooing_since" name="tattooing_since" type="text" value="{{ old('tattooing_since', $studio?->tattooing_since ?? '') }}" placeholder="e.g. 2019" inputmode="numeric">
            <div class="help">The year the studio opened. Optional.</div>
            <div id="tattooing_since_error" class="field-err"></div>
          </div>
        </div>
      </div>
    </div>

    <div class="pgsaverow">
      <button class="btn pgsave" id="studioSaveBtn" type="submit"><span class="ms">save</span>Save changes</button>
    </div>
  </form>

  <div id="logoCropperModal" class="crop-ov" role="dialog" aria-modal="true" aria-labelledby="logoCropTitle">
    <div class="crop-box">
      <h3 id="logoCropTitle">Crop studio logo</h3>
      <div class="sub">Adjust your image to a square crop for a uniform logo.</div>
      <div class="crop-stage">
        <img id="logoCropImage" src="" alt="">
      </div>
      <div class="crop-actions">
        <button id="cancelLogoCropBtn" type="button" class="btn ghost">Cancel</button>
        <button id="applyLogoCropBtn" type="button" class="btn">Use logo</button>
      </div>
    </div>
  </div>
@endsection

@push('scripts')
@if (config('services.google.place_api_key'))
<script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google.place_api_key') }}&libraries=places"></script>
@endif
<script src="https://unpkg.com/cropperjs@1.6.2/dist/cropper.min.js"></script>
<script>
(function () {
  var form = document.getElementById('studioDetailsForm');
  if (!form) return;

  var btn = document.getElementById('studioSaveBtn');
  var defaultBtnHtml = btn ? btn.innerHTML : '';
  var fields = [
    'name', 'studio_type', 'street_name', 'street_number', 'city', 'state',
    'postal_code', 'country', 'google_maps_link', 'business_phone', 'contact_email', 'tattooing_since', 'logo'
  ];
  var logoWrap = document.getElementById('studioLogo');
  var logoImg = document.getElementById('studioLogoImg');
  var changeLogoBtn = document.getElementById('changeLogoBtn');
  var logoImageInput = document.getElementById('logoImageInput');
  var logoCropperModal = document.getElementById('logoCropperModal');
  var logoCropImage = document.getElementById('logoCropImage');
  var logoCropper = null;
  var logoObjectUrl = '';
  var croppedLogoBlob = null;
  var logoPreviewUrl = '';

  var group = document.getElementById('studioTypeGroup');
  if (group) {
    group.addEventListener('click', function (e) {
      var opt = e.target.closest('.opt');
      if (!opt) return;
      group.querySelectorAll('.opt').forEach(function (el) {
        var on = el === opt;
        el.classList.toggle('on', on);
        el.setAttribute('aria-pressed', on ? 'true' : 'false');
        var icon = el.querySelector('.rad');
        if (icon) icon.textContent = on ? 'radio_button_checked' : 'radio_button_unchecked';
      });
      setVal('studio_type', opt.getAttribute('data-v') || '');
      clearFieldError('studio_type');
    });
  }

  function val(id) {
    var el = document.getElementById(id);
    return el ? String(el.value || '').trim() : '';
  }

  function setVal(id, value) {
    var el = document.getElementById(id);
    if (el) el.value = value || '';
  }

  function clearFieldError(name) {
    var el = document.getElementById(name + '_error');
    if (el) {
      el.textContent = '';
      el.style.display = 'none';
    }
  }

  function setFieldError(name, message) {
    var el = document.getElementById(name + '_error');
    if (!el) return;
    el.textContent = message || '';
    el.style.display = message ? 'block' : 'none';
  }

  function clearAllErrors() {
    fields.forEach(clearFieldError);
  }

  fields.forEach(function (name) {
    var input = document.getElementById(name);
    if (input) input.addEventListener('input', function () { clearFieldError(name); });
  });

  function setLogoPreview(url) {
    if (!logoImg || !logoWrap) return;
    var previous = logoPreviewUrl;
    logoPreviewUrl = '';
    if (!url) {
      if (previous) URL.revokeObjectURL(previous);
      logoImg.removeAttribute('src');
      logoImg.style.display = 'none';
      logoWrap.classList.remove('has-img');
      return;
    }
    if (previous && previous !== url) URL.revokeObjectURL(previous);
    if (String(url).indexOf('blob:') === 0) logoPreviewUrl = url;
    logoImg.src = url;
    logoImg.style.display = 'block';
    logoWrap.classList.add('has-img');
  }

  function closeLogoCropper() {
    if (logoCropperModal) logoCropperModal.classList.remove('open');
    if (logoCropper) {
      logoCropper.destroy();
      logoCropper = null;
    }
    if (logoCropImage) logoCropImage.src = '';
    if (logoImageInput) logoImageInput.value = '';
    if (logoObjectUrl) {
      URL.revokeObjectURL(logoObjectUrl);
      logoObjectUrl = '';
    }
  }

  if (changeLogoBtn && logoImageInput) {
    changeLogoBtn.addEventListener('click', function () {
      clearFieldError('logo');
      logoImageInput.click();
    });

    logoImageInput.addEventListener('change', function (e) {
      clearFieldError('logo');
      var file = e.target.files && e.target.files[0];
      if (!file) return;
      if (!/^image\//.test(file.type)) {
        setFieldError('logo', 'Please choose a valid image file.');
        logoImageInput.value = '';
        return;
      }
      if (logoObjectUrl) URL.revokeObjectURL(logoObjectUrl);
      logoObjectUrl = URL.createObjectURL(file);
      logoCropImage.src = logoObjectUrl;
      logoCropperModal.classList.add('open');
      if (logoCropper) logoCropper.destroy();
      logoCropper = new Cropper(logoCropImage, {
        aspectRatio: 1,
        viewMode: 1,
        dragMode: 'move',
        background: false,
        autoCropArea: 1,
        responsive: true
      });
    });
  }

  document.getElementById('cancelLogoCropBtn')?.addEventListener('click', closeLogoCropper);
  logoCropperModal?.addEventListener('click', function (e) {
    if (e.target === logoCropperModal) closeLogoCropper();
  });

  document.getElementById('applyLogoCropBtn')?.addEventListener('click', function () {
    if (!logoCropper) return;
    var canvas = logoCropper.getCroppedCanvas({ width: 512, height: 512, imageSmoothingQuality: 'high' });
    canvas.toBlob(function (blob) {
      if (!blob) {
        setFieldError('logo', 'Could not crop this image. Try another photo.');
        return;
      }
      croppedLogoBlob = blob;
      setLogoPreview(URL.createObjectURL(blob));
      clearFieldError('logo');
      closeLogoCropper();
    }, 'image/jpeg', 0.92);
  });

  function buildMapsLinkFromFields() {
    var parts = [
      val('street_number'),
      val('street_name'),
      val('city'),
      val('state'),
      val('postal_code'),
      val('country'),
    ].filter(Boolean);
    if (!parts.length) return '';
    return 'https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(parts.join(', '));
  }

  function buildAddressFromFields() {
    return [
      val('street_number'),
      val('street_name'),
      val('city'),
      val('state'),
      val('postal_code'),
      val('country'),
    ].filter(Boolean).join(', ');
  }

  function updateMapsBox(link, label) {
    var a = document.getElementById('mapsLink');
    var hidden = document.getElementById('google_maps_link');
    if (hidden) hidden.value = link || '';
    if (!a) return;
    if (!link) {
      a.setAttribute('href', '#');
      a.classList.add('is-empty');
      a.textContent = 'Add an address to generate a Maps link';
      return;
    }
    a.setAttribute('href', link);
    a.classList.remove('is-empty');
    a.textContent = label || link;
  }

  function syncAddressFromFields() {
    var link = buildMapsLinkFromFields();
    var addr = buildAddressFromFields();
    setVal('studio_address', addr);
    updateMapsBox(link, link ? ('google.com/maps · ' + addr) : '');
  }

  ['street_number', 'street_name', 'city', 'state', 'postal_code', 'country'].forEach(function (id) {
    var el = document.getElementById(id);
    if (el) el.addEventListener('input', syncAddressFromFields);
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

  function initAutocomplete() {
    var input = document.getElementById('address_search');
    if (!input || typeof google === 'undefined' || !google.maps || !google.maps.places) return;

    var ac = new google.maps.places.Autocomplete(input, {
      types: ['address'],
      fields: ['address_components', 'formatted_address', 'place_id', 'geometry'],
    });

    ac.addListener('place_changed', function () {
      var place = ac.getPlace();
      if (!place || !place.address_components) return;

      var sn = '', st = '', city = '', state = '', zip = '', country = '';
      place.address_components.forEach(function (c) {
        var t = c.types || [];
        if (t.indexOf('street_number') !== -1) sn = c.long_name;
        if (t.indexOf('route') !== -1) st = c.long_name;
        if (t.indexOf('locality') !== -1) city = c.long_name;
        else if (t.indexOf('postal_town') !== -1 && !city) city = c.long_name;
        if (t.indexOf('administrative_area_level_1') !== -1) state = c.short_name || c.long_name;
        if (t.indexOf('postal_code') !== -1) zip = c.long_name;
        if (t.indexOf('country') !== -1) country = c.long_name;
      });

      setVal('street_number', sn);
      setVal('street_name', st);
      setVal('city', city);
      setVal('state', state);
      setVal('postal_code', zip);
      setVal('country', country);

      var formatted = place.formatted_address || buildAddressFromFields();
      setVal('studio_address', formatted);
      input.value = formatted;

      var link = place.place_id
        ? ('https://www.google.com/maps/place/?q=place_id:' + place.place_id)
        : ('https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(formatted || ''));

      updateMapsBox(link, 'google.com/maps · ' + formatted);
      ['street_name', 'city', 'country'].forEach(clearFieldError);
    });
  }

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    clearAllErrors();
    syncAddressFromFields();

    if (btn) {
      btn.disabled = true;
      btn.innerHTML = '<span class="ms">hourglass_top</span>Saving…';
    }

    var body = new FormData(form);
    if (croppedLogoBlob) {
      var logoFile = croppedLogoBlob;
      try {
        logoFile = new File([croppedLogoBlob], 'logo.jpg', { type: croppedLogoBlob.type || 'image/jpeg' });
      } catch (err) {}
      body.set('logo', logoFile, 'logo.jpg');
    }

    fetch(form.action, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || form.querySelector('input[name="_token"]').value,
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json'
      },
      body: body
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
          croppedLogoBlob = null;
          if (result.data.studio) {
            var ini = document.getElementById('studioLogoInitials');
            if (ini && result.data.studio.initials) ini.textContent = result.data.studio.initials;
            if (result.data.studio.logo) setLogoPreview(result.data.studio.logo);
            if (result.data.studio.address) {
              setVal('studio_address', result.data.studio.address);
              var search = document.getElementById('address_search');
              if (search) search.value = result.data.studio.address;
            }
            if (result.data.studio.google_maps_link) {
              updateMapsBox(
                result.data.studio.google_maps_link,
                'google.com/maps · ' + (result.data.studio.address || result.data.studio.name || '')
              );
            }
          }
          return;
        }

        var errors = (result.data && result.data.errors) || {};
        fields.forEach(function (name) {
          if (errors[name] && errors[name][0]) setFieldError(name, errors[name][0]);
        });
      })
      .catch(function () {
        if (window.bpToast) window.bpToast('Something went wrong. Please try again.', true);
        else setFieldError('name', 'Something went wrong. Please try again.');
      })
      .finally(function () {
        if (btn) {
          btn.disabled = false;
          btn.innerHTML = defaultBtnHtml;
        }
      });
  });

  if (document.readyState === 'complete') {
    initAutocomplete();
  } else {
    window.addEventListener('load', initAutocomplete);
  }
})();
</script>
@endpush
