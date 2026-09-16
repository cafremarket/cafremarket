
          <div class="jumbotron" style="padding: 20px; margin-bottom: 10px;">
            <p class="text-center">{{ trans('help.config_enable_payment_method') }}</p>
          </div>
          @foreach ($payment_method_types as $type_id => $type)
            @php
              $payment_providers = $payment_methods->where('type', $type_id);
              $logo_path = sys_image_path('payment-method-types') . "{$type_id}.svg";
            @endphp

            <div class="row">
              <span class="spacer10"></span>
              <div class="col-sm-6">
                @if (File::exists($logo_path))
                  <img src="{{ asset($logo_path) }}" width="100" height="25" alt="{{ $type }}">
                  <span class="spacer10"></span>
                @else
                  <p class="lead">{{ $type }}</p>
                @endif
                <p>{!! get_payment_method_type($type_id)['admin_description'] !!}</p>

                @if (!vendor_get_paid_directly() && $type_id == \App\Models\PaymentMethod::TYPE_MANUAL)
                  <div class="spacer20"></div>
                  <div class="alert alert-info">
                    <strong class="text-uppercase">
                      <i class="fa fa-info-circle"></i> {{ trans('app.important') }} :
                    </strong>
                    <span>{!! trans('messages.manual_payment_configure_help') !!}</span>
                  </div>
                @endif
              </div>

              <div class="col-sm-6">
                @foreach ($payment_providers as $payment_provider)
                  <!-- Skip removed gateways and wallet (wallet has its own setting) -->
                  @continue($payment_provider->code === 'zcart-wallet')

                  @php
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

                      <div class="handle inline pull-right no-margin">
                        <span class="spacer10"></span>
                        <a href="javascript:void(0)" data-link="{{ route('admin.setting.system.paymentMethod.toggle', $payment_provider->id) }}" type="button" class="btn btn-md btn-secondary btn-toggle {{ $payment_provider->enabled == 1 ? 'active' : '' }}" data-toggle="button" aria-pressed="{{ $payment_provider->enabled == 1 ? 'true' : 'false' }}" autocomplete="off">
                          <div class="btn-handle"></div>
                        </a>
                      </div>

                      <span class="spacer10"></span>

                      <p class="list-group-item-text">
                        {!! $payment_provider->admin_description !!}
                      </p>

                      @if (vendor_get_paid_directly() && !$payment_provider->split_money)
                        <div class="spacer20"></div>
                        <div class="alert alert-info small">
                          <strong class="text-uppercase">
                            <i class="fa fa-info-circle"></i> {{ trans('app.important') }} :
                          </strong>
                          <span>{!! trans('messages.cant_charge_application_fee') !!}</span>
                        </div>
                      @endif

                      {{-- Check if not miscofigured --}}
                      @if (!vendor_get_paid_directly() && !\App\Models\SystemConfig::isPaymentConfigured($payment_provider->code))
                        <div class="spacer20"></div>
                        <div class="alert alert-danger">
                          <strong class="text-uppercase">
                            <i class="fa fa-exclamation-triangle"></i> {{ trans('app.alert') }} :
                          </strong>
                          <span>{!! trans('messages.misconfigured_payment', ['payment' => $payment_provider->name]) !!}</span>
                        </div>
                      @endif

                      <span class="spacer15"></span>

                      @if ($payment_provider->admin_help_doc_link)
                        <a href="{{ $payment_provider->admin_help_doc_link }}" class="btn btn-default" target="_blank"> {{ trans('app.documentation') }}</a>
                        <span class="spacer15"></span>
                      @endif
                    </li>
                  </ul>
                @endforeach
              </div>
            </div>

            @unless ($loop->last)
              <hr>
            @endunless
          @endforeach
