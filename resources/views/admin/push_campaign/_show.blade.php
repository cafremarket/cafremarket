<div class="modal-dialog modal-md">
  <div class="modal-content">
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
      <h4 class="modal-title">{{ $campaign->title }}</h4>
    </div>
    <div class="modal-body">
      <p>{{ $campaign->body }}</p>
      <dl class="dl-horizontal">
        <dt>{{ trans('app.audience') }}</dt><dd>{{ $campaign->audience }}</dd>
        <dt>{{ trans('app.type') }}</dt><dd>{{ $campaign->type }}</dd>
        <dt>{{ trans('app.status') }}</dt><dd>{{ $campaign->status }}</dd>
        <dt>{{ trans('app.targets') }}</dt><dd>{{ $campaign->target_count }}</dd>
        <dt>{{ trans('app.sent_failed') }}</dt><dd>{{ $campaign->sent_count }} / {{ $campaign->failed_count }}</dd>
        <dt>{{ trans('app.deep_link') }}</dt><dd>{{ $campaign->deep_link ?: '—' }}</dd>
        <dt>{{ trans('app.image') }}</dt><dd>{{ $campaign->image_url ?: '—' }}</dd>
        <dt>{{ trans('app.sent_at') }}</dt><dd>{{ optional($campaign->sent_at)->toDateTimeString() ?: '—' }}</dd>
        @if ($campaign->error_message)
          <dt>{{ trans('app.error_label') }}</dt><dd class="text-danger">{{ $campaign->error_message }}</dd>
        @endif
      </dl>
    </div>
  </div>
</div>
