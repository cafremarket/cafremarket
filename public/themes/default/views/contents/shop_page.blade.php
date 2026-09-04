@php
  $storeRoute = \Request::route()->getName();
  $isStoreHome = $storeRoute === 'show.store';
  $isStoreProducts = $storeRoute === 'shop.products';
  $isStoreReviews = $storeRoute === 'shop.reviews';
  $storeRating = $shop->feedbacks->count() ? $shop->feedbacks->avg('rating') : 0;
@endphp

<div class="sf-store">
  <div class="sf-store__hero">
    <img class="sf-store__cover lazy" src="{{ get_cover_img_src($shop, 'shop', 'cover_thumb') }}" data-src="{{ get_cover_img_src($shop, 'shop') }}" alt="{{ $shop->name }}">
    <div class="sf-store__hero-shade"></div>
  </div>

  <div class="container sf-store__shell">
    <section class="sf-store__identity">
      <div class="sf-store__logo-wrap">
        @include('theme::partials._shop_logo_frame', ['shop' => $shop, 'frameSize' => 'lg', 'class' => 'sf-store__logo', 'thumbSize' => 'tiny', 'fullSize' => 'full'])
      </div>

      <div class="sf-store__identity-main">
        <div class="sf-store__title-row">
          <h1 class="sf-store__name">{!! $shop->getQualifiedName() !!}</h1>
          {!! $shop->reward_badge !!}
        </div>

        @if ($shop->feedbacks->count())
          <div class="sf-store__rating">
            @include('theme::layouts.ratings', ['ratings' => $storeRating, 'count' => $shop->feedbacks->count(), 'shop' => true])
          </div>
        @endif

        @if ($shop->description)
          <div class="sf-store__about show-hide-content less">
            {!! clean_rich_html($shop->description) !!}
          </div>
          <a href="javascript:void(0)" class="sf-store__more show-hide-content-btn">
            {{ trans('theme.show_more') }} <i class="fa fa-angle-down"></i>
          </a>
        @endif

        <div class="sf-store__actions">
          <a href="javascript:void(0);" class="sf-store__btn sf-store__btn--primary sf-open-livechat">
            <i class="fas fa-comment"></i> @lang('theme.button.chat_now')
          </a>
          <a href="javascript:void(0);" class="sf-store__btn sf-store__btn--ghost contact-seller-btn" data-toggle="modal" data-target="{{ Auth::guard('customer')->check() ? '#contactSellerModal' : '#loginModal' }}">
            <i class="far fa-envelope"></i> @lang('theme.button.contact_seller')
          </a>
          <a href="{{ route('shop.products', $shop->slug) }}" class="sf-store__btn sf-store__btn--ghost">
            <i class="far fa-cubes"></i> {{ trans('theme.products') }}
          </a>
        </div>
      </div>

      <aside class="sf-store__stats">
        <div class="sf-store__stat">
          <strong>{{ $shop->inventories_count }}</strong>
          <span>{{ trans('theme.active_listings') }}</span>
        </div>
        <div class="sf-store__stat">
          <strong>{{ $shop->total_item_sold }}</strong>
          <span>{{ trans('theme.items_sold') }}</span>
        </div>
        <div class="sf-store__meta">
          @if ($shop->address)
            <div><i class="fa fa-map-marker"></i> {!! $shop->address->toShortString() !!}</div>
          @endif
          <div><i class="fa fa-calendar"></i> {{ trans('theme.member_since') . ' ' . $shop->created_at->toFormattedDateString() }}</div>
        </div>
      </aside>
    </section>

    <nav class="sf-store__tabs" aria-label="{{ $shop->name }}">
      <a class="{{ $isStoreHome ? 'is-active' : '' }}" href="{{ route('show.store', $shop->slug) }}">{{ trans('theme.shop_home') }}</a>
      <a class="{{ $isStoreProducts ? 'is-active' : '' }}" href="{{ route('shop.products', $shop->slug) }}">{{ trans('theme.products') }}</a>
      @if ($shop->config->return_refund)
        <a data-toggle="tab" href="#return-policy-tab">{{ trans('theme.return_and_refund_policy') }}</a>
      @endif
      <a class="{{ $isStoreReviews ? 'is-active' : '' }}" href="{{ route('shop.reviews', $shop->slug) }}">{{ trans('theme.latest_reviews') }}</a>
    </nav>

    <div class="tab-content sf-store__body">
      <div id="overview-tab" class="tab-pane {{ $isStoreHome ? 'active' : '' }}">
        @include('theme::sections.slider_shop_page')

        @if (!empty($banners['group_1']))
          @include('theme::sections.banners', ['banners' => $banners['group_1']])
        @endif

        @if (isset($top_items) && count($top_items))
          <section class="sf-store__shelf">
            <header class="sf-store__shelf-head">
              <h2>{{ trans('theme.top_selling') }}</h2>
              <div class="sf-store__shelf-nav">
                <button type="button" class="left-arrow slider-arrow slick-arrow neckbands-left"><i class="fal fa-chevron-left"></i></button>
                <button type="button" class="right-arrow slider-arrow slick-arrow neckbands-right"><i class="fal fa-chevron-right"></i></button>
              </div>
            </header>
            <div class="neckbands-items">
              <div class="neckbands-items-inner">
                @include('theme::partials._product_horizontal', ['products' => $top_items, 'ratings' => 1])
              </div>
            </div>
          </section>
        @endif

        @include('theme::sections.deal_of_the_day')
        @include('theme::sections.recently_added')

        @if (!empty($banners['group_2']))
          <div class="sf-store__banners">
            @include('theme::sections.banners', ['banners' => $banners['group_2']])
          </div>
        @endif

        @if (isset($deals_under) && count($deals_under))
          <section class="sf-store__shelf">
            <header class="sf-store__shelf-head">
              <h2>{{ trans('theme.best_find_under', ['amount' => get_formated_currency(get_from_option_table('best_finds_under' . $shop->id))]) }}</h2>
              <div class="sf-store__shelf-nav">
                <button type="button" class="left-arrow slider-arrow slick-arrow best-deal-left"><i class="fal fa-chevron-left"></i></button>
                <button type="button" class="right-arrow slider-arrow slick-arrow best-deal-right"><i class="fal fa-chevron-right"></i></button>
              </div>
            </header>
            <div class="best-deals-items">
              <div class="best-deals-items-inner">
                @include('theme::partials._product_horizontal', ['products' => $deals_under, 'title' => 1, 'ratings' => 1, 'hover' => 1])
              </div>
            </div>
          </section>
        @endif
      </div>

      <div id="products-tab" class="tab-pane {{ $isStoreProducts ? 'active' : '' }}">
        @include('theme::contents.product_list', ['colum' => 3])
      </div>

      <div id="return-policy-tab" class="tab-pane">
        <article class="sf-store__policy html-content">
          {!! $shop->config->return_refund !!}
        </article>
      </div>

      <div id="reviews-tab" class="tab-pane {{ $isStoreReviews ? 'active' : '' }}">
        <div class="sf-store__reviews">
          @isset($reviews)
            @forelse($reviews as $review)
              <article class="sf-store__review">
                <header>
                  <strong>{{ $review->customer->nice_name ?? $review->customer->name }}</strong>
                  <span>
                    <b class="text-success">@lang('theme.verified_purchase')</b>
                    <span class="text-muted">{{ $review->created_at->diffForHumans() }}</span>
                  </span>
                </header>
                <p>{{ $review->comment }}</p>
                @include('theme::layouts.ratings', ['ratings' => $review->rating])
              </article>
            @empty
              <p class="sf-store__empty">@lang('theme.no_reviews')</p>
            @endforelse

            <div class="row d-flex justify-content-center pagenav-wrapper mt-4 mb-2">
              {{ $reviews->links('theme::layouts.pagination') }}
            </div>
          @endisset
        </div>
      </div>
    </div>
  </div>
</div>
