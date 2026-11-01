@php
  $type = $question->type === 'image' ? 'images' : $question->type;
  $options = is_array($question->options) ? $question->options : [];
  $isSystem = in_array((int) $question->id, $system_questions_ids ?? [], true);
  $isEnabled = (bool) ($question->is_active ?? true);
  $formContext = $formContext ?? 'default';
  $typeLabel = match ($type) {
      'input' => 'Input',
      'textarea' => 'Textarea',
      'select' => 'Select',
      'toggle' => 'Toggle',
      'radio' => 'Radio',
      'images' => 'Images',
      'style' => 'Style',
      'placement' => 'Placement',
      'sizes' => 'Sizes',
      default => ucfirst((string) $type),
  };
  $pillCls = match ($type) {
      'images', 'image' => 'a',
      'textarea', 'input' => 'k',
      default => 'b',
  };
@endphp
<div
  class="q-row cfrow {{ $isEnabled ? '' : 'off' }}"
  data-id="{{ $question->id }}"
  data-question="{{ e($question->question) }}"
  data-type="{{ $typeLabel }}"
  data-type-value="{{ $type }}"
  data-question-type="{{ $question->question_type ?? 'other' }}"
  data-form-context="{{ $formContext }}"
  data-description="{{ e($question->description ?? '') }}"
  data-placeholder="{{ e($question->placeholder ?? '') }}"
  data-system="{{ $isSystem ? 'true' : 'false' }}"
  data-enabled="{{ $isEnabled ? 'true' : 'false' }}"
  data-required="{{ $question->is_required ? 'true' : 'false' }}"
  data-available="{{ $isEnabled ? 'true' : 'false' }}"
  data-options='@json($options)'
>
  <span class="ms faint q-drag-handle drag" title="Drag to reorder">drag_indicator</span>
  <span class="qt q-text" title="{{ $isSystem ? '' : 'Edit question' }}" @if(! $isSystem) role="button" tabindex="0" @endif>
    {{ $question->question }}
    @unless($question->is_required)
      <span class="faint" style="font-size:12px">(optional)</span>
    @endunless
  </span>
  <span class="pill nd {{ $pillCls }}">{{ $typeLabel }}</span>
  @if($isSystem)
    <span class="pill nd k" style="font-size:10.5px">SYSTEM</span>
  @else
    <div class="cf-actions">
      <button type="button" class="cf-icon-btn js-edit-question" title="Edit question" aria-label="Edit question">
        <span class="ms">edit</span>
      </button>
      <button type="button" class="cf-icon-btn del js-remove-question" title="Delete question" aria-label="Delete question">
        <span class="ms">delete</span>
      </button>
    </div>
  @endif
  <div class="tog toggle-switch {{ $isEnabled ? 'on active' : '' }}" onclick="toggleQuestionStatus(this)" role="switch" aria-checked="{{ $isEnabled ? 'true' : 'false' }}" title="Show this question" data-own></div>
</div>
