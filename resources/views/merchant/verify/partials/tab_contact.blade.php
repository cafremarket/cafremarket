<div class="mp-verify-tab-panel" id="mp-tab-contact" role="tabpanel" hidden>
  <div class="mp-panel mp-panel--flat">
    <div class="mp-panel__head">
      <div class="mp-panel__head-icon"><i class="fa fa-phone"></i></div>
      <div class="mp-panel__head-text">
        <h2>{{ trans('app.phone_and_email_verification') }}</h2>
        <p>{{ trans('messages.verification_tab_contact_help') }}</p>
      </div>
      @if ($shop->phone_verified)
        <span class="mp-verify-tab-badge mp-verify-tab-badge--success">{{ trans('app.verified') }}</span>
      @elseif ($hasPhone && $hasEmail)
        <span class="mp-verify-tab-badge mp-verify-tab-badge--info">{{ trans('app.submitted') }}</span>
      @else
        <span class="mp-verify-tab-badge mp-verify-tab-badge--warning">{{ trans('app.action_required') }}</span>
      @endif
    </div>
    <div class="mp-panel__body">
      @if ($canSubmit)
        @if ($hasPhone)
          <div class="mp-alert mp-alert--success"><i class="fa fa-check-circle"></i> {{ trans('messages.verification_phone_saved_notice') }}</div>
        @endif

        {!! Form::open(['route' => 'merchant.verify.phone', 'id' => 'store-phone-form', 'class' => 'mp-verify-contact-form']) !!}
          <div class="mp-form-group mp-form-group--narrow">
            {!! Form::label('support_phone', trans('app.form.phone')) !!}
            {!! Form::text('support_phone', old('support_phone', $shopPhone), ['class' => 'mp-form-control', 'placeholder' => trans('app.placeholder.phone'), 'required']) !!}
            <small class="mp-form-hint">{{ trans('messages.verification_phone_field_help') }}</small>
          </div>
          <button type="submit" class="mp-btn mp-btn--primary">
            <i class="fa fa-phone"></i> {{ trans('app.save_phone_number') }}
          </button>
        {!! Form::close() !!}

        <hr class="mp-divider">

        @if ($hasEmail)
          <div class="mp-alert mp-alert--success"><i class="fa fa-check-circle"></i> {{ trans('messages.verification_email_saved_notice') }}</div>
        @endif

        {!! Form::open(['route' => 'merchant.verify.email', 'id' => 'store-email-form', 'class' => 'mp-verify-contact-form']) !!}
          <div class="mp-form-group mp-form-group--narrow">
            {!! Form::label('support_email', trans('app.form.email_address')) !!}
            {!! Form::email('support_email', old('support_email', $shopEmail), ['class' => 'mp-form-control', 'placeholder' => trans('app.placeholder.valid_email'), 'required']) !!}
            <small class="mp-form-hint">{{ trans('messages.verification_email_field_help') }}</small>
          </div>
          <button type="submit" class="mp-btn mp-btn--primary">
            <i class="fa fa-envelope"></i> {{ trans('app.save_email_address') }}
          </button>
        {!! Form::close() !!}
      @else
        <ul class="mp-checklist">
          <li class="{{ $hasPhone ? 'is-done' : 'is-pending' }}">
            <i class="fa fa-{{ $hasPhone ? 'check-circle' : 'circle-o' }}"></i>
            {{ trans('app.form.phone') }}: {{ $hasPhone ? $shopPhone : trans('app.not_available') }}
          </li>
          <li class="{{ $hasEmail ? 'is-done' : 'is-pending' }}">
            <i class="fa fa-{{ $hasEmail ? 'check-circle' : 'circle-o' }}"></i>
            {{ trans('app.form.email_address') }}: {{ $hasEmail ? $shopEmail : trans('app.not_available') }}
          </li>
        </ul>
      @endif
    </div>
  </div>
</div>
