@php
  $perPage = (int) ($perPage ?? \App\Support\AdminListPagination::DEFAULT);
  $perPageOptions = \App\Support\AdminListPagination::OPTIONS;
  $selectId = $selectId ?? 'adminPerPage';
@endphp
<div>
  <label for="{{ $selectId }}" class="block text-xs font-semibold text-on-surface-variant mb-1.5">Per page</label>
  <select
    id="{{ $selectId }}"
    name="per_page"
    class="w-full text-sm border border-outline-variant/30 rounded-xl px-3 py-2 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30"
  >
    @foreach($perPageOptions as $option)
      <option value="{{ $option }}" @selected($perPage === $option)>{{ $option }}</option>
    @endforeach
  </select>
</div>
