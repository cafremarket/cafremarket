<div class="modal-dialog modal-md">
  <div class="modal-content">
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
      {{ trans('app.courier_details') }}
    </div>
    <div class="modal-body">
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
        <button type="submit" class="btn btn-flat btn-new btn-block">{{ trans('app.save_courier_details') }}</button>
      {!! Form::close() !!}
    </div>
  </div>
</div>
