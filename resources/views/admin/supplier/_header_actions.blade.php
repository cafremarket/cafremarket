@can('create', \App\Models\Supplier::class)
  <a href="javascript:void(0)" data-link="{{ route('admin.stock.supplier.create') }}" class="ajax-modal-btn btn btn-new btn-flat">
    <i class="fa fa-plus"></i> {{ trans('app.add_supplier') }}
  </a>
@endcan
