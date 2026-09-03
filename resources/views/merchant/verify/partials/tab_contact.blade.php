<div class="mp-verify-tab-panel" id="mp-tab-contact" role="tabpanel" hidden>
  <section class="mp-verify-card">
    <header class="mp-verify-card__head">
      <div>
        <h2>{{ trans('app.phone_and_email_verification') }}</h2>
        <p>{{ trans('messages.verification_tab_contact_help') }}</p>
      </div>
      @if ($shop->phone_verified)
        <span class="mp-chip mp-chip--success">{{ trans('app.verified') }}</span>
      @elseif ($hasPhone && $hasEmail)
        <span class="mp-chip mp-chip--info">{{ trans('app.submitted') }}</span>
      @endif
    </header>

    @if ($canSubmit)
      <div class="mp-verify-contact">
        {!! Form::open(['route' => 'merchant.verify.phone', 'id' => 'store-phone-form', 'class' => 'mp-verify-contact__form']) !!}
          <div class="mp-form-group">
            {!! Form::label('support_phone', trans('app.form.phone')) !!}
            {!! Form::text('support_phone', old('support_phone', $shopPhone), ['class' => 'mp-form-control', 'placeholder' => trans('app.placeholder.phone'), 'required']) !!}
            <small class="mp-form-hint">{{ trans('messages.verification_phone_field_help') }}</small>
          </div>
          <button type="submit" class="mp-btn mp-btn--primary">{{ trans('app.save_phone_number') }}</button>
        {!! Form::close() !!}

        {!! Form::open(['route' => 'merchant.verify.email', 'id' => 'store-email-form', 'class' => 'mp-verify-contact__form']) !!}
          <div class="mp-form-group">
            {!! Form::label('support_email', trans('app.form.email_address')) !!}
            {!! Form::email('support_email', old('support_email', $shopEmail), ['class' => 'mp-form-control', 'placeholder' => trans('app.placeholder.valid_email'), 'required']) !!}
            <small class="mp-form-hint">{{ trans('messages.verification_email_field_help') }}</small>
          </div>
          <button type="submit" class="mp-btn mp-btn--primary">{{ trans('app.save_email_address') }}</button>
        {!! Form::close() !!}
      </div>
    @else
      <ul class="mp-verify-submit__checks">
        <li class="{{ $hasPhone ? 'is-done' : '' }}">
          <i class="fa fa-{{ $hasPhone ? 'check' : 'circle-o' }}"></i>
          {{ trans('app.form.phone') }}: {{ $hasPhone ? $shopPhone : trans('app.not_available') }}
        </li>
        <li class="{{ $hasEmail ? 'is-done' : '' }}">
          <i class="fa fa-{{ $hasEmail ? 'check' : 'circle-o' }}"></i>
          {{ trans('app.form.email_address') }}: {{ $hasEmail ? $shopEmail : trans('app.not_available') }}
        </li>
      </ul>
    @endif
  </section>
</div>
