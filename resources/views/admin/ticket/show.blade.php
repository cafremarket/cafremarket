@extends('admin.layouts.master')

@section('page_title')
  {{ trans('app.ticket') }}
@endsection

@section('content')
  @php
    $ticketActions = '';
    if (Gate::allows('index', $ticket)) {
      $ticketActions .= '<a href="' . route('admin.support.ticket.index') . '" class="btn btn-default btn-flat btn-sm"><i class="fa fa-arrow-left"></i> ' . e(trans('app.back')) . '</a> ';
    }
    if (Gate::allows('reply', $ticket)) {
      $ticketActions .= '<a href="javascript:void(0)" data-link="' . route('admin.support.ticket.reply', $ticket) . '" class="ajax-modal-btn btn btn-new btn-flat btn-sm"><i class="fa fa-reply"></i> ' . e(trans('app.reply')) . '</a>';
    }
  @endphp

  @include('admin.partials.ui.card_start', [
    'title' => trans('app.ticket'),
    'icon' => 'fa-ticket',
    'actions' => $ticketActions,
    'bodyClass' => 'admin-detail-view',
  ])

  <div class="row admin-detail-view__layout">
    <div class="col-md-3 admin-detail-view__sidebar">
      <div class="admin-detail-panel">
        <label class="admin-detail-panel__label">{{ trans('app.merchant') }}</label>
        @can('view', $ticket->shop)
          <a href="javascript:void(0)" data-link="{{ route('admin.vendor.shop.show', $ticket->shop_id) }}" class="ajax-modal-btn admin-detail-panel__link"><strong>{{ $ticket->shop->name }}</strong></a>
        @else
          <strong>{{ $ticket->shop->name }}</strong>
        @endcan
        <img src="{{ get_storage_file_url(optional($ticket->shop->image)->path, 'small') }}" class="admin-detail-panel__thumb" alt="">

        @if($ticket->assignedTo)
          <label class="admin-detail-panel__label">{{ trans('app.created_by') }}</label>
          <div class="admin-detail-panel__user">
            @if($ticket->user->image)
              <img src="{{ get_storage_file_url(optional($ticket->user->image)->path, 'tiny') }}" class="img-circle img-sm" alt="">
            @else
              <img src="{{ get_gravatar_url($ticket->user->email, 'tiny') }}" class="img-circle img-sm" alt="">
            @endif
            @can('view', $ticket->user)
              <a href="javascript:void(0)" data-link="{{ route('admin.admin.user.show', $ticket->user_id) }}" class="ajax-modal-btn">{{ $ticket->user->getName() }}</a>
            @else
              {{ $ticket->user->getName() }}
            @endcan
          </div>
        @endif

        <dl class="admin-detail-panel__meta">
          <dt>{{ trans('app.created_at') }}</dt>
          <dd>{{ $ticket->created_at->diffForHumans() }}</dd>
          <dt>{{ trans('app.updated_at') }}</dt>
          <dd>{{ $ticket->updated_at->diffForHumans() }}</dd>
        </dl>
      </div>
    </div>

    <div class="col-md-6 admin-detail-view__main">
      <div class="admin-detail-view__badges">
        <span class="label label-outline">{{ $ticket->category->name }}</span>
        {!! $ticket->priorityLevel() !!}
        {!! $ticket->statusName() !!}
      </div>

      <h3 class="admin-detail-view__title">{{ $ticket->subject }}</h3>

      @if(count($ticket->attachments))
        <div class="admin-detail-view__attachments">
          {{ trans('app.attachments') }}:
          @foreach($ticket->attachments as $attachment)
            <a href="{{ route('attachment.download', $attachment) }}" class="btn btn-default btn-xs btn-flat"><i class="fa fa-file"></i> {{ $attachment->name ?? trans('app.file') }}</a>
          @endforeach
        </div>
      @endif

      @if($ticket->message)
        <div class="admin-detail-view__message well">
          {!! $ticket->message !!}
        </div>
      @endif

      @if($ticket->replies->count())
        <div class="admin-detail-view__replies">
          <strong>{{ trans('app.conversations') }}</strong>
          @foreach($ticket->replies as $reply)
            @include('admin.partials._reply_conversations')
          @endforeach
        </div>
      @endif

      <div class="admin-detail-view__footer-actions">
        <a href="javascript:void(0)" data-link="{{ route('admin.support.ticket.reply', $ticket) }}" class="ajax-modal-btn btn btn-new btn-flat btn-sm"><i class="fa fa-reply"></i> {{ trans('app.reply') }}</a>
        {!! Form::open(['route' => ['admin.support.ticket.archive', $ticket], 'method' => 'delete', 'class' => 'inline']) !!}
        <button class="confirm btn btn-danger btn-sm" type="submit"><i class="fa fa-archive"></i> {{ trans('app.archive') }}</button>
        {!! Form::close() !!}
      </div>
    </div>

    <div class="col-md-3 admin-detail-view__aside">
      @if($ticket->assignedTo)
        <div class="admin-detail-panel">
          <label class="admin-detail-panel__label">{{ trans('app.assigned_to') }}</label>
          <div class="admin-detail-panel__user">
            @if($ticket->assignedTo->image)
              <img src="{{ get_storage_file_url(optional($ticket->assignedTo->image)->path, 'tiny') }}" class="img-circle img-sm" alt="">
            @else
              <img src="{{ get_gravatar_url($ticket->assignedTo->email, 'tiny') }}" class="img-circle img-sm" alt="">
            @endif
            <div>
              <strong>{{ $ticket->assignedTo->getName() }}</strong>
              @can('view', $ticket->assignedTo)
                <a href="javascript:void(0)" data-link="{{ route('admin.admin.user.show', $ticket->assigned_to) }}" class="ajax-modal-btn small">{{ trans('app.view_detail') }}</a>
              @endcan
            </div>
          </div>
        </div>
      @endif

      @can('assign', $ticket)
        <a class="btn btn-default btn-flat btn-sm btn-block ajax-modal-btn" href="javascript:void(0)" data-link="{{ route('admin.support.ticket.showAssignForm', $ticket) }}">
          <i class="fa fa-hashtag"></i> {{ trans('app.assign') }}
        </a>
      @endcan

      @if($ticket->shop->tickets)
        <div class="admin-detail-panel admin-detail-panel--list">
          <label class="admin-detail-panel__label">{{ trans('app.other_conversations') }}</label>
          <ul class="admin-detail-panel__list">
            @foreach($ticket->shop->tickets as $old_ticket)
              @continue($old_ticket->id == $ticket->id)
              <li>
                <a href="{{ route('admin.support.ticket.show', $old_ticket->id) }}">{{ $old_ticket->subject }}</a>
                {!! $old_ticket->statusName() !!}
              </li>
            @endforeach
          </ul>
        </div>
      @endif
    </div>
  </div>

  @include('admin.partials.ui.card_end')
@endsection
