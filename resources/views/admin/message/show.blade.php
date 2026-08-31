@extends('admin.layouts.master')

@section('page_title')
  {{ trans('app.message') }}
@endsection

@section('content')
  <div class="admin-mailbox">
    <aside class="admin-mailbox__sidebar no-print">
      @include('admin.message._left_nav')
    </aside>

    <div class="admin-mailbox__main">
      @if ($message->user_id)
        <div class="alert alert-info alert-dismissible">
          <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
          <strong>{{ trans('app.important') }}: </strong>
          {!! trans('app.message_send_by_staff', ['user' => $message->user->getName()]) !!}
        </div>
      @endif

      @include('admin.partials.ui.card_start', [
        'title' => trans('app.message'),
        'icon' => 'fa-envelope-open',
        'class' => 'admin-card--flush',
        'bodyClass' => 'admin-card__body--flush-top admin-mailbox-read',
      ])

          <div class="admin-mailbox-read__header">
            <div class="admin-mailbox-read__avatar">
              <img src="{{ get_avatar_src($message->customer, 'tiny') }}" class="img-circle img-sm" alt="">
              @can('view', $message->customer)
                @if ($message->customer->id)
                  <a href="javascript:void(0)" data-link="{{ route('admin.admin.customer.show', $message->customer) }}" class="ajax-modal-btn small">{{ trans('app.view_detail') }}</a>
                @endif
              @endcan
            </div>

            <div class="admin-mailbox-read__meta">
              <h3 class="admin-mailbox-read__subject">{!! $message->subject !!}</h3>
              <div class="admin-mailbox-read__from">
                {{ $message->user_id ? trans('app.to') : trans('app.from') }}:
                <strong>{{ $message->customer->getName() }}</strong>
                @if ($message->phone)
                  <span class="admin-mailbox-read__phone"><i class="fa fa-phone"></i> {{ $message->phone }}</span>
                @endif
                @if ($message->order)
                  &lt;{{ get_customer_email_from_order($message->order) }}&gt;
                @endif
                <span class="admin-mailbox-read__date">{{ $message->updated_at->toDayDateTimeString() }}</span>
              </div>
              @if ($message->order)
                <div class="admin-mailbox-read__order">
                  {{ trans('app.order') }}:
                  @can('view', $message->order)
                    <a href="{{ route('admin.order.order.show', $message->order->id) }}"><strong>{{ $message->order->order_number }}</strong></a>
                  @else
                    <strong>{{ $message->order->order_number }}</strong>
                  @endcan
                </div>
              @endif
            </div>
          </div>

          <div class="admin-mailbox__toolbar no-print">
            <div class="btn-group">
              @if ($message->label < \App\Models\Message::LABEL_DRAFT)
                @can('reply', $message)
                  <a href="javascript:void(0)" data-link="{{ route('admin.support.message.reply', $message) }}" class="ajax-modal-btn btn btn-default btn-sm">
                    <i class="fa fa-reply"></i> {{ trans('app.reply') }}
                  </a>
                  <a href="javascript:void(0)" data-link="{{ route('admin.support.message.reply', [$message, true]) }}" class="ajax-modal-btn btn btn-default btn-sm">
                    <i class="fa fa-reply"></i> {{ trans('app.reply_with_template') }}
                  </a>
                @endcan

                @if ($message->label == \App\Models\Message::LABEL_INBOX)
                  <a href="{{ route('admin.support.message.update', [$message, \App\Models\Message::STATUS_UNREAD, 'status']) }}" class="btn btn-default btn-sm">
                    <i class="fa fa-envelope-o"></i> {{ trans('app.mark_as_unread') }}
                  </a>
                @endif

                @can('update', $message)
                  <a href="{{ route('admin.support.message.update', [$message, \App\Models\Message::LABEL_SPAM]) }}" class="btn btn-default btn-sm">
                    <i class="fa fa-filter"></i> {{ trans('app.mark_as_spam') }}
                  </a>
                  <a href="{{ route('admin.support.message.update', [$message, \App\Models\Message::LABEL_TRASH]) }}" class="btn btn-default btn-sm">
                    <i class="fa fa-trash-o"></i> {{ trans('app.trash') }}
                  </a>
                @endcan
              @else
                @if ($message->label == \App\Models\Message::LABEL_DRAFT)
                  <a href="javascript:void(0)" data-link="{{ route('admin.support.message.edit', $message) }}" class="ajax-modal-btn btn btn-default btn-sm">
                    <i class="fa fa-send"></i> {{ trans('app.open') }}
                  </a>
                @endif

                @if ($message->label > \App\Models\Message::LABEL_DRAFT)
                  @can('update', $message)
                    <a href="{{ route('admin.support.message.update', [$message, \App\Models\Message::LABEL_INBOX]) }}" class="btn btn-default btn-sm">
                      <i class="fa fa-inbox"></i> {{ trans('app.move_to_inbox') }}
                    </a>
                  @endcan
                @endif

                @can('delete', $message)
                  <a href="{{ url('admin/support/message/destroy/' . $message->id) }}" class="confirm ajax-silent btn btn-default btn-sm">
                    <i class="glyphicon glyphicon-trash"></i> {{ trans('app.delete_permanently') }}
                  </a>
                @endcan
              @endif
            </div>

            <button type="button" class="btn btn-default btn-sm" onclick="window.print();">
              <i class="fa fa-print"></i> {{ trans('app.print') }}
            </button>
          </div>

          <div class="admin-mailbox-read__body">
            {!! $message->message !!}

            @if (is_incevio_package_loaded('smartForm'))
              @include('smartForm::partials.extra_info_for_message')
            @endif
          </div>
      @include('admin.partials.ui.card_body_end')

        @if ($message->attachments->count())
          <div class="admin-card__footer">
            @include('admin.message._view_attachments')
          </div>
        @endif

        @unless ($message->label == \App\Models\Message::LABEL_DRAFT)
          @if ($message->replies->count())
            <div class="admin-card__footer admin-mailbox-read__replies">
              <strong class="admin-mailbox-read__replies-title">{{ trans('app.replies') }}</strong>
              @foreach ($message->replies as $reply)
                @include('admin.partials._reply_conversations')
              @endforeach
            </div>
          @endif
        @endunless

        <div class="admin-card__footer admin-mailbox__toolbar admin-mailbox__toolbar--bottom no-print">
          @if ($message->label < \App\Models\Message::LABEL_DRAFT)
            <div class="pull-right">
              @can('reply', $message)
                <a href="javascript:void(0)" data-link="{{ route('admin.support.message.reply', $message) }}" class="ajax-modal-btn btn btn-default btn-sm">
                  <i class="fa fa-reply"></i> {{ trans('app.reply') }}
                </a>
                <a href="javascript:void(0)" data-link="{{ route('admin.support.message.reply', [$message, true]) }}" class="ajax-modal-btn btn btn-default btn-sm">
                  <i class="fa fa-reply"></i> {{ trans('app.reply_with_template') }}
                </a>
              @endcan
            </div>

            @can('update', $message)
              <a href="{{ route('admin.support.message.update', [$message, \App\Models\Message::LABEL_TRASH]) }}" class="btn btn-default btn-sm">
                <i class="fa fa-trash-o"></i> {{ trans('app.trash') }}
              </a>
              <a href="{{ route('admin.support.message.update', [$message, \App\Models\Message::LABEL_SPAM]) }}" class="btn btn-default btn-sm">
                <i class="fa fa-filter"></i> {{ trans('app.mark_as_spam') }}
              </a>
            @endcan
          @else
            @if ($message->label > \App\Models\Message::LABEL_DRAFT)
              @can('update', $message)
                <a href="{{ route('admin.support.message.update', [$message, \App\Models\Message::LABEL_INBOX]) }}" class="btn btn-default btn-sm">
                  <i class="fa fa-inbox"></i> {{ trans('app.move_to_inbox') }}
                </a>
              @endcan
            @endif

            @can('delete', $message)
              <a href="{{ url('admin/support/message/destroy/' . $message->id) }}" class="confirm ajax-silent btn btn-default btn-sm">
                <i class="glyphicon glyphicon-trash"></i> {{ trans('app.delete_permanently') }}
              </a>
            @endcan
          @endif

          <button type="button" class="btn btn-default btn-sm" onclick="window.print();">
            <i class="fa fa-print"></i> {{ trans('app.print') }}
          </button>
        </div>
      </div>
    </div>
  </div>
@endsection
