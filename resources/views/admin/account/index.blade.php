@extends('admin.layouts.master')

@section('page_title')
  {{ trans('app.profile') }}
@endsection

@section('content')
  @if (Auth::user()->isFromPlatform())
    @include('admin.partials.ui.card_start', [
      'title' => trans('app.profile'),
      'icon' => 'fa-user',
      'bodyClass' => '',
    ])
      @include('admin.account._profile')
    @include('admin.partials.ui.card_end')
  @else
    @include('admin.partials.ui.card_tabbed_start', [
      'title' => trans('app.account'),
      'icon' => 'fa-user',
    ])
        <ul class="nav nav-tabs nav-justified admin-tabs">
          <li class="{{ Request::is('admin/account/profile') ? 'active' : '' }}">
            <a href="#profile_tab" data-toggle="tab">
              <i class="fa fa-user hidden-sm"></i>
              {{ trans('app.profile') }}
            </a>
          </li>

          <li class="{{ Request::is('admin/account/billing') ? 'active' : '' }}">
            <a href="#billing_tab" data-toggle="tab">
              <i class="fa fa-credit-card hidden-sm"></i>
              {{ trans('app.billing') }}
            </a>
          </li>

          <li class="{{ Request::is('admin/account/ticket') ? 'active' : '' }}">
            <a href="#ticket_tab" data-toggle="tab">
              <i class="fa fa-ticket hidden-sm"></i>
              {{ trans('app.tickets') }}
            </a>
          </li>
        </ul>
        <div class="tab-content">
          <div class="tab-pane {{ Request::is('admin/account/profile') ? 'active' : '' }}" id="profile_tab">
            @include('admin.account._profile')
          </div>

          <div class="tab-pane {{ Request::is('admin/account/billing') ? 'active' : '' }}" id="billing_tab">
            @include('admin.account._billing')
          </div>

          <div class="tab-pane {{ Request::is('admin/account/ticket') ? 'active' : '' }}" id="ticket_tab">
            @include('admin.account._ticket')
          </div>
        </div> <!-- /.tab-content -->

    @include('admin.partials.ui.card_tabbed_end')
  @endif
@endsection

@section('page-script')
  @includeWhen(
    Auth::user()->isFromMerchant()
      && Request::is('admin/account/billing')
      && \App\Models\SystemConfig::isPaymentConfigured('stripe')
      && !\App\Models\SystemConfig::isBillingThroughWallet(),
    'plugins.stripe-scripts'
  )
@endsection
