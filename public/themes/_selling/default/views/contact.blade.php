<div class="container">
  <div class="sf-sell-section__head">
    <span class="sf-sell-section__eyebrow">{{ trans('theme.selling_page.contact_eyebrow') }}</span>
    <h2 class="sf-sell-section__title">{{ trans('theme.contact_us') }}</h2>
    <p class="sf-sell-section__subtitle">{{ trans('messages.we_will_get_back_to_you_soon') }}</p>
  </div>

  <div class="sf-sell-contact__grid">
    <div class="sf-sell-contact__info">
      <h3>{{ trans('theme.selling_page.contact_heading') }}</h3>
      <p>{{ trans('theme.selling_page.contact_intro') }}</p>
      <ul class="sf-sell-contact__channels">
        <li>
          <i class="fa fa-envelope"></i>
          <span>{{ trans('theme.selling_page.contact_email_label') }}: {{ config('system_settings.support_email') ?: trans('theme.selling_page.contact_email_fallback') }}</span>
        </li>
        <li>
          <i class="fa fa-clock-o"></i>
          <span>{{ trans('theme.selling_page.contact_hours') }}</span>
        </li>
        <li>
          <i class="fa fa-map-marker"></i>
          <span>{{ trans('theme.selling_page.contact_location') }}</span>
        </li>
      </ul>
    </div>

    <div class="sf-sell-contact__form">
      <div id="success" class="sf-sell-contact__alert"></div>

      {!! Form::open(['route' => 'contact_us', 'id' => 'contactForm', 'name' => 'sentMessage', 'data-toggle' => 'validator', 'novalidate', 'files' => true]) !!}
        <div class="row">
          <div class="col-sm-6">
            <div class="form-group">
              {!! Form::text('name', null, ['class' => 'form-control', 'placeholder' => trans('theme.placeholder.name'), 'required']) !!}
            </div>
          </div>
          <div class="col-sm-6">
            <div class="form-group">
              {!! Form::email('email', null, ['id' => 'email', 'class' => 'form-control', 'placeholder' => trans('theme.placeholder.email'), 'required']) !!}
            </div>
          </div>
        </div>

        <div class="row">
          <div class="col-sm-6">
            <div class="form-group">
              {!! Form::text('phone', null, ['class' => 'form-control', 'placeholder' => trans('theme.placeholder.phone_number')]) !!}
            </div>
          </div>
          <div class="col-sm-6">
            <div class="form-group">
              {!! Form::text('subject', null, ['class' => 'form-control', 'placeholder' => trans('theme.placeholder.contact_us_subject'), 'required']) !!}
            </div>
          </div>
        </div>

        <div class="form-group">
          {!! Form::textarea('message', null, ['class' => 'form-control', 'placeholder' => trans('theme.placeholder.message'), 'rows' => '4', 'required']) !!}
        </div>

        @if (config('services.recaptcha.key'))
          <div class="form-group">
            <div class="g-recaptcha" data-sitekey="{{ config('services.recaptcha.key') }}"></div>
          </div>
        @endif

        @if (is_incevio_package_loaded('smartForm') && config('system_settings.smart_form_id_for_selling_page'))
          @include('smartForm::partials._parsed_input_fields', ['row' => smart_form_fields(config('system_settings.smart_form_id_for_selling_page'))])
        @endif

        <button type="submit" class="sf-sell-btn sf-sell-btn--primary sf-sell-btn--lg">
          {{ trans('theme.button.send_message') }}
        </button>
      {!! Form::close() !!}
    </div>
  </div>
</div>

@if (config('services.recaptcha.key'))
  <script src="https://www.google.com/recaptcha/api.js" async defer></script>
@endif
