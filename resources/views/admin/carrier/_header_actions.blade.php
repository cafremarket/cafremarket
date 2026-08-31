@if (is_incevio_package_loaded('shippo'))
  @include('shippo::_btn_fetch_carriers')
@endif

@can('create', \App\Models\Carrier::class)
  <a href="javascript:void(0)" data-link="{{ route('admin.shipping.carrier.create') }}" class="ajax-modal-btn btn btn-new btn-flat">
    <i class="fa fa-plus"></i> {{ trans('app.add_carrier') }}
  </a>
@endcan
