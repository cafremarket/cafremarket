@can('create', \App\Models\Faq::class)
  <a href="javascript:void(0)" data-link="{{ route('admin.utility.faq.create') }}" class="ajax-modal-btn btn btn-new btn-flat">
    <i class="fa fa-plus"></i> {{ trans('app.add_faq') }}
  </a>
@endcan
