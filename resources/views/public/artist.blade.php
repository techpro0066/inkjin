<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>{{ $userDetail->user->first_name }} {{ $userDetail->user->last_name }} - Tattoo Artist | Inkjin</title>
  <meta name="description" content="Book tattoo designs or request custom work from Julian Ink at Open Ink Studio, Athens, Greece.">
  <link rel="icon" href="{{asset('assets/img/favicon/favicon.png')}}">
  <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
  
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

    /* Custom scrollbar for modals */
    .modal-body::-webkit-scrollbar { width: 6px; }
    .modal-body::-webkit-scrollbar-thumb { background: #cac4d3; border-radius: 3px; }

    /* Aspect ratios */
    .aspect-4-5 { aspect-ratio: 4/5; }
    .aspect-1-1 { aspect-ratio: 1/1; }
  </style>
</head>
<body class="bg-surface text-on-surface min-h-screen">

  <!-- ═══════════════════════════════════════════════ -->
  <!-- HEADER / HERO                                   -->
  <!-- ═══════════════════════════════════════════════ -->
  <header class="relative">
    <!-- Banner -->
    <div class="w-full h-[300px] relative bg-surface-container-highest overflow-hidden">
        <img src="{{ $userDetail->personal_page_background_image ? asset($userDetail->personal_page_background_image) : '' }}" alt="Tattoo Header" class="w-full h-full object-cover absolute inset-0">
        <div class="absolute inset-0 bg-black/20"></div> <!-- Subtle dark overlay to ensure avatar/text stands out -->
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
                {{ $userDetail->user->first_name[0] }} {{ $userDetail->user->last_name[0] }}
              </span>
            @endif
          </span>
        </div>
      </div>

      <div class="pt-16 pb-6">
        <!-- Name -->
        <div class="flex flex-wrap items-baseline gap-2 mb-1">
            @if($userDetail->personal_page_name_alias == 'full')
              <h1 class="text-2xl sm:text-3xl font-extrabold text-on-surface">{{ $userDetail->user->first_name }} {{ $userDetail->user->last_name }}</h1>
            @elseif($userDetail->personal_page_name_alias == 'username')
              <h1 class="text-2xl sm:text-3xl font-extrabold text-on-surface">{{ $userDetail->user_name }}</h1>   
            @elseif($userDetail->personal_page_name_alias == 'both')
              <h1 class="text-2xl sm:text-3xl font-extrabold text-on-surface">{{ $userDetail->user->first_name }} {{ $userDetail->user->last_name }}</h1>
              <span class="text-lg text-on-surface-variant font-light">({{ $userDetail->user_name }})</span>
            @endif
        </div>

        <!-- Studio -->
        <p class="text-base font-semibold text-primary mb-2">{{ $userDetail->studio_name }}</p>

        <!-- Meta row -->
        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-on-surface-variant mb-4">
          <span class="flex items-center gap-1">
            <span class="material-symbols-outlined text-[16px] mr-1 align-bottom">location_on</span> {{ $userDetail->city }}, {{ $userDetail->country }}
          </span>
          <span class="flex items-center gap-1">
            <span class="material-symbols-outlined text-[18px]">calendar_month</span>
            Tattooing since {{ $userDetail->tattoo_styles['tattooing_since'] ?? '' }}
          </span>
        </div>

        <!-- Social icons -->
        <div class="flex items-center gap-2 mb-5">
            {{-- website --}}
            @if(isset($userDetail->social_links['website']) && $userDetail->social_links['website'] != '')
                <a href="{{ $userDetail->social_links['website'] ?? 'javascript:void(0)' }}" target="_blank" rel="noopener noreferrer" class="w-9 h-9 rounded-full bg-surface-container-high hover:bg-surface-container-highest flex items-center justify-center transition-colors" title="Website">
                    <i class="fa-solid fa-globe"></i>
                </a>
            @endif

            @if(isset($userDetail->social_links['instagram']) && $userDetail->social_links['instagram'] != '')
                <a href="{{ $userDetail->social_links['instagram'] ?? 'javascript:void(0)' }}" target="_blank" rel="noopener noreferrer" class="w-9 h-9 rounded-full bg-surface-container-high hover:bg-surface-container-highest flex items-center justify-center transition-colors" title="Instagram">
                    <i class="fa-brands fa-instagram"></i>
                </a>
            @endif
            @if(isset($userDetail->social_links['tiktok']) && $userDetail->social_links['tiktok'] != '')
                <a href="{{ $userDetail->social_links['tiktok'] ?? 'javascript:void(0)' }}" target="_blank" rel="noopener noreferrer" class="w-9 h-9 rounded-full bg-surface-container-high hover:bg-surface-container-highest flex items-center justify-center transition-colors" title="TikTok">
                    <i class="fa-brands fa-tiktok"></i>
                </a>
            @endif
            @if(isset($userDetail->social_links['youtube']) && $userDetail->social_links['youtube'] != '')
                <a href="{{ $userDetail->social_links['youtube'] ?? 'javascript:void(0)' }}" target="_blank" rel="noopener noreferrer" class="w-9 h-9 rounded-full bg-surface-container-high hover:bg-surface-container-highest flex items-center justify-center transition-colors" title="YouTube">
                    <i class="fa-brands fa-youtube"></i>
                </a>
            @endif
            @if(isset($userDetail->social_links['facebook']) && $userDetail->social_links['facebook'] != '')
                <a href="{{ $userDetail->social_links['facebook'] ?? 'javascript:void(0)' }}" target="_blank" rel="noopener noreferrer" class="w-9 h-9 rounded-full bg-surface-container-high hover:bg-surface-container-highest flex items-center justify-center transition-colors" title="Facebook">
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
        
        @if($userDetail->availability_status != 'closed')
            <div id="ctaButtons" class="flex flex-wrap gap-3">
                @if($userDetail->availability_status == 'design_only' || $userDetail->availability_status == 'design_custom')
                    <button id="btnBrowseDesigns" onclick="switchTab('designs')" class="px-6 py-2.5 bg-primary text-on-primary rounded-full font-semibold text-sm hover:bg-primary-container transition-colors shadow-md shadow-primary/20">
                        Browse Available Designs
                    </button>
                @endif
                @if($userDetail->availability_status == 'custom_only' || $userDetail->availability_status == 'design_custom')
                    <a id="btnRequestCustom" href="request-custom.html?artist=Julian+Ink" class="px-6 py-2.5 border-2 border-primary text-primary rounded-full font-semibold text-sm hover:bg-primary hover:text-on-primary transition-colors inline-flex items-center">
                        Request Custom Tattoo
                    </a>
                @endif
            </div>
        @endif

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

  <!-- ═══════════════════════════════════════════════ -->
  <!-- TABS                                            -->
  <!-- ═══════════════════════════════════════════════ -->
  <nav class="border-b border-outline-variant sticky top-0 bg-surface/95 backdrop-blur-sm z-30">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 flex gap-0">
      @if($artistDesigns->count() > 0)
        <button id="tab-designs" onclick="switchTab('designs')" class="tab-btn px-5 py-3.5 text-sm font-semibold border-b-2 border-primary text-primary transition-colors">
          Available Designs
        </button>
      @endif
      @if($artistPortfolios->count() > 0)
        <button id="tab-portfolio" onclick="switchTab('portfolio')" class="tab-btn px-5 py-3.5 text-sm font-semibold border-b-2 border-transparent text-on-surface-variant hover:text-on-surface transition-colors">
          Portfolio
        </button>
      @endif
    </div>
  </nav>

  <!-- ═══════════════════════════════════════════════ -->
  <!-- AVAILABLE DESIGNS TAB                           -->
  <!-- ═══════════════════════════════════════════════ -->
  <main class="max-w-4xl mx-auto px-4 sm:px-6 py-8">
    <!-- About Section -->
    <div class="mb-8">
      <h3 class="text-lg font-bold text-on-surface mb-3">About</h3>
      <p class="text-on-surface-variant text-sm leading-relaxed">{{ $userDetail->personal_page_description ?? '' }}</p>
    </div>
    @if($artistDesigns->count() > 0)
        <div id="content-designs" class="tab-content active">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach($artistDesigns as $artistDesign)
                <div class="design-card bg-white rounded-2xl overflow-hidden shadow-sm border border-outline-variant/50 cursor-pointer" onclick="window.location.href='{{ route('public.tattoo', ['user_name' => $userDetail->user_name, 'tattoo_slug' => $artistDesign->slug]) }}'">
                    <div class="aspect-4-5 bg-gradient-to-br from-violet-100 via-violet-200 to-violet-300 relative">
                        <div class="absolute inset-0 flex items-center justify-center">
                            <img src="{{ asset($artistDesign->image) }}" alt="Design" class="w-full h-full object-cover">
                        </div>
                    </div>
                    <div class="p-4">
                        <h3 class="font-bold text-on-surface mb-1.5">{{ $artistDesign->title }}</h3>
                        <div class="flex flex-wrap gap-1.5 mb-2">
                            <span class="text-xs px-2 py-0.5 rounded-full bg-secondary-container text-secondary font-medium">{{ ucwords(str_replace('-', ' ', $artistDesign->primary_style)) }}</span>
                            <span class="text-xs px-2 py-0.5 rounded-full bg-surface-container-high text-on-surface-variant font-medium">{{ $artistDesign->color == 'color' ? 'Full Color' : ($artistDesign->color == 'black-grey' ? 'Black & Grey' : ($artistDesign->color == 'both' ? 'Black & Color' : $artistDesign->color)) }}</span>
                        </div>
                        <p class="text-sm font-semibold text-primary mb-3">€{{ $artistDesign->min_price }} — €{{ $artistDesign->max_price }}</p>
                        <a href="{{ route('public.tattoo', ['user_name' => $userDetail->user_name, 'tattoo_slug' => $artistDesign->slug]) }}" onclick="event.stopPropagation()" class="block w-full py-2 bg-primary text-on-primary rounded-full text-sm font-semibold hover:bg-primary-container transition-colors text-center">
                            Get This Tattoo
                        </a>
                    </div>
                </div>
            @endforeach

        </div>
        </div>
    @endif

    <!-- ═══════════════════════════════════════════════ -->
    <!-- PORTFOLIO TAB                                   -->
    <!-- ═══════════════════════════════════════════════ -->
    <div id="content-portfolio" class="tab-content">
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
        @foreach($artistPortfolios as $artistPortfolio)
            <div class="design-card bg-white rounded-2xl overflow-hidden shadow-sm border border-outline-variant/50 cursor-pointer" onclick="openPortfolioModal(5)">
            <div class="aspect-1-1 bg-gradient-to-br from-warmGray-200 via-gray-300 to-gray-500 relative">
                <div class="absolute inset-0 flex items-center justify-center">
                    <img src="{{ asset($artistPortfolio->image) }}" alt="Portfolio" class="w-full h-full object-fill">
                </div>
            </div>
            <div class="p-4">
                <h3 class="font-bold text-on-surface mb-1.5">{{ $artistPortfolio->title }}</h3>
                <div class="flex flex-wrap gap-1.5 mb-2">
                <span class="text-xs px-2 py-0.5 rounded-full bg-secondary-container text-secondary font-medium">{{ ucwords(str_replace('-', ' ', $artistPortfolio->primary_style)) }}</span>
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
        <a href="#" class="hover:text-primary transition-colors">Privacy</a>
        <a href="#" class="hover:text-primary transition-colors">Terms</a>
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
        <div id="portfolioModalImage" class="aspect-1-1 bg-gradient-to-br from-gray-200 via-gray-300 to-gray-400 relative">
          <div class="absolute inset-0 flex items-center justify-center">
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
              <p class="text-[11px] text-on-surface-variant uppercase tracking-wide mb-0.5">Colors</p>
              <p id="portfolioModalColors" class="text-sm font-semibold text-on-surface">Full Color</p>
            </div>
          </div>
          <div id="portfolioModalTags" class="flex flex-wrap gap-1.5">
            <span class="text-xs px-2 py-0.5 rounded-full bg-surface-container-high text-on-surface-variant">#tag</span>
          </div>
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
        <p class="text-sm text-on-surface-variant mb-6">Enter your name and email to be the first to know when Julian Ink opens their books.</p>
        
        <form onsubmit="event.preventDefault(); submitWaitlist();" class="flex flex-col gap-4">
          <div>
            <label class="block text-sm font-semibold text-on-surface mb-1">First Name</label>
            <input type="text" required class="w-full px-4 py-2.5 bg-surface rounded-xl border border-outline-variant focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all">
          </div>
          <div>
            <label class="block text-sm font-semibold text-on-surface mb-1">Email Address</label>
            <input type="email" required class="w-full px-4 py-2.5 bg-surface rounded-xl border border-outline-variant focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all">
          </div>
          <button type="submit" class="w-full py-3 mt-2 bg-primary text-on-primary rounded-full font-semibold text-sm hover:bg-primary-container transition-colors">
            Notify Me
          </button>
        </form>
      </div>
      
      <div id="waitlistSuccessView" class="hidden flex-col items-center text-center py-4">
        <span class="material-symbols-outlined text-6xl text-green-500 mb-4">check_circle</span>
        <h2 class="text-xl font-bold text-on-surface mb-2">You're on the list!</h2>
        <p class="text-sm text-on-surface-variant mb-6">We'll email you the moment Julian Ink's books open.</p>
        <button onclick="closeModal('waitlistModal')" class="w-full py-3 bg-surface-container text-on-surface rounded-full font-semibold text-sm hover:bg-surface-container-high transition-colors">
          Close
        </button>
      </div>
    </div>
  </div>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/js/all.min.js" integrity="sha512-6BTOlkauINO65nLhXhthZMtepgJSghyimIalb+crKRPhvhmsCdnIuGcVbR5/aQY2A+260iC1OPy1oCdB6pSSwQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
  <!-- ═══════════════════════════════════════════════ -->
  <!-- JAVASCRIPT                                      -->
  <!-- ═══════════════════════════════════════════════ -->
  <script>
    // ── Data ──────────────────────────────────────────
    const portfolio = [
      {
        title: "Phoenix Rising",
        desc: "Full back piece completed over 5 sessions. Traditional Japanese phoenix with flowing feathers and fire elements, surrounded by peonies and wind bars. One of my most ambitious pieces to date.",
        style: "Japanese", colors: "Full Color",
        tags: ["#backpiece", "#phoenix", "#japanese", "#peony"]
      },
      {
        title: "Geometric Wolf",
        desc: "Half-geometric, half-realistic wolf portrait on the upper arm. The geometric side features sacred geometry patterns that transition seamlessly into photo-realistic fur detail.",
        style: "Geometric", colors: "Black & Grey",
        tags: ["#geometric", "#wolf", "#realism", "#hybrid"]
      },
      {
        title: "Koi Fish Sleeve",
        desc: "A vibrant full-sleeve featuring two koi fish swimming in opposite directions through churning water. Classic Japanese symbolism of perseverance and strength.",
        style: "Japanese", colors: "Full Color",
        tags: ["#sleeve", "#koi", "#japanese", "#water"]
      },
      {
        title: "Minimalist Butterfly",
        desc: "Ultra-fine single-needle butterfly on the inner wrist. Delicate wing details with subtle dotwork shading. Proof that small tattoos can carry immense beauty.",
        style: "Fine Line", colors: "Black & Grey",
        tags: ["#fineline", "#butterfly", "#minimalist", "#wrist"]
      },
      {
        title: "Sacred Geometry Chest",
        desc: "Symmetrical sacred geometry design spanning the full chest, featuring the Flower of Life, Metatron's Cube, and custom geometric patterns. Pure precision work.",
        style: "Geometric", colors: "Black & Grey",
        tags: ["#sacred", "#chest", "#geometry", "#symmetry"]
      },
      {
        title: "Medusa Portrait",
        desc: "Hyper-realistic Medusa portrait on the thigh with flowing snake hair and piercing eyes. Dramatic black and grey shading with white highlights for depth.",
        style: "Realism", colors: "Black & Grey",
        tags: ["#realism", "#medusa", "#portrait", "#mythology"]
      }
    ];

    // ── Tab Switching ─────────────────────────────────
    function switchTab(tab) {
      document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
      document.querySelectorAll('.tab-btn').forEach(el => {
        el.classList.remove('border-primary', 'text-primary');
        el.classList.add('border-transparent', 'text-on-surface-variant');
      });
      document.getElementById('content-' + tab).classList.add('active');
      const btn = document.getElementById('tab-' + tab);
      btn.classList.remove('border-transparent', 'text-on-surface-variant');
      btn.classList.add('border-primary', 'text-primary');
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
    }

    // ── Portfolio Detail Modal ────────────────────────
    function openPortfolioModal(index) {
      const p = portfolio[index];
      document.getElementById('portfolioModalTitle').textContent = p.title;
      document.getElementById('portfolioModalDesc').textContent = p.desc;
      document.getElementById('portfolioModalStyle').textContent = p.style;
      document.getElementById('portfolioModalColors').textContent = p.colors;
      document.getElementById('portfolioModalTags').innerHTML = p.tags.map(t =>
        `<span class="text-xs px-2 py-0.5 rounded-full bg-surface-container-high text-on-surface-variant">${t}</span>`
      ).join('');
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
    function submitWaitlist() {
      document.getElementById('waitlistFormView').classList.add('hidden');
      document.getElementById('waitlistSuccessView').classList.remove('hidden');
      document.getElementById('waitlistSuccessView').classList.add('flex');
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
      btnBrowse.style.display = '';
      btnCustom.style.display = '';
      ctaButtons.classList.remove('hidden');
      closedBanner.classList.add('hidden');
      statusBadge.classList.add('hidden');

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
          btnCustom.style.display = 'none';
          statusBadge.classList.remove('hidden');
          statusBadgeText.textContent = 'Currently accepting available design bookings only';
          break;

        case 'custom':
          // Hide browse button, disable design cards
          btnBrowse.style.display = 'none';
          statusBadge.classList.remove('hidden');
          statusBadgeText.textContent = 'Currently accepting custom requests only';
          if (tabDesigns) {
            tabDesigns.style.display = 'none';
          }
          if (designsContent) {
            designsContent.style.display = 'none';
          }
          switchTab('portfolio');
          break;

        case 'closed':
          // Hide CTA buttons, show closed banner
          ctaButtons.classList.add('hidden');
          closedBanner.classList.remove('hidden');
          statusBadge.classList.add('hidden');
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
