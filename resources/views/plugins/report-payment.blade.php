<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css"/>
<script src="/js/chartjs.js" charset=utf-8></script>

<script type="text/javascript">
    var generate;
    var methodGenerate;
    var statusGenerate;
    var salesCtx;
    var paymentMethodChart;
    var paymentStatusChart;
    var reportDefaultDays = {{ (int) config('report.sales.default', 7) }};

    function buildPaymentsFilterString() {
        return "fromDate=" + encodeURIComponent($('#getFromDate').val()) +
            "&toDate=" + encodeURIComponent($('#getToDate').val()) +
            "&paymentMethod=" + encodeURIComponent($('#paymentMethod').val() || '') +
            "&paymentStatus=" + encodeURIComponent($('#paymentStatus').val() || '') +
            "&customerId=" + encodeURIComponent($('#customerId').val() || '') +
            "&shopId=" + encodeURIComponent($('#shopId').val() || '') +
            "&orderNumber=" + encodeURIComponent($('#orderNumber').val() || '');
    }

    function refreshPaymentsReport() {
        var dataString = buildPaymentsFilterString();

        ajaxFire('{{ route('admin.sales.payments.getMoreForChart') }}', dataString, function (output) {
            generate.clear();
            generate.destroy();
            generate = new Chart(salesCtx, chartDataFormat(output));
        });

        ajaxFire('{{ route('admin.sales.payments.getMethod') }}', dataString, function (output) {
            methodGenerate.clear();
            methodGenerate.destroy();
            methodGenerate = new Chart(paymentMethodChart, paymentMethodPie(output));
        });

        ajaxFire('{{ route('admin.sales.payments.getStatus') }}', dataString, function (output) {
            statusGenerate.clear();
            statusGenerate.destroy();
            statusGenerate = new Chart(paymentStatusChart, paymentStatusPie(output));
        });

        paymentsTableResetting(dataString);
    }

    ;(function ($) {
        var chartData = @json($chartDataArray);
        var paymentMethod = @json($paymentMethod);
        var paymentStatus = @json($paymentStatus);

        salesCtx = document.getElementById('salesReport').getContext('2d');
        paymentMethodChart = document.getElementById('paymentMethodChart').getContext('2d');
        paymentStatusChart = document.getElementById('paymentStatusChart').getContext('2d');

        generate = new Chart(salesCtx, chartDataFormat(chartData));
        methodGenerate = new Chart(paymentMethodChart, paymentMethodPie(paymentMethod));
        statusGenerate = new Chart(paymentStatusChart, paymentStatusPie(paymentStatus));

        $(document).ready(function () {
            var startDefault = moment().subtract(reportDefaultDays - 1, 'days');
            var endDefault = moment();

            $('#daterangepicker').daterangepicker({
                startDate: startDefault,
                endDate: endDefault,
                showDropdowns: false,
                showWeekNumbers: true,
                ranges: {
                    '{{ trans('app.today') }}': [moment(), moment()],
                    '{{ trans('app.yesterday') }}': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                    '{{ trans('app.last_7_days') }}': [moment().subtract(6, 'days'), moment()],
                    '{{ trans('app.last_30_day') }}': [moment().subtract(29, 'days'), moment()],
                    '{{ trans('app.this_month') }}': [moment().startOf('month'), moment()],
                    '{{ trans('app.last_month') }}': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
                    '{{ trans('app.last_12_month') }}': [moment().startOf('month').subtract(12, 'month'), moment().endOf('month')],
                    '{{ trans('app.this_year') }}': [moment().startOf('year'), moment()],
                    '{{ trans('app.last_year') }}': [moment().subtract(1, 'year').startOf('year'), moment().subtract(1, 'year').endOf('year')]
                },
                opens: 'left',
                buttonClasses: ['btn btn-default'],
                format: 'DD/MM/YYYY',
                separator: ' to ',
            }, function (start, end) {
                $('#daterangepicker span').html(start.format('D MMMM YYYY') + ' - ' + end.format('D MMMM YYYY'));
                $('#getFromDate').val(start.format('YYYY-MM-DD'));
                $('#getToDate').val(end.format('YYYY-MM-DD'));
                refreshPaymentsReport();
            });

            $('#daterangepicker span').html(startDefault.format('D MMMM YYYY') + ' - ' + endDefault.format('D MMMM YYYY'));
            $('#getFromDate').val(startDefault.format('YYYY-MM-DD'));
            $('#getToDate').val(endDefault.format('YYYY-MM-DD'));

            refreshPaymentsReport();
        });
    }(window.jQuery));

    function paymentsTableResetting(dataString) {
        var table = $('.payments-report-table');
        if ($.fn.dataTable.isDataTable(table)) {
            table.DataTable().destroy();
        }

        table.DataTable({
            responsive: true,
            iDisplayLength: {{ getPaginationValue() }},
            ajax: {
                url: '{{ route('admin.sales.payments.getMore') }}/?' + dataString,
                dataSrc: function (json) {
                    if (json.summary) {
                        updateReportSummary(json.summary);
                    }
                    return json.data;
                }
            },
            columns: [
                {data: 'date', name: 'date'},
                {data: 'order_number', name: 'order_number'},
                {data: 'customer', name: 'customer'},
                {data: 'shop', name: 'shop'},
                {data: 'payment_method', name: 'payment_method'},
                {data: 'payment_status_name', name: 'payment_status'},
                {data: 'item', name: 'item', render: function (data) { return formatReportInteger(data); }},
                {
                    data: 'total_formatted',
                    name: 'total',
                    className: 'text-right',
                    render: function (data, type, row) {
                        return data || formatReportMoney(row.total);
                    }
                },
                {
                    data: 'grand_total_formatted',
                    name: 'grand_total',
                    className: 'text-right',
                    render: function (data, type, row) {
                        return data || formatReportMoney(row.grand_total);
                    }
                },
            ],
            dom: 'Bfrtip',
            buttons: ['copy', 'csv', 'excel', 'pdf', 'print']
        });
    }

    function chartDataFormat(chartData) {
        var labelData = [];
        var pending = [];
        var paid = [];
        var refunded = [];

        for (var i = 0; i < chartData.length; i++) {
            labelData.push(chartData[i].date);
            pending.push(roundReportAmount(chartData[i].pending));
            paid.push(roundReportAmount(chartData[i].paid));
            refunded.push(roundReportAmount(chartData[i].refunded));
        }

        return {
            type: 'line',
            data: {
                labels: labelData,
                datasets: [
                    {label: '{{ trans('app.pending') }}', fill: true, backgroundColor: 'rgba(255, 193, 7, 0.25)', borderColor: '#ffc107', data: pending},
                    {label: '{{ trans('app.paid') }}', fill: true, backgroundColor: 'rgba(40, 167, 69, 0.25)', borderColor: '#28a745', data: paid},
                    {label: '{{ trans('app.refunded') }}', fill: true, backgroundColor: 'rgba(220, 53, 69, 0.25)', borderColor: '#dc3545', data: refunded},
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                legend: {position: 'bottom'},
                tooltips: reportChartMoneyTooltip(),
                scales: {yAxes: [{ticks: reportChartMoneyTicks()}]}
            }
        };
    }

    function paymentMethodPie(chartData) {
        var labelData = [];
        var mainData = [];

        for (var i = 0; i < chartData.length; i++) {
            labelData.push(chartData[i].name);
            mainData.push(roundReportAmount(chartData[i].total));
        }

        return {
            type: 'doughnut',
            data: {
                labels: labelData,
                datasets: [{
                    data: mainData,
                    backgroundColor: ['#28a745', '#17a2b8', '#ffc107', '#dc3545', '#6c757d', '#007bff', '#6610f2', '#fd7e14']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                legend: {position: 'bottom'},
                tooltips: reportChartMoneyTooltip()
            }
        };
    }

    function paymentStatusPie(chartData) {
        var pending = 0;
        var paid = 0;
        var refunded = 0;

        for (var i = 0; i < chartData.length; i++) {
            pending += roundReportAmount(chartData[i].pending);
            paid += roundReportAmount(chartData[i].paid);
            refunded += roundReportAmount(chartData[i].refunded);
        }

        return {
            type: 'doughnut',
            data: {
                labels: ['{{ trans('app.pending') }}', '{{ trans('app.paid') }}', '{{ trans('app.refunded') }}'],
                datasets: [{
                    data: [pending, paid, refunded],
                    backgroundColor: ['#ffc107', '#28a745', '#dc3545']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                legend: {position: 'bottom'},
                tooltips: reportChartMoneyTooltip()
            }
        };
    }

    function ajaxFire(ajaxUrl, params, handleData) {
        $.ajax({
            url: ajaxUrl + '/?' + params,
            method: 'get',
            contentType: 'application/json',
            success: function (response) {
                handleData(response.data);
            }
        });
    }

    function clearAllFilter() {
        $('#paymentMethod').val('');
        $('#paymentStatus').val('');
        $('#customerId').val(null).trigger('change');
        $('#shopId').val(null).trigger('change');
        $('#orderNumber').val('');
        refreshPaymentsReport();
    }

    function fireEventOnFilter() {
        refreshPaymentsReport();
    }
</script>
