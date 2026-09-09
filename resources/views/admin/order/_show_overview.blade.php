{{--
  Read-only order overview for platform admins.
  No forms, no ajax-modal-btn triggers, no state-changing links — admins can see
  everything about an order here, but every action (assign delivery boy, add
  courier, fulfill, cancel, mark paid, refund, archive, edit admin note, etc.)
  belongs to the merchant, on the merchant panel's own order page.
--}}
@extends('admin.layouts.master')

@section('page_title')
  {{ trans('app.order') }} #{{ $order->order_number }}
@endsection

@section('content')
  <div class="row admin-order-detail">
    <div class="col-md-8">
      @include('admin.partials.ui.card_start', [
        'title' => trans('app.order') . ': ' . $order->order_number,
        'icon' => 'fa-shopping-cart',
        'headerExtra' => $order->dispute ? '<span class="label label-danger">' . e(trans('app.statuses.disputed')) . '</span>' : '',
        'actions' => $order->orderStatus(),
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
              <tbody>
                @if (count($order->inventories) > 0)
                  @foreach ($order->inventories as $item)
                    <tr>
                      <td>
                        <img src="{{ get_product_img_src($item, 'tiny') }}" class="img-circle img-md" alt="{{ trans('app.image') }}">
                      </td>
                      <td class="nopadding-right" width="55%">
                        {{ $item->pivot->item_description }}
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
              @if ($order->handling)
                <tr>
                  <td class="text-right">{{ trans('app.handling') }}</td>
                  <td class="text-right" width="40%">{{ get_formated_currency($order->handling, 2, $order->currency_id) }}</td>
                </tr>
              @endif
              <tr>
                <td class="text-right">
                  {{ trans('app.taxes') }}<br>
                  <em class="small">{{ get_formated_decimal($order->taxrate, true, 2) }}%</em>
                </td>
                <td class="text-right" width="40%">{{ get_formated_currency($order->taxes, 2, $order->currency_id) }}</td>
              </tr>
              @php
                $adminOrderTransactionFee = (float) ($order->subscription_transaction_fee ?? 0) + (float) ($order->platform_payment_fee ?? 0);
                $adminOrderTotalPaid = round((float) $order->grand_total + $adminOrderTransactionFee, 2);
              @endphp
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
        ])
          <table class="table table-hover admin-table admin-table--compact">
            <tbody>
              @foreach ($order->refunds as $refund)
                <tr>
                  <td class="small">{{ $refund->created_at->diffForHumans() }}</td>
                  <td>{{ get_formated_currency($refund->amount, 2, $order->currency_id) }}</td>
                  <td>{!! $refund->statusName() !!}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
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
          <img src="{{ get_storage_file_url(optional($order->shop->image)->path, 'mini') }}" class="admin-order-sidebar-panel__logo" alt="">
          <div>
            <strong>{{ $order->shop->name }}</strong>
            @if ($order->shop->id)
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
            <strong>{{ $order->customer->getName() }}</strong>
            @if ($order->email)
              <br><small class="text-muted">{{ $order->email }}</small>
            @elseif ($order->customer->email)
              <br><small class="text-muted">{{ $order->customer->email }}</small>
            @endif
            @if ($order->customer_phone_number)
              <br><small class="text-muted">{{ $order->customer_phone_number }}</small>
            @endif
          </div>
        </div>

        @if ($order->dispute)
          <div class="spacer10"></div>
          <span class="label label-danger">{{ trans('app.view_dispute') }}</span>
        @endif

        @if (optional($order->paymentMethod)->code === 'wire' && count($order->attachments))
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
        @elseif (optional($order->paymentMethod)->code === 'wire' && $order->wire_transfer_proof_path)
          <fieldset>
            <legend><i class="fa fa-bank"></i> {{ trans('app.payment') }} - Bank Transfer Proof</legend>
          </fieldset>
          <span><i class="fa fa-file"></i> {{ $order->wire_transfer_proof_name ?: basename($order->wire_transfer_proof_path) }}</span>
        @endif

        @if ($order->pickup())
          <fieldset><legend>{{ strtoupper(trans('app.pick_up_address')) }}</legend></fieldset>
          @if ($order->warehouse)
            <strong>{{ $order->warehouse->name }}</strong><br>
            {!! $order->warehouse->address->toHtml() !!}
          @else
            <p><i class="fa fa-warning"></i> {{ trans('app.info_not_found') }}</p>
          @endif
        @elseif ($order->deliver())
          <fieldset><legend>{{ strtoupper(trans('app.shipping_address')) }}</legend></fieldset>
          {!! address_str_to_html($order->shipping_address) !!}

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
          'actions' => '<a href="' . route('admin.order.order.invoice', $order->id) . '" class="btn btn-default btn-xs btn-flat"><i class="fa fa-download"></i> ' . e(trans('app.invoice')) . '</a>'
            . ' <a href="' . panel_route('admin.order.shipping_label', $order) . '" class="btn btn-default btn-xs btn-flat"><i class="fa fa-file"></i> ' . e(trans('app.download_shipping_label')) . '</a>',
        ])
          <dl class="admin-order-sidebar-panel__meta">
            <dt>{{ trans('app.tracking_id') }}</dt>
            <dd>{{ $order->tracking_id ?: '—' }}</dd>
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
    })();
  </script>
@endsection
