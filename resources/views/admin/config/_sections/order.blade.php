
          <div class="row">
            {!! Form::model($config, ['method' => 'PUT', 'route' => ['admin.setting.config.update', $config], 'files' => true, 'id' => 'form2', 'class' => 'form-horizontal ajax-form', 'data-toggle' => 'validator']) !!}
            <div class="col-sm-8 col-sm-offset-1">
              <div class="form-group">
                {!! Form::label('order_number_prefix', trans('app.order_number_prefix') . ':', ['class' => 'with-help col-sm-4 control-label']) !!}
                <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="left" title="{{ trans('help.order_number_prefix_suffix') }}"></i>
                <div class="col-sm-2 nopadding-left">
                  @if ($can_update)
                    {!! Form::text('order_number_prefix', $config->order_number_prefix, ['class' => 'form-control', 'placeholder' => trans('app.placeholder.order_number_prefix')]) !!}
                  @else
                    <span>{{ $config->order_number_prefix }}</span>
                  @endif
                  <div class="help-block with-errors"></div>
                </div>

                {!! Form::label('order_number_suffix', trans('app.and') . ' ' . trans('app.suffix') . ':', ['class' => 'with-help col-sm-3 control-label']) !!}
                <div class="col-sm-2 nopadding-left">
                  @if ($can_update)
                    {!! Form::text('order_number_suffix', $config->order_number_suffix, ['class' => 'form-control', 'placeholder' => trans('app.placeholder.order_number_suffix')]) !!}
                  @else
                    <span>{{ $config->order_number_suffix }}</span>
                  @endif
                  <div class="help-block with-errors"></div>
                </div>
              </div> <!-- /.form-group -->

              @if (vendor_get_paid_directly())
                <div class="form-group">
                  {!! Form::label('default_payment_method_id', trans('app.default_payment_method') . ':', ['class' => 'with-help col-sm-4 control-label']) !!}
                  <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="left" title="{{ trans('help.default_payment_method_id') }}"></i>
                  <div class="col-sm-7 nopadding-left">
                    @if ($can_update)
                      {!! Form::select('default_payment_method_id', $payment_methods, $config->default_payment_method_id, ['class' => 'form-control select2-normal']) !!}
                    @else
                      <span>{{ optional($config->payment_method)->name }}</span>
                    @endif
                  </div>
                </div> <!-- /.form-group -->
              @endif

              <div class="form-group">
                {!! Form::label('order_invoice_pdf_template', trans('app.order_invoice_pdf_template') . ':', ['class' => 'with-help col-sm-4 control-label']) !!}
                <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="left" title="{{ trans('help.order_invoice_pdf_template') }}"></i>
                <div class="col-sm-7 nopadding-left">
                  @if ($can_update)
                    {!! Form::select('order_invoice_pdf_template', $order_invoice_pdf_templates, $config->order_invoice_pdf_template, ['class' => 'form-control select2-nullable']) !!}
                  @else
                    <span>{{ optional($config->order_invoice_pdf_template)->name }}</span>
                  @endif
                </div>
              </div> <!-- /.form-group -->

              <div class="form-group">
                {!! Form::label('shipping_label_pdf_template', trans('app.shipping_label_pdf_template') . ':', ['class' => 'with-help col-sm-4 control-label']) !!}
                <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="left" title="{{ trans('help.shipping_label_pdf_template') }}"></i>
                <div class="col-sm-7 nopadding-left">
                  @if ($can_update)
                    {!! Form::select('shipping_label_pdf_template', $shipping_label_pdf_templates, $config->shipping_label_pdf_template, ['class' => 'form-control select2-nullable']) !!}
                  @else
                    <span>{{ optional($config->shipping_label_pdf_template)->name }}</span>
                  @endif
                </div>
              </div> <!-- /.form-group -->

              <div class="form-group">
                {!! Form::label('default_tax_id', trans('app.default_tax') . ':', ['class' => 'with-help col-sm-4 control-label']) !!}
                <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="left" title="{{ trans('help.default_tax_id') }}"></i>
                <div class="col-sm-7 nopadding-left">
                  @if ($can_update)
                    {!! Form::select('default_tax_id', $taxes, $config->default_tax_id, ['class' => 'form-control select2', 'placeholder' => trans('app.placeholder.select')]) !!}
                  @else
                    <span>{{ $config->tax->name }}</span>
                  @endif
                </div>
              </div> <!-- /.form-group -->

              <div class="form-group">
                {!! Form::label('order_handling_cost', trans('app.order_handling_cost') . ':', ['class' => 'with-help col-sm-4 control-label']) !!}
                <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="left" title="{{ trans('help.config_order_handling_cost') }}"></i>
                <div class="col-sm-7 nopadding-left">
                  @if ($can_update)
                    <div class="input-group">
                      @if (get_currency_prefix())
                        <span class="input-group-addon" id="basic-addon1">
                          {{ get_currency_prefix() }}
                        </span>
                      @endif

                      {!! Form::number('order_handling_cost', get_formated_decimal($config->order_handling_cost), ['class' => 'form-control', 'placeholder' => trans('app.placeholder.order_handling_cost'), 'min' => 0]) !!}

                      @if (get_currency_suffix())
                        <span class="input-group-addon" id="basic-addon1">
                          {{ get_currency_suffix() }}
                        </span>
                      @endif
                    </div>
                  @else
                    <span>{{ get_formated_decimal($config->order_handling_cost) }}</span>
                  @endif
                  <div class="help-block with-errors"></div>
                </div>
              </div> <!-- /.form-group -->

              @include('merchant.config.partials._shipping_settings')

              <div class="row">
                <div class="col-sm-4 text-right">
                  <div class="form-group">
                    {!! Form::label('auto_archive_order', trans('app.auto_archive_order') . ':', ['class' => 'with-help control-label']) !!}
                    <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="left" title="{{ trans('help.config_auto_archive_order') }}"></i>
                  </div>
                </div>

                <div class="col-sm-7">
                  @if ($can_update)
                    <div class="handle horizontal">
                      <a href="javascript:void(0)" data-link="{{ route('admin.setting.config.notification.toggle', 'auto_archive_order') }}" type="button" class="btn btn-md btn-secondary btn-toggle {{ $config->auto_archive_order == 1 ? 'active' : '' }}" data-toggle="button" aria-pressed="{{ $config->auto_archive_order == 1 ? 'true' : 'false' }}" autocomplete="off">
                        <div class="btn-handle"></div>
                      </a>
                    </div>
                  @else
                    <span>{{ $config->auto_archive_order == 1 ? trans('app.on') : trans('app.off') }}</span>
                  @endif
                </div>
              </div> <!-- /.row -->

              {{-- Checkout Config --}}
              <div class="row">
                <div class="col-sm-4 text-right">
                  <div class="form-group">
                    {!! Form::label('pay_online', trans('app.pay_online') . ':', ['class' => 'with-help control-label']) !!}
                    <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="left" title="{{ trans('help.pay_online') }}"></i>
                  </div>
                </div>
                <div class="col-sm-7">
                  @if ($can_update)
                    <div class="handle horizontal">
                      <a href="javascript:void(0)" data-link="{{ route('admin.setting.config.notification.toggle', 'pay_online') }}" type="button" class="btn btn-md btn-secondary btn-toggle {{ $config->pay_online == 1 ? 'active' : '' }}" data-toggle="button" aria-pressed="{{ $config->pay_online == 1 ? 'true' : 'false' }}" autocomplete="off">
                        <div class="btn-handle"></div>
                      </a>
                    </div>
                  @else
                    <span>{{ $config->pay_online == 1 ? trans('app.on') : trans('app.off') }}</span>
                  @endif
                </div>
              </div> <!-- /.row -->

              {{-- Pickup enable/disable config removed (pickup disabled system-wide) --}}
            </div> <!-- /.col-sm-* -->

            @if ($can_update)
              <div class="row my-5">
                <div class="col-sm-4 text-right">
                  <p class="help-block">* {{ trans('app.form.required_fields') }}</p>
                </div>
                <div class="col-md-5">
                  {!! Form::submit(trans('app.update'), ['class' => 'btn btn-lg btn-flat btn-new pull-right']) !!}
                </div>
                <div class="col-sm-3">
                </div>
              </div> <!-- /.row -->
            @endif
            {!! Form::close() !!}
          </div>
