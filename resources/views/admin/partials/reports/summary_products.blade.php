<div class="row report-summary-row" id="report-summary-cards">
  <div class="col-md-3 col-sm-6 col-xs-12 nopadding-right">
    <div class="report-stat-box">
      <div class="report-stat-icon bg-green"><i class="fa fa-cube"></i></div>
      <div class="report-stat-body">
        <span class="report-stat-label">{{ trans('app.products') }}</span>
        <span class="report-stat-value" data-summary="products_sold">{{ $summary['products_sold'] ?? 0 }}</span>
      </div>
    </div>
  </div>
  <div class="col-md-3 col-sm-6 col-xs-12 nopadding-right nopadding-left">
    <div class="report-stat-box">
      <div class="report-stat-icon bg-aqua"><i class="fa fa-cubes"></i></div>
      <div class="report-stat-body">
        <span class="report-stat-label">{{ trans('app.quantity') }}</span>
        <span class="report-stat-value" data-summary="units_sold">{{ $summary['units_sold'] ?? 0 }}</span>
      </div>
    </div>
  </div>
  <div class="col-md-3 col-sm-6 col-xs-12 nopadding-right nopadding-left">
    <div class="report-stat-box">
      <div class="report-stat-icon bg-yellow"><i class="fa fa-shopping-cart"></i></div>
      <div class="report-stat-body">
        <span class="report-stat-label">{{ trans('app.orders') }}</span>
        <span class="report-stat-value" data-summary="order_count">{{ $summary['order_count'] ?? 0 }}</span>
      </div>
    </div>
  </div>
  <div class="col-md-3 col-sm-6 col-xs-12 nopadding-left">
    <div class="report-stat-box">
      <div class="report-stat-icon bg-purple"><i class="fa fa-money"></i></div>
      <div class="report-stat-body">
        <span class="report-stat-label">{{ trans('app.revenue') }}</span>
        <span class="report-stat-value" data-summary="revenue">{{ $summary['revenue'] ?? 0 }}</span>
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
