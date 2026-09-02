<!DOCTYPE html>
<html lang="en">
<head>
  @include('layouts.partials.google-analytics')
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>{{ $userDetail->publicDisplayName() }} - Tattoo Artist | Inkjin</title>
  <meta name="description" content="Book tattoo designs or request custom work from {{ $userDetail->publicDisplayName() }} at {{ $userDetail->studio_name }}, {{ $userDetail->city }}, {{ $userDetail->country }}.">
  <link rel="icon" href="{{ asset('design/images/icons/favicon.png') }}">
  <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
  @php
    $themeMap = [
      'default' => [ 'primary' => '#310F7A', 'bg' => '#531BC9' ],
      'ocean' => [ 'primary' => '#1565C0', 'bg' => '#4D96E9' ],
      'forest' => [ 'primary' => '#2E7D32', 'bg' => '#5DAB61' ],
      'coral' => [ 'primary' => '#D84315', 'bg' => '#FF7B52' ],
      'midnight' => [ 'primary' => '#1A1A2E', 'bg' => '#42426D' ],
      'golden' => [ 'primary' => '#F57F17', 'bg' => '#FFAC62' ]
    ];
    $selectedThemeKey = $userDetail->personal_page_color ?? 'default';
    $selectedTheme = $themeMap[$selectedThemeKey] ?? $themeMap['default'];
    $showAvailableDesigns = in_array($userDetail->availability_status, ['design_custom', 'design_only'], true);
    $hasVisibleDesigns = $showAvailableDesigns && $artistDesigns->count() > 0;
    $hasPortfolio = $artistPortfolios->count() > 0;
    $displayPolicies = (bool) ($userDetail->display_policies ?? true);
    $displayGuestSpots = (bool) ($userDetail->display_guest_spots ?? false);
    $displayFaq = (bool) ($userDetail->display_faq ?? false);
    $guestSpots = $guestSpots ?? collect();
    $faqs = $faqs ?? collect();
    $hasFaqs = $displayFaq && $faqs->count() > 0;
    $designsTabActive = $hasVisibleDesigns;
    $portfolioTabActive = ! $hasVisibleDesigns && $hasPortfolio;
    $guestSpotsTabActive = $displayGuestSpots && ! $hasVisibleDesigns && ! $hasPortfolio;
    $policiesTabActive = $displayPolicies && ! $hasVisibleDesigns && ! $hasPortfolio && ! $displayGuestSpots;
    $faqTabActive = $hasFaqs && ! $hasVisibleDesigns && ! $hasPortfolio && ! $displayGuestSpots && ! $displayPolicies;
    $showTabsNav = $hasVisibleDesigns || $hasPortfolio || $displayGuestSpots || $displayPolicies || $hasFaqs;

    $policyCopy = \App\Support\ArtistPolicyCopy::for($userDetail);
    $depositPolicyText = $policyCopy['deposit'];
    $reschedulePolicyText = $policyCopy['rescheduling'];
    $cancellationPolicyText = $policyCopy['cancellation'];
    $noShowPolicyText = $policyCopy['no_show'];
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
    $requestSimilarBaseUrl = route('public.request-custom', ['user_name' => $userDetail->user_name]);
  @endphp

  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            "primary": "#310f7a",
            "primary-container": "#482d91",
            "on-primary": "#ffffff",
            "on-primary-container": "#b69fff",
            "surface": "#fdf7ff",
            "surface-container": "#f2ecf5",
            "surface-container-high": "#ece6ef",
            "surface-container-highest": "#e6e0ea",
            "surface-container-low": "#f8f1fb",
            "on-surface": "#1c1b21",
            "on-surface-variant": "#494552",
            "outline": "#7a7583",
            "outline-variant": "#cac4d3",
            "secondary": "#625881",
            "secondary-container": "#ddd0ff",
            "inverse-surface": "#322f36",
            "inverse-on-surface": "#f5eff8",
            "error": "#ba1a1a",
          },
          fontFamily: {
            "sans": ["Plus Jakarta Sans", "system-ui", "sans-serif"],
          },
        },
      },
    }
  </script>
  <style>
    body { font-family: 'Plus Jakarta Sans', sans-serif; }
    .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
    .material-symbols-outlined.filled { font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24; }

    /* Smooth tab transitions */
    .tab-content { display: none; animation: fadeIn 0.2s ease; }
    .tab-content.active { display: block; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(4px); } to { opacity: 1; transform: translateY(0); } }

    /* Modal backdrop */
    .modal-backdrop { background: rgba(0,0,0,0.6); backdrop-filter: blur(4px); }

    /* Card hover */
    .design-card { transition: transform 0.2s ease, box-shadow 0.2s ease; }
    .design-card:hover { transform: translateY(-2px); box-shadow: 0 8px 30px rgba(49,15,122,0.12); }

    /* Portfolio: Request similar tattoo */
    .portfolio-card-media { position: relative; }
    .portfolio-similar-overlay {
      position: absolute;
      inset: 0;
      display: flex;
      align-items: flex-end;
      justify-content: center;
      padding: 0.85rem;
      background: linear-gradient(to top, rgba(28, 27, 33, 0.72) 0%, rgba(28, 27, 33, 0.2) 45%, transparent 70%);
      opacity: 0;
      transition: opacity 0.2s ease;
      pointer-events: none;
    }
    .portfolio-card:hover .portfolio-similar-overlay,
    .portfolio-card:focus-within .portfolio-similar-overlay {
      opacity: 1;
      pointer-events: auto;
    }
    @media (hover: none) {
      .portfolio-similar-overlay {
        opacity: 1;
        pointer-events: auto;
        background: linear-gradient(to top, rgba(28, 27, 33, 0.55) 0%, transparent 55%);
      }
    }
    .portfolio-similar-btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 0.35rem;
      width: 100%;
      padding: 0.55rem 0.9rem;
      border-radius: 9999px;
      border: none;
      background: {{ $selectedTheme['primary'] }};
      color: #ffffff;
      font-size: 0.8125rem;
      font-weight: 700;
      line-height: 1.2;
      cursor: default;
      box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
      transition: background 0.15s ease, transform 0.15s ease;
    }
    .portfolio-similar-btn:hover {
      background: {{ $selectedTheme['bg'] }};
    }
    .portfolio-similar-btn-modal {
      width: 100%;
      padding: 0.85rem 1.25rem;
      font-size: 0.9375rem;
      box-shadow: 0 8px 20px rgba(49, 15, 122, 0.18);
    }

    /* Custom scrollbar for modals */
    .modal-body::-webkit-scrollbar { width: 6px; }
    .modal-body::-webkit-scrollbar-thumb { background: #cac4d3; border-radius: 3px; }

    /* Aspect ratios */
    .aspect-4-5 { aspect-ratio: 4/5; }
    .aspect-1-1 { aspect-ratio: 1/1; }

    .studio-name-color { 
      color: {{ $selectedTheme['primary'] }};
    }

    #btnBrowseDesigns {
      background-color: {{ $selectedTheme['primary'] }};
    }

    #btnBrowseDesigns:hover {
      background-color: {{ $selectedTheme['bg'] }};
    }

    #btnRequestCustom{
      color: {{ $selectedTheme['primary'] }};
      border-color: {{ $selectedTheme['primary'] }};
    }

    #btnRequestCustom:hover {
      color: white;
      background-color: {{ $selectedTheme['bg'] }};
    }

    #tab-designs,
    #tab-portfolio,
    #tab-guest-spots,
    #tab-policies,
    #tab-faq {
      color: {{ $selectedTheme['primary'] }};
    }

    .faq-item {
      border-bottom: 1px solid rgba(202, 196, 211, 0.35);
    }
    .faq-item:last-child { border-bottom: none; }
    .faq-trigger {
      width: 100%;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 1rem;
      text-align: left;
      padding: 1rem 0;
      background: transparent;
      border: none;
      cursor: pointer;
    }
    .faq-trigger:hover .faq-question-text { color: {{ $selectedTheme['primary'] }}; }
    .faq-question-text {
      font-size: 0.95rem;
      font-weight: 600;
      color: #1c1b21;
      line-height: 1.4;
      transition: color 0.15s;
    }
    .faq-icon {
      flex-shrink: 0;
      font-size: 1.35rem;
      color: {{ $selectedTheme['primary'] }};
      font-weight: 500;
      line-height: 1;
      width: 1.5rem;
      text-align: center;
    }
    .faq-answer {
      display: none;
      padding: 0 0 1rem 0;
      font-size: 0.875rem;
      color: #494552;
      line-height: 1.6;
    }
    .faq-item.open .faq-answer { display: block; }

    .border-primary {
      border-color: {{ $selectedTheme['primary'] }};
    }

    .price-color {
      color: {{ $selectedTheme['primary'] }};
    }

    .border-tab-btn{
      border-color: {{ $selectedTheme['primary'] }};
    }

    #artistDesignsSection {
      scroll-margin-top: 1rem;
    }

    .get-this-tattoo-btn{
      background-color: {{ $selectedTheme['primary'] }};
      color: white;
    }

    .get-this-tattoo-btn:hover {
      color: white;
      background-color: {{ $selectedTheme['bg'] }};
    }

  </style>
</head>
<body class="bg-surface text-on-surface min-h-screen">

  

  <!-- ═══════════════════════════════════════════════ -->
  <!-- HEADER / HERO                                   -->
  <!-- ═══════════════════════════════════════════════ -->
  <header class="relative">
    <!-- Banner -->
    <div class="w-full h-[300px] relative overflow-hidden">
      @if($userDetail->personal_page_background_image && $userDetail->personal_page_background_image != '')
        <img src="{{ asset($userDetail->personal_page_background_image) }}" alt="Tattoo Header" class="w-full h-full object-cover object-center absolute inset-0">
      @else
        <div class="absolute inset-0" style="background-color: {{ $selectedTheme['bg'] }};"></div> <!-- Subtle dark overlay to ensure avatar/text stands out -->
      @endif
    </div>

    <!-- Profile Info -->
    <div class="max-w-4xl mx-auto px-4 sm:px-6 relative">
      <!-- Avatar -->
      <div class="absolute -top-12 left-4 sm:left-6">
        <div class="w-24 h-24 rounded-full bg-gradient-to-br from-primary to-primary-container border-4 border-surface shadow-lg flex items-center justify-center">
          <span class="text-white text-3xl font-bold">
            {{-- image --}}
            @if($userDetail->avatar && $userDetail->avatar != '')
              <img src="{{ asset($userDetail->avatar) }}" alt="Avatar" class="w-full h-full object-cover rounded-full">
            @else
              <span class="text-white text-3xl font-bold">
                {{ $userDetail->publicDisplayInitials() }}
              </span>
            @endif
          </span>
        </div>
      </div>

      <div class="pt-16 pb-6">
        @php
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
          $stylesLine = implode(', ', $styleLabels);
          $tattooingSince = trim((string) ($stylesPayload['tattooing_since'] ?? ''));
          $locationParts = array_filter([
              trim((string) ($userDetail->city ?? '')),
              trim((string) ($userDetail->country ?? '')),
          ]);
          $locationLine = implode(', ', $locationParts);
          $tagline = trim((string) ($userDetail->personal_page_tagline ?? ''));
          $studioName = trim((string) ($userDetail->studio_name ?? ''));
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
          $rateParts = [];
          if ($userDetail->hourly_rate !== null && $userDetail->hourly_rate !== '') {
              $rateParts[] = $formatRate($userDetail->hourly_rate).'/hr';
          }
          if ($userDetail->half_day_rate !== null && $userDetail->half_day_rate !== '') {
              $rateParts[] = $formatRate($userDetail->half_day_rate).' half-day';
          }
          if ($userDetail->full_day_rate !== null && $userDetail->full_day_rate !== '') {
              $rateParts[] = $formatRate($userDetail->full_day_rate).' full-day';
          }
          $ratesLine = implode(' • ', $rateParts);
          $artistPublicName = $userDetail->publicDisplayName();
        @endphp

        <!-- Name -->
        <div class="flex flex-wrap items-baseline gap-2 mb-1">
          <h1 class="text-2xl sm:text-3xl font-extrabold text-on-surface">{{ $artistPublicName }}</h1>
        </div>

        @if(($userDetail->display_tagline ?? true) && $tagline !== '')
          <p class="text-base text-on-surface-variant mb-3">{{ $tagline }}</p>
        @endif

        <!-- Studio / location / tattooing since -->
        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-on-surface-variant mb-2">
          @if($studioName !== '')
            <span class="flex items-center gap-1">
              <span class="material-symbols-outlined text-[18px] studio-name-color">storefront</span>
              <span class="font-semibold studio-name-color">{{ $studioName }}</span>
            </span>
          @endif
          @if($locationLine !== '')
            <span class="flex items-center gap-1">
              <span class="material-symbols-outlined text-[18px]">location_on</span>
              {{ $locationLine }}
            </span>
          @endif
          @if($tattooingSince !== '')
            <span class="flex items-center gap-1">
              <span class="material-symbols-outlined text-[18px]">calendar_month</span>
              Tattooing since {{ $tattooingSince }}
            </span>
          @endif
        </div>

        @if($stylesLine !== '')
          <div class="flex flex-wrap items-start gap-1 text-sm text-on-surface-variant mb-2">
            <span class="material-symbols-outlined text-[18px] mt-0.5 shrink-0">palette</span>
            <span>{{ $stylesLine }}</span>
          </div>
        @endif

        @if($ratesLine !== '')
          <div class="flex flex-wrap items-center gap-1 text-sm text-on-surface-variant mb-4">
            <span class="material-symbols-outlined text-[18px] shrink-0">payments</span>
            <span>{{ $ratesLine }}</span>
          </div>
        @else
          <div class="mb-4"></div>
        @endif

        @php
            $websiteHref = \App\Support\SocialLinks::publicHref('website', $userDetail->social_links['website'] ?? null);
            $instagramHref = \App\Support\SocialLinks::publicHref('instagram', $userDetail->social_links['instagram'] ?? null);
            $tiktokHref = \App\Support\SocialLinks::publicHref('tiktok', $userDetail->social_links['tiktok'] ?? null);
            $youtubeHref = \App\Support\SocialLinks::publicHref('youtube', $userDetail->social_links['youtube'] ?? null);
            $facebookHref = \App\Support\SocialLinks::publicHref('facebook', $userDetail->social_links['facebook'] ?? null);
        @endphp

        <!-- Social icons -->
        <div class="flex items-center gap-2 mb-5">
            {{-- website --}}
            @if($websiteHref)
                <a href="{{ $websiteHref }}" target="_blank" rel="noopener noreferrer" class="w-9 h-9 rounded-full bg-surface-container-high hover:bg-surface-container-highest flex items-center justify-center transition-colors" title="Website">
                    <i class="fa-solid fa-globe"></i>
                </a>
            @endif

            @if($instagramHref)
                <a href="{{ $instagramHref }}" target="_blank" rel="noopener noreferrer" class="w-9 h-9 rounded-full bg-surface-container-high hover:bg-surface-container-highest flex items-center justify-center transition-colors" title="Instagram">
                    <i class="fa-brands fa-instagram"></i>
                </a>
            @endif
            @if($tiktokHref)
                <a href="{{ $tiktokHref }}" target="_blank" rel="noopener noreferrer" class="w-9 h-9 rounded-full bg-surface-container-high hover:bg-surface-container-highest flex items-center justify-center transition-colors" title="TikTok">
                    <i class="fa-brands fa-tiktok"></i>
                </a>
            @endif
            @if($youtubeHref)
                <a href="{{ $youtubeHref }}" target="_blank" rel="noopener noreferrer" class="w-9 h-9 rounded-full bg-surface-container-high hover:bg-surface-container-highest flex items-center justify-center transition-colors" title="YouTube">
                    <i class="fa-brands fa-youtube"></i>
                </a>
            @endif
            @if($facebookHref)
                <a href="{{ $facebookHref }}" target="_blank" rel="noopener noreferrer" class="w-9 h-9 rounded-full bg-surface-container-high hover:bg-surface-container-highest flex items-center justify-center transition-colors" title="Facebook">
                    <i class="fa-brands fa-facebook"></i>
                </a>
            @endif
        </div>

        @if($userDetail->availability_status == 'design_only' || $userDetail->availability_status == 'custom_only')
            <div id="statusBadge" class="mb-4">
                <span class="status-badge inline-flex items-center gap-1.5 px-3 py-1.5 bg-secondary-container text-secondary rounded-full text-xs font-semibold">
                    <span class="material-symbols-outlined text-[16px]">info</span>
                    <span id="statusBadgeText">{{ $userDetail->availability_status == 'design_only' ? 'Currently accepting available design bookings only' : 'Currently accepting custom tattoo requests only' }}</span>
                </span>
            </div>
        @endif
        
        <div id="ctaButtons" class="flex flex-wrap gap-3 {{ $userDetail->availability_status == 'closed' ? 'mb-4' : '' }}">
            @if($userDetail->availability_status != 'closed')
                @if($userDetail->availability_status == 'design_only' || $userDetail->availability_status == 'design_custom')
                    <button id="btnBrowseDesigns" onclick="browseDesigns()" class="px-6 py-2.5  bg-primary text-on-primary rounded-full font-semibold text-sm hover:bg-primary-container transition-colors shadow-md shadow-primary/20">
                        Browse Available Designs
                    </button>
                @endif
                @if($userDetail->availability_status == 'custom_only' || $userDetail->availability_status == 'design_custom')
                    <a id="btnRequestCustom" href="{{route('public.request-custom', ['user_name' => $userDetail->user_name])}}" class="px-6 py-2.5 border-2 border-primary text-primary rounded-full font-semibold text-sm hover:bg-primary hover:text-on-primary transition-colors inline-flex items-center">
                        Request Custom Tattoo
                    </a>
                @endif
            @endif
            @if($displayGuestSpots)
            <button type="button" id="btnGuestSpots" onclick="openGuestSpots()" class="px-6 py-2.5 border-2 border-outline-variant text-on-surface rounded-full font-semibold text-sm hover:bg-surface-container transition-colors inline-flex items-center gap-1.5">
                <span class="material-symbols-outlined text-[18px]">luggage</span>
                Guest Spots
            </button>
            @endif
            @if($displayPolicies)
            <button type="button" id="btnPolicies" onclick="openPolicies()" class="px-6 py-2.5 border-2 border-outline-variant text-on-surface rounded-full font-semibold text-sm hover:bg-surface-container transition-colors inline-flex items-center gap-1.5">
                <span class="material-symbols-outlined text-[18px]">gavel</span>
                Policies
            </button>
            @endif
            @if($hasFaqs)
            <button type="button" id="btnFaq" onclick="openFaq()" class="px-6 py-2.5 border-2 border-outline-variant text-on-surface rounded-full font-semibold text-sm hover:bg-surface-container transition-colors inline-flex items-center gap-1.5">
                <span class="material-symbols-outlined text-[18px]">help</span>
                FAQ
            </button>
            @endif
        </div>

        <!-- Closed Banner (shown when bookings closed) -->
        @if($userDetail->availability_status == 'closed')
            <div id="closedBanner">
                <div class="bg-surface-container rounded-2xl p-6 text-center">
                    <span class="material-symbols-outlined text-4xl text-on-surface-variant mb-2">event_busy</span>
                    <h3 class="text-lg font-bold text-on-surface mb-1">Books Closed.</h3>
                    <p class="text-sm text-on-surface-variant mb-4">The artist is currently not accepting new bookings. Check back soon!</p>
                    <button onclick="openModal('waitlistModal')" class="px-6 py-2.5 border-2 border-primary text-primary rounded-full font-semibold text-sm hover:bg-primary hover:text-on-primary transition-colors">
                        Notify Me When Open
                    </button>
                </div>
            </div>
        @endif
      </div>
    </div>
  </header>

  @if($userDetail->shouldDisplayBio())
    <!-- About Section -->
    <div class="max-w-4xl mx-auto px-4 sm:px-6">
      <div class="mb-6">
        <h3 class="text-lg font-bold text-on-surface mb-3">About</h3>
        <p class="text-on-surface-variant text-sm leading-relaxed">{{ $userDetail->personal_page_description }}</p>
      </div>
    </div>
  @endif

  <!-- ═══════════════════════════════════════════════ -->
  <!-- TABS                                            -->
  <!-- ═══════════════════════════════════════════════ -->
  @if($showTabsNav)
  <nav id="artistDesignsSection" class="border-b border-outline-variant sticky top-0 bg-surface/95 backdrop-blur-sm z-30">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 flex gap-0 overflow-x-auto">
      @if($hasVisibleDesigns)
        <button id="tab-designs" onclick="switchTab('designs')" class="tab-btn px-5 py-3.5 text-sm font-semibold border-b-2 {{ $designsTabActive ? 'border-tab-btn text-primary' : 'border-transparent text-on-surface-variant' }} transition-colors whitespace-nowrap">
          Available Designs
        </button>
      @endif
      @if($hasPortfolio)
        <button id="tab-portfolio" onclick="switchTab('portfolio')" class="tab-btn px-5 py-3.5 text-sm font-semibold border-b-2 {{ $portfolioTabActive ? 'border-tab-btn text-primary' : 'border-transparent text-on-surface-variant' }} hover:text-on-surface transition-colors whitespace-nowrap">
          Portfolio
        </button>
      @endif
      @if($displayGuestSpots)
      <button id="tab-guest-spots" onclick="switchTab('guest-spots')" class="tab-btn px-5 py-3.5 text-sm font-semibold border-b-2 {{ $guestSpotsTabActive ? 'border-tab-btn text-primary' : 'border-transparent text-on-surface-variant' }} hover:text-on-surface transition-colors whitespace-nowrap">
        Guest spots
      </button>
      @endif
      @if($displayPolicies)
      <button id="tab-policies" onclick="switchTab('policies')" class="tab-btn px-5 py-3.5 text-sm font-semibold border-b-2 {{ $policiesTabActive ? 'border-tab-btn text-primary' : 'border-transparent text-on-surface-variant' }} hover:text-on-surface transition-colors whitespace-nowrap">
        Policies
      </button>
      @endif
      @if($hasFaqs)
      <button id="tab-faq" onclick="switchTab('faq')" class="tab-btn px-5 py-3.5 text-sm font-semibold border-b-2 {{ $faqTabActive ? 'border-tab-btn text-primary' : 'border-transparent text-on-surface-variant' }} hover:text-on-surface transition-colors whitespace-nowrap">
        FAQ
      </button>
      @endif
    </div>
  </nav>
  @endif

  <!-- ═══════════════════════════════════════════════ -->
  <!-- AVAILABLE DESIGNS TAB                           -->
  <!-- ═══════════════════════════════════════════════ -->
  <main class="max-w-4xl mx-auto px-4 sm:px-6 py-8">
    @if($hasVisibleDesigns)
        <div id="content-designs" class="tab-content {{ $designsTabActive ? 'active' : '' }}">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach($artistDesigns as $artistDesign)
                @php $designSoldOut = $artistDesign->isSoldOut(); @endphp
                <div class="design-card bg-white rounded-2xl overflow-hidden shadow-sm border border-outline-variant/50 cursor-pointer" onclick="window.location.href='{{ route('public.tattoo', ['user_name' => $userDetail->user_name, 'tattoo_slug' => $artistDesign->slug]) }}'">
                    <div class="aspect-4-5 bg-gradient-to-br from-violet-100 via-violet-200 to-violet-300 relative">
                        <div class="absolute inset-0 flex items-center justify-center">
                            <img src="{{ asset($artistDesign->image) }}" alt="Design" class="w-full h-full object-cover {{ $designSoldOut ? 'opacity-60' : '' }}">
                        </div>
                        @if($designSoldOut)
                        <div class="absolute inset-0 flex items-center justify-center bg-black/40">
                            <span class="px-3 py-1.5 rounded-full bg-white/95 text-on-surface text-xs font-bold uppercase tracking-wide">Sold Out</span>
                        </div>
                        @endif
                    </div>
                    <div class="p-4">
                        <h3 class="font-bold text-on-surface mb-1.5">{{ $artistDesign->title }}</h3>
                        <div class="flex flex-wrap gap-1.5 mb-2">
                            <span class="text-xs px-2 py-0.5 rounded-full bg-secondary-container text-secondary font-medium">{{ ucwords(str_replace('-', ' ', $artistDesign->primary_style)) }}</span>
                            <span class="text-xs px-2 py-0.5 rounded-full bg-surface-container-high text-on-surface-variant font-medium">{{ $artistDesign->color == 'color' ? 'Full Color' : ($artistDesign->color == 'black-grey' ? 'Black & Grey' : ($artistDesign->color == 'both' ? 'Black & Color' : $artistDesign->color)) }}</span>
                        </div>
                        <p class="text-sm font-semibold price-color mb-1">€{{ $artistDesign->min_price }} — €{{ $artistDesign->max_price }}</p>
                        <div class="mb-3">
                            @include('public.partials.design-booking-slots', ['design' => $artistDesign])
                        </div>
                        @if($designSoldOut)
                        <a href="{{ route('public.tattoo', ['user_name' => $userDetail->user_name, 'tattoo_slug' => $artistDesign->slug]) }}" onclick="event.stopPropagation()" class="block w-full py-2 rounded-full text-sm font-semibold text-center bg-surface-container-high text-on-surface-variant hover:bg-surface-container transition-colors">
                            Sold Out
                        </a>
                        @else
                        <a href="{{ route('public.tattoo', ['user_name' => $userDetail->user_name, 'tattoo_slug' => $artistDesign->slug]) }}" onclick="event.stopPropagation()" class="block w-full py-2 text-on-primary rounded-full text-sm font-semibold transition-colors text-center get-this-tattoo-btn">
                            Get This Tattoo
                        </a>
                        @endif
                    </div>
                </div>
            @endforeach

        </div>
        </div>
    @endif

    <!-- ═══════════════════════════════════════════════ -->
    <!-- PORTFOLIO TAB                                   -->
    <!-- ═══════════════════════════════════════════════ -->
    @if($hasPortfolio)
    <div id="content-portfolio" class="tab-content {{ $portfolioTabActive ? 'active' : '' }}">
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
        @foreach($artistPortfolios as $artistPortfolio)
            <div class="design-card portfolio-card bg-white rounded-2xl overflow-hidden shadow-sm border border-outline-variant/50 cursor-pointer" onclick="openPortfolioModal({{ $loop->index }})">
            <div class="aspect-1-1 bg-gradient-to-br from-warmGray-200 via-gray-300 to-gray-500 portfolio-card-media">
                <div class="absolute inset-0 flex items-center justify-center">
                    <img src="{{ asset($artistPortfolio->image) }}" alt="{{ $artistPortfolio->title }}" class="w-full h-full object-cover">
                </div>
                <div class="portfolio-similar-overlay">
                  <button
                    type="button"
                    class="portfolio-similar-btn"
                    onclick="event.stopPropagation(); requestSimilarTattoo({{ (int) $artistPortfolio->id }});"
                    aria-label="Request similar tattoo"
                  >
                    <span class="material-symbols-outlined text-[18px]" aria-hidden="true">brush</span>
                    Request similar tattoo
                  </button>
                </div>
            </div>
            <div class="p-4">
                <h3 class="font-bold text-on-surface mb-1.5">{{ $artistPortfolio->title }}</h3>
                <div class="flex flex-wrap gap-1.5 mb-2">
                <span class="text-xs px-2 py-0.5 rounded-full bg-secondary-container text-secondary font-medium">{{ ucwords(str_replace('-', ' ', $artistPortfolio->primary_style)) }}</span>
                @if(filled($artistPortfolio->placement))
                <span class="text-xs px-2 py-0.5 rounded-full bg-surface-container-high text-on-surface-variant font-medium">{{ $artistPortfolio->placement }}</span>
                @endif
                <span class="text-xs px-2 py-0.5 rounded-full bg-surface-container-high text-on-surface-variant font-medium">{{ $artistPortfolio->color == 'color' ? 'Full Color' : ($artistPortfolio->color == 'black-grey' ? 'Black & Grey' : ($artistPortfolio->color == 'both' ? 'Black & Color' : $artistPortfolio->color)) }}</span>
                </div>
                <div class="flex flex-wrap gap-1">
                @foreach($artistPortfolio->tags as $tag)
                    <span class="text-[11px] px-1.5 py-0.5 rounded bg-surface-container text-on-surface-variant">#{{ $tag }}</span>
                @endforeach
                </div>
            </div>
            </div>
        @endforeach
      </div>
    </div>
    @endif

    <!-- ═══════════════════════════════════════════════ -->
    <!-- GUEST SPOTS TAB                                 -->
    <!-- ═══════════════════════════════════════════════ -->
    @if($displayGuestSpots)
    <div id="content-guest-spots" class="tab-content {{ $guestSpotsTabActive ? 'active' : '' }}">
      <div class="bg-white rounded-2xl border border-outline-variant/40 shadow-sm p-5 sm:p-6">
        <div class="mb-5">
          <h3 class="text-lg font-bold text-on-surface mb-1">Guest spots</h3>
          <p class="text-sm text-on-surface-variant">Upcoming locations where this artist will be guesting.</p>
        </div>

        @forelse($guestSpots as $spot)
          @php
            $spot->ensureCompletedStatus();
            $spotStatus = $spot->publicStatusLabel();
            $spotStatusColor = $spot->publicStatusColor();
            $spotStatusBg = $spot->publicStatusBackground();
            $spotCountLabel = $spot->effectiveStatusKey() === 'available'
                ? $spot->listRemainingSpotsLabel()
                : null;
            $canReserve = $spot->isReservable();
          @endphp
          <div class="flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-4 py-4 {{ ! $loop->last ? 'border-b border-outline-variant/20' : '' }}">
            <div class="flex items-center gap-3 min-w-0 flex-1">
              <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-primary text-xl">luggage</span>
              </div>
              <div class="min-w-0">
                <p class="font-bold text-on-surface truncate">{{ $spot->city }}, {{ $spot->country }}</p>
                <p class="text-sm text-on-surface-variant">
                  {{ $spot->from_date->format('M j, Y') }} — {{ $spot->to_date->format('M j, Y') }}
                  @if($spot->effectiveStatusKey() === 'available' && $spot->listAvailabilityTimeLabel())
                    · {{ $spot->listAvailabilityTimeLabel() }}
                  @endif
                </p>
                @if($spotCountLabel)
                  <p class="text-xs font-semibold mt-1" style="color: {{ $spotStatusColor }};">
                    {{ $spotCountLabel }}
                  </p>
                @endif
              </div>
            </div>
            <div class="flex flex-col sm:items-end gap-2 shrink-0 self-start sm:self-center">
              <span class="inline-flex text-xs font-semibold px-2.5 py-1 rounded-full" style="background: {{ $spotStatusBg }}; color: {{ $spotStatusColor }};">
                {{ $spotStatus }}
              </span>
              @if($canReserve)
                <a
                  href="{{ route('public.request-custom', ['user_name' => $userDetail->user_name, 'guest_spot' => $spot->id]) }}"
                  class="guest-spot-reserve inline-flex items-center gap-1 text-sm font-semibold hover:opacity-80 transition-opacity"
                  style="color: {{ $selectedTheme['primary'] }};"
                >
                  Reserve
                  <span class="material-symbols-outlined text-[18px]" aria-hidden="true">arrow_forward</span>
                </a>
              @endif
            </div>
          </div>
        @empty
          <div class="py-8 text-center">
            <span class="material-symbols-outlined text-4xl text-outline/40 mb-2 block">luggage</span>
            <p class="text-sm font-semibold text-on-surface">No guest locations listed yet</p>
            <p class="text-xs text-on-surface-variant mt-1">Check back soon for upcoming guest spots.</p>
          </div>
        @endforelse
      </div>
    </div>
    @endif

    <!-- ═══════════════════════════════════════════════ -->
    <!-- POLICIES TAB                                    -->
    <!-- ═══════════════════════════════════════════════ -->
    @if($displayPolicies)
    <div id="content-policies" class="tab-content {{ $policiesTabActive ? 'active' : '' }}">
      <div class="bg-white rounded-2xl border border-outline-variant/40 shadow-sm p-5 sm:p-6 space-y-5">
        <div>
          <h3 class="text-lg font-bold text-on-surface mb-1">Policies</h3>
          <p class="text-sm text-on-surface-variant">Booking terms for appointments with this artist.</p>
        </div>
        <div class="space-y-4 text-sm text-on-surface-variant leading-relaxed">
          <p><span class="font-semibold text-on-surface">Deposit:</span> {{ $depositPolicyText }}</p>
          <p><span class="font-semibold text-on-surface">Rescheduling:</span> {{ $reschedulePolicyText }}</p>
          <p><span class="font-semibold text-on-surface">Cancellation:</span> {{ $cancellationPolicyText }}</p>
          <p><span class="font-semibold text-on-surface">No-show or late cancellation:</span> {{ $noShowPolicyText }}</p>
        </div>
      </div>
    </div>
    @endif

    @if($hasFaqs)
    <div id="content-faq" class="tab-content {{ $faqTabActive ? 'active' : '' }}">
      <div class="bg-white rounded-2xl border border-outline-variant/40 shadow-sm p-5 sm:p-6">
        <div class="mb-2">
          <h3 class="text-lg font-bold text-on-surface mb-1">FAQ</h3>
          <p class="text-sm text-on-surface-variant">Answers to questions clients ask most often.</p>
        </div>
        <div id="faqAccordion" class="divide-y divide-outline-variant/20">
          @foreach($faqs as $faq)
            <div class="faq-item">
              <button type="button" class="faq-trigger" aria-expanded="false" onclick="toggleFaqItem(this)">
                <span class="faq-question-text">{{ $faq->question }}</span>
                <span class="faq-icon" aria-hidden="true">+</span>
              </button>
              <div class="faq-answer">{{ $faq->answer }}</div>
            </div>
          @endforeach
        </div>
      </div>
    </div>
    @endif
  </main>

  <!-- ═══════════════════════════════════════════════ -->
  <!-- FOOTER                                          -->
  <!-- ═══════════════════════════════════════════════ -->
  <footer class="border-t border-outline-variant mt-12 py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 flex flex-col sm:flex-row items-center justify-between gap-4">
      <div class="flex items-baseline gap-4 mb-4 justify-center md:justify-start">
  <span class="text-3xl font-bold text-on-surface tracking-tighter leading-none" style="font-family: 'Space Grotesk', sans-serif;">bookpay</span>
  <span class="text-[9px] font-medium text-on-surface-variant uppercase tracking-widest leading-tight">Tattoo artist platform<br>by Inkjin</span>
</div>
      <div class="flex items-center gap-4 text-sm text-on-surface-variant">
        <a href="https://inkjin.com/en/privacy" class="hover:text-primary transition-colors" target="_blank">Privacy</a>
        <a href="https://inkjin.com/en/terms" class="hover:text-primary transition-colors" target="_blank">Terms</a>
      </div>
    </div>
  </footer>

  <!-- ═══════════════════════════════════════════════ -->
  <!-- PORTFOLIO DETAIL MODAL (kept)                   -->
  <!-- ═══════════════════════════════════════════════ -->
  <div id="portfolioDetailModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4" onclick="if(event.target===this)closeModal('portfolioDetailModal')">
    <div class="modal-backdrop absolute inset-0"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-hidden flex flex-col">
      <button onclick="closeModal('portfolioDetailModal')" class="absolute top-3 right-3 z-10 w-8 h-8 rounded-full bg-white/90 hover:bg-white flex items-center justify-center shadow transition-colors">
        <span class="material-symbols-outlined text-[20px]">close</span>
      </button>
      <div class="modal-body overflow-y-auto">
        <div id="portfolioModalImage" class="aspect-1-1 bg-surface-container-high relative overflow-hidden">
          <img id="portfolioModalImageEl" src="" alt="" class="hidden w-full h-full object-cover">
          <div id="portfolioModalImagePlaceholder" class="absolute inset-0 flex items-center justify-center">
            <span class="material-symbols-outlined text-gray-400 text-6xl">image</span>
          </div>
        </div>
        <div class="p-6">
          <h2 id="portfolioModalTitle" class="text-xl font-bold text-on-surface mb-2">Piece Title</h2>
          <p id="portfolioModalDesc" class="text-sm text-on-surface-variant mb-5 leading-relaxed">Description of this portfolio piece.</p>
          <div class="flex flex-wrap gap-3 mb-4">
            <div>
              <p class="text-[11px] text-on-surface-variant uppercase tracking-wide mb-0.5">Style</p>
              <p id="portfolioModalStyle" class="text-sm font-semibold text-on-surface">Japanese</p>
            </div>
            <div>
              <p class="text-[11px] text-on-surface-variant uppercase tracking-wide mb-0.5">Placement</p>
              <p id="portfolioModalPlacement" class="text-sm font-semibold text-on-surface">Anywhere</p>
            </div>
            <div>
              <p class="text-[11px] text-on-surface-variant uppercase tracking-wide mb-0.5">Colors</p>
              <p id="portfolioModalColors" class="text-sm font-semibold text-on-surface">Full Color</p>
            </div>
          </div>
          <div id="portfolioModalTags" class="flex flex-wrap gap-1.5 mb-5">
            <span class="text-xs px-2 py-0.5 rounded-full bg-surface-container-high text-on-surface-variant">#tag</span>
          </div>
          <button
            type="button"
            id="portfolioModalSimilarBtn"
            class="portfolio-similar-btn portfolio-similar-btn-modal"
            aria-label="Request similar tattoo"
          >
            <span class="material-symbols-outlined text-[20px]" aria-hidden="true">brush</span>
            Request similar tattoo
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- ═══════════════════════════════════════════════ -->
  <!-- WAITLIST MODAL                                  -->
  <!-- ═══════════════════════════════════════════════ -->
  <div id="waitlistModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4" onclick="if(event.target===this)closeModal('waitlistModal')">
    <div class="modal-backdrop absolute inset-0"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 overflow-hidden flex flex-col">
      <button onclick="closeModal('waitlistModal')" class="absolute top-3 right-3 z-10 w-8 h-8 rounded-full bg-surface-container hover:bg-surface-container-high flex items-center justify-center shadow-sm transition-colors">
        <span class="material-symbols-outlined text-[20px]">close</span>
      </button>
      
      <div id="waitlistFormView">
        <h2 class="text-xl font-bold text-on-surface mb-2">Join the Waitlist</h2>
        <p class="text-sm text-on-surface-variant mb-6">Enter your name and email to be the first to know when {{ $userDetail->publicDisplayName() }} opens their books.</p>
        
        <form id="waitlistForm" onsubmit="event.preventDefault(); submitWaitlist();" class="flex flex-col gap-4" novalidate>
          <div>
            <label for="waitlistName" class="block text-sm font-semibold text-on-surface mb-1">Name</label>
            <input type="text" id="waitlistName" name="name" autocomplete="name" class="w-full px-4 py-2.5 bg-surface rounded-xl border border-outline-variant focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all">
            <p id="waitlistNameError" class="hidden text-xs text-error mt-1"></p>
          </div>
          <div>
            <label for="waitlistEmail" class="block text-sm font-semibold text-on-surface mb-1">Email Address</label>
            <input type="email" id="waitlistEmail" name="email" autocomplete="email" class="w-full px-4 py-2.5 bg-surface rounded-xl border border-outline-variant focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all">
            <p id="waitlistEmailError" class="hidden text-xs text-error mt-1"></p>
          </div>
          <p id="waitlistFormError" class="hidden text-sm text-error"></p>
          <button type="submit" id="waitlistSubmitBtn" class="w-full py-3 mt-2 bg-primary text-on-primary rounded-full font-semibold text-sm hover:bg-primary-container transition-colors disabled:opacity-60 disabled:cursor-not-allowed">
            Notify Me
          </button>
        </form>
      </div>
      
      <div id="waitlistSuccessView" class="hidden flex-col items-center text-center py-4">
        <span class="material-symbols-outlined text-6xl text-green-500 mb-4">check_circle</span>
        <h2 class="text-xl font-bold text-on-surface mb-2">You're on the list!</h2>
        <p class="text-sm text-on-surface-variant mb-6">We'll email you the moment {{ $userDetail->publicDisplayName() }}'s books open.</p>
        <button onclick="closeModal('waitlistModal')" class="w-full py-3 bg-surface-container text-on-surface rounded-full font-semibold text-sm hover:bg-surface-container-high transition-colors">
          Close
        </button>
      </div>
    </div>
  </div>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/js/all.min.js" integrity="sha512-6BTOlkauINO65nLhXhthZMtepgJSghyimIalb+crKRPhvhmsCdnIuGcVbR5/aQY2A+260iC1OPy1oCdB6pSSwQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
  @include('partials.bookpay-public-api')
  <!-- ═══════════════════════════════════════════════ -->
  <!-- JAVASCRIPT                                      -->
  <!-- ═══════════════════════════════════════════════ -->
  <script>
    const waitlistArtistUsername = @json($userDetail->user_name);
    const csrfToken = @json(csrf_token());

    // ── Data ──────────────────────────────────────────
      const portfolio = @json($portfolioForJs);
      const requestSimilarBaseUrl = @json($requestSimilarBaseUrl);

    function requestSimilarTattoo(portfolioId) {
      const id = parseInt(portfolioId, 10);
      if (!id || !requestSimilarBaseUrl) return;
      const url = new URL(requestSimilarBaseUrl, window.BOOKPAY_BASE_URL || window.location.origin);
      url.searchParams.set('portfolio', String(id));
      window.location.href = url.toString();
    }

    // ── Tab Switching ─────────────────────────────────
    // Hash deep-links use these exact ids: designs | portfolio | guest-spots | policies | faq
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

      document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
      document.querySelectorAll('.tab-btn').forEach(el => {
        el.classList.remove('border-tab-btn', 'text-primary');
        el.classList.add('border-transparent', 'text-on-surface-variant');
      });
      const content = document.getElementById('content-' + tab);
      if (content) content.classList.add('active');
      const btn = document.getElementById('tab-' + tab);
      if (btn) {
        btn.classList.remove('border-transparent', 'text-on-surface-variant');
        btn.classList.add('border-tab-btn', 'text-primary');
      }
      if (updateHash) setTabHash(tab);
    }

    function applyTabFromHash(options) {
      const tab = tabFromHash();
      if (!tab) return false;
      switchTab(tab, Object.assign({ updateHash: false }, options || {}));
      return true;
    }

    window.addEventListener('hashchange', function () {
      applyTabFromHash();
    });

    // Script is at end of body; apply hash tab immediately (and scroll once if linked).
    if (applyTabFromHash()) {
      const nav = document.getElementById('artistDesignsSection');
      if (nav) {
        requestAnimationFrame(function () {
          nav.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
      }
    }

    function browseDesigns() {
      switchTab('designs');
      const target = document.getElementById('artistDesignsSection') || document.getElementById('content-designs');
      if (target) {
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    }

    function openPolicies() {
      switchTab('policies');
      const target = document.getElementById('artistDesignsSection') || document.getElementById('content-policies');
      if (target) {
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    }

    function openGuestSpots() {
      switchTab('guest-spots');
      const target = document.getElementById('artistDesignsSection') || document.getElementById('content-guest-spots');
      if (target) {
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    }

    function openFaq() {
      switchTab('faq');
      const target = document.getElementById('artistDesignsSection') || document.getElementById('content-faq');
      if (target) {
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    }

    function toggleFaqItem(btn) {
      const item = btn.closest('.faq-item');
      if (!item) return;
      const isOpen = item.classList.contains('open');
      item.classList.toggle('open', !isOpen);
      btn.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
      const icon = btn.querySelector('.faq-icon');
      if (icon) icon.textContent = isOpen ? '+' : '−';
    }

    // ── Modal Helpers ─────────────────────────────────
    function openModal(id) {
      const modal = document.getElementById(id);
      modal.classList.remove('hidden');
      modal.classList.add('flex');
      document.body.style.overflow = 'hidden';
    }

    function closeModal(id) {
      const modal = document.getElementById(id);
      modal.classList.add('hidden');
      modal.classList.remove('flex');
      document.body.style.overflow = '';
      if (id === 'waitlistModal') {
        resetWaitlistModal();
      }
    }

    function resetWaitlistModal() {
      document.getElementById('waitlistFormView')?.classList.remove('hidden');
      const successView = document.getElementById('waitlistSuccessView');
      if (successView) {
        successView.classList.add('hidden');
        successView.classList.remove('flex');
      }
      const form = document.getElementById('waitlistForm');
      if (form) form.reset();
      clearWaitlistErrors();
      const submitBtn = document.getElementById('waitlistSubmitBtn');
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Notify Me';
      }
    }

    function clearWaitlistErrors() {
      ['waitlistNameError', 'waitlistEmailError', 'waitlistFormError'].forEach((id) => {
        const el = document.getElementById(id);
        if (el) {
          el.textContent = '';
          el.classList.add('hidden');
        }
      });
      ['waitlistName', 'waitlistEmail'].forEach((id) => {
        document.getElementById(id)?.classList.remove('border-error');
      });
    }

    function showWaitlistFieldError(fieldId, errorId, message) {
      const field = document.getElementById(fieldId);
      const error = document.getElementById(errorId);
      if (field) field.classList.add('border-error');
      if (error) {
        error.textContent = message;
        error.classList.remove('hidden');
      }
    }

    // ── Portfolio Detail Modal ────────────────────────
    function openPortfolioModal(index) {
      const p = portfolio[index];
      if (!p) {
        return;
      }

      if (document.getElementById('content-portfolio')) {
        switchTab('portfolio');
      }

      const imageEl = document.getElementById('portfolioModalImageEl');
      const imagePlaceholder = document.getElementById('portfolioModalImagePlaceholder');
      if (imageEl && p.image) {
        imageEl.src = p.image;
        imageEl.alt = p.title || 'Portfolio piece';
        imageEl.classList.remove('hidden');
        imagePlaceholder?.classList.add('hidden');
      } else if (imageEl) {
        imageEl.src = '';
        imageEl.alt = '';
        imageEl.classList.add('hidden');
        imagePlaceholder?.classList.remove('hidden');
      }

      document.getElementById('portfolioModalTitle').textContent = p.title || '';

      const descEl = document.getElementById('portfolioModalDesc');
      if (descEl) {
        if (p.desc) {
          descEl.textContent = p.desc;
          descEl.classList.remove('hidden');
        } else {
          descEl.textContent = '';
          descEl.classList.add('hidden');
        }
      }

      document.getElementById('portfolioModalStyle').textContent = p.style || '';
      document.getElementById('portfolioModalPlacement').textContent = p.placement || 'Anywhere';
      document.getElementById('portfolioModalColors').textContent = p.colors || '';

      const tagsEl = document.getElementById('portfolioModalTags');
      const tags = Array.isArray(p.tags) ? p.tags : [];
      tagsEl.innerHTML = tags.length
        ? tags.map(t => `<span class="text-xs px-2 py-0.5 rounded-full bg-surface-container-high text-on-surface-variant">${t}</span>`).join('')
        : '';

      const similarBtn = document.getElementById('portfolioModalSimilarBtn');
      if (similarBtn) {
        similarBtn.onclick = function () {
          requestSimilarTattoo(p.id);
        };
      }

      openModal('portfolioDetailModal');
    }

    // ── Escape Key ────────────────────────────────────
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        ['portfolioDetailModal', 'waitlistModal'].forEach(id => {
          if (!document.getElementById(id).classList.contains('hidden')) {
            closeModal(id);
          }
        });
      }
    });

    // ── Waitlist Submit ───────────────────────────────
    async function submitWaitlist() {
      clearWaitlistErrors();

      const name = String(document.getElementById('waitlistName')?.value || '').trim();
      const email = String(document.getElementById('waitlistEmail')?.value || '').trim();
      let hasError = false;

      if (!name) {
        showWaitlistFieldError('waitlistName', 'waitlistNameError', 'Please enter your name.');
        hasError = true;
      }

      if (!email) {
        showWaitlistFieldError('waitlistEmail', 'waitlistEmailError', 'Please enter your email address.');
        hasError = true;
      } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        showWaitlistFieldError('waitlistEmail', 'waitlistEmailError', 'Please enter a valid email address.');
        hasError = true;
      }

      if (hasError) return;

      const submitBtn = document.getElementById('waitlistSubmitBtn');
      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = 'Submitting...';
      }

      try {
        const response = await fetch(bookpayUrl('/api/public/submit-waitlist'), {
          method: 'POST',
          credentials: 'include',
          headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
          },
          body: JSON.stringify({
            artist_username: waitlistArtistUsername,
            name: name,
            email: email,
          }),
        });

        const data = await response.json();

        if (!response.ok) {
          if (response.status === 422 && data.errors) {
            if (data.errors.name) {
              showWaitlistFieldError('waitlistName', 'waitlistNameError', data.errors.name[0]);
            }
            if (data.errors.email) {
              showWaitlistFieldError('waitlistEmail', 'waitlistEmailError', data.errors.email[0]);
            }
            const formError = document.getElementById('waitlistFormError');
            if (formError && data.message && !data.errors.name && !data.errors.email) {
              formError.textContent = data.message;
              formError.classList.remove('hidden');
            }
          } else {
            const formError = document.getElementById('waitlistFormError');
            if (formError) {
              formError.textContent = (data && data.message) || 'Unable to join the waitlist. Please try again.';
              formError.classList.remove('hidden');
            }
          }
          return;
        }

        document.getElementById('waitlistFormView').classList.add('hidden');
        document.getElementById('waitlistSuccessView').classList.remove('hidden');
        document.getElementById('waitlistSuccessView').classList.add('flex');
      } catch (err) {
        const formError = document.getElementById('waitlistFormError');
        if (formError) {
          formError.textContent = 'Unable to join the waitlist. Please try again.';
          formError.classList.remove('hidden');
        }
      } finally {
        if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.textContent = 'Notify Me';
        }
      }
    }

    // ── Deep Linking (Hash + Query Params) ────────────
    function handleDeepLink() {
      const hash = window.location.hash.replace('#', '');
      const params = new URLSearchParams(window.location.search);
      const action = params.get('action');

      // Hash takes priority
      if (hash) {
        if (hash === 'request-custom') {
          window.location.href = 'request-custom.html?artist=Julian+Ink';
          return;
        }
        const designMatch = hash.match(/^book-design-(\d+)$/);
        if (designMatch) {
          window.location.href = 'design-detail.html?design=' + designMatch[1];
          return;
        }
        const portfolioMatch = hash.match(/^portfolio-(\d+)$/);
        if (portfolioMatch) {
          const idx = parseInt(portfolioMatch[1], 10);
          if (idx >= 0 && idx < portfolio.length) {
            openPortfolioModal(idx);
          }
          return;
        }
      }

      // Fallback to query params
      if (action === 'request') {
        window.location.href = 'request-custom.html?artist=Julian+Ink';
      } else if (action === 'book') {
        const designIdx = params.get('design') || '0';
        window.location.href = 'design-detail.html?design=' + designIdx;
      } else if (action === 'portfolio') {
        const itemIdx = parseInt(params.get('item') || '0', 10);
        if (itemIdx >= 0 && itemIdx < portfolio.length) {
          openPortfolioModal(itemIdx);
        }
      }
    }

    // Run on page load
    handleDeepLink();

    // Also handle hash changes
    window.addEventListener('hashchange', handleDeepLink);

    // ── Booking Status Toggle ─────────────────────────
    let currentStatus = 'open';

    function setBookingStatus(status) {
      currentStatus = status;

      // Update toggle buttons
      document.querySelectorAll('.demo-status-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.status === status);
      });

      // Update URL
      const url = new URL(window.location);
      if (status === 'open') url.searchParams.delete('status');
      else url.searchParams.set('status', status);
      history.replaceState(null, '', url);

      applyBookingStatus(status);
    }

    function applyBookingStatus(status) {
      const btnBrowse = document.getElementById('btnBrowseDesigns');
      const btnCustom = document.getElementById('btnRequestCustom');
      const ctaButtons = document.getElementById('ctaButtons');
      const closedBanner = document.getElementById('closedBanner');
      const statusBadge = document.getElementById('statusBadge');
      const statusBadgeText = document.getElementById('statusBadgeText');
      const designsContent = document.getElementById('content-designs');
      const designCards = designsContent ? designsContent.querySelectorAll('.design-card') : [];

      // Reset everything
      if (btnBrowse) btnBrowse.style.display = '';
      if (btnCustom) btnCustom.style.display = '';
      if (ctaButtons) ctaButtons.classList.remove('hidden');
      if (closedBanner) closedBanner.classList.add('hidden');
      if (statusBadge) statusBadge.classList.add('hidden');

      // Re-enable all "Get This Tattoo" buttons
      designCards.forEach(card => {
        const btn = card.querySelector('a[href^="design-detail"]');
        if (btn) {
          btn.classList.remove('opacity-40', 'cursor-not-allowed', 'pointer-events-none');
          btn.removeAttribute('title');
          btn.style.background = '';
        }
        card.style.opacity = '';
        card.style.pointerEvents = '';
      });

      // Show designs tab content normally
      const tabDesigns = document.getElementById('tab-designs');
      if (tabDesigns) tabDesigns.style.display = '';
      if (designsContent) designsContent.style.display = '';

      switch (status) {
        case 'open':
          // Everything normal
          break;

        case 'flash':
          // Hide custom button, show badge
          if (btnCustom) btnCustom.style.display = 'none';
          if (statusBadge) statusBadge.classList.remove('hidden');
          if (statusBadgeText) statusBadgeText.textContent = 'Currently accepting available design bookings only';
          break;

        case 'custom':
          // Hide browse button, disable design cards
          if (btnBrowse) btnBrowse.style.display = 'none';
          if (statusBadge) statusBadge.classList.remove('hidden');
          if (statusBadgeText) statusBadgeText.textContent = 'Currently accepting custom requests only';
          if (tabDesigns) {
            tabDesigns.style.display = 'none';
          }
          if (designsContent) {
            designsContent.style.display = 'none';
          }
          switchTab('portfolio');
          break;

        case 'closed':
          // Hide booking CTAs, keep Policies, show closed banner
          if (btnBrowse) btnBrowse.style.display = 'none';
          if (btnCustom) btnCustom.style.display = 'none';
          if (closedBanner) closedBanner.classList.remove('hidden');
          if (statusBadge) statusBadge.classList.add('hidden');
          if (tabDesigns) {
            tabDesigns.style.display = 'none';
          }
          if (designsContent) {
            designsContent.style.display = 'none';
          }
          switchTab('portfolio');
          break;
      }
    }

    // Init from URL param
    (function() {
      const urlParams = new URLSearchParams(window.location.search);
      const statusParam = urlParams.get('status');
      if (statusParam && ['open', 'flash', 'custom', 'closed'].includes(statusParam)) {
        currentStatus = statusParam;
        document.querySelectorAll('.demo-status-btn').forEach(btn => {
          btn.classList.toggle('active', btn.dataset.status === statusParam);
        });
        applyBookingStatus(statusParam);
      }
    })();
  </script>

</body>
</html>
