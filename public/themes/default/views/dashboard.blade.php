@extends('theme::layouts.main')

@section('content')
  <section class="sf-dashboard-page account-section pb-5">
    <div class="container">
      @if (!Auth::guard('customer')->user()->isVerified())
        <div class="sf-alert sf-alert--info">
          <i class="fas fa-info-circle" aria-hidden="true"></i>
          <div>
            <strong>{{ trans('theme.notice') }}</strong>
            {{ trans('messages.email_verification_notice') }}
            <a href="{{ route('customer.verify') }}">{{ trans('auth.resend_verification_link') }}</a>
          </div>
        </div>
      @endif

      <div class="sf-account-layout">
        @include('theme::nav.account_sidebar')

        <div class="sf-account-content">
          <div class="sf-account-page-head">
            <div>
              <p class="sf-account-page-head__eyebrow">@lang('theme.nav.my_account')</p>
              <h1 class="sf-account-page-head__title">@lang('theme.' . $tab)</h1>
            </div>
          </div>

          @if (isset($content))
            {!! $content !!}
          @else
            @if ($tab == 'events')
              @if (is_incevio_package_loaded('eventy'))
                @include('eventy::frontend.customer_events')
              @endif
            @else
              @include('theme::contents.' . $tab)
            @endif
          @endif
        </div>
      </div>
    </div>
  </section>

  @include('theme::sections.recent_views')
@endsection

@if (request()->is('*/wallet/deposit/form'))
  @section('scripts')
    @include('wallet::customer.scripts.deposit')
  @endsection
@endif
