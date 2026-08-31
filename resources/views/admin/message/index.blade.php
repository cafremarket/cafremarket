@extends('admin.layouts.master')

@section('page_title')
  {{ $search_q ? trans('app.search_result') : get_msg_folder_name_from_label($requestLabel) }}
@endsection

@section('content')
  @php
    $search_q = isset($search_q) ? $search_q : null;
    $requestLabel = isset(request()->route()->parameters['label']) ? request()->route()->parameters['label'] : 1;
  @endphp

  <div class="admin-mailbox">
    <aside class="admin-mailbox__sidebar">
      @include('admin.message._left_nav')
    </aside>

    <div class="admin-mailbox__main">
      @include('admin.partials.ui.card_start', [
        'title' => $search_q ? trans('app.search_result') : get_msg_folder_name_from_label($requestLabel),
        'icon' => 'fa-envelope',
        'class' => 'admin-card--flush',
        'bodyClass' => 'admin-card__body--flush-top',
        'actions' => view('admin.message._header_search', compact('search_q'))->render(),
      ])
          <div class="admin-mailbox__toolbar">
            <div class="btn-group">
              <button type="button" class="btn btn-sm btn-default checkbox-toggle" title="{{ trans('app.select_all') }}" data-toggle="tooltip">
                <i class="fa fa-square-o"></i>
              </button>
              <button type="button" class="btn btn-sm btn-default dropdown-toggle" data-toggle="dropdown">
                <span class="caret"></span>
              </button>
              <ul class="dropdown-menu" role="menu">
                <li><a href="javascript:void(0)" data-link="{{ route('admin.support.message.massUpdate', [App\Models\Message::STATUS_NEW, 'status']) }}" class="massAction" data-doafter="reload"><i class="fa fa-envelope-o"></i> {{ trans('app.new') }}</a></li>
                <li><a href="javascript:void(0)" data-link="{{ route('admin.support.message.massUpdate', [App\Models\Message::STATUS_READ, 'status']) }}" class="massAction" data-doafter="reload"><i class="fa fa-envelope-open"></i> {{ trans('app.read') }}</a></li>
                <li><a href="javascript:void(0)" data-link="{{ route('admin.support.message.massUpdate', [App\Models\Message::STATUS_UNREAD, 'status']) }}" class="massAction" data-doafter="reload"><i class="fa fa-envelope"></i> {{ trans('app.unread') }}</a></li>
                <li class="divider"></li>
                @if ($requestLabel <= \App\Models\Message::LABEL_DRAFT)
                  <li><a href="javascript:void(0)" data-link="{{ route('admin.support.message.massUpdate', [App\Models\Message::LABEL_SPAM, 'label']) }}" class="massAction" data-doafter="remove"><i class="fa fa-filter"></i> {{ trans('app.spam') }}</a></li>
                  <li><a href="javascript:void(0)" data-link="{{ route('admin.support.message.massUpdate', [App\Models\Message::LABEL_TRASH, 'label']) }}" class="massAction" data-doafter="remove"><i class="fa fa-trash"></i> {{ trans('app.trash') }}</a></li>
                @else
                  <li><a href="javascript:void(0)" data-link="{{ route('admin.support.message.massUpdate', [App\Models\Message::LABEL_INBOX, 'label']) }}" class="massAction" data-doafter="remove"><i class="fa fa-inbox"></i> {{ trans('app.move_to_inbox') }}</a></li>
                @endif
                @if ($requestLabel > \App\Models\Message::LABEL_DRAFT)
                  <li><a href="javascript:void(0)" data-link="{{ route('admin.support.message.massDestroy') }}" class="massAction" data-doafter="remove"><i class="fa fa-trash"></i> {{ trans('app.delete_permanently') }}</a></li>
                @endif
              </ul>
            </div>
            <button type="button" class="btn btn-default btn-sm" onclick="window.location.reload();" title="{{ trans('app.refresh') }}" data-toggle="tooltip">
              <i class="fa fa-refresh"></i>
            </button>
            @if ($search_q)
              <span class="admin-mailbox__search-hint">{{ trans('app.search_result_for') . " '" . $search_q . "'" }}</span>
            @endif
            <div class="admin-mailbox__pagination">
              @if ($messages->count())
                {{ $messages->links('admin.partials._pagination_btn') }}
              @endif
            </div>
          </div>

          <div class="table-responsive admin-mailbox__list" id="massSelectArea">
            <table class="table table-hover admin-table admin-mailbox-table">
              <tbody>
                @forelse($messages as $message)
                  <tr id="item_{{ $message->id }}" class="{{ $message->isUnread() ? 'admin-mailbox-table__row--unread' : '' }}">
                    <td width="36"><input id="{{ $message->id }}" type="checkbox" class="massCheck"></td>
                    <td class="admin-mailbox-table__sender">
                      <a href="{{ route('admin.support.message.show', $message) }}">
                        @if ($message->isUnread())
                          <strong>{!! highlightWords($message->getSenderName(), $search_q) !!}</strong>
                          <small class="text-muted">{!! highlightWords($message->getSenderEmail(), $search_q) !!}</small>
                        @else
                          {!! highlightWords($message->getSenderName(), $search_q) !!}
                          <small class="text-muted">{!! highlightWords($message->getSenderEmail(), $search_q) !!}</small>
                        @endif
                      </a>
                    </td>
                    <td class="admin-mailbox-table__subject">
                      <a href="{{ route('admin.support.message.show', $message) }}">
                        <strong>{!! highlightWords($message->subject, $search_q) !!}</strong>
                        <span class="text-muted">— {!! highlightWords(\Str::limit(strip_tags($message->lastReply->reply ?? $message->message), max(160 - strlen($message->subject), 0)), $search_q) !!}</span>
                      </a>
                    </td>
                    <td class="admin-mailbox-table__meta">
                      @if ($message->isUnread())
                        {!! $message->statusName() !!}
                      @endif
                      @if ($message->about())
                        {!! $message->about() !!}
                      @endif
                      @if ($message->replies_count)
                        <span class="label label-default" data-toggle="tooltip" title="{{ trans('app.replies') }}">{{ $message->replies_count }}</span>
                      @endif
                      @if ($message->hasAttachments())
                        <i class="fa fa-paperclip text-muted" data-toggle="tooltip" title="{{ trans('app.attachments') }}"></i>
                      @endif
                    </td>
                    <td class="admin-mailbox-table__date text-muted small">{{ $message->updated_at->diffForHumans() }}</td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="5" class="text-center text-muted admin-mailbox-table__empty">{{ trans('app.no_data_found') }}</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>

          <div class="admin-mailbox__toolbar admin-mailbox__toolbar--bottom">
            <div class="admin-mailbox__pagination">
              @if ($messages->count())
                {{ $messages->links('admin.partials._pagination_btn') }}
              @endif
            </div>
          </div>
      @include('admin.partials.ui.card_end')
    </div>
  </div>
@endsection
