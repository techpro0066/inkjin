@extends('layouts.artist-onboarding-layout')

@section('title', 'Studio — Artist onboarding')

@php
  $wt = $userDetail->workspace_type ?? '';
  $hasStudio = filled($userDetail->studio_name ?? null);
  $relSelected = $userDetail->studio_relationship_type ?? '';
  // Resume path: relationship is only saved for "not on Bookpay"; empty → own space.
  $initialPath = '';
  if ($hasStudio) {
    $initialPath = filled($relSelected) ? 'not_found' : 'own_space';
  }
  $studioTypes = [
    'private' => ['icon' => 'home', 'label' => 'Private Studio', 'desc' => 'A personal workspace. Clients visit by appointment only'],
    'shop' => ['icon' => 'storefront', 'label' => 'Tattoo Shop', 'desc' => 'A shared shop with walk-ins and appointments'],
    'home' => ['icon' => 'cottage', 'label' => 'Home Studio', 'desc' => 'Working from home. Address shared only after booking'],
  ];
  $ownSpaceTypes = [
    'private' => $studioTypes['private'],
    'home' => $studioTypes['home'],
  ];
  $relationshipTypes = [
    'co_owner' => ['label' => 'Co-owner', 'desc' => 'Owns or partners in the studio'],
    'resident' => ['label' => 'Resident', 'desc' => 'Permanent spot with regular bookings'],
    'collective_member' => ['label' => 'Collective Member', 'desc' => 'Part of a collective studio model'],
    'apprentice' => ['label' => 'Apprentice', 'desc' => 'Learning and training under someone'],
    'other' => ['label' => 'Other (Contract Artist, Freelancer)', 'desc' => 'Contract or freelance arrangement'],
  ];
  $mapsHref = $userDetail->google_maps_link ?? '';
  $mapsLabel = $mapsHref ? (parse_url($mapsHref, PHP_URL_HOST) ?: 'google.com/maps') . ' · ' . ($userDetail->studio_address ?? 'Open map') : 'google.com/maps';
@endphp

@push('styles')
<style>
  .field-err{color:#C62828;font-size:12px;margin-top:6px}
  .field-err.hidden{display:none}
  input.in.is-err{border-color:#C62828}
  .in.is-err{border-color:#C62828}
  button.btn,a.btn{border:0;cursor:pointer;font:inherit;text-decoration:none}
  button.btn:disabled{opacity:.6;cursor:not-allowed}
  .po input{accent-color:#3E007C;width:18px;height:18px;margin-top:2px;flex-shrink:0}
  .studio-panel[hidden]{display:none!important}
  .mlbox{display:flex;align-items:center;gap:8px;border:1px solid var(--line);background:#FAF8FB;border-radius:10px;padding:8px 8px 8px 12px;min-width:0}
  .mlbox>.ms{font-size:18px;color:#3E007C;flex:none}
  .mlbox a.mlk{flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#3E007C;font-weight:600;font-size:13px;text-decoration:none}
  .mlbox a.mlk:hover{text-decoration:underline}
  .mlbox a.mlk.is-empty{color:var(--faint);font-weight:500;pointer-events:none}
  .mlcopy{flex:none;border:1px solid #D9D2DC;background:#fff;border-radius:8px;padding:5px 10px;font:inherit;font-size:12.5px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:4px}
  .mlcopy .ms{font-size:16px}
  .search-in{display:flex;align-items:center;gap:8px}
  .search-in input{border:0;outline:0;font:inherit;flex:1;background:none;min-width:0;padding:2px 0}
  .addr-in{display:flex;align-items:center;gap:8px}
  .addr-in input{border:0;outline:0;font:inherit;flex:1;background:none;min-width:0;padding:2px 0}
  .path-err{color:#C62828;font-size:12.5px;margin-top:8px}
  .path-err.hidden{display:none}
  @media (max-width:700px){
    .addr-grid-2{grid-template-columns:1fr!important}
  }
</style>
@endpush

@push('head')
@if(config('services.google.place_api_key'))
<script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google.place_api_key') }}&libraries=places"></script>
@endif
@endpush

@section('content')
<form id="studioForm">
  @csrf
  <input type="hidden" name="workspace_type" id="workspace_type" value="{{ $wt }}">
  <input type="hidden" name="studio_address" id="studio_address" value="{{ $userDetail->studio_address ?? '' }}">
  <input type="hidden" name="google_maps_link" id="google_maps_link" value="{{ $userDetail->google_maps_link ?? '' }}">
  <input type="hidden" name="latitude" id="latitude" value="">
  <input type="hidden" name="longitude" id="longitude" value="">
  <input type="hidden" name="studio_path" id="studio_path" value="{{ $initialPath }}">

  <div class="wrap">
    <h1 style="margin-top:6px">Set up your studio<a class="help-q" href="https://help.inkjin.com/en/articles/17200672-setup-step-3-studio" target="_blank" rel="noopener" data-help-article="O3-studio" title="Help with this page" aria-label="Help with this page"><span class="ms">help</span></a></h1>
    <div class="sub" style="max-width:640px;margin-bottom:24px">Tell clients where they can find you.</div>

    {{-- Search + path chooser (always visible unless a Bookpay studio is picked) --}}
    <div class="card pad" id="panelSearch" @if($initialPath === 'found') hidden @endif>
      <h3 style="margin-bottom:4px">Find your studio</h3>
      <div class="faint" style="font-size:13px;margin-bottom:14px">If your studio is on Bookpay, pick it and its details fill in automatically.</div>
      <label class="fl" for="studio_search">Where do you tattoo? <span style="color:#C62828">*</span></label>
      <div class="in search-in">
        <span class="ms">search</span>
        <input type="text" id="studio_search" placeholder="Search by studio name or city" autocomplete="off" value="">
      </div>
      <div id="studioSearchEmpty" class="faint" style="font-size:13px;margin:10px 0 0" hidden></div>
      <div class="results" id="studioSearchResults" hidden></div>

      <div style="font-weight:700;font-size:13px;color:var(--muted);margin:16px 0 8px">Can’t find it?</div>
      <label class="po {{ $initialPath === 'not_found' ? 'sel' : '' }}" id="pathNotFound">
        <input type="radio" name="studio_path_radio" value="not_found" {{ $initialPath === 'not_found' ? 'checked' : '' }}>
        <div style="flex:1">
          <b>My studio isn’t on Bookpay</b>
          <div class="d">Enter the studio name and address yourself</div>
        </div>
      </label>
      <label class="po {{ $initialPath === 'own_space' ? 'sel' : '' }}" id="pathOwnSpace">
        <input type="radio" name="studio_path_radio" value="own_space" {{ $initialPath === 'own_space' ? 'checked' : '' }}>
        <div style="flex:1">
          <b>I work from my own space</b>
          <div class="d">Private or home studio. The address stays yours</div>
        </div>
      </label>
      <p id="studio_path_error" class="path-err hidden" role="alert"></p>
    </div>

    {{-- Found on Bookpay (reserved for when search returns a pick) --}}
    <div class="studio-panel" id="panelFound" hidden>
      <div class="card pad" style="margin-bottom:14px">
        <h3 style="margin-bottom:12px">Your studio</h3>
        <div class="picked">
          <div class="row" style="gap:14px;padding-bottom:12px;border-bottom:1px solid #F0EAF2">
            <div class="logo2" id="foundLogo">ST</div>
            <div style="flex:1">
              <div class="row" style="gap:8px">
                <b style="font-size:16px" id="foundName">Studio</b>
                <span class="pill g">On Bookpay</span>
              </div>
              <div class="faint" style="font-size:12.5px" id="foundType">Tattoo Shop</div>
            </div>
            <button type="button" id="foundChange" style="font-weight:700;color:#3E007C;background:none;border:0;cursor:pointer;font-size:13px;font:inherit">Change</button>
          </div>
          <div class="kv" style="margin-top:8px"><span>Address</span><span id="foundAddress">—</span></div>
          <div class="kv">
            <span>Map</span>
            <span>
              <a class="mlk" id="foundMapLink" href="#" target="_blank" rel="noopener">Open in Google Maps</a>
            </span>
          </div>
          <div class="faint" style="font-size:11.5px;margin-top:6px" id="foundManaged">Managed by studio</div>
        </div>
        <div class="note" style="margin-top:12px;background:#EEF4FF;border-color:#C9DBFA;color:#1D4EA0;display:block">
          <b>We’ll send a request to join.</b> They see the relationship you pick below and can confirm or change it when they accept. You can keep setting up your profile now.
        </div>
      </div>
      <div class="card pad" style="margin-bottom:14px">
        <h3 style="margin-bottom:4px">Your relationship with this studio <span style="color:#C62828">*</span></h3>
        <div class="faint" style="font-size:13px;margin-bottom:12px">How do you work with this studio?</div>
        <div class="tgrid" id="foundRelGrid">
          @foreach ($relationshipTypes as $val => $meta)
            <label class="po {{ $relSelected === $val ? 'sel' : '' }}">
              <input type="radio" name="studio_relationship_type_found" value="{{ $val }}" {{ $relSelected === $val ? 'checked' : '' }}>
              <div style="flex:1">
                <b>{{ $meta['label'] }}</b>
                <div class="d">{{ $meta['desc'] }}</div>
              </div>
            </label>
          @endforeach
        </div>
        <div class="help" style="margin-top:10px">Guest spots are set up separately, in Schedule &gt; Guest spots.</div>
      </div>
    </div>

    {{-- Not on Bookpay: manual studio details --}}
    <div class="studio-panel" id="panelNotFound" @if($initialPath !== 'not_found') hidden @endif>
      <div class="card" style="margin-bottom:14px;margin-top:14px">
        <div class="ch">
          <div>
            <h3>Studio details</h3>
            <div class="faint" style="font-size:12.5px;margin-top:2px">You manage these details until the studio joins Bookpay.</div>
          </div>
        </div>
        <div style="padding:18px 22px">
          @include('onboarding.partials.studio-address-fields', [
            'nameLabel' => 'Studio name',
            'nameId' => 'studio_name',
            'nameValue' => $userDetail->studio_name ?? '',
            'addressSearchId' => 'address_search',
            'mapsBoxId' => 'mapsBox',
            'mapsHref' => $mapsHref,
            'mapsLabel' => $mapsLabel,
            'userDetail' => $userDetail,
            'prefix' => '',
          ])
        </div>
      </div>

      <div class="card" style="margin-bottom:14px">
        <div class="ch">
          <div>
            <h3>Studio type <span style="color:#C62828">*</span></h3>
            <div class="faint" style="font-size:12.5px;margin-top:2px">What best describes your workspace?</div>
          </div>
        </div>
        <div style="padding:18px 22px">
          <div class="tgrid" id="studioTypeCards">
            @foreach ($studioTypes as $val => $meta)
              <label class="po {{ $wt === $val && $initialPath === 'not_found' ? 'sel' : '' }}">
                <input type="radio" name="workspace_type_ui" value="{{ $val }}" data-scope="not_found" {{ $wt === $val && $initialPath === 'not_found' ? 'checked' : '' }}>
                <div style="flex:1">
                  <b><span class="ms" style="font-size:18px;vertical-align:-3px">{{ $meta['icon'] }}</span> {{ $meta['label'] }}</b>
                  <div class="d">{{ $meta['desc'] }}</div>
                </div>
              </label>
            @endforeach
          </div>
          <p id="workspace_type_error" class="field-err hidden" role="alert"></p>
        </div>
      </div>

      <div class="card" style="margin-bottom:14px">
        <div class="ch">
          <div>
            <h3>Your relationship with this studio <span style="color:#C62828">*</span></h3>
            <div class="faint" style="font-size:12.5px;margin-top:2px">How do you work with this studio?</div>
          </div>
        </div>
        <div style="padding:18px 22px">
          <div class="tgrid" id="relTypeCards">
            @foreach ($relationshipTypes as $val => $meta)
              <label class="po {{ $relSelected === $val ? 'sel' : '' }}">
                <input type="radio" name="studio_relationship_type" value="{{ $val }}" {{ $relSelected === $val ? 'checked' : '' }}>
                <div style="flex:1">
                  <b>{{ $meta['label'] }}</b>
                  <div class="d">{{ $meta['desc'] }}</div>
                </div>
              </label>
            @endforeach
          </div>
          <p id="studio_relationship_type_error" class="field-err hidden" role="alert"></p>
          <div class="help" style="margin-top:10px">Guest spots are set up separately, in Schedule &gt; Guest spots.</div>
        </div>
      </div>
    </div>

    {{-- Own space --}}
    <div class="studio-panel" id="panelOwnSpace" @if($initialPath !== 'own_space') hidden @endif>
      <div class="card" style="margin-bottom:14px;margin-top:14px">
        <div class="ch">
          <div>
            <h3>Your space</h3>
            <div class="faint" style="font-size:12.5px;margin-top:2px">Only shown to clients the way you choose below.</div>
          </div>
        </div>
        <div style="padding:18px 22px">
          @include('onboarding.partials.studio-address-fields', [
            'nameLabel' => 'Space name',
            'nameId' => 'space_name',
            'nameValue' => ($initialPath === 'own_space' ? ($userDetail->studio_name ?? '') : ''),
            'nameInputName' => 'space_name_ui',
            'addressSearchId' => 'address_search_own',
            'mapsBoxId' => 'mapsBoxOwn',
            'mapsHref' => $initialPath === 'own_space' ? $mapsHref : '',
            'mapsLabel' => $initialPath === 'own_space' ? $mapsLabel : 'google.com/maps',
            'userDetail' => $initialPath === 'own_space' ? $userDetail : null,
            'prefix' => 'own_',
            'fieldsDisabled' => $initialPath !== 'own_space',
          ])
        </div>
      </div>

      <div class="card" style="margin-bottom:14px">
        <div class="ch">
          <div>
            <h3>Space type <span style="color:#C62828">*</span></h3>
            <div class="faint" style="font-size:12.5px;margin-top:2px">What best describes your workspace?</div>
          </div>
        </div>
        <div style="padding:18px 22px">
          <div class="tgrid" id="ownTypeCards">
            @foreach ($ownSpaceTypes as $val => $meta)
              <label class="po {{ $wt === $val && $initialPath === 'own_space' ? 'sel' : '' }}">
                <input type="radio" name="workspace_type_ui_own" value="{{ $val }}" data-scope="own_space" {{ $wt === $val && $initialPath === 'own_space' ? 'checked' : '' }}>
                <div style="flex:1">
                  <b><span class="ms" style="font-size:18px;vertical-align:-3px">{{ $meta['icon'] }}</span> {{ $meta['label'] }}</b>
                  <div class="d">{{ $meta['desc'] }}</div>
                </div>
              </label>
            @endforeach
          </div>
          <p id="workspace_type_own_error" class="field-err hidden" role="alert"></p>
        </div>
      </div>
    </div>

    <div class="obfoot">
      <a href="{{ route('onboarding.styles-social') }}" class="btn ghost"><span class="ms">arrow_back</span>Back</a>
      <button type="submit" class="btn" id="studioSubmit">Next step<span class="ms">arrow_forward</span></button>
    </div>
  </div>
</form>
@endsection

@push('scripts')
@include('partials.reddit-pixel', ['event' => 'Step_3'])
<script>
(function ($) {
  var currentPath = @json($initialPath);

  function clearErrors() {
    $('#studioForm').find('[id$="_error"]').addClass('hidden').text('');
    $('#studioForm').find('.is-err').removeClass('is-err');
  }

  function setErr(id, msg) {
    var $e = $('#' + id + '_error');
    if ($e.length) $e.text(msg).removeClass('hidden');
    var $f = $('#' + id);
    if ($f.length) $f.addClass('is-err');
    if ($f.closest('.in').length) $f.closest('.in').addClass('is-err');
  }

  function syncPo($scope) {
    $scope.find('.po').each(function () {
      var on = $(this).find('input[type=radio]').is(':checked');
      $(this).toggleClass('sel', on);
    });
  }

  function setPath(path) {
    currentPath = path || '';
    $('#studio_path').val(currentPath);
    $('#panelNotFound').prop('hidden', currentPath !== 'not_found');
    $('#panelOwnSpace').prop('hidden', currentPath !== 'own_space');
    $('#panelFound').prop('hidden', currentPath !== 'found');
    $('#panelSearch').prop('hidden', currentPath === 'found');

    $('input[name=studio_path_radio]').each(function () {
      this.checked = this.value === currentPath;
    });
    syncPo($('#panelSearch'));

    // Enable active path fields; disable the other so FormData stays clean
    $('#panelNotFound').find('input,select,textarea').prop('disabled', currentPath !== 'not_found');
    $('#panelOwnSpace').find('input,select,textarea').prop('disabled', currentPath !== 'own_space');

    if (currentPath === 'not_found') {
      var $n = $('#studio_name');
      if ($n.length && !$n.attr('name')) $n.attr('name', 'studio_name');
      $('#space_name').removeAttr('name');
    } else if (currentPath === 'own_space') {
      var $s = $('#space_name');
      $s.attr('name', 'studio_name');
      $('#studio_name').removeAttr('name');
    }

    $('#studio_path_error').addClass('hidden').text('');
  }

  function updateMapsBox(boxId, link, label) {
    var $box = $('#' + boxId);
    if (!$box.length) return;
    var $a = $box.find('a.mlk');
    if (!link) {
      $a.attr('href', '#').addClass('is-empty').text('google.com/maps');
      return;
    }
    $a.attr('href', link).removeClass('is-empty').text(label || link);
  }

  function buildMapsLinkFromFields(prefix) {
    var parts = [
      $('#' + prefix + 'street_number').val(),
      $('#' + prefix + 'street_name').val(),
      $('#' + prefix + 'city').val(),
      $('#' + prefix + 'state').val(),
      $('#' + prefix + 'postal_code').val(),
      $('#' + prefix + 'country').val(),
    ].map(function (v) { return $.trim(v || ''); }).filter(Boolean);
    if (!parts.length) return '';
    return 'https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(parts.join(', '));
  }

  function syncAddressHidden(prefix, mapsBoxId) {
    var link = buildMapsLinkFromFields(prefix);
    var addr = [
      $.trim($('#' + prefix + 'street_number').val() || ''),
      $.trim($('#' + prefix + 'street_name').val() || ''),
      $.trim($('#' + prefix + 'city').val() || ''),
      $.trim($('#' + prefix + 'state').val() || ''),
      $.trim($('#' + prefix + 'postal_code').val() || ''),
      $.trim($('#' + prefix + 'country').val() || ''),
    ].filter(Boolean).join(', ');
    if (currentPath === 'not_found' && prefix === '') {
      $('#studio_address').val(addr);
      $('#google_maps_link').val(link);
      updateMapsBox(mapsBoxId, link, link ? ('google.com/maps · ' + addr) : '');
    }
    if (currentPath === 'own_space' && prefix === 'own_') {
      $('#studio_address').val(addr);
      $('#google_maps_link').val(link);
      updateMapsBox(mapsBoxId, link, link ? ('google.com/maps · ' + addr) : '');
    }
  }

  function bindAddressSync(prefix, mapsBoxId) {
    var ids = ['street_number', 'street_name', 'city', 'state', 'postal_code', 'country'];
    ids.forEach(function (id) {
      $('#' + prefix + id).on('input', function () {
        syncAddressHidden(prefix, mapsBoxId);
        if (typeof window.clearOnboardingFieldError === 'function') window.clearOnboardingFieldError(prefix ? id : id);
      });
    });
  }

  function initAutocomplete(inputId, prefix, mapsBoxId) {
    @if(config('services.google.place_api_key'))
    var input = document.getElementById(inputId);
    if (!input || typeof google === 'undefined' || !google.maps || !google.maps.places) return;
    var ac = new google.maps.places.Autocomplete(input, {
      types: ['address'],
      fields: ['address_components', 'formatted_address', 'place_id', 'geometry'],
    });
    ac.addListener('place_changed', function () {
      var place = ac.getPlace();
      if (!place.address_components) return;
      if (place.geometry && place.geometry.location) {
        $('#latitude').val(place.geometry.location.lat());
        $('#longitude').val(place.geometry.location.lng());
      }
      var sn = '', st = '', city = '', state = '', zip = '', country = '';
      place.address_components.forEach(function (c) {
        var t = c.types;
        if (t.indexOf('street_number') !== -1) sn = c.long_name;
        if (t.indexOf('route') !== -1) st = c.long_name;
        if (t.indexOf('locality') !== -1) city = c.long_name;
        else if (t.indexOf('postal_town') !== -1 && !city) city = c.long_name;
        if (t.indexOf('administrative_area_level_1') !== -1) state = c.short_name || c.long_name;
        if (t.indexOf('postal_code') !== -1) zip = c.long_name;
        if (t.indexOf('country') !== -1) country = c.long_name;
      });
      $('#' + prefix + 'street_number').val(sn);
      $('#' + prefix + 'street_name').val(st);
      $('#' + prefix + 'city').val(city);
      $('#' + prefix + 'state').val(state);
      $('#' + prefix + 'postal_code').val(zip);
      $('#' + prefix + 'country').val(country);
      var link = place.place_id
        ? ('https://www.google.com/maps/place/?q=place_id:' + place.place_id)
        : ('https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(place.formatted_address || ''));
      if (currentPath === 'not_found' && prefix === '') {
        $('#studio_address').val(place.formatted_address || '');
        $('#google_maps_link').val(link);
      }
      if (currentPath === 'own_space' && prefix === 'own_') {
        $('#studio_address').val(place.formatted_address || '');
        $('#google_maps_link').val(link);
      }
      updateMapsBox(mapsBoxId, link, 'google.com/maps · ' + (place.formatted_address || ''));
    });
    @endif
  }

  function activePrefix() {
    return currentPath === 'own_space' ? 'own_' : '';
  }

  function activeNameId() {
    return currentPath === 'own_space' ? 'space_name' : 'studio_name';
  }

  function validateClient() {
    clearErrors();
    if (!currentPath || (currentPath !== 'not_found' && currentPath !== 'own_space' && currentPath !== 'found')) {
      $('#studio_path_error').text('Choose how you work — pick a studio on Bookpay, or one of the options below.').removeClass('hidden');
      return false;
    }
    if (currentPath === 'found') {
      // Bookpay join not wired yet — force manual path for save.
      $('#studio_path_error').text('Studio search on Bookpay is coming soon. Choose an option under “Can’t find it?”.').removeClass('hidden');
      setPath('');
      return false;
    }

    var prefix = activePrefix();
    var nameId = activeNameId();
    var ok = true;
    var required = [
      [nameId, 'Name is required.'],
      [prefix + 'street_number', 'Street number is required.'],
      [prefix + 'street_name', 'Street name is required.'],
      [prefix + 'city', 'City is required.'],
      [prefix + 'state', 'State / province is required.'],
      [prefix + 'postal_code', 'Postal code is required.'],
      [prefix + 'country', 'Country is required.'],
    ];
    required.forEach(function (pair) {
      if (!$.trim($('#' + pair[0]).val() || '')) {
        setErr(pair[0], pair[1]);
        ok = false;
      }
    });

    var wt = $('#workspace_type').val();
    if (!wt) {
      if (currentPath === 'own_space') setErr('workspace_type_own', 'Please select a space type.');
      else setErr('workspace_type', 'Please select a studio type.');
      ok = false;
    }

    if (currentPath === 'not_found') {
      var rel = $('input[name="studio_relationship_type"]:checked').val();
      if (!rel) {
        setErr('studio_relationship_type', 'Please select your relationship with this studio.');
        ok = false;
      }
    }

    syncAddressHidden(prefix, currentPath === 'own_space' ? 'mapsBoxOwn' : 'mapsBox');
    if (!$.trim($('#studio_address').val() || '')) {
      setErr((currentPath === 'own_space' ? 'address_search_own' : 'address_search'), 'Please enter your address.');
      ok = false;
    }

    if (!ok && typeof window.scrollToFirstOnboardingError === 'function') {
      window.scrollToFirstOnboardingError(document.getElementById('studioForm'));
    }
    return ok;
  }

  function prepareFormData(form) {
    // Ensure disabled path fields don't matter; copy own_* → canonical names
    var fd = new FormData(form);
    if (currentPath === 'own_space') {
      ['street_number', 'street_name', 'city', 'state', 'postal_code', 'country'].forEach(function (k) {
        fd.set(k, $('#' + 'own_' + k).val() || '');
      });
      fd.set('studio_name', $('#space_name').val() || '');
    }
    fd.set('workspace_type', $('#workspace_type').val() || '');
    fd.set('studio_address', $('#studio_address').val() || '');
    fd.set('google_maps_link', $('#google_maps_link').val() || '');
    fd.set('studio_path', currentPath || '');
    return fd;
  }

  $(function () {
    setPath(currentPath);

    $('input[name=studio_path_radio]').on('change', function () {
      setPath(this.value);
    });
    $('#panelSearch').on('click', '.po', function () {
      var $r = $(this).find('input[type=radio]');
      $r.prop('checked', true).trigger('change');
    });

    function bindTypeRadios(sel) {
      $(sel).on('change', 'input[type=radio]', function () {
        $('#workspace_type').val(this.value);
        syncPo($(sel));
        if (typeof window.clearOnboardingFieldError === 'function') {
          window.clearOnboardingFieldError('workspace_type');
        }
        $('#workspace_type_error, #workspace_type_own_error').addClass('hidden').text('');
      });
    }
    bindTypeRadios('#studioTypeCards');
    bindTypeRadios('#ownTypeCards');
    $('#relTypeCards').on('change', 'input[type=radio]', function () {
      syncPo($('#relTypeCards'));
      $('#studio_relationship_type_error').addClass('hidden').text('');
      if (typeof window.clearOnboardingFieldError === 'function') {
        window.clearOnboardingFieldError('studio_relationship_type');
      }
    });

    bindAddressSync('', 'mapsBox');
    bindAddressSync('own_', 'mapsBoxOwn');
    initAutocomplete('address_search', '', 'mapsBox');
    initAutocomplete('address_search_own', 'own_', 'mapsBoxOwn');

    $(document).on('click', '.mlcopy', function (e) {
      e.preventDefault();
      var href = $(this).closest('.mlbox').find('a.mlk').attr('href');
      if (!href || href === '#') return;
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(href);
      }
    });

    var searchTimer = null;
    $('#studio_search').on('input', function () {
      var q = $.trim(this.value);
      clearTimeout(searchTimer);
      searchTimer = setTimeout(function () {
        if (q.length < 2) {
          $('#studioSearchEmpty').prop('hidden', true);
          $('#studioSearchResults').prop('hidden', true).empty();
          return;
        }
        // Bookpay studio search API not wired yet — show empty state like O3c.
        $('#studioSearchResults').prop('hidden', true).empty();
        $('#studioSearchEmpty').prop('hidden', false)
          .text('No studios on Bookpay match “' + q + '”.');
      }, 250);
    });

    $('#foundChange').on('click', function () {
      setPath('');
    });

    $('#studioForm').on('submit', function (e) {
      e.preventDefault();
      if (!validateClient()) return;
      var $btn = $('#studioSubmit');
      var original = $btn.html();
      $btn.prop('disabled', true).text('Saving...');
      var fd = prepareFormData(this);
      $.ajax({
        url: @json(route('onboarding.studio.save')),
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
          } else if (data.errors) {
            $.each(data.errors, function (k, messages) {
              setErr(k, messages[0]);
            });
            if (typeof window.scrollToFirstOnboardingError === 'function') {
              window.scrollToFirstOnboardingError(document.getElementById('studioForm'));
            }
          } else {
            alert(data.message || 'Error');
          }
        })
        .fail(function (xhr) {
          if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
            $.each(xhr.responseJSON.errors, function (k, messages) {
              setErr(k, messages[0]);
            });
            if (typeof window.scrollToFirstOnboardingError === 'function') {
              window.scrollToFirstOnboardingError(document.getElementById('studioForm'));
            }
          } else {
            alert((xhr.responseJSON && xhr.responseJSON.message) || 'Error');
          }
        })
        .always(function () {
          $btn.prop('disabled', false).html(original);
        });
    });
  });
})(window.jQuery);
</script>
@endpush
