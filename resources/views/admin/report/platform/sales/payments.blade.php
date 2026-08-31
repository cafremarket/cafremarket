@extends('admin.layouts.master')

@section('page_title')
  {{ trans('app.payments') }}
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
          <select style="width: 100%" onchange="fireEventOnFilter()" id="shopId" name="shop_id" class="form-control searchMerchant"></select>
        </div>
      </div>
      <div class="col-md-2 nopadding-right nopadding-left">
        <div class="form-group">
          <label>{{ trans('app.order_number') }}</label>
          <input type="text" id="orderNumber" onkeyup="fireEventOnFilter()" name="order_number" class="form-control" placeholder="{{ trans('app.order_number') }}">
        </div>
      </div>
      <div class="col-md-2 nopadding-right nopadding-left">
        <div class="form-group">
          <label>{{ trans('app.payment_method') }}</label>
          <select id="paymentMethod" class="form-control" name="payment_method" onchange="fireEventOnFilter()">
            <option value="">{{ trans('app.all') }}</option>
            @foreach ($paymentMethods as $payment)
              <option value="{{ $payment->id }}">{{ $payment->name }}</option>
            @endforeach
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
    'title' => trans('app.payments'),
    'icon' => 'fa-credit-card',
    'class' => 'report-table-box',
    'actions' => view('admin.partials.reports.timeframe')->render(),
    'bodyClass' => '',
  ])

      <div class="report-chart-card">
        <h4>{{ trans('app.payments') }} — {{ trans('app.timeframe') }}</h4>
        <canvas id="salesReport" style="height: 280px; min-height: 280px; max-height: 280px; width: 100%"></canvas>
      </div>

      <div class="row">
        <div class="col-md-6">
          <div class="report-chart-card">
            <h4>{{ trans('app.payment_method') }}</h4>
            <canvas id="paymentMethodChart" style="height: 260px; min-height: 260px; max-height: 260px"></canvas>
          </div>
        </div>
        <div class="col-md-6">
          <div class="report-chart-card">
            <h4>{{ trans('app.payment_status') }}</h4>
            <canvas id="paymentStatusChart" style="height: 260px; min-height: 260px; max-height: 260px"></canvas>
          </div>
        </div>
      </div>

      <table class="table table-hover admin-table table-no-sort table-responsive payments-report-table">
        <thead>
          <tr>
            <th>{{ trans('app.date') }}</th>
            <th>{{ trans('app.order_number') }}</th>
            <th>{{ trans('app.customer') }}</th>
            <th>{{ trans('app.shop') }}</th>
            <th>{{ trans('app.payment_method') }}</th>
            <th>{{ trans('app.payment_status') }}</th>
            <th>{{ trans('app.items') }}</th>
            <th>{{ trans('app.total') }}</th>
            <th>{{ trans('app.grand_total') }}</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>

  @include('admin.partials.ui.card_end')
@endsection

@section('page-script')
  @include('plugins.report-payment')
@endsection
