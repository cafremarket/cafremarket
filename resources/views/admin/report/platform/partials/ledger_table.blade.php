{{-- Per-order commission ledger. Pass: $ledger, $withShop (bool). --}}
<table class="table table-hover report-dt" data-order='[0, "desc"]'>
  <thead>
    <tr>
      <th>{{ trans('reports.col.date') }}</th>
      <th>{{ trans('reports.col.order') }}</th>
      @if ($withShop)
        <th>{{ trans('reports.col.shop') }}</th>
      @endif
      <th>{{ trans('reports.col.customer') }}</th>
      <th>{{ trans('reports.col.order_status') }}</th>
      <th class="num">{{ trans('reports.col.gross') }}</th>
      <th class="num">{{ trans('reports.col.commission') }}</th>
      <th class="num">{{ trans('reports.col.rate') }}</th>
      <th class="num">{{ trans('reports.col.affiliate') }}</th>
      <th class="num">{{ trans('reports.col.net_vendor') }}</th>
      <th>{{ trans('reports.col.settlement') }}</th>
    </tr>
  </thead>
  <tbody>
    @foreach ($ledger as $row)
      <tr>
        <td data-order="{{ $row['date'] }}">{{ report_date($row['date']) }}</td>
        <td>{{ $row['order_number'] }}<br><small class="text-muted">{{ $row['payment_method'] }}</small></td>
        @if ($withShop)
          <td>{{ $row['shop'] }}</td>
        @endif
        <td>{{ $row['customer'] ?: '—' }}</td>
        <td>{{ $row['order_status'] }}</td>
        <td class="num" data-order="{{ $row['gross'] }}">{{ report_money($row['gross']) }}</td>
        <td class="num" data-order="{{ $row['commission'] }}">
          <strong>{{ report_money($row['commission']) }}</strong>
          @if ($row['reversed_commission'] > 0)
            <br><small class="text-danger">−{{ report_money($row['reversed_commission']) }}</small>
          @endif
        </td>
        <td class="num" data-order="{{ $row['rate'] }}">{{ report_percent($row['rate'], 2) }}</td>
        <td class="num" data-order="{{ $row['affiliate'] }}">{{ report_money($row['affiliate']) }}</td>
        <td class="num" data-order="{{ $row['net_vendor'] }}">{{ report_money($row['net_vendor']) }}</td>
        <td>
          <span class="report-status report-status--{{ $row['status'] }}">{{ $row['status_label'] }}</span>
          @if ($row['settled_at'])
            <br><small class="text-muted">{{ report_date($row['settled_at']) }}</small>
          @endif
        </td>
      </tr>
    @endforeach
  </tbody>
</table>
