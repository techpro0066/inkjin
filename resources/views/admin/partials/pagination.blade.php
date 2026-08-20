@if ($paginator->hasPages())
  <nav role="navigation" aria-label="Pagination" class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
    <p class="text-sm text-on-surface-variant">
      Showing
      <span class="font-semibold text-on-surface">{{ $paginator->firstItem() }}</span>
      –
      <span class="font-semibold text-on-surface">{{ $paginator->lastItem() }}</span>
      of
      <span class="font-semibold text-on-surface">{{ number_format($paginator->total()) }}</span>
    </p>

    <div class="flex flex-wrap items-center gap-1.5">
      @if ($paginator->onFirstPage())
        <span class="inline-flex items-center gap-1 px-3 py-2 rounded-xl text-sm font-semibold text-outline bg-surface-container-low cursor-not-allowed">
          <span class="material-symbols-outlined text-[16px]">chevron_left</span>
          Prev
        </span>
      @else
        <a href="{{ $paginator->previousPageUrl() }}" class="inline-flex items-center gap-1 px-3 py-2 rounded-xl text-sm font-semibold text-on-surface bg-white border border-outline-variant/30 hover:border-primary/40 hover:text-primary transition-colors">
          <span class="material-symbols-outlined text-[16px]">chevron_left</span>
          Prev
        </a>
      @endif

      @foreach ($elements as $element)
        @if (is_string($element))
          <span class="px-2 py-2 text-sm text-outline">{{ $element }}</span>
        @endif

        @if (is_array($element))
          @foreach ($element as $page => $url)
            @if ($page == $paginator->currentPage())
              <span aria-current="page" class="inline-flex min-w-[2.25rem] items-center justify-center px-3 py-2 rounded-xl text-sm font-semibold bg-primary text-white">{{ $page }}</span>
            @else
              <a href="{{ $url }}" class="inline-flex min-w-[2.25rem] items-center justify-center px-3 py-2 rounded-xl text-sm font-semibold text-on-surface bg-white border border-outline-variant/30 hover:border-primary/40 hover:text-primary transition-colors">{{ $page }}</a>
            @endif
          @endforeach
        @endif
      @endforeach

      @if ($paginator->hasMorePages())
        <a href="{{ $paginator->nextPageUrl() }}" class="inline-flex items-center gap-1 px-3 py-2 rounded-xl text-sm font-semibold text-on-surface bg-white border border-outline-variant/30 hover:border-primary/40 hover:text-primary transition-colors">
          Next
          <span class="material-symbols-outlined text-[16px]">chevron_right</span>
        </a>
      @else
        <span class="inline-flex items-center gap-1 px-3 py-2 rounded-xl text-sm font-semibold text-outline bg-surface-container-low cursor-not-allowed">
          Next
          <span class="material-symbols-outlined text-[16px]">chevron_right</span>
        </span>
      @endif
    </div>
  </nav>
@endif
