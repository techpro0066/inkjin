@extends('layouts.onboarding_bookpay')

@section('title', 'Studio')

@push('head')
@if(config('services.google.place_api_key'))
<script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google.place_api_key') }}&libraries=places"></script>
@endif
@endpush

@section('content')
<form id="studioForm" class="contents">
  @csrf
  <input type="hidden" name="workspace_type" id="workspace_type" value="{{ $userDetail->workspace_type ?? '' }}">
  <div class=" flex-1 p-8 md:p-12 max-w-4xl">
    <div class="mb-10">
      <h2 class="text-3xl font-extrabold text-on-surface tracking-tight">Set up your studio</h2>
      <p class="text-on-surface-variant mt-2 max-w-lg">Tell clients where they can find you.</p>
    </div>

    <div class="bg-surface-container-low rounded-2xl p-6 space-y-6 mb-10">
      <h3 class="text-lg font-bold text-on-surface">Studio Details</h3>
      <div>
        <label for="studio_name" class="block text-sm font-semibold text-on-surface mb-2">Studio Name <span class="text-red-600">*</span></label>
        <input type="text" id="studio_name" name="studio_name" value="{{ $userDetail->studio_name ?? '' }}" placeholder="e.g., Ink & Soul Tattoo Studio"
          class="w-full text-sm border border-outline-variant/30 rounded-xl px-4 py-3 bg-white text-on-surface placeholder:text-outline focus:outline-none focus:ring-2 focus:ring-primary/30">
        <p id="studio_name_error" class="text-error text-xs mt-1 hidden"></p>
      </div>
      <div>
        <label class="block text-sm font-semibold text-on-surface mb-2">Find Your Address</label>
        <div class="relative" id="addressSearchWrapper">
          <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-lg">location_on</span>
          <input type="text" id="address_search" autocomplete="off" placeholder="Start typing your studio address..."
            class="w-full text-sm border border-outline-variant/30 rounded-xl pl-10 pr-4 py-3 bg-white text-on-surface placeholder:text-outline focus:outline-none focus:ring-2 focus:ring-primary/30" value="{{ $userDetail->studio_address ?? '' }}" >
        </div>
      </div>
      <input type="hidden" name="studio_address" id="studio_address" value="{{ $userDetail->studio_address ?? '' }}">
      <div class="grid grid-cols-1 sm:grid-cols-12 gap-4">
        <div class="sm:col-span-4">
          <label for="street_number" class="block text-sm font-semibold text-on-surface mb-2">Street number <span class="text-red-600">*</span></label>
          <input type="text" name="street_number" id="street_number" value="{{ $userDetail->street_number ?? '' }}" placeholder="e.g. 42"
            class="w-full text-sm border border-outline-variant/30 rounded-xl px-4 py-3 bg-white text-on-surface placeholder:text-outline focus:outline-none focus:ring-2 focus:ring-primary/30">
          <p id="street_number_error" class="text-error text-xs mt-1 hidden"></p>
        </div>
        <div class="sm:col-span-8">
          <label for="street_name" class="block text-sm font-semibold text-on-surface mb-2">Street name <span class="text-red-600">*</span></label>
          <input type="text" name="street_name" id="street_name" value="{{ $userDetail->street_name ?? '' }}" placeholder="e.g. Main Street"
            class="w-full text-sm border border-outline-variant/30 rounded-xl px-4 py-3 bg-white text-on-surface placeholder:text-outline focus:outline-none focus:ring-2 focus:ring-primary/30">
          <p id="street_name_error" class="text-error text-xs mt-1 hidden"></p>
        </div>
      </div>
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label for="city" class="block text-sm font-semibold text-on-surface mb-2">City <span class="text-red-600">*</span></label>
          <input type="text" id="city" name="city" value="{{ $userDetail->city ?? '' }}" class="w-full text-sm border border-outline-variant/30 rounded-xl px-4 py-3 bg-white focus:ring-2 focus:ring-primary/30">
          <p id="city_error" class="text-error text-xs mt-1 hidden"></p>
        </div>
        <div>
          <label for="state" class="block text-sm font-semibold text-on-surface mb-2">State / Province <span class="text-red-600">*</span></label>
          <input type="text" id="state" name="state" value="{{ $userDetail->state ?? '' }}" class="w-full text-sm border border-outline-variant/30 rounded-xl px-4 py-3 bg-white focus:ring-2 focus:ring-primary/30">
          <p id="state_error" class="text-error text-xs mt-1 hidden"></p>
        </div>
      </div>
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label for="postal_code" class="block text-sm font-semibold text-on-surface mb-2">Postal / Zip Code <span class="text-red-600">*</span></label>
          <input type="text" id="postal_code" name="postal_code" value="{{ $userDetail->postal_code ?? '' }}" class="w-full text-sm border border-outline-variant/30 rounded-xl px-4 py-3 bg-white focus:ring-2 focus:ring-primary/30">
          <p id="postal_code_error" class="text-error text-xs mt-1 hidden"></p>
        </div>
        <div>
          <label for="country" class="block text-sm font-semibold text-on-surface mb-2">Country <span class="text-red-600">*</span></label>
          <input type="text" id="country" name="country" value="{{ $userDetail->country ?? '' }}" class="w-full text-sm border border-outline-variant/30 rounded-xl px-4 py-3 bg-white focus:ring-2 focus:ring-primary/30">
          <p id="country_error" class="text-error text-xs mt-1 hidden"></p>
        </div>
      </div>
      <div>
        <label for="google_maps_link" class="block text-sm font-semibold text-on-surface mb-2">Google Maps Link</label>
        <div class="relative">
          <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-lg">location_on</span>
          <input type="url" id="google_maps_link" name="google_maps_link" value="{{ $userDetail->google_maps_link ?? '' }}" placeholder="Paste your Google Maps link"
            class="w-full text-sm border border-outline-variant/30 rounded-xl pl-10 pr-4 py-3 bg-white focus:ring-2 focus:ring-primary/30">
        </div>
        <p class="text-on-surface-variant text-xs mt-1.5">Paste the Google Maps link to your studio so clients can find you easily.</p>
        <p id="google_maps_link_error" class="text-error text-xs mt-1 hidden"></p>
      </div>
    </div>

    <div class="mb-10">
      <h3 class="text-lg font-bold text-on-surface mb-1">Studio Type</h3>
      <p class="text-on-surface-variant text-sm mb-5">What best describes your workspace? <span class="text-red-600">*</span></p>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4" id="studioTypeCards">
        @foreach ([
          'private' => ['home','Private Studio','A personal workspace — clients visit by appointment only'],
          'shop' => ['storefront','Tattoo Shop','A shared shop with walk-ins and appointments'],
          'home' => ['cottage','Home Studio','Working from home — address shared only after booking'],
          'mobile' => ['flight','Mobile / Travel','You travel to clients or work at guest spots'],
        ] as $val => $meta)
          <div class="radio-card {{ ($userDetail->workspace_type ?? '') === $val ? 'selected' : '' }}" data-workspace="{{ $val }}" onclick="selectStudioType(this)">
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
      <p id="workspace_type_error" class="text-error text-xs mt-1 hidden"></p>
    </div>

    <div class="mb-10 opacity-80 pointer-events-none select-none">
      <h3 class="text-lg font-bold text-on-surface mb-1">Studio Photos <span class="text-on-surface-variant font-normal text-sm">(optional)</span></h3>
      <p class="text-on-surface-variant text-sm mb-5">Help clients know what to expect — show your setup, waiting area, or equipment.</p>
      <div class="border-2 border-dashed border-outline-variant/40 rounded-2xl p-8 text-center mb-6">
        <span class="material-symbols-outlined text-4xl text-primary/40 mb-3 block">add_photo_alternate</span>
        <p class="font-semibold text-sm text-on-surface">Add photos of your workspace</p>
        <p class="text-on-surface-variant text-xs mt-1">Coming soon</p>
      </div>
    </div>
  </div>

  <div class="sticky bottom-0 bg-surface border-t border-outline-variant/10 px-8 md:px-12 py-5 flex items-center justify-between mt-auto">
    <a href="{{ route('onboarding.profile') }}" class="inline-flex items-center gap-1 text-on-surface font-semibold hover:text-primary transition-colors">
      <span class="material-symbols-outlined text-lg">arrow_back</span> Back
    </a>
    <button type="submit" id="studioSubmit" class="inline-flex items-center gap-2 bg-gradient-to-br from-primary to-primary-container text-white font-bold py-3 px-8 rounded-xl shadow-lg shadow-primary/20 hover:opacity-90 transition-all active:scale-[0.98]">
      Next Step <span class="material-symbols-outlined text-lg">arrow_forward</span>
    </button>
  </div>
</form>
@endsection

@push('scripts')
<script>
function selectStudioType(card) {
  $('#studioTypeCards .radio-card').removeClass('selected');
  $(card).addClass('selected');
  $('#workspace_type').val($(card).data('workspace') || '');
  if (typeof window.clearOnboardingFieldError === 'function') window.clearOnboardingFieldError('workspace_type');
}
$(function () {
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
      if (typeof window.clearOnboardingFieldError === 'function') {
        $.each(['studio_name', 'street_number', 'street_name', 'city', 'state', 'postal_code', 'country', 'google_maps_link', 'studio_address'], function (_, k) {
          window.clearOnboardingFieldError(k);
        });
      }
    });
  })();
@endif
  $.each(['studio_name', 'city', 'state', 'postal_code', 'country', 'google_maps_link', 'street_number', 'street_name', 'address_search'], function (_, fieldName) {
    $('#' + fieldName).on('input', function () {
      if (typeof window.clearOnboardingFieldError === 'function') window.clearOnboardingFieldError(fieldName);
    });
  });

  function showStudioErrors(errors) {
    $.each(errors, function (k, messages) {
      var $el = $('#' + k + '_error');
      if ($el.length) $el.text(messages[0]).removeClass('hidden');
    });
    if (typeof window.scrollToFirstOnboardingError === 'function') {
      window.scrollToFirstOnboardingError(document.getElementById('studioForm'));
    }
  }

  $('#studioForm').on('submit', function (e) {
    e.preventDefault();
    var $btn = $('#studioSubmit');
    var originalBtnHtml = $btn.html();
    $btn.prop('disabled', true);
    $btn.text('Saving...');
    var fd = new FormData(this);
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
          showStudioErrors(data.errors);
        } else {
          alert(data.message || 'Error');
        }
      })
      .fail(function (xhr) {
        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
          showStudioErrors(xhr.responseJSON.errors);
        } else {
          alert((xhr.responseJSON && xhr.responseJSON.message) || 'Error');
        }
      })
      .always(function () {
        $btn.prop('disabled', false);
        $btn.html(originalBtnHtml);
      });
  });
});
</script>
@endpush
