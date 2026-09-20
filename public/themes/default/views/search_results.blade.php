@extends('theme::layouts.main')

@section('content')
  <div class="sf-search-page">
    <section class="sf-search-hero">
      <div class="container">
        <ol class="breadcrumb nav-breadcrumb sf-search-breadcrumb">
          @include('theme::headers.lists.home')

          @if ($category && Request::has('insubgrp') && Request::get('insubgrp') != 'all')
            <li class="active">{{ $category->name }}</li>
          @elseif($category && Request::has('in'))
            <li>
              <a class="link-filter-opt" data-name="insubgrp" data-value="{{ $category->category->slug }}">
                {{ $category->category->name }}
              </a>
            </li>
            <li class="active">{{ $category->name }}</li>
          @endif
        </ol>

        <div class="sf-search-hero__inner">
          <div class="sf-search-hero__text">
            <h1>
              @if (Request::filled('q'))
                &ldquo;{{ Request::get('q') }}&rdquo;
              @else
                {{ trans('theme.all_categories') ?? 'All products' }}
              @endif
            </h1>
            @if (empty($require_location))
              <p>{{ trans('app.search_result_found', ['count' => $products->total()]) }}</p>
            @endif
          </div>

          <form action="{{ route('inCategoriesSearch') }}" method="GET" class="sf-search-hero__form">
            @foreach (['in', 'insubgrp', 'ingrp'] as $catParam)
              @if (Request::filled($catParam))
                <input type="hidden" name="{{ $catParam }}" value="{{ Request::get($catParam) }}">
              @endif
            @endforeach

            <label class="sf-search-hero__field">
              <i class="fal fa-search"></i>
              <input
                type="text"
                name="q"
                value="{{ Request::get('q') }}"
                placeholder="{{ trans('theme.search_keyword') ?? 'Search keyword' }}"
                required
              >
            </label>

            <button type="submit" class="sf-btn-primary sf-search-hero__submit">
              {{ trans('theme.button.search') ?? 'Search' }}
            </button>
          </form>
        </div>

        @if (empty($require_location))
          <button type="button" class="home-location-picker sf-search-hero__location js-open-address-setup" aria-label="{{ trans('theme.set_delivery_location') }}">
            <span class="home-location-picker__icon"><i class="fal fa-map-marker-alt"></i></span>
            <span class="home-location-picker__body">
              <span class="home-location-picker__label">{{ trans('theme.deliver_to') }}</span>
              @if (buyer_delivery_address_label())
                <span class="home-location-picker__value">{{ Str::limit(buyer_delivery_address_label(), 42) }}</span>
              @else
                <span class="home-location-picker__value is-empty">{{ trans('theme.set_delivery_location') }}</span>
              @endif
            </span>
            <span class="home-location-picker__action">{{ trans('theme.change') }}</span>
          </button>
        @endif
      </div>
    </section>

    <div class="container">
      @if (! empty($require_location))
        <div class="sf-empty-stores text-center py-5">
          <div class="sf-empty-stores__icon">
            <i class="fal fa-map-marker-alt"></i>
          </div>
          <h3 class="sf-empty-stores__title">{{ trans('theme.set_delivery_location') }}</h3>
          <p class="sf-empty-stores__text">{{ trans('theme.set_location_to_shop') }}</p>
          <button type="button" class="btn sf-btn-primary btn-round mt-2 js-open-address-setup">
            {{ trans('theme.confirm_location') }}
          </button>
        </div>
      @else
        <div class="sf-search-layout">
          <div class="sf-search-sidebar-col">
            @include('theme::partials._search_filters')
          </div>

          <div class="sf-search-content-col">
            <div class="sf-search-topbar">
              <div class="sf-search-topbar__toggles">
                <label class="sf-search-toggle">
                  <input name="free_shipping" class="i-check filter_opt_checkbox" type="checkbox" {{ Request::has('free_shipping') ? 'checked' : '' }}>
                  <span>{{ trans('theme.free_shipping') }}</span>
                </label>

                <label class="sf-search-toggle">
                  <input name="has_offers" class="i-check filter_opt_checkbox" type="checkbox" {{ Request::has('has_offers') ? 'checked' : '' }}>
                  <span>{{ trans('theme.has_offers') }}</span>
                </label>

                <label class="sf-search-toggle">
                  <input name="new_arrivals" class="i-check filter_opt_checkbox" type="checkbox" {{ Request::has('new_arrivals') ? 'checked' : '' }}>
                  <span>{{ trans('theme.new_arrivals') }}</span>
                </label>

                @if (is_incevio_package_loaded('auction'))
                  <label class="sf-search-toggle">
                    <input name="auction" class="i-check filter_opt_checkbox" type="checkbox" {{ Request::has('auction') ? 'checked' : '' }}>
                    <span>{{ trans('packages.auction.auction') }}</span>
                  </label>
                @endif
              </div>

              <div class="sf-search-sort">
                <label for="filter_opt_sort">{{ trans('theme.sort_by') }}</label>
                <select name="sort_by" class="sf-search-sort__select" id="filter_opt_sort">
                  <option value="best_match">{{ trans('theme.best_match') }}</option>
                  <option value="nearest" {{ Request::get('sort_by') == 'nearest' ? 'selected' : '' }}>{{ trans('theme.nearest') }}</option>
                  <option value="farthest" {{ Request::get('sort_by') == 'farthest' ? 'selected' : '' }}>{{ trans('theme.farthest') }}</option>
                  <option value="newest" {{ Request::get('sort_by') == 'newest' ? 'selected' : '' }}>{{ trans('theme.newest') }}</option>
                  <option value="oldest" {{ Request::get('sort_by') == 'oldest' ? 'selected' : '' }}>{{ trans('theme.oldest') }}</option>
                  <option value="price_asc" {{ Request::get('sort_by') == 'price_asc' ? 'selected' : '' }}>{{ trans('theme.price') }}: {{ trans('theme.low_to_high') }}</option>
                  <option value="price_desc" {{ Request::get('sort_by') == 'price_desc' ? 'selected' : '' }}>{{ trans('theme.price') }}: {{ trans('theme.high_to_low') }}</option>
                </select>
              </div>
            </div>

            @include('theme::partials._search_product_grid')
          </div>
        </div>
      @endif
    </div>
  </div>

  {{-- BROWSING ITEMS --}}
  @include('theme::sections.recent_views')
@endsection
