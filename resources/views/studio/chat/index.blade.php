@extends('layouts.studio-dashboard-layout')

@section('title', 'Inbox · Studio')

@section('content')
  <x-chat-inbox
    skin="bookpay"
    role="studio"
    :stream-configured="$streamConfigured"
    :has-open-booking="$hasOpenBooking"
    :has-conversations="$hasConversations"
  />
@endsection
