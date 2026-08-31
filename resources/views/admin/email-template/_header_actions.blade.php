@can('create', \App\Models\EmailTemplate::class)
  <a href="javascript:void(0)" data-link="{{ route('admin.utility.emailTemplate.create') }}" class="ajax-modal-btn btn btn-new btn-flat">
    <i class="fa fa-plus"></i> {{ trans('app.add_template') }}
  </a>
@endcan
