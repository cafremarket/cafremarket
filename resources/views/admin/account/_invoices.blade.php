@php
  $walletInvoices = $billable
    ? $billable->subscriptionFeeTransactions()
    : collect();
@endphp

<ul class="list-group">
  @if ($walletInvoices->isNotEmpty())
    <table class="table">
      <thead>
        <tr>
          <th>{{ trans('app.date') }}</th>
          <th>{{ trans('app.description') }}</th>
          <th>{{ trans('app.status') }}</th>
          <th>{{ trans('app.amount') }}</th>
          <th>&nbsp;</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($walletInvoices as $transaction)
          <tr>
            <td>{{ $transaction->created_at?->toFormattedDateString() }}</td>
            <td>{{ $transaction->meta['description'] ?? trans('app.subscription_fee') }}</td>
            <td>{!! $transaction->statusName() !!}</td>
            <td>{{ get_formated_currency(abs((float) $transaction->amount), 2, config('system_settings.currency.id')) }}</td>
            <td>
              <a href="{{ route('wallet.transaction.invoice', $transaction) }}"><i class="fa fa-cloud-download" data-toggle="tooltip" data-placement="top" title="{{ trans('app.download') }}"></i></a>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  @else
    <span class="indent5">{{ trans('app.no_invoice') }}</span>
  @endif
</ul>
