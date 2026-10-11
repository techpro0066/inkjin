@php
  $fromInstagram = filled($portfolio->instagram_media_id);
  $isActive = (bool) $portfolio->is_active;
  $colorLabel = match ($portfolio->color) {
      'color' => 'Color',
      'black-grey' => 'Black & Grey',
      'both' => 'Both',
      default => (string) $portfolio->color,
  };
  $tags = collect($portfolio->tags ?? [])->filter(fn ($t) => trim((string) $t) !== '')->values();
@endphp
<div
  class="card pcard portfolio-card{{ $isActive ? '' : ' off' }}"
  data-portfolio-id="{{ $portfolio->id }}"
  data-is-active="{{ $isActive ? '1' : '0' }}"
  data-sort-order="{{ (int) ($portfolio->sort_order ?? 0) }}"
  @if ($fromInstagram) data-ig="1" @endif
>
  <div class="pimg" style="background-image:url('{{ asset($portfolio->image) }}')">
    @if ($fromInstagram)
      <span class="igb portfolio-ig-badge" title="Imported from Instagram" aria-label="Imported from Instagram">
        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="#fff" stroke-width="2.2" aria-hidden="true">
          <rect x="3" y="3" width="18" height="18" rx="5"/>
          <circle cx="12" cy="12" r="4"/>
          <circle cx="17.5" cy="6.5" r="1" fill="#fff" stroke="none"/>
        </svg>
      </span>
    @endif
    <button type="button" class="portfolio-drag-handle phd" title="Drag to reorder" aria-label="Drag to reorder">
      <span class="ms">drag_indicator</span>
    </button>
  </div>
  <div style="padding:12px 14px 14px">
    <div class="row" style="justify-content:space-between">
      <span class="faint portfolio-visibility-label" style="font-size:12.5px;font-weight:600">{{ $isActive ? 'Visible' : 'Hidden' }}</span>
      <div
        class="tog btn-toggle-portfolio-visibility {{ $isActive ? 'on active' : '' }}"
        role="switch"
        aria-checked="{{ $isActive ? 'true' : 'false' }}"
        title="Toggle visibility"
        data-own="1"
        data-toggle-url="{{ route('portfolio.toggle-visibility', $portfolio) }}"
        data-portfolio-id="{{ $portfolio->id }}"
        style="cursor:pointer"
      ></div>
    </div>
    <b style="display:block;font-size:14px;margin-top:8px">{{ $portfolio->title }}</b>
    <div class="ptags" style="margin-top:8px">
      <span class="pst">{{ $portfolio->primary_style }}</span>
      <span class="pst">{{ $colorLabel }}</span>
    </div>
    @if ($tags->isNotEmpty())
      <div class="ptags" style="margin-top:6px">
        @foreach ($tags as $tag)
          <span class="ptg">{{ ltrim((string) $tag, '#') }}</span>
        @endforeach
      </div>
    @endif
    <div class="row" style="gap:14px;margin-top:12px;padding-top:10px;border-top:1px solid #F3EEF4">
      <button
        type="button"
        class="pic pedit btn-edit-portfolio"
        aria-label="Edit"
        title="Edit"
        data-portfolio-id="{{ $portfolio->id }}"
        data-update-url="{{ route('portfolio.update', $portfolio) }}"
      ><span class="ms">edit</span></button>
      @if ($fromInstagram)
        <span class="faint igonly" style="font-size:12px;margin-left:auto">From Instagram</span>
      @else
        <button
          type="button"
          class="pic pdel btn-delete-portfolio"
          aria-label="Delete"
          title="Delete"
          style="color:#C62828"
          data-delete-url="{{ route('portfolio.destroy', $portfolio) }}"
          data-portfolio-id="{{ $portfolio->id }}"
        ><span class="ms">delete</span></button>
      @endif
    </div>
  </div>
</div>
