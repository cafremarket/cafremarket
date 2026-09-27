{{-- Platform income by source. Pass: $revenue (MarketplaceReportService::platformRevenue()). --}}
<ul class="report-breakdown">
  <li>
    <span>{{ trans('reports.revenue.commission_settled') }}<br><span class="muted">{{ trans('reports.revenue.commission_settled_hint') }}</span></span>
    <strong>{{ report_money($revenue['commission_settled']) }}</strong>
  </li>
  <li>
    <span>{{ trans('reports.revenue.commission_awaiting') }}<br><span class="muted">{{ trans('reports.revenue.commission_awaiting_hint') }}</span></span>
    <strong>{{ report_money($revenue['commission_awaiting']) }}</strong>
  </li>
  <li>
    <span>{{ trans('reports.revenue.customer_fees') }}<br><span class="muted">{{ trans('reports.revenue.customer_fees_hint') }}</span></span>
    <strong>{{ report_money($revenue['customer_fees']) }}</strong>
  </li>
  <li>
    <span>{{ trans('reports.revenue.subscription_fees') }}<br><span class="muted">{{ trans('reports.revenue.subscription_fees_hint') }}</span></span>
    <strong>{{ report_money($revenue['subscription_fees']) }}</strong>
  </li>
  <li class="total">
    <span>{{ trans('reports.revenue.total') }}</span>
    <strong>{{ report_money($revenue['total']) }}</strong>
  </li>
</ul>
