<!-- CONTENT SECTION -->
@php
  $orderTransactionFee = (float) ($order->subscription_transaction_fee ?? 0) + (float) ($order->platform_payment_fee ?? 0);
  $orderTotalPaid = round((float) $order->grand_total + $orderTransactionFee, 2);
  $paymentTableCols = 4 + ($order->is_digital ? 0 : 2) + ($orderTransactionFee > 0 ? 1 : 0);
  $paymentSummaryColspan = max(1, intdiv($paymentTableCols, 3));
  $paymentSummaryLastColspan = max(1, $paymentTableCols - 2 * $paymentSummaryColspan);
@endphp
<div class="sf-detail-page order-detail-page">
<section id="payment-detail-section" name="payment-detail-section" class="account-section mb-3">
  <div class="container">
    <div class="row">
      <div class="col-12 px-3 px-md-0">
        <div class="sf-panel">
          <div class="sf-panel__head">@lang('theme.payment_detail')</div>
          <div class="sf-panel__body table-responsive order-detail-table-wrap">
          <table class="table order-detail-stack-table" id="buyer-payment-detail-table">
            <tbody>
              <tr class="buyer-payment-info-head">
                <td>@lang('theme.price')</td>
                @unless ($order->is_digital)
                  <td>@lang('theme.shipping_cost')</td>
                  <td>@lang('theme.packaging_cost')</td>
                @endunless
                <td>@lang('theme.taxes')</td>
                <td>@lang('theme.discount')</td>
                @if ($orderTransactionFee > 0)
                  <td>@lang('theme.transaction_fee')</td>
                @endif
                <td>@lang('theme.total')</td>
              </tr>

              <tr class="buyer-payment-info-body">
                <td data-label="@lang('theme.price')">{{ get_formated_currency($order->total, 2, $order->currency_id) }}</td>
                @unless ($order->is_digital)
                  <td data-label="@lang('theme.shipping_cost')">{{ get_formated_currency($order->get_shipping_cost(), 2, $order->currency_id) }}</td>
                  <td data-label="@lang('theme.packaging_cost')">{{ get_formated_currency($order->packaging, 2, $order->currency_id) }}</td>
                @endunless
                <td data-label="@lang('theme.taxes')">{{ get_formated_currency($order->taxes, 2, $order->currency_id) }}</td>
                <td data-label="@lang('theme.discount')">{{ get_formated_currency($order->discount, 2, $order->currency_id) }}</td>
                @if ($orderTransactionFee > 0)
                  <td data-label="@lang('theme.transaction_fee')">{{ get_formated_currency($orderTransactionFee, 2, $order->currency_id) }}</td>
                @endif
                <td data-label="@lang('theme.total')">{{ get_formated_currency($orderTransactionFee > 0 ? $orderTotalPaid : $order->grand_total, 2, $order->currency_id) }}</td>
              </tr>

              <tr class="buyer-payment-info-head order-detail-table-divider">
                <td colspan="{{ $paymentSummaryColspan }}">@lang('theme.amount')</td>
                <td colspan="{{ $paymentSummaryColspan }}">@lang('theme.payment_method')</td>
                <td colspan="{{ $paymentSummaryLastColspan }}">@lang('theme.status')</td>
              </tr>

              <tr class="buyer-payment-info-body buyer-payment-info-summary">
                <td colspan="{{ $paymentSummaryColspan }}" data-label="@lang('theme.amount')">
                  {{ get_formated_currency($orderTransactionFee > 0 ? $orderTotalPaid : $order->grand_total, 2, $order->currency_id) }}
                  @if ($orderTransactionFee > 0)
                    <div class="small text-muted mt-1">
                      @lang('theme.order_amount'): {{ get_formated_currency($order->grand_total, 2, $order->currency_id) }}
                      + @lang('theme.transaction_fee'): {{ get_formated_currency($orderTransactionFee, 2, $order->currency_id) }}
                    </div>
                  @endif
                </td>
                <td colspan="{{ $paymentSummaryColspan }}" data-label="@lang('theme.payment_method')">{{ $order->paymentMethod->name }}</td>
                <td colspan="{{ $paymentSummaryLastColspan }}" data-label="@lang('theme.status')">{!! $order->paymentStatusName() !!}</td>
              </tr>

              @if ($order->canResendEmolaPayment())
                <tr class="buyer-payment-info-head order-detail-table-divider">
                  <td colspan="{{ $paymentTableCols }}">@lang('theme.emola_resend_title')</td>
                </tr>
                <tr class="buyer-payment-info-body">
                  <td colspan="{{ $paymentTableCols }}">
                    <p class="text-muted mb-3">@lang('theme.emola_resend_help')</p>
                    {!! Form::open(['route' => ['order.emola.resend', $order], 'method' => 'POST', 'class' => 'emola-resend-form', 'id' => 'emola-resend-form']) !!}
                    <div class="emola-resend-panel">
                      <div class="form-group emola-resend-field">
                        <label for="emola-resend-number" class="control-label">@lang('theme.emola_number')</label>
                      {!! Form::text('emola_number', old('emola_number', $order->suggestedEmolaNumber()), [
                          'id' => 'emola-resend-number',
                          'class' => 'form-control flat',
                          'placeholder' => trans('theme.emola_number_placeholder'),
                          'inputmode' => 'numeric',
                          'maxlength' => 9,
                          'required' => 'required',
                          'pattern' => '^(86|87)[0-9]{7}$',
                      ]) !!}
                      </div>
                      <div class="emola-resend-actions">
                        {!! Form::button('<i class="fa fa-refresh"></i> ' . trans('theme.emola_resend_button'), [
                            'type' => 'button',
                            'class' => 'btn sf-btn-primary btn-block emola-resend-submit',
                            'data-confirm' => trans('theme.emola_resend_confirm'),
                        ]) !!}
                        <button type="button"
                          class="btn btn-default btn-block emola-sync-payment"
                          data-url="{{ route('order.emola.sync', $order) }}">
                          <i class="fa fa-search"></i> @lang('theme.emola_check_payment')
                        </button>
                      </div>
                    </div>
                    {!! Form::close() !!}
                    <p class="help-block small text-muted mb-0">@lang('theme.emola_number_help')</p>
                    <p class="help-block small text-muted mb-0">@lang('theme.emola_check_payment_help')</p>
                  </td>
                </tr>
              @endif

              @if (optional($order->paymentMethod)->code === 'wire')
                <tr class="buyer-payment-info-head order-detail-table-divider">
                  <td colspan="{{ $paymentTableCols }}">@lang('theme.payment_detail') - @lang('theme.payment_proof')</td>
                </tr>
                <tr class="buyer-payment-info-body">
                  <td colspan="{{ $paymentTableCols }}">
                    @php
                      $wireProofs = $order->attachments->filter(function ($attachment) {
                          return in_array(strtolower((string) $attachment->extension), ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf']);
                      });
                    @endphp

                    @if ($wireProofs->count())
                      <div class="wire-proof-list">
                        @foreach ($wireProofs as $attachment)
                          @php
                            $isImage = in_array(strtolower((string) $attachment->extension), ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                          @endphp
                          <div class="wire-proof-item">
                            <div class="wire-proof-name">
                              <i class="fa fa-file"></i> {{ $attachment->name }}
                            </div>
                            <div class="wire-proof-actions">
                              <a href="{{ route('attachment.view', $attachment) }}" target="_blank" class="btn btn-sm btn-default">@lang('theme.button.open')</a>
                              @if ($isImage)
                                <a href="javascript:void(0)" class="btn btn-sm sf-btn-primary customer-wire-proof-preview"
                                  data-src="{{ route('attachment.view', $attachment) }}"
                                  data-name="{{ $attachment->name }}">
                                  @lang('app.preview')
                                </a>
                              @endif
                              <a href="{{ route('attachment.download', $attachment) }}" class="btn btn-sm btn-default">
                                <i class="fa fa-download"></i> @lang('theme.download')
                              </a>
                            </div>
                          </div>
                        @endforeach
                      </div>
                    @elseif($order->wire_transfer_proof_path)
                      @php
                        $proofUrl = \Illuminate\Support\Facades\Storage::url($order->wire_transfer_proof_path);
                        $proofName = $order->wire_transfer_proof_name ?: basename($order->wire_transfer_proof_path);
                        $proofExt = strtolower(pathinfo($proofName, PATHINFO_EXTENSION));
                        $proofIsImage = in_array($proofExt, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                      @endphp
                      <div class="wire-proof-item">
                        <div class="wire-proof-name">
                          <i class="fa fa-file"></i> {{ $proofName }}
                        </div>
                        <div class="wire-proof-actions">
                          <a href="{{ $proofUrl }}" target="_blank" class="btn btn-sm btn-default">@lang('theme.button.open')</a>
                          @if ($proofIsImage)
                            <a href="javascript:void(0)" class="btn btn-sm sf-btn-primary customer-wire-proof-preview"
                              data-src="{{ $proofUrl }}"
                              data-name="{{ $proofName }}">
                              @lang('app.preview')
                            </a>
                          @endif
                        </div>
                      </div>
                    @else
                      <span class="text-muted">@lang('theme.not_available')</span>
                    @endif
                  </td>
                </tr>
              @endif
            </tbody>
          </table>
          </div>
        </div>
      </div><!-- /.col-md-12 -->
    </div><!-- /.row -->
  </div><!-- /.container -->
</section>

@if ($order->refunds->count())
  <section id="refund-detail-section" name="refund-detail-section" class="account-section mb-3">
    <div class="container">
      <div class="row">
        <div class="col-12 px-3 px-md-0">
          <div class="sf-panel">
            <div class="sf-panel__head">@lang('theme.refunds')</div>
            <div class="sf-panel__body table-responsive">
            <table class="table order-detail-stack-table" id="buyer-payment-detail-table">
              <tbody>
                <tr class="buyer-payment-info-head">
                  <td>{{ trans('theme.return_goods') }}</td>
                  <td>{{ trans('theme.amount') }}</td>
                  <td>{{ trans('theme.status') }}</td>
                  <td>{{ trans('theme.created_at') }}</td>
                  <td>{{ trans('theme.updated_at') }}</td>
                </tr>

                @foreach ($order->refunds as $refund)
                  <tr class="buyer-payment-info-body">
                    <td>{!! get_yes_or_no($refund->return_goods) !!}</td>
                    <td>{{ get_formated_currency($refund->amount, 2, $order->currency_id) }}</td>
                    <td>{!! $refund->statusName() !!}</td>
                    <td>{{ $refund->created_at->diffForHumans() }}</td>
                    <td>{{ $refund->updated_at->diffForHumans() }}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
            </div>
          </div>
        </div><!-- /.col-md-12 -->
      </div><!-- /.row -->
    </div><!-- /.container -->
  </section>
@endif

@if (is_incevio_package_loaded('wallet') && is_wallet_credit_reward_enabled())
  @include('wallet::_order_page_credit_rewards', ['order' => $order])
@endif

<section id="order-detail-section" name="order-detail-section" class="account-section">
  <div class="container">
    <div class="row">
      <div class="col-12 px-3 px-md-0">
        <div class="order-detail-mobile-summary d-md-none">
          <div class="order-detail-mobile-summary__row">
            <span class="text-muted">@lang('theme.order_id')</span>
            <strong>{{ $order->order_number }}</strong>
          </div>
          <div class="order-detail-mobile-summary__row">
            <span class="text-muted">@lang('theme.status')</span>
            <span>{!! $order->orderStatus(true) . ' ' . $order->paymentStatusName() !!}</span>
          </div>
          <div class="order-detail-mobile-summary__row">
            <span class="text-muted">@lang('theme.order_amount')</span>
            <strong>{{ get_formated_currency($order->grand_total, 2, $order->currency_id) }}</strong>
          </div>
          @if ($orderTransactionFee > 0)
            <div class="order-detail-mobile-summary__row">
              <span class="text-muted">@lang('theme.transaction_fee')</span>
              <strong>{{ get_formated_currency($orderTransactionFee, 2, $order->currency_id) }}</strong>
            </div>
            <div class="order-detail-mobile-summary__row">
              <span class="text-muted">@lang('theme.total_paid')</span>
              <strong>{{ get_formated_currency($orderTotalPaid, 2, $order->currency_id) }}</strong>
            </div>
          @endif
          @if ($order->canResendEmolaPayment())
            <a href="#payment-detail-section" class="btn btn-warning btn-block btn-sm mt-2">
              <i class="fa fa-refresh"></i> @lang('theme.emola_resend_button')
            </a>
          @endif
        </div>

        <div class="sf-panel">
          <div class="sf-panel__head">
            <span>
              @lang('theme.order_detail')
              @if ($order->auction_bid_id)
                <span class="label label-primary ml-2"><i class="fa fa-gavel"></i> {{ trans('packages.auction.winner') }}</span>
              @endif
            </span>
          </div>
          <div class="sf-panel__body table-responsive order-detail-table-wrap">
          <table class="table order-detail-stack-table" id="buyer-order-table" name="buyer-order-table">
            <tbody>
              @unless ($order->is_digital)
                <tr class="order-detail-location-row">
                  <td colspan="3" class="order-detail-location-cell">
                    @include('theme::partials.order_delivery_location', ['order' => $order, 'compact' => true])
                  </td>
                </tr>
              @endunless

              <tr class="buyer-payment-info-head bg-light order-detail-address-head">
                <td>@lang('theme.shipping_address'):</td>
                <td colspan="2">@lang('theme.billing_address'):</td>
              </tr>
              <tr class="order-detail-address-body">
                <td data-label="@lang('theme.shipping_address')">
                  @if ($order->is_digital)
                    @lang('theme.donwloadable')
                  @else
                    {!! address_str_to_html($order->shipping_address) !!}
                  @endif
                </td>
                <td colspan="2" data-label="@lang('theme.billing_address')">{!! address_str_to_html($order->billing_address) !!}</td>
              </tr>

              <tr class="order-info-head order-detail-meta-row">
                <td class="order-detail-meta-cell" width="40%">
                  <h5 class="my-1">
                    <span>@lang('theme.order_id'): </span>
                    {{ $order->order_number }}

                    @if ($order->hasPendingCancellationRequest())
                      <span class="label label-warning pl-2 text-uppercase">
                        {{ trans('theme.' . $order->cancellation->request_type . '_requested') }}
                      </span>
                    @elseif($order->hasClosedCancellationRequest())
                      <span class="pl-2">
                        {{ trans('theme.' . $order->cancellation->request_type) }}
                      </span>
                      {!! $order->cancellation->statusName() !!}
                    @elseif($order->isCanceled())
                      <span class="pl-2">{!! $order->orderStatus() !!}</span>
                    @endif
                    @if ($order->dispute)
                      <span class="label label-danger pl-2 text-uppercase">@lang('theme.disputed')</span>
                    @endif
                  </h5>
                  <h5 class="mt-2">
                    <span>@lang('theme.order_time_date'): </span>{{ $order->created_at->toDayDateTimeString() }}
                  </h5>
                </td>
                <td class="order-detail-meta-cell store-info" width="40%">
                  <h5 class="my-1">
                    <span>@lang('theme.store'):</span>
                    @if ($order->shop->slug)
                      <a href="{{ route('show.store', $order->shop->slug) }}">
                        {{ $order->shop->name }}
                      </a>
                    @else
                      @lang('theme.store_not_available')
                    @endif
                  </h5>
                  <h5 class="mt-2">
                    <span>@lang('theme.status')</span>
                    {!! $order->orderStatus(true) . ' &nbsp; ' . $order->paymentStatusName() !!}
                  </h5>
                </td>
                <td class="order-detail-meta-cell order-amount" width="20%">
                  <h5 class="my-1">
                    <span>@lang('theme.order_amount'): </span>{{ get_formated_currency($order->grand_total, 2, $order->currency_id) }}
                  </h5>
                  @if ($orderTransactionFee > 0)
                    <h5 class="my-1">
                      <span>@lang('theme.transaction_fee'): </span>{{ get_formated_currency($orderTransactionFee, 2, $order->currency_id) }}
                    </h5>
                    <h5 class="my-1">
                      <span>@lang('theme.total_paid'): </span>{{ get_formated_currency($orderTotalPaid, 2, $order->currency_id) }}
                    </h5>
                  @endif
                </td>
              </tr> <!-- /.order-info-head -->

              @foreach ($order->inventories as $item)
                <tr class="order-body order-detail-product-row">
                  <td colspan="2" class="order-detail-product-cell">
                    <div class="order-detail-product-layout">
                    <div class="product-img-wrap">
                      <img src="{{ get_product_img_src($item, 'small') }}" alt="{{ $item->slug }}" title="{{ $item->slug }}" />
                    </div>
                    <div class="product-info">
                      {{ $item->pivot->item_description }}

                      <a href="{{ storefront_product_url($item) }}" class="ml-2" target="_blank" data-toggle="tooltip" data-placement="top" title="{{ trans('theme.show_product_page') }}">
                        <i class="fa fa-external-link" aria-hidden="true"></i>
                      </a>

                      @if ($order->cancellation && $order->cancellation->isItemInRequest($item->id))
                        <span class="label label-danger pl-2">
                          {{ trans('theme.' . $order->cancellation->request_type . '_requested') }}
                        </span>
                      @endif

                      <div class="order-info-amount">
                        <span>{{ get_formated_currency($item->pivot->unit_price, 2, $order->currency_id) }} x {{ $item->pivot->quantity }}</span>
                      </div>

                      <ul class="mailbox-attachments clearfix order-detail-attachments">
                        @if (isset($item->attachments))
                          @foreach ($item->attachments as $attachment)
                            <li>
                              <div class="mailbox-attachment-info">
                                {{-- <a href="{{ route('order.attachment.download', ['attachment' => $attachment, 'order' => $order->id, 'inventory' => $item->id]) }}" class="mailbox-attachment-name"><i class="fa fa-file"></i> {{ $attachment->name }}</a> --}}
                                {{--                        <span class="mailbox-attachment-size">{{ get_formated_file_size($attachment->size) }} --}}
                                <a href="{{ route('order.attachment.download', ['attachment' => $attachment, 'order' => $order->id, 'inventory' => $item->id]) }}" class="btn btn-default btn-sm pull-right">@lang('theme.download') <i class="fa fa-cloud-download"></i></a>
                                </span>
                              </div>
                            </li>
                          @endforeach

                          @if (!is_null($item->download_limit) && !is_null($item->pivot->download) && $item->download_limit <= $item->pivot->download)
                            <span class="text-danger"> You have reached maximum download limit</span>
                          @elseif (!is_null($item->download_limit) && !is_null($item->pivot->download) && $item->download_limit > $item->pivot->download)
                            <span class="text-info">@lang('theme.download_left', ['download_number' => $item->download_limit - $item->pivot->download, 'download_limit' => $item->download_limit])</span>
                          @endif
                        @endif
                      </ul>
                    </div>
                    </div>
                  </td>

                  @if ($loop->first)
                    <td rowspan="{{ $loop->count }}" class="order-actions order-detail-actions-cell">
                      <a href="{{ route('order.again', $order) }}" class="btn btn-default btn-sm btn-block">
                        <i class="fas fa-shopping-cart"></i> @lang('theme.order_again')
                      </a>

                      @unless ($order->isCanceled())
                        <a href="{{ route('order.invoice', $order) }}" class="btn btn-default btn-sm btn-block">
                          <i class="fas fa-cloud-download"></i> @lang('theme.invoice')
                        </a>

                        @if ($order->canBeCanceled())
                          {!! Form::model($order, ['method' => 'PUT', 'route' => ['order.cancel', $order]]) !!}
                          {!! Form::button('<i class="fas fa-times-circle-o"></i> ' . trans('theme.cancel_order'), ['type' => 'submit', 'class' => 'confirm btn btn-default btn-block flat', 'data-confirm' => trans('theme.confirm_action.cant_undo')]) !!}
                          {!! Form::close() !!}
                        @endif

                        @if ($order->canTrack())
                          <a href="{{ route('order.track', $order) }}" class="btn btn-black btn-sm btn-block">
                            <i class="fas fa-map-marker"></i> @lang('theme.button.track_order')
                          </a>
                        @endif

                        @if ($order->canResendEmolaPayment())
                          <a href="#payment-detail-section" class="btn btn-warning btn-sm btn-block">
                            <i class="fa fa-refresh"></i> @lang('theme.emola_resend_button')
                          </a>
                        @endif

                        @if ($order->canEvaluate())
                          <a href="{{ route('order.feedback', $order) }}" class="btn sf-btn-primary btn-sm btn-block">
                            @lang('theme.button.give_feedback')
                          </a>
                        @endif

                        @if ($order->isFulfilled())
                          @if ($order->canRequestReturn())
                            <a href="{{ route('cancellation.form', ['order' => $order, 'action' => 'return']) }}" class="modalAction btn btn-default btn-sm btn-block"><i class="fas fa-undo"></i> @lang('theme.return_items')</a>
                          @endif

                          @unless ($order->goods_received)
                            {!! Form::model($order, ['method' => 'PUT', 'route' => ['goods.received', $order]]) !!}
                            {!! Form::button(trans('theme.button.confirm_goods_received'), ['type' => 'submit', 'class' => 'confirm btn sf-btn-primary btn-block flat', 'data-confirm' => trans('theme.confirm_action.goods_received')]) !!}
                            {!! Form::close() !!}
                          @endunless
                        @endif
                      @endunless

                      @if ($order->dispute)
                        <a href="{{ route('dispute.open', $order) }}" class="btn btn-link btn-block" data-confirm="@lang('theme.confirm_action.open_a_dispute')">@lang('theme.dispute_detail')</a>
                      @else
                        <a href="{{ route('dispute.open', $order) }}" class="confirm btn btn-link btn-block" data-confirm="@lang('theme.confirm_action.open_a_dispute')">@lang('theme.button.open_dispute')</a>
                      @endif
                    </td>
                  @endif
                </tr> <!-- /.order-body -->
              @endforeach

              @if ($order->message_to_customer)
                <tr class="message_from_seller">
                  <td colspan="3">
                    <p>
                      <strong>@lang('theme.message_from_seller'): </strong> {{ $order->message_to_customer }}
                    </p>
                  </td>
                </tr>
              @endif

              @if ($order->buyer_note)
                <tr class="order-info-footer">
                  <td colspan="3">
                    <p class="order-detail-buyer-note">
                      <strong>@lang('theme.note'): </strong> {{ $order->buyer_note }}
                    </p>
                  </td>
                </tr>
              @endif
            </tbody>
          </table>
          </div>
        </div>
      </div><!-- /.col-md-12 -->
    </div><!-- /.row -->
  </div><!-- /.container -->
</section>

<section id="message-section" name="message-section" class="account-section">
  <div class="container mb-3">
    <div class="row">
      <div class="col-12 px-3 px-md-0">
        <div class="sf-panel">
          <div class="sf-panel__head">@lang('theme.section_headings.contact_seller')</div>
          <div class="sf-panel__body" style="padding:16px 18px;">
        <div class="message-list">
          <div class="row">
            {!! Form::open(['route' => ['order.conversation', $order], 'files' => true, 'id' => 'conversation-form', 'data-toggle' => 'validator', 'class' => 'order-detail-message-form sf-form w-100']) !!}
            <div class="col-12 col-md-6">
              <div class="sf-form-group">
                {!! Form::label('message', trans('theme.write_your_message'), ['class' => 'sf-form-label']) !!}
                {!! Form::textarea('message', null, ['class' => 'form-control sf-input', 'placeholder' => trans('theme.leave_message_to_seller'), 'rows' => '4', 'maxlength' => 500, 'required']) !!}
                <div class="help-block with-errors"></div>
              </div>
            </div>

            <div class="col-12 col-md-6">
              <div class="sf-form-group">
                {!! Form::label('photoInput', trans('theme.button.upload_photo'), ['class' => 'sf-form-label']) !!}
                {!! Form::file('photo') !!}
                <span class="help-block small">@lang('theme.help.upload_photo')</span>
              </div>

              @unless ($order->order_status_id == \App\Models\Order::STATUS_DELIVERED)
                <div class="checkbox">
                  <label>
                    {!! Form::checkbox('goods_received', 1, null, ['class' => 'i-check-blue']) !!} {{ trans('theme.goods_received') }}
                  </label>
                </div>
              @endunless
              {!! Form::button(trans('theme.button.send_message'), ['type' => 'submit', 'class' => 'btn sf-btn-primary btn-block btn-md-inline order-detail-send-btn']) !!}
            </div>
            {!! Form::close() !!}
          </div> <!-- /.row -->

          @if ($order->conversation)
            <div class="sf-panel__head mt-3" style="margin:16px -18px 0;border-radius:0;border-left:0;border-right:0;">
              <span>@lang('theme.message_history')</span>
            </div>

            <div class="sf-message-thread mt-3">
            @foreach ($order->conversation->replies->sortByDesc('created_at') as $msg)
              <div class="sf-message-bubble {{ $msg->customer_id ? 'sf-message-bubble--me' : '' }}">
                <div>
                  <div class="sf-message-bubble__meta">
                    <strong>
                      @if ($msg->customer_id)
                        @lang('theme.me')
                      @else
                        {{ $order->shop ? $order->shop->getQualifiedName(10) : trans('theme.seller') }}
                      @endif
                    </strong>
                    {{ $msg->created_at->toDayDateTimeString() }}
                  </div>
                  <div class="sf-message-bubble__body">
                    {!! strip_tags($msg->reply) !!}
                    @if ($attachment = optional($msg->attachments)->first())
                      <div class="sf-message-bubble__attach">
                        <a href="{{ get_storage_file_url($attachment->path, 'original') }}" target="_blank" rel="noopener">
                          <img src="{{ get_storage_file_url($attachment->path, 'tiny') }}" class="img-sm thumbnail" alt="">
                        </a>
                      </div>
                    @endif
                  </div>
                </div>
              </div>
            @endforeach

            <div class="sf-message-bubble sf-message-bubble--me">
              <div>
                <div class="sf-message-bubble__meta">
                  <strong>@lang('theme.me')</strong>
                  {{ $order->conversation->created_at->toDayDateTimeString() }}
                </div>
                <div class="sf-message-bubble__body">
                  {{ strip_tags($order->conversation->message) }}
                  @if ($attachment = optional($order->conversation->attachments)->first())
                    <div class="sf-message-bubble__attach">
                      <a href="{{ get_storage_file_url($attachment->path, 'original') }}" target="_blank" rel="noopener">
                        <img src="{{ get_storage_file_url($attachment->path, 'tiny') }}" class="img-sm thumbnail" alt="">
                      </a>
                    </div>
                  @endif
                </div>
              </div>
            </div>
            </div>
          @endif
        </div><!-- /.message-list -->
          </div>
        </div>
      </div><!-- /.col-md-12 -->
    </div><!-- /.row -->
  </div><!-- /.container -->
</section>
</div>
<!-- END CONTENT SECTION -->

<style>
  .order-detail-page .title {
    text-align: left;
  }

  #buyer-payment-detail-table tr.order-detail-table-divider > td {
    border-top: 2px solid #e0e0e0;
  }

  .order-detail-mobile-summary {
    background: #fff;
    border: 1px solid #e8edf2;
    border-radius: 14px;
    padding: 14px 16px;
    margin-bottom: 16px;
    box-shadow: 0 2px 12px rgba(15, 23, 42, 0.04);
  }

  .order-detail-mobile-summary__row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 12px;
    padding: 6px 0;
    font-size: 14px;
  }

  .order-detail-mobile-summary__row + .order-detail-mobile-summary__row {
    border-top: 1px solid #eef2f7;
  }

  .emola-resend-panel {
    display: flex;
    flex-direction: column;
    gap: 12px;
    max-width: 420px;
  }

  .emola-resend-actions {
    display: flex;
    flex-direction: column;
    gap: 8px;
  }

  .emola-resend-actions .btn {
    margin: 0;
    border-radius: 8px;
  }

  .order-detail-product-layout {
    display: flex;
    align-items: flex-start;
    gap: 12px;
  }

  .order-detail-product-layout .product-info {
    flex: 1;
    min-width: 0;
  }

  .order-detail-attachments {
    float: none !important;
    margin-top: 10px;
    padding-left: 0;
  }

  .wire-proof-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
  }

  .wire-proof-item {
    border: 1px solid #e8edf2;
    border-radius: 10px;
    padding: 10px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    background: #fff;
  }

  .wire-proof-name {
    font-weight: 500;
    word-break: break-word;
  }

  .wire-proof-actions {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
  }

  #customerWireProofPreviewImage {
    max-width: 100%;
    max-height: 70vh;
    object-fit: contain;
  }

  @media (max-width: 767px) {
    .order-detail-page .account-section,
    .order-detail-page.account-section {
      margin-bottom: 1rem;
    }

    .order-detail-table-wrap {
      margin-left: -4px;
      margin-right: -4px;
    }

    .order-detail-stack-table {
      border: 0;
    }

    .order-detail-stack-table tbody,
    .order-detail-stack-table tr {
      display: block;
      width: 100%;
    }

    .order-detail-stack-table td {
      display: block;
      width: 100% !important;
      max-width: 100%;
      border: none !important;
      border-bottom: 1px solid #eee !important;
      padding: 10px 12px;
      box-sizing: border-box;
    }

    .order-detail-stack-table tr.buyer-payment-info-head,
    .order-detail-stack-table tr.order-detail-address-head {
      display: none;
    }

    .order-detail-stack-table tr.buyer-payment-info-body td,
    .order-detail-stack-table tr.order-detail-address-body td {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 12px;
      text-align: right;
    }

    .order-detail-stack-table tr.buyer-payment-info-body td::before,
    .order-detail-stack-table tr.order-detail-address-body td::before {
      content: attr(data-label);
      font-weight: 600;
      color: #555;
      text-align: left;
      flex: 0 0 42%;
    }

    .order-detail-stack-table tr.buyer-payment-info-summary td {
      flex-direction: column;
      align-items: flex-start;
      text-align: left;
    }

    .order-detail-stack-table tr.buyer-payment-info-summary td::before {
      margin-bottom: 4px;
    }

    .order-detail-stack-table tr.order-info-head.order-detail-meta-row {
      background: #f8f9fa;
    }

    .order-detail-stack-table .order-detail-meta-cell {
      border-bottom: 1px solid #e9ecef !important;
    }

    .order-detail-stack-table .order-detail-meta-cell h5 {
      margin: 0 !important;
      font-size: 13px;
      line-height: 1.45;
    }

    .order-detail-stack-table .order-detail-product-cell {
      border-bottom: none !important;
      padding-bottom: 4px;
    }

    .order-detail-product-layout {
      flex-direction: row;
    }

    .order-detail-product-layout .product-img-wrap {
      width: 64px;
      flex-shrink: 0;
    }

    .order-detail-product-layout .product-img-wrap img {
      width: 64px;
      height: 64px;
      object-fit: cover;
      border-radius: 8px;
    }

    .order-detail-stack-table .order-detail-actions-cell {
      border-top: 1px solid #e9ecef !important;
      padding-top: 12px;
    }

    .order-detail-stack-table .order-detail-actions-cell .btn {
      margin-bottom: 8px;
      border-radius: 8px;
    }

    .order-detail-message-form .form-group,
    .order-detail-message-form .sf-form-group {
      margin-bottom: 12px;
    }

    .order-detail-send-btn {
      width: 100%;
    }

    .message-list-item .col-2 {
      display: none;
    }

    .message-list-item .col-8 {
      width: 100%;
      max-width: 100%;
      flex: 0 0 100%;
    }
  }
</style>

<div class="modal fade" id="customerWireProofPreviewModal" tabindex="-1" role="dialog" aria-labelledby="customerWireProofPreviewLabel">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="customerWireProofPreviewLabel">@lang('app.preview')</h4>
      </div>
      <div class="modal-body text-center">
        <img id="customerWireProofPreviewImage" src="" alt="">
      </div>
    </div>
  </div>
</div>

<script>
  $(function() {
    $('body').on('click', '.customer-wire-proof-preview', function(e) {
      e.preventDefault();
      var src = $(this).data('src');
      var name = $(this).data('name') || '';
      $('#customerWireProofPreviewImage').attr('src', src).attr('alt', name);
      $('#customerWireProofPreviewLabel').text(name || @json(trans('app.preview')));
      $('#customerWireProofPreviewModal').modal('show');
    });
  });
</script>

