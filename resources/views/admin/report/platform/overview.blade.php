@extends('admin.layouts.master')

@section('page_title')
  {{ trans('reports.overview.title') }}
@endsection

@section('content')
  @include('admin.partials.reports.hub_nav')

  @php
    $q = request()->only(['from', 'to', 'shop_id']);
  @endphp

  @include('admin.partials.reports.filter_bar', [
    'action' => route('admin.report.overview'),
    'exports' => [
      trans('reports.export.commission_orders') => route('admin.report.export', ['type' => 'commission'] + $q),
      trans('reports.export.vendors') => route('admin.report.export', ['type' => 'vendors'] + $q),
      trans('reports.export.customers') => route('admin.report.export', ['type' => 'customers'] + $q),
      trans('reports.export.products') => route('admin.report.export', ['type' => 'products'] + $q),
    ],
  ])

  <div class="report-kpi-grid">
    @include('admin.partials.reports.kpi', ['label' => trans('reports.kpi.gross_sales'), 'icon' => 'fa-money', 'value' => report_money($sales['gross_sales']), 'compare' => true, 'change' => $changes['gross_sales'], 'sub' => trans('reports.kpi.gross_sales_sub')])
    @include('admin.partials.reports.kpi', ['label' => trans('reports.kpi.commission'), 'icon' => 'fa-percent', 'tone' => 'green', 'value' => report_money($commission['total']), 'compare' => true, 'change' => $changes['commission'], 'sub' => trans('reports.kpi.effective_rate', ['rate' => report_percent($commission['effective_rate'], 2)])])
    @include('admin.partials.reports.kpi', ['label' => trans('reports.kpi.paid_orders'), 'icon' => 'fa-shopping-cart', 'tone' => 'orange', 'value' => number_format($sales['paid_orders']), 'compare' => true, 'change' => $changes['paid_orders'], 'sub' => trans('reports.kpi.of_placed', ['count' => number_format($sales['placed_orders'])])])
    @include('admin.partials.reports.kpi', ['label' => trans('reports.kpi.aov'), 'icon' => 'fa-shopping-basket', 'tone' => 'purple', 'value' => report_money($sales['average_order_value']), 'compare' => true, 'change' => $changes['average_order_value']])
    @include('admin.partials.reports.kpi', ['label' => trans('reports.kpi.buyers'), 'icon' => 'fa-users', 'tone' => 'teal', 'value' => number_format($sales['buyers']), 'compare' => true, 'change' => $changes['buyers'], 'sub' => trans('reports.kpi.new_customers', ['count' => number_format($growth['new_customers'])])])
    @include('admin.partials.reports.kpi', ['label' => trans('reports.kpi.refunded'), 'icon' => 'fa-undo', 'tone' => 'red', 'value' => report_money($sales['refunded']), 'compare' => true, 'change' => $changes['refunded'], 'badWhenUp' => true, 'sub' => trans('reports.kpi.refund_count', ['count' => $sales['refund_count']])])
    @include('admin.partials.reports.kpi', ['label' => trans('reports.kpi.net_sales'), 'icon' => 'fa-line-chart', 'value' => report_money($sales['net_sales']), 'sub' => trans('reports.kpi.net_sales_sub')])
    @include('admin.partials.reports.kpi', ['label' => trans('reports.kpi.unpaid'), 'icon' => 'fa-clock-o', 'tone' => 'grey', 'value' => report_money($sales['unpaid_value']), 'sub' => trans('reports.kpi.unpaid_sub', ['count' => $sales['unpaid_orders']])])
  </div>

  <div class="row">
    <div class="col-md-8">
      <div class="report-panel">
        <div class="report-panel__head">
          <h4><i class="fa fa-area-chart"></i>{{ trans('reports.overview.trend') }}</h4>
          <span class="report-panel__hint">{{ $report->period()->groupsByMonth() ? trans('reports.per_month') : trans('reports.per_day') }}</span>
        </div>
        <div class="report-panel__body">
          <div class="report-chart"><canvas id="trendChart"></canvas></div>
        </div>
      </div>
    </div>

    <div class="col-md-4">
      <div class="report-panel">
        <div class="report-panel__head">
          <h4><i class="fa fa-university"></i>{{ trans('reports.revenue.title') }}</h4>
          <a href="{{ route('admin.report.commission', $q) }}" class="report-panel__hint">{{ trans('reports.details') }} &rarr;</a>
        </div>
        <div class="report-panel__body">
          @include('admin.report.platform.partials.revenue_breakdown', ['revenue' => $revenue])
        </div>
      </div>

      <div class="report-panel">
        <div class="report-panel__head">
          <h4><i class="fa fa-exchange"></i>{{ trans('reports.payouts.title') }}</h4>
        </div>
        <div class="report-panel__body">
          <ul class="report-breakdown">
            <li><span>{{ trans('reports.payouts.paid', ['count' => $payouts['paid_count']]) }}</span><strong>{{ report_money($payouts['paid_amount']) }}</strong></li>
            <li><span>{{ trans('reports.payouts.pending', ['count' => $payouts['pending_count']]) }}</span><strong>{{ report_money($payouts['pending_amount']) }}</strong></li>
            <li><span>{{ trans('reports.payouts.wallet_balance') }} <span class="muted">({{ trans('reports.now') }})</span></span><strong>{{ report_money($payouts['wallet_balance']) }}</strong></li>
          </ul>
        </div>
      </div>
    </div>
  </div>

  <div class="report-kpi-grid">
    @include('admin.partials.reports.kpi', ['label' => trans('reports.kpi.new_shops'), 'icon' => 'fa-building', 'tone' => 'purple', 'value' => number_format($growth['new_shops'])])
    @include('admin.partials.reports.kpi', ['label' => trans('reports.kpi.selling_shops'), 'icon' => 'fa-shopping-bag', 'tone' => 'green', 'value' => number_format($growth['selling_shops'])])
    @include('admin.partials.reports.kpi', ['label' => trans('reports.kpi.units_sold'), 'icon' => 'fa-cubes', 'tone' => 'orange', 'value' => number_format($sales['units_sold'])])
    @include('admin.partials.reports.kpi', ['label' => trans('reports.kpi.delivered'), 'icon' => 'fa-truck', 'tone' => 'teal', 'value' => number_format($sales['delivered_orders'])])
    @include('admin.partials.reports.kpi', ['label' => trans('reports.kpi.cancel_rate'), 'icon' => 'fa-ban', 'tone' => 'red', 'value' => report_percent($sales['cancel_rate']), 'sub' => trans('reports.kpi.canceled_count', ['count' => $sales['canceled_orders']])])
    @include('admin.partials.reports.kpi', ['label' => trans('reports.kpi.discounts'), 'icon' => 'fa-tags', 'tone' => 'grey', 'value' => report_money($sales['discounts'])])
  </div>

  <div class="row">
    <div class="col-md-5">
      <div class="report-panel">
        <div class="report-panel__head">
          <h4><i class="fa fa-pie-chart"></i>{{ trans('reports.overview.order_status') }}</h4>
          <a href="{{ route('admin.sales.orders') }}" class="report-panel__hint">{{ trans('reports.details') }} &rarr;</a>
        </div>
        <div class="report-panel__body">
          @if ($statuses->isEmpty())
            <div class="report-empty">{{ trans('reports.empty') }}</div>
          @else
            <div class="report-chart report-chart--sm"><canvas id="statusChart"></canvas></div>
          @endif
        </div>
      </div>
    </div>

    <div class="col-md-7">
      <div class="report-panel">
        <div class="report-panel__head">
          <h4><i class="fa fa-credit-card"></i>{{ trans('reports.overview.payment_methods') }}</h4>
          <a href="{{ route('admin.sales.payments') }}" class="report-panel__hint">{{ trans('reports.details') }} &rarr;</a>
        </div>
        <div class="report-panel__body report-panel__body--flush">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>{{ trans('reports.col.payment_method') }}</th>
                <th class="num">{{ trans('reports.col.orders') }}</th>
                <th class="num">{{ trans('reports.col.paid_orders') }}</th>
                <th class="num">{{ trans('reports.col.paid_value') }}</th>
                <th style="width: 22%">{{ trans('reports.col.share') }}</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($paymentMethods as $row)
                <tr>
                  <td>{{ $row['method'] }}</td>
                  <td class="num">{{ number_format($row['orders']) }}</td>
                  <td class="num">{{ number_format($row['paid_orders']) }}</td>
                  <td class="num">{{ report_money($row['paid_value']) }}</td>
                  <td>
                    {{ report_percent($row['share']) }}
                    <div class="report-bar"><span style="width: {{ min(100, $row['share']) }}%"></span></div>
                  </td>
                </tr>
              @empty
                <tr><td colspan="5" class="report-empty">{{ trans('reports.empty') }}</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-md-6">
      <div class="report-panel">
        <div class="report-panel__head">
          <h4><i class="fa fa-trophy"></i>{{ trans('reports.overview.top_shops') }}</h4>
          <a href="{{ route('admin.report.vendors', $q) }}" class="report-panel__hint">{{ trans('reports.details') }} &rarr;</a>
        </div>
        <div class="report-panel__body report-panel__body--flush">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>{{ trans('reports.col.shop') }}</th>
                <th class="num">{{ trans('reports.col.orders') }}</th>
                <th class="num">{{ trans('reports.col.gross') }}</th>
                <th class="num">{{ trans('reports.col.commission') }}</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($topShops as $row)
                <tr>
                  <td>{{ $row['shop'] }}</td>
                  <td class="num">{{ number_format($row['orders']) }}</td>
                  <td class="num">{{ report_money($row['gross']) }}</td>
                  <td class="num">{{ report_money($row['commission']) }} <small class="text-muted">({{ report_percent($row['rate'], 1) }})</small></td>
                </tr>
              @empty
                <tr><td colspan="4" class="report-empty">{{ trans('reports.empty') }}</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="col-md-6">
      <div class="report-panel">
        <div class="report-panel__head">
          <h4><i class="fa fa-cube"></i>{{ trans('reports.overview.top_products') }}</h4>
          <a href="{{ route('admin.sales.products') }}" class="report-panel__hint">{{ trans('reports.details') }} &rarr;</a>
        </div>
        <div class="report-panel__body report-panel__body--flush">
          @include('admin.report.platform.partials.product_table', ['rows' => $topProducts])
        </div>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-md-6">
      <div class="report-panel">
        <div class="report-panel__head">
          <h4><i class="fa fa-sitemap"></i>{{ trans('reports.overview.top_categories') }}</h4>
          <span class="report-panel__hint">{{ trans('reports.overview.categories_hint') }}</span>
        </div>
        <div class="report-panel__body">
          @php $maxCategory = max(1, (float) $topCategories->max('revenue')); @endphp
          @forelse ($topCategories as $row)
            <div style="margin-bottom: 10px">
              <div class="clearfix">
                <span class="pull-left">{{ $row['name'] }} <small class="text-muted">· {{ trans('reports.units', ['count' => number_format($row['units'])]) }}</small></span>
                <strong class="pull-right">{{ report_money($row['revenue']) }}</strong>
              </div>
              <div class="report-bar"><span style="width: {{ round($row['revenue'] / $maxCategory * 100, 1) }}%"></span></div>
            </div>
          @empty
            <div class="report-empty">{{ trans('reports.empty') }}</div>
          @endforelse
        </div>
      </div>
    </div>

    <div class="col-md-6">
      <div class="report-panel">
        <div class="report-panel__head">
          <h4><i class="fa fa-star"></i>{{ trans('reports.overview.top_customers') }}</h4>
          <a href="{{ route('admin.report.customers', $q) }}" class="report-panel__hint">{{ trans('reports.details') }} &rarr;</a>
        </div>
        <div class="report-panel__body report-panel__body--flush">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>{{ trans('reports.col.customer') }}</th>
                <th class="num">{{ trans('reports.col.orders') }}</th>
                <th class="num">{{ trans('reports.col.spent') }}</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($topCustomers as $row)
                <tr>
                  <td>
                    {{ $row['name'] }}
                    @if ($row['is_new'])<span class="label label-success">{{ trans('reports.new') }}</span>@endif
                  </td>
                  <td class="num">{{ number_format($row['orders']) }}</td>
                  <td class="num">{{ report_money($row['spent']) }}</td>
                </tr>
              @empty
                <tr><td colspan="3" class="report-empty">{{ trans('reports.empty') }}</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
@endsection

@section('page-script')
  @include('admin.partials.reports.scripts')
  <script>
    $(function () {
      reportLineChart('trendChart', @json($trend), {
        grossLabel: @json(trans('reports.kpi.gross_sales')),
        commissionLabel: @json(trans('reports.kpi.commission')),
        ordersLabel: @json(trans('reports.kpi.paid_orders'))
      });
      reportDoughnut('statusChart', @json($statuses->pluck('label')), @json($statuses->pluck('orders')), false);
    });
  </script>
@endsection
