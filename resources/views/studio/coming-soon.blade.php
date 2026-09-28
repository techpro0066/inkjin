@extends('layouts.new-auth')

@section('title', 'Studio dashboard coming soon | Bookpay by Inkjin')
@section('meta_description', 'Your Bookpay studio dashboard is on the way.')
@section('robots', 'noindex, follow')

@section('help')
@endsection

@section('content')
  <div class="icon"><span class="ms">hourglass_top</span></div>
  <h1>We're getting your dashboard ready</h1>
  <p class="sub">
    Studio tools are on the way. We'll email <b>{{ auth()->user()->email }}</b> as soon as your Bookpay studio dashboard is ready to use.
  </p>

  <form method="POST" action="{{ route('logout') }}">
    @csrf
    <button class="btn" type="submit">Sign out<span class="ms">logout</span></button>
  </form>

  <a class="back" href="mailto:artists@inkjin.com"><span class="ms">mail</span>Contact support</a>
@endsection
