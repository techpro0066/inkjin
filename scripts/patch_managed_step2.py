#!/usr/bin/env python3
"""Add dynamic step2 (consult vs no-consult) to managed-book.blade.php"""
from pathlib import Path

BLADE = Path(__file__).resolve().parents[1] / "resources/views/public/managed-book.blade.php"

STEP2_MANAGED = r'''    @php
      $consultDurationMinutes = (int) ($userDetail->session_duration_minutes ?: 30);
      $consultDurationLabel = $consultDurationMinutes >= 60
        ? (floor($consultDurationMinutes / 60) . ' hour' . (floor($consultDurationMinutes / 60) > 1 ? 's' : ''))
        : ($consultDurationMinutes . ' minutes');
      $artistDisplayName = trim(($userDetail->user->first_name ?? '') . ' ' . ($userDetail->user->last_name ?? ''));
      $studioAddressLine = trim(implode(', ', array_filter([
        $userDetail->studio_address ?? '',
        $userDetail->city ?? '',
        $userDetail->country ?? '',
      ])));
    @endphp

    <!-- STEP 2: MANAGED (no consultation) -->
    <div class="step-panel" id="step2Managed">
      <button class="js-back-to-questions flex items-center gap-1 text-sm text-on-surface-variant hover:text-primary mb-4 transition-colors" onclick="goToStep(1, true)"><span class="material-symbols-outlined text-[18px]">arrow_back</span> Back to Questions</button>
      <motion class="bg-white rounded-2xl border border-outline-variant/20 p-6 mb-6">
        <div class="mb-6">
          <h3 class="text-xl font-bold text-on-surface mb-1">When are you available?</h3>
          <p class="text-sm text-on-surface-variant"><span class="managedArtistHint">{{ $artistDisplayName }}</span> will confirm a time that works for both of you.</p>
        </div>
        <div id="prefBlocks" class="space-y-4 mb-6">
          <div class="pref-block" data-pref="0">
            <p class="text-xs font-bold text-primary uppercase tracking-wider mb-3">Preference 1 <span class="text-error">*</span></p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div><label class="text-xs font-semibold text-on-surface-variant mb-1 block">Date</label><input type="date" class="pref-date w-full border border-outline-variant/30 bg-white rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30"></div>
              <div><label class="text-xs font-semibold text-on-surface-variant mb-1 block">Time of day</label><div class="flex flex-wrap gap-1.5"><button type="button" class="time-pref-pill" data-value="Morning" onclick="toggleTimePref(this)">Morning</button><button type="button" class="time-pref-pill" data-value="Afternoon" onclick="toggleTimePref(this)">Afternoon</button><button type="button" class="time-pref-pill" data-value="Evening" onclick="toggleTimePref(this)">Evening</button></div></div>
            </div>
          </div>
          <div class="pref-block" data-pref="1">
            <p class="text-xs font-bold text-primary uppercase tracking-wider mb-3">Preference 2 <span class="text-error">*</span></p>
            <motion class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div><label class="text-xs font-semibold text-on-surface-variant mb-1 block">Date</label><input type="date" class="pref-date w-full border border-outline-variant/30 bg-white rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30"></div>
              <div><label class="text-xs font-semibold text-on-surface-variant mb-1 block">Time of day</label><motion class="flex flex-wrap gap-1.5"><button type="button" class="time-pref-pill" data-value="Morning" onclick="toggleTimePref(this)">Morning</button><button type="button" class="time-pref-pill" data-value="Afternoon" onclick="toggleTimePref(this)">Afternoon</button><button type="button" class="time-pref-pill" data-value="Evening" onclick="toggleTimePref(this)">Evening</button></div></div>
            </div>
          </div>
        </div>
        <button type="button" id="addPrefBtn" onclick="addPreference()" class="text-sm text-primary font-semibold flex items-center gap-1 hover:underline mb-6"><span class="material-symbols-outlined text-[18px]">add</span> Add another preference</button>
        <div class="space-y-4">
          <div><label class="text-xs font-semibold text-on-surface-variant mb-2 block">Preferred days of the week</label><div class="flex flex-wrap gap-1.5" id="dayPills"><button type="button" class="day-pill" data-value="Mon" onclick="this.classList.toggle('selected')">Mon</button><button type="button" class="day-pill" data-value="Tue" onclick="this.classList.toggle('selected')">Tue</button><button type="button" class="day-pill" data-value="Wed" onclick="this.classList.toggle('selected')">Wed</button><button type="button" class="day-pill" data-value="Thu" onclick="this.classList.toggle('selected')">Thu</button><button type="button" class="day-pill" data-value="Fri" onclick="this.classList.toggle('selected')">Fri</button><button type="button" class="day-pill" data-value="Sat" onclick="this.classList.toggle('selected')">Sat</button><button type="button" class="day-pill" data-value="Sun" onclick="this.classList.toggle('selected')">Sun</button></div></div>
          <div><label class="text-xs font-semibold text-on-surface-variant mb-1 block">Any dates to avoid?</label><input type="text" id="managedAvoid" placeholder="e.g., April 10-15, May 1st" class="w-full border border-outline-variant/30 bg-white rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30"></div>
          <div><label class="text-xs font-semibold text-on-surface-variant mb-2 block">How flexible are you?</label><div class="flex flex-wrap gap-2" id="flexPills"><button type="button" class="pill-btn text-sm !py-2 !px-4" data-value="Very flexible" onclick="selectPill(this,'flexPills')">Very flexible</button><button type="button" class="pill-btn text-sm !py-2 !px-4" data-value="Somewhat flexible" onclick="selectPill(this,'flexPills')">Somewhat flexible</button><button type="button" class="pill-btn text-sm !py-2 !px-4" data-value="These are my only options" onclick="selectPill(this,'flexPills')">These are my only options</button></div></div>
          <div><label class="text-xs font-semibold text-on-surface-variant mb-2 block">Urgency</label><div class="flex flex-wrap gap-2" id="urgencyPills"><button type="button" class="pill-btn text-sm !py-2 !px-4" data-value="No rush" onclick="selectPill(this,'urgencyPills')">No rush</button><button type="button" class="pill-btn text-sm !py-2 !px-4" data-value="Within 2 weeks" onclick="selectPill(this,'urgencyPills')">Within 2 weeks</button><button type="button" class="pill-btn text-sm !py-2 !px-4" data-value="Within a month" onclick="selectPill(this,'urgencyPills')">Within a month</button><button type="button" class="pill-btn text-sm !py-2 !px-4" data-value="ASAP" onclick="selectPill(this,'urgencyPills')">ASAP</button></motion></motion>
        </div>
      </div>
      <div class="mb-studio-location flex items-start gap-3 p-4 bg-surface-container-low rounded-xl mb-6">
        <span class="material-symbols-outlined text-primary mt-0.5">location_on</span>
        <div>
          <p class="text-sm font-semibold text-on-surface mc-studioName">{{ $userDetail->studio_name ?: 'Studio' }}</p>
          <p class="text-xs text-on-surface-variant mc-studioAddress">{{ $studioAddressLine ?: '—' }}</p>
          @if(!empty($userDetail->google_maps_link))
          <a href="{{ $userDetail->google_maps_link }}" target="_blank" rel="noopener noreferrer" class="text-xs text-primary font-medium hover:underline mt-1 inline-block">Get Directions →</a>
          @endif
        </div>
      </div>
      <button type="button" onclick="goToStep(3)" class="w-full py-3.5 rounded-xl font-bold text-white bg-primary hover:opacity-90 transition-all text-sm flex items-center justify-center gap-2">Continue to Your Details <span class="material-symbols-outlined text-[18px]">arrow_forward</span></button>
    </div>

    <!-- STEP 2: MANAGED + CONSULTATION -->
'''

# fix motion typos in STEP2_MANAGED
STEP2_MANAGED = (
    STEP2_MANAGED.replace("<motion ", "<div ")
    .replace("</motion>", "</div>")
    .replace("</motion></motion>", "</motion></div>")
)


def main():
    text = BLADE.read_text(encoding="utf-8")

    marker = "    <!-- ═══════════════════════════════════════════ -->\n    <!-- STEP 2D: MANAGED + CONSULTATION"
    if marker not in text:
        marker = "    <!-- STEP 2D: MANAGED + CONSULTATION"
    idx = text.find(marker)
    if idx == -1:
        raise SystemExit("step2 marker not found")

    text = text[:idx] + STEP2_MANAGED + text[idx:]

    text = text.replace("<!-- STEP 2D: MANAGED + CONSULTATION", "<!-- STEP 2: MANAGED + CONSULTATION", 1)
    text = text.replace(
        '<span class="mc-artistName">Julian Ink</span> includes a free consultation before your tattoo session</h3>',
        '<span class="mc-artistName">{{ $artistDisplayName }}</span> includes a free consultation before your tattoo session</h3>',
        1,
    )
    text = text.replace(
        "<p class=\"text-sm text-on-surface-variant\">You'll have a 15-minute call to discuss your design, placement, and any questions.</p>",
        "<p class=\"text-sm text-on-surface-variant\">You'll have a {{ $consultDurationLabel }} consultation to discuss your design, placement, and any questions.</p>",
        1,
    )
    text = text.replace("15-minute call on Inkjin", "{{ $consultDurationLabel }} on Inkjin", 1)
    text = text.replace("15-minute phone consultation", "{{ $consultDurationLabel }} phone consultation", 1)
    text = text.replace(
        'Visit <span class="mc-studioName">Black Lotus Studio</span> in person',
        'Visit <span class="mc-studioName">{{ $userDetail->studio_name ?: \'Studio\' }}</span> in person',
        1,
    )
    text = text.replace(
        '<p class="text-xs text-primary font-medium mt-1 mc-studioAddress">Athens, Greece</p>',
        '@if($studioAddressLine)<p class="text-xs text-primary font-medium mt-1 mc-studioAddress">{{ $studioAddressLine }}</p>@endif',
        1,
    )
    text = text.replace(
        '<p class="text-sm text-on-surface-variant"><span class="mc-artistName">Julian Ink</span> will schedule both your consultation and tattoo session.</p>',
        '<p class="text-sm text-on-surface-variant"><span class="mc-artistName">{{ $artistDisplayName }}</span> will schedule both your consultation and tattoo session.</p>',
        1,
    )
    text = text.replace(
        '<p class="text-sm text-on-surface-variant mb-3"><span class="mc-artistName">Julian Ink</span> will review your availability and schedule:</p>',
        '<p class="text-sm text-on-surface-variant mb-3"><span class="mc-artistName">{{ $artistDisplayName }}</span> will review your availability and schedule:</p>',
        1,
    )

    # Replace hardcoded location in consult panel
    old_loc = '''        <div class="flex items-start gap-3 mt-6 p-4 bg-surface-container-low rounded-xl">
          <span class="material-symbols-outlined text-primary mt-0.5">location_on</span>
          <div>
            <p class="text-sm font-semibold text-on-surface">Ink & Soul Tattoo Studio</p>
            <p class="text-xs text-on-surface-variant">742 Evergreen Terrace, Athens, 10001, Greece</p>
            <a href="https://maps.google.com/?q=Ink+Soul+Tattoo+Studio+Athens" target="_blank" class="text-xs text-primary font-medium hover:underline mt-1 inline-block">Get Directions →</a>
          </div>
        </motion>'''
    old_loc = old_loc.replace("</motion>", "</div>")
    new_loc = '''        <div class="mb-studio-location flex items-start gap-3 mt-6 p-4 bg-surface-container-low rounded-xl">
          <span class="material-symbols-outlined text-primary mt-0.5">location_on</span>
          <div>
            <p class="text-sm font-semibold text-on-surface mc-studioName">{{ $userDetail->studio_name ?: 'Studio' }}</p>
            <p class="text-xs text-on-surface-variant mc-studioAddress">{{ $studioAddressLine ?: '—' }}</p>
            @if(!empty($userDetail->google_maps_link))
            <a href="{{ $userDetail->google_maps_link }}" target="_blank" rel="noopener noreferrer" class="text-xs text-primary font-medium hover:underline mt-1 inline-block">Get Directions →</a>
            @endif
          </div>
        </div>'''
    if old_loc in text:
        text = text.replace(old_loc, new_loc, 1)

    # Add mcConsultTypeError after consult type cards
    if 'id="mcConsultTypeError"' not in text:
        text = text.replace(
            '</motion>\n      <!-- Single availability block',
            '</div>\n        <p id="mcConsultTypeError" class="hidden text-sm text-error mt-3">Please select a consultation type before continuing.</p>\n      </div>\n      <!-- Single availability block',
            1,
        )
        text = text.replace(
            '</div>\n      <!-- Single availability block (shown after type selected) -->',
            '</div>\n        <p id="mcConsultTypeError" class="hidden text-sm text-error mt-3">Please select a consultation type before continuing.</p>\n      </motion>\n      <!-- Single availability block (shown after type selected) -->',
            1,
        )

    # JS patches
    js_old = """    const artistName = params.get('artist') || 'Julian Ink';
    const studioName = 'Black Lotus Studio';
    const studioAddress = 'Athens, Greece';
    const initials = artistName.split(' ').map(w => w[0]).join('').toUpperCase().slice(0,2);"""

    js_new = """    const artistConsultationSettings = @json($artistConsultationSettings ?? []);
    const consultationRequired = !!artistConsultationSettings.required;
    const consultationSessionType = String(artistConsultationSettings.session_type || 'both').trim().toLowerCase();
    const consultDurationLabel = @json($consultDurationLabel ?? '30 minutes');

    const design = {
      title: @json($tattoo->title ?? ''),
      style: @json(ucwords(str_replace('-', ' ', $tattoo->primary_style ?? ''))),
      price: '€' + @json($tattoo->min_price ?? 0) + ' — €' + @json($tattoo->max_price ?? 0),
      time: @json(($tattoo->session_duration ?? '') . ' hours'),
    };
    const artistName = @json($artistDisplayName ?? 'Artist');
    const studioName = @json($userDetail->studio_name ?? 'Studio');
    const studioAddress = @json($studioAddressLine ?? '');
    const initials = (@json(strtoupper(substr($userDetail->user->first_name ?? 'A', 0, 1) . substr($userDetail->user->last_name ?? 'A', 0, 1))));"""

    if js_old in text:
        text = text.replace(js_old, js_new, 1)
    else:
        raise SystemExit("JS artist block not found")

    # Remove designs array block - find and replace
    designs_start = "    const designs = ["
    designs_end = "    const params = new URLSearchParams"
    ds = text.find(designs_start)
    de = text.find(designs_end, ds)
    if ds != -1 and de != -1:
        text = text[:ds] + "    const params = new URLSearchParams" + text[de + len("    const params = new URLSearchParams"):]

    # Remove duplicate design assignment
    old_design_block = """    const params = new URLSearchParams(window.location.search);
    const designIdx = parseInt(params.get('design') || '0', 10);
    let design;
    if (params.get('title')) {
      const priceStr = params.get('price') || '€300 — €500';
      const lp = parseInt(priceStr.replace(/[^0-9]/g, ''), 10) || 300;
      design = { title: params.get('title'), style: params.get('style') || 'Custom', price: priceStr, time: params.get('time') || '—', sessions: params.get('sessions') || '—', lowerPrice: lp, size: params.get('size') || 'Medium (10-20cm)' };
    } else {
      design = designs[designIdx] || designs[0];
    }

"""
    if old_design_block in text:
        text = text.replace(old_design_block, "    const params = new URLSearchParams(window.location.search);\n\n", 1)

    text = text.replace(
        """    function showStep2() {
      const el = document.getElementById('step2ManagedConsult');
      if (el) el.classList.add('active');
    }""",
        """    function showStep2(reverse) {
      const panelId = consultationRequired ? 'step2ManagedConsult' : 'step2Managed';
      const el = document.getElementById(panelId);
      if (el) {
        if (reverse) el.classList.add('reverse');
        el.classList.add('active');
      }
    }""",
        1,
    )

    text = text.replace(
        "      else if (step === 2) showStep2();",
        "      else if (step === 2) showStep2(reverse);",
        1,
    )

    # configureMcConsultTypeCards + addPreference
    insert_before_register = "    // ── Register ──"
    consult_js = """
    function configureMcConsultTypeCards() {
      $('#mcConsultTypeCards .consult-type-card[data-type="phone"]').addClass('hidden');
      var allowedTypes = [];
      if (consultationSessionType === 'online') allowedTypes = ['video'];
      else if (consultationSessionType === 'physical') allowedTypes = ['studio'];
      else allowedTypes = ['video', 'studio'];
      $('#mcConsultTypeCards .consult-type-card').each(function() {
        var type = String($(this).data('type') || '');
        if (allowedTypes.indexOf(type) === -1) $(this).addClass('hidden');
      });
      if (allowedTypes.length === 1) {
        var onlyCard = document.querySelector('#mcConsultTypeCards .consult-type-card[data-type="' + allowedTypes[0] + '"]');
        if (onlyCard) selectMcConsultType(onlyCard, allowedTypes[0]);
      }
    }

    let prefCount = 2;
    window.addPreference = function() {
      if (prefCount >= 5) return;
      prefCount++;
      const block = document.createElement('motion');
      block.className = 'pref-block';
      block.dataset.pref = String(prefCount - 1);
      block.innerHTML = '<p class="text-xs font-bold text-primary uppercase tracking-wider mb-3">Preference ' + prefCount + '</p><div class="grid grid-cols-1 sm:grid-cols-2 gap-3"><div><label class="text-xs font-semibold text-on-surface-variant mb-1 block">Date</label><input type="date" class="pref-date w-full border border-outline-variant/30 bg-white rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30"></div><motion><label class="text-xs font-semibold text-on-surface-variant mb-1 block">Time of day</label><div class="flex flex-wrap gap-1.5"><button type="button" class="time-pref-pill" data-value="Morning" onclick="toggleTimePref(this)">Morning</button><button type="button" class="time-pref-pill" data-value="Afternoon" onclick="toggleTimePref(this)">Afternoon</button><button type="button" class="time-pref-pill" data-value="Evening" onclick="toggleTimePref(this)">Evening</button></div></div></motion>';
      block.innerHTML = block.innerHTML.replace(/<motion>/g, '<div>').replace(/<\\/motion>/g, '</div>');
      document.getElementById('prefBlocks').appendChild(block);
      if (prefCount >= 5) document.getElementById('addPrefBtn').classList.add('hidden');
    };

"""
    consult_js = consult_js.replace("createElement('motion')", "createElement('motion')").replace(
        "block.innerHTML = '<p class=\"text-xs",
        "block.innerHTML = '<p class=\"text-xs",
    )
    consult_js = """
    function configureMcConsultTypeCards() {
      document.querySelectorAll('#mcConsultTypeCards .consult-type-card[data-type="phone"]').forEach(function(el) { el.classList.add('hidden'); });
      var allowedTypes = [];
      if (consultationSessionType === 'online') allowedTypes = ['video'];
      else if (consultationSessionType === 'physical') allowedTypes = ['studio'];
      else allowedTypes = ['video', 'studio'];
      document.querySelectorAll('#mcConsultTypeCards .consult-type-card').forEach(function(card) {
        var type = String(card.getAttribute('data-type') || '');
        if (allowedTypes.indexOf(type) === -1) card.classList.add('hidden');
      });
      if (allowedTypes.length === 1) {
        var onlyCard = document.querySelector('#mcConsultTypeCards .consult-type-card[data-type="' + allowedTypes[0] + '"]');
        if (onlyCard) selectMcConsultType(onlyCard, allowedTypes[0]);
      }
    }

    let prefCount = 2;
    window.addPreference = function() {
      if (prefCount >= 5) return;
      prefCount++;
      const block = document.createElement('div');
      block.className = 'pref-block';
      block.dataset.pref = String(prefCount - 1);
      block.innerHTML = '<p class="text-xs font-bold text-primary uppercase tracking-wider mb-3">Preference ' + prefCount + '</p><div class="grid grid-cols-1 sm:grid-cols-2 gap-3"><div><label class="text-xs font-semibold text-on-surface-variant mb-1 block">Date</label><input type="date" class="pref-date w-full border border-outline-variant/30 bg-white rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30"></div><div><label class="text-xs font-semibold text-on-surface-variant mb-1 block">Time of day</label><div class="flex flex-wrap gap-1.5"><button type="button" class="time-pref-pill" data-value="Morning" onclick="toggleTimePref(this)">Morning</button><button type="button" class="time-pref-pill" data-value="Afternoon" onclick="toggleTimePref(this)">Afternoon</button><button type="button" class="time-pref-pill" data-value="Evening" onclick="toggleTimePref(this)">Evening</button></div></div></div>';
      document.getElementById('prefBlocks').appendChild(block);
      if (prefCount >= 5) document.getElementById('addPrefBtn').classList.add('hidden');
    };

"""
    if "configureMcConsultTypeCards" not in text:
        text = text.replace(insert_before_register, consult_js + insert_before_register, 1)

    # Update selectMcConsultType
    text = text.replace(
        "      document.getElementById('mcAvailSection').classList.remove('hidden');",
        "      document.getElementById('mcConsultTypeError').classList.add('hidden');\n      document.getElementById('mcAvailSection').classList.remove('hidden');",
        1,
    )

    # buildManagedReview rewrite
    old_review_fn_start = "    function buildManagedReview() {"
    old_review_fn_end = "      document.getElementById('managedReview').innerHTML = html;\n    }"
    if old_review_fn_start in text:
        new_review = r'''    function collectPrefDates(blocksSelector, dateInputSelector) {
      const prefs = [];
      document.querySelectorAll(blocksSelector + ' .pref-block').forEach(function(block) {
        const date = block.querySelector(dateInputSelector)?.value || '';
        const times = [];
        block.querySelectorAll('.time-pref-pill.selected').forEach(function(p) { times.push(p.dataset.value); });
        if (date) prefs.push(date + (times.length ? ' (' + times.join(', ') + ')' : ''));
      });
      return prefs;
    }

    function buildManagedReview() {
      const isConsult = consultationRequired;
      const prefs = collectPrefDates(isConsult ? '#mcPrefBlocks' : '#prefBlocks', isConsult ? '.mc-pref-date' : '.pref-date');
      const days = [];
      document.querySelectorAll((isConsult ? '#mcDayPills' : '#dayPills') + ' .day-pill.selected').forEach(function(d) { days.push(d.dataset.value); });
      const flex = document.querySelector((isConsult ? '#mcFlexPills' : '#flexPills') + ' .pill-btn.selected')?.dataset.value || '—';
      const name = document.getElementById('bdName').value.trim() || '—';
      const email = document.getElementById('bdEmail').value.trim() || '—';
      const phone = document.getElementById('bdPhone').value.trim() || '—';

      let html = '<div class="space-y-2 text-sm">' +
        '<div class="flex justify-between"><span class="text-on-surface-variant">Design</span><span class="font-semibold">' + design.title + '</span></div>' +
        '<div class="flex justify-between"><span class="text-on-surface-variant">Placement</span><span class="font-semibold">' + ((typeof window.mbGetAnswerByKeywords === 'function' && window.mbGetAnswerByKeywords(['placement', 'body part', 'where'])) || '—') + '</span></motion>' +
        '<div class="flex justify-between"><span class="text-on-surface-variant">Size</span><span class="font-semibold">' + ((typeof window.mbGetAnswerByKeywords === 'function' && window.mbGetAnswerByKeywords(['size', 'cm', 'inch'])) || '—') + '</span></div>';

      var structuredAnswers = (typeof window.mbBuildStructuredQuestionAnswers === 'function') ? window.mbBuildStructuredQuestionAnswers() : {};
      Object.keys(structuredAnswers).forEach(function(key) {
        var item = structuredAnswers[key];
        if (!item || !item.question) return;
        var answerText = item.answer;
        if (typeof answerText === 'boolean') answerText = answerText ? 'Yes' : 'No';
        if (Array.isArray(answerText)) answerText = answerText.join(', ');
        html += '<div class="flex justify-between gap-4"><span class="text-on-surface-variant shrink-0">' + item.question + '</span><span class="font-semibold text-right">' + (answerText || '—') + '</span></div>';
      });

      if (isConsult && mcConsultType) {
        const info = consultTypeLabels[mcConsultType];
        html += '<div class="flex justify-between"><span class="text-on-surface-variant">Consultation Type</span><span class="font-semibold">' + info.emoji + ' ' + info.label + '</span></div>';
        const gap = document.querySelector('#mcGapPills .pill-btn.selected')?.dataset.value || '—';
        html += '<motion class="flex justify-between"><span class="text-on-surface-variant">Session Gap</span><span class="font-semibold">' + gap + '</span></div>';
      }

      if (!isConsult) {
        const avoid = document.getElementById('managedAvoid')?.value.trim() || '—';
        const urgency = document.querySelector('#urgencyPills .pill-btn.selected')?.dataset.value || '—';
        html += '<div class="flex justify-between"><span class="text-on-surface-variant">Dates to Avoid</span><span class="font-semibold">' + avoid + '</span></div>';
        html += '<div class="flex justify-between"><span class="text-on-surface-variant">Urgency</span><span class="font-semibold">' + urgency + '</span></div>';
      }

      html += '<div class="flex justify-between"><span class="text-on-surface-variant">Preferred Dates</span><span class="font-semibold text-right">' + (prefs.join('<br>') || '—') + '</span></div>' +
        '<div class="flex justify-between"><span class="text-on-surface-variant">Preferred Days</span><span class="font-semibold">' + (days.join(', ') || '—') + '</span></div>' +
        '<div class="flex justify-between"><span class="text-on-surface-variant">Flexibility</span><span class="font-semibold">' + flex + '</span></div>' +
        '<hr class="border-outline-variant/20">' +
        '<div class="flex justify-between"><span class="text-on-surface-variant">Name</span><span class="font-semibold">' + name + '</span></div>' +
        '<div class="flex justify-between"><span class="text-on-surface-variant">Email</span><span class="font-semibold">' + email + '</span></div>' +
        '<div class="flex justify-between"><span class="text-on-surface-variant">Phone</span><span class="font-semibold">' + phone + '</span></div>' +
        '</div>';
      document.getElementById('managedReview').innerHTML = html;
    }'''
        new_review = new_review.replace("</motion>", "</div>").replace("<motion ", "<div ")
        # replace function body via regex
        import re
        pattern = r"    function buildManagedReview\(\) \{[\s\S]*?      document\.getElementById\('managedReview'\)\.innerHTML = html;\n    \}"
        text, n = re.subn(pattern, new_review.strip(), text, count=1)
        if n == 0:
            raise SystemExit("buildManagedReview replace failed")

    # confirmBooking messages
    old_confirm = """        document.getElementById('confManagedDesc').innerHTML = artistName + ' will review your availability and confirm both your consultation and tattoo session times. You\\'ll receive an email once both appointments are confirmed.';
        const info = consultTypeLabels[mcConsultType] || consultTypeLabels.video;
        document.getElementById('confManagedWhatsNext').innerHTML =
          '<li class="flex items-start gap-2"><span class="text-primary mt-0.5">✦</span> The artist will review your availability and schedule both appointments</li>' +
          '<li class="flex items-start gap-2"><span class="text-primary mt-0.5">✦</span> You\\'ll receive an email with both confirmed dates & times</li>' +
          '<li class="flex items-start gap-2"><span class="text-primary mt-0.5">✦</span> Your ' + info.label.toLowerCase() + ' consultation will be scheduled first</li>' +
          '<li class="flex items-start gap-2"><span class="text-primary mt-0.5">✦</span> A deposit may be required after consultation to secure your tattoo session</li>' +
          '<li class="flex items-start gap-2"><span class="text-primary mt-0.5">✦</span> You can message the artist if anything changes</li>';"""

    new_confirm = """        if (consultationRequired) {
          document.getElementById('confManagedDesc').innerHTML = artistName + ' will review your availability and confirm both your consultation and tattoo session times. You\\'ll receive an email once both appointments are confirmed.';
          const info = consultTypeLabels[mcConsultType] || consultTypeLabels.video;
          document.getElementById('confManagedWhatsNext').innerHTML =
            '<li class="flex items-start gap-2"><span class="text-primary mt-0.5">✦</span> The artist will review your availability and schedule both appointments</li>' +
            '<li class="flex items-start gap-2"><span class="text-primary mt-0.5">✦</span> You\\'ll receive an email with both confirmed dates & times</li>' +
            '<li class="flex items-start gap-2"><span class="text-primary mt-0.5">✦</span> Your ' + info.label.toLowerCase() + ' consultation will be scheduled first</li>' +
            '<li class="flex items-start gap-2"><span class="text-primary mt-0.5">✦</span> A deposit may be required after consultation to secure your tattoo session</li>' +
            '<li class="flex items-start gap-2"><span class="text-primary mt-0.5">✦</span> You can message the artist if anything changes</li>';
        } else {
          document.getElementById('confManagedDesc').innerHTML = artistName + ' will review your preferred times and confirm your appointment. You\\'ll receive an email once your booking is confirmed.';
          document.getElementById('confManagedWhatsNext').innerHTML =
            '<li class="flex items-start gap-2"><span class="text-primary mt-0.5">✦</span> The artist will review your availability</li>' +
            '<li class="flex items-start gap-2"><span class="text-primary mt-0.5">✦</span> You\\'ll receive an email with the confirmed date & time</li>' +
            '<li class="flex items-start gap-2"><span class="text-primary mt-0.5">✦</span> A deposit may be required to secure your spot</li>' +
            '<li class="flex items-start gap-2"><span class="text-primary mt-0.5">✦</span> You can message the artist if anything changes</li>';
        }"""

    if old_confirm in text:
        text = text.replace(old_confirm, new_confirm, 1)

    # init configureMcConsultTypeCards
    init_marker = "    // ── Booking Status Check ──"
    if "configureMcConsultTypeCards();" not in text:
        text = text.replace(
            init_marker,
            "    if (consultationRequired) configureMcConsultTypeCards();\n\n    " + init_marker,
            1,
        )

    BLADE.write_text(text, encoding="utf-8")
    print("Patched step2", BLADE)


if __name__ == "__main__":
    main()
