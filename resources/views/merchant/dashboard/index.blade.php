@extends('merchant.layouts.app')

@section('page_title', trans('nav.dashboard'))

@section('head')
  @include('plugins.ionic')
@endsection

@section('content')
  <div class="mp-page-intro">
    <h2>{{ trans('app.welcome') }}, {{ Auth::user()->getName() }}!</h2>
    <p>{{ optional(Auth::user()->shop)->name }}</p>
    @if (optional(Auth::user()->shop)->slug)
      <p class="mp-shop-url">
        <a href="{{ get_shop_url() }}" target="_blank" rel="noopener">{{ get_shop_url() }}</a>
      </p>
    @endif
  </div>

  @include('merchant.partials.subscription_notice')

  <div class="mp-stat-grid">
    <div class="mp-stat-card mp-stat-card--yellow">
      <div class="mp-stat-card__icon"><i class="icon ion-md-cube"></i></div>
      <div class="mp-stat-card__body">
        <span class="mp-stat-card__label">{{ trans('app.unfulfilled_orders') }}</span>
        <span class="mp-stat-card__value">
          {{ $unfulfilled_order_count }}
          <a href="{{ url('merchant/order/order?tab=unfulfilled') }}" class="mp-stat-card__link" title="{{ trans('app.detail') }}">
            <i class="icon ion-md-send"></i>
          </a>
        </span>
      </div>
    </div>

    <div class="mp-stat-card mp-stat-card--blue">
      <div class="mp-stat-card__icon"><i class="icon ion-md-cart"></i></div>
      <div class="mp-stat-card__body">
        <span class="mp-stat-card__label">{{ trans('app.last_sale') }}</span>
        <span class="mp-stat-card__value">
          {{ get_formated_currency($last_sale ? $last_sale->total : 0, 2, config('system_settings.currency.id')) }}
        </span>
      </div>
    </div>

    <div class="mp-stat-card mp-stat-card--green">
      <div class="mp-stat-card__icon"><i class="icon ion-md-cash"></i></div>
      <div class="mp-stat-card__body">
        <span class="mp-stat-card__label">{{ trans('app.todays_sale') }}</span>
        <span class="mp-stat-card__value">
          {{ get_formated_currency($todays_sale_amount, 2, config('system_settings.currency.id')) }}
        </span>
      </div>
    </div>

    <div class="mp-stat-card mp-stat-card--red">
      <div class="mp-stat-card__icon"><i class="icon ion-md-basket"></i></div>
      <div class="mp-stat-card__body">
        <span class="mp-stat-card__label">{{ trans('app.stock_outs') }}</span>
        <span class="mp-stat-card__value">
          {{ $stock_out_count }}
          <a href="{{ url('merchant/stock/inventory') }}" class="mp-stat-card__link" title="{{ trans('app.detail') }}">
            <i class="icon ion-md-send"></i>
          </a>
        </span>
      </div>
    </div>
  </div>

  @if (! Auth::user()->shop->isVerified() && ! Auth::user()->shop->config->pending_verification)
    <div class="mp-alert mp-alert--warning">
      <i class="fa fa-shield"></i>
      {{ trans('messages.complete_store_verification') }}
      <a href="{{ route('merchant.verify') }}" class="mp-btn mp-btn--primary" style="margin-left:12px;height:36px;padding:0 16px">{{ trans('app.get_verified') }}</a>
    </div>
  @endif

  <div class="mp-panel">
    <div class="mp-panel__head"><h2 style="margin:0;font-size:16px">{{ trans('app.latest_orders') }}</h2></div>
    <div class="mp-panel__body mp-panel__body--flush">
      <table class="mp-table">
        <thead>
          <tr>
            <th>{{ trans('app.order_number') }}</th>
            <th>{{ trans('app.order_date') }}</th>
            <th>{{ trans('app.customer') }}</th>
            <th>{{ trans('app.grand_total') }}</th>
            <th>{{ trans('app.status') }}</th>
          </tr>
        </thead>
        <tbody>
          @forelse($latest_orders ?? [] as $order)
            <tr>
              <td>
                @can('view', $order)
                  <a href="{{ mp_route('admin.order.order.show', $order->id) }}">{{ $order->order_number }}</a>
                @else
                  {{ $order->order_number }}
                @endcan
              </td>
              <td>{{ $order->created_at->diffForHumans() }}</td>
              <td>{{ optional($order->customer)->name }}</td>
              <td>{{ get_formated_currency($order->grand_total, 2, config('system_settings.currency.id')) }}</td>
              <td>{!! $order->orderStatus() !!}</td>
            </tr>
          @empty
            <tr><td colspan="5" class="mp-table__empty">{{ trans('messages.no_orders') }}</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
@endsection
