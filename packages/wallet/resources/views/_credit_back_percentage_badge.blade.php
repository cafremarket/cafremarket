@if (is_wallet_credit_reward_enabled())
  <span class="label label-primary" data-toggle="tooltip" data-placement="top" title="{{ trans('packages.wallet.get_percentage_credit_back', ['percentage' => $rw_percentage]) }}"><i class="fa fa-star"></i>
    {!! trans('packages.wallet.get_credit_back_rewards', ['percentage' => $rw_percentage]) !!}
  </span>
@endif
