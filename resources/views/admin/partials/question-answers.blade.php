@php
  $qaRows = \App\Support\QuestionAnswerPresenter::rows($questionsAnswers ?? []);
@endphp
<div class="bg-white rounded-2xl border border-outline-variant/20 p-5">
  <h3 class="text-sm font-bold text-on-surface mb-4">Questions &amp; answers</h3>
  @if($qaRows === [])
    <p class="text-sm text-on-surface-variant">No intake answers recorded.</p>
  @else
    <div class="space-y-4">
      @foreach($qaRows as $row)
        <div class="border-b border-outline-variant/15 pb-3 last:border-b-0 last:pb-0">
          <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider mb-1">{{ $row['question'] }}</p>
          @if(!empty($row['images']))
            <div class="flex flex-wrap gap-2 mt-2">
              @foreach($row['images'] as $index => $url)
                <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" class="block w-20 h-20 rounded-lg overflow-hidden border border-outline-variant/20 bg-surface-container-high hover:ring-2 hover:ring-primary/30" title="View image {{ $index + 1 }}">
                  <img src="{{ $url }}" alt="Reference image {{ $index + 1 }}" class="w-full h-full object-cover">
                </a>
              @endforeach
            </div>
          @elseif(is_string($row['answer']) && preg_match('#^https?://#i', trim($row['answer'])))
            <a href="{{ trim($row['answer']) }}" target="_blank" rel="noopener noreferrer" class="text-sm font-semibold text-primary hover:underline">View file</a>
          @else
            <p class="text-sm font-medium text-on-surface whitespace-pre-line">{{ \App\Support\QuestionAnswerPresenter::formatAnswer($row['answer'], $row['type']) }}</p>
          @endif
        </div>
      @endforeach
    </div>
  @endif
</div>
