@extends('admin.layouts.master')

@section('page_title')
  {{ trans('reports.commission.title') }}
@endsection

@section('content')
  @include('admin.partials.reports.hub_nav')

  @php
    $q = request()->only(['from', 'to', 'shop_id']);
  @endphp

  @include('admin.partials.reports.filter_bar', [
    'action' => route('admin.report.commission'),
    'exports' => [
      trans('reports.export.commission_orders') => route('admin.report.export', ['type' => 'commission'] + $q),
      trans('reports.export.commission_shops') => route('admin.report.export', ['type' => 'commission-shops'] + $q),
    ],
  ])

  <div class="report-kpi-grid">
    @include('admin.partials.reports.kpi', ['label' => trans('reports.kpi.commission'), 'icon' => 'fa-percent', 'tone' => 'green', 'value' => report_money($summary['total']), 'sub' => trans('reports.kpi.effective_rate', ['rate' => report_percent($summary['effective_rate'], 2)])])
    @include('admin.partials.reports.kpi', ['label' => trans('reports.commission.settled'), 'icon' => 'fa-check-circle', 'tone' => 'green', 'value' => report_money($summary['settled']), 'sub' => trans('reports.orders_count', ['count' => $summary['settled_orders']])])
    @include('admin.partials.reports.kpi', ['label' => trans('reports.commission.awaiting'), 'icon' => 'fa-hourglass-half', 'tone' => 'orange', 'value' => report_money($summary['awaiting']), 'sub' => trans('reports.orders_count', ['count' => $summary['awaiting_orders']])])
    @include('admin.partials.reports.kpi', ['label' => trans('reports.commission.reversed'), 'icon' => 'fa-undo', 'tone' => 'red', 'value' => report_money($summary['reversed']), 'sub' => trans('reports.orders_count', ['count' => $summary['reversed_orders']])])
    @include('admin.partials.reports.kpi', ['label' => trans('reports.commission.commissionable'), 'icon' => 'fa-money', 'value' => report_money($summary['gross']), 'sub' => trans('reports.orders_count', ['count' => $summary['orders']]).' · '.trans('reports.commission.not_charged', ['count' => $summary['void_orders']])])
    @include('admin.partials.reports.kpi', ['label' => trans('reports.col.affiliate'), 'icon' => 'fa-handshake-o', 'tone' => 'purple', 'value' => report_money($summary['affiliate'])])
    @include('admin.partials.reports.kpi', ['label' => trans('reports.col.net_vendor'), 'icon' => 'fa-building', 'tone' => 'teal', 'value' => report_money($summary['net_vendor'])])
    @include('admin.partials.reports.kpi', ['label' => trans('reports.revenue.customer_fees'), 'icon' => 'fa-mobile', 'tone' => 'grey', 'value' => report_money($summary['customer_fees'])])
  </div>

  <div class="report-note">
    <i class="fa fa-info-circle"></i>{{ trans('reports.commission.explain') }}
  </div>

  <div class="row">
    <div class="col-md-8">
      <div class="report-panel">
        <div class="report-panel__head">
          <h4><i class="fa fa-area-chart"></i>{{ trans('reports.commission.trend') }}</h4>
          <span class="report-panel__hint">{{ $report->period()->groupsByMonth() ? trans('reports.per_month') : trans('reports.per_day') }}</span>
        </div>
        <div class="report-panel__body">
          <div class="report-chart"><canvas id="commissionTrend"></canvas></div>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="report-panel">
        <div class="report-panel__head">
          <h4><i class="fa fa-university"></i>{{ trans('reports.revenue.title') }}</h4>
        </div>
        <div class="report-panel__body">
          @include('admin.report.platform.partials.revenue_breakdown', ['revenue' => $revenue])
        </div>
      </div>
    </div>
  </div>

  <div class="report-panel">
    <div class="report-panel__head">
      <h4><i class="fa fa-building"></i>{{ trans('reports.commission.by_shop') }}</h4>
      <span class="report-panel__hint">{{ trans('reports.rows', ['count' => $byShop->count()]) }}</span>
    </div>
    <div class="report-panel__body report-panel__body--flush">
      <table class="table table-hover report-dt" data-order='[6, "desc"]'>
        <thead>
          <tr>
            <th>{{ trans('reports.col.shop') }}</th>
            <th>{{ trans('reports.col.plan') }}</th>
            <th class="num">{{ trans('reports.col.orders') }}</th>
            <th class="num">{{ trans('reports.col.gross') }}</th>
            <th class="num">{{ trans('reports.col.settled') }}</th>
            <th class="num">{{ trans('reports.col.awaiting') }}</th>
            <th class="num">{{ trans('reports.col.commission') }}</th>
            <th class="num">{{ trans('reports.col.rate') }}</th>
            <th class="num">{{ trans('reports.col.affiliate') }}</th>
            <th class="num">{{ trans('reports.col.net_vendor') }}</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($byShop as $row)
            <tr>
              <td><a href="{{ route('admin.report.commission', ['shop_id' => $row['shop_id']] + request()->only(['from', 'to'])) }}">{{ $row['shop'] }}</a></td>
              <td>{{ $row['plan'] ?: '—' }}</td>
              <td class="num" data-order="{{ $row['orders'] }}">{{ number_format($row['orders']) }}</td>
              <td class="num" data-order="{{ $row['gross'] }}">{{ report_money($row['gross']) }}</td>
              <td class="num" data-order="{{ $row['settled'] }}">{{ report_money($row['settled']) }}</td>
              <td class="num" data-order="{{ $row['awaiting'] }}">{{ report_money($row['awaiting']) }}</td>
              <td class="num" data-order="{{ $row['commission'] }}"><strong>{{ report_money($row['commission']) }}</strong></td>
              <td class="num" data-order="{{ $row['rate'] }}">{{ report_percent($row['rate'], 2) }}</td>
              <td class="num" data-order="{{ $row['affiliate'] }}">{{ report_money($row['affiliate']) }}</td>
              <td class="num" data-order="{{ $row['net_vendor'] }}">{{ report_money($row['net_vendor']) }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>

  <div class="report-panel">
    <div class="report-panel__head">
      <h4><i class="fa fa-list"></i>{{ trans('reports.commission.by_order') }}</h4>
      <span class="report-panel__hint">{{ trans('reports.rows', ['count' => $ledger->count()]) }}</span>
    </div>
    <div class="report-panel__body report-panel__body--flush">
      @include('admin.report.platform.partials.ledger_table', ['ledger' => $ledger, 'withShop' => true])
    </div>
  </div>
@endsection

@section('page-script')
  @include('admin.partials.reports.scripts')
  <script>
    $(function () {
      reportLineChart('commissionTrend', @json($trend), {
        grossLabel: @json(trans('reports.kpi.gross_sales')),
        commissionLabel: @json(trans('reports.kpi.commission'))
      });
    });
  </script>
@endsection
