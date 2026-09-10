@php
  $historyLogs = method_exists($logger, 'logs')
    ? $logger->logs()
    : $logger->activities->sortByDesc('created_at');

  // Hide system/default logs (no human causer) — keep them in DB, don't show in UI
  $historyLogs = collect($historyLogs)->filter(function ($log) {
    return ! empty($log->causer_id) && $log->causer;
  })->values();

  // Hide noisy internal fields from order history details
  $historySkipAttrs = [
    'updated_at', 'created_at', 'deleted_at', 'otp', 'otp_hash',
    'latitude', 'longitude', 'delivery_lat', 'delivery_lng',
    'razorpay_order_id', 'razorpay_payment_id', 'stripe_payment_intent',
    'payment_instruction', 'admin_note',
  ];
@endphp

@include('admin.partials.ui.card_start', [
  'title' => trans('app.history'),
  'icon' => 'fa-history',
  'bodyClass' => 'admin-activity-log',
])

<div id="order-history-menu" class="admin-activity-log__list">
  <div class="panel list-group admin-activity-log__panel">
    @forelse($historyLogs as $log)
      @php
        $changes = method_exists($log, 'changes') ? $log->changes()->all() : [];
        $hasAttributeChanges = ! empty($changes['attributes']) && is_array($changes['attributes']);
        $isUpdated = strtolower((string) $log->description) === 'updated';
      @endphp

      <a href="#sl-{{ $log->id }}"
         class="list-group-item admin-activity-log__item"
         data-toggle="collapse"
         data-target="#sl-{{ $log->id }}"
         data-parent="#order-history-menu"
         role="button"
         aria-expanded="false"
         aria-controls="sl-{{ $log->id }}">
        <span class="admin-activity-log__icon fa-stack fa-md">
          <i class="fa fa-circle-thin fa-stack-2x"></i>
          <i class="fa fa-check fa-stack-1x"></i>
        </span>
        <span class="admin-activity-log__title">{{ get_activity_title($log) }}</span>
        <span class="admin-activity-log__time">{{ $log->created_at->diffForHumans() }}</span>
        <i class="fa fa-chevron-down admin-activity-log__caret" aria-hidden="true"></i>
      </a>
      <div id="sl-{{ $log->id }}" class="sublinks collapse admin-activity-log__details">
        @if ($isUpdated && $hasAttributeChanges)
          @php $shown = 0; @endphp
          @foreach ($changes['attributes'] as $attrbute => $new_value)
            @continue(in_array($attrbute, $historySkipAttrs, true))
            @continue($new_value === null && ! array_key_exists($attrbute, $changes['attributes']))
            @php
              $old_value = $changes['old'][$attrbute] ?? null;
            @endphp
            @continue($old_value === $new_value)
            @php $shown++; @endphp
            <p class="list-group-item list-group-item-info admin-activity-log__change">
              <i class="fa fa-arrow-circle-o-right"></i>
              <span>{!! get_activity_str($logger, $attrbute, $new_value, $old_value) !!}</span>
            </p>
          @endforeach
          @if ($shown === 0)
            <p class="list-group-item list-group-item-info admin-activity-log__change">
              <i class="fa fa-arrow-circle-o-right"></i>
              <span>{{ get_activity_title($log) }} &mdash; {{ $log->created_at->toDayDateTimeString() }}</span>
            </p>
          @endif
        @else
          <p class="list-group-item list-group-item-info admin-activity-log__change">
            <i class="fa fa-arrow-circle-o-right"></i>
            <span>{{ get_activity_title($log) }} &mdash; {{ $log->created_at->toDayDateTimeString() }}</span>
          </p>
        @endif
      </div>
    @empty
      <p class="text-muted admin-activity-log__empty">{{ trans('messages.no_history_data') }}</p>
    @endforelse
  </div>
</div>

@include('admin.partials.ui.card_end')
