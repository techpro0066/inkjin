<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="color-scheme" content="only light">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  @include('layouts.partials.google-analytics')
  @php
    $studioName = trim((string) ($studio->name ?? ''));
    if ($studioName === '') {
        $studioName = 'Studio';
    }
    $studioUsername = trim((string) ($studio->username ?? ''));
    $studioHandle = $studioUsername !== '' ? '@'.$studioUsername : '';
    $studioInitials = strtoupper(substr(preg_replace('/\s+/', '', $studioName) ?: 'ST', 0, 2));

    $lookState = \App\Support\PageLook::stateFromStudio($studio);
    $isLookPreview = request()->boolean('preview');
    $lookIsCustom = ($lookState['mode'] ?? '') === 'custom';
    $lookBoxStyle = \App\Support\PageLook::boxStyleFromState($lookState);
    [$schemePrimary, $schemeMid, $schemeTint] = \App\Support\PageLook::schemeParts(
        (string) ($lookState['scheme'] ?? '')
    );
    $bannerUrl = $lookState['banner'] ?? null;

    $typeLabels = [
        'private' => 'Private studio',
        'tattoo' => 'Tattoo shop',
        'home' => 'Home studio',
        'collective' => 'Collective',
    ];
    $studioTypeKey = trim((string) ($studio->studio_type ?? 'tattoo'));
    $studioTypeLabel = $typeLabels[$studioTypeKey] ?? 'Tattoo shop';

    $street = trim(trim((string) ($studio->street_number ?? '')).' '.trim((string) ($studio->street_name ?? '')));
    if ($street === '') {
        $street = trim((string) ($studio->address ?? ''));
    }
    $cityLine = implode(', ', array_filter([
        trim((string) ($studio->postal_code ?? '')),
        trim((string) ($studio->city ?? '')),
        trim((string) ($studio->country ?? '')),
    ]));
    $fullAddress = implode(', ', array_filter([$street, $cityLine]));

    $phone = trim((string) ($studio->business_phone ?? ''));
    $established = trim((string) ($studio->tattooing_since ?? ''));
    $description = trim((string) ($studio->description ?? ''));
    $acceptsWalkIns = (bool) ($studio->accepts_walk_ins ?? false);
    $walkInsNote = trim((string) ($studio->walk_ins_note ?? ''));
    $mapsLink = trim((string) ($studio->google_maps_link ?? ''));
    if ($mapsLink === '' && $fullAddress !== '') {
        $mapsLink = 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($fullAddress);
    }
    $showMap = (bool) ($studio->show_map ?? true) && $fullAddress !== '';

    $formatStyleLabel = static function (string $style): string {
        return ucwords(str_replace(['-', '_'], ' ', $style));
    };

    $studioStyleLabels = [];
    foreach ((array) ($studio->tattoo_styles ?? []) as $style) {
        $style = trim((string) $style);
        if ($style === '') {
            continue;
        }
        $label = $formatStyleLabel($style);
        if (! in_array($label, $studioStyleLabels, true)) {
            $studioStyleLabels[] = $label;
        }
    }

    $extraServicesPayload = is_array($studio->extra_services ?? null) ? $studio->extra_services : [];
    $extraServiceList = is_array($extraServicesPayload['services'] ?? null)
        ? $extraServicesPayload['services']
        : (array_is_list($extraServicesPayload) ? $extraServicesPayload : []);
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

    $logoPath = trim((string) ($studio->logo_url ?? ''));
    if ($logoPath === '') {
        $logoPath = trim((string) ($studio->image_url ?? ''));
    }
    $logoUrl = $logoPath !== ''
        ? (str_starts_with($logoPath, 'http') ? $logoPath : asset(ltrim($logoPath, '/')))
        : null;

    $links = is_array($studio->links ?? null) ? $studio->links : [];
    $instagramHref = \App\Support\SocialLinks::publicHref('instagram', $links['instagram'] ?? null);
    $websiteHref = \App\Support\SocialLinks::publicHref('website', $links['website'] ?? null);
    $tiktokHref = \App\Support\SocialLinks::publicHref('tiktok', $links['tiktok'] ?? null);
    $youtubeHref = \App\Support\SocialLinks::publicHref('youtube', $links['youtube'] ?? null);
    $facebookHref = \App\Support\SocialLinks::publicHref('facebook', $links['facebook'] ?? null);
    $hasSocial = $instagramHref || $websiteHref || $tiktokHref || $youtubeHref || $facebookHref;

    $studioArtists = $studioArtists ?? collect();
    $artistCount = $studioArtists->count();
    $artistCountLabel = $artistCount === 1
        ? '1 artist works here'
        : ($artistCount > 0 ? $artistCount.' artists work here' : 'Artists work here');

    $workItems = $workItems ?? collect();
    $showArtistStyles = (bool) ($studio->show_artist_styles ?? true);
    $showGuestArtists = (bool) ($studio->show_guest_artists ?? true);
    $showFlash = (bool) ($showFlash ?? $studio->show_artists_flash ?? true);
    $showPort = (bool) ($showPort ?? $studio->show_artists_portfolio ?? true);
    $hasFlashWork = $workItems->contains(fn ($i) => ($i['kind'] ?? '') === 'flash');
    $hasPortWork = $workItems->contains(fn ($i) => ($i['kind'] ?? '') === 'port');
    $hasStudioWork = $workItems->contains(fn ($i) => ($i['kind'] ?? '') === 'studio');
    $hasWork = $workItems->count() > 0;

    $artistStyleLabels = [];
    if ($showArtistStyles) {
        foreach ($studioArtists as $artist) {
            foreach ((array) ($artist['all_styles'] ?? $artist['styles'] ?? []) as $style) {
                $style = trim((string) $style);
                if ($style === '') {
                    continue;
                }
                if (! in_array($style, $artistStyleLabels, true) && ! in_array($style, $studioStyleLabels, true)) {
                    $artistStyleLabels[] = $style;
                }
            }
        }
    }
    $styleLabels = array_values(array_merge($studioStyleLabels, $artistStyleLabels));

    $profileUrl = $studioUsername !== ''
        ? route('public.artist', ['username' => $studioUsername])
        : url('/');
    $profileHostPath = preg_replace('#^https?://#', '', $profileUrl);

    $escAttr = static fn ($v) => e((string) $v);
    if ($bannerUrl) {
        $bannerStyle = 'background-color:'.$escAttr($schemePrimary)
            .';background-image:url('.$escAttr($bannerUrl).')'
            .';background-position:center top;background-size:cover;background-repeat:no-repeat';
    } else {
        $bannerStyle = 'background-color:'.$escAttr($schemePrimary)
            .';background-image:linear-gradient(120deg,'.$escAttr($schemePrimary).','.$escAttr($schemeMid).')'
            .';background-position:center top;background-size:cover;background-repeat:no-repeat';
    }

    $logoStyle = $logoUrl
        ? 'cursor:pointer;background:transparent url('.$escAttr($logoUrl).') center/cover no-repeat;box-shadow:inset 0 0 0 1px #E8E1EC'
        : 'cursor:pointer;background:linear-gradient(135deg,'.$escAttr($schemePrimary).','.$escAttr($schemeMid).')';

    $metaDescription = $description !== ''
        ? $description
        : ($studioName.' — tattoo studio'.($cityLine !== '' ? ' in '.$cityLine : '').'. Book ahead or walk in.');

    $boxHtmlStyles = collect($styleLabels)->map(fn ($l) => '<span>'.e($l).'</span>')->implode('');
    $lookBoxHtml = ($logoUrl
            ? '<button type="button" class="cst-av sq" data-photo aria-label="Open '.e($studioName).'\'s logo" style="background-image:url(\''.e($logoUrl).'\')"></button>'
            : '<button type="button" class="cst-av sq" data-photo aria-label="Open '.e($studioName).'\'s logo" style="background:linear-gradient(135deg,'.e($schemePrimary).','.e($schemeMid).');display:flex;align-items:center;justify-content:center;font-weight:800;font-size:40px;color:#fff">'.e($studioInitials).'</button>')
        .'<h1>'.e($studioName).'</h1>'
        .($studioHandle !== '' ? '<div class="cst-tag">'.e($studioHandle).'</div>' : '')
        .($boxHtmlStyles !== '' ? '<div class="cst-sty">'.$boxHtmlStyles.'</div>' : '')
        .($extraServicesLine !== '' ? '<div class="cst-svc"><b>Also:</b> '.e($extraServicesLine).'</div>' : '')
        .($description !== '' ? '<p class="cst-bio">'.e($description).'</p>' : '');
  @endphp
  <title>{{ $studioName }} · Book a tattoo</title>
  <meta name="description" content="{{ $metaDescription }}">
  <link rel="icon" href="{{ asset('design/images/icons/favicon.png') }}">
  @include('public.partials.studio-page-css')
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
    .sp-b.pri,.wf button.on{background:var(--acc);border-color:var(--acc);color:var(--btnt)}
    .sp-b.pri .ms,.wf button.on .ms{color:inherit}
    .bbtn.pri{background:var(--acc);color:var(--btnt)}
    .bbtn.pri .ms{color:inherit}
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
    try { sessionStorage.setItem('bp-look-studio', @json($lookState)); } catch (e) {}
  </script>
  <script src="{{ asset('new-ui-design-assets/look.js') }}"></script>
  @endif
</head>
<body class="{{ trim(($lookIsCustom ? 'cst ' : '').($isLookPreview ? 'bp-prev' : '')) }}" data-look-who="studio">
  <div class="sp-ban" id="banner" style="{{ $bannerStyle }}"></div>

  <div class="sp-wrap">
    <div class="sp-hero" id="herohome">
      <div>
        <div class="sp-logo" data-photo role="button" tabindex="0" aria-label="Open {{ $studioName }}'s logo" style="{{ $logoStyle }}">@if(! $logoUrl){{ $studioInitials }}@endif</div>
        <h1 class="sp-name">{{ $studioName }}</h1>
        @if($studioHandle !== '')
          <div class="sp-handle">{{ $studioHandle }}</div>
        @endif
        @if($description !== '')
          <p class="sp-desc">{{ $description }}</p>
        @endif

        <div class="sp-mi-list">
          <div class="sp-mi">
            <span class="ms">storefront</span>
            <div>
              <b>{{ $studioTypeLabel }}</b>
              @if($fullAddress !== '')
                <div class="sp-sub">{{ $fullAddress }}@if($showMap) · <a href="#findus" style="text-decoration:underline">Map</a>@endif</div>
              @endif
            </div>
          </div>
          @if($acceptsWalkIns)
            <div class="sp-mi">
              <span class="ms">directions_walk</span>
              <div>
                <b>Walk-ins welcome</b>
                @if($walkInsNote !== '')
                  <div class="sp-sub">{{ $walkInsNote }}</div>
                @endif
              </div>
            </div>
          @endif
          @if($established !== '')
            <div class="sp-mi"><span class="ms">calendar_month</span><div>Est. {{ $established }}</div></div>
          @endif
          @if($phone !== '')
            <div class="sp-mi"><span class="ms">call</span><div><a href="tel:{{ preg_replace('/\s+/', '', $phone) }}">{{ $phone }}</a></div></div>
          @endif
          @if(count($styleLabels))
            <div class="sp-mi">
              <span class="ms">palette</span>
              <div class="chips">
                @foreach($styleLabels as $i => $label)
                  <span class="chip">{{ $label }}</span>
                @endforeach
              </div>
            </div>
          @endif
          @if($extraServicesLine !== '')
            <div class="sp-mi">
              <span class="ms">add_circle</span>
              <div><b>Also:</b> {{ $extraServicesLine }}</div>
            </div>
          @endif
        </div>

        @if($hasSocial)
          <div class="sp-soc">
            @if($instagramHref)
              <a href="{{ $instagramHref }}" target="_blank" rel="noopener noreferrer" aria-label="Instagram"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/></svg></a>
            @endif
            @if($tiktokHref)
              <a href="{{ $tiktokHref }}" target="_blank" rel="noopener noreferrer" aria-label="TikTok"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M16.6 2h-3.3v13.1a2.9 2.9 0 1 1-2.1-2.8V8.9a6.3 6.3 0 1 0 5.4 6.2V8.4a7.9 7.9 0 0 0 4.4 1.3V6.4a4.5 4.5 0 0 1-4.4-4.4z"/></svg></a>
            @endif
            @if($youtubeHref)
              <a href="{{ $youtubeHref }}" target="_blank" rel="noopener noreferrer" aria-label="YouTube"><span class="ms" style="font-size:19px">play_circle</span></a>
            @endif
            @if($facebookHref)
              <a href="{{ $facebookHref }}" target="_blank" rel="noopener noreferrer" aria-label="Facebook"><span class="ms" style="font-size:19px">public</span></a>
            @endif
            @if($websiteHref)
              <a href="{{ $websiteHref }}" target="_blank" rel="noopener noreferrer" aria-label="Website"><span class="ms" style="font-size:19px">language</span></a>
            @endif
          </div>
        @endif
      </div>

      <div class="sp-book" id="book">
        <div class="sp-bk-h">Book with us</div>
        <p>Tell us your idea. We match you with the right artist, or pick one yourself.</p>
        <a class="sp-b pri" href="{{ route('public.studio-request', ['user_name' => $studio->username]) }}"><span class="ms" style="font-size:19px;vertical-align:-4px;margin-right:6px">brush</span>Book with us</a>
        <div class="sp-bk-n"><span class="ms">group</span>{{ $artistCountLabel }}</div>
      </div>
    </div>

    @if($artistCount > 0)
      <section class="sp-sec" id="artists">
        <h2>Our artists</h2>
        <p class="s">Pick an artist to see their work and book with them.</p>
        <div class="sp-grid">
          @foreach($studioArtists as $artist)
            <article class="sp-card">
              @if(! empty($artist['avatar']))
                <div class="sp-av" style="background-image:url('{{ $artist['avatar'] }}')"></div>
              @else
                <div class="sp-av">{{ $artist['initials'] }}</div>
              @endif
              <h3>{{ $artist['name'] }}</h3>
              <div class="sp-h">{{ trim(($artist['handle'] ?? '').' · '.($artist['relationship'] ?? ''), ' ·') }}</div>
              @if(! empty($artist['styles']))
                <div class="sp-tags">
                  @foreach($artist['styles'] as $style)
                    <span>{{ $style }}</span>
                  @endforeach
                </div>
              @endif
              <div class="sp-btns">
                @if(! empty($artist['request_url']))
                  <a class="sp-b pri" href="{{ $artist['request_url'] }}">Request custom tattoo</a>
                @endif
                @if(! empty($artist['profile_url']))
                  @if(! empty($artist['has_designs']))
                    <a class="sp-b" href="{{ $artist['profile_url'] }}#designs">View available designs</a>
                  @else
                    <a class="sp-b" href="{{ $artist['profile_url'] }}">View artist's page</a>
                  @endif
                @endif
              </div>
            </article>
          @endforeach
        </div>
      </section>
    @endif

    @if($hasWork)
      <section class="sp-sec" id="work">
        <h2>Our work</h2>
        <p class="s">Flash designs and tattoos by our artists, and pieces from the studio.</p>
        <div class="wf" role="tablist" id="workFilters">
          <button type="button" class="on" data-f="all">All</button>
          @if($showFlash && $hasFlashWork)<button type="button" data-f="flash">Flash designs</button>@endif
          @if($showPort && $hasPortWork)<button type="button" data-f="port">Portfolio</button>@endif
          @if($hasStudioWork)<button type="button" data-f="studio">Studio</button>@endif
        </div>
        <div class="wg" id="workGrid">
          @foreach($workItems as $item)
            <div class="wk" data-k="{{ $item['kind'] }}">
              <span class="wi" @if(! empty($item['image'])) style="background-image:url('{{ $item['image'] }}')" @endif></span>
              <span class="wt">
                <b>{{ $item['title'] }}</b>
                <span class="wb">
                  @if(($item['kind'] ?? '') === 'studio')
                    <span class="ms">storefront</span>{{ $item['artist_name'] }}
                  @else
                    <span class="wav" @if(! empty($item['artist_avatar'])) style="background-image:url('{{ $item['artist_avatar'] }}')" @endif>@if(empty($item['artist_avatar'])){{ $item['artist_initials'] }}@endif</span>by {{ $item['artist_name'] }}
                  @endif
                </span>
                @if(! empty($item['price_label']))
                  <span class="wp">{{ $item['price_label'] }}</span>
                @endif
                @if(! empty($item['cta_url']))
                  <a class="wact{{ ! empty($item['cta_pri']) ? ' pri' : '' }}" href="{{ $item['cta_url'] }}">
                    @if(in_array($item['kind'] ?? '', ['port', 'studio'], true))<span class="ms">brush</span>@endif
                    {{ $item['cta_label'] }}
                  </a>
                @endif
              </span>
            </div>
          @endforeach
        </div>
      </section>
    @endif

    @php $guestArtists = $guestArtists ?? collect(); @endphp
    @if($showGuestArtists && $guestArtists->count() > 0)
      <section class="sp-sec" id="guest-artists">
        <h2>Guest artists</h2>
        <p class="s">Visiting artists working at {{ $studioName }}.</p>
        @foreach($guestArtists as $guest)
          <div class="sp-guest">
            <div class="sp-date"><small>{{ $guest['month'] ?? '' }}</small><b>{{ $guest['day'] ?? '' }}</b></div>
            <div style="flex:1">
              <b style="font-size:16px">{{ $guest['name'] ?? '' }}</b>
              @if(! empty($guest['meta']))
                <div style="color:#6B6470;font-size:13.5px;margin-top:2px">{{ $guest['meta'] }}</div>
              @endif
            </div>
            @if(! empty($guest['url']))
              <a class="sp-b pri" href="{{ $guest['url'] }}" style="padding:10px 18px">Reserve</a>
            @endif
          </div>
        @endforeach
      </section>
    @endif

    <section class="sp-sec" id="findus">
      <h2>Find us</h2>
      <p class="s">Visits by appointment. Book first, then come to the studio.</p>
      <div class="fu{{ $showMap ? '' : ' nomap' }}">
        @if($showMap)
          <div class="fu-map" aria-hidden="true">
            <svg viewBox="0 0 600 340" preserveAspectRatio="xMidYMid slice" role="img" aria-label="Map area near {{ $studioName }}" style="display:block;width:100%;height:100%">
              <rect width="600" height="340" fill="#EEF0EC"/>
              <rect x="360" y="20" width="150" height="110" rx="10" fill="#D6E9CF"/>
              <rect x="40" y="210" width="120" height="90" rx="10" fill="#D6E9CF"/>
              <g stroke="#fff" stroke-linecap="round">
                <path d="M-10 150 L610 110" stroke-width="18"/>
                <path d="M-10 260 L610 230" stroke-width="12"/>
                <path d="M180 -10 L230 350" stroke-width="16"/>
                <path d="M330 -10 L300 350" stroke-width="10"/>
              </g>
              <g transform="translate(300 170)">
                <circle r="26" fill="{{ $schemePrimary }}" opacity=".12"/>
                <path d="M0 8 C-10 -4 -14 -9 -14 -16 A14 14 0 1 1 14 -16 C14 -9 10 -4 0 8 Z" fill="{{ $schemePrimary }}" transform="translate(0 -8)"/>
                <circle cy="-24" r="5.5" fill="#fff"/>
              </g>
            </svg>
          </div>
        @endif
        <div class="fu-info">
          <div class="fu-r">
            <span class="ms">storefront</span>
            <div>
              <b>{{ $studioName }}</b>
              @if($fullAddress !== '')
                <div class="sp-sub">{{ $fullAddress }}</div>
              @endif
            </div>
          </div>
          @if($phone !== '')
            <div class="fu-r"><span class="ms">call</span><div><a href="tel:{{ preg_replace('/\s+/', '', $phone) }}">{{ $phone }}</a></div></div>
          @endif
          @if($mapsLink !== '')
            <a class="sp-b pri fu-dir" href="{{ $mapsLink }}" target="_blank" rel="noopener noreferrer"><span class="ms" style="font-size:19px;vertical-align:-4px;margin-right:6px">directions</span>Get directions</a>
          @endif
        </div>
      </div>
    </section>

    <footer class="sp-footer">
      <div class="flogo">
        <b>bookpay</b>
        <small>Tattoo artist platform<br>by Inkjin</small>
      </div>
      <nav>
        <a href="https://inkjin.com/en/privacy" target="_blank" rel="noopener noreferrer">Privacy</a>
        <a href="https://inkjin.com/en/terms" target="_blank" rel="noopener noreferrer">Terms</a>
      </nav>
    </footer>
  </div>

  @if(! $lookIsCustom)
  <script src="{{ asset('new-ui-design-assets/look.js') }}"></script>
  @endif
  <script>
    (function () {
      const $ = (id) => document.getElementById(id);
      const lookState = @json($lookState);
      const isPreview = @json($isLookPreview);
      const lookBoxHTML = @json($lookBoxHtml);
      const logoUrl = @json($logoUrl);
      const studioName = @json($studioName);
      const profilePath = @json($profileHostPath);
      const lookIsCustom = @json($lookIsCustom);

      try {
        if (!isPreview) {
          sessionStorage.setItem('bp-look-studio', JSON.stringify(lookState));
        }
      } catch (e) {}

      const filters = $('workFilters');
      if (filters) {
        filters.addEventListener('click', function (e) {
          const btn = e.target.closest('button[data-f]');
          if (!btn) return;
          filters.querySelectorAll('button').forEach(function (b) { b.classList.toggle('on', b === btn); });
          const f = btn.getAttribute('data-f');
          document.querySelectorAll('#workGrid .wk').forEach(function (card) {
            card.hidden = f !== 'all' && card.getAttribute('data-k') !== f;
          });
        });
      }

      document.addEventListener('click', function (e) {
        if (e.target.closest('[data-photo]') && window.BPLook) {
          BPLook.viewer({
            img: logoUrl || '',
            name: studioName,
            link: profilePath,
          });
        }
      });

      if (window.BPLook) {
        const hero = $('herohome');
        const book = $('book');
        const LOOK = BPLook.publicPage({
          who: 'studio',
          base: '',
          noWrap: true,
          before: hero,
          book: book,
          bookHome: hero,
          std: [document.querySelector('.sp-ban'), hero].filter(Boolean),
          boxHTML: lookBoxHTML,
          onMode: function (mode, S) {
            applyScheme(S);
            var b = document.querySelector('.sp-ban');
            if (!b) return;
            if (mode === 'standard' && S && S.banner) {
              b.style.backgroundColor = (BPLook.parseScheme ? BPLook.parseScheme(S.scheme)[0] : String(S.scheme || '').split(',')[0]) || '#1A1A2E';
              b.style.backgroundImage = "url('" + BPLook.res(S.banner, '') + "')";
              b.style.backgroundPosition = 'center top';
              b.style.backgroundSize = 'cover';
              b.style.backgroundRepeat = 'no-repeat';
            } else if (mode === 'standard') {
              const g = BPLook.parseScheme(S && S.scheme);
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
          var b = document.querySelector('.sp-ban');
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
