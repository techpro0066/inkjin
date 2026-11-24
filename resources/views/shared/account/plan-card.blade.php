@if($isFounding)
  <section class="account-plan-card" aria-labelledby="accountPlanTitle">
    <div class="account-plan-header">
      <div>
        <p class="account-plan-eyebrow">Your plan</p>
        <div class="account-plan-title-row">
          <h2 id="accountPlanTitle">Founding</h2>
          <span class="account-plan-badge"><svg class="fmark-i" viewBox="0 0 24 24" width="12" height="12" aria-hidden="true" focusable="false"><g stroke="currentColor" stroke-width="3.2" stroke-linecap="round"><path d="M12 3.5v17"></path><path d="M4.6 7.75l14.8 8.5"></path><path d="M4.6 16.25l14.8-8.5"></path></g></svg>Founding {{auth()->user()->role}}</span>
          <span class="account-plan-current">Current plan</span>
        </div>
        <p class="account-plan-description">Today’s terms, for as long as your account is active.</p>
      </div>
    </div>

    <div class="account-plan-terms" aria-label="Founding plan terms">
      <div class="account-plan-term"><strong>No subscription</strong><span>€0/month</span></div>
      <div class="account-plan-term"><strong>No commission</strong><span>0% per booking</span></div>
      @if(auth()->user()->role === 'artist')
        <div class="account-plan-term"><strong>€10 booking fee</strong><span>Paid by your client</span></div>
      @elseif(auth()->user()->role === 'studio')
        <div class="account-plan-term"><strong>Artists</strong><span>Up to 6 artists · plus guests</span></div>
      @endif
      <div class="account-plan-term"><strong>Card fees</strong><span>Stripe’s fees, paid by you</span></div>
    </div>

    <p class="account-plan-note">You're a Founding {{auth()->user()->role}}. You'll always have access to this plan, whatever plans we add may later.</p>
  </section>
@else
  <section class="account-plan-card account-plan-empty" aria-labelledby="accountPlanTitle">
    <p class="account-plan-eyebrow">Your plan</p>
    <h2 id="accountPlanTitle">Plan details aren’t available yet</h2>
    <p>Your current plan and its terms will appear here when plan details are available for your account.</p>
  </section>
@endif
