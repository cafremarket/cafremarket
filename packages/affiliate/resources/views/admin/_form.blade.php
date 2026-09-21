<div class="form-group">
  {!! Form::label('name', trans('app.name') . '*') !!}
  {!! Form::text('name', null, ['class' => 'form-control', 'placeholder' => trans('theme.placeholder.full_name'), 'required']) !!}
  <div class="help-block with-errors"></div>
</div>

<div class="form-group">
  {!! Form::label('email', trans('app.email') . '*') !!}
  {!! Form::email('email', null, ['class' => 'form-control', 'placeholder' => trans('theme.placeholder.valid_email'), 'required']) !!}
  <div class="help-block with-errors"></div>
</div>

<div class="form-group">
  {!! Form::label('phone', trans('app.phone')) !!}
  {!! Form::text('phone', null, ['class' => 'form-control', 'placeholder' => trans('app.placeholder.phone_number')]) !!}
  <div class="help-block with-errors"></div>
</div>

@if (! isset($affiliate))
  <div class="form-group">
    {!! Form::label('password', trans('app.form.password') . '*') !!}
    <div class="row">
      <div class="col-md-6 nopadding-right">
        {!! Form::password('password', ['class' => 'form-control', 'id' => 'password', 'placeholder' => trans('app.placeholder.password'), 'data-minlength' => '6', 'required']) !!}
        <div class="help-block with-errors"></div>
      </div>

      <div class="col-md-6 nopadding-left">
        {!! Form::password('password_confirmation', ['class' => 'form-control', 'placeholder' => trans('app.placeholder.confirm_password'), 'data-match' => '#password', 'required']) !!}
        <div class="help-block with-errors"></div>
      </div>
    </div>
  </div>
@endif

<div class="form-group">
  {!! Form::hidden('active', 0) !!}
  <label>
    {!! Form::checkbox('active', 1, isset($affiliate) ? (bool) $affiliate->active : true, ['class' => 'icheck']) !!}
    {{ trans('app.active') }}
  </label>
</div>
