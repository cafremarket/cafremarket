<style>
  .report-sales-nav {
    margin-bottom: 20px;
  }
  .report-sales-nav .nav-pills > li > a {
    border-radius: 4px;
    font-weight: 600;
    padding: 10px 18px;
  }
  .report-sales-nav .nav-pills > li.active > a,
  .report-sales-nav .nav-pills > li.active > a:hover,
  .report-sales-nav .nav-pills > li.active > a:focus {
    background-color: #3c8dbc;
  }
  .report-summary-row {
    margin-bottom: 20px;
  }
  .report-stat-box {
    background: #fff;
    border-radius: 6px;
    border: 1px solid #e8e8e8;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
    display: flex;
    align-items: stretch;
    min-height: 92px;
    overflow: hidden;
  }
  .report-stat-icon {
    align-items: center;
    color: #fff;
    display: flex;
    font-size: 28px;
    justify-content: center;
    min-width: 72px;
    width: 72px;
  }
  .report-stat-body {
    flex: 1;
    padding: 14px 16px;
  }
  .report-stat-label {
    color: #777;
    display: block;
    font-size: 12px;
    font-weight: 600;
    letter-spacing: 0.3px;
    margin-bottom: 4px;
    text-transform: uppercase;
  }
  .report-stat-value {
    color: #222;
    display: block;
    font-size: 22px;
    font-weight: 700;
    line-height: 1.2;
  }
  .report-filter-panel {
    background: #fff;
    border: 1px solid #e8e8e8;
    border-radius: 6px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    margin-bottom: 20px;
    padding: 16px 18px 6px;
  }
  .report-filter-panel .form-group label {
    color: #555;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
  }
  .report-chart-card {
    background: #fff;
    border: 1px solid #e8e8e8;
    border-radius: 6px;
    margin-bottom: 20px;
    padding: 16px;
  }
  .report-chart-card h4 {
    color: #444;
    font-size: 15px;
    font-weight: 600;
    margin: 0 0 12px;
  }
  .report-table-box .box-header {
    border-bottom: 1px solid #f0f0f0;
  }
  .report-table-box .table > thead > tr > th {
    background: #f9fafb;
    border-bottom-width: 1px;
    color: #555;
    font-size: 12px;
    text-transform: uppercase;
  }
  .report-timeframe {
    background: #fff !important;
    border: 1px solid #ddd;
    border-radius: 4px;
    cursor: pointer;
    display: inline-block;
    min-width: 260px;
    padding: 8px 12px !important;
  }
  .report-table-box .table td.text-right,
  .report-table-box .table th.text-right {
    text-align: right;
  }
</style>

@include('admin.partials.reports.formatters')
