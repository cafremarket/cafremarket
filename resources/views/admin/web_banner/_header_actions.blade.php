@can('create', \App\Models\Banner::class)
  <a href="javascript:void(0)" data-link="{{ route('admin.web_banner.create') }}" class="ajax-modal-btn btn btn-new btn-flat">
    <i class="fa fa-plus"></i> {{ trans('app.add_web_banner') }}
  </a>
@endcan
