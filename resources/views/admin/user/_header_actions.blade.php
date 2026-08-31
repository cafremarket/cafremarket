@can('create', \App\Models\User::class)
  <a href="javascript:void(0)" data-link="{{ route('admin.admin.user.create') }}" class="ajax-modal-btn btn btn-new btn-flat">
    <i class="fa fa-plus"></i> {{ trans('app.add_user') }}
  </a>
@endcan
