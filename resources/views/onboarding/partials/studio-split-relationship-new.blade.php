{{--
  Studio revenue split (O6-style). Relationship is collected on the studio step.
--}}
@php
  $fieldSuffix = $fieldSuffix ?? '';
  $showSaveButton = (bool) ($showSaveButton ?? true);
  $showStepNumbers = (bool) ($showStepNumbers ?? true);
  $splitStep = (int) ($splitStep ?? 2);
  $percentName = $fieldSuffix === '' ? 'studio_revenue_artist_percent' : 'studio_revenue_artist_percent'.$fieldSuffix;
@endphp
<div class="studio-split-relationship-panel">
  <div class="studio-revenue-split">
    <div class="row" style="gap:12px;align-items:flex-start">
      @if ($showStepNumbers)
        <div class="stepn">{{ $splitStep }}</div>
      @endif
      <div style="flex:1">
        <b style="font-size:14px">Enter the revenue split</b>
        <div class="row" style="gap:28px;margin-top:8px;flex-wrap:wrap;align-items:flex-end">
          <div>
            <label class="fl" for="{{ $percentId }}">You get</label>
            <div class="pct">
              <input class="in js-studio-revenue-you-get" type="text" id="{{ $percentId }}" name="{{ $percentName }}" value="{{ $studioRevenueArtistPercent }}" maxlength="3" inputmode="numeric" pattern="[0-9]*" autocomplete="off" @if(!empty($hintId)) aria-describedby="{{ $hintId }}" @endif>
              %
            </div>
          </div>
          <div>
            <span class="fl">Studio gets</span>
            <div style="font-size:20px;font-weight:800;padding:8px 0">
              <span class="js-studio-revenue-studio-gets">{{ 100 - $studioRevenueArtistPercent }}</span>%
            </div>
          </div>
        </div>
        <p @if(!empty($hintId)) id="{{ $hintId }}" @endif class="help">{{ $hintText }}</p>
      </div>
    </div>
  </div>

  @if ($showSaveButton)
    <button type="button" id="{{ $saveBtnId }}" class="btn ghost js-save-studio-split" style="margin-top:14px">Save</button>
  @endif
</div>
