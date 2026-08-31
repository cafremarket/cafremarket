@can('create', \App\Models\Country::class)
  <a href="javascript:void(0)" data-link="{{ route('admin.setting.country.create') }}" class="ajax-modal-btn btn btn-new btn-flat">
    <i class="fa fa-plus"></i> {{ trans('app.add_country') }}
  </a>
@endcan
