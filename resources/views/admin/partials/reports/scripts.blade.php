{{-- Shared scripts for report hub pages: period picker, Chart.js (v2) helpers, table setup. --}}
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css"/>
<script src="/js/chartjs.js" charset="utf-8"></script>

<script type="text/javascript">
  var reportPalette = ['#3c8dbc', '#00a65a', '#f39c12', '#dd4b39', '#605ca8', '#39cccc', '#d81b60', '#001f3f', '#ff851b', '#999999'];

  function reportLineChart(canvasId, trend, options) {
    var el = document.getElementById(canvasId);
    if (!el || !trend) {
      return null;
    }
    options = options || {};

    var datasets = [
      {
        type: 'bar',
        label: options.grossLabel,
        data: trend.gross,
        backgroundColor: 'rgba(60, 141, 188, 0.25)',
        borderColor: '#3c8dbc',
        borderWidth: 1,
        yAxisID: 'money'
      },
      {
        type: 'line',
        label: options.commissionLabel,
        data: trend.commission,
        borderColor: '#00a65a',
        backgroundColor: 'rgba(0, 166, 90, 0.08)',
        pointRadius: trend.labels.length > 45 ? 0 : 3,
        lineTension: 0.25,
        fill: true,
        yAxisID: 'money'
      }
    ];

    if (options.ordersLabel) {
      datasets.push({
        type: 'line',
        label: options.ordersLabel,
        data: trend.orders,
        borderColor: '#f39c12',
        backgroundColor: 'transparent',
        borderDash: [4, 4],
        pointRadius: 0,
        lineTension: 0.25,
        fill: false,
        yAxisID: 'count'
      });
    }

    return new Chart(el.getContext('2d'), {
      type: 'bar',
      data: {labels: trend.labels, datasets: datasets},
      options: {
        responsive: true,
        maintainAspectRatio: false,
        legend: {position: 'bottom'},
        tooltips: {
          mode: 'index',
          intersect: false,
          callbacks: {
            label: function (item, data) {
              var ds = data.datasets[item.datasetIndex];
              var v = ds.data[item.index];
              return ds.label + ': ' + (ds.yAxisID === 'count' ? formatReportInteger(v) : formatReportMoney(v));
            }
          }
        },
        scales: {
          xAxes: [{gridLines: {display: false}}],
          yAxes: [
            {id: 'money', position: 'left', ticks: reportChartMoneyTicks()},
            {id: 'count', position: 'right', display: !!options.ordersLabel, gridLines: {drawOnChartArea: false}, ticks: {beginAtZero: true, precision: 0}}
          ]
        }
      }
    });
  }

  function reportDoughnut(canvasId, labels, values, isMoney) {
    var el = document.getElementById(canvasId);
    if (!el) {
      return null;
    }

    return new Chart(el.getContext('2d'), {
      type: 'doughnut',
      data: {
        labels: labels,
        datasets: [{data: values, backgroundColor: reportPalette.slice(0, values.length), borderWidth: 1}]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutoutPercentage: 60,
        legend: {position: 'right', labels: {boxWidth: 12}},
        tooltips: {
          callbacks: {
            label: function (item, data) {
              var v = data.datasets[0].data[item.index];
              return data.labels[item.index] + ': ' + (isMoney ? formatReportMoney(v) : formatReportInteger(v));
            }
          }
        }
      }
    });
  }

  ;(function ($) {
    $(document).ready(function () {
      var $form = $('#report-filter-form');
      var $range = $('#report-range');

      if ($range.length && $.fn.daterangepicker) {
        var from = moment($form.find('[name=from]').val(), 'YYYY-MM-DD');
        var to = moment($form.find('[name=to]').val(), 'YYYY-MM-DD');

        $range.daterangepicker({
          startDate: from,
          endDate: to,
          maxDate: moment(),
          opens: 'right',
          showDropdowns: true,
          alwaysShowCalendars: false,
          buttonClasses: 'btn btn-sm',
          locale: {
            format: 'YYYY-MM-DD',
            applyLabel: @json(trans('reports.filter.apply')),
            cancelLabel: @json(trans('app.cancel')),
            customRangeLabel: @json(trans('reports.filter.custom'))
          },
          ranges: {
            @json(trans('app.today')): [moment(), moment()],
            @json(trans('app.yesterday')): [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
            @json(trans('app.last_7_days')): [moment().subtract(6, 'days'), moment()],
            @json(trans('app.last_30_day')): [moment().subtract(29, 'days'), moment()],
            @json(trans('app.this_month')): [moment().startOf('month'), moment()],
            @json(trans('app.last_month')): [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
            @json(trans('reports.filter.last_90_days')): [moment().subtract(89, 'days'), moment()],
            @json(trans('app.this_year')): [moment().startOf('year'), moment()],
            @json(trans('app.last_year')): [moment().subtract(1, 'year').startOf('year'), moment().subtract(1, 'year').endOf('year')]
          }
        }, function (start, end) {
          $form.find('[name=from]').val(start.format('YYYY-MM-DD'));
          $form.find('[name=to]').val(end.format('YYYY-MM-DD'));
          $form.trigger('submit');
        });
      }

      // Sortable, searchable tables with copy/CSV/Excel/print.
      $('table.report-dt').each(function () {
        var $t = $(this);
        if ($.fn.dataTable.isDataTable($t)) {
          return;
        }
        var order = $t.data('order');
        $t.DataTable({
          responsive: true,
          iDisplayLength: {{ getPaginationValue() }},
          order: order ? [order] : [],
          oLanguage: {sSearch: '', sSearchPlaceholder: @json(trans('app.search'))},
          dom: 'Bfrtip',
          buttons: ['copy', 'csv', 'excel', 'print']
        });
      });
    });
  }(window.jQuery));
</script>
