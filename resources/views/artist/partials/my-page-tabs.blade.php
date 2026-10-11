@php
  $myPageTab = $myPageTab ?? 'about';
  $designsCount = (int) ($designsCount ?? 0);
  $portfolioCount = (int) ($portfolioCount ?? 0);
  $faqCount = (int) ($faqCount ?? 0);
@endphp
<div class="tabs">
  <a href="{{ route('personal-page.index') }}" class="{{ $myPageTab === 'about' ? 'on' : '' }}">About</a>
  <a href="{{ route('artist-designs.index') }}" class="{{ $myPageTab === 'designs' ? 'on' : '' }}">
    Designs<span class="n{{ $designsCount === 0 ? ' zero' : '' }}">{{ $designsCount }}</span>
  </a>
  <a href="{{ route('portfolio.index') }}" class="{{ $myPageTab === 'portfolio' ? 'on' : '' }}">
    Portfolio<span class="n{{ $portfolioCount === 0 ? ' zero' : '' }}">{{ $portfolioCount }}</span>
  </a>
  <a href="{{ route('artist.faq.index') }}" class="{{ $myPageTab === 'faq' ? 'on' : '' }}">
    FAQ<span class="n{{ $faqCount === 0 ? ' zero' : '' }}">{{ $faqCount }}</span>
  </a>
  <a href="{{ route('personal-page.policies') }}" class="{{ $myPageTab === 'policies' ? 'on' : '' }}">Policies</a>
  <a href="{{ route('personal-page.appearance') }}" class="{{ $myPageTab === 'appearance' ? 'on' : '' }}">Appearance</a>
</div>
