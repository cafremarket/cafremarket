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
                  {{ $transaction->payable->getName() }}
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
                </td>
              </tr>
            @endif
          @endforeach
        </tbody>
      </table>
  @include('admin.partials.ui.card_end')
@endsection
