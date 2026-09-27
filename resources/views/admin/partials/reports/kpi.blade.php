{{--
  One KPI tile. Pass: label, value, icon (optional), tone (blue|green|orange|red|purple|teal|grey),
  sub (optional text), compare + change (% vs previous period, null = no earlier data), badWhenUp (true when a rise is bad, e.g. refunds).
--}}
<div class="report-kpi report-kpi--{{ $tone ?? 'blue' }}">
  <span class="report-kpi__label">
    @if (!empty($icon))<i class="fa {{ $icon }}"></i>@endif{{ $label }}
  </span>
  <span class="report-kpi__value">{{ $value }}</span>
  @if (!empty($compare) || !empty($sub))
    <span class="report-kpi__sub">
      @if (!empty($compare))
        @if ($change === null)
          <span class="report-change report-change--flat" title="{{ trans('reports.vs_previous') }}">{{ trans('reports.new') }}</span>
        @else
          <span class="report-change {{ $change > 0 ? 'report-change--up' : ($change < 0 ? 'report-change--down' : 'report-change--flat') }} {{ !empty($badWhenUp) ? 'report-change--bad' : '' }}"
                title="{{ trans('reports.vs_previous') }}">
            <i class="fa {{ $change > 0 ? 'fa-arrow-up' : ($change < 0 ? 'fa-arrow-down' : 'fa-minus') }}"></i>
            {{ number_format(abs($change), 1) }}%
          </span>
        @endif
      @endif
      {{ $sub ?? '' }}
    </span>
  @endif
</div>
