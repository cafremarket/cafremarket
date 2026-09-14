
          <div class="row">
            {!! Form::model($config, ['method' => 'PUT', 'route' => [$configUpdateRoute, $config], 'files' => true, 'id' => 'merchant-config-order', 'class' => 'form-horizontal ajax-form', 'data-toggle' => 'validator']) !!}
            <div class="col-sm-8 col-sm-offset-1">
              <div class="form-group">
                {!! Form::label('default_tax_id', trans('app.default_tax') . ':', ['class' => 'with-help col-sm-4 control-label']) !!}
                <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="left" title="{{ trans('help.default_tax_id') }}"></i>
                <div class="col-sm-7 nopadding-left">
                  @if ($can_update)
                    {!! Form::select('default_tax_id', $taxes, $config->default_tax_id, ['class' => 'form-control select2', 'placeholder' => trans('app.placeholder.select')]) !!}
                  @else
                    <span>{{ optional($config->tax)->name }}</span>
                  @endif
                </div>
              </div>

              <div class="form-group">
                {!! Form::label('order_handling_cost', trans('app.order_handling_cost') . ':', ['class' => 'with-help col-sm-4 control-label']) !!}
                <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="left" title="{{ trans('help.config_order_handling_cost') }}"></i>
                <div class="col-sm-7 nopadding-left">
                  @if ($can_update)
                    <div class="input-group">
                      @if (get_currency_prefix())
                        <span class="input-group-addon">{{ get_currency_prefix() }}</span>
                      @endif
                      {!! Form::number('order_handling_cost', get_formated_decimal($config->order_handling_cost), ['class' => 'form-control', 'placeholder' => trans('app.placeholder.order_handling_cost'), 'min' => 0]) !!}
                      @if (get_currency_suffix())
                        <span class="input-group-addon">{{ get_currency_suffix() }}</span>
                      @endif
                    </div>
                  @else
                    <span>{{ get_formated_decimal($config->order_handling_cost) }}</span>
                  @endif
                </div>
              </div>

              @include('merchant.config.partials._shipping_settings')

              @include('merchant.config.partials._toggle_row', [
                'field' => 'pickup_enabled',
                'label' => trans('theme.pickup'),
                'help' => trans('help.config_enable_pickup_order'),
                'active' => $config->isPickupEnabled(),
              ])

              @if ($can_update)
                <div class="col-md-offset-4">
                  {!! Form::submit(trans('app.update'), ['class' => 'btn btn-lg btn-flat btn-new']) !!}
                </div>
              @endif
            </div>
            {!! Form::close() !!}
          </div>
