
          <div class="row">
            {!! Form::model($config, ['method' => 'PUT', 'route' => [$configUpdateRoute, $config], 'files' => true, 'id' => 'merchant-config-support', 'class' => 'form-horizontal ajax-form', 'data-toggle' => 'validator']) !!}
            <div class="col-sm-12">
              <div class="form-group">
                {!! Form::label('support_phone', trans('app.support_phone') . ':', ['class' => 'with-help col-sm-3 control-label']) !!}
                <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="left" title="{{ trans('help.support_phone') }}"></i>
                <div class="col-sm-6 nopadding-left">
                  @if ($can_update)
                    <div class="input-group">
                      <span class="input-group-addon"><i class="fa fa-phone"></i></span>
                      {!! Form::text('support_phone', $config->support_phone, ['class' => 'form-control', 'placeholder' => trans('app.placeholder.support_phone')]) !!}
                    </div>
                  @else
                    <span>{{ $config->support_phone }}</span>
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
                  @else
                    <span>{{ $config->support_email }}</span>
                  @endif
                </div>
              </div>

              <div class="form-group">
                {!! Form::label('return_refund', '*' . trans('app.form.config_return_refund') . ':', ['class' => 'with-help col-sm-3 control-label']) !!}
                <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="left" title="{{ trans('help.config_return_refund') }}"></i>
                <div class="col-sm-6 nopadding-left">
                  @if ($can_update)
                    {!! Form::textarea('return_refund', $config->return_refund, ['class' => 'form-control summernote', 'placeholder' => trans('app.placeholder.config_return_refund'), 'required']) !!}
                  @else
                    <span>{!! $config->return_refund !!}</span>
                  @endif
                </div>
              </div>

              @if ($can_update)
                <div class="col-md-offset-3">
                  {!! Form::submit(trans('app.update'), ['class' => 'btn btn-lg btn-flat btn-new']) !!}
                </div>
              @endif
            </div>
            {!! Form::close() !!}
          </div>
