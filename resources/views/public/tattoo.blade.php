<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Inkjin | {{ $tattoo->title }}</title>
  <meta name="description" content="View tattoo design details and book your session with Inkjin Book & Pay.">
  <link rel="icon" href="{{asset('design/images/icons/favicon.png')}}">
  <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
  <link href="{{asset('design/css/inkjin_bookpay.css')}}" rel="stylesheet">
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
    .aspect-4-5 { aspect-ratio: 4/5; }
    .design-card { transition: transform 0.2s ease, box-shadow 0.2s ease; }
    .design-card:hover { transform: translateY(-2px); box-shadow: 0 8px 30px rgba(49,15,122,0.12); }
  </style>
</head>
<body class="bg-surface text-on-surface min-h-screen pb-24 md:pb-0">

  <!-- STICKY HEADER -->
  <header class="sticky top-0 z-40 bg-surface/95 backdrop-blur-sm border-b border-outline-variant">
    <div class="max-w-[900px] mx-auto px-4 sm:px-6 flex items-center justify-between h-14">
      <div class="flex items-center gap-3">
        <a href="{{route('public.artist', ['username' => $userDetail->user_name])}}" class="flex items-center gap-1.5 text-sm font-semibold text-primary hover:text-primary-container transition-colors">
          <span class="material-symbols-outlined text-[20px]">arrow_back</span>
          <span class="hidden sm:inline">Back to {{ $userDetail->user->first_name }} {{ $userDetail->user->last_name }}</span>
          <span class="sm:hidden">Back</span>
        </a>
      </div>
      <div class="flex items-center gap-2">
        <button onclick="shareDesign()" class="w-9 h-9 rounded-full bg-surface-container-high hover:bg-surface-container-highest flex items-center justify-center transition-colors" title="Share">
          <span class="material-symbols-outlined text-[20px] text-on-surface-variant">share</span>
        </button>
        <a href="{{route('public.artist', ['username' => $userDetail->user_name])}}" class="w-7 h-7 rounded bg-primary flex items-center justify-center" title="Inkjin">
          <span class="text-white text-[9px] font-extrabold">{{ strtoupper($userDetail->user->first_name[0]) }}{{ strtoupper($userDetail->user->last_name[0]) }}</span>
        </a>
      </div>
    </div>
  </header>

  <!-- MAIN CONTENT -->
  <main class="max-w-[900px] mx-auto px-4 sm:px-6 py-6 sm:py-8">

    <!-- HERO SECTION: Image + Details -->
    <div class="flex flex-col md:flex-row gap-6 md:gap-8 mb-10">
      <!-- Design Image -->
      <div class="w-full md:w-1/2 flex-shrink-0">
        <div id="heroImage" class="aspect-4-5 bg-gradient-to-br from-gray-200 via-gray-300 to-gray-400 rounded-2xl relative overflow-hidden">
          <div class="absolute inset-0 flex items-center justify-center">
            {{-- <span class="material-symbols-outlined text-gray-400 text-6xl">palette</span> --}}
            <img src="{{asset($tattoo->image)}}" alt="{{ $tattoo->title }}" class="w-full h-full object-cover">
          </div>
        </div>
      </div>

      <!-- Design Details -->
      <div class="w-full md:w-1/2 flex flex-col">
        <!-- Title -->
        <h1 id="designTitle" class="text-2xl sm:text-3xl font-extrabold text-on-surface mb-3">{{ $tattoo->title }}</h1>

        <!-- Artist -->
        <a href="{{route('public.artist', ['username' => $userDetail->user_name])}}" class="flex items-center gap-2.5 mb-4 group">
          <div class="w-8 h-8 rounded-full bg-gradient-to-br from-primary to-primary-container flex items-center justify-center flex-shrink-0">
            <span class="text-white text-xs font-bold">{{ strtoupper($userDetail->user->first_name[0]) }}{{ strtoupper($userDetail->user->last_name[0]) }}</span>
          </div>
          <span class="text-sm text-on-surface-variant group-hover:text-primary transition-colors">by <strong class="text-on-surface group-hover:text-primary">{{ $userDetail->user->first_name }} {{ $userDetail->user->last_name }}</strong></span>
        </a>

        <!-- Price -->
        <p id="designPrice" class="text-xl font-bold text-primary mb-3">€{{ $tattoo->min_price }} — €{{ $tattoo->max_price }}</p>

        <!-- Rating -->
        {{-- <div class="flex items-center gap-1.5 mb-6">
          <div class="flex text-amber-400 text-sm">
            <span>★</span><span>★</span><span>★</span><span>★</span><span>★</span>
          </div>
          <span class="text-sm text-on-surface-variant font-medium">4.9</span>
        </div> --}}

        <!-- Desktop CTA -->
        <div class="hidden md:block mt-auto">
          @php
            $cwRaw = strtolower(trim((string) ($userDetail->cancellation_window ?? '48h')));
            if (str_contains($cwRaw, 'w')) {
                preg_match('/(\d+)/', $cwRaw, $m);
                $n = (int) ($m[1] ?? 1);
                $cancelWindowHuman = $n === 1 ? '1 week' : $n.' weeks';
            } elseif (str_contains($cwRaw, 'day')) {
                preg_match('/(\d+)/', $cwRaw, $m);
                $n = (int) ($m[1] ?? 1);
                $cancelWindowHuman = $n === 1 ? '1 day' : $n.' days';
            } else {
                preg_match('/(\d+)/', $cwRaw, $m);
                $n = (int) ($m[1] ?? 48);
                $cancelWindowHuman = $n === 1 ? '1 hour' : $n.' hours';
            }

            $reschedulePolicy = strtolower((string) ($userDetail->reschedule_times ?? 'never'));
            $rescheduleText = match ($reschedulePolicy) {
                'once' => 'The artist allows you to reschedule your appointment once',
                'twice' => 'The artist allows you to reschedule your appointment twice',
                'unlimited' => 'The artist allows unlimited reschedules before the cancellation deadline',
                default => 'Rescheduling is not allowed for this artist',
            };
          @endphp
          <a id="ctaDesktop" href="{{route('public.tattoo.book', ['user_name' => $userDetail->user_name, 'tattoo_slug' => $tattoo->slug])}}" class="block w-full py-3.5 bg-primary text-on-primary rounded-full text-base font-semibold hover:bg-primary-container transition-colors text-center shadow-md shadow-primary/20">
            Book Now — €{{ $tattoo->min_price }} — €{{ $tattoo->max_price }}
          </a>
          @php
            $depositType = (string) ($userDetail->minimum_deposit_type ?? 'percentage');
            $depositAmountRaw = (float) ($userDetail->minimum_deposit_amount ?? 30);
            if ($depositType === 'amount') {
                $depositLabel = 'EUR '.number_format(max(0, $depositAmountRaw), 2);
            } else {
                $depositType = 'percentage';
                $depositLabel = rtrim(rtrim(number_format(max(0, $depositAmountRaw), 2, '.', ''), '0'), '.').'%';
            }
          @endphp
          <p class="text-xs text-on-surface-variant text-center mt-2">{{ $depositLabel }} deposit required to secure your booking</p>
          <!-- Cancellation Policy (expandable) -->
          <div class="mt-3" id="detailCancPolicySection">
            <button onclick="toggleDetailCancPolicy()" class="text-xs text-on-surface-variant hover:text-primary transition-colors flex items-center gap-1 mx-auto">
              📋 Cancellation Policy
              <span class="material-symbols-outlined text-[16px] transition-transform" id="detailCancArrow" style="transition: transform 0.2s ease;">expand_more</span>
            </button>
            <div class="hidden mt-2 bg-surface-container-low rounded-xl p-3 text-left" id="detailCancContent">
              <div class="text-xs text-on-surface-variant space-y-1">
                <p class="font-semibold text-on-surface mb-1.5">Artist's Cancellation Policy:</p>
                <p>• Full refund if canceled at least {{ $cancelWindowHuman }} before your appointment</p>
                <p>• No refund if canceled less than {{ $cancelWindowHuman }} before your appointment</p>
                <p>• {{ $rescheduleText }}</p>
                <p>• No-shows forfeit the full deposit</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- INFO GRID -->
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 mb-10">
      <div class="bg-surface-container-low rounded-xl p-4">
        <div class="flex items-center gap-2 mb-1.5">
          <span class="material-symbols-outlined text-[18px] text-primary">brush</span>
          <span class="text-xs text-on-surface-variant uppercase tracking-wide font-medium">Style</span>
        </div>
        <p id="infoStyle" class="text-sm font-semibold text-on-surface">{{ ucfirst($tattoo->primary_style) }}</p>
      </div>
      <div class="bg-surface-container-low rounded-xl p-4">
        <div class="flex items-center gap-2 mb-1.5">
          <span class="material-symbols-outlined text-[18px] text-primary">palette</span>
          <span class="text-xs text-on-surface-variant uppercase tracking-wide font-medium">Colors</span>
        </div>
        <p id="infoColors" class="text-sm font-semibold text-on-surface">{{ ucfirst($tattoo->color) }}</p>
      </div>
      <div class="bg-surface-container-low rounded-xl p-4">
        <div class="flex items-center gap-2 mb-1.5">
          <span class="material-symbols-outlined text-[18px] text-primary">straighten</span>
          <span class="text-xs text-on-surface-variant uppercase tracking-wide font-medium">Size</span>
        </div>
        <p id="infoSize" class="text-sm font-semibold text-on-surface">{{ $tattoo->min_size }} cm - {{ $tattoo->max_size }} cm</p>
      </div>
      <div class="bg-surface-container-low rounded-xl p-4">
        <div class="flex items-center gap-2 mb-1.5">
          <span class="material-symbols-outlined text-[18px] text-primary">schedule</span>
          <span class="text-xs text-on-surface-variant uppercase tracking-wide font-medium">Est. Time</span>
        </div>
        <p id="infoTime" class="text-sm font-semibold text-on-surface">{{ $tattoo->session_duration }} hour(s)</p>
      </div>
      <div class="bg-surface-container-low rounded-xl p-4">
        <div class="flex items-center gap-2 mb-1.5">
          <span class="material-symbols-outlined text-[18px] text-primary">event_repeat</span>
          <span class="text-xs text-on-surface-variant uppercase tracking-wide font-medium">Sessions</span>
        </div>
        <p id="infoSessions" class="text-sm font-semibold text-on-surface">{{ $tattoo->min_sessions }} - {{ $tattoo->max_sessions }}</p>
      </div>
      <div class="bg-surface-container-low rounded-xl p-4">
        <div class="flex items-center gap-2 mb-1.5">
          <span class="material-symbols-outlined text-[18px] text-primary">body_system</span>
          <span class="text-xs text-on-surface-variant uppercase tracking-wide font-medium">Placement</span>
        </div>
        <p id="infoPlacement" class="text-sm font-semibold text-on-surface">{{ $tattoo->suggested_placement }}</p>
      </div>
    </div>

    <!-- DESCRIPTION -->
    <section class="mb-8">
      <h2 class="text-lg font-bold text-on-surface mb-3">About This Design</h2>
      <p id="designDesc" class="text-on-surface-variant leading-relaxed text-[15px]">
        {!! $tattoo->description !!}
      </p>
    </section>

    <!-- TAGS -->
    <div id="designTags" class="flex flex-wrap gap-2 mb-10">
        {{-- array of tags --}}
        @foreach($tattoo->tags as $tag)
            <span class="text-xs px-3 py-1 rounded-full bg-surface-container-high text-on-surface-variant font-medium">#{{ $tag }}</span>
        @endforeach
    </div>

    <!-- AR TRY-ON -->
    <section class="mb-10 bg-gradient-to-br from-primary via-primary-container to-primary rounded-2xl p-6 relative overflow-hidden">
      <div class="absolute top-0 right-0 w-32 h-32 bg-white/5 rounded-full -translate-y-1/2 translate-x-1/2"></div>
      <div class="absolute bottom-0 left-0 w-24 h-24 bg-white/5 rounded-full translate-y-1/2 -translate-x-1/2"></div>
      <div class="relative flex flex-col sm:flex-row items-start sm:items-center gap-5">
        <div class="flex-1">
          <div class="flex items-center gap-2 mb-2">
            <span class="text-xl">📲</span>
            <h2 class="text-lg font-bold text-white">See this on your body</h2>
          </div>
          <p class="text-white/80 text-sm mb-4 max-w-md">Use AR try-on in the Inkjin app to preview this design on your skin. Move it around, resize it, and find the perfect placement.</p>
          <a href="#" class="inline-flex items-center gap-2 bg-white text-primary font-semibold text-sm px-5 py-2.5 rounded-full hover:bg-white/90 transition-colors shadow-md">
            <span class="material-symbols-outlined text-lg">phone_iphone</span> Open in App
          </a>
        </div>
        <div class="hidden sm:flex flex-col items-center gap-1.5 bg-white/10 rounded-2xl p-5 border border-white/15">
          <span class="material-symbols-outlined text-white text-3xl">view_in_ar</span>
          <div class="flex items-center gap-1">
            <span class="material-symbols-outlined text-amber-300 text-sm">auto_awesome</span>
            <span class="text-xs font-bold text-white uppercase tracking-wide">AR Try-On</span>
            <span class="material-symbols-outlined text-amber-300 text-sm">auto_awesome</span>
          </div>
        </div>
      </div>
    </section>

    <!-- WHAT'S INCLUDED -->
    <section class="mb-10 bg-surface-container-low rounded-2xl p-6">
      <h2 class="text-lg font-bold text-on-surface mb-4">What's Included</h2>
      <ul class="space-y-3 text-[15px] text-on-surface-variant">
        <li class="flex items-start gap-2.5">
          <span class="text-primary mt-0.5">✦</span>
          <span>Custom sizing consultation</span>
        </li>
        <li class="flex items-start gap-2.5">
          <span class="text-primary mt-0.5">✦</span>
          <span>Design placement guidance</span>
        </li>
        <li class="flex items-start gap-2.5">
          <span class="text-primary mt-0.5">✦</span>
          <span>Touch-up session within 3 months</span>
        </li>
        <li class="flex items-start gap-2.5">
          <span class="text-primary mt-0.5">✦</span>
          <span>Aftercare instructions</span>
        </li>
      </ul>
    </section>

    <!-- YOU MIGHT ALSO LIKE -->
    @if($relatedTattoos->count() > 0)
        <section class="mb-8">
            <h2 class="text-lg font-bold text-on-surface mb-4">You Might Also Like</h2>
            <div id="alsoLike" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                @foreach($relatedTattoos as $relatedTattoo)
                    <a href="{{route('public.tattoo', ['user_name' => $userDetail->user_name, 'tattoo_slug' => $relatedTattoo->slug])}}" class="design-card bg-white rounded-xl overflow-hidden shadow-sm border border-outline-variant/50 flex sm:flex-col">
                        <div class="w-28 sm:w-full aspect-square sm:aspect-4-5 bg-gradient-to-br from-amber-100 via-amber-200 to-amber-300 relative flex-shrink-0">
                            <div class="absolute inset-0 flex items-center justify-center">
                                <img src="{{ asset($relatedTattoo->image) }}" alt="{{ $relatedTattoo->title }}" class="w-full h-full object-cover">
                            </div>
                        </div>
                        <div class="p-3 sm:p-4 flex flex-col justify-center">
                            <h3 class="font-bold text-on-surface text-sm mb-1">{{ $relatedTattoo->title }}</h3>
                            <span class="text-xs px-2 py-0.5 rounded-full bg-secondary-container text-secondary font-medium w-fit mb-1.5">{{ ucwords(str_replace('-', ' ', $relatedTattoo->primary_style)) }}</span>
                            <p class="text-sm font-semibold text-primary">€{{ $relatedTattoo->min_price }} — €{{ $relatedTattoo->max_price }}</p>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

  </main>

  <!-- FOOTER -->
  <footer class="border-t border-outline-variant py-8">
    <div class="max-w-[900px] mx-auto px-4 sm:px-6 flex flex-col sm:flex-row items-center justify-between gap-4">
      <div class="flex items-center gap-2 text-on-surface-variant text-sm">
        <div class="w-6 h-6 rounded bg-primary flex items-center justify-center">
          <span class="text-white text-[10px] font-extrabold">IJ</span>
        </div>
        <span>Powered by <strong class="text-on-surface">Inkjin</strong></span>
        <span class="text-outline">·</span>
        <span>Book & Pay</span>
      </div>
      <div class="flex items-center gap-4 text-sm text-on-surface-variant">
        <a href="#" class="hover:text-primary transition-colors">Privacy</a>
        <a href="#" class="hover:text-primary transition-colors">Terms</a>
      </div>
    </div>
  </footer>

  <!-- MOBILE STICKY CTA -->
  <div class="md:hidden fixed bottom-0 left-0 right-0 z-50 bg-surface/95 backdrop-blur-sm border-t border-outline-variant px-4 py-3">
    <a id="ctaMobile" href="{{route('public.tattoo.book', ['user_name' => $userDetail->user_name, 'tattoo_slug' => $tattoo->slug])}}" class="block w-full py-3.5 bg-primary text-on-primary rounded-full text-base font-semibold hover:bg-primary-container transition-colors text-center shadow-lg shadow-primary/25">
      Book Now — €{{ $tattoo->min_price }} — €{{ $tattoo->max_price }}
    </a>
    <p class="text-[11px] text-on-surface-variant text-center mt-1.5">30% deposit required to secure your booking</p>
    <button onclick="toggleDetailCancPolicy()" class="text-[11px] text-on-surface-variant hover:text-primary transition-colors flex items-center gap-0.5 mx-auto mt-1">
      📋 Cancellation Policy
    </button>
  </div>

  <!-- SHARE TOAST -->
  <div id="shareToast" class="fixed bottom-24 md:bottom-8 left-1/2 -translate-x-1/2 z-50 bg-inverse-surface text-inverse-on-surface px-5 py-2.5 rounded-full text-sm font-medium shadow-xl transition-all duration-300 opacity-0 pointer-events-none translate-y-2">
    Link copied to clipboard!
  </div>

  <script>
    function shareDesign() {
      const url = window.location.href;
      if (navigator.share) {
        navigator.share({ title: '{{ $tattoo->title }} — {{ $userDetail->user->first_name }} {{ $userDetail->user->last_name }}', url: url }).catch(() => {});
      } else if (navigator.clipboard) {
        navigator.clipboard.writeText(url).then(() => showToast());
      }
    }
    
    function toggleDetailCancPolicy() {
      const content = document.getElementById('detailCancContent');
      const arrow = document.getElementById('detailCancArrow');
      if (!content) return;
      if (content.classList.contains('hidden')) {
        content.classList.remove('hidden');
        if (arrow) arrow.style.transform = 'rotate(180deg)';
        content.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
      } else {
        content.classList.add('hidden');
        if (arrow) arrow.style.transform = 'rotate(0deg)';
      }
    }

    function showToast() {
      const toast = document.getElementById('shareToast');
      toast.classList.remove('opacity-0', 'pointer-events-none', 'translate-y-2');
      toast.classList.add('opacity-100', 'translate-y-0');
      setTimeout(() => {
        toast.classList.add('opacity-0', 'pointer-events-none', 'translate-y-2');
        toast.classList.remove('opacity-100', 'translate-y-0');
      }, 2000);
    }
  </script>
</body>
</html>
