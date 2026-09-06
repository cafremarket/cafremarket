<div class="dashboard-section">
  @if (\App\Models\SystemConfig::CustomerNeedsApproval() && !Auth::guard('customer')->user()->isApproved())
    <div class="alert alert-warning mb-3">
      <strong>{{ trans('app.account_pending_for_approval') }}</strong> {{ trans('help.account_pending_for_approval') }}
    </div>
  @endif

  <div class="sf-dashboard-welcome">
    <div class="sf-dashboard-welcome__top">
      <div class="sf-dashboard-welcome__greeting">
        <p class="sf-dashboard-welcome__eyebrow">@lang('theme.nav.dashboard')</p>
        <h2>{{ trans('theme.hello') }}, {{ $dashboard->getName() }}!</h2>
        <p>
          <i class="fas fa-clock" aria-hidden="true"></i>
          {{ trans('theme.member_since') }} {{ $dashboard->created_at->diffForHumans() }}
        </p>
      </div>

      <div class="sf-dashboard-welcome__actions">
        @unless ($dashboard->shippingAddress)
          <a href="{{ route('account.addresses') }}" class="btn btn-default btn-sm">
            <i class="fas fa-map-marker-alt" aria-hidden="true"></i> @lang('theme.add_shipping_address')
          </a>
        @endunless
        <a href="{{ url('/') }}" class="btn sf-btn-primary btn-sm">
          <i class="fas fa-shopping-cart" aria-hidden="true"></i> @lang('theme.button.continue_shopping')
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

    <a href="{{ route('account', 'disputes') }}" class="sf-stat-card">
      <span class="sf-stat-card__icon"><i class="fas fa-undo-alt"></i></span>
      <span class="sf-stat-card__value">{{ $dashboard->disputes_count }}</span>
      <span class="sf-stat-card__label">@lang('theme.nav.refunds_disputes')</span>
    </a>
  </div>

  <div class="row">
    <div class="col-12 mb-3">
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
  </div>

  @if (is_incevio_package_loaded('auction') && $dashboard->bids_count > 0)
    @include('auction::frontend._dashboard_bid_table')
  @endif

  @if (is_incevio_package_loaded('buyerGroup'))
    @include('buyerGroup::charts.customerCharts')
    @include('buyerGroup::partials._customer_report_section')
  @endif
</div>
