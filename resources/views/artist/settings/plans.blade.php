@extends('layouts.new-artist-dashboard-layout')

@section('title', 'Account')

@section('styles')
<style>
  .account-plans { max-width: 1440px; margin: 0 auto; padding: 28px 32px 48px; }
  .account-plans .head { margin-bottom: 20px; }
  .account-plans h1 { margin: 0; font-size: 26px; font-weight: 800; }
  @media (max-width: 700px) {
    .account-plans { padding: 22px 16px 36px; }
  }
</style>
@include('shared.account.plan-card-styles')
@endsection

@section('content')
<div class="account-plans">
  <header class="head">
    <div>
      <h1>Account</h1>
      <div class="sub">Your account plan and its terms.</div>
    </div>
  </header>

  @include('artist.partials.profile-settings-tabs', ['activeProfileTab' => 'plans'])

  @include('shared.account.plan-card', ['isFounding' => $isFounding])
</div>
@endsection
