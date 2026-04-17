<html class="light" lang="en">

<head>
  <meta charset="utf-8" />
  <meta content="width=device-width, initial-scale=1.0" name="viewport" />
  <title>@yield('title')</title>
  <meta name="description"
    content="Log in to Inkjin Book & Pay — 100% free for tattoo artists. Manage bookings, payments, and client inquiries.">
  <link rel="icon" href="{{ asset('design/images/icons/favicon.png') }}">
  <link href="{{ asset('design/css/inkjin_main.css') }}" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&amp;display=swap"
    rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap"
    rel="stylesheet">
  <link
    href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap"
    rel="stylesheet" />
  <link
    href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap"
    rel="stylesheet" />
  <script id="tailwind-config">
    tailwind.config = {
      darkMode: "class",
      theme: {
        extend: {
          colors: {
            "surface-container-high": "#ece6ef",
            "surface-container-lowest": "#ffffff",
            "surface-container": "#f2ecf5",
            "background": "#fdf7ff",
            "primary": "#1a1a1a",
            "surface-dim": "#ded8e1",
            "on-surface-variant": "#494552",
            "secondary-fixed": "#e8ddff",
            "on-secondary-fixed-variant": "#4a4168",
            "inverse-surface": "#322f36",
            "error-container": "#ffdad6",
            "inverse-on-surface": "#f5eff8",
            "tertiary": "#452200",
            "surface-container-low": "#f8f1fb",
            "surface": "#fdf7ff",
            "secondary-fixed-dim": "#ccc0ee",
            "on-tertiary-fixed": "#2e1500",
            "on-error": "#ffffff",
            "on-primary-container": "#e0e0e0",
            "secondary": "#625881",
            "inverse-primary": "#cebdff",
            "primary-fixed": "#e0e0e0",
            "outline": "#7a7583",
            "tertiary-fixed": "#ffdcc2",
            "tertiary-container": "#653500",
            "on-secondary": "#ffffff",
            "on-primary": "#ffffff",
            "on-tertiary-fixed-variant": "#6c3a04",
            "error": "#ba1a1a",
            "tertiary-fixed-dim": "#ffb77b",
            "surface-bright": "#fdf7ff",
            "surface-tint": "#664db1",
            "on-error-container": "#93000a",
            "on-primary-fixed": "#111111",
            "primary-fixed-dim": "#cebdff",
            "on-tertiary-container": "#e49e62",
            "on-primary-fixed-variant": "#444444",
            "primary-container": "#333333",
            "on-surface": "#1c1b21",
            "outline-variant": "#cac4d3",
            "on-tertiary": "#ffffff",
            "surface-container-highest": "#e6e0ea",
            "on-background": "#1c1b21",
            "secondary-container": "#ddd0ff",
            "on-secondary-fixed": "#1e1539",
            "surface-variant": "#e6e0ea",
            "on-secondary-container": "#615780"
          },
          fontFamily: {
            "headline": ["Plus Jakarta Sans"],
            "body": ["Plus Jakarta Sans"],
            "label": ["Plus Jakarta Sans"]
          },
          borderRadius: { "DEFAULT": "0.25rem", "lg": "0.5rem", "xl": "0.75rem", "full": "9999px" },
        },
      },
    }
  </script>
  <style>
    body {
      font-family: 'Plus Jakarta Sans', sans-serif;
      background-color: #fdf7ff;
    }

    .material-symbols-outlined {
      font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
    }

    .glass-panel {
      background: rgba(255, 255, 255, 0.8);
      backdrop-filter: blur(20px);
    }

    .brand-gradient {
      background: linear-gradient(135deg, #111111 0%, #333333 100%);
    }
  </style>
  @stack('styles')
</head>

  <body class="bg-background text-on-surface min-h-screen flex flex-col">
    @yield('content')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    @stack('scripts')
  </body>
</html>

