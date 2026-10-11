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
      'pageUrl' => $publicPageUrl,
  ];
@endphp

@section('styles')
<link rel="stylesheet" href="https://unpkg.com/cropperjs@1.6.2/dist/cropper.min.css">
<style>
  .mp-look .ql{position:relative}
  .mp-look .ql-b{display:inline-flex;align-items:center;gap:8px;font:inherit;font-weight:600;font-size:13.5px;padding:9px 12px 9px 13px;border-radius:12px;border:1px solid var(--line);background:#fff;color:var(--ink);cursor:pointer;white-space:nowrap}
  .mp-look .ql-b:hover{background:#F7F3F9}
  .mp-look .ql-b .ms{font-size:18px}.mp-look .ql-b .cv{font-size:20px}
  .mp-look .ql-p{position:absolute;right:0;top:calc(100% + 8px);width:min(400px,calc(100vw - 32px));background:#fff;border:1px solid #EAE2EE;border-radius:16px;box-shadow:0 18px 44px rgba(20,0,40,.18);padding:6px;z-index:80}
  .mp-look .ql-p[hidden]{display:none}
  .mp-look .ql-r{display:flex;align-items:center;gap:6px;padding:4px 4px 4px 12px;border-radius:10px}
  .mp-look .ql-r:hover{background:#F7F3F9}
  .mp-look .ql-u{flex:1;min-width:0;font-size:13.5px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
  .mp-look .ql-u b{font-weight:700}
  .mp-look .ql-i{width:34px;height:34px;border-radius:9px;border:0;background:none;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;color:var(--ink);flex-shrink:0;text-decoration:none}
  .mp-look .ql-i:hover{background:#EFE7F3}
  .mp-look .ql-i .ms{font-size:18px}.mp-look .ql-i.done .ms{color:#1F8A4C}
  .mp-look .mp-toast{position:fixed;top:22px;right:24px;background:#1A1A1A;color:#fff;padding:12px 18px;border-radius:10px;font-weight:600;font-size:14px;z-index:400;box-shadow:0 10px 30px rgba(0,0,0,.22);display:flex;gap:10px;align-items:center;opacity:0;transform:translateX(calc(100% + 40px));transition:opacity .25s,transform .35s;pointer-events:none}
  .mp-look .mp-toast.on{opacity:1;transform:none}
  .mp-look .mp-toast .ms{color:#3DD68C;font-size:20px}
  .mp-look .btn.lk-save{display:inline-flex;align-items:center;gap:8px;background:var(--ink);color:#fff;border-radius:10px;padding:10px 16px}
  .mp-look .btn.lk-save .ms{font-size:18px}
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
  .mp-look .crop-stage{width:100%;height:360px;background:#F7F2F8;border-radius:12px;overflow:hidden}
  .mp-look .crop-box.tall .crop-stage{height:min(520px,70vh)}
  .mp-look .crop-actions{display:flex;justify-content:flex-end;gap:10px;margin-top:16px}
  @media (max-width:900px){.mp-look .mp-toast{left:16px;right:16px;top:74px}.mp-look .ql-p{right:auto;left:0}.mp-look .crop-stage{height:280px}.mp-look .crop-box.tall .crop-stage{height:min(420px,60vh)}}
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
      @if ($username !== '')
        <div class="ql" id="quickLinks">
          <button type="button" class="ql-b" id="quickLinksBtn" aria-haspopup="true" aria-expanded="false" aria-controls="quickLinksMenu">
            <span class="ms">link</span>Quick links<span class="ms cv">expand_more</span>
          </button>
          <div class="ql-p" id="quickLinksMenu" hidden role="menu" aria-label="Quick links">
            @foreach ($quickLinkRows as $row)
              <div class="ql-r" role="none">
                <div class="ql-u" title="{{ $row['label'] }}">
                  {{ $publicPageHost }}@if($row['suffix'] !== '')<b>{{ $row['suffix'] }}</b>@endif
                </div>
                <button type="button" class="ql-i" data-ql-copy="{{ $publicPageUrl.$row['suffix'] }}" data-ql-label="{{ $row['label'] }}" role="menuitem" title="Copy link">
                  <span class="ms">content_copy</span>
                </button>
                <a class="ql-i" href="{{ $publicPageUrl.$row['suffix'] }}" target="_blank" rel="noopener" role="menuitem" title="Open">
                  <span class="ms">open_in_new</span>
                </a>
              </div>
            @endforeach
          </div>
        </div>
        <a class="btn" href="{{ $publicPageUrl }}" target="_blank" rel="noopener">
          <span class="ms">open_in_new</span>Open my page
        </a>
      @endif
    </div>
  </div>

  @include('artist.partials.my-page-tabs', [
    'myPageTab' => 'appearance',
    'designsCount' => $designsCount ?? 0,
    'portfolioCount' => $portfolioCount ?? 0,
    'faqCount' => $faqCount ?? 0,
  ])

  <div id="lookfeel"></div>
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
<script src="{{ asset('new-ui-design-assets/look.js') }}"></script>
<script>
(function () {
  var cfg = @json($lookCfg);
  var lookState = @json($lookState);
  var saveUrl = @json(route('personal-page.appearance.update'));
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
      sub: 'Recommended 3:1 · keep the main subject near the centre.',
      aspect: 3 / 1,
      width: 1600,
      height: 534,
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
    if (cropper) {
      cropper.destroy();
      cropper = null;
    }
    if (cropModal) cropModal.classList.remove('open');
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

  var toastEl = document.getElementById('mpToast');
  var toastText = document.getElementById('mpToastText');
  function toast(msg) {
    if (!toastEl) return;
    toastText.textContent = msg || 'Saved';
    toastEl.classList.add('on');
    clearTimeout(toastEl._t);
    toastEl._t = setTimeout(function () { toastEl.classList.remove('on'); }, 2600);
  }

  var ql = document.getElementById('quickLinks');
  var qlBtn = document.getElementById('quickLinksBtn');
  var qlMenu = document.getElementById('quickLinksMenu');
  if (ql && qlBtn && qlMenu) {
    function openQl(show) {
      qlMenu.hidden = !show;
      qlBtn.setAttribute('aria-expanded', show ? 'true' : 'false');
      var cv = qlBtn.querySelector('.cv');
      if (cv) cv.textContent = show ? 'expand_less' : 'expand_more';
    }
    qlBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      openQl(qlMenu.hidden);
    });
    qlMenu.addEventListener('click', function (e) {
      e.stopPropagation();
      var btn = e.target.closest('[data-ql-copy]');
      if (!btn) return;
      var url = btn.getAttribute('data-ql-copy') || '';
      try { navigator.clipboard.writeText(url); } catch (x) {}
      btn.classList.add('done');
      var icon = btn.querySelector('.ms');
      if (icon) icon.textContent = 'check';
      setTimeout(function () {
        btn.classList.remove('done');
        if (icon) icon.textContent = 'content_copy';
      }, 1600);
      toast('Link copied: ' + (btn.getAttribute('data-ql-label') || url));
    });
    document.addEventListener('click', function (e) {
      if (!qlMenu.hidden && !ql.contains(e.target)) openQl(false);
    });
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
@endsection
