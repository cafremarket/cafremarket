@if ($messages->count() > 0)
  @php
    $search_q = isset($search_q) ? $search_q : null;
  @endphp

  <div class="sf-message-list">
    <div class="sf-message-list__count">
      {{ trans('theme.of_total', ['first' => $messages->firstItem(), 'last' => $messages->lastItem(), 'total' => $messages->total()]) . ' ' . trans('theme.my_messages') }}
    </div>

    @foreach ($messages as $message)
      <div class="sf-message-row" id="item_{{ $message->id }}">
        <div class="sf-message-row__shop">
          @if ($message->shop)
            <a href="{{ route('show.store', $message->shop->slug) }}">
              @include('theme::partials._shop_logo_frame', ['shop' => $message->shop, 'frameSize' => 'sm', 'thumbSize' => 'thumbnail', 'fullSize' => 'thumbnail'])
              {!! $message->shop->getQualifiedName(10) !!}
            </a>
          @elseif($message->shop_id)
            {{ trans('theme.store') }}
          @else
            <a href="{{ url('/') }}">
              <img src="{{ get_logo_url('system', 'logo') }}" alt="{{ trans('theme.logo') }}" title="{{ trans('theme.logo') }}">
              {{ get_platform_title() }}
            </a>
          @endif
        </div>

        <div class="sf-message-row__subject">
          <a href="{{ route('message.show', $message) }}" class="{{ $message->isUnread() ? 'unread' : '' }}">
            <span>{!! highlightWords($message->subject, $search_q) !!}</span>
            — {!! highlightWords(\Illuminate\Support\Str::limit(strip_tags($message->lastReply->reply ?? $message->message), max(180 - strlen($message->subject), 0)), $search_q) !!}
          </a>
        </div>

        <div class="sf-message-row__meta">
          @if ($message->replies_count)
            <span class="label label-primary" data-toggle="tooltip" data-placement="top" title="{{ trans('app.replies') }}">{{ $message->replies_count }}</span>
          @endif

          @if ($message->attachments_count)
            <i class="fas fa-paperclip" data-toggle="tooltip" data-placement="top" title="{{ trans('app.attachments') }}"></i>
          @endif

          @if ($message->isUnread())
            {!! $message->statusName() !!}
          @endif

          @if ($message->about())
            {!! $message->about() !!}
          @endif
        </div>

        <div class="sf-message-row__date">
          {{ $message->lastReply ? $message->lastReply->updated_at->diffForHumans() : $message->updated_at->diffForHumans() }}
        </div>

        <div class="sf-message-row__actions">
          @if ($message->order_id)
            <a href="{{ route('order.detail', $message->order_id) }}" data-toggle="tooltip" data-placement="left" data-title="{{ trans('theme.button.order_detail') }}"><i class="fas fa-shopping-cart"></i></a>
          @endif

          @if ($message->product_id)
            <a href="{{ storefront_product_url($message->item) }}" data-toggle="tooltip" data-placement="left" data-title="{{ trans('theme.button.view_product_details') }}"><i class="far fa-external-link"></i></a>
          @endif

          <a href="{{ route('message.archive', $message) }}" class="confirm" data-toggle="tooltip" data-placement="left" data-title="{{ trans('theme.archive') }}"><i class="fas fa-trash-o"></i></a>
        </div>
      </div>
    @endforeach
  </div>
@else
  <div class="sf-empty-state">
    <i class="fas fa-envelope" aria-hidden="true"></i>
    <p>@lang('theme.nothing_found')</p>
  </div>
@endif

<div class="row pagenav-wrapper mb-3">
  {{ $messages->links('theme::layouts.pagination') }}
</div>
