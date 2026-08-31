@extends('admin.layouts.master')

@section('page_title')
  {{ trans('packages.wallet.payouts') }}
@endsection

@section('content')
  @include('admin.partials.ui.card_start', [
    'title' => trans('packages.wallet.payouts'),
    'icon' => 'fa-money',
    'actions' => view('wallet::admin._btn_payout')->render(),
  ])

  <table class="table table-hover admin-table table-no-sort">
    <thead>
      <tr>
        <th>{{ trans('packages.wallet.date') }}</th>
        <th>{{ trans('packages.wallet.shop') }}</th>
        <th>{{ trans('packages.wallet.description') }}</th>
        <th>{{ trans('packages.wallet.remaining_balance') }}</th>
        <th>{{ trans('packages.wallet.amount') }}</th>
        <th>{{ trans('packages.wallet.status') }}</th>
        <th>{{ trans('packages.wallet.payout_payment_proof') }}</th>
        <th class="admin-table__actions-col">{{ trans('packages.wallet.option') }}</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($payouts as $transaction)
        @if ($transaction->isTypeOf(\Incevio\Package\Wallet\Models\Transaction::TYPE_PAYOUT))
          <tr>
            <td class="small">{{ $transaction->created_at->toFormattedDateString() }}</td>
            <td>{{ optional($transaction->payable)->getName() }}</td>
            <td class="small">{!! $transaction->getFromMetaData('description') !!}</td>
            <td>{{ get_formated_currency($transaction->balance, 2, config('system_settings.currency.id')) }}</td>
            <td>{{ get_formated_currency($transaction->amount, 2, config('system_settings.currency.id')) }}</td>
            <td>{!! $transaction->statusName() !!}</td>
            <td>@include('wallet::admin.partials._payout_payment_proof', ['transaction' => $transaction])</td>
            <td class="row-options admin-row-actions">
              @if ($transaction->isApproved())
                <a href="{{ route('wallet.transaction.invoice', $transaction) }}" class="admin-action-btn" title="{{ trans('app.invoice') }}" data-toggle="tooltip"><i class="fa fa-file-o"></i></a>
              @endif
              @if ($transaction->hasPayoutPaymentProof())
                <a href="{{ route('wallet.transaction.payout_proof.download', $transaction) }}" class="admin-action-btn" title="{{ trans('packages.wallet.payout_payment_proof_download') }}" data-toggle="tooltip"><i class="fa fa-download"></i></a>
              @endif
            </td>
          </tr>
        @endif
      @endforeach
    </tbody>
  </table>

  @include('admin.partials.ui.card_end')
@endsection

@section('page-script')
  <script>
    document.addEventListener('click', function (e) {
      var trigger = e.target.closest('.wire-proof-preview');
      if (!trigger) return;
      e.preventDefault();
      var src = trigger.getAttribute('data-src');
      if (!src) return;
      var name = trigger.getAttribute('data-name') || 'Payment proof';
      var html = '<div class="text-center"><p><strong>' + name + '</strong></p>' +
        '<img src="' + src + '" class="img-responsive" style="max-height:70vh;margin:0 auto;"></div>';
      if (typeof bootbox !== 'undefined') {
        bootbox.alert({ message: html, size: 'large' });
      } else {
        window.open(src, '_blank');
      }
    });
  </script>
@endsection
