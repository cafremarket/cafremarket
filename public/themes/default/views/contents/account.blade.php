<div class="sf-account-settings">
  <section class="sf-account-section">
    <div class="sf-account-section__head">
      <span class="sf-account-section__icon"><i class="fas fa-user" aria-hidden="true"></i></span>
      <div>
        <h2 class="sf-account-section__title">@lang('theme.basic_info')</h2>
        <p class="sf-account-section__hint">@lang('theme.help.required_fields')</p>
      </div>
    </div>

    <div class="sf-form-panel">
      @if (!customer_can_register())
        @include('partials.update_on_merchant_account_notice')
      @else
        <div class="row">
          <div class="col-lg-8">
            {!! Form::model($account, ['method' => 'PUT', 'route' => 'account.update', 'class' => 'sf-form', 'data-toggle' => 'validator']) !!}
            <div class="sf-form-group">
              {!! Form::label('name', trans('theme.full_name') . '*', ['class' => 'sf-form-label']) !!}
              {!! Form::text('name', null, ['id' => 'name', 'class' => 'form-control sf-input', 'placeholder' => trans('theme.placeholder.full_name'), 'required']) !!}
              <div class="help-block with-errors"></div>
            </div>

            <div class="sf-form-group">
              {!! Form::label('nice_name', trans('theme.nice_name'), ['class' => 'sf-form-label']) !!}
              {!! Form::text('nice_name', null, ['id' => 'nice_name', 'class' => 'form-control sf-input', 'placeholder' => trans('theme.placeholder.nice_name')]) !!}
            </div>

            <div class="sf-form-group">
              {!! Form::label('email', trans('theme.email') . '*', ['class' => 'sf-form-label']) !!}
              {!! Form::email('email', null, ['class' => 'form-control sf-input', 'placeholder' => trans('theme.placeholder.email'), 'required']) !!}
              <div class="help-block with-errors"></div>
            </div>

            @if (is_incevio_package_loaded('otp-login'))
              <div class="sf-form-group">
                {!! Form::label('phone', trans('packages.otp-login.phone') . '*', ['class' => 'sf-form-label']) !!}
                {!! Form::text('phone', null, ['class' => 'form-control sf-input', 'placeholder' => trans('packages.otp-login.valid_phone'), 'id' => 'phone', 'required']) !!}
              </div>
            @endif

            <div class="sf-form-group">
              {!! Form::label('dob', trans('theme.dob'), ['class' => 'sf-form-label']) !!}
              <div class="input-group sf-input-group">
                {!! Form::text('dob', null, ['class' => 'form-control sf-input datepicker', 'placeholder' => trans('theme.placeholder.dob')]) !!}
                <span class="input-group-addon"><i class="fas fa-calendar" aria-hidden="true"></i></span>
              </div>
            </div>

            <div class="sf-form-group">
              {!! Form::label('description', trans('theme.bio'), ['class' => 'sf-form-label']) !!}
              {!! Form::textarea('description', null, ['class' => 'form-control sf-input', 'rows' => '4', 'placeholder' => trans('theme.placeholder.bio')]) !!}
            </div>

            <div class="sf-form-actions">
              <small class="text-muted">* {{ trans('theme.help.required_fields') }}</small>
              {!! Form::submit(trans('theme.button.update'), ['class' => 'btn sf-btn-primary']) !!}
            </div>
            {!! Form::close() !!}
          </div>

          <div class="col-lg-4">
            <div class="sf-avatar-card">
              @if ($account->image)
                {!! Form::model($account, ['method' => 'DELETE', 'route' => 'my.avatar.remove', 'class' => 'sf-avatar-card__remove']) !!}
                <button class="btn btn-xs btn-default confirm" data-confirm="@lang('theme.confirm_action.delete')" type="submit" title="{{ trans('theme.button.delete') }}">
                  <i class="fas fa-trash" aria-hidden="true"></i>
                </button>
                {!! Form::close() !!}
              @endif

              <img class="sf-avatar-card__image lazy" src="{{ get_storage_file_url(optional($account->image)->path, 'tiny_thumb') }}" data-src="{{ get_storage_file_url(optional($account->image)->path, 'full') }}" alt="{{ trans('theme.avatar') }}" />

              {!! Form::open(['route' => 'my.avatar.save', 'files' => true, 'data-toggle' => 'validator', 'class' => 'sf-avatar-card__upload']) !!}
              <label class="sf-form-label">@lang('theme.avatar')</label>
              {!! Form::file('avatar', ['required', 'class' => 'sf-input']) !!}
              <button type="submit" class="btn btn-default btn-sm mt-2">{{ trans('theme.button.change_avatar') }}</button>
              {!! Form::close() !!}
            </div>
          </div>
        </div>
      @endif
    </div>
  </section>

  @if (is_incevio_package_loaded('buyerGroup'))
    <section class="sf-account-section">
      <div class="sf-account-section__head">
        <span class="sf-account-section__icon"><i class="fas fa-users" aria-hidden="true"></i></span>
        <div>
          <h2 class="sf-account-section__title">@lang('packages.buyer_group')</h2>
        </div>
      </div>
      <div class="sf-form-panel">
        @include('buyerGroup::frontend.buyer_group_tab')
      </div>
    </section>
  @endif
</div>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    var hash = window.location.hash.replace('#', '');
    var legacyRoutes = {
      'address-tab': @json(route('account.addresses')),
      'addresses': @json(route('account.addresses')),
      'password-tab': @json(route('account.password')),
      'password': @json(route('account.password')),
      'delete-tab': @json(route('account.delete')),
      'delete-account': @json(route('account.delete')),
      'account-info-tab': @json(route('account', 'account')),
      'profile-info': @json(route('account', 'account'))
    };

    if (hash && legacyRoutes[hash]) {
      window.location.replace(legacyRoutes[hash]);
    }
  });
</script>
