@extends('admin.layouts.master')

@section('page_title')
  {{ trans('reports.customers.title') }}
@endsection

@section('content')
  @include('admin.partials.reports.hub_nav')

  @include('admin.partials.reports.filter_bar', [
    'action' => route('admin.report.customers'),
    'exports' => [
      trans('reports.export.customers') => route('admin.report.export', ['type' => 'customers'] + request()->only(['from', 'to', 'shop_id'])),
    ],
  ])

  <div class="report-kpi-grid">
    @include('admin.partials.reports.kpi', ['label' => trans('reports.kpi.buyers'), 'icon' => 'fa-users', 'value' => number_format($summary['buyers'])])
    @include('admin.partials.reports.kpi', ['label' => trans('reports.customers.new_buyers'), 'icon' => 'fa-user-plus', 'tone' => 'green', 'value' => number_format($summary['new_buyers']), 'sub' => trans('reports.customers.new_buyers_hint')])
    @include('admin.partials.reports.kpi', ['label' => trans('reports.customers.returning_buyers'), 'icon' => 'fa-refresh', 'tone' => 'purple', 'value' => number_format($summary['returning_buyers'])])
    @include('admin.partials.reports.kpi', ['label' => trans('reports.customers.repeat_rate'), 'icon' => 'fa-repeat', 'tone' => 'teal', 'value' => report_percent($summary['repeat_rate']), 'sub' => trans('reports.customers.repeat_hint', ['count' => $summary['repeat_buyers']])])
    @include('admin.partials.reports.kpi', ['label' => trans('reports.customers.orders_per_buyer'), 'icon' => 'fa-shopping-cart', 'tone' => 'orange', 'value' => number_format($summary['orders_per_buyer'], 2)])
    @include('admin.partials.reports.kpi', ['label' => trans('reports.customers.spend_per_buyer'), 'icon' => 'fa-money', 'tone' => 'orange', 'value' => report_money($summary['spend_per_buyer'])])
    @unless ($report->shopId())
      @include('admin.partials.reports.kpi', ['label' => trans('reports.customers.registered'), 'icon' => 'fa-id-card-o', 'tone' => 'grey', 'value' => number_format($summary['registered'])])
    @endunless
  </div>

  <div class="report-panel">
    <div class="report-panel__head">
      <h4><i class="fa fa-users"></i>{{ trans('reports.customers.table') }}</h4>
      <span class="report-panel__hint">{{ trans('reports.rows', ['count' => $customers->count()]) }}</span>
    </div>
    <div class="report-panel__body report-panel__body--flush">
      <table class="table table-hover report-dt" data-order='[3, "desc"]'>
        <thead>
          <tr>
            <th>{{ trans('reports.col.customer') }}</th>
            <th>{{ trans('reports.col.email') }}</th>
            <th class="num">{{ trans('reports.col.orders') }}</th>
            <th class="num">{{ trans('reports.col.spent') }}</th>
            <th class="num">{{ trans('reports.col.aov') }}</th>
            <th class="num">{{ trans('reports.col.shops') }}</th>
            <th>{{ trans('reports.col.first_order') }}</th>
            <th>{{ trans('reports.col.last_order') }}</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($customers as $row)
            <tr>
              <td>
                {{ $row['name'] }}
                @if ($row['is_new'])<span class="label label-success">{{ trans('reports.new') }}</span>@endif
              </td>
              <td>{{ $row['email'] ?: '—' }}</td>
              <td class="num" data-order="{{ $row['orders'] }}">{{ number_format($row['orders']) }}</td>
              <td class="num" data-order="{{ $row['spent'] }}"><strong>{{ report_money($row['spent']) }}</strong></td>
              <td class="num" data-order="{{ $row['average_order_value'] }}">{{ report_money($row['average_order_value']) }}</td>
              <td class="num">{{ $row['shops'] }}</td>
              <td data-order="{{ $row['first_order_at'] }}">{{ report_date($row['first_order_at']) }}</td>
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
