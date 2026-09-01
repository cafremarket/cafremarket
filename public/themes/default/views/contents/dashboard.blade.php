<div class="dashboard-section">
  @if (\App\Models\SystemConfig::CustomerNeedsApproval() && !Auth::guard('customer')->user()->isApproved())
    <div class="alert alert-warning mb-3">
      <strong>{{ trans('app.account_pending_for_approval') }}</strong> {{ trans('help.account_pending_for_approval') }}
    </div>
  @endif

  <div class="sf-dashboard-welcome">
    <div class="sf-dashboard-welcome__top">
      <div class="sf-dashboard-welcome__greeting">
        <h2>{{ trans('theme.hello') }}, {{ $dashboard->getName() }}!</h2>
        <p>
          <i class="fas fa-clock"></i>
          {{ trans('theme.member_since') }} {{ $dashboard->created_at->diffForHumans() }}
        </p>
      </div>

      <div class="d-flex flex-wrap gap-2" style="gap: 8px;">
        @unless ($dashboard->shippingAddress)
          <a href="{{ route('account', 'account') }}#address-tab" class="btn btn-default btn-sm">
            <i class="fas fa-truck"></i> @lang('theme.add_shipping_address')
          </a>
        @endunless
        <a href="{{ url('/') }}" class="btn sf-btn-primary btn-sm">
          <i class="fas fa-shopping-cart"></i> @lang('theme.button.continue_shopping')
        </a>
      </div>
    </div>
  </div>

  <div class="sf-stat-grid">
    <a href="{{ route('account', 'orders') }}" class="sf-stat-card">
      <span class="sf-stat-card__icon"><i class="fas fa-shopping-bag"></i></span>
      <span class="sf-stat-card__value">{{ $dashboard->orders_count }}</span>
      <span class="sf-stat-card__label">@lang('theme.orders')</span>
    </a>

    <a href="{{ route('account', 'wishlist') }}" class="sf-stat-card">
      <span class="sf-stat-card__icon"><i class="fas fa-heart"></i></span>
      <span class="sf-stat-card__value">{{ $dashboard->wishlists_count }}</span>
      <span class="sf-stat-card__label">@lang('theme.wishlist')</span>
    </a>

    <a href="{{ route('account', 'messages') }}" class="sf-stat-card">
      <span class="sf-stat-card__icon"><i class="fas fa-envelope"></i></span>
      <span class="sf-stat-card__value">{{ $dashboard->messages_count }}</span>
      <span class="sf-stat-card__label">@lang('theme.unread_messages')</span>
    </a>

    <a href="{{ route('account', 'coupons') }}" class="sf-stat-card">
      <span class="sf-stat-card__icon"><i class="fas fa-tags"></i></span>
      <span class="sf-stat-card__value">{{ $dashboard->coupons_count }}</span>
      <span class="sf-stat-card__label">@lang('theme.coupons')</span>
    </a>
  </div>

  <div class="row">
    <div class="col-lg-6 mb-3">
      <div class="sf-panel">
        <div class="sf-panel__head">
          <span>@lang('theme.orders')</span>
          <a href="{{ route('account', 'orders') }}" class="small">@lang('theme.nav.my_orders') &rarr;</a>
        </div>
        <div class="sf-panel__body table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>{{ trans('theme.date') }}</th>
                <th>{{ trans('theme.orders') }}</th>
                <th>{{ trans('theme.amount') }}</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($dashboard->orders as $order)
                <tr>
                  <td>{{ $order->created_at->format('M j') }}</td>
                  <td>
                    <a href="{{ route('order.detail', $order) }}">{{ $order->order_number }}</a>
                    <small class="text-muted d-block">{!! $order->orderStatus() !!}</small>
                  </td>
                  <td>{!! get_formated_currency($order->grand_total, 2, $order->currency_id) !!}</td>
                </tr>
              @empty
                <tr>
                  <td colspan="3" class="text-center text-muted py-4">@lang('theme.no_order_history')</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="col-lg-6 mb-3">
      <div class="sf-panel">
        <div class="sf-panel__head">
          <span>@lang('theme.wishlist')</span>
          <a href="{{ route('account', 'wishlist') }}" class="small">@lang('theme.nav.my_wishlist') &rarr;</a>
        </div>
        <div class="sf-panel__body table-responsive">
          <table class="table table-hover">
            <tbody>
              @forelse ($dashboard->wishlists as $wish)
                @if ($wish->inventory)
                  <tr>
                    <td width="50">
                      <img src="{{ get_product_img_src($wish->inventory, 'tiny_thumb') }}" alt="" width="40" height="40" style="object-fit:cover;border-radius:6px;">
                    </td>
                    <td>
                      <a href="{{ route('show.product', $wish->inventory->slug) }}">{{ Str::limit($wish->inventory->title, 40) }}</a>
                    </td>
                    <td class="text-right">
                      <a class="btn btn-xs sf-btn-primary" href="{{ route('direct.checkout', $wish->inventory->slug) }}">
                        @lang('theme.button.buy_now')
                      </a>
                    </td>
                  </tr>
                @elseif ($wish->product)
                  <tr>
                    <td width="50">
                      <img src="{{ get_storage_file_url(optional($wish->product->featureImage)->path, 'tiny') }}" alt="" width="40" height="40" style="object-fit:cover;border-radius:6px;">
                    </td>
                    <td>
                      <a href="{{ route('show.offers', $wish->product->slug) }}">{{ Str::limit($wish->product->name, 40) }}</a>
                    </td>
                    <td class="text-right">
                      <a class="btn btn-xs btn-default" href="{{ route('show.offers', $wish->product->slug) }}">
                        @lang('theme.view_more_offers', ['count' => $wish->product->inventories_count ?? 0])
                      </a>
                    </td>
                  </tr>
                @endif
              @empty
                <tr>
                  <td colspan="3" class="text-center text-muted py-4">@lang('theme.empty_wishlist')</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  @if (is_incevio_package_loaded('auction') && $dashboard->bids_count > 0)
    @include('auction::frontend._dashboard_bid_table')
  @endif

  @if (is_incevio_package_loaded('buyerGroup'))
    @include('buyerGroup::charts.customerCharts')
    @include('buyerGroup::partials._customer_report_section')
  @endif
</div>
