@extends('theme::layouts.main')

@section('content')
  @include('theme::headers.dispute_page')

  @include('theme::contents.dispute_page')

  @includeWhen(! $order->dispute, 'theme::modals.dispute')

  @if ($order->dispute && $order->dispute->canReply())
    @include('theme::modals.dispute_response')
  @endif
@endsection
