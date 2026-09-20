@isset($products)
  <div class="row product-list-wrapper mb-4">
    <div class="col-xl-2 col-lg-3 border radius">
      @include('theme::partials._product_list_sidebar_filters')
    </div><!-- /.col-sm-3 -->

    <div class="col-xl-10 col-lg-9">
      <div class="row product-2nd-parent">
        <div class="col-md-12 pr-0">
          <div class="product-list-top-filter border-t radius px-3 mb-4">
            <span>
              @lang('theme.sort_by'):
              <select name="sort_by" class="selectBoxIt" id="filter_opt_sort">
                <option value="best_match">
                  @lang('theme.best_match')
                </option>

                <option value="newest" {{ Request::get('sort_by') == 'newest' ? 'selected' : '' }}>
                  @lang('theme.newest')
                </option>

                <option value="oldest" {{ Request::get('sort_by') == 'oldest' ? 'selected' : '' }}>
                  @lang('theme.oldest')
                </option>

                <option value="price_asc" {{ Request::get('sort_by') == 'price_asc' ? 'selected' : '' }}>
                  @lang('theme.price'): @lang('theme.low_to_high')
                </option>

                <option value="price_desc" {{ Request::get('sort_by') == 'price_desc' ? 'selected' : '' }}>
                  @lang('theme.price'): @lang('theme.high_to_low')
                </option>
              </select> <!-- /.sort_by -->
            </span>

            @if (is_incevio_package_loaded('auction'))
              <div class="checkbox">
                <label>
                  <input name="auction" class="i-check filter_opt_checkbox" type="checkbox" {{ Request::has('auction') ? 'checked' : '' }}>
                  @lang('packages.auction.auction')
                  {{-- <span class="small">({{ $products->where('auctionable', 1)->count() }})</span> --}}
                </label>
              </div> <!-- /.checkbox -->
            @endif

            <div class="checkbox">
              <label>
                <input name="free_shipping" class="i-check filter_opt_checkbox" type="checkbox" {{ Request::has('free_shipping') ? 'checked' : '' }}>
                @lang('theme.free_shipping')
                {{-- <span class="small">({{ $hasFreeShipping }})</span> --}}
                {{-- <span class="small">({{ $products->where('free_shipping', 1)->count() }})</span> --}}
              </label>
            </div> <!-- /.checkbox -->

            <div class="checkbox">
              <label>
                <input name="has_offers" class="i-check filter_opt_checkbox" type="checkbox" {{ Request::has('has_offers') ? 'checked' : '' }} />
                @lang('theme.has_offers')
                {{-- <span class="small">({{ $hasOffers }})</span> --}}
                {{-- <span class="small">({{ $products->where('offer_price', '>', 0)->where('offer_start', '<', \Carbon\Carbon::now())->where('offer_end', '>', \Carbon\Carbon::now())->count() }})</span> --}}
              </label>
            </div> <!-- /.checkbox -->

            <div class="checkbox">
              <label>
                <input name="new_arrivals" class="i-check filter_opt_checkbox" type="checkbox" {{ Request::has('new_arrivals') ? 'checked' : '' }} />
                @lang('theme.new_arrivals')
                <span class="small">
                  {{-- ({{ $newArrivals }}) --}}
                  {{-- ({{ $products->where('created_at', '>', \Carbon\Carbon::now()->subDays(config('system.filter.new_arraival', 7)))->count() }}) --}}
                </span>
              </label>
            </div> <!-- /.checkbox -->

            <span class="pull-right text-muted d-none d-xl-inline-block ">
              <a href="javascript:void(0);" class="viewSwitcher btn btn-primary btn-sm">
                <i class="fas fa-th" data-toggle="tooltip" title="@lang('theme.grid_view')"></i>
              </a>
              <a href="javascript:void(0);" class="viewSwitcher btn btn-default btn-sm">
                <i class="fas fa-list" data-toggle="tooltip" title="@lang('theme.list_view')"></i>
              </a>
            </span>
          </div> <!-- /.product-list-top-filter -->
        </div> <!-- /.col-md-12 -->

        @php
          $sf_catalog = app(\App\Services\Hyperlocal\HyperlocalCatalogService::class);
          $sf_card_distances = $sf_catalog->shopDistances();
          $sf_card_out_of_range = $sf_catalog->outOfRangeShopIds();
        @endphp

        @forelse ($products as $item)
          <div class="col-4 col-sm-3 col-md-3 col-lg-3 col-xl-{{ $colum ?? '2' }} px-2 mb-3 categoryCard">
            @include('theme::partials._product_card', [
              'item' => $item,
              'distance' => $sf_card_distances->get($item->shop_id),
              'outOfRange' => in_array($item->shop_id, $sf_card_out_of_range),
            ])
          </div> <!-- /.col-md-* -->
        @empty
          <div class="col-12 lead text-center my-5">
            <p class="mb-3">{{ trans('theme.no_product_found') }}</p>

            <a href="{{ url('categories') }}" class="btn btn-primary btn-sm">
              {{ trans('theme.button.choose_from_categories') }}
            </a>
          </div> <!-- /.col-12 -->
        @endforelse
      </div><!-- /.row -->


      <div class="row pagenav-wrapper my-4">
        <div class="col-12">
          {{ $products->appends(request()->input())->links('theme::layouts.pagination') }}
        </div>
      </div><!-- /.row .pagenav-wrapper -->
    </div><!-- /.col-sm-9 -->
  </div><!-- /.row .product-list-wrapper -->

  <hr class="dotted" />
@endisset
