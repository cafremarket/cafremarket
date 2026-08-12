<div class="modal-dialog modal-md">
  <div class="modal-content">
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
      <h4 class="modal-title">{{ $campaign->title }}</h4>
    </div>
    <div class="modal-body">
      <p>{{ $campaign->body }}</p>
      <dl class="dl-horizontal">
        <dt>Audience</dt><dd>{{ $campaign->audience }}</dd>
        <dt>Type</dt><dd>{{ $campaign->type }}</dd>
        <dt>Status</dt><dd>{{ $campaign->status }}</dd>
        <dt>Targets</dt><dd>{{ $campaign->target_count }}</dd>
        <dt>Sent / Failed</dt><dd>{{ $campaign->sent_count }} / {{ $campaign->failed_count }}</dd>
        <dt>Deep link</dt><dd>{{ $campaign->deep_link ?: '—' }}</dd>
        <dt>Image</dt><dd>{{ $campaign->image_url ?: '—' }}</dd>
        <dt>Sent at</dt><dd>{{ optional($campaign->sent_at)->toDateTimeString() ?: '—' }}</dd>
        @if ($campaign->error_message)
          <dt>Error</dt><dd class="text-danger">{{ $campaign->error_message }}</dd>
        @endif
      </dl>
    </div>
  </div>
</div>
