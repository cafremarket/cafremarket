@if (Auth::user()->isSubscribed() && !Auth::user()->shop->hide_trial_notice)
  @php
    $subscription = Auth::user()->getCurrentPlan();
  @endphp

  @if ($subscription && $subscription->onTrial())
    <div class="mp-notice mp-notice--warning">
      <div class="mp-notice__body">
        <strong><i class="fa fa-info-circle"></i> {{ trans('app.notice') }}</strong>
        {{ trans('messages.trial_ends_at', ['ends' => remaining_days_until($subscription->trial_ends_at)]) }}
      </div>
    </div>
  @elseif($subscription && Auth::user()->isOnGracePeriod())
    <div class="mp-notice mp-notice--danger">
      <div class="mp-notice__body">
        <strong><i class="fa fa-info-circle"></i> {{ trans('app.notice') }}</strong>
        {{ trans('messages.resume_subscription', ['ends' => remaining_days_until($subscription->ends_at)]) }}
      </div>
      <a href="{{ route('merchant.account.subscription.resume') }}" class="mp-btn mp-btn--primary mp-btn--sm confirm">
        <i class="fa fa-rocket"></i> {{ trans('app.resume_subscription') }}
      </a>
    </div>
  @elseif($subscription && $subscription->provider == 'wallet' && $subscription->active() && optional($subscription->ends_at))
    <div class="mp-notice mp-notice--success">
      <div class="mp-notice__body">
        <strong><i class="fa fa-info-circle"></i> {{ trans('app.notice') }}</strong>
        {!! trans('messages.next_billing_date', ['date' => $subscription->ends_at->toDayDateTimeString()]) !!}
      </div>
      @if (Route::has('merchant.wallet.deposit.form'))
        <a href="{{ route('merchant.wallet.deposit.form') }}" class="mp-btn mp-btn--primary mp-btn--sm">
          {{ trans('packages.wallet.deposit_fund') }}
        </a>
      @endif
    </div>
  @elseif(Auth::user()->isOnGenericTrial())
    <div class="mp-notice mp-notice--warning">
      <div class="mp-notice__body">
        <strong><i class="fa fa-info-circle"></i> {{ trans('app.notice') }}</strong>
        {{ trans('messages.generic_trial_ends_at', ['ends' => remaining_days_until(Auth::user()->shop->trial_ends_at)]) }}
      </div>
      @unless (Request::is('merchant/account/billing'))
        <a href="{{ route('merchant.account.billing') }}" class="mp-btn mp-btn--primary mp-btn--sm">
          <i class="fa fa-rocket"></i> {{ trans('app.choose_plan') }}
        </a>
      @endunless
    </div>
  @endif
@elseif(Auth::user()->hasExpiredPlan())
  <div class="mp-notice mp-notice--danger">
    <div class="mp-notice__body">
      <strong><i class="fa fa-info-circle"></i> {{ trans('app.notice') }}</strong>
      {{ trans('messages.trial_expired') }}
    </div>
    @unless (Request::is('merchant/account/billing'))
      <a href="{{ route('merchant.account.billing') }}" class="mp-btn mp-btn--primary mp-btn--sm">
        <i class="fa fa-rocket"></i> {{ trans('app.choose_plan') }}
      </a>
    @endunless
  </div>
@endif
