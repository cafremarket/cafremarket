@can('create', \App\Models\Coupon::class)
  <a href="javascript:void(0)" data-link="{{ route('admin.promotion.coupon.create') }}" class="ajax-modal-btn btn btn-new btn-flat">
    <i class="fa fa-plus"></i> {{ trans('app.add_coupon') }}
  </a>
@endcan
