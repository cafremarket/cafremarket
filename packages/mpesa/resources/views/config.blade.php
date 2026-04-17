<div class="modal-dialog modal-sm">
  <div class="modal-content">
    {!! Form::model($config, ['method' => 'PUT', 'route' => ['admin.setting.mpesa.update', $config], 'id' => 'form', 'data-toggle' => 'validator']) !!}
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
      {{ trans('app.form.config') . ' M-Pesa Mozambique' }}
    </div>
    <div class="modal-body">
      <div class="form-group">
        {!! Form::label('sandbox', trans('app.form.environment') . '*', ['class' => 'with-help']) !!}
        <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="top" title="{{ trans('help.config_payment_environment') }}"></i>
        {!! Form::select('sandbox', ['1' => trans('app.test'), '0' => trans('app.live')], null, ['class' => 'form-control select2-normal', 'required']) !!}
        <div class="help-block with-errors"></div>
      </div>

      <div class="form-group">
        {!! Form::label('consumer_key', trans('mpesa::lang.api_key') . '*', ['class' => 'with-help']) !!}
        {!! Form::text('consumer_key', null, ['class' => 'form-control', 'placeholder' => trans('mpesa::lang.api_key'), 'required']) !!}
        <small class="help-block">{{ trans('mpesa::lang.api_key_help') }}</small>
        <div class="help-block with-errors"></div>
      </div>

      <div class="form-group">
        {!! Form::label('consumer_secret', trans('mpesa::lang.public_key') . '*', ['class' => 'with-help']) !!}
        {!! Form::textarea('consumer_secret', null, ['class' => 'form-control', 'placeholder' => trans('mpesa::lang.public_key'), 'rows' => 4, 'required']) !!}
        <small class="help-block">{{ trans('mpesa::lang.public_key_help') }}</small>
        <div class="help-block with-errors"></div>
      </div>

      <div class="form-group">
        {!! Form::label('short_code', trans('mpesa::lang.service_provider_code') . '*', ['class' => 'with-help']) !!}
        {!! Form::text('short_code', null, ['class' => 'form-control', 'placeholder' => trans('mpesa::lang.service_provider_code'), 'required']) !!}
        <small class="help-block">{{ trans('mpesa::lang.service_provider_code_help') }}</small>
        <div class="help-block with-errors"></div>
      </div>

      {{-- Keep for DB backward compat, not shown for Mozambique --}}
      {!! Form::hidden('lipa_na_mpesa', $config->short_code ?? '') !!}
      {!! Form::hidden('mpesa_passkey', '') !!}
    </div>
    <div class="modal-footer">
      {!! Form::submit(trans('app.update'), ['class' => 'btn btn-flat btn-new']) !!}
    </div>
    {!! Form::close() !!}
  </div>
</div>
