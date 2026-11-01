{{-- F01-style add/edit guest spot modal (Details tab wired; Sections/Appearance UI stubs) --}}
<div id="guestSpotFormModal" class="modal-backdrop gs-ov" aria-hidden="true">
  <div class="modal xl gs-modal" id="guestSpotFormCard" role="dialog" aria-modal="true" aria-labelledby="guestFormTitle" onclick="event.stopPropagation()">
    <div class="mh gx">
      <div>
        <div class="ic" style="background:#F3E8FF;color:#3E007C;margin-bottom:12px"><span class="ms" id="guestFormIcon">flight_takeoff</span></div>
        <h2 id="guestFormTitle">Add guest spot</h2>
        <div class="muted" id="guestFormSubtitle" style="font-size:13.5px;margin-top:4px">New guest spot</div>
      </div>
      <button type="button" class="gs-x" id="btnCloseGuestSpotForm" aria-label="Close"><span class="ms faint">close</span></button>
    </div>

    <div class="gtabs" role="tablist" id="guestSpotTabs">
      <button type="button" role="tab" class="on" aria-selected="true" data-gt="d"><span class="ms">event_note</span>Details</button>
      <button type="button" role="tab" aria-selected="false" data-gt="s"><span class="ms">view_agenda</span>Sections</button>
      <button type="button" role="tab" aria-selected="false" data-gt="l"><span class="ms">palette</span>Appearance</button>
    </div>

    <form id="guestSpotForm" class="gs-form">
      @csrf
      <input type="hidden" id="guest_editing_id" value="">
      <input type="hidden" id="guest_status" name="status" value="available">

      <div class="mb">
        {{-- Details --}}
        <div class="gtp" data-gp="d">
          <div class="gs-details-grid">
            <div>
              <div class="card" style="margin-bottom:14px">
                <div class="ch">
                  <div>
                    <h3>Status</h3>
                    <div class="faint" style="font-size:12.5px;margin-top:2px">Completed is set automatically after the last day</div>
                  </div>
                </div>
                <div style="padding:18px 22px">
                  <div class="tgrid" id="guestStatusCards">
                    <label class="po sel" data-status="available" onclick="selectGuestStatus(this)">
                      <input type="radio" name="gst" value="available" checked>
                      <div style="flex:1"><b>Available</b><div class="d">Open for bookings</div></div>
                    </label>
                    <label class="po" data-status="planned" onclick="selectGuestStatus(this)">
                      <input type="radio" name="gst" value="planned">
                      <div style="flex:1"><b>Planned</b><div class="d">Shows as coming soon. Not bookable yet</div></div>
                    </label>
                  </div>
                  <p id="status_error" class="text-error hidden"></p>
                </div>
              </div>

              <div class="card" style="margin-bottom:14px">
                <div class="ch"><div><h3>Location</h3></div></div>
                <div style="padding:18px 22px">
                  <div class="gs-2col">
                    <div>
                      <span class="fl">City <span style="color:#C62828">*</span></span>
                      <input class="in" type="text" id="guest_city" name="city" maxlength="255" placeholder="e.g. London">
                      <p id="city_error" class="text-error hidden"></p>
                    </div>
                    <div>
                      <span class="fl">Country <span style="color:#C62828">*</span></span>
                      <input class="in" type="text" id="guest_country" name="country" maxlength="255" placeholder="e.g. United Kingdom">
                      <p id="country_error" class="text-error hidden"></p>
                    </div>
                    <div>
                      <span class="fl">From <span style="color:#C62828">*</span></span>
                      <input class="in" type="date" id="guest_from" name="from_date" placeholder="Select date" data-ph="Select date" aria-label="From date">
                      <p id="from_date_error" class="text-error hidden"></p>
                    </div>
                    <div>
                      <span class="fl">To <span style="color:#C62828">*</span></span>
                      <input class="in" type="date" id="guest_to" name="to_date" placeholder="Select date" data-ph="Select date" aria-label="To date">
                      <p id="to_date_error" class="text-error hidden"></p>
                    </div>
                  </div>
                  <div id="guestAvailableFields">
                    <div class="gs-2col" style="margin-top:12px">
                      <div>
                        <span class="fl">Start time <span class="gs-req" style="color:#C62828">*</span></span>
                        <input class="in" type="time" id="guest_start_time" name="start_time" placeholder="Select time" data-ph="Select time" aria-label="Start time">
                        <p id="start_time_error" class="text-error hidden"></p>
                      </div>
                      <div>
                        <span class="fl">End time <span class="gs-req" style="color:#C62828">*</span></span>
                        <input class="in" type="time" id="guest_end_time" name="end_time" placeholder="Select time" data-ph="Select time" aria-label="End time">
                        <p id="end_time_error" class="text-error hidden"></p>
                      </div>
                    </div>
                    <div class="help">The hours you tattoo each day of the guest spot.</div>
                  </div>
                </div>
              </div>

              <div class="card" style="margin-bottom:14px" id="guestStudioCard">
                <div class="ch"><div><h3>Guest studio</h3></div></div>
                <div style="padding:18px 22px">
                  <div class="gs-stack">
                    <div>
                      <span class="fl">Studio name <span class="gs-req" style="color:#C62828">*</span></span>
                      <input class="in" type="text" id="guest_studio_name" name="guest_studio_name" maxlength="255" placeholder="e.g. Origin Tattoo London">
                      <p id="guest_studio_name_error" class="text-error hidden"></p>
                    </div>
                    <div>
                      <span class="fl">Address <span class="gs-req" style="color:#C62828">*</span></span>
                    <div id="guestAddressSearchWrapper">
                      <input class="in" type="text" id="guest_address_search" autocomplete="off" placeholder="Start typing the studio address…">
                    </div>
                      <div class="help">Select from Google suggestions to auto-fill the fields below.</div>
                      <p id="guest_studio_address_error" class="text-error hidden"></p>
                    </div>
                    <input type="hidden" name="guest_studio_address" id="guest_studio_address" value="">
                    <input type="hidden" name="guest_latitude" id="guest_latitude" value="">
                    <input type="hidden" name="guest_longitude" id="guest_longitude" value="">
                    <div class="gs-2col">
                      <div>
                        <span class="fl">Street number <span class="gs-req" style="color:#C62828">*</span></span>
                        <input class="in" type="text" id="guest_street_number" name="guest_street_number" placeholder="e.g. 42">
                        <p id="guest_street_number_error" class="text-error hidden"></p>
                      </div>
                      <div>
                        <span class="fl">Street name <span class="gs-req" style="color:#C62828">*</span></span>
                        <input class="in" type="text" id="guest_street_name" name="guest_street_name" placeholder="e.g. Main Street">
                        <p id="guest_street_name_error" class="text-error hidden"></p>
                      </div>
                      <div>
                        <span class="fl">City <span class="gs-req" style="color:#C62828">*</span></span>
                        <input class="in" type="text" id="guest_studio_city" name="guest_studio_city">
                        <p id="guest_studio_city_error" class="text-error hidden"></p>
                      </div>
                      <div>
                        <span class="fl">State / Province <span class="gs-req" style="color:#C62828">*</span></span>
                        <input class="in" type="text" id="guest_studio_state" name="guest_studio_state">
                        <p id="guest_studio_state_error" class="text-error hidden"></p>
                      </div>
                      <div>
                        <span class="fl">Postal / Zip <span class="gs-req" style="color:#C62828">*</span></span>
                        <input class="in" type="text" id="guest_postal_code" name="guest_postal_code">
                        <p id="guest_postal_code_error" class="text-error hidden"></p>
                      </div>
                      <div>
                        <span class="fl">Country <span class="gs-req" style="color:#C62828">*</span></span>
                        <input class="in" type="text" id="guest_studio_country" name="guest_studio_country">
                        <p id="guest_studio_country_error" class="text-error hidden"></p>
                      </div>
                    </div>
                    <div>
                      <span class="fl">Google Maps link</span>
                      <input class="in" type="url" id="guest_google_maps_link" name="guest_google_maps_link" placeholder="Paste a Google Maps link">
                      <p id="guest_google_maps_link_error" class="text-error hidden"></p>
                    </div>
                  </div>
                </div>
              </div>

              <div class="card" style="margin-bottom:14px" id="guestRulesCard">
                <div class="ch">
                  <div>
                    <h3>Booking rules</h3>
                    <div class="faint" style="font-size:12.5px;margin-top:2px">Only for this guest spot</div>
                  </div>
                </div>
                <div style="padding:18px 22px">
                  <div class="gs-3col">
                    <div>
                      <span class="fl">Buffer before <span class="gs-req" style="color:#C62828">*</span></span>
                      <input class="in" type="text" id="guest_buffer_days_before" name="buffer_days_before" inputmode="numeric" placeholder="e.g. 2 days" autocomplete="off">
                      <div class="help">No bookings this close to the start</div>
                      <p id="buffer_days_before_error" class="text-error hidden"></p>
                    </div>
                    <div>
                      <span class="fl">Buffer after <span class="gs-req" style="color:#C62828">*</span></span>
                      <input class="in" type="text" id="guest_buffer_days_after" name="buffer_days_after" inputmode="numeric" placeholder="e.g. 2 days" autocomplete="off">
                      <div class="help">No bookings this close to the end</div>
                      <p id="buffer_days_after_error" class="text-error hidden"></p>
                    </div>
                    <div>
                      <span class="fl">Available spots <span class="gs-req" style="color:#C62828">*</span></span>
                      <input class="in" type="text" id="guest_number_of_spots" name="number_of_spots" inputmode="numeric" placeholder="e.g. 8" autocomplete="off">
                      <div class="help">0 = unlimited</div>
                      <p id="number_of_spots_error" class="text-error hidden"></p>
                    </div>
                  </div>
                  <div style="margin-top:14px">
                    <span class="fl">Response deadline <span class="gs-req" style="color:#C62828">*</span></span>
                    <div class="row" style="gap:8px;align-items:stretch">
                      <input class="in" type="text" id="guest_response_deadline" name="response_deadline" inputmode="numeric" placeholder="e.g. 48" style="flex:1" autocomplete="off">
                      <select class="in" id="guest_response_deadline_unit" name="response_deadline_unit" style="width:7.5rem;flex:none">
                        <option value="hours" selected>Hours</option>
                        <option value="days">Days</option>
                      </select>
                    </div>
                    <div class="help">How long a client has to confirm a proposed time.</div>
                    <p id="response_deadline_error" class="text-error hidden"></p>
                  </div>
                </div>
              </div>
            </div>

            <div id="guestPayoutBlock">
              <div class="sect" style="margin:0 0 14px">
                <div class="lbl">Your current payout setup</div>
                <div class="row" style="gap:8px;margin-top:6px;flex-wrap:wrap">
                  <b>{{ $gsPayoutTitle ?? 'Artist - Direct payment' }}</b>
                  @if(!empty($gsPayoutConnected))
                    <span class="pill g">Connected</span>
                  @else
                    <span class="pill nd k">Not connected</span>
                  @endif
                </div>
                <div class="faint" style="font-size:12.5px;margin-top:4px;line-height:1.5">
                  Payments for your normal bookings follow this setup. You can change how this guest spot is paid without changing it.
                </div>
              </div>

              <b style="display:block;font-size:15px;margin-bottom:10px">Who should receive payments for this guest spot?</b>

              <label class="po sel" data-po="direct">
                <input type="radio" name="guest_po" value="direct" checked>
                <div style="flex:1">
                  <b>Artist - Direct payment</b>
                  <div class="d">The full payment goes to your own Stripe account. Choose this if you rent a workstation and pay the guest studio outside Bookpay.</div>
                </div>
              </label>

              <label class="po" data-po="split">
                <input type="radio" name="guest_po" value="split">
                <div style="flex:1">
                  <b id="guestPoSplitTitle">Guest studio - Revenue split</b>
                  <div class="d" id="guestPoSplitDesc">Each payment is split between you and the guest studio. Your share goes to your Stripe account and theirs to their Stripe account.</div>
                </div>
              </label>

              <div class="splitbox" id="guestSplitbox" hidden>
                <div class="row" style="gap:28px;flex-wrap:wrap">
                  <div>
                    <span class="fl">You get</span>
                    <div class="pct"><input class="in" id="guest_you_pct" type="number" min="0" max="100" value="60" inputmode="numeric">%</div>
                  </div>
                  <div>
                    <span class="fl">Guest studio gets</span>
                    <div id="guest_them_pct" style="font-size:20px;font-weight:800;padding:8px 0">40%</div>
                  </div>
                </div>
                <div class="help gsbp" id="guestSplitHelpOn" hidden>
                  <span class="gs-studio-name">the guest studio</span> is on Bookpay, so they confirm the split in their dashboard. No invite needed.
                </div>
                <div class="gsnb" id="guestSplitInvite" style="margin-top:12px">
                  <span class="fl">Guest studio email <span style="color:#C62828">*</span></span>
                  <input class="in" id="guest_studio_invite_email" name="guest_studio_invite_email" type="email" placeholder="studio@example.com" style="width:100%" autocomplete="off">
                  <p id="guest_studio_invite_email_error" class="text-error hidden"></p>
                  <div class="help"><span class="gs-studio-name">The guest studio</span> isn’t on Bookpay yet — we’ll email them an invite to join. You’re paid directly until they join and confirm the split.</div>
                </div>
                <div class="gscur" id="guestSplitCur" style="display:flex;gap:8px;margin-top:10px;padding:10px 12px;border-radius:10px;background:#F6F3F8;font-size:12.5px;line-height:1.45;color:#3F3A44">
                  <span class="ms" style="font-size:18px;color:#6F6874">currency_exchange</span>
                  <span id="guestSplitCurText">If <span class="gs-studio-name">the guest studio</span> is paid in another currency, their share is converted when it’s paid out. They pay the conversion fee.</span>
                </div>
              </div>

              <div class="lockbar" style="margin-top:10px;{{ !empty($gsStripeConnected) ? 'background:#EEFAF3;color:#1F6B4E' : '' }}">
                <span class="ms" style="font-size:17px">{{ !empty($gsStripeConnected) ? 'check_circle' : 'link' }}</span>
                {{ $gsStripeLockbar ?? 'Connect Stripe in Money → Payouts if you haven’t yet.' }}
              </div>
            </div>
          </div>
        </div>

        {{-- Sections (UI only for now) --}}
        <div class="gtp" data-gp="s" hidden>
          <div class="help" style="margin:0 0 12px">Choose what shows on this guest spot’s page. Your name, photo, studio, styles, services and social links always show.</div>

          <div class="card" style="margin-bottom:14px">
            <div class="ch">
              <div>
                <h3>Available designs</h3>
                <div class="faint" style="font-size:12.5px;margin-top:2px">Do you want to list flash designs?</div>
              </div>
              <div class="tog" id="gsTogDesigns" role="switch" aria-checked="false" tabindex="0" aria-label="Show available designs"></div>
            </div>
            <div id="gsDesignsWrap" hidden style="padding:16px 22px 18px">
              @if(($gsDesigns ?? collect())->isEmpty())
                <div class="faint" style="font-size:13px;line-height:1.5">No flash designs yet. Add designs on My Page to pick them here.</div>
              @else
                <div class="row" style="justify-content:space-between;margin-bottom:10px;gap:10px;flex-wrap:wrap">
                  <span class="faint" style="font-size:12.5px">Tap the designs to show on this page.</span>
                  <span class="row" style="gap:12px">
                    <b id="gsDesignsCnt" style="font-size:12.5px">0 selected</b>
                    <button type="button" class="selall" id="gsDesignsSelectAll">Select all</button>
                  </span>
                </div>
                <div class="pfg" id="gsDesignsGrid">
                  @foreach($gsDesigns as $design)
                    <button type="button" class="pfp dsp" data-id="{{ $design['id'] }}" aria-pressed="false" title="{{ $design['title'] }}"
                      @if(!empty($design['image_url'])) style="background-image:url('{{ $design['image_url'] }}')" @endif>
                      @if(!empty($design['badge']))
                        <span class="dsl">{{ $design['badge'] }}</span>
                      @endif
                      <span class="pfk"><span class="ms">check</span></span>
                    </button>
                  @endforeach
                </div>
                <div class="help" id="gsDesignsNone" style="color:#B26A00" hidden>Select at least one design, or turn Available designs off.</div>
              @endif
            </div>
          </div>

          <div class="card" style="margin-bottom:14px">
            <div class="ch">
              <div>
                <h3>Portfolio</h3>
                <div class="faint" style="font-size:12.5px;margin-top:2px">Do you want to list portfolio work?</div>
              </div>
              <div class="tog" id="gsTogPortfolio" role="switch" aria-checked="false" tabindex="0" aria-label="Show portfolio"></div>
            </div>
            <div id="gsPortfolioWrap" hidden style="padding:16px 22px 18px">
              @if(($gsPortfolios ?? collect())->isEmpty())
                <div class="faint" style="font-size:13px;line-height:1.5">No portfolio pieces yet. Add work on My Page to pick it here.</div>
              @else
                <div class="row" style="justify-content:space-between;margin-bottom:10px;gap:10px;flex-wrap:wrap">
                  <span class="faint" style="font-size:12.5px">Tap the pieces to show on this page.</span>
                  <span class="row" style="gap:12px">
                    <b id="gsPortfolioCnt" style="font-size:12.5px">0 selected</b>
                    <button type="button" class="selall" id="gsPortfolioSelectAll">Select all</button>
                  </span>
                </div>
                <div class="pfg" id="gsPortfolioGrid">
                  @foreach($gsPortfolios as $piece)
                    <button type="button" class="pfp" data-id="{{ $piece['id'] }}" aria-pressed="false" title="{{ $piece['title'] }}"
                      @if(!empty($piece['image_url'])) style="background-image:url('{{ $piece['image_url'] }}')" @endif>
                      <span class="pfk"><span class="ms">check</span></span>
                    </button>
                  @endforeach
                </div>
                <div class="help" id="gsPortfolioNone" style="color:#B26A00" hidden>Select at least one piece, or turn Portfolio off.</div>
              @endif
            </div>
          </div>

          <div class="card" style="margin-bottom:14px">
            <div class="ch">
              <div>
                <h3>Rates</h3>
                <div class="faint" style="font-size:12.5px;margin-top:2px">Your hourly, half day and full day rates</div>
              </div>
              <div class="tog" id="gsTogRates" role="switch" aria-checked="false" tabindex="0" aria-label="Show rates"></div>
            </div>
          </div>

          <div class="card" style="margin-bottom:14px">
            <div class="ch">
              <div>
                <h3>Policies</h3>
                <div class="faint" style="font-size:12.5px;margin-top:2px">Your policies from My Page &gt; Policies</div>
              </div>
              <div class="tog on" id="gsTogPolicies" role="switch" aria-checked="true" tabindex="0" aria-label="Show policies"></div>
            </div>
          </div>

          <div class="card" style="margin-bottom:14px">
            <div class="ch">
              <div>
                <h3>FAQ</h3>
                <div class="faint" style="font-size:12.5px;margin-top:2px">Your questions from My Page &gt; FAQ</div>
              </div>
              <div class="tog on" id="gsTogFaq" role="switch" aria-checked="true" tabindex="0" aria-label="Show FAQ"></div>
            </div>
          </div>
        </div>

        {{-- Appearance (UI only for now) --}}
        <div class="gtp" data-gp="l" hidden>
          <div class="gs-look-grid">
            <div>
              <div class="help" style="margin:0 0 12px">These settings only change this guest spot’s page. Your own page keeps its look.</div>

              <div class="card" style="margin-bottom:14px">
                <div class="ch">
                  <div>
                    <h3>Header banner</h3>
                    <div class="faint" style="font-size:12.5px;margin-top:2px">The wide image at the top of this guest spot’s page</div>
                  </div>
                </div>
                <div style="padding:18px 22px">
                  <div id="gsBannerHas" class="bnprev" hidden>
                    <div class="bnimg" id="gsBannerImg"></div>
                    <div class="row" style="gap:8px;margin-top:10px">
                      <button type="button" class="btn sm ghost" id="gsBannerChange"><span class="ms" style="font-size:16px">upload</span>Change banner</button>
                      <button type="button" class="btn sm ghost" id="gsBannerRemove"><span class="ms" style="font-size:16px">delete</span>Remove</button>
                    </div>
                  </div>
                  <button type="button" id="gsBannerNone" class="bnup">
                    <span class="ms">photo_camera</span>
                    <span>
                      <b>Upload banner</b>
                      <small>1600 × 230 recommended. Without one, the page uses your color.</small>
                    </span>
                  </button>
                  <input type="file" id="gsBannerFile" accept="image/jpeg,image/png,image/webp" hidden>
                </div>
              </div>

              <div class="card" style="margin-bottom:14px">
                <div class="ch">
                  <div>
                    <h3>Color scheme</h3>
                    <div class="faint" style="font-size:12.5px;margin-top:2px">Pick the colors of this guest spot’s page</div>
                  </div>
                </div>
                <div style="padding:18px 22px">
                  <div class="lfgrid" id="gsSchemeGrid">
                    <button type="button" class="lf on" data-sch="#4B0F8F,#7B3FC4,#F3E8FF" aria-pressed="true">
                      <span class="lfb" style="background:linear-gradient(90deg,#4B0F8F,#7B3FC4)"><span class="swmark" aria-hidden="true"><span class="ms">check_circle</span></span></span>
                      <span class="lfn">Default Purple</span>
                    </button>
                    <button type="button" class="lf" data-sch="#1565C0,#42A5F5,#E3F0FC" aria-pressed="false">
                      <span class="lfb" style="background:linear-gradient(90deg,#1565C0,#42A5F5)"><span class="swmark" aria-hidden="true"><span class="ms">check_circle</span></span></span>
                      <span class="lfn">Ocean Blue</span>
                    </button>
                    <button type="button" class="lf" data-sch="#1B7F3B,#3FBF6A,#E4F5EA" aria-pressed="false">
                      <span class="lfb" style="background:linear-gradient(90deg,#1B7F3B,#3FBF6A)"><span class="swmark" aria-hidden="true"><span class="ms">check_circle</span></span></span>
                      <span class="lfn">Forest Green</span>
                    </button>
                    <button type="button" class="lf" data-sch="#E64A19,#FF7043,#FDEBE4" aria-pressed="false">
                      <span class="lfb" style="background:linear-gradient(90deg,#E64A19,#FF7043)"><span class="swmark" aria-hidden="true"><span class="ms">check_circle</span></span></span>
                      <span class="lfn">Warm Coral</span>
                    </button>
                    <button type="button" class="lf" data-sch="#1A1A2E,#3A3A5C,#ECEBF3" aria-pressed="false">
                      <span class="lfb" style="background:linear-gradient(90deg,#1A1A2E,#3A3A5C)"><span class="swmark" aria-hidden="true"><span class="ms">check_circle</span></span></span>
                      <span class="lfn">Midnight</span>
                    </button>
                    <button type="button" class="lf" data-sch="#E07000,#FFB300,#FDF0DC" aria-pressed="false">
                      <span class="lfb" style="background:linear-gradient(90deg,#E07000,#FFB300)"><span class="swmark" aria-hidden="true"><span class="ms">check_circle</span></span></span>
                      <span class="lfn">Golden Hour</span>
                    </button>
                  </div>
                  <div class="lfc">
                    <span class="lfsw" id="gsCustomSwatch"></span>
                    <div style="flex:1;min-width:180px">
                      <b style="font-size:13.5px">Custom color</b>
                      <div class="faint" style="font-size:12px">Use your own brand color.</div>
                    </div>
                    <input type="color" id="gsCustomColor" value="#7A1FC4" aria-label="Custom color">
                    <button type="button" class="btn sm ghost" id="gsUseCustomColor">Use this color</button>
                  </div>
                  <button type="button" class="btn sm ghost" id="gsResetLook" style="margin-top:12px">
                    <span class="ms" style="font-size:16px">restart_alt</span>Same as My Page
                  </button>
                </div>
              </div>
            </div>

            <div class="gpv">
              <div class="lbl" style="margin-bottom:8px">PREVIEW</div>
              <div class="gpvc">
                <div class="gpvb" id="gsPreviewBanner">
                  <svg class="gsky" viewBox="0 0 1200 230" preserveAspectRatio="xMidYMax slice" aria-hidden="true">
                    <path fill="#fff" opacity=".2" d="M0 230V170h60v-30h40v30h30v-60h50v60h40v-20h30v-50l25-30 25 30v70h40v-90h20v-30h10v30h20v90h50V120h20l10-40 10 40h20v110h40v-70h60v-40h30v40h40v70h30V90h15l15-35 15 35h15v140h40v-60h50v60h40v-100l40-40 40 40v100h50v-50h40v50h40v-80h60v80h50V230z"/>
                  </svg>
                </div>
                <div class="gpva" id="gsPreviewAvatar" @if(!empty($gsAvatarUrl)) style="background:#fff url('{{ $gsAvatarUrl }}') center/cover" @endif role="img" aria-label="{{ $gsArtistName ?? 'Artist' }}">
                  @if(empty($gsAvatarUrl))
                    {{ strtoupper(substr($gsArtistName ?? 'A', 0, 1)) }}
                  @endif
                </div>
                <div class="gpvt">
                  <b>{{ $gsArtistName ?? 'Artist' }}</b>
                  <span class="gpvs">Available</span>
                  <div class="gpvl" id="gsPreviewLocation">Guest spot in your city, with your dates</div>
                  <div class="gpvbtn" id="gsPreviewBtn">Reserve your guest spot</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="mf">
        <button type="button" class="btn ghost" id="btnCancelGuestSpotEdit">Cancel</button>
        <button type="submit" class="btn" id="btnAddGuestSpot">
          <span class="ms btn-icon">save</span>
          <span class="btn-label">Save guest spot</span>
        </button>
      </div>
    </form>
  </div>
</div>
