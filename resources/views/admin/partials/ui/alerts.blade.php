{{-- Flash alerts, validation errors, and platform notices --}}
@if (Request::session()->has('impersonated'))
  <div class="admin-alert admin-alert--info no-print">
    <div class="admin-alert__icon"><i class="fa fa-user-secret"></i></div>
    <div class="admin-alert__body">
      <strong>{{ trans('app.alert') }}</strong>
      {{ trans('messages.you_are_impersonated') }}
    </div>
    <a href="{{ route('admin.secretLogout') }}" class="admin-alert__action" title="{{ trans('app.log_out') }}">
      <i class="fa fa-sign-out"></i>
    </a>
  </div>
@endif

@if (isset($errors) && count($errors) > 0)
  <div class="admin-alert admin-alert--danger no-print">
    <div class="admin-alert__icon"><i class="fa fa-exclamation-circle"></i></div>
    <div class="admin-alert__body">
      <strong>{{ trans('app.error') }}!</strong> {{ trans('messages.input_error') }}
      <ul class="admin-alert__list">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  </div>
@endif

@include('admin.partials._global_notice')

@if (Auth::check() && Auth::user()->isFromMerchant())
  @if (Auth::user()->hasBillingInfo() || !requires_stripe_card_for_subscription())
    @unless (Auth::user()->isVerified())
      <div class="admin-alert admin-alert--info admin-alert--dismissible no-print">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        <div class="admin-alert__icon"><i class="fa fa-info-circle"></i></div>
        <div class="admin-alert__body">
          <strong>{{ trans('app.notice') }}</strong>
          {{ trans('messages.email_verification_notice') }}
          <a href="{{ route('verify') }}">{{ trans('app.resend_verification_link') }}</a>
        </div>
      </div>
    @endunless

    @if (optional(Auth::user()->shop)->config && ! Auth::user()->shop->isVerified())
      <div class="admin-alert admin-alert--warning admin-alert--dismissible no-print">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        <div class="admin-alert__icon"><i class="fa fa-shield"></i></div>
        <div class="admin-alert__body">
          <strong>{{ trans('app.verification') }}</strong>
          @if (Auth::user()->shop->config->pending_verification)
            {{ trans('messages.verification_request_pending_notice') }}
          @elseif (Auth::user()->shop->config->verification_rejected_at)
            {{ trans('messages.verification_request_rejected_notice') }}
            <a href="{{ route('admin.setting.verify') }}">{{ trans('app.get_verified') }}</a>
          @else
            {{ trans('messages.verification_intro') }}
            <a href="{{ route('admin.setting.verify') }}">{{ trans('app.get_verified') }}</a>
          @endif
        </div>
      </div>
    @endif

    @include('admin.partials._listings_notice')
  @endif
@endif
