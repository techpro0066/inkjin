{{-- Shared Bookpay API base for public pages that may be reverse-proxied from inkjin.com --}}
<script>
  window.BOOKPAY_BASE_URL = @json(rtrim((string) (config('inkjin.bookpay_public_url') ?: config('app.url')), '/'));
  window.bookpayUrl = function (path) {
    var base = String(window.BOOKPAY_BASE_URL || '').replace(/\/$/, '');
    var p = String(path || '');
    if (p === '') {
      return base;
    }
    if (/^https?:\/\//i.test(p)) {
      return p;
    }
    if (p.charAt(0) !== '/') {
      p = '/' + p;
    }
    return base + p;
  };
</script>
