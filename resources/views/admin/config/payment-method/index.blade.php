@extends('admin.layouts.master')

@section('page_title')
  {{ trans('app.payment_methods') }}
@endsection

@php
  $can_update = Gate::allows('update', $config) ?? null;
  $active_payment_methods = $config->paymentMethods->pluck('id')->toArray();
  $has_config = false;
@endphp

@section('content')
  @include('admin.partials.ui.card_start', [
    'title' => trans('app.payment_methods'),
    'icon' => 'fa-credit-card',
    'bodyClass' => 'admin-card__body--settings',
  ])

      <div class="row">
        <div class="col-sm-12">
          <div class="alert alert-info">
            <strong>{{ trans('packages.wallet.payment_method_fee_admin_title') }}</strong>
            @php
              $subscriptionPlansUrl = \Illuminate\Support\Facades\Route::has('admin.subscriptionPlan.index')
                  ? route('admin.subscriptionPlan.index')
                  : url('admin/subscriptionPlan');
              $walletSettingsUrl = \Illuminate\Support\Facades\Route::has('admin.wallet.settings')
                  ? route('admin.wallet.settings')
                  : (\Illuminate\Support\Facades\Route::has('admin.admin.wallet.settings')
                      ? route('admin.admin.wallet.settings')
                      : url('admin/setting/wallet'));
            @endphp
            <ul class="mb-0 mt-2">
              <li>{!! trans('packages.wallet.payment_method_fee_admin_checkout', ['url' => $subscriptionPlansUrl]) !!}</li>
              <li>{!! trans('packages.wallet.payment_method_fee_admin_topup', ['url' => $walletSettingsUrl]) !!}</li>
            </ul>
          </div>
          @foreach ($payment_method_types as $type_id => $type)
            @php
              $payment_providers = $payment_methods->where('type', $type_id);
              $logo_path = sys_image_path('payment-method-types') . "{$type_id}.svg";
            @endphp

            @if ($payment_providers->count())
              <div class="row">
                <span class="spacer10"></span>
                <div class="col-sm-5">
                  @if (File::exists($logo_path))
                    <img src="{{ asset($logo_path) }}" width="100" height="25" alt="{{ $type }}">
                    <span class="spacer10"></span>
                  @else
                    <p class="lead">{{ $type }}</p>
                  @endif
                  <p>{!! get_payment_method_type($type_id)['description'] !!}</p>
                </div> <!-- /.col-ms-5 -->

                <div class="col-sm-7">
                  @foreach ($payment_providers as $payment_provider)
                    {{-- Skip the wallet because wallet setting has option to activate --}}
                    @continue(in_array($payment_provider->code, ['zcart-wallet', 'stripe'], true))

                    @php
                      $has_config = false;
                      $logo_path = sys_image_path('payment-methods') . "{$payment_provider->code}.png";
                    @endphp
                    <ul class="list-group">
                      <li class="list-group-item">
                        @if (File::exists($logo_path))
                          <img src="{{ asset($logo_path) }}" class="open-img-md" alt="{{ $payment_provider->name }}">
                        @endif
                        <p class="list-group-item-heading inline lead" style="{{ File::exists($logo_path) ? 'margin-left: 8px;' : '' }}">
                          {{ $payment_provider->name }}
                        </p>

                        <span class="spacer10"></span>

                        <p class="list-group-item-text">
                          {!! $payment_provider->description !!}
                        </p>

                        <span class="spacer20"></span>

                        @if (in_array($payment_provider->id, $active_payment_methods))
                          @if ($can_update)
                            @php
                              $has_config = in_array($payment_provider->code, ['mpesa', 'emola'], true)
                                  ? \App\Models\SystemConfig::isPaymentConfigured($payment_provider->code)
                                  : (! empty($config->{$payment_provider->code}) && in_array($payment_provider->code, (array) $config));
                            @endphp

                            @unless ($has_config)
                              <div class="alert alert-danger">@lang('app.payment_method_configuration_issue')</div>
                            @endunless

                            <a href="javascript:void(0)" data-link="{{ route('admin.setting.paymentMethod.activate', $payment_provider->id) }}" class="btn ajax-modal-btn btn-info">{{ trans('app.update') }}</a>

                            <a href="{{ route('admin.setting.paymentMethod.deactivate', $payment_provider->id) }}" class="btn btn-default ajax-silent confirm"> {{ trans('app.deactivate') }}</a>
                          @else
                            <span class="label label-default">{{ trans('app.active') }}</span>
                          @endif
                        @else
                          @if ($can_update)
                            <a href="javascript:void(0)" data-link="{{ route('admin.setting.paymentMethod.activate', $payment_provider->id) }}" class="btn ajax-modal-btn btn-primary">{{ $has_config ? trans('app.reactivate') : trans('app.activate') }}</a>
                          @else
                            <span class="label label-default">{{ trans('app.inactive') }}</span>
                          @endif
                        @endif

                        <span class="spacer15"></span>
                      </li>
                    </ul>
                  @endforeach
                </div> <!-- /.col-ms-7 -->
              </div> <!-- /.row -->

              @unless ($loop->last)
                <hr />
              @endunless
            @endif
          @endforeach
        </div> <!-- /.col-sm-12 -->
      </div> <!-- /.row -->

  @include('admin.partials.ui.card_end')
@endsection
