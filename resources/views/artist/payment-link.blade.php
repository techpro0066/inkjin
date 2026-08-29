@extends('layouts.artist_dashboard_layout')

@section('title', 'New payment link')

@section('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<style>
  .flatpickr-calendar {
    font-family: 'Plus Jakarta Sans', sans-serif;
    border-radius: 1rem;
    border: 1px solid rgba(202, 196, 211, 0.4);
    box-shadow: 0 12px 32px rgba(28, 27, 33, 0.12);
  }
  .flatpickr-day.selected,
  .flatpickr-day.startRange,
  .flatpickr-day.endRange,
  .flatpickr-day.selected:hover {
    background: #1a4d9e;
    border-color: #1a4d9e;
  }
  .flatpickr-time input:hover,
  .flatpickr-time .flatpickr-am-pm:hover,
  .flatpickr-time input:focus,
  .flatpickr-time .flatpickr-am-pm:focus {
    background: #e7f1ff;
  }
  .modal-backdrop {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.6);
    z-index: 400;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s ease;
  }
  .modal-backdrop.modal-visible { display: flex; }
  .modal-backdrop.modal-visible:not(.modal-open) { pointer-events: none; }
  .modal-backdrop.modal-open { opacity: 1; pointer-events: auto; }
  .payment-link-modal-inner {
    transform: scale(0.96) translateY(10px);
    opacity: 0;
    transition: transform 0.32s cubic-bezier(0.22, 1, 0.36, 1), opacity 0.28s ease;
  }
  .modal-backdrop.modal-open .payment-link-modal-inner {
    transform: scale(1) translateY(0);
    opacity: 1;
  }
</style>
@endsection

@section('content')
<main class="main-content flex-1 min-h-screen flex items-start justify-center">
  <div class="w-full max-w-2xl p-4 sm:p-6 md:p-10">
    <div class="bg-white rounded-2xl border border-outline-variant/30 p-6 sm:p-8 md:p-10 shadow-sm">
      <div id="paymentLinkFormView">
      <h1 class="text-2xl sm:text-3xl font-extrabold text-on-surface tracking-tight mb-2">New payment link</h1>
      @if(empty($canCreatePaymentLinks))
        <div class="mb-6 rounded-xl border px-4 py-3 text-sm font-medium" style="border-color: rgba(255,191,0,0.45); background: rgba(255,191,0,0.12); color: #8B6914;">
          {{ $paymentLinksBlockedMessage ?? 'Complete payout setup in Payment settings before creating payment links.' }}
          <a href="{{ route('settings.payment') }}" class="block mt-2 font-semibold text-primary hover:underline">Go to Payment settings</a>
        </div>
      @endif
      @if(!empty($isAutoScheduling))
        <p class="text-sm text-on-surface-variant mb-6 sm:mb-8">Artist — auto-scheduling on</p>
      @else
        <p class="text-sm text-on-surface-variant mb-6 sm:mb-8">Artist — managed scheduling</p>
      @endif

      <form id="paymentLinkForm" action="{{ route('artist.payment-link.validate') }}" method="POST" novalidate class="space-y-5 sm:space-y-6">
        @csrf
        <input type="hidden" name="payment_type" id="paymentLinkPaymentType" value="">
        <input type="hidden" name="session_duration" id="paymentLinkSessionDuration" value="">
        <input type="hidden" name="expires" id="paymentLinkExpires" value="">

        <div>
          <label for="paymentLinkAmount" class="block text-sm text-on-surface-variant mb-2">Amount</label>
          <input
            id="paymentLinkAmount"
            name="amount"
            type="text"
            placeholder="€ 0"
            class="w-full rounded-xl border border-outline-variant/40 bg-white px-4 py-3.5 text-base text-on-surface placeholder:text-outline/50 outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary"
          >
          <p id="amount_error" class="hidden text-sm text-error mt-1.5"></p>
        </div>

        <div>
          <div class="grid grid-cols-2 gap-2 sm:gap-3" role="radiogroup" aria-label="Payment type">
            <button type="button" data-choice-group="payment-type" data-choice-value="deposit" class="payment-choice-btn rounded-xl border border-outline-variant/40 bg-white px-3 py-3.5 text-sm sm:text-base font-semibold text-on-surface">
              Deposit
            </button>
            <button type="button" data-choice-group="payment-type" data-choice-value="full" class="payment-choice-btn rounded-xl border border-outline-variant/40 bg-white px-3 py-3.5 text-sm sm:text-base font-semibold text-on-surface">
              Full payment
            </button>
          </div>
          <p id="payment_type_error" class="hidden text-sm text-error mt-1.5"></p>
        </div>

        <div>
          <label for="paymentLinkTitle" class="block text-sm text-on-surface-variant mb-2">Title</label>
          <input
            id="paymentLinkTitle"
            name="title"
            type="text"
            placeholder="e.g. Deposit — peony, forearm"
            class="w-full rounded-xl border border-outline-variant/40 bg-white px-4 py-3.5 text-base text-on-surface placeholder:text-outline/50 outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary"
          >
          <p id="title_error" class="hidden text-sm text-error mt-1.5"></p>
        </div>

        @if(!empty($isAutoScheduling))
          <div class="rounded-xl bg-surface-container-low px-4 py-3.5 text-sm sm:text-base text-on-surface">
            Your client picks a time from your open slots
          </div>
        @else
          <div>
            <label for="paymentLinkDateTime" class="block text-sm text-on-surface-variant mb-2">Date and time</label>
            <div class="relative">
              <input
                id="paymentLinkDateTime"
                name="date_time"
                type="text"
                placeholder="Select date and time"
                readonly
                class="w-full rounded-xl border border-outline-variant/40 bg-white px-4 py-3.5 pr-12 text-base text-on-surface placeholder:text-outline/50 outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary cursor-pointer"
              >
              <span class="material-symbols-outlined pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 text-on-surface-variant">calendar_month</span>
            </div>
            <p id="date_time_error" class="hidden text-sm text-error mt-1.5"></p>
          </div>
        @endif

        <div>
          <p class="block text-sm text-on-surface-variant mb-2">Session duration · required</p>
          <div class="flex flex-wrap gap-2" role="radiogroup" aria-label="Session duration">
            <button type="button" data-choice-group="session-duration" data-choice-value="2h" class="payment-choice-btn rounded-xl border border-outline-variant/40 bg-white px-4 py-2.5 text-sm sm:text-base font-semibold text-on-surface">2h</button>
            <button type="button" data-choice-group="session-duration" data-choice-value="3h" class="payment-choice-btn rounded-xl border border-outline-variant/40 bg-white px-4 py-2.5 text-sm sm:text-base font-semibold text-on-surface">3h</button>
            <button type="button" data-choice-group="session-duration" data-choice-value="4h" class="payment-choice-btn rounded-xl border border-outline-variant/40 bg-white px-4 py-2.5 text-sm sm:text-base font-semibold text-on-surface">4h</button>
            <button type="button" data-choice-group="session-duration" data-choice-value="half-day" class="payment-choice-btn rounded-xl border border-outline-variant/40 bg-white px-4 py-2.5 text-sm sm:text-base font-semibold text-on-surface">Half day</button>
            <button type="button" data-choice-group="session-duration" data-choice-value="full-day" class="payment-choice-btn rounded-xl border border-outline-variant/40 bg-white px-4 py-2.5 text-sm sm:text-base font-semibold text-on-surface">Full day</button>
          </div>
          <p id="session_duration_error" class="hidden text-sm text-error mt-1.5"></p>
        </div>

        <div id="paymentLinkTotalPriceWrap" class="hidden">
          <label for="paymentLinkTotalPrice" class="block text-sm text-on-surface-variant mb-2">Total price</label>
          <input
            id="paymentLinkTotalPrice"
            name="total_price"
            type="text"
            placeholder="€ 0"
            class="w-full rounded-xl border border-outline-variant/40 bg-white px-4 py-3.5 text-base text-on-surface placeholder:text-outline/50 outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary"
          >
          <p id="total_price_error" class="hidden text-sm text-error mt-1.5"></p>
        </div>

        <div>
          <p class="block text-sm text-on-surface-variant mb-2">Expires</p>
          <div class="flex flex-wrap gap-2" role="radiogroup" aria-label="Expires">
            <button type="button" data-choice-group="expires" data-choice-value="2 days" class="payment-choice-btn rounded-xl border border-outline-variant/40 bg-white px-4 py-2.5 text-sm sm:text-base font-semibold text-on-surface">2 days</button>
            <button type="button" data-choice-group="expires" data-choice-value="3 days" class="payment-choice-btn rounded-xl border border-outline-variant/40 bg-white px-4 py-2.5 text-sm sm:text-base font-semibold text-on-surface">3 days</button>
            <button type="button" data-choice-group="expires" data-choice-value="7 days" class="payment-choice-btn rounded-xl border border-outline-variant/40 bg-white px-4 py-2.5 text-sm sm:text-base font-semibold text-on-surface">7 days</button>
          </div>
          <p id="expires_error" class="hidden text-sm text-error mt-1.5"></p>
        </div>

        <button type="submit" id="paymentLinkGenerateBtn" class="w-full rounded-xl bg-[#1c1b21] px-4 py-4 text-base font-bold text-white disabled:opacity-60 disabled:pointer-events-none" @if(empty($canCreatePaymentLinks)) disabled @endif>
          Generate link
        </button>

        <p id="paymentLinkBalanceNote" class="hidden text-center text-sm text-on-surface-variant">Balance auto-calculated after you enter amounts</p>
      </form>
      </div>

      <div id="paymentLinkReadyView" class="hidden">
        <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight mb-6" style="color: #1b5e4a;">Link ready</h1>
        <div class="rounded-xl bg-surface-container-low px-4 py-3.5 mb-5">
          <p id="paymentLinkReadyTitle" class="font-bold text-on-surface"></p>
          <p id="paymentLinkReadySummary" class="text-sm text-on-surface-variant mt-1"></p>
        </div>
        <div class="relative mb-5">
          <input
            id="paymentLinkReadyUrl"
            type="text"
            readonly
            class="w-full rounded-xl border border-outline-variant/40 bg-white px-4 py-3.5 pr-24 text-base text-on-surface outline-none"
          >
          <button type="button" id="paymentLinkCopyUrlInline" class="absolute right-3 top-1/2 -translate-y-1/2 inline-flex items-center gap-1 text-on-surface-variant hover:text-on-surface" aria-label="Copy link">
            <span class="material-symbols-outlined text-[20px]" data-copy-icon>content_copy</span>
            <span class="hidden text-sm font-semibold text-green-600" data-copy-label>Copied</span>
          </button>
        </div>
        <div class="mb-5">
          <label for="paymentLinkReadyMessage" class="block text-sm text-on-surface-variant mb-2">Message for your client</label>
          <textarea
            id="paymentLinkReadyMessage"
            rows="4"
            class="w-full rounded-xl border border-outline-variant/40 bg-white px-4 py-3.5 text-base text-on-surface outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary resize-none"
          ></textarea>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-5">
          <button type="button" id="paymentLinkCopyMessageBtn" class="w-full rounded-xl bg-[#1c1b21] px-4 py-3.5 text-base font-bold text-white">
            Copy message
          </button>
          <button type="button" id="paymentLinkCopyLinkBtn" class="w-full rounded-xl border border-outline-variant/40 bg-white px-4 py-3.5 text-base font-bold text-on-surface">
            Copy link
          </button>
        </div>
        <div class="rounded-xl bg-surface-container-low px-4 py-3.5 flex items-center gap-4">
          <img id="paymentLinkReadyQr" alt="Payment link QR code" class="hidden w-14 h-14 rounded-lg bg-white object-contain flex-shrink-0">
          <div>
            <p class="font-bold text-on-surface">Show QR at the chair</p>
            <p class="text-sm text-on-surface-variant">Client scans and pays on the spot</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>

  <div class="modal-backdrop" id="paymentLinkConfirmModal" aria-hidden="true">
    <div class="payment-link-modal-inner bg-white rounded-2xl w-full max-w-md mx-4 shadow-2xl overflow-hidden">
      <div class="p-6">
        <h3 class="text-lg font-bold text-on-surface tracking-tight">Generate this payment link?</h3>
        <p id="paymentLinkConfirmTitle" class="text-sm font-semibold text-on-surface mt-3"></p>
        <div class="mt-4 space-y-2 text-sm text-on-surface-variant">
          <p id="paymentLinkConfirmAmount"></p>
          <p id="paymentLinkConfirmDue" class="hidden"></p>
          <p id="paymentLinkConfirmExpires"></p>
        </div>
        <p id="paymentLinkConfirmError" class="hidden mt-3 text-xs text-error font-semibold leading-snug"></p>
      </div>
      <div class="px-6 py-4 border-t border-outline-variant/15 flex items-center justify-end gap-3 bg-surface-container-low/30">
        <button type="button" id="paymentLinkConfirmCancel" class="text-sm font-semibold text-on-surface-variant hover:text-on-surface px-4 py-2.5 rounded-xl transition-colors">Cancel</button>
        <button type="button" id="paymentLinkConfirmBtn" class="bg-[#1c1b21] text-white px-5 py-2.5 rounded-xl font-semibold text-sm hover:opacity-95 transition-opacity">Confirm</button>
      </div>
    </div>
  </div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
(function () {
  var selectedClass = ['bg-[#e7f1ff]', 'border-transparent', 'font-bold', 'text-[#1a4d9e]'];
  var idleClass = ['bg-white', 'border-outline-variant/40', 'font-semibold', 'text-on-surface'];
  var form = document.getElementById('paymentLinkForm');
  var generateBtn = document.getElementById('paymentLinkGenerateBtn');
  var storeUrl = @json(route('artist.payment-link.store'));
  var confirmModal = document.getElementById('paymentLinkConfirmModal');
  var confirmBtn = document.getElementById('paymentLinkConfirmBtn');
  var confirmCancel = document.getElementById('paymentLinkConfirmCancel');
  var confirmError = document.getElementById('paymentLinkConfirmError');
  var formView = document.getElementById('paymentLinkFormView');
  var readyView = document.getElementById('paymentLinkReadyView');
  var totalPriceWrap = document.getElementById('paymentLinkTotalPriceWrap');
  var totalPriceInput = document.getElementById('paymentLinkTotalPrice');
  var amountInput = document.getElementById('paymentLinkAmount');
  var balanceNote = document.getElementById('paymentLinkBalanceNote');
  var paymentTypeInput = document.getElementById('paymentLinkPaymentType');
  var sessionDurationInput = document.getElementById('paymentLinkSessionDuration');
  var expiresInput = document.getElementById('paymentLinkExpires');
  var dateTimeInput = document.getElementById('paymentLinkDateTime');
  var fieldInputs = {
    amount: amountInput,
    title: document.getElementById('paymentLinkTitle'),
    date_time: dateTimeInput,
    total_price: totalPriceInput,
    expires: expiresInput
  };

  if (window.flatpickr && dateTimeInput) {
    flatpickr(dateTimeInput, {
      enableTime: true,
      time_24hr: true,
      dateFormat: 'Y-m-d H:i',
      altInput: true,
      altFormat: 'D j M · H:i',
      minDate: 'today',
      minuteIncrement: 15,
      allowInput: false,
      disableMobile: true,
      onChange: function () {
        clearFieldError('date_time');
      }
    });
  }

  function parseMoney(value) {
    if (!value) return null;
    var cleaned = String(value).replace(/[^\d.,-]/g, '').replace(',', '.');
    if (cleaned === '' || cleaned === '.' || cleaned === '-') return null;
    var number = parseFloat(cleaned);
    return isNaN(number) ? null : number;
  }

  function setFieldError(field, message) {
    var errorEl = document.getElementById(field + '_error');
    var input = fieldInputs[field];
    if (errorEl) {
      errorEl.textContent = message || '';
      errorEl.classList.toggle('hidden', !message);
    }
    if (input) {
      input.classList.toggle('border-error', !!message);
      if (field === 'date_time' && dateTimeInput && dateTimeInput._flatpickr && dateTimeInput._flatpickr.altInput) {
        dateTimeInput._flatpickr.altInput.classList.toggle('border-error', !!message);
      }
    }
  }

  function clearFieldError(field) {
    setFieldError(field, '');
  }

  function clearAllFieldErrors() {
    ['amount', 'payment_type', 'title', 'date_time', 'session_duration', 'total_price', 'expires'].forEach(clearFieldError);
  }

  var fieldOrder = ['amount', 'payment_type', 'title', 'date_time', 'session_duration', 'total_price', 'expires'];

  function getFieldScrollTarget(field) {
    if (field === 'date_time' && dateTimeInput && dateTimeInput._flatpickr && dateTimeInput._flatpickr.altInput) {
      return dateTimeInput._flatpickr.altInput;
    }
    if (fieldInputs[field]) {
      return fieldInputs[field];
    }
    return document.getElementById(field + '_error');
  }

  function scrollToFirstError(errors) {
    var firstField = fieldOrder.find(function (field) {
      return errors && errors[field];
    });
    if (!firstField) return;
    var target = getFieldScrollTarget(firstField);
    if (!target || typeof target.scrollIntoView !== 'function') return;
    target.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }

  function showFieldErrors(errors) {
    clearAllFieldErrors();
    Object.keys(errors || {}).forEach(function (field) {
      var messages = errors[field];
      setFieldError(field, Array.isArray(messages) ? messages[0] : messages);
    });
    scrollToFirstError(errors);
  }

  function setTotalPriceError(message) {
    setFieldError('total_price', message);
  }

  function formatMoney(value) {
    var rounded = Math.round(value * 100) / 100;
    if (Number.isInteger(rounded)) {
      return '€' + rounded.toString();
    }
    return '€' + rounded.toFixed(2);
  }

  function updateBalanceNote(amount, total) {
    if (!balanceNote) return;
    if (amount === null || total === null || total <= amount) {
      balanceNote.textContent = 'Balance auto-calculated after you enter amounts';
      return;
    }
    var remaining = Math.round((total - amount) * 100) / 100;
    balanceNote.textContent = 'Balance auto-calculated: ' + formatMoney(remaining) + ' due at session';
  }

  function validateTotalPrice() {
    if (!totalPriceWrap || totalPriceWrap.classList.contains('hidden')) {
      setTotalPriceError('');
      updateBalanceNote(null, null);
      return true;
    }

    var totalRaw = (totalPriceInput.value || '').trim();
    if (totalRaw === '') {
      setTotalPriceError('');
      updateBalanceNote(null, null);
      return true;
    }

    var amountRaw = (amountInput.value || '').trim();
    if (amountRaw === '') {
      setTotalPriceError('Please enter the amount first.');
      updateBalanceNote(null, null);
      return false;
    }

    var amount = parseMoney(amountRaw);
    var total = parseMoney(totalRaw);

    if (amount === null) {
      setTotalPriceError('Please enter a valid amount first.');
      updateBalanceNote(null, null);
      return false;
    }

    if (total === null) {
      setTotalPriceError('Please enter a valid total price.');
      updateBalanceNote(null, null);
      return false;
    }

    if (total <= amount) {
      setTotalPriceError('Total price must be greater than the amount.');
      updateBalanceNote(null, null);
      return false;
    }

    setTotalPriceError('');
    updateBalanceNote(amount, total);
    return true;
  }

  function setChoiceSelected(button, selected) {
    selectedClass.concat(idleClass).forEach(function (cls) {
      button.classList.remove(cls);
    });
    (selected ? selectedClass : idleClass).forEach(function (cls) {
      button.classList.add(cls);
    });
    button.setAttribute('aria-pressed', selected ? 'true' : 'false');
  }

  function updateDepositFields(paymentType) {
    var isDeposit = paymentType === 'deposit';
    if (totalPriceWrap) totalPriceWrap.classList.toggle('hidden', !isDeposit);
    if (balanceNote) balanceNote.classList.toggle('hidden', !isDeposit);
    if (!isDeposit) {
      setTotalPriceError('');
      updateBalanceNote(null, null);
    }
  }

  document.querySelectorAll('.payment-choice-btn').forEach(function (button) {
    button.setAttribute('aria-pressed', 'false');
    button.addEventListener('click', function () {
      var group = button.getAttribute('data-choice-group');
      var value = button.getAttribute('data-choice-value');
      document.querySelectorAll('.payment-choice-btn[data-choice-group="' + group + '"]').forEach(function (peer) {
        setChoiceSelected(peer, peer === button);
      });
      if (group === 'payment-type') {
        if (paymentTypeInput) paymentTypeInput.value = value;
        clearFieldError('payment_type');
        updateDepositFields(value);
        validateTotalPrice();
      }
      if (group === 'session-duration') {
        if (sessionDurationInput) sessionDurationInput.value = value;
        clearFieldError('session_duration');
      }
      if (group === 'expires') {
        if (expiresInput) expiresInput.value = value;
        clearFieldError('expires');
      }
    });
  });

  function selectDefaultChoice(group, value) {
    var button = document.querySelector('.payment-choice-btn[data-choice-group="' + group + '"][data-choice-value="' + value + '"]');
    if (button) button.click();
  }

  selectDefaultChoice('payment-type', 'deposit');
  selectDefaultChoice('session-duration', '3h');
  selectDefaultChoice('expires', '7 days');

  if (totalPriceInput) {
    totalPriceInput.addEventListener('input', validateTotalPrice);
    totalPriceInput.addEventListener('focus', validateTotalPrice);
  }
  if (amountInput) {
    amountInput.addEventListener('input', function () {
      clearFieldError('amount');
      validateTotalPrice();
    });
  }

  Object.keys(fieldInputs).forEach(function (field) {
    var input = fieldInputs[field];
    if (!input || field === 'amount' || field === 'total_price' || field === 'date_time') return;
    input.addEventListener('input', function () {
      clearFieldError(field);
    });
  });

  if (!form) return;

  form.addEventListener('submit', function (event) {
    event.preventDefault();
    clearAllFieldErrors();
    if (generateBtn) {
      generateBtn.disabled = true;
    }

    fetch(form.action, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: new FormData(form)
    }).then(function (response) {
      return response.json().then(function (data) {
        return { status: response.status, data: data };
      });
    }).then(function (result) {
      if (result.status === 422 && result.data && result.data.errors) {
        showFieldErrors(result.data.errors);
        return;
      }
      if (result.status === 200 && result.data && result.data.success) {
        openConfirmModal(result.data.preview || {});
      }
    }).catch(function () {
      // Ignore network errors silently.
    }).finally(function () {
      if (generateBtn) {
        generateBtn.disabled = false;
      }
    });
  });

  function openConfirmModal(preview) {
    var titleEl = document.getElementById('paymentLinkConfirmTitle');
    var amountEl = document.getElementById('paymentLinkConfirmAmount');
    var dueEl = document.getElementById('paymentLinkConfirmDue');
    var expiresEl = document.getElementById('paymentLinkConfirmExpires');
    if (titleEl) titleEl.textContent = preview.title || '';
    if (amountEl) {
      amountEl.textContent = preview.payment_type === 'full'
        ? 'Full payment ' + (preview.amount_formatted || '') + ' will be requested now.'
        : 'Deposit ' + (preview.amount_formatted || '') + ' will be requested now.';
    }
    if (dueEl) {
      var showDue = preview.payment_type === 'deposit' && preview.due_formatted;
      dueEl.textContent = showDue ? (preview.due_formatted + ' due at the session.') : '';
      dueEl.classList.toggle('hidden', !showDue);
    }
    if (expiresEl) expiresEl.textContent = 'Link expires in ' + (preview.expires || '7 days') + '.';
    if (confirmError) {
      confirmError.textContent = '';
      confirmError.classList.add('hidden');
    }
    if (!confirmModal) return;
    confirmModal.classList.add('modal-visible');
    requestAnimationFrame(function () {
      confirmModal.classList.add('modal-open');
    });
    confirmModal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
  }

  function closeConfirmModal() {
    if (!confirmModal) return;
    confirmModal.classList.remove('modal-open');
    setTimeout(function () {
      confirmModal.classList.remove('modal-visible');
    }, 300);
    confirmModal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
  }

  function setConfirmLoading(loading) {
    if (!confirmBtn) return;
    confirmBtn.disabled = loading;
    confirmBtn.textContent = loading ? 'Generating...' : 'Confirm';
  }

  function showReadyScreen(link) {
    var titleEl = document.getElementById('paymentLinkReadyTitle');
    var summaryEl = document.getElementById('paymentLinkReadySummary');
    var urlEl = document.getElementById('paymentLinkReadyUrl');
    var messageEl = document.getElementById('paymentLinkReadyMessage');
    var qrEl = document.getElementById('paymentLinkReadyQr');
    if (titleEl) titleEl.textContent = link.title || '';
    if (summaryEl) summaryEl.textContent = link.summary || '';
    if (urlEl) {
      urlEl.value = link.display_url || link.url || '';
      urlEl.setAttribute('data-full-url', link.url || '');
    }
    if (messageEl) messageEl.value = link.client_message || '';
    if (qrEl && link.url) {
      qrEl.src = 'https://api.qrserver.com/v1/create-qr-code/?size=112x112&data=' + encodeURIComponent(link.url);
      qrEl.classList.remove('hidden');
    }
    if (formView) formView.classList.add('hidden');
    if (readyView) readyView.classList.remove('hidden');
    if (readyView && typeof readyView.scrollIntoView === 'function') {
      readyView.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  }

  function copyText(text, button, idleLabel, onSuccess) {
    if (!text) return;
    var done = function () {
      if (typeof onSuccess === 'function') onSuccess();
      if (!button) return;
      button.textContent = 'Copied';
      setTimeout(function () {
        button.textContent = idleLabel;
      }, 1500);
    };
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(text).then(done).catch(function () {});
      return;
    }
    var temp = document.createElement('textarea');
    temp.value = text;
    document.body.appendChild(temp);
    temp.select();
    try { document.execCommand('copy'); } catch (e) {}
    document.body.removeChild(temp);
    done();
  }

  function readyLinkValue() {
    var urlEl = document.getElementById('paymentLinkReadyUrl');
    if (!urlEl) return '';
    return urlEl.getAttribute('data-full-url') || urlEl.value;
  }

  if (confirmCancel) {
    confirmCancel.addEventListener('click', closeConfirmModal);
  }
  if (confirmModal) {
    confirmModal.addEventListener('click', function (event) {
      if (event.target === confirmModal) closeConfirmModal();
    });
  }
  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && confirmModal && confirmModal.classList.contains('modal-open')) {
      closeConfirmModal();
    }
  });

  if (confirmBtn) {
    confirmBtn.addEventListener('click', function () {
      if (!form) return;
      setConfirmLoading(true);
      if (confirmError) {
        confirmError.textContent = '';
        confirmError.classList.add('hidden');
      }

      fetch(storeUrl, {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: new FormData(form)
      }).then(function (response) {
        return response.json().then(function (data) {
          return { status: response.status, data: data };
        });
      }).then(function (result) {
        if (result.status === 422 && result.data && result.data.errors) {
          closeConfirmModal();
          showFieldErrors(result.data.errors);
          return;
        }
        if (result.status === 200 && result.data && result.data.success && result.data.payment_link) {
          closeConfirmModal();
          showReadyScreen(result.data.payment_link);
          return;
        }
        if (confirmError) {
          confirmError.textContent = (result.data && result.data.message) || 'Could not generate the link.';
          confirmError.classList.remove('hidden');
        }
      }).catch(function () {
        if (confirmError) {
          confirmError.textContent = 'Could not generate the link. Please try again.';
          confirmError.classList.remove('hidden');
        }
      }).finally(function () {
        setConfirmLoading(false);
      });
    });
  }

  var copyUrlInline = document.getElementById('paymentLinkCopyUrlInline');
  var copyLinkBtn = document.getElementById('paymentLinkCopyLinkBtn');
  var copyMessageBtn = document.getElementById('paymentLinkCopyMessageBtn');
  var copyUrlInlineTimer = null;

  function showInlineCopied() {
    if (!copyUrlInline) return;
    var icon = copyUrlInline.querySelector('[data-copy-icon]');
    var label = copyUrlInline.querySelector('[data-copy-label]');
    copyUrlInline.classList.remove('text-on-surface-variant', 'hover:text-on-surface');
    copyUrlInline.classList.add('text-green-600');
    if (icon) icon.textContent = 'check';
    if (label) label.classList.remove('hidden');
    if (copyUrlInlineTimer) clearTimeout(copyUrlInlineTimer);
    copyUrlInlineTimer = setTimeout(function () {
      copyUrlInline.classList.add('text-on-surface-variant', 'hover:text-on-surface');
      copyUrlInline.classList.remove('text-green-600');
      if (icon) icon.textContent = 'content_copy';
      if (label) label.classList.add('hidden');
    }, 1500);
  }

  if (copyUrlInline) {
    copyUrlInline.addEventListener('click', function () {
      copyText(readyLinkValue(), null, '', showInlineCopied);
    });
  }
  if (copyLinkBtn) {
    copyLinkBtn.addEventListener('click', function () {
      copyText(readyLinkValue(), copyLinkBtn, 'Copy link');
    });
  }
  if (copyMessageBtn) {
    copyMessageBtn.addEventListener('click', function () {
      var messageEl = document.getElementById('paymentLinkReadyMessage');
      copyText(messageEl ? messageEl.value : '', copyMessageBtn, 'Copy message');
    });
  }
})();
</script>
@endsection
