<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Browse all tattoo artists on Inkjin">
  <title>All Artists | Inkjin</title>
  <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            "primary": "#310f7a",
            "primary-container": "#482d91",
            "on-primary": "#ffffff",
            "surface": "#fdf7ff",
            "surface-container": "#f2ecf5",
            "surface-container-low": "#f8f1fb",
            "on-surface": "#1c1b21",
            "on-surface-variant": "#494552",
            "outline-variant": "#cac4d3",
          },
          fontFamily: {
            "sans": ["Plus Jakarta Sans", "system-ui", "sans-serif"],
          },
        },
      },
    };
  </script>
  <style>
    body { font-family: 'Plus Jakarta Sans', sans-serif; }
  </style>
</head>
<body class="bg-surface text-on-surface min-h-screen">
  <header class="sticky top-0 z-30 bg-surface/90 backdrop-blur border-b border-outline-variant/40">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-3 flex items-center justify-between">
      <a href="{{ route('public.artists.list') }}" class="text-xl font-extrabold tracking-tight text-primary">inkjin</a>
      <a href="{{ url('/') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-on-surface-variant hover:text-on-surface">
        <span class="material-symbols-outlined text-[18px]">{{Auth::check() ? 'home' : 'login'}}</span>
        {{Auth::check() ? 'Dashboard' : 'Login'}}
      </a>
    </div>
  </header>

  <main class="max-w-7xl mx-auto px-4 sm:px-6 py-10">
    <section class="mb-8">
      <div class="rounded-3xl border border-outline-variant/30 bg-gradient-to-br from-primary/95 to-primary-container text-white p-6 sm:p-8">
        <p class="text-xs uppercase tracking-[0.16em] text-white/70 mb-2">Explore</p>
        <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight">Discover Tattoo Artists</h1>
        <p class="mt-2 text-sm sm:text-base text-white/85">Find artists by style, city, and studio in one place.</p>
      </div>
    </section>

    <section class="mb-8">
      <form method="GET" action="{{ route('public.artists.list') }}" class="bg-white border border-outline-variant/30 rounded-2xl p-4 sm:p-5">
        <label for="q" class="text-xs font-semibold uppercase tracking-wide text-on-surface-variant">Search artists</label>
        <div class="mt-2 flex gap-2">
          <input
            id="q"
            name="q"
            value="{{ $search }}"
            type="text"
            placeholder="Name, username, studio, city..."
            class="flex-1 rounded-xl border border-outline-variant/40 bg-surface-container-low px-4 py-2.5 text-sm focus:border-primary focus:ring-primary/30">
          <button type="submit" class="rounded-xl bg-primary text-on-primary px-4 py-2.5 text-sm font-semibold hover:bg-primary-container">Search</button>
        </div>
      </form>
    </section>

    @if($artists->isEmpty())
      <section class="rounded-2xl border border-outline-variant/30 bg-white p-10 text-center">
        <p class="text-lg font-bold text-on-surface mb-1">No artists found</p>
        <p class="text-sm text-on-surface-variant">Try changing your search and check again.</p>
      </section>
    @else
      <section class="mb-4 flex items-center justify-between">
        <p class="text-sm text-on-surface-variant">
          <span class="font-semibold text-on-surface">{{ $artists->count() }}</span>
          {{ \Illuminate\Support\Str::plural('artist', $artists->count()) }} available
        </p>
      </section>
      <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
        @foreach($artists as $artist)
          @php
            $avatar = !empty($artist['avatar']) ? asset(ltrim((string) $artist['avatar'], '/')) : '';
            $initials = strtoupper(substr((string) ($artist['display_name'] ?? 'A'), 0, 1));
            $location = trim(collect([$artist['city'] ?? '', $artist['country'] ?? ''])->filter()->join(', '));
            $status = (string) ($artist['availability_status'] ?? 'closed');
            $statusLabel = match ($status) {
              'design_custom' => 'Design + Custom',
              'design_only' => 'Design Only',
              'custom_only' => 'Custom Only',
              default => 'Closed',
            };
            $statusCls = $status === 'closed'
              ? 'bg-red-50 text-red-700 ring-red-200'
              : 'bg-emerald-50 text-emerald-700 ring-emerald-200';
          @endphp
          <a href="{{ route('public.artist', ['username' => $artist['username']]) }}"
             class="group rounded-2xl border border-outline-variant/30 bg-white p-5 hover:shadow-lg hover:shadow-primary/10 hover:-translate-y-0.5 transition-all">
            <div class="flex items-start gap-4">
              @if($avatar !== '')
                <img src="{{ $avatar }}" alt="{{ $artist['display_name'] }}" class="w-16 h-16 rounded-2xl object-cover border border-outline-variant/20">
              @else
                <div class="w-16 h-16 rounded-2xl bg-surface-container flex items-center justify-center text-primary font-extrabold text-xl border border-outline-variant/20">{{ $initials }}</div>
              @endif
              <div class="min-w-0 flex-1">
                <h3 class="text-base font-extrabold text-on-surface group-hover:text-primary transition-colors truncate">{{ $artist['display_name'] }}</h3>
                <p class="text-xs text-on-surface-variant mt-0.5 truncate">{{ '@'.$artist['username'] }}</p>
                <div class="mt-2">
                  <span class="inline-flex items-center text-[11px] font-semibold px-2 py-1 rounded-full ring-1 ring-inset {{ $statusCls }}">{{ $statusLabel }}</span>
                </div>
              </div>
            </div>

            <div class="mt-4 space-y-1.5">
              @if(!empty($artist['studio_name']))
                <p class="text-sm text-on-surface truncate"><span class="text-on-surface-variant">Studio:</span> {{ $artist['studio_name'] }}</p>
              @endif
              @if($location !== '')
                <p class="text-sm text-on-surface truncate"><span class="text-on-surface-variant">Location:</span> {{ $location }}</p>
              @endif
              @if(!empty($artist['primary_style']))
                <p class="text-sm text-on-surface truncate"><span class="text-on-surface-variant">Style:</span> {{ $artist['primary_style'] }}</p>
              @endif
            </div>

            @if(!empty($artist['tagline']) || !empty($artist['description']))
              <p class="mt-4 text-sm text-on-surface-variant line-clamp-2">{{ $artist['tagline'] ?: $artist['description'] }}</p>
            @endif

            <div class="mt-4 pt-4 border-t border-outline-variant/20 flex items-center justify-between">
              <p class="text-xs text-on-surface-variant">
                <span class="font-semibold text-on-surface">{{ (int) ($artist['tattoo_count'] ?? 0) }}</span>
                designs
              </p>
              <span class="inline-flex items-center gap-1 text-xs font-semibold text-primary">
                View profile
                <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
              </span>
            </div>
          </a>
        @endforeach
      </section>
    @endif
  </main>
</body>
</html>

