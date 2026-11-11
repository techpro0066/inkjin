<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="color-scheme" content="only light">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  @include('layouts.partials.google-analytics')
  @php
    $artistPublicName = $userDetail->publicDisplayName();
    $artistHandle = '@'.$userDetail->user_name;
    $city = trim((string) ($guestSpot->city ?? ''));
    $country = trim((string) ($guestSpot->country ?? ''));
    $cityFull = trim($city.($country !== '' ? ', '.$country : ''));
    $from = $guestSpot->from_date;
    $to = $guestSpot->to_date;
    $sameMonth = $from && $to && $from->format('Y-m') === $to->format('Y-m');
    $dateRangeLabel = $from && $to
        ? ($sameMonth
            ? $from->format('M j').' to '.$to->format('j, Y')
            : $from->format('M j, Y').' to '.$to->format('M j, Y'))
        : '';
    $dateRangeShort = $from && $to
        ? ($sameMonth
            ? $from->format('M j').' to '.$to->format('j, Y')
            : $from->format('M j').' to '.$to->format('M j, Y'))
        : '';
    $hoursLabel = null;
    if ($guestSpot->start_time && $guestSpot->end_time) {
        $hoursLabel = \Carbon\Carbon::parse($guestSpot->start_time)->format('g:i A')
            .' to '
            .\Carbon\Carbon::parse($guestSpot->end_time)->format('g:i A');
    }
    $statusKey = $guestSpot->effectiveStatusKey();
    $canReserve = $guestSpot->isReservable();
    $statusLabel = match ($statusKey) {
        'available' => 'Available',
        'full' => 'Fully booked',
        'completed' => 'Ended',
        default => 'Planned',
    };
    $statusClosed = $statusKey !== 'available';

    $schemeSource = trim((string) ($guestSpot->color_scheme ?? ''));
    if ($schemeSource === '') {
        $lookState = \App\Support\PageLook::stateFromUserDetail($userDetail);
        $schemeSource = (string) ($lookState['scheme'] ?? '');
    }
    [$schemePrimary, $schemeMid, $schemeTint] = \App\Support\PageLook::schemeParts($schemeSource);

    $bannerUrl = $guestSpot->banner_path
        ? (str_starts_with((string) $guestSpot->banner_path, 'http')
            ? $guestSpot->banner_path
            : asset(ltrim((string) $guestSpot->banner_path, '/')))
        : null;
    $hasBannerImage = (bool) $bannerUrl;

    $avatarUrl = ($userDetail->avatar && $userDetail->avatar !== '') ? asset($userDetail->avatar) : null;
    $avatarBg = $avatarUrl ? "background-image:url('".e($avatarUrl)."')" : '';

    $stylesPayload = is_array($userDetail->tattoo_styles ?? null) ? $userDetail->tattoo_styles : [];
    $primaryStyleRaw = trim((string) ($stylesPayload['primary_style'] ?? ''));
    $otherStylesRaw = $stylesPayload['other_styles'] ?? [];
    if (! is_array($otherStylesRaw)) {
        $otherStylesRaw = array_filter(array_map('trim', explode(',', (string) $otherStylesRaw)));
    }
    $formatStyleLabel = static function (string $style): string {
        return ucwords(str_replace(['-', '_'], ' ', $style));
    };
    $styleLabels = [];
    if ($primaryStyleRaw !== '') {
        $styleLabels[] = $formatStyleLabel($primaryStyleRaw);
    }
    foreach ($otherStylesRaw as $style) {
        $style = trim((string) $style);
        if ($style === '') {
            continue;
        }
        $label = $formatStyleLabel($style);
        if (! in_array($label, $styleLabels, true)) {
            $styleLabels[] = $label;
        }
    }
    $tattooingSince = trim((string) ($stylesPayload['tattooing_since'] ?? ''));
    $extraServicesPayload = is_array($userDetail->extra_services ?? null) ? $userDetail->extra_services : [];
    $extraServiceList = is_array($extraServicesPayload['services'] ?? null) ? $extraServicesPayload['services'] : [];
    $extraServiceOther = trim((string) ($extraServicesPayload['other'] ?? ''));
    $extraServiceLabels = [];
    foreach ($extraServiceList as $svc) {
        $svc = trim((string) $svc);
        if ($svc === '' || $svc === 'Other') {
            continue;
        }
        $extraServiceLabels[] = $svc;
    }
    if (in_array('Other', array_map('strval', $extraServiceList), true) && $extraServiceOther !== '') {
        $extraServiceLabels[] = $extraServiceOther;
    }
    $extraServicesLine = implode(' · ', $extraServiceLabels);

    $studioName = $guestSpot->studioDisplayName() ?: 'Guest studio';
    $studioAddress = $guestSpot->listLocationLabel() ?: '';
    $mapsHref = trim((string) ($guestSpot->google_maps_link ?? ''));
    if ($mapsHref === '' && $studioAddress !== '') {
        $mapsHref = 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($studioAddress);
    }

    $capacity = (int) ($guestSpot->number_of_spots ?? 0);
    $remaining = max(0, (int) ($guestSpot->remaining_spots ?? 0));
    $showSpots = $capacity > 0 && $statusKey === 'available';
    $spotsLeftLabel = $showSpots ? ($remaining.' of '.$capacity.' spots left') : null;
    $spotsBarPct = $showSpots ? round((($capacity - $remaining) / max(1, $capacity)) * 100) : 0;

    $currencyCode = strtoupper(trim((string) ($userDetail->currency ?? 'EUR')));
    $currencySymbol = match ($currencyCode) {
        'EUR' => '€',
        'GBP' => '£',
        'USD', 'CAD', 'AUD', 'NZD', 'SGD' => '$',
        default => $currencyCode !== '' ? $currencyCode.' ' : '€',
    };
    $formatRate = static function ($amount) use ($currencySymbol): string {
        if ($amount === null || $amount === '') {
            return '';
        }
        $number = rtrim(rtrim(number_format((float) $amount, 2, '.', ''), '0'), '.');

        return $currencySymbol.$number;
    };
    $hourlyRateLabel = $formatRate($userDetail->hourly_rate);
    $halfDayRateLabel = $formatRate($userDetail->half_day_rate);
    $fullDayRateLabel = $formatRate($userDetail->full_day_rate);
    $showRates = (bool) ($guestSpot->show_rates ?? false)
        && ($hourlyRateLabel !== '' || $halfDayRateLabel !== '' || $fullDayRateLabel !== '');

    $depositType = strtolower((string) ($userDetail->minimum_deposit_type ?? 'percentage'));
    $depositAmountRaw = (float) ($userDetail->minimum_deposit_amount ?? 30);
    $depositAmountLabel = rtrim(rtrim(number_format(max(0, $depositAmountRaw), 2, '.', ''), '0'), '.');
    $depositShort = $depositType === 'amount'
        ? $currencySymbol.$depositAmountLabel.' deposit to book. It comes off your final price.'
        : $depositAmountLabel.'% deposit to book. It comes off your final price.';

    $showPolicies = (bool) ($guestSpot->show_policies ?? true);
    $policyCopy = \App\Support\ArtistPolicyCopy::for($userDetail);
    $depositPolicyText = $policyCopy['deposit'];
    $reschedulePolicyText = $policyCopy['rescheduling'];
    $cancellationPolicyText = $policyCopy['cancellation'];
    $noShowPolicyText = $policyCopy['no_show'];
    $policiesExtraNote = trim((string) ($userDetail->policies_extra_note ?? ''));
    $whatsIncludedItems = $userDetail->activeDesignWhatsIncludedItems();

    $faqs = $faqs ?? collect();
    $showFaq = (bool) ($guestSpot->show_faq ?? true) && $faqs->count() > 0;
    $hasDesigns = $artistDesigns->count() > 0;
    $hasPortfolio = $artistPortfolios->count() > 0;
    $showTabs = $hasDesigns || $hasPortfolio || $showPolicies || $showFaq;

    $designsTabActive = $hasDesigns;
    $portfolioTabActive = ! $hasDesigns && $hasPortfolio;
    $policiesTabActive = $showPolicies && ! $hasDesigns && ! $hasPortfolio;
    $faqTabActive = $showFaq && ! $hasDesigns && ! $hasPortfolio && ! $showPolicies;

    $profileUrl = route('public.artist', ['username' => $userDetail->user_name]);
    $reserveUrl = route('public.request-custom', [
        'user_name' => $userDetail->user_name,
        'guest_spot' => $guestSpot->id,
    ]);
    $notifyCity = $cityFull !== '' ? $cityFull : 'this city';

    $instagramHref = \App\Support\SocialLinks::publicHref('instagram', $userDetail->social_links['instagram'] ?? null);
    $tiktokHref = \App\Support\SocialLinks::publicHref('tiktok', $userDetail->social_links['tiktok'] ?? null);
    $youtubeHref = \App\Support\SocialLinks::publicHref('youtube', $userDetail->social_links['youtube'] ?? null);
    $facebookHref = \App\Support\SocialLinks::publicHref('facebook', $userDetail->social_links['facebook'] ?? null);
    $websiteHref = \App\Support\SocialLinks::publicHref('website', $userDetail->social_links['website'] ?? null);
    $hasSocial = $websiteHref || $instagramHref || $tiktokHref || $youtubeHref || $facebookHref;

    $pageTitle = $artistPublicName.' · Guest spot'.($city !== '' ? ' in '.$city : '').($dateRangeShort !== '' ? ', '.$dateRangeShort : '');
    $metaDescription = 'Reserve a guest spot with '.$artistPublicName
        .($cityFull !== '' ? ' in '.$cityFull : '')
        .($dateRangeLabel !== '' ? ' ('.$dateRangeLabel.')' : '')
        .'.';

    $closedTitle = match ($statusKey) {
        'full' => 'Fully booked',
        'completed' => 'This guest spot has ended',
        default => 'Not open yet',
    };
    $closedBody = match ($statusKey) {
        'full' => 'Every spot on this trip is taken.',
        'completed' => 'Dates for this guest spot have passed.',
        default => 'This guest spot isn’t open for reservations yet.',
    };
  @endphp
  <title>{{ $pageTitle }}</title>
  <meta name="description" content="{{ $metaDescription }}">
  <link rel="icon" href="{{ asset('design/images/icons/favicon.png') }}">
  @include('public.partials.artist-page-css')
  @include('public.partials.guest-spot-page-css')
  <style>
    :root{
      --a1: {{ $schemePrimary }};
      --a2: {{ $schemeMid }};
      --acc: {{ $schemePrimary }};
      --accl: {{ $schemeTint }};
    }
    .bbtn.pri{background:var(--acc)}
    .tab[aria-selected=true]{border-bottom-color:var(--acc)}
    .tab[aria-selected=true] .n{background:var(--acc)}
    .bar i,.chip.pri{background:var(--acc)}
    .tg.st,.pill.pl,.gd{background:var(--accl);color:var(--acc)}
  </style>
</head>
<body>
@if($hasBannerImage)
  <div class="banner img" id="banner" style="background-image:url('{{ $bannerUrl }}');background-size:cover;background-position:center;"></div>
@else
  <div class="banner" id="banner" style="background:linear-gradient(120deg,var(--a1),var(--a2))">
    <svg class="sky" viewBox="0 0 1200 230" preserveAspectRatio="xMidYMax slice" aria-hidden="true"><g fill="#fff" opacity=".16"><path d="M0 230V170h60v-30h40v30h30v-60h50v60h40v-20h30v-50l25-30 25 30v70h40v-90h20v-30h10v30h20v90h50V120h20l10-40 10 40h20v110h40v-70h60v-40h30v40h40v70h30V90h15l15-35 15 35h15v140h40v-60h50v60h40v-100l40-40 40 40v100h50v-50h40v50h40v-80h60v80h50V230z"/></g><g fill="#fff" opacity=".28"><rect x="610" y="40" width="10" height="190"/><path d="M590 230v-70h50v70z"/><circle cx="615" cy="34" r="9"/></g></svg>
  </div>
@endif

<div class="wrap">
  <div class="hero">
    <div>
      @if($avatarUrl)
        <div class="av" style="{{ $avatarBg }}" role="img" aria-label="{{ $artistPublicName }}"></div>
      @else
        <div class="av av-initials" role="img" aria-label="{{ $artistPublicName }}">{{ $userDetail->publicDisplayInitials() }}</div>
      @endif
      <h1>{{ $artistPublicName }}</h1>
      <div class="gstat"><span class="status {{ $statusClosed ? 'closed' : '' }}">{{ $statusLabel }}</span></div>
      <p class="gline"><span class="ms">flight_takeoff</span>Guest spot{{ $city !== '' ? ' in '.$city : '' }}{{ $dateRangeShort !== '' ? ', '.$dateRangeShort : '' }}</p>
      <div class="meta">
        <div class="mi">
          <span class="ms">storefront</span>
          <div>
            <b>{{ $studioName }}</b>
            @if($studioAddress !== '')
              <div style="color:var(--muted);font-size:13.5px">{{ $studioAddress }}</div>
            @endif
          </div>
        </div>
        @if($tattooingSince !== '')
          <div class="mi"><span class="ms">calendar_month</span><div>Tattooing since {{ $tattooingSince }}</div></div>
        @endif
        @if(count($styleLabels))
          <div class="mi">
            <span class="ms">palette</span>
            <div class="chips">
              @foreach($styleLabels as $i => $label)
                <span class="chip {{ $i === 0 ? 'pri' : '' }}">{{ $label }}</span>
              @endforeach
            </div>
          </div>
        @endif
        @if($extraServicesLine !== '')
          <div class="mi"><span class="ms">add_circle</span><div><b>Also:</b> {{ $extraServicesLine }}</div></div>
        @endif
      </div>
      @if($hasSocial)
        <div class="soc">
          @if($instagramHref)
            <a href="{{ $instagramHref }}" target="_blank" rel="noopener noreferrer" aria-label="Instagram"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/></svg></a>
          @endif
          @if($tiktokHref)
            <a href="{{ $tiktokHref }}" target="_blank" rel="noopener noreferrer" aria-label="TikTok"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M16.6 2h-3.3v13.1a2.9 2.9 0 1 1-2.1-2.8V8.9a6.3 6.3 0 1 0 5.4 6.2V8.4a7.9 7.9 0 0 0 4.4 1.3V6.4a4.5 4.5 0 0 1-4.4-4.4z"/></svg></a>
          @endif
          @if($youtubeHref)
            <a href="{{ $youtubeHref }}" target="_blank" rel="noopener noreferrer" aria-label="YouTube"><span class="ms">play_circle</span></a>
          @endif
          @if($facebookHref)
            <a href="{{ $facebookHref }}" target="_blank" rel="noopener noreferrer" aria-label="Facebook"><span class="ms">public</span></a>
          @endif
          @if($websiteHref)
            <a href="{{ $websiteHref }}" target="_blank" rel="noopener noreferrer" aria-label="Website"><span class="ms">language</span></a>
          @endif
        </div>
      @endif
    </div>

    <aside class="book" id="book">
      <div class="gbox">
        @if($dateRangeLabel !== '')
          <div class="gr">
            <span class="ms">event</span>
            <div>
              <b>{{ $dateRangeLabel }}</b>
              @if($hoursLabel)<small>{{ $hoursLabel }}</small>@endif
            </div>
          </div>
        @endif
        <div class="gr">
          <span class="ms">location_on</span>
          <div>
            <b>{{ $cityFull !== '' ? $cityFull : 'Guest location' }}</b>
            <small>{{ $studioName }}</small>
          </div>
        </div>
        @if($spotsLeftLabel)
          <div class="gr">
            <span class="ms">event_seat</span>
            <div>
              <b>{{ $spotsLeftLabel }}</b>
              <div class="bar"><i style="width:{{ $spotsBarPct }}%"></i></div>
            </div>
          </div>
        @endif
      </div>

      @if($showRates)
        <div class="rates">
          @if($hourlyRateLabel !== '')
            <div class="rate"><span>Hourly rate</span><b>{{ $hourlyRateLabel }}</b></div>
          @endif
          @if($halfDayRateLabel !== '')
            <div class="rate"><span>Half day</span><b>{{ $halfDayRateLabel }}</b></div>
          @endif
          @if($fullDayRateLabel !== '')
            <div class="rate"><span>Full day</span><b>{{ $fullDayRateLabel }}</b></div>
          @endif
        </div>
      @endif

      <div class="dep"><span class="ms">payments</span><span>{{ $depositShort }}</span></div>

      @if(! $canReserve)
        <div class="closedbox">
          <span class="ms">{{ $statusKey === 'full' ? 'event_busy' : ($statusKey === 'completed' ? 'event_available' : 'schedule') }}</span>
          <b>{{ $closedTitle }}</b>
          <p>{{ $closedBody }}</p>
        </div>
      @endif

      @if($canReserve)
        <a class="bbtn pri" id="reserve" href="{{ $reserveUrl }}"><span class="ms">event_available</span>Reserve your guest spot</a>
      @endif
      <button type="button" class="bbtn sec" id="gnotify" data-act="gwl-next" data-c="{{ $notifyCity }}" data-gs-id="{{ (int) $guestSpot->id }}"><span class="ms">notifications</span>Notify me for the next {{ $city !== '' ? $city : 'guest' }} guest spot</button>
    </aside>
  </div>
</div>

@if($showTabs)
  <div class="tabbar">
    <div class="wrap">
      <div class="tabs" role="tablist" id="gstabs">
        @if($hasDesigns)
          <button type="button" class="tab" role="tab" data-tab="designs" aria-selected="{{ $designsTabActive ? 'true' : 'false' }}">Available designs <span class="n">{{ $artistDesigns->count() }}</span></button>
        @endif
        @if($hasPortfolio)
          <button type="button" class="tab" role="tab" data-tab="portfolio" aria-selected="{{ $portfolioTabActive ? 'true' : 'false' }}">Portfolio <span class="n">{{ $artistPortfolios->count() }}</span></button>
        @endif
        @if($showPolicies)
          <button type="button" class="tab" role="tab" data-tab="policies" aria-selected="{{ $policiesTabActive ? 'true' : 'false' }}">Policies</button>
        @endif
        @if($showFaq)
          <button type="button" class="tab" role="tab" data-tab="faq" aria-selected="{{ $faqTabActive ? 'true' : 'false' }}">FAQ <span class="n">{{ $faqs->count() }}</span></button>
        @endif
      </div>
    </div>
  </div>

  <main class="wrap" id="panels">
    @if($hasDesigns)
      <div class="panel tab-panel {{ $designsTabActive ? 'active' : '' }}" id="panel-designs" role="tabpanel">
        <div class="ph"><div><h2>Available designs</h2><p>Flash {{ $artistPublicName }} is bringing{{ $city !== '' ? ' to '.$city : '' }}. Pick one and choose a date.</p></div></div>
        <div class="dgrid">
          @foreach($artistDesigns as $artistDesign)
            @php
              $designImage = $artistDesign->image ? asset($artistDesign->image) : null;
              $isSold = (bool) ($artistDesign->is_sold_out ?? false);
              $designUrl = route('public.tattoo', [
                  'user_name' => $userDetail->user_name,
                  'tattoo_slug' => $artistDesign->slug,
                  'guest_spot' => $guestSpot->id,
              ]);
              $priceMin = $formatRate($artistDesign->min_price);
              $priceMax = $formatRate($artistDesign->max_price);
              $priceLabel = $priceMin !== '' && $priceMax !== '' && $priceMin !== $priceMax
                  ? $priceMin.' – '.$priceMax
                  : ($priceMin !== '' ? $priceMin : $priceMax);
            @endphp
            <article class="dc {{ $isSold ? 'off' : '' }}">
              <div class="dimg" style="{{ $designImage ? "background-image:url('".e($designImage)."')" : '' }}">
                <span class="pill {{ $isSold ? 'r' : 'g' }}">{{ $isSold ? 'Sold' : 'Available' }}</span>
              </div>
              <div class="db">
                <h3>{{ $artistDesign->title }}</h3>
                @if($priceLabel !== '')
                  <div class="price">{{ $priceLabel }}</div>
                @endif
                @if($canReserve && ! $isSold)
                  <a class="bbtn pri" href="{{ $designUrl }}" style="margin-top:14px">Get this tattoo</a>
                @else
                  <div class="sp"></div>
                  <div class="bbtn dis" aria-disabled="true">{{ $isSold ? 'Sold' : 'Reserve closed' }}</div>
                @endif
              </div>
            </article>
          @endforeach
        </div>
      </div>
    @endif

    @if($hasPortfolio)
      <div class="panel tab-panel {{ $portfolioTabActive ? 'active' : '' }}" id="panel-portfolio" role="tabpanel">
        <div class="ph"><div><h2>Portfolio</h2><p>Recent work from {{ $artistPublicName }}.</p></div></div>
        <div class="pgrid">
          @foreach($artistPortfolios as $portfolio)
            @php
              $pfImage = $portfolio->image ? asset($portfolio->image) : null;
            @endphp
            <button type="button" class="pc" style="{{ $pfImage ? "background-image:url('".e($pfImage)."')" : '' }}" data-pf-title="{{ $portfolio->title }}" data-pf-img="{{ $pfImage }}">
              <span class="ov">
                <b>{{ $portfolio->title }}</b>
                @if($canReserve)
                  <span class="req"><span class="ms">edit</span>Request custom</span>
                @endif
              </span>
            </button>
          @endforeach
        </div>
      </div>
    @endif

    @if($showPolicies)
      <div class="panel tab-panel {{ $policiesTabActive ? 'active' : '' }}" id="panel-policies" role="tabpanel">
        <div class="ph"><div><h2>Policies</h2><p>Booking terms for appointments with {{ $artistPublicName }}.</p></div></div>
        @if(count($whatsIncludedItems))
          <div class="inc">
            <h3>What’s included in the session</h3>
            <p class="s">These come with every appointment.</p>
            <div class="incl">
              @foreach($whatsIncludedItems as $item)
                <div><span class="ms">check_circle</span>{{ $item }}</div>
              @endforeach
            </div>
          </div>
        @endif
        <div class="pg">
          <div class="pr"><b>Deposit</b><p>{{ $depositPolicyText }}</p></div>
          <div class="pr"><b>Rescheduling</b><p>{{ $reschedulePolicyText }}</p></div>
          <div class="pr"><b>Cancellation</b><p>{{ $cancellationPolicyText }}</p></div>
          <div class="pr"><b>No-show or late cancellation</b><p>{{ $noShowPolicyText }}</p></div>
        </div>
        @if($policiesExtraNote !== '')
          <div class="pnote"><b>Also:</b> {{ $policiesExtraNote }}</div>
        @endif
      </div>
    @endif

    @if($showFaq)
      <div class="panel tab-panel {{ $faqTabActive ? 'active' : '' }}" id="panel-faq" role="tabpanel">
        <div class="ph"><div><h2>FAQ</h2><p>Common questions about booking with {{ $artistPublicName }}.</p></div></div>
        <div class="faq">
          @foreach($faqs as $faq)
            <div class="fq">
              <button type="button" aria-expanded="false">
                <span>{{ $faq->question }}</span>
                <span class="ms">expand_more</span>
              </button>
              <div class="fa" hidden>{{ $faq->answer }}</div>
            </div>
          @endforeach
        </div>
      </div>
    @endif
  </main>
@endif

<section class="wrap gsec" id="findus">
  <h2>Where you’ll be tattooed</h2>
  <p class="s">{{ $artistPublicName }} works from {{ $studioName }} for this guest spot. Visits by appointment.</p>
  <div class="fu">
    <div class="fu-map" aria-hidden="true">
      <svg viewBox="0 0 600 340" preserveAspectRatio="xMidYMid slice" role="img" aria-label="Map area" style="display:block;width:100%;height:100%"><rect width="600" height="340" fill="#EEF0EC"/><rect x="380" y="200" width="160" height="110" rx="10" fill="#D6E9CF"/><rect x="30" y="20" width="130" height="80" rx="10" fill="#CFE0EE"/><g stroke="#fff" stroke-linecap="round"><path d="M-10 170 L610 140" stroke-width="16"/><path d="M200 -10 L170 350" stroke-width="18"/><path d="M340 -10 L360 350" stroke-width="10"/><path d="M-10 290 L610 270" stroke-width="10"/><path d="M480 -10 L520 190" stroke-width="8"/></g><g transform="translate(272 168)"><circle r="26" fill="var(--acc)" opacity=".14"/><path d="M0 8 C-10 -4 -14 -9 -14 -16 A14 14 0 1 1 14 -16 C14 -9 10 -4 0 8 Z" fill="var(--acc)" transform="translate(0 -8)"/><circle cy="-24" r="5.5" fill="#fff"/></g></svg>
    </div>
    <div class="fu-info">
      <div class="fu-r">
        <span class="ms">storefront</span>
        <div>
          <b>{{ $studioName }}</b>
          @if($studioAddress !== '')
            <div class="sub">{{ $studioAddress }}</div>
          @endif
        </div>
      </div>
      @if($dateRangeLabel !== '')
        <div class="fu-r">
          <span class="ms">event</span>
          <div>
            {{ $dateRangeLabel }}
            @if($hoursLabel)<div class="sub">{{ $hoursLabel }}</div>@endif
          </div>
        </div>
      @endif
      @if($mapsHref !== '')
        <a class="bbtn sec fu-dir" href="{{ $mapsHref }}" target="_blank" rel="noopener"><span class="ms">directions</span>Get directions</a>
      @endif
    </div>
  </div>
</section>

<footer>
  <div class="wrap">
    <div class="flogo"><b>bookpay</b><small>Tattoo artist platform<br>by Inkjin</small></div>
    <nav>
      <a href="{{ $profileUrl }}">{{ $artistPublicName }}’s page</a>
    </nav>
  </div>
</footer>

<div class="mcta" id="mcta">
  @if($canReserve)
    <a class="bbtn pri wide" id="mreserve" href="{{ $reserveUrl }}">Reserve your guest spot</a>
  @endif
  <button type="button" class="bbtn sec{{ $canReserve ? '' : ' wide' }}" data-act="gwl-next" data-c="{{ $notifyCity }}" data-gs-id="{{ (int) $guestSpot->id }}" aria-label="Notify me for the next {{ $city !== '' ? $city : 'guest' }} guest spot" title="Notify me for the next {{ $city !== '' ? $city : 'guest' }} guest spot">
    <span class="ms">notifications</span>
    @if(! $canReserve)
      Notify me for the next {{ $city !== '' ? $city : 'guest' }} guest spot
    @endif
  </button>
</div>

<div class="mb" id="lb" hidden>
  <div class="lb" role="dialog" aria-modal="true" aria-label="Portfolio piece">
    <button type="button" class="x" data-close aria-label="Close"><span class="ms">close</span></button>
    <div class="lbimg" id="lbimg"></div>
    <div class="lbb" id="lbb">
      <h2 id="lbtitle"></h2>
      @if($canReserve)
        <a class="bbtn pri" href="{{ $reserveUrl }}" style="margin-top:auto">Reserve your guest spot</a>
      @endif
    </div>
  </div>
</div>

@include('partials.bookpay-public-api')
<script src="{{ asset('new-ui-design-assets/guest-waitlist.js') }}"></script>
<script>
(function () {
  var artistDisplayName = @json($artistPublicName);
  var artistUsername = @json($userDetail->user_name);

  document.addEventListener('click', function (e) {
    var act = e.target.closest('[data-act]');
    if (!act || !window.bpGuestWL) return;
    var a = act.getAttribute('data-act');
    if (a === 'gwl-next') {
      e.preventDefault();
      bpGuestWL.open({
        artist: artistDisplayName,
        artistUsername: artistUsername,
        city: act.dataset.c || null,
        guestSpotId: act.dataset.gsId ? Number(act.dataset.gsId) : null,
        kind: 'next',
      });
    }
  });

  var tabs = document.querySelectorAll('#gstabs .tab');
  var panels = {
    designs: document.getElementById('panel-designs'),
    portfolio: document.getElementById('panel-portfolio'),
    policies: document.getElementById('panel-policies'),
    faq: document.getElementById('panel-faq')
  };
  function switchTab(key) {
    tabs.forEach(function (t) {
      t.setAttribute('aria-selected', t.getAttribute('data-tab') === key ? 'true' : 'false');
    });
    Object.keys(panels).forEach(function (k) {
      if (!panels[k]) return;
      panels[k].classList.toggle('active', k === key);
    });
  }
  tabs.forEach(function (t) {
    t.addEventListener('click', function () { switchTab(t.getAttribute('data-tab')); });
  });

  document.querySelectorAll('.fq > button').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var open = btn.getAttribute('aria-expanded') === 'true';
      var ans = btn.nextElementSibling;
      btn.setAttribute('aria-expanded', open ? 'false' : 'true');
      if (ans) ans.hidden = open;
      var icon = btn.querySelector('.ms');
      if (icon) icon.textContent = open ? 'expand_more' : 'expand_less';
    });
  });

  var lb = document.getElementById('lb');
  var lbimg = document.getElementById('lbimg');
  var lbtitle = document.getElementById('lbtitle');
  document.querySelectorAll('.pc').forEach(function (pc) {
    pc.addEventListener('click', function () {
      if (!lb) return;
      var img = pc.getAttribute('data-pf-img') || '';
      var title = pc.getAttribute('data-pf-title') || '';
      if (lbimg) lbimg.style.backgroundImage = img ? "url('" + img.replace(/'/g, "\\'") + "')" : '';
      if (lbtitle) lbtitle.textContent = title;
      lb.hidden = false;
    });
  });
  if (lb) {
    lb.addEventListener('click', function (e) {
      if (e.target === lb || e.target.closest('[data-close]')) lb.hidden = true;
    });
  }

  var mcta = document.getElementById('mcta');
  var book = document.getElementById('book');
  if (mcta && book && 'IntersectionObserver' in window) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        mcta.classList.toggle('show', !en.isIntersecting);
      });
    }, { threshold: 0.15 });
    io.observe(book);
  }
})();
</script>
</body>
</html>
