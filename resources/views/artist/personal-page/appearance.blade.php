@extends('layouts.new-artist-dashboard-layout')

@section('title', 'My Page')

@php
  $userDetail = $userDetail ?? $user->userDetail;
  $styleOptions = $styleOptions ?? [];
  $username = trim((string) ($userDetail->user_name ?? ''));
  $displayName = trim((string) ($userDetail->display_name ?? ''));
  $firstName = (string) ($user->first_name ?? '');
  $lastName = (string) ($user->last_name ?? '');
  $fullName = trim($firstName.' '.$lastName);
  $alias = in_array($userDetail->personal_page_name_alias, ['username', 'display_name'], true)
      ? $userDetail->personal_page_name_alias
      : 'username';
  $pageName = match ($alias) {
      'display_name' => $displayName !== '' ? $displayName : ($username !== '' ? $username : $fullName),
      'username' => $username !== '' ? $username : ($displayName !== '' ? $displayName : $fullName),
      default => $fullName !== '' ? $fullName : ($username !== '' ? $username : 'Artist'),
  };
  if ($pageName === '') {
      $pageName = 'Artist';
  }
  $initials = strtoupper(substr(preg_replace('/\s+/', '', $pageName) ?: 'AR', 0, 2));
  $avatarPath = trim((string) ($userDetail->avatar ?? ''));
  $avatarUrl = $avatarPath !== ''
      ? (str_starts_with($avatarPath, 'http') ? $avatarPath : asset(ltrim($avatarPath, '/')))
      : null;
  $publicPageUrl = $username !== '' ? url('/@'.$username) : url('/');
  $publicPageHost = 'inkjin.com/@'.($username !== '' ? $username : 'username');
  $quickLinkRows = $username !== '' ? [
    ['suffix' => '', 'label' => $publicPageHost],
    ['suffix' => '#designs', 'label' => $publicPageHost.'#designs'],
    ['suffix' => '#portfolio', 'label' => $publicPageHost.'#portfolio'],
    ['suffix' => '#guest-spots', 'label' => $publicPageHost.'#guest-spots'],
    ['suffix' => '#policies', 'label' => $publicPageHost.'#policies'],
    ['suffix' => '#faq', 'label' => $publicPageHost.'#faq'],
  ] : [];
  $studioName = trim((string) ($userDetail->resolvedStudioName() ?? ''));
  $city = trim((string) ($userDetail->city ?? ''));
  $ts = is_array($userDetail->tattoo_styles ?? null) ? $userDetail->tattoo_styles : [];
  $since = $ts['tattooing_since'] ?? null;
  $primary = $ts['primary_style'] ?? null;
  $otherList = [];
  if (isset($ts['other_styles']) && is_array($ts['other_styles'])) {
      $otherList = array_values(array_filter(array_map('strval', $ts['other_styles'])));
  } elseif (array_is_list($ts)) {
      $otherList = $ts;
  }
  $styleLabels = [];
  if ($primary) {
      $styleLabels[] = $styleOptions[$primary] ?? ucwords(str_replace('-', ' ', (string) $primary));
  }
  foreach ($otherList as $os) {
      if ($os === '') {
          continue;
      }
      $styleLabels[] = $styleOptions[$os] ?? ucwords(str_replace('-', ' ', (string) $os));
  }
  $metaParts = array_filter([
      $studioName !== '' ? $studioName : null,
      $city !== '' ? $city : null,
      $since ? 'Tattooing since '.$since : null,
  ]);
  $tagline = trim((string) ($userDetail->personal_page_tagline ?? ''));
  if ($tagline === '') {
      $tagline = 'Bold traditional tattoos, made to last.';
  }
  $bio = trim((string) ($userDetail->personal_page_description ?? ''));
  if ($bio === '') {
      $bio = 'Bring your idea and we\'ll make it yours.';
  }
  $lookState = $lookState ?? \App\Support\PageLook::stateFromUserDetail($userDetail);
  $lookCfg = [
      'who' => 'artist',
      'base' => '',
      'ownSave' => true,
      'name' => $pageName,
      'avatar' => $avatarUrl,
      'initials' => $initials,
      'tagline' => $tagline,
      'meta' => $metaParts !== [] ? implode(' · ', $metaParts) : 'Your studio · Your city',
      'styles' => $styleLabels !== [] ? implode(' · ', $styleLabels) : 'Traditional · Neo-Traditional',
      'bio' => $bio,
      'cta' => 'Request custom tattoo',
      'bookLine' => 'Hourly rate · Deposit to book',
      'handle' => $username !== '' ? '@'.$username : '',
      'pageUrl' => $publicPageUrl,
  ];
@endphp

@section('styles')
<link rel="stylesheet" href="https://unpkg.com/cropperjs@1.6.2/dist/cropper.min.css">
@include('partials.page-quick-links-styles')
<style>
  .mp-look .look-loading{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:12px;min-height:280px;padding:36px 20px;border:1px solid #E8DFEA;border-radius:16px;background:#fff;color:#6B6470;text-align:center}
  .mp-look .look-loading .ms{font-size:28px;color:#3E007C;animation:lookSpin 1s linear infinite}
  .mp-look .look-loading.is-error .ms{color:#B3261E;animation:none}
  .mp-look .look-loading b{display:block;font-size:15px;font-weight:700;color:#1A1A1A;margin-bottom:2px}
  .mp-look .look-loading span{font-size:13px;line-height:1.45}
  .mp-look .look-loading .look-reload{margin-top:6px;display:inline-flex;align-items:center;gap:6px;border:0;border-radius:10px;background:#3E007C;color:#fff;font:inherit;font-weight:600;font-size:13.5px;padding:10px 14px;cursor:pointer}
  @keyframes lookSpin{to{transform:rotate(360deg)}}
  .mp-look .mp-toast{position:fixed;top:22px;right:24px;background:#1A1A1A;color:#fff;padding:12px 18px;border-radius:10px;font-weight:600;font-size:14px;z-index:400;box-shadow:0 10px 30px rgba(0,0,0,.22);display:flex;gap:10px;align-items:center;opacity:0;transform:translateX(calc(100% + 40px));transition:opacity .25s,transform .35s;pointer-events:none}
  .mp-look .mp-toast.on{opacity:1;transform:none}
  .mp-look .mp-toast .ms{color:#3DD68C;font-size:20px}
  .mp-look .btn.lk-save{display:inline-flex;align-items:center;gap:8px;background:var(--ink);color:#fff;border-radius:10px;padding:10px 16px}
  .mp-look .btn.lk-save .ms{font-size:18px}
  .mp-look .btn.lk-save:disabled{cursor:default}
  .mp-look .mp-leave{position:fixed;inset:0;background:rgba(20,10,30,.45);display:none;align-items:center;justify-content:center;z-index:500;padding:16px}
  .mp-look .mp-leave.on{display:flex}
  .mp-look .mp-leave-box{background:#fff;border-radius:22px;width:420px;max-width:100%;box-shadow:0 20px 60px rgba(0,0,0,.25);overflow:hidden;position:relative}
  .mp-look .mp-leave-x{position:absolute;top:14px;right:14px;width:36px;height:36px;border:0;border-radius:10px;background:none;color:var(--muted);cursor:pointer;display:inline-flex;align-items:center;justify-content:center;padding:0}
  .mp-look .mp-leave-x:hover{background:#F7F3F9;color:var(--ink)}
  .mp-look .mp-leave-x .ms{font-size:22px}
  .mp-look .crop-ov{display:none;position:fixed;inset:0;z-index:600;background:rgba(0,0,0,.7);align-items:center;justify-content:center;padding:16px}
  .mp-look .crop-ov.open{display:flex}
  .mp-look .crop-box{width:100%;max-width:640px;background:#fff;border-radius:16px;padding:20px 22px;border:1px solid var(--line)}
  .mp-look .crop-box.tall{max-width:420px}
  .mp-look .crop-stage{width:100%;height:360px;background:#F7F2F8;border-radius:12px;overflow:hidden;position:relative}
  .mp-look .crop-banner-guide{display:none;position:absolute;z-index:20;pointer-events:none;overflow:hidden}
  .mp-look .crop-banner-covered{position:absolute;left:0;right:0;bottom:0;height:33.333%;background:repeating-linear-gradient(135deg,rgba(255,255,255,.24) 0 7px,transparent 7px 14px),rgba(17,17,17,.24)}
  .mp-look .crop-banner-safe{position:absolute;left:18.33%;top:18%;width:63.34%;height:50%;box-sizing:border-box;border:2px dashed rgba(255,255,255,.95);box-shadow:0 0 0 999px rgba(0,0,0,.16),inset 0 0 0 1px rgba(0,0,0,.25)}
  .mp-look .crop-box.tall .crop-stage{height:min(520px,70vh)}
  .mp-look .crop-actions{display:flex;justify-content:flex-end;gap:10px;margin-top:16px}
  @media (max-width:900px){.mp-look .mp-toast{left:16px;right:16px;top:74px}.mp-look .crop-stage{height:280px}.mp-look .crop-box.tall .crop-stage{height:min(420px,60vh)}}
</style>
@endsection

@section('content')
<div class="mp-look">
  <div class="head">
    <div>
      <h1>
        My Page
        <a class="help-q" href="https://help.inkjin.com/en/articles/17201367-dashboard-my-page-look-feel" target="_blank" rel="noopener" title="Help with this page" aria-label="Help with this page">
          <span class="ms">help</span>
        </a>
      </h1>
      <div class="sub">Everything clients see on your page.</div>
    </div>
    <div class="btns">
      @include('partials.page-quick-links', [
        'qlPublicUrl' => $publicPageUrl ?? '',
        'qlDisplayHost' => $publicPageHost ?? '',
        'qlRows' => $quickLinkRows ?? [],
        'qlButtonLabel' => 'Open my page',
        'qlButtonClass' => 'btn',
      ])
    </div>
  </div>

  @include('artist.partials.my-page-tabs', [
    'myPageTab' => 'appearance',
    'designsCount' => $designsCount ?? 0,
    'portfolioCount' => $portfolioCount ?? 0,
    'faqCount' => $faqCount ?? 0,
  ])

  <div id="lookfeel">
    <div class="look-loading" role="status" aria-live="polite">
      <span class="ms">progress_activity</span>
      <div>
        <b>Loading appearance</b>
        <span>Please wait a moment…</span>
      </div>
    </div>
  </div>
  <div class="mp-toast" id="mpToast" role="status"><span class="ms">check_circle</span><span id="mpToastText">Saved</span></div>

  <div class="mp-leave" id="mpLeave" role="dialog" aria-modal="true" aria-labelledby="mpLeaveTitle">
    <div class="mp-leave-box">
      <button type="button" class="mp-leave-x" id="mpLeaveClose" aria-label="Close"><span class="ms">close</span></button>
      <div style="padding:24px 24px 16px">
        <div style="width:48px;height:48px;border-radius:12px;background:#FFF3DC;color:#B7791F;display:flex;align-items:center;justify-content:center;margin-bottom:14px">
          <span class="ms">warning</span>
        </div>
        <h2 id="mpLeaveTitle" style="font-size:20px;font-weight:800;margin-bottom:8px;padding-right:28px">Your changes are not saved</h2>
        <p style="color:#3a3a3a;line-height:1.55;margin:0">Save them before you leave, or leave without saving and lose them.</p>
      </div>
      <div style="display:flex;gap:10px;padding:16px 24px;background:#FCF6FE;border-top:1px solid #F0E4F5;flex-wrap:wrap">
        <button type="button" class="btn ghost" id="mpLeaveDiscard" style="flex:1;justify-content:center">Leave without saving</button>
        <button type="button" class="btn" id="mpLeaveSave" style="flex:1;justify-content:center"><span class="ms">save</span>Save and leave</button>
      </div>
    </div>
  </div>

  <div id="lookCropperModal" class="crop-ov" role="dialog" aria-modal="true" aria-labelledby="lookCropTitle">
    <div class="crop-box" id="lookCropBox">
      <h3 id="lookCropTitle" style="font-size:17px;font-weight:800;margin-bottom:4px">Crop image</h3>
      <p class="sub" id="lookCropSub" style="margin-bottom:14px">Drag to reposition. Scroll or pinch to zoom.</p>
      <div class="crop-stage">
        <img id="lookCropImage" src="" alt="">
        <div id="lookCropBannerGuide" class="crop-banner-guide" aria-hidden="true">
          <div class="crop-banner-covered"></div>
          <div class="crop-banner-safe"></div>
        </div>
      </div>
      <div class="crop-actions">
        <button id="lookCancelCropBtn" type="button" class="btn ghost">Cancel</button>
        <button id="lookApplyCropBtn" type="button" class="btn">Use image</button>
      </div>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script src="https://unpkg.com/cropperjs@1.6.2/dist/cropper.min.js"></script>
@php($lookJsVer = file_exists(public_path('new-ui-design-assets/look.js')) ? (string) filemtime(public_path('new-ui-design-assets/look.js')) : '17')
<script src="{{ asset('new-ui-design-assets/look.js') }}?v={{ $lookJsVer }}"></script>
<script>
(function () {
  var cfg = @json($lookCfg, 15 | JSON_INVALID_UTF8_SUBSTITUTE);
  var lookState = @json($lookState, 15 | JSON_INVALID_UTF8_SUBSTITUTE);
  var saveUrl = @json(route('personal-page.appearance.update'));
  var uploadUrl = @json(route('personal-page.appearance.upload-image'));
  var logUrl = @json(route('personal-page.client-error'));
  var csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  var host = document.getElementById('lookfeel');
  var LOOK_JS_VERSION = @json($lookJsVer);

  function colorProbe(value) {
    var raw = String(value == null ? '' : value);
    var result = { raw: raw, ok: false, error: null, normalized: null };
    try {
      result.normalized = window.BPLook && BPLook.colorVal ? BPLook.colorVal(raw, '#000000') : raw;
      var probe = document.createElement('input');
      probe.type = 'color';
      probe.value = result.normalized || '#000000';
      result.ok = true;
    } catch (e) {
      result.error = (e && e.message) || String(e);
    }
    return result;
  }

  function reportLookError(payload) {
    payload = payload || {};
    var colors = payload.colors || {
      scheme: lookState && lookState.scheme,
      box: lookState && lookState.c && lookState.c.box,
      titleC: lookState && lookState.c && lookState.c.titleC,
      textC: lookState && lookState.c && lookState.c.textC,
      btnC: lookState && lookState.c && lookState.c.btnC,
      bgAvg: lookState && lookState.c && lookState.c.bgAvg
    };
    var body = {
      stage: payload.stage || 'unknown',
      message: payload.message || '',
      stack: payload.stack || '',
      url: location.href,
      look_js_version: LOOK_JS_VERSION,
      who: (cfg && cfg.who) || 'artist',
      mode: payload.mode || (lookState && lookState.mode) || '',
      scheme: payload.scheme || (colors && colors.scheme) || '',
      colors: colors,
      color_probe: payload.color_probe || {
        scheme0: colorProbe(String((colors && colors.scheme) || '').split(',')[0] || ''),
        box: colorProbe(colors && colors.box),
        titleC: colorProbe(colors && colors.titleC),
        textC: colorProbe(colors && colors.textC),
        btnC: colorProbe(colors && colors.btnC),
        bgAvg: colorProbe(colors && colors.bgAvg)
      },
      context: payload.context || {},
      user_agent: navigator.userAgent || ''
    };
    try { console.warn('[appearance-debug]', body); } catch (e) {}
    try {
      fetch(logUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': csrf,
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(body),
        keepalive: true
      }).catch(function () {});
    } catch (e) {}
  }

  cfg.onClientLog = reportLookError;

  function showLookLoadError(message) {
    if (!host) return;
    host.innerHTML =
      '<div class="look-loading is-error" role="alert">' +
        '<span class="ms">error</span>' +
        '<div><b>Couldn’t load appearance</b><span>' + (message || 'Please refresh and try again.') + '</span></div>' +
        '<button type="button" class="look-reload" onclick="location.reload()"><span class="ms">refresh</span>Reload page</button>' +
      '</div>';
  }

  if (!host) return;
  if (!window.BPLook) {
    reportLookError({ stage: 'bplook_missing', message: 'BPLook missing' });
    showLookLoadError('The appearance editor didn’t load. Check your connection, then reload.');
    return;
  }

  reportLookError({ stage: 'appearance_boot', message: 'editor boot', mode: lookState && lookState.mode });

  window.addEventListener('error', function (ev) {
    reportLookError({
      stage: 'window_error',
      message: (ev && ev.message) || 'window error',
      stack: (ev && ev.error && ev.error.stack) || '',
      context: { filename: ev && ev.filename, lineno: ev && ev.lineno, colno: ev && ev.colno }
    });
  });
  window.addEventListener('unhandledrejection', function (ev) {
    var reason = ev && ev.reason;
    reportLookError({
      stage: 'unhandledrejection',
      message: (reason && reason.message) || String(reason || 'rejection'),
      stack: reason && reason.stack
    });
  });

  var cropModal = document.getElementById('lookCropperModal');
  var cropBox = document.getElementById('lookCropBox');
  var cropImage = document.getElementById('lookCropImage');
  var cropBannerGuide = document.getElementById('lookCropBannerGuide');
  var cropTitle = document.getElementById('lookCropTitle');
  var cropSub = document.getElementById('lookCropSub');
  var cropper = null;
  var cropObjectUrl = '';
  var cropResolve = null;
  var cropReject = null;
  var cropKind = 'banner';

  var CROP_PRESETS = {
    banner: {
      title: 'Crop banner',
    sub: 'Use an image 1500 x 500 px or larger. Keep faces, logos and text in the middle. The bottom third is covered by your profile photo and the booking card.',
      aspect: 3,
      width: 1500,
      height: 500,
      tall: false
    },
    bgD: {
      title: 'Crop desktop background',
      sub: '16:9 · keep the subject away from the left and right edges.',
      aspect: 16 / 9,
      width: 1600,
      height: 900,
      tall: false
    },
    bgM: {
      title: 'Crop phone background',
      sub: '9:16 · keep the main subject in the upper third.',
      aspect: 9 / 16,
      width: 900,
      height: 1600,
      tall: true
    }
  };

  var LOOK_MAX_UPLOAD_BYTES = 1200 * 1024;

  function canvasToCompressedFile(canvas, kind, maxBytes) {
    maxBytes = maxBytes || LOOK_MAX_UPLOAD_BYTES;
    var qualities = [0.72, 0.6, 0.48, 0.36, 0.28];
    return new Promise(function (resolve, reject) {
      var lastBlob = null;
      function tryAt(i) {
        if (i >= qualities.length) {
          if (lastBlob) {
            resolve(new File([lastBlob], 'look-' + kind + '.jpg', { type: 'image/jpeg' }));
            return;
          }
          reject(new Error('Could not compress this image. Try a smaller photo.'));
          return;
        }
        canvas.toBlob(function (blob) {
          if (!blob) {
            reject(new Error('Could not crop this image. Try another one.'));
            return;
          }
          lastBlob = blob;
          if (blob.size <= maxBytes || i === qualities.length - 1) {
            resolve(new File([blob], 'look-' + kind + '.jpg', { type: 'image/jpeg' }));
            return;
          }
          tryAt(i + 1);
        }, 'image/jpeg', qualities[i]);
      }
      tryAt(0);
    });
  }

  function recompressBlob(blob, kind, maxW, maxH) {
    if (!blob || blob.size <= LOOK_MAX_UPLOAD_BYTES) return Promise.resolve(blob);
    return createImageBitmap(blob).then(function (bmp) {
      var scale = Math.min(1, maxW / bmp.width, maxH / bmp.height);
      var c = document.createElement('canvas');
      c.width = Math.max(1, Math.round(bmp.width * scale));
      c.height = Math.max(1, Math.round(bmp.height * scale));
      c.getContext('2d').drawImage(bmp, 0, 0, c.width, c.height);
      try { bmp.close(); } catch (e) {}
      return canvasToCompressedFile(c, kind);
    }).catch(function () { return blob; });
  }

  function cleanupCropperUi() {
    if (cropper) {
      cropper.destroy();
      cropper = null;
    }
    if (cropModal) cropModal.classList.remove('open');
    if (cropBannerGuide) cropBannerGuide.style.display = 'none';
    if (cropImage) {
      cropImage.onload = null;
      cropImage.src = '';
    }
    if (cropObjectUrl) {
      URL.revokeObjectURL(cropObjectUrl);
      cropObjectUrl = '';
    }
    document.body.style.overflow = '';
  }

  function syncCropBannerGuide() {
    if (!cropper || !cropBannerGuide || cropKind !== 'banner') {
      if (cropBannerGuide) cropBannerGuide.style.display = 'none';
      return;
    }

    var box = cropper.getCropBoxData();
    cropBannerGuide.style.left = box.left + 'px';
    cropBannerGuide.style.top = box.top + 'px';
    cropBannerGuide.style.width = box.width + 'px';
    cropBannerGuide.style.height = box.height + 'px';
    cropBannerGuide.style.display = 'block';
  }

  function finishCrop(result, isError) {
    var resolve = cropResolve;
    var reject = cropReject;
    cropResolve = null;
    cropReject = null;
    cleanupCropperUi();
    if (isError) {
      if (reject) reject(result instanceof Error ? result : new Error(String(result || 'Could not crop this image.')));
      return;
    }
    if (resolve) resolve(result || null);
  }

  function openCropper(file, kind) {
    return new Promise(function (resolve, reject) {
      var preset = CROP_PRESETS[kind] || CROP_PRESETS.bgD;
      if (!file || typeof Cropper === 'undefined' || !cropModal || !cropImage) {
        resolve(file);
        return;
      }
      if (!/^image\/(jpeg|png|webp)$/i.test(file.type || '')) {
        reject(new Error('Use a JPG, PNG or WebP image.'));
        return;
      }
      if (cropResolve) finishCrop(null, false);

      cropResolve = resolve;
      cropReject = reject;
      cropKind = kind;
      cropTitle.textContent = preset.title;
      cropSub.textContent = preset.sub;
      cropBox.classList.toggle('tall', !!preset.tall);

      if (cropObjectUrl) URL.revokeObjectURL(cropObjectUrl);
      cropObjectUrl = URL.createObjectURL(file);
      cropImage.onload = function () {
        if (kind === 'banner' && (cropImage.naturalWidth < 1500 || cropImage.naturalHeight < 500)) {
          finishCrop(new Error('This image is ' + cropImage.naturalWidth + ' × ' + cropImage.naturalHeight + ' px. Use an image at least 1500 × 500 px.'), true);
          return;
        }
        if (cropper) cropper.destroy();
        cropper = new Cropper(cropImage, {
          aspectRatio: preset.aspect,
          viewMode: 1,
          dragMode: 'move',
          background: false,
          autoCropArea: 1,
          responsive: true,
          movable: true,
          zoomable: true,
          scalable: false,
          rotatable: false,
          ready: syncCropBannerGuide,
          crop: syncCropBannerGuide
        });
        setTimeout(syncCropBannerGuide, 0);
      };
      cropImage.src = cropObjectUrl;
      cropModal.classList.add('open');
      document.body.style.overflow = 'hidden';
    });
  }

  document.getElementById('lookCancelCropBtn')?.addEventListener('click', function () {
    finishCrop(null, false);
  });
  cropModal?.addEventListener('click', function (e) {
    if (e.target === cropModal) finishCrop(null, false);
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && cropModal && cropModal.classList.contains('open')) {
      e.preventDefault();
      finishCrop(null, false);
    }
  });
  document.getElementById('lookApplyCropBtn')?.addEventListener('click', function () {
    if (!cropper || !cropResolve) return;
    var preset = CROP_PRESETS[cropKind] || CROP_PRESETS.bgD;
    var btn = this;
    btn.disabled = true;
    var canvas = cropper.getCroppedCanvas({
      width: preset.width,
      height: preset.height,
      maxWidth: 4096,
      maxHeight: 4096,
      imageSmoothingQuality: 'medium',
      fillColor: '#ffffff'
    });
    if (!canvas) {
      btn.disabled = false;
      finishCrop(new Error('Could not crop this image. Try another one.'), true);
      return;
    }
    canvasToCompressedFile(canvas, cropKind).then(function (file) {
      btn.disabled = false;
      finishCrop(file, false);
    }).catch(function (err) {
      btn.disabled = false;
      finishCrop(err instanceof Error ? err : new Error('Could not crop this image. Try another one.'), true);
    });
  });

  try { BPLook.save(cfg.who, lookState); } catch (e) {}

  cfg.cropImage = openCropper;
  cfg.showSaveStatus = false;

  var dirty = false;
  var pendingNav = null;
  var pendingReload = false;
  var leaveAfterSave = false;
  var leaveEl = document.getElementById('mpLeave');

  function setDirty(v) { dirty = !!v; }
  function runPendingLeave() {
    if (pendingReload) { pendingReload = false; window.location.reload(); return; }
    var n = pendingNav; pendingNav = null; if (n) n.click();
  }
  function showLeaveModal(opts) {
    opts = opts || {};
    pendingNav = opts.nav || null;
    pendingReload = !!opts.reload;
    leaveEl?.classList.add('on');
  }
  function dismissLeaveModal() {
    leaveEl?.classList.remove('on');
    pendingNav = null;
    pendingReload = false;
    leaveAfterSave = false;
  }

  cfg.onDirty = function (d) { setDirty(d); };

  function httpFailMessage(status) {
    if (status === 413 || status === 502) {
      return 'Upload was blocked (file still too large after compress). Try a smaller photo, or refresh this page and try again.';
    }
    if (!status) return 'Upload failed (network). Check your connection and try again.';
    return 'Save failed (HTTP ' + status + '). Please try again.';
  }

  function parseJsonResponse(r, meta) {
    meta = meta || {};
    return r.text().then(function (text) {
      var j = null;
      if (text) {
        try { j = JSON.parse(text); } catch (e) {
          var err = new Error(httpFailMessage(r.status));
          err.status = r.status;
          err.responseSnippet = String(text).slice(0, 500);
          err.uploadMeta = meta;
          throw err;
        }
      } else {
        var emptyErr = new Error(httpFailMessage(r.status));
        emptyErr.status = r.status;
        emptyErr.responseSnippet = '';
        emptyErr.uploadMeta = meta;
        throw emptyErr;
      }
      return { r: r, j: j, text: text };
    });
  }

  function dataUrlToBlob(dataUrl) {
    return fetch(dataUrl).then(function (res) { return res.blob(); });
  }

  function uploadLookImage(kind, blob) {
    var type = (blob && blob.type) || 'image/jpeg';
    var ext = type.indexOf('png') >= 0 ? 'png' : (type.indexOf('webp') >= 0 ? 'webp' : 'jpg');
    var meta = { kind: kind, bytes: blob && blob.size, type: type };
    var fd = new FormData();
    fd.append('kind', kind);
    fd.append('image', blob, 'look-' + kind + '.' + ext);
    return fetch(uploadUrl, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrf,
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: fd
    }).then(function (r) { return parseJsonResponse(r, meta); }).then(function (pack) {
      if (!pack.r.ok || !pack.j || !pack.j.success) {
        var fail = new Error((pack.j && pack.j.message) || 'Could not upload image.');
        fail.status = pack.r.status;
        fail.responseSnippet = String(pack.text || '').slice(0, 500);
        fail.uploadMeta = meta;
        throw fail;
      }
      return pack.j.path || pack.j.url;
    }).catch(function (err) {
      reportLookError({
        stage: 'upload_image',
        status: err && err.status,
        message: (err && err.message) || 'upload failed',
        response_snippet: err && err.responseSnippet,
        banner_blob_size: meta.bytes,
        banner_blob_type: meta.type,
        context: meta
      });
      throw err;
    });
  }

  function prepareStateImages(state) {
    var next = JSON.parse(JSON.stringify(state));
    var files = cfg._lookFiles || {};
    var jobs = [];
    var dims = { banner: [1500, 500], bgD: [1600, 900], bgM: [900, 1600] };

    function queue(kind, current, file) {
      var needs = !!file || (typeof current === 'string' && current.indexOf('data:') === 0);
      if (!needs) return;
      var wh = dims[kind] || [1600, 900];
      jobs.push(
        Promise.resolve(file || null)
          .then(function (blob) {
            if (blob) return blob;
            return dataUrlToBlob(current);
          })
          .then(function (blob) { return recompressBlob(blob, kind, wh[0], wh[1]); })
          .then(function (blob) { return uploadLookImage(kind, blob); })
          .then(function (url) {
            if (kind === 'banner') next.banner = url;
            else {
              next.c = next.c || {};
              next.c[kind] = url;
            }
            if (cfg._lookFiles) delete cfg._lookFiles[kind];
            if (kind === 'banner') cfg.bannerFile = null;
          })
      );
    }

    queue('banner', next.banner, files.banner || null);
    queue('bgD', next.c && next.c.bgD, files.bgD || null);
    queue('bgM', next.c && next.c.bgM, files.bgM || null);

    return Promise.all(jobs).then(function () { return next; });
  }

  cfg.onSave = function (state) {
    return prepareStateImages(state).then(function (prepared) {
      state = prepared;
      return fetch(saveUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': csrf,
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(prepared)
      });
    }).then(parseJsonResponse).then(function (pack) {
      var j = pack.j;
      if (!pack.r.ok || !j || !j.success) throw new Error((j && j.message) || 'Could not save');
      if (j.look) {
        try { BPLook.save(cfg.who, j.look); } catch (x) {}
        return j.look;
      }
      return state;
    }).then(function (result) {
      if (leaveAfterSave) {
        leaveAfterSave = false;
        setDirty(false);
        setTimeout(runPendingLeave, 0);
      }
      return result;
    }).catch(function (err) {
      leaveAfterSave = false;
      reportLookError({
        stage: 'save',
        status: err && err.status,
        message: (err && err.message) || 'save failed',
        stack: err && err.stack,
        response_snippet: err && err.responseSnippet,
        banner_blob_size: err && err.uploadMeta && err.uploadMeta.bytes,
        banner_blob_type: err && err.uploadMeta && err.uploadMeta.type,
        mode: state && state.mode,
        scheme: state && state.scheme,
        colors: state && state.c ? {
          scheme: state.scheme,
          box: state.c.box,
          titleC: state.c.titleC,
          textC: state.c.textC,
          btnC: state.c.btnC,
          bgAvg: state.c.bgAvg
        } : null,
        context: err && err.uploadMeta ? err.uploadMeta : null
      });
      throw err;
    });
  };

  try {
    BPLook.editor(host, cfg);
  } catch (err) {
    reportLookError({
      stage: 'editor_init',
      message: (err && err.message) || 'editor init failed',
      stack: err && err.stack,
      mode: lookState && lookState.mode
    });
    showLookLoadError((err && err.message) || 'Something went wrong while opening the editor.');
    return;
  }

  var toastEl = document.getElementById('mpToast');
  var toastText = document.getElementById('mpToastText');
  function toast(msg) {
    if (!toastEl) return;
    toastText.textContent = msg || 'Saved';
    toastEl.classList.add('on');
    clearTimeout(toastEl._t);
    toastEl._t = setTimeout(function () { toastEl.classList.remove('on'); }, 2600);
  }

  document.getElementById('mpLeaveClose')?.addEventListener('click', dismissLeaveModal);
  leaveEl?.addEventListener('click', function (e) {
    if (e.target === leaveEl) dismissLeaveModal();
  });
  document.getElementById('mpLeaveDiscard')?.addEventListener('click', function () {
    leaveEl.classList.remove('on');
    setDirty(false);
    runPendingLeave();
  });
  document.getElementById('mpLeaveSave')?.addEventListener('click', function () {
    leaveEl.classList.remove('on');
    leaveAfterSave = true;
    var btn = host.querySelector('.lk-save');
    if (btn && !btn.disabled) {
      btn.click();
    } else {
      leaveAfterSave = false;
      runPendingLeave();
    }
  });

  document.addEventListener('click', function (e) {
    if (!dirty) return;
    var n = e.target.closest('aside a[href], .tabs a[href], a[href]:not([href="#"])');
    if (!n || n.closest('#mpLeave') || n.closest('#quickLinks') || n.closest('#lookfeel')) return;
    if (n.getAttribute('target') === '_blank') return;
    if (n.closest('.head')) return;
    e.preventDefault();
    e.stopPropagation();
    showLeaveModal({ nav: n });
  }, true);

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && leaveEl?.classList.contains('on')) {
      e.preventDefault();
      dismissLeaveModal();
      return;
    }
    if (!dirty) return;
    var isReloadKey = e.key === 'F5' || ((e.ctrlKey || e.metaKey) && (e.key === 'r' || e.key === 'R'));
    if (!isReloadKey) return;
    e.preventDefault();
    showLeaveModal({ reload: true });
  }, true);
})();
</script>
@include('partials.page-quick-links-script')
@endsection
