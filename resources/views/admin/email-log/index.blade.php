@extends('admin.layouts.master')

@section('page_title')
  {{ trans('nav.email_logs') }}
@endsection

@section('content')
  @include('admin.partials.ui.card_start', [
    'title' => trans('nav.email_logs'),
    'icon' => 'fa-envelope-o',
    'actions' => view('admin.email-log._header_actions')->render(),
  ])

  <div class="admin-filters">
    {!! Form::open(['route' => 'admin.utility.emailLog.index', 'method' => 'GET', 'class' => 'form-inline admin-filters__form']) !!}
      <div class="form-group">
        {!! Form::text('q', request('q'), ['class' => 'form-control input-sm', 'placeholder' => trans('app.search')]) !!}
      </div>
      <div class="form-group">
        {!! Form::select('status', [
          '' => trans('app.all'),
          'sent' => trans('app.sent'),
          'failed' => trans('app.failed'),
          'pending' => trans('app.pending'),
        ], request('status'), ['class' => 'form-control input-sm']) !!}
      </div>
      <button type="submit" class="btn btn-default btn-sm btn-flat">
        <i class="fa fa-search"></i> {{ trans('app.search') }}
      </button>
    {!! Form::close() !!}
  </div>

  <table class="table table-hover admin-table table-no-sort">
    <thead>
      <tr>
        <th>{{ trans('app.date') }}</th>
        <th>{{ trans('app.to') }}</th>
        <th>{{ trans('app.subject') }}</th>
        <th>{{ trans('app.type') }}</th>
        <th>{{ trans('app.status') }}</th>
        <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
      </tr>
    </thead>
    <tbody>
      @forelse ($logs as $log)
        <tr>
          <td class="small">{{ optional($log->created_at)->toDayDateTimeString() }}</td>
          <td>{{ $log->to ?: '-' }}</td>
          <td>{{ $log->subject ?: '-' }}</td>
          <td><small>{{ $log->notification ? class_basename($log->notification) : ($log->context ?: '-') }}</small></td>
          <td>{!! $log->statusBadge() !!}</td>
          <td class="row-options admin-row-actions">
            <a href="javascript:void(0)" data-link="{{ route('admin.utility.emailLog.show', $log) }}" class="admin-action-btn ajax-modal-btn" title="{{ trans('app.view') }}" data-toggle="tooltip"><i class="fa fa-eye"></i></a>
            {!! Form::open(['route' => ['admin.utility.emailLog.destroy', $log], 'method' => 'delete', 'class' => 'data-form admin-inline-form confirm']) !!}
            <button type="submit" class="admin-action-btn" title="{{ trans('app.delete') }}" data-toggle="tooltip"><i class="fa fa-trash-o"></i></button>
            {!! Form::close() !!}
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="6" class="text-center text-muted">{{ trans('app.no_data_found') }}</td>
        </tr>
      @endforelse
    </tbody>
  </table>

  <div class="text-center">{{ $logs->links() }}</div>

  @include('admin.partials.ui.card_end')
@endsection
