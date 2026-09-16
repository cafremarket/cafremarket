<ul class="list-group">
  @if ($logger)
    @php
      $logChanges = ['current_billing_plan', 'card_brand', 'card_last_four'];
      $historyShown = 0;
    @endphp

    @foreach ($logger->logs() as $activity)
      @php
        $changes = $activity->changes()->all();
      @endphp

      @continue(empty($changes) || empty($changes['attributes']) || ! is_array($changes['attributes']))
      @continue(strtolower((string) $activity->description) !== 'updated')

      @foreach ($changes['attributes'] as $attrbute => $new_value)
        @continue(! in_array($attrbute, $logChanges, true))

        @php
          $old_value = $changes['old'][$attrbute] ?? null;
        @endphp

        @continue($old_value === $new_value)

        @php
          $activityStr = get_activity_str($logger, $attrbute, $new_value, $old_value);
        @endphp

        @continue($activityStr === '' || $activityStr === null)

        @php $historyShown++; @endphp

        <li class="list-group-item">
          <i class="fa fa-arrow-circle-o-right"></i>
          <span class="indent10">
            {!! $activityStr !!}
          </span>

          <span class="pull-right">{{ $activity->created_at->diffForHumans() }} {{ trans('app.by') }} {{ $activity->causer?->getName() ?: 'System' }}</span>
        </li>
      @endforeach
    @endforeach

    @if ($historyShown === 0)
      <span class="indent5">{{ trans('messages.no_history_data') }}</span>
    @endif
  @else
    <span class="indent5">{{ trans('messages.no_history_data') }}</span>
  @endif
</ul>
