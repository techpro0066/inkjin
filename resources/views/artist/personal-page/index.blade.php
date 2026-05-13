@extends('layouts.artist_dashboard_layout')

@section('title', 'Personal Page')

@section('styles')

<link href="https://unpkg.com/cropperjs@1.6.2/dist/cropper.min.css" rel="stylesheet">
<style>
    /* Radio card */
    .radio-card { border: 2px solid #cac4d3; border-radius: 16px; padding: 16px; cursor: pointer; transition: all 0.2s; }
    .radio-card:hover { border-color: #664db1; }
    .radio-card.selected { border-color: #310f7a; background: rgba(49,15,122,0.04); }
    .radio-card.selected .radio-dot { background: #310f7a; border-color: #310f7a; }
    .radio-card.selected .radio-dot::after { content: ''; display: block; width: 8px; height: 8px; background: white; border-radius: 50%; }
    .radio-dot { width: 20px; height: 20px; border: 2px solid #7a7583; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; transition: all 0.2s; }

    /* Theme card */
    .theme-card { border: 2px solid #cac4d3; border-radius: 16px; overflow: hidden; cursor: pointer; transition: all 0.2s; position: relative; }
    .theme-card:hover { border-color: #664db1; }
    .theme-card.selected { border-color: #310f7a; }
    .theme-card .theme-check { display: none; position: absolute; top: 8px; right: 8px; width: 24px; height: 24px; background: #310f7a; border-radius: 50%; color: white; align-items: center; justify-content: center; }
    .theme-card.selected .theme-check { display: flex; }
    .theme-swatch { height: 40px; width: 100%; }
    .theme-label { padding: 10px 12px; font-size: 13px; font-weight: 600; color: #1c1b21; }

    /* Upload areas */
    .banner-upload { border: 2px dashed #cac4d3; border-radius: 16px; padding: 32px; display: flex; flex-direction: column; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s; background: white; }
    .banner-upload:hover { border-color: #664db1; background: #f8f1fb; }
    .banner-upload.has-image {
      height: 220px;
      padding: 0;
      border-style: solid;
      border-color: rgba(49,15,122,0.2);
      background-size: cover;
      background-position: center;
      background-repeat: no-repeat;
      overflow: hidden;
      position: relative;
    }
    .banner-upload .upload-overlay {
      display: none;
      position: absolute;
      inset: 0;
      background: rgba(20, 20, 20, 0.42);
      color: #ffffff;
      font-size: 13px;
      font-weight: 600;
      align-items: center;
      justify-content: center;
      letter-spacing: 0.02em;
    }
    .banner-upload.has-image .upload-prompt { display: none; }
    .banner-upload.has-image .upload-overlay { display: flex; }
    .banner-upload.has-image:hover .upload-overlay { background: rgba(20, 20, 20, 0.55); }
    .profile-upload { width: 96px; height: 96px; border-radius: 50%; border: 2px dashed #cac4d3; display: flex; flex-direction: column; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s; background: white; margin-top: -48px; margin-left: 24px; position: relative; z-index: 1; }
    .profile-upload:hover { border-color: #664db1; background: #f8f1fb; }

    .cropper-backdrop { background: rgba(0, 0, 0, 0.65); }
    .cropper-wrapper {
      background: #ffffff;
      border-radius: 20px;
      width: min(92vw, 920px);
      max-height: 90vh;
      overflow: hidden;
      display: flex;
      flex-direction: column;
      box-shadow: 0 20px 40px rgba(0,0,0,0.2);
    }
    .cropper-stage {
      background: #f5f4f8;
      min-height: 340px;
      height: min(58vh, 520px);
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
    }
    #bannerCropImage {
      max-width: 100%;
      max-height: 100%;
      display: block;
    }

    /* Mobile overflow fixes */
    @media (max-width: 1023px) {
      .main-content { overflow-x: hidden; padding: 16px; padding-top: 70px; }
      body { overflow-x: hidden; }
    }
</style>
@endsection

@section('content')
@php
  $selectedAlias = $userDetail->personal_page_name_alias ?? 'full';
  $selectedTheme = $userDetail->personal_page_color ?? 'default';
  $tagline = $userDetail->personal_page_tagline ?? '';
  $description = $userDetail->personal_page_description ?? '';
  $bannerUrl = $userDetail->personal_page_background_image ? asset($userDetail->personal_page_background_image) : '';
  $fullName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
  if ($fullName === '') {
      $fullName = $user->name ?? 'Artist Name';
  }
  $username = $userDetail->user_name ?? 'username';
@endphp
<main class="main-content flex-1 min-h-screen">
    <form id="personalPageForm" class="p-6 md:p-10 lg:p-12 max-w-6xl" enctype="multipart/form-data">
      @csrf

      <!-- Content Tabs -->
      <div class="flex items-center gap-1 mb-6 border-b border-outline-variant/20 pb-0 overflow-x-auto">
        <a href="{{ route('artist.forms.index') }}" class="px-4 py-3 text-sm font-semibold whitespace-nowrap border-b-2 border-transparent text-on-surface-variant hover:text-on-surface hover:border-outline-variant transition-all">Forms</a>
        <a href="{{ route('artist-designs.index') }}" class="px-4 py-3 text-sm font-semibold whitespace-nowrap border-b-2 border-transparent text-on-surface-variant hover:text-on-surface hover:border-outline-variant transition-all">Available Designs</a>
        <a href="{{ route('portfolio.index') }}" class="px-4 py-3 text-sm font-semibold whitespace-nowrap border-b-2 border-transparent text-on-surface-variant hover:text-on-surface hover:border-outline-variant transition-all">Portfolio</a>
        <a href="javascript:void(0)" class="px-4 py-3 text-sm font-semibold whitespace-nowrap border-b-2 border-primary text-primary hover:text-on-surface hover:border-outline-variant transition-all">Personal Page</a>
      </div>


      <!-- Page Header -->
      <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-8">
        <div>
          <h2 class="text-3xl font-extrabold text-on-surface tracking-tight">Personal Page</h2>
          <p class="text-on-surface-variant mt-1">Customize how your booking page looks to clients.</p>
        </div>
        <button type="button" onclick="copyPageLink('{{ $username }}')" class="inline-flex items-center gap-2 text-sm font-semibold text-primary hover:underline bg-primary/5 px-4 py-2 rounded-xl transition-colors">
          <span class="material-symbols-outlined text-lg">content_copy</span> Copy Your Personal Page Link
        </button>
      </div>
      <div id="personalPageSuccessAlert" class="hidden mb-6 rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-800 px-4 py-3 text-sm"></div>
      <div id="personalPageErrorAlert" class="hidden mb-6 rounded-xl border border-error/30 bg-error/10 text-error px-4 py-3 text-sm"></div>
      <input type="hidden" id="personal_page_name_alias" name="personal_page_name_alias" value="{{ $selectedAlias }}">
      <input type="hidden" id="personal_page_color" name="personal_page_color" value="{{ $selectedTheme }}">

      <!-- 1. Header & Banner Section -->
      <div class="bg-surface-container-low rounded-2xl p-5 md:p-6 mb-6 border border-outline-variant/20">
        <div class="flex items-center gap-3 mb-5">
          <div class="w-9 h-9 rounded-xl bg-primary/10 flex items-center justify-center">
            <span class="material-symbols-outlined text-primary text-lg">image</span>
          </div>
          <div>
            <h3 class="font-bold text-on-surface">Header & Banner <span class="text-red-600">*</span></h3>
            <p class="text-xs text-on-surface-variant">Upload a banner and profile photo for your page.</p>
          </div>
        </div>

        <!-- Banner Upload -->
        <div class="banner-upload mb-0 {{ $bannerUrl ? 'has-image' : '' }}" id="bannerUploadArea" onclick="document.getElementById('bannerInput').click()" @if($bannerUrl) style="background-image: url('{{ $bannerUrl }}')" @endif>
          <input type="file" id="bannerInput" name="personal_page_background_image" accept="image/*" class="hidden" onchange="handleBannerUpload(this)">
          <div class="upload-prompt flex flex-col items-center justify-center">
            <span class="material-symbols-outlined text-outline text-3xl mb-2">photo_camera</span>
            <p class="text-sm font-semibold text-on-surface">Upload Banner Image</p>
            <p class="text-xs text-on-surface-variant mt-1">Recommended: 1200×400px</p>
          </div>
          <div class="upload-overlay">Click to change banner</div>
        </div>
        <div class="relative z-20 mt-2 text-right pr-2">
          <p id="personal_page_background_image_error" class="text-error text-xs hidden inline-block"></p>
        </div>

        <!-- Profile Photo -->
        <div class="mb-4">
          <div class="profile-upload cursor-default hover:bg-white hover:border-outline-variant/30" id="profileUploadArea" onclick="">
            <img src="{{ $userDetail->avatar ? asset($userDetail->avatar) : '' }}" alt="Profile Photo" class="w-full h-full object-cover rounded-full">
          </div>
          <p class="text-xs text-on-surface-variant mt-1 ml-6">Change your photo in <a href="{{ route('profile.edit') }}" class="text-primary hover:underline">Profile Settings</a>.</p>
        </div>

        <!-- Tagline -->
        <div class="mt-6">
          <label for="tagline" class="block text-xs font-semibold text-on-surface-variant mb-1.5">Tagline <span class="text-red-600">*</span></label>
          <p class="text-xs text-on-surface-variant mb-2">Your one-liner that appears under your name</p>
          <input type="text" id="tagline" name="personal_page_tagline" value="{{ old('personal_page_tagline', $tagline) }}" class="w-full text-sm border border-outline-variant/30 rounded-xl px-3 py-2.5 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30" oninput="updatePreview()">
          <p id="personal_page_tagline_error" class="text-error text-xs mt-1 hidden"></p>
        </div>

        <!-- Bio -->
        <div class="mt-6">
          <label for="bio" class="block text-xs font-semibold text-on-surface-variant mb-1.5">Bio <span class="text-red-600">*</span></label>
          <p class="text-xs text-on-surface-variant mb-2">This will appear on your public artist page.</p>
          <textarea id="bio" name="personal_page_description" rows="5" maxlength="500" placeholder="Tell clients about yourself — your journey, your passion, what inspires your work..." class="w-full text-sm border border-outline-variant/30 rounded-xl px-3 py-2.5 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30 resize-none" oninput="updateBioCount()">{{ old('personal_page_description', $description) }}</textarea>
          <div class="flex justify-end mt-1">
            <span class="text-xs text-on-surface-variant" id="bioCount">0/255</span>
          </div>
          <p id="personal_page_description_error" class="text-error text-xs mt-1 hidden"></p>
        </div>
      </div>

      <!-- 2. Display Name Section -->
      <div class="bg-white rounded-2xl p-5 md:p-6 mb-6 border border-outline-variant/20">
        <div class="flex items-center gap-3 mb-5">
          <div class="w-9 h-9 rounded-xl bg-primary/10 flex items-center justify-center">
            <span class="material-symbols-outlined text-primary text-lg">badge</span>
          </div>
          <div>
            <h3 class="font-bold text-on-surface">How should your name appear? <span class="text-red-600">*</span></h3>
            <p class="text-xs text-on-surface-variant">Choose how clients see your name on your booking page.</p>
          </div>
        </div>

        <!-- Radio Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-6">
          <div class="radio-card {{ $selectedAlias === 'full' ? 'selected' : '' }}" data-name-option="full" onclick="selectNameOption('full')">
            <div class="flex items-start gap-3">
              <div class="radio-dot mt-0.5"><div></div></div>
              <div>
                <p class="text-sm font-bold text-on-surface">Full Name</p>
                <p class="text-lg font-extrabold text-primary mt-1">{{ $fullName }}</p>
                <p class="text-xs text-on-surface-variant mt-1">Show your full professional name</p>
              </div>
            </div>
          </div>
          <div class="radio-card {{ $selectedAlias === 'username' ? 'selected' : '' }}" data-name-option="username" onclick="selectNameOption('username')">
            <div class="flex items-start gap-3">
              <div class="radio-dot mt-0.5"><div></div></div>
              <div>
                <p class="text-sm font-bold text-on-surface">Username</p>
                <p class="text-lg font-extrabold text-primary mt-1">{{ $username }}</p>
                <p class="text-xs text-on-surface-variant mt-1">This is your unique profile URL handle</p>
              </div>
            </div>
          </div>
          <div class="radio-card {{ $selectedAlias === 'both' ? 'selected' : '' }}" data-name-option="both" onclick="selectNameOption('both')">
            <div class="flex items-start gap-3">
              <div class="radio-dot mt-0.5"><div></div></div>
              <div>
                <p class="text-sm font-bold text-on-surface">Both</p>
                <p class="text-lg font-extrabold text-primary mt-1">{{ $fullName }} ({{ $username }})</p>
                <p class="text-xs text-on-surface-variant mt-1">Show full name with username in parentheses</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Name Inputs -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label for="fullName" class="block text-xs font-semibold text-on-surface-variant mb-1.5">Full Name</label>
            <input type="text" id="fullName" name="fullName" value="{{ $fullName }}" class="w-full text-sm border border-outline-variant/30 rounded-xl px-3 py-2.5 bg-surface-container-highest text-on-surface-variant cursor-not-allowed focus:outline-none focus:ring-0" readonly oninput="updatePreview()">
            <p class="text-xs text-on-surface-variant mt-1">Edit in <a href="{{ route('profile.edit') }}" class="text-primary hover:underline">Profile Settings</a>.</p>
          </div>
          <div>
            <label for="username" class="block text-xs font-semibold text-on-surface-variant mb-1.5">Username</label>
            <input type="text" id="username" name="username" value="{{ $username }}" class="w-full text-sm border border-outline-variant/30 rounded-xl px-3 py-2.5 bg-surface-container-highest text-on-surface-variant cursor-not-allowed focus:outline-none focus:ring-0" readonly oninput="updatePreview()">
            <p class="text-xs text-on-surface-variant mt-1">Edit in <a href="{{ route('profile.edit') }}" class="text-primary hover:underline">Profile Settings</a>.</p>
          </div>
        </div>
        <p id="personal_page_name_alias_error" class="text-error text-xs mt-3 hidden"></p>
      </div>

      <!-- 3. Color Scheme Section -->
      <div class="bg-white rounded-2xl p-5 md:p-6 mb-6 border border-outline-variant/20">
        <div class="flex items-center gap-3 mb-2">
          <div class="w-9 h-9 rounded-xl bg-primary/10 flex items-center justify-center">
            <span class="material-symbols-outlined text-primary text-lg">palette</span>
          </div>
          <div>
            <h3 class="font-bold text-on-surface">Page Color Scheme <span class="text-red-600">*</span></h3>
          </div>
        </div>
        <p class="text-xs text-on-surface-variant mb-5 ml-12">Choose a color theme for your public booking page.</p>

        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4" id="themeGrid">
          <!-- Default Purple -->
          <div class="theme-card {{ $selectedTheme === 'default' ? 'selected' : '' }}" data-theme="default" data-primary="#310F7A" data-bg="#F8F1FB" onclick="selectTheme(this)">
            <div class="theme-check"><span class="material-symbols-outlined text-sm">check</span></div>
            <div class="theme-swatch" style="background: linear-gradient(135deg, #310F7A, #664db1);"></div>
            <div class="theme-label">Default Purple</div>
          </div>
          <!-- Ocean Blue -->
          <div class="theme-card {{ $selectedTheme === 'ocean' ? 'selected' : '' }}" data-theme="ocean" data-primary="#1565C0" data-bg="#E3F2FD" onclick="selectTheme(this)">
            <div class="theme-check"><span class="material-symbols-outlined text-sm">check</span></div>
            <div class="theme-swatch" style="background: linear-gradient(135deg, #1565C0, #42A5F5);"></div>
            <div class="theme-label">Ocean Blue</div>
          </div>
          <!-- Forest Green -->
          <div class="theme-card {{ $selectedTheme === 'forest' ? 'selected' : '' }}" data-theme="forest" data-primary="#2E7D32" data-bg="#E8F5E9" onclick="selectTheme(this)">
            <div class="theme-check"><span class="material-symbols-outlined text-sm">check</span></div>
            <div class="theme-swatch" style="background: linear-gradient(135deg, #2E7D32, #66BB6A);"></div>
            <div class="theme-label">Forest Green</div>
          </div>
          <!-- Warm Coral -->
          <div class="theme-card {{ $selectedTheme === 'coral' ? 'selected' : '' }}" data-theme="coral" data-primary="#D84315" data-bg="#FBE9E7" onclick="selectTheme(this)">
            <div class="theme-check"><span class="material-symbols-outlined text-sm">check</span></div>
            <div class="theme-swatch" style="background: linear-gradient(135deg, #D84315, #FF7043);"></div>
            <div class="theme-label">Warm Coral</div>
          </div>
          <!-- Midnight -->
          <div class="theme-card {{ $selectedTheme === 'midnight' ? 'selected' : '' }}" data-theme="midnight" data-primary="#1A1A2E" data-bg="#F5F5F5" onclick="selectTheme(this)">
            <div class="theme-check"><span class="material-symbols-outlined text-sm">check</span></div>
            <div class="theme-swatch" style="background: linear-gradient(135deg, #1A1A2E, #3a3a5e);"></div>
            <div class="theme-label">Midnight</div>
          </div>
          <!-- Golden Hour -->
          <div class="theme-card {{ $selectedTheme === 'golden' ? 'selected' : '' }}" data-theme="golden" data-primary="#F57F17" data-bg="#FFF8E1" onclick="selectTheme(this)">
            <div class="theme-check"><span class="material-symbols-outlined text-sm">check</span></div>
            <div class="theme-swatch" style="background: linear-gradient(135deg, #F57F17, #FFB300);"></div>
            <div class="theme-label">Golden Hour</div>
          </div>
        </div>
        <p id="personal_page_color_error" class="text-error text-xs mt-3 hidden"></p>
      </div>

      <!-- Actions -->
      <div class="flex flex-col sm:flex-row justify-between items-center gap-4 mt-8">
        <button type="button" onclick="openModal('previewPersonalPageModal')" class="px-6 py-2.5 border border-outline-variant text-on-surface font-semibold rounded-xl hover:bg-surface-container transition-colors shadow-sm flex items-center gap-2">
          <span class="material-symbols-outlined align-middle" style="font-size:18px;">visibility</span> Preview Personal Page
        </button>
        <button type="submit" id="savePersonalPageBtn" class="bg-primary text-white px-8 py-3 rounded-xl font-semibold text-sm hover:bg-primary-container transition-colors shadow-sm flex items-center gap-2">
          <span class="material-symbols-outlined text-lg">save</span> Save Changes
        </button>
      </div>

    </form>
  </main>

  <div id="copyLinkToast" class="pointer-events-none fixed top-6 right-6 z-[70] transform translate-x-full opacity-0 transition-all duration-300">
    <div class="pointer-events-auto flex items-center gap-3 bg-on-surface text-white px-5 py-3 rounded-xl shadow-lg max-w-sm">
      <span class="material-symbols-outlined text-green-400 shrink-0" style="font-size:20px;">check_circle</span>
      <span class="text-sm font-medium" id="copyLinkToastMessage">Personal page link copied.</span>
    </div>
  </div>

  <!-- Preview Modal -->
  <div id="previewPersonalPageModal" class="fixed inset-0 z-50 hidden">
    <!-- Backdrop -->
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeModal('previewPersonalPageModal')"></div>
    <!-- Modal Content -->
    <div class="absolute inset-4 md:inset-10 bg-white rounded-3xl shadow-2xl flex flex-col overflow-hidden max-w-4xl mx-auto">
      <!-- Header -->
      <div class="flex items-center justify-between px-6 py-4 border-b border-outline-variant/20">
        <h3 class="text-lg font-bold text-on-surface">Preview: Public Artist Page</h3>
        <button onclick="closeModal('previewPersonalPageModal')" class="w-8 h-8 rounded-full hover:bg-surface-container-high flex items-center justify-center text-on-surface-variant">
          <span class="material-symbols-outlined">close</span>
        </button>
      </div>
      <!-- Body -->
      <div class="flex-1 overflow-y-auto bg-surface p-6 md:p-10">
        <div class="max-w-2xl mx-auto bg-white rounded-3xl shadow-sm border border-outline-variant/20 overflow-hidden" id="previewModalCard">
          <div class="w-full h-48 bg-gray-200 flex items-center justify-center" id="modalPreviewBanner">
            @if($bannerUrl)
            <img src="{{ $bannerUrl }}" alt="Banner" class="w-full h-full object-cover">
            @else
              <span class="material-symbols-outlined text-gray-400 text-4xl">panorama</span>
            @endif
          </div>
          <div class="flex flex-col items-center -mt-16 relative z-10 pb-10 px-6">
            <div class="w-32 h-32 rounded-full bg-surface-container-high border-4 border-white flex items-center justify-center shadow-sm" id="modalPreviewProfilePhoto">
              @if($userDetail->avatar)
              <img src="{{ $userDetail->avatar }}" alt="Profile Photo" class="w-full h-full object-cover rounded-full">
              @else
                <span class="material-symbols-outlined text-outline text-4xl">person</span>
              @endif
            </div>
            <h4 class="text-2xl font-extrabold text-on-surface mt-4" id="modalPreviewName">{{ $fullName ?? 'Artist Name' }}</h4>
            <p class="text-sm text-on-surface-variant mt-1 text-center font-medium" id="modalPreviewTagline">{{ $tagline ?? 'Add your artist tagline' }}</p>
            <p class="text-sm text-on-surface mt-4 text-center max-w-lg" id="modalPreviewBio">{{ $description ?? 'Add your bio so clients can learn more about your style and journey.' }}</p>
            <button class="mt-8 px-8 py-3 rounded-2xl text-white font-bold text-sm transition-colors shadow-sm w-full max-w-xs" id="modalPreviewBookBtn" style="background: {{ $userDetail->personal_page_color ?? '#310F7A' }};">
              Book Now
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Banner Cropper Modal -->
  <div id="bannerCropperModal" class="fixed inset-0 z-[60] hidden items-center justify-center p-4">
    <div class="cropper-backdrop absolute inset-0" onclick="closeBannerCropper()"></div>
    <div class="cropper-wrapper relative">
      <div class="flex items-center justify-between px-5 py-4 border-b border-outline-variant/30">
        <div>
          <h4 class="text-base font-bold text-on-surface">Crop Banner Image</h4>
          <p class="text-xs text-on-surface-variant mt-0.5">Use the crop area to keep a consistent 3:1 banner ratio.</p>
        </div>
        <button type="button" onclick="closeBannerCropper()" class="w-8 h-8 rounded-full hover:bg-surface-container-high flex items-center justify-center text-on-surface-variant">
          <span class="material-symbols-outlined text-lg">close</span>
        </button>
      </div>
      <div class="cropper-stage">
        <img id="bannerCropImage" alt="Crop banner preview">
      </div>
      <div class="flex items-center justify-end gap-3 px-5 py-4 border-t border-outline-variant/30">
        <button type="button" onclick="closeBannerCropper()" class="px-4 py-2 rounded-xl border border-outline-variant text-on-surface text-sm font-semibold hover:bg-surface-container transition-colors">Cancel</button>
        <button type="button" onclick="applyBannerCrop()" class="px-4 py-2 rounded-xl bg-primary text-white text-sm font-semibold hover:bg-primary-container transition-colors">Apply Crop</button>
      </div>
    </div>
  </div>

  <script src="https://unpkg.com/cropperjs@1.6.2/dist/cropper.min.js"></script>
  <script>
    const THEME_MAP = {
      default: { primary: '#310F7A', bg: '#F8F1FB' },
      ocean: { primary: '#1565C0', bg: '#E3F2FD' },
      forest: { primary: '#2E7D32', bg: '#E8F5E9' },
      coral: { primary: '#D84315', bg: '#FBE9E7' },
      midnight: { primary: '#1A1A2E', bg: '#F5F5F5' },
      golden: { primary: '#F57F17', bg: '#FFF8E1' }
    };

    let selectedNameOption = document.getElementById('personal_page_name_alias')?.value || 'full';
    let selectedThemeKey = document.getElementById('personal_page_color')?.value || 'default';
    let selectedTheme = THEME_MAP[selectedThemeKey] || THEME_MAP.default;
    let bannerCropper = null;
    let activeBannerObjectUrl = null;
    let croppedBannerBlob = null;
    let croppedBannerFilename = 'banner.jpg';
    let bannerSourceMime = '';

    function inferImageMimeFromFilename(name) {
      if (!name) return '';
      if (/\.png$/i.test(name)) return 'image/png';
      if (/\.webp$/i.test(name)) return 'image/webp';
      if (/\.gif$/i.test(name)) return 'image/gif';
      if (/\.jpe?g$/i.test(name)) return 'image/jpeg';
      return '';
    }

    function bannerCropShouldPreserveAlpha() {
      const m = (bannerSourceMime || '').toLowerCase();
      return m === 'image/png' || m === 'image/webp' || m === 'image/gif';
    }

    function getDisplayName() {
      const fullName = (document.getElementById('fullName')?.value || '').trim() || 'Artist Name';
      const username = (document.getElementById('username')?.value || '').trim() || 'username';

      if (selectedNameOption === 'username') return username;
      if (selectedNameOption === 'both') return fullName + ' (' + username + ')';
      return fullName;
    }

    function refreshPreviewModal() {
      const modalPreviewName = document.getElementById('modalPreviewName');
      const modalPreviewTagline = document.getElementById('modalPreviewTagline');
      const modalPreviewBio = document.getElementById('modalPreviewBio');
      const modalPreviewBookBtn = document.getElementById('modalPreviewBookBtn');
      const modalPreviewCard = document.getElementById('previewModalCard');
      const taglineInput = document.getElementById('tagline');
      const bioInput = document.getElementById('bio');

      if (modalPreviewName) modalPreviewName.textContent = getDisplayName();
      if (modalPreviewTagline) modalPreviewTagline.textContent = (taglineInput?.value || '').trim() || 'Add your artist tagline';
      if (modalPreviewBio) modalPreviewBio.textContent = (bioInput?.value || '').trim() || 'Add your bio so clients can learn more about your style and journey.';
      if (modalPreviewBookBtn) modalPreviewBookBtn.style.background = selectedTheme.primary;
      if (modalPreviewCard) modalPreviewCard.style.background = selectedTheme.bg;
    }

    function openModal(id) {
      const modal = document.getElementById(id);
      if (!modal) return;
      if (id === 'previewPersonalPageModal') refreshPreviewModal();
      modal.classList.remove('hidden');
    }

    function closeModal(id) {
      const modal = document.getElementById(id);
      if (!modal) return;
      modal.classList.add('hidden');
    }

    function selectNameOption(option) {
      selectedNameOption = option;
      const aliasInput = document.getElementById('personal_page_name_alias');
      if (aliasInput) aliasInput.value = option;
      document.querySelectorAll('[data-name-option]').forEach(function (card) {
        card.classList.toggle('selected', card.dataset.nameOption === option);
      });
      clearFieldError('personal_page_name_alias');
      refreshPreviewModal();
    }

    function selectTheme(card) {
      if (!card) return;
      selectedThemeKey = card.dataset.theme || 'default';
      selectedTheme = {
        primary: card.dataset.primary || '#310F7A',
        bg: card.dataset.bg || '#F8F1FB'
      };
      const colorInput = document.getElementById('personal_page_color');
      if (colorInput) colorInput.value = selectedThemeKey;
      clearFieldError('personal_page_color');
      document.querySelectorAll('.theme-card').forEach(function (themeCard) {
        themeCard.classList.remove('selected');
      });
      card.classList.add('selected');
      refreshPreviewModal();
    }

    function updatePreview() {
      refreshPreviewModal();
    }

    function updateBioCount() {
      const bioInput = document.getElementById('bio');
      const bioCount = document.getElementById('bioCount');
      if (!bioInput || !bioCount) return;
      bioCount.textContent = bioInput.value.length + '/500';
      refreshPreviewModal();
    }

    var copyLinkToastTimer = null;
    function showCopyLinkToast(message) {
      var toast = document.getElementById('copyLinkToast');
      var msgEl = document.getElementById('copyLinkToastMessage');
      if (!toast || !msgEl) return;
      msgEl.textContent = message || 'Personal page link copied.';
      toast.classList.remove('translate-x-full', 'opacity-0');
      toast.classList.add('translate-x-0', 'opacity-100');
      clearTimeout(copyLinkToastTimer);
      copyLinkToastTimer = setTimeout(function () {
        toast.classList.add('translate-x-full', 'opacity-0');
        toast.classList.remove('translate-x-0', 'opacity-100');
      }, 3000);
    }

    function copyPageLink(username) {
      const pageUrl = "{{ url('/') }}/" + username;
      var done = function () {
        showCopyLinkToast('Personal page link copied.');
      };
      var fail = function () {
        showCopyLinkToast('Could not copy link. Copy it manually from the address bar.');
      };
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(pageUrl).then(done).catch(fail);
      } else {
        fail();
      }
    }

    function clearAlerts() {
      const successAlert = document.getElementById('personalPageSuccessAlert');
      const errorAlert = document.getElementById('personalPageErrorAlert');
      if (successAlert) {
        successAlert.classList.add('hidden');
        successAlert.textContent = '';
      }
      if (errorAlert) {
        errorAlert.classList.add('hidden');
        errorAlert.textContent = '';
      }
    }

    function clearFieldError(fieldName) {
      const field = document.getElementById(fieldName);
      const error = document.getElementById(fieldName + '_error');
      if (field) field.classList.remove('border-error', 'ring-2', 'ring-error/40');
      if (fieldName === 'personal_page_color') {
        const themeGrid = document.getElementById('themeGrid');
        if (themeGrid) themeGrid.classList.remove('ring-2', 'ring-error/40', 'rounded-2xl', 'p-2');
      }
      if (error) {
        error.textContent = '';
        error.classList.add('hidden');
      }
    }

    function setFieldError(fieldName, message) {
      const field = document.getElementById(fieldName);
      const error = document.getElementById(fieldName + '_error');
      if (field) field.classList.add('border-error', 'ring-2', 'ring-error/40');
      if (error) {
        error.textContent = message;
        error.classList.remove('hidden');
      }
      if (fieldName === 'personal_page_color') {
        const themeGrid = document.getElementById('themeGrid');
        if (themeGrid) themeGrid.classList.add('ring-2', 'ring-error/40', 'rounded-2xl', 'p-2');
      }
    }

    function scrollToFirstPersonalPageError() {
      const firstError = document.querySelector('p[id$="_error"]:not(.hidden)');
      if (firstError && firstError.textContent.trim() !== '') {
        firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return;
      }
      const globalError = document.getElementById('personalPageErrorAlert');
      if (globalError && !globalError.classList.contains('hidden')) {
        globalError.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
    }

    function hasBannerSelected() {
      if (croppedBannerBlob) return true;
      const bannerArea = document.getElementById('bannerUploadArea');
      return !!(bannerArea && bannerArea.classList.contains('has-image'));
    }

    function validatePersonalPageForm() {
      let ok = true;
      const tagline = (document.getElementById('tagline')?.value || '').trim();
      const bio = (document.getElementById('bio')?.value || '').trim();
      const alias = (document.getElementById('personal_page_name_alias')?.value || '').trim();
      const color = (document.getElementById('personal_page_color')?.value || '').trim();

      if (!hasBannerSelected()) {
        setFieldError('personal_page_background_image', 'Background image is required.');
        ok = false;
      }
      if (!tagline) {
        setFieldError('personal_page_tagline', 'Tagline is required.');
        ok = false;
      }
      if (!bio) {
        setFieldError('personal_page_description', 'Bio is required.');
        ok = false;
      }
      if (!alias) {
        setFieldError('personal_page_name_alias', 'Please select how your name should appear.');
        ok = false;
      }
      if (!color) {
        setFieldError('personal_page_color', 'Please select a color scheme.');
        ok = false;
      }
      return ok;
    }

    function handleBannerUpload(input) {
      if (!input || !input.files || !input.files[0]) return;
      const file = input.files[0];
      bannerSourceMime = ((file.type || inferImageMimeFromFilename(file.name) || '') + '').toLowerCase();
      const isImageMime = !!file.type && file.type.startsWith('image/');
      const isImageByName = /\.(png|jpe?g|gif|webp|bmp|svg)$/i.test(file.name || '');
      if (!isImageMime && !isImageByName) {
        input.value = '';
        alert('Please select a valid image file.');
        return;
      }

      if (activeBannerObjectUrl) URL.revokeObjectURL(activeBannerObjectUrl);
      activeBannerObjectUrl = URL.createObjectURL(file);

      const cropImage = document.getElementById('bannerCropImage');
      if (!cropImage) return;

      cropImage.onload = null;
      cropImage.onerror = null;

      const modal = document.getElementById('bannerCropperModal');
      modal.classList.remove('hidden');
      modal.classList.add('flex');

      if (bannerCropper) {
        bannerCropper.destroy();
        bannerCropper = null;
      }

      const initCropper = function () {
        bannerCropper = new Cropper(cropImage, {
          aspectRatio: 3,
          viewMode: 1,
          dragMode: 'move',
          autoCropArea: 1,
          responsive: true,
          background: false,
          guides: false,
          checkCrossOrigin: false
        });
      };

      cropImage.onload = initCropper;
      cropImage.onerror = function () {
        closeBannerCropper();
        alert('This image format is not supported by your browser. Please use JPG or PNG.');
      };
      cropImage.src = activeBannerObjectUrl;
    }

    function closeBannerCropper() {
      const modal = document.getElementById('bannerCropperModal');
      modal.classList.add('hidden');
      modal.classList.remove('flex');

      if (bannerCropper) {
        bannerCropper.destroy();
        bannerCropper = null;
      }

      if (activeBannerObjectUrl) {
        URL.revokeObjectURL(activeBannerObjectUrl);
        activeBannerObjectUrl = null;
      }

      const bannerInput = document.getElementById('bannerInput');
      if (bannerInput) bannerInput.value = '';
    }

    function applyBannerCrop() {
      if (!bannerCropper) return;

      const preserveAlpha = bannerCropShouldPreserveAlpha();
      const canvasOpts = {
        width: 1200,
        height: 400,
        imageSmoothingEnabled: true,
        imageSmoothingQuality: 'high'
      };
      if (!preserveAlpha) {
        canvasOpts.fillColor = '#ffffff';
      }

      const canvas = bannerCropper.getCroppedCanvas(canvasOpts);

      if (!canvas) return;

      const outMime = preserveAlpha ? 'image/png' : 'image/jpeg';
      const outQuality = preserveAlpha ? undefined : 0.92;
      croppedBannerFilename = preserveAlpha ? 'banner.png' : 'banner.jpg';

      canvas.toBlob(function (blob) {
        if (!blob) return;
        croppedBannerBlob = blob;
        const croppedBannerDataUrl = preserveAlpha
          ? canvas.toDataURL('image/png')
          : canvas.toDataURL('image/jpeg', 0.92);

        const bannerArea = document.getElementById('bannerUploadArea');
        bannerArea.style.backgroundImage = "url('" + croppedBannerDataUrl + "')";
        bannerArea.classList.add('has-image');
        clearFieldError('personal_page_background_image');

        const previewBanner = document.getElementById('modalPreviewBanner');
        if (previewBanner) {
          previewBanner.style.backgroundImage = "url('" + croppedBannerDataUrl + "')";
          previewBanner.style.backgroundSize = 'cover';
          previewBanner.style.backgroundPosition = 'center';
          previewBanner.innerHTML = '';
        }

        closeBannerCropper();
      }, outMime, outQuality);
    }

    document.addEventListener('DOMContentLoaded', function () {
      const form = document.getElementById('personalPageForm');
      const saveBtn = document.getElementById('savePersonalPageBtn');

      ['personal_page_tagline', 'personal_page_description', 'personal_page_background_image'].forEach(function (field) {
        const id = field === 'personal_page_tagline' ? 'tagline' : (field === 'personal_page_description' ? 'bio' : 'bannerInput');
        const el = document.getElementById(id);
        if (el) {
          el.addEventListener('input', function () {
            clearFieldError(field);
          });
          el.addEventListener('change', function () {
            clearFieldError(field);
          });
        }
      });

      const selectedThemeCard = document.querySelector('.theme-card.selected');
      if (selectedThemeCard) {
        selectTheme(selectedThemeCard);
      }

      const selectedNameCard = document.querySelector('[data-name-option].selected');
      if (selectedNameCard) selectNameOption(selectedNameCard.dataset.nameOption || 'full');

      updateBioCount();
      refreshPreviewModal();

      if (!form || !saveBtn) return;
      form.addEventListener('submit', function (e) {
        e.preventDefault();
        clearAlerts();
        ['personal_page_background_image', 'personal_page_tagline', 'personal_page_description', 'personal_page_name_alias', 'personal_page_color'].forEach(clearFieldError);

        if (!validatePersonalPageForm()) {
          scrollToFirstPersonalPageError();
          return;
        }

        saveBtn.disabled = true;
        saveBtn.innerHTML = '<span class="material-symbols-outlined text-lg">hourglass_top</span> Saving...';

        const formData = new FormData(form);
        if (croppedBannerBlob) {
          formData.delete('personal_page_background_image');
          formData.append('personal_page_background_image', croppedBannerBlob, croppedBannerFilename || 'banner.jpg');
        }

        fetch(@json(route('personal-page.update')), {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': @json(csrf_token()),
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
          },
          body: formData
        }).then(function (response) {
          return response.json().then(function (data) {
            return { ok: response.ok, status: response.status, data: data };
          });
        }).then(function (result) {
          if (result.ok && result.data && result.data.success) {
            const successAlert = document.getElementById('personalPageSuccessAlert');
            successAlert.textContent = result.data.message || 'Personal page updated successfully.';
            successAlert.classList.remove('hidden');
            if (result.data.banner) {
              const bannerArea = document.getElementById('bannerUploadArea');
              bannerArea.style.backgroundImage = "url('" + result.data.banner + "')";
              bannerArea.classList.add('has-image');
            }
            showSaveToast();
            return;
          }
          if (result.status === 422 && result.data && result.data.errors) {
            Object.keys(result.data.errors).forEach(function (key) {
              setFieldError(key, result.data.errors[key][0]);
            });
            scrollToFirstPersonalPageError();
            return;
          }
          const errorAlert = document.getElementById('personalPageErrorAlert');
          errorAlert.textContent = (result.data && result.data.message) ? result.data.message : 'Could not save personal page.';
          errorAlert.classList.remove('hidden');
          scrollToFirstPersonalPageError();
        }).catch(function () {
          const errorAlert = document.getElementById('personalPageErrorAlert');
          errorAlert.textContent = 'Network error. Please try again.';
          errorAlert.classList.remove('hidden');
          scrollToFirstPersonalPageError();
        }).finally(function () {
          saveBtn.disabled = false;
          saveBtn.innerHTML = '<span class="material-symbols-outlined text-lg">save</span> Save Changes';
        });
      });
    });
  </script>
@endsection