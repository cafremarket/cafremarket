@extends('theme::layouts.main')

@section('content')
  <!-- HEADER SECTION -->
  @include('theme::headers.product_page', ['product' => $item])

  <!-- CONTENT SECTION -->
  @if ($item->deleted_at == null)
    @include('theme::contents.product_page')
  @else
    <section>
      <div class="container">
        <p class="lead text-center my-5">
          {!! trans('theme.item_not_available') !!}<br /><br />
          <a href="{{ url('/') }}" class="btn btn-primary btn-sm">@lang('theme.button.shop_from_other_categories')</a>
        </p>
      </div> <!-- /.container -->
    </section>
  @endif

  <!-- RELATED ITEMS -->
  <section class="sf-pdp__band">
    <div class="container">
      <header class="sf-pdp__band-head">
        <h2>{!! trans('theme.related_items') !!}</h2>
      </header>
      <div class="feature-items-inner">
        @include('theme::partials._product_horizontal', ['products' => $related])
      </div>
    </div>
  </section>

  <!-- BROWSING ITEMS -->
  @include('theme::sections.recent_views')

  <!-- MODALS -->
  @include('theme::modals.shopReviews', ['shop' => $item->shop])

  @if (Auth::guard('customer')->check())
    @include('theme::modals.contact_seller', ['shop' => $item->shop, 'item' => $item])
  @endif
@endsection

@section('scripts')
  @if (is_incevio_package_loaded('liveChat') && is_chat_enabled($item->shop))
    @if (isset($item->shop->fb_page_id))
      @include('liveChat::facebook.script', ['fb_page_id' => $item->shop->fb_page_id]);
    @else
      @include('liveChat::livechat', ['shop' => $item->shop, 'agent' => $item->shop->owner, 'agent_status' => trans('theme.online'), 'product' => $item])
    @endif
  @endif

  @include('theme::modals.ship_to')
  @include('theme::scripts.product_page')
  @include('scripts.flash_deal')

  @if (is_incevio_package_loaded('auction') && $item->auctionable)
    @include('auction::frontend.script')
  @endif
@endsection
