<div class="sf-wallet-page">
  <div class="sf-dashboard-welcome">
    <div class="sf-dashboard-welcome__top">
      <div class="sf-dashboard-welcome__greeting">
        <p class="sf-dashboard-welcome__eyebrow">@lang('packages.wallet.my_wallet')</p>
        <h2>{{ get_formated_currency($wallet->balance, 2) }}</h2>
        <p>@lang('packages.wallet.available_balance')</p>
      </div>

      <div class="sf-wallet-actions">
        <a href="{{ route('customer.account.wallet.deposit.form') }}" class="btn sf-btn-primary btn-sm">
          <i class="fas fa-plus" aria-hidden="true"></i> {{ trans('packages.wallet.deposit_fund') }}
        </a>

        @if (config('wallet.transfer.storefront') == true)
          <a href="{{ route('customer.account.wallet.transfer.form') }}" class="btn btn-default btn-sm">
            <i class="fas fa-exchange-alt" aria-hidden="true"></i> {{ trans('packages.wallet.transfer') }}
          </a>
        @endif

        @if (config('wallet.transfer.storefront') == true && !customer_can_register())
          <a href="{{ route('customer.account.wallet.transfer.self_transfer_form') }}" class="btn btn-default btn-sm">
            <i class="fas fa-store" aria-hidden="true"></i> {{ trans('packages.wallet.transfer_self_merchant') }}
          </a>
        @endif
      </div>
    </div>
  </div>

  <div class="sf-stat-grid">
    <div class="sf-stat-card sf-stat-card--action">
      <span class="sf-stat-card__icon"><i class="fas fa-wallet" aria-hidden="true"></i></span>
      <span class="sf-stat-card__value">{{ get_formated_currency($wallet->balance, 2) }}</span>
      <span class="sf-stat-card__label">{{ trans('packages.wallet.available_balance') }}</span>
    </div>

    <div class="sf-stat-card sf-stat-card--action">
      <span class="sf-stat-card__icon"><i class="fas fa-arrow-down" aria-hidden="true"></i></span>
      <span class="sf-stat-card__value">{{ get_formated_currency($wallet->lastDeposit ? $wallet->lastDeposit->amount : 0, 2) }}</span>
      <span class="sf-stat-card__label">{{ trans('packages.wallet.last_deposit') }}</span>
    </div>

    <div class="sf-stat-card sf-stat-card--action">
      <span class="sf-stat-card__icon"><i class="fas fa-arrow-up" aria-hidden="true"></i></span>
      <span class="sf-stat-card__value">{{ get_formated_currency($wallet->lastDebited ? $wallet->lastDebited->amount : 0, 2) }}</span>
      <span class="sf-stat-card__label">{{ trans('packages.wallet.last_debited') }}</span>
    </div>

    <a href="{{ route('customer.account.wallet.deposit.form') }}" class="sf-stat-card">
      <span class="sf-stat-card__icon"><i class="fas fa-plus-circle" aria-hidden="true"></i></span>
      <span class="sf-stat-card__value">@lang('packages.wallet.deposit_fund')</span>
      <span class="sf-stat-card__label">@lang('theme.wallet')</span>
    </a>
  </div>

  <div class="sf-panel">
    <div class="sf-panel__head">
      <span>{{ trans('packages.wallet.transaction_type') }}</span>
    </div>
    <div class="sf-panel__body table-responsive">
      <table class="table table-hover">
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
              <td>{{ $transaction->updated_at->toFormattedDateString() }}</td>
              <td>{{ $transaction->type }}</td>
              <td>{!! $transaction->getFromMetaData('description') !!}</td>
              <td>
                @include('wallet::partials.transaction_amount_cell', ['transaction' => $transaction])
              </td>
              <td>{!! $transaction->statusName() !!}</td>
              <td>
                @if ($transaction->confirmed)
                  <a href="{{ route('wallet.transaction.invoice', $transaction) }}" class="btn btn-default btn-xs">
                    <i class="fas fa-file-alt" aria-hidden="true"></i>
                    {{ trans('app.invoice') }}
                  </a>
                @endif
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="text-center text-muted py-4">{{ trans('packages.wallet.no_transaction_found') }}</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
