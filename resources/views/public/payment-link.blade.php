<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>{{ $isExpired ? 'Link expired' : $paymentLink->title }} — Bookpay</title>
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <link rel="icon" href="{{ asset('assets/img/favicon/favicon.png') }}">
  <link href="{{ asset('assets/design/css/inkjin_main.css') }}" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            "primary": "#310f7a",
            "primary-container": "#482d91",
            "on-primary": "#ffffff",
            "on-surface": "#1c1b21",
            "on-surface-variant": "#494552",
            "outline-variant": "#cac4d3",
            "surface-container-low": "#f8f1fb",
            "error": "#ba1a1a",
            "error-container": "#ffdad6",
          },
          fontFamily: { sans: ["Plus Jakarta Sans", "sans-serif"] },
        },
      },
    }
  </script>
  <style>
    .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
    #paymentLinkDatePills {
      overflow-x: hidden;
      scrollbar-width: none;
    }
    #paymentLinkDatePills::-webkit-scrollbar { display: none; }
    .card-type-icon { width: 32px; height: 20px; border-radius: 3px; background: #f2ecf5; display: inline-flex; align-items: center; justify-content: center; font-size: 0.6rem; font-weight: 700; color: #494552; }
    .card-type-icon.active { background: #1c1b21; color: white; }
  </style>
</head>
<body class="min-h-screen bg-[#fdf7ff] font-sans flex items-center justify-center p-4">
  @if($isExpired)
    <div class="w-full max-w-md bg-white rounded-2xl border border-outline-variant/30 p-8 shadow-sm text-center flex flex-col items-center">
      <p class="text-sm font-semibold tracking-tight text-on-surface-variant mb-1" style="font-family: 'Space Grotesk', sans-serif;">bookpay</p>
      <div class="flex items-center justify-center w-12 h-12 rounded-2xl bg-error-container/60 text-error mt-4 mb-4">
        <span class="material-symbols-outlined text-3xl">event_busy</span>
      </div>
      <h1 class="text-2xl font-extrabold text-on-surface tracking-tight mb-2">This link has expired</h1>
      <p class="text-sm text-on-surface-variant leading-relaxed max-w-sm">
        This payment link is no longer valid. Ask your artist to send you a new one.
      </p>
    </div>
  @else
    <div class="w-full max-w-md bg-white rounded-2xl border border-outline-variant/30 p-6 sm:p-8 shadow-sm">
      <div id="paymentLinkArtistHeader" class="{{ in_array($checkoutStep ?? 'booking', ['payment', 'booked'], true) ? 'hidden' : '' }}">
      <div class="flex items-center gap-3 mb-6">
        @if(!empty($artistHeader['avatar_url']))
          <img src="{{ $artistHeader['avatar_url'] }}" alt="{{ $artistHeader['name'] }}" class="w-12 h-12 rounded-full object-cover flex-shrink-0">
        @else
          <div class="w-12 h-12 rounded-full bg-[#e7f1ff] text-[#1a4d9e] flex items-center justify-center font-bold text-sm flex-shrink-0">{{ $artistHeader['initials'] }}</div>
        @endif
        <div class="min-w-0">
          <p class="font-bold text-on-surface leading-tight">{{ $artistHeader['name'] }}</p>
          @if($artistHeader['username'] !== '')
            @if($artistHeader['profile_url'])
              <a href="{{ $artistHeader['profile_url'] }}" class="text-sm text-[#1a4d9e] hover:underline">{{ '@'.$artistHeader['username'] }}</a>
            @else
              <p class="text-sm text-[#1a4d9e]">{{ '@'.$artistHeader['username'] }}</p>
            @endif
          @endif
          @if($artistHeader['studio_line'] !== '')
            <p class="text-sm text-on-surface-variant">{{ $artistHeader['studio_line'] }}</p>
          @endif
        </div>
      </div>
      </div>

      <div id="paymentLinkBookingView" class="{{ ($checkoutStep ?? 'booking') === 'booking' ? '' : 'hidden' }}">
      @if($isAutoScheduling)
        <div class="rounded-xl bg-surface-container-low px-4 py-3.5 mb-5">
          <p class="font-bold text-on-surface">{{ $summary['title'] }}</p>
          <p class="text-sm text-on-surface-variant mt-1">
            {{ $summary['amount'] }} · {{ $summary['duration_label'] }} session
            @if($summary['is_deposit'] && $summary['due'])
              · {{ $summary['due'] }} at session
            @endif
          </p>
        </div>

        @if(count($autoDates) > 0)
          <p class="text-sm font-semibold text-on-surface mb-3">Pick your time</p>
          <div class="flex items-center gap-1 mb-3">
            <button type="button" id="paymentLinkDatePrev" class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center text-on-surface-variant hover:bg-surface-container-low disabled:opacity-30 disabled:pointer-events-none" aria-label="Previous dates">
              <span class="material-symbols-outlined text-[20px]">chevron_left</span>
            </button>
            <div id="paymentLinkDatePills" class="flex gap-2 flex-1 min-w-0">
              @foreach($autoDates as $index => $date)
                <button
                  type="button"
                  class="pl-date-pill flex-shrink-0 rounded-full px-4 py-2 text-sm font-semibold border {{ $index === 0 ? 'selected bg-[#e7f1ff] border-transparent text-[#1a4d9e]' : 'bg-white border-outline-variant/40 text-on-surface' }}"
                  data-ymd="{{ $date['ymd'] }}"
                  data-book-label="{{ $date['book_label'] }}"
                  data-times="{{ e(json_encode($date['times'])) }}"
                >{{ $date['label'] }}</button>
              @endforeach
            </div>
            <button type="button" id="paymentLinkDateNext" class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center text-on-surface-variant hover:bg-surface-container-low disabled:opacity-30 disabled:pointer-events-none" aria-label="Next dates">
              <span class="material-symbols-outlined text-[20px]">chevron_right</span>
            </button>
          </div>
          <div id="paymentLinkTimeGrid" class="grid grid-cols-2 gap-2 mb-5"></div>
        @else
          <p class="text-sm text-on-surface-variant mb-5">No open times right now. Check back later or ask the artist for a new link.</p>
        @endif

        <button type="button" id="paymentLinkBookBtn" class="w-full rounded-xl bg-[#1c1b21] px-4 py-4 text-base font-bold text-white disabled:opacity-40" {{ count($autoDates) > 0 ? '' : 'disabled' }}>
          Book
        </button>
      @else
        <div class="rounded-xl bg-surface-container-low px-4 py-4 mb-6">
          <p class="font-bold text-on-surface">{{ $summary['title'] }}</p>
          <p class="text-3xl font-extrabold text-on-surface tracking-tight mt-2">{{ $summary['amount'] }}</p>
          @if($summary['date_line'])
            <p class="text-sm text-on-surface-variant mt-2">{{ $summary['date_line'] }}</p>
          @endif
          @if($summary['is_deposit'] && $summary['total'] && $summary['due'])
            <p class="text-sm text-on-surface-variant mt-3">Total {{ $summary['total'] }} · {{ $summary['due'] }} due at session</p>
          @endif
        </div>
        <button type="button" id="paymentLinkBookBtn" class="w-full rounded-xl bg-[#1c1b21] px-4 py-4 text-base font-bold text-white">
          Book
        </button>
      @endif
      </div>

      <div id="paymentLinkContactView" class="hidden">
        <button type="button" id="paymentLinkContactBack" class="flex items-center gap-1 text-sm text-on-surface-variant hover:text-on-surface mb-4 transition-colors">
          <span class="material-symbols-outlined text-[18px]">arrow_back</span> Back
        </button>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-on-surface tracking-tight mb-6">Almost there</h1>
        <form id="paymentLinkContactForm" novalidate class="space-y-5">
          <div>
            <label for="plName" class="block text-sm text-on-surface-variant mb-2">Name</label>
            <input id="plName" name="name" type="text" autocomplete="name" placeholder="Maria Katsarou" class="w-full rounded-xl border border-outline-variant/40 bg-white px-4 py-3.5 text-base text-on-surface placeholder:text-outline/50 outline-none focus:ring-2 focus:ring-[#1a4d9e]/20 focus:border-[#1a4d9e]">
            <p id="plNameError" class="hidden text-sm text-error mt-1.5"></p>
          </div>
          <div>
            <label for="plEmail" class="block text-sm text-on-surface-variant mb-2">Email</label>
            <input id="plEmail" name="email" type="email" autocomplete="email" placeholder="maria.k@email.com" class="w-full rounded-xl border border-outline-variant/40 bg-white px-4 py-3.5 text-base text-on-surface placeholder:text-outline/50 outline-none focus:ring-2 focus:ring-[#1a4d9e]/20 focus:border-[#1a4d9e]">
            <p id="plEmailError" class="hidden text-sm text-error mt-1.5"></p>
          </div>
          <div>
            <label for="plPhone" class="block text-sm text-on-surface-variant mb-2">Phone · for appointment reminders</label>
            @include('partials.phone-country-input', ['idPrefix' => 'pl'])
          </div>
          <p id="plContactError" class="hidden text-sm text-error"></p>
          <button type="submit" id="plContinueBtn" class="w-full rounded-xl bg-[#1c1b21] px-4 py-4 text-base font-bold text-white disabled:opacity-40">
            Continue
          </button>
        </form>
      </div>

      <div id="paymentLinkOtpView" class="{{ ($checkoutStep ?? '') === 'otp' ? '' : 'hidden' }}">
        <button type="button" id="paymentLinkOtpBack" class="flex items-center gap-1 text-sm text-on-surface-variant hover:text-on-surface mb-4 transition-colors">
          <span class="material-symbols-outlined text-[18px]">arrow_back</span> Back
        </button>
        <div class="text-center mb-6">
          <span class="material-symbols-outlined text-4xl text-[#1a4d9e] mb-2">mark_email_read</span>
          <h1 class="text-2xl font-extrabold text-on-surface tracking-tight mb-2">Verify your email</h1>
          <p class="text-sm text-on-surface-variant">We sent a 4-digit code to <strong id="plOtpEmailLabel"></strong>.</p>
        </div>
        <div class="mb-4">
          <label for="plOtpCode" class="block text-sm text-on-surface-variant mb-2">4-digit code</label>
          <input id="plOtpCode" type="text" maxlength="4" inputmode="numeric" autocomplete="one-time-code" placeholder="1234" class="w-full rounded-xl border border-outline-variant/40 bg-white px-4 py-3.5 text-lg tracking-[0.3em] text-on-surface outline-none focus:ring-2 focus:ring-[#1a4d9e]/20 focus:border-[#1a4d9e]">
          <p id="plOtpError" class="hidden text-sm text-error mt-1.5"></p>
        </div>
        <p id="plOtpStatus" class="hidden text-sm text-green-700 bg-green-50 border border-green-200 rounded-xl px-3 py-2 mb-4"></p>
        <button type="button" id="plVerifyOtpBtn" class="w-full rounded-xl bg-[#1c1b21] px-4 py-4 text-base font-bold text-white disabled:opacity-40 mb-3">
          Verify
        </button>
        <button type="button" id="plResendOtpBtn" class="w-full text-sm font-semibold text-on-surface-variant hover:text-on-surface disabled:opacity-40">
          Resend code
        </button>
      </div>

      <div id="paymentLinkPayView" class="{{ ($checkoutStep ?? '') === 'payment' ? '' : 'hidden' }}">
        <div class="flex items-start justify-between gap-4 mb-5">
          <div class="min-w-0">
            <p class="text-base font-medium text-on-surface-variant leading-snug">{{ $summary['title'] }}</p>
            @if(!empty($summary['date_line']))
              <p class="text-sm text-on-surface-variant mt-1">{{ $summary['date_line'] }}</p>
            @endif
          </div>
          <p class="text-3xl font-extrabold text-on-surface tracking-tight flex-shrink-0">{{ $summary['amount'] }}</p>
        </div>

        <div class="rounded-2xl bg-[#f4eee4] px-4 py-3.5 mb-5">
          <p class="font-bold text-on-surface mb-1">{{ $policyPossessive }} booking policy</p>
          <p class="text-sm text-on-surface-variant leading-relaxed">
            {{ $policyCopy['deposit'] }}
            {{ $policyCopy['cancellation'] }}
            {{ $policyCopy['rescheduling'] }}
            18+ only.
          </p>
        </div>

        @include('partials.checkout-payment-tabs', [
          'showIrisTab' => $showIrisTab ?? false,
          'artistSupportsIris' => $artistSupportsIris ?? false,
        ])

        <div id="panelPayCard">
          <div class="bg-white rounded-2xl border border-outline-variant/20 p-5 mb-4">
            <div class="space-y-4">
              <div>
                <label class="text-xs font-semibold text-on-surface-variant mb-1.5 block">Card Number</label>
                <div class="relative">
                  <div id="stripeCardNumber" class="w-full border border-outline-variant/30 bg-white rounded-xl px-4 py-3 pr-24 text-sm"></div>
                  <div class="absolute right-3 top-1/2 -translate-y-1/2 flex gap-1">
                    <span class="card-type-icon" id="iconVisa">VISA</span>
                    <span class="card-type-icon" id="iconMC">MC</span>
                    <span class="card-type-icon" id="iconAmex">AMEX</span>
                  </div>
                </div>
              </div>
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="text-xs font-semibold text-on-surface-variant mb-1.5 block">Expiry</label>
                  <div id="stripeCardExpiry" class="w-full border border-outline-variant/30 bg-white rounded-xl px-4 py-3 text-sm"></div>
                </div>
                <div>
                  <label class="text-xs font-semibold text-on-surface-variant mb-1.5 block">CVV</label>
                  <div id="stripeCardCvc" class="w-full border border-outline-variant/30 bg-white rounded-xl px-4 py-3 text-sm"></div>
                </div>
              </div>
              <div>
                <label class="text-xs font-semibold text-on-surface-variant mb-1.5 block">Cardholder Name</label>
                <input type="text" id="inputCardName" placeholder="Name on card" value="{{ $verifiedCheckout['name'] ?? '' }}" class="w-full border border-outline-variant/30 bg-white rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30">
              </div>
            </div>
          </div>
        </div>

        @include('partials.checkout-iris-panel', [
          'showIrisTab' => $showIrisTab ?? false,
          'artistSupportsIris' => $artistSupportsIris ?? false,
        ])

        <div id="panelPayCardExtras">
          @if($userDetail)
            @include('partials.artist-cancellation-policy', ['userDetail' => $userDetail])
          @endif
          @include('partials.checkout-policy-agree', ['agreeOnChange' => 'checkPayReady()'])
          <p class="text-sm text-error hidden mb-3" id="formError"></p>
          <button id="btnConfirmPay" type="button" disabled class="w-full py-4 rounded-xl font-bold text-white bg-[#1c1b21] disabled:opacity-40 disabled:cursor-not-allowed hover:opacity-90 transition-all text-base">
            Pay {{ $summary['amount'] }}
          </button>
        </div>
        <p class="text-center text-xs text-on-surface-variant/80 mt-5">Secured by Bookpay</p>
      </div>

      <div id="paymentLinkBookedView" class="{{ ($checkoutStep ?? '') === 'booked' ? '' : 'hidden' }}">
        <div class="flex items-center gap-3 mb-6">
          <div class="flex items-center justify-center w-8 h-8 rounded-full bg-[#e6f4ea] flex-shrink-0">
            <span class="material-symbols-outlined text-[18px] text-[#1b7f3a]" style="font-variation-settings: 'FILL' 1, 'wght' 600, 'GRAD' 0, 'opsz' 24;">check</span>
          </div>
          <h1 class="text-xl sm:text-[22px] font-bold text-on-surface tracking-tight">
            @if(!empty($clientFirstName))
              You're booked, {{ $clientFirstName }}
            @else
              You're booked
            @endif
          </h1>
        </div>

        <div class="rounded-2xl bg-[#f4efe8] px-5 py-4 mb-6">
          <div class="flex items-center justify-between gap-4 py-1.5">
            <span class="text-sm text-on-surface-variant">Paid</span>
            <span class="text-sm font-bold text-on-surface">{{ $summary['amount'] }}</span>
          </div>
          @if(!empty($summary['session_line']) || !empty($summary['date_line']))
            <div class="flex items-center justify-between gap-4 py-1.5">
              <span class="text-sm text-on-surface-variant">Session</span>
              <span class="text-sm font-medium text-on-surface text-right">{{ $summary['session_line'] ?: $summary['date_line'] }}</span>
            </div>
          @endif
          <div class="flex items-center justify-between gap-4 py-1.5">
            <span class="text-sm text-on-surface-variant">Artist</span>
            <span class="text-sm font-medium text-on-surface text-right">{{ $artistHeader['name'] }}</span>
          </div>
          @if(!empty($summary['is_deposit']) && !empty($summary['due']))
            <div class="flex items-center justify-between gap-4 py-1.5">
              <span class="text-sm text-on-surface-variant">Due at session</span>
              <span class="text-sm font-medium text-on-surface">{{ $summary['due'] }}</span>
            </div>
          @endif
        </div>

        <p class="text-sm text-on-surface-variant leading-relaxed">
          A few quick questions about your session are on their way to your email.
        </p>
      </div>
    </div>
  @endif

  @if(!$isExpired && $isAutoScheduling && count($autoDates) > 0)
  <script>
  (function () {
    var dates = @json($autoDates);
    var pills = document.querySelectorAll('.pl-date-pill');
    var datePills = document.getElementById('paymentLinkDatePills');
    var datePrev = document.getElementById('paymentLinkDatePrev');
    var dateNext = document.getElementById('paymentLinkDateNext');
    var timeGrid = document.getElementById('paymentLinkTimeGrid');
    var bookBtn = document.getElementById('paymentLinkBookBtn');
    var selectedYmd = dates[0] ? dates[0].ymd : null;
    var selectedTime = null;
    var showingAllTimes = false;

    function updateDateArrows() {
      if (!datePills) return;
      var maxScroll = datePills.scrollWidth - datePills.clientWidth;
      var canScroll = maxScroll > 2;
      if (datePrev) datePrev.disabled = !canScroll || datePills.scrollLeft <= 2;
      if (dateNext) dateNext.disabled = !canScroll || datePills.scrollLeft >= maxScroll - 2;
    }

    function scrollDates(direction) {
      if (!datePills) return;
      datePills.scrollBy({ left: direction * Math.max(140, datePills.clientWidth * 0.7), behavior: 'smooth' });
    }

    function selectedDate() {
      return dates.find(function (d) { return d.ymd === selectedYmd; }) || dates[0];
    }

    function setDateSelected(ymd) {
      selectedYmd = ymd;
      showingAllTimes = false;
      pills.forEach(function (pill) {
        var on = pill.getAttribute('data-ymd') === ymd;
        pill.classList.toggle('selected', on);
        pill.classList.toggle('bg-[#e7f1ff]', on);
        pill.classList.toggle('border-transparent', on);
        pill.classList.toggle('text-[#1a4d9e]', on);
        pill.classList.toggle('bg-white', !on);
        pill.classList.toggle('border-outline-variant/40', !on);
        pill.classList.toggle('text-on-surface', !on);
      });
    }

    function renderTimes() {
      var date = selectedDate();
      if (!date || !timeGrid) return;
      var times = date.times || [];
      var visible = showingAllTimes ? times : times.slice(0, 3);
      var html = '';
      visible.forEach(function (time) {
        var on = time === selectedTime;
        html += '<button type="button" class="pl-time-btn rounded-xl px-4 py-3 text-sm font-semibold border ' +
          (on ? 'selected bg-[#1c1b21] border-transparent text-white' : 'bg-white border-outline-variant/40 text-on-surface') +
          '" data-time="' + time + '">' + time + '</button>';
      });
      if (!showingAllTimes && times.length > 3) {
        html += '<button type="button" id="paymentLinkMoreTimes" class="rounded-xl px-4 py-3 text-sm font-semibold border bg-white border-outline-variant/40 text-on-surface-variant">More times</button>';
      }
      timeGrid.innerHTML = html;
    }

    function updateBookLabel() {
      if (!bookBtn) return;
      var date = selectedDate();
      if (!date || !selectedTime) {
        bookBtn.textContent = 'Book';
        bookBtn.disabled = true;
        return;
      }
      bookBtn.disabled = false;
      bookBtn.textContent = 'Book ' + date.book_label + ' · ' + selectedTime;
    }

    function selectTime(time) {
      selectedTime = time;
      renderTimes();
      updateBookLabel();
    }

    window.paymentLinkSelectedSlot = function () {
      return { ymd: selectedYmd || '', time: selectedTime || '' };
    };

    pills.forEach(function (pill) {
      pill.addEventListener('click', function () {
        setDateSelected(pill.getAttribute('data-ymd'));
        var date = selectedDate();
        selectTime(date && date.times && date.times[0] ? date.times[0] : null);
      });
    });

    if (timeGrid) {
      timeGrid.addEventListener('click', function (event) {
        var more = event.target.closest('#paymentLinkMoreTimes');
        if (more) {
          showingAllTimes = true;
          renderTimes();
          return;
        }
        var btn = event.target.closest('.pl-time-btn');
        if (btn) selectTime(btn.getAttribute('data-time'));
      });
    }

    if (dates[0] && dates[0].times && dates[0].times[0]) {
      setDateSelected(dates[0].ymd);
      selectTime(dates[0].times[0]);
    } else {
      renderTimes();
      updateBookLabel();
    }

    if (datePrev) datePrev.addEventListener('click', function () { scrollDates(-1); });
    if (dateNext) dateNext.addEventListener('click', function () { scrollDates(1); });
    if (datePills) {
      datePills.addEventListener('scroll', updateDateArrows);
      window.addEventListener('resize', updateDateArrows);
      updateDateArrows();
    }
  })();
  </script>
  @endif

  @if(!$isExpired)
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
  @include('partials.phone-country-scripts')
  <script>
  (function () {
    var csrfToken = @json(csrf_token());
    var sendOtpUrl = @json(route('public.payment-link.otp.send', ['code' => $paymentLink->code]));
    var verifyOtpUrl = @json(route('public.payment-link.otp.verify', ['code' => $paymentLink->code]));
    var emailCheckUrl = @json(route('public.email.availability'));
    var pendingOtp = @json($pendingOtp ?? null);
    var bookingView = document.getElementById('paymentLinkBookingView');
    var contactView = document.getElementById('paymentLinkContactView');
    var otpView = document.getElementById('paymentLinkOtpView');
    var payView = document.getElementById('paymentLinkPayView');
    var bookedView = document.getElementById('paymentLinkBookedView');
    var artistHeader = document.getElementById('paymentLinkArtistHeader');
    var checkoutStep = @json($checkoutStep ?? 'booking');
    var bookBtn = document.getElementById('paymentLinkBookBtn');
    var contactForm = document.getElementById('paymentLinkContactForm');
    var continueBtn = document.getElementById('plContinueBtn');
    var nameInput = document.getElementById('plName');
    var emailInput = document.getElementById('plEmail');
    var phoneInput = document.getElementById('plPhone');
    var otpCodeInput = document.getElementById('plOtpCode');
    var otpEmailLabel = document.getElementById('plOtpEmailLabel');
    var otpStatus = document.getElementById('plOtpStatus');
    var verifyBtn = document.getElementById('plVerifyOtpBtn');
    var resendBtn = document.getElementById('plResendOtpBtn');
    var contact = { name: '', email: '', phone: '' };
    var resendRemaining = 0;
    var resendTimer = null;

    function showView(name) {
      if (bookingView) bookingView.classList.toggle('hidden', name !== 'booking');
      if (contactView) contactView.classList.toggle('hidden', name !== 'contact');
      if (otpView) otpView.classList.toggle('hidden', name !== 'otp');
      if (payView) payView.classList.toggle('hidden', name !== 'payment');
      if (bookedView) bookedView.classList.toggle('hidden', name !== 'booked');
      if (artistHeader) artistHeader.classList.toggle('hidden', name === 'payment' || name === 'booked');
      if (name === 'payment' && typeof window.initPaymentLinkCheckout === 'function') {
        window.initPaymentLinkCheckout();
      }
    }

    function setError(field, message) {
      var input = document.getElementById(field);
      var errorEl = document.getElementById(field + 'Error');
      if (input) input.classList.toggle('border-error', !!message);
      if (errorEl) {
        errorEl.textContent = message || '';
        errorEl.classList.toggle('hidden', !message);
      }
    }

    function clearContactErrors() {
      ['plName', 'plEmail', 'plPhone'].forEach(function (field) { setError(field, ''); });
      var general = document.getElementById('plContactError');
      if (general) {
        general.textContent = '';
        general.classList.add('hidden');
      }
    }

    function setContactError(message) {
      var general = document.getElementById('plContactError');
      if (!general) return;
      general.textContent = message || '';
      general.classList.toggle('hidden', !message);
    }

    function setOtpError(message) {
      var errorEl = document.getElementById('plOtpError');
      if (otpCodeInput) otpCodeInput.classList.toggle('border-error', !!message);
      if (errorEl) {
        errorEl.textContent = message || '';
        errorEl.classList.toggle('hidden', !message);
      }
    }

    function isValidEmail(email) {
      return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(email || '').trim());
    }

    function validateBookingEmailRole(email) {
      return fetch(emailCheckUrl + '?email=' + encodeURIComponent(email), {
        method: 'GET',
        headers: { 'Accept': 'application/json' }
      }).then(function (res) {
        if (!res.ok) {
          throw new Error('Unable to validate email right now. Please try again.');
        }
        return res.json();
      }).then(function (data) {
        if (typeof data.allowed === 'boolean') return data.allowed;
        if (!data.exists) return true;
        return !!data.is_user;
      });
    }

    function validateContact() {
      clearContactErrors();
      var name = (nameInput.value || '').trim();
      var email = (emailInput.value || '').trim();
      var nationalVal = (phoneInput && phoneInput.value || '').trim();
      var countryEl = document.getElementById('plPhoneCountry');
      var phone = contactPhone();
      var ok = true;
      if (!name) {
        setError('plName', 'Please enter your name.');
        ok = false;
      }
      if (!email) {
        setError('plEmail', 'This field is required.');
        ok = false;
      } else if (!isValidEmail(email)) {
        setError('plEmail', 'Please enter a valid email address.');
        ok = false;
      }
      if (!nationalVal) {
        setError('plPhone', 'This field is required.');
        ok = false;
      } else if (!countryEl || !countryEl.value) {
        setError('plPhone', 'Please select a country code.');
        ok = false;
      } else if (!isValidPhoneWithCountryCode(phone)) {
        setError('plPhone', 'Enter a valid phone number for the selected country.');
        ok = false;
      }
      if (!ok) {
        return Promise.resolve(false);
      }

      return validateBookingEmailRole(email).then(function (allowed) {
        if (!allowed) {
          setError('plEmail', 'Please use another email.');
          return false;
        }
        return true;
      }).catch(function (err) {
        setError('plEmail', (err && err.message) || 'Unable to validate email right now. Please try again.');
        return false;
      });
    }

    function isValidPhoneWithCountryCode(phone) {
      if (window.InkjinPhoneCountry && window.InkjinPhoneCountry.isValidFullPhone('pl')) {
        return true;
      }
      return /^\+[1-9]\d{7,14}$/.test(String(phone || '').trim());
    }

    function contactPhone() {
      return (window.InkjinPhoneCountry && window.InkjinPhoneCountry.getFullPhone('pl')) || '';
    }

    function formatMmSs(seconds) {
      var s = Math.max(0, parseInt(seconds || 0, 10) || 0);
      var mm = String(Math.floor(s / 60)).padStart(2, '0');
      var ss = String(s % 60).padStart(2, '0');
      return mm + ':' + ss;
    }

    function applyResendUi() {
      if (!resendBtn) return;
      if (resendRemaining > 0) {
        resendBtn.disabled = true;
        resendBtn.textContent = 'Resend in ' + formatMmSs(resendRemaining);
      } else {
        resendBtn.disabled = false;
        resendBtn.textContent = 'Resend code';
      }
    }

    function startResendCountdown(seconds) {
      resendRemaining = Math.max(0, parseInt(seconds || 0, 10) || 0);
      if (resendTimer) {
        clearInterval(resendTimer);
        resendTimer = null;
      }
      applyResendUi();
      if (resendRemaining <= 0) return;
      resendTimer = setInterval(function () {
        resendRemaining = Math.max(0, resendRemaining - 1);
        applyResendUi();
        if (resendRemaining <= 0 && resendTimer) {
          clearInterval(resendTimer);
          resendTimer = null;
        }
      }, 1000);
    }

    function selectedSlot() {
      if (typeof window.paymentLinkSelectedSlot === 'function') {
        return window.paymentLinkSelectedSlot() || {};
      }
      return {};
    }

    function sendOtp() {
      var slot = selectedSlot();
      return fetch(sendOtpUrl, {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken,
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
          name: contact.name,
          email: contact.email,
          phone: contact.phone,
          slot_ymd: slot.ymd || null,
          slot_time: slot.time || null
        })
      }).then(function (response) {
        return response.json().then(function (data) {
          return { status: response.status, data: data };
        });
      });
    }

    if (bookBtn) {
      bookBtn.addEventListener('click', function () {
        if (bookBtn.disabled) return;
        showView('contact');
      });
    }

    var contactBack = document.getElementById('paymentLinkContactBack');
    if (contactBack) {
      contactBack.addEventListener('click', function () { showView('booking'); });
    }
    var otpBack = document.getElementById('paymentLinkOtpBack');
    if (otpBack) {
      otpBack.addEventListener('click', function () { showView('contact'); });
    }

    ['plName', 'plEmail', 'plPhone'].forEach(function (field) {
      var input = document.getElementById(field);
      if (!input) return;
      input.addEventListener('input', function () { setError(field, ''); });
    });
    var phoneCountry = document.getElementById('plPhoneCountry');
    if (phoneCountry) {
      phoneCountry.addEventListener('change', function () { setError('plPhone', ''); });
    }

    if (contactForm) {
      contactForm.addEventListener('submit', function (event) {
        event.preventDefault();
        if (continueBtn) {
          continueBtn.disabled = true;
          continueBtn.textContent = 'Checking...';
        }
        validateContact().then(function (ok) {
          if (!ok) {
            if (continueBtn) {
              continueBtn.disabled = false;
              continueBtn.textContent = 'Continue';
            }
            return;
          }
          contact = {
            name: (nameInput.value || '').trim(),
            email: (emailInput.value || '').trim(),
            phone: contactPhone()
          };
          if (continueBtn) continueBtn.textContent = 'Sending...';
          setContactError('');
          return sendOtp().then(function (result) {
          if (result.status === 429 || (result.data && result.data.sent === false)) {
            if (result.data && result.data.resend_available_in_seconds) {
              startResendCountdown(result.data.resend_available_in_seconds);
            }
            setContactError((result.data && result.data.message) || 'Please wait before requesting another code.');
            return;
          }
          if (result.status !== 200 || !result.data || !result.data.sent) {
            var message = (result.data && result.data.message) || 'Could not send verification code.';
            if (result.data && result.data.errors && result.data.errors.email) {
              setError('plEmail', result.data.errors.email[0]);
              return;
            }
            setContactError(message);
            return;
          }
          if (otpEmailLabel) otpEmailLabel.textContent = contact.email;
          if (otpStatus) {
            otpStatus.textContent = '4-digit code sent to ' + contact.email + '.';
            otpStatus.classList.remove('hidden');
          }
          setOtpError('');
          if (otpCodeInput) otpCodeInput.value = '';
          startResendCountdown(result.data.resend_available_in_seconds || 60);
          showView('otp');
        }).catch(function () {
          setContactError('Could not send verification code. Please try again.');
        }).finally(function () {
          if (continueBtn) {
            continueBtn.disabled = false;
            continueBtn.textContent = 'Continue';
          }
        });
        });
      });
    }

    if (resendBtn) {
      resendBtn.addEventListener('click', function () {
        if (resendRemaining > 0) return;
        resendBtn.disabled = true;
        resendBtn.textContent = 'Sending...';
        setOtpError('');
        sendOtp().then(function (result) {
          if (result.status === 200 && result.data && result.data.sent) {
            if (otpStatus) {
              otpStatus.textContent = '4-digit code sent to ' + contact.email + '.';
              otpStatus.classList.remove('hidden');
            }
            startResendCountdown(result.data.resend_available_in_seconds || 60);
            return;
          }
          if (result.data && result.data.resend_available_in_seconds) {
            startResendCountdown(result.data.resend_available_in_seconds);
          }
          setOtpError((result.data && result.data.message) || 'Could not send verification code.');
          applyResendUi();
        }).catch(function () {
          setOtpError('Could not send verification code. Please try again.');
          applyResendUi();
        });
      });
    }

    if (verifyBtn) {
      verifyBtn.addEventListener('click', function () {
        var code = (otpCodeInput && otpCodeInput.value || '').trim();
        setOtpError('');
        if (!/^\d{4}$/.test(code)) {
          setOtpError('Please enter a valid 4-digit code.');
          return;
        }
        verifyBtn.disabled = true;
        verifyBtn.textContent = 'Verifying...';
        fetch(verifyOtpUrl, {
          method: 'POST',
          headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: JSON.stringify({
            email: contact.email,
            code: code,
            name: contact.name
          })
        }).then(function (response) {
          return response.json().then(function (data) {
            return { status: response.status, data: data };
          });
        }).then(function (result) {
          if (result.status !== 200 || !result.data || !result.data.verified) {
            setOtpError((result.data && result.data.message) || 'Invalid verification code.');
            return;
          }
          if (otpStatus) {
            otpStatus.textContent = 'Email verified.';
            otpStatus.classList.remove('hidden');
          }
          verifyBtn.textContent = 'Verified';
          var nameField = document.getElementById('inputCardName');
          if (nameField && contact.name && !String(nameField.value || '').trim()) {
            nameField.value = contact.name;
          }
          showView('payment');
        }).catch(function () {
          setOtpError('Could not verify the code. Please try again.');
        }).finally(function () {
          if (verifyBtn.textContent !== 'Verified') {
            verifyBtn.disabled = false;
            verifyBtn.textContent = 'Verify';
          }
        });
      });
    }

    function restorePendingOtp() {
      if (checkoutStep === 'payment') {
        showView('payment');
        return;
      }
      if (checkoutStep === 'booked') {
        showView('booked');
        return;
      }
      if (!pendingOtp || !pendingOtp.email) return;
      contact = {
        name: pendingOtp.name || '',
        email: pendingOtp.email || '',
        phone: pendingOtp.phone || ''
      };
      if (nameInput) nameInput.value = contact.name;
      if (emailInput) emailInput.value = contact.email;
      if (window.InkjinPhoneCountry && contact.phone) {
        window.InkjinPhoneCountry.setFullPhone('pl', contact.phone);
      } else if (phoneInput && contact.phone) {
        phoneInput.value = contact.phone;
      }
      if (otpEmailLabel) otpEmailLabel.textContent = contact.email;
      if (otpStatus) {
        otpStatus.textContent = '4-digit code sent to ' + contact.email + '.';
        otpStatus.classList.remove('hidden');
      }
      if (otpCodeInput) otpCodeInput.value = '';
      if (pendingOtp.resend_available_in_seconds) {
        startResendCountdown(pendingOtp.resend_available_in_seconds);
      }
      showView('otp');
    }

    if (window.jQuery) {
      window.jQuery(restorePendingOtp);
    } else {
      restorePendingOtp();
    }
  })();
  </script>
  @if(empty($isPaid))
  <script src="https://js.stripe.com/v3/"></script>
  @include('partials.checkout-stripe-wallets')
  <script>
  (function () {
    var csrfToken = @json(csrf_token());
    var stripePublishableKey = @json($stripePublishableKey ?? '');
    var payAmountLabel = @json($summary['amount'] ?? '');
    var amountCents = {{ (int) round(((float) $paymentLink->amount) * 100) }};
    var intentUrl = @json(route('public.payment-link.payment-intent', ['code' => $paymentLink->code]));
    var confirmUrl = @json(route('public.payment-link.payment.confirm', ['code' => $paymentLink->code]));
    var stripe = null;
    var stripeElements = null;
    var stripeCardNumber = null;
    var stripeCardExpiry = null;
    var stripeCardCvc = null;
    var isStripeMounted = false;
    var stripeCardComplete = { number: false, expiry: false, cvc: false };
    var checkoutReady = false;

    window.vivaCsrfToken = csrfToken;
    window.vivaOrderUrl = @json(route('public.payment-link.viva.order', ['code' => $paymentLink->code]));
    window.vivaStatusUrl = @json(route('public.payment-link.viva.status', ['code' => $paymentLink->code]));
    window.vivaOrderBody = function () { return {}; };
    window.vivaStatusExtraQuery = function () { return ''; };
    window.vivaOnPaymentPaid = function () {
      window.location.reload();
    };

    function setFormError(message) {
      var el = document.getElementById('formError');
      if (!el) return;
      el.textContent = message || '';
      el.classList.toggle('hidden', !message);
    }

    function highlightCardBrand(brand) {
      ['iconVisa', 'iconMC', 'iconAmex'].forEach(function (id) {
        var el = document.getElementById(id);
        if (el) el.classList.remove('active');
      });
      if (brand === 'visa') document.getElementById('iconVisa')?.classList.add('active');
      if (brand === 'mastercard') document.getElementById('iconMC')?.classList.add('active');
      if (brand === 'amex') document.getElementById('iconAmex')?.classList.add('active');
    }

    function isPaymentCardReady() {
      return !!(stripeCardComplete.number && stripeCardComplete.expiry && stripeCardComplete.cvc);
    }

    window.checkPayReady = function () {
      var agreed = !!document.getElementById('agreePolicy')?.checked;
      var hasCardName = String(document.getElementById('inputCardName')?.value || '').trim().length > 1;
      var ready = agreed && hasCardName && isPaymentCardReady();
      var btn = document.getElementById('btnConfirmPay');
      if (btn) btn.disabled = !ready;
    };

    window.toggleCancellationPolicy = function () {
      var content = document.getElementById('cancellationPolicyContent');
      var arrow = document.getElementById('cancPolicyArrow');
      if (!content) return;
      var isOpen = !content.classList.contains('hidden');
      content.classList.toggle('hidden', isOpen);
      if (arrow) arrow.style.transform = isOpen ? 'rotate(0deg)' : 'rotate(180deg)';
    };

    window.expandCancellationPolicy = function () {
      var content = document.getElementById('cancellationPolicyContent');
      if (content && content.classList.contains('hidden')) {
        window.toggleCancellationPolicy();
      }
      document.getElementById('cancellationPolicySection')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    };

    function mountStripeElements() {
      if (!stripePublishableKey || isStripeMounted || typeof Stripe === 'undefined') return;
      stripe = Stripe(stripePublishableKey);
      stripeElements = stripe.elements();
      var baseStyle = {
        base: {
          color: '#1c1b21',
          fontFamily: 'Plus Jakarta Sans, system-ui, sans-serif',
          fontSize: '14px',
          '::placeholder': { color: '#7a7583' }
        },
        invalid: { color: '#ba1a1a' }
      };
      stripeCardNumber = stripeElements.create('cardNumber', { style: baseStyle });
      stripeCardExpiry = stripeElements.create('cardExpiry', { style: baseStyle });
      stripeCardCvc = stripeElements.create('cardCvc', { style: baseStyle });
      stripeCardNumber.mount('#stripeCardNumber');
      stripeCardExpiry.mount('#stripeCardExpiry');
      stripeCardCvc.mount('#stripeCardCvc');
      isStripeMounted = true;

      stripeCardNumber.on('change', function (event) {
        stripeCardComplete.number = !!event.complete;
        highlightCardBrand(event.brand || '');
        setFormError(event.error ? event.error.message : '');
        window.checkPayReady();
      });
      stripeCardExpiry.on('change', function (event) {
        stripeCardComplete.expiry = !!event.complete;
        setFormError(event.error ? event.error.message : '');
        window.checkPayReady();
      });
      stripeCardCvc.on('change', function (event) {
        stripeCardComplete.cvc = !!event.complete;
        setFormError(event.error ? event.error.message : '');
        window.checkPayReady();
      });
    }

    async function createPaymentIntent(cardholderName) {
      var response = await fetch(intentUrl, {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken,
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ cardholder_name: cardholderName })
      });
      var data = await response.json();
      if (!response.ok || !data || !data.client_secret) {
        throw new Error((data && data.message) || 'Unable to initialize payment.');
      }
      return data;
    }

    async function persistBooking(paymentIntentId, paymentMethod) {
      var response = await fetch(confirmUrl, {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken,
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
          payment_intent_id: paymentIntentId,
          payment_method: paymentMethod || 'card'
        })
      });
      var data = await response.json();
      if (!response.ok || !data || !data.saved) {
        throw new Error((data && data.message) || 'Payment succeeded but booking could not be saved.');
      }
      return data;
    }

    async function payWithCard() {
      setFormError('');
      if (!document.getElementById('agreePolicy')?.checked) {
        setFormError('Please accept the cancellation policy and terms.');
        return;
      }
      var cardholderName = String(document.getElementById('inputCardName')?.value || '').trim();
      if (!cardholderName) {
        setFormError('Please enter cardholder name.');
        return;
      }
      if (!isPaymentCardReady()) {
        setFormError('Please complete card details.');
        return;
      }
      var btn = document.getElementById('btnConfirmPay');
      if (btn) {
        btn.disabled = true;
        btn.textContent = 'Processing...';
      }
      try {
        var intent = await createPaymentIntent(cardholderName);
        var confirmResult = await stripe.confirmCardPayment(intent.client_secret, {
          payment_method: {
            card: stripeCardNumber,
            billing_details: { name: cardholderName }
          }
        });
        if (confirmResult.error) {
          throw new Error(confirmResult.error.message || 'Payment failed.');
        }
        if (!confirmResult.paymentIntent || confirmResult.paymentIntent.status !== 'succeeded') {
          throw new Error('Payment was not completed. Please try again.');
        }
        await persistBooking(confirmResult.paymentIntent.id, 'card');
        window.location.reload();
      } catch (error) {
        setFormError(error.message || 'Payment failed. Please try again.');
        if (btn) {
          btn.disabled = false;
          btn.textContent = 'Pay ' + payAmountLabel;
        }
        window.checkPayReady();
      }
    }

    window.checkoutStripeWalletConfig = {
      country: @json(strtoupper($userDetail?->payout_bank_country ?? 'GR')),
      currency: 'eur',
      label: @json($summary['title'] ?? 'Bookpay'),
      getAmountCents: function () { return amountCents; },
      isPolicyAccepted: function () {
        return !!document.getElementById('agreePolicy')?.checked;
      },
      getClientSecret: async function (ev) {
        var cardholderName = (ev && ev.payerName)
          ? String(ev.payerName).trim()
          : String(document.getElementById('inputCardName')?.value || '').trim() || 'Wallet';
        var data = await createPaymentIntent(cardholderName);
        return data.client_secret;
      },
      onSuccess: async function (paymentIntentId, ev) {
        var method = 'card';
        try {
          var wallet = ev && ev.walletName ? String(ev.walletName).toLowerCase() : '';
          if (wallet.indexOf('apple') !== -1) method = 'apple_pay';
          else if (wallet.indexOf('google') !== -1) method = 'google_pay';
        } catch (e) {}
        try {
          await persistBooking(paymentIntentId, method);
          window.location.reload();
        } catch (error) {
          setFormError(error.message || 'Payment succeeded but booking could not be saved.');
        }
      },
      onError: function (error) {
        setFormError(error.message || 'Wallet payment failed.');
      }
    };

    window.initPaymentLinkCheckout = function () {
      if (checkoutReady) return;
      checkoutReady = true;
      mountStripeElements();
      if (stripe && stripeElements && typeof window.checkoutInitStripeWallets === 'function') {
        window.checkoutInitStripeWallets(stripe, stripeElements);
      }
      document.getElementById('inputCardName')?.addEventListener('input', window.checkPayReady);
      document.getElementById('btnConfirmPay')?.addEventListener('click', payWithCard);
      window.checkPayReady();
    };

    if (@json(($checkoutStep ?? '') === 'payment')) {
      window.initPaymentLinkCheckout();
    }
  })();
  </script>
  @include('partials.checkout-payment-tabs-script')
  @endif
  @endif
</body>
</html>
