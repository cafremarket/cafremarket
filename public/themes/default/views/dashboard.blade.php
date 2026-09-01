@extends('theme::layouts.main')

@section('content')
  <div class="container">
    <header class="page-header">
      <ol class="breadcrumb nav-breadcrumb">
        @include('theme::headers.lists.home')
        @include('theme::headers.lists.account')
        <li class="active">@lang('theme.' . $tab)</li>
      </ol>
    </header>
  </div>

  <div class="container">
    @if (!Auth::guard('customer')->user()->isVerified())
      <div class="alert alert-info alert-dismissible">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
        <strong><i class="icon fas fa-info-circle"></i> {{ trans('theme.notice') }}</strong>
        {{ trans('messages.email_verification_notice') }}
        <a href="{{ route('customer.verify') }}"> {{ trans('auth.resend_verification_link') }}</a>
      </div>
    @endif
  </div>

  <section class="account-section pb-5">
    <div class="container">
      <div class="sf-account-layout">
        @include('theme::nav.account_sidebar')

        <div class="sf-account-content">
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
