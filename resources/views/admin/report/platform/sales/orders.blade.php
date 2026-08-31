@extends('admin.layouts.master')

@section('page_title')
  {{ trans('app.orders') }}
@endsection

@section('content')
  @include('admin.partials.reports.sales_nav')
  @include('admin.partials.reports.summary_orders')

  <div class="report-filter-panel">
    <div class="row">
      <div class="col-md-2 nopadding-right">
        <div class="form-group">
          <label>{{ trans('app.customer') }}</label>
          <select style="width: 100%" onchange="fireEventOnFilter()" id="customerId" name="customer_id" class="form-control searchCustomer"></select>
        </div>
      </div>
      <div class="col-md-2 nopadding-right nopadding-left">
        <div class="form-group">
          <label>{{ trans('app.shops') }}</label>
          <select style="width: 100%" name="shop_id" onchange="fireEventOnFilter()" id="shopId" class="form-control searchMerchant"></select>
        </div>
      </div>
      <div class="col-md-2 nopadding-right nopadding-left">
        <div class="form-group">
          <label>{{ trans('app.order_number') }}</label>
          <input type="text" id="orderNumber" onkeyup="fireEventOnFilter()" name="order_number" value="{{ request()->get('order_number') }}" class="form-control" placeholder="{{ trans('app.order_number') }}">
        </div>
      </div>
      <div class="col-md-2 nopadding-right nopadding-left">
        <div class="form-group">
          <label>{{ trans('app.order_status') }}</label>
          <select id="orderStatus" onchange="fireEventOnFilter()" class="form-control" name="order_status">
            <option value="">{{ trans('app.all') }}</option>
            <option value="STATUS_WAITING_FOR_PAYMENT">{{ trans('app.waiting_for_payment') }}</option>
            <option value="STATUS_CONFIRMED">{{ trans('app.confirmed') }}</option>
            <option value="STATUS_FULFILLED">{{ trans('app.fulfilled') }}</option>
            <option value="STATUS_AWAITING_DELIVERY">{{ trans('app.awaiting_delivery') }}</option>
            <option value="STATUS_DELIVERED">{{ trans('app.delivered') }}</option>
            <option value="STATUS_CANCELED">{{ trans('app.canceled') }}</option>
            <option value="STATUS_PAYMENT_ERROR">{{ trans('app.payment_error') }}</option>
            <option value="STATUS_RETURNED">{{ trans('app.returns') }}</option>
            <option value="STATUS_DISPUTED">{{ trans('app.disputed') }}</option>
          </select>
        </div>
      </div>
      <div class="col-md-2 nopadding-right nopadding-left">
        <div class="form-group">
          <label>{{ trans('app.payment_status') }}</label>
          <select id="paymentStatus" onchange="fireEventOnFilter()" class="form-control" name="payment_status">
            <option value="">{{ trans('app.all') }}</option>
            <option value="PAYMENT_STATUS_UNPAID">{{ trans('app.unpaid') }}</option>
            <option value="PAYMENT_STATUS_PENDING">{{ trans('app.pending') }}</option>
            <option value="PAYMENT_STATUS_PAID">{{ trans('app.paid') }}</option>
            <option value="PAYMENT_STATUS_REFUNDED">{{ trans('app.refunded') }}</option>
          </select>
        </div>
      </div>
      <div class="col-md-2 nopadding-left">
        <div class="form-group">
          <label>&nbsp;</label>
          <button onclick="clearAllFilter()" type="button" class="btn btn-default btn-block"><i class="fa fa-times"></i> {{ trans('app.clear') }}</button>
        </div>
      </div>
    </div>
  </div>

  @include('admin.partials.ui.card_start', [
    'title' => trans('app.orders'),
    'icon' => 'fa-shopping-cart',
    'class' => 'report-table-box',
    'actions' => view('admin.partials.reports.timeframe')->render(),
    'bodyClass' => '',
  ])

      <div class="report-chart-card">
        <h4>{{ trans('app.orders') }}</h4>
        <canvas id="salesReport" style="height: 300px; min-height: 300px; max-height: 300px"></canvas>
      </div>

      <table class="table table-hover admin-table table-no-sort table-responsive orders-report-table">
        <thead>
          <tr>
            <th>{{ trans('app.order_date') }}</th>
            <th>{{ trans('app.delivery_date') }}</th>
            <th>{{ trans('app.order_number') }}</th>
            <th>{{ trans('app.customer') }}</th>
            @if (!Auth::user()->isMerchant())
              <th>{{ trans('app.shop') }}</th>
            @endif
            <th>{{ trans('app.quantity') }}</th>
            <th>{{ trans('app.payment_method') }}</th>
            <th>{{ trans('app.payment_status') }}</th>
            <th>{{ trans('app.total') }}</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>

  @include('admin.partials.ui.card_end')
@endsection

@section('page-script')
  @include('plugins.report-orders')
@endsection
