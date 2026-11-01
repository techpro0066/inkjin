@extends('layouts.new-artist-dashboard-layout')

@section('title', 'Schedule')

@php
  $savedStatus = old('availability_status', $savedAvailabilityStatus ?? '');
  $tzLabel = $userTimezone ?? 'UTC';
  $multiStudio = (bool) ($multiStudio ?? false);
  $scheduleStudios = $scheduleStudios ?? [];
  $travelBufferMinutes = (int) ($travelBufferMinutes ?? 120);
  $statusCards = [
    ['key' => 'design_custom', 'icon' => 'bolt', 'title' => 'Open', 'desc' => 'Flash designs + custom requests'],
    ['key' => 'design_only', 'icon' => 'palette', 'title' => 'Flash designs only', 'desc' => 'Only your available flash designs'],
    ['key' => 'custom_only', 'icon' => 'edit_note', 'title' => 'Custom only', 'desc' => 'Only custom tattoo requests'],
    ['key' => 'closed', 'icon' => 'block', 'title' => 'Books closed', 'desc' => 'Not taking new bookings'],
  ];
  $studioDotColors = ['#7A1FC4', '#0E9C86', '#1E5BD8', '#B86E00', '#C62828', '#3E007C'];
@endphp

@section('styles')
@include('partials.bookpay-datetime-picker')
    <style>
  .av-page .help-q{
    display:inline-flex;align-items:center;justify-content:center;width:26px;height:26px;margin-left:8px;
    border-radius:50%;color:#9A929E;vertical-align:middle;text-decoration:none;position:relative;top:-2px
  }
  .av-page .help-q .ms{font-size:22px}
  .av-page .help-q:hover{color:#3E007C;background:#F3E8FF}
  .av-page .bst{border:1px solid var(--line);transition:border-color .15s}
  .av-page .bst:hover{border-color:#C9B8D8}
  .av-page .bst[aria-checked="true"]{border:2px solid #3E007C}
  .av-page .bst .brad{font-size:22px}
  .av-page .whday{display:flex;gap:14px;padding:14px 0;border-bottom:1px solid #F3EEF4}
  .av-page .whday:last-child{border-bottom:0}
  .av-page .whl{
    width:34px;height:34px;border-radius:50%;border:1.5px solid #CFC6D3;display:flex;align-items:center;
    justify-content:center;font-weight:700;font-size:13px;flex-shrink:0;color:var(--muted)
  }
  .av-page .whday.on .whl{background:#1A1A1A;border-color:#1A1A1A;color:#fff}
  .av-page .whbody{flex:1;min-width:0}
  .av-page .whtop{display:flex;justify-content:space-between;align-items:center;gap:10px}
  .av-page .whday.on .whoff,.av-page .whday.on .whadd{display:none}
  .av-page .whoff{font-size:14px;color:var(--muted);margin-top:2px}
  .av-page .whadd{
    width:32px;height:32px;border-radius:50%;background:#1A1A1A;color:#fff;border:0;cursor:pointer;
    display:flex;align-items:center;justify-content:center
  }
  .av-page .whadd .ms{font-size:20px}
  .av-page .whslots,.av-page .whact{display:none}
  .av-page .whday.on .whslots{display:flex;flex-direction:column;gap:8px;margin-top:8px}
  .av-page .whday.on .whact{display:flex;gap:8px;flex-wrap:wrap;margin-top:10px}
  .av-page .whslot{
    display:flex;align-items:center;gap:8px;background:#FBF6FC;border-radius:12px;padding:8px 10px;
    width:max-content;max-width:100%
  }
  .av-page .whslot .in,.av-page .whslot .bpx-btn.in{width:112px;min-height:36px;padding:6px 10px}
  .av-page .whdel{cursor:pointer}
  .av-page .whact .btn{cursor:pointer;font:inherit;font-size:12.5px;font-weight:600}
  .av-page .pgsaverow{justify-content:flex-start;margin-top:16px}
  .av-page .pgsave{opacity:.4;pointer-events:none}
  .av-page .pgsave.is-dirty{opacity:1;pointer-events:auto}
  .av-page .bdnav{border:0;background:none;cursor:pointer;padding:4px;border-radius:8px;color:inherit}
  .av-page .bdnav:hover{background:#F3E8FF}
  .av-page .bdgrid{display:grid;grid-template-columns:repeat(7,1fr);gap:4px;text-align:center}
  .av-page .bdgrid .dw{font-size:11px;font-weight:700;color:var(--faint);padding-bottom:4px}
  .av-page .bdgrid .d{
    height:34px;display:flex;align-items:center;justify-content:center;border-radius:50%;
    font-size:13px;margin:0 auto;width:34px
  }
  .av-page .bdgrid .d.past{color:#CFC6D3}
  .av-page .bdgrid .d.bk{background:#F3E8FF;color:#3E007C;font-weight:700}
  .av-page .bdgrid .d.bl{background:#FDDDDD;color:#C62828;font-weight:700;text-decoration:line-through}
  .av-page .bdgrid .d.today{box-shadow:inset 0 0 0 2px #3E007C}
  .av-page .lg{width:11px;height:11px;border-radius:50%;display:inline-block}
  .av-page .lg-b{background:#FDDDDD;border:1px solid #C62828}
  .av-page .lg-k{background:#F3E8FF;border:1px solid #3E007C}
  .av-page .lg-t{border:2px solid #3E007C}
  .av-page .bditem{
    display:flex;align-items:center;gap:10px;background:#FBF3FC;border-radius:12px;
    padding:11px 12px;margin-bottom:8px
  }
  .av-page .bditem.loc{background:#EAF7F4}
  .av-page .bditem b{font-size:13.5px}
  .av-page .bditem .rs{font-size:12px;color:var(--faint)}
  .av-page .bditem button{border:0;background:none;cursor:pointer;padding:4px;border-radius:6px;color:var(--muted)}
  .av-page .bditem button:hover{background:#fff}
  .av-page .twonote{
    display:flex;gap:8px;align-items:flex-start;background:#EAF7F4;border:1px solid #BFE5DC;color:#0E5C50;
    border-radius:12px;padding:10px 12px;font-size:12.5px;line-height:1.45;margin:10px 0 8px
  }
  .av-page .twonote .ms{font-size:18px;flex-shrink:0;margin-top:1px}
  .av-page .whtop{flex-wrap:wrap}
  .av-page .whlocw{display:none;align-items:flex-start;gap:6px;color:var(--muted);max-width:100%}
  .av-page .whday.on .whlocw{display:flex}
  .av-page .whloc-multi{display:flex;flex-wrap:wrap;gap:6px 10px;align-items:center}
  .av-page .whloc-opt{
    display:inline-flex;align-items:center;gap:5px;font-size:12.5px;color:var(--ink,#1c1b21);
    cursor:pointer;user-select:none;padding:3px 8px;border:1px solid var(--line2,#cac4d3);border-radius:999px;background:#fff
  }
  .av-page .whloc-opt:has(input:checked){border-color:var(--p,#310f7a);background:#FBF5FF;color:var(--p,#310f7a)}
  .av-page .whloc-opt input{margin:0;accent-color:var(--p,#310f7a)}
  .av-page .loc-multi{display:flex;flex-direction:column;gap:8px;margin-top:4px}
  .av-page .loc-opt{
    display:flex;align-items:center;gap:10px;padding:10px 12px;border:1.5px solid var(--line2,#cac4d3);
    border-radius:12px;background:#fff;cursor:pointer;font-size:13.5px;color:var(--ink,#1c1b21)
  }
  .av-page .loc-opt:has(input:checked){border-color:var(--p,#310f7a);background:#FBF5FF}
  .av-page .loc-opt input{margin:0;accent-color:var(--p,#310f7a)}
  .av-page .dot{width:6px;height:6px;border-radius:50%;display:inline-block;margin-left:2px;vertical-align:middle}
  .av-page .bdgrid .d{position:relative}
  .av-page .bdgrid .d .dots{
    position:absolute;bottom:1px;left:50%;transform:translateX(-50%);display:flex;gap:2px;align-items:center
  }
  .av-page .bdgrid .d .dots .dot{position:static;transform:none;margin:0;flex-shrink:0}
  .av-page .bdov,.av-unsaved-ov{
    position:fixed;inset:0;background:rgba(20,10,30,.45);display:none;align-items:center;
    justify-content:center;z-index:400;padding:16px;opacity:0;transition:opacity .2s
  }
  .av-page .bdov.open,.av-unsaved-ov.open{display:flex}
  .av-page .bdov.show,.av-unsaved-ov.show{opacity:1}
  .av-unsaved-ov{z-index:520}
  .av-unsaved-ov [data-x]{
    border:0;background:none;cursor:pointer;padding:4px;border-radius:8px;color:var(--muted);
    display:inline-flex;align-items:center;justify-content:center;line-height:1
  }
  .av-unsaved-ov [data-x]:hover{background:#F3E8FF;color:var(--pri)}
  .av-unsaved-ov [data-x] .ms{font-size:22px}
  .av-page .bdm{
    background:#fff;border-radius:18px;width:420px;max-width:100%;
    box-shadow:0 20px 60px rgba(0,0,0,.25)
  }
  .av-page .bdm .bt{display:flex;justify-content:space-between;align-items:center;padding:18px 20px;border-bottom:1px solid #F0EAF2}
  .av-page .bdm .bt h2{font-size:17px;font-weight:800;margin:0}
  .av-page .bdm .bb{padding:16px 20px;display:grid;gap:12px}
  .av-page .bdm .bf{display:flex;justify-content:flex-end;gap:10px;padding:14px 20px;border-top:1px solid #F0EAF2}
  .av-page .bdm textarea{width:100%;min-height:70px;resize:vertical;font:inherit}
  .av-page .bdm .err{color:#C62828;font-size:12.5px}
  .av-page .bdm .bf .btn.danger{background:#C62828;border-color:#C62828;color:#fff}
  .av-page .bdm .bf .btn.danger:hover{background:#B71C1C;border-color:#B71C1C}
  .av-page .setup-note{
    border:1.5px dashed #E0B86A;background:#FFF8EB;border-radius:12px;padding:12px 14px;
    font-size:13px;color:#6B4E00;display:flex;gap:10px;align-items:flex-start;margin-bottom:16px
  }
  .av-page .setup-note .ms{font-size:20px;flex-shrink:0;margin-top:1px}
  .av-page .mp-toast{
    position:fixed;top:22px;right:24px;background:#1A1A1A;color:#fff;padding:12px 18px;border-radius:10px;
    font-weight:600;font-size:14px;z-index:500;box-shadow:0 10px 30px rgba(0,0,0,.22);
    display:flex;gap:10px;align-items:center;opacity:0;transform:translateX(calc(100% + 40px));
    transition:opacity .25s,transform .35s;pointer-events:none
  }
  .av-page .mp-toast.show{opacity:1;transform:none}
  .av-page .text-error{color:#C62828;font-size:12.5px;margin-top:6px}
  .av-page .hidden{display:none!important}
  @media (max-width:900px){
    .av-page .av-grid{grid-template-columns:1fr!important}
    .av-page .bst-grid{grid-template-columns:1fr 1fr!important}
  }
  @media (max-width:560px){
    .av-page .bst-grid{grid-template-columns:1fr!important}
    .av-page .mp-toast{left:16px;right:16px;top:74px}
      }
  /* Waitlist notify modal (no Tailwind on new layout) */
  #waitlistNotifyModal{position:fixed;inset:0;z-index:320;display:none;align-items:center;justify-content:center;padding:16px}
  #waitlistNotifyModal.is-open,#waitlistNotifyModal.flex{display:flex}
  #waitlistNotifyModal.hidden{display:none!important}
  #waitlistNotifyModalBackdrop{position:absolute;inset:0;background:rgba(0,0,0,.45);border:0;cursor:default}
  #waitlistNotifyModal .waitlist-notify-dialog{position:relative;background:#fff;border-radius:16px;box-shadow:0 20px 50px rgba(0,0,0,.2);width:100%;max-width:28rem;overflow:hidden}
  #waitlistNotifyModal .p-6{padding:24px}
  #waitlistNotifyModal .pb-5{padding-bottom:20px}
  #waitlistNotifyModal .px-6{padding-left:24px;padding-right:24px}
  #waitlistNotifyModal .py-4{padding-top:16px;padding-bottom:16px}
  #waitlistNotifyModal .mb-4{margin-bottom:16px}
  #waitlistNotifyModal .mb-2{margin-bottom:8px}
  #waitlistNotifyModal .mt-1{margin-top:4px}
  #waitlistNotifyModal .mt-4{margin-top:16px}
  #waitlistNotifyModal .w-12{width:48px;height:48px}
  #waitlistNotifyModal .rounded-2xl{border-radius:16px}
  #waitlistNotifyModal .rounded-xl{border-radius:12px}
  #waitlistNotifyModal .bg-primary\/10,  #waitlistNotifyModal [class*="bg-primary"]{background:#EDEDED}
  #waitlistNotifyModal .text-primary{color:#1A1A1A}
  #waitlistNotifyModal #waitlistNotifyModalIcon{background:#EDEDED}
  #waitlistNotifyModal .text-on-surface{color:#1A1A1A}
  #waitlistNotifyModal .text-on-surface-variant{color:#6F6874}
  #waitlistNotifyModal .text-xl{font-size:20px;font-weight:800}
  #waitlistNotifyModal .text-sm{font-size:14px}
  #waitlistNotifyModal .text-xs{font-size:12px}
  #waitlistNotifyModal .font-bold{font-weight:800}
  #waitlistNotifyModal .font-semibold{font-weight:600}
  #waitlistNotifyModal .leading-relaxed{line-height:1.5}
  #waitlistNotifyModal .bg-green-50{background:#E6F8EE;border:1px solid #B7E2C6;border-radius:12px;padding:12px 16px}
  #waitlistNotifyModal .text-green-900{color:#1F6B4E;font-weight:700;font-size:14px}
  #waitlistNotifyModal .text-green-800{color:#2F6B4E;font-size:12px}
  #waitlistNotifyModal .bg-surface-container-low,#waitlistNotifyModal [class*="bg-surface-container"]{background:#F8F1FB}
  #waitlistNotifyModal [class*="border-outline"]{border:1px solid #E8DFEA}
  #waitlistNotifyModal .border-t{border-top:1px solid #E8DFEA}
  #waitlistNotifyModal .flex{display:flex}
  #waitlistNotifyModal .flex-col-reverse{flex-direction:column-reverse}
  #waitlistNotifyModal .gap-3{gap:12px}
  #waitlistNotifyModal .flex-1{flex:1}
  #waitlistNotifyModal .items-center{align-items:center}
  #waitlistNotifyModal .justify-center{justify-content:center}
  #waitlistNotifyModal .inline-flex{display:inline-flex}
  #waitlistNotifyModal button{font:inherit;cursor:pointer}
  #waitlistNotifyModal #waitlistNotifyModalCancel{padding:10px 16px;border-radius:12px;border:1px solid #E8DFEA;background:#fff;color:#1A1A1A;font-weight:600;font-size:14px}
  #waitlistNotifyModal #waitlistNotifyModalConfirm{padding:10px 16px;border-radius:12px;border:0;background:#1A1A1A!important;color:#fff;font-weight:600;font-size:14px}
  #waitlistNotifyModal .hidden{display:none!important}
  @media (min-width:640px){
    #waitlistNotifyModal .sm\:flex-row{flex-direction:row}
      }
    </style>
@endsection

@section('content')
<div class="av-page">
  <div class="head">
    <div>
      <h1>
        Schedule
        <a class="help-q" href="https://help.inkjin.com/en/articles/17200976-dashboard-schedule-availability" target="_blank" rel="noopener" title="Help with this page" aria-label="Help with this page">
          <span class="ms">help</span>
        </a>
      </h1>
      <div class="sub">When and how clients can book you.</div>
      </div>
    </div>

  @include('artist.partials.schedule-tabs', ['scheduleTab' => 'availability'])

  <div class="mp-toast" id="avToast" role="status"><span class="ms">check_circle</span><span id="avToastText">Saved</span></div>

  <div id="avSetupNote" class="setup-note{{ empty($needsWeeklyAvailabilitySetup) ? ' hidden' : '' }}" role="alert">
    <span class="ms">event_available</span>
                <div>
      <b style="display:block;margin-bottom:2px">Weekly hours not configured</b>
      Add at least one working time below and save. Until you do, clients cannot complete bookings with you.
                  </div>
                </div>

  <div class="row" style="justify-content:space-between;margin-bottom:12px;flex-wrap:wrap;gap:10px">
    <h3 style="margin:0">Booking status</h3>
              </div>
  <p id="bookingStatusError" class="text-error hidden" role="alert"></p>
  <input type="hidden" id="availability_status" value="{{ $savedStatus }}">

  <div class="grid bst-grid" style="grid-template-columns:repeat(4,1fr)" id="statusCards" role="radiogroup" aria-label="Booking status">
    @foreach ($statusCards as $card)
      @php $on = $savedStatus === $card['key']; @endphp
      <div
        class="card bst"
        role="radio"
        tabindex="0"
        data-status="{{ $card['key'] }}"
        aria-checked="{{ $on ? 'true' : 'false' }}"
        style="padding:16px;cursor:pointer;{{ $on ? 'border:2px solid #3E007C' : '' }}"
      >
        <div class="row" style="justify-content:space-between">
          <div class="ic"><span class="ms">{{ $card['icon'] }}</span></div>
          <span class="ms brad" style="color:{{ $on ? '#3E007C' : '#C8BFCC' }};font-variation-settings:'FILL' {{ $on ? '1' : '0' }}">
            {{ $on ? 'radio_button_checked' : 'radio_button_unchecked' }}
          </span>
                </div>
        <b style="display:block;margin-top:12px;font-size:14.5px">{{ $card['title'] }}</b>
        <div class="faint" style="font-size:12.5px;margin-top:3px">{{ $card['desc'] }}</div>
        @if ($on)
          <span class="pill g" style="margin-top:10px">Active</span>
        @endif
                  </div>
    @endforeach
          </div>

  @if($multiStudio)
  <div class="card" style="margin-top:26px" id="travelbuf">
    <div class="ch">
                <div>
        <h3>Travel between studios</h3>
        <div class="faint" style="font-size:12.5px;margin-top:2px">Time kept free when you move between studios on the same day</div>
                  </div>
                </div>
    <div style="padding:16px 22px 18px">
      <div class="row" style="gap:14px;align-items:flex-start;flex-wrap:wrap">
        <div style="min-width:180px">
          <span class="fl">Travel buffer</span>
          <select class="in" id="travelBufferSelect" style="width:180px" aria-label="Travel buffer">
            @foreach([30 => '30 minutes', 60 => '1 hour', 90 => '1.5 hours', 120 => '2 hours', 180 => '3 hours', 240 => '4 hours'] as $mins => $label)
              <option value="{{ $mins }}" @selected($travelBufferMinutes === $mins)>{{ $label }}</option>
            @endforeach
          </select>
              </div>
        <div class="faint" style="flex:1;min-width:240px;font-size:13px;line-height:1.55;margin-top:4px">
          Used on days set to <b>All studios</b>. If a client books 10:00 to 13:00 at one studio, clients booking at another that day only see times from <b id="travelBufferExample">{{ sprintf('%02d:00', 13 + intdiv(max(30, $travelBufferMinutes), 60)) }}{{ max(30, $travelBufferMinutes) % 60 === 30 ? ':30' : '' }}</b>. Bookings at the same studio keep your normal buffer time from Booking rules.
            </div>
          </div>
                </div>
  </div>
  @endif

  <div class="grid av-grid" style="grid-template-columns:1fr 1fr;margin-top:26px;align-items:start;gap:16px">
    <div class="card">
      <div class="ch">
                <div>
          <h3>Working hours</h3>
          <div class="faint" style="font-size:12.5px;margin-top:2px">Your regular weekly availability · {{ $tzLabel }}</div>
                  </div>
                </div>
      <div style="padding:4px 22px 16px">
        @if($multiStudio)
          <div class="twonote">
            <span class="ms">storefront</span>
            <span>You have {{ count($scheduleStudios) }} active studios. Pick where you work each day, and clients only see times at that studio. Pick <b>All studios</b> to take bookings at both, with your travel buffer between them.</span>
              </div>
        @endif
        <div id="workingHoursContainer"></div>
            </div>
      <p id="workingHoursError" class="text-error hidden" style="padding:0 22px 16px" role="alert"></p>
          </div>

    <div class="card">
      <div class="ch">
                <div>
          <h3>{{ $multiStudio ? 'Blocked dates & locations' : 'Blocked dates' }}</h3>
          <div class="faint" style="font-size:12.5px;margin-top:2px">
            {{ $multiStudio
              ? "Days you're not available, or at a different studio than usual."
              : "Days you're not available. To unblock, remove the entry below." }}
          </div>
          </div>
                  </div>
      <div style="padding:16px 22px">
        <div class="row" style="justify-content:space-between;margin-bottom:12px">
          <button type="button" class="bdnav" id="bdprev" aria-label="Previous month"><span class="ms">chevron_left</span></button>
          <b id="bdmonth"></b>
          <button type="button" class="bdnav" id="bdnext" aria-label="Next month"><span class="ms">chevron_right</span></button>
                </div>
        <div class="bdgrid" id="bdgrid"></div>
        <div class="row" style="gap:16px;margin-top:12px;font-size:12px;flex-wrap:wrap;color:var(--muted)">
          <span class="row" style="gap:6px"><i class="lg lg-b"></i>Blocked</span>
          <span class="row" style="gap:6px"><i class="lg lg-t"></i>Today</span>
          @if($multiStudio)
            @foreach($scheduleStudios as $si => $st)
              <span class="row" style="gap:6px"><i class="dot" style="background:{{ $studioDotColors[$si % count($studioDotColors)] }}"></i>{{ $st['name'] }}</span>
            @endforeach
          @endif
              </div>
        <div class="row" style="gap:8px;margin-top:14px;flex-wrap:wrap">
          <button type="button" class="btn sm ghost" id="bdone"><span class="ms" style="font-size:16px">event_busy</span>Block a date</button>
          <button type="button" class="btn sm ghost" id="bdrange"><span class="ms" style="font-size:16px">date_range</span>Block a range</button>
          @if($multiStudio)
            <button type="button" class="btn sm ghost" id="bdloc"><span class="ms" style="font-size:16px">storefront</span>Set location for a date</button>
          @endif
            </div>
        <div class="lbl" style="margin:18px 0 6px">{{ $multiStudio ? 'Blocked and location changes' : 'Currently blocked' }}</div>
        <p id="blockedDatesError" class="text-error hidden" role="alert"></p>
        <div id="bdlist"></div>
            </div>
          </div>
        </div>

  <div class="row pgsaverow" id="pgSaveRow">
    <button type="button" class="btn pgsave" id="saveWorkingHoursBtn" disabled aria-disabled="true">
      <span class="ms" id="saveWorkingHoursBtnIcon">save</span>
      <span id="saveWorkingHoursBtnLabel">Save changes</span>
          </button>
        </div>

  <div class="bdov" id="blockModal" aria-hidden="true">
    <div class="bdm" role="dialog" aria-modal="true" aria-labelledby="blockModalTitle" onclick="event.stopPropagation()">
      <div class="bt">
        <h2 id="blockModalTitle">Block a date</h2>
        <button type="button" class="bdnav" id="blockModalClose" aria-label="Close"><span class="ms">close</span></button>
    </div>
      <div class="bb">
        <div id="blockModalFields"></div>
      <div>
          <span class="fl">Reason <span class="faint" style="font-weight:500">(optional)</span></span>
          <textarea class="in" id="blockReason" placeholder="e.g. Personal day, appointment"></textarea>
      </div>
        <div class="err hidden" id="blockModalError"></div>
      </div>
      <div class="bf">
        <button type="button" class="btn ghost" id="blockModalCancel">Cancel</button>
        <button type="button" class="btn" id="blockModalSave"><span class="ms">event_busy</span><span id="blockModalSaveLabel">Block date</span></button>
    </div>
  </div>
</div>

  <div class="bdov" id="blockDeleteModal" aria-hidden="true">
    <div class="bdm" role="dialog" aria-modal="true" aria-labelledby="blockDeleteTitle" onclick="event.stopPropagation()">
      <div class="bt">
        <h2 id="blockDeleteTitle">Remove blocked dates?</h2>
        <button type="button" class="bdnav" id="blockDeleteClose" aria-label="Close"><span class="ms">close</span></button>
    </div>
      <div class="bb">
        <p style="margin:0;font-size:14px;line-height:1.55;color:var(--ink)">
          <span id="blockDeleteLead">Remove this blocked period?</span>
          <span id="blockDeleteRange" style="display:block;margin-top:8px;font-weight:700"></span>
        </p>
        <div class="err hidden" id="blockDeleteError"></div>
        </div>
      <div class="bf">
        <button type="button" class="btn ghost" id="blockDeleteCancel">Cancel</button>
        <button type="button" class="btn danger" id="blockDeleteConfirm"><span class="ms">delete</span>Remove</button>
    </div>
  </div>
</div>

  @if($multiStudio)
  <div class="bdov" id="locationModal" aria-hidden="true">
    <div class="bdm" role="dialog" aria-modal="true" aria-labelledby="locationModalTitle" onclick="event.stopPropagation()">
      <div class="bt">
        <h2 id="locationModalTitle">Set location for a date</h2>
        <button type="button" class="bdnav" id="locationModalClose" aria-label="Close"><span class="ms">close</span></button>
        </div>
      <div class="bb">
        <div class="grid" style="grid-template-columns:1fr 1fr;gap:10px">
          <div><span class="fl">From</span><input class="in" type="date" id="locFrom"></div>
          <div><span class="fl">To</span><input class="in" type="date" id="locTo"></div>
        </div>
        <div>
          <span class="fl">Where will you work?</span>
          <div class="loc-multi" id="locStudioList" role="group" aria-label="Where will you work">
            @foreach($scheduleStudios as $st)
              <label class="loc-opt">
                <input type="checkbox" class="loc-studio-cb" value="{{ $st['id'] }}">
                <span>{{ $st['name'] }}</span>
              </label>
            @endforeach
            <label class="loc-opt">
              <input type="checkbox" id="locStudioAll" value="all">
              <span>All studios</span>
            </label>
      </div>
          <div class="help" style="margin-top:6px">Replaces your usual studio for these days. Clients only see times at the selected studio(s).</div>
    </div>
      <div>
          <span class="fl">Note <span class="faint" style="font-weight:500">(optional)</span></span>
          <input class="in" id="locReason" placeholder="e.g. Covering for another artist" style="width:100%">
      </div>
        <div class="err hidden" id="locationModalError"></div>
      </div>
      <div class="bf">
        <button type="button" class="btn ghost" id="locationModalCancel">Cancel</button>
        <button type="button" class="btn" id="locationModalSave"><span class="ms">storefront</span><span id="locationModalSaveLabel">Set location</span></button>
    </div>
  </div>
</div>
  @endif

  @include('components.waitlist-notify-modal')
</div>
@endsection

@section('scripts')
<script src="{{ asset('js/waitlist-notify.js') }}?v=6"></script>
<script>
(function () {
  var csrf = @json(csrf_token());
  var statusUrl = @json(route('availability.booking-status'));
  var hoursUrl = @json(route('availability.store'));
  var blockStoreUrl = @json(route('availability.override.store'));
  var blockDestroyTpl = @json(route('availability.override.destroy', ['id' => '__ID__']));
  var savedStatus = @json($savedStatus);
  var blockedPeriods = @json($blockedPeriods ?? []);
  var workingHoursData = @json($workingHoursInitial ?? []);
  var todayIso = @json(now()->format('Y-m-d'));
  var multiStudio = @json($multiStudio);
  var scheduleStudios = @json($scheduleStudios);
  var studioDotColors = @json($studioDotColors);
  var travelBufferMinutes = @json($travelBufferMinutes);
  var allStudioIds = scheduleStudios.map(function (s) { return Number(s.id); });

  var MON = ['January','February','March','April','May','June','July','August','September','October','November','December'];
  var view = new Date();
  view = new Date(view.getFullYear(), view.getMonth(), 1);
  var editingBlockId = null;
  var editingLocationId = null;
  var pendingDeleteIndex = null;
  var blockMode = 'date'; // date | range
  var toastTimer = null;
  var hoursDirty = false;
  var selectedStatus = savedStatus || '';

  function studioNameById(id) {
    for (var i = 0; i < scheduleStudios.length; i++) {
      if (Number(scheduleStudios[i].id) === Number(id)) return scheduleStudios[i].name;
    }
    return 'Studio';
  }

  function studioIdsLabel(ids) {
    ids = Array.isArray(ids) ? ids.map(Number) : [];
    if (!ids.length) return '';
    if (isAllStudios(ids)) return 'All studios';
    return ids.map(studioNameById).filter(Boolean).join(', ');
  }

  function isAllStudios(ids) {
    ids = Array.isArray(ids) ? ids.map(Number) : [];
    return allStudioIds.length > 0 && ids.length === allStudioIds.length && allStudioIds.every(function (id) { return ids.indexOf(id) !== -1; });
  }

  function studioDotColorsForIds(ids) {
    ids = Array.isArray(ids) ? ids.map(Number) : [];
    if (!ids.length) return [];
    var list = isAllStudios(ids) ? allStudioIds.slice() : ids;
    return list.map(function (id) {
      var idx = allStudioIds.indexOf(Number(id));
      if (idx < 0) idx = 0;
      return studioDotColors[idx % studioDotColors.length];
    });
  }

  function studioDotsHtml(ids) {
    var colors = studioDotColorsForIds(ids);
    if (!colors.length) return '';
    return '<span class="dots">' + colors.map(function (c) {
      return '<i class="dot" style="background:' + c + '"></i>';
    }).join('') + '</span>';
  }

  function normalizeSelectedStudioIds(ids) {
    ids = Array.isArray(ids) ? ids.map(Number).filter(function (id) { return allStudioIds.indexOf(id) !== -1; }) : [];
    if (!ids.length && allStudioIds.length) return [allStudioIds[0]];
    return ids;
  }

  function readLocationIdsFromContainer(root) {
    if (!root) return allStudioIds.length ? [allStudioIds[0]] : [];
    var allCb = root.querySelector('#locStudioAll, .whloc-all');
    if (allCb && allCb.checked) return allStudioIds.slice();
    var ids = [];
    root.querySelectorAll('.whloc-cb, .loc-studio-cb').forEach(function (cb) {
      if (cb.checked) ids.push(Number(cb.value));
    });
    return normalizeSelectedStudioIds(ids);
  }

  function setLocationCheckboxes(root, ids) {
    if (!root) return;
    ids = normalizeSelectedStudioIds(ids);
    var allOn = isAllStudios(ids);
    var allCb = root.querySelector('#locStudioAll, .whloc-all');
    if (allCb) allCb.checked = allOn;
    root.querySelectorAll('.whloc-cb, .loc-studio-cb').forEach(function (cb) {
      cb.checked = allOn || ids.indexOf(Number(cb.value)) !== -1;
    });
  }

  function locationSelectHtml(day, idx) {
    if (!multiStudio || !scheduleStudios.length) return '';
    var ids = normalizeSelectedStudioIds(day.studio_ids);
    var allOn = isAllStudios(ids);
    var checks = scheduleStudios.map(function (s) {
      var on = allOn || ids.indexOf(Number(s.id)) !== -1;
      return '<label class="whloc-opt"><input type="checkbox" class="whloc-cb" value="' + Number(s.id) + '"' + (on ? ' checked' : '') + '> ' + escapeHtml(s.name) + '</label>';
    }).join('') +
      '<label class="whloc-opt"><input type="checkbox" class="whloc-all" value="all"' + (allOn ? ' checked' : '') + '> All studios</label>';
    return '<span class="whlocw"><span class="ms" style="font-size:16px">storefront</span>' +
      '<div class="whloc-multi" data-loc-idx="' + idx + '" role="group" aria-label="Where on ' + escapeHtml(day.day) + '">' + checks + '</div></span>';
  }

  function syncTravelBufferExample() {
    var el = document.getElementById('travelBufferExample');
    var sel = document.getElementById('travelBufferSelect');
    if (!el || !sel) return;
    var mins = parseInt(sel.value, 10) || 120;
    travelBufferMinutes = mins;
    var end = 13 * 60 + mins;
    var h = Math.floor(end / 60);
    var m = end % 60;
    el.textContent = ('0' + h).slice(-2) + ':' + ('0' + m).slice(-2);
  }
  var pendingNav = null;

  function statusDirty() {
    return String(selectedStatus || '') !== String(savedStatus || '');
  }

  function isPageDirty() {
    return hoursDirty || statusDirty();
  }

  function refreshSaveBtn() {
    var dirty = isPageDirty();
    var btn = document.getElementById('saveWorkingHoursBtn');
    if (!btn) return;
    btn.classList.toggle('is-dirty', dirty);
    btn.disabled = !dirty;
    btn.setAttribute('aria-disabled', dirty ? 'false' : 'true');
    btn.style.opacity = dirty ? '1' : '.4';
    btn.style.pointerEvents = dirty ? 'auto' : 'none';
  }

  function setHoursDirty(v) {
    hoursDirty = !!v;
    refreshSaveBtn();
  }

  function scanDateTime(root) {
    if (typeof window.bpDtScan === 'function') {
      window.bpDtScan(root || document);
    }
  }

  function showToast(msg) {
    var el = document.getElementById('avToast');
    var text = document.getElementById('avToastText');
    if (!el || !text) return;
    text.textContent = msg || 'Saved';
    el.classList.add('show');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(function () { el.classList.remove('show'); }, 2800);
  }

  function escapeHtml(s) {
    var d = document.createElement('div');
    d.textContent = s == null ? '' : String(s);
    return d.innerHTML;
  }

  function iso(d) {
    return d.getFullYear() + '-' + ('0' + (d.getMonth() + 1)).slice(-2) + '-' + ('0' + d.getDate()).slice(-2);
  }

  function parseIso(s) {
    var p = String(s).split('-');
    return new Date(+p[0], +p[1] - 1, +p[2]);
  }

  function nice(s) {
    var d = parseIso(s);
    return MON[d.getMonth()].slice(0, 3) + ' ' + d.getDate() + ', ' + d.getFullYear();
  }

  function postForm(url, fields) {
    var fd = new FormData();
    fd.append('_token', csrf);
    Object.keys(fields || {}).forEach(function (k) {
      var v = fields[k];
      if (v == null) return;
      if (typeof v === 'object' && !(v instanceof File)) {
        // nested handled by caller for hours
        return;
      }
      fd.append(k, v);
    });
    return fetch(url, {
        method: 'POST',
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin',
        body: fd,
    }).then(function (res) {
      return res.json().then(function (body) {
        return { ok: res.ok, status: res.status, body: body };
      });
    });
  }

  // ── Booking status (saved only via Save changes) ──
  function applyStatusUi(key) {
    selectedStatus = key || '';
    document.getElementById('availability_status').value = selectedStatus;
    document.querySelectorAll('#statusCards .bst').forEach(function (x) {
      var on = x.getAttribute('data-status') === selectedStatus;
      var r = x.querySelector('.brad');
      x.style.border = on ? '2px solid #3E007C' : '';
      x.setAttribute('aria-checked', on ? 'true' : 'false');
      if (r) {
        r.textContent = on ? 'radio_button_checked' : 'radio_button_unchecked';
        r.style.color = on ? '#3E007C' : '#C8BFCC';
        r.style.fontVariationSettings = on ? "'FILL' 1" : "'FILL' 0";
      }
      var p = x.querySelector('.pill');
      if (on && !p) {
        x.insertAdjacentHTML('beforeend', '<span class="pill g" style="margin-top:10px">Active</span>');
      }
      if (!on && p) p.remove();
    });
    refreshSaveBtn();
  }

  function pickStatus(card) {
    var key = card.getAttribute('data-status');
    if (!key) return;
    var err = document.getElementById('bookingStatusError');
    if (err) { err.classList.add('hidden'); err.textContent = ''; }
    applyStatusUi(key);
  }

  function promptWaitlistNotify(notify) {
    if (!notify || !notify.pending_count || !notify.notify_url || !window.InkjinWaitlistNotify) {
      return;
    }
    window.setTimeout(function () {
      window.InkjinWaitlistNotify.showPrompt({
        notifyUrl: notify.notify_url,
        pendingCount: Number(notify.pending_count) || 0,
        requestMoveCount: Number(notify.request_move_count) || 0,
      });
    }, 250);
  }

  function saveBookingStatus(val) {
    var err = document.getElementById('bookingStatusError');
    if (err) { err.classList.add('hidden'); err.textContent = ''; }

    return postForm(statusUrl, { availability_status: val }).then(function (result) {
      if (result.ok && result.body && result.body.success) {
        savedStatus = result.body.availability_status || val;
        selectedStatus = savedStatus;
        document.getElementById('availability_status').value = savedStatus;
        refreshSaveBtn();
        promptWaitlistNotify(result.body.waitlist_notify);
        return true;
      }
      var msg = (result.body && result.body.message) || 'Could not save booking status.';
      if (result.body && result.body.errors && result.body.errors.availability_status) {
        msg = result.body.errors.availability_status[0];
      }
      if (err) { err.textContent = msg; err.classList.remove('hidden'); }
      return false;
    }).catch(function () {
      if (err) { err.textContent = 'Network error. Please try again.'; err.classList.remove('hidden'); }
      return false;
    });
  }

  document.querySelectorAll('#statusCards .bst').forEach(function (c) {
    c.addEventListener('click', function () { pickStatus(c); });
    c.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); pickStatus(c); }
    });
  });

  // ── Working hours ──
  function clearWhError() {
    var el = document.getElementById('workingHoursError');
    if (el) { el.classList.add('hidden'); el.textContent = ''; }
  }

  function showWhError(msg) {
    var el = document.getElementById('workingHoursError');
    if (!el) return;
      el.textContent = msg;
      el.classList.remove('hidden');
  }

  function slotEl(start, end) {
    var d = document.createElement('div');
    d.className = 'whslot';
    d.innerHTML =
      '<input class="in" type="time" value="' + escapeHtml(start) + '">' +
      '<span class="faint">–</span>' +
      '<input class="in" type="time" value="' + escapeHtml(end) + '">' +
      '<span class="ms faint whdel" title="Remove time slot">delete</span>';
    return d;
  }

  function syncDayFromDom(row) {
    var idx = +row.getAttribute('data-idx');
    var day = workingHoursData[idx];
    if (!day) return;
    var on = row.classList.contains('on');
    day.available = on;
    day.slots = [];
    var locRoot = row.querySelector('.whloc-multi');
    if (locRoot) day.studio_ids = readLocationIdsFromContainer(locRoot);
    if (!on) return;
    row.querySelectorAll('.whslot').forEach(function (s) {
      var inputs = s.querySelectorAll('input[type="time"]');
      day.slots.push({
        start: inputs[0] ? inputs[0].value : '',
        end: inputs[1] ? inputs[1].value : '',
      });
    });
  }

  function renderWorkingHours() {
    var container = document.getElementById('workingHoursContainer');
    if (!container) return;
    container.innerHTML = '';

    workingHoursData.forEach(function (item, idx) {
      if (!Array.isArray(item.studio_ids) || !item.studio_ids.length) {
        item.studio_ids = allStudioIds.length ? [allStudioIds[0]] : [];
      }
      if (item.available && (!item.slots || !item.slots.length)) item.available = false;
      var row = document.createElement('div');
      row.className = 'whday' + (item.available ? ' on' : '');
      row.setAttribute('data-idx', String(idx));
      row.setAttribute('data-day', item.day);

      var slotsHtml = (item.slots || []).map(function (s) {
        return '<div class="whslot">' +
          '<input class="in" type="time" value="' + escapeHtml(s.start || '') + '">' +
          '<span class="faint">–</span>' +
          '<input class="in" type="time" value="' + escapeHtml(s.end || '') + '">' +
          '<span class="ms faint whdel" title="Remove time slot">delete</span>' +
        '</div>';
      }).join('');

      row.innerHTML =
        '<div class="whl">' + escapeHtml(item.letter) + '</div>' +
        '<div class="whbody">' +
          '<div class="whtop">' +
            '<div><div class="lbl">' + escapeHtml(String(item.day).toUpperCase()) + '</div><div class="whoff">Unavailable</div></div>' +
            locationSelectHtml(item, idx) +
            '<button type="button" class="whadd" aria-label="Make ' + escapeHtml(item.day) + ' available"><span class="ms">add</span></button>' +
          '</div>' +
          '<div class="whslots">' + slotsHtml + '</div>' +
          '<div class="whact">' +
            '<button type="button" class="btn sm ghost whmore"><span class="ms" style="font-size:16px">add</span>Add another time slot</button>' +
            '<button type="button" class="btn sm ghost whoffbtn" style="color:#C62828;border-color:#F3C9C9">Make this day unavailable</button>' +
          '</div>' +
        '</div>';

      var slots = row.querySelector('.whslots');

      row.querySelector('.whadd').addEventListener('click', function () {
        if (!slots.children.length) slots.appendChild(slotEl('09:00', '17:00'));
        row.classList.add('on');
        syncDayFromDom(row);
        scanDateTime(slots);
        markHoursDirty();
      });

      row.querySelector('.whmore').addEventListener('click', function () {
        var last = slots.lastElementChild;
        var inputs = last ? last.querySelectorAll('input[type="time"]') : [];
        var st = inputs[1] ? inputs[1].value : '09:00';
        var h = Math.min(23, parseInt(st, 10) + 1);
        var start = ('0' + h).slice(-2) + ':00';
        var end = ('0' + Math.min(23, h + 3)).slice(-2) + ':00';
        slots.appendChild(slotEl(start, end));
        row.classList.add('on');
        syncDayFromDom(row);
        scanDateTime(slots);
        markHoursDirty();
      });

      row.querySelector('.whoffbtn').addEventListener('click', function () {
        slots.innerHTML = '';
        row.classList.remove('on');
        syncDayFromDom(row);
        markHoursDirty();
      });

      var locRoot = row.querySelector('.whloc-multi');
      if (locRoot) {
        locRoot.addEventListener('change', function (e) {
          var t = e.target;
          if (!t || t.type !== 'checkbox') return;
          if (t.classList.contains('whloc-all')) {
            locRoot.querySelectorAll('.whloc-cb').forEach(function (cb) { cb.checked = t.checked; });
            if (!t.checked && allStudioIds.length) {
              var first = locRoot.querySelector('.whloc-cb');
              if (first) first.checked = true;
            }
    } else {
            var checked = locRoot.querySelectorAll('.whloc-cb:checked');
            if (!checked.length && allStudioIds.length) t.checked = true;
            var allCb = locRoot.querySelector('.whloc-all');
            if (allCb) {
              allCb.checked = locRoot.querySelectorAll('.whloc-cb').length === locRoot.querySelectorAll('.whloc-cb:checked').length;
            }
          }
          item.studio_ids = readLocationIdsFromContainer(locRoot);
          markHoursDirty();
        });
      }

      slots.addEventListener('click', function (e) {
        if (!e.target.classList.contains('whdel')) return;
        e.target.parentNode.remove();
        if (!slots.children.length) row.classList.remove('on');
        syncDayFromDom(row);
        markHoursDirty();
      });

      slots.addEventListener('change', function () {
        syncDayFromDom(row);
        markHoursDirty();
      });

      container.appendChild(row);
    });

    scanDateTime(container);
  }

  function collectHoursPayload() {
    document.querySelectorAll('#workingHoursContainer .whday').forEach(syncDayFromDom);
    var availability = {};
    workingHoursData.forEach(function (day) {
      if (!day.available || !day.slots || !day.slots.length) return;
      availability[day.dayKey] = day.slots.map(function (s) {
        return { from: s.start, to: s.end };
      });
    });
    return availability;
  }

  function saveWorkingHoursOnly() {
    clearWhError();
    var availability = collectHoursPayload();

    for (var i = 0; i < workingHoursData.length; i++) {
      var day = workingHoursData[i];
      if (!day.available || !day.slots) continue;
      var intervals = [];
      for (var s = 0; s < day.slots.length; s++) {
        var a = day.slots[s].start, b = day.slots[s].end;
        if (!a || !b) {
          showWhError('Fill both start and end times on ' + day.day + '.');
          return Promise.resolve(false);
        }
        if (a >= b) {
          showWhError('End time must be after start time on ' + day.day + '.');
          return Promise.resolve(false);
        }
        intervals.push({ from: a, to: b });
      }
      intervals.sort(function (x, y) { return x.from < y.from ? -1 : 1; });
      for (var j = 0; j < intervals.length - 1; j++) {
        if (intervals[j].to > intervals[j + 1].from) {
          showWhError('Overlapping time slots on ' + day.day + '.');
          return Promise.resolve(false);
        }
      }
    }

    var fd = new FormData();
    fd.append('_token', csrf);
    Object.keys(availability).forEach(function (dayKey) {
      availability[dayKey].forEach(function (slot, idx) {
        fd.append('availability[' + dayKey + '][' + idx + '][from]', slot.from);
        fd.append('availability[' + dayKey + '][' + idx + '][to]', slot.to);
      });
    });
    workingHoursData.forEach(function (day) {
      if (!day.available || !day.slots || !day.slots.length) return;
      var ids = normalizeSelectedStudioIds(day.studio_ids);
      var mode = isAllStudios(ids) ? 'all' : 'studio';
      fd.append('day_studio_mode[' + day.dayKey + ']', mode);
      if (mode === 'studio') {
        ids.forEach(function (id, i) {
          fd.append('day_studio_ids[' + day.dayKey + '][' + i + ']', String(id));
        });
        if (ids[0]) fd.append('day_studio_id[' + day.dayKey + ']', String(ids[0]));
      }
    });
    if (multiStudio) {
      var tb = document.getElementById('travelBufferSelect');
      fd.append('travel_buffer_minutes', String(tb ? tb.value : travelBufferMinutes));
    }

    return fetch(hoursUrl, {
      method: 'POST',
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin',
      body: fd,
    }).then(function (res) {
      return res.json().then(function (body) { return { ok: res.ok, body: body }; });
    }).then(function (result) {
      if (result.ok && result.body && result.body.success) {
        hoursDirty = false;
        var note = document.getElementById('avSetupNote');
        if (note) {
          if (result.body.needs_weekly_availability_setup) note.classList.remove('hidden');
          else note.classList.add('hidden');
        }
        refreshSaveBtn();
        return true;
      }
      var msg = (result.body && result.body.message) || 'Could not save working hours.';
      if (result.body && result.body.errors) {
        var first = Object.keys(result.body.errors)[0];
        if (first && result.body.errors[first][0]) msg = result.body.errors[first][0];
      }
      showWhError(msg);
      return false;
    }).catch(function () {
      showWhError('Network error. Please try again.');
      return false;
    });
  }

  function saveChanges(opts) {
    opts = opts || {};
    var btn = document.getElementById('saveWorkingHoursBtn');
    var icon = document.getElementById('saveWorkingHoursBtnIcon');
    var label = document.getElementById('saveWorkingHoursBtnLabel');
    var needStatus = statusDirty();
    var needHours = hoursDirty;

    if (!needStatus && !needHours) {
      return Promise.resolve(true);
    }

    if (needStatus && !selectedStatus) {
      var err = document.getElementById('bookingStatusError');
      if (err) {
        err.textContent = 'Please select a booking status before saving.';
        err.classList.remove('hidden');
      }
      return Promise.resolve(false);
    }

    if (btn) { btn.disabled = true; btn.style.pointerEvents = 'none'; }
    if (icon) icon.textContent = 'hourglass_empty';
    if (label) label.textContent = 'Saving…';

    var chain = Promise.resolve(true);
    if (needStatus) {
      chain = chain.then(function (ok) {
        if (!ok) return false;
        return saveBookingStatus(selectedStatus);
      });
    }
    if (needHours) {
      chain = chain.then(function (ok) {
        if (!ok) return false;
        return saveWorkingHoursOnly();
      });
    }

    return chain.then(function (ok) {
      if (ok) {
        refreshSaveBtn();
        showToast(opts.toast || 'Changes saved');
      }
      return ok;
    }).finally(function () {
      if (icon) icon.textContent = 'save';
      if (label) label.textContent = 'Save changes';
      refreshSaveBtn();
    });
  }

  function markHoursDirty() {
    setHoursDirty(true);
  }

  document.getElementById('saveWorkingHoursBtn').addEventListener('click', function () {
    saveChanges();
  });

  var whContainer = document.getElementById('workingHoursContainer');
  whContainer.addEventListener('input', function (e) {
    if (e.target.closest('.bdm')) return;
    markHoursDirty();
  });
  whContainer.addEventListener('change', function (e) {
    if (e.target.closest('.bdm')) return;
    markHoursDirty();
  });
  whContainer.addEventListener('click', function (e) {
    if (e.target.closest('.whadd,.whmore,.whoffbtn,.whdel')) markHoursDirty();
  }, true);

  function navigatePending(nav) {
    if (!nav) return;
    var href = nav.getAttribute('href');
    if (href && href !== '#' && !href.startsWith('javascript:')) {
      window.location.href = href;
      return;
    }
    nav.click();
  }

  function openUnsavedModal() {
    if (document.querySelector('.av-unsaved-ov')) return;

    var o = document.createElement('div');
    o.className = 'av-unsaved-ov open show';
    o.setAttribute('role', 'presentation');
    o.innerHTML =
      '<div role="dialog" aria-modal="true" style="background:#fff;border-radius:22px;width:420px;max-width:100%;box-shadow:0 20px 60px rgba(0,0,0,.25);overflow:hidden;position:relative">' +
        '<button type="button" data-x class="bdnav" aria-label="Close" style="position:absolute;top:14px;right:14px;z-index:1">' +
          '<span class="ms">close</span>' +
        '</button>' +
        '<div style="padding:24px 24px 16px">' +
          '<div style="width:48px;height:48px;border-radius:12px;background:#FFF3DC;color:#B7791F;display:flex;align-items:center;justify-content:center;margin-bottom:14px"><span class="ms">warning</span></div>' +
          '<h2 style="font-size:20px;font-weight:800;margin-bottom:8px;padding-right:28px">Your changes are not saved</h2>' +
          '<p style="color:#3a3a3a;line-height:1.55;margin:0">Save them before you leave, or leave without saving and lose them.</p>' +
        '</div>' +
        '<div style="display:flex;gap:10px;padding:16px 24px;background:#FCF6FE;border-top:1px solid #F0E4F5;flex-wrap:wrap">' +
          '<button type="button" data-l class="btn ghost" style="flex:1;justify-content:center">Leave without saving</button>' +
          '<button type="button" data-s class="btn" style="flex:1;justify-content:center"><span class="ms">save</span>Save and leave</button>' +
        '</div>' +
      '</div>';
    document.body.appendChild(o);
    document.body.style.overflow = 'hidden';

    function close() {
      o.remove();
      document.body.style.overflow = '';
      document.removeEventListener('keydown', onKey, true);
      pendingNav = null;
    }
    function onKey(ev) {
      if (ev.key === 'Escape') { ev.preventDefault(); close(); }
    }
    document.addEventListener('keydown', onKey, true);

    o.addEventListener('click', function (ev) {
      if (ev.target === o || ev.target.closest('[data-x]')) { close(); return; }
      var leave = ev.target.closest('[data-l]');
      var saveLeave = ev.target.closest('[data-s]');
      if (!leave && !saveLeave) return;
      var nav = pendingNav;
      pendingNav = null;
      if (saveLeave) {
        saveChanges({ toast: 'Changes saved' }).then(function (ok) {
          close();
          if (ok) navigatePending(nav);
        });
      return;
    }
      applyStatusUi(savedStatus || '');
      setHoursDirty(false);
      close();
      navigatePending(nav);
    });
  }

  document.addEventListener('click', function (e) {
    if (!isPageDirty()) return;
    if (e.target.closest('.av-unsaved-ov,.bdov,.pgsaverow,[role=dialog]')) return;

    var n = e.target.closest('aside a[href], .tabs a[href], a.user[href], a[href]:not([href="#"])');
    if (!n) return;
    if (n.matches('a[href^="#"]') || n.getAttribute('target') === '_blank') return;
    // Same-page Availability tab — nothing to leave.
    try {
      var dest = new URL(n.href, window.location.href);
      if (dest.pathname === window.location.pathname && dest.search === window.location.search) return;
    } catch (err) {}

    e.preventDefault();
    e.stopPropagation();
    if (typeof e.stopImmediatePropagation === 'function') e.stopImmediatePropagation();
    pendingNav = n;
    openUnsavedModal();
  }, true);

  window.addEventListener('beforeunload', function (e) {
    if (!isPageDirty()) return;
    e.preventDefault();
    e.returnValue = '';
  });

  renderWorkingHours();
  selectedStatus = savedStatus || '';
  refreshSaveBtn();
  scanDateTime(whContainer);

  // ── Blocked dates & locations ──
  function isBlockedOnly(s) {
    return blockedPeriods.some(function (i) {
      return !i.is_location && !(Array.isArray(i.studio_ids) && i.studio_ids.length) && s >= i.start_date && s <= i.end_date;
    });
  }

  function locationOverrideFor(s) {
    return blockedPeriods.filter(function (i) {
      return (i.is_location || (Array.isArray(i.studio_ids) && i.studio_ids.length)) && s >= i.start_date && s <= i.end_date;
    })[0] || null;
  }

  function defaultStudioIdsForWeekday(isoDate) {
    var d = parseIso(isoDate);
    var jsDay = d.getDay(); // 0 Sun .. 6 Sat
    var map = { 1: 'monday', 2: 'tuesday', 3: 'wednesday', 4: 'thursday', 5: 'friday', 6: 'saturday', 0: 'sunday' };
    var key = map[jsDay];
    for (var i = 0; i < workingHoursData.length; i++) {
      if (workingHoursData[i].dayKey === key && workingHoursData[i].available) {
        return Array.isArray(workingHoursData[i].studio_ids) ? workingHoursData[i].studio_ids.slice() : [];
      }
    }
    // Day marked unavailable in working hours → no studio dot
    return [];
  }

  function renderCal() {
    document.getElementById('bdmonth').textContent = MON[view.getMonth()] + ' ' + view.getFullYear();
    var g = document.getElementById('bdgrid');
    var h = '';
    ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'].forEach(function (x) {
      h += '<div class="dw">' + x + '</div>';
    });
    var first = (view.getDay() + 6) % 7;
    for (var i = 0; i < first; i++) h += '<div></div>';
    var n = new Date(view.getFullYear(), view.getMonth() + 1, 0).getDate();
    for (var d = 1; d <= n; d++) {
      var s = iso(new Date(view.getFullYear(), view.getMonth(), d));
      var c = 'd';
      if (s < todayIso) c += ' past';
      else if (isBlockedOnly(s)) c += ' bl';
      if (s === todayIso) c += ' today';
      var dot = '';
      if (multiStudio && s >= todayIso && !isBlockedOnly(s)) {
        var loc = locationOverrideFor(s);
        var ids = loc ? (loc.studio_ids || []) : defaultStudioIdsForWeekday(s);
        dot = studioDotsHtml(ids);
      }
      h += '<div class="' + c + '">' + d + dot + '</div>';
    }
    g.innerHTML = h;
  }

  function renderList() {
    var l = document.getElementById('bdlist');
    var items = blockedPeriods.slice().sort(function (a, b) {
      return a.start_date < b.start_date ? -1 : 1;
    });
    blockedPeriods = items;
    if (!items.length) {
      l.innerHTML = '<div class="faint" style="font-size:13px">' + (multiStudio ? 'No blocked dates or location changes.' : 'No blocked dates.') + '</div>';
      return;
    }
    l.innerHTML = items.map(function (i, k) {
      var isLoc = !!(i.is_location || (Array.isArray(i.studio_ids) && i.studio_ids.length));
      var label = i.start_date === i.end_date
        ? nice(i.start_date)
        : nice(i.start_date) + ' to ' + nice(i.end_date);
      if (isLoc) label += ' · at ' + studioIdsLabel(i.studio_ids);
      return '<div class="bditem' + (isLoc ? ' loc' : '') + '">' +
        '<span class="ms" style="color:' + (isLoc ? '#0E7C6B' : '#C62828') + '">' + (isLoc ? 'storefront' : 'event_busy') + '</span>' +
        '<div style="flex:1"><b>' + escapeHtml(label) + '</b>' +
        (i.reason ? '<div class="rs">' + escapeHtml(i.reason) + '</div>' : '') +
        '</div>' +
        '<button type="button" data-e="' + k + '" aria-label="Edit"><span class="ms">edit</span></button>' +
        '<button type="button" data-d="' + k + '" aria-label="Remove" style="color:#C62828"><span class="ms">delete</span></button>' +
      '</div>';
    }).join('');
  }

  function renderBlocked() {
    renderCal();
    renderList();
  }

  function openBlockModal(range, index) {
    var it = index == null ? null : blockedPeriods[index];
    if (it && (it.is_location || (Array.isArray(it.studio_ids) && it.studio_ids.length))) {
      openLocationModal(index);
      return;
    }
    editingBlockId = it ? it.id : null;
    blockMode = it ? (it.start_date !== it.end_date ? 'range' : 'date') : (range ? 'range' : 'date');
    var isRange = blockMode === 'range';
    var title = (it ? 'Edit blocked ' : 'Block a ') + (isRange ? 'range' : 'date');
    document.getElementById('blockModalTitle').textContent = title;
    document.getElementById('blockModalSaveLabel').textContent = it
      ? 'Save'
      : ('Block ' + (isRange ? 'range' : 'date'));
    document.getElementById('blockModalError').classList.add('hidden');
    document.getElementById('blockModalError').textContent = '';
    document.getElementById('blockReason').value = it ? (it.reason || '') : '';
    document.getElementById('blockReason').placeholder = isRange
      ? 'e.g. Vacation, convention, travel'
      : 'e.g. Personal day, appointment';

    var fields = document.getElementById('blockModalFields');
    if (isRange) {
      fields.innerHTML =
        '<div class="grid" style="grid-template-columns:1fr 1fr;gap:10px">' +
          '<div><span class="fl">From</span><input class="in" type="date" id="bdf"></div>' +
          '<div><span class="fl">To</span><input class="in" type="date" id="bdt"></div>' +
          '</div>';
      } else {
      fields.innerHTML = '<div><span class="fl">Date</span><input class="in" type="date" id="bdf"></div>';
    }

    var f = document.getElementById('bdf');
    var t = document.getElementById('bdt');
    if (!it) {
      f.min = todayIso;
      if (t) t.min = todayIso;
    }
    if (it) {
      f.value = it.start_date;
      if (t) t.value = it.end_date;
      } else {
      f.value = '';
      if (t) t.value = '';
    }

    var ov = document.getElementById('blockModal');
    ov.classList.add('open');
    requestAnimationFrame(function () { ov.classList.add('show'); });
    ov.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
    scanDateTime(ov);
    setTimeout(function () {
      if (f && f.__bpx) f.__bpx.focus();
      else if (f) f.focus();
    }, 50);
  }

  function closeBlockModal() {
    var ov = document.getElementById('blockModal');
    ov.classList.remove('show');
    setTimeout(function () {
      ov.classList.remove('open');
      ov.setAttribute('aria-hidden', 'true');
    }, 180);
    document.body.style.overflow = '';
    editingBlockId = null;
  }

  function showBlockError(msg) {
    var e = document.getElementById('blockModalError');
    e.textContent = msg;
    e.classList.remove('hidden');
  }

  function saveBlock() {
    var f = document.getElementById('bdf');
    var t = document.getElementById('bdt');
    var fv = f ? f.value : '';
    var tv = t ? t.value : fv;
    var reason = document.getElementById('blockReason').value.trim();
    document.getElementById('blockModalError').classList.add('hidden');

    if (!fv || !tv) {
      showBlockError('Pick ' + (blockMode === 'range' ? 'both dates' : 'a date') + '.');
      return;
    }
    if (tv < fv) {
      showBlockError('The end date is before the start date.');
      return;
    }

    var fields = {
      start_date: fv,
      end_date: tv,
      reason: reason,
      type: 'blocked',
    };
    if (editingBlockId) fields.block_id = editingBlockId;

    var btn = document.getElementById('blockModalSave');
    btn.disabled = true;

    postForm(blockStoreUrl, fields).then(function (result) {
      if (result.ok && result.body && result.body.success && result.body.block) {
        var b = result.body.block;
        if (editingBlockId) {
          blockedPeriods = blockedPeriods.map(function (x) {
            return x.id === editingBlockId ? b : x;
          });
        } else {
          blockedPeriods.push(b);
        }
        view = new Date(parseIso(fv).getFullYear(), parseIso(fv).getMonth(), 1);
        closeBlockModal();
        renderBlocked();
        showToast(result.body.message || 'Dates blocked');
        return;
      }
      var msg = (result.body && result.body.message) || 'Could not save blocked dates.';
      if (result.body && result.body.errors) {
        var first = Object.keys(result.body.errors)[0];
        if (first && result.body.errors[first][0]) msg = result.body.errors[first][0];
      }
      showBlockError(msg);
    }).catch(function () {
      showBlockError('Network error. Please try again.');
    }).finally(function () {
      btn.disabled = false;
    });
  }

  function openLocationModal(index) {
    if (!multiStudio) return;
    var it = index == null ? null : blockedPeriods[index];
    editingLocationId = it ? it.id : null;
    document.getElementById('locationModalTitle').textContent = it ? 'Edit location' : 'Set location for a date';
    document.getElementById('locationModalSaveLabel').textContent = it ? 'Save' : 'Set location';
    document.getElementById('locationModalError').classList.add('hidden');
    document.getElementById('locationModalError').textContent = '';
    var f = document.getElementById('locFrom');
    var t = document.getElementById('locTo');
    var list = document.getElementById('locStudioList');
    var r = document.getElementById('locReason');
    f.min = todayIso;
    t.min = todayIso;
    if (it) {
      f.value = it.start_date;
      t.value = it.end_date;
      r.value = it.reason || '';
      setLocationCheckboxes(list, it.studio_ids || []);
    } else {
      f.value = '';
      t.value = '';
      r.value = '';
      var defaultIds = allStudioIds.length > 1
        ? [allStudioIds[Math.min(1, allStudioIds.length - 1)]]
        : (allStudioIds[0] ? [allStudioIds[0]] : []);
      setLocationCheckboxes(list, defaultIds);
    }
    var ov = document.getElementById('locationModal');
    ov.classList.add('open');
    requestAnimationFrame(function () { ov.classList.add('show'); });
    ov.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
    scanDateTime(ov);
  }

  function closeLocationModal() {
    var ov = document.getElementById('locationModal');
    if (!ov) return;
    ov.classList.remove('show');
    setTimeout(function () {
      ov.classList.remove('open');
      ov.setAttribute('aria-hidden', 'true');
    }, 180);
    document.body.style.overflow = '';
    editingLocationId = null;
  }

  function showLocationError(msg) {
    var e = document.getElementById('locationModalError');
    e.textContent = msg;
    e.classList.remove('hidden');
  }

  function saveLocation() {
    var f = document.getElementById('locFrom');
    var t = document.getElementById('locTo');
    var list = document.getElementById('locStudioList');
    var fv = f ? f.value : '';
    var tv = t && t.value ? t.value : fv;
    var reason = (document.getElementById('locReason').value || '').trim();
    document.getElementById('locationModalError').classList.add('hidden');
    if (!fv) { showLocationError('Pick a date.'); return; }
    if (tv < fv) { showLocationError('The end date is before the start date.'); return; }

    var studioIds = readLocationIdsFromContainer(list);
    if (!studioIds.length) { showLocationError('Pick at least one studio.'); return; }
    var mode = isAllStudios(studioIds) ? 'all' : 'studio';

    var fields = {
      start_date: fv,
      end_date: tv,
      reason: reason,
      type: 'location',
      location_mode: mode,
    };
    studioIds.forEach(function (id, idx) { fields['studio_ids[' + idx + ']'] = id; });
    if (editingLocationId) fields.block_id = editingLocationId;

    var btn = document.getElementById('locationModalSave');
    btn.disabled = true;
    postForm(blockStoreUrl, fields).then(function (result) {
      if (result.ok && result.body && result.body.success && result.body.block) {
        var b = result.body.block;
        if (editingLocationId) {
          blockedPeriods = blockedPeriods.map(function (x) {
            return x.id === editingLocationId ? b : x;
          });
        } else {
          blockedPeriods.push(b);
        }
        view = new Date(parseIso(fv).getFullYear(), parseIso(fv).getMonth(), 1);
        closeLocationModal();
        renderBlocked();
        showToast(result.body.message || 'Location set');
        return;
      }
      var msg = (result.body && result.body.message) || 'Could not save location.';
      if (result.body && result.body.errors) {
        var first = Object.keys(result.body.errors)[0];
        if (first && result.body.errors[first][0]) msg = result.body.errors[first][0];
      }
      showLocationError(msg);
    }).catch(function () {
      showLocationError('Network error. Please try again.');
    }).finally(function () {
      btn.disabled = false;
    });
  }

  function openBlockDeleteModal(index) {
    var b = blockedPeriods[index];
    if (!b) return;
    pendingDeleteIndex = index;
    var isLoc = !!(b.is_location || (Array.isArray(b.studio_ids) && b.studio_ids.length));
    var range = b.start_date === b.end_date
      ? nice(b.start_date)
      : nice(b.start_date) + ' to ' + nice(b.end_date);
    if (isLoc) range += ' · at ' + studioIdsLabel(b.studio_ids);
    document.getElementById('blockDeleteTitle').textContent = isLoc ? 'Remove location change?' : 'Remove blocked dates?';
    document.getElementById('blockDeleteLead').textContent = isLoc
      ? 'Remove this location change?'
      : 'Remove this blocked period?';
    document.getElementById('blockDeleteRange').textContent = range;
    var err = document.getElementById('blockDeleteError');
    err.classList.add('hidden');
    err.textContent = '';
    var ov = document.getElementById('blockDeleteModal');
    ov.classList.add('open');
    requestAnimationFrame(function () { ov.classList.add('show'); });
    ov.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
    setTimeout(function () {
      var btn = document.getElementById('blockDeleteConfirm');
      if (btn) btn.focus();
    }, 50);
  }

  function closeBlockDeleteModal() {
    var ov = document.getElementById('blockDeleteModal');
    ov.classList.remove('show');
    setTimeout(function () {
      ov.classList.remove('open');
      ov.setAttribute('aria-hidden', 'true');
    }, 180);
    document.body.style.overflow = '';
    pendingDeleteIndex = null;
    var btn = document.getElementById('blockDeleteConfirm');
    if (btn) {
      btn.disabled = false;
      btn.innerHTML = '<span class="ms">delete</span>Remove';
    }
  }

  function confirmDestroyBlock() {
    var index = pendingDeleteIndex;
    var b = index == null ? null : blockedPeriods[index];
    if (!b) {
      closeBlockDeleteModal();
      return;
    }
    var btn = document.getElementById('blockDeleteConfirm');
    var err = document.getElementById('blockDeleteError');
    err.classList.add('hidden');
    err.textContent = '';
    btn.disabled = true;
    btn.textContent = 'Removing…';

    var url = blockDestroyTpl.replace('__ID__', encodeURIComponent(String(b.id)));
    var fd = new FormData();
    fd.append('_token', csrf);
    fd.append('_method', 'DELETE');
    fetch(url, {
      method: 'POST',
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin',
      body: fd,
    }).then(function (res) {
      return res.json().then(function (body) { return { ok: res.ok, body: body }; });
    }).then(function (result) {
      if (result.ok && result.body && result.body.success) {
        blockedPeriods.splice(index, 1);
        closeBlockDeleteModal();
        renderBlocked();
        showToast(result.body.message || 'Dates unblocked');
        return;
      }
      err.textContent = (result.body && result.body.message) || 'Could not remove blocked dates.';
      err.classList.remove('hidden');
      btn.disabled = false;
      btn.innerHTML = '<span class="ms">delete</span>Remove';
    }).catch(function () {
      err.textContent = 'Network error. Please try again.';
      err.classList.remove('hidden');
      btn.disabled = false;
      btn.innerHTML = '<span class="ms">delete</span>Remove';
    });
  }

  document.getElementById('bdprev').addEventListener('click', function () {
    view = new Date(view.getFullYear(), view.getMonth() - 1, 1);
    renderCal();
  });
  document.getElementById('bdnext').addEventListener('click', function () {
    view = new Date(view.getFullYear(), view.getMonth() + 1, 1);
    renderCal();
  });
  document.getElementById('bdone').addEventListener('click', function () { openBlockModal(false); });
  document.getElementById('bdrange').addEventListener('click', function () { openBlockModal(true); });
  document.getElementById('blockModalClose').addEventListener('click', closeBlockModal);
  document.getElementById('blockModalCancel').addEventListener('click', closeBlockModal);
  document.getElementById('blockModalSave').addEventListener('click', saveBlock);
  document.getElementById('blockModal').addEventListener('click', function (e) {
    if (e.target === this) closeBlockModal();
  });
  document.getElementById('blockDeleteClose').addEventListener('click', closeBlockDeleteModal);
  document.getElementById('blockDeleteCancel').addEventListener('click', closeBlockDeleteModal);
  document.getElementById('blockDeleteConfirm').addEventListener('click', confirmDestroyBlock);
  document.getElementById('blockDeleteModal').addEventListener('click', function (e) {
    if (e.target === this) closeBlockDeleteModal();
  });
  var bdloc = document.getElementById('bdloc');
  if (bdloc) bdloc.addEventListener('click', function () { openLocationModal(null); });
  var locClose = document.getElementById('locationModalClose');
  var locCancel = document.getElementById('locationModalCancel');
  var locSave = document.getElementById('locationModalSave');
  var locModal = document.getElementById('locationModal');
  var locList = document.getElementById('locStudioList');
  if (locList) {
    locList.addEventListener('change', function (e) {
      var t = e.target;
      if (!t || t.type !== 'checkbox') return;
      var allCb = document.getElementById('locStudioAll');
      var studioCbs = locList.querySelectorAll('.loc-studio-cb');
      if (t.id === 'locStudioAll') {
        studioCbs.forEach(function (cb) { cb.checked = t.checked; });
        if (!t.checked && studioCbs.length) studioCbs[0].checked = true;
            } else {
        var checked = locList.querySelectorAll('.loc-studio-cb:checked');
        if (!checked.length) t.checked = true;
        if (allCb) allCb.checked = studioCbs.length === locList.querySelectorAll('.loc-studio-cb:checked').length;
      }
    });
  }
  if (locClose) locClose.addEventListener('click', closeLocationModal);
  if (locCancel) locCancel.addEventListener('click', closeLocationModal);
  if (locSave) locSave.addEventListener('click', saveLocation);
  if (locModal) locModal.addEventListener('click', function (e) {
    if (e.target === this) closeLocationModal();
  });
  var travelSel = document.getElementById('travelBufferSelect');
  if (travelSel) {
    syncTravelBufferExample();
    travelSel.addEventListener('change', function () {
      syncTravelBufferExample();
      markHoursDirty();
    });
  }
  document.getElementById('bdlist').addEventListener('click', function (e) {
    var b = e.target.closest('button');
    if (!b) return;
    if (b.dataset.e != null) openBlockModal(null, +b.dataset.e);
    if (b.dataset.d != null) openBlockDeleteModal(+b.dataset.d);
  });
  document.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape') return;
    if (document.getElementById('blockDeleteModal').classList.contains('open')) {
      closeBlockDeleteModal();
          return;
        }
    if (locModal && locModal.classList.contains('open')) {
      closeLocationModal();
          return;
        }
    if (document.getElementById('blockModal').classList.contains('open')) {
      closeBlockModal();
    }
  });

  renderBlocked();
})();
</script>
@endsection
