{{--
  Studio revenue split + relationship type (+ optional save).
  @var string $fieldSuffix  '' or '_nc' (unique radio/input names per section)
  @var string $percentId
  @var string|null $saveBtnId
  @var bool $showSaveButton
  @var string $hintId
  @var string $hintText
  @var int $studioRevenueArtistPercent
  @var string $studioRelationshipSelected
  @var array $studioRelationshipTypes
--}}
@php
  $fieldSuffix = $fieldSuffix ?? '';
  $showSaveButton = (bool) ($showSaveButton ?? true);
  $percentName = $fieldSuffix === '' ? 'studio_revenue_artist_percent' : 'studio_revenue_artist_percent'.$fieldSuffix;
  $relName = $fieldSuffix === '' ? 'studio_relationship_type' : 'studio_relationship_type'.$fieldSuffix;
@endphp
<div class="studio-split-relationship-panel space-y-4">
  <div class="studio-revenue-split space-y-3">
    <p class="text-sm font-semibold text-on-surface">Enter revenue split</p>
    <div class="flex flex-wrap items-end gap-4">
      <div>
        <label for="{{ $percentId }}" class="block text-xs font-medium text-on-surface-variant mb-1.5">You get</label>
        <div class="relative w-24">
          <input type="text" id="{{ $percentId }}" name="{{ $percentName }}" value="{{ $studioRevenueArtistPercent }}" maxlength="3" inputmode="numeric" pattern="[0-9]*" autocomplete="off"
            class="js-studio-revenue-you-get form-input rounded-xl pr-8 tabular-nums text-center" @if(!empty($hintId)) aria-describedby="{{ $hintId }}" @endif>
          <span class="pointer-events-none absolute inset-y-0 right-2.5 flex items-center text-sm font-semibold text-on-surface-variant">%</span>
        </div>
      </div>
      <div>
        <p class="block text-xs font-medium text-on-surface-variant mb-1.5">Studio gets</p>
        <div class="form-input rounded-xl w-24 bg-surface-container-high/60 text-on-surface font-semibold tabular-nums flex items-center justify-center gap-1 pointer-events-none select-none" aria-live="polite">
          <span class="js-studio-revenue-studio-gets">{{ 100 - $studioRevenueArtistPercent }}</span>
          <span class="text-on-surface-variant font-semibold">%</span>
        </div>
      </div>
    </div>
    <p @if(!empty($hintId)) id="{{ $hintId }}" @endif class="text-xs text-on-surface-variant">{{ $hintText }}</p>
  </div>
  <div class="studio-relationship-type space-y-3">
    <p class="text-sm font-semibold text-on-surface">Select relationship type</p>
    <div class="grid grid-cols-1 gap-3" role="radiogroup" aria-label="Select relationship type">
      @foreach ($studioRelationshipTypes as $relVal => $relMeta)
        <label class="radio-card js-studio-relationship-card flex items-start gap-3 cursor-pointer {{ $relVal === $studioRelationshipSelected ? 'selected' : '' }}">
          <input type="radio" name="{{ $relName }}" value="{{ $relVal }}" class="sr-only js-studio-relationship-input" {{ $relVal === $studioRelationshipSelected ? 'checked' : '' }}>
          <div class="radio-dot mt-0.5" aria-hidden="true"></div>
          <div class="min-w-0">
            <p class="font-semibold text-sm text-on-surface">{{ $relMeta[0] }}</p>
            <p class="text-on-surface-variant text-xs leading-relaxed mt-0.5">{{ $relMeta[1] }}</p>
          </div>
        </label>
      @endforeach
    </div>
  </div>
  @if ($showSaveButton)
    <button type="button" id="{{ $saveBtnId }}" class="js-save-studio-split inline-flex items-center gap-2 border border-primary/20 text-primary font-bold py-3 px-8 rounded-xl hover:bg-primary/5 transition-all active:scale-[0.98]">
      Save
    </button>
  @endif
</div>
