@can('create', \App\Models\Warehouse::class)
  <a href="javascript:void(0)" data-link="{{ route('admin.stock.warehouse.create') }}" class="ajax-modal-btn btn btn-new btn-flat">
    <i class="fa fa-plus"></i> {{ trans('app.add_warehouse') }}
  </a>
@endcan
