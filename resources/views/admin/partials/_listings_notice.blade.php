@php
  $webUser = auth_web_user();
  $shop = $webUser?->shop;
@endphp

@if ($webUser && $shop)
  @if ($shop->isDown())
    @unless (Request::is('admin/setting/general*') || Request::is('merchant/setting/general*'))
      <div class="alert alert-error alert-dismissible no-print">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
        <strong><i class="icon fa fa-warning"></i>{{ trans('app.alert') }}</strong>
        {!! trans('messages.listings_not_visible', ['reason' => trans('messages.your_shop_in_maintenance_mode')]) !!}
        @if ($webUser->isMerchant())
          <span class="pull-right">
            <a href="{{ panel_route('admin.setting.config.general') }}" class="btn bg-navy"><i class="fa fa-rocket"></i> {{ trans('app.take_action') }}</a>
          </span>
        @endif
      </div>
    @endunless
  @elseif (! $shop->active)
    <div class="alert alert-error alert-dismissible no-print">
      <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
      <strong><i class="icon fa fa-warning"></i>{{ trans('app.alert') }}</strong>
      {!! trans('messages.your_shop_in_hold') !!}
    </div>
  @elseif (! $shop->hasPaymentMethods() && vendor_get_paid_directly())
    @unless (Request::is('admin/setting/paymentMethod*') || Request::is('merchant/setting/paymentMethod*'))
      <div class="alert alert-error alert-dismissible no-print">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
        <strong><i class="icon fa fa-warning"></i>{{ trans('app.alert') }}</strong>
        {!! trans('messages.listings_not_visible', ['reason' => trans('messages.no_active_payment_method')]) !!}
        @if ($webUser->isMerchant())
          <span class="pull-right">
            <a href="{{ panel_route('admin.setting.config.paymentMethod.index') }}" class="btn bg-navy"><i class="fa fa-rocket"></i> {{ trans('app.take_action') }}</a>
          </span>
        @endif
      </div>
    @endunless
  @elseif (! $shop->hasAddress())
    <div class="alert alert-warning alert-dismissible no-print">
      <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
      <strong><i class="icon fa fa-warning"></i>{{ trans('app.alert') }}</strong>
      {!! trans('messages.no_address_for_invoice') !!}
      @if ($webUser->isMerchant() && ! Request::is('admin/setting/general*') && ! Request::is('merchant/setting/general*'))
        <span class="pull-right">
          <a href="{{ panel_route('admin.setting.config.general') }}" class="btn bg-navy"><i class="fa fa-rocket"></i> {{ trans('app.take_action') }}</a>
        </span>
      @endif
    </div>
  @elseif (! $shop->hasShippingZones() && ! $webUser->isFromMerchant())
    @unless (Request::is('admin/shipping/shippingZone*') || Request::is('merchant/shipping/shippingZone*'))
      <div class="alert alert-warning alert-dismissible no-print">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
        <strong><i class="icon fa fa-warning"></i>{{ trans('app.alert') }}</strong>
        {!! trans('messages.no_active_shipping_zone') !!}
        @if ($webUser->isMerchant())
          <span class="pull-right">
            <a href="{{ panel_route('admin.shipping.shippingZone.index') }}" class="btn bg-navy"><i class="fa fa-rocket"></i> {{ trans('app.take_action') }}</a>
          </span>
        @endif
      </div>
    @endunless
  @endif
@endif
