<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="color-scheme" content="only light">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  @include('layouts.partials.google-analytics')
  <title>{{ $userDetail->publicDisplayName() }} · Book a tattoo</title>
  @php
    $metaDescription = 'Book tattoo designs or request custom work from '.$userDetail->publicDisplayName();
    if (trim((string) ($userDetail->studio_name ?? '')) !== '') {
        $metaDescription .= ' at '.$userDetail->studio_name;
    }
    if (trim((string) ($userDetail->city ?? '')) !== '') {
        $metaDescription .= ', '.$userDetail->city;
    }
    $metaDescription .= '.';
  @endphp
  <meta name="description" content="{{ $metaDescription }}">
  <link rel="icon" href="{{ asset('design/images/icons/favicon.png') }}">
  @php
    $lookState = \App\Support\PageLook::stateFromUserDetail($userDetail);
    $isLookPreview = request()->boolean('preview');
    $lookIsCustom = ($lookState['mode'] ?? '') === 'custom';
    $lookBoxStyle = \App\Support\PageLook::boxStyleFromState($lookState);
    [$schemePrimary, $schemeMid, $schemeTint] = \App\Support\PageLook::schemeParts(
        (string) ($lookState['scheme'] ?? '')
    );

    $displayDesigns = (bool) ($userDetail->display_designs ?? true);
    $showAvailableDesigns = $displayDesigns && in_array($userDetail->availability_status, ['design_custom', 'design_only'], true);
    $hasVisibleDesigns = $showAvailableDesigns && $artistDesigns->count() > 0;
    $displayPortfolio = (bool) ($userDetail->display_portfolio ?? true);
    $hasPortfolio = $displayPortfolio && $artistPortfolios->count() > 0;
    $displayPolicies = (bool) ($userDetail->display_policies ?? true);
    $displayGuestSpots = (bool) ($userDetail->display_guest_spots ?? false);
    $displayFaq = (bool) ($userDetail->display_faq ?? false);
    $guestSpots = $guestSpots ?? collect();
    $faqs = $faqs ?? collect();
    $hasFaqs = $displayFaq && $faqs->count() > 0;
    $showTabsNav = $hasVisibleDesigns || $hasPortfolio || $displayGuestSpots || $displayPolicies || $hasFaqs;

    $designsTabActive = $hasVisibleDesigns;
    $portfolioTabActive = ! $hasVisibleDesigns && $hasPortfolio;
    $guestSpotsTabActive = $displayGuestSpots && ! $hasVisibleDesigns && ! $hasPortfolio;
    $policiesTabActive = $displayPolicies && ! $hasVisibleDesigns && ! $hasPortfolio && ! $displayGuestSpots;
    $faqTabActive = $hasFaqs && ! $hasVisibleDesigns && ! $hasPortfolio && ! $displayGuestSpots && ! $displayPolicies;

    $policyCopy = \App\Support\ArtistPolicyCopy::for($userDetail);
    $depositPolicyText = $policyCopy['deposit'];
    $reschedulePolicyText = $policyCopy['rescheduling'];
    $cancellationPolicyText = $policyCopy['cancellation'];
    $noShowPolicyText = $policyCopy['no_show'];
    $policiesExtraNote = trim((string) ($userDetail->policies_extra_note ?? ''));
    $whatsIncludedItems = $userDetail->activeDesignWhatsIncludedItems();

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
          $locationParts = array_filter([
              trim((string) ($userDetail->city ?? '')),
              trim((string) ($userDetail->country ?? '')),
          ]);
          $locationLine = implode(', ', $locationParts);
          $tagline = trim((string) ($userDetail->personal_page_tagline ?? ''));
    $showTagline = ($userDetail->display_tagline ?? true) && $tagline !== '';
    $artistPublicName = $userDetail->publicDisplayName();
    $artistHandle = '@'.$userDetail->user_name;
    $avatarUrl = ($userDetail->avatar && $userDetail->avatar !== '') ? asset($userDetail->avatar) : null;
    $bannerUrl = ($userDetail->personal_page_background_image && $userDetail->personal_page_background_image !== '')
        ? asset($userDetail->personal_page_background_image)
        : null;

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
    $hasRates = $hourlyRateLabel !== '' || $halfDayRateLabel !== '' || $fullDayRateLabel !== '';

    $depositType = strtolower((string) ($userDetail->minimum_deposit_type ?? 'percentage'));
    $depositAmountRaw = (float) ($userDetail->minimum_deposit_amount ?? 30);
    $depositAmountLabel = rtrim(rtrim(number_format(max(0, $depositAmountRaw), 2, '.', ''), '0'), '.');
    $depositShort = $depositType === 'amount'
        ? $currencySymbol.$depositAmountLabel.' deposit to book. It comes off your final price.'
        : $depositAmountLabel.'% deposit to book. It comes off your final price.';

    $availability = (string) ($userDetail->availability_status ?? 'design_custom');
    $booksClosed = $availability === 'closed';
    $acceptsCustom = in_array($availability, ['design_custom', 'custom_only'], true);
    $acceptsDesigns = in_array($availability, ['design_custom', 'design_only'], true);
    $statusLabel = match ($availability) {
        'closed' => 'Books closed',
        'design_only' => 'Designs only',
        'custom_only' => 'Custom only',
        default => 'Books open',
    };
    $requestCustomUrl = route('public.request-custom', ['user_name' => $userDetail->user_name]);
    $waitlistSignupMode = (string) ($userDetail->waitlist_signup_mode ?? 'name_email');
    $waitlistUsesRequestForm = $waitlistSignupMode === 'request_form';
    $waitlistRequestFormUrl = route('public.request-custom', [
        'user_name' => $userDetail->user_name,
        'waitlist' => 1,
    ]);
    $profileUrl = route('public.artist', ['username' => $userDetail->user_name]);
    $profileHostPath = preg_replace('#^https?://#', '', $profileUrl);

    $workplaces = collect();
    if (\App\Models\UserStudio::tableReady()) {
        $workplaces = $userDetail->user->userStudios()
            ->active()
            ->with('studio')
            ->orderBy('id')
            ->get();
    }
    $workplaceCards = $workplaces->map(function ($row) {
        $name = trim((string) ($row->studio?->name ?? $row->studio_name ?? ''));
        $street = trim(trim((string) ($row->street_number ?? '')).' '.trim((string) ($row->street_name ?? '')));
        if ($street === '') {
            $street = trim((string) ($row->studio_address ?? ''));
        }
        $cityLine = implode(', ', array_filter([
            trim((string) ($row->postal_code ?? '')),
            trim((string) ($row->city ?? '')),
            trim((string) ($row->country ?? '')),
        ]));
        $address = implode(', ', array_filter([$street, $cityLine]));

        return [
            'name' => $name !== '' ? $name : 'Studio',
            'address' => $address,
            'maps' => trim((string) ($row->google_maps_link ?? '')),
        ];
    })->filter(fn ($c) => $c['name'] !== '' || $c['address'] !== '')->values();

    if ($workplaceCards->isEmpty()) {
        $studioName = trim((string) ($userDetail->studio_name ?? ''));
        $addressLines = $userDetail->studioLocationLines();
        array_shift($addressLines);
        $workplaceCards = collect([[
            'name' => $studioName !== '' ? $studioName : 'Studio',
            'address' => implode(', ', $addressLines) ?: $locationLine,
            'maps' => trim((string) ($userDetail->google_maps_link ?? '')),
        ]])->filter(fn ($c) => ($c['name'] !== 'Studio' || $c['address'] !== ''));
    }

    $multiStudio = $workplaceCards->count() > 1;
    $primaryWorkplace = $workplaceCards->first();

    $nextGuest = $guestSpots->first(fn ($s) => $s->isReservable());
    $hasAnyGuest = $displayGuestSpots && $guestSpots->count() > 0;
    $guestNotifyAny = $displayGuestSpots && ! $nextGuest;

    $portfolioForJs = $artistPortfolios->values()->map(function ($portfolio) {
        $colorLabel = match ($portfolio->color) {
            'color' => 'Full Color',
            'black-grey' => 'Black & Grey',
            'both' => 'Black & Color',
            default => (string) $portfolio->color,
        };

        return [
            'id' => $portfolio->id,
            'title' => $portfolio->title,
            'desc' => trim((string) ($portfolio->description ?? '')),
            'style' => ucwords(str_replace('-', ' ', $portfolio->primary_style)),
            'placement' => $portfolio->placement_label,
            'colors' => $colorLabel,
            'image' => asset($portfolio->image),
            'tags' => collect($portfolio->tags ?? [])->map(function ($tag) {
                $tag = trim((string) $tag);

                return $tag === '' ? '' : (str_starts_with($tag, '#') ? $tag : '#'.$tag);
            })->filter()->values()->all(),
        ];
    })->values();

    $websiteHref = \App\Support\SocialLinks::publicHref('website', $userDetail->social_links['website'] ?? null);
    $instagramHref = \App\Support\SocialLinks::publicHref('instagram', $userDetail->social_links['instagram'] ?? null);
    $tiktokHref = \App\Support\SocialLinks::publicHref('tiktok', $userDetail->social_links['tiktok'] ?? null);
    $youtubeHref = \App\Support\SocialLinks::publicHref('youtube', $userDetail->social_links['youtube'] ?? null);
    $facebookHref = \App\Support\SocialLinks::publicHref('facebook', $userDetail->social_links['facebook'] ?? null);
    $hasSocial = $websiteHref || $instagramHref || $tiktokHref || $youtubeHref || $facebookHref;

    $escAttr = static fn ($v) => e((string) $v);
    $avatarBg = $avatarUrl ? ('background-color:#fff;background-image:url('.$escAttr($avatarUrl).')') : '';
    if ($bannerUrl) {
        $bannerStyle = 'background-color:'.$escAttr($schemePrimary)
            .';background-image:url('.$escAttr($bannerUrl).')'
            .';background-position:center top;background-size:cover;background-repeat:no-repeat';
    } else {
        $bannerStyle = 'background-color:'.$escAttr($schemePrimary)
            .';background-image:linear-gradient(120deg,'.$escAttr($schemePrimary).','.$escAttr($schemeMid).')'
            .';background-position:center top;background-size:cover;background-repeat:no-repeat';
    }

    $boxHtmlAvatar = $avatarUrl
        ? '<button type="button" class="cst-av" data-photo aria-label="Open '.e($artistPublicName).'\'s profile photo" style="background-color:#fff;background-image:url(\''.e($avatarUrl).'\')"></button>'
        : '<button type="button" class="cst-av" data-photo aria-label="Open '.e($artistPublicName).'\'s profile photo" style="background:linear-gradient(135deg,'.e($schemePrimary).','.e($schemeMid).');display:flex;align-items:center;justify-content:center;font-weight:800;font-size:48px;color:#fff">'.e($userDetail->publicDisplayInitials()).'</button>';
    $boxHtmlStyles = collect($styleLabels)->map(fn ($l) => '<span>'.e($l).'</span>')->implode('');
    $boxHtmlBio = $userDetail->shouldDisplayBio()
        ? '<p class="cst-bio" id="cbio">'.e($userDetail->personal_page_description).'</p>'
        : '';
    $boxHtmlTag = $showTagline ? '<div class="cst-tag" id="ctag">'.e($tagline).'</div>' : '';
    $boxHtmlLoc = $locationLine !== '' ? '<span class="sep">&middot;</span><span>'.e($locationLine).'</span>' : '';
    $boxHtmlSince = $tattooingSince !== '' ? '<span class="sep">&middot;</span><span>Tattooing since '.e($tattooingSince).'</span>' : '';
    $boxHtmlStudio = $primaryWorkplace
        ? '<span id="cstudio"></span>'
        : '';
    $boxHtmlSocial = '';
    if ($hasSocial) {
        $boxHtmlSocial = '<div class="cst-soc">';
        if ($instagramHref) {
            $boxHtmlSocial .= '<a href="'.e($instagramHref).'" target="_blank" rel="noopener noreferrer" aria-label="Instagram"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/></svg></a>';
        }
        if ($tiktokHref) {
            $boxHtmlSocial .= '<a href="'.e($tiktokHref).'" target="_blank" rel="noopener noreferrer" aria-label="TikTok"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M16.6 2h-3.3v13.1a2.9 2.9 0 1 1-2.1-2.8V8.9a6.3 6.3 0 1 0 5.4 6.2V8.4a7.9 7.9 0 0 0 4.4 1.3V6.4a4.5 4.5 0 0 1-4.4-4.4z"/></svg></a>';
        }
        if ($youtubeHref) {
            $boxHtmlSocial .= '<a href="'.e($youtubeHref).'" target="_blank" rel="noopener noreferrer" aria-label="YouTube"><span class="ms">play_circle</span></a>';
        }
        if ($facebookHref) {
            $boxHtmlSocial .= '<a href="'.e($facebookHref).'" target="_blank" rel="noopener noreferrer" aria-label="Facebook"><span class="ms">public</span></a>';
        }
        if ($websiteHref) {
            $boxHtmlSocial .= '<a href="'.e($websiteHref).'" target="_blank" rel="noopener noreferrer" aria-label="Website"><span class="ms">language</span></a>';
        }
        $boxHtmlSocial .= '</div>';
    }
    $lookBoxHtml = $boxHtmlAvatar
        .'<h1>'.e($artistPublicName).'</h1>'
        .$boxHtmlTag
        .'<div class="cst-meta" style="position:relative">'.$boxHtmlStudio.$boxHtmlLoc.$boxHtmlSince.'<div class="cst-pop" id="cstpop" hidden></div></div>'
        .($boxHtmlStyles !== '' ? '<div class="cst-sty">'.$boxHtmlStyles.'</div>' : '')
        .($extraServicesLine !== '' ? '<div class="cst-svc" id="csvc"><b>Also:</b> '.e($extraServicesLine).'</div>' : '')
        .$boxHtmlBio
        .$boxHtmlSocial;
        @endphp
  @include('public.partials.artist-page-css')
  @php
    $buttonTextColor = \App\Support\PageLook::buttonTextColorFromState($lookState);
  @endphp
  <style>
    :root{
      --a1: {{ $schemePrimary }};
      --a2: {{ $schemeMid }};
      --acc: {{ $schemePrimary }};
      --accl: {{ $schemeTint }};
      --btnt: {{ $buttonTextColor }};
    }
    .bbtn.pri{background:var(--acc);color:var(--btnt)}
    .bbtn.pri .ms{color:inherit}
    .mcta .bbtn.pri{color:var(--btnt)}
    .tab[aria-selected=true]{border-bottom-color:var(--acc)}
    .tab[aria-selected=true] .n{background:var(--acc)}
    .bar i,.chip.pri{background:var(--acc)}
    .tg.st,.pill.pl,.gd{background:var(--accl);color:var(--acc)}
    @if($lookIsCustom)
    :root{
      --bx: {{ $lookBoxStyle['bg'] }};
      --bxf: {{ $lookBoxStyle['blur'] }};
      --bxr: {{ $lookBoxStyle['radius'] }};
      --bxb: {{ $lookBoxStyle['border'] }};
      --bxt: {{ $lookBoxStyle['text'] }};
      --bxtt: {{ $lookBoxStyle['title'] }};
      --bxs: {{ $lookBoxStyle['sub'] }};
      --bxl: {{ $lookBoxStyle['line'] }};
      --bxc: {{ $lookBoxStyle['chip'] }};
      --btnt: {{ $lookBoxStyle['btn'] }};
    }
    html.look-pending,html.look-pending body{background:{{ $lookBoxStyle['pageBg'] }}}
    html.look-pending body{visibility:hidden}
    html.look-ready body{visibility:visible}
    body.cst{background:{{ $lookBoxStyle['pageBg'] }}}
    @endif
  </style>
  @if($lookIsCustom)
  <script>
    document.documentElement.classList.add('look-pending');
    try { sessionStorage.setItem('bp-look-artist', @json($lookState)); } catch (e) {}
  </script>
  <script src="{{ asset('new-ui-design-assets/look.js') }}?v=3"></script>
  @endif
</head>
<body class="{{ trim(($lookIsCustom ? 'cst ' : '').($isLookPreview ? 'bp-prev' : '')) }}" data-books="{{ $booksClosed ? 'closed' : 'open' }}" data-look-who="artist"><div class="banner" id="banner" style="{{ $bannerStyle }}"></div>

<div class="wrap" id="stdhero">
  <div class="hero" id="herohome">
    <div>
      @if($avatarUrl)
        <div class="av" data-photo role="button" tabindex="0" aria-label="Open {{ $artistPublicName }}'s profile photo" style="cursor:pointer;{{ $avatarBg }}"></div>
          @else
        <div class="av av-initials" data-photo role="button" tabindex="0" aria-label="Open {{ $artistPublicName }}'s profile photo" style="cursor:pointer">{{ $userDetail->publicDisplayInitials() }}</div>
          @endif
      <h1>{{ $artistPublicName }}</h1>
      <div class="handle">{{ $artistHandle }}</div>
      @if($showTagline)
        <div class="tagline" id="tagline">{{ $tagline }}</div>
      @endif
      @if($userDetail->shouldDisplayBio())
        <p class="bio" id="bio">{{ $userDetail->personal_page_description }}</p>
        @endif

      <div class="meta">
        @if(! $multiStudio && $primaryWorkplace)
          <div class="mi" id="st1">
            <span class="ms">storefront</span>
            <div>
              <b>{{ $primaryWorkplace['name'] }}</b>
              @if($primaryWorkplace['address'] !== '')
                <div style="color:var(--muted);font-size:13.5px">{{ $primaryWorkplace['address'] }}</div>
          @endif
            </div>
          </div>
        @endif

        @if($multiStudio)
          <div class="mi" id="st2">
            <span class="ms">storefront</span>
            <div style="flex:1">
              <b>Works at {{ $workplaceCards->count() }} studios</b>
              <div class="stl">
                @foreach($workplaceCards as $card)
                  <div>
                    <b>{{ $card['name'] }}</b>
                    @if($card['address'] !== '')
                      <small>{{ $card['address'] }}</small>
          @endif
        </div>
                @endforeach
              </div>
              <div class="stlnote">
                <span class="ms">swap_horiz</span>
                <span>Where you’ll be tattooed depends on the day you pick.</span>
              </div>
            </div>
          </div>
          @endif

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
          <div class="mi" id="svcline">
            <span class="ms">add_circle</span>
            <div><b>Also:</b> {{ $extraServicesLine }}</div>
          </div>
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
      <div class="status {{ $booksClosed ? 'closed' : '' }}" id="status">{{ $statusLabel }}</div>

      @if($hasRates)
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

      @if($multiStudio)
        <div class="stnote" id="stnote">
          <span class="ms">location_on</span>
          <span>Where you’ll be tattooed depends on the day you pick.</span>
            </div>
      @endif

      @php
        $hasOpenBookingCta = $acceptsCustom || ($acceptsDesigns && $hasVisibleDesigns);
      @endphp
      <div id="openbox" @if($booksClosed || ! $hasOpenBookingCta) hidden @endif>
        @if($acceptsCustom)
          <a class="bbtn pri" href="{{ $requestCustomUrl }}"><span class="ms">brush</span>Request custom tattoo</a>
        @elseif($acceptsDesigns && $hasVisibleDesigns)
          <button type="button" class="bbtn pri" data-act="designs"><span class="ms">palette</span>Browse available designs</button>
        @endif
      </div>

      <div id="closedbox" @if(! $booksClosed) hidden @endif>
        <div class="closedbox">
          <span class="ms">event_busy</span>
          <b>Books closed</b>
          <p>{{ $artistPublicName }} isn’t taking new bookings right now. Get an email as soon as books open.</p>
      </div>
        <button type="button" class="bbtn pri" data-act="notify"><span class="ms">notifications</span>Books closed - Notify me</button>
    </div>

      @if($displayGuestSpots && ($nextGuest || $guestNotifyAny))
        <div id="gcta">
          @if($nextGuest)
            @php
              $nextCity = trim($nextGuest->city.($nextGuest->country ? ', '.$nextGuest->country : ''));
              $nextDates = $nextGuest->from_date->format('M j').' to '.$nextGuest->to_date->format('M j Y');
              $nextLeft = $nextGuest->listRemainingSpotsLabel();
            @endphp
            <a class="next" id="gnext" href="#guest-spots" data-act="guest">
              <span class="ms">flight_takeoff</span>
              <span>
                <b>Next guest spot: {{ $nextCity }}</b>
                <small>{{ $nextDates }}@if($nextLeft) · {{ $nextLeft }}@endif</small>
              </span>
            </a>
  @endif
          @if($guestNotifyAny)
            <button type="button" class="next" id="gnotify" data-act="gwl-any">
              <span class="ms">notifications</span>
              <span>
                <b>Notify me for guest spots in…</b>
                <small>Get an email when {{ $artistPublicName }} guests in your city</small>
              </span>
            </button>
          @endif
        </div>
      @endif
    </aside>
  </div>
</div>

  @if($showTabsNav)
<div class="tabbar" id="artistDesignsSection">
  <div class="wrap">
    <div class="tabs" role="tablist" id="tabs">
      @if($hasVisibleDesigns)
        <button type="button" class="tab" role="tab" id="tab-designs" data-tab="designs" aria-selected="{{ $designsTabActive ? 'true' : 'false' }}">Available designs <span class="n">{{ $artistDesigns->count() }}</span></button>
      @endif
      @if($hasPortfolio)
        <button type="button" class="tab" role="tab" id="tab-portfolio" data-tab="portfolio" aria-selected="{{ $portfolioTabActive ? 'true' : 'false' }}">Portfolio <span class="n">{{ $artistPortfolios->count() }}</span></button>
      @endif
      @if($displayGuestSpots)
        <button type="button" class="tab" role="tab" id="tab-guest-spots" data-tab="guest-spots" aria-selected="{{ $guestSpotsTabActive ? 'true' : 'false' }}">Guest spots @if($guestSpots->count())<span class="n">{{ $guestSpots->count() }}</span>@endif</button>
      @endif
      @if($displayPolicies)
        <button type="button" class="tab" role="tab" id="tab-policies" data-tab="policies" aria-selected="{{ $policiesTabActive ? 'true' : 'false' }}">Policies</button>
      @endif
      @if($hasFaqs)
        <button type="button" class="tab" role="tab" id="tab-faq" data-tab="faq" aria-selected="{{ $faqTabActive ? 'true' : 'false' }}">FAQ <span class="n">{{ $faqs->count() }}</span></button>
      @endif
    </div>
  </div>
</div>
  @endif

{{-- Keep #panels as BPLook insert anchor; hide chrome when there is no tab content. --}}
<main class="wrap panel{{ $showTabsNav ? '' : ' is-empty' }}" id="panels" @if(! $showTabsNav) hidden aria-hidden="true" @endif>
    @if($hasVisibleDesigns)
    <div id="content-designs" class="tab-panel {{ $designsTabActive ? 'active' : '' }}" role="tabpanel">
      <div class="ph mt-3"><div><h2>Available designs</h2><p>Flash designs ready to book. Pick one and choose a date.</p></div></div>
      <div class="dgrid">
            @foreach($artistDesigns as $artistDesign)
          @php
            $designSoldOut = $artistDesign->isSoldOut();
            $claimed = $artistDesign->claimedBookingCount();
            $limit = $artistDesign->effectiveRepeatLimit();
            $remaining = $artistDesign->remainingBookingSlots();
            $pct = $limit > 0 ? max(0, min(100, (int) round(($claimed / $limit) * 100))) : 0;
            $designUrl = route('public.tattoo', ['user_name' => $userDetail->user_name, 'tattoo_slug' => $artistDesign->slug]);
            $colorLabel = match ($artistDesign->color) {
              'color' => 'Full Color',
              'black-grey' => 'Black & Grey',
              'both' => 'Black & Color',
              default => (string) $artistDesign->color,
            };
            $priceMin = $formatRate($artistDesign->min_price);
            $priceMax = $formatRate($artistDesign->max_price);
          @endphp
          <article class="dc {{ $designSoldOut ? 'off' : '' }}">
            <div class="dimg" style="background-image:url('{{ asset($artistDesign->image) }}')">
                        @if($designSoldOut)
                <span class="pill r">Sold out</span>
              @elseif($remaining > 0 && $remaining <= 2)
                <span class="pill g">{{ $remaining }} left</span>
                        @endif
                    </div>
            <div class="db">
              <h3>{{ $artistDesign->title }}</h3>
              <div class="tags">
                <span class="tg st">{{ ucwords(str_replace('-', ' ', $artistDesign->primary_style)) }}</span>
                <span class="tg">{{ $colorLabel }}</span>
                        </div>
              <div class="price">{{ $priceMin }}@if($priceMax !== '' && $priceMax !== $priceMin) — {{ $priceMax }}@endif</div>
              <div class="lim"><b>{{ $claimed }}/{{ $limit }}</b> booked</div>
              <div class="bar"><i style="width:{{ $pct }}%"></i></div>
              <div class="sp"></div>
                        @if($designSoldOut)
                <div class="bbtn dis" aria-disabled="true">Sold out</div>
                        @else
                <a class="bbtn pri" href="{{ $designUrl }}">Get this tattoo</a>
                        @endif
                    </div>
          </article>
            @endforeach
        </div>
        </div>
    @endif

    @if($hasPortfolio)
    <div id="content-portfolio" class="tab-panel {{ $portfolioTabActive ? 'active' : '' }}" role="tabpanel">
      <div class="ph"><div><h2>Portfolio</h2><p>Past work by {{ $artistPublicName }}.</p></div></div>
      <div class="pgrid">
        @foreach($artistPortfolios as $artistPortfolio)
          <button type="button" class="pc" style="background-image:url('{{ asset($artistPortfolio->image) }}')" onclick="openPortfolioModal({{ $loop->index }})" aria-label="{{ $artistPortfolio->title }}">
            <span class="ov">
              <b>{{ $artistPortfolio->title }}</b>
              <small>{{ ucwords(str_replace('-', ' ', $artistPortfolio->primary_style)) }}</small>
              @if($booksClosed)
                <span class="req" onclick="event.stopPropagation(); openBooksWaitlist();"><span class="ms">notifications</span>Books closed - Notify me</span>
              @else
                <span class="req" onclick="event.stopPropagation(); requestSimilarTattoo({{ (int) $artistPortfolio->id }});"><span class="ms">brush</span>Request similar</span>
              @endif
            </span>
          </button>
        @endforeach
                </div>
            </div>
  @endif

  @if($displayGuestSpots)
    <div id="content-guest-spots" class="tab-panel {{ $guestSpotsTabActive ? 'active' : '' }}" role="tabpanel">
      <div class="ph"><div><h2>Guest spots</h2><p>Where {{ $artistPublicName }} is guesting next.</p></div></div>
      <div class="gl">
        @forelse($guestSpots as $spot)
          @php
            $spot->ensureCompletedStatus();
            $key = $spot->effectiveStatusKey();
            $canReserve = $spot->isReservable();
            $isPlanned = $key === 'planned';
            $spotCountLabel = $key === 'available' ? $spot->listRemainingSpotsLabel() : null;
            $cityFull = trim($spot->city.($spot->country ? ', '.$spot->country : ''));
            $dates = $spot->from_date->format('M j').' to '.$spot->to_date->format('M j Y');
            $hours = $key === 'available' ? $spot->listAvailabilityTimeLabel() : null;
          @endphp
          <div class="gs">
            <div class="gd">
              <small>{{ strtoupper($spot->from_date->format('M')) }}</small>
              <b>{{ $spot->from_date->format('j') }}</b>
                </div>
            <div class="gi">
              <h3>{{ $spot->city }}@if($spot->country), {{ $spot->country }}@endif</h3>
              <p>{{ $dates }}@if($hours) · {{ $hours }}@endif</p>
              @if($spotCountLabel)
                <div class="left">{{ $spotCountLabel }}</div>
              @endif
                </div>
            <div class="act">
              <span class="pill {{ $canReserve ? 'av2' : 'pl' }}">{{ $spot->publicStatusLabel() }}</span>
              @if($canReserve)
                <a class="bbtn pri" href="{{ route('public.guest-spot', ['user_name' => $userDetail->user_name, 'guestSpotSlug' => $spot->publicSlug()]) }}">Reserve <span class="ms">arrow_forward</span></a>
              @elseif($isPlanned)
                <button type="button" class="bbtn sec" data-act="gwl-planned" data-c="{{ $cityFull }}" data-gs-id="{{ (int) $spot->id }}"><span class="ms">notifications</span>Notify</button>
              @endif
            </div>
            </div>
        @empty
          <div class="gs" style="justify-content:center;padding:36px">
            <div class="gi" style="text-align:center">
              <h3>No guest locations listed yet</h3>
              <p>Check back soon for upcoming guest spots.</p>
            </div>
          </div>
        @endforelse
      </div>
    </div>
    @endif

    @if($displayPolicies)
    <div id="content-policies" class="tab-panel {{ $policiesTabActive ? 'active' : '' }}" role="tabpanel">
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

  @if($hasFaqs)
    <div id="content-faq" class="tab-panel {{ $faqTabActive ? 'active' : '' }}" role="tabpanel">
      <div class="ph"><div><h2>FAQ</h2><p>Answers to questions clients ask most often.</p></div></div>
      <div class="faq">
        @foreach($faqs as $faq)
          <div class="fq">
            <button type="button" aria-expanded="false" onclick="toggleFaqItem(this)">
              <span>{{ $faq->question }}</span>
              <span class="ms">add</span>
      </button>
            <div class="fa" hidden>{{ $faq->answer }}</div>
          </div>
        @endforeach
        </div>
            </div>
  @endif
</main>

<footer>
  <div class="wrap">
    <div class="flogo">
      <b>bookpay</b>
      <small>Tattoo artist platform<br>by Inkjin</small>
            </div>
    <nav>
      <a href="https://inkjin.com/en/privacy" target="_blank" rel="noopener noreferrer">Privacy</a>
      <a href="https://inkjin.com/en/terms" target="_blank" rel="noopener noreferrer">Terms</a>
    </nav>
          </div>
</footer>

<div class="mcta" id="mcta"></div>

{{-- Portfolio lightbox --}}
<div class="mb" id="portfolioDetailModal" hidden>
  <div class="lb" role="dialog" aria-modal="true" aria-label="Portfolio piece">
    <button type="button" class="x" data-close aria-label="Close"><span class="ms">close</span></button>
    <div class="lbimg" id="portfolioModalImage"></div>
    <div class="lbb" id="portfolioModalBody">
      <h2 id="portfolioModalTitle"></h2>
      <p id="portfolioModalDesc" style="color:var(--muted);margin-top:8px;font-size:14px;line-height:1.6"></p>
      <div class="facts">
        <div><small>Style</small><b id="portfolioModalStyle"></b></div>
        <div><small>Placement</small><b id="portfolioModalPlacement"></b></div>
        <div><small>Colors</small><b id="portfolioModalColors"></b></div>
          </div>
      <div class="tags" id="portfolioModalTags"></div>
      @if($booksClosed)
        <button type="button" class="bbtn pri" id="portfolioModalSimilarBtn"><span class="ms">notifications</span>Books closed - Notify me</button>
      @else
        <button type="button" class="bbtn pri" id="portfolioModalSimilarBtn"><span class="ms">brush</span>Request similar tattoo</button>
      @endif
      </div>
    </div>
  </div>

{{-- Books waitlist: name + email --}}
<div class="mb" id="waitlistModal" hidden>
  <div class="nm" role="dialog" aria-modal="true" aria-labelledby="nmh">
    <button type="button" class="x" data-close aria-label="Close" style="box-shadow:none"><span class="ms">close</span></button>
      <div id="waitlistFormView">
      <h2 id="nmh">Join the waitlist</h2>
      <p>Enter your name and email to be the first to know when {{ $artistPublicName }} opens their books.</p>
      <form id="waitlistForm" onsubmit="event.preventDefault(); submitWaitlist();" novalidate>
        <label for="waitlistName">Name</label>
        <input type="text" id="waitlistName" name="name" autocomplete="name">
        <p id="waitlistNameError" class="gw-err" hidden style="color:#C62828;font-size:12.5px;font-weight:600;margin-top:5px"></p>
        <label for="waitlistEmail">Email</label>
        <input type="email" id="waitlistEmail" name="email" autocomplete="email">
        <p id="waitlistEmailError" hidden style="color:#C62828;font-size:12.5px;font-weight:600;margin-top:5px"></p>
        <p id="waitlistFormError" hidden style="color:#C62828;font-size:13px;margin-top:10px"></p>
        <button type="submit" class="bbtn pri" id="waitlistSubmitBtn" style="margin-top:18px"><span class="ms">notifications</span>Notify me</button>
        </form>
          </div>
    <div id="waitlistSuccessView" hidden>
      <div style="text-align:center;padding-top:8px">
        <span class="ms" style="font-size:48px;color:var(--green)">check_circle</span>
        <h2 style="margin-top:10px">You’re on the list!</h2>
        <p>We’ll email you the moment {{ $artistPublicName }}’s books open.</p>
        <button type="button" class="bbtn sec" data-close style="margin-top:18px">Close</button>
          </div>
      </div>
      </div>
    </div>

{{-- Books waitlist: request-form intro --}}
<div class="mb" id="waitlistRequestModal" hidden>
  <div class="nm" role="dialog" aria-modal="true" aria-labelledby="wlReqTitle">
    <button type="button" class="x" data-close aria-label="Close" style="box-shadow:none"><span class="ms">close</span></button>
    <h2 id="wlReqTitle">Get notified when books open</h2>
    <p>Tell {{ $artistPublicName }} what you’d like to get. You’ll fill in a short request form, and you’ll get an email as soon as books open.</p>
    <a href="{{ $waitlistRequestFormUrl }}" class="bbtn pri" id="waitlistRequestContinueBtn" style="margin-top:18px;text-decoration:none">
      Continue to request form <span class="ms">arrow_forward</span>
    </a>
    </div>
  </div>

@include('partials.bookpay-public-api')
<script src="{{ asset('new-ui-design-assets/guest-waitlist.js') }}"></script>
@if(! $lookIsCustom)
<script src="{{ asset('new-ui-design-assets/look.js') }}?v=3"></script>
@endif
  <script>
(function () {
    const waitlistArtistUsername = @json($userDetail->user_name);
    const waitlistUsesRequestForm = @json($waitlistUsesRequestForm);
    const waitlistRequestFormUrl = @json($waitlistRequestFormUrl);
  const artistDisplayName = @json($artistPublicName);
    const csrfToken = @json(csrf_token());
      const portfolio = @json($portfolioForJs);
  const requestSimilarBaseUrl = @json($requestCustomUrl);
  const profileLink = @json($profileHostPath);
  const avatarUrl = @json($avatarUrl);
  const multiStudio = @json($multiStudio);
  const workplaces = @json($workplaceCards);
  const lookState = @json($lookState);
  const isPreview = @json($isLookPreview);
  const lookBoxHTML = @json($lookBoxHtml);
  const booksClosed = @json($booksClosed);
  const acceptsCustom = @json($acceptsCustom);
  const hasVisibleDesigns = @json($hasVisibleDesigns);
  const lookIsCustom = @json($lookIsCustom);

  const $ = (id) => document.getElementById(id);

  try {
    if (isPreview) {
      /* BPLook.get already reads bp-look-artist-preview */
    } else {
      sessionStorage.setItem('bp-look-artist', JSON.stringify(lookState));
    }
  } catch (e) {}

  function openBooksWaitlist() {
    if (waitlistUsesRequestForm) {
      openModal('waitlistRequestModal');
      return;
    }
    openModal('waitlistModal');
  }
  window.openBooksWaitlist = openBooksWaitlist;

  function requestSimilarTattoo(portfolioId) {
    if (booksClosed) {
      openBooksWaitlist();
      return;
    }
    const id = parseInt(portfolioId, 10);
    if (!id || !requestSimilarBaseUrl) return;
    const url = new URL(requestSimilarBaseUrl, window.BOOKPAY_BASE_URL || window.location.origin);
    url.searchParams.set('portfolio', String(id));
    window.location.href = url.toString();
  }
  window.requestSimilarTattoo = requestSimilarTattoo;

  const ARTIST_TAB_IDS = ['designs', 'portfolio', 'guest-spots', 'policies', 'faq'];

  function isValidArtistTab(tab) {
    return ARTIST_TAB_IDS.includes(tab) && !!document.getElementById('tab-' + tab);
  }

  function tabFromHash() {
    const raw = (location.hash || '').replace(/^#/, '').trim().toLowerCase();
    return isValidArtistTab(raw) ? raw : null;
  }

  function setTabHash(tab) {
    const next = '#' + tab;
    if (location.hash === next) return;
    history.replaceState(null, '', next);
  }

  function switchTab(tab, options) {
    const opts = options || {};
    const updateHash = opts.updateHash !== false;
    if (!isValidArtistTab(tab)) return;

    document.querySelectorAll('.tab-panel').forEach((el) => el.classList.remove('active'));
    document.querySelectorAll('.tab').forEach((el) => el.setAttribute('aria-selected', 'false'));

      const content = document.getElementById('content-' + tab);
      if (content) content.classList.add('active');
      const btn = document.getElementById('tab-' + tab);
    if (btn) btn.setAttribute('aria-selected', 'true');
    if (updateHash) setTabHash(tab);
    syncMcta();
  }
  window.switchTab = switchTab;

  function applyTabFromHash() {
    const tab = tabFromHash();
    if (!tab) return false;
    switchTab(tab, { updateHash: false });
    return true;
  }

  document.querySelectorAll('.tab[data-tab]').forEach((btn) => {
    btn.addEventListener('click', () => switchTab(btn.dataset.tab));
  });

  window.addEventListener('hashchange', applyTabFromHash);
  if (applyTabFromHash()) {
    const nav = $('artistDesignsSection');
    if (nav) requestAnimationFrame(() => nav.scrollIntoView({ behavior: 'smooth', block: 'start' }));
  }

  function openGuestSpots() {
    if (!isValidArtistTab('guest-spots')) return;
    switchTab('guest-spots');
    const target = $('artistDesignsSection') || $('content-guest-spots');
    if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function browseDesigns() {
    if (!isValidArtistTab('designs')) return;
      switchTab('designs');
    const target = $('artistDesignsSection') || $('content-designs');
    if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  function toggleFaqItem(btn) {
    const item = btn.closest('.fq');
    if (!item) return;
    const ans = item.querySelector('.fa');
    const open = btn.getAttribute('aria-expanded') === 'true';
    btn.setAttribute('aria-expanded', open ? 'false' : 'true');
    if (ans) ans.hidden = open;
    const icon = btn.querySelector('.ms');
    if (icon) icon.textContent = open ? 'add' : 'remove';
  }
  window.toggleFaqItem = toggleFaqItem;

    function openModal(id) {
    const modal = $(id);
    if (!modal) return;
    modal.hidden = false;
    document.documentElement.style.overflow = 'hidden';
    }

    function closeModal(id) {
    const modal = $(id);
    if (!modal) return;
    modal.hidden = true;
    document.documentElement.style.overflow = '';
    if (id === 'waitlistModal') resetWaitlistModal();
    }

    function resetWaitlistModal() {
    $('waitlistFormView')?.removeAttribute('hidden');
    const success = $('waitlistSuccessView');
    if (success) success.hidden = true;
    $('waitlistForm')?.reset();
      clearWaitlistErrors();
    const submitBtn = $('waitlistSubmitBtn');
      if (submitBtn) {
        submitBtn.disabled = false;
      submitBtn.innerHTML = '<span class="ms">notifications</span>Notify me';
      }
    }

    function clearWaitlistErrors() {
      ['waitlistNameError', 'waitlistEmailError', 'waitlistFormError'].forEach((id) => {
      const el = $(id);
      if (el) { el.textContent = ''; el.hidden = true; }
    });
    ['waitlistName', 'waitlistEmail'].forEach((id) => $(id)?.classList.remove('border-error'));
    }

    function showWaitlistFieldError(fieldId, errorId, message) {
    const field = $(fieldId);
    const error = $(errorId);
      if (field) field.classList.add('border-error');
    if (error) { error.textContent = message; error.hidden = false; }
    }

  window.openPortfolioModal = function (index) {
      const p = portfolio[index];
    if (!p) return;
    if ($('content-portfolio')) switchTab('portfolio');

    const img = $('portfolioModalImage');
    if (img) img.style.backgroundImage = p.image ? "url('" + p.image + "')" : '';
    $('portfolioModalTitle').textContent = p.title || '';
    const desc = $('portfolioModalDesc');
    if (desc) {
      desc.textContent = p.desc || '';
      desc.hidden = !p.desc;
    }
    $('portfolioModalStyle').textContent = p.style || '';
    $('portfolioModalPlacement').textContent = p.placement || 'Anywhere';
    $('portfolioModalColors').textContent = p.colors || '';
    const tagsEl = $('portfolioModalTags');
    const tags = Array.isArray(p.tags) ? p.tags : [];
    tagsEl.innerHTML = tags.map((t) => '<span class="tg">' + t + '</span>').join('');
    const similarBtn = $('portfolioModalSimilarBtn');
    if (similarBtn) {
      if (booksClosed) {
        similarBtn.onclick = () => openBooksWaitlist();
        } else {
        similarBtn.onclick = () => requestSimilarTattoo(p.id);
      }
    }
      openModal('portfolioDetailModal');
  };

  document.addEventListener('click', function (e) {
    const closeBtn = e.target.closest('[data-close]');
    if (closeBtn) {
      const mb = closeBtn.closest('.mb');
      if (mb) { mb.hidden = true; document.documentElement.style.overflow = ''; if (mb.id === 'waitlistModal') resetWaitlistModal(); }
      return;
    }
    if (e.target.classList.contains('mb')) {
      e.target.hidden = true;
      document.documentElement.style.overflow = '';
      if (e.target.id === 'waitlistModal') resetWaitlistModal();
      return;
    }

    const act = e.target.closest('[data-act]');
    if (!act) return;
    const a = act.dataset.act;
    if (a === 'notify') {
      if (waitlistUsesRequestForm) {
        return openModal('waitlistRequestModal');
      }
      return openModal('waitlistModal');
    }
    if (a === 'custom') return (window.location.href = requestSimilarBaseUrl);
    if (a === 'designs') return browseDesigns();
    if (a === 'guest') return openGuestSpots();
    if (a === 'gwl-any' && window.bpGuestWL) return bpGuestWL.open({ artist: artistDisplayName, artistUsername: waitlistArtistUsername });
    if (a === 'gwl-planned' && window.bpGuestWL) return bpGuestWL.open({
      artist: artistDisplayName,
      artistUsername: waitlistArtistUsername,
      city: act.dataset.c,
      guestSpotId: act.dataset.gsId ? Number(act.dataset.gsId) : null,
      kind: 'planned',
    });
  });

  document.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape') return;
    ['portfolioDetailModal', 'waitlistModal', 'waitlistRequestModal'].forEach((id) => {
      const el = $(id);
      if (el && !el.hidden) closeModal(id);
    });
  });

    async function submitWaitlist() {
      clearWaitlistErrors();
    const name = String($('waitlistName')?.value || '').trim();
    const email = String($('waitlistEmail')?.value || '').trim();
      let hasError = false;
    if (!name) { showWaitlistFieldError('waitlistName', 'waitlistNameError', 'Please enter your name.'); hasError = true; }
    if (!email) { showWaitlistFieldError('waitlistEmail', 'waitlistEmailError', 'Please enter your email address.'); hasError = true; }
    else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { showWaitlistFieldError('waitlistEmail', 'waitlistEmailError', 'Please enter a valid email address.'); hasError = true; }
      if (hasError) return;

    const submitBtn = $('waitlistSubmitBtn');
    if (submitBtn) { submitBtn.disabled = true; submitBtn.textContent = 'Submitting...'; }

    try {
      const response = await fetch(bookpayUrl('/api/public/submit-waitlist'), {
          method: 'POST',
        credentials: 'include',
          headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
          },
        body: JSON.stringify({ artist_username: waitlistArtistUsername, name, email }),
      });
      const data = await response.json().catch(() => ({}));
        if (!response.ok) {
          if (response.status === 422 && data.errors) {
          if (data.errors.name) showWaitlistFieldError('waitlistName', 'waitlistNameError', data.errors.name[0]);
          if (data.errors.email) showWaitlistFieldError('waitlistEmail', 'waitlistEmailError', data.errors.email[0]);
          } else {
          const formError = $('waitlistFormError');
          if (formError) { formError.textContent = (data && data.message) || 'Unable to join the waitlist. Please try again.'; formError.hidden = false; }
          }
          return;
        }
      $('waitlistFormView').hidden = true;
      $('waitlistSuccessView').hidden = false;
      } catch (err) {
      const formError = $('waitlistFormError');
      if (formError) { formError.textContent = 'Unable to join the waitlist. Please try again.'; formError.hidden = false; }
      } finally {
        if (submitBtn) {
          submitBtn.disabled = false;
        submitBtn.innerHTML = '<span class="ms">notifications</span>Notify me';
      }
    }
  }
  window.submitWaitlist = submitWaitlist;

  function syncMcta() {
    const mcta = $('mcta');
    if (!mcta) return;
    if (booksClosed) {
      mcta.innerHTML = '<button type="button" class="bbtn pri" data-act="notify"><span class="ms">notifications</span>Notify me when open</button>';
    } else if (acceptsCustom) {
      mcta.innerHTML = '<a class="bbtn pri" href="' + requestSimilarBaseUrl + '"><span class="ms">brush</span>Request custom tattoo</a>';
    } else if (hasVisibleDesigns) {
      mcta.innerHTML = '<button type="button" class="bbtn pri" data-act="designs"><span class="ms">palette</span>Browse designs</button>';
    } else {
      mcta.innerHTML = '';
    }
  }
  syncMcta();

  const book = $('book');
  if (book && 'IntersectionObserver' in window) {
    new IntersectionObserver(function (es) {
      es.forEach(function (e) {
        $('mcta')?.classList.toggle('show', !e.isIntersecting && e.boundingClientRect.top < 0);
      });
    }).observe(book);
  }

  document.addEventListener('click', function (e) {
    const t = e.target.closest('[data-photo]');
    if (!t || !window.BPLook) return;
    BPLook.viewer({
      img: avatarUrl || '',
      name: artistDisplayName,
      link: profileLink,
      qr: 'https://api.qrserver.com/v1/create-qr-code/?size=280x280&data=' + encodeURIComponent('https://' + profileLink),
      file: waitlistArtistUsername + '-qr-code.png',
    });
  });
  document.addEventListener('keydown', function (e) {
    if ((e.key === 'Enter' || e.key === ' ') && e.target.closest('[data-photo]')) {
      e.preventDefault();
      e.target.closest('[data-photo]').click();
    }
  });

  function studioLine() {
    const el = $('cstudio');
    if (!el || !workplaces.length) return;
    if (workplaces.length === 1) {
      el.innerHTML = '<span class="ms">location_on</span><span class="t">' + workplaces[0].name + '</span>';
          return;
        }
    el.innerHTML = '<button type="button" class="cst-stb" aria-expanded="false" aria-controls="cstpop"><span class="ms">location_on</span><span class="t">' + workplaces[0].name + ' + ' + (workplaces.length - 1) + ' more</span><span class="ms" style="font-size:18px">expand_more</span></button>';
  }

  document.addEventListener('click', function (e) {
    const b = e.target.closest('.cst-stb');
    const pop = $('cstpop');
    if (b && pop) {
      const open = pop.hidden;
      pop.hidden = !open;
      b.setAttribute('aria-expanded', open ? 'true' : 'false');
      if (open) {
        pop.innerHTML = workplaces.map(function (w) {
          const maps = w.maps
            ? '<a href="' + w.maps + '" target="_blank" rel="noopener noreferrer">Map</a>'
            : '';
          return '<div class="it">' + maps + '<b>' + w.name + '</b><small>' + (w.address || '') + '</small></div>';
        }).join('');
      }
      return;
    }
    if (pop && !e.target.closest('.cst-meta')) {
      pop.hidden = true;
      const stb = document.querySelector('.cst-stb');
      if (stb) stb.setAttribute('aria-expanded', 'false');
    }
  });

  if (window.BPLook) {
    const LOOK = BPLook.publicPage({
      who: 'artist',
      base: '',
      before: document.querySelector('.tabbar') || $('panels'),
      book: $('book'),
      bookHome: $('herohome'),
      std: [$('banner'), $('stdhero')].filter(Boolean),
      boxHTML: lookBoxHTML,
      onBuild: function () { studioLine(); },
      onMode: function (mode, S) {
        applyScheme(S);
        if (mode === 'standard' && S && S.banner) {
          var b = $('banner');
          b.style.backgroundColor = (BPLook.parseScheme ? BPLook.parseScheme(S.scheme)[0] : String(S.scheme || '').split(',')[0]) || '#1A1A2E';
          b.style.backgroundImage = "url('" + BPLook.res(S.banner, '') + "')";
          b.style.backgroundPosition = 'center top';
          b.style.backgroundSize = 'cover';
          b.style.backgroundRepeat = 'no-repeat';
        } else if (mode === 'standard') {
          const g = BPLook.parseScheme(S && S.scheme);
          var b = $('banner');
          b.style.backgroundColor = g[0] || '#1A1A2E';
          b.style.backgroundImage = 'linear-gradient(120deg,' + (g[0] || '#1A1A2E') + ',' + (g[1] || g[0] || '#3A3A5C') + ')';
          b.style.backgroundPosition = 'center top';
          b.style.backgroundSize = 'cover';
          b.style.backgroundRepeat = 'no-repeat';
        }
      },
    });

    function applyScheme(S) {
      const g = BPLook.parseScheme(S && S.scheme);
      const root = document.documentElement.style;
      root.setProperty('--a1', g[0] || '#1A1A2E');
      root.setProperty('--a2', g[1] || g[0] || '#3A3A5C');
      root.setProperty('--acc', g[0] || '#1A1A2E');
      root.setProperty('--accl', g[2] || '#ECEBF3');
      try {
        const st = BPLook.style(S || LOOK.state);
        if (st && st.btn) root.setProperty('--btnt', st.btn);
      } catch (e) {}
    }
    applyScheme(LOOK.state);
    if (LOOK.state.mode === 'standard' && LOOK.state.banner) {
      var b = $('banner');
      b.style.backgroundColor = BPLook.parseScheme(LOOK.state.scheme)[0] || '#1A1A2E';
      b.style.backgroundImage = "url('" + BPLook.res(LOOK.state.banner, '') + "')";
      b.style.backgroundPosition = 'center top';
      b.style.backgroundSize = 'cover';
      b.style.backgroundRepeat = 'no-repeat';
    }
  }
  function revealCustomLook() {
    if (!lookIsCustom) return;
    document.documentElement.classList.add('look-ready');
    document.documentElement.classList.remove('look-pending');
  }
  revealCustomLook();
  setTimeout(revealCustomLook, 1200);
    })();
  </script>
</body>
</html>
