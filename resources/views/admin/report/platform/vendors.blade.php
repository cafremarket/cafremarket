@extends('admin.layouts.master')

@section('page_title')
  {{ trans('reports.vendors.title') }}
@endsection

@section('content')
  @include('admin.partials.reports.hub_nav')

  @php
    $q = request()->only(['from', 'to', 'shop_id']);
  @endphp

  @include('admin.partials.reports.filter_bar', [
    'action' => route('admin.report.vendors'),
    'exports' => [
      trans('reports.export.vendors') => route('admin.report.export', ['type' => 'vendors'] + $q),
    ],
  ])

  <div class="report-kpi-grid">
    @include('admin.partials.reports.kpi', ['label' => trans('reports.vendors.shops'), 'icon' => 'fa-building', 'value' => number_format($summary['shops']), 'sub' => trans('reports.vendors.new_in_period', ['count' => $growth['new_shops']])])
    @include('admin.partials.reports.kpi', ['label' => trans('reports.kpi.selling_shops'), 'icon' => 'fa-shopping-bag', 'tone' => 'green', 'value' => number_format($summary['selling'])])
    @include('admin.partials.reports.kpi', ['label' => trans('reports.vendors.idle'), 'icon' => 'fa-moon-o', 'tone' => 'grey', 'value' => number_format($summary['idle']), 'sub' => trans('reports.vendors.idle_hint')])
    @include('admin.partials.reports.kpi', ['label' => trans('reports.kpi.gross_sales'), 'icon' => 'fa-money', 'tone' => 'orange', 'value' => report_money($summary['gross'])])
    @include('admin.partials.reports.kpi', ['label' => trans('reports.kpi.commission'), 'icon' => 'fa-percent', 'tone' => 'green', 'value' => report_money($summary['commission'])])
    @include('admin.partials.reports.kpi', ['label' => trans('reports.payouts.paid_short'), 'icon' => 'fa-exchange', 'tone' => 'purple', 'value' => report_money($payouts['paid_amount']), 'sub' => trans('reports.payouts.pending', ['count' => $payouts['pending_count']]).': '.report_money($payouts['pending_amount'])])
    @include('admin.partials.reports.kpi', ['label' => trans('reports.payouts.wallet_balance'), 'icon' => 'fa-google-wallet', 'tone' => 'teal', 'value' => report_money($payouts['wallet_balance']), 'sub' => trans('reports.now')])
  </div>

  <div class="report-panel">
    <div class="report-panel__head">
      <h4><i class="fa fa-building"></i>{{ trans('reports.vendors.table') }}</h4>
      <span class="report-panel__hint">{{ trans('reports.rows', ['count' => $vendors->count()]) }}</span>
    </div>
    <div class="report-panel__body report-panel__body--flush">
      <table class="table table-hover report-dt" data-order='[4, "desc"]'>
        <thead>
          <tr>
            <th>{{ trans('reports.col.shop') }}</th>
            <th>{{ trans('reports.col.plan') }}</th>
            <th class="num">{{ trans('reports.col.placed_orders') }}</th>
            <th class="num">{{ trans('reports.col.paid_orders') }}</th>
            <th class="num">{{ trans('reports.col.gross') }}</th>
            <th class="num">{{ trans('reports.col.aov') }}</th>
            <th class="num">{{ trans('reports.col.commission') }}</th>
            <th class="num">{{ trans('reports.col.net_vendor') }}</th>
            <th class="num">{{ trans('reports.col.refunded') }}</th>
            <th class="num">{{ trans('reports.col.earnings') }}</th>
            <th class="num">{{ trans('reports.col.cancel_rate') }}</th>
            <th class="num">{{ trans('reports.col.paid_out') }}</th>
            <th class="num">{{ trans('reports.col.wallet_balance') }}</th>
            <th>{{ trans('reports.col.last_order') }}</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($vendors as $row)
            <tr>
              <td>
                <a href="{{ route('admin.report.overview', ['shop_id' => $row['shop_id']] + request()->only(['from', 'to'])) }}">{{ $row['shop'] }}</a>
                @unless ($row['active'])
                  <span class="label label-default">{{ trans('reports.vendors.inactive') }}</span>
                @endunless
              </td>
              <td>{{ $row['plan'] ?: '—' }}</td>
              <td class="num" data-order="{{ $row['placed_orders'] }}">{{ number_format($row['placed_orders']) }}</td>
              <td class="num" data-order="{{ $row['paid_orders'] }}">{{ number_format($row['paid_orders']) }}</td>
              <td class="num" data-order="{{ $row['gross'] }}"><strong>{{ report_money($row['gross']) }}</strong></td>
              <td class="num" data-order="{{ $row['average_order_value'] }}">{{ report_money($row['average_order_value']) }}</td>
              <td class="num" data-order="{{ $row['commission'] }}">{{ report_money($row['commission']) }}</td>
              <td class="num" data-order="{{ $row['net_vendor'] }}">{{ report_money($row['net_vendor']) }}</td>
              <td class="num" data-order="{{ $row['refunded'] }}">
                {{ report_money($row['refunded']) }}
                @if ($row['refunded'] > 0)<br><small class="text-muted">{{ report_percent($row['refund_rate']) }}</small>@endif
              </td>
              <td class="num" data-order="{{ $row['earnings'] }}">{{ report_money($row['earnings']) }}</td>
              <td class="num" data-order="{{ $row['cancel_rate'] }}">{{ report_percent($row['cancel_rate']) }}</td>
              <td class="num" data-order="{{ $row['paid_out'] }}">{{ report_money($row['paid_out']) }}</td>
              <td class="num" data-order="{{ $row['wallet_balance'] }}">{{ report_money($row['wallet_balance']) }}</td>
              <td data-order="{{ $row['last_order_at'] }}">{{ report_date($row['last_order_at']) }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
@endsection

@section('page-script')
  @include('admin.partials.reports.scripts')
@endsection
