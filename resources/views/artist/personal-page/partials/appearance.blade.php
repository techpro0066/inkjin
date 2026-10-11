@php
  $selectedTheme = $userDetail->personal_page_color ?? 'default';
  $bannerUrl = $userDetail->personal_page_background_image ? asset($userDetail->personal_page_background_image) : '';
  $displayGuestSpots = (bool) ($userDetail->display_guest_spots ?? false);
  $themes = [
    'default' => ['label' => 'Default Purple', 'grad' => 'linear-gradient(135deg,#310F7A,#664db1)'],
    'ocean' => ['label' => 'Ocean Blue', 'grad' => 'linear-gradient(135deg,#1565C0,#42A5F5)'],
    'forest' => ['label' => 'Forest Green', 'grad' => 'linear-gradient(135deg,#2E7D32,#66BB6A)'],
    'coral' => ['label' => 'Warm Coral', 'grad' => 'linear-gradient(135deg,#D84315,#FF7043)'],
    'midnight' => ['label' => 'Midnight', 'grad' => 'linear-gradient(135deg,#1A1A2E,#3a3a5e)'],
    'rose' => ['label' => 'Rose', 'grad' => 'linear-gradient(135deg,#AD1457,#F06292)'],
  ];
@endphp

<form id="appearanceForm" enctype="multipart/form-data">
  @csrf
  <input type="hidden" name="section" value="appearance">
  <input type="hidden" id="personal_page_color" name="personal_page_color" value="{{ $selectedTheme }}">
  <input type="hidden" id="remove_banner" name="remove_personal_page_background_image" value="0">
  <input type="hidden" name="display_guest_spots" id="display_guest_spots" value="{{ $displayGuestSpots ? '1' : '0' }}">

  <div class="card" style="margin-bottom:14px">
    <div class="ch">
      <div>
        <h3>Banner</h3>
        <div class="faint" style="font-size:12.5px;margin-top:2px">Optional image at the top of your page</div>
      </div>
    </div>
    <div style="padding:18px 22px">
      <div class="banner-upload {{ $bannerUrl ? 'has-image' : '' }}" id="bannerUploadArea" role="button" tabindex="0">
        <input type="file" id="bannerInput" name="personal_page_background_image" accept="image/jpeg,image/png,image/webp" hidden>
        <div class="upload-prompt">
          <span class="ms" style="font-size:28px;color:var(--faint)">add_photo_alternate</span>
          <p style="font-weight:600;margin-top:8px">Upload banner</p>
          <p class="faint" style="font-size:12px;margin-top:4px">JPG, PNG or WebP · under 4 MB</p>
        </div>
        <div class="upload-overlay">Change banner</div>
      </div>
      <div class="row" style="margin-top:10px;gap:10px">
        <button type="button" class="btn sm ghost" id="bannerRemoveBtn" {{ $bannerUrl ? '' : 'hidden' }}>Remove banner</button>
      </div>
    </div>
  </div>

  <div class="card" style="margin-bottom:14px">
    <div class="ch">
      <div>
        <h3>Color theme</h3>
        <div class="faint" style="font-size:12.5px;margin-top:2px">Accent color for your public page</div>
      </div>
    </div>
    <div style="padding:18px 22px">
      <div class="theme-grid">
        @foreach ($themes as $key => $theme)
          <button type="button" class="theme-card {{ $selectedTheme === $key ? 'on' : '' }}" data-theme="{{ $key }}">
            <span class="theme-swatch" style="background:{{ $theme['grad'] }}"></span>
            <span class="theme-label">{{ $theme['label'] }}</span>
            <span class="ms theme-check">check</span>
          </button>
        @endforeach
      </div>
    </div>
  </div>

  <div class="card" style="margin-bottom:14px">
    <div class="ch">
      <div>
        <h3>Guest spots</h3>
        <div class="faint" style="font-size:12.5px;margin-top:2px">Show upcoming guest spots on your page</div>
      </div>
      <div class="tog {{ $displayGuestSpots ? 'on' : '' }}" id="guestSpotsTog" data-own="1" role="switch" aria-checked="{{ $displayGuestSpots ? 'true' : 'false' }}" tabindex="0"></div>
    </div>
  </div>

  <div class="row pgsaverow" style="justify-content:flex-start;margin-top:8px">
    <button type="button" class="btn pgsave" id="appearanceSaveBtn" disabled>
      <span class="ms">save</span>Save changes
    </button>
  </div>
</form>
