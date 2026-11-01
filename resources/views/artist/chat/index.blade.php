@extends('layouts.new-artist-dashboard-layout')

@section('title', 'Inbox')

@php
  $activeNav = 'inbox';
@endphp

@section('content')
  <x-chat-inbox
    skin="bookpay"
    :role="$role"
    :stream-configured="$streamConfigured"
    :has-open-booking="$hasOpenBooking"
    :has-conversations="$hasConversations"
  />
@endsection
