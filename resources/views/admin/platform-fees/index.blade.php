@extends('layouts.admin_dashboard_layout')

@section('title', 'Platform Fees')

@section('content')
<main class="main-content flex-1 min-h-screen">
  <div class="p-6 md:p-10 lg:p-12 max-w-7xl">
    <div class="mb-8">
      <h2 class="text-3xl font-extrabold text-on-surface tracking-tight">Platform Fees</h2>
      <p class="text-on-surface-variant mt-1">Set the booking fee for each supported currency. Each amount applies to every listed country using that currency.</p>
    </div>

    @if (session('status'))
      <div role="status" class="mb-5 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-semibold text-green-800">
        {{ session('status') }}
      </div>
    @endif

    @if ($errors->any())
      <div role="alert" class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
        <p class="font-semibold">Please correct the fee amounts and try again.</p>
        <ul class="mt-1 list-disc pl-5">
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <form method="POST" action="{{ route('admin.platform-fees.update') }}">
      @csrf
      @method('PUT')
      <div class="bg-white rounded-2xl shadow-sm border border-outline-variant/20 overflow-hidden">
        <div class="hidden md:grid md:grid-cols-[120px_minmax(0,1fr)_220px] gap-5 px-6 py-4 border-b border-outline-variant/15 text-[11px] font-bold uppercase tracking-wider text-on-surface-variant">
          <span>Currency</span>
          <span>Countries</span>
          <span>Amount</span>
        </div>
        <div class="divide-y divide-outline-variant/10">
          @foreach ($feeGroups as $group)
            <div class="grid grid-cols-1 md:grid-cols-[120px_minmax(0,1fr)_220px] gap-3 md:gap-5 items-start md:items-center px-4 sm:px-6 py-5">
              <div>
                <span class="text-xs font-bold uppercase text-on-surface-variant md:hidden">Currency</span>
                <p class="font-extrabold text-on-surface">{{ $group['currency'] }}</p>
              </div>
              <div>
                <span class="text-xs font-bold uppercase text-on-surface-variant md:hidden">Countries</span>
                <p class="text-sm leading-6 text-on-surface-variant">{{ implode(', ', $group['countries']) }}</p>
              </div>
              <label class="block">
                <span class="text-xs font-bold uppercase text-on-surface-variant md:hidden">Amount ({{ $group['currency'] }})</span>
                <span class="sr-only">Amount in {{ $group['currency'] }}</span>
                <input
                  type="text"
                  name="amounts[{{ $group['currency'] }}]"
                  value="{{ old('amounts.'.$group['currency'], number_format($group['amount'], 2, '.', '')) }}"
                  pattern="(\d+(\.\d{0,2})?|\.\d{1,2})"
                  maxlength="11"
                  required
                  inputmode="decimal"
                  autocomplete="off"
                  class="mt-1 md:mt-0 w-full rounded-xl border-outline-variant/50 text-sm font-semibold focus:border-primary focus:ring-primary"
                >
              </label>
            </div>
          @endforeach
        </div>
        <div class="flex justify-end border-t border-outline-variant/15 px-4 sm:px-6 py-5">
          <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-3 text-sm font-semibold text-white hover:bg-primary-container transition-colors">
            <span class="material-symbols-outlined text-[18px]">save</span>
            Save platform fees
          </button>
        </div>
      </div>
    </form>
  </div>
</main>
@endsection

@section('scripts')
<script>
  document.querySelectorAll('input[name^="amounts["]').forEach(function (input) {
    input.addEventListener('input', function () {
      var value = input.value.replace(/[^\d.]/g, '');
      var decimal = value.indexOf('.');
      if (decimal !== -1) {
        value = value.slice(0, decimal + 1) + value.slice(decimal + 1).replace(/\./g, '');
        var parts = value.split('.');
        value = parts[0].slice(0, 8) + '.' + (parts[1] || '').slice(0, 2);
      } else {
        value = value.slice(0, 8);
      }

      if (input.value !== value) {
        input.value = value;
      }
    });
  });
</script>
@endsection
