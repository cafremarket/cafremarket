<a href="{{ route('admin.wallet.list') }}" class="btn btn-default btn-flat">
  <i class="fa fa-arrow-left"></i> {{ trans('packages.wallet.active_wallets') }}
</a>
<a href="javascript:void(0)" data-link="{{ route('admin.wallet.topup', $wallet ? ['wallet_id' => $wallet->id] : []) }}" class="ajax-modal-btn btn btn-new btn-flat">
  <i class="fa fa-plus"></i> {{ trans('packages.wallet.topup_wallet') }}
</a>
