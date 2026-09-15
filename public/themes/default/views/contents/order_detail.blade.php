<!-- CONTENT SECTION -->
@php
  $paymentTableCols = 4 + ($order->is_digital ? 0 : 2);
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
                <td data-label="@lang('theme.total')">{{ get_formated_currency($order->grand_total, 2, $order->currency_id) }}</td>
              </tr>

              <tr class="buyer-payment-info-head order-detail-table-divider">
                <td colspan="{{ $paymentSummaryColspan }}">@lang('theme.amount')</td>
                <td colspan="{{ $paymentSummaryColspan }}">@lang('theme.payment_method')</td>
                <td colspan="{{ $paymentSummaryLastColspan }}">@lang('theme.status')</td>
              </tr>

              <tr class="buyer-payment-info-body buyer-payment-info-summary">
                <td colspan="{{ $paymentSummaryColspan }}" data-label="@lang('theme.amount')">
                  {{ get_formated_currency($order->grand_total, 2, $order->currency_id) }}
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

@if ($order->isWireTransferRejected())
  <section id="wire-recovery-section" name="wire-recovery-section" class="account-section mb-3">
    <div class="container">
      <div class="row">
        <div class="col-12 px-3 px-md-0">
          <div class="sf-panel wire-recovery-panel">
            <div class="sf-panel__head">
              <span><i class="fa fa-exclamation-circle text-danger mr-2"></i> @lang('theme.wire_transfer_rejected')</span>
            </div>
            <div class="sf-panel__body wire-recovery-body">
              <div class="wire-recovery-reason">
                <strong>@lang('theme.wire_transfer_rejected_reason_label'):</strong>
                {{ $order->wire_transfer_rejection_reason }}
              </div>
              <p class="wire-recovery-help">@lang('theme.wire_transfer_rejected_help')</p>

              {!! Form::open([
                  'route' => ['order.paymentMethod.change', $order],
                  'method' => 'put',
                  'files' => true,
                  'id' => 'wire-recovery-form',
                  'class' => 'wire-recovery-form',
              ]) !!}

              <fieldset class="wire-recovery-field-group">
                <legend class="wire-recovery-label">@lang('theme.choose_payment_method')</legend>
                <div class="wire-recovery-methods" role="radiogroup">
                  @foreach ($paymentSwitchOptions as $method)
                    @php $isDefault = old('payment_method', 'wire') == $method->code; @endphp
                    <label class="wire-recovery-option">
                      <input
                        type="radio"
                        name="payment_method"
                        value="{{ $method->code }}"
                        class="wire-recovery-method-input"
                        @if ($isDefault) checked @endif
                        required
                      >
                      <span class="wire-recovery-option__label">{{ $method->name }}</span>
                    </label>
                  @endforeach
                </div>
              </fieldset>

              <div class="wire-recovery-field-group wire-recovery-field" data-for="wire">
                <label class="wire-recovery-label" for="wire-recovery-proof">@lang('app.attachment')</label>
                <label for="wire-recovery-proof" class="wire-recovery-upload">
                  <i class="fa fa-cloud-upload"></i>
                  <span class="wire-recovery-upload__text">@lang('theme.wire_transfer_proof_upload_hint')</span>
                  <span class="wire-recovery-upload__filename"></span>
                </label>
                <input
                  id="wire-recovery-proof"
                  name="wire_transfer_proof"
                  type="file"
                  class="wire-recovery-upload-input"
                  accept=".jpg,.jpeg,.png,.pdf"
                >
              </div>

              <div class="wire-recovery-field-group wire-recovery-field" data-for="mpesa">
                <label class="wire-recovery-label">@lang('packages.mpesa.mpesa_number')</label>
                <input type="text" name="mpesa_number" class="form-control" value="{{ old('mpesa_number') }}">
              </div>

              <div class="wire-recovery-field-group wire-recovery-field" data-for="emola">
                <label class="wire-recovery-label">@lang('theme.emola_number')</label>
                <input
                  type="text"
                  name="emola_number"
                  class="form-control"
                  value="{{ old('emola_number') }}"
                  inputmode="numeric"
                  maxlength="9"
                  pattern="^(86|87)[0-9]{7}$"
                >
              </div>

              <button type="submit" class="btn sf-btn-primary wire-recovery-submit">@lang('theme.submit_payment')</button>
              {!! Form::close() !!}
            </div>
          </div>
        </div><!-- /.col-md-12 -->
      </div><!-- /.row -->
    </div><!-- /.container -->
  </section>
@endif

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
                    @include('theme::partials.order_delivery_proof', ['order' => $order])
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
            <p class="text-muted" style="margin-top:0;">
              Chat with the seller in the same live chat. You can share this order’s details like a product card.
            </p>
            @if ($order->shop)
              <button type="button" class="btn sf-btn-primary sf-open-livechat">
                <i class="fa fa-comments"></i> {{ trans('theme.button.contact_seller') ?? 'Contact seller' }}
              </button>
              <span class="help-block" style="display:block;margin-top:8px;">
                After chat opens, tap <strong>Share</strong> on the order card to send order details.
              </span>
            @else
              <p class="text-muted">{{ trans('theme.chat_unavailable') ?? 'Seller chat is currently unavailable.' }}</p>
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

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

  .wire-recovery-panel {
    border: 1px solid #f7c8b8;
  }

  .wire-recovery-panel .sf-panel__head {
    background: #fff5f1;
    color: #c0392b;
  }

  .wire-recovery-body {
    padding: 18px;
    text-align: left;
  }

  .wire-recovery-reason {
    background: #fdecea;
    border: 1px solid #f5c6cb;
    border-radius: 10px;
    padding: 12px 14px;
    color: #6b1a12;
    font-size: 0.9rem;
    margin-bottom: 10px;
  }

  .wire-recovery-help {
    color: var(--secondary-text, #868e8e);
    font-size: 0.88rem;
    margin-bottom: 18px;
  }

  fieldset.wire-recovery-field-group {
    border: 0;
    padding: 0;
  }

  .wire-recovery-field-group {
    margin-bottom: 18px;
  }

  .wire-recovery-label,
  legend.wire-recovery-label {
    display: block;
    width: 100%;
    font-weight: 600;
    font-size: 0.85rem;
    color: var(--primary-text, #333e48);
    margin-bottom: 10px;
    padding: 0;
  }

  .wire-recovery-methods {
    display: flex;
    flex-direction: column;
    gap: 8px;
  }

  /*
   * Selection is a plain, always-visible native radio (accent-color'd) inside
   * a clickable card. The card highlight and the conditional field toggling
   * below both use the CSS :has() selector, so both work purely from HTML —
   * no JavaScript has to run, load, or avoid colliding with another script
   * on the page for the core interaction to work.
   */
  label.wire-recovery-option {
    display: flex;
    align-items: center;
    gap: 12px;
    border: 1.5px solid #e8edf2;
    border-radius: 10px;
    padding: 12px 14px;
    cursor: pointer;
    font-weight: 500;
    margin: 0;
    transition: border-color .15s ease, background .15s ease;
  }

  label.wire-recovery-option:hover {
    border-color: var(--primary-light, #ff944d);
  }

  label.wire-recovery-option:has(input:checked) {
    border-color: var(--primary-color, #ff6600);
    background: #fff8f3;
  }

  input.wire-recovery-method-input[type="radio"] {
    position: static;
    opacity: 1;
    width: 20px;
    height: 20px;
    margin: 0;
    flex: 0 0 20px;
    accent-color: var(--primary-color, #ff6600);
    cursor: pointer;
  }

  .wire-recovery-option__label {
    flex: 1 1 auto;
  }

  .wire-recovery-field {
    display: none;
  }

  #wire-recovery-form:has(.wire-recovery-method-input[value="wire"]:checked) .wire-recovery-field[data-for="wire"],
  #wire-recovery-form:has(.wire-recovery-method-input[value="mpesa"]:checked) .wire-recovery-field[data-for="mpesa"],
  #wire-recovery-form:has(.wire-recovery-method-input[value="emola"]:checked) .wire-recovery-field[data-for="emola"] {
    display: block;
  }

  /* JS fallback class (see script below) for browsers without :has() support. */
  .wire-recovery-field.js-field-visible {
    display: block;
  }

  .wire-recovery-upload {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 6px;
    border: 2px dashed #d8dee5;
    border-radius: 10px;
    padding: 22px 16px;
    text-align: center;
    cursor: pointer;
    color: var(--secondary-text, #868e8e);
    transition: border-color .15s ease, background .15s ease;
  }

  .wire-recovery-upload:hover {
    border-color: var(--primary-color, #ff6600);
    background: #fff8f3;
  }

  .wire-recovery-upload i {
    font-size: 1.3rem;
    color: var(--primary-color, #ff6600);
  }

  .wire-recovery-upload__filename:not(:empty) {
    font-weight: 600;
    color: var(--primary-text, #333e48);
  }

  /* Beats the vendor reset `input[type="file"]{display:block}` (higher
     specificity than a single class) which was making the native file
     picker button show up duplicated next to the styled dropzone. */
  input.wire-recovery-upload-input[type="file"] {
    display: none !important;
  }

  .wire-recovery-submit {
    min-width: 160px;
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

<script>
  // Bank-transfer-rejected recovery form: which payment method is selected,
  // and which field(s) it needs, is driven entirely by CSS :has() (see the
  // <style> block above) — a native radio + a plain form, no class toggling
  // required for the core interaction to work. This script is a pure
  // enhancement on top of that: it (a) sets `required` correctly on the
  // field that's actually showing so validation doesn't block on a hidden
  // one, (b) mirrors the same visibility with a fallback class for browsers
  // without :has() support, and (c) shows the picked filename. None of this
  // runs jQuery and none of it is required for selecting a payment method
  // or submitting the form — if this script fails to load or throws, the
  // form still works.
  (function () {
    'use strict';

    function init() {
      var form = document.getElementById('wire-recovery-form');
      if (!form) {
        return;
      }

      var radios = form.querySelectorAll('.wire-recovery-method-input');
      var fields = form.querySelectorAll('.wire-recovery-field');
      var fileInput = document.getElementById('wire-recovery-proof');
      var filenameEl = form.querySelector('.wire-recovery-upload__filename');

      function syncFields() {
        var checked = form.querySelector('.wire-recovery-method-input:checked');
        var selected = checked ? checked.value : null;

        for (var i = 0; i < fields.length; i++) {
          var field = fields[i];
          var isMatch = field.getAttribute('data-for') === selected;

          field.classList.toggle('js-field-visible', isMatch);

          var inputs = field.querySelectorAll('input');
          for (var j = 0; j < inputs.length; j++) {
            inputs[j].required = isMatch;
          }
        }
      }

      for (var i = 0; i < radios.length; i++) {
        radios[i].addEventListener('change', syncFields);
      }

      syncFields();

      if (fileInput && filenameEl) {
        fileInput.addEventListener('change', function () {
          filenameEl.textContent = (fileInput.files && fileInput.files[0]) ? fileInput.files[0].name : '';
        });
      }
    }

    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', init);
    } else {
      init();
    }
  })();
</script>

