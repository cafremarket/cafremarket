<div class="row report-summary-row" id="report-summary-cards">
  <div class="col-md-3 col-sm-6 col-xs-12 nopadding-right">
    <div class="report-stat-box">
      <div class="report-stat-icon bg-yellow"><i class="fa fa-shopping-cart"></i></div>
      <div class="report-stat-body">
        <span class="report-stat-label">{{ trans('app.orders') }}</span>
        <span class="report-stat-value" data-summary="total_orders">{{ $summary['total_orders'] ?? 0 }}</span>
      </div>
    </div>
  </div>
  <div class="col-md-3 col-sm-6 col-xs-12 nopadding-right nopadding-left">
    <div class="report-stat-box">
      <div class="report-stat-icon bg-aqua"><i class="fa fa-money"></i></div>
      <div class="report-stat-body">
        <span class="report-stat-label">{{ trans('app.total_revenue') }}</span>
        <span class="report-stat-value" data-summary="total_revenue">{{ $summary['total_revenue'] ?? 0 }}</span>
      </div>
    </div>
  </div>
  <div class="col-md-3 col-sm-6 col-xs-12 nopadding-right nopadding-left">
    <div class="report-stat-box">
      <div class="report-stat-icon bg-green"><i class="fa fa-check-circle"></i></div>
      <div class="report-stat-body">
        <span class="report-stat-label">{{ trans('app.paid') }}</span>
        <span class="report-stat-value" data-summary="paid_orders">{{ $summary['paid_orders'] ?? 0 }}</span>
        <small class="text-muted" data-summary="paid_revenue">{{ $summary['paid_revenue'] ?? 0 }}</small>
      </div>
    </div>
  </div>
  <div class="col-md-3 col-sm-6 col-xs-12 nopadding-left">
    <div class="report-stat-box">
      <div class="report-stat-icon bg-red"><i class="fa fa-clock-o"></i></div>
      <div class="report-stat-body">
        <span class="report-stat-label">{{ trans('app.pending') }}</span>
        <span class="report-stat-value" data-summary="pending_revenue">{{ $summary['pending_revenue'] ?? 0 }}</span>
      </div>
    </div>
  </div>
</div>

<script>
  function updateReportSummary(summary) {
    if (!summary) {
      return;
    }
    Object.keys(summary).forEach(function (key) {
      $('[data-summary="' + key + '"]').text(summary[key]);
    });
  }
</script>
