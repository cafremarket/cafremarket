@extends('admin.layouts.master')

@section('page_title')
  {{ trans('cron.title') }}
@endsection

@section('content')
  @include('admin.partials.reports.styles')

  <style>
    .cron-banner { align-items: center; border-radius: 6px; display: flex; gap: 14px; margin-bottom: 18px; padding: 14px 18px; }
    .cron-banner--ok { background: #e3f5ea; border: 1px solid #b7e2c7; color: #00632f; }
    .cron-banner--down { background: #fbe7e5; border: 1px solid #f1bdb7; color: #9b2c20; }
    .cron-banner .fa { font-size: 26px; }
    .cron-banner h4 { font-size: 16px; font-weight: 700; margin: 0 0 2px; }
    .cron-banner pre { background: #fff; border: 1px solid #f1bdb7; margin: 8px 0 0; white-space: pre-wrap; word-break: break-all; }
    .cron-log-output { background: #1e1e1e; border-radius: 4px; color: #e6e6e6; font-size: 12px; margin: 6px 0 0; max-height: 320px; overflow: auto; white-space: pre-wrap; }
    .report-status--success { background: #e3f5ea; color: #00804a; }
    .report-status--failed, .report-status--interrupted { background: #fbe7e5; color: #c0392b; }
    .report-status--running { background: #e6f0fa; color: #1f6fb2; }
    .report-status--skipped { background: #f0f0f0; color: #777; }
  </style>

  @if ($running)
    <div class="cron-banner cron-banner--ok">
      <i class="fa fa-check-circle"></i>
      <div>
        <h4>{{ trans('cron.scheduler_running') }}</h4>
        {{ trans('cron.last_check_in', ['time' => $lastHeartbeat->diffForHumans(), 'date' => $lastHeartbeat->format('d/m/Y H:i:s')]) }}
      </div>
    </div>
  @else
    <div class="cron-banner cron-banner--down">
      <i class="fa fa-exclamation-triangle"></i>
      <div style="flex: 1; min-width: 0">
        <h4>{{ trans('cron.scheduler_not_running') }}</h4>
        @if ($lastHeartbeat)
          {{ trans('cron.last_check_in', ['time' => $lastHeartbeat->diffForHumans(), 'date' => $lastHeartbeat->format('d/m/Y H:i:s')]) }}
        @else
          {{ trans('cron.never_checked_in') }}
        @endif
        <br>{{ trans('cron.add_cron_line') }}
        <pre>{{ $cronLine }}</pre>
      </div>
    </div>
  @endif

  @unless ($ready)
    <div class="report-note"><i class="fa fa-info-circle"></i>{{ trans('cron.not_migrated') }}</div>
  @endunless

  <div class="report-kpi-grid">
    @include('admin.partials.reports.kpi', ['label' => trans('cron.kpi.scheduled'), 'icon' => 'fa-calendar', 'value' => $tasks->count()])
    @include('admin.partials.reports.kpi', ['label' => trans('cron.kpi.runs_24h'), 'icon' => 'fa-play', 'tone' => 'green', 'value' => number_format($summary['runs'])])
    @include('admin.partials.reports.kpi', ['label' => trans('cron.kpi.failures_24h'), 'icon' => 'fa-times-circle', 'tone' => $summary['failures'] > 0 ? 'red' : 'grey', 'value' => number_format($summary['failures'])])
    @include('admin.partials.reports.kpi', ['label' => trans('cron.kpi.running_now'), 'icon' => 'fa-spinner', 'tone' => 'teal', 'value' => number_format($summary['running'])])
  </div>

  <div class="report-panel">
    <div class="report-panel__head">
      <h4><i class="fa fa-clock-o"></i>{{ trans('cron.scheduled_jobs') }}</h4>
      <a href="{{ route('admin.report.cron') }}" class="btn btn-default btn-sm"><i class="fa fa-refresh"></i> {{ trans('cron.refresh') }}</a>
    </div>
    <div class="report-panel__body report-panel__body--flush">
      <table class="table table-hover">
        <thead>
          <tr>
            <th>{{ trans('cron.col.job') }}</th>
            <th>{{ trans('cron.col.frequency') }}</th>
            <th>{{ trans('cron.col.next_run') }}</th>
            <th>{{ trans('cron.col.last_run') }}</th>
            <th>{{ trans('cron.col.last_result') }}</th>
            <th class="num">{{ trans('cron.col.runs_24h') }}</th>
            <th class="num">{{ trans('cron.col.failures_24h') }}</th>
            <th class="num">{{ trans('cron.col.avg_duration') }}</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          @forelse ($tasks as $task)
            <tr>
              <td>
                <a href="{{ route('admin.report.cron', ['task' => $task['task']]) }}"><code>{{ $task['task'] }}</code></a>
                @if ($task['background'])<span class="label label-default">{{ trans('cron.background') }}</span>@endif
                @if ($task['overlapping'])<span class="label label-default" title="{{ trans('cron.no_overlap_help') }}">{{ trans('cron.no_overlap') }}</span>@endif
              </td>
              <td>{{ $task['frequency'] }}<br><small class="text-muted"><code>{{ $task['expression'] }}</code></small></td>
              <td>{{ $task['next_run'] ? $task['next_run']->format('d/m H:i') : '—' }}</td>
              <td>{{ $task['last'] && $task['last']->started ? $task['last']->started->diffForHumans() : trans('cron.never') }}</td>
              <td>
                @if ($task['last'])
                  <span class="report-status report-status--{{ $task['last']->display_status }}">{{ trans('cron.status.'.$task['last']->display_status) }}</span>
                @else
                  —
                @endif
              </td>
              <td class="num">{{ $task['runs_24h'] }}</td>
              <td class="num {{ $task['failures_24h'] > 0 ? 'text-danger' : '' }}">{{ $task['failures_24h'] }}</td>
              <td class="num">{{ $task['avg_ms'] !== null ? number_format($task['avg_ms'] / 1000, 1).' s' : '—' }}</td>
              <td class="text-right" style="white-space: nowrap">
                @if ($ready)
                  {!! Form::open(['route' => 'admin.report.cron.run', 'method' => 'POST', 'class' => 'admin-inline-form']) !!}
                    {!! Form::hidden('task', $task['task']) !!}
                    <button type="submit" class="btn btn-default btn-sm confirm"
                            data-confirm="{{ trans('cron.run_confirm', ['task' => $task['task']]) }}"
                            {{ $task['last'] && $task['last']->display_status === 'running' ? 'disabled' : '' }}>
                      <i class="fa fa-play"></i> {{ trans('cron.run_now') }}
                    </button>
                  {!! Form::close() !!}
                @endif
              </td>
            </tr>
          @empty
            <tr><td colspan="9" class="report-empty">{{ trans('cron.no_jobs') }}</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <div class="report-panel">
    <div class="report-panel__head">
      <h4><i class="fa fa-list"></i>{{ trans('cron.run_log') }}</h4>
      <span class="report-panel__hint">{{ trans('cron.kept_days', ['days' => \App\Services\Cron\CronMonitor::KEEP_DAYS]) }}</span>
    </div>
    <div class="report-panel__body">
      <form method="GET" action="{{ route('admin.report.cron') }}" class="form-inline" style="margin-bottom: 12px">
        <select name="task" class="form-control input-sm">
          <option value="">{{ trans('cron.all_jobs') }}</option>
          @foreach ($taskNames as $name)
            <option value="{{ $name }}" {{ $filters['task'] === $name ? 'selected' : '' }}>{{ $name }}</option>
          @endforeach
        </select>
        <select name="status" class="form-control input-sm">
          <option value="">{{ trans('cron.all_statuses') }}</option>
          @foreach (\App\Http\Controllers\Admin\Report\CronJobController::STATUSES as $status)
            <option value="{{ $status }}" {{ $filters['status'] === $status ? 'selected' : '' }}>{{ trans('cron.status.'.$status) }}</option>
          @endforeach
        </select>
        <button class="btn btn-primary btn-sm"><i class="fa fa-filter"></i> {{ trans('cron.filter') }}</button>
        @if ($filters['task'] !== '' || $filters['status'] !== '')
          <a href="{{ route('admin.report.cron') }}" class="btn btn-default btn-sm">{{ trans('cron.clear') }}</a>
        @endif
      </form>
    </div>
    <div class="report-panel__body report-panel__body--flush">
      <table class="table table-hover">
        <thead>
          <tr>
            <th>{{ trans('cron.col.started') }}</th>
            <th>{{ trans('cron.col.job') }}</th>
            <th>{{ trans('cron.col.status') }}</th>
            <th class="num">{{ trans('cron.col.duration') }}</th>
            <th class="num">{{ trans('cron.col.exit_code') }}</th>
            <th style="width: 45%">{{ trans('cron.col.output') }}</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($logs ?? [] as $log)
            <tr>
              <td>{{ $log->started ? $log->started->format('d/m/Y H:i:s') : '—' }}</td>
              <td>
                <code>{{ $log->task }}</code>
                <br><small class="text-muted">
                  @if ($log->triggered_by ?? null)
                    <i class="fa fa-hand-pointer-o"></i> {{ $log->triggered_by }}
                  @else
                    <i class="fa fa-clock-o"></i> {{ trans('cron.scheduled') }}
                  @endif
                </small>
              </td>
              <td><span class="report-status report-status--{{ $log->display_status }}">{{ trans('cron.status.'.$log->display_status) }}</span></td>
              <td class="num">{{ $log->duration ?? ($log->display_status === 'running' ? trans('cron.in_progress') : '—') }}</td>
              <td class="num">{{ $log->exit_code ?? '—' }}</td>
              <td>
                @if ($log->error)
                  <span class="text-danger">{{ \Illuminate\Support\Str::limit($log->error, 300) }}</span>
                @endif
                @if ($log->output)
                  <details>
                    <summary class="text-muted">{{ \Illuminate\Support\Str::limit(strtok($log->output, "\n"), 90) }}</summary>
                    <pre class="cron-log-output">{{ $log->output }}</pre>
                  </details>
                @elseif (! $log->error)
                  <span class="text-muted">—</span>
                @endif
              </td>
            </tr>
          @empty
            <tr><td colspan="6" class="report-empty">{{ trans('cron.no_runs') }}</td></tr>
          @endforelse
        </tbody>
      </table>
      @if ($logs && $logs->hasPages())
        <div style="padding: 0 16px">{{ $logs->links() }}</div>
      @endif
    </div>
  </div>
@endsection

@section('page-script')
  @if ($summary['running'] > 0)
    <script>
      // A job is running: reload to show its result (stops once nothing is running).
      setTimeout(function () { window.location.reload(); }, 10000);
    </script>
  @endif
@endsection
