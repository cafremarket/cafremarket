<div class="sf-account-settings">
  <ul class="sf-account-tabs" role="tablist">
    <li role="presentation" class="active">
      <a href="#account-info-tab" role="tab" data-toggle="tab" aria-expanded="true">
        <i class="fas fa-user" aria-hidden="true"></i>
        <span>@lang('theme.basic_info')</span>
      </a>
    </li>

    @if (is_incevio_package_loaded('buyerGroup'))
      <li role="presentation">
        <a href="#buyer-group-tab" role="tab" data-toggle="tab" aria-expanded="false">
          <i class="fas fa-users" aria-hidden="true"></i>
          <span>@lang('packages.buyer_group')</span>
        </a>
      </li>
    @endif

    <li role="presentation">
      <a href="#password-tab" role="tab" data-toggle="tab" aria-expanded="false">
        <i class="fas fa-lock" aria-hidden="true"></i>
        <span>@lang('theme.change_password')</span>
      </a>
    </li>

    <li role="presentation">
      <a href="#address-tab" role="tab" data-toggle="tab" aria-expanded="false">
        <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
        <span>@lang('theme.addresses')</span>
      </a>
    </li>

    <li role="presentation">
      <a href="#delete-tab" role="tab" data-toggle="tab" aria-expanded="false">
        <i class="fas fa-trash-alt" aria-hidden="true"></i>
        <span>@lang('theme.button.delete')</span>
      </a>
    </li>
  </ul>

  <div class="tab-content sf-account-tab-panels">
    <div role="tabpanel" class="tab-pane fade active in" id="account-info-tab">
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
    </div>

    @if (is_incevio_package_loaded('buyerGroup'))
      <div role="tabpanel" class="tab-pane fade" id="buyer-group-tab">
        <div class="sf-form-panel">
          @include('buyerGroup::frontend.buyer_group_tab')
        </div>
      </div>
    @endif

    <div role="tabpanel" class="tab-pane fade" id="password-tab">
      <div class="sf-form-panel">
        @if (!customer_can_register())
          @include('partials.update_on_merchant_account_notice')
        @else
          <div class="row">
            <div class="col-lg-8 col-lg-offset-2">
              {!! Form::model($account, ['method' => 'PUT', 'route' => 'my.password.update', 'class' => 'sf-form', 'data-toggle' => 'validator']) !!}
              @if ($account->password)
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
    </div>

    <div role="tabpanel" class="tab-pane fade" id="address-tab">
      <div class="sf-address-toolbar">
        <div>
          <h3 class="sf-address-toolbar__title">@lang('theme.addresses')</h3>
          <p class="sf-address-toolbar__hint">@lang('theme.set_delivery_location')</p>
        </div>
        <a href="{{ route('my.address.create') }}" class="modalAction btn sf-btn-primary">
          <i class="fas fa-plus" aria-hidden="true"></i> @lang('theme.button.add_new_address')
        </a>
      </div>

      <div class="sf-address-grid">
        @forelse($account->addresses as $address)
          <article class="sf-address-card">
            <div class="sf-address-card__head">
              <span class="sf-address-card__type">{{ $address->address_type }}</span>
              <div class="sf-address-card__actions">
                <a href="{{ route('my.address.edit', $address) }}" class="modalAction btn btn-default btn-xs" title="@lang('theme.edit')">
                  <i class="fas fa-edit" aria-hidden="true"></i>
                </a>
                <a href="{{ route('my.address.delete', $address->id) }}" class="confirm btn btn-default btn-xs" data-confirm="@lang('theme.confirm_action.delete')" title="@lang('theme.button.delete')">
                  <i class="fas fa-trash" aria-hidden="true"></i>
                </a>
              </div>
            </div>
            <div class="sf-address-card__body">
              {!! $address->toHtml() !!}
            </div>
          </article>
        @empty
          <div class="sf-empty-state">
            <i class="fas fa-map-marked-alt" aria-hidden="true"></i>
            <p>@lang('theme.nothing_found')</p>
            <a href="{{ route('my.address.create') }}" class="modalAction btn sf-btn-primary">
              @lang('theme.button.add_new_address')
            </a>
          </div>
        @endforelse
      </div>
    </div>

    <div role="tabpanel" class="tab-pane fade" id="delete-tab">
      <div class="sf-form-panel sf-form-panel--danger">
        <div class="sf-alert sf-alert--danger">
          <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
          <div>
            <strong>{{ trans('app.notice') }}</strong>
            {{ trans('messages.account_delete') }}
          </div>
        </div>

        {!! Form::model($account, ['method' => 'DELETE', 'route' => 'my.account.remove', 'class' => 'text-center', 'data-toggle' => 'validator']) !!}
        <button class="btn btn-danger px-5 py-2 confirm" data-confirm="{{ trans('theme.confirm_action.delete') }}" type="submit">
          <i class="fas fa-trash mr-2" aria-hidden="true"></i>
          {{ trans('theme.button.delete') }}
        </button>
        {!! Form::close() !!}
      </div>
    </div>
  </div>
</div>

@if (request()->has('address'))
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      var tab = document.querySelector('a[href="#address-tab"]');
      if (tab) {
        tab.click();
      }
    });
  </script>
@endif

<script>
  document.addEventListener('DOMContentLoaded', function() {
    if (window.location.hash === '#address-tab') {
      var tab = document.querySelector('a[href="#address-tab"]');
      if (tab) {
        tab.click();
      }
    }
  });
</script>
