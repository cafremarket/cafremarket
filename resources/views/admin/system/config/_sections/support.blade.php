{!! Form::model($system, ['method' => 'PUT', 'route' => ['admin.setting.system.update'], 'files' => true, 'id' => 'form-system-support', 'class' => 'form-horizontal ajax-form', 'data-toggle' => 'validator']) !!}
<div class="row">
  <div class="col-sm-10 col-sm-offset-1">
    <div class="form-group">
      {!! Form::label('support_phone', trans('app.support_phone') . ':', ['class' => 'with-help col-sm-3 control-label']) !!}
      <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="left" title="{{ trans('help.support_phone') }}"></i>
      <div class="col-sm-7 nopadding-left">
        @if ($can_update)
          <div class="input-group">
            <span class="input-group-addon"><i class="fa fa-phone"></i></span>
            {!! Form::text('support_phone', $system->support_phone, ['class' => 'form-control', 'placeholder' => trans('app.placeholder.support_phone')]) !!}
          </div>
        @else
          <span>{{ $system->support_phone }}</span>
        @endif
      </div>
    </div>

    <div class="form-group">
      {!! Form::label('support_email', '*' . trans('app.support_email') . ':', ['class' => 'with-help col-sm-3 control-label']) !!}
      <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="left" title="{{ trans('help.support_email') }}"></i>
      <div class="col-sm-7 nopadding-left">
        @if ($can_update)
          <div class="input-group">
            <span class="input-group-addon"><i class="fa fa-envelope-o"></i></span>
            {!! Form::email('support_email', $system->support_email, ['class' => 'form-control', 'placeholder' => trans('app.placeholder.support_email'), 'required']) !!}
          </div>
          <div class="help-block with-errors"></div>
        @else
          <span>{{ $system->support_email }}</span>
        @endif
      </div>
    </div>

    <div class="form-group">
      {!! Form::label('default_sender_email_address', '*' . trans('app.default_sender_email_address') . ':', ['class' => 'with-help col-sm-3 control-label']) !!}
      <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="left" title="{{ trans('help.default_sender_email_address') }}"></i>
      <div class="col-sm-7 nopadding-left">
        @if ($can_update)
          <div class="input-group">
            <span class="input-group-addon"><i class="fa fa-at"></i></span>
            {!! Form::email('default_sender_email_address', $system->default_sender_email_address, ['class' => 'form-control', 'placeholder' => trans('app.placeholder.default_sender_email_address'), 'required']) !!}
          </div>
          <div class="help-block with-errors"></div>
        @else
          <span>{{ $system->default_sender_email_address }}</span>
        @endif
      </div>
    </div>

    <div class="form-group">
      {!! Form::label('default_email_sender_name', '*' . trans('app.default_email_sender_name') . ':', ['class' => 'with-help col-sm-3 control-label']) !!}
      <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="left" title="{{ trans('help.default_email_sender_name') }}"></i>
      <div class="col-sm-7 nopadding-left">
        @if ($can_update)
          <div class="input-group">
            <span class="input-group-addon"><i class="fa fa-user"></i></span>
            {!! Form::text('default_email_sender_name', $system->default_email_sender_name, ['class' => 'form-control', 'placeholder' => trans('app.placeholder.default_email_sender_name'), 'required']) !!}
          </div>
          <div class="help-block with-errors"></div>
        @else
          <span>{{ $system->default_email_sender_name }}</span>
        @endif
      </div>
    </div>

    @if ($can_update)
      <div class="col-md-offset-3">
        {!! Form::submit(trans('app.update'), ['class' => 'btn btn-lg btn-flat btn-new']) !!}
      </div>
    @endif
  </div>
</div>
{!! Form::close() !!}
