<div class="modal-dialog modal-md">
  <div class="modal-content">
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
      {{ trans('app.assign_deliveryboy') }}
    </div>
    <div class="modal-body">
      @if (($shopRidersAvailable ?? 0) > 0)
        {!! Form::open(['route' => ['admin.order.deliveryboy.assign', $order], 'method' => 'post']) !!}
          <div class="form-group">
            <label>{{ trans('app.shop_riders') }}</label>
            {!! Form::select('delivery_boy_id', $deliveryboys, $order->delivery_boy_id, ['class' => 'form-control select2', 'placeholder' => trans('app.placeholder.select'), 'required']) !!}
          </div>
          <button type="submit" class="btn btn-flat btn-new btn-block">{{ trans('app.assign_shop_rider') }}</button>
        {!! Form::close() !!}
      @else
        <div class="alert alert-warning">{{ trans('app.no_shop_riders_online') }}</div>
      @endif

      @if ($order->shop->supportsSystemDelivery())
        <hr>
        <label>{{ trans('app.platform_delivery') }}</label>

        @if (($platformRiders ?? collect())->isNotEmpty())
          <ul class="list-group">
            @foreach ($platformRiders as $rider)
              <li class="list-group-item clearfix">
                {{ $rider->nice_name ?: $rider->getName() }}
                <span class="text-muted">({{ number_format($rider->distance_km, 1) }} km)</span>
                {!! Form::open(['route' => ['admin.order.platform_delivery.request', $order], 'method' => 'post', 'class' => 'pull-right']) !!}
                  <input type="hidden" name="platform_rider_id" value="{{ $rider->id }}">
                  <button type="submit" class="btn btn-xs btn-primary">{{ trans('app.assign') }}</button>
                {!! Form::close() !!}
              </li>
            @endforeach
          </ul>
        @endif

        {!! Form::open(['route' => ['admin.order.platform_delivery.request', $order], 'method' => 'post', 'class' => 'mt-3']) !!}
          <button type="submit" class="btn btn-warning btn-block btn-flat">{{ trans('app.request_platform_delivery') }}</button>
        {!! Form::close() !!}
      @endif

      @if ($order->delivery_mode)
        <p class="mt-3">
          <span class="label label-info">{{ trans('app.delivery_mode_' . $order->delivery_mode) }}</span>
        </p>
      @endif
    </div>
  </div>
</div>
