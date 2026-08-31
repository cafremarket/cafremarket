@extends('admin.layouts.master')

@section('page_title')
  {{ trans('app.products') }}
@endsection

@section('content')
  @include('admin.partials.reports.sales_nav')
  @include('admin.partials.reports.summary_products')

  <div class="report-filter-panel">
    <div class="row">
      <div class="col-md-4 nopadding-right">
        <div class="form-group">
          <label>{{ trans('app.products') }}</label>
          <select onchange="fireEventOnFilter()" id="productId" style="width: 100%" name="product_id" class="form-control searchProductForSelect"></select>
        </div>
      </div>
      <div class="col-md-4 nopadding-right nopadding-left">
        <div class="form-group">
          <label>{{ trans('app.shops') }}</label>
          <select style="width: 100%" onchange="fireEventOnFilter()" id="shopId" name="shop_id" class="form-control searchMerchant"></select>
        </div>
      </div>
      <div class="col-md-4 nopadding-left">
        <div class="form-group">
          <label>&nbsp;</label>
          <button onclick="clearAllFilter()" type="button" class="btn btn-default btn-block"><i class="fa fa-times"></i> {{ trans('app.clear') }}</button>
        </div>
      </div>
    </div>
  </div>

  @include('admin.partials.ui.card_start', [
    'title' => trans('app.products'),
    'icon' => 'fa-cube',
    'class' => 'report-table-box',
    'actions' => view('admin.partials.reports.timeframe')->render(),
    'bodyClass' => 'responsive-table',
  ])

      <table class="table table-hover admin-table table-no-sort table-responsive products-report-table">
        <thead>
          <tr>
            <th>{{ trans('app.product') }}</th>
            <th>{{ trans('app.model_number') }}</th>
            <th>{{ trans('app.gtin') }}</th>
            <th>{{ trans('app.quantity') }}</th>
            <th>{{ trans('app.unique_purchase') }}</th>
            <th>{{ trans('app.average_price') }}</th>
            <th>{{ trans('app.revenue') }}</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>

  @include('admin.partials.ui.card_end')
@endsection

@section('page-script')
  @include('plugins.report-products')
@endsection
