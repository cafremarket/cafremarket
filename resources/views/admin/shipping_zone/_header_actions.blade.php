@can('create', \App\Models\ShippingZone::class)
  <a href="javascript:void(0)" data-link="{{ route('admin.shipping.shippingZone.create') }}" class="ajax-modal-btn btn btn-new btn-flat">
    <i class="fa fa-plus"></i> {{ trans('app.add_shipping_zone') }}
  </a>
@endcan
