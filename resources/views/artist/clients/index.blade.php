@extends('layouts.new-artist-dashboard-layout')

@section('title', 'Clients')

@php
  $activeNav = 'clients';
  $clientsTotal = (int) ($stats['total'] ?? 0);
  $waitlistTotal = (int) ($stats['waitlist'] ?? 0);
  $guestWaitlistTotal = (int) ($stats['guest_waitlist'] ?? 0);
@endphp

@section('styles')
<style>
  .cl-page .grid.stats{grid-template-columns:repeat(4,minmax(0,1fr));gap:16px;margin-top:4px}
  .cl-page .stat .v{font-size:28px;font-weight:800;margin-top:12px;letter-spacing:-.4px}
  .cl-page .stat .l{font-size:12.5px;color:var(--faint);margin-top:2px}
  .cl-page .cl-tools{display:flex;gap:10px;margin:18px 0 12px;flex-wrap:wrap;align-items:center}
  .cl-page .cl-search{flex:1;min-width:200px}
  .cl-page .cl-search .in{padding:0 13px}
  .cl-page .cl-search input{
    border:0!important;outline:0!important;box-shadow:none!important;background:transparent!important;
    font:inherit;font-size:13.5px;flex:1;min-width:0;padding:10px 0;color:var(--ink);width:100%
  }
  .cl-page .cl-search input::placeholder{color:var(--faint)}
  .cl-page .cl-sel{width:170px;max-width:100%}
  .cl-page select.in{cursor:pointer;appearance:auto}
  .cl-page .cl-act{background:none;border:0;padding:0;margin:0;cursor:pointer;color:var(--faint);line-height:1}
  .cl-page .cl-act:hover{color:var(--ink)}
  .cl-page .cl-act .ms{font-size:22px}
  .cl-page .cl-empty{padding:40px 20px;text-align:center;color:var(--muted);font-size:13.5px}
  #m-cl{position:fixed;inset:0;background:rgba(20,10,30,.45);display:flex;align-items:center;justify-content:center;z-index:320;padding:24px}
  #m-cl[hidden],#m-cl [hidden]{display:none!important}
  #m-cl .cl-m{
    background:#fff;border-radius:18px;width:1080px;max-width:100%;max-height:calc(100vh - 48px);max-height:calc(100dvh - 48px);
    display:flex;flex-direction:column;box-shadow:0 20px 60px rgba(0,0,0,.25);font-size:13.5px;color:#1A1A1A;overflow:hidden
  }
  #m-cl .cl-mh{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:18px 24px 14px;border-bottom:1px solid #EFE8F1;flex-shrink:0}
  #m-cl .cl-mh h2{font-size:18px;font-weight:800;margin:0;letter-spacing:-.2px}
  #m-cl .cl-mx{border:0;background:none;cursor:pointer;color:#8F8A95;padding:2px;display:flex}
  #m-cl .cl-mx:hover{color:#1A1A1A}
  #m-cl .cl-mx .ms{font-size:22px}
  #m-cl .cl-mb{padding:18px 24px;overflow:auto;flex:1;min-height:0}
  #m-cl .cl-grid{display:grid;grid-template-columns:minmax(280px,340px) minmax(0,1fr);gap:18px;align-items:start}
  #m-cl .cl-profile{display:flex;gap:12px;align-items:flex-start;margin-bottom:14px}
  #m-cl .cl-profile .ini{width:48px;height:48px;font-size:15px;flex:none;background:#111;color:#fff}
  #m-cl .cl-profile .nm{font-size:17px;font-weight:800;margin:0;letter-spacing:-.2px}
  #m-cl .cl-profile .cl-sub{color:#6F6874;font-size:12.5px;margin-top:6px;display:flex;align-items:center;gap:8px;flex-wrap:wrap}
  #m-cl .cl-card{border:1px solid #E8DFEA;border-radius:14px;background:#fff;padding:14px 16px;margin-bottom:12px}
  #m-cl .cl-card:last-child{margin-bottom:0}
  #m-cl .cl-card h4{font-size:11px;font-weight:700;letter-spacing:.7px;color:#6F6874;margin:0 0 12px;text-transform:uppercase}
  #m-cl .cl-contact .item{display:flex;align-items:center;gap:8px;font-size:13.5px;padding:7px 0;border-bottom:1px solid #F3EEF4}
  #m-cl .cl-contact .item:last-child{border-bottom:0;padding-bottom:0}
  #m-cl .cl-contact .item:first-child{padding-top:0}
  #m-cl .cl-contact .ms{font-size:18px;color:#6F6874;flex:none}
  #m-cl .cl-contact .val{flex:1;min-width:0;word-break:break-word}
  #m-cl .cl-contact .faint{color:#9A929E}
  #m-cl .cl-copy{background:none;border:0;padding:0;cursor:pointer;color:#9A929E;display:inline-flex;line-height:1;flex:none}
  #m-cl .cl-copy:hover{color:#1A1A1A}
  #m-cl .cl-copy .ms{font-size:16px}
  #m-cl .cl-about{display:grid;gap:10px}
  #m-cl .cl-about .row{display:flex;justify-content:space-between;gap:12px;align-items:baseline}
  #m-cl .cl-about .k{color:#6F6874;font-size:13px}
  #m-cl .cl-about .v{font-size:13.5px;text-align:right;font-weight:500}
  #m-cl .cl-about .v.strong{font-weight:700;color:#1A1A1A}
  #m-cl .cl-about .v.due{font-weight:800;color:#C47A00}
  #m-cl textarea.in{
    display:block;min-height:84px;resize:vertical;width:100%;box-sizing:border-box;
    font:inherit;font-size:13.5px;line-height:1.45;padding:10px 13px;border:1px solid #E8DFEA;border-radius:10px;background:#FAFAFA
  }
  #m-cl textarea.in:focus{outline:0;border-color:#C9B8CF;background:#fff;box-shadow:none}
  #m-cl .cl-note-ft{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-top:10px;flex-wrap:wrap}
  #m-cl .cl-note-ft .hint{font-size:12px;color:#9A929E}
  #m-cl .cl-kpis{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:10px;margin-bottom:14px}
  #m-cl .cl-kpi{background:#FAF0FB;border:1px solid #F1E7F3;border-radius:12px;padding:12px 10px;text-align:center}
  #m-cl .cl-kpi b{display:block;font-size:18px;font-weight:800;letter-spacing:-.3px}
  #m-cl .cl-kpi span{display:block;font-size:11.5px;color:#6F6874;margin-top:3px}
  #m-cl .cl-hist{border:1px solid #E8DFEA;border-radius:14px;overflow:hidden;background:#fff;margin-bottom:14px}
  #m-cl .cl-hist .cl-sec-h{padding:12px 14px 0;font-size:13px;font-weight:700}
  #m-cl .cl-hist table{width:100%;border-collapse:collapse}
  #m-cl .cl-hist th{font-size:10.5px;letter-spacing:.8px;color:#6F6874;text-align:left;font-weight:700;padding:10px 14px;background:#FBF8FC;border-bottom:1px solid #E8DFEA}
  #m-cl .cl-hist td{padding:12px 14px;border-bottom:1px solid #F3EEF4;font-size:13px;vertical-align:middle}
  #m-cl .cl-hist tr:last-child td{border-bottom:0}
  #m-cl .cl-hist .due{font-weight:700;color:#C47A00}
  #m-cl .cl-reqs{border:1px solid #E8DFEA;border-radius:14px;overflow:hidden;background:#fff}
  #m-cl .cl-reqs .cl-sec-h{padding:12px 14px;font-size:13px;font-weight:700;border-bottom:1px solid #EFE8F1}
  #m-cl .cl-req{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:12px 14px;border-bottom:1px solid #F3EEF4}
  #m-cl .cl-req:last-child{border-bottom:0}
  #m-cl .cl-req .t{font-weight:600;font-size:13.5px}
  #m-cl .cl-req .m{font-size:12px;color:#6F6874;margin-top:2px}
  #m-cl .cl-mf{display:flex;gap:10px;justify-content:space-between;align-items:center;flex-wrap:wrap;padding:14px 24px;border-top:1px solid #EFE8F1;background:#FCFAFD;flex-shrink:0}
  #m-cl .cl-mf .cl-mf-r{display:flex;gap:10px;flex-wrap:wrap}
  #m-cl .cl-mf .btn{text-decoration:none}
  @media (max-width:900px){
    #m-cl .cl-grid{grid-template-columns:1fr}
    #m-cl .cl-kpis{grid-template-columns:repeat(3,minmax(0,1fr))}
  }
  @media (max-width:700px){
    #m-cl{padding:0;align-items:flex-end}
    #m-cl .cl-m{width:100%;max-height:94vh;border-radius:18px 18px 0 0}
    #m-cl .cl-kpis{grid-template-columns:repeat(2,minmax(0,1fr))}
    #m-cl .cl-mf .btn{flex:1;justify-content:center}
    #m-cl .cl-mf .cl-mf-r{width:100%}
    #m-cl .cl-hist{overflow:auto}
    #m-cl .cl-hist table{min-width:560px}
  }
  .cl-page .wl-signup{padding:16px 20px;margin:18px 0 18px}
  .cl-page .wlset{display:flex;background:#F3EEF4;border-radius:12px;padding:4px;gap:4px;flex-wrap:wrap}
  .cl-page .wlo{border:0;background:none;font:inherit;font-size:13px;font-weight:600;color:var(--muted);padding:8px 14px;border-radius:9px;cursor:pointer}
  .cl-page .wlo.on{background:#fff;color:#1A1A1A;box-shadow:0 1px 4px rgba(0,0,0,.08)}
  .cl-page .wlmv{display:inline-flex;align-items:center;gap:4px;color:#3E007C;font-weight:600;font-size:12.5px;text-decoration:none;white-space:nowrap}
  .cl-page .wlmv:hover{text-decoration:underline}
  .cl-page .wlc{width:44px}
  .cl-page .wlc input{width:17px;height:17px;accent-color:#1A1A1A;cursor:pointer}
  .cl-page .wl-search-card{background:#FBF3FD;margin-bottom:14px}
  .cl-page .wl-search-card .fl{display:block;font-size:12px;color:var(--muted);margin-bottom:6px;font-weight:600}
  .cl-page .wl-empty-card{padding:60px 20px;text-align:center;margin-bottom:24px}
  .cl-page .wl-empty-card .ic{margin:0 auto;width:56px;height:56px;border-radius:50%}
  .cl-page .wl-empty-card h3{margin-top:14px;font-size:18px;font-weight:800}
  .cl-page .wl-help{margin-top:10px;margin-bottom:24px;font-size:12.5px;color:var(--muted);line-height:1.5}
  .cl-page #waitlistNotifyBtn:disabled{opacity:.45;cursor:not-allowed}
  .cl-page .tog{width:38px;height:22px;border-radius:12px;background:#D9D2DD;position:relative;flex-shrink:0;border:0;padding:0;cursor:pointer}
  .cl-page .tog:after{content:'';position:absolute;width:16px;height:16px;border-radius:50%;background:#fff;top:3px;left:3px;transition:left .15s}
  .cl-page .tog.on{background:var(--pri)}.cl-page .tog.on:after{left:19px}
  .cl-page .gwtyped{display:block;font-size:11.5px;color:var(--muted);margin-top:2px}
  .cl-page .gwno{font-size:12px;color:var(--muted);white-space:nowrap;margin-top:4px}
  .cl-page .btn[disabled]{opacity:.45;cursor:default}
  @media (max-width:900px){
    .cl-page .grid.stats{grid-template-columns:repeat(2,minmax(0,1fr))}
    .cl-page .cl-kpis{grid-template-columns:repeat(2,minmax(0,1fr))}
    .cl-page .cl-sel{width:100%}
    .cl-page .wlset{width:100%;flex-wrap:nowrap}
    .cl-page .wlo{flex:1;padding:8px 6px;font-size:12.5px}
    .cl-page .card:has(table){overflow:visible!important;background:none!important;border:0!important}
    .cl-page #clientsTabPanel table,.cl-page #clientsTabPanel tbody,.cl-page #clientsTabPanel tr,.cl-page #clientsTabPanel td,
    .cl-page #waitlistTabPanel table,.cl-page #waitlistTabPanel tbody,.cl-page #waitlistTabPanel tr,.cl-page #waitlistTabPanel td,
    .cl-page #guestWaitlistTabPanel table,.cl-page #guestWaitlistTabPanel tbody,.cl-page #guestWaitlistTabPanel tr,.cl-page #guestWaitlistTabPanel td{
      display:block;width:100%
    }
    .cl-page tr:has(th){display:none}
    .cl-page tbody tr.client-row,.cl-page tbody tr.wait-row{
      position:relative;background:#fff;border:1px solid var(--line);border-radius:14px;padding:12px 14px;margin-bottom:10px
    }
    .cl-page tbody tr.wait-row td.wlc{position:absolute;top:12px;left:12px;width:auto;padding:0!important;justify-content:flex-start}
    .cl-page tbody tr.wait-row td.wlc::before{display:none}
    .cl-page tbody tr.wait-row td:first-child + td{padding-left:28px!important}
    .cl-page td{border:0!important;padding:5px 0!important;display:flex!important;justify-content:space-between;align-items:center;gap:12px;text-align:right}
    .cl-page td::before{content:attr(data-label);font-size:10.5px;font-weight:700;letter-spacing:.7px;color:var(--muted);text-align:left;flex-shrink:0}
    .cl-page td:first-child{padding:0 44px 8px 0!important;margin-bottom:4px;border-bottom:1px solid #F3EEF4!important;justify-content:flex-start;text-align:left}
    .cl-page td:first-child::before{display:none}
    .cl-page td[data-label=""]{position:absolute;top:10px;right:10px;width:auto;padding:0!important}
    .cl-page td[data-label=""]::before{display:none}
  }
  @media (max-width:560px){
    .cl-page .grid.stats{grid-template-columns:1fr 1fr}
  }
</style>
@endsection

@section('content')
<div class="cl-page">
  <div class="head">
        <div>
      <h1>
        Clients
        <a class="help-q" href="https://help.inkjin.com/en/articles/17201129-dashboard-clients-clients" target="_blank" rel="noopener" title="Help with this page" aria-label="Help with this page">
          <span class="ms">help</span>
        </a>
      </h1>
      <div class="sub" id="clientsCountLabel">{{ $clientsTotal }} {{ Str::plural('client', $clientsTotal) }}</div>
    </div>
    <button type="button" class="btn ghost" id="exportClientsBtn">
      <span class="ms">download</span>Export
    </button>
  </div>

  <div class="tabs">
    <div class="on" data-clients-tab="clients" role="button" tabindex="0">
      Clients<span class="n{{ $clientsTotal === 0 ? ' zero' : '' }}">{{ $clientsTotal }}</span>
    </div>
    <div data-clients-tab="waitlist" role="button" tabindex="0">
      Waitlist<span class="n{{ $waitlistTotal === 0 ? ' zero' : '' }}" id="waitlistTabCount">{{ $waitlistTotal }}</span>
    </div>
    <div data-clients-tab="guest-waitlist" role="button" tabindex="0">
      Guest waitlist<span class="n{{ $guestWaitlistTotal === 0 ? ' zero' : '' }}" id="guestWaitlistTabCount">{{ $guestWaitlistTotal }}</span>
        </div>
      </div>

  <div class="grid stats" id="clientsStatsGrid">
    <div class="card pad stat">
      <div class="ic"><span class="ms">group</span></div>
      <div class="v">{{ $stats['total'] ?? 0 }}</div>
      <div class="l">Total clients</div>
    </div>
    <div class="card pad stat">
      <div class="ic"><span class="ms">person_check</span></div>
      <div class="v">{{ $stats['active'] ?? 0 }}</div>
      <div class="l">Active clients</div>
    </div>
    <div class="card pad stat">
      <div class="ic"><span class="ms">person_add</span></div>
      <div class="v">{{ $stats['new_this_month'] ?? 0 }}</div>
      <div class="l">New this month</div>
    </div>
    <div class="card pad stat">
      <div class="ic"><span class="ms">autorenew</span></div>
      <div class="v">{{ $stats['returning_rate'] ?? 0 }}%</div>
      <div class="l">Returning rate</div>
          </div>
        </div>

  <div id="clientsTabPanel">
    <div class="cl-tools">
      <div class="cl-search">
        <label class="in" for="searchInput">
          <span class="ms">search</span>
          <input type="search" id="searchInput" placeholder="Search by name, email or phone" autocomplete="off" aria-label="Search clients">
        </label>
      </div>
      <div class="cl-sel">
        <select class="in" id="filterSelect" aria-label="Filter clients">
          <option value="all">All clients</option>
          <option value="new">New clients</option>
          <option value="returning">Returning clients</option>
          <option value="upcoming">With an upcoming booking</option>
        </select>
          </div>
      <div class="cl-sel">
        <select class="in" id="sortSelect" aria-label="Sort clients">
          <option value="recent">Most recent</option>
          <option value="name">Name A–Z</option>
          <option value="bookings">Most bookings</option>
          <option value="spent">Most spent</option>
        </select>
      </div>
    </div>

    <div class="card" style="overflow:hidden;margin-bottom:24px">
      <table>
        <thead>
          <tr>
            <th>CLIENT</th>
            <th>EMAIL</th>
            <th>PHONE</th>
            <th>BOOKINGS</th>
            <th>TOTAL SPENT</th>
            <th>STATUS</th>
            <th></th>
          </tr>
        </thead>
        <tbody id="clientsBody"></tbody>
      </table>
      <div id="clientsEmpty" class="cl-empty" hidden>No clients match your search yet.</div>
    </div>
  </div>

  <div id="waitlistTabPanel" hidden>
    @php
      $wlMode = $waitlistSignupMode ?? 'name_email';
      $wlIsRequest = $wlMode === 'request_form';
    @endphp

    <div class="card wl-signup">
      <div class="row" style="justify-content:space-between;gap:14px;flex-wrap:wrap">
        <div style="flex:1;min-width:240px">
          <b style="font-size:14.5px">Waitlist sign-up</b>
          <div class="faint" style="font-size:12.5px;margin-top:2px">What clients fill in to join your waitlist when your books are closed or a design is sold out.</div>
          </div>
        <div class="wlset" role="radiogroup" aria-label="Waitlist sign-up">
          <button type="button" class="wlo{{ $wlIsRequest ? '' : ' on' }}" role="radio" aria-checked="{{ $wlIsRequest ? 'false' : 'true' }}" data-wl-mode="name_email">Name and email only</button>
          <button type="button" class="wlo{{ $wlIsRequest ? ' on' : '' }}" role="radio" aria-checked="{{ $wlIsRequest ? 'true' : 'false' }}" data-wl-mode="request_form">Require request form</button>
        </div>
      </div>
      <div class="row" style="gap:14px;margin-top:10px;flex-wrap:wrap;justify-content:space-between">
        <div class="help" id="wlSignupHelp" style="margin:0;flex:1;min-width:240px">
          @if ($wlIsRequest)
            Clients fill in your custom request form to join. You see their request here, and it moves to Bookings &gt; Requests when you notify them.
          @else
            Clients only give their name and email to join. You can ask for details after you notify them.
          @endif
        </div>
        <a href="{{ route('artist.forms.index') }}" id="wlFormLink" class="wlmv" @if (! $wlIsRequest) hidden @endif>Edit form in Client Flow<span class="ms" style="font-size:15px">arrow_forward</span></a>
      </div>
    </div>

    <div id="waitlistFullPanel">
      <div id="waitlistNotifyBar" class="row" style="justify-content:space-between;gap:14px;flex-wrap:wrap;margin-bottom:14px" @if (! ($booksOpen ?? false)) hidden @endif>
        <div class="muted" style="margin:0;flex:1;min-width:200px">Your books are open. Notify waitlist subscribers that they can book now.</div>
        <button type="button" class="btn" id="waitlistNotifyBtn" @if (! ($showWaitlistNotifyButton ?? false)) disabled @endif>
          <span class="ms">mail</span><span id="waitlistNotifyBtnLabel">Send email to all ({{ (int) ($waitlistPendingCount ?? 0) }})</span>
      </button>
      </div>
      <div class="card pad wl-search-card">
        <span class="fl">Search</span>
        <input class="in" type="search" id="waitlistSearchInput" placeholder="Search by name or email..." autocomplete="off" aria-label="Search waitlist" style="width:100%">
      </div>

      <div class="card" id="waitlistTableCard" style="overflow:hidden">
        <table>
          <thead>
            <tr>
              <th class="wlc"><input type="checkbox" id="waitlistSelectAll" aria-label="Select all"></th>
              <th>NAME</th>
              <th>EMAIL</th>
              <th>JOINED</th>
              <th>REQUEST</th>
              <th>STATUS</th>
              <th></th>
            </tr>
          </thead>
          <tbody id="waitlistBody"></tbody>
        </table>
      </div>
      <div class="help wl-help" id="waitlistHelpText">Send email to all only emails people who are still waiting. Use Send again on a row to email someone who was already notified. When you notify someone who sent a request, it moves to Bookings &gt; Requests so you can send a quote.</div>
    </div>

    <div id="waitlistEmpty" class="card wl-empty-card" hidden>
      <div class="ic"><span class="ms" style="font-size:26px">notifications</span></div>
      <h3>No one on your waitlist yet</h3>
      <div class="muted" style="max-width:440px;margin:6px auto 0;line-height:1.5">When your books are closed or a design is sold out, clients can join the waitlist. You'll see them here and can notify them when a slot opens.</div>
      <div class="row" style="justify-content:center;margin-top:18px;gap:10px">
        <a href="{{ route('availability.index') }}" class="btn ghost">Change booking status</a>
      </div>
          </div>
        </div>

  <div id="guestWaitlistTabPanel" hidden>
    @php
      $gwAutoOn = (bool) ($guestWaitlistAutoNotify ?? true);
    @endphp
    <div class="card wl-signup" id="guestWaitlistAutoCard">
      <div class="row" style="justify-content:space-between;gap:14px;flex-wrap:nowrap">
        <div style="flex:1;min-width:0">
          <b style="font-size:14.5px">Automatic notifications</b>
          <div class="faint" style="font-size:12.5px;margin-top:2px">Send the client a notification automatically when a guest spot becomes available for their city.</div>
        </div>
        <button type="button" class="tog{{ $gwAutoOn ? ' on' : '' }}" id="guestWaitlistAutoToggle" role="switch" aria-checked="{{ $gwAutoOn ? 'true' : 'false' }}" aria-label="Send notifications automatically"></button>
      </div>
      <div class="row" style="gap:14px;margin-top:10px;flex-wrap:wrap;justify-content:space-between">
        <div class="help" id="guestWaitlistAutoHelp" style="margin:0;flex:1;min-width:240px">
          @if ($gwAutoOn)
            When you set a guest spot to Available, everyone waiting for that city gets an email with a link to the guest spot page.
          @else
            No emails go out by themselves. Use Send email to tell people about a guest spot in their city.
          @endif
        </div>
      </div>
    </div>

    <div id="guestWaitlistFullPanel">
      <div class="row" style="justify-content:space-between;gap:14px;flex-wrap:wrap;margin-bottom:14px">
        <div class="muted" id="guestWaitlistIntro">Clients who asked to hear when you guest in their city. They sign up from your page and your guest spot pages.</div>
      </div>
      <div class="card pad wl-search-card">
        <span class="fl">Search</span>
        <input class="in" type="search" id="guestWaitlistSearchInput" placeholder="Search by name, email or city..." autocomplete="off" aria-label="Search guest waitlist" style="width:100%">
      </div>

      <div class="card" id="guestWaitlistTableCard" style="overflow:hidden">
        <table>
          <thead>
            <tr>
              <th>NAME</th>
              <th>EMAIL</th>
              <th>CITY</th>
              <th>STATUS</th>
              <th></th>
            </tr>
          </thead>
          <tbody id="guestWaitlistBody"></tbody>
        </table>
      </div>
      <div class="help wl-help" id="guestWaitlistHelpText">Send email works when the guest spot they joined for is Available. Planned spots show “No open guest spot yet”.</div>
    </div>

    <div id="guestWaitlistEmpty" class="card wl-empty-card" hidden>
      <div class="ic"><span class="ms" style="font-size:26px">flight_takeoff</span></div>
      <h3>No one on your guest waitlist yet</h3>
      <div class="muted" style="max-width:460px;margin:6px auto 0;line-height:1.5">Clients join from your page with “Notify me for guest spots in…”, and from your guest spot pages. You’ll see them here.</div>
      <div class="row" style="justify-content:center;margin-top:18px;gap:10px">
        <a href="{{ route('guest-spots.index') }}" class="btn ghost">Go to guest spots</a>
      </div>
    </div>
  </div>
</div>

@include('components.waitlist-notify-modal')

<div id="m-cl" hidden role="dialog" aria-modal="true" aria-labelledby="cl-m-heading">
  <div class="cl-m">
    <div class="cl-mh">
      <h2 id="cl-m-heading">Client details</h2>
      <button type="button" class="cl-mx" id="cl-m-x" aria-label="Close"><span class="ms">close</span></button>
    </div>
    <div class="cl-mb">
      <div class="cl-grid">
        <div class="cl-col-l">
          <div class="cl-profile">
            <div class="ini" id="cl-m-ini">?</div>
            <div style="min-width:0">
              <div class="nm" id="cl-m-title">Client</div>
              <div class="cl-sub">
                <span class="pill g" id="cl-m-status">Active</span>
                <span id="cl-m-since">Client since —</span>
              </div>
            </div>
          </div>

          <div class="cl-card cl-contact">
            <h4>Contact</h4>
            <div class="item">
              <span class="ms">mail</span>
              <span class="val" id="cl-m-email">—</span>
              <button type="button" class="cl-copy" id="cl-m-copy-email" title="Copy email"><span class="ms">content_copy</span></button>
            </div>
            <div class="item">
              <span class="ms">phone</span>
              <span class="val" id="cl-m-phone">—</span>
              <button type="button" class="cl-copy" id="cl-m-copy-phone" title="Copy phone"><span class="ms">content_copy</span></button>
            </div>
            <div class="item">
              <span class="ms">photo_camera</span>
              <span class="val" id="cl-m-ig">No Instagram added</span>
    </div>
    </div>

          <div class="cl-card">
            <h4>About this client</h4>
            <div class="cl-about">
              <div class="row"><span class="k">How they found you</span><span class="v" id="cl-m-found">Not recorded</span></div>
              <div class="row"><span class="k">Next booking</span><span class="v strong" id="cl-m-next">None yet</span></div>
              <div class="row"><span class="k">Last booking</span><span class="v" id="cl-m-last">None yet</span></div>
              <div class="row"><span class="k">Deposits paid</span><span class="v" id="cl-m-deposits">€0.00</span></div>
              <div class="row"><span class="k">Outstanding</span><span class="v due" id="cl-m-outstanding">€0.00</span></div>
            </div>
      </div>

          <div class="cl-card">
            <h4>Private notes</h4>
            <textarea class="in" id="cl-m-note" rows="3" placeholder="Add private notes about this client..."></textarea>
            <div class="cl-note-ft">
              <span class="hint">Only you can see these</span>
              <button type="button" class="btn sm" id="cl-m-save"><span class="ms">save</span>Save note</button>
            </div>
          </div>
        </div>

        <div class="cl-col-r">
          <div class="cl-kpis">
            <div class="cl-kpi"><b id="cl-m-bookings">0</b><span>Total bookings</span></div>
            <div class="cl-kpi"><b id="cl-m-completed" style="color:#00A650">0</b><span>Completed</span></div>
            <div class="cl-kpi"><b id="cl-m-cancelled" style="color:#C62828">0</b><span>Cancelled</span></div>
            <div class="cl-kpi"><b id="cl-m-noshows">0</b><span>No-shows</span></div>
            <div class="cl-kpi"><b id="cl-m-spent">€0</b><span>Total paid</span></div>
      </div>

          <div class="cl-hist">
            <div class="cl-sec-h">Booking history</div>
            <table>
            <thead>
                <tr>
                  <th>DATE</th>
                  <th>BOOKING</th>
                  <th>STATUS</th>
                  <th>PAID</th>
                  <th>DUE</th>
              </tr>
            </thead>
              <tbody id="cl-m-bookings-body"></tbody>
          </table>
        </div>

          <div class="cl-reqs">
            <div class="cl-sec-h">Requests</div>
            <div id="cl-m-requests-body"></div>
          </div>
        </div>
      </div>
    </div>
    <div class="cl-mf">
      <button type="button" class="btn ghost" id="cl-m-export"><span class="ms">download</span>Export this client</button>
      <div class="cl-mf-r">
        <a href="#" class="btn ghost" id="cl-m-bookings-link"><span class="ms">calendar_month</span>View bookings</a>
        <a href="#" class="btn ghost" id="cl-m-requests-link"><span class="ms">edit_note</span>View requests</a>
        <a href="#" class="btn" id="cl-m-message-link"><span class="ms">chat</span>Send message</a>
      </div>
  </div>
  </div>
</div>

<div id="saveToast" class="bpw-toast" role="status" aria-live="polite">
  <span class="ms">check_circle</span>
  <span id="saveToastMsg">Note saved</span>
</div>
@endsection

@section('scripts')
<script>
  window.inkjinArtistClients = @json($clients);
  window.inkjinArtistWaitlist = @json($waitlist);
  window.inkjinArtistGuestWaitlist = @json($guestWaitlist ?? []);
  window.inkjinCurrencySymbol = @json($currencySymbol);
  window.inkjinWaitlistNotifyUrl = @json(route('artist.clients.waitlist.notify'));
  window.inkjinWaitlistSignupUrl = @json(route('artist.clients.waitlist.signup-mode'));
  window.inkjinGuestWaitlistAutoNotifyUrl = @json(route('artist.clients.guest-waitlist.auto-notify'));
  window.inkjinGuestWaitlistNotifyUrl = @json(route('artist.clients.guest-waitlist.notify'));
  window.inkjinWaitlistSignupMode = @json($waitlistSignupMode ?? 'name_email');
  window.inkjinGuestWaitlistAutoNotify = @json((bool) ($guestWaitlistAutoNotify ?? true));
  window.inkjinBooksOpen = @json((bool) ($booksOpen ?? false));
  window.inkjinArtistId = @json((int) Auth::id());
  window.inkjinRequestsUrl = @json(route('artist.requests.index'));
</script>
<script src="{{ asset('js/waitlist-notify.js') }}?v=6"></script>
<script src="{{ asset('js/artist-clients.js') }}?v=19"></script>
@endsection
