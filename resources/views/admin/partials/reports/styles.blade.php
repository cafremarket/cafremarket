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

  /* ---- Report hub ---- */
  .report-hub-nav {
    background: #fff;
    border: 1px solid #e8e8e8;
    border-radius: 6px;
    margin-bottom: 16px;
    overflow-x: auto;
    white-space: nowrap;
  }
  .report-hub-nav ul {
    display: flex;
    list-style: none;
    margin: 0;
    padding: 0 6px;
  }
  .report-hub-nav li a {
    border-bottom: 3px solid transparent;
    color: #555;
    display: block;
    font-weight: 600;
    padding: 13px 16px 10px;
  }
  .report-hub-nav li a:hover {
    color: #3c8dbc;
  }
  .report-hub-nav li.active a {
    border-bottom-color: #3c8dbc;
    color: #3c8dbc;
  }
  .report-hub-nav li a .fa {
    margin-right: 5px;
    opacity: .8;
  }
  .report-sub-nav {
    margin: -4px 0 16px;
  }
  .report-sub-nav .nav-pills > li > a {
    border-radius: 16px;
    font-size: 13px;
    padding: 6px 14px;
  }
  .report-filter-bar {
    align-items: flex-end;
    background: #fff;
    border: 1px solid #e8e8e8;
    border-radius: 6px;
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 18px;
    padding: 14px 16px;
  }
  .report-filter-bar .form-group {
    margin: 0;
  }
  .report-filter-bar label {
    color: #666;
    display: block;
    font-size: 11px;
    font-weight: 600;
    margin-bottom: 4px;
    text-transform: uppercase;
  }
  .report-filter-bar .report-filter-shop {
    min-width: 220px;
  }
  .report-filter-bar .report-filter-actions {
    display: flex;
    gap: 8px;
    margin-left: auto;
  }
  .report-period-note {
    color: #888;
    font-size: 12px;
    margin: -10px 0 16px;
  }
  .report-kpi-grid {
    display: grid;
    gap: 14px;
    grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
    margin-bottom: 18px;
  }
  .report-kpi {
    background: #fff;
    border: 1px solid #e8e8e8;
    border-left: 4px solid #3c8dbc;
    border-radius: 6px;
    padding: 14px 16px;
  }
  .report-kpi--green { border-left-color: #00a65a; }
  .report-kpi--orange { border-left-color: #f39c12; }
  .report-kpi--red { border-left-color: #dd4b39; }
  .report-kpi--purple { border-left-color: #605ca8; }
  .report-kpi--teal { border-left-color: #39cccc; }
  .report-kpi--grey { border-left-color: #999; }
  .report-kpi__label {
    color: #777;
    display: block;
    font-size: 11px;
    font-weight: 600;
    letter-spacing: .3px;
    text-transform: uppercase;
  }
  .report-kpi__label .fa {
    margin-right: 4px;
  }
  .report-kpi__value {
    color: #222;
    display: block;
    font-size: 22px;
    font-weight: 700;
    line-height: 1.3;
    margin-top: 4px;
    word-break: break-word;
  }
  .report-kpi__sub {
    color: #888;
    display: block;
    font-size: 12px;
    margin-top: 2px;
  }
  .report-change {
    border-radius: 10px;
    display: inline-block;
    font-size: 11px;
    font-weight: 700;
    margin-left: 4px;
    padding: 1px 7px;
  }
  .report-change--up { background: #e3f5ea; color: #00804a; }
  .report-change--down { background: #fbe7e5; color: #c0392b; }
  .report-change--flat { background: #f0f0f0; color: #777; }
  .report-change--bad.report-change--up { background: #fbe7e5; color: #c0392b; }
  .report-change--bad.report-change--down { background: #e3f5ea; color: #00804a; }
  .report-panel {
    background: #fff;
    border: 1px solid #e8e8e8;
    border-radius: 6px;
    margin-bottom: 18px;
  }
  .report-panel__head {
    align-items: center;
    border-bottom: 1px solid #f0f0f0;
    display: flex;
    gap: 10px;
    justify-content: space-between;
    padding: 12px 16px;
  }
  .report-panel__head h4 {
    color: #333;
    font-size: 15px;
    font-weight: 600;
    margin: 0;
  }
  .report-panel__head h4 .fa {
    color: #3c8dbc;
    margin-right: 6px;
  }
  .report-panel__hint {
    color: #999;
    font-size: 12px;
  }
  .report-panel__body {
    padding: 14px 16px;
  }
  .report-panel__body--flush {
    padding: 0;
  }
  .report-panel .table {
    margin-bottom: 0;
  }
  .report-panel .table > thead > tr > th {
    background: #f9fafb;
    border-bottom-width: 1px;
    color: #555;
    font-size: 11px;
    text-transform: uppercase;
    white-space: nowrap;
  }
  .report-panel .table td.num,
  .report-panel .table th.num {
    text-align: right;
    white-space: nowrap;
  }
  .report-panel .dataTables_wrapper {
    padding: 10px 12px;
  }
  .report-chart {
    height: 300px;
    position: relative;
  }
  .report-chart--sm {
    height: 240px;
  }
  .report-bar {
    background: #eef3f8;
    border-radius: 3px;
    height: 6px;
    margin-top: 4px;
    overflow: hidden;
  }
  .report-bar span {
    background: #3c8dbc;
    display: block;
    height: 100%;
  }
  .report-breakdown {
    list-style: none;
    margin: 0;
    padding: 0;
  }
  .report-breakdown li {
    border-bottom: 1px solid #f3f3f3;
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
  }
  .report-breakdown li:last-child {
    border-bottom: 0;
  }
  .report-breakdown li.total {
    border-top: 2px solid #e8e8e8;
    font-weight: 700;
  }
  .report-breakdown .muted {
    color: #999;
    font-size: 12px;
  }
  .report-status {
    border-radius: 10px;
    display: inline-block;
    font-size: 11px;
    font-weight: 600;
    padding: 2px 9px;
    white-space: nowrap;
  }
  .report-status--settled, .report-status--2 { background: #e3f5ea; color: #00804a; }
  .report-status--awaiting, .report-status--1 { background: #fff4e0; color: #b36b00; }
  .report-status--reversed, .report-status--3, .report-status--4 { background: #fbe7e5; color: #c0392b; }
  .report-status--void { background: #f0f0f0; color: #777; }
  .report-empty {
    color: #999;
    padding: 24px;
    text-align: center;
  }
  .report-note {
    background: #f7fafc;
    border: 1px solid #e3edf5;
    border-radius: 6px;
    color: #5b6b7a;
    font-size: 12px;
    margin-bottom: 18px;
    padding: 10px 14px;
  }
  .report-note .fa {
    color: #3c8dbc;
    margin-right: 6px;
  }
  @media (max-width: 767px) {
    .report-filter-bar .report-filter-actions {
      margin-left: 0;
      width: 100%;
    }
    .report-filter-bar .report-filter-shop,
    .report-filter-bar .form-group {
      width: 100%;
    }
    .report-timeframe {
      min-width: 0;
      width: 100%;
    }
  }
</style>

@include('admin.partials.reports.formatters')
