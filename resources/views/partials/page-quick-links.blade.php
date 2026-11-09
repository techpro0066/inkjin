{{--
  Open-page control that opens a Quick links dropdown.
  Expected:
    $qlPublicUrl   string  absolute public page URL
    $qlDisplayHost string  display host path e.g. inkjin.com/@user
    $qlRows        list of ['suffix' => '', 'hash' => optional, 'label' => optional]
    $qlButtonLabel string  default "Open my page"
    $qlButtonClass string  default "btn"
--}}
@php
  $qlPublicUrl = trim((string) ($qlPublicUrl ?? ''));
  $qlDisplayHost = trim((string) ($qlDisplayHost ?? ''));
  $qlRows = is_array($qlRows ?? null) ? $qlRows : [];
  $qlButtonLabel = trim((string) ($qlButtonLabel ?? 'Open my page')) ?: 'Open my page';
  $qlButtonClass = trim((string) ($qlButtonClass ?? 'btn')) ?: 'btn';
  $qlFallbackUrl = trim((string) ($qlFallbackUrl ?? ''));
@endphp

@if ($qlPublicUrl !== '' && $qlRows !== [])
  <div class="ql" id="quickLinks">
    <button
      type="button"
      class="{{ $qlButtonClass }}"
      id="quickLinksBtn"
      aria-haspopup="true"
      aria-expanded="false"
      aria-controls="quickLinksMenu"
    >
      <span class="ms">open_in_new</span>{{ $qlButtonLabel }}<span class="ms cv">expand_more</span>
    </button>
    <div class="ql-p" id="quickLinksMenu" hidden role="menu" aria-label="Quick links">
      @foreach ($qlRows as $row)
        @php
          $suffix = (string) ($row['suffix'] ?? '');
          $hash = array_key_exists('hash', $row) ? (string) $row['hash'] : $suffix;
          $copyUrl = $qlPublicUrl.$suffix;
          $openUrl = $qlPublicUrl.$hash;
          $label = trim((string) ($row['label'] ?? ''));
          if ($label === '') {
              $label = $qlDisplayHost.$suffix;
          }
        @endphp
        <div class="ql-r" role="none">
          <div class="ql-u" title="{{ $label }}">
            {{ $qlDisplayHost }}@if($suffix !== '')<b>{{ $suffix }}</b>@endif
          </div>
          <button
            type="button"
            class="ql-i"
            data-ql-copy="{{ $copyUrl }}"
            data-ql-label="{{ $label }}"
            role="menuitem"
            aria-label="Copy {{ $label }}"
            title="Copy link"
          >
            <span class="ms">content_copy</span>
          </button>
          <a
            class="ql-i"
            href="{{ $openUrl }}"
            target="_blank"
            rel="noopener"
            role="menuitem"
            aria-label="Open {{ $label }}"
            title="Open"
          >
            <span class="ms">open_in_new</span>
          </a>
        </div>
      @endforeach
    </div>
  </div>
@elseif ($qlFallbackUrl !== '')
  <a class="{{ $qlButtonClass }}" href="{{ $qlFallbackUrl }}">
    <span class="ms">open_in_new</span>{{ $qlButtonLabel }}
  </a>
@elseif ($qlPublicUrl !== '')
  <a class="{{ $qlButtonClass }}" href="{{ $qlPublicUrl }}" target="_blank" rel="noopener">
    <span class="ms">open_in_new</span>{{ $qlButtonLabel }}
  </a>
@endif
