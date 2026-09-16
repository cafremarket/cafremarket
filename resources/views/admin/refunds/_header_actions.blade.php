@can('initiate', \App\Models\Refund::class)
  <a href="javascript:void(0)" data-link="{{ route('admin.refunds.form') }}" class="ajax-modal-btn btn btn-new btn-flat">
    <i class="fa fa-plus"></i> {{ trans('app.initiate_refund') }}
  </a>
@endcan
