{{--
  Shared address block for studio onboarding (not-on-Bookpay + own space).
  @var string $nameLabel
  @var string $nameId
  @var string $nameValue
  @var string|null $nameInputName  — omit to use $nameId as name
  @var string $addressSearchId
  @var string $mapsBoxId
  @var string $mapsHref
  @var string $mapsLabel
  @var \App\Models\UserDetail|null $userDetail
  @var string $prefix  — '' or 'own_'
  @var bool $fieldsDisabled
--}}
@php
  $nameInputName = $nameInputName ?? $nameId;
  $prefix = $prefix ?? '';
  $ud = $userDetail;
  $disabled = !empty($fieldsDisabled);
  $disAttr = $disabled ? 'disabled' : '';
@endphp
<div class="grid" style="gap:14px">
  <div>
    <label class="fl" for="{{ $nameId }}">{{ $nameLabel }} <span style="color:#C62828">*</span></label>
    <input class="in" type="text" id="{{ $nameId }}" name="{{ $nameInputName }}" value="{{ $nameValue }}" placeholder="" {{ $disAttr }}>
    <p id="{{ $nameId }}_error" class="field-err hidden" role="alert"></p>
  </div>

  <div>
    <label class="fl" for="{{ $addressSearchId }}">Find your address <span style="color:#C62828">*</span></label>
    <div class="in addr-in">
      <span class="ms">location_on</span>
      <input type="text" id="{{ $addressSearchId }}" value="{{ $ud->studio_address ?? '' }}" placeholder="Start typing your address" autocomplete="off" {{ $disAttr }}>
    </div>
    <div class="help">Start typing and pick from Google suggestions to fill the fields below.</div>
    <p id="{{ $addressSearchId }}_error" class="field-err hidden" role="alert"></p>
  </div>

  <div class="grid addr-grid-2" style="grid-template-columns:1fr 2fr;gap:12px">
    <div>
      <label class="fl" for="{{ $prefix }}street_number">Street number <span style="color:#C62828">*</span></label>
      <input class="in" type="text" id="{{ $prefix }}street_number" name="{{ $prefix === '' ? 'street_number' : $prefix.'street_number' }}" value="{{ $ud->street_number ?? '' }}" {{ $disAttr }}>
      <p id="{{ $prefix }}street_number_error" class="field-err hidden" role="alert"></p>
    </div>
    <div>
      <label class="fl" for="{{ $prefix }}street_name">Street name <span style="color:#C62828">*</span></label>
      <input class="in" type="text" id="{{ $prefix }}street_name" name="{{ $prefix === '' ? 'street_name' : $prefix.'street_name' }}" value="{{ $ud->street_name ?? '' }}" {{ $disAttr }}>
      <p id="{{ $prefix }}street_name_error" class="field-err hidden" role="alert"></p>
    </div>
  </div>

  <div class="grid addr-grid-2" style="grid-template-columns:1fr 1fr;gap:12px">
    <div>
      <label class="fl" for="{{ $prefix }}city">City <span style="color:#C62828">*</span></label>
      <input class="in" type="text" id="{{ $prefix }}city" name="{{ $prefix === '' ? 'city' : $prefix.'city' }}" value="{{ $ud->city ?? '' }}" {{ $disAttr }}>
      <p id="{{ $prefix }}city_error" class="field-err hidden" role="alert"></p>
    </div>
    <div>
      <label class="fl" for="{{ $prefix }}state">State / Province <span style="color:#C62828">*</span></label>
      <input class="in" type="text" id="{{ $prefix }}state" name="{{ $prefix === '' ? 'state' : $prefix.'state' }}" value="{{ $ud->state ?? '' }}" {{ $disAttr }}>
      <p id="{{ $prefix }}state_error" class="field-err hidden" role="alert"></p>
    </div>
    <div>
      <label class="fl" for="{{ $prefix }}postal_code">Postal code <span style="color:#C62828">*</span></label>
      <input class="in" type="text" id="{{ $prefix }}postal_code" name="{{ $prefix === '' ? 'postal_code' : $prefix.'postal_code' }}" value="{{ $ud->postal_code ?? '' }}" {{ $disAttr }}>
      <p id="{{ $prefix }}postal_code_error" class="field-err hidden" role="alert"></p>
    </div>
    <div>
      <label class="fl" for="{{ $prefix }}country">Country <span style="color:#C62828">*</span></label>
      <input class="in" type="text" id="{{ $prefix }}country" name="{{ $prefix === '' ? 'country' : $prefix.'country' }}" value="{{ $ud->country ?? '' }}" {{ $disAttr }}>
      <p id="{{ $prefix }}country_error" class="field-err hidden" role="alert"></p>
    </div>
  </div>

  <div>
    <span class="fl">Google Maps link</span>
    <div class="mlbox" id="{{ $mapsBoxId }}" data-auto>
      <span class="ms">map</span>
      <a class="mlk {{ $mapsHref ? '' : 'is-empty' }}" href="{{ $mapsHref ?: '#' }}" target="_blank" rel="noopener">{{ $mapsLabel }}</a>
      <button type="button" class="mlcopy"><span class="ms">content_copy</span>Copy</button>
    </div>
    <div class="help">Made from the address, so it updates when you change the address. Copy it to share.</div>
    <p id="{{ $prefix }}google_maps_link_error" class="field-err hidden" role="alert"></p>
  </div>
</div>
