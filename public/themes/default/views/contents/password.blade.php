<div class="sf-account-settings">
  <section class="sf-account-section">
    <div class="sf-account-section__head">
      <span class="sf-account-section__icon"><i class="fas fa-lock" aria-hidden="true"></i></span>
      <div>
        <h2 class="sf-account-section__title">@lang('theme.change_password')</h2>
      </div>
    </div>

    <div class="sf-form-panel">
      @if (!customer_can_register())
        @include('partials.update_on_merchant_account_notice')
      @else
        <div class="row">
          <div class="col-lg-8 col-lg-offset-2">
            {!! Form::model($password, ['method' => 'PUT', 'route' => 'my.password.update', 'class' => 'sf-form', 'data-toggle' => 'validator']) !!}
            @if ($password->password)
              <div class="sf-form-group">
                {!! Form::label('current_password', trans('theme.current_password') . '*', ['class' => 'sf-form-label']) !!}
                {!! Form::password('current_password', ['class' => 'form-control sf-input', 'id' => 'current_password', 'placeholder' => trans('theme.placeholder.current_password'), 'data-minlength' => '6', 'required']) !!}
              </div>
            @endif

            <div class="sf-form-group">
              {!! Form::label('password', trans('theme.new_password') . '*', ['class' => 'sf-form-label']) !!}
              {!! Form::password('password', ['class' => 'form-control sf-input', 'id' => 'password', 'placeholder' => trans('theme.placeholder.password'), 'data-minlength' => '6', 'required']) !!}
            </div>

            <div class="sf-form-group">
              {!! Form::label('password_confirmation', trans('theme.confirm_password') . '*', ['class' => 'sf-form-label']) !!}
              {!! Form::password('password_confirmation', ['class' => 'form-control sf-input', 'placeholder' => trans('theme.placeholder.confirm_password'), 'data-match' => '#password', 'required']) !!}
            </div>

            <div class="sf-form-actions">
              {!! Form::submit(trans('theme.button.update'), ['class' => 'btn sf-btn-primary']) !!}
            </div>
            {!! Form::close() !!}
          </div>
        </div>
      @endif
    </div>
  </section>
</div>
