@extends('admin.layouts.master')

@section('page_title')
  {{ trans('reports.earnings.title') }}
@endsection

@section('content')
  @include('admin.partials.reports.styles')

  @php
    $q = request()->only(['from', 'to']);
  @endphp

  @include('admin.partials.reports.filter_bar', [
    'action' => route('merchant.shop-earnings'),
    'shops' => null,
    'exports' => [
      trans('reports.export.earnings') => route('merchant.shop-earnings.export', ['type' => 'earnings'] + $q),
      trans('reports.export.products') => route('merchant.shop-earnings.export', ['type' => 'products'] + $q),
    ],
  ])

  <div class="report-kpi-grid">
    @include('admin.partials.reports.kpi', ['label' => trans('reports.kpi.gross_sales'), 'icon' => 'fa-money', 'value' => report_money($sales['gross_sales']), 'sub' => trans('reports.orders_count', ['count' => $sales['paid_orders']])])
    @include('admin.partials.reports.kpi', ['label' => trans('reports.earnings.commission'), 'icon' => 'fa-percent', 'tone' => 'red', 'value' => report_money($summary['total']), 'sub' => trans('reports.kpi.effective_rate', ['rate' => report_percent($summary['effective_rate'], 2)])])
    @include('admin.partials.reports.kpi', ['label' => trans('reports.col.affiliate'), 'icon' => 'fa-handshake-o', 'tone' => 'purple', 'value' => report_money($summary['affiliate'])])
    @include('admin.partials.reports.kpi', ['label' => trans('reports.earnings.net'), 'icon' => 'fa-google-wallet', 'tone' => 'green', 'value' => report_money($summary['net_vendor'] - $sales['refunded']), 'sub' => trans('reports.earnings.net_hint')])
    @include('admin.partials.reports.kpi', ['label' => trans('reports.earnings.credited'), 'icon' => 'fa-check-circle', 'tone' => 'green', 'value' => trans('reports.orders_count', ['count' => $summary['settled_orders']]), 'sub' => trans('reports.earnings.commission_amount', ['amount' => report_money($summary['settled'])])])
    @include('admin.partials.reports.kpi', ['label' => trans('reports.earnings.awaiting'), 'icon' => 'fa-hourglass-half', 'tone' => 'orange', 'value' => trans('reports.orders_count', ['count' => $summary['awaiting_orders']]), 'sub' => trans('reports.earnings.commission_amount', ['amount' => report_money($summary['awaiting'])])])
    @include('admin.partials.reports.kpi', ['label' => trans('reports.kpi.refunded'), 'icon' => 'fa-undo', 'tone' => 'red', 'value' => report_money($sales['refunded']), 'sub' => trans('reports.kpi.refund_count', ['count' => $sales['refund_count']])])
    @include('admin.partials.reports.kpi', ['label' => trans('reports.payouts.paid_short'), 'icon' => 'fa-exchange', 'tone' => 'teal', 'value' => report_money($payouts['paid_amount']), 'sub' => trans('reports.col.wallet_balance').': '.report_money($payouts['wallet_balance'])])
  </div>

  <div class="report-note">
    <i class="fa fa-info-circle"></i>{{ trans('reports.earnings.explain') }}
  </div>

  <div class="row">
    <div class="col-md-7">
      <div class="report-panel">
        <div class="report-panel__head">
          <h4><i class="fa fa-area-chart"></i>{{ trans('reports.earnings.trend') }}</h4>
          <span class="report-panel__hint">{{ $report->period()->groupsByMonth() ? trans('reports.per_month') : trans('reports.per_day') }}</span>
        </div>
        <div class="report-panel__body">
          <div class="report-chart"><canvas id="earningsTrend"></canvas></div>
        </div>
      </div>
    </div>
    <div class="col-md-5">
      <div class="report-panel">
        <div class="report-panel__head">
          <h4><i class="fa fa-cube"></i>{{ trans('reports.overview.top_products') }}</h4>
        </div>
        <div class="report-panel__body report-panel__body--flush">
          @include('admin.report.platform.partials.product_table', ['rows' => $topProducts])
        </div>
      </div>
    </div>
  </div>

  <div class="report-panel">
    <div class="report-panel__head">
      <h4><i class="fa fa-list"></i>{{ trans('reports.earnings.by_order') }}</h4>
      <span class="report-panel__hint">{{ trans('reports.rows', ['count' => $ledger->count()]) }}</span>
    </div>
    <div class="report-panel__body report-panel__body--flush">
      @include('admin.report.platform.partials.ledger_table', ['ledger' => $ledger, 'withShop' => false])
    </div>
  </div>
@endsection

@section('page-script')
  @include('admin.partials.reports.scripts')
  <script>
    $(function () {
      reportLineChart('earningsTrend', @json($trend), {
        grossLabel: @json(trans('reports.kpi.gross_sales')),
        commissionLabel: @json(trans('reports.earnings.commission')),
        ordersLabel: @json(trans('reports.kpi.paid_orders'))
      });
    });
  </script>
@endsection
