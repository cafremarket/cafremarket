@if ($disputes->count() > 0)
  <div class="sf-order-list">
    @foreach ($disputes as $dispute)
      <article class="sf-order-card">
        <div class="sf-order-card__head">
          <div>
            <p class="sf-order-card__meta">
              <span>@lang('theme.order_id'):</span>
              {{ $dispute->order->order_number }}
            </p>
            <p class="sf-order-card__meta">
              <span>@lang('theme.order_time_date'):</span>
              {{ $dispute->order->created_at->toDayDateTimeString() }}
            </p>
          </div>

          <div>
            <p class="sf-order-card__meta">
              <span>@lang('theme.store'):</span>
              @if ($dispute->shop->slug)
                <a href="{{ route('show.store', $dispute->shop->slug) }}">{{ $dispute->shop->name }}</a>
              @else
                @lang('theme.seller')
              @endif
            </p>
            <p class="sf-order-card__meta">
              <span>@lang('theme.status'):</span>
              {!! $dispute->order->dispute->statusName() !!}
            </p>
          </div>

          <div>
            <p class="sf-order-card__meta">
              <span>@lang('theme.order_amount'):</span>
              <strong>{{ get_formated_currency($dispute->order->grand_total, 2, $dispute->order->currency_id) }}</strong>
            </p>
            <div class="mt-1">
              <a class="btn btn-xs btn-default" href="{{ route('order.detail', $dispute->order) }}">@lang('theme.button.order_detail')</a>
              <a class="btn btn-xs btn-default" href="{{ route('order.detail', $dispute->order) . '#message-section' }}">@lang('theme.button.contact_seller')</a>
            </div>
          </div>
        </div>

        <div class="sf-order-card__body">
          <div class="sf-order-card__items">
            @foreach ($dispute->order->inventories as $item)
              <div class="sf-order-item">
                <img class="sf-order-item__img" src="{{ get_storage_file_url(optional($item->image)->path, 'small') }}" alt="{{ $item->slug }}" title="{{ $item->slug }}" />

                <div class="sf-order-item__info">
                  <a href="{{ storefront_product_url($item) }}">{{ $item->pivot->item_description }}</a>
                  <div class="sf-order-item__amount">
                    {{ get_formated_currency($item->pivot->unit_price, 2, $dispute->order->currency_id) }} x {{ $item->pivot->quantity }}
                  </div>
                  @if ($dispute->product_id == $item->product_id)
                    <span class="label label-danger">@lang('theme.disputed')</span>
                  @endif
                </div>
              </div>
            @endforeach
          </div>

          <div class="sf-order-card__actions">
            @if ($dispute->order->refunds->count())
              <a href="{{ route('order.detail', $dispute->order) . '#refund-detail-section' }}" class="btn sf-btn-primary btn-sm">
                @lang('theme.refund_details')
              </a>
            @endif

            <a href="{{ route('dispute.open', $dispute->order) }}" class="btn btn-default btn-sm">
              {!! trans('theme.dispute_details') !!}
            </a>

            @if ($dispute->isOpen())
              {!! Form::open(['route' => ['dispute.markAsSolved', $dispute]]) !!}
              {!! Form::button(trans('theme.mark_as_solved'), ['type' => 'submit', 'class' => 'confirm btn sf-btn-primary btn-sm btn-block flat']) !!}
              {!! Form::close() !!}
            @endif
          </div>
        </div>

        @if ($dispute->order->message_to_customer)
          <p class="sf-order-card__note">
            <strong>@lang('theme.message_from_seller'):</strong> {{ $dispute->order->message_to_customer }}
          </p>
        @endif

        @if ($dispute->order->buyer_note)
          <p class="sf-order-card__note">
            <span>@lang('theme.note'):</span> {{ $dispute->order->buyer_note }}
          </p>
        @endif
      </article>
    @endforeach
  </div>
@else
  <div class="sf-empty-state">
    <i class="fas fa-undo-alt" aria-hidden="true"></i>
    <p>@lang('theme.nothing_found')</p>
  </div>
@endif

<div class="row pagenav-wrapper mb-3">
  {{ $disputes->links('theme::layouts.pagination') }}
</div>
