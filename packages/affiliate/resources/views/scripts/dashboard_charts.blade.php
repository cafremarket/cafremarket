<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script><!-- jQuery -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script><!--Chart js-->

<script type="text/javascript">
  $(document).ready(function() {

    createCommissionByLinkChart();

    createCommissionByShopChart();

    createVisitorByLinkChart();

  });

  function createCommissionByLinkChart() {
    var chartData = getDefaultDataObject('doughnut', '');
    const MonthlyPurchaseAmountChart = new Chart($('#js-commissionByLinkChart'), chartData);
    chartData.data.datasets[0].label = 'Number of Commissions';
    chartData.options.scales.y.display = false; // remove grid line

    $.ajax({
      url: "{{ route('affiliate.chartData.commission.link') }}",
      type: 'GET',
      success: function(response) {
        
        if (response.labels.length === 0 || checkDataArrayValues(response.data)) {
          response.labels = ['You don\'t have any active links with commission'];
          response.data = ['1'];
          MonthlyPurchaseAmountChart.options = {
            plugins : {
              tooltip: {
                enabled: false
              }
            }
          };
        }

        chartData.data.labels = response.labels;
        chartData.data.datasets[0].data = response.data;

        MonthlyPurchaseAmountChart.update('active');
      }
    });
  }

  function createCommissionByShopChart() {
    var chartData = getDefaultDataObject('doughnut', '');
    chartData.data.datasets[0].label = 'Total Commission';
    chartData.options.scales.y.display = false; // remove grid line

    const TotalOrderedByShopChart = new Chart($('#js-commissionByShopChart'), chartData);

    $.ajax({
      url: "{{ route('affiliate.chartData.commission.shop') }}",
      type: 'GET',
      success: function(response) {
        if (response.labels.length === 0 || checkDataArrayValues(response.data)) {
          response.labels = ['You don\'t have any active links with commission'];
          response.data = ['1'];
          TotalOrderedByShopChart.options = {
                  plugins : {
                    tooltip: {
                      enabled: false
                    }
                  }
                };
        }

        chartData.data.labels = response.labels;
        chartData.data.datasets[0].data = response.data;
        
        TotalOrderedByShopChart.update('active');
      }
    });
  }

  function createVisitorByLinkChart() {
    var chartData = getDefaultDataObject('doughnut', '');
    chartData.data.datasets[0].label = 'Total Visitors';
    chartData.options.scales.y.display = false; // remove grid line

    const TotalOrderedByShopChart = new Chart($('#js-visitorByLinkChart'), chartData);

    $.ajax({
      url: "{{ route('affiliate.chartData.visitor.link') }}",
      type: 'GET',
      success: function(response) {
        if (response.labels.length === 0 || checkDataArrayValues(response.data)) {
          response.labels = ['You don\'t have any active links with visitors'];
          response.data = ['1'];
          TotalOrderedByShopChart.options = {
            plugins : {
              tooltip: {
                enabled: false
              }
            }
          };
        }

        chartData.data.datasets[0].data = response.data;
        chartData.data.labels = response.labels;

        TotalOrderedByShopChart.update('active');
      }
    });
  }

  /**
   * Returns a default data object for a chart.
   *
   * @param {string} chartType - The type of chart for chart.js Chart object (default: 'line').
   * @param {string} chartTitle - The title of the chart (default: 'Chart title').
   * @param {Array} chartLabels - The labels for the chart (default: ['label 1', 'label 2', 'label 3']).
   * @param {Array} chartData - The data for the chart (default: [100, 200, 300]).
   * @param {string} dataLabel - The label for the data that appears on hover (default: 'Data label').
   * @returns {Object} - The default data object for the chart.js library chart object.
   */
  function getDefaultDataObject(chartType = 'line', chartTitle = 'Chart title', chartLabels = ['label 1', 'label 2', 'label 3'], chartData = [100, 200, 300], dataLabel = 'Data label') {
    defaultObject = {
      type: chartType,
      data: {
        labels: chartLabels,
        datasets: [{
          label: dataLabel,
          data: chartData,
          borderWidth: 1
        }]
      },
      options: {
        scales: {
          y: {
            beginAtZero: true
          },
        },
        plugins: {
          legend: {
             display: false
          },
          title: {
            display: true,
            text: chartTitle
          }
        },
      }
    }

    return defaultObject;
  }

  function getLineObject(line_data = ['10', '50', '100'], hover_label = 'hover_label', bg_color = 'rgba(54, 162, 235, 0.5)', border_color = 'rgb(54, 162, 235)') {
    return {
      backgroundColor: bg_color,
      borderColor: border_color,
      label: hover_label,
      data: line_data,
      borderWidth: 1
    };
  }

  function getOrderStatusColor(status) {
    switch (status) {
      case 'Confirmed':
        return 'rgb(191, 214, 65)';
      case 'Fulfilled':
        return 'rgba(54, 162, 235, 0.5)';
      case 'Waiting for payment':
        return 'rgb(255, 222, 89)';
      case 'Delivered':
        return 'rgb(68, 163, 31)';
      case 'Awaiting delivery':
        return 'rgb(254, 153, 0)';
      default:
        return getRandomRgb();
    }
  }

  function getRandomRgb() {
    const r = Math.floor(Math.random() * 256);
    const g = Math.floor(Math.random() * 256);
    const b = Math.floor(Math.random() * 256);

    return `rgb(${r},${g},${b})`;
  }

  /**
   * Checks if all values in an array are zero or non existant.
   *
   * @param {Array} array - The array to check.
   * @returns {boolean} - Returns true if all values in the array are zero, otherwise returns false.
   */
  function checkDataArrayValues(array) {
    if (array.length === 0) {
      return true;
    }

    for (let i = 0; i < array.length; i++) {
      if (array[i] !== 0) {
        return false;
      }
    }

    return true;
  }
</script>
