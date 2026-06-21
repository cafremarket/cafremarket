@php
  $reportCurrencyId = config('system_settings.currency.id');
  $reportCurrencyDecimals = function_exists('is_non_decimal_currency') && is_non_decimal_currency(get_system_currency())
      ? 0
      : 2;
@endphp

<script>
  var reportCurrencyPrefix = @json(get_currency_prefix($reportCurrencyId));
  var reportCurrencySuffix = @json(get_currency_suffix($reportCurrencyId));
  var reportCurrencyDecimals = {{ $reportCurrencyDecimals }};
  var reportDecimalMark = @json(config('system_settings.currency.decimal_mark', '.'));
  var reportThousandsSep = @json(config('system_settings.currency.thousands_separator', ','));

  function roundReportAmount(value) {
    var num = parseFloat(value);
    if (isNaN(num)) {
      return 0;
    }

    var factor = Math.pow(10, reportCurrencyDecimals);

    return Math.round((num + Number.EPSILON) * factor) / factor;
  }

  function formatReportMoney(value) {
    if (value === null || value === undefined || value === '') {
      return reportCurrencyPrefix + '0' + reportCurrencySuffix;
    }

    if (typeof value === 'string' && (value.indexOf(reportCurrencyPrefix) === 0 || /[^\d.,\-]/.test(value.replace(/[\d.,\-\s]/g, '')))) {
      return value;
    }

    var num = roundReportAmount(value);
    var fixed = num.toFixed(reportCurrencyDecimals);
    var parts = fixed.split('.');
    parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, reportThousandsSep);
    var formatted = reportCurrencyDecimals > 0 ? parts.join(reportDecimalMark) : parts[0];

    return reportCurrencyPrefix + formatted + reportCurrencySuffix;
  }

  function formatReportInteger(value) {
    var num = parseInt(value, 10);

    return isNaN(num) ? 0 : num;
  }

  function reportChartMoneyTooltip() {
    return {
      callbacks: {
        label: function (tooltipItem, data) {
          var dataset = data.datasets[tooltipItem.datasetIndex];
          var value = dataset.data[tooltipItem.index];

          return dataset.label + ': ' + formatReportMoney(value);
        }
      }
    };
  }

  function reportChartMoneyTicks() {
    return {
      beginAtZero: true,
      callback: function (value) {
        return formatReportMoney(value);
      }
    };
  }
</script>
