@extends('theme::layouts.main')

@section('content')
  <!-- CONTENT SECTION -->
  @include('theme::contents.shop_page')

  <!-- MODALS -->
  {{-- @include('theme::modals.shopReviews') --}}

  @if (Auth::guard('customer')->check())
    @include('theme::modals.contact_seller', ['shop' => $shop])
  @endif
@endsection

@section('scripts')
  @include('liveChat::livechat', ['shop' => $shop, 'agent' => $shop->owner ?? optional($shop->config)->supportAgent, 'agent_status' => trans('theme.online')])
@endsection
