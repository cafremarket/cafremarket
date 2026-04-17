$('#all-affiliates-table').DataTable($.extend({}, dataTableOptions, {
    "ajax": "{{ route('admin.affiliate.getAffiliates') }}",
    "columns": [{
        'data': 'checkbox',
        'name': 'checkbox',
        'orderable': false,
        'searchable': false,
        'exportable': false,
        'printable': false
      },
      {
        'data': 'name',
        'name': 'name'
      },
      {
        'data': 'email',
        'name': 'email',
        'orderable': false,
      },
      {
        'data': 'option',
        'name': 'option',
        'orderable': false,
        'searchable': false,
        'exportable': false,
        'printable': false
      }
    ]
  }));