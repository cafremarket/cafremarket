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
                  @if ($transaction->isApproved())
                    <a href="{{ route('wallet.transaction.invoice', $transaction) }}" class="btn btn-default btn-sm btn-flat">
                      <i class="fa fa-file-o"></i> {{ trans('app.invoice') }}
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
