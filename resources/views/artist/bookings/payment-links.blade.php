@extends('layouts.artist_dashboard_layout')

@section('title', 'Payment links')

@section('content')
<main class="main-content flex-1 min-h-screen">
  <div class="p-6 md:p-10 lg:p-12 max-w-6xl">
    <div class="mb-8">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-2">
        <div>
          <h2 class="text-3xl font-extrabold text-on-surface tracking-tight">Bookings</h2>
          <p class="text-on-surface-variant mt-1">
            {{ $paymentLinks->total() }} {{ Str::plural('payment link', $paymentLinks->total()) }} total
          </p>
        </div>
        <a href="{{ route('artist.payment-link') }}"
          class="inline-flex items-center justify-center gap-2 bg-primary text-white px-5 py-2.5 rounded-xl font-semibold text-sm hover:bg-primary-container transition-colors shadow-sm">
          <span class="material-symbols-outlined text-lg">add</span> New payment link
        </a>
      </div>
    </div>

    @include('artist.bookings.partials.page-tabs', ['activeTab' => 'payment-links'])

    @php
      $statusStyles = [
          'paid' => 'bg-green-50 text-green-700 ring-green-500/20',
          'pending' => 'bg-amber-50 text-amber-900 ring-amber-500/20',
          'expired' => 'bg-slate-100 text-slate-700 ring-slate-500/15',
      ];
    @endphp

    @if ($paymentLinks->isEmpty())
      <div class="bg-white rounded-2xl border border-outline-variant/20 p-10 text-center text-on-surface-variant">
        <p class="font-medium text-on-surface mb-1">No payment links yet</p>
        <p class="text-sm">Create a link to collect a deposit or full payment, then find it here anytime.</p>
      </div>
    @else
      <div class="bg-white rounded-2xl shadow-sm border border-outline-variant/20 mb-6 overflow-hidden">
        <div class="hidden sm:block overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="bg-surface-container-low/50 text-on-surface-variant text-xs uppercase tracking-wider">
                <th class="text-left px-6 py-3 font-semibold">Title</th>
                <th class="text-left px-6 py-3 font-semibold">Deposit</th>
                <th class="text-left px-6 py-3 font-semibold">Total</th>
                <th class="text-left px-6 py-3 font-semibold">Status</th>
                <th class="text-left px-6 py-3 font-semibold">Actions</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($paymentLinks as $link)
                @php
                  $status = $link->listStatus();
                  $badgeCls = $statusStyles[$status] ?? 'bg-surface-container text-on-surface ring-outline-variant/20';
                  $label = ucfirst($status);
                @endphp
                <tr class="border-t border-outline-variant/10 hover:bg-surface-container-low/40">
                  <td class="px-6 py-4 font-medium text-on-surface">{{ $link->title }}</td>
                  <td class="px-6 py-4 text-on-surface tabular-nums">€{{ number_format($link->depositAmount(), 2) }}</td>
                  <td class="px-6 py-4 text-on-surface tabular-nums">€{{ number_format($link->totalAmount(), 2) }}</td>
                  <td class="px-6 py-4">
                    <span class="inline-flex items-center text-xs font-semibold px-2.5 py-1 rounded-full ring-1 ring-inset {{ $badgeCls }}">{{ $label }}</span>
                  </td>
                  <td class="px-6 py-4">
                    <button type="button"
                      class="js-copy-payment-link inline-flex h-9 w-9 items-center justify-center rounded-lg border border-transparent text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface"
                      title="Copy link"
                      data-link="{{ e($link->publicUrl()) }}">
                      <span class="material-symbols-outlined text-[22px]">content_copy</span>
                    </button>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>

        <div class="sm:hidden divide-y divide-outline-variant/10">
          @foreach ($paymentLinks as $link)
            @php
              $status = $link->listStatus();
              $badgeCls = $statusStyles[$status] ?? 'bg-surface-container text-on-surface ring-outline-variant/20';
              $label = ucfirst($status);
            @endphp
            <div class="p-4">
              <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                  <p class="font-semibold text-on-surface truncate">{{ $link->title }}</p>
                  <p class="text-sm text-on-surface-variant mt-1">
                    Deposit €{{ number_format($link->depositAmount(), 2) }}
                    · Total €{{ number_format($link->totalAmount(), 2) }}
                  </p>
                </div>
                <span class="inline-flex items-center text-xs font-semibold px-2.5 py-1 rounded-full ring-1 ring-inset {{ $badgeCls }} shrink-0">{{ $label }}</span>
              </div>
              <button type="button"
                class="js-copy-payment-link mt-3 inline-flex items-center gap-1.5 text-sm font-semibold text-primary"
                data-link="{{ e($link->publicUrl()) }}">
                <span class="material-symbols-outlined text-[18px]">content_copy</span>
                Copy link
              </button>
            </div>
          @endforeach
        </div>
      </div>

      <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
        {{ $paymentLinks->links() }}
      </div>
    @endif
  </div>
</main>

<div id="paymentLinkCopyToast" class="fixed bottom-24 right-5 z-[130] translate-x-full opacity-0 pointer-events-none transition-all duration-300" role="status" aria-live="polite">
  <div class="bg-on-surface text-white text-sm font-semibold px-4 py-3 rounded-xl shadow-lg flex items-center gap-2">
    <span class="material-symbols-outlined text-base text-green-300">check_circle</span>
    Link copied
  </div>
</div>
@endsection

@section('scripts')
<script>
(function () {
  var toast = document.getElementById('paymentLinkCopyToast');
  var toastTimer = null;

  function showCopied() {
    if (!toast) return;
    toast.classList.remove('translate-x-full', 'opacity-0', 'pointer-events-none');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(function () {
      toast.classList.add('translate-x-full', 'opacity-0', 'pointer-events-none');
    }, 2200);
  }

  function copyLink(url) {
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(url).then(showCopied).catch(function () {
        fallbackCopy(url);
      });
      return;
    }
    fallbackCopy(url);
  }

  function fallbackCopy(url) {
    var input = document.createElement('textarea');
    input.value = url;
    input.setAttribute('readonly', '');
    input.style.position = 'absolute';
    input.style.left = '-9999px';
    document.body.appendChild(input);
    input.select();
    try {
      document.execCommand('copy');
      showCopied();
    } catch (e) {}
    document.body.removeChild(input);
  }

  document.querySelectorAll('.js-copy-payment-link').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var url = btn.getAttribute('data-link') || '';
      if (url) copyLink(url);
    });
  });
})();
</script>
@endsection
