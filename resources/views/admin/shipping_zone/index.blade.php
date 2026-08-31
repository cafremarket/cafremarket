@extends('admin.layouts.master')

@section('page_title')
  {{ trans('app.shipping_zones') }}
@endsection

@section('content')
  @include('admin.partials.ui.card_start', [
    'title' => trans('app.shipping_zones'),
    'icon' => 'fa-truck',
    'actions' => view('admin.shipping_zone._header_actions')->render(),
    'bodyClass' => '',
  ])

  @forelse($shipping_zones as $shipping_zone)
    <div class="admin-shipping-zone">
      <div class="admin-shipping-zone__header">
        <div class="admin-shipping-zone__title">
          <i class="fa fa-{{ $shipping_zone->rest_of_the_world ? 'globe' : 'map-marker' }}"></i>
          {{ $shipping_zone->name }}
          @if ($shipping_zone->rest_of_the_world)
            <span class="label label-outline">{{ trans('app.rest_of_the_world') }}</span>
          @endif
          @unless ($shipping_zone->active)
            <span class="label label-default">{{ trans('app.inactive') }}</span>
          @endunless
          <span class="text-muted small">{{ $shipping_zone->tax->name . ' (' . $shipping_zone->tax->label . ')' }}</span>
        </div>
        <div class="admin-shipping-zone__actions">
          @can('create', \App\Models\ShippingRate::class)
            <div class="btn-group">
              <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">
                <i class="fa fa-plus-square-o"></i> {{ trans('app.add_shipping_rate') }} <span class="caret"></span>
              </button>
              <ul class="dropdown-menu">
                <li><a href="javascript:void(0)" data-link="{{ route('admin.shipping.shippingRate.create', [$shipping_zone->id, 'price']) }}" class="ajax-modal-btn"><i class="fa fa-money"></i> {{ trans('app.add_price_based_rate') }}</a></li>
                <li role="separator" class="divider"></li>
                <li><a href="javascript:void(0)" data-link="{{ route('admin.shipping.shippingRate.create', [$shipping_zone->id, 'weight']) }}" class="ajax-modal-btn"><i class="fa fa-balance-scale"></i> {{ trans('app.add_weight_based_rate') }}</a></li>
              </ul>
            </div>
          @endcan
          @unless ($shipping_zone->rest_of_the_world)
            @can('create', \App\Models\ShippingZone::class)
              <a href="javascript:void(0)" data-link="{{ route('admin.shipping.shippingZone.edit', $shipping_zone->id) }}" class="ajax-modal-btn btn btn-default btn-sm btn-flat">
                <i class="fa fa-plus-square-o"></i> {{ trans('app.add_shipping_country') }}
              </a>
            @endcan
          @endunless
          @can('update', $shipping_zone)
            <a href="javascript:void(0)" data-link="{{ route('admin.shipping.shippingZone.edit', $shipping_zone->id) }}" class="ajax-modal-btn btn btn-default btn-sm btn-flat"><i class="fa fa-edit"></i> {{ trans('app.edit') }}</a>
          @endcan
          @can('delete', $shipping_zone)
            {!! Form::open(['route' => ['admin.shipping.shippingZone.destroy', $shipping_zone->id], 'method' => 'delete', 'class' => 'admin-inline-form']) !!}
            <button type="submit" class="confirm btn btn-danger btn-sm btn-flat"><i class="fa fa-trash-o"></i> {{ trans('app.delete') }}</button>
            {!! Form::close() !!}
          @endcan
        </div>
      </div>

      <div class="row">
        <div class="col-sm-6">
          <h5 class="admin-shipping-zone__section-title">{{ trans('app.countries') }}</h5>
          <ul class="list-group admin-list-group">
            @if ($shipping_zone->rest_of_the_world)
              <li class="list-group-item">{{ trans('help.rest_of_the_world') }}</li>
            @elseif (!empty($shipping_zone->country_ids))
              @php $countries = get_countries_in_shipping_zone($shipping_zone); @endphp
              @foreach ($countries as $country)
                <li class="list-group-item {{ $country->in_active_business_area ? '' : 'disabled' }}">
                  <div class="admin-list-group__item-head">
                    {!! get_formated_country_name($country->name, $country->iso_code) !!}
                    @unless ($country->in_active_business_area)
                      <span class="label label-outline" data-toggle="tooltip" title="{{ trans('help.not_in_business_area') }}">{{ trans('app.not_in_business_area') }}</span>
                    @endunless
                    {!! Form::open(['route' => ['admin.shipping.shippingZone.removeCountry', $shipping_zone->id, $country->id], 'method' => 'delete', 'class' => 'data-form admin-inline-form pull-right']) !!}
                    <button type="submit" class="confirm ajax-silent admin-action-btn" title="{{ trans('app.remove') }}" data-toggle="tooltip"><i class="fa fa-times-circle"></i></button>
                    {!! Form::close() !!}
                  </div>
                  @if ($country->states_count)
                    <p class="list-group-item-text small text-muted">
                      {{ trans('app._of_states', ['states' => $shipping_zone->state_ids ? count(array_intersect($shipping_zone->state_ids, $country->states->pluck('id')->toArray())) : '0', 'allStates' => $country->states_count]) }}
                      @if ($country->in_active_business_area)
                        <a href="javascript:void(0)" data-link="{{ route('admin.shipping.shippingZone.editStates', [$shipping_zone->id, $country->id]) }}" class="ajax-modal-btn pull-right"><i class="fa fa-edit"></i> {{ trans('app.edit') }}</a>
                      @endif
                    </p>
                  @endif
                </li>
              @endforeach
            @else
              <li class="list-group-item text-muted">{{ trans('app.empty_shipping_country') }}</li>
            @endif
          </ul>
        </div>

        <div class="col-sm-6">
          <h5 class="admin-shipping-zone__section-title">{{ trans('app.shipping_rates') }}</h5>
          <ul class="list-group admin-list-group">
            @forelse($shipping_zone->rates as $shipping)
              <li class="list-group-item">
                <div class="admin-list-group__item-head">
                  <strong>{{ $shipping->name }}</strong>
                  @if ($shipping->carrier)
                    <small class="text-muted">{{ trans('app.by') . ' ' . $shipping->carrier->name . ' ' . trans('app.and_takes', ['time' => $shipping->delivery_takes]) }}</small>
                  @endif
                  @can('delete', $shipping)
                    {!! Form::open(['route' => ['admin.shipping.shippingRate.destroy', $shipping->id], 'method' => 'delete', 'class' => 'data-form admin-inline-form pull-right']) !!}
                    <button type="submit" class="confirm ajax-silent admin-action-btn" title="{{ trans('app.delete') }}" data-toggle="tooltip"><i class="fa fa-times-circle"></i></button>
                    {!! Form::close() !!}
                  @endcan
                </div>
                <p class="list-group-item-text small">
                  {{ get_formated_shipping_range_of($shipping) }}
                  <span class="badge">{{ $shipping->rate > 0 ? get_formated_currency($shipping->rate, 2, config('system_settings.currency.id')) : trans('app.free') }}</span>
                  @can('update', $shipping)
                    <a href="javascript:void(0)" data-link="{{ route('admin.shipping.shippingRate.edit', $shipping->id) }}" class="ajax-modal-btn pull-right"><i class="fa fa-edit"></i> {{ trans('app.edit') }}</a>
                  @endcan
                </p>
              </li>
            @empty
              <li class="list-group-item text-muted">{{ trans('app.empty_shipping_rates') }}</li>
            @endforelse
          </ul>
        </div>
      </div>
    </div>
    @unless ($loop->last)
      <hr class="admin-divider">
    @endunless
  @empty
    <div class="admin-empty-state">
      <i class="fa fa-truck"></i>
      <p>{{ trans('app.empty_shipping_zones') }}</p>
    </div>
  @endforelse

  @include('admin.partials.ui.card_end')
@endsection
