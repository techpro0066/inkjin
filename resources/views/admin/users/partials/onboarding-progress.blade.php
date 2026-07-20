@if(!empty($user['onboarding_progress']))
  <div class="bg-white rounded-xl border border-amber-200/60 p-4">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
      <div>
        <h4 class="text-sm font-bold text-on-surface">Onboarding Progress</h4>
        <p class="text-xs text-on-surface-variant mt-0.5">
          {{ $user['onboarding_progress']['completed_count'] }}/{{ $user['onboarding_progress']['total_steps'] }} steps completed
          · currently on <span class="font-semibold text-on-surface">{{ $user['onboarding_progress']['current_step_label'] }}</span>
        </p>
      </div>
      <span class="inline-flex items-center self-start bg-amber-50 text-amber-800 text-xs font-bold px-3 py-1.5 rounded-full">
        {{ $user['onboarding_progress']['progress_label'] }}
      </span>
    </div>

    <div class="w-full bg-surface-container-high rounded-full h-2 mb-4 overflow-hidden">
      <div
        class="h-full bg-amber-500 rounded-full transition-all"
        style="width: {{ ($user['onboarding_progress']['completed_count'] / max(1, $user['onboarding_progress']['total_steps'])) * 100 }}%"
      ></div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
      @foreach($user['onboarding_progress']['steps'] as $step)
        <div class="flex items-center gap-2 rounded-lg px-3 py-2 {{ $step['completed'] ? 'bg-green-50 text-green-800' : ($step['step'] === $user['onboarding_progress']['current_step'] ? 'bg-amber-50 text-amber-900 ring-1 ring-amber-200' : 'bg-surface-container-low text-on-surface-variant') }}">
          <span class="material-symbols-outlined text-base {{ $step['completed'] ? 'text-green-600' : ($step['step'] === $user['onboarding_progress']['current_step'] ? 'text-amber-600' : 'text-outline') }}">
            {{ $step['completed'] ? 'check_circle' : ($step['step'] === $user['onboarding_progress']['current_step'] ? 'radio_button_checked' : 'radio_button_unchecked') }}
          </span>
          <span class="text-xs font-semibold">{{ $step['step'] }}. {{ $step['label'] }}</span>
        </div>
      @endforeach
    </div>
  </div>
@endif
