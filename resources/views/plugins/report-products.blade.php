<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css"/>

<script type="text/javascript">
    var reportDefaultDays = {{ (int) config('report.sales.default', 7) }};

    function buildProductsFilterString() {
        return "fromDate=" + encodeURIComponent($('#getFromDate').val()) +
            "&toDate=" + encodeURIComponent($('#getToDate').val()) +
            "&productId=" + encodeURIComponent($('#productId').val() || '') +
            "&shopId=" + encodeURIComponent($('#shopId').val() || '');
    }

    function refreshProductsReport() {
        dataTableResetting(buildProductsFilterString());
    }

    ;(function ($) {
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
                refreshProductsReport();
            });

            $('#daterangepicker span').html(startDefault.format('D MMMM YYYY') + ' - ' + endDefault.format('D MMMM YYYY'));
            $('#getFromDate').val(startDefault.format('YYYY-MM-DD'));
            $('#getToDate').val(endDefault.format('YYYY-MM-DD'));

            refreshProductsReport();
        });
    }(window.jQuery));

    function dataTableResetting(dataString) {
        var table = $('.products-report-table');
        if ($.fn.dataTable.isDataTable(table)) {
            table.DataTable().destroy();
        }

        table.DataTable({
            responsive: true,
            iDisplayLength: {{ getPaginationValue() }},
            ajax: {
                url: '{{ route('admin.sales.products.getMore') }}/?' + dataString,
                dataSrc: function (json) {
                    if (json.summary) {
                        updateReportSummary(json.summary);
                    }
                    return json.data;
                }
            },
            columns: [
                {data: 'name', name: 'name'},
                {data: 'model_number', name: 'model_number'},
                {
                    data: null,
                    name: 'gtin',
                    render: function (data) {
                        return (data.gtin_type ? data.gtin_type + ' ' : '') + (data.gtin || '');
                    }
                },
                {data: 'quantity', name: 'quantity', render: function (data) { return formatReportInteger(data); }},
                {data: 'uniquePurchase', name: 'uniquePurchase', render: function (data) { return formatReportInteger(data); }},
                {
                    data: 'avgPrice_formatted',
                    name: 'avgPrice',
                    className: 'text-right',
                    render: function (data, type, row) {
                        return data || formatReportMoney(row.avgPrice);
                    }
                },
                {
                    data: 'totalSale_formatted',
                    name: 'totalSale',
                    className: 'text-right',
                    render: function (data, type, row) {
                        return data || formatReportMoney(row.totalSale);
                    }
                },
            ],
            dom: 'Bfrtip',
            buttons: ['copy', 'csv', 'excel', 'pdf', 'print']
        });
    }

    function clearAllFilter() {
        $('#productId').val(null).trigger('change');
        $('#shopId').val(null).trigger('change');
        refreshProductsReport();
    }

    function fireEventOnFilter() {
        refreshProductsReport();
    }
</script>
