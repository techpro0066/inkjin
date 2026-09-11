<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Consent Form — {{ $payload['artist']['name'] ?? 'Bookpay' }}</title>
  <link rel="icon" href="{{ asset('assets/img/favicon/favicon.png') }}">
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    body { margin: 0; }
  </style>
</head>
<body>
  <div id="root"></div>

  <script>
    window.CLIENT_CONSENT = @json($payload);
  </script>
  <script crossorigin src="https://unpkg.com/react@18.3.1/umd/react.production.min.js"></script>
  <script crossorigin src="https://unpkg.com/react-dom@18.3.1/umd/react-dom.production.min.js"></script>
  <script src="{{ asset('js/client-consent-view.js') }}?v={{ @filemtime(public_path('js/client-consent-view.js')) }}"></script>
</body>
</html>
