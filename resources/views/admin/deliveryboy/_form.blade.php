<div class="row">
  <div class="col-md-6 nopadding-right">
    <div class="form-group">
      {!! Form::label('first_name', trans('app.first_name') . '*') !!}
      {!! Form::text('first_name', null, ['class' => 'form-control', 'placeholder' => trans('app.first_name'), 'required']) !!}
      <input type="hidden" name="shop_id" value="{{ auth()->user()->merchantId() }}">
      <div class="help-block with-errors"></div>
    </div>
  </div>
  <div class="col-md-6 nopadding-left">
    <div class="form-group">
      {!! Form::label('last_name', trans('app.last_name') . '*') !!}
      {!! Form::text('last_name', null, ['class' => 'form-control', 'placeholder' => trans('app.last_name'), 'required']) !!}
      <div class="help-block with-errors"></div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-md-6 nopadding-right">
    <div class="form-group">
      {!! Form::label('nice_name', trans('app.form.nice_name')) !!}
      {!! Form::text('nice_name', null, ['class' => 'form-control', 'placeholder' => trans('app.placeholder.nice_name')]) !!}
      <div class="help-block with-errors"></div>
    </div>
  </div>

  <div class="col-md-6 nopadding-left">
    <div class="form-group">
      {!! Form::label('active', trans('app.form.status') . '*') !!}
      {!! Form::select('status', ['1' => trans('app.active'), '0' => trans('app.inactive')], null, ['class' => 'form-control select2-normal', 'placeholder' => trans('app.placeholder.status'), 'required']) !!}
      <div class="help-block with-errors"></div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-md-6 nopadding-right">
    <div class="form-group">
      {!! Form::label('email', trans('app.form.email_address') . '*') !!}
      {!! Form::email('email', null, ['class' => 'form-control', 'id' => 'deliveryboy-email', 'placeholder' => trans('app.placeholder.valid_email'), 'required']) !!}
      <div class="help-block with-errors"></div>
    </div>
  </div>
  <div class="col-md-6 nopadding-left">
    <div class="form-group">
      {!! Form::label('phone_number', 'Phone Number' . '*') !!}
      {!! Form::text('phone_number', null, ['class' => 'form-control', 'placeholder' => trans('app.phone'), 'required']) !!}
      <div class="help-block with-errors"></div>
    </div>
  </div>
</div>

@if (!isset($deliveryboy))
  <div class="alert alert-info" id="deliveryboy-email-exists-msg" style="display: none;">
    {{ trans('app.delivery_boy_email_exists') }}
  </div>

  <div class="form-group" id="deliveryboy-password-fields">
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

  <script>
    (function() {
      var emailInput = document.getElementById('deliveryboy-email');
      var passwordFields = document.getElementById('deliveryboy-password-fields');
      var existsMsg = document.getElementById('deliveryboy-email-exists-msg');
      if (!emailInput || !passwordFields || !existsMsg) {
        return;
      }

      var passwordInput = passwordFields.querySelector('#password');
      var confirmInput = passwordFields.querySelector('input[name="password_confirmation"]');
      var checkUrl = "{{ route('admin.admin.deliveryboy.checkEmail') }}";
      if (typeof window.toPanelUrl === 'function') {
        checkUrl = window.toPanelUrl(checkUrl);
      }

      function setPasswordRequired(required) {
        if (required) {
          passwordFields.style.display = '';
          existsMsg.style.display = 'none';
          passwordInput.setAttribute('required', 'required');
          confirmInput.setAttribute('required', 'required');
        } else {
          passwordFields.style.display = 'none';
          existsMsg.style.display = '';
          passwordInput.removeAttribute('required');
          confirmInput.removeAttribute('required');
          passwordInput.value = '';
          confirmInput.value = '';
        }
      }

      emailInput.addEventListener('blur', function() {
        var email = emailInput.value.trim();
        if (!email) {
          setPasswordRequired(true);
          return;
        }

        if (typeof jQuery === 'undefined') {
          return;
        }

        jQuery.get(checkUrl, {email: email}, function(response) {
          setPasswordRequired(! (response && response.exists));
        });
      });
    })();
  </script>
@endif

<div class="row">
  <div class="col-md-6 nopadding-right">
    <div class="form-group">
      {!! Form::label('sex', trans('app.form.sex')) !!}
      {!! Form::select('sex', get_gerder_list(), null, ['class' => 'form-control select2-normal', 'placeholder' => trans('app.placeholder.sex')]) !!}
    </div>
  </div>
  <div class="col-md-6 nopadding-left">
    <div class="form-group">
      {!! Form::label('dob', trans('app.form.dob')) !!}
      <div class="input-group">
        <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
        {!! Form::text('dob', null, ['class' => 'form-control datepicker', 'placeholder' => trans('app.placeholder.dob')]) !!}
      </div>
    </div>
  </div>
</div>

<div class="form-group">
  @if (isset($deliveryboy) && $deliveryboy->avatarImage)
    <label>
      <img src="{{ get_avatar_src($deliveryboy, 'small') }}" width="" alt="{{ trans('app.avatar') }}">
      <span style="margin-left: 10px;">
        {!! Form::checkbox('delete_image[avatar]', 1, null, ['class' => 'icheck']) !!} {{ trans('app.form.delete_avatar') }}
      </span>
    </label>
  @endif

  <div class="row">
    <div class="col-md-9 nopadding-right">
      <input id="uploadFile" placeholder="{{ trans('app.placeholder.avatar') }}" class="form-control" disabled="disabled" style="height: 28px;" />
    </div>
    <div class="col-md-3 nopadding-left">
      <div class="fileUpload btn btn-primary btn-block btn-flat">
        <span>{{ trans('app.form.upload') }}</span>
        <input type="file" name="images[avatar]" id="uploadBtn" class="upload" />
      </div>
    </div>
  </div>
</div>

<p class="help-block">* {{ trans('app.form.required_fields') }}</p>
