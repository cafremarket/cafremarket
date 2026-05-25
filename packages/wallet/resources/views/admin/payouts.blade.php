@extends('admin.layouts.master')

@section('content')
  <div class="box">
    <div class="box-header with-border">
      <h3 class="box-title">{{ trans('packages.wallet.payouts') }}</h3>
      <div class="box-tools pull-right">
        @include('wallet::admin._btn_payout')
      </div>
    </div> <!-- /.box-header -->

    <div class="box-body">
      <table class="table table-hover table-no-sort">
        <thead>
          <tr>
            <th>{{ trans('packages.wallet.date') }}</th>
            <th>{{ trans('packages.wallet.shop') }}</th>
            <th>{{ trans('packages.wallet.description') }}</th>
            <th>{{ trans('packages.wallet.remaining_balance') }}</th>
            <th>{{ trans('packages.wallet.amount') }}</th>
            <th>{{ trans('packages.wallet.status') }}</th>
            <th>{{ trans('packages.wallet.payout_payment_proof') }}</th>
            <th>{{ trans('packages.wallet.option') }}</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($payouts as $transaction)
            @if ($transaction->isTypeOf(\Incevio\Package\Wallet\Models\Transaction::TYPE_PAYOUT))
              <tr>
                <td>
                  {{ $transaction->created_at->toFormattedDateString() }}
                </td>
                <td>
                  {{ optional($transaction->payable)->getName() }}
                </td>
                <td>
                  {!! $transaction->getFromMetaData('description') !!}
                </td>
                <td>
                  {{ get_formated_currency($transaction->balance, 2, config('system_settings.currency.id')) }}
                </td>
                <td>
                  {{ get_formated_currency($transaction->amount, 2, config('system_settings.currency.id')) }}
                </td>
                <td>
                  {!! $transaction->statusName() !!}
                </td>
                <td>
                  @include('wallet::admin.partials._payout_payment_proof', ['transaction' => $transaction])
                </td>
                <td class="text-nowrap">
                  @if ($transaction->isApproved())
                    <a href="{{ route('wallet.transaction.invoice', $transaction) }}" class="btn btn-default btn-sm btn-flat">
                      <i class="fa fa-file-o"></i> {{ trans('app.invoice') }}
                    </a>
                  @endif
                  @if ($transaction->hasPayoutPaymentProof())
                    <a href="{{ route('wallet.transaction.payout_proof.download', $transaction) }}" class="btn btn-default btn-sm btn-flat">
                      <i class="fa fa-download"></i> {{ trans('packages.wallet.payout_payment_proof_download') }}
                    </a>
                  @endif
                </td>
              </tr>
            @endif
          @endforeach
        </tbody>
      </table>
    </div> <!-- /.box-body -->
  </div> <!-- /.box -->
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
