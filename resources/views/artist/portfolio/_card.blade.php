@php
  $fromInstagram = filled($portfolio->instagram_media_id);
@endphp
<div class="portfolio-card bg-white rounded-2xl border border-outline-variant/20 overflow-hidden shadow-sm" data-portfolio-id="{{ $portfolio->id }}" data-is-active="{{ $portfolio->is_active ? '1' : '0' }}">
  <div class="aspect-square bg-surface-container-high rounded-t-2xl relative overflow-hidden">
    <img src="{{ asset($portfolio->image) }}" alt="" class="w-full h-full object-cover">
    @if ($fromInstagram)
      <span class="portfolio-ig-badge" title="Imported from Instagram" aria-label="Imported from Instagram">
        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
      </span>
    @endif
  </div>
  <div class="p-4">
    <div class="flex items-center justify-between gap-2 mb-3">
      <span class="portfolio-visibility-label text-xs font-semibold {{ $portfolio->is_active ? 'text-primary' : 'text-on-surface-variant' }}">
        {{ $portfolio->is_active ? 'Visible' : 'Hidden' }}
      </span>
      <div
        class="btn-toggle-portfolio-visibility toggle-switch {{ $portfolio->is_active ? 'active' : '' }}"
        role="switch"
        aria-checked="{{ $portfolio->is_active ? 'true' : 'false' }}"
        title="Toggle visibility"
        data-toggle-url="{{ route('portfolio.toggle-visibility', $portfolio) }}"
        data-portfolio-id="{{ $portfolio->id }}"
      ></div>
    </div>
    <h4 class="font-bold text-on-surface text-sm mb-1.5">{{ $portfolio->title }}</h4>
    <div class="flex flex-wrap items-center gap-2 mb-2">
      <span class="text-xs font-semibold px-2 py-0.5 rounded-md bg-primary/10 text-primary">{{ $portfolio->primary_style }}</span>
      <span class="text-xs font-semibold px-2 py-0.5 rounded-md bg-surface-container-high text-on-surface-variant">
        @if ($portfolio->color === 'color') Color @elseif ($portfolio->color === 'black-grey') Black & Grey @elseif ($portfolio->color === 'both') Both @else {{ $portfolio->color }} @endif
      </span>
    </div>
    @if (!empty($portfolio->tags))
    <div class="flex flex-wrap gap-1 mb-3">
      @foreach ($portfolio->tags as $tag)
      <span class="tag-pill">{{ $tag }}</span>
      @endforeach
    </div>
    @endif
    <div class="flex items-center gap-1">
      <button type="button" class="btn-edit-portfolio w-8 h-8 rounded-lg flex items-center justify-center hover:bg-surface-container-low transition-colors" title="Edit" data-portfolio-id="{{ $portfolio->id }}" data-update-url="{{ route('portfolio.update', $portfolio) }}"><span class="material-symbols-outlined text-on-surface-variant text-lg">edit</span></button>
      <button type="button" class="btn-delete-portfolio w-8 h-8 rounded-lg flex items-center justify-center hover:bg-error-container transition-colors" title="Delete" data-delete-url="{{ route('portfolio.destroy', $portfolio) }}" data-portfolio-id="{{ $portfolio->id }}"><span class="material-symbols-outlined text-error text-lg">delete</span></button>
    </div>
  </div>
</div>
