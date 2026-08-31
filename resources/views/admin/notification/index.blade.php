@extends('admin.layouts.master')

@section('page_title')
  {{ trans('app.notifications') }}
@endsection

@section('content')
  @include('admin.partials.ui.card_start', [
    'title' => trans('app.notifications'),
    'icon' => 'fa-bell-o',
    'actions' => view('admin.notification._header_actions')->render(),
    'bodyClass' => '',
  ])

  <div class="admin-notification-list">
    @forelse(Auth::user()->notifications as $notification)
      @php
        $notification_view = 'admin.partials.notifications.' . Str::snake(class_basename($notification->type));
      @endphp
      <div class="admin-notification-item">
        <div class="admin-notification-item__body">
          @include($notification_view)
        </div>
        <div class="admin-notification-item__meta">
          <span class="text-muted small">{{ $notification->created_at->diffForHumans() }}</span>
          {!! Form::open(['route' => ['admin.notifications.delete', $notification->id], 'method' => 'delete', 'class' => 'data-form admin-inline-form']) !!}
          <button type="submit" class="admin-action-btn confirm ajax-silent" title="{{ trans('app.delete') }}" data-toggle="tooltip"><i class="fa fa-trash-o"></i></button>
          {!! Form::close() !!}
        </div>
      </div>
    @empty
      <div class="admin-empty-state">
        <i class="fa fa-bell-o"></i>
        <p>{{ trans('app.no_data_found') }}</p>
      </div>
    @endforelse
  </div>

  @include('admin.partials.ui.card_end')
@endsection
