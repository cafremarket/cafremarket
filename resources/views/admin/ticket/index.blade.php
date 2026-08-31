@extends('admin.layouts.master')

@section('page_title')
  {{ trans('app.tickets') }}
@endsection

@section('content')
  @if ($assigned->count())
    @include('admin.partials.ui.card_start', [
      'title' => trans('app.assigned_to_me'),
      'icon' => 'fa-user-circle',
      'bodyClass' => 'responsive-table',
    ])

    <table class="table table-hover admin-table table-no-sort">
      <thead>
        @include('admin.ticket._table_head')
      </thead>
      <tbody>
        @foreach ($assigned as $ticket)
          <tr>
            <td>
              <div class="admin-table__shop-cell">
                <img src="{{ get_storage_file_url(optional($ticket->shop->image)->path, 'tiny') }}" class="img-circle img-sm" alt="">
                <div>
                  <strong>{{ $ticket->shop->name }}</strong>
                  <br><span class="text-muted">{{ trans('app.by') . ' ' . $ticket->user->name }}</span>
                </div>
              </div>
            </td>
            <td>
              {!! $ticket->statusName() !!}
              <span class="label label-outline">{{ $ticket->category->name }}</span>
              <a href="{{ route('admin.support.ticket.show', $ticket->id) }}">{{ $ticket->subject }}</a>
            </td>
            <td>{!! $ticket->priorityLevel() !!}</td>
            <td><span class="label label-default">{{ $ticket->replies_count }}</span></td>
            <td>{{ $ticket->assignedTo ? $ticket->assignedTo->name : '-' }}</td>
            <td>{{ $ticket->updated_at->diffForHumans() }}</td>
            <td class="row-options admin-row-actions">
              @include('admin.ticket._row_actions', ['ticket' => $ticket])
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>

    @include('admin.partials.ui.card_end')
  @endif

  @include('admin.partials.ui.card_start', [
    'title' => trans('app.open_tickets'),
    'icon' => 'fa-life-ring',
    'bodyClass' => 'responsive-table',
  ])

  <table class="table table-hover admin-table table-no-sort">
    <thead>
      @include('admin.ticket._table_head')
    </thead>
    <tbody>
      @foreach ($tickets as $ticket)
        <tr>
          <td>
            <div class="admin-table__shop-cell">
              <img src="{{ get_storage_file_url(optional($ticket->shop->image)->path, 'tiny') }}" class="img-circle img-sm" alt="">
              <div>
                <strong>{{ $ticket->shop->name }}</strong>
                @if ($ticket->user)
                  <br><span class="text-muted">{{ trans('app.by') . ' ' . $ticket->user->name }}</span>
                @endif
              </div>
            </div>
          </td>
          <td>
            {!! $ticket->statusName() !!}
            <span class="label label-outline">{{ $ticket->category->name }}</span>
            <a href="{{ route('admin.support.ticket.show', $ticket->id) }}">{{ $ticket->subject }}</a>
          </td>
          <td>{!! $ticket->priorityLevel() !!}</td>
          <td><span class="label label-default">{{ $ticket->replies_count }}</span></td>
          <td>{{ $ticket->assignedTo ? $ticket->assignedTo->name : '-' }}</td>
          <td>{{ $ticket->updated_at->diffForHumans() }}</td>
          <td class="row-options admin-row-actions">
            @include('admin.ticket._row_actions', ['ticket' => $ticket])
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>

  @include('admin.partials.ui.card_end')

  @include('admin.partials.ui.trash_start', ['title' => trans('app.trash')])

  <table class="table table-hover admin-table table-no-sort">
    <thead>
      <tr>
        <th>{{ trans('app.shop') }}</th>
        <th>{{ trans('app.subject') }}</th>
        <th>{{ trans('app.priority') }}</th>
        <th>{{ trans('app.assigned_to') }}</th>
        <th>{{ trans('app.updated_at') }}</th>
        <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($closed as $ticket)
        <tr>
          <td>
            <strong>{{ $ticket->shop->name }}</strong>
            <br><span class="text-muted">{{ trans('app.by') . ' ' . $ticket->user->name }}</span>
          </td>
          <td>
            {!! $ticket->statusName() !!}
            <span class="label label-outline">{{ $ticket->category->name }}</span>
            <a href="{{ route('admin.support.ticket.show', $ticket->id) }}">{{ $ticket->subject }}</a>
          </td>
          <td>{!! $ticket->priorityLevel() !!}</td>
          <td>{{ $ticket->assignedTo ? $ticket->assignedTo->name : '-' }}</td>
          <td>{{ $ticket->updated_at->diffForHumans() }}</td>
          <td class="row-options admin-row-actions">
            @can('update', $ticket)
              {!! Form::open(['route' => ['admin.support.ticket.reopen', $ticket->id], 'method' => 'POST', 'class' => 'data-form admin-inline-form']) !!}
              <button type="submit" class="admin-action-btn confirm ajax-silent" title="{{ trans('app.reopen') }}" data-toggle="tooltip"><i class="fa fa-refresh"></i></button>
              {!! Form::close() !!}
            @endcan
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>

  @include('admin.partials.ui.card_end')
@endsection
