@if (Gate::allows('create', \App\Models\Order::class) || Gate::allows('create', \App\Models\Cart::class))
  <a href="javascript:void(0)" data-link="{{ route('admin.order.order.searchCustomer') }}" class="ajax-modal-btn btn btn-new btn-flat">
    <i class="fa fa-plus"></i> {{ trans('app.add_order') }}
  </a>
@endif
