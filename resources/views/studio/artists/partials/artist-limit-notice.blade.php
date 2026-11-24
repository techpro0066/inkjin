@if($artistLimitReached ?? false)
  @once
    @push('styles')
      <style>
        .artist-limit-notice{display:flex;gap:12px;align-items:flex-start;margin:12px 0 14px;padding:12px 14px;border:1px solid #F3D9A2;border-radius:10px;background:#FFF9ED;color:#80520C;font-size:12.5px;line-height:1.45}
        .artist-limit-notice .ms{flex:none;font-size:18px;line-height:1.2}
        .artist-limit-notice p{margin:0}
        .artist-limit-notice a{color:#3E007C;font-weight:700;text-decoration:underline}
        .artists-page button:disabled{opacity:.55;cursor:not-allowed}
      </style>
    @endpush
  @endonce
  <div class="artist-limit-notice" role="status" aria-live="polite">
    <span class="ms" aria-hidden="true">storefront</span>
    <p>
      @if($isFounding ?? false)
        <strong>Founding plan: {{ $artistLimit }} artists, the most you can have on this plan.</strong>
        If someone leaves, you can add an artist in their place. Guest artists don't count.
      @else
        <strong>Your studio has reached the {{ $artistLimit }}-artist limit.</strong>
        Invites and accepting join requests are off until a place opens. Guest artists don't count.
        <a href="{{ route('studio.account.plans') }}">View plans</a> to add more.
      @endif
    </p>
  </div>
@endif
