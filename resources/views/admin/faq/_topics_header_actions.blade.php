@can('create', \App\Models\Faq::class)
  <a href="javascript:void(0)" data-link="{{ route('admin.utility.faqTopic.create') }}" class="ajax-modal-btn btn btn-default btn-flat">
    <i class="fa fa-plus"></i> {{ trans('app.add_topic') }}
  </a>
@endcan
