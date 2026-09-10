<div class="modal-dialog modal-md">
  <div class="modal-content">
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
      {{ trans('app.assign_deliveryboy') }}
    </div>
    <div class="modal-body">
      @if (($shopRidersAvailable ?? 0) > 0)
        {!! Form::open(['url' => panel_route('admin.order.deliveryboy.assign', $order, false), 'method' => 'post']) !!}
          <div class="form-group">
            <label>{{ trans('app.shop_riders') }}</label>
            {!! Form::select('delivery_boy_id', $deliveryboys, $order->delivery_boy_id, ['class' => 'form-control select2', 'placeholder' => trans('app.placeholder.select'), 'required']) !!}
          </div>
          <button type="submit" class="btn btn-flat btn-new btn-block">{{ trans('app.assign_shop_rider') }}</button>
        {!! Form::close() !!}
      @else
        <div class="alert alert-warning">{{ trans('app.no_shop_riders_online') }}</div>
      @endif
    </div>
  </div>
</div>
