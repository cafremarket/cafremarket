@can('create', \App\Models\Customer::class)
  <a href="javascript:void(0)" data-link="{{ route('admin.admin.customer.bulk') }}" class="ajax-modal-btn btn btn-default btn-flat">
    <i class="fa fa-upload"></i> {{ trans('app.bulk_import') }}
  </a>
  <a href="javascript:void(0)" data-link="{{ route('admin.admin.customer.create') }}" class="ajax-modal-btn btn btn-new btn-flat">
    <i class="fa fa-plus"></i> {{ trans('app.add_customer') }}
  </a>
@endcan
