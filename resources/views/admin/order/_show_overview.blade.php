{{--
  Order overview for platform admins.
  Most merchant actions (assign rider/courier, fulfill, refund, etc.) stay on the
  merchant panel. Platform admins can still cancel an order from this page.
--}}
@extends('admin.layouts.master')

@section('page_title')
  {{ trans('app.order') }} #{{ $order->order_number }}
@endsection

@section('content')
  @php
    $fulfilmentLabel = trans('app.fulfilment_type.' . ($order->fulfilment_type ?: 'deliver'));
    $adminOrderTransactionFee = (float) ($order->subscription_transaction_fee ?? 0) + (float) ($order->platform_payment_fee ?? 0);
    $adminOrderTotalPaid = round((float) $order->grand_total + $adminOrderTransactionFee, 2);
  @endphp

  <div class="row admin-order-detail">
    <div class="col-md-8">
      @include('admin.partials.ui.card_start', [
        'title' => trans('app.order') . ': ' . $order->order_number,
        'icon' => 'fa-shopping-cart',
        'headerExtra' => $order->dispute ? '<span class="label label-danger">' . e(trans('app.statuses.disputed')) . '</span>' : '',
        'actions' => $order->orderStatus(),
        'bodyClass' => 'admin-order-detail__main',
      ])
        @can('cancel', $order)
          @unless ($order->isCanceled())
            <div style="margin:0 0 16px;">
              <a href="javascript:void(0)" data-link="{{ route('admin.order.cancellation.create', $order) }}" class="ajax-modal-btn btn btn-warning">
                {{ trans('app.cancel_order') }}
              </a>
            </div>
          @endunless
        @endcan
        <div class="admin-order-payment-bar">
          <span class="admin-order-payment-bar__method">
            {{ trans('app.payment') . ': ' . optional($order->paymentMethod)->name }}
          </span>
          <span class="admin-order-payment-bar__status">
            {!! $order->paymentStatusName() !!}
          </span>
        </div>

        <dl class="admin-order-sidebar-panel__meta" style="margin:12px 0 20px; display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:8px 16px;">
          <div>
            <dt>{{ trans('app.order_date') }}</dt>
            <dd>{{ optional($order->created_at)->toDayDateTimeString() ?: '—' }}</dd>
          </div>
          <div>
            <dt>{{ trans('app.form.fulfilment_type') }}</dt>
            <dd>{{ $fulfilmentLabel }}</dd>
          </div>
          @if ($order->shipping_date)
            <div>
              <dt>{{ trans('app.shipping_date') }}</dt>
              <dd>{{ $order->shipping_date->toFormattedDateString() }}</dd>
            </div>
          @endif
          @if ($order->delivery_date)
            <div>
              <dt>{{ trans('app.delivery_date') }}</dt>
              <dd>{{ $order->delivery_date->toFormattedDateString() }}</dd>
            </div>
          @endif
          @if ($order->payment_ref_id)
            <div>
              <dt>{{ trans('app.payment') }} Ref</dt>
              <dd>{{ $order->payment_ref_id }}</dd>
            </div>
          @endif
          @if ($order->coupon)
            <div>
              <dt>{{ trans('app.coupon') }}</dt>
              <dd>{{ $order->coupon->code ?? $order->coupon->name }}</dd>
            </div>
          @endif
          @if ($order->goods_received)
            <div>
              <dt>{{ trans('app.statuses.delivered') }}</dt>
              <dd><span class="label label-success">{{ trans('app.yes') }}</span></dd>
            </div>
          @endif
        </dl>

        <div class="row">
          <div class="col-md-12">
            <h4 class="admin-order-section-title">{{ trans('app.order_details') }}
              @if ($order->auction_bid_id)
                <span class="label label-primary ml-2"><i class="fa fa-gavel"></i> {{ trans('packages.auction.winner') }}</span>
              @endif
            </h4>
            <span class="spacer10"></span>

            <table class="table table-striped admin-table">
              <tbody>
                @if (count($order->inventories) > 0)
                  @foreach ($order->inventories as $item)
                    <tr>
                      <td>
                        <img src="{{ get_product_img_src($item, 'tiny') }}" class="img-circle img-md" alt="{{ trans('app.image') }}">
                      </td>
                      <td class="nopadding-right" width="55%">
                        {{ $item->pivot->item_description }}
                        <a href="{{ storefront_product_url($item) }}" target="_blank" class="indent5 small"><i class="fa fa-external-link"></i></a>
                      </td>
                      <td class="nopadding-right text-right" width="15%">
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
                  <tr>
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
            @if ($order->buyer_note)
              <div class="spacer10"></div>
              {{ trans('app.buyer_note') }}:
              <blockquote>{{ $order->buyer_note }}</blockquote>
            @endif

            @if ($order->admin_note)
              <div class="spacer10"></div>
              {{ trans('app.admin_note') }}:
              <blockquote>{!! $order->admin_note !!}</blockquote>
            @endif

            @if ($order->message_to_customer)
              <div class="spacer10"></div>
              {{ trans('app.message_to_customer') }}:
              <blockquote>{{ $order->message_to_customer }}</blockquote>
            @endif

            @if ($order->cancellation)
              <div class="spacer10"></div>
              {{ trans('app.' . $order->cancellation->request_type . '_request') }}:
              <blockquote>
                {!! $order->cancellation->statusName() !!}<br>
                <strong>{{ trans('app.reason') }}:</strong> {!! $order->cancellation->reason !!}
                @if ($order->cancellation->description)
                  <br><strong>{{ trans('app.detail') }}:</strong> {{ $order->cancellation->description }}
                @endif
              </blockquote>

              @if ($order->cancellation->isPartial() && is_array($order->cancellation->items))
                <table class="table table-striped admin-table">
                  <tbody>
                    @foreach ($order->inventories as $item)
                      @if (in_array($item->id, $order->cancellation->items))
                        <tr>
                          <td>{{ $item->pivot->item_description }}</td>
                          <td class="text-right">{{ $item->pivot->quantity }} &times; {{ get_formated_currency($item->pivot->unit_price, 2, $order->currency_id) }}</td>
                        </tr>
                      @endif
                    @endforeach
                  </tbody>
                </table>
              @else
                <p class="text-muted">{{ trans('app.all_items') }}</p>
              @endif
            @endif
          </div>
          <div class="col-md-6">
            <table class="table admin-order-summary">
              <tr>
                <td class="text-right">{{ trans('app.total') }}</td>
                <td class="text-right" width="40%">{{ get_formated_currency($order->total, 2, $order->currency_id) }}</td>
              </tr>
              <tr>
                <td class="text-right">{{ trans('app.discount') }}</td>
                <td class="text-right" width="40%">&minus; {{ get_formated_currency($order->discount, 2, $order->currency_id) }}</td>
              </tr>
              <tr>
                <td class="text-right">
                  {{ trans('app.shipping') }}<br>
                  <em class="small">
                    @if ($order->shippingRate)
                      {{ optional($order->shippingRate)->name }}
                      @php $carrier_name = $order->carrier ? $order->carrier->name : optional($order->shippingRate->carrier)->name; @endphp
                      @if ($carrier_name)
                        <small>{{ trans('app.by') . ' ' . $carrier_name }}</small>
                      @endif
                    @else
                      {{ trans('app.custom_shipping') }}
                    @endif
                  </em>
                </td>
                <td class="text-right" width="40%">{{ get_formated_currency($order->shipping, 2, $order->currency_id) }}</td>
              </tr>
              @if (is_incevio_package_loaded('packaging') && $order->shippingPackage)
                <tr>
                  <td class="text-right">
                    {{ trans('app.packaging') }}<br>
                    <em class="small">{{ optional($order->shippingPackage)->name }}</em>
                  </td>
                  <td class="text-right" width="40%">{{ get_formated_currency($order->packaging, 2, $order->currency_id) }}</td>
                </tr>
              @endif
              @if ($order->handling)
                <tr>
                  <td class="text-right">{{ trans('app.handling') }}</td>
                  <td class="text-right" width="40%">{{ get_formated_currency($order->handling, 2, $order->currency_id) }}</td>
                </tr>
              @endif
              <tr>
                <td class="text-right">
                  {{ trans('app.taxes') }}<br>
                  <em class="small">
                    @if ($order->shippingZone)
                      {{ optional($order->shippingZone)->name }}
                    @elseif ($order->shippingRate)
                      {{ optional($order->shippingRate->shippingZone)->name }}
                    @endif
                    {{ get_formated_decimal($order->taxrate, true, 2) }}%
                  </em>
                </td>
                <td class="text-right" width="40%">{{ get_formated_currency($order->taxes, 2, $order->currency_id) }}</td>
              </tr>
              <tr class="lead">
                <td class="text-right">{{ trans('app.grand_total') }}</td>
                <td class="text-right" width="40%">{{ get_formated_currency($order->grand_total, 2, $order->currency_id) }}</td>
              </tr>
              @if ($adminOrderTransactionFee > 0)
                <tr>
                  <td class="text-right">{{ trans('app.transaction_fee') }}</td>
                  <td class="text-right" width="40%">{{ get_formated_currency($adminOrderTransactionFee, 2, $order->currency_id) }}</td>
                </tr>
                <tr class="lead">
                  <td class="text-right">{{ trans('app.total_paid') }}</td>
                  <td class="text-right" width="40%">{{ get_formated_currency($adminOrderTotalPaid, 2, $order->currency_id) }}</td>
                </tr>
              @elseif ((float) ($order->subscription_transaction_fee ?? 0) == 0 && in_array(optional($order->paymentMethod)->code, ['mpesa', 'emola'], true))
                <tr>
                  <td class="text-right text-muted">{{ trans('app.transaction_fee') }}</td>
                  <td class="text-right text-muted" width="40%">{{ get_formated_currency(0, 2, $order->currency_id) }}</td>
                </tr>
              @endif
            </table>
          </div>
        </div><!-- /.row -->
      @include('admin.partials.ui.card_end')

      @php $refunded_amt = $order->refundedSum(); @endphp
      @if ($refunded_amt > 0)
        <div class="alert alert-warning" role="alert">
          <h4><i class="fa fa-warning"></i> {{ trans('app.alert') }}!</h4>
          {!! trans('help.order_refunded', ['amount' => get_formated_currency($refunded_amt, 2, $order->currency_id), 'total' => get_formated_currency($order->grand_total, 2, $order->currency_id)]) !!}
        </div>
      @endif

      @if ($order->refunds->count())
        @include('admin.partials.ui.card_start', [
          'title' => trans('app.refunds'),
          'icon' => 'fa-undo',
          'bodyClass' => 'admin-order-sidebar-panel',
          'actions' => '<a href="' . e(route('admin.refunds.index', ['q' => $order->order_number])) . '" class="btn btn-default btn-xs btn-flat"><i class="fa fa-list"></i> ' . e(trans('app.view_all')) . '</a>'
            . (Gate::allows('initiate', \App\Models\Refund::class)
              ? ' <a href="javascript:void(0)" data-link="' . e(route('admin.refunds.form', $order)) . '" class="btn btn-default btn-xs btn-flat ajax-modal-btn"><i class="fa fa-plus"></i> ' . e(trans('app.initiate_refund')) . '</a>'
              : ''),
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
      @elseif (Gate::allows('initiate', \App\Models\Refund::class))
        @include('admin.partials.ui.card_start', [
          'title' => trans('app.refunds'),
          'icon' => 'fa-undo',
          'bodyClass' => 'admin-order-sidebar-panel',
        ])
          <p class="text-muted" style="margin:0 0 10px;">{{ trans('app.no_refunds_yet') }}</p>
          <a href="javascript:void(0)" data-link="{{ route('admin.refunds.form', $order) }}" class="ajax-modal-btn btn btn-default btn-sm">
            <i class="fa fa-plus"></i> {{ trans('app.initiate_refund') }}
          </a>
        @include('admin.partials.ui.card_end')
      @endif

      @include('admin.partials._activity_logs', ['logger' => $order])
    </div> <!-- /.col-md-8 -->

    <div class="col-md-4 admin-order-detail__sidebar">
      @include('admin.partials.ui.card_start', [
        'title' => trans('app.shop'),
        'icon' => 'fa-store',
        'bodyClass' => 'admin-order-sidebar-panel',
      ])
        <div class="admin-order-sidebar-panel__shop">
          <img src="{{ get_storage_file_url(optional(optional($order->shop)->image)->path, 'mini') }}" class="admin-order-sidebar-panel__logo" alt="">
          <div>
            <strong>{{ optional($order->shop)->name }}</strong>
            @if (optional($order->shop)->id)
              <br><a href="{{ route('show.store', $order->shop->slug) }}" target="_blank" class="small"><i class="fa fa-external-link"></i> {{ trans('app.store_front') }}</a>
            @endif
          </div>
        </div>
      @include('admin.partials.ui.card_end')

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
            <strong>{{ optional($order->customer)->getName() }}</strong>
            @if ($order->email)
              <br><small class="text-muted">{{ $order->email }}</small>
            @elseif (optional($order->customer)->email)
              <br><small class="text-muted">{{ $order->customer->email }}</small>
            @endif
            @if ($order->customer_phone_number)
              <br><small class="text-muted">{{ $order->customer_phone_number }}</small>
            @endif
          </div>
        </div>

        <div class="admin-order-sidebar-panel__actions btn-group btn-group-justified" style="margin-top:12px;">
          @if ($order->conversation)
            <a href="{{ route('admin.support.message.show', $order->conversation) }}" class="btn btn-sm btn-info btn-flat">{{ trans('app.view_conversations') }}</a>
          @endif
          <a href="{{ panel_route('admin.order.order.invoice', $order) }}" class="btn btn-sm btn-default btn-flat">{{ trans('app.invoice') }}</a>
        </div>

        @if ($order->dispute)
          <div class="spacer10"></div>
          <a href="{{ panel_route('admin.support.dispute.show', $order->dispute) }}" class="btn btn-sm btn-danger btn-flat">{{ trans('app.view_dispute') }}</a>
        @elseif (!Auth::user()->isFromPlatform())
          <div class="spacer10"></div>
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
            @php $isImage = in_array(strtolower((string) $attachment->extension), ['jpg', 'jpeg', 'png', 'gif', 'webp']); @endphp
            <a href="{{ route('attachment.download', $attachment) }}"><i class="fa fa-file"></i> {{ $attachment->name }}</a>
            @if ($isImage)
              <a href="{{ route('attachment.view', $attachment) }}" target="_blank" class="btn btn-xs btn-default wire-proof-preview"
                data-src="{{ route('attachment.view', $attachment) }}" data-name="{{ $attachment->name }}">{{ trans('app.preview') }}</a>
            @endif
            <br>
          @endforeach
        @elseif (Auth::user()->isFromPlatform() && optional($order->paymentMethod)->code === 'wire' && $order->wire_transfer_proof_path)
          <fieldset>
            <legend><i class="fa fa-bank"></i> {{ trans('app.payment') }} - Bank Transfer Proof</legend>
          </fieldset>
          <span><i class="fa fa-file"></i> {{ $order->wire_transfer_proof_name ?: basename($order->wire_transfer_proof_path) }}</span>
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

        @if (is_incevio_package_loaded('pharmacy') && count($order->attachments))
          <fieldset>
            <legend><i class="far fa-stethoscope"></i> {{ trans('packages.pharmacy.prescription') }}</legend>
          </fieldset>
          @foreach ($order->attachments as $attachment)
            <a href="{{ route('attachment.download', $attachment) }}">
              <i class="fa fa-file"></i> {{ $attachment->name }}
            </a><br>
          @endforeach
        @endif

        @if ($order->pickup())
          <fieldset><legend>{{ strtoupper(trans('app.pick_up_address')) }}</legend></fieldset>
          @if ($order->warehouse)
            <strong>{{ $order->warehouse->name }}</strong><br>
            {!! optional($order->warehouse->address)->toHtml() !!}
            @if (is_array($order->warehouse->business_days))
              <em class="fa fa-calendar"></em> {{ trans('app.form.business_days') }} : {{ implode(', ', $order->warehouse->business_days) }} <br>
            @endif
            @if ($order->warehouse->opening_time || $order->warehouse->close_time)
              <em class="fa fa-clock-o"></em> {{ trans('app.form.business_hours') }} : {{ $order->warehouse->opening_time }} - {{ $order->warehouse->close_time }}
            @endif
          @else
            <p><i class="fa fa-warning"></i> {{ trans('app.info_not_found') }}</p>
          @endif
        @elseif ($order->deliver())
          <fieldset><legend>{{ strtoupper(trans('app.shipping_address')) }}</legend></fieldset>
          {!! address_str_to_html($order->shipping_address) !!}
          @if ($order->shipping_address)
            <iframe width="100%" height="150" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src="https://maps.google.com/maps?q={{ urlencode(address_str_to_geocode_str($order->shipping_address)) }}&output=embed"></iframe>
          @endif

          <fieldset><legend>{{ strtoupper(trans('app.billing_address')) }}</legend></fieldset>
          @if ($order->shipping_address == $order->billing_address)
            <small><i class="fa fa-check-square-o"></i> {{ strtoupper(trans('app.same_as_shipping_address')) }}</small>
          @else
            {!! address_str_to_html($order->billing_address) !!}
          @endif
        @endif
      @include('admin.partials.ui.card_end')

      @if ($order->deliver())
        @include('admin.partials.ui.card_start', [
          'title' => trans('app.shipping'),
          'icon' => 'fa-truck',
          'bodyClass' => 'admin-order-sidebar-panel',
          'actions' => '<a href="' . panel_route('admin.order.order.invoice', $order) . '" class="btn btn-default btn-xs btn-flat"><i class="fa fa-download"></i> ' . e(trans('app.invoice')) . '</a>'
            . ' <a href="' . panel_route('admin.order.shipping_label', $order) . '" class="btn btn-default btn-xs btn-flat"><i class="fa fa-file"></i> ' . e(trans('app.download_shipping_label')) . '</a>',
        ])
          <dl class="admin-order-sidebar-panel__meta">
            <dt>{{ trans('app.customer_name') }}</dt>
            <dd><strong>{{ optional($order->customer)->getName() }}</strong></dd>
            <dt>{{ trans('app.phone_number') }}</dt>
            <dd>{{ $order->customer_phone_number ?: '—' }}</dd>
            <dt>{{ trans('app.tracking_id') }}</dt>
            <dd>{{ $order->tracking_id ?: '—' }}</dd>
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
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
          <h4 class="modal-title" id="wireProofPreviewTitle">{{ trans('app.preview') }}</h4>
        </div>
        <div class="modal-body text-center">
          <img id="wireProofPreviewImage" src="" alt="Payment proof" style="max-width:100%; max-height:70vh; object-fit:contain;">
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

      // Keep History caret / aria-expanded in sync with Bootstrap collapse
      if (window.jQuery) {
        window.jQuery(document).on('show.bs.collapse hide.bs.collapse', '.admin-activity-log__details', function(e) {
          var id = this.id;
          var trigger = window.jQuery('a[data-target="#' + id + '"]');
          trigger.attr('aria-expanded', e.type === 'show' ? 'true' : 'false');
        });
      }
    })();
  </script>
@endsection
