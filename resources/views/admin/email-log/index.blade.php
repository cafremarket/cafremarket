@extends('admin.layouts.master')

@section('content')
  <div class="box">
    <div class="box-header with-border">
      <h3 class="box-title">{{ trans('nav.email_logs') }}</h3>
      <div class="box-tools pull-right">
        {!! Form::open(['route' => 'admin.utility.emailLog.clear', 'method' => 'delete', 'class' => 'inline confirm', 'style' => 'display:inline;']) !!}
          <button type="submit" class="btn btn-danger btn-flat btn-sm">
            <i class="fa fa-trash"></i> {{ trans('app.empty_trash') }}
          </button>
        {!! Form::close() !!}
      </div>
    </div>

    <div class="box-body">
      {!! Form::open(['route' => 'admin.utility.emailLog.index', 'method' => 'GET', 'class' => 'form-inline', 'style' => 'margin-bottom:15px;']) !!}
        <div class="form-group" style="margin-right:8px;">
          {!! Form::text('q', request('q'), ['class' => 'form-control', 'placeholder' => trans('app.search')]) !!}
        </div>
        <div class="form-group" style="margin-right:8px;">
          {!! Form::select('status', [
            '' => trans('app.all'),
            'sent' => trans('app.sent'),
            'failed' => trans('app.failed'),
            'pending' => trans('app.pending'),
          ], request('status'), ['class' => 'form-control']) !!}
        </div>
        <button type="submit" class="btn btn-default btn-flat">
          <i class="fa fa-search"></i> {{ trans('app.search') }}
        </button>
      {!! Form::close() !!}

      <table class="table table-hover table-no-sort">
        <thead>
          <tr>
            <th>{{ trans('app.date') }}</th>
            <th>{{ trans('app.to') }}</th>
            <th>{{ trans('app.subject') }}</th>
            <th>{{ trans('app.type') }}</th>
            <th>{{ trans('app.status') }}</th>
            <th>{{ trans('app.option') }}</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($logs as $log)
            <tr>
              <td>{{ optional($log->created_at)->toDayDateTimeString() }}</td>
              <td>{{ $log->to ?: '-' }}</td>
              <td>{{ $log->subject ?: '-' }}</td>
              <td>
                <small>{{ $log->notification ? class_basename($log->notification) : ($log->context ?: '-') }}</small>
              </td>
              <td>{!! $log->statusBadge() !!}</td>
              <td class="text-nowrap">
                <a href="javascript:void(0)" data-link="{{ route('admin.utility.emailLog.show', $log) }}" class="ajax-modal-btn btn btn-default btn-sm btn-flat">
                  <i class="fa fa-eye"></i>
                </a>
                {!! Form::open(['route' => ['admin.utility.emailLog.destroy', $log], 'method' => 'delete', 'class' => 'inline confirm', 'style' => 'display:inline;']) !!}
                  <button type="submit" class="btn btn-danger btn-sm btn-flat">
                    <i class="fa fa-trash"></i>
                  </button>
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

      <div class="text-center">
        {{ $logs->links() }}
      </div>
    </div>
  </div>
@endsection
