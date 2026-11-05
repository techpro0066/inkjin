@extends('layouts.studio-dashboard-layout')

@section('title', 'Money · Earnings')

@php
  $earningsRows = $earningsRows ?? collect();
  $summary = $earningsSummary ?? [
    'next_payout' => 0,
    'next_payout_meta' => 'Nothing queued yet',
    'held' => 0,
    'earned_this_month' => 0,
    'month_delta_label' => 'No earnings yet this month',
  ];
@endphp

@push('styles')
<style>
  .money-page .lbl{font-size:11px;letter-spacing:.8px;font-weight:700;color:var(--muted);text-transform:uppercase}
  .money-page .card.pad{padding:20px 22px}
  .money-page .pill{gap:5px}
  .money-page .pill:before{content:'';width:5px;height:5px;border-radius:50%;background:currentColor}
  .money-page .pill.nd:before{display:none}
  .money-page .pill.b{background:#E8F0FF;color:#1E5BD8}
  .money-page .pill.g{background:#E6F8EE;color:#00A650}
  .money-page .pill.a{background:#FFF3DE;color:#B86E00}
  .money-page .help{max-width:920px}
  .money-page .empty{padding:48px 22px;text-align:center;color:var(--muted)}
  @media (max-width:900px){
    .money-page .card:has(table){overflow:visible!important;background:none!important;border:0!important}
    .money-page table,.money-page tbody,.money-page tr,.money-page td{display:block;width:100%}
    .money-page tr:has(th){display:none}
    .money-page tbody tr{background:#fff;border:1px solid var(--line);border-radius:14px;padding:12px 14px;margin-bottom:10px}
    .money-page td{border:0!important;padding:5px 0!important;display:flex!important;justify-content:space-between;align-items:center;gap:12px;text-align:right}
    .money-page td::before{content:attr(data-label);font-size:10.5px;font-weight:700;letter-spacing:.7px;color:var(--muted);text-align:left;flex-shrink:0}
    .money-page td:first-child{padding:0 0 8px!important;margin-bottom:4px;border-bottom:1px solid #F3EEF4!important;justify-content:flex-start;text-align:left}
    .money-page td:first-child::before{display:none}
    .money-page .grid[style*="repeat(3"]{grid-template-columns:1fr!important}
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
      <div style="font-size:26px;font-weight:800;margin-top:6px">€{{ number_format((float) $summary['next_payout'], ((float) $summary['next_payout']) == floor((float) $summary['next_payout']) ? 0 : 2) }}</div>
      <div class="faint" style="font-size:12px">{{ $summary['next_payout_meta'] }}</div>
    </div>
    <div class="card pad">
      <div class="lbl">Held (cancellation window)</div>
      <div style="font-size:26px;font-weight:800;margin-top:6px">€{{ number_format((float) $summary['held'], ((float) $summary['held']) == floor((float) $summary['held']) ? 0 : 2) }}</div>
      <div class="faint" style="font-size:12px">Sent when each window ends</div>
    </div>
    <div class="card pad">
      <div class="lbl">Earned this month</div>
      <div style="font-size:26px;font-weight:800;margin-top:6px">€{{ number_format((float) $summary['earned_this_month'], ((float) $summary['earned_this_month']) == floor((float) $summary['earned_this_month']) ? 0 : 2) }}</div>
      <div class="faint" style="font-size:12px">{{ $summary['month_delta_label'] }}</div>
    </div>
  </div>

  <div class="card" style="overflow:hidden;margin-top:16px">
    <div class="ch">
      <h3>Your share per booking</h3>
      @if ($earningsRows->isNotEmpty())
        <a class="btn ghost sm" href="{{ route('studio.money.earnings.export') }}"><span class="ms">download</span>Export CSV</a>
      @else
        <span class="btn ghost sm" style="opacity:.45;pointer-events:none"><span class="ms">download</span>Export CSV</span>
      @endif
    </div>
    @if ($earningsRows->isEmpty())
      <div class="empty">
        <div style="font-weight:700;color:var(--ink);margin-bottom:4px">No split earnings yet</div>
        <div>When clients pay for bookings with a studio revenue split, your share shows up here.</div>
      </div>
    @else
      <table>
        <thead>
          <tr>
            <th>Date</th>
            <th>Booking</th>
            <th>Artist</th>
            <th>Paid by client</th>
            <th>Studio share</th>
            <th>Fee</th>
            <th>Studio gets</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($earningsRows as $row)
            <tr>
              <td data-label="Date">{{ $row['date'] }}</td>
              <td data-label="Booking"><b style="font-weight:600">{{ $row['booking_label'] }}</b></td>
              <td data-label="Artist">{{ $row['artist'] }}</td>
              <td data-label="Paid by client">€{{ number_format($row['client_paid'], 2) }}</td>
              <td data-label="Studio share">
                €{{ number_format($row['studio_share'], 2) }}
                <div class="faint" style="font-size:11.5px">{{ $row['studio_percent'] }}% of €{{ number_format($row['client_paid'], 2) }}</div>
              </td>
              <td data-label="Fee" class="muted">−€{{ number_format($row['studio_fee'], 2) }}</td>
              <td data-label="Studio gets"><b>€{{ number_format($row['studio_gets'], 2) }}</b></td>
              <td data-label="Status"><span class="pill {{ $row['status_pill'] }}">{{ $row['status_label'] }}</span></td>
            </tr>
          @endforeach
        </tbody>
      </table>
    @endif
  </div>
  <div class="help" style="margin-top:12px">
    Artists on direct payment don't appear here. Each booking keeps the split it was made with. Fee is your part of the Stripe fee (1.5% + €0.25 on EU cards) plus your part of any artist-paid booking fee, shared by the same split %. When the client pays the booking fee, that goes to Bookpay and isn't taken from your share.
  </div>
</div>
@endsection
