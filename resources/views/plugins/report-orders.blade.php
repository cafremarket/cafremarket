<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css"/>
<script src="/js/chartjs.js" charset=utf-8></script>

<script type="text/javascript">
    var salesCtx;
    var generate;
    var reportDefaultDays = {{ (int) config('report.sales.default', 7) }};

    function buildOrdersFilterString() {
        return "paymentStatus=" + encodeURIComponent($('#paymentStatus').val() || '') +
            "&customerId=" + encodeURIComponent($('#customerId').val() || '') +
            "&shopId=" + encodeURIComponent($('#shopId').val() || '') +
            "&orderNumber=" + encodeURIComponent($('#orderNumber').val() || '') +
            "&orderStatus=" + encodeURIComponent($('#orderStatus').val() || '') +
            "&fromDate=" + encodeURIComponent($('#getFromDate').val()) +
            "&toDate=" + encodeURIComponent($('#getToDate').val());
    }

    function refreshOrdersReport() {
        var dataString = buildOrdersFilterString();
        dataTableResetting(dataString);
        ajaxFire('{{ route('admin.sales.getMoreForChart') }}', dataString, function (output) {
            generate.clear();
            generate.destroy();
            generate = new Chart(salesCtx, chartDataFormat(output));
        });
    }

    ;(function ($) {
        var jsonData = @json($chartDataArray);
        var chartFormatData = chartDataFormat(jsonData);
        salesCtx = document.getElementById('salesReport').getContext('2d');
        generate = new Chart(salesCtx, chartFormatData);

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
                refreshOrdersReport();
            });

            $('#daterangepicker span').html(startDefault.format('D MMMM YYYY') + ' - ' + endDefault.format('D MMMM YYYY'));
            $('#getFromDate').val(startDefault.format('YYYY-MM-DD'));
            $('#getToDate').val(endDefault.format('YYYY-MM-DD'));

            refreshOrdersReport();
        });
    }(window.jQuery));

    function dataTableResetting(dataString) {
        var table = $('.orders-report-table');
        if ($.fn.dataTable.isDataTable(table)) {
            table.DataTable().destroy();
        }

        table.DataTable({
            responsive: true,
            iDisplayLength: {{ getPaginationValue() }},
            ajax: {
                url: '{{ route('admin.sales.getMore') }}/?' + dataString,
                dataSrc: function (json) {
                    if (json.summary) {
                        updateReportSummary(json.summary);
                    }
                    return json.data;
                }
            },
            columns: [
                {data: 'date', name: 'date'},
                {data: 'delivery_date', name: 'delivery_date'},
                {data: 'order_number', name: 'order_number'},
                {data: 'customer', name: 'customer'},
                @if (!Auth::user()->isMerchant())
                {data: 'shop', name: 'shop'},
                @endif
                {data: 'quantity', name: 'quantity', render: function (data) { return formatReportInteger(data); }},
                {data: 'payment_method', name: 'payment_method'},
                {data: 'payment_status_name', name: 'payment_status'},
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
        var awaitingDelivery = [];
        var awaitingPayment = [];
        var canceled = [];
        var paymentError = [];
        var returned = [];
        var fulfilled = [];
        var confirmed = [];
        var delivered = [];
        var disputed = [];

        for (var i = 0; i < chartData.length; i++) {
            labelData.push(chartData[i].date);
            awaitingDelivery.push(chartData[i].awaiting_delivery || 0);
            awaitingPayment.push(chartData[i].awaiting_payment || 0);
            canceled.push(chartData[i].canceled || 0);
            paymentError.push(chartData[i].payment_error || 0);
            returned.push(chartData[i].returned || 0);
            fulfilled.push(chartData[i].fulfilled || 0);
            confirmed.push(chartData[i].confirmed || 0);
            delivered.push(chartData[i].delivered || 0);
            disputed.push(chartData[i].disputed || 0);
        }

        return {
            type: 'bar',
            data: {
                labels: labelData,
                datasets: [
                    {label: '{{ trans('app.awaiting_delivery') }}', backgroundColor: '#d238aa', data: awaitingDelivery},
                    {label: '{{ trans('app.waiting_for_payment') }}', backgroundColor: '#FFA500', data: awaitingPayment},
                    {label: '{{ trans('app.canceled') }}', backgroundColor: '#FFFF00', data: canceled},
                    {label: '{{ trans('app.payment_error') }}', backgroundColor: '#fb5a2a', data: paymentError},
                    {label: '{{ trans('app.returns') }}', backgroundColor: '#353535', data: returned},
                    {label: '{{ trans('app.fulfilled') }}', backgroundColor: '#337ab7', data: fulfilled},
                    {label: '{{ trans('app.confirmed') }}', backgroundColor: '#00c0ef', data: confirmed},
                    {label: '{{ trans('app.delivered') }}', backgroundColor: '#00a65a', data: delivered},
                    {label: '{{ trans('app.disputed') }}', backgroundColor: '#da1a07', data: disputed},
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                legend: {position: 'bottom'},
                scales: {
                    x: {stacked: true},
                    y: {stacked: true, ticks: {beginAtZero: true, precision: 0}}
                }
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
        $("#customerId").val(null).trigger('change');
        $('#shopId').val(null).trigger('change');
        $('#orderNumber').val('');
        $('#orderStatus').val('');
        $('#paymentStatus').val('');
        refreshOrdersReport();
    }

    function fireEventOnFilter() {
        refreshOrdersReport();
    }
</script>
