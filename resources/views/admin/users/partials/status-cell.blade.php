@if(!empty($onboardingProgress))
  <div class="flex flex-col items-start gap-0.5">
    @include('admin.users.partials.status-badge', ['status' => $status])
    <span class="text-[10px] font-semibold text-amber-700/80 pl-1">{{ $onboardingProgress['progress_label'] }} · on {{ $onboardingProgress['current_step_label'] }}</span>
  </div>
@else
  @include('admin.users.partials.status-badge', ['status' => $status])
@endif
