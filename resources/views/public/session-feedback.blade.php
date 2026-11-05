<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ $alreadyReviewed ? 'Thanks for your feedback' : 'Submit feedback' }} — Bookpay</title>
  <style>
    body{margin:0;background:#FFF6FF;color:#1A1A1A;font:400 15px/1.55 'Plus Jakarta Sans',system-ui,sans-serif}
    .wrap{max-width:480px;margin:0 auto;padding:48px 20px}
    .card{background:#fff;border:1px solid #EEE9F0;border-radius:14px;padding:28px 24px}
    .brand{font-family:'Space Grotesk',system-ui,sans-serif;font-weight:700;font-size:26px;letter-spacing:-.055em;margin:0 0 22px}
    h1{font-size:22px;margin:0 0 10px;letter-spacing:-.02em}
    p{margin:0;color:#3d3942}
  </style>
</head>
<body>
  <div class="wrap">
    <div class="card">
      <p class="brand">bookpay</p>
      @if ($alreadyReviewed)
        <h1>Thanks — we got your feedback</h1>
        <p>Your answers about {{ $artistName }} help them improve. We’ll keep building Bookpay from what you share.</p>
      @else
        <h1>Submit feedback</h1>
        <p>Tell us how your session with {{ $artistName }} went. The feedback form will open here.</p>
      @endif
    </div>
  </div>
</body>
</html>
