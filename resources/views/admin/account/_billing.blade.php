<div class="row">
  <div class="col-md-12">
    @include('admin.partials._subscription_notice')

    <!-- Error Message -->
    @if (Session::has('error'))
      <div class="alert alert-danger">{{ Session::get('error') }}</div>
    @endif
  </div>

  <div class="col-md-8 col-md-offset-2">
    @if (Auth::user()->hasExpiredPlan())
      <div class="alert alert-danger">
        <strong><i class="icon fa fa-info-circle"></i>{{ trans('app.notice') }}</strong>
        {{ trans('messages.subscription_expired') }}
      </div>
    @endif

    @unless (Auth::user()->isSubscribed())
      <div class="alert alert-info">
        <i class="icon fa fa-rocket"></i>{{ trans('messages.choose_subscription') }}
      </div>
    @endunless

    <div class="panel panel-default">
      <div class="panel-body">
        <fieldset>
          <legend>{{ trans('app.subscription_plans') }}</legend>
          <div class="table-responsive">
            <table class="table no-border">
              <tbody>
                @foreach ($plans as $plan)
                  <tr>
                    <td>
                      <span class="lead">{{ $plan->name }}</span>

                      @if (optional($current_plan)->stripe_price == $plan->plan_id)
                        <i class="fa fa-dot-circle-o text-primary indent5" data-toggle="tooltip" title="{{ trans('app.current_plan') }}"></i>
                      @endif

                      <p class="hidden-md hidden-lg hidden-xl hidden-xxl">
                        {{ get_formated_currency($plan->cost, 2, config('system_settings.currency.id')) . trans('app.per_month') }}
                      </p>
                    </td>

                    <td class="hidden-xs hidden-sm">
                      <a href="javascript:void(0)" data-link="{{ route('admin.account.subscription.features', $plan->plan_id) }}" class="ajax-modal-btn btn btn-link">
                        <i class="fa fa-star-o"></i> {{ trans('app.features') }}
                      </a>
                    </td>

                    <td class="lead hidden-xs hidden-sm">
                      {{ get_formated_currency($plan->cost, 2, config('system_settings.currency.id')) . trans('app.per_month') }}
                    </td>

                    @if (\Auth::user()->isMerchant())
                      <td class="pull-right">
                        @if (optional($current_plan)->type == $plan->name)
                          @if (Auth::user()->isOnGracePeriod())
                            <a href="{{ route('admin.account.subscription.resume') }}" class="confirm btn btn-lg btn-primary">
                              <i class="fa fa-play"></i> {{ trans('app.resume_subscription') }}
                            </a>
                          @elseif($current_plan->provider == 'stripe')
                            {!! Form::open(['route' => ['admin.account.subscription.cancel', $current_plan], 'method' => 'delete', 'class' => 'inline']) !!}
                            <button type="submit" class="confirm ajax-silent btn btn-lg btn-danger">
                              <i class="fa fa-times-circle-o"></i> {{ trans('app.cancel') }}
                            </button>
                            {!! Form::close() !!}
                          @else
                            <button class="btn btn-lg btn-new disabled">
                              <i class="fa fa-check-circle-o"></i> {{ trans('app.current_plan') }}
                            </button>
                          @endif
                        @else
                          <button type="button"
                            class="btn btn-lg btn-default subscription-plan-select"
                            data-plan-id="{{ $plan->plan_id }}"
                            data-plan-name="{{ $plan->name }}"
                            data-plan-cost="{{ get_formated_currency($plan->cost, 2, config('system_settings.currency.id')) . trans('app.per_month') }}">
                            <i class="fa fa-leaf"></i> {{ trans('app.select_this_plan') }}
                          </button>
                        @endif
                      </td>
                    @endif
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
          @if ((bool) config('system_settings.trial_days'))
            <span class="spacer10"></span>
            <span class="text-info">
              <i class="icon fa fa-info-circle"></i>
              {!! trans('messages.plan_comes_with_trial', ['days' => config('system_settings.trial_days')]) !!}
            </span>
          @endif
        </fieldset>
      </div>
    </div>

    @if (Auth::user()->isMerchant())
      <div class="panel panel-default">
        <div class="panel-body">
          <fieldset>
            <legend>{{ trans('app.billing') }}</legend>

            @include('admin.partials._check_misconfigured_subscription')

            @if (\App\Models\SystemConfig::isBillingThroughWallet() || is_subscription_enabled())
              @include('admin.account._subscription_billing_methods')
            @elseif (\App\Models\SystemConfig::isPaymentConfigured('stripe'))
              {{-- When Stripe is configured for billing --}}
              @if (isset($billable) && $billable->stripe_id && $billable->pm_last_four)
                @include('admin.account._creditcard_view', ['billable' => $billable])

                <span class="spacer10"></span>
                <p class="text-center">
                  <button type="button" class="btn btn-link" data-toggle="modal" data-target="#cardUpdateModal">
                    {{ trans('app.update_card') }}
                    <i class="icon fa fa-edit"></i>
                  </button>
                </p>

                <div class="modal fade" id="cardUpdateModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                  <div class="modal-dialog modal-sm">
                    <div class="modal-content">
                      <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                        {{ trans('app.update_card') }}
                      </div>
                      <div class="modal-body">
                        @include('admin.account._card_update')
                        <div class="spacer10"></div>
                      </div>
                    </div> <!-- / .modal-content -->
                  </div> <!-- / .modal-dialog -->
                </div>
              @else
                <div class="alert alert-info">
                  <strong><i class="icon fa fa-credit-card"></i></strong>
                  {{ trans('messages.no_billing_info') }}
                </div>

                @include('admin.account._card_update')
              @endif
            @elseif (is_billing_info_required())
              <div class="alert alert-warning">
                <strong><i class="icon fa fa-exclamation-triangle"></i></strong>
                {{ trans('messages.billing_setup_unavailable') }}
              </div>
            @else
              <p class="text-muted">{{ trans('messages.billing_not_required_for_plan') }}</p>
            @endif
          </fieldset>
        </div>
      </div>

      <div class="panel panel-default">
        <div class="panel-body">
          <fieldset>
            <legend>{{ trans('app.invoices') }} <i class="fa fa-files"></i> </legend>
            @include('admin.account._invoices', ['billable' => Auth::user()->shop])
          </fieldset>
        </div>
      </div>
    @else
      <div class="alert alert-danger">
        <strong><i class="icon fa fa-info-circle"></i>{{ trans('app.notice') }}</strong>
        {{ trans('messages.only_merchant_can_change_plan') }}
      </div>
    @endif

    <fieldset>
      <legend>{{ trans('app.history') }} <i class="fa fa-history"></i> </legend>
      @include('admin.account._activity_logs', ['logger' => Auth::user()->shop])
    </fieldset>
  </div>
</div>

@if (Auth::user()->isMerchant() && is_subscription_enabled())
  <div class="modal fade" id="subscriptionPaymentModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal">&times;</button>
          <h4 class="modal-title">{{ trans('messages.subscription_choose_payment') }}</h4>
        </div>
        <div class="modal-body">
          <p class="text-muted" id="subscription-plan-summary"></p>
          <div class="list-group" id="subscription-payment-options">
            @foreach (get_subscription_payment_methods() as $method)
              <a href="#" class="list-group-item subscription-pay-option" data-method="{{ $method['code'] }}">
                <i class="fa fa-{{ $method['code'] === 'wallet' ? 'wallet' : 'mobile' }}"></i>
                {{ $method['name'] }}
              </a>
            @endforeach
          </div>
          <div id="subscription-mobile-fields" style="display:none;">
            <div class="form-group mpesa-sub-field" style="display:none;">
              <label>{{ trans('mpesa::lang.mpesa_number') }}</label>
              <input type="text" class="form-control" id="subscription-mpesa-number" name="mpesa_number">
            </div>
            <div class="form-group emola-sub-field" style="display:none;">
              <label>{{ trans('theme.emola_number') }}</label>
              <input type="text" class="form-control" id="subscription-emola-number" name="emola_number" maxlength="9">
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">{{ trans('app.cancel') }}</button>
          <button type="button" class="btn btn-new" id="subscription-pay-confirm" disabled>{{ trans('app.confirm') }}</button>
        </div>
      </div>
    </div>
  </div>

  <script>
    (function () {
      var selectedPlan = null;
      var selectedMethod = null;

      $('.subscription-plan-select').on('click', function () {
        selectedPlan = $(this).data('plan-id');
        $('#subscription-plan-summary').text($(this).data('plan-name') + ' — ' + $(this).data('plan-cost'));
        selectedMethod = null;
        $('#subscription-pay-confirm').prop('disabled', true);
        $('#subscription-mobile-fields').hide();
        $('.subscription-pay-option').removeClass('active');
        $('#subscriptionPaymentModal').modal('show');
      });

      $('.subscription-pay-option').on('click', function (e) {
        e.preventDefault();
        selectedMethod = $(this).data('method');
        $('.subscription-pay-option').removeClass('active');
        $(this).addClass('active');
        $('#subscription-pay-confirm').prop('disabled', false);
        var showMobile = selectedMethod === 'mpesa' || selectedMethod === 'emola';
        $('#subscription-mobile-fields').toggle(showMobile);
        $('.mpesa-sub-field').toggle(selectedMethod === 'mpesa');
        $('.emola-sub-field').toggle(selectedMethod === 'emola');
      });

      $('#subscription-pay-confirm').on('click', function () {
        if (!selectedPlan || !selectedMethod) return;
        var url = '{{ url('admin/account/subscribe') }}/' + selectedPlan + '?payment_method=' + selectedMethod;
        if (selectedMethod === 'mpesa') {
          url += '&mpesa_number=' + encodeURIComponent($('#subscription-mpesa-number').val());
        }
        if (selectedMethod === 'emola') {
          url += '&emola_number=' + encodeURIComponent($('#subscription-emola-number').val());
        }
        window.location.href = url;
      });
    })();
  </script>
@endif
