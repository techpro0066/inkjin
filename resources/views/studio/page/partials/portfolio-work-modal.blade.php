@php
  $styles = $styles ?? [];
  $placements = $placements ?? [];
  $taggableArtists = $taggableArtists ?? collect();
@endphp
<div class="modal-backdrop aw-ov" id="addWorkModal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="addWorkModalTitle">
  <div class="modal xl add-work-modal-inner">
    <div class="mh">
      <div>
        <h2 id="addWorkModalTitle">Add work</h2>
        <div class="muted" id="addWorkModalSubtitle" style="font-size:13.5px;margin-top:4px">Upload a photo of a finished tattoo. AI fills in what it can.</div>
      </div>
      <button type="button" class="mx" id="btnCloseAddWork" aria-label="Close"><span class="ms">close</span></button>
    </div>
    <div class="mb">
      <input type="hidden" id="workPortfolioId" value="">
      <input type="hidden" id="workPortfolioUpdateUrl" value="">
      <div id="workFormBanner" class="hidden aw-banner"></div>

      <div class="grid aw-grid">
        <div class="work-field-section" data-work-field="image">
          <div id="workImageUpload" class="aw-img work-image-upload work-image-upload--empty" role="button" tabindex="0">
            <div id="workImageUploadEmpty" class="aw-img-empty">
              <span class="ms">cloud_upload</span>
              <b>Drop image here</b>
              <span class="faint">or click to browse · PNG, JPG up to 10MB</span>
            </div>
            <div id="workImageUploadPreview" class="hidden aw-img-preview">
              <div class="aw-img-frame">
                <img id="workImagePreviewImg" src="" alt="Selected work preview">
                <span id="workAiFilledPill" class="pill nd aw-ai-pill" hidden>
                  <span class="ms">auto_awesome</span><span id="workAiFilledText">AI filled</span>
                </span>
              </div>
            </div>
            <div class="work-ai-overlay" aria-live="polite">
              <span class="ms">auto_awesome</span>
              <p>Filling empty fields with AI…</p>
            </div>
          </div>
          <div class="row aw-img-meta">
            <span class="faint" style="font-size:12px">1080 × 1350 · 4:5</span>
            <button type="button" class="aw-replace" id="btnReplaceWorkImage">Upload image</button>
          </div>
          <input type="file" id="workImage" name="workImage" accept="image/*" class="hidden">
          <input type="hidden" id="workImageData" name="workImageData" value="">
          <p class="hidden work-field-error" data-error-for="image"></p>
        </div>

        <div class="aw-fields">
          <div class="work-field-section" data-work-field="is_active">
            <div class="row" style="gap:8px">
              <div id="workToggleVisibility" class="tog toggle-switch active on" role="switch" aria-checked="true"></div>
              <span style="font-size:13.5px;font-weight:600">Visible on studio page</span>
            </div>
            <p class="hidden work-field-error" data-error-for="is_active"></p>
          </div>

          <div class="work-field-section" data-work-field="title" style="margin-top:16px">
            <div class="row" style="margin-bottom:6px">
              <label class="fl" for="workTitle" style="margin:0">Title<span class="ai"><span class="ms">auto_awesome</span>AI</span></label>
            </div>
            <input class="in" type="text" id="workTitle" name="workTitle" placeholder="e.g., Mandala elbow piece" autocomplete="off">
            <p class="hidden work-field-error" data-error-for="title"></p>
          </div>

          <div class="work-field-section" data-work-field="description" style="margin-top:16px">
            <div class="row" style="margin-bottom:6px">
              <label class="fl" for="workDescription" style="margin:0">Description <span class="faint" style="font-weight:500">(optional)</span><span class="ai"><span class="ms">auto_awesome</span>AI</span></label>
            </div>
            <textarea class="in" id="workDescription" name="workDescription" rows="3" placeholder="Describe this piece…" style="min-height:58px;align-items:flex-start;resize:vertical"></textarea>
            <p class="hidden work-field-error" data-error-for="description"></p>
          </div>

          <div class="work-field-section" data-work-field="attribution" style="margin-top:16px">
            <label class="fl" for="workAttribution" style="margin:0 0 6px">Credit</label>
            <select id="workAttribution" name="workAttribution" class="in">
              <option value="studio">Studio (no artist)</option>
              <option value="pending">Tag the artist</option>
              @foreach ($taggableArtists as $artist)
                <option value="artist:{{ $artist['user_id'] }}">{{ $artist['name'] }}</option>
              @endforeach
            </select>
          </div>

          <div class="work-field-section" data-work-field="primary_style" style="margin-top:16px">
            <div class="row" style="margin-bottom:6px">
              <label class="fl" for="workPrimaryStyle" style="margin:0">Primary style<span class="ai"><span class="ms">auto_awesome</span>AI</span></label>
            </div>
            <select id="workPrimaryStyle" name="workPrimaryStyle" class="in">
              <option value="">Select style…</option>
              @foreach ($styles as $style)
                <option value="{{ $style }}">{{ $style }}</option>
              @endforeach
            </select>
            <p class="hidden work-field-error" data-error-for="primary_style"></p>
          </div>

          <div class="work-field-section" data-work-field="other_styles" style="margin-top:16px">
            <div class="row" style="margin-bottom:6px">
              <span class="fl" style="margin:0">Other styles<span class="ai"><span class="ms">auto_awesome</span>AI</span></span>
              <span class="cnt2"><span id="workOtherStylesCount">0</span> / 2</span>
            </div>
            <div class="chips" id="workOtherStylesChips" role="group" aria-label="Other tattoo styles">
              @foreach ($styles as $style)
                <button type="button" class="chip style-chip" data-value="{{ $style }}" aria-pressed="false">{{ $style }}</button>
              @endforeach
            </div>
            <select id="workOtherStyles" name="workOtherStyles" multiple class="hidden" tabindex="-1" aria-hidden="true">
              @foreach ($styles as $style)
                <option value="{{ $style }}">{{ $style }}</option>
              @endforeach
            </select>
            <p class="hidden work-field-error" data-error-for="other_styles"></p>
          </div>

          <div class="work-field-section" data-work-field="placement" style="margin-top:16px">
            <div class="row" style="margin-bottom:6px">
              <span class="fl" style="margin:0">Placement</span>
              <span class="cnt2"><span id="workPlacementsCount">0</span> / 1</span>
            </div>
            <div class="chips" id="workPlacementsChips" role="group" aria-label="Placement">
              @foreach ($placements as $placement)
                <button type="button" class="chip style-chip" data-value="{{ $placement }}" aria-pressed="false">{{ $placement }}</button>
              @endforeach
            </div>
            <select id="workPlacement" name="workPlacement" class="hidden" tabindex="-1" aria-hidden="true">
              <option value=""></option>
              @foreach ($placements as $placement)
                <option value="{{ $placement }}">{{ $placement }}</option>
              @endforeach
            </select>
            <p class="hidden work-field-error" data-error-for="placement"></p>
          </div>

          <div class="grid aw-half" style="margin-top:4px">
            <div class="work-field-section" data-work-field="color">
              <div class="row" style="margin-top:12px;margin-bottom:6px">
                <label class="fl" for="workColors" style="margin:0">Colors<span class="ai"><span class="ms">auto_awesome</span>AI</span></label>
              </div>
              <select id="workColors" name="workColors" class="in">
                <option value="">Select…</option>
                <option value="color">Color</option>
                <option value="black-grey">Black & Grey</option>
                <option value="both">Both</option>
              </select>
              <p class="hidden work-field-error" data-error-for="color"></p>
            </div>
            <div class="work-field-section" data-work-field="tags">
              <div class="row" style="margin-top:12px;margin-bottom:6px">
                <label class="fl" for="workTags" style="margin:0">Tags<span class="ai"><span class="ms">auto_awesome</span>AI</span></label>
              </div>
              <input class="in" type="text" id="workTags" name="workTags" placeholder="e.g., dragon, rose, traditional" autocomplete="off">
              <p class="hidden work-field-error" data-error-for="tags"></p>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="mf">
      <button type="button" class="btn ghost" id="btnCancelAddWork">Cancel</button>
      <button type="button" class="btn" id="btnSaveWork"><span class="ms">save</span><span class="save-btn-label">Save</span></button>
    </div>
  </div>
</div>
