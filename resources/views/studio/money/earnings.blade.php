@extends('layouts.studio-dashboard-layout')

@section('title', 'Money · Earnings')

@push('styles')
<style>
  .money-page .lbl{font-size:11px;letter-spacing:.8px;font-weight:700;color:var(--muted);text-transform:uppercase}
  .money-page .card.pad{padding:20px 22px}
  .money-page .pill{gap:5px}
  .money-page .pill:before{content:'';width:5px;height:5px;border-radius:50%;background:currentColor}
  .money-page .pill.nd:before{display:none}
  .money-page .pill.b{background:#E8F0FF;color:#1E5BD8}
  .money-page .help{max-width:920px}
  @media (max-width:900px){
    .money-page .card:has(table){overflow:visible!important;background:none!important;border:0!important}
    .money-page table,.money-page tbody,.money-page tr,.money-page td{display:block;width:100%}
    .money-page tr:has(th){display:none}
    .money-page tbody tr{background:#fff;border:1px solid var(--line);border-radius:14px;padding:12px 14px;margin-bottom:10px}
    .money-page td{border:0!important;padding:5px 0!important;display:flex!important;justify-content:space-between;align-items:center;gap:12px;text-align:right}
    .money-page td::before{content:attr(data-label);font-size:10.5px;font-weight:700;letter-spacing:.7px;color:var(--muted);text-align:left;flex-shrink:0}
    .money-page td:first-child{padding:0 0 8px!important;margin-bottom:4px;border-bottom:1px solid #F3EEF4!important;justify-content:flex-start;text-align:left}
    .money-page td:first-child::before{display:none}
  }
</style>
@endpush

@section('content')
<div class="money-page">
  <div class="head">
    <div>
      <h1>
        Money
        <a class="help-q" href="https://help.inkjin.com/en/articles/17338627-studio-dashboard-money-earnings" target="_blank" rel="noopener" title="Help with this page" aria-label="Help with this page"><span class="ms">help</span></a>
      </h1>
      <div class="sub">Your studio's share of splits and where it goes.</div>
    </div>
  </div>

  @include('studio.money.partials.tabs')

  <div class="grid" style="grid-template-columns:repeat(3,1fr);gap:14px">
    <div class="card pad">
      <div class="lbl">Next payout</div>
      <div style="font-size:26px;font-weight:800;margin-top:6px">€640</div>
      <div class="faint" style="font-size:12px">Thu 01/10 · bank ••• 7730</div>
    </div>
    <div class="card pad">
      <div class="lbl">Held (cancellation window)</div>
      <div style="font-size:26px;font-weight:800;margin-top:6px">€420</div>
      <div class="faint" style="font-size:12px">Sent when each window ends</div>
    </div>
    <div class="card pad">
      <div class="lbl">Earned this month</div>
      <div style="font-size:26px;font-weight:800;margin-top:6px">€2,140</div>
      <div class="faint" style="font-size:12px">+12% from last month</div>
    </div>
  </div>

  <div class="card" style="overflow:hidden;margin-top:16px">
    <div class="ch">
      <h3>Your share per booking</h3>
      <span class="btn ghost sm"><span class="ms">download</span>Export CSV</span>
    </div>
    <table>
      <thead>
        <tr>
          <th>Date</th>
          <th>Booking</th>
          <th>Artist</th>
          <th>Paid by client</th>
          <th>Studio share</th>
          <th>Stripe fee</th>
          <th>Studio gets</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td data-label="Date">29/09/2026</td>
          <td data-label="Booking"><b style="font-weight:600">Japanese dragon · Louis H.</b></td>
          <td data-label="Artist">Julian Ink</td>
          <td data-label="Paid by client">€300.00</td>
          <td data-label="Studio share">€120.00<div class="faint" style="font-size:11.5px">40% of €300.00</div></td>
          <td data-label="Stripe fee" class="muted">−€1.90</td>
          <td data-label="Studio gets"><b>€118.10</b></td>
          <td data-label="Status"><span class="pill a">Held until 27/09</span></td>
        </tr>
        <tr>
          <td data-label="Date">18/09/2026</td>
          <td data-label="Booking"><b style="font-weight:600">Koi half sleeve · Eva M.</b></td>
          <td data-label="Artist">Julian Ink</td>
          <td data-label="Paid by client">€1,200.00</td>
          <td data-label="Studio share">€480.00<div class="faint" style="font-size:11.5px">40% of €1,200.00</div></td>
          <td data-label="Stripe fee" class="muted">−€7.30</td>
          <td data-label="Studio gets"><b>€472.70</b></td>
          <td data-label="Status"><span class="pill g">Paid 21/09</span></td>
        </tr>
        <tr>
          <td data-label="Date">15/09/2026</td>
          <td data-label="Booking"><b style="font-weight:600">Rose · Kostas T.</b></td>
          <td data-label="Artist">Nikos Pappas</td>
          <td data-label="Paid by client">€180.00</td>
          <td data-label="Studio share">€54.00<div class="faint" style="font-size:11.5px">30% of €180.00</div></td>
          <td data-label="Stripe fee" class="muted">−€0.89</td>
          <td data-label="Studio gets"><b>€53.11</b></td>
          <td data-label="Status"><span class="pill b">On its way</span></td>
        </tr>
        <tr>
          <td data-label="Date">10/09/2026</td>
          <td data-label="Booking"><b style="font-weight:600">Peony · Anna P.</b></td>
          <td data-label="Artist">Maria Kosta</td>
          <td data-label="Paid by client">€220.00</td>
          <td data-label="Studio share">€77.00<div class="faint" style="font-size:11.5px">35% of €220.00</div></td>
          <td data-label="Stripe fee" class="muted">−€1.24</td>
          <td data-label="Studio gets"><b>€75.76</b></td>
          <td data-label="Status"><span class="pill g">Paid 13/09</span></td>
        </tr>
      </tbody>
    </table>
  </div>
  <div class="help" style="margin-top:12px">
    Artists on direct payment don't appear here. Each booking keeps the split it was made with. Stripe charges 1.5% + €0.25 per payment (EU cards; other cards cost more). With a split, you and the artist each pay your own part of it, and the Stripe fee column shows yours. The €10 Service Booking Fee is paid by the client and goes to Bookpay, so it isn't in these amounts.
  </div>
</div>
@endsection
