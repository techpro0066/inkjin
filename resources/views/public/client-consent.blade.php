<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Consent Form — {{ $payload['artist']['name'] ?? 'Bookpay' }}</title>
  <link rel="icon" href="{{ asset('assets/img/favicon/favicon.png') }}">
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    body { margin: 0; }
    .consent-phone-country-field .select2-container { width: 100% !important; z-index: 1; }
    .select2-container--open { z-index: 10060 !important; }
    .consent-phone-country-field .select2-container--default .select2-selection--single {
      min-height: 38px;
      height: 38px;
      border: 1px solid #d6d3d1;
      border-radius: 0.5rem;
      background: #fff;
    }
    .consent-phone-country-field .select2-container--default .select2-selection--single .select2-selection__rendered {
      line-height: 36px;
      padding-left: 0.75rem;
      padding-right: 1.75rem;
      font-size: 0.875rem;
      color: #1C1B18;
    }
    .consent-phone-country-field .select2-container--default .select2-selection--single .select2-selection__arrow {
      height: 36px;
      right: 6px;
    }
    .consent-phone-country-field .select2-container--default.select2-container--focus .select2-selection--single,
    .consent-phone-country-field .select2-container--default.select2-container--open .select2-selection--single {
      border-color: #1E3A34;
      box-shadow: 0 0 0 2px rgba(30, 58, 52, 0.2);
    }
    .select2-dropdown {
      border: 1px solid #d6d3d1;
      border-radius: 0.5rem;
      overflow: hidden;
    }
    .select2-container--default .select2-results__option {
      font-size: 0.875rem;
      padding: 0.5rem 0.75rem;
    }
    .select2-container--default .select2-results__option--highlighted.select2-results__option--selectable {
      background-color: #1E3A34;
      color: #fff;
    }
    .select2-container--default .select2-search--dropdown .select2-search__field {
      border: 1px solid #d6d3d1;
      border-radius: 0.375rem;
      font-size: 0.875rem;
    }
  </style>
</head>
<body>
  <div id="root"></div>

  <script>
    window.CLIENT_CONSENT = @json($payload);
  </script>
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
  <script crossorigin src="https://unpkg.com/react@18.3.1/umd/react.production.min.js"></script>
  <script crossorigin src="https://unpkg.com/react-dom@18.3.1/umd/react-dom.production.min.js"></script>
  <script src="{{ asset('js/client-consent-view.js') }}?v={{ @filemtime(public_path('js/client-consent-view.js')) }}"></script>
</body>
</html>
