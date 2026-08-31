@can('create', \App\Models\Currency::class)
  <a href="javascript:void(0)" data-link="{{ route('admin.setting.currency.create') }}" class="ajax-modal-btn btn btn-new btn-flat">
    <i class="fa fa-plus"></i> {{ trans('app.add_currency') }}
  </a>
@endcan
