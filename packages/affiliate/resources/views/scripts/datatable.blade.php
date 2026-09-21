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
        'name': 'email'
      },
      {
        'data': 'phone',
        'name': 'phone',
        'defaultContent': '—'
      },
      {
        'data': 'status',
        'name': 'active',
        'orderable': true,
        'searchable': false
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
