@can('create', \App\Models\Tax::class)
  <a href="javascript:void(0)" data-link="{{ route('admin.setting.tax.create') }}" class="ajax-modal-btn btn btn-new btn-flat">
    <i class="fa fa-plus"></i> {{ trans('app.add_tax') }}
  </a>
@endcan
