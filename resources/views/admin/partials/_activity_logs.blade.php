@include('admin.partials.ui.card_start', [
  'title' => trans('app.history'),
  'icon' => 'fa-history',
  'bodyClass' => 'admin-activity-log',
])

<div id="menu" class="admin-activity-log__list">
  <div class="panel list-group admin-activity-log__panel">
    @forelse($logger->activities as $log)
      @php $changes = $log->changes; @endphp

      <a class="list-group-item admin-activity-log__item" data-toggle="collapse" data-target="#sl-{{ $log->id }}" data-parent="#menu">
        <span class="admin-activity-log__icon fa-stack fa-md">
          <i class="fa fa-circle-thin fa-stack-2x"></i>
          <i class="fa fa-check fa-stack-1x"></i>
        </span>
        {{ get_activity_title($log) }}
        <span class="admin-activity-log__time">{{ $log->created_at->diffForHumans() }}</span>
      </a>
      <div id="sl-{{ $log->id }}" class="sublinks collapse admin-activity-log__details">
        @if (!empty($changes) && isset($changes['attributes']) && strtolower($log->description) == 'updated')
          @foreach ($changes['attributes'] as $attrbute => $new_value)
            @if(isset($new_value))
              <p class="list-group-item list-group-item-info admin-activity-log__change">
                <i class="fa fa-arrow-circle-o-right"></i>
                <span>{!! get_activity_str($logger, $attrbute, $new_value, $changes['old'][$attrbute]) !!}</span>
              </p>
            @endif
          @endforeach
        @else
          <p class="list-group-item list-group-item-info admin-activity-log__change">
            <i class="fa fa-arrow-circle-o-right"></i>
            <span>{{ trans('messages.no_changes') }}</span>
          </p>
        @endif
      </div>
    @empty
      <p class="text-muted admin-activity-log__empty">{{ trans('messages.no_history_data') }}</p>
    @endforelse
  </div>
</div>

@include('admin.partials.ui.card_end')
