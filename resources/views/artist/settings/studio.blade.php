@extends('layouts.artist_dashboard_layout')

@section('title', 'Studio Information')

@section('styles')
@if(config('services.google.place_api_key'))
<script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google.place_api_key') }}&libraries=places"></script>
@endif
<style>
  .radio-card { border: 1.5px solid #cac4d3; border-radius: 12px; padding: 16px; cursor: pointer; transition: all 0.2s; position: relative; }
  .radio-card:hover { border-color: #664db1; }
  .radio-card.selected { border-color: #310f7a; background: #fdf7ff; }
  .radio-card .radio-dot { width: 18px; height: 18px; border-radius: 50%; border: 2px solid #cac4d3; transition: all 0.2s; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
  .radio-card.selected .radio-dot { border-color: #310f7a; background: #310f7a; }
  .radio-card.selected .radio-dot::after { content: ''; width: 6px; height: 6px; background: white; border-radius: 50%; }
  /* Address autocomplete dropdown */
  .address-dropdown { display: none; position: absolute; top: 100%; left: 0; right: 0; z-index: 50; margin-top: 4px; }
  .address-dropdown.show { display: block; }
  .address-item { display: flex; align-items: center; gap: 10px; padding: 12px 16px; cursor: pointer; transition: background 0.15s; }
  .address-item:hover { background: #f8f1fb; }
  .address-item:first-child { border-radius: 12px 12px 0 0; }
  .address-item:last-child { border-radius: 0 0 12px 12px; }

  @media (max-width: 1023px) {
    .main-content { overflow-x: hidden; padding: 16px; padding-top: 70px; }
    body { overflow-x: hidden; }
  }
</style>
@endsection

@section('content')
  <main class="main-content flex-1 min-h-screen flex flex-col">
    <div class="flex-1 p-6 md:p-10 lg:p-12 max-w-4xl">

      <!-- Settings Tabs -->
      <div class="flex items-center gap-1 mb-6 border-b border-outline-variant/20 pb-0 overflow-x-auto">
        <a href="{{ route('profile.edit') }}" class="px-4 py-3 text-sm font-semibold whitespace-nowrap border-b-2 border-transparent text-on-surface-variant hover:text-on-surface hover:border-outline-variant transition-all">Profile</a>
        <a href="{{ route('settings.styles') }}" class="px-4 py-3 text-sm font-semibold whitespace-nowrap border-b-2 border-transparent text-on-surface-variant hover:text-on-surface hover:border-outline-variant transition-all">Styles &amp; Social</a>
        <a href="javascript:void(0)" class="px-4 py-3 text-sm font-semibold whitespace-nowrap border-b-2 border-primary text-primary hover:text-on-surface hover:border-outline-variant transition-all">Studio</a>
        <a href="{{ route('settings.preferences') }}" class="px-4 py-3 text-sm font-semibold whitespace-nowrap border-b-2 border-transparent text-on-surface-variant hover:text-on-surface hover:border-outline-variant transition-all">Preferences</a>
        <a href="{{ route('settings.calendar') }}" class="px-4 py-3 text-sm font-semibold whitespace-nowrap border-b-2 border-transparent text-on-surface-variant hover:text-on-surface hover:border-outline-variant transition-all">Calendar</a>
        <a href="{{ route('settings.payment') }}" class="px-4 py-3 text-sm font-semibold whitespace-nowrap border-b-2 border-transparent text-on-surface-variant hover:text-on-surface hover:border-outline-variant transition-all">Payments</a>
        {{-- <a href="{{ route('settings.notifications') }}" class="px-4 py-3 text-sm font-semibold whitespace-nowrap border-b-2 border-transparent text-on-surface-variant hover:text-on-surface hover:border-outline-variant transition-all">Notifications</a> --}}
      </div>


      <!-- Page Header -->
      <div class="mb-8">
        <h2 class="text-3xl font-extrabold text-on-surface tracking-tight">Studio Settings</h2>
        <p class="text-on-surface-variant mt-1">Update your studio name, location, workspace type, and map link.</p>
      </div>
      <div id="studioSuccessAlert" class="hidden mb-6 rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-800 px-4 py-3 text-sm"></div>
      <div id="studioErrorAlert" class="hidden mb-6 rounded-xl border border-error/30 bg-error/10 text-error px-4 py-3 text-sm"></div>

      <form id="studioForm" method="POST" action="{{ route('settings.studio.update') }}">
        @csrf
        <input type="hidden" name="workspace_type" id="workspace_type" value="{{ old('workspace_type', $userDetail->workspace_type ?? '') }}">
        <div class="bg-surface-container-low rounded-2xl p-6 space-y-6">
          <div>
            <label for="studio_name" class="block text-sm font-semibold text-on-surface mb-2">Studio Name <span class="text-red-600">*</span></label>
            <input type="text" id="studio_name" name="studio_name" value="{{ old('studio_name', $userDetail->studio_name ?? '') }}" class="w-full text-sm border border-outline-variant/30 rounded-xl px-4 py-3 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
            <p id="studio_name_error" class="text-error text-xs mt-1 hidden"></p>
          </div>

          <div>
            <label class="block text-sm font-semibold text-on-surface mb-2">Find Your Address <span class="text-red-600">*</span></label>
            <div class="relative" id="addressSearchWrapper">
              <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-lg">location_on</span>
              <input type="text" id="address_search" autocomplete="off" placeholder="Start typing your studio address..." value="{{ old('studio_address', $userDetail->studio_address ?? '') }}" class="w-full text-sm border border-outline-variant/30 rounded-xl pl-10 pr-4 py-3 bg-white text-on-surface placeholder:text-outline focus:outline-none focus:ring-2 focus:ring-primary/30">
            </div>
            <p class="text-on-surface-variant text-xs mt-1.5">Start typing and select from Google suggestions to auto-fill address fields.</p>
          </div>
          <input type="hidden" name="studio_address" id="studio_address" value="{{ old('studio_address', $userDetail->studio_address ?? '') }}">
          <p id="studio_address_error" class="text-error text-xs -mt-4 hidden"></p>

          <div class="grid grid-cols-1 sm:grid-cols-12 gap-4">
            <div class="sm:col-span-4">
              <label for="street_number" class="block text-sm font-semibold text-on-surface mb-2">Street Number <span class="text-red-600">*</span></label>
              <input type="text" id="street_number" name="street_number" value="{{ old('street_number', $userDetail->street_number ?? '') }}" class="w-full text-sm border border-outline-variant/30 rounded-xl px-4 py-3 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
              <p id="street_number_error" class="text-error text-xs mt-1 hidden"></p>
            </div>
            <div class="sm:col-span-8">
              <label for="street_name" class="block text-sm font-semibold text-on-surface mb-2">Street Name <span class="text-red-600">*</span></label>
              <input type="text" id="street_name" name="street_name" value="{{ old('street_name', $userDetail->street_name ?? '') }}" class="w-full text-sm border border-outline-variant/30 rounded-xl px-4 py-3 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
              <p id="street_name_error" class="text-error text-xs mt-1 hidden"></p>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label for="city" class="block text-sm font-semibold text-on-surface mb-2">City <span class="text-red-600">*</span></label>
              <input type="text" id="city" name="city" value="{{ old('city', $userDetail->city ?? '') }}" class="w-full text-sm border border-outline-variant/30 rounded-xl px-4 py-3 bg-white focus:ring-2 focus:ring-primary/30">
              <p id="city_error" class="text-error text-xs mt-1 hidden"></p>
            </div>
            <div>
              <label for="state" class="block text-sm font-semibold text-on-surface mb-2">State / Province <span class="text-red-600">*</span></label>
              <input type="text" id="state" name="state" value="{{ old('state', $userDetail->state ?? '') }}" class="w-full text-sm border border-outline-variant/30 rounded-xl px-4 py-3 bg-white focus:ring-2 focus:ring-primary/30">
              <p id="state_error" class="text-error text-xs mt-1 hidden"></p>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label for="postal_code" class="block text-sm font-semibold text-on-surface mb-2">Postal / Zip Code <span class="text-red-600">*</span></label>
              <input type="text" id="postal_code" name="postal_code" value="{{ old('postal_code', $userDetail->postal_code ?? '') }}" class="w-full text-sm border border-outline-variant/30 rounded-xl px-4 py-3 bg-white focus:ring-2 focus:ring-primary/30">
              <p id="postal_code_error" class="text-error text-xs mt-1 hidden"></p>
            </div>
            <div>
              <label for="country" class="block text-sm font-semibold text-on-surface mb-2">Country <span class="text-red-600">*</span></label>
              <input type="text" id="country" name="country" value="{{ old('country', $userDetail->country ?? '') }}" class="w-full text-sm border border-outline-variant/30 rounded-xl px-4 py-3 bg-white focus:ring-2 focus:ring-primary/30">
              <p id="country_error" class="text-error text-xs mt-1 hidden"></p>
            </div>
          </div>

          <div>
            <label for="google_maps_link" class="block text-sm font-semibold text-on-surface mb-2">Google Maps Link</label>
            <div class="relative">
              <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-lg">location_on</span>
              <input type="url" id="google_maps_link" name="google_maps_link" value="{{ old('google_maps_link', $userDetail->google_maps_link ?? '') }}" placeholder="Paste your Google Maps link" class="w-full text-sm border border-outline-variant/30 rounded-xl pl-10 pr-4 py-3 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
            </div>
            <p class="text-on-surface-variant text-xs mt-1.5">Paste the Google Maps link to your studio so clients can find you easily.</p>
            <p id="google_maps_link_error" class="text-error text-xs mt-1 hidden"></p>
          </div>
        </div>

        <div class="mt-8 mb-2">
          <h3 class="text-lg font-bold text-on-surface mb-1">Studio Type</h3>
          <p class="text-on-surface-variant text-sm mb-5">What best describes your workspace? <span class="text-red-600">*</span></p>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4" id="studioTypeCards">
            @foreach ([
              'private' => ['home','Private Studio','A personal workspace — clients visit by appointment only'],
              'shop' => ['storefront','Tattoo Shop','A shared shop with walk-ins and appointments'],
              'home' => ['cottage','Home Studio','Working from home — address shared only after booking'],
              'mobile' => ['flight','Mobile / Travel','You travel to clients or work at guest spots'],
            ] as $val => $meta)
              <div class="radio-card {{ (old('workspace_type', $userDetail->workspace_type ?? '')) === $val ? 'selected' : '' }}" data-workspace="{{ $val }}" onclick="selectStudioType(this)">
                <div class="flex items-start gap-3">
                  <div class="radio-dot mt-0.5"></div>
                  <div class="flex-1">
                    <div class="flex items-center gap-2 mb-1">
                      <span class="material-symbols-outlined text-primary text-xl">{{ $meta[0] }}</span>
                      <span class="font-semibold text-sm text-on-surface">{{ $meta[1] }}</span>
                    </div>
                    <p class="text-on-surface-variant text-xs leading-relaxed">{{ $meta[2] }}</p>
                  </div>
                </div>
              </div>
            @endforeach
          </div>
          <p id="workspace_type_error" class="text-error text-xs mt-2 hidden"></p>
        </div>
      </form>
    </div>

    <!-- Footer: Save Changes -->
    <div class="sticky bottom-0 bg-surface border-t border-outline-variant/10 px-6 md:px-10 lg:px-12 py-5 flex items-center justify-end">
      <button type="submit" id="saveStudioBtn" form="studioForm" class="inline-flex items-center gap-2 bg-gradient-to-br from-primary to-primary-container text-white font-bold py-3 px-8 rounded-xl shadow-lg shadow-primary/20 hover:opacity-90 transition-all active:scale-[0.98]">
        <span class="material-symbols-outlined text-lg">save</span> Save Changes
      </button>
    </div>
  </main>
@endsection

@section('scripts')
<script>
function selectStudioType(card) {
  $('#studioTypeCards .radio-card').removeClass('selected');
  $(card).addClass('selected');
  $('#workspace_type').val($(card).data('workspace') || '');
  $('#workspace_type_error').text('').addClass('hidden');
  $('#studioTypeCards').removeClass('ring-2 ring-error rounded-2xl p-2');
}

$(function () {
  function clearStudioErrors() {
    $('#studioForm [id$="_error"]').text('').addClass('hidden');
    $('#studioForm input').removeClass('border-error');
    $('#studioTypeCards').removeClass('ring-2 ring-error rounded-2xl p-2');
  }
  function showStudioErrors(errors) {
    $.each(errors, function (k, messages) {
      var $err = $('#' + k + '_error');
      if ($err.length) {
        $err.text(messages[0]).removeClass('hidden');
      }
      if (k === 'workspace_type') {
        $('#studioTypeCards').addClass('ring-2 ring-error rounded-2xl p-2');
        return;
      }
      var fieldId = k === 'studio_address' ? 'address_search' : k;
      $('#' + fieldId).addClass('border-error');
    });
  }

@if(config('services.google.place_api_key'))
  (function () {
    var input = document.getElementById('address_search');
    if (!input || typeof google === 'undefined' || !google.maps || !google.maps.places) return;
    var ac = new google.maps.places.Autocomplete(input, { types: ['address'], fields: ['address_components', 'formatted_address', 'place_id'] });
    ac.addListener('place_changed', function () {
      var place = ac.getPlace();
      if (!place.address_components) return;
      var sn = '', st = '', city = '', state = '', zip = '', country = '';
      for (var i = 0; i < place.address_components.length; i++) {
        var c = place.address_components[i];
        var t = c.types;
        if (t.indexOf('street_number') !== -1) sn = c.long_name;
        if (t.indexOf('route') !== -1) st = c.long_name;
        if (t.indexOf('locality') !== -1) city = c.long_name;
        else if (t.indexOf('postal_town') !== -1 && !city) city = c.long_name;
        if (t.indexOf('administrative_area_level_1') !== -1) state = c.short_name || c.long_name;
        if (t.indexOf('postal_code') !== -1) zip = c.long_name;
        if (t.indexOf('country') !== -1) country = c.long_name;
      }
      $('#street_number').val(sn);
      $('#street_name').val(st);
      $('#city').val(city);
      $('#state').val(state);
      $('#postal_code').val(zip);
      $('#country').val(country);
      $('#studio_address').val(place.formatted_address || '');
      if (place.place_id) {
        $('#google_maps_link').val('https://www.google.com/maps/place/?q=place_id:' + place.place_id);
      }
    });
  })();
@endif
  $('#address_search').on('input', function () {
    $('#studio_address').val($(this).val());
    $('#studio_address_error').text('').addClass('hidden');
    $(this).removeClass('border-error');
  });
  $.each(['studio_name', 'street_number', 'street_name', 'city', 'state', 'postal_code', 'country', 'google_maps_link'], function (_, id) {
    $('#' + id).on('input', function () {
      $(this).removeClass('border-error');
      $('#' + id + '_error').text('').addClass('hidden');
    });
  });

  $('#studioForm').on('submit', function (e) {
    e.preventDefault();
    clearStudioErrors();
    $('#studioSuccessAlert').addClass('hidden').text('');
    $('#studioErrorAlert').addClass('hidden').text('');
    var $btn = $('#saveStudioBtn');
    $btn.prop('disabled', true).html('<span class="material-symbols-outlined text-lg">hourglass_top</span> Saving...');
    var fd = new FormData(this);
    $.ajax({
      url: @json(route('settings.studio.update')),
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
        if (data.success) {
          $('#studioSuccessAlert').text(data.message || 'Studio information updated successfully!').removeClass('hidden');
          showSaveToast();
          return;
        }
        $('#studioErrorAlert').text(data.message || 'Could not save studio settings.').removeClass('hidden');
      })
      .fail(function (xhr) {
        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
          showStudioErrors(xhr.responseJSON.errors);
        } else {
          $('#studioErrorAlert').text((xhr.responseJSON && xhr.responseJSON.message) || 'An error occurred while saving.').removeClass('hidden');
        }
      })
      .always(function () {
        $btn.prop('disabled', false).html('<span class="material-symbols-outlined text-lg">save</span> Save Changes');
      });
  });
});
</script>
@endsection
