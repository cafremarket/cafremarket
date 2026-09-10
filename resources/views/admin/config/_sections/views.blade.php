
          <div class="row">
            {!! Form::model($config, ['method' => 'PUT', 'route' => ['admin.setting.config.update', $config], 'files' => true, 'id' => 'form2', 'class' => 'form-horizontal ajax-form', 'data-toggle' => 'validator']) !!}
            <div class="col-sm-7 col-sm-offset-2">
              <fieldset>
                <legend>{{ trans('app.back_office') }}</legend>
                <div class="form-group">
                  {!! Form::label('pagination', trans('app.pagination') . ':', ['class' => 'with-help col-sm-4 control-label']) !!}
                  <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="left" title="{{ trans('help.config_pagination') }}"></i>
                  <div class="col-sm-7 nopadding-left">
                    @if ($can_update)
                      <div class="input-group">
                        {!! Form::number('pagination', get_formated_decimal($config->pagination) ?? null, ['class' => 'form-control', 'placeholder' => trans('app.placeholder.pagination')]) !!}
                        <span class="input-group-addon"><i class="fa fa-list-ul"></i></span>
                      </div>
                    @else
                      <span>{{ get_formated_decimal($config->pagination) ?? null }}</span>
                    @endif
                    <div class="help-block with-errors"></div>
                  </div>
                </div>
              </fieldset>

              <fieldset>
                <legend>{{ trans('app.store_front') }}</legend>
                <div class="row">
                  <div class="col-sm-8 text-right">
                    <div class="form-group">
                      {!! Form::label('active_ecommerce', trans('app.active_ecommerce') . ':', ['class' => 'with-help control-label']) !!}
                      <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="left" title="{{ trans('help.active_ecommerce') }}"></i>
                    </div>
                  </div>
                  <div class="col-sm-4">
                    @if ($can_update)
                      <div class="handle horizontal">
                        <a href="javascript:void(0)" data-link="{{ route('admin.setting.config.notification.toggle', 'active_ecommerce') }}" type="button" class="btn btn-md btn-secondary btn-toggle {{ $config->active_ecommerce == 1 ? 'active' : '' }}" data-toggle="button" aria-pressed="{{ $config->active_ecommerce == 1 ? 'true' : 'false' }}" autocomplete="off">
                          <div class="btn-handle"></div>
                        </a>
                      </div>
                    @else
                      <span>{{ $config->active_ecommerce == 1 ? trans('app.on') : trans('app.off') }}</span>
                    @endif
                  </div>
                </div>

                <div class="row">
                  <div class="col-sm-8 text-right">
                    <div class="form-group">
                      {!! Form::label('show_shop_desc_with_listing', trans('app.show_shop_desc_with_listing') . ':', ['class' => 'with-help control-label']) !!}
                      <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="left" title="{{ trans('help.show_shop_desc_with_listing') }}"></i>
                    </div>
                  </div>
                  <div class="col-sm-4">
                    @if ($can_update)
                      <div class="handle horizontal">
                        <a href="javascript:void(0)" data-link="{{ route('admin.setting.config.notification.toggle', 'show_shop_desc_with_listing') }}" type="button" class="btn btn-md btn-secondary btn-toggle {{ $config->show_shop_desc_with_listing == 1 ? 'active' : '' }}" data-toggle="button" aria-pressed="{{ $config->show_shop_desc_with_listing == 1 ? 'true' : 'false' }}" autocomplete="off">
                          <div class="btn-handle"></div>
                        </a>
                      </div>
                    @else
                      <span>{{ $config->show_shop_desc_with_listing == 1 ? trans('app.on') : trans('app.off') }}</span>
                    @endif
                  </div>
                </div> <!-- /.row -->

                <div class="row">
                  <div class="col-sm-8 text-right">
                    <div class="form-group">
                      {!! Form::label('show_refund_policy_with_listing', trans('app.show_refund_policy_with_listing') . ':', ['class' => 'with-help control-label']) !!}
                      <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="left" title="{{ trans('help.show_refund_policy_with_listing') }}"></i>
                    </div>
                  </div>
                  <div class="col-sm-4">
                    @if ($can_update)
                      <div class="handle horizontal">
                        <a href="javascript:void(0)" data-link="{{ route('admin.setting.config.notification.toggle', 'show_refund_policy_with_listing') }}" type="button" class="btn btn-md btn-secondary btn-toggle {{ $config->show_refund_policy_with_listing == 1 ? 'active' : '' }}" data-toggle="button" aria-pressed="{{ $config->show_refund_policy_with_listing == 1 ? 'true' : 'false' }}" autocomplete="off">
                          <div class="btn-handle"></div>
                        </a>
                      </div>
                    @else
                      <span>{{ $config->show_refund_policy_with_listing == 1 ? trans('app.on') : trans('app.off') }}</span>
                    @endif
                  </div>
                </div> <!-- /.row -->
              </fieldset>
            </div>

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
