<div class="modal-dialog modal-lg">
  <div class="modal-content">
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
      {{ trans('nav.email_logs') }} #{{ $emailLog->id }}
    </div>
    <div class="modal-body">
      <table class="table table-striped">
        <tr>
          <th width="160">{{ trans('app.status') }}</th>
          <td>{!! $emailLog->statusBadge() !!}</td>
        </tr>
        <tr>
          <th>{{ trans('app.date') }}</th>
          <td>{{ optional($emailLog->created_at)->toDayDateTimeString() }}</td>
        </tr>
        <tr>
          <th>{{ trans('app.to') }}</th>
          <td>{{ $emailLog->to ?: '-' }}</td>
        </tr>
        <tr>
          <th>CC</th>
          <td>{{ $emailLog->cc ?: '-' }}</td>
        </tr>
        <tr>
          <th>{{ trans('app.subject') }}</th>
          <td>{{ $emailLog->subject ?: '-' }}</td>
        </tr>
        <tr>
          <th>{{ trans('app.type') }}</th>
          <td><code>{{ $emailLog->notification ?: '-' }}</code></td>
        </tr>
        <tr>
          <th>Context</th>
          <td>{{ $emailLog->context ?: '-' }}</td>
        </tr>
        @if ($emailLog->error)
          <tr>
            <th>{{ trans('app.error') }}</th>
            <td><pre style="white-space:pre-wrap;max-height:240px;overflow:auto;">{{ $emailLog->error }}</pre></td>
          </tr>
        @endif
      </table>
    </div>
  </div>
</div>
