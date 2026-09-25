<a href="{{ route('admin.wallet.payout.requests') }}">
  <i class="fa fa-money text-green"></i>&nbsp;{{ trans('notifications.withdrawal_requested.short', ['shop_name' => $notification->data['shop_name'] ?? '', 'amount' => $notification->data['amount'] ?? '']) }}
</a>
