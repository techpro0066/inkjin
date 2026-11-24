@extends('layouts.studio-dashboard-layout')

@section('title', 'Account · Plans')

@push('styles')
@include('shared.account.plan-card-styles')
@endpush

@section('content')
<div class="head">
  <div>
    <h1>Account</h1>
    <div class="sub">Your account plan and its terms.</div>
  </div>
</div>

@include('studio.account._tabs', ['accountTab' => 'plans'])
@include('shared.account.plan-card', ['isFounding' => $isFounding])
@endsection
