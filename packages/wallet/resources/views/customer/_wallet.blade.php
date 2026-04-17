<div class="row">
  <div class="col-12">
    <div class="my-info-container">
      <div class="my-info-details radius" style="border-top: 1px #e8e8e8 solid;">
        <ul>
          <li>
            <span class="v">
              {{ get_formated_currency($wallet->balance, 2) }}
            </span>

            <span class="d">{{ trans('packages.wallet.available_balance') }}</span>
          </li>

          <li>
            <span class="v">
              {{ get_formated_currency($wallet->lastDeposit ? $wallet->lastDeposit->amount : 0, 2) }}
            </span>
            <span class="d">{{ trans('packages.wallet.last_deposit') }}</span>
          </li>

          <li>
            <span class="v">
              {{ get_formated_currency($wallet->lastDebited ? $wallet->lastDebited->amount : 0, 2) }}
            </span>
            <span class="d">{{ trans('packages.wallet.last_debited') }} </span>
          </li>

          <li>
            <a href="{{ route('customer.account.wallet.deposit.form') }}">
              <span class="d text-primary">
                <i class="fa fa-plus"></i> {{ trans('packages.wallet.deposit_fund') }}
              </span>
            </a>
          </li>

          <li>
            @if (config('wallet.transfer.storefront') == true)
              <a href="{{ route('customer.account.wallet.transfer.form') }}">
                <span class="d text-primary">
                  <i class="fa fa-exchange"></i> {{ trans('packages.wallet.transfer') }}
                </span>
              </a>
            @endif
          </li>
          <li>
            @if (config('wallet.transfer.storefront') == true && !customer_can_register())
              <a href="{{ route('customer.account.wallet.transfer.self_transfer_form') }}">
                <span class="d text-primary">
                  <i class="fa fa-exchange"></i> {{ trans('packages.wallet.transfer_self_merchant') }}
                </span>
              </a>
            @endif
          </li>
        </ul>
      </div><!-- .my-info-details -->
    </div><!-- .my-info-container -->
  </div><!-- .col-sm-12 -->
</div><!-- .row -->

<table class="table table-bordered table-no-sort">
  <thead>
    <tr>
      <th>{{ trans('packages.wallet.date') }}</th>
      <th>{{ trans('packages.wallet.transaction_type') }}</th>
      <th>{{ trans('packages.wallet.description') }}</th>
      <th>{{ trans('packages.wallet.amount') }}</th>
      <th>{{ trans('packages.wallet.status') }}</th>
      <th>{{ trans('packages.wallet.option') }}</th>
    </tr>
  </thead>

  <tbody>
    @forelse($wallet->transactions()->take(10)->get() as $transaction)
      <tr>
        <td>
          {{ $transaction->updated_at->toFormattedDateString() }}
        </td>
        <td>
          {{ $transaction->type }}
        </td>
        <td>
          {!! $transaction->getFromMetaData('description') !!}
        </td>
        <td>
          {{ get_formated_currency($transaction->amount, 2, config('system_settings.currency.id')) }}
        </td>
        <td>
          {!! $transaction->statusName() !!}
        </td>
        <td>
          @if ($transaction->confirmed)
            <a href="{{ route('wallet.transaction.invoice', $transaction) }}" class="btn btn-default btn-sm btn-flat">
              <i class="fa fa-file-o"></i>
              {{ trans('app.invoice') }}
            </a>
          @endif
        </td>
      </tr>
    @empty
      <tr>
        <td colspan="6">
          <h4 class="text-center text-muted">{{ trans('packages.wallet.no_transaction_found') }}</h4>
        </td>
      </tr>
    @endforelse
  </tbody>
</table>
