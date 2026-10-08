@php
  $card = $card ?? [];
  $ownSpaceWorkspaceOptions = $ownSpaceWorkspaceOptions ?? [];
  $ws = $card['workspace_type'] ?? 'private';
  if (! isset($ownSpaceWorkspaceOptions[$ws])) {
    $ws = 'private';
  }
@endphp

<form method="post" action="{{ route('settings.studio.update') }}" data-studio-edit>
  @csrf
  <input type="hidden" name="form_mode" value="own_space">
  <input type="hidden" name="studio_address" value="{{ old('studio_address', $card['studio_address'] ?? $card['address'] ?? '') }}">
  <input type="hidden" name="google_maps_link" value="{{ old('google_maps_link', $card['google_maps_link'] ?? $card['maps_url'] ?? '') }}">

  <div class="card" style="margin-bottom:14px">
    <div class="ch">
      <div>
        <h3>Your space</h3>
        <div class="faint" style="font-size:12.5px;margin-top:2px">Where clients come for their tattoo</div>
      </div>
      <a class="btn sm addst" href="{{ route('settings.studio.change', ['mode' => 'add']) }}">
        <span class="ms" style="font-size:17px">add</span>Add studio
      </a>
    </div>
    <div style="padding:18px 22px">
      <div class="grid" style="gap:14px">
        <div>
          <span class="fl">Space name <span style="color:#C62828">*</span></span>
          <input class="in" name="studio_name" value="{{ old('studio_name', $card['studio_name'] ?? $card['name'] ?? '') }}" placeholder="">
          @error('studio_name')<div class="help" style="color:var(--red)">{{ $message }}</div>@enderror
        </div>
        <div>
          <span class="fl">Find your address <span style="color:#C62828">*</span></span>
          <div class="in">
            <span class="ms">location_on</span>
            <input data-address-search value="{{ old('studio_address', $card['studio_address'] ?? $card['address'] ?? '') }}" style="border:0;outline:0;font:inherit;flex:1;background:none">
          </div>
          <div class="help">Start typing and pick from Google suggestions to fill the fields below.</div>
        </div>
        <div class="grid" style="grid-template-columns:1fr 2fr;gap:12px">
          <div>
            <span class="fl">Street number <span style="color:#C62828">*</span></span>
            <input class="in" name="street_number" value="{{ old('street_number', $card['street_number'] ?? '') }}" placeholder="">
          </div>
          <div>
            <span class="fl">Street name <span style="color:#C62828">*</span></span>
            <input class="in" name="street_name" value="{{ old('street_name', $card['street_name'] ?? '') }}" placeholder="">
          </div>
        </div>
        <div class="grid" style="grid-template-columns:1fr 1fr;gap:12px">
          <div>
            <span class="fl">City <span style="color:#C62828">*</span></span>
            <input class="in" name="city" value="{{ old('city', $card['city'] ?? '') }}" placeholder="">
          </div>
          <div>
            <span class="fl">State / Province <span style="color:#C62828">*</span></span>
            <input class="in" name="state" value="{{ old('state', $card['state'] ?? '') }}" placeholder="">
          </div>
          <div>
            <span class="fl">Postal code <span style="color:#C62828">*</span></span>
            <input class="in" name="postal_code" value="{{ old('postal_code', $card['postal_code'] ?? '') }}" placeholder="">
          </div>
          <div>
            <span class="fl">Country <span style="color:#C62828">*</span></span>
            <input class="in" name="country" value="{{ old('country', $card['country'] ?? '') }}" placeholder="">
          </div>
        </div>
        <div>
          <span class="fl">Google Maps link</span>
          <div class="mlbox" data-auto>
            <span class="ms">map</span>
            <a class="mlk" href="{{ $card['maps_url'] ?? '#' }}" target="_blank" rel="noopener">
              @if(!empty($card['address']))
                google.com/maps · {{ $card['address'] }}
              @else
                google.com/maps
              @endif
            </a>
            <button type="button" class="mlcopy"><span class="ms">content_copy</span>Copy</button>
          </div>
          <div class="help">Made from the address, so it updates when you change the address. Copy it to share.</div>
        </div>
      </div>
    </div>
  </div>

  <div class="card" style="margin-bottom:14px">
    <div class="ch">
      <div>
        <h3>Space type</h3>
        <div class="faint" style="font-size:12.5px;margin-top:2px">What best describes your workspace?</div>
      </div>
    </div>
    <div style="padding:18px 22px">
      <div class="tgrid">
        @foreach($ownSpaceWorkspaceOptions as $value => $meta)
          <label class="po {{ old('workspace_type', $ws) === $value ? 'sel' : '' }}">
            <input type="radio" name="workspace_type" value="{{ $value }}" @checked(old('workspace_type', $ws) === $value)>
            <div style="flex:1">
              <b><span class="ms" style="font-size:18px;vertical-align:-3px">{{ $meta['icon'] }}</span> {{ $meta['label'] }}</b>
              <div class="d">{{ $meta['hint'] }}</div>
            </div>
          </label>
        @endforeach
      </div>
      @error('workspace_type')<div class="help" style="color:var(--red)">{{ $message }}</div>@enderror
    </div>
  </div>

  <div class="row" style="justify-content:flex-start;margin-top:4px;margin-bottom:14px">
    <button type="submit" class="btn"><span class="ms">save</span>Save changes</button>
  </div>
</form>
