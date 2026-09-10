
          <div class="row">
            {!! Form::model($config, ['method' => 'PUT', 'route' => ['admin.setting.config.update', $config], 'files' => true, 'id' => 'form2', 'class' => 'form-horizontal ajax-form', 'data-toggle' => 'validator']) !!}
            <div class="col-sm-8 col-sm-offset-1">
              <div class="form-group">
                {!! Form::label('alert_quantity', trans('app.alert_quantity') . ':', ['class' => 'with-help col-sm-4 control-label']) !!}
                <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="left" title="{{ trans('help.config_alert_quantity') }}"></i>
                <div class="col-sm-7 nopadding-left">
                  @if ($can_update)
                    {!! Form::number('alert_quantity', get_formated_decimal($config->alert_quantity), ['class' => 'form-control', 'placeholder' => trans('app.placeholder.alert_quantity'), 'min' => 0]) !!}
                  @else
                    <span>{{ get_formated_decimal($config->alert_quantity) }}</span>
                  @endif
                  <div class="help-block with-errors"></div>
                </div>
              </div>

              <div class="form-group">
                {!! Form::label('default_supplier_id', trans('app.default_supplier') . ':', ['class' => 'with-help col-sm-4 control-label']) !!}
                <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="left" title="{{ trans('help.default_supplier') }}"></i>
                <div class="col-sm-7 nopadding-left">
                  @if ($can_update)
                    {!! Form::select('default_supplier_id', $suppliers, $config->default_supplier_id, ['class' => 'form-control select2', 'placeholder' => trans('app.placeholder.select')]) !!}
                  @else
                    <span>{{ optional($config->supplier)->name }}</span>
                  @endif
                  <div class="help-block with-errors"></div>
                </div>
              </div>

              <div class="form-group">
                {!! Form::label('default_warehouse_id', trans('app.default_warehouse') . ':', ['class' => 'with-help col-sm-4 control-label']) !!}
                <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="left" title="{{ trans('help.default_warehouse') }}"></i>
                <div class="col-sm-7 nopadding-left">
                  @if ($can_update)
                    {!! Form::select('default_warehouse_id', $warehouses, $config->default_warehouse_id, ['class' => 'form-control select2', 'placeholder' => trans('app.placeholder.select')]) !!}
                  @else
                    <span>{{ optional($config->warehouse)->name }}</span>
                  @endif
                  <div class="help-block with-errors"></div>
                </div>
              </div>

              @if (is_incevio_package_loaded('packaging'))
                <div class="form-group">
                  {!! Form::label('default_packaging_ids', trans('app.default_packagings') . ':', ['class' => 'with-help col-sm-4 control-label']) !!}
                  <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="left" title="{{ trans('help.default_packaging_ids_for_inventory') }}"></i>
                  <div class="col-sm-7 nopadding-left">
                    @if ($can_update)
                      {!! Form::select('default_packaging_ids[]', $packagings, $config->default_packaging_ids, ['class' => 'form-control select2-normal', 'multiple' => 'multiple']) !!}
                    @else
                      @foreach ($config->default_packaging_ids as $packaging)
                        <span class="label label-outline">{{ get_value_from($packaging, 'packagings', 'name') }}</span>
                      @endforeach
                    @endif
                    <div class="help-block with-errors"></div>
                  </div>
                </div> <!-- /.form-group -->
              @endif

              @if (is_incevio_package_loaded('affiliate'))
                @include('affiliate::admin._shop_config_default_commission_field')
              @endif

              @if ($can_update)
                <div class="col-md-offset-4">
                  {!! Form::submit(trans('app.update'), ['class' => 'btn btn-lg btn-flat btn-new']) !!}
                </div>
              @endif
            </div>
            {!! Form::close() !!}
          </div>
