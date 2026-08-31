@can('create', \App\Models\Role::class)
  <a href="javascript:void(0)" data-link="{{ route('admin.setting.role.create') }}" class="ajax-modal-btn btn btn-new btn-flat">
    <i class="fa fa-plus"></i> {{ trans('app.add_role') }}
  </a>
@endcan
