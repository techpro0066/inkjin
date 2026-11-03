@extends('layouts.studio-dashboard-layout')

@section('title', 'Studio Page · Portfolio')

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css">
<style>
  .studio-page .tog{width:38px;height:22px;border-radius:12px;background:#D9D2DD;position:relative;flex:none;cursor:pointer;display:inline-block}
  .studio-page .tog:after{content:'';position:absolute;width:16px;height:16px;border-radius:50%;background:#fff;top:3px;left:3px}
  .studio-page .tog.on{background:var(--pri)}.studio-page .tog.on:after{left:19px}
  .studio-page .btns{display:flex;gap:10px;flex-wrap:wrap;align-items:center}
  .studio-page .ql{position:relative}
  .studio-page .ql-b{display:inline-flex;align-items:center;gap:8px;font:inherit;font-weight:600;font-size:13.5px;padding:9px 12px 9px 13px;border-radius:12px;border:1px solid #E8DFEA;background:#fff;color:var(--ink);cursor:pointer;white-space:nowrap}
  .studio-page .ql-b:hover{background:#F7F3F9}
  .studio-page .ql-b:focus-visible{outline:2px solid #3E007C;outline-offset:2px}
  .studio-page .ql-b .ms{font-size:18px}.studio-page .ql-b .cv{font-size:20px}
  .studio-page .ql-p{position:absolute;right:0;top:calc(100% + 8px);width:min(400px,calc(100vw - 32px));background:#fff;border:1px solid #EAE2EE;border-radius:16px;box-shadow:0 18px 44px rgba(20,0,40,.18);padding:6px;z-index:80}
  .studio-page .ql-p[hidden]{display:none}
  .studio-page .ql-r{display:flex;align-items:center;gap:6px;padding:4px 4px 4px 12px;border-radius:10px}
  .studio-page .ql-r:hover{background:#F7F3F9}
  .studio-page .ql-u{flex:1;min-width:0;font-size:13.5px;color:var(--ink);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
  .studio-page .ql-u b{font-weight:700}
  .studio-page .ql-i{width:34px;height:34px;border-radius:9px;border:0;background:none;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;color:var(--ink);flex-shrink:0;text-decoration:none}
  .studio-page .ql-i:hover{background:#EFE7F3}
  .studio-page .ql-i .ms{font-size:18px}.studio-page .ql-i.done .ms{color:#1F8A4C}
  .studio-page .pgsave{opacity:.4;pointer-events:none}
  .studio-page .pgsave.is-dirty{opacity:1;pointer-events:auto}
  .studio-page .pgsave:disabled{cursor:wait}
  .studio-page .tabs a.is-soon{opacity:.55;cursor:default}
  .studio-page .smodal{position:fixed;inset:0;background:rgba(20,10,30,.45);display:flex;align-items:center;justify-content:center;padding:24px;z-index:200}
  .studio-page .smodal[hidden]{display:none!important}
  .studio-page .smbox{background:#fff;border-radius:18px;width:460px;max-width:100%;box-shadow:0 20px 60px rgba(0,0,0,.25);display:flex;flex-direction:column}
  .studio-page .smh{display:flex;justify-content:space-between;align-items:flex-start;gap:12px;padding:20px 22px 12px}
  .studio-page .smh h2{margin:0;font-size:19px;font-weight:800}
  .studio-page .smh .x{border:0;background:none;cursor:pointer;color:var(--faint);padding:2px}
  .studio-page .smf{display:flex;gap:10px;justify-content:flex-end;flex-wrap:wrap;padding:14px 22px;border-top:1px solid var(--line)}
  .studio-page .spg{display:grid;grid-template-columns:repeat(auto-fill,minmax(170px,1fr));gap:12px}
  .studio-page .spf{position:relative;border:1px solid var(--line);border-radius:12px;overflow:hidden;background:#fff;display:flex;flex-direction:column}
  .studio-page .spi{aspect-ratio:4/5;background:#F3F0F5 center/contain no-repeat}
  .studio-page .spc{padding:8px 10px 10px;display:flex;flex-direction:column;gap:6px;flex:1}
  .studio-page .spc b{display:block;font-size:12.5px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  .studio-page .in.sm{padding:6px 8px;font-size:12px;min-height:0;width:100%}
  .studio-page select.in.sm.needs-tag{border-color:#F3C98B;background:#FFF6EC}
  .studio-page .sp-actions{display:flex;align-items:center;gap:12px;padding-top:8px;margin-top:2px;border-top:1px solid #F3EEF4}
  .studio-page .sp-act{border:0;background:none;cursor:pointer;padding:2px;color:#5A5360;display:inline-flex;align-items:center;justify-content:center}
  .studio-page .sp-act .ms{font-size:18px}
  .studio-page .sp-act.edit:hover{color:var(--pri)}
  .studio-page .sp-act.del{color:#C62828}
  .studio-page .sp-act.del:hover{opacity:.85}
  .studio-page .spadd{border:1.5px dashed #CDBBD9;border-radius:12px;background:#FBF6FD;color:#3E007C;font:inherit;font-weight:700;font-size:13px;display:flex;flex-direction:column;gap:6px;align-items:center;justify-content:center;min-height:190px;cursor:pointer;width:100%}
  .studio-page .spadd .ms{font-size:26px}
  .studio-page .igb{position:absolute;top:6px;left:6px;background:linear-gradient(135deg,#F58529,#DD2A7B 55%,#8134AF);color:#fff;font-size:10px;font-weight:800;border-radius:6px;padding:2px 6px;z-index:2}
  .studio-page .igb.static{position:static;display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:10px;font-size:12px;padding:0}
  .studio-page .empty{font-size:13.5px;color:var(--muted);padding:4px 0 8px}
  .studio-page .spf .spi{cursor:pointer}
  .studio-page .chips{display:flex;flex-wrap:wrap;gap:8px}
  .studio-page .chip{border:1px solid var(--line);background:#fff;border-radius:20px;padding:6px 12px;font-size:12.5px;font-weight:600;color:var(--muted)}
  .studio-page .grid{display:grid}
  .studio-page .row{display:flex;align-items:center;gap:10px}
  .modal-backdrop{position:fixed;inset:0;z-index:500;display:none;align-items:center;justify-content:center;background:rgba(20,10,30,.45);padding:16px;opacity:0;transition:opacity .18s}
  .modal-backdrop.modal-visible{display:flex}
  .modal-backdrop.modal-visible:not(.modal-open){pointer-events:none}
  .modal-backdrop.modal-open{opacity:1;pointer-events:auto}
  #addWorkModal .modal.xl{width:min(1040px,100%);max-width:100%;max-height:calc(100vh - 48px);max-height:calc(100dvh - 48px);background:#fff;border-radius:22px;box-shadow:0 20px 60px rgba(0,0,0,.25);display:flex;flex-direction:column;overflow:hidden}
  #addWorkModal .mh{padding:22px 24px 14px;border-bottom:1px solid #F0EAF2;display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-shrink:0}
  #addWorkModal .mh h2{font-size:18px;font-weight:800;margin:0}
  #addWorkModal .mx{border:0;background:none;cursor:pointer;color:var(--muted);width:36px;height:36px;border-radius:10px;display:inline-flex;align-items:center;justify-content:center}
  #addWorkModal .mx:hover{background:#F7F3F9;color:var(--ink)}
  #addWorkModal .mb{padding:18px 24px;flex:1 1 auto;min-height:0;overflow-y:auto}
  #addWorkModal .mf{display:flex;gap:10px;justify-content:flex-end;padding:16px 24px;background:#FCF6FE;border-top:1px solid #F0E4F5;flex-shrink:0}
  #addWorkModal .aw-grid{grid-template-columns:300px 1fr;gap:26px;align-items:start}
  #addWorkModal .aw-half{grid-template-columns:1fr 1fr;gap:12px}
  #addWorkModal .ai{display:inline-flex;align-items:center;gap:3px;background:#F3E8FF;color:#6A2BB8;border-radius:6px;font-size:10.5px;font-weight:700;padding:1px 6px;margin-left:6px}
  #addWorkModal .ai .ms{font-size:12px}
  #addWorkModal .cnt2{margin-left:auto;background:#F0EDF2;border-radius:6px;font-size:10.5px;font-weight:700;padding:2px 7px;color:#6F6874}
  #addWorkModal .chips{display:flex;flex-wrap:wrap;gap:8px}
  #addWorkModal .chip.style-chip{
    display:inline-flex;align-items:center;gap:4px;
    padding:8px 12px;border-radius:12px;font:inherit;font-size:12px;font-weight:600;
    border:1px solid rgba(122,117,131,.35);background:#fff;color:#494552;cursor:pointer;
    transition:border-color .2s,background .2s,color .2s,box-shadow .2s
  }
  #addWorkModal .chip.style-chip:hover:not(:disabled){border-color:rgba(26,26,26,.35);background:#f8f1fb}
  #addWorkModal .chip.style-chip.is-selected,#addWorkModal .chip.style-chip.sel{
    background:#e8ddff;border-color:#310f7a;color:#310f7a;box-shadow:0 1px 2px rgba(49,15,122,.12)
  }
  #addWorkModal .chip.style-chip:disabled{opacity:.42;cursor:not-allowed}
  #addWorkModal .aw-banner{border:1px solid #F3C9C9;background:#FDECEC;color:#C62828;border-radius:12px;padding:10px 12px;font-size:12.5px;margin-bottom:14px}
  #addWorkModal .work-field-error{color:#C62828;font-size:12px;margin-top:6px}
  #addWorkModal .aw-img{border-radius:14px;overflow:hidden;position:relative;cursor:pointer}
  #addWorkModal .aw-img.work-image-upload--empty{border:1.5px dashed var(--line);background:#FAF7FB;aspect-ratio:4/5;display:flex;align-items:center;justify-content:center}
  #addWorkModal .aw-img.work-image-upload--has-image{border:1px solid var(--line);max-width:300px}
  #addWorkModal .aw-img-empty{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:6px;padding:24px;text-align:center}
  #addWorkModal .aw-img-empty .ms{font-size:36px;color:var(--faint)}
  #addWorkModal .aw-img-frame{aspect-ratio:4/5;background:#EEE;position:relative;overflow:hidden;border-radius:14px}
  #addWorkModal .aw-img-frame img{width:100%;height:100%;object-fit:cover;display:block}
  #addWorkModal .aw-ai-pill{position:absolute;left:10px;bottom:10px;background:#fff;color:#6A2BB8;z-index:2}
  #addWorkModal .aw-img-meta{justify-content:space-between;margin-top:10px}
  #addWorkModal .aw-replace{border:0;background:none;font:inherit;font-size:12.5px;font-weight:600;color:var(--ink);cursor:pointer;padding:0}
  #addWorkModal .hidden{display:none!important}
  #addWorkModal .tog.toggle-switch{width:38px;height:22px;border-radius:12px;background:#D9D2DD;position:relative;flex-shrink:0;cursor:pointer}
  #addWorkModal .tog.toggle-switch::after{content:'';position:absolute;width:16px;height:16px;border-radius:50%;background:#fff;top:3px;left:3px}
  #addWorkModal .tog.toggle-switch.active,#addWorkModal .tog.toggle-switch.on{background:var(--pri)}
  #addWorkModal .tog.toggle-switch.active::after,#addWorkModal .tog.toggle-switch.on::after{left:19px}
  #addWorkModal textarea.in{display:block;line-height:1.45;padding:10px 13px}
  #addWorkModal .work-ai-overlay{display:none;position:absolute;inset:0;background:rgba(20,10,30,.55);color:#fff;align-items:center;justify-content:center;flex-direction:column;gap:8px;z-index:3;text-align:center;padding:16px}
  #addWorkModal .aw-img.is-ai .work-ai-overlay{display:flex}
  #deletePieceModal .del-modal{width:min(420px,100%);background:#fff;border-radius:18px;box-shadow:0 20px 60px rgba(0,0,0,.25);overflow:hidden}
  #deletePieceModal .del-body{padding:22px 22px 8px;display:flex;gap:14px;align-items:flex-start}
  #deletePieceModal .del-ico{width:44px;height:44px;border-radius:14px;background:#FDECEC;color:#C62828;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0}
  #deletePieceModal .del-ico .ms{font-size:24px}
  #deletePieceModal .del-body h2{margin:0;font-size:18px;font-weight:800}
  #deletePieceModal .del-body p{margin:6px 0 0;font-size:13.5px;line-height:1.5;color:var(--muted)}
  #deletePieceModal .del-err{margin:10px 0 0;font-size:12.5px;font-weight:600;color:#C62828}
  #deletePieceModal .del-err[hidden]{display:none!important}
  #deletePieceModal .del-foot{display:flex;gap:10px;justify-content:flex-end;flex-wrap:wrap;padding:16px 22px 20px;border-top:1px solid var(--line);margin-top:14px}
  #deletePieceModal .btn-del{background:#C62828;color:#fff}
  #deletePieceModal .btn-del:hover{opacity:.92}
  #deletePieceModal .btn-del:disabled{opacity:.6;cursor:wait}
  .crop-modal-backdrop{display:none;position:fixed;inset:0;z-index:600;background:rgba(0,0,0,.78);align-items:center;justify-content:center;padding:16px}
  .crop-modal-backdrop.is-open{display:flex}
  .crop-modal-inner{width:100%;max-width:560px;max-height:min(92vh,900px);display:flex;flex-direction:column;background:#fff;border-radius:22px;box-shadow:0 20px 60px rgba(0,0,0,.25);overflow:hidden}
  #workCropModal .mh{padding:22px 24px 14px;border-bottom:1px solid #F0EAF2;display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-shrink:0}
  #workCropModal .mh h2{font-size:18px;font-weight:800;margin:0}
  #workCropModal .mx{border:0;background:none;cursor:pointer;color:var(--muted);width:36px;height:36px;border-radius:10px;display:inline-flex;align-items:center;justify-content:center}
  #workCropModal .mx:hover{background:#F7F3F9;color:var(--ink)}
  #workCropModal .mf{display:flex;gap:10px;justify-content:flex-end;padding:16px 24px;background:#FCF6FE;border-top:1px solid #F0E4F5;flex-shrink:0}
  #workCropModal .mf .btn{min-width:120px;justify-content:center}
  .cropper-stage-wrap{flex:1;min-height:200px;max-height:min(58vh,520px);background:#1a1a1a;border-radius:12px;overflow:hidden;margin:12px 16px}
  .cropper-stage-wrap img{max-height:min(58vh,520px);display:block}
  .cropper-stage-wrap .cropper-container,.cropper-stage-wrap .cropper-wrap-box,.cropper-stage-wrap .cropper-canvas img{max-height:min(58vh,520px)}
  @media (max-width:900px){#addWorkModal .aw-grid{grid-template-columns:1fr!important}#addWorkModal .aw-half{grid-template-columns:1fr!important}}
  @media (max-width:700px){.studio-page .head .btns{width:100%}}
  @media (max-width:600px){.studio-page .ql-p{right:auto;left:0}}
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
  $showArtistsFlash = $showArtistsFlash ?? true;
  $showArtistsPortfolio = $showArtistsPortfolio ?? true;
  $artistWorkPreview = $artistWorkPreview ?? collect();
  $studioPieces = $studioPieces ?? collect();
  $taggableArtists = $taggableArtists ?? collect();
  $instagramConnected = $instagramConnected ?? false;
  $instagramUsername = $instagramUsername ?? '';
  $instagramImportCount = $instagramImportCount ?? 0;
@endphp
<div class="studio-page">
  <div class="head">
    <div>
      <h1>
        Studio Page
        <a class="help-q" href="https://help.inkjin.com/en/articles/17338786-studio-dashboard-studio-page-portfolio" target="_blank" rel="noopener" title="Help with this page" aria-label="Help with this page"><span class="ms">help</span></a>
      </h1>
      <div class="sub">What clients see at {{ $pageUrl }}.</div>
    </div>
    <div class="btns">
      <div class="ql" id="quickLinks">
        <button type="button" class="ql-b" id="quickLinksBtn" aria-haspopup="true" aria-expanded="false" aria-controls="quickLinksMenu">
          <span class="ms">link</span>Quick links<span class="ms cv">expand_more</span>
        </button>
        <div class="ql-p" id="quickLinksMenu" hidden role="menu" aria-label="Quick links">
          @foreach ($quickLinkRows as $row)
            @php
              $displayPath = $pageUrl.$row['suffix'];
              $copyUrl = $publicPageUrl.$row['suffix'];
              $openUrl = $publicPageUrl.($row['hash'] ?? '');
            @endphp
            <div class="ql-r" role="none">
              <div class="ql-u" title="{{ $displayPath }}">
                {{ $pageUrl }}@if($row['suffix'] !== '')<b>{{ $row['suffix'] }}</b>@endif
              </div>
              <button type="button" class="ql-i" data-ql-copy="{{ $copyUrl }}" data-ql-label="{{ $displayPath }}" role="menuitem" title="Copy link">
                <span class="ms">content_copy</span>
              </button>
              <a class="ql-i" href="{{ $openUrl }}" target="_blank" rel="noopener" role="menuitem" title="Open">
                <span class="ms">open_in_new</span>
              </a>
            </div>
          @endforeach
        </div>
      </div>
      <a class="btn" href="{{ $publicPageUrl }}" target="_blank" rel="noopener">
        <span class="ms">open_in_new</span>Open studio page
      </a>
    </div>
  </div>

  @include('studio.page.partials.tabs')

  @if (session('success'))
    <div class="note" style="margin-bottom:14px"><span class="ms">check_circle</span>{{ session('success') }}</div>
  @endif
  @if (session('error'))
    <div class="note" style="margin-bottom:14px;border-color:#F3C9C9;background:#FDECEC;color:#C62828"><span class="ms">error</span>{{ session('error') }}</div>
  @endif

  <div class="card" style="margin-bottom:14px">
    <div class="ch">
      <div>
        <h3>Your artists' work</h3>
        <div class="faint" style="font-size:12.5px;margin-top:2px">Shown on your studio page automatically</div>
      </div>
    </div>
    <div style="padding:18px 22px">
      <div class="row" style="justify-content:space-between;padding:2px 0 10px">
        <span style="font-size:13.5px;font-weight:600">Show artists' flash designs</span>
        <div class="tog {{ $showArtistsFlash ? 'on' : '' }}" id="togFlash" role="switch" aria-checked="{{ $showArtistsFlash ? 'true' : 'false' }}" tabindex="0"></div>
      </div>
      <div class="row" style="justify-content:space-between;padding:0 0 14px">
        <span style="font-size:13.5px;font-weight:600">Show artists' portfolio pieces</span>
        <div class="tog {{ $showArtistsPortfolio ? 'on' : '' }}" id="togPortfolio" role="switch" aria-checked="{{ $showArtistsPortfolio ? 'true' : 'false' }}" tabindex="0"></div>
      </div>
      <div class="spg" id="artistWorkPreview">
        @forelse ($artistWorkPreview as $item)
          <div class="spf">
            <div class="spi" @if($item['image']) style="background-image:url('{{ $item['image'] }}')" @endif></div>
            <div class="spc">
              <b>{{ $item['title'] }}</b>
              <div class="faint" style="font-size:11.5px">{{ $item['subtitle'] }}</div>
            </div>
          </div>
        @empty
          <div class="empty" style="grid-column:1/-1">No flash or portfolio pieces from your artists yet.</div>
        @endforelse
      </div>
      <div class="help">Every piece shows the name of the artist who made it and links to their page. Artists add and edit their own work in their dashboard.</div>
    </div>
  </div>

  <div class="card" style="margin-bottom:14px">
    <div class="ch">
      <div>
        <h3>Studio portfolio</h3>
        <div class="faint" style="font-size:12.5px;margin-top:2px">Optional. Your own pieces, like work done at the studio or by past guest artists.</div>
      </div>
    </div>
    <div style="padding:18px 22px">
      <div class="row" style="gap:10px;padding:12px 14px;border:1px solid var(--line);border-radius:12px;flex-wrap:wrap;margin-bottom:8px" id="igBox">
        <span class="igb static">IG</span>
        @if ($instagramConnected)
          <div style="flex:1;min-width:200px">
            <b style="font-size:13.5px">@{{ ltrim($instagramUsername, '@') }}</b>
            <span class="pill g">Connected</span>
            <div class="faint" style="font-size:12px;margin-top:2px">{{ $instagramImportCount }} posts imported. New pieces come in as “Tag the artist” so each one is credited to the right person.</div>
          </div>
          <button type="button" class="btn ghost sm" id="igRefreshBtn">Refresh</button>
          <button type="button" class="btn ghost sm" id="igDisconnectBtn">Disconnect</button>
        @else
          <div style="flex:1;min-width:200px">
            <b style="font-size:13.5px">Instagram</b>
            <div class="faint" style="font-size:12px;margin-top:2px">Connect to import posts into your studio portfolio.</div>
          </div>
          <a class="btn ghost sm" href="{{ $instagramConnectUrl }}">Connect</a>
        @endif
      </div>
      <div class="help" style="margin:8px 0 14px">Tag a piece with an artist and it also shows their name, with “Request similar tattoo”. Leave it as Studio and it shows your studio name. Pieces waiting for a tag don’t show on your page.</div>

      <div class="spg" id="studioPiecesGrid">
        @foreach ($studioPieces as $piece)
          <div class="spf" data-id="{{ $piece['id'] }}" data-piece='@json($piece)'>
            @if ($piece['from_instagram'])
              <span class="igb">IG</span>
            @endif
            <div class="spi" data-edit @if($piece['image']) style="background-image:url('{{ $piece['image'] }}')" @endif></div>
            <div class="spc">
              <b data-edit style="cursor:pointer">{{ $piece['title'] }}</b>
              <select class="in sm {{ $piece['attribution'] === 'pending' ? 'needs-tag' : '' }}" aria-label="Artist" data-attr>
                <option value="pending" @selected($piece['attribution'] === 'pending')>Tag the artist</option>
                <option value="studio" @selected($piece['attribution'] === 'studio')>Studio (no artist)</option>
                @foreach ($taggableArtists as $artist)
                  <option value="artist:{{ $artist['user_id'] }}" @selected($piece['attribution'] === 'artist' && (int) $piece['artist_user_id'] === (int) $artist['user_id'])>{{ $artist['name'] }}</option>
                @endforeach
              </select>
              <div class="sp-actions">
                <button type="button" class="sp-act edit" data-edit aria-label="Edit" title="Edit"><span class="ms">edit</span></button>
                <button type="button" class="sp-act del" data-delete aria-label="Delete" title="Delete"><span class="ms">delete</span></button>
              </div>
            </div>
          </div>
        @endforeach
        <button type="button" class="spadd" id="addPiecesBtn">
          <span class="ms">add_photo_alternate</span>
          Add work
        </button>
      </div>
    </div>
  </div>

  @include('studio.page.partials.portfolio-work-modal')

  <div class="modal-backdrop" id="deletePieceModal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="deletePieceTitle">
    <div class="del-modal">
      <div class="del-body">
        <div class="del-ico" aria-hidden="true"><span class="ms">delete_forever</span></div>
        <div>
          <h2 id="deletePieceTitle">Delete this piece?</h2>
          <p>This permanently removes it from your studio portfolio. You can’t undo this.</p>
          <p class="del-err" id="deletePieceError" hidden></p>
        </div>
      </div>
      <div class="del-foot">
        <button type="button" class="btn ghost" id="btnDeletePieceCancel">Cancel</button>
        <button type="button" class="btn btn-del" id="btnDeletePieceConfirm"><span class="ms">delete</span><span class="del-label">Delete</span></button>
      </div>
    </div>
  </div>

  <div id="workCropModal" class="crop-modal-backdrop" aria-hidden="true">
    <div class="crop-modal-inner">
      <div class="mh">
        <div>
          <h2>Crop image</h2>
          <div class="muted" style="font-size:13px;margin-top:4px">Output <b style="color:var(--ink);font-weight:700">1080 × 1350 px</b> · ratio 4:5</div>
        </div>
        <button type="button" class="mx" id="btnCropClose" aria-label="Close cropper"><span class="ms">close</span></button>
      </div>
      <div class="cropper-stage-wrap">
        <img id="workCropperImg" src="" alt="" style="max-width:100%">
      </div>
      <div class="mf">
        <button type="button" class="btn ghost" id="btnCropCancel">Cancel</button>
        <button type="button" class="btn" id="btnCropApply"><span class="ms">check</span>Apply crop</button>
      </div>
    </div>
  </div>

  <div class="pgsaverow">
    <button class="btn pgsave" type="button" id="pageSaveBtn"><span class="ms">save</span>Save changes</button>
  </div>
</div>

<div class="smodal" id="leaveguard" hidden>
  <div class="smbox" role="dialog" aria-modal="true" aria-labelledby="leaveguardTitle">
    <div class="smh">
      <div>
        <h2 id="leaveguardTitle">Your changes are not saved</h2>
        <div class="muted" style="font-size:13.5px;margin-top:4px">Save them before you leave this page?</div>
      </div>
      <button type="button" class="x" data-close aria-label="Close"><span class="ms">close</span></button>
    </div>
    <div class="smf">
      <button type="button" class="btn ghost" id="lg-leave">Leave without saving</button>
      <button type="button" class="btn" id="lg-save">Save and leave</button>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js"></script>
<script>
(function () {
  var root = document.querySelector('.studio-page');
  if (!root) return;

  var CSRF = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  var SAVE_URL = @json($updatePortfolioUrl ?? route('studio.page.portfolio.update'));
  var STORE_PIECE_URL = @json($storePieceUrl ?? route('studio.page.portfolio.piece.store'));
  var AI_SUGGEST_URL = @json($aiSuggestUrl ?? route('studio.page.portfolio.ai-suggest'));
  var REFRESH_URL = @json($instagramRefreshUrl ?? route('studio.page.portfolio.instagram.refresh'));
  var DISCONNECT_URL = @json($instagramDisconnectUrl ?? route('studio.page.portfolio.instagram.disconnect'));
  var AUTO_IMPORT = @json($autoImportInstagram ?? false);

  var saveBtn = document.getElementById('pageSaveBtn');
  var dirty = false;
  var pendingHref = null;
  var saving = false;
  var deletedIds = [];
  var saveBtnDefaultHtml = saveBtn ? saveBtn.innerHTML : '';
  var lgSave = document.getElementById('lg-save');
  var lgSaveDefaultHtml = lgSave ? lgSave.innerHTML : '';
  var workImageFile = null;
  var aiFilled = false;
  var workCropper = null;
  var CROP_OUT_W = 1080;
  var CROP_OUT_H = 1350;
  var CROP_RATIO = 4 / 5;
  var MAX_FILE_BYTES = 10 * 1024 * 1024;
  var cropModal = document.getElementById('workCropModal');
  var cropperImg = document.getElementById('workCropperImg');

  function toast(msg, ok) {
    if (typeof window.bpToast === 'function') { window.bpToast(msg); return; }
    var n = window.innerWidth <= 900;
    var t = document.createElement('div');
    t.setAttribute('role', 'status');
    t.style.cssText = 'position:fixed;top:' + (n ? '74px' : '22px') + ';' + (n ? 'left:16px;right:16px;' : 'right:24px;') + 'background:#1A1A1A;color:#fff;padding:12px 18px;border-radius:10px;font-weight:600;font-size:14px;z-index:99;box-shadow:0 10px 30px rgba(0,0,0,.22);display:flex;gap:10px;align-items:center';
    t.innerHTML = '<span class="ms" style="color:' + (ok === false ? '#FF6B6B' : '#3DD68C') + '">' + (ok === false ? 'error' : 'check_circle') + '</span>' + msg;
    document.body.appendChild(t);
    setTimeout(function () { t.remove(); }, 2800);
  }

  function setDirty(v) {
    dirty = !!v;
    if (saveBtn) saveBtn.classList.toggle('is-dirty', dirty);
  }

  function openLeave() { var m = document.getElementById('leaveguard'); if (m) m.hidden = false; }
  function closeLeave() { var m = document.getElementById('leaveguard'); if (m) m.hidden = true; }

  function setSavingUi(on) {
    saving = !!on;
    if (saveBtn) {
      saveBtn.disabled = on;
      saveBtn.setAttribute('aria-busy', on ? 'true' : 'false');
      if (on) {
        saveBtn.classList.add('is-dirty');
        saveBtn.innerHTML = '<span class="ms">hourglass_top</span>Saving…';
      } else {
        saveBtn.innerHTML = saveBtnDefaultHtml;
      }
    }
    if (lgSave) {
      lgSave.disabled = on;
      if (on) lgSave.textContent = 'Saving…';
      else lgSave.innerHTML = lgSaveDefaultHtml;
    }
  }

  function bindTog(el) {
    if (!el) return;
    function flip() {
      el.classList.toggle('on');
      el.setAttribute('aria-checked', el.classList.contains('on') ? 'true' : 'false');
      setDirty(true);
    }
    el.addEventListener('click', flip);
    el.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); flip(); }
    });
  }
  bindTog(document.getElementById('togFlash'));
  bindTog(document.getElementById('togPortfolio'));

  function parseAttr(select) {
    var v = select.value || 'pending';
    if (v === 'studio') return { attribution: 'studio', artist_user_id: null };
    if (v.indexOf('artist:') === 0) {
      return { attribution: 'artist', artist_user_id: parseInt(v.slice(7), 10) || null };
    }
    return { attribution: 'pending', artist_user_id: null };
  }

  var pendingDeleteCard = null;
  var pendingDeleteUrl = '';
  var $deleteModal = document.getElementById('deletePieceModal');

  function openDeletePieceModal(card) {
    var raw = card.getAttribute('data-piece') || '{}';
    var piece = {};
    try { piece = JSON.parse(raw); } catch (err) { piece = {}; }
    pendingDeleteCard = card;
    pendingDeleteUrl = piece.delete_url || '';
    var err = document.getElementById('deletePieceError');
    if (err) { err.hidden = true; err.textContent = ''; }
    var btn = document.getElementById('btnDeletePieceConfirm');
    if (btn) {
      btn.disabled = false;
      var label = btn.querySelector('.del-label');
      if (label) label.textContent = 'Delete';
      var ico = btn.querySelector('.ms');
      if (ico) ico.textContent = 'delete';
    }
    if (!$deleteModal) return;
    if ($deleteModal.parentElement !== document.body) document.body.appendChild($deleteModal);
    $deleteModal.classList.add('modal-visible');
    $deleteModal.setAttribute('aria-hidden', 'false');
    requestAnimationFrame(function () { $deleteModal.classList.add('modal-open'); });
  }

  function closeDeletePieceModal() {
    if (!$deleteModal) return;
    $deleteModal.classList.remove('modal-open');
    setTimeout(function () {
      $deleteModal.classList.remove('modal-visible');
      $deleteModal.setAttribute('aria-hidden', 'true');
      pendingDeleteCard = null;
      pendingDeleteUrl = '';
    }, 160);
  }

  function confirmDeletePiece() {
    if (!pendingDeleteUrl || !pendingDeleteCard) return;
    var btn = document.getElementById('btnDeletePieceConfirm');
    var err = document.getElementById('deletePieceError');
    if (err) { err.hidden = true; err.textContent = ''; }
    if (btn) {
      btn.disabled = true;
      var label = btn.querySelector('.del-label');
      if (label) label.textContent = 'Deleting…';
      var ico = btn.querySelector('.ms');
      if (ico) ico.textContent = 'hourglass_top';
    }
    var card = pendingDeleteCard;
    fetch(pendingDeleteUrl, {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': CSRF,
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    }).then(function (r) { return r.json().then(function (j) { return { ok: r.ok, j: j }; }); })
      .then(function (res) {
        if (res.ok && res.j.success) {
          card.remove();
          closeDeletePieceModal();
          toast(res.j.message || 'Work deleted');
          return;
        }
        if (err) {
          err.textContent = (res.j && res.j.message) || 'Could not delete';
          err.hidden = false;
        }
        if (btn) {
          btn.disabled = false;
          var label2 = btn.querySelector('.del-label');
          if (label2) label2.textContent = 'Delete';
          var ico2 = btn.querySelector('.ms');
          if (ico2) ico2.textContent = 'delete';
        }
      })
      .catch(function () {
        if (err) { err.textContent = 'Network error. Try again.'; err.hidden = false; }
        if (btn) {
          btn.disabled = false;
          var label3 = btn.querySelector('.del-label');
          if (label3) label3.textContent = 'Delete';
          var ico3 = btn.querySelector('.ms');
          if (ico3) ico3.textContent = 'delete';
        }
      });
  }

  function wirePiece(card) {
    var sel = card.querySelector('[data-attr]');
    var del = card.querySelector('[data-delete]');
    if (sel) {
      sel.addEventListener('change', function () {
        sel.classList.toggle('needs-tag', sel.value === 'pending');
        setDirty(true);
      });
    }
    if (del) {
      del.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        openDeletePieceModal(card);
      });
    }
    card.querySelectorAll('[data-edit]').forEach(function (el) {
      el.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        var raw = card.getAttribute('data-piece') || '{}';
        var piece = {};
        try { piece = JSON.parse(raw); } catch (err) { piece = { id: card.getAttribute('data-id') }; }
        openEditWorkModal(piece);
      });
    });
  }
  document.querySelectorAll('#studioPiecesGrid .spf[data-id]').forEach(wirePiece);

  document.getElementById('btnDeletePieceCancel')?.addEventListener('click', closeDeletePieceModal);
  document.getElementById('btnDeletePieceConfirm')?.addEventListener('click', confirmDeletePiece);
  $deleteModal?.addEventListener('click', function (e) {
    if (e.target === $deleteModal) closeDeletePieceModal();
  });

  function collectFormData() {
    var fd = new FormData();
    fd.append('show_artists_flash', document.getElementById('togFlash')?.classList.contains('on') ? '1' : '0');
    fd.append('show_artists_portfolio', document.getElementById('togPortfolio')?.classList.contains('on') ? '1' : '0');
    var pieces = [];
    document.querySelectorAll('#studioPiecesGrid .spf[data-id]').forEach(function (card) {
      var sel = card.querySelector('[data-attr]');
      var parsed = parseAttr(sel);
      pieces.push({
        id: parseInt(card.getAttribute('data-id'), 10),
        title: (card.querySelector('.spc b')?.textContent || '').trim(),
        attribution: parsed.attribution,
        artist_user_id: parsed.artist_user_id
      });
    });
    pieces.forEach(function (p, i) {
      fd.append('pieces[' + i + '][id]', p.id);
      fd.append('pieces[' + i + '][title]', p.title);
      fd.append('pieces[' + i + '][attribution]', p.attribution);
      if (p.artist_user_id) fd.append('pieces[' + i + '][artist_user_id]', p.artist_user_id);
    });
    deletedIds.forEach(function (id, i) { fd.append('deleted_ids[' + i + ']', id); });
    return fd;
  }

  /* ---- Add / edit work modal ---- */
  var $modal = document.getElementById('addWorkModal');
  function openModal() {
    if (!$modal) return;
    /* Reparent out of scrolling <main> so position:fixed is not clipped */
    if ($modal.parentElement !== document.body) document.body.appendChild($modal);
    $modal.classList.add('modal-visible');
    $modal.setAttribute('aria-hidden', 'false');
    requestAnimationFrame(function () { $modal.classList.add('modal-open'); });
    document.body.style.overflow = 'hidden';
  }
  function closeModal() {
    if (!$modal) return;
    $modal.classList.remove('modal-open');
    setTimeout(function () {
      $modal.classList.remove('modal-visible');
      $modal.setAttribute('aria-hidden', 'true');
      document.body.style.overflow = '';
    }, 160);
  }
  function clearErrors() {
    document.querySelectorAll('#addWorkModal .work-field-error').forEach(function (el) {
      el.textContent = '';
      el.classList.add('hidden');
    });
    var banner = document.getElementById('workFormBanner');
    if (banner) { banner.textContent = ''; banner.classList.add('hidden'); }
  }
  function showFieldError(key, msg) {
    var el = document.querySelector('#addWorkModal [data-error-for="' + key + '"]');
    if (!el) return;
    el.textContent = msg;
    el.classList.remove('hidden');
  }
  function setOtherStyles(values) {
    values = (values || []).slice(0, 2);
    var select = document.getElementById('workOtherStyles');
    Array.from(select.options).forEach(function (opt) { opt.selected = values.indexOf(opt.value) > -1; });
    document.querySelectorAll('#workOtherStylesChips .chip').forEach(function (chip) {
      var on = values.indexOf(chip.getAttribute('data-value')) > -1;
      chip.classList.toggle('is-selected', on);
      chip.classList.toggle('sel', on);
      chip.setAttribute('aria-pressed', on ? 'true' : 'false');
    });
    document.getElementById('workOtherStylesCount').textContent = String(values.length);
    syncOtherDisabled();
  }
  function syncOtherDisabled() {
    var primary = document.getElementById('workPrimaryStyle').value;
    var selected = Array.from(document.getElementById('workOtherStyles').selectedOptions).map(function (o) { return o.value; });
    document.querySelectorAll('#workOtherStylesChips .chip').forEach(function (chip) {
      var v = chip.getAttribute('data-value');
      chip.disabled = v === primary || (!chip.classList.contains('is-selected') && selected.length >= 2);
    });
  }
  function setPlacement(value) {
    value = value || '';
    document.getElementById('workPlacement').value = value;
    document.querySelectorAll('#workPlacementsChips .chip').forEach(function (chip) {
      var on = chip.getAttribute('data-value') === value;
      chip.classList.toggle('is-selected', on);
      chip.classList.toggle('sel', on);
      chip.setAttribute('aria-pressed', on ? 'true' : 'false');
    });
    document.getElementById('workPlacementsCount').textContent = value ? '1' : '0';
  }
  function setImagePreview(url) {
    var wrap = document.getElementById('workImageUpload');
    var empty = document.getElementById('workImageUploadEmpty');
    var preview = document.getElementById('workImageUploadPreview');
    var img = document.getElementById('workImagePreviewImg');
    if (!url) {
      wrap.classList.add('work-image-upload--empty');
      wrap.classList.remove('work-image-upload--has-image');
      empty.classList.remove('hidden');
      preview.classList.add('hidden');
      img.src = '';
      return;
    }
    wrap.classList.remove('work-image-upload--empty');
    wrap.classList.add('work-image-upload--has-image');
    empty.classList.add('hidden');
    preview.classList.remove('hidden');
    img.src = url;
  }
  function resetWorkForm() {
    clearErrors();
    workImageFile = null;
    aiFilled = false;
    document.getElementById('workPortfolioId').value = '';
    document.getElementById('workPortfolioUpdateUrl').value = '';
    document.getElementById('workTitle').value = '';
    document.getElementById('workDescription').value = '';
    document.getElementById('workPrimaryStyle').value = '';
    document.getElementById('workColors').value = '';
    document.getElementById('workTags').value = '';
    document.getElementById('workAttribution').value = 'studio';
    document.getElementById('workImage').value = '';
    document.getElementById('workImageData').value = '';
    document.getElementById('workAiFilledPill').hidden = true;
    var tog = document.getElementById('workToggleVisibility');
    tog.classList.add('active', 'on');
    tog.setAttribute('aria-checked', 'true');
    setOtherStyles([]);
    setPlacement('');
    setImagePreview('');
    document.getElementById('btnReplaceWorkImage').textContent = 'Upload image';
    document.getElementById('addWorkModalTitle').textContent = 'Add work';
    document.querySelector('#btnSaveWork .save-btn-label').textContent = 'Save';
  }
  function openAddWorkModal() {
    resetWorkForm();
    openModal();
  }
  function openEditWorkModal(piece) {
    piece = piece || {};
    resetWorkForm();
    document.getElementById('addWorkModalTitle').textContent = 'Edit work';
    var saveLabel = document.querySelector('#btnSaveWork .save-btn-label');
    if (saveLabel) saveLabel.textContent = 'Save changes';
    document.getElementById('workPortfolioId').value = piece.id || '';
    document.getElementById('workPortfolioUpdateUrl').value = piece.update_url || '';
    document.getElementById('workTitle').value = piece.title || '';
    document.getElementById('workDescription').value = piece.description || '';
    document.getElementById('workPrimaryStyle').value = piece.primary_style || '';
    document.getElementById('workColors').value = piece.color || '';
    var tags = Array.isArray(piece.tags) ? piece.tags : [];
    document.getElementById('workTags').value = tags.join(', ');
    var attr = piece.attribution || 'studio';
    if (attr === 'artist' && piece.artist_user_id) {
      document.getElementById('workAttribution').value = 'artist:' + piece.artist_user_id;
    } else {
      document.getElementById('workAttribution').value = attr === 'pending' ? 'pending' : 'studio';
    }
    var tog = document.getElementById('workToggleVisibility');
    if (tog && piece.is_active === false) {
      tog.classList.remove('active', 'on');
      tog.setAttribute('aria-checked', 'false');
    }
    setOtherStyles(Array.isArray(piece.other_styles) ? piece.other_styles : []);
    setPlacement(piece.placement || '');
    if (piece.image) setImagePreview(piece.image);
    openModal();
  }
  function dataUrlToBlob(dataUrl) {
    if (!dataUrl || dataUrl.indexOf(',') === -1) return null;
    var parts = dataUrl.split(',');
    var mimeMatch = parts[0].match(/:(.*?);/);
    if (!mimeMatch) return null;
    var mime = mimeMatch[1];
    var binary = atob(parts[1]);
    var len = binary.length;
    var arr = new Uint8Array(len);
    for (var i = 0; i < len; i++) arr[i] = binary.charCodeAt(i);
    return new Blob([arr], { type: mime });
  }
  function destroyWorkCropper() {
    if (workCropper) {
      workCropper.destroy();
      workCropper = null;
    }
  }
  function revokeWorkCropBlob() {
    var u = cropperImg && cropperImg.dataset.blobUrl;
    if (u) {
      URL.revokeObjectURL(u);
      delete cropperImg.dataset.blobUrl;
    }
  }
  function closeWorkCropModal() {
    destroyWorkCropper();
    revokeWorkCropBlob();
    if (cropperImg) cropperImg.src = '';
    if (cropModal) {
      cropModal.classList.remove('is-open');
      cropModal.setAttribute('aria-hidden', 'true');
    }
  }
  function openWorkCropModalWithFile(file) {
    if (!file || !/^image\//.test(file.type)) {
      toast('Please choose an image file (PNG or JPG).', false);
      return;
    }
    if (file.size > MAX_FILE_BYTES) {
      toast('Image must be 10MB or smaller.', false);
      return;
    }
    if (typeof Cropper === 'undefined') {
      toast('Cropper failed to load. Refresh the page and try again.', false);
      return;
    }
    destroyWorkCropper();
    revokeWorkCropBlob();
    if (cropModal && cropModal.parentElement !== document.body) document.body.appendChild(cropModal);
    var url = URL.createObjectURL(file);
    cropperImg.dataset.blobUrl = url;
    cropModal.classList.add('is-open');
    cropModal.setAttribute('aria-hidden', 'false');

    function initCropperOnImg() {
      destroyWorkCropper();
      workCropper = new Cropper(cropperImg, {
        aspectRatio: CROP_RATIO,
        viewMode: 1,
        dragMode: 'move',
        autoCropArea: 1,
        responsive: true,
        restore: false,
        guides: true,
        center: true,
        highlight: true,
        background: false
      });
    }

    var cropInitDone = false;
    function startCropperOnce() {
      if (cropInitDone) return;
      cropInitDone = true;
      cropperImg.removeEventListener('load', startCropperOnce);
      cropperImg.removeEventListener('error', onCropError);
      initCropperOnImg();
    }
    function onCropError() {
      cropperImg.removeEventListener('load', startCropperOnce);
      cropperImg.removeEventListener('error', onCropError);
      closeWorkCropModal();
      toast('Could not open that image. Try another file.', false);
    }
    cropperImg.addEventListener('load', startCropperOnce);
    cropperImg.addEventListener('error', onCropError);
    cropperImg.src = url;
    if (cropperImg.complete && cropperImg.naturalWidth > 0) startCropperOnce();
  }
  function applyWorkCrop() {
    if (!workCropper) return;
    var canvas = workCropper.getCroppedCanvas({
      width: CROP_OUT_W,
      height: CROP_OUT_H,
      fillColor: '#e8e8e8',
      imageSmoothingEnabled: true,
      imageSmoothingQuality: 'high'
    });
    if (!canvas) {
      toast('Could not read the crop. Try again.', false);
      return;
    }
    var dataUrl = canvas.toDataURL('image/jpeg', 0.92);
    document.getElementById('workImageData').value = dataUrl;
    workImageFile = dataUrlToBlob(dataUrl);
    setImagePreview(dataUrl);
    document.getElementById('workAiFilledPill').hidden = true;
    document.getElementById('btnReplaceWorkImage').textContent = 'Replace image';
    closeWorkCropModal();
    document.getElementById('workImage').value = '';
    requestAiSuggest();
  }
  function requestAiSuggest() {
    var dataUrl = document.getElementById('workImageData').value;
    if (!workImageFile && !dataUrl && !document.getElementById('workImagePreviewImg').src) return;
    var wrap = document.getElementById('workImageUpload');
    wrap.classList.add('is-ai');
    var fd = new FormData();
    if (workImageFile) {
      fd.append('image', workImageFile, 'work.jpg');
    } else if (dataUrl) {
      fd.append('image_data', dataUrl);
    }
    fd.append('title', document.getElementById('workTitle').value || '');
    fd.append('description', document.getElementById('workDescription').value || '');
    fd.append('primary_style', document.getElementById('workPrimaryStyle').value || '');
    fd.append('color', document.getElementById('workColors').value || '');
    Array.from(document.getElementById('workOtherStyles').selectedOptions).forEach(function (o) {
      fd.append('other_styles[]', o.value);
    });
    var place = document.getElementById('workPlacement').value;
    if (place) fd.append('suggested_placements[]', place);
    var tags = document.getElementById('workTags').value;
    if (tags) tags.split(',').forEach(function (t) { t = t.trim(); if (t) fd.append('tags[]', t); });
    fetch(AI_SUGGEST_URL, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      body: fd
    }).then(function (r) { return r.json().then(function (j) { return { ok: r.ok, j: j }; }); })
      .then(function (res) {
        wrap.classList.remove('is-ai');
        if (!res.ok || !res.j.success) return;
        var s = res.j.suggestions || {};
        if (s.title && !document.getElementById('workTitle').value) document.getElementById('workTitle').value = s.title;
        if (s.description && !document.getElementById('workDescription').value) document.getElementById('workDescription').value = s.description;
        if (s.primary_style && !document.getElementById('workPrimaryStyle').value) document.getElementById('workPrimaryStyle').value = s.primary_style;
        if (s.color && !document.getElementById('workColors').value) document.getElementById('workColors').value = s.color;
        if (s.other_styles && Array.from(document.getElementById('workOtherStyles').selectedOptions).length === 0) setOtherStyles(s.other_styles);
        if (s.suggested_placements && s.suggested_placements[0] && !document.getElementById('workPlacement').value) setPlacement(s.suggested_placements[0]);
        if (s.tags && !document.getElementById('workTags').value) document.getElementById('workTags').value = (s.tags || []).join(', ');
        aiFilled = true;
        document.getElementById('workAiFilledPill').hidden = false;
        syncOtherDisabled();
      })
      .catch(function () { wrap.classList.remove('is-ai'); });
  }
  function onImageChosen(file) {
    openWorkCropModalWithFile(file);
  }
  function saveWork() {
    clearErrors();
    var editing = !!document.getElementById('workPortfolioId').value;
    var updateUrl = document.getElementById('workPortfolioUpdateUrl').value;
    var dataUrl = document.getElementById('workImageData').value;
    if (!workImageFile && dataUrl) workImageFile = dataUrlToBlob(dataUrl);
    if (!editing && !workImageFile) {
      showFieldError('image', 'Please upload an image.');
      return;
    }
    var fd = new FormData();
    if (workImageFile) fd.append('image', workImageFile, 'work.jpg');
    fd.append('title', (document.getElementById('workTitle').value || '').trim());
    fd.append('description', (document.getElementById('workDescription').value || '').trim());
    fd.append('is_active', document.getElementById('workToggleVisibility').classList.contains('on') ? '1' : '0');
    fd.append('primary_style', document.getElementById('workPrimaryStyle').value || '');
    Array.from(document.getElementById('workOtherStyles').selectedOptions).forEach(function (o) {
      fd.append('other_styles[]', o.value);
    });
    fd.append('placement', document.getElementById('workPlacement').value || '');
    fd.append('color', document.getElementById('workColors').value || '');
    var rawTags = document.getElementById('workTags').value || '';
    rawTags.split(',').forEach(function (t) { t = t.trim(); if (t) fd.append('tags[]', t); });
    var attrVal = document.getElementById('workAttribution').value || 'studio';
    if (attrVal.indexOf('artist:') === 0) {
      fd.append('attribution', 'artist');
      fd.append('artist_user_id', attrVal.slice(7));
    } else {
      fd.append('attribution', attrVal);
    }
    var url = editing && updateUrl ? updateUrl : STORE_PIECE_URL;
    if (editing && updateUrl) fd.append('_method', 'PUT');
    var btn = document.getElementById('btnSaveWork');
    var html = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="ms">hourglass_top</span>' + (editing ? 'Updating…' : 'Saving…');
    fetch(url, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      body: fd
    }).then(function (r) { return r.json().then(function (j) { return { ok: r.ok, status: r.status, j: j }; }); })
      .then(function (res) {
        if (res.ok && res.j.success) {
          toast(res.j.message || 'Saved');
          closeModal();
          setTimeout(function () { location.reload(); }, 400);
          return;
        }
        if (res.status === 422 && res.j.errors) {
          Object.keys(res.j.errors).forEach(function (k) {
            showFieldError(k, res.j.errors[k][0]);
          });
          return;
        }
        var banner = document.getElementById('workFormBanner');
        banner.textContent = res.j.message || 'Could not save';
        banner.classList.remove('hidden');
      })
      .catch(function () {
        var banner = document.getElementById('workFormBanner');
        banner.textContent = 'Could not save';
        banner.classList.remove('hidden');
      })
      .finally(function () {
        btn.disabled = false;
        btn.innerHTML = html;
      });
  }

  document.getElementById('addPiecesBtn')?.addEventListener('click', openAddWorkModal);
  document.getElementById('btnCloseAddWork')?.addEventListener('click', closeModal);
  document.getElementById('btnCancelAddWork')?.addEventListener('click', closeModal);
  document.getElementById('btnSaveWork')?.addEventListener('click', saveWork);
  document.getElementById('btnReplaceWorkImage')?.addEventListener('click', function (e) {
    e.preventDefault();
    e.stopPropagation();
    document.getElementById('workImage').click();
  });
  document.getElementById('workImageUpload')?.addEventListener('click', function () {
    document.getElementById('workImage').click();
  });
  document.getElementById('workImage')?.addEventListener('change', function () {
    var f = this.files && this.files[0];
    if (f) onImageChosen(f);
    this.value = '';
  });
  document.getElementById('btnCropClose')?.addEventListener('click', closeWorkCropModal);
  document.getElementById('btnCropCancel')?.addEventListener('click', closeWorkCropModal);
  document.getElementById('btnCropApply')?.addEventListener('click', applyWorkCrop);
  document.getElementById('workToggleVisibility')?.addEventListener('click', function () {
    this.classList.toggle('on');
    this.classList.toggle('active');
    this.setAttribute('aria-checked', this.classList.contains('on') ? 'true' : 'false');
  });
  document.getElementById('workPrimaryStyle')?.addEventListener('change', syncOtherDisabled);
  document.getElementById('workOtherStylesChips')?.addEventListener('click', function (e) {
    var chip = e.target.closest('.chip');
    if (!chip || chip.disabled) return;
    var v = chip.getAttribute('data-value');
    var selected = Array.from(document.getElementById('workOtherStyles').selectedOptions).map(function (o) { return o.value; });
    var idx = selected.indexOf(v);
    if (idx > -1) selected.splice(idx, 1);
    else if (selected.length < 2) selected.push(v);
    setOtherStyles(selected);
  });
  document.getElementById('workPlacementsChips')?.addEventListener('click', function (e) {
    var chip = e.target.closest('.chip');
    if (!chip) return;
    var v = chip.getAttribute('data-value');
    setPlacement(document.getElementById('workPlacement').value === v ? '' : v);
  });
  $modal?.addEventListener('click', function (e) {
    if (e.target === $modal) closeModal();
  });

  function firstError(j) {
    if (!j) return 'Could not save';
    if (j.message && typeof j.message === 'string' && !j.errors) return j.message;
    if (j.errors) {
      var keys = Object.keys(j.errors);
      if (keys.length && j.errors[keys[0]] && j.errors[keys[0]][0]) return j.errors[keys[0]][0];
    }
    return j.message || 'Could not save';
  }

  function savePortfolio(andLeave) {
    if (saving) return Promise.resolve(false);
    setSavingUi(true);
    return fetch(SAVE_URL, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      body: collectFormData()
    })
      .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, j: j }; }); })
      .then(function (res) {
        if (!res.ok || !res.j.success) throw new Error(firstError(res.j));
        setDirty(false);
        toast(res.j.message || 'Changes saved');
        if (andLeave && pendingHref) {
          try { sessionStorage.setItem('bp-toast', res.j.message || 'Changes saved'); } catch (x) {}
          location.href = pendingHref;
          return true;
        }
        location.reload();
        return true;
      })
      .catch(function (e) {
        toast(e.message || 'Could not save', false);
        setDirty(true);
        setSavingUi(false);
        return false;
      });
  }

  function refreshIg() {
    var btn = document.getElementById('igRefreshBtn');
    if (btn) { btn.disabled = true; btn.textContent = 'Importing…'; }
    return fetch(REFRESH_URL, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    })
      .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, j: j }; }); })
      .then(function (res) {
        if (!res.ok || !res.j.success) throw new Error(res.j.message || 'Import failed');
        toast(res.j.message || 'Imported');
        location.reload();
      })
      .catch(function (e) {
        toast(e.message || 'Import failed', false);
        if (btn) { btn.disabled = false; btn.textContent = 'Refresh'; }
      });
  }

  document.getElementById('igRefreshBtn')?.addEventListener('click', refreshIg);
  document.getElementById('igDisconnectBtn')?.addEventListener('click', function () {
    fetch(DISCONNECT_URL, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    }).then(function () { location.reload(); });
  });

  if (AUTO_IMPORT) setTimeout(refreshIg, 300);

  (function quickLinks() {
    var wrap = document.getElementById('quickLinks');
    var btn = document.getElementById('quickLinksBtn');
    var menu = document.getElementById('quickLinksMenu');
    if (!wrap || !btn || !menu) return;
    function openQl(show) {
      menu.hidden = !show;
      btn.setAttribute('aria-expanded', show ? 'true' : 'false');
      var cv = btn.querySelector('.cv');
      if (cv) cv.textContent = show ? 'expand_less' : 'expand_more';
    }
    btn.addEventListener('click', function (e) { e.stopPropagation(); openQl(menu.hidden); });
    menu.addEventListener('click', function (e) {
      e.stopPropagation();
      var c = e.target.closest('[data-ql-copy]');
      if (!c) return;
      var u = c.getAttribute('data-ql-copy') || '';
      var label = c.getAttribute('data-ql-label') || u;
      try { navigator.clipboard.writeText(u).catch(function () {}); } catch (x) {}
      c.classList.add('done');
      var icon = c.querySelector('.ms');
      if (icon) icon.textContent = 'check';
      setTimeout(function () { c.classList.remove('done'); if (icon) icon.textContent = 'content_copy'; }, 1600);
      toast('Link copied: ' + label);
    });
    document.addEventListener('click', function (e) { if (!menu.hidden && !wrap.contains(e.target)) openQl(false); });
  })();

  saveBtn?.addEventListener('click', function () { if (!dirty || saving) return; savePortfolio(false); });

  document.addEventListener('click', function (e) {
    if (e.target.closest('[data-close]') || e.target.id === 'leaveguard') { closeLeave(); return; }
    if (e.target.closest('#lg-leave')) { dirty = false; if (pendingHref) location.href = pendingHref; return; }
    if (e.target.closest('#lg-save')) { closeLeave(); savePortfolio(true); return; }
    var soon = e.target.closest('.tabs a.is-soon');
    if (soon) { e.preventDefault(); return; }
    var link = e.target.closest('a[href]');
    if (link && dirty && !link.closest('#leaveguard') && !link.closest('#quickLinks') && !link.getAttribute('href').startsWith('#') && !link.target) {
      e.preventDefault();
      pendingHref = link.getAttribute('href');
      openLeave();
    }
  });

  try {
    var q = sessionStorage.getItem('bp-toast');
    if (q) { sessionStorage.removeItem('bp-toast'); setTimeout(function () { toast(q); }, 120); }
  } catch (e) {}
})();
</script>
@endpush
