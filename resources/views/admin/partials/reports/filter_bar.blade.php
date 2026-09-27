{{--
  Period + shop filter for report pages (plain GET form).
  Pass: $report (MarketplaceReportService), $action (url), $shops (id => name, or null to hide), $exports ([label => url]).
--}}
@php
  $filterPeriod = $report->period();
@endphp

<form method="GET" action="{{ $action }}" class="report-filter-bar" id="report-filter-form">
  <div class="form-group">
    <label>{{ trans('reports.filter.period') }}</label>
    <div id="report-range" class="report-timeframe">
      <i class="fa fa-calendar"></i>&nbsp;
      <span>{{ $filterPeriod->from->translatedFormat('d M Y') }} – {{ $filterPeriod->to->translatedFormat('d M Y') }}</span>
      &nbsp;<i class="fa fa-caret-down"></i>
    </div>
    <input type="hidden" name="from" value="{{ $filterPeriod->from->toDateString() }}">
    <input type="hidden" name="to" value="{{ $filterPeriod->to->toDateString() }}">
  </div>

  @if (!empty($shops))
    <div class="form-group report-filter-shop">
      <label>{{ trans('reports.filter.shop') }}</label>
      <select name="shop_id" class="form-control select2-normal" style="width: 100%" onchange="this.form.submit()">
        <option value="">{{ trans('reports.filter.all_shops') }}</option>
        @foreach ($shops as $id => $name)
          <option value="{{ $id }}" {{ (int) $report->shopId() === (int) $id ? 'selected' : '' }}>{{ $name }}</option>
        @endforeach
      </select>
    </div>
  @endif

  <div class="report-filter-actions">
    @if ($report->shopId() || request()->has('from'))
      <a href="{{ $action }}" class="btn btn-default"><i class="fa fa-times"></i> {{ trans('reports.filter.reset') }}</a>
    @endif

    @if (!empty($exports))
      <div class="btn-group">
        <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
          <i class="fa fa-download"></i> {{ trans('reports.filter.export') }} <span class="caret"></span>
        </button>
        <ul class="dropdown-menu dropdown-menu-right">
          @foreach ($exports as $label => $url)
            <li><a href="{{ $url }}"><i class="fa fa-file-excel-o"></i> {{ $label }}</a></li>
          @endforeach
        </ul>
      </div>
    @endif
  </div>
</form>

<p class="report-period-note">
  {{ trans('reports.filter.note', ['days' => $filterPeriod->days()]) }}
</p>
