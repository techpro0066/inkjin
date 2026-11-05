{{-- Studio New booking → payment link modal --}}
@php
  $currencySymbol = $currencySymbol ?? '€';
  $canPayLinks = ! empty($canCreatePaymentLinks);
  $studioArtistsForModal = $studioArtistsForModal ?? collect();
  $studioNameForModal = $studioName ?? 'your studio';
@endphp

<div id="m-pl" hidden role="dialog" aria-modal="true" aria-labelledby="pl-t">
  <div class="pl-pb">
    <div class="pl-ph">
      <div class="pl-ic"><span class="ms" id="pl-ic">event_available</span></div>
      <div>
        <h2 id="pl-t">New booking</h2>
        <a class="help-q" href="https://help.inkjin.com/en/articles/17338491-studio-dashboard-new-booking" target="_blank" rel="noopener" title="Help with New booking" aria-label="Help with New booking (opens in a new tab)">
          <span class="ms">help</span>
        </a>
        <div class="pl-sub" id="pl-sub">Makes a payment link. The booking is added once the client pays.</div>
      </div>
      <button type="button" class="pl-px" data-plx aria-label="Close"><span class="ms">close</span></button>
    </div>

    <div class="pl-pbd" id="pl-form">
      @if (! $canPayLinks)
        <div class="pl-warn">
          {{ $paymentLinksBlockedMessage ?? 'Complete your studio profile and add an artist before creating bookings.' }}
          @if (! empty($paymentLinksBlockedActionUrl))
            <div style="margin-top:6px"><a href="{{ $paymentLinksBlockedActionUrl }}">{{ $paymentLinksBlockedActionLabel ?? 'Continue setup' }}</a></div>
          @endif
        </div>
      @endif

      <div id="pl-artw">
        <span class="pl-lb">Artist <i>*</i></span>
        <div class="pl-fi" id="pl-artf">
          <select id="pl-art" class="js-select2" aria-label="Artist" data-placeholder="Choose an artist" data-searchable="true" @disabled(! $canPayLinks)>
            <option value="">Choose an artist</option>
            @foreach ($studioArtistsForModal as $artist)
              <option value="{{ (int) $artist['user_id'] }}"
                data-first="{{ e($artist['first_name']) }}"
                data-name="{{ e($artist['name']) }}"
                data-pay="{{ e($artist['pay_note'] ?? '') }}">
                {{ $artist['option_label'] ?? $artist['name'] }}
              </option>
            @endforeach
          </select>
        </div>
        <div class="pl-err" id="pl-e-art" hidden>Choose an artist.</div>
        <div class="pl-note" id="pl-pay" hidden>
          <span class="ms">payments</span>
          <span></span>
        </div>
      </div>

      <span class="pl-lb">Amount <i>*</i></span>
      <div class="pl-fi" id="pl-amtf">
        <span class="pl-cur">{{ $currencySymbol }}</span>
        <input id="pl-amt" type="text" inputmode="decimal" placeholder="0" aria-label="Amount" @disabled(! $canPayLinks)>
      </div>
      <div class="pl-err" id="pl-e-amt" hidden></div>

      <div class="pl-seg" id="pl-type">
        <button type="button" data-v="dep" class="pl-on" @disabled(! $canPayLinks)>Deposit</button>
        <button type="button" data-v="full" @disabled(! $canPayLinks)>Full payment</button>
      </div>
      <div class="pl-err" id="pl-e-payment_type" hidden></div>

      <span class="pl-lb">Title <i>*</i></span>
      <div class="pl-fi" id="pl-titlef">
        <input id="pl-title" type="text" placeholder="e.g. Deposit · peony, forearm" aria-label="Title" @disabled(! $canPayLinks)>
      </div>
      <div class="pl-err" id="pl-e-title" hidden></div>

      <span class="pl-lb" id="pl-when-lb">When</span>
      <div class="pl-seg" id="pl-when" style="margin-top:0">
        <button type="button" data-v="pick" class="pl-on" @disabled(! $canPayLinks)><span class="ms">calendar_month</span>Pick a date</button>
        <button type="button" data-v="walk" @disabled(! $canPayLinks)><span class="ms">directions_walk</span>Walk-in, now</button>
      </div>
      <div id="pl-walkn" class="pl-note" hidden>
        <span class="ms">qr_code_2</span>
        <span>Starts today, now. After you make the link, show the QR code. The client scans it and pays on their phone.</span>
      </div>

      <div id="pl-datew">
        <span class="pl-lb">Date and time <i>*</i></span>
        <div class="pl-fi pl-dt" id="pl-date" tabindex="0" role="button" @if(! $canPayLinks) aria-disabled="true" @endif>
          <span class="pl-ph0" id="pl-datet">Select date and time</span>
          <span class="ms">calendar_month</span>
        </div>
        <div class="pl-err" id="pl-e-date" hidden></div>
        <div class="pl-cal" id="pl-cal" hidden></div>
        <div class="pl-slots" id="pl-slots">Pick an artist to see their free times.</div>
      </div>

      <span class="pl-lb">Session duration <i>*</i></span>
      <div class="pl-chips" id="pl-dur">
        <button type="button" data-v="2h" @disabled(! $canPayLinks)>2h</button>
        <button type="button" data-v="3h" class="pl-on" @disabled(! $canPayLinks)>3h</button>
        <button type="button" data-v="4h" @disabled(! $canPayLinks)>4h</button>
        <button type="button" data-v="half-day" @disabled(! $canPayLinks)>Half day</button>
        <button type="button" data-v="full-day" @disabled(! $canPayLinks)>Full day</button>
      </div>
      <div class="pl-err" id="pl-e-dur" hidden></div>

      <div class="pl-g2">
        <div id="pl-totw">
          <span class="pl-lb">Total price <i>*</i></span>
          <div class="pl-fi" id="pl-totf">
            <span class="pl-cur">{{ $currencySymbol }}</span>
            <input id="pl-tot" type="text" inputmode="decimal" placeholder="0" aria-label="Total price" @disabled(! $canPayLinks)>
          </div>
          <div class="pl-err" id="pl-e-tot" hidden></div>
        </div>
        <div>
          <span class="pl-lb">Link expires <i>*</i></span>
          <div class="pl-chips" id="pl-exp">
            <button type="button" data-v="2 days" @disabled(! $canPayLinks)>2 days</button>
            <button type="button" data-v="3 days" @disabled(! $canPayLinks)>3 days</button>
            <button type="button" data-v="7 days" class="pl-on" @disabled(! $canPayLinks)>7 days</button>
          </div>
          <div id="pl-expw" class="pl-expw" hidden>At the end of today</div>
          <div class="pl-err" id="pl-e-exp" hidden></div>
        </div>
      </div>

      <div class="pl-sum" id="pl-sum" hidden></div>
      <div class="pl-err" id="pl-e-form" hidden style="margin-top:12px"></div>
    </div>

    <div class="pl-pbd" id="pl-done" hidden>
      <div class="pl-qr"><img id="pl-qr" alt="QR code" width="168" height="168"></div>
      <div style="text-align:center;font-size:13.5px;margin-bottom:14px" id="pl-dtxt">Send the link to the client, or show the QR code.</div>
      <span class="pl-lb">Link</span>
      <div class="pl-lnk">
        <div class="pl-fi"><span id="pl-url"></span></div>
        <button type="button" class="pl-pbtn pl-gh" data-plcopy><span class="ms">content_copy</span>Copy</button>
      </div>
      <div class="pl-note" id="pl-dinfo" style="margin-top:14px">
        <span class="ms">info</span>
        <span>The booking shows in the artist's calendar and in your Bookings once the client pays.</span>
      </div>
    </div>

    <div class="pl-pf" id="pl-ff">
      <button type="button" class="pl-pbtn pl-gh" data-plx>Cancel</button>
      <button type="button" class="pl-pbtn" id="pl-go" @disabled(! $canPayLinks)>
        <span class="ms">link</span><span id="pl-got">Generate link</span>
      </button>
    </div>
    <div class="pl-pf" id="pl-df" hidden>
      <button type="button" class="pl-pbtn pl-gh" data-plcopy><span class="ms">content_copy</span>Copy link</button>
      <button type="button" class="pl-pbtn" data-pldone>Done</button>
    </div>
  </div>
</div>
