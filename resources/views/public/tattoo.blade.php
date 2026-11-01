<!DOCTYPE html>
<html lang="en">
<head>
  @include('layouts.partials.google-analytics')
  <meta charset="utf-8">
  <meta name="color-scheme" content="only light">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ $tattoo->title }} · {{ $userDetail->publicDisplayName() }}</title>
  <meta name="description" content="View tattoo design details and book your session with {{ $userDetail->publicDisplayName() }} on Inkjin.">
  <link rel="icon" href="{{ asset('design/images/icons/favicon.png') }}">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@700&display=swap" rel="stylesheet">
  @php
    $canBookOnline = $canBookOnline ?? true;
    $bookingUnavailableMessage = $bookingUnavailableMessage ?? 'This artist is not currently accepting online bookings. Please check back later.';
    $isSoldOut = $isSoldOut ?? $tattoo->isSoldOut();
    $isAvailable = ! $isSoldOut && $canBookOnline;

    $lookState = \App\Support\PageLook::stateFromUserDetail($userDetail);
    [$schemePrimary, $schemeMid, $schemeTint] = \App\Support\PageLook::schemeParts(
        (string) ($lookState['scheme'] ?? ''),
        '#3E007C,#5A238E,#F5ECFB'
    );

    $currencyCode = strtoupper(trim((string) ($userDetail->currency ?? 'EUR')));
    $currencySymbol = match ($currencyCode) {
        'EUR' => '€',
        'GBP' => '£',
        'USD', 'CAD', 'AUD', 'NZD', 'SGD' => '$',
        default => $currencyCode !== '' ? $currencyCode.' ' : '€',
    };
    $formatMoney = static function ($amount) use ($currencySymbol): string {
        $number = rtrim(rtrim(number_format((float) $amount, 2, '.', ''), '0'), '.');

        return $currencySymbol.$number;
    };
    $priceRange = $formatMoney($tattoo->min_price).' – '.$formatMoney($tattoo->max_price);

    $depositType = strtolower((string) ($userDetail->minimum_deposit_type ?? 'percentage'));
    $depositAmountRaw = (float) ($userDetail->minimum_deposit_amount ?? 30);
    $depositAmountLabel = rtrim(rtrim(number_format(max(0, $depositAmountRaw), 2, '.', ''), '0'), '.');
    $depositLabel = $depositType === 'amount'
        ? $currencySymbol.$depositAmountLabel
        : $depositAmountLabel.'%';

    $cwRaw = strtolower(trim((string) ($userDetail->cancellation_window ?? '48h')));
    $cancelWindowHuman = match (true) {
        str_contains($cwRaw, '2w') || $cwRaw === '2w' => '2 weeks',
        str_contains($cwRaw, '1w') || $cwRaw === '1w' || str_contains($cwRaw, 'w') => '1 week',
        str_contains($cwRaw, '72') => '72 hours',
        str_contains($cwRaw, '48') => '48 hours',
        str_contains($cwRaw, '24') => '24 hours',
        str_contains($cwRaw, '12') => '12 hours',
        default => '48 hours',
    };
    $reschedulePolicy = strtolower((string) ($userDetail->reschedule_times ?? 'never'));
    $rescheduleBullet = match ($reschedulePolicy) {
        'once' => 'You can reschedule your appointment once',
        'twice' => 'You can reschedule your appointment twice',
        'unlimited' => 'You can reschedule your appointment an unlimited number of times',
        default => 'Rescheduling is not allowed',
    };

    $styleLabel = ucwords(str_replace(['-', '_'], ' ', (string) ($tattoo->primary_style ?? '')));
    $colorLabel = ucfirst((string) ($tattoo->color ?? ''));
    $sizeLabel = $tattoo->sizeLabel($userDetail->size_unit ?? null);
    $hoursLabel = ((float) ($tattoo->session_duration ?? 0)).' hour(s)';
    $minSessions = (int) ($tattoo->min_sessions ?? 1);
    $maxSessions = (int) ($tattoo->max_sessions ?? $minSessions);
    $sessionsLabel = $minSessions === $maxSessions
        ? (string) $minSessions
        : $minSessions.' to '.$maxSessions;
    $placementLabel = $tattoo->suggested_placement ?: 'Anywhere';
    $aboutHtml = trim((string) ($tattoo->description ?? ''));
    $designWhatsIncludedItems = $userDetail->activeDesignWhatsIncludedItems();
    $claimed = $tattoo->claimedBookingCount();
    $limit = $tattoo->effectiveRepeatLimit();
    $remaining = $tattoo->remainingBookingSlots();
    $artistName = $userDetail->publicDisplayName();
    $artistInitials = $userDetail->publicDisplayInitials();
    $artistUrl = route('public.artist', ['username' => $userDetail->user_name]);
    $guestSpotId = $guestSpotId ?? null;
    $bookUrl = route('public.tattoo.book', array_filter([
        'user_name' => $userDetail->user_name,
        'tattoo_slug' => $tattoo->slug,
        'guest_spot' => $guestSpotId ?: null,
    ]));
    $imageUrl = asset($tattoo->image);
  @endphp
  <style>
    :root{
      --bg:#FFF6FF;--card:#fff;--ink:#1D1A21;--muted:#6E6475;--faint:#9C92A3;
      --line:#EFE3F2;--line2:#DCCFE3;--p:{{ $schemePrimary }};--p2:{{ $schemeMid }};
      --pl:{{ $schemeTint }};--pll:#FBF5FD
    }
    *{box-sizing:border-box}
    html,body{margin:0;min-height:100%}
    html{height:100%}
    body{background:var(--bg);color:var(--ink);font-family:'Plus Jakarta Sans',system-ui,-apple-system,sans-serif;font-size:14px;line-height:1.5;-webkit-font-smoothing:antialiased;min-height:100vh;display:flex;flex-direction:column}
    button,input,select,textarea{font:inherit;color:inherit}
    button{cursor:pointer;border:0;background:none;padding:0}
    a{color:inherit;text-decoration:none}
    .ms{font-family:'Material Symbols Outlined';font-weight:normal;font-style:normal;font-size:18px;line-height:1;display:inline-block;vertical-align:middle;white-space:nowrap;font-variation-settings:'FILL' 0,'wght' 400,'GRAD' 0,'opsz' 24}
    .top{background:#fff;border-bottom:1px solid var(--line);position:sticky;top:0;z-index:4}
    .top .in{max-width:640px;margin:0 auto;padding:12px 16px;display:flex;align-items:center;justify-content:space-between;gap:12px}
    .logo{display:flex;align-items:center;gap:6px;font-weight:800;font-size:17px;color:var(--p);letter-spacing:-.055em;font-family:'Space Grotesk',system-ui,sans-serif}
    .logo i{width:22px;height:22px;border-radius:50%;background:var(--p);color:#fff;font-style:normal;font-size:11px;display:flex;align-items:center;justify-content:center;letter-spacing:0;font-family:'Plus Jakarta Sans',system-ui,sans-serif;font-weight:700}
    .back{font-size:12.5px;display:inline-flex;align-items:center;gap:4px;color:var(--ink)}
    .back .ms{font-size:16px}
    .prog{height:2px;background:var(--p);width:0}
    .wrap{max-width:640px;margin:0 auto;padding:22px 16px;width:100%}
    .wrap.wide{max-width:860px}
    main.wrap{flex:1 0 auto}
    .btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;background:var(--p);color:#fff;border-radius:999px;padding:10px 20px;font-weight:700;font-size:13.5px}
    .btn:hover{background:var(--p2)}
    .btn.full{width:100%;border-radius:10px;padding:12px}
    .btn:disabled,.btn[aria-disabled=true]{background:#C9B6DA;cursor:not-allowed;pointer-events:none}
    .warn{border:1px solid rgba(255,191,0,.45);background:rgba(255,191,0,.12);color:#8B6914;border-radius:12px;padding:10px 12px;font-size:13px;font-weight:500;margin-bottom:16px}
    .dp{display:grid;grid-template-columns:260px 1fr;gap:20px}
    .dp .img{aspect-ratio:4/5;border-radius:8px;background:#111 center/cover no-repeat}
    .dp h1{font-size:22px;margin:0 0 6px;letter-spacing:-.3px}
    .by{display:flex;gap:6px;align-items:center;font-size:12px}
    .by i{width:20px;height:20px;border-radius:50%;background:var(--p);color:#fff;font-style:normal;font-size:8.5px;font-weight:700;display:inline-flex;align-items:center;justify-content:center}
    .by b{font-weight:700}
    .pr{color:var(--p);font-weight:800;font-size:16px;margin-top:10px}
    .cnt{font-size:11px;color:var(--muted)}.cnt b{color:var(--p)}
    .dpr{display:flex;flex-direction:column}
    .dpr .sp{flex:1;min-height:24px}
    .dnote{font-size:11px;color:var(--muted);text-align:center;margin-top:6px}
    .dpol{text-align:center;margin-top:4px}
    .dpol button{font-size:11px;color:var(--muted);display:inline-flex;align-items:center;gap:3px}
    .dpol button:hover{color:var(--p)}
    .dpol-b{background:#fff;border:1px solid var(--line);border-radius:10px;padding:10px 12px;font-size:11.5px;margin-top:8px;text-align:left}
    .dpol-b ul{margin:0;padding-left:16px}
    .facts{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin:18px 0}
    .fact{background:var(--pl);border-radius:8px;padding:9px 10px}
    .fact small{display:flex;align-items:center;gap:4px;font-size:9.5px;font-weight:700;letter-spacing:.4px;color:var(--muted);text-transform:uppercase}
    .fact small .ms{font-size:13px;color:var(--p)}
    .fact b{font-size:12px;font-weight:600}
    .h3{font-size:13.5px;font-weight:700;margin:18px 0 8px}
    .about{font-size:12.5px;color:#3F3A45;line-height:1.55}
    .about p{margin:0 0 .6em}.about p:last-child{margin-bottom:0}
    .incl{background:var(--pl);border-radius:10px;padding:12px 14px}
    .incl div{font-size:11.5px;color:#3F3A45;padding:2px 0}
    .incl div:before{content:'+';color:var(--p);font-weight:800;margin-right:6px}
    .more{display:grid;grid-template-columns:repeat(3,1fr);gap:10px}
    .mc{background:#fff;border:1px solid var(--line);border-radius:10px;overflow:hidden;text-align:left;display:block;text-decoration:none}
    .mc .i{aspect-ratio:1;background:#222 center/cover no-repeat}
    .mc .t{padding:8px 10px;font-size:11.5px}
    .mc .t b{display:block}
    .tag{display:inline-block;background:var(--pl);color:var(--p);border-radius:999px;font-size:9.5px;padding:1px 7px;margin:3px 0}
    .mc .p{color:var(--p);font-weight:700}
    .mc .c{font-size:10px;color:var(--muted)}.mc .c b{color:var(--p)}
    .ft{border-top:1px solid var(--line);margin-top:28px;padding-top:12px;display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;font-size:10.5px;color:var(--muted)}
    .ft a:hover{color:var(--p)}
    @media (max-width:720px){
      .dp{grid-template-columns:1fr}
      .dp .img{aspect-ratio:1}
      .facts{grid-template-columns:1fr 1fr}
      .more{grid-template-columns:1fr 1fr}
    }
  </style>
</head>
<body>
  <header class="top">
    <div class="in">
      <a class="logo" href="{{ $artistUrl }}"><i>ij</i>inkjin</a>
      <a class="back" href="{{ $artistUrl }}#designs"><span class="ms">arrow_back</span>Back to {{ $artistName }}</a>
    </div>
    <div class="prog" id="prog"></div>
  </header>

  <main class="wrap wide" style="max-width:640px">
    @if(! $canBookOnline)
      <div class="warn">{{ $bookingUnavailableMessage }}</div>
    @endif

    <div class="dp">
      <div class="img" style="background-image:url('{{ $imageUrl }}')" role="img" aria-label="{{ $tattoo->title }}"></div>
      <div class="dpr">
        <h1>{{ $tattoo->title }}</h1>
        <a class="by" href="{{ $artistUrl }}"><i>{{ $artistInitials }}</i>by <b>{{ $artistName }}</b></a>
        <div class="pr">{{ $priceRange }}</div>
        <div class="cnt">
          {{ $claimed }}/{{ $limit }} booked
          @if($isSoldOut)
            · <b style="color:var(--muted)">Sold</b>
          @elseif($remaining > 0)
            · <b>{{ $remaining }} left</b>
          @endif
        </div>
        <div class="sp"></div>

        @if($isSoldOut)
          <button type="button" class="btn full" disabled>Sold</button>
          <div class="dnote">
            @if($tattoo->is_repeatable)
              All {{ $limit }} spots for this design are booked.
            @else
              This one-of-a-kind design has already been booked.
            @endif
          </div>
        @elseif(! $canBookOnline)
          <button type="button" class="btn full" disabled>Not available right now</button>
          <div class="dnote">{{ $artistName }} isn't taking bookings for this design right now.</div>
        @else
          <a class="btn full" href="{{ $bookUrl }}">Book now · {{ $priceRange }}</a>
          <div class="dnote">{{ $depositLabel }} deposit required to secure your booking</div>
        @endif

        <div class="dpol">
          <button type="button" id="dpolBtn" aria-expanded="false">
            <span class="ms" style="font-size:13px">receipt_long</span>Cancellation policy
            <span class="ms" id="dpolIcon" style="font-size:14px">expand_more</span>
          </button>
        </div>
        <div class="dpol-b" id="dpolBody" hidden>
          <ul>
            <li>Full refund if you cancel at least {{ $cancelWindowHuman }} before your appointment</li>
            <li>No refund if you cancel less than {{ $cancelWindowHuman }} before</li>
            <li>{{ $rescheduleBullet }}</li>
            <li>No-shows lose the full deposit</li>
          </ul>
        </div>
      </div>
    </div>

    <div class="facts">
      @foreach([
        ['brush', 'Style', $styleLabel],
        ['palette', 'Colors', $colorLabel !== '' ? $colorLabel : '—'],
        ['straighten', 'Size (min)', $sizeLabel],
        ['schedule', 'Est. time', $hoursLabel],
        ['event', 'Sessions', $sessionsLabel],
        ['accessibility_new', 'Suggested placement', $placementLabel],
      ] as $fact)
        <div class="fact">
          <small><span class="ms">{{ $fact[0] }}</span>{{ $fact[1] }}</small>
          <b>{{ $fact[2] }}</b>
        </div>
      @endforeach
    </div>

    @if($aboutHtml !== '')
      <div class="h3">About this design</div>
      <div class="about">{!! $aboutHtml !!}</div>
    @endif

    @if(count($designWhatsIncludedItems) > 0)
      <div class="h3">What's included</div>
      <div class="incl">
        @foreach($designWhatsIncludedItems as $includedItem)
          <div>{{ $includedItem }}</div>
        @endforeach
      </div>
    @endif

    @if($relatedTattoos->count() > 0)
      <div class="h3">You might also like</div>
      <div class="more">
        @foreach($relatedTattoos as $related)
          @php
            $relSold = $related->isSoldOut();
            $relClaimed = $related->claimedBookingCount();
            $relLimit = $related->effectiveRepeatLimit();
            $relLeft = $related->remainingBookingSlots();
            $relStyle = ucwords(str_replace(['-', '_'], ' ', (string) ($related->primary_style ?? '')));
            $relPrice = $formatMoney($related->min_price).' – '.$formatMoney($related->max_price);
            $relCount = $relSold
              ? $relClaimed.'/'.$relLimit.' booked · <b>Sold</b>'
              : ($relLeft > 0
                ? $relClaimed.'/'.$relLimit.' booked · <b>'.$relLeft.' left</b>'
                : $relClaimed.'/'.$relLimit.' booked · <b>Not available</b>');
          @endphp
          <a class="mc" href="{{ route('public.tattoo', ['user_name' => $userDetail->user_name, 'tattoo_slug' => $related->slug]) }}">
            <div class="i" style="background-image:url('{{ asset($related->image) }}')"></div>
            <div class="t">
              <b>{{ $related->title }}</b>
              @if($relStyle !== '')
                <span class="tag">{{ $relStyle }}</span>
              @endif
              <div class="p">{{ $relPrice }}</div>
              <div class="c">{!! $relCount !!}</div>
            </div>
          </a>
        @endforeach
      </div>
    @endif

    <div class="ft">
      <span>Powered by <b>inkjin</b> · Bookpay</span>
      <span>
        <a href="https://inkjin.com/en/privacy" target="_blank" rel="noopener">Privacy</a>
        ·
        <a href="https://inkjin.com/en/terms" target="_blank" rel="noopener">Terms</a>
      </span>
    </div>
  </main>

  <script>
    (function () {
      var polBtn = document.getElementById('dpolBtn');
      var polBody = document.getElementById('dpolBody');
      var polIcon = document.getElementById('dpolIcon');
      if (polBtn && polBody) {
        polBtn.addEventListener('click', function () {
          var open = polBody.hasAttribute('hidden');
          if (open) {
            polBody.removeAttribute('hidden');
            polBtn.setAttribute('aria-expanded', 'true');
            if (polIcon) polIcon.textContent = 'expand_less';
          } else {
            polBody.setAttribute('hidden', '');
            polBtn.setAttribute('aria-expanded', 'false');
            if (polIcon) polIcon.textContent = 'expand_more';
          }
        });
      }
    })();
  </script>
</body>
</html>
