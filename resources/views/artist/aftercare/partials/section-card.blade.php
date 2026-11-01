<div class="card aftercare-section acsec" style="margin-bottom:14px" data-section-key="{{ $section['key'] }}">
  <div class="ch">
    <div>
      <h3>{{ $section['title'] }}</h3>
      <div class="faint" style="font-size:12.5px;margin-top:2px">Add, edit or remove bullet points</div>
    </div>
  </div>
  <div style="padding:18px 22px">
    <div class="aftercare-item-list" aria-label="{{ $section['title'] }} items">
      @forelse(($section['items'] ?? []) as $item)
        <div class="acitem row">
          <input type="text" class="in included-item-input" maxlength="500" value="{{ $item }}">
          <button type="button" class="ac-rm" title="Remove" aria-label="Remove item"><span class="ms">close</span></button>
        </div>
      @empty
        <div class="acitem row">
          <input type="text" class="in included-item-input" maxlength="500" placeholder="Add an aftercare tip" value="">
          <button type="button" class="ac-rm" title="Remove" aria-label="Remove item"><span class="ms">close</span></button>
        </div>
      @endforelse
    </div>
    <button type="button" class="acadd btn-add-aftercare-item"><span class="ms">add</span>Add item</button>
  </div>
</div>
