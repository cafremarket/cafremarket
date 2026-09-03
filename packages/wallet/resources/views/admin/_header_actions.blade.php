<a href="javascript:void(0)" data-link="{{ route('admin.wallet.create') }}" class="ajax-modal-btn btn btn-default btn-flat">
  <i class="fa fa-wallet"></i> {{ trans('packages.wallet.create_wallet') }}
</a>
<a href="javascript:void(0)" data-link="{{ route('admin.wallet.topup') }}" class="ajax-modal-btn btn btn-new btn-flat">
  <i class="fa fa-plus"></i> {{ trans('packages.wallet.topup_wallet') }}
</a>
<a href="{{ route('admin.wallet.transactions') }}" class="btn btn-default btn-flat">
  <i class="fa fa-list"></i> {{ trans('packages.wallet.wallet_logs') }}
</a>
