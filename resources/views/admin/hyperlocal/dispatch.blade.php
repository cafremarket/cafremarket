@extends('admin.layouts.master')

@section('page_title')
  {{ trans('app.dispatch_dashboard') }}
@endsection

@section('content')
  @include('admin.partials.ui.card_start', [
    'title' => trans('app.dispatch_dashboard'),
    'icon' => 'fa-map',
  ])
    <div id="dispatch-map" style="height: 520px; border-radius: 8px; border: 1px solid #e5e7eb;"></div>
    <div class="row mt-3">
      <div class="col-md-6">
        <h4>{{ trans('app.online_platform_riders') }} ({{ $platformRiders->count() }})</h4>
        <ul class="list-group">
          @forelse ($platformRiders as $rider)
            <li class="list-group-item">{{ $rider->getName() }} <span class="text-muted pull-right">{{ optional($rider->last_location_at)->diffForHumans() }}</span></li>
          @empty
            <li class="list-group-item text-muted">{{ trans('app.no_platform_riders_online') }}</li>
          @endforelse
        </ul>
      </div>
      <div class="col-md-6">
        <h4>{{ trans('app.unassigned_orders') }} ({{ $unassignedOrders->count() }})</h4>
        <ul class="list-group">
          @forelse ($unassignedOrders as $order)
            <li class="list-group-item">
              <a href="{{ route('admin.order.order.show', $order->id) }}">#{{ $order->order_number }}</a>
              <span class="text-muted pull-right">{{ $order->shop->name ?? '' }}</span>
            </li>
          @empty
            <li class="list-group-item text-muted">{{ trans('app.no_unassigned_orders') }}</li>
          @endforelse
        </ul>
      </div>
    </div>
  @include('admin.partials.ui.card_end')

  @if (config('services.google.place_api_key'))
    <script>
      function initDispatchMap() {
        fetch('{{ route('admin.admin.hyperlocal.dispatch.data') }}')
          .then(function(r) { return r.json(); })
          .then(function(data) {
            var map = new google.maps.Map(document.getElementById('dispatch-map'), {
              zoom: 12,
              center: { lat: -25.9655, lng: 32.5832 },
            });

            (data.riders || []).forEach(function(rider) {
              new google.maps.Marker({
                position: { lat: parseFloat(rider.current_latitude), lng: parseFloat(rider.current_longitude) },
                map: map,
                title: rider.nice_name || rider.first_name,
                icon: 'http://maps.google.com/mapfiles/ms/icons/green-dot.png',
              });
            });

            (data.orders || []).forEach(function(order) {
              new google.maps.Marker({
                position: { lat: parseFloat(order.latitude), lng: parseFloat(order.longitude) },
                map: map,
                title: order.order_number,
                icon: 'http://maps.google.com/mapfiles/ms/icons/red-dot.png',
              });
            });
          });
      }
    </script>
    <script async defer src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google.place_api_key') }}&callback=initDispatchMap"></script>
  @endif
@endsection
