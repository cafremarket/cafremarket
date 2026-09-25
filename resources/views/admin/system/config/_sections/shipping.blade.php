
          {{-- <div class="jumbotron" style="padding: 20px; margin-bottom: 10px;">
            <p class="text-center">{{ trans('help.config_enable_shipping_method') }}</p>
          </div> --}}

          @foreach ($shipping_method_types as $type_id => $type)
            @php
              $shipping_providers = $shipping_methods->where('type', $type_id);
              $logo_path = sys_image_path('shipping-method-types') . "{$type_id}.svg";
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
                <p>{!! get_shipping_method_type($type_id)['admin_description'] !!}</p>
              </div>

              <div class="col-sm-6">
                @forelse ($shipping_providers as $shipping_provider)
                  @php
                    $logo_path = sys_image_path('shipping-methods') . "{$shipping_provider->code}.png";
                  @endphp

                  <ul class="list-group">
                    <li class="list-group-item">
                      @if (File::exists($logo_path))
                        <img src="{{ asset($logo_path) }}" class="open-img-md" alt="{{ $type }}">
                      @else
                        <p class="list-group-item-heading inline lead">
                          {{ $shipping_provider->name }}
                        </p>
                      @endif

                      <div class="handle inline pull-right no-margin">
                        <span class="spacer10"></span>
                        <a href="javascript:void(0)" data-link="{{ route('admin.setting.system.shippingMethod.toggle', $shipping_provider->id) }}" type="button" class="btn btn-md btn-secondary btn-toggle {{ $shipping_provider->enabled == 1 ? 'active' : '' }}" data-toggle="button" aria-pressed="{{ $shipping_provider->enabled == 1 ? 'true' : 'false' }}" autocomplete="off">
                          <div class="btn-handle"></div>
                        </a>
                      </div>

                      <span class="spacer10"></span>

                      <p class="list-group-item-text">
                        {!! $shipping_provider->admin_description !!}
                      </p>

                      <span class="spacer15"></span>

                      @if ($shipping_provider->admin_help_doc_link)
                        <a href="{{ $shipping_provider->admin_help_doc_link }}" class="btn btn-default" target="_blank"> {{ trans('app.documentation') }}</a>
                        <span class="spacer15"></span>
                      @endif
                    </li>
                  </ul>
                @empty
                  @if (\App\Models\ShippingMethod::TYPE_ONLINE === $type_id)
                    <div class="pull-right">
                      <p><i class="fa fa-info-circle"></i> {{ trans('help.live_shipping_rates_intro') }}</p>

                      {{-- External marketplace plugin link removed --}}
                    </div>
                  @endif
                @endforelse
              </div>
            </div>

            @unless ($loop->last)
              <hr>
            @endunless
          @endforeach
