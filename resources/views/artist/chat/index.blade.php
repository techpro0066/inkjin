@extends('layouts.artist_dashboard_layout')

@section('title', 'Inbox')

@section('content')
  <x-chat-inbox
    :role="$role"
    :stream-configured="$streamConfigured"
    :has-open-booking="$hasOpenBooking"
    :has-conversations="$hasConversations"
  />
@endsection
