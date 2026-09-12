{{-- Single modal: Delivery Boy OR Courier (exclusive). --}}
<div class="modal-dialog modal-md">
  <div class="modal-content">
    @php
      $defaultMethod = $order->hasCourier()
        ? 'courier'
        : 'delivery_boy';
      $isChange = $order->hasCourier() || $order->delivery_boy_id;
      $modalTitle = $order->hasCourier()
        ? trans('app.change_courier')
        : ($order->delivery_boy_id
          ? trans('app.change_deliveryboy')
          : trans('app.fulfill_order'));
    @endphp

    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
      {{ $modalTitle }}
    </div>
    <div class="modal-body">
      <div class="admin-fulfill-method-toggle btn-group btn-group-justified" data-toggle="buttons" style="margin-bottom:16px;">
        <label class="btn btn-default {{ $defaultMethod === 'delivery_boy' ? 'active' : '' }}">
          <input type="radio" name="fulfillment_method_ui" value="delivery_boy" autocomplete="off" {{ $defaultMethod === 'delivery_boy' ? 'checked' : '' }}>
          <i class="fa fa-motorcycle"></i> {{ trans('app.deliveryboy') }}
        </label>
        <label class="btn btn-default {{ $defaultMethod === 'courier' ? 'active' : '' }}">
          <input type="radio" name="fulfillment_method_ui" value="courier" autocomplete="off" {{ $defaultMethod === 'courier' ? 'checked' : '' }}>
          <i class="fa fa-truck"></i> {{ trans('app.courier') }}
        </label>
      </div>

      <div id="fulfill-panel-delivery-boy" style="{{ $defaultMethod === 'delivery_boy' ? '' : 'display:none;' }}">
        @if (($shopRidersAvailable ?? 0) > 0)
          {!! Form::open(['url' => panel_route('admin.order.deliveryboy.assign', $order, false), 'method' => 'post']) !!}
            <div class="form-group">
              <label>{{ trans('app.shop_riders') }}</label>
              {!! Form::select('delivery_boy_id', $deliveryboys, $order->delivery_boy_id, ['class' => 'form-control select2-in-modal', 'placeholder' => trans('app.placeholder.select'), 'required']) !!}
            </div>
            <button type="submit" class="btn btn-flat btn-new btn-block">{{ $isChange && $defaultMethod === 'delivery_boy' ? trans('app.change_deliveryboy') : trans('app.assign_shop_rider') }}</button>
          {!! Form::close() !!}
        @else
          <div class="alert alert-warning" style="margin-bottom:0;">{{ trans('app.no_shop_riders_online') }}</div>
        @endif
      </div>

      <div id="fulfill-panel-courier" style="{{ $defaultMethod === 'courier' ? '' : 'display:none;' }}">
        {!! Form::open(['url' => panel_route('admin.order.courier.assign', $order, false), 'method' => 'post']) !!}
          <div class="form-group">
            <label>{{ trans('app.courier_name') }}</label>
            {!! Form::text('courier_name', $order->courier_name, ['class' => 'form-control', 'required']) !!}
          </div>
          <div class="form-group">
            <label>{{ trans('app.courier_phone') }}</label>
            {!! Form::text('courier_phone', $order->courier_phone, ['class' => 'form-control', 'required']) !!}
          </div>
          <div class="form-group">
            <label>{{ trans('app.courier_tracking_number') }}</label>
            {!! Form::text('courier_tracking_number', $order->courier_tracking_number, ['class' => 'form-control']) !!}
          </div>
          <button type="submit" class="btn btn-flat btn-new btn-block">{{ $isChange && $defaultMethod === 'courier' ? trans('app.change_courier') : trans('app.save_courier_details') }}</button>
        {!! Form::close() !!}
      </div>
    </div>
  </div>
</div>

<script>
  (function () {
    var $modal = $('.modal:visible').last();
    if (!$modal.length) {
      $modal = $('#myModal, .modal').last();
    }
    $modal.find('input[name="fulfillment_method_ui"]').on('change', function () {
      var method = $(this).val();
      $modal.find('#fulfill-panel-delivery-boy').toggle(method === 'delivery_boy');
      $modal.find('#fulfill-panel-courier').toggle(method === 'courier');
    });
  })();
</script>
