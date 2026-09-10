
          {{-- Live chat is always enabled; shop on/off toggle removed. --}}

          <div class="row">
            {!! Form::model($config, ['method' => 'PUT', 'route' => ['admin.setting.config.update', $config], 'files' => true, 'id' => 'form2', 'class' => 'form-horizontal ajax-form', 'data-toggle' => 'validator']) !!}
            <div class="col-sm-12">
              <div class="form-group">
                {!! Form::label('support_agent', trans('app.support_agent') . ':', ['class' => 'with-help col-sm-3 control-label']) !!}
                <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="left" title="{{ trans('help.support_agent') }}"></i>
                <div class="col-sm-6 nopadding-left">
                  @if ($can_update)
                    <div class="input-group">
                      <span class="input-group-addon"><i class="fa fa-user"></i></span>
                      {!! Form::select('support_agent', $staffs, $config->support_agent, ['class' => 'form-control select2', 'placeholder' => trans('app.placeholder.select')]) !!}
                    </div>
                    <div class="help-block with-errors"></div>
                  @else
                    <span>{{ $config->supportAgent->getName() }}</span>
                  @endif
                </div>
              </div>

              <div class="form-group">
                {!! Form::label('support_phone', trans('app.support_phone') . ':', ['class' => 'with-help col-sm-3 control-label']) !!}
                <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="left" title="{{ trans('help.support_phone') }}"></i>
                <div class="col-sm-6 nopadding-left">
                  @if ($can_update)
                    <div class="input-group">
                      <span class="input-group-addon"><i class="fa fa-phone"></i></span>
                      {!! Form::text('support_phone', $config->support_phone, ['class' => 'form-control', 'placeholder' => trans('app.placeholder.support_phone')]) !!}
                    </div>
                    <div class="help-block with-errors"></div>
                  @else
                    <span>{{ $config->support_phone }}</span>
                  @endif
                </div>
              </div>

              <div class="form-group">
                {!! Form::label('support_phone_toll_free', trans('app.support_phone_toll_free') . ':', ['class' => 'with-help col-sm-3 control-label']) !!}
                <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="left" title="{{ trans('help.support_phone_toll_free') }}"></i>
                <div class="col-sm-6 nopadding-left">
                  @if ($can_update)
                    <div class="input-group">
                      <span class="input-group-addon"><i class="fa fa-phone-square"></i></span>
                      {!! Form::text('support_phone_toll_free', $config->support_phone_toll_free, ['class' => 'form-control', 'placeholder' => trans('app.placeholder.support_phone_toll_free')]) !!}
                    </div>
                    <div class="help-block with-errors"></div>
                  @else
                    <span>{{ $config->support_phone_toll_free }}</span>
                  @endif
                </div>
              </div>

              <div class="form-group">
                {!! Form::label('support_email', '*' . trans('app.support_email') . ':', ['class' => 'with-help col-sm-3 control-label']) !!}
                <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="left" title="{{ trans('help.support_email') }}"></i>
                <div class="col-sm-6 nopadding-left">
                  @if ($can_update)
                    <div class="input-group">
                      <span class="input-group-addon"><i class="fa fa-envelope-o"></i></span>
                      {!! Form::email('support_email', $config->support_email, ['class' => 'form-control', 'placeholder' => trans('app.placeholder.support_email'), 'required']) !!}
                    </div>
                    <div class="help-block with-errors"></div>
                  @else
                    <span>{{ $config->support_email }}</span>
                  @endif
                </div>
              </div>

              <div class="form-group">
                {!! Form::label('default_sender_email_address', '*' . trans('app.default_sender_email_address') . ':', ['class' => 'with-help col-sm-3 control-label']) !!}
                <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="left" title="{{ trans('help.default_sender_email_address') }}"></i>
                <div class="col-sm-6 nopadding-left">
                  @if ($can_update)
                    <div class="input-group">
                      <span class="input-group-addon"><i class="fa fa-at"></i></span>
                      {!! Form::email('default_sender_email_address', $config->default_sender_email_address, ['class' => 'form-control', 'placeholder' => trans('app.placeholder.default_sender_email_address'), 'required']) !!}
                    </div>
                    <div class="help-block with-errors"></div>
                  @else
                    <span>{{ $config->default_sender_email_address }}</span>
                  @endif
                </div>
              </div>

              <div class="form-group">
                {!! Form::label('default_email_sender_name', '*' . trans('app.default_email_sender_name') . ':', ['class' => 'with-help col-sm-3 control-label']) !!}
                <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="left" title="{{ trans('help.default_email_sender_name') }}"></i>
                <div class="col-sm-6 nopadding-left">
                  @if ($can_update)
                    <div class="input-group">
                      <span class="input-group-addon"><i class="fa fa-user"></i></span>
                      {!! Form::text('default_email_sender_name', $config->default_email_sender_name, ['class' => 'form-control', 'placeholder' => trans('app.placeholder.default_email_sender_name'), 'required']) !!}
                    </div>
                    <div class="help-block with-errors"></div>
                  @else
                    <span>{{ $config->default_email_sender_name }}</span>
                  @endif
                </div>
              </div>

              <div class="form-group">
                {!! Form::label('return_refund', '*' . trans('app.form.config_return_refund') . ':', ['class' => 'with-help col-sm-3 control-label']) !!}
                <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="left" title="{{ trans('help.config_return_refund') }}"></i>
                <div class="col-sm-6 nopadding-left">
                  @if ($can_update)
                    {!! Form::textarea('return_refund', $config->return_refund, ['class' => 'form-control summernote', 'placeholder' => trans('app.placeholder.config_return_refund'), 'required']) !!}
                    <div class="help-block with-errors"></div>
                  @else
                    <span>{{ $config->return_refund }}</span>
                  @endif
                </div>
              </div>

              <p class="help-block">* {{ trans('app.form.required_fields') }}</p>

              @if ($can_update)
                <div class="col-md-offset-3">
                  {!! Form::submit(trans('app.update'), ['class' => 'btn btn-lg btn-flat btn-new']) !!}
                </div>
              @endif
            </div>
            {!! Form::close() !!}
          </div>
