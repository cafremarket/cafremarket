@extends('admin.layouts.master')

@section('page_title')
  {{ trans('app.visitors') }}
@endsection

@section('content')
  @include('admin.partials.ui.card_start', [
    'title' => trans('app.visitors'),
    'icon' => 'fa-globe',
  ])

  <table class="table table-hover admin-table" id="all-visitors-table">
    <thead>
      <tr>
        <th>{{ trans('app.flag') }}</th>
        <th>{{ trans('app.ip') }}</th>
        <th>{{ trans('app.hits') }}</th>
        <th>{{ trans('app.page_views') }}</th>
        <th>{{ trans('app.last_visits') }}</th>
        <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
      </tr>
    </thead>
    <tbody></tbody>
  </table>

  @include('admin.partials.ui.card_end')
@endsection

@section('page-script')
  <script type="text/javascript">
    $('#all-visitors-table').DataTable({
      "aaSorting": [[2, "desc"]],
      "iDisplayLength": {{ getPaginationValue() }},
      "processing": true,
      "serverSide": true,
      "ajax": "{{ route('admin.report.visitors.getMore') }}",
      "columns": [
        { data: 'flag', name: 'flag', searchable: false },
        { data: 'ip', name: 'ip' },
        { data: 'hits', name: 'hits', searchable: false },
        { data: 'page_views', name: 'page_views', searchable: false },
        { data: 'last_visits', name: 'last_visits', searchable: false },
        { data: 'option', name: 'option', orderable: false, searchable: false, exportable: false, printable: false }
      ],
      "oLanguage": {
        "sInfo": "_START_ to _END_ of _TOTAL_ entries",
        "sLengthMenu": "Show _MENU_",
        "sSearch": "",
        "sEmptyTable": "No data found!",
        "oPaginate": {
          "sNext": '<i class="fa fa-hand-o-right"></i>',
          "sPrevious": '<i class="fa fa-hand-o-left"></i>'
        }
      },
      "aoColumnDefs": [{ "bSortable": false, "aTargets": [-1] }],
      "lengthMenu": [[10, 25, 50, -1], ['10 rows', '25 rows', '50 rows', 'Show all']],
      dom: 'Bfrtip',
      buttons: ['pageLength', 'copy', 'csv', 'excel', 'pdf', 'print']
    });
  </script>
@endsection
