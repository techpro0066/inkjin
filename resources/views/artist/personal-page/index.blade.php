@extends('layouts.new-artist-dashboard-layout')

@section('title', 'My Page')

@php
  $userDetail = $userDetail ?? $user->userDetail;
  $myPageTab = $myPageTab ?? 'about';
  $styleOptions = $styleOptions ?? [];
  $ts = is_array($userDetail->tattoo_styles ?? null) ? $userDetail->tattoo_styles : [];
  $since = $ts['tattooing_since'] ?? null;
  $primary = $ts['primary_style'] ?? null;
  $otherList = [];
  if (isset($ts['other_styles']) && is_array($ts['other_styles'])) {
    $otherList = array_values(array_filter(array_map('strval', $ts['other_styles'])));
  } elseif (array_is_list($ts)) {
    $otherList = $ts;
  }
  if ($primary && ! array_key_exists($primary, $styleOptions)) {
    $styleOptions[$primary] = ucwords(str_replace('-', ' ', (string) $primary));
  }
  foreach ($otherList as $os) {
    if ($os !== '' && ! array_key_exists($os, $styleOptions)) {
      $styleOptions[$os] = ucwords(str_replace('-', ' ', (string) $os));
    }
  }
  $extraServices = is_array($userDetail->extra_services ?? null) ? $userDetail->extra_services : [];
  $extraServiceList = is_array($extraServices['services'] ?? null) ? $extraServices['services'] : [];
  $extraServiceOther = trim((string) ($extraServices['other'] ?? ''));
  $sl = is_array($userDetail->social_links ?? null) ? $userDetail->social_links : [];
  $selectedAlias = in_array($userDetail->personal_page_name_alias, ['username', 'display_name'], true)
      ? $userDetail->personal_page_name_alias
      : 'username';
  $displayTagline = (bool) ($userDetail->display_tagline ?? false);
  $displayBio = (bool) ($userDetail->display_bio ?? false);
  $tagline = (string) ($userDetail->personal_page_tagline ?? '');
  $bio = (string) ($userDetail->personal_page_description ?? '');
  $username = trim((string) ($userDetail->user_name ?? ''));
  $displayName = trim((string) ($userDetail->display_name ?? ''));
  $firstName = (string) ($user->first_name ?? '');
  $lastName = (string) ($user->last_name ?? '');
  $avatarPath = trim((string) ($userDetail->avatar ?? ''));
  $avatarUrl = $avatarPath !== ''
      ? (str_starts_with($avatarPath, 'http') ? $avatarPath : asset(ltrim($avatarPath, '/')))
      : null;
  $publicPageUrl = $username !== '' ? url('/@'.$username) : null;
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
  $previewMeta = trim(implode(' · ', array_filter([
    $username !== '' ? '@'.$username : null,
    $studioName !== '' ? $studioName : null,
    $city !== '' ? $city : null,
  ])));
  $whatsIncluded = $userDetail->activeDesignWhatsIncludedItems();
  $whatsIncludedText = $whatsIncluded !== []
      ? implode(' · ', $whatsIncluded)
      : 'Sizing consultation · Stencil preview · Aftercare';
  $primaryLabel = $primary && isset($styleOptions[$primary])
      ? $styleOptions[$primary]
      : ($primary ? ucwords(str_replace('-', ' ', (string) $primary)) : '');
  $styleAllJson = collect($styleOptions)->map(fn ($lab, $val) => ['v' => $val, 'l' => $lab])->values()->toJson();
  $otherJson = json_encode(array_values($otherList));
@endphp

@section('styles')
<link rel="stylesheet" href="https://unpkg.com/cropperjs@1.6.2/dist/cropper.min.css">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<style>
  .mp-page .tog{width:38px;height:22px;border-radius:12px;background:#D9D2DD;position:relative;flex-shrink:0;cursor:pointer}
  .mp-page .tog:after{content:'';position:absolute;width:16px;height:16px;border-radius:50%;background:#fff;top:3px;left:3px;transition:left .15s}
  .mp-page .tog.on{background:var(--pri)}.mp-page .tog.on:after{left:19px}
  .mp-page .btn.pgsave{opacity:.4;pointer-events:none}
  .mp-page .btn.pgsave.on{opacity:1;pointer-events:auto}
  .mp-page select.in,.mp-page select.js-select2{width:100%}
  .mp-page textarea.in{display:block;resize:vertical;min-height:74px;padding:10px 13px;line-height:1.45}
  .mp-page .social-in{display:flex;align-items:center;gap:8px;background:#fff;border:1px solid var(--line);border-radius:10px;padding:0 13px;min-height:40px}
  .mp-page .social-in .ms{font-size:20px;flex-shrink:0}
  .mp-page .social-in input{border:0;outline:none;background:transparent;font:inherit;font-size:13.5px;color:var(--ink);width:100%;min-width:0;padding:10px 0}
  .mp-page .social-in input::placeholder{color:var(--faint)}
  .mp-page .social-in:focus-within{border-color:#3E007C;box-shadow:0 0 0 3px #F3E8FF}
  .mp-page .social-in.is-err{border-color:#C62828}
  .mp-page .field-err{color:#C62828;font-size:12px;margin-top:6px}
  .mp-page .field-err.hidden{display:none}
  .mp-page .select2-container{width:100%!important;z-index:1;font-family:inherit}
  .mp-page .select2-container--open{z-index:10060!important}
  .mp-page .select2-container--default .select2-selection--single{
    height:40px;border:1px solid var(--line);border-radius:10px;background:#fff;padding:0 8px 0 4px
  }
  .mp-page .select2-container--default .select2-selection--single .select2-selection__rendered{
    line-height:38px;padding-left:9px;color:var(--ink);font-size:13.5px
  }
  .mp-page .select2-container--default .select2-selection--single .select2-selection__placeholder{color:var(--faint)}
  .mp-page .select2-container--default .select2-selection--single .select2-selection__arrow{height:38px;right:6px}
  .mp-page .select2-container--default.select2-container--focus .select2-selection--single,
  .mp-page .select2-container--default.select2-container--open .select2-selection--single{
    border-color:#3E007C;box-shadow:0 0 0 3px #F3E8FF
  }
  .select2-dropdown{border:1px solid var(--line);border-radius:12px;overflow:hidden;font-family:'Plus Jakarta Sans',system-ui,sans-serif}
  .select2-container--default .select2-results__option--highlighted.select2-results__option--selectable{
    background:#3E007C;color:#fff
  }
  .select2-container--default .select2-results__option--selected{background:#F7F3F9;color:#3E007C;font-weight:600}
  .select2-container--default .select2-search--dropdown .select2-search__field{
    border:1px solid var(--line);border-radius:8px;padding:8px 10px;font-family:inherit;font-size:13.5px;outline:none
  }
  .select2-container--default .select2-search--dropdown .select2-search__field:focus{
    border-color:#3E007C;box-shadow:0 0 0 3px #F3E8FF
  }
  .mp-page .ql{position:relative}
  .mp-page .ql-b{
    display:inline-flex;align-items:center;gap:8px;font:inherit;font-weight:600;font-size:13.5px;
    padding:9px 12px 9px 13px;border-radius:12px;border:1px solid var(--line);background:#fff;color:var(--ink);
    cursor:pointer;white-space:nowrap
  }
  .mp-page .ql-b:hover{background:#F7F3F9}
  .mp-page .ql-b:focus-visible{outline:2px solid #3E007C;outline-offset:2px}
  .mp-page .ql-b .ms{font-size:18px}.mp-page .ql-b .cv{font-size:20px}
  .mp-page .ql-p{
    position:absolute;right:0;top:calc(100% + 8px);width:min(400px,calc(100vw - 32px));
    background:#fff;border:1px solid #EAE2EE;border-radius:16px;box-shadow:0 18px 44px rgba(20,0,40,.18);
    padding:6px;z-index:80
  }
  .mp-page .ql-p[hidden]{display:none}
  .mp-page .ql-r{display:flex;align-items:center;gap:6px;padding:4px 4px 4px 12px;border-radius:10px}
  .mp-page .ql-r:hover{background:#F7F3F9}
  .mp-page .ql-u{flex:1;min-width:0;font-size:13.5px;color:var(--ink);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
  .mp-page .ql-u b{font-weight:700}
  .mp-page .ql-i{
    width:34px;height:34px;border-radius:9px;border:0;background:none;display:inline-flex;align-items:center;
    justify-content:center;cursor:pointer;color:var(--ink);flex-shrink:0;text-decoration:none
  }
  .mp-page .ql-i:hover{background:#EFE7F3}
  .mp-page .ql-i:focus-visible{outline:2px solid #3E007C;outline-offset:1px}
  .mp-page .ql-i .ms{font-size:18px}.mp-page .ql-i.done .ms{color:#1F8A4C}
  @media (max-width:600px){.mp-page .ql-p{right:auto;left:0}}
  .mp-page .av-lg{
    width:84px;height:84px;border-radius:50%;background:var(--pri);color:#fff;
    display:flex;align-items:center;justify-content:center;font-weight:700;font-size:24px;
    overflow:hidden;position:relative;flex-shrink:0
  }
  .mp-page .av-lg img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover}
  .mp-page .nmopt{flex:1;border:1px solid var(--line);border-radius:12px;padding:12px 14px;background:#fff;cursor:pointer}
  .mp-page .nmopt.on{border:2px solid #3E007C;padding:11px 13px;background:#FDFAFF}
  .mp-page .svcs{display:flex;flex-wrap:wrap;gap:8px}
  .mp-page .svc{display:inline-flex;align-items:center;gap:8px;border:1px solid var(--line);border-radius:12px;padding:9px 13px;font-size:13.5px;font-weight:600;cursor:pointer;background:#fff;margin:0}
  .mp-page .svc input{accent-color:#3E007C;width:17px;height:17px;margin:0}
  .mp-page .svc:has(input:checked){border-color:#3E007C;background:#FBF5FF}
  .mp-page .stwrap{position:relative}
  .mp-page .stmenu{position:absolute;left:0;right:0;top:calc(100% + 6px);background:#fff;border:1px solid var(--line);border-radius:14px;box-shadow:0 14px 40px rgba(40,10,60,.16);max-height:240px;overflow-y:auto;z-index:30;padding:6px}
  .mp-page .sto{display:flex;align-items:center;justify-content:space-between;width:100%;border:0;background:none;font:inherit;font-size:14px;padding:11px 12px;border-radius:9px;cursor:pointer;text-align:left}
  .mp-page .sto:hover{background:#F8F2FC}
  .mp-page .sto .ms{font-size:20px;color:#C8BFCC}
  .mp-page .sto.on .ms{color:#3E007C;font-variation-settings:'FILL' 1}
  .mp-page .stchip{display:inline-flex;align-items:center;gap:6px;background:#3E007C;color:#fff;border:0;border-radius:20px;padding:6px 10px 6px 13px;font:inherit;font-size:12.5px;font-weight:600;cursor:pointer}
  .mp-page .stchip .ms{font-size:15px}
  .mp-page .stempty{padding:12px;color:var(--faint);font-size:13px}
  .mp-page .slrow{display:flex;align-items:center;gap:8px;border:1px solid var(--line);border-radius:12px;background:#fff;padding:0 6px 0 12px;min-height:44px}
  .mp-page .slrow input{border:0;outline:0;font:inherit;flex:1;min-width:0;background:none;font-size:14px}
  .mp-page .slrow .slx{border:0;background:none;cursor:pointer;color:var(--muted);padding:6px;border-radius:8px}
  .mp-page .slrow .slx:hover{background:#F3E8FF}
  .mp-page .slrow:focus-within{border-color:#3E007C}
  .mp-page .incadd{display:inline-flex;align-items:center;gap:6px;border:0;background:none;font:inherit;font-weight:700;font-size:14px;cursor:pointer;padding:10px 0;color:#1A1A1A}
  .mp-page .incadd .ms{font-size:20px}
  .mp-page .phone-frame{width:300px;margin:0 auto;border:8px solid #1A1A1A;border-radius:34px;overflow:hidden;background:#fff}
  .mp-page .mp-toast{
    position:fixed;top:22px;right:24px;background:#1A1A1A;color:#fff;padding:12px 18px;border-radius:10px;
    font-weight:600;font-size:14px;z-index:400;box-shadow:0 10px 30px rgba(0,0,0,.22);
    display:flex;gap:10px;align-items:center;opacity:0;transform:translateX(calc(100% + 40px));
    transition:opacity .25s,transform .35s;pointer-events:none
  }
  .mp-page .mp-toast.on{opacity:1;transform:none}
  .mp-page .mp-toast .ms{color:#3DD68C;font-size:20px}
  .mp-page .mp-leave{
    position:fixed;inset:0;background:rgba(20,10,30,.45);display:none;align-items:center;justify-content:center;z-index:500;padding:16px
  }
  .mp-page .mp-leave.on{display:flex}
  .mp-page .mp-leave-box{background:#fff;border-radius:22px;width:420px;max-width:100%;box-shadow:0 20px 60px rgba(0,0,0,.25);overflow:hidden;position:relative}
  .mp-page .mp-leave-x{
    position:absolute;top:14px;right:14px;width:36px;height:36px;border:0;border-radius:10px;background:none;
    color:var(--muted);cursor:pointer;display:inline-flex;align-items:center;justify-content:center;padding:0
  }
  .mp-page .mp-leave-x:hover{background:#F7F3F9;color:var(--ink)}
  .mp-page .mp-leave-x .ms{font-size:22px}
  .mp-page .mp-leave-x:focus-visible{outline:2px solid #3E007C;outline-offset:2px}
  .mp-page .theme-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}
  .mp-page .theme-card{
    border:2px solid var(--line);border-radius:14px;overflow:hidden;background:#fff;cursor:pointer;
    text-align:left;padding:0;font:inherit;position:relative
  }
  .mp-page .theme-card.on{border-color:#3E007C}
  .mp-page .theme-swatch{display:block;height:40px;width:100%}
  .mp-page .theme-label{display:block;padding:10px 12px;font-size:13px;font-weight:600}
  .mp-page .theme-check{display:none;position:absolute;top:8px;right:8px;width:22px;height:22px;border-radius:50%;background:#3E007C;color:#fff;font-size:16px;align-items:center;justify-content:center}
  .mp-page .theme-card.on .theme-check{display:flex}
  .mp-page .banner-upload{
    border:2px dashed var(--line);border-radius:16px;padding:32px;display:flex;flex-direction:column;
    align-items:center;justify-content:center;cursor:pointer;background:#fff;min-height:160px;
    background-size:cover;background-position:center;position:relative;overflow:hidden
  }
  .mp-page .banner-upload.has-image{border-style:solid;padding:0;height:200px}
  .mp-page .banner-upload.has-image .upload-prompt{display:none}
  .mp-page .banner-upload .upload-overlay{
    display:none;position:absolute;inset:0;background:rgba(20,20,20,.45);color:#fff;
    font-weight:600;align-items:center;justify-content:center
  }
  .mp-page .banner-upload.has-image .upload-overlay{display:flex}
  #pvnote[hidden]{display:none!important}
  .mp-page .crop-ov{display:none;position:fixed;inset:0;z-index:600;background:rgba(0,0,0,.7);align-items:center;justify-content:center;padding:16px}
  .mp-page .crop-ov.open{display:flex}
  .mp-page .crop-box{width:100%;max-width:560px;background:#fff;border-radius:16px;padding:20px 22px;border:1px solid var(--line)}
  .mp-page .crop-stage{width:100%;height:360px;background:#F7F2F8;border-radius:12px;overflow:hidden}
  .mp-page .crop-actions{display:flex;justify-content:flex-end;gap:10px;margin-top:16px}
  @media (max-width:1180px){
    .mp-about-grid{grid-template-columns:minmax(0,1fr)!important}
    .mp-about-grid > div:last-child{position:static!important}
  }
  @media (max-width:900px){
    .mp-page .mp-toast{left:16px;right:16px;top:74px}
    .mp-page .theme-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
    .mp-name-grid{grid-template-columns:1fr!important}
    }
</style>
@endsection

@section('content')
<div class="mp-page">
  <div class="head">
          <div>
      <h1>
        My Page
        <a class="help-q" href="https://help.inkjin.com/en/articles/17201253-dashboard-my-page-about" target="_blank" rel="noopener" title="Help with this page" aria-label="Help with this page">
          <span class="ms">help</span>
        </a>
      </h1>
      <div class="sub">Everything clients see on your booking page.</div>
          </div>
    <div class="btns">
      @if ($publicPageUrl)
        <div class="ql" id="quickLinks">
          <button type="button" class="ql-b" id="quickLinksBtn" aria-haspopup="true" aria-expanded="false" aria-controls="quickLinksMenu">
            <span class="ms">link</span>Quick links<span class="ms cv">expand_more</span>
          </button>
          <div class="ql-p" id="quickLinksMenu" hidden role="menu" aria-label="Quick links">
            @foreach ($quickLinkRows as $row)
              @php
                $copyUrl = $publicPageUrl.$row['suffix'];
                $displayBase = $publicPageHost;
                $displaySuffix = $row['suffix'];
@endphp
              <div class="ql-r" role="none">
                <div class="ql-u" title="{{ $row['label'] }}">
                  {{ $displayBase }}@if($displaySuffix !== '')<b>{{ $displaySuffix }}</b>@endif
          </div>
                <button type="button" class="ql-i" data-ql-copy="{{ $copyUrl }}" data-ql-label="{{ $row['label'] }}" role="menuitem" aria-label="Copy {{ $row['label'] }}" title="Copy link">
                  <span class="ms">content_copy</span>
                </button>
                <a class="ql-i" href="{{ $copyUrl }}" target="_blank" rel="noopener" role="menuitem" aria-label="Open {{ $row['label'] }}" title="Open">
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
    'myPageTab' => $myPageTab,
    'designsCount' => $designsCount ?? 0,
    'portfolioCount' => $portfolioCount ?? 0,
    'faqCount' => $faqCount ?? 0,
  ])

  <form id="aboutForm" enctype="multipart/form-data">
    @csrf
    <input type="hidden" name="section" value="about">
    <input type="hidden" name="personal_page_name_alias" id="nameAlias" value="{{ $selectedAlias }}">
    <input type="hidden" name="display_tagline" id="displayTagline" value="{{ $displayTagline ? '1' : '0' }}">
    <input type="hidden" name="display_bio" id="displayBio" value="{{ $displayBio ? '1' : '0' }}">
    <div id="otherStylesInputs"></div>

    <div class="grid mp-about-grid" style="grid-template-columns:1fr 340px;gap:28px;align-items:start">
          <div>
        {{-- Photo --}}
        <div class="card" style="margin-bottom:14px">
          <div class="ch">
            <div>
              <h3>Photo</h3>
              <div class="faint" style="font-size:12.5px;margin-top:2px">Shown at the top of your page</div>
          </div>
        </div>
          <div style="padding:18px 22px">
            <div class="row" style="gap:18px">
              <div class="av-lg" id="photoPreview" @if($avatarUrl) style="background:#fff" @endif>
                @if ($avatarUrl)
                  <img src="{{ $avatarUrl }}" alt="">
                @else
                  {{ strtoupper(substr(preg_replace('/\s+/', '', $firstName.$lastName) ?: 'AR', 0, 2)) }}
                @endif
          </div>
              <div>
                <button type="button" class="btn sm ghost" id="changePhotoBtn">Change photo</button>
                <input type="file" id="avatarInput" accept="image/jpeg,image/png,image/webp,image/heic,image/heif" hidden>
                <div class="faint" style="font-size:12px;margin-top:8px">Professional headshots increase booking conversion by 40%</div>
        </div>
        </div>
        </div>
        </div>

        {{-- Name --}}
        <div class="card" style="margin-bottom:14px">
          <div class="ch">
            <div>
              <h3>Name</h3>
              <div class="faint" style="font-size:12.5px;margin-top:2px">Edit your name and pick which one clients see</div>
      </div>
          </div>
          <div style="padding:18px 22px">
            <div class="grid mp-name-grid" style="grid-template-columns:1fr 1fr 1fr;gap:12px;margin-bottom:16px">
          <div>
                <label class="fl" for="fn">First name</label>
                <input class="in" id="fn" name="first_name" value="{{ $firstName }}" required>
          </div>
              <div>
                <label class="fl" for="ln">Last name</label>
                <input class="in" id="ln" name="last_name" value="{{ $lastName }}" required>
        </div>
              <div>
                <label class="fl" for="dn">Display name <span class="faint" style="font-weight:500">(optional)</span></label>
                <input class="in" id="dn" name="display_name" value="{{ $displayName }}" maxlength="100">
              </div>
            </div>
            <div class="lbl" style="margin-bottom:8px">Show on my page as</div>
            <div class="row" style="gap:10px">
              <div class="nmopt {{ $selectedAlias === 'username' ? 'on' : '' }}" data-k="username">
                <div class="row" style="gap:8px">
                  <span class="ms nmico" style="font-size:18px;color:{{ $selectedAlias === 'username' ? '#3E007C' : '#C8BFCC' }};font-variation-settings:'FILL' {{ $selectedAlias === 'username' ? '1' : '0' }}">{{ $selectedAlias === 'username' ? 'radio_button_checked' : 'radio_button_unchecked' }}</span>
                  <span class="faint" style="font-size:12px;font-weight:600">Username</span>
          </div>
                <b class="nmval" style="display:block;margin-top:4px;font-size:14px">{{ '@' . ($username !== '' ? $username : 'username') }}</b>
              </div>
              <div class="nmopt {{ $selectedAlias === 'display_name' ? 'on' : '' }}" data-k="display_name">
                <div class="row" style="gap:8px">
                  <span class="ms nmico" style="font-size:18px;color:{{ $selectedAlias === 'display_name' ? '#3E007C' : '#C8BFCC' }};font-variation-settings:'FILL' {{ $selectedAlias === 'display_name' ? '1' : '0' }}">{{ $selectedAlias === 'display_name' ? 'radio_button_checked' : 'radio_button_unchecked' }}</span>
                  <span class="faint" style="font-size:12px;font-weight:600">Display name</span>
            </div>
                <b class="nmval" id="dnOptVal" style="display:block;margin-top:4px;font-size:14px">{{ $displayName !== '' ? $displayName : '—' }}</b>
          </div>
              </div>
            <div class="help" style="margin-top:10px">Your username shows by default. Add a display name to show it instead. First and last name are the same as in Account &gt; Profile and are not shown on your page. Your username is changed in Account &gt; Profile because it is part of your page link.</div>
          </div>
        </div>

        {{-- Tagline & bio --}}
        <div class="card" style="margin-bottom:14px">
          <div class="ch">
          <div>
              <h3>Tagline &amp; bio</h3>
              <div class="faint" style="font-size:12.5px;margin-top:2px">Edit and show them here, in one place</div>
          </div>
          </div>
          <div style="padding:18px 22px">
            <div class="row" style="justify-content:space-between;margin-bottom:6px">
              <span class="fl" style="margin:0">Tagline</span>
              <div class="row" style="gap:8px">
                <span class="faint" style="font-size:12.5px;font-weight:600">Show on page</span>
                <div class="tog {{ $displayTagline ? 'on' : '' }}" id="tgtog" data-own="1" role="switch" aria-checked="{{ $displayTagline ? 'true' : 'false' }}" tabindex="0"></div>
          </div>
        </div>
            <input class="in" id="tgin" name="personal_page_tagline" maxlength="80" value="{{ $tagline }}" style="width:100%">

            <div class="row" style="justify-content:space-between;margin:16px 0 6px">
              <span class="fl" style="margin:0">Bio</span>
              <div class="row" style="gap:8px">
                <span class="faint" style="font-size:12.5px;font-weight:600">Show on page</span>
                <div class="tog {{ $displayBio ? 'on' : '' }}" id="biotog" data-own="1" role="switch" aria-checked="{{ $displayBio ? 'true' : 'false' }}" tabindex="0"></div>
          </div>
            </div>
            <textarea class="in" id="bioin" name="personal_page_description" maxlength="500">{{ $bio }}</textarea>
            <div class="help" style="text-align:right" id="biocnt">{{ mb_strlen($bio) }} / 500</div>
          </div>
        </div>

        {{-- Experience, styles & services --}}
        <div class="card" style="margin-bottom:14px">
          <div class="ch">
            <div>
              <h3>Experience, styles &amp; services</h3>
              <div class="faint" style="font-size:12.5px;margin-top:2px">Helps clients find you by style and by the work you take on</div>
            </div>
            </div>
          <div style="padding:18px 22px">
            <div class="grid" style="grid-template-columns:1fr 1fr;gap:12px">
              <div>
                <label class="fl" for="tattooing_since">Tattooing since</label>
                <select class="in js-select2" id="tattooing_since" name="tattooing_since" data-placeholder="Select year" data-searchable="true">
                  <option value=""></option>
                  @for ($y = (int) date('Y'); $y >= 1970; $y--)
                    <option value="{{ $y }}" @selected((int) $since === $y)>{{ $y }}</option>
                  @endfor
                </select>
          </div>
            <div>
                <label class="fl" for="primary_style">Primary style</label>
                <select class="in js-select2" id="primary_style" name="primary_style" data-placeholder="Select style" data-searchable="true">
                  <option value=""></option>
                  @foreach ($styleOptions as $val => $lab)
                    <option value="{{ $val }}" @selected(($primary ?? '') === $val)>{{ $lab }}</option>
                  @endforeach
                </select>
            </div>
            </div>

            <span class="fl" style="margin-top:14px">Other styles</span>
            <div class="stwrap">
              <div class="in stsearch" style="cursor:text">
                <span class="ms">search</span>
                <input id="stq" placeholder="Search styles..." autocomplete="off" style="border:0;outline:0;font:inherit;flex:1;background:none">
          </div>
              <div class="stmenu" id="stmenu" hidden role="listbox" aria-multiselectable="true"></div>
        </div>
            <div class="row" id="stsel" style="gap:8px;flex-wrap:wrap;margin-top:10px"></div>
            <div class="help">Pick all the styles you tattoo. Your primary style is shown first.</div>

            <span class="fl" style="margin-top:18px">Services you also offer <span class="faint" style="font-weight:500">(optional)</span></span>
            <div class="svcs" id="svcs">
              @foreach (['Touch-ups', 'Cover-ups', 'Blackout', 'Other'] as $svc)
                <label class="svc">
                  <input type="checkbox" name="extra_services[]" value="{{ $svc }}" @checked(in_array($svc, $extraServiceList, true)) @if($svc === 'Other') id="svco" @endif>
                  <span>{{ $svc }}</span>
                </label>
              @endforeach
      </div>
            <div id="svcow" @if(!in_array('Other', $extraServiceList, true)) hidden @endif style="margin-top:10px;max-width:360px">
              <label class="fl" for="svcot">Other service</label>
              <input class="in" id="svcot" name="extra_service_other" maxlength="40" placeholder="e.g. Scar cover-ups" value="{{ $extraServiceOther }}">
              <div class="help">Up to 40 characters.</div>
            </div>
            <div class="help">Shown on your page under your styles.</div>
          </div>
        </div>

        {{-- Social --}}
        <div class="card" style="margin-bottom:14px">
          <div class="ch">
            <div>
              <h3>Social links</h3>
              <div class="faint" style="font-size:12.5px;margin-top:2px">Shown as icons under your name</div>
          </div>
        </div>
          <div style="padding:18px 22px">
            <div class="grid social-grid" style="grid-template-columns:1fr 1fr;gap:10px">
              <div>
                <div class="social-in">
                  <span class="ms" style="color:#C13584">photo_camera</span>
                  <input type="url" id="instagram" name="social_links[instagram]" value="{{ $sl['instagram'] ?? '' }}" placeholder="instagram.com/yourhandle" autocomplete="off">
      </div>
                <p id="instagram_error" class="field-err hidden" role="alert"></p>
          </div>
          <div>
                <div class="social-in">
                  <span class="ms">music_note</span>
                  <input type="url" id="tiktok" name="social_links[tiktok]" value="{{ $sl['tiktok'] ?? '' }}" placeholder="tiktok.com/@yourhandle" autocomplete="off">
          </div>
                <p id="tiktok_error" class="field-err hidden" role="alert"></p>
        </div>
              <div>
                <div class="social-in">
                  <span class="ms" style="color:#E00">smart_display</span>
                  <input type="url" id="youtube" name="social_links[youtube]" value="{{ $sl['youtube'] ?? '' }}" placeholder="youtube.com/@yourchannel" autocomplete="off">
          </div>
                <p id="youtube_error" class="field-err hidden" role="alert"></p>
          </div>
              <div>
                <div class="social-in">
                  <span class="ms" style="color:#1877F2">thumb_up</span>
                  <input type="url" id="facebook" name="social_links[facebook]" value="{{ $sl['facebook'] ?? '' }}" placeholder="facebook.com/yourpage" autocomplete="off">
          </div>
                <p id="facebook_error" class="field-err hidden" role="alert"></p>
          </div>
              <div style="grid-column:1/-1">
                <div class="social-in">
                  <span class="ms">add</span>
                  <input type="url" id="website" name="social_links[website]" value="{{ $sl['website'] ?? '' }}" placeholder="Other link" autocomplete="off">
          </div>
                <p id="website_error" class="field-err hidden" role="alert"></p>
          </div>
        </div>
            <div class="help">Shown as icons under your name on your page.</div>
          </div>
      </div>

        <div class="row pgsaverow" style="justify-content:flex-start;margin-top:8px">
          <button type="button" class="btn pgsave" id="aboutSaveBtn" disabled>
            <span class="ms">save</span>Save changes
        </button>
        </div>
      </div>

      {{-- Live preview --}}
      <div style="position:sticky;top:20px">
        <div class="row" style="justify-content:space-between;margin-bottom:10px">
          <div class="lbl">Live preview</div>
      </div>
        <div class="phone-frame">
          <div style="height:92px;background:linear-gradient(135deg,#2a1147,#7a3a8f 60%,#e28b56)"></div>
          <div style="padding:0 18px 18px;margin-top:-34px">
            <div class="av-lg" id="pvav" style="width:68px;height:68px;border:4px solid #fff;font-size:20px;@if($avatarUrl) background:#fff @endif">
              @if ($avatarUrl)
                <img src="{{ $avatarUrl }}" alt="">
            @else
                {{ strtoupper(substr(preg_replace('/\s+/', '', $firstName.$lastName) ?: 'AR', 0, 2)) }}
            @endif
          </div>
            <b id="pvname" style="display:block;font-size:18px;margin-top:8px">
              @if ($selectedAlias === 'display_name' && $displayName !== '')
                {{ $displayName }}
              @else
                {{ '@' . ($username !== '' ? $username : 'username') }}
              @endif
            </b>
            <div class="faint" style="font-size:12px">{{ $previewMeta !== '' ? $previewMeta : 'Your studio · City' }}</div>
            <div id="pvtag" @if(!$displayTagline || trim($tagline)==='') hidden @endif style="font-size:12.5px;font-weight:600;margin-top:6px">{{ $tagline }}</div>
            <div id="pvbio" @if(!$displayBio || trim($bio)==='') hidden @endif style="font-size:12px;color:#555;margin-top:6px;line-height:1.45">{{ $bio }}</div>
            <div class="row" id="pvstyles" style="gap:6px;margin-top:10px;flex-wrap:wrap">
              @if ($primaryLabel !== '')
                <span class="pill nd k">{{ $primaryLabel }}</span>
              @endif
              @foreach (array_slice($otherList, 0, 2) as $os)
                <span class="pill nd k">{{ $styleOptions[$os] ?? ucwords(str_replace('-', ' ', $os)) }}</span>
              @endforeach
              @if ($since)
                <span class="pill nd k">Since {{ $since }}</span>
              @endif
            </div>
            <div id="pvsvc" class="faint" style="font-size:12px;margin-top:6px" @if(empty(array_filter($extraServiceList, fn($s)=>$s!=='Other')) && $extraServiceOther==='') hidden @endif>
              Also:
              @php
                $svcPreview = array_values(array_filter($extraServiceList, fn ($s) => $s !== 'Other'));
                if ($extraServiceOther !== '') { $svcPreview[] = $extraServiceOther; }
              @endphp
              {{ implode(' · ', $svcPreview) }}
          </div>
            <div style="margin-top:14px;display:grid;grid-template-columns:1fr 1fr 1fr;gap:4px">
              <div style="height:74px;border-radius:6px;background:#f2d6c9"></div>
              <div style="height:74px;border-radius:6px;background:#e6e0ea"></div>
              <div style="height:74px;border-radius:6px;background:#2b2b2b"></div>
        </div>
            <div style="margin-top:14px;font-weight:700;font-size:13px">What's included</div>
            <div class="faint" style="font-size:12px;margin-top:4px">{{ $whatsIncludedText }}</div>
            <div class="btn" style="width:100%;justify-content:center;margin-top:16px;background:#3E007C;pointer-events:none">Book a tattoo</div>
      </div>
    </div>
        <div class="note" id="pvnote" style="margin-top:14px" @if($displayTagline && $displayBio) hidden @endif>
          <span class="ms">visibility_off</span>
          <span id="pvnotet">
            @if (!$displayTagline && !$displayBio)
              Tagline and bio are hidden. Turn them on to show them here.
            @elseif (!$displayTagline)
              Tagline is hidden. Turn it on to show it here.
            @else
              Bio is hidden. Turn it on to show it here.
            @endif
          </span>
  </div>
      </div>
    </div>
  </form>

  <div id="cropperModal" class="crop-ov" role="dialog" aria-modal="true" aria-labelledby="cropTitle">
    <div class="crop-box">
      <h3 id="cropTitle" style="font-size:17px;font-weight:800;margin-bottom:4px">Crop profile photo</h3>
      <p class="sub" style="margin-bottom:14px">Adjust your image to a square crop for a uniform profile photo.</p>
      <div class="crop-stage">
        <img id="cropImage" src="" alt="">
        </div>
      <div class="crop-actions">
        <button id="cancelCropBtn" type="button" class="btn ghost">Cancel</button>
        <button id="applyCropBtn" type="button" class="btn">Use photo</button>
      </div>
      </div>
      </div>

  <div class="mp-toast" id="mpToast" role="status"><span class="ms">check_circle</span><span id="mpToastText">Changes saved</span></div>
  <div class="mp-leave" id="mpLeave" role="dialog" aria-modal="true" aria-labelledby="mpLeaveTitle">
    <div class="mp-leave-box">
      <button type="button" class="mp-leave-x" id="mpLeaveClose" aria-label="Close">
        <span class="ms">close</span>
        </button>
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
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
  <script src="https://unpkg.com/cropperjs@1.6.2/dist/cropper.min.js"></script>
  <script>
(function () {
  var CSRF = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  var UPDATE_URL = @json(route('personal-page.update'));
  var USERNAME = @json($username !== '' ? $username : 'username');
  var STYLE_ALL = @json(json_decode($styleAllJson, true) ?: []);
  var OTHER_INIT = @json(json_decode($otherJson, true) ?: []);
  var dirty = false;
  var pendingNav = null;
  var pendingReload = false;
  var croppedAvatarBlob = null;
  var toastEl = document.getElementById('mpToast');
  var leaveEl = document.getElementById('mpLeave');

  function runPendingLeave() {
    if (pendingReload) {
      pendingReload = false;
      window.location.reload();
      return;
    }
    var n = pendingNav;
    pendingNav = null;
    if (n) n.click();
  }

  function showLeaveModal(opts) {
    opts = opts || {};
    pendingNav = opts.nav || null;
    pendingReload = !!opts.reload;
    if (leaveEl) leaveEl.classList.add('on');
  }

  function toast(msg) {
    if (!toastEl) return;
    document.getElementById('mpToastText').textContent = msg || 'Changes saved';
    toastEl.classList.add('on');
    clearTimeout(toastEl._t);
    toastEl._t = setTimeout(function () { toastEl.classList.remove('on'); }, 2600);
  }

  function setDirty(v) {
    dirty = !!v;
    document.querySelectorAll('.mp-page .btn.pgsave').forEach(function (b) {
      b.disabled = !dirty;
      b.classList.toggle('on', dirty);
    });
  }

  function markDirty() { setDirty(true); }

  var qlWrap = document.getElementById('quickLinks');
  var qlBtn = document.getElementById('quickLinksBtn');
  var qlMenu = document.getElementById('quickLinksMenu');
  if (qlWrap && qlBtn && qlMenu) {
    function openQuickLinks(show) {
      qlMenu.hidden = !show;
      qlBtn.setAttribute('aria-expanded', show ? 'true' : 'false');
      var cv = qlBtn.querySelector('.cv');
      if (cv) cv.textContent = show ? 'expand_less' : 'expand_more';
    }
    qlBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      openQuickLinks(qlMenu.hidden);
    });
    qlMenu.addEventListener('click', function (e) {
      e.stopPropagation();
      var c = e.target.closest('[data-ql-copy]');
      if (!c) return;
      var u = c.getAttribute('data-ql-copy') || '';
      var label = c.getAttribute('data-ql-label') || u;
      try { navigator.clipboard.writeText(u).catch(function () {}); } catch (x) {}
      c.classList.add('done');
      var icon = c.querySelector('.ms');
      if (icon) icon.textContent = 'check';
      setTimeout(function () {
        c.classList.remove('done');
        if (icon) icon.textContent = 'content_copy';
      }, 1600);
      toast('Link copied: ' + label);
    });
    document.addEventListener('click', function (e) {
      if (!qlMenu.hidden && !qlWrap.contains(e.target)) openQuickLinks(false);
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && !qlMenu.hidden) {
        openQuickLinks(false);
        qlBtn.focus();
      }
    });
  }

  /* ---------- Appearance tab ---------- */
  var appearanceForm = document.getElementById('appearanceForm');
  if (appearanceForm) {
    var bannerArea = document.getElementById('bannerUploadArea');
    var bannerInput = document.getElementById('bannerInput');
    var bannerRemove = document.getElementById('bannerRemoveBtn');
    var removeFlag = document.getElementById('remove_banner');
    var colorInput = document.getElementById('personal_page_color');
    var guestTog = document.getElementById('guestSpotsTog');
    var guestHidden = document.getElementById('display_guest_spots');

    if (bannerArea && bannerInput) {
      bannerArea.addEventListener('click', function () { bannerInput.click(); });
      bannerInput.addEventListener('change', function () {
        var f = bannerInput.files && bannerInput.files[0];
        if (!f) return;
        removeFlag.value = '0';
        var url = URL.createObjectURL(f);
        bannerArea.classList.add('has-image');
        bannerArea.style.backgroundImage = 'url(' + url + ')';
        if (bannerRemove) bannerRemove.hidden = false;
        markDirty();
      });
    }
    if (bannerRemove) {
      bannerRemove.addEventListener('click', function () {
      if (bannerInput) bannerInput.value = '';
        removeFlag.value = '1';
        bannerArea.classList.remove('has-image');
        bannerArea.style.backgroundImage = '';
        bannerRemove.hidden = true;
        markDirty();
      });
    }
    appearanceForm.querySelectorAll('.theme-card').forEach(function (card) {
      card.addEventListener('click', function () {
        appearanceForm.querySelectorAll('.theme-card').forEach(function (c) { c.classList.remove('on'); });
        card.classList.add('on');
        colorInput.value = card.dataset.theme;
        markDirty();
      });
    });
    if (guestTog) {
      guestTog.addEventListener('click', function () {
        guestTog.classList.toggle('on');
        guestTog.setAttribute('aria-checked', guestTog.classList.contains('on'));
        guestHidden.value = guestTog.classList.contains('on') ? '1' : '0';
        markDirty();
      });
    }

    document.getElementById('appearanceSaveBtn')?.addEventListener('click', function () {
      saveAppearance(false);
    });

    window.__mpSave = function (andLeave) { return saveAppearance(andLeave); };

    function saveAppearance(andLeave) {
      var btn = document.getElementById('appearanceSaveBtn');
      if (btn) { btn.disabled = true; btn.classList.remove('on'); }
      var fd = new FormData(appearanceForm);
      return fetch(UPDATE_URL, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: fd
      }).then(function (r) { return r.json().then(function (j) { return { ok: r.ok, j: j }; }); })
        .then(function (res) {
          if (!res.ok || !res.j.success) throw new Error(res.j.message || 'Could not save');
          setDirty(false);
          toast(res.j.message || 'Changes saved');
          if (andLeave) runPendingLeave();
        })
        .catch(function (e) {
          toast(e.message || 'Could not save');
          setDirty(true);
        });
    }
  }

  /* ---------- About tab ---------- */
  var aboutForm = document.getElementById('aboutForm');
  if (aboutForm) {
    var aliasInput = document.getElementById('nameAlias');
    var fn = document.getElementById('fn');
    var ln = document.getElementById('ln');
    var dn = document.getElementById('dn');
    var pvname = document.getElementById('pvname');
    var opts = [].slice.call(document.querySelectorAll('.nmopt'));
    var sel = aliasInput.value || 'username';

    function aliasVal(k) {
      if (k === 'display_name') return (dn.value || '').trim() || ('@' + USERNAME);
      return '@' + USERNAME;
    }
    function drawAlias() {
      opts.forEach(function (o) {
        var k = o.dataset.k;
        var on = k === sel;
        o.classList.toggle('on', on);
        var r = o.querySelector('.nmico');
        if (r) {
          r.textContent = on ? 'radio_button_checked' : 'radio_button_unchecked';
          r.style.color = on ? '#3E007C' : '#C8BFCC';
          r.style.fontVariationSettings = on ? "'FILL' 1" : "'FILL' 0";
        }
        var nv = o.querySelector('.nmval');
        if (nv && k === 'display_name') nv.textContent = (dn.value || '').trim() || '—';
      });
      if (pvname) pvname.textContent = aliasVal(sel);
      aliasInput.value = sel;
    }
    opts.forEach(function (o) {
      o.addEventListener('click', function () { sel = o.dataset.k; drawAlias(); markDirty(); });
    });
    [fn, ln, dn].forEach(function (el) {
      el?.addEventListener('input', function () { drawAlias(); markDirty(); });
    });
    drawAlias();

    // Photo cropper
    var avatarInput = document.getElementById('avatarInput');
    var changePhotoBtn = document.getElementById('changePhotoBtn');
    var cropperModal = document.getElementById('cropperModal');
    var cropImage = document.getElementById('cropImage');
    var cropper = null;
    var cropObjectUrl = '';

    function setAvatarPreview(url) {
      ['photoPreview', 'pvav'].forEach(function (id) {
        var el = document.getElementById(id);
        if (!el) return;
        el.style.background = '#fff';
        el.innerHTML = '<img src="' + url + '" alt="">';
      });
    }

    function closeCropperModal() {
      if (!cropperModal) return;
      cropperModal.classList.remove('open');
      if (cropper) {
        cropper.destroy();
        cropper = null;
      }
      if (cropImage) cropImage.src = '';
      if (avatarInput) avatarInput.value = '';
      if (cropObjectUrl) {
        URL.revokeObjectURL(cropObjectUrl);
        cropObjectUrl = '';
      }
    }

    function openCropper(file) {
      if (!file || typeof Cropper === 'undefined' || !cropperModal || !cropImage) return;
      if (cropObjectUrl) URL.revokeObjectURL(cropObjectUrl);
      cropObjectUrl = URL.createObjectURL(file);
      cropImage.src = cropObjectUrl;
      cropperModal.classList.add('open');
      if (cropper) cropper.destroy();
      cropper = new Cropper(cropImage, {
        aspectRatio: 1,
        viewMode: 1,
        dragMode: 'move',
        background: false,
        autoCropArea: 1,
        responsive: true,
      });
    }

    changePhotoBtn?.addEventListener('click', function () { avatarInput?.click(); });
    document.getElementById('photoPreview')?.addEventListener('click', function () { avatarInput?.click(); });
    document.getElementById('photoPreview') && (document.getElementById('photoPreview').style.cursor = 'pointer');

    avatarInput?.addEventListener('change', function () {
      var f = avatarInput.files && avatarInput.files[0];
      if (!f) return;
      openCropper(f);
    });

    document.getElementById('cancelCropBtn')?.addEventListener('click', closeCropperModal);
    cropperModal?.addEventListener('click', function (e) {
      if (e.target === cropperModal) closeCropperModal();
    });
    document.getElementById('applyCropBtn')?.addEventListener('click', function () {
      if (!cropper) return;
      var canvas = cropper.getCroppedCanvas({ width: 512, height: 512, imageSmoothingQuality: 'high' });
      if (!canvas) {
        toast('Could not crop this image. Try another photo.');
        return;
      }
      canvas.toBlob(function (blob) {
        if (!blob) {
          toast('Could not crop this image. Try another photo.');
          return;
        }
        croppedAvatarBlob = blob;
        setAvatarPreview(URL.createObjectURL(blob));
        closeCropperModal();
        markDirty();
      }, 'image/jpeg', 0.92);
    });

    // Tagline / bio
    var tt = document.getElementById('tgtog');
    var bt = document.getElementById('biotog');
    var ti = document.getElementById('tgin');
    var bi = document.getElementById('bioin');
    function updTagBio() {
      var ts = tt.classList.contains('on');
      var bs = bt.classList.contains('on');
      tt.setAttribute('aria-checked', ts);
      bt.setAttribute('aria-checked', bs);
      document.getElementById('displayTagline').value = ts ? '1' : '0';
      document.getElementById('displayBio').value = bs ? '1' : '0';
      var pt = document.getElementById('pvtag');
      var pb = document.getElementById('pvbio');
      pt.textContent = ti.value;
      pb.textContent = bi.value;
      pt.hidden = !ts || !ti.value.trim();
      pb.hidden = !bs || !bi.value.trim();
      document.getElementById('biocnt').textContent = bi.value.length + ' / 500';
      var n = document.getElementById('pvnote');
      var m = document.getElementById('pvnotet');
      if (ts && bs) { n.hidden = true; }
      else {
        n.hidden = false;
        m.textContent = (!ts && !bs)
          ? 'Tagline and bio are hidden. Turn them on to show them here.'
          : (!ts ? 'Tagline is hidden. Turn it on to show it here.' : 'Bio is hidden. Turn it on to show it here.');
      }
    }
    [tt, bt].forEach(function (t) {
      t.addEventListener('click', function () { t.classList.toggle('on'); updTagBio(); markDirty(); });
      t.addEventListener('keydown', function (e) {
        if (e.key === ' ' || e.key === 'Enter') { e.preventDefault(); t.classList.toggle('on'); updTagBio(); markDirty(); }
      });
    });
    ti.addEventListener('input', function () { updTagBio(); markDirty(); });
    bi.addEventListener('input', function () { updTagBio(); markDirty(); });
    updTagBio();

    // Other styles
    var otherSel = OTHER_INIT.slice();
    var stq = document.getElementById('stq');
    var stmenu = document.getElementById('stmenu');
    var stsel = document.getElementById('stsel');
    var otherBox = document.getElementById('otherStylesInputs');
    function esc(x) {
      return String(x).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/"/g, '&quot;');
    }
    function syncOtherInputs() {
      otherBox.innerHTML = otherSel.map(function (v) {
        return '<input type="hidden" name="other_styles[]" value="' + esc(v) + '">';
      }).join('');
    }
    function styleMenu() {
      var f = (stq.value || '').trim().toLowerCase();
      var primary = document.getElementById('primary_style')?.value || '';
      var L = STYLE_ALL.filter(function (x) {
        if (x.v === primary) return false;
        return !f || x.l.toLowerCase().indexOf(f) > -1 || x.v.toLowerCase().indexOf(f) > -1;
      });
      stmenu.innerHTML = L.length
        ? L.map(function (x) {
            var on = otherSel.indexOf(x.v) > -1;
            return '<button type="button" class="sto' + (on ? ' on' : '') + '" data-s="' + esc(x.v) + '">' + esc(x.l) +
              '<span class="ms">' + (on ? 'check_box' : 'check_box_outline_blank') + '</span></button>';
          }).join('')
        : '<div class="stempty">No styles match</div>';
    }
    function styleChips() {
      stsel.innerHTML = otherSel.map(function (v) {
        var lab = (STYLE_ALL.find(function (x) { return x.v === v; }) || {}).l || v;
        return '<button type="button" class="stchip" data-s="' + esc(v) + '">' + esc(lab) + '<span class="ms">close</span></button>';
      }).join('');
      syncOtherInputs();
      updatePvStyles();
    }
    function openStyleMenu() {
      styleMenu();
      stmenu.hidden = false;
    }
    stq.addEventListener('focus', openStyleMenu);
    stq.addEventListener('click', openStyleMenu);
    stq.addEventListener('input', openStyleMenu);
    document.querySelector('.stsearch')?.addEventListener('click', function () {
      openStyleMenu();
      stq.focus();
    });
    stmenu.addEventListener('mousedown', function (e) { e.preventDefault(); });
    stmenu.addEventListener('click', function (e) {
      var o = e.target.closest('.sto'); if (!o) return;
      var v = o.dataset.s; var i = otherSel.indexOf(v);
      if (i > -1) otherSel.splice(i, 1); else otherSel.push(v);
      openStyleMenu(); styleChips(); markDirty();
    });
    stsel.addEventListener('click', function (e) {
      var c = e.target.closest('.stchip'); if (!c) return;
      otherSel.splice(otherSel.indexOf(c.dataset.s), 1);
      styleChips(); openStyleMenu(); markDirty();
    });
    document.addEventListener('click', function (e) {
      if (!e.target.closest('.stwrap')) stmenu.hidden = true;
    });
    function updatePvStyles() {
      var box = document.getElementById('pvstyles');
      if (!box) return;
      var primary = document.getElementById('primary_style');
      var since = document.getElementById('tattooing_since');
      var pills = [];
      if (primary && primary.value) {
        var pl = primary.options[primary.selectedIndex]?.text || primary.value;
        pills.push(pl);
      }
      otherSel.slice(0, 2).forEach(function (v) {
        var lab = (STYLE_ALL.find(function (x) { return x.v === v; }) || {}).l || v;
        pills.push(lab);
      });
      if (since && since.value) pills.push('Since ' + since.value);
      box.innerHTML = pills.map(function (p) {
        return '<span class="pill nd k">' + esc(p) + '</span>';
      }).join('');
    }

    function onPrimaryStyleChange() {
      var p = document.getElementById('primary_style')?.value || '';
      otherSel = otherSel.filter(function (v) { return v !== p; });
      styleChips();
      styleMenu();
      markDirty();
      updatePvStyles();
    }

    function onTattooingSinceChange() {
      markDirty();
      updatePvStyles();
    }

    styleChips();

    // Services
    var svcs = document.getElementById('svcs');
    var svcow = document.getElementById('svcow');
    var svcot = document.getElementById('svcot');
    function updatePvSvc() {
      var l = document.getElementById('pvsvc');
      if (!l) return;
      var v = [].filter.call(svcs.querySelectorAll('input'), function (i) {
        return i.checked && i.value !== 'Other';
      }).map(function (i) { return i.value; });
      if (document.getElementById('svco')?.checked && (svcot.value || '').trim()) {
        v.push(svcot.value.trim());
      }
      l.textContent = v.length ? 'Also: ' + v.join(' · ') : '';
      l.hidden = !v.length;
    }
    svcs.addEventListener('change', function () {
      svcow.hidden = !document.getElementById('svco').checked;
      updatePvSvc(); markDirty();
    });
    svcot?.addEventListener('input', function () { updatePvSvc(); markDirty(); });
    updatePvSvc();

    aboutForm.addEventListener('input', function (e) {
      if (!e.target.closest('#aboutForm')) return;
      markDirty();
    });
    aboutForm.addEventListener('change', function () { markDirty(); });

    // Select2 for year / primary style (use jQuery change — Select2 does not always hit native listeners)
    if (window.jQuery && jQuery.fn.select2) {
      jQuery('#aboutForm select.js-select2').each(function () {
        var $el = jQuery(this);
        if ($el.hasClass('select2-hidden-accessible')) return;
        var placeholder = $el.data('placeholder') || 'Select';
        var searchable = $el.data('searchable');
        var cfg = {
          width: '100%',
          placeholder: placeholder,
          allowClear: false,
          dropdownParent: $el.closest('.card').length ? $el.closest('.card') : jQuery('.mp-page')
        };
        if (searchable === false || searchable === 0 || searchable === 'false') {
          cfg.minimumResultsForSearch = Infinity;
        }
        $el.select2(cfg);
        $el.on('change.select2preview', function () {
          if (this.id === 'primary_style') onPrimaryStyleChange();
          else if (this.id === 'tattooing_since') onTattooingSinceChange();
          else markDirty();
        });
      });
    } else {
      document.getElementById('primary_style')?.addEventListener('change', onPrimaryStyleChange);
      document.getElementById('tattooing_since')?.addEventListener('change', onTattooingSinceChange);
    }

    function saveAbout(andLeave) {
      var btn = document.getElementById('aboutSaveBtn');
      if (btn) { btn.disabled = true; btn.classList.remove('on'); }
      syncOtherInputs();
      var fd = new FormData(aboutForm);
      if (croppedAvatarBlob) {
        fd.delete('avatar');
        fd.append('avatar', croppedAvatarBlob, 'avatar.jpg');
      }
      return fetch(UPDATE_URL, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: fd
      }).then(function (r) {
        return r.json().then(function (j) { return { ok: r.ok, status: r.status, j: j }; });
      }).then(function (res) {
        if (!res.ok || !res.j.success) {
          var msg = res.j.message || 'Could not save';
          if (res.j.errors) {
            var first = Object.values(res.j.errors)[0];
            if (first && first[0]) msg = first[0];
          }
          throw new Error(msg);
        }
        croppedAvatarBlob = null;
        setDirty(false);
        toast(res.j.message || 'Changes saved');
        if (andLeave) runPendingLeave();
      }).catch(function (e) {
        toast(e.message || 'Could not save');
        setDirty(true);
      });
    }

    document.getElementById('aboutSaveBtn')?.addEventListener('click', function () {
      saveAbout(false);
    });
    window.__mpSave = function (andLeave) { return saveAbout(andLeave); };
  }

  /* Dirty leave / refresh guard — same modal (no browser alert) */
  function dismissLeaveModal() {
    if (leaveEl) leaveEl.classList.remove('on');
    pendingNav = null;
    pendingReload = false;
  }
  document.getElementById('mpLeaveClose')?.addEventListener('click', dismissLeaveModal);
  leaveEl?.addEventListener('click', function (e) {
    if (e.target === leaveEl) dismissLeaveModal();
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && leaveEl?.classList.contains('on')) {
      e.preventDefault();
      dismissLeaveModal();
    }
  });
  document.getElementById('mpLeaveDiscard')?.addEventListener('click', function () {
    leaveEl.classList.remove('on');
    setDirty(false);
    runPendingLeave();
  });
  document.getElementById('mpLeaveSave')?.addEventListener('click', function () {
    leaveEl.classList.remove('on');
    if (typeof window.__mpSave === 'function') window.__mpSave(true);
  });

  document.addEventListener('click', function (e) {
    if (!dirty) return;
    var n = e.target.closest('aside a[href], .tabs a[href], a[href]:not([href="#"])');
    if (!n || n.closest('.pgsaverow') || n.closest('#mpLeave') || n.closest('#quickLinks')) return;
    if (n.getAttribute('target') === '_blank') return;
    if (n.closest('.head')) return;
    e.preventDefault();
    e.stopPropagation();
    showLeaveModal({ nav: n });
  }, true);

  document.addEventListener('keydown', function (e) {
    if (!dirty) return;
    var isReloadKey = e.key === 'F5' || ((e.ctrlKey || e.metaKey) && (e.key === 'r' || e.key === 'R'));
    if (!isReloadKey) return;
    e.preventDefault();
    e.stopPropagation();
    showLeaveModal({ reload: true });
  }, true);
})();
  </script>
@endsection
