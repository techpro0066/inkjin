@extends('layouts.studio-dashboard-layout')

@section('title', 'Studio Page · Appearance')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/cropperjs@1.6.2/dist/cropper.min.css">
@include('partials.page-quick-links-styles')
<style>
  .studio-page .btns{display:flex;gap:10px;flex-wrap:wrap;align-items:center}
  .studio-page .btn.lk-save{display:inline-flex;align-items:center;gap:8px;background:var(--ink);color:#fff;border-radius:10px;padding:10px 16px}
  .studio-page .btn.lk-save .ms{font-size:18px}
  .studio-page .btn.lk-save:disabled{cursor:default}
  .studio-page .sp-toast{position:fixed;top:22px;right:24px;background:#1A1A1A;color:#fff;padding:12px 18px;border-radius:10px;font-weight:600;font-size:14px;z-index:400;box-shadow:0 10px 30px rgba(0,0,0,.22);display:flex;gap:10px;align-items:center;opacity:0;transform:translateX(calc(100% + 40px));transition:opacity .25s,transform .35s;pointer-events:none}
  .studio-page .sp-toast.on{opacity:1;transform:none}
  .studio-page .sp-toast .ms{color:#3DD68C;font-size:20px}
  .studio-page .sp-leave{position:fixed;inset:0;background:rgba(20,10,30,.45);display:none;align-items:center;justify-content:center;z-index:500;padding:16px}
  .studio-page .sp-leave.on{display:flex}
  .studio-page .sp-leave-box{background:#fff;border-radius:22px;width:420px;max-width:100%;box-shadow:0 20px 60px rgba(0,0,0,.25);overflow:hidden;position:relative}
  .studio-page .sp-leave-x{position:absolute;top:14px;right:14px;width:36px;height:36px;border:0;border-radius:10px;background:none;color:var(--muted);cursor:pointer;display:inline-flex;align-items:center;justify-content:center;padding:0}
  .studio-page .sp-leave-x:hover{background:#F7F3F9;color:var(--ink)}
  .studio-page .sp-leave-x .ms{font-size:22px}
  .studio-page .crop-ov{display:none;position:fixed;inset:0;z-index:600;background:rgba(0,0,0,.7);align-items:center;justify-content:center;padding:16px}
  .studio-page .crop-ov.open{display:flex}
  .studio-page .crop-box{width:100%;max-width:640px;background:#fff;border-radius:16px;padding:20px 22px;border:1px solid var(--line)}
  .studio-page .crop-box.tall{max-width:420px}
  .studio-page .crop-stage{width:100%;height:360px;background:#F7F2F8;border-radius:12px;overflow:hidden}
  .studio-page .crop-box.tall .crop-stage{height:min(520px,70vh)}
  .studio-page .crop-actions{display:flex;justify-content:flex-end;gap:10px;margin-top:16px}
  @media (max-width:900px){.studio-page .sp-toast{left:16px;right:16px;top:74px}.studio-page .crop-stage{height:280px}.studio-page .crop-box.tall .crop-stage{height:min(420px,60vh)}}
  @media (max-width:700px){.studio-page .head .btns{width:100%}}
</style>
@endpush

@section('content')
@php
  $pageUrl = $pageUrl ?? 'inkjin.com/@username';
  $publicPageUrl = $publicPageUrl ?? ('https://'.$pageUrl);
  $quickLinkRows = $quickLinkRows ?? [
    ['suffix' => '', 'hash' => ''],
    ['suffix' => '/artists', 'hash' => '#artists'],
    ['suffix' => '/portfolio', 'hash' => '#work'],
    ['suffix' => '/guest-artists', 'hash' => '#guest-artists'],
    ['suffix' => '/find-us', 'hash' => '#findus'],
  ];
  $lookCfg = $lookCfg ?? ['who' => 'studio', 'ownSave' => true, 'name' => 'Studio', 'pageUrl' => $publicPageUrl];
  $lookState = $lookState ?? ['mode' => 'standard', 'scheme' => \App\Support\PageLook::DEFAULT_SCHEME, 'banner' => null, 'c' => []];
@endphp
<div class="studio-page">
  <div class="head">
    <div>
      <h1>
        Studio Page
        <a class="help-q" href="https://help.inkjin.com/en/articles/17338843-studio-dashboard-studio-page-appearance" target="_blank" rel="noopener" title="Help with this page" aria-label="Help with this page"><span class="ms">help</span></a>
      </h1>
      <div class="sub">What clients see at {{ $pageUrl }}.</div>
    </div>
    <div class="btns">
      @include('partials.page-quick-links', [
        'qlPublicUrl' => $publicPageUrl,
        'qlDisplayHost' => $pageUrl,
        'qlRows' => $quickLinkRows,
        'qlButtonLabel' => 'Open studio page',
        'qlButtonClass' => 'btn',
        'qlFallbackUrl' => route('studio.page.about'),
      ])
    </div>
  </div>

  @include('studio.page.partials.tabs', ['pageTab' => 'look'])

  <div id="lookfeel"></div>
  <div class="sp-toast" id="spToast" role="status"><span class="ms">check_circle</span><span id="spToastText">Saved</span></div>

  <div class="sp-leave" id="spLeave" role="dialog" aria-modal="true" aria-labelledby="spLeaveTitle">
    <div class="sp-leave-box">
      <button type="button" class="sp-leave-x" id="spLeaveClose" aria-label="Close"><span class="ms">close</span></button>
      <div style="padding:24px 24px 16px">
        <div style="width:48px;height:48px;border-radius:12px;background:#FFF3DC;color:#B7791F;display:flex;align-items:center;justify-content:center;margin-bottom:14px">
          <span class="ms">warning</span>
        </div>
        <h2 id="spLeaveTitle" style="font-size:20px;font-weight:800;margin-bottom:8px;padding-right:28px">Your changes are not saved</h2>
        <p style="color:#3a3a3a;line-height:1.55;margin:0">Save them before you leave, or leave without saving and lose them.</p>
      </div>
      <div style="display:flex;gap:10px;padding:16px 24px;background:#FCF6FE;border-top:1px solid #F0E4F5;flex-wrap:wrap">
        <button type="button" class="btn ghost" id="spLeaveDiscard" style="flex:1;justify-content:center">Leave without saving</button>
        <button type="button" class="btn" id="spLeaveSave" style="flex:1;justify-content:center"><span class="ms">save</span>Save and leave</button>
      </div>
    </div>
  </div>

  <div id="lookCropperModal" class="crop-ov" role="dialog" aria-modal="true" aria-labelledby="lookCropTitle">
    <div class="crop-box" id="lookCropBox">
      <h3 id="lookCropTitle" style="font-size:17px;font-weight:800;margin-bottom:4px">Crop image</h3>
      <p class="sub" id="lookCropSub" style="margin-bottom:14px">Drag to reposition. Scroll or pinch to zoom.</p>
      <div class="crop-stage">
        <img id="lookCropImage" src="" alt="">
      </div>
      <div class="crop-actions">
        <button id="lookCancelCropBtn" type="button" class="btn ghost">Cancel</button>
        <button id="lookApplyCropBtn" type="button" class="btn">Use image</button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/cropperjs@1.6.2/dist/cropper.min.js"></script>
<script src="{{ asset('new-ui-design-assets/look.js') }}"></script>
<script>
(function () {
  var cfg = @json($lookCfg);
  var lookState = @json($lookState);
  var saveUrl = @json($updateLookUrl ?? route('studio.page.look.update'));
  var csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  var host = document.getElementById('lookfeel');
  if (!host || !window.BPLook) return;

  var cropModal = document.getElementById('lookCropperModal');
  var cropBox = document.getElementById('lookCropBox');
  var cropImage = document.getElementById('lookCropImage');
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
      sub: 'Recommended 1600 × 230 · keep the main subject near the centre.',
      aspect: 1600 / 230,
      width: 1600,
      height: 230,
      tall: false
    },
    bgD: {
      title: 'Crop desktop background',
      sub: '16:9 · keep the subject away from the left and right edges.',
      aspect: 16 / 9,
      width: 1920,
      height: 1080,
      tall: false
    },
    bgM: {
      title: 'Crop phone background',
      sub: '9:16 · keep the main subject in the upper third.',
      aspect: 9 / 16,
      width: 1080,
      height: 1920,
      tall: true
    }
  };

  function cleanupCropperUi() {
    if (cropper) { cropper.destroy(); cropper = null; }
    if (cropModal) cropModal.classList.remove('open');
    if (cropImage) { cropImage.onload = null; cropImage.src = ''; }
    if (cropObjectUrl) { URL.revokeObjectURL(cropObjectUrl); cropObjectUrl = ''; }
    document.body.style.overflow = '';
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
          rotatable: false
        });
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
      imageSmoothingQuality: 'high',
      fillColor: '#ffffff'
    });
    if (!canvas) {
      btn.disabled = false;
      finishCrop(new Error('Could not crop this image. Try another one.'), true);
      return;
    }
    canvas.toBlob(function (blob) {
      btn.disabled = false;
      if (!blob) {
        finishCrop(new Error('Could not crop this image. Try another one.'), true);
        return;
      }
      finishCrop(new File([blob], 'look-' + cropKind + '.jpg', { type: 'image/jpeg' }), false);
    }, 'image/jpeg', 0.9);
  });

  try { BPLook.save(cfg.who, lookState); } catch (e) {}

  cfg.cropImage = openCropper;
  cfg.showSaveStatus = false;

  var dirty = false;
  var pendingNav = null;
  var pendingReload = false;
  var leaveAfterSave = false;
  var leaveEl = document.getElementById('spLeave');

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

  cfg.onSave = function (state) {
    return fetch(saveUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrf,
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify(state)
    }).then(function (r) {
      return r.json().then(function (j) {
        if (!r.ok || !j.success) throw new Error(j.message || 'Could not save');
        if (j.look) {
          try { BPLook.save(cfg.who, j.look); } catch (x) {}
          return j.look;
        }
        return state;
      });
    }).then(function (result) {
      if (leaveAfterSave) {
        leaveAfterSave = false;
        setDirty(false);
        setTimeout(runPendingLeave, 0);
      }
      return result;
    }).catch(function (err) {
      leaveAfterSave = false;
      throw err;
    });
  };

  BPLook.editor(host, cfg);

  var toastEl = document.getElementById('spToast');
  var toastText = document.getElementById('spToastText');
  function toast(msg) {
    if (!toastEl) return;
    toastText.textContent = msg || 'Saved';
    toastEl.classList.add('on');
    clearTimeout(toastEl._t);
    toastEl._t = setTimeout(function () { toastEl.classList.remove('on'); }, 2600);
  }

  document.getElementById('spLeaveClose')?.addEventListener('click', dismissLeaveModal);
  leaveEl?.addEventListener('click', function (e) {
    if (e.target === leaveEl) dismissLeaveModal();
  });
  document.getElementById('spLeaveDiscard')?.addEventListener('click', function () {
    leaveEl.classList.remove('on');
    setDirty(false);
    runPendingLeave();
  });
  document.getElementById('spLeaveSave')?.addEventListener('click', function () {
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
    if (!n || n.closest('#spLeave') || n.closest('#quickLinks') || n.closest('#lookfeel')) return;
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
@endpush
