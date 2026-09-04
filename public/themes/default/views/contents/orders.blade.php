@if ($orders->count() > 0)
  <div class="sf-account-toolbar">
    <form action="{{ url('/my/orders') }}" method="get" class="sf-account-toolbar__search">
      <input type="text" class="form-control" name="q" value="{{ request('q') }}" placeholder="{{ trans('theme.order_id') }}...">
      <button type="submit" class="btn sf-btn-primary"><i class="fa fa-search" aria-hidden="true"></i></button>
    </form>
  </div>

  <div class="sf-order-list">
    @foreach ($orders as $order)
      <article class="sf-order-card">
        <div class="sf-order-card__head">
          <div>
            <p class="sf-order-card__meta">
              <span>@lang('theme.order_id'):</span>
              <a href="{{ route('order.detail', $order) }}" data-toggle="tooltip" data-placement="left" title="{{ trans('theme.button.order_detail') }}">{{ $order->order_number }}</a>

              @if ($order->hasPendingCancellationRequest())
                <span class="label label-warning text-uppercase">
                  {{ trans('theme.' . $order->cancellation->request_type . '_requested') }}
                </span>
              @elseif($order->hasClosedCancellationRequest())
                <span>{{ trans('theme.' . $order->cancellation->request_type) }}</span>
                {!! $order->cancellation->statusName() !!}
              @elseif($order->isCanceled())
                <span>{!! $order->orderStatus() !!}</span>
              @endif

              @if ($order->dispute)
                <span class="label label-danger text-uppercase">@lang('theme.disputed')</span>
              @endif
            </p>
            <p class="sf-order-card__meta">
              <span>@lang('theme.product_type'):</span> {{ $order->type }}
            </p>
          </div>

          <div>
            <p class="sf-order-card__meta">
              <span>@lang('theme.store'):</span>
              @if ($order->shop->slug)
                <a href="{{ route('show.store', $order->shop->slug) }}">{{ $order->shop->name }}</a>
              @else
                @lang('theme.store_not_available')
              @endif
              <a href="{{ route('order.detail', $order) . '#message-section' }}" class="btn btn-xs btn-default ml-1">
                @lang('theme.button.contact_seller')
              </a>
            </p>
            <p class="sf-order-card__meta">
              <span>@lang('theme.status'):</span>
              {!! $order->orderStatus(true) . ' &nbsp; ' . $order->paymentStatusName() !!}
            </p>
          </div>

          <div>
            <p class="sf-order-card__meta">
              <span>@lang('theme.order_amount'):</span>
              <strong>{{ get_formated_currency($order->grand_total, 2, $order->currency_id) }}</strong>
            </p>
            <p class="sf-order-card__meta">
              <span>@lang('theme.order_date'):</span>
              {{ $order->created_at->toDayDateTimeString() }}
            </p>
          </div>
        </div>

        <div class="sf-order-card__body">
          <div class="sf-order-card__items">
            @foreach ($order->inventories as $item)
              <div class="sf-order-item">
                <img class="sf-order-item__img lazy" src="{{ get_storage_file_url(optional($item->image)->path, 'tiny') }}" data-src="{{ get_storage_file_url(optional($item->image)->path, 'small') }}" alt="{{ $item->slug }}" title="{{ $item->slug }}" />

                <div class="sf-order-item__info">
                  {{ $item->pivot->item_description }}
                  <a href="{{ storefront_product_url($item) }}" class="ml-1" target="_blank" data-toggle="tooltip" data-placement="top" title="{{ trans('theme.show_product_page') }}">
                    <i class="fa fa-external-link" aria-hidden="true"></i>
                  </a>

                  @if (is_incevio_package_loaded('wallet') && is_wallet_credit_reward_enabled())
                    @if ($item->pivot->credit_back_amount)
                      @include('wallet::_credit_back_amount_badge', ['amount' => get_formated_currency($item->pivot->credit_back_amount)])
                    @endif
                  @endif

                  @if ($order->cancellation && $order->cancellation->isItemInRequest($item->id))
                    <span class="label label-danger">
                      {{ trans('theme.' . $order->cancellation->request_type . '_requested') }}
                    </span>
                  @endif

                  <div class="sf-order-item__amount">
                    {{ get_formated_currency($item->pivot->unit_price, 2, $order->currency_id) }} x {{ $item->pivot->quantity }}
                  </div>

                  @if (isset($item->attachments))
                    <ul class="mailbox-attachments clearfix mt-2 mb-0">
                      @foreach ($item->attachments as $attachment)
                        <li>
                          <div class="mailbox-attachment-info">
                            <a href="{{ route('order.attachment.download', ['attachment' => $attachment, 'order' => $order->id, 'inventory' => $item->id]) }}" class="btn btn-default btn-sm">
                              @lang('theme.download') <i class="fa fa-cloud-download"></i>
                            </a>
                          </div>
                        </li>
                      @endforeach

                      @if (!is_null($item->download_limit) && !is_null($item->pivot->download) && $item->download_limit <= $item->pivot->download)
                        <span class="text-danger">@lang('theme.maximum_download_limit_reached')</span>
                      @elseif (!is_null($item->download_limit) && !is_null($item->pivot->download) && $item->download_limit > $item->pivot->download)
                        <span class="text-info">@lang('theme.download_left', ['download_number' => $item->download_limit - $item->pivot->download, 'download_limit' => $item->download_limit])</span>
                      @endif
                    </ul>
                  @endif
                </div>
              </div>
            @endforeach
          </div>

          <div class="sf-order-card__actions">
            <a href="{{ route('order.again', $order) }}" class="btn btn-default btn-sm">
              <i class="fas fa-shopping-cart"></i> @lang('theme.order_again')
            </a>

            @unless ($order->isCanceled())
              <a href="{{ route('order.invoice', $order) }}" class="btn btn-default btn-sm">
                <i class="fa fa-file-pdf-o" aria-hidden="true"></i> @lang('theme.invoice')
              </a>

              @if ($order->dispute)
                <a href="{{ route('dispute.open', $order) }}" class="btn btn-default btn-sm" data-confirm="@lang('theme.confirm_action.open_a_dispute')">
                  <i class="fa fa-thumbs-o-down" aria-hidden="true"></i>
                  @lang('theme.dispute_detail')
                </a>
              @else
                <a href="{{ route('dispute.open', $order) }}" class="confirm btn btn-default btn-sm" data-confirm="@lang('theme.confirm_action.open_a_dispute')">
                  <i class="fa fa-thumbs-o-down" aria-hidden="true"></i>
                  @lang('theme.button.open_dispute')
                </a>
              @endif

              @if ($order->canBeCanceled())
                {!! Form::model($order, ['method' => 'PUT', 'route' => ['order.cancel', $order]]) !!}
                {!! Form::button('<i class="fas fa-times-circle-o"></i> ' . trans('theme.cancel_order'), ['type' => 'submit', 'class' => 'confirm btn btn-default btn-sm btn-block flat', 'data-confirm' => trans('theme.confirm_action.cant_undo')]) !!}
                {!! Form::close() !!}
              @endif

              @if ($order->canTrack())
                <a href="{{ route('order.track', $order) }}" class="btn btn-default btn-sm">
                  <i class="fas fa-map-marker"></i> @lang('theme.button.track_order')
                </a>
              @endif

              @if ($order->canEvaluate())
                <a href="{{ route('order.feedback', $order) }}" class="btn sf-btn-primary btn-sm">
                  <i class="fa fa-thumbs-o-up" aria-hidden="true"></i>
                  @lang('theme.button.give_feedback')
                </a>
              @endif

              @if ($order->isFulfilled())
                @if ($order->canRequestReturn())
                  <a href="{{ route('cancellation.form', ['order' => $order, 'action' => 'return']) }}" class="modalAction btn btn-default btn-sm">
                    <i class="fas fa-undo"></i> @lang('theme.return_items')
                  </a>
                @endif

                @unless ($order->goods_received)
                  {!! Form::model($order, ['method' => 'PUT', 'route' => ['goods.received', $order]]) !!}
                  {!! Form::button(trans('theme.button.confirm_goods_received'), ['type' => 'submit', 'class' => 'confirm btn sf-btn-primary btn-sm btn-block flat', 'data-confirm' => trans('theme.confirm_action.goods_received')]) !!}
                  {!! Form::close() !!}
                @endunless
              @endif
            @endunless
          </div>
        </div>

        @if ($order->message_to_customer)
          <p class="sf-order-card__note">
            <strong>@lang('theme.message_from_seller'):</strong> {{ $order->message_to_customer }}
          </p>
        @endif

        @if ($order->buyer_note)
          <p class="sf-order-card__note">
            <span>@lang('theme.note'):</span> {{ $order->buyer_note }}
          </p>
        @endif
      </article>
    @endforeach
  </div>
@else
  <div class="sf-empty-state">
    <i class="fas fa-shopping-bag" aria-hidden="true"></i>
    <p>@lang('theme.no_order_history')</p>
    <a href="{{ url('/') }}" class="btn sf-btn-primary btn-sm">@lang('theme.button.shop_now')</a>
  </div>
@endif

<div class="row pagenav-wrapper mb-3">
  {{ $orders->links('theme::layouts.pagination') }}
</div>
