@extends('admin.layouts.master')

@section('page_title')
  {{ trans('app.order') }} #{{ $order->order_number }}
@endsection

@section('content')
  <div class="row admin-order-detail">
    <div class="col-md-8">
      @if ($order->cancellation)
        @include('admin.partials.ui.card_start', [
          'title' => trans('app.' . $order->cancellation->request_type . '_request'),
          'icon' => 'fa-exclamation-triangle',
          'class' => 'admin-card--warning',
          'actions' => $order->cancellation->statusName(),
        ])
            <div class="row">
              <div class="col-sm-8">
                <p>
                  <strong>@lang('app.reason'):</strong>
                  {!! $order->cancellation->reason !!}
                </p>

                @if ($order->cancellation->description)
                  <p>
                    <strong>@lang('app.detail'):</strong>
                    {{ $order->cancellation->description ?? '' }}
                  </p>
                @endif

                <strong>{{ trans('app.requested_items') }}:</strong>
              </div>
              <div class="col-sm-4 text-right">
                @can('cancel', $order)
                  @if ($order->cancellation->isNew())
                    {!! Form::open(['route' => ['admin.order.cancellation.handle', $order, 'approve'], 'method' => 'put', 'class' => 'form-inline indent5']) !!}
                    <button class="btn btn-default-outline btn-sm confirm" type="submit">
                      <i class="fa fa-check"></i>
                      {{ trans('app.approve') }}
                    </button>
                    {!! Form::close() !!}

                    {!! Form::open(['route' => ['admin.order.cancellation.handle', $order, 'decline'], 'method' => 'put', 'class' => 'form-inline indent5']) !!}
                    <button class="btn btn-danger btn-sm confirm" type="submit">
                      <i class="fa fa-times"></i>
                      {{ trans('app.decline') }}
                    </button>
                    {!! Form::close() !!}
                  @endif

                  @if ($order->cancellation->inReview())
                    @if (Auth::user()->isFromPlatform())
                      <a href="javascript:void(0)" data-link="{{ route('admin.order.cancellation.create', $order) }}" class='ajax-modal-btn btn btn-default btn-sm'>
                        {{ trans('app.approve') }}
                      </a>
                    @else
                      <span class="label label-info">{!! trans('app.waiting_for_approval') !!}</span>
                    @endif
                  @endif
                @endcan
              </div>

              <span class="spacer10"></span>

              <div class="col-sm-12">
                <table class="table table-striped admin-table">
                  <tbody id="items">
                    @if ($order->cancellation->isPartial())
                      @foreach ($order->inventories as $item)
                        @if (in_array($item->id, $order->cancellation->items))
                          <tr>
                            <td>
                              <img src="{{ get_product_img_src($item, 'tiny') }}" class="img-circle img-md" alt="{{ trans('app.image') }}">
                            </td>
                            <td class="nopadding-right" width="55%">
                              {{ $item->pivot->item_description }}
                              @unless ($item->is_chat_custom)
                                <a href="{{ storefront_product_url($item) }}" target="_blank" class="indent5 small"><i class=" fa fa-external-link"></i></a>
                              @endunless
                            </td>
                            <td class="nopadding-right" width="15%">
                              {{ get_formated_currency($item->pivot->unit_price, 2, $order->currency_id) }}
                            </td>
                            <td>&times;</td>
                            <td class="nopadding-right" width="10%">
                              {{ $item->pivot->quantity }}
                            </td>
                            <td class="nopadding-right text-center" width="10%">
                              {{ get_formated_currency($item->pivot->quantity * $item->pivot->unit_price, 2, $order->currency_id) }}
                            </td>
                          </tr>
                        @endif
                      @endforeach
                    @else
                      <tr id="empty-cart">
                        <td colspan="6">{{ trans('app.all_items') }}</td>
                      </tr>
                    @endif
                  </tbody>
                </table>
              </div> <!-- /.col-* -->
            </div> <!-- /.row -->
        @include('admin.partials.ui.card_end')
      @endif

      @if (is_incevio_package_loaded('affiliate') && isset($commissions))
        @include('affiliate::admin._order_page_commission_table', ['commissions' => $commissions, 'order' => $order])
      @endif

      @php
        $orderHeaderExtra = $order->dispute
          ? '<span class="label label-danger">' . e(trans('app.statuses.disputed')) . '</span>'
          : '';
        $orderHeaderActions = $order->orderStatus();
      @endphp

      @include('admin.partials.ui.card_start', [
        'title' => trans('app.order') . ': ' . $order->order_number,
        'icon' => 'fa-shopping-cart',
        'headerExtra' => $orderHeaderExtra,
        'actions' => $orderHeaderActions,
        'bodyClass' => 'admin-order-detail__main',
      ])
          <div class="admin-order-payment-bar">
            <span class="admin-order-payment-bar__method">
              {{ trans('app.payment') . ': ' . $order->paymentMethod->name }}
            </span>
            <span class="admin-order-payment-bar__status">
              {!! $order->paymentStatusName() !!}
            </span>
          </div>

          <div class="row">
            <div class="col-md-12">
              <h4 class="admin-order-section-title">{{ trans('app.order_details') }}

                @if ($order->auction_bid_id)
                  <span class="label label-primary ml-2"><i class="fa fa-gavel"></i> {{ trans('packages.auction.winner') }}</span>
                @endif
              </h4>
              <span class="spacer10"></span>

              <table class="table table-striped admin-table">
                <tbody id="items">
                  @if (count($order->inventories) > 0)
                    @foreach ($order->inventories as $item)
                      <tr>
                        <td>
                          <img src="{{ get_product_img_src($item, 'tiny') }}" class="img-circle img-md" alt="{{ trans('app.image') }}">
                        </td>
                        <td class="nopadding-right" width="55%">
                          {{ $item->pivot->item_description }}
                          @unless ($item->is_chat_custom)
                            <a href="{{ storefront_product_url($item) }}" target="_blank" class="indent5 small"><i class=" fa fa-external-link"></i></a>
                          @endunless
                        </td>
                        <td class="nopadding-right text-right " width="15%">
                          {{ get_formated_currency($item->pivot->unit_price, 2, $order->currency_id) }}
                        </td>
                        <td>&times;</td>
                        <td class="nopadding text-left" width="10%">
                          {{ $item->pivot->quantity }}
                        </td>
                        <td class="nopadding-right text-center">
                          {{ get_formated_currency($item->pivot->quantity * $item->pivot->unit_price, 2, $order->currency_id) }}
                        </td>
                      </tr>
                    @endforeach
                  @else
                    <tr id="empty-cart">
                      <td colspan="6">{{ trans('help.empty_cart') }}</td>
                    </tr>
                  @endif
                </tbody>
              </table>
            </div>
          </div><!-- /.row -->

          <span class="spacer30"></span>

          <div class="row">
            <div class="col-md-6">
              <div class="spacer30"></div>
              @if ($order->buyer_note)
                {{ trans('app.buyer_note') }}:
                <blockquote>
                  {{ $order->buyer_note }}
                </blockquote>
              @endif

              <div class="spacer30"></div>
              @if ($order->admin_note)
                {{ trans('app.admin_note') }}:

                @can('fulfill', $order)
                  <a href="javascript:void(0)" data-link="{{ route('admin.order.order.adminNote', $order) }}" class="ajax-modal-btn btn btn-link">
                    {{ trans('app.edit') }}
                  </a>
                @endcan

                <blockquote>
                  {!! $order->admin_note !!}
                </blockquote>
              @else
                @can('fulfill', $order)
                  <dir class="spacer20"></dir>
                  <a href="javascript:void(0)" data-link="{{ route('admin.order.order.adminNote', $order) }}" class="ajax-modal-btn btn btn-link">
                    {{ trans('app.add_admin_note') }}
                  </a>
                @endcan
              @endif
            </div>
            <div class="col-md-6" id="summary-block">
              <table class="table admin-order-summary">
                <tr>
                  <td class="text-right">{{ trans('app.total') }}</td>
                  <td class="text-right" width="40%">
                    {{ get_formated_currency($order->total, 2, $order->currency_id) }}
                  </td>
                </tr>

                <tr>
                  <td class="text-right">
                    <span>{{ trans('app.discount') }}</span>
                  </td>
                  <td class="text-right" width="40%"> &minus;
                    {{ get_formated_currency($order->discount, 2, $order->currency_id) }}
                  </td>
                </tr>

                <tr>
                  <td class="text-right">
                    <span>{{ trans('app.shipping') }}</span><br />
                    <em class="small">
                      @if ($order->shippingRate)
                        {{ optional($order->shippingRate)->name }}
                        @php
                          $carrier_name = $order->carrier ? $order->carrier->name : ($order->shippingRate ? optional($order->shippingRate->carrier)->name : null);
                        @endphp
                        @if ($carrier_name)
                          <small> {{ trans('app.by') . ' ' . $carrier_name }} </small>
                        @endif
                      @else
                        {{ trans('app.custom_shipping') }}
                      @endif
                    </em>
                  </td>
                  <td class="text-right" width="40%">
                    {{ get_formated_currency($order->shipping, 2, $order->currency_id) }}
                  </td>
                </tr>

                @if (is_incevio_package_loaded('packaging') && $order->shippingPackage)
                  <tr>
                    <td class="text-right">
                      <span>{{ trans('app.packaging') }}</span><br />
                      <em class="small">{{ optional($order->shippingPackage)->name }}</em>
                    </td>
                    <td class="text-right" width="40%">
                      {{ get_formated_currency($order->packaging, 2, $order->currency_id) }}
                    </td>
                  </tr>
                @endif

                @if ($order->handling)
                  <tr>
                    <td class="text-right">{{ trans('app.handling') }}</td>
                    <td class="text-right" width="40%">
                      {{ get_formated_currency($order->handling, 2, $order->currency_id) }}
                    </td>
                  </tr>
                @endif

                <tr>
                  <td class="text-right">{{ trans('app.taxes') }} <br />
                    <em class="small">
                      @if ($order->shippingZone)
                        {{ optional($order->shippingZone)->name }}
                      @elseif($order->shippingRate)
                        {{ optional($order->shippingRate->shippingZone)->name }}
                      @endif
                      {{ get_formated_decimal($order->taxrate, true, 2) }}%
                    </em>
                  </td>
                  <td class="text-right" width="40%">
                    {{ get_formated_currency($order->taxes, 2, $order->currency_id) }}
                  </td>
                </tr>

                <tr class="lead">
                  <td class="text-right">{{ trans('app.grand_total') }}</td>
                  <td class="text-right" width="40%">
                    {{ get_formated_currency($order->grand_total, 2, $order->currency_id) }}
                  </td>
                </tr>
              </table>
            </div>
          </div><!-- /.row -->
      @include('admin.partials.ui.card_end')

      @php
        $refunded_amt = $order->refundedSum();
      @endphp

      @if ($refunded_amt > 0)
        <div class="alert alert-warning alert-dismissible" role="alert">
          <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
          <h4><i class="fa fa-warning"></i> {{ trans('app.alert') }}!</h4>
          {!! trans('help.order_refunded', ['amount' => get_formated_currency($refunded_amt, 2, $order->currency_id), 'total' => get_formated_currency($order->grand_total, 2, $order->currency_id)]) !!}
        </div>
      @endif

      @can('fulfill', $order)
        <div class="admin-card admin-order-actions">
          <div class="admin-card__body admin-order-actions__bar">
            @if (Auth::user()->canManageOrderPayments() && ! (Auth::user()->isFromMerchant() && optional($order->paymentMethod)->code === 'wire'))
              {!! Form::open(['route' => ['admin.order.order.togglePaymentStatus', $order], 'method' => 'put', 'class' => 'inline']) !!}
              <button type="submit" class="confirm ajax-silent btn btn-lg btn-danger">{{ $order->isPaid() ? trans('app.mark_as_unpaid') : trans('app.mark_as_paid') }}</button>
              {!! Form::close() !!}

              @if ($order->isPaid() && ((Auth::user()->isFromPlatForm() && !vendor_get_paid_directly()) || (Auth::user()->isFromMerchant() && vendor_get_paid_directly())))
                @can('initiate', \App\Models\Refund::class)
                  <a href="javascript:void(0)" data-link="{{ route('admin.refunds.form', $order) }}" class="ajax-modal-btn btn btn-flat btn-lg btn-default">
                    {{ trans('app.initiate_refund') }}
                  </a>
                @endcan
              @endif
            @endif

            <div class="admin-order-actions__primary">
              @if (! $order->isFulfilled())
                @unless ($order->isCanceled() || $order->cancellation)
                  <a href="javascript:void(0)" data-link="{{ route('admin.order.cancellation.create', $order) }}" class="ajax-modal-btn btn btn-lg btn-warning">
                    {{ trans('app.cancel_order') }}
                  </a>
                @endunless

                @if ($order->deliver() && ! $order->isDelivered())
                  @if ($order->hasCourier())
                    {{-- Courier already assigned: OTP confirmation is the next step (same as sidebar). --}}
                    <span class="admin-order-fulfill-options__label">{{ trans('app.confirm_courier_otp') }}:</span>
                    {!! Form::open(['url' => panel_route('admin.order.courier.confirmOtp', $order, false), 'method' => 'put', 'class' => 'form-inline admin-order-otp-inline']) !!}
                      {!! Form::text('otp', null, ['class' => 'form-control input-lg', 'maxlength' => 6, 'pattern' => '[0-9]{6}', 'placeholder' => trans('app.delivery_otp'), 'required', 'style' => 'width:140px; display:inline-block;']) !!}
                      <button type="submit" class="btn btn-flat btn-lg btn-primary">{{ trans('app.confirm') }}</button>
                    {!! Form::close() !!}
                    <a href="javascript:void(0)" data-link="{{ route('admin.order.deliveryboys', $order->id) }}" class="ajax-modal-btn btn btn-flat btn-lg btn-default">
                      <i class="fa fa-truck"></i>
                      {{ trans('app.change_courier') }}
                    </a>
                  @elseif ($order->delivery_boy_id)
                    <a href="javascript:void(0)" data-link="{{ route('admin.order.deliveryboys', $order->id) }}" class="ajax-modal-btn btn btn-flat btn-lg btn-default">
                      <i class="fa fa-motorcycle"></i>
                      {{ trans('app.change_deliveryboy') }}
                    </a>
                  @else
                    <a href="javascript:void(0)" data-link="{{ route('admin.order.deliveryboys', $order->id) }}" class="ajax-modal-btn btn btn-flat btn-lg btn-primary">
                      <i class="fa fa-truck"></i>
                      {{ trans('app.assign_deliveryboy') }}
                    </a>
                  @endif
                @elseif ($order->deliver())
                  <a href="javascript:void(0)" data-link="{{ route('admin.order.order.fulfillment', $order) }}" class="ajax-modal-btn btn btn-flat btn-lg btn-primary">
                    {{ trans('app.fulfill_order') }}
                  </a>
                @endif
              @endif
            </div>
          </div>
        </div>
      @endcan

      @include('admin.partials._activity_logs', ['logger' => $order])
    </div> <!-- /.col-md-8 -->

    <div class="col-md-4 admin-order-detail__sidebar">
      @include('admin.order._order_feedback')

      @if (Auth::user()->isFromPlatform())
        @include('admin.partials.ui.card_start', [
          'title' => trans('app.shop'),
          'icon' => 'fa-store',
          'bodyClass' => 'admin-order-sidebar-panel',
          'actions' => Gate::allows('secretLogin', $order->shop->owner)
            ? '<a href="' . route('admin.user.secretLogin', $order->shop->owner->id) . '" class="btn btn-default btn-xs btn-flat"><i class="fa fa-user-secret"></i> ' . e(trans('app.secret_login_merchant')) . '</a>'
            : '',
        ])
          <div class="admin-order-sidebar-panel__shop">
            <img src="{{ get_storage_file_url(optional($order->shop->image)->path, 'mini') }}" class="admin-order-sidebar-panel__logo" alt="">
            <div>
              @if (Gate::allows('view', $order->shop) && $order->shop->id)
                <a href="javascript:void(0)" data-link="{{ route('admin.vendor.shop.show', $order->shop->id) }}" class="ajax-modal-btn admin-order-sidebar-panel__name">{{ $order->shop->name }}</a>
              @else
                <strong>{{ $order->shop->name }}</strong>
              @endif
              @if ($order->shop->id)
                <a href="{{ route('show.store', $order->shop->slug) }}" target="_blank" class="small"><i class="fa fa-external-link"></i> {{ trans('app.store_front') }}</a>
              @endif
            </div>
          </div>
        @include('admin.partials.ui.card_end')
      @endif

      @if ($order->fulfilment_type == \App\Models\Order::FULFILMENT_TYPE_DELIVER)
        @include('admin.partials.ui.card_start', [
          'title' => trans('app.deliveryboy'),
          'icon' => 'fa-motorcycle',
          'bodyClass' => 'admin-order-sidebar-panel',
        ])
          @if ($order->deliveryBoy)
            <div class="admin-order-sidebar-panel__user">
              <img src="{{ get_avatar_src($order->deliveryBoy, 'tiny') }}" class="img-circle img-sm" alt="">
              <div>
                <strong>{{ $order->deliveryBoy->getName() }}</strong>
                <small class="text-muted">{{ $order->deliveryBoy->email }}</small>
                @if ($order->isReached())
                  <br><span class="label label-info">{{ $order->deliveryStatusLabel() }}</span>
                @endif
              </div>
            </div>

            @if ($order->isReached())
              <div class="admin-order-sidebar-panel__otp-confirm" style="margin-top:12px; padding-top:12px; border-top:1px solid #eee;">
                {!! Form::open(['url' => panel_route('admin.order.deliveryboy.confirmOtp', $order, false), 'method' => 'put']) !!}
                  <label class="control-label" style="font-weight:600;">{{ trans('app.confirm_courier_otp') }}</label>
                  <p class="text-muted" style="margin-bottom:8px;">{{ trans('app.confirm_courier_otp_help') }}</p>
                  <div class="input-group">
                    {!! Form::text('otp', null, ['class' => 'form-control', 'maxlength' => 6, 'pattern' => '[0-9]{6}', 'placeholder' => trans('app.delivery_otp'), 'required']) !!}
                    <span class="input-group-btn">
                      <button type="submit" class="btn btn-new">{{ trans('app.confirm') }}</button>
                    </span>
                  </div>
                {!! Form::close() !!}
              </div>
            @endif
          @elseif ($order->hasCourier())
            <div class="admin-order-sidebar-panel__user">
              <strong>{{ $order->courier_name }}</strong>
              <br><small class="text-muted">{{ $order->courier_phone }}</small>
              @if ($order->courier_tracking_number)
                <br><small class="text-muted">{{ trans('app.tracking_id') }}: {{ $order->courier_tracking_number }}</small>
              @endif
            </div>
          @else
            <p class="text-muted">{{ trans('app.delivery_boy_not_assigned') }}</p>
          @endif

          @if (! empty($order->otp) && ! $order->isDelivered() && Auth::user()->isAdmin())
            <div class="admin-order-sidebar-panel__otp" style="margin-top:12px; padding-top:12px; border-top:1px solid #eee;">
              <small class="text-muted">{{ trans('app.delivery_otp') }}</small>
              <div style="font-size:20px; font-weight:700; letter-spacing:3px;">{{ $order->otp }}</div>
              <small class="text-muted">{{ trans('app.delivery_otp_help') }}</small>
            </div>
          @endif

          @if ($order->hasCourier() && ! $order->isDelivered())
            <div class="admin-order-sidebar-panel__otp-confirm" style="margin-top:12px; padding-top:12px; border-top:1px solid #eee;">
              {!! Form::open(['url' => panel_route('admin.order.courier.confirmOtp', $order, false), 'method' => 'put']) !!}
                <label class="control-label" style="font-weight:600;">{{ trans('app.confirm_courier_otp') }}</label>
                <p class="text-muted" style="margin-bottom:8px;">{{ trans('app.confirm_courier_otp_help') }}</p>
                <div class="input-group">
                  {!! Form::text('otp', null, ['class' => 'form-control', 'maxlength' => 6, 'pattern' => '[0-9]{6}', 'placeholder' => trans('app.delivery_otp'), 'required']) !!}
                  <span class="input-group-btn">
                    <button type="submit" class="btn btn-new">{{ trans('app.confirm') }}</button>
                  </span>
                </div>
              {!! Form::close() !!}
            </div>
          @endif
        @include('admin.partials.ui.card_end')
      @endif

      @if ($order->pickup() && ! $order->isCanceled())
        @include('admin.partials.ui.card_start', [
          'title' => trans('theme.pickup'),
          'icon' => 'fa-shopping-basket',
          'bodyClass' => 'admin-order-sidebar-panel',
        ])
          @if ($order->warehouse)
            <div class="admin-order-sidebar-panel__user">
              <strong>{{ $order->warehouse->name }}</strong>
            </div>
          @endif

          @if (! empty($order->otp) && ! $order->isDelivered() && Auth::user()->isAdmin())
            <div class="admin-order-sidebar-panel__otp" style="margin-top:12px; padding-top:12px; border-top:1px solid #eee;">
              <small class="text-muted">{{ trans('app.pickup_otp') }}</small>
              <div style="font-size:20px; font-weight:700; letter-spacing:3px;">{{ $order->otp }}</div>
              <small class="text-muted">{{ trans('app.pickup_otp_help') }}</small>
            </div>
          @endif

          @if (! $order->isDelivered())
            <div class="admin-order-sidebar-panel__otp-confirm" style="margin-top:12px; padding-top:12px; border-top:1px solid #eee;">
              {!! Form::open(['url' => panel_route('admin.order.pickup.confirmOtp', $order, false), 'method' => 'put']) !!}
                <label class="control-label" style="font-weight:600;">{{ trans('app.confirm_pickup_otp') }}</label>
                <p class="text-muted" style="margin-bottom:8px;">{{ trans('app.confirm_pickup_otp_help') }}</p>
                <div class="input-group">
                  {!! Form::text('otp', null, ['class' => 'form-control', 'maxlength' => 6, 'pattern' => '[0-9]{6}', 'placeholder' => trans('app.pickup_otp'), 'required']) !!}
                  <span class="input-group-btn">
                    <button type="submit" class="btn btn-new">{{ trans('app.confirm') }}</button>
                  </span>
                </div>
              {!! Form::close() !!}
            </div>
          @endif
        @include('admin.partials.ui.card_end')
      @endif

      @include('admin.partials.ui.card_start', [
          'title' => trans('app.customer'),
          'icon' => 'fa-user',
          'bodyClass' => 'admin-order-sidebar-panel',
        ])

            <div class="admin-order-sidebar-panel__user">
              <img src="{{ get_avatar_src($order->customer, 'tiny') }}" class="img-circle img-sm" alt="">
              <div>
                @if (Auth::user()->isFromPlatform() && $order->customer_id)
                  {{-- Full customer profile (order history across every shop) stays
                       platform-admin only — sellers only ever see this one order's info. --}}
                  <a href="javascript:void(0)" data-link="{{ route('admin.admin.customer.show', $order->customer->id) }}" class="ajax-modal-btn"><strong>{{ $order->customer->getName() }}</strong></a>
                @else
                  <strong>{{ $order->customer->getName() }}</strong>
                @endif
                @if ($order->email)
                  <small class="text-muted">{{ $order->email }}</small>
                @elseif ($order->customer->email)
                  <small class="text-muted">{{ $order->customer->email }}</small>
                @endif
                @php
                  $orderCustomerPhone = optional(optional($order->customer)->address)->phone ?: $order->customer_phone_number;
                @endphp
                @if ($orderCustomerPhone)
                  <br><small class="text-muted"><i class="fa fa-phone"></i> {{ $orderCustomerPhone }}</small>
                @endif
              </div>
            </div>

            <div class="admin-order-sidebar-panel__actions btn-group btn-group-justified">
              @php
                $useLiveChatModal = Auth::user()->isFromMerchant()
                  && class_exists(\Incevio\Package\LiveChat\Models\ChatConversation::class)
                  && $order->customer_id;
              @endphp
              @if ($useLiveChatModal)
                <a href="javascript:void(0)" data-link="{{ panel_route('admin.support.orderConversation.create', $order->id) }}" class="ajax-modal-btn btn btn-new btn-sm">{{ trans('app.send_message') }}</a>
              @elseif ($order->conversation)
                <a href="{{ panel_route('admin.support.message.show', $order->conversation) }}" class="btn btn-sm btn-info btn-flat">{{ trans('app.view_conversations') }}</a>
              @else
                <a href="javascript:void(0)" data-link="{{ panel_route('admin.support.orderConversation.create', $order->id) }}" class="ajax-modal-btn btn btn-new btn-sm">{{ trans('app.send_message') }}</a>
              @endif
              <a href="{{ panel_route('admin.order.order.invoice', $order) }}" class="btn btn-sm btn-default btn-flat">{{ trans('app.invoice') }}</a>
            </div>
            @if ($order->dispute)
              <a href="{{ panel_route('admin.support.dispute.show', $order->dispute) }}" class="btn btn-sm btn-danger btn-flat">{{ trans('app.view_dispute') }}</a>
            @elseif (!Auth::user()->isFromPlatform())
              <a href="{{ route('merchant.support.dispute.create', ['order_id' => $order->id]) }}" class="btn btn-sm btn-warning btn-flat">{{ trans('app.raise_dispute') }}</a>
            @endif

            @if (Auth::user()->isFromPlatform() && $order->wire_transfer_rejected_at)
              <div class="alert alert-danger">
                <strong>{{ trans('app.rejected') }}</strong> ({{ $order->wire_transfer_rejected_at->diffForHumans() }}):
                {{ $order->wire_transfer_rejection_reason }}
              </div>
            @endif

            @if (Auth::user()->isFromPlatform() && optional($order->paymentMethod)->code === 'wire' && count($order->attachments))
              <fieldset>
                <legend><i class="fa fa-bank"></i> {{ trans('app.payment') }} - Bank Transfer Proof</legend>
              </fieldset>

              @foreach ($order->attachments as $attachment)
                @php
                  $isImage = in_array(strtolower((string) $attachment->extension), ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                @endphp
                <a href="{{ route('attachment.download', $attachment) }}">
                  <i class="fa fa-file"></i> {{ $attachment->name }}
                </a>
                @if ($isImage)
                  <a href="{{ route('attachment.view', $attachment) }}" target="_blank" class="btn btn-xs btn-default wire-proof-preview"
                    data-src="{{ route('attachment.view', $attachment) }}"
                    data-name="{{ $attachment->name }}">
                    {{ trans('app.preview') }}
                  </a>
                @endif
                <br>
              @endforeach
            @elseif (Auth::user()->isFromPlatform() && optional($order->paymentMethod)->code === 'wire' && $order->wire_transfer_proof_path)
              <fieldset>
                <legend><i class="fa fa-bank"></i> {{ trans('app.payment') }} - Bank Transfer Proof</legend>
              </fieldset>
              <span>
                <i class="fa fa-file"></i>
                {{ $order->wire_transfer_proof_name ?: basename($order->wire_transfer_proof_path) }}
              </span>
              @php
                $dbProofExt = strtolower(pathinfo((string) $order->wire_transfer_proof_name, PATHINFO_EXTENSION));
                $dbProofIsImage = in_array($dbProofExt, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
              @endphp
              @if ($dbProofIsImage)
                <a href="{{ \Illuminate\Support\Facades\Storage::url($order->wire_transfer_proof_path) }}" target="_blank" class="btn btn-xs btn-default wire-proof-preview"
                  data-src="{{ \Illuminate\Support\Facades\Storage::url($order->wire_transfer_proof_path) }}"
                  data-name="{{ $order->wire_transfer_proof_name ?: basename($order->wire_transfer_proof_path) }}">
                  {{ trans('app.preview') }}
                </a>
              @endif
            @endif

            @if (is_incevio_package_loaded('pharmacy'))
              <fieldset>
                <legend><i class="far fa-stethoscope"></i> {{ trans('packages.pharmacy.prescription') }}</legend>
              </fieldset>

              @if (count($order->attachments))
                @foreach ($order->attachments as $attachment)
                  <a href="{{ route('attachment.download', $attachment) }}">
                    <i class="fa fa-file"></i> {{ $attachment->name }}
                  </a>
                @endforeach
              @endif
            @endif

            @if ($order->pickup())
              <fieldset>
                <legend>{{ strtoupper(trans('app.pick_up_address')) }}</legend>
              </fieldset>
              @if ($order->warehouse)
                <strong>{{ $order->warehouse->name }}</strong><br>
                {!! optional($order->warehouse->pickupAddress())->toHtml() !!}
                @if (is_array($order->warehouse->business_days))
                  <em class="fa fa-calendar"></em> {{ trans('app.form.business_days') }} : {{ implode(', ', $order->warehouse->business_days) }} <br>
                @endif
                @if ($order->warehouse->opening_time && $order->warehouse->close_time)
                  <em class="fa fa-clock-o"></em> {{ trans('app.form.business_hours') }} : {{ $order->warehouse->opening_time }} - {{ $order->warehouse->close_time }}
                @endif
              @else
                <p><i class="fa fa-warning"></i> {{ trans('app.info_not_found') }}</p>
              @endif
            @elseif ($order->deliver())
              <fieldset>
                <legend>{{ strtoupper(trans('app.shipping_address')) }}</legend>
              </fieldset>
              {!! address_str_to_html($order->shipping_address) !!}
              <iframe width="100%" height="150" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src="https://maps.google.com/maps?q={{ urlencode(address_str_to_geocode_str($order->shipping_address)) }}&output=embed"></iframe>

              <fieldset>
                <legend>{{ strtoupper(trans('app.billing_address')) }}</legend>
              </fieldset>

              @if ($order->shipping_address == $order->billing_address)
                <small>
                  <i class="fa fa-check-square-o"></i>
                  {!! Form::label('same_as_shipping_address', strtoupper(trans('app.same_as_shipping_address')), ['class' => 'indent5']) !!}
                </small>
              @else
                {!! address_str_to_html($order->billing_address) !!}
              @endif
            @endif
        @include('admin.partials.ui.card_end')

      @if ($order->refunds->count())
        @include('admin.partials.ui.card_start', [
          'title' => trans('app.refunds'),
          'icon' => 'fa-undo',
          'bodyClass' => 'admin-order-sidebar-panel',
          'actions' => '<a href="' . e(route('admin.refunds.index', ['q' => $order->order_number])) . '" class="btn btn-default btn-xs btn-flat"><i class="fa fa-list"></i> ' . e(trans('app.view_all')) . '</a>',
        ])
          <table class="table table-hover admin-table admin-table--compact">
            <tbody>
              @foreach ($order->refunds as $refund)
                <tr>
                  <td class="small">{{ $refund->created_at->diffForHumans() }}</td>
                  <td>{{ get_formated_currency($refund->amount, 2, $order->currency_id) }}</td>
                  <td>{!! $refund->statusName() !!}</td>
                  <td class="row-options admin-row-actions">
                    <a href="javascript:void(0)" data-link="{{ route('admin.refunds.response', $refund) }}" class="admin-action-btn ajax-modal-btn" title="{{ $refund->isOpen() ? trans('app.response') : trans('app.detail') }}" data-toggle="tooltip"><i class="fa fa-{{ $refund->isOpen() ? 'random' : 'eye' }}"></i></a>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        @include('admin.partials.ui.card_end')
      @endif

      @if ($order->deliver())
        @include('admin.partials.ui.card_start', [
          'title' => trans('app.shipping'),
          'icon' => 'fa-truck',
          'bodyClass' => 'admin-order-sidebar-panel',
          'actions' => '<a href="' . panel_route('admin.order.order.invoice', $order) . '" class="btn btn-default btn-xs btn-flat"><i class="fa fa-download"></i> ' . e(trans('app.download_invoice') ?: trans('app.invoice')) . '</a>'
            . ' <a href="' . panel_route('admin.order.shipping_label', $order) . '" class="btn btn-default btn-xs btn-flat"><i class="fa fa-file"></i> ' . e(trans('app.download_shipping_label')) . '</a>',
        ])
          <dl class="admin-order-sidebar-panel__meta">
            <dt>{{ trans('app.customer_name') }}</dt>
            <dd><strong>{{ $order->customer->getName() }}</strong></dd>
            <dt>{{ trans('app.phone_number') }}</dt>
            <dd>{{ $order->customer_phone_number ?: '—' }}</dd>
            <dt>{{ trans('app.shipping_address') }}</dt>
            <dd>{!! address_str_to_html($order->shipping_address) !!}</dd>
          </dl>
        @include('admin.partials.ui.card_end')
      @endif
    </div>
  </div> <!-- /.row -->

  <div class="modal fade" id="wireProofPreviewModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
          <h4 class="modal-title" id="wireProofPreviewTitle">{{ trans('app.preview') }}</h4>
        </div>
        <div class="modal-body text-center">
          <img id="wireProofPreviewImage" src="" alt="Payment proof"
            style="max-width:100%; max-height:70vh; object-fit:contain;">
        </div>
      </div>
    </div>
  </div>

  <script>
    (function() {
      'use strict';
      document.addEventListener('click', function(e) {
        var trigger = e.target.closest('.wire-proof-preview');
        if (!trigger) return;

        var src = trigger.getAttribute('data-src');
        var name = trigger.getAttribute('data-name') || '{{ trans('app.preview') }}';
        if (!src) return;

        var img = document.getElementById('wireProofPreviewImage');
        var title = document.getElementById('wireProofPreviewTitle');
        if (!img || !title) return;

        img.setAttribute('src', src);
        title.textContent = name;

        if (window.jQuery && window.jQuery.fn && window.jQuery.fn.modal) {
          e.preventDefault();
          window.jQuery('#wireProofPreviewModal').modal('show');
        }
      });
    })();
  </script>
@endsection
