@extends('admin.layouts.master')

@section('page_title')
  {{ trans('reports.refunds.title') }}
@endsection

@section('content')
  @include('admin.partials.reports.hub_nav')

  @include('admin.partials.reports.filter_bar', [
    'action' => route('admin.report.refunds'),
    'exports' => [
      trans('reports.export.refunds') => route('admin.report.export', ['type' => 'refunds'] + request()->only(['from', 'to', 'shop_id'])),
    ],
  ])

  <div class="report-kpi-grid">
    @include('admin.partials.reports.kpi', ['label' => trans('reports.refunds.requests'), 'icon' => 'fa-inbox', 'value' => number_format($totals['total_count']), 'sub' => report_money($totals['total_amount'])])
    @include('admin.partials.reports.kpi', ['label' => trans('reports.refunds.approved'), 'icon' => 'fa-check', 'tone' => 'red', 'value' => report_money($totals['approved_amount']), 'sub' => trans('reports.refunds.count', ['count' => $totals['approved_count']])])
    @include('admin.partials.reports.kpi', ['label' => trans('reports.refunds.open'), 'icon' => 'fa-hourglass-half', 'tone' => 'orange', 'value' => report_money($totals['open_amount']), 'sub' => trans('reports.refunds.count', ['count' => $totals['open_count']])])
    @include('admin.partials.reports.kpi', ['label' => trans('reports.refunds.declined'), 'icon' => 'fa-times', 'tone' => 'grey', 'value' => number_format($totals['declined_count'])])
    @include('admin.partials.reports.kpi', ['label' => trans('reports.refunds.rate'), 'icon' => 'fa-percent', 'tone' => 'purple', 'value' => report_percent($refundRate), 'sub' => trans('reports.refunds.rate_hint')])
  </div>

  <div class="report-panel">
    <div class="report-panel__head">
      <h4><i class="fa fa-undo"></i>{{ trans('reports.refunds.table') }}</h4>
      <span class="report-panel__hint">{{ trans('reports.rows', ['count' => $refunds->count()]) }}</span>
    </div>
    <div class="report-panel__body report-panel__body--flush">
      <table class="table table-hover report-dt" data-order='[0, "desc"]'>
        <thead>
          <tr>
            <th>{{ trans('reports.col.date') }}</th>
            <th>{{ trans('reports.col.order') }}</th>
            <th>{{ trans('reports.col.shop') }}</th>
            <th>{{ trans('reports.col.customer') }}</th>
            <th class="num">{{ trans('reports.col.order_total') }}</th>
            <th class="num">{{ trans('reports.col.amount') }}</th>
            <th>{{ trans('reports.col.status') }}</th>
            <th>{{ trans('reports.col.reason') }}</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($refunds as $row)
            <tr>
              <td data-order="{{ $row['date'] }}">{{ report_date($row['date']) }}</td>
              <td>{{ $row['order_number'] ?: '—' }}</td>
              <td>{{ $row['shop'] ?: '—' }}</td>
              <td>{{ $row['customer'] ?: '—' }}</td>
              <td class="num" data-order="{{ $row['order_total'] }}">{{ report_money($row['order_total']) }}</td>
              <td class="num" data-order="{{ $row['amount'] }}"><strong>{{ report_money($row['amount']) }}</strong></td>
              <td>
                <span class="report-status report-status--{{ $row['status'] }}">{{ $row['status_label'] }}</span>
                @if ($row['return_goods'])<br><small class="text-muted">{{ trans('reports.refunds.goods_returned') }}</small>@endif
              </td>
              <td>{{ \Illuminate\Support\Str::limit(strip_tags((string) $row['reason']), 80) ?: '—' }}</td>
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
