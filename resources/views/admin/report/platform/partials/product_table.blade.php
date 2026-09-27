{{-- Best-selling products. Pass: $rows (MarketplaceReportService::topProducts()). --}}
<table class="table table-hover">
  <thead>
    <tr>
      <th>#</th>
      <th>{{ trans('reports.col.product') }}</th>
      <th class="num">{{ trans('reports.col.units') }}</th>
      <th class="num">{{ trans('reports.col.orders') }}</th>
      <th class="num">{{ trans('reports.col.revenue') }}</th>
    </tr>
  </thead>
  <tbody>
    @forelse ($rows as $i => $row)
      <tr>
        <td class="text-muted">{{ $i + 1 }}</td>
        <td>{{ \Illuminate\Support\Str::limit($row['name'], 50) }}</td>
        <td class="num">{{ number_format($row['units']) }}</td>
        <td class="num">{{ number_format($row['orders']) }}</td>
        <td class="num">{{ report_money($row['revenue']) }}</td>
      </tr>
    @empty
      <tr><td colspan="5" class="report-empty">{{ trans('reports.empty') }}</td></tr>
    @endforelse
  </tbody>
</table>
