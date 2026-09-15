@extends('admin.layouts.master')

@section('page_title')
  {{ trans('nav.payment_instructions') }}
@endsection

@section('content')
  @include('admin.partials.ui.card_start', [
    'title' => trans('nav.payment_instructions'),
    'icon' => 'fa-file-text-o',
  ])

  <div class="row">
    <div class="col-sm-10 col-sm-offset-1">
      <p class="text-muted">{{ trans('messages.notice.payment_instructions_help') }}</p>

      @if ($manualPaymentMethods->isEmpty())
        <p>{{ trans('messages.no_orders') }}</p>
      @else
        {!! Form::open(['route' => 'admin.setting.config.paymentInstructions.update', 'method' => 'put', 'class' => 'form-horizontal admin-settings-form', 'id' => 'form', 'data-toggle' => 'validator']) !!}

        @foreach ($manualPaymentMethods as $method)
          @php
            $infoField = "wallet_payment_info_{$method->code}";
            $instructionsField = "wallet_payment_instructions_{$method->code}";
          @endphp

          <fieldset>
            <legend>{{ $method->name }}</legend>
          </fieldset>

          <div class="form-group">
            <div class="col-sm-4 text-right">
              {!! Form::label($infoField, trans('app.form.additional_details') . ':*', ['class' => 'with-help control-label']) !!}
              <small class="text-info d-block">
                {!! trans('help.config_additional_details') !!}
              </small>
            </div>

            <div class="col-sm-8 nopadding-left">
              {!! Form::text($infoField, get_from_option_table($infoField), ['class' => 'form-control', 'required']) !!}
              <div class="help-block with-errors"></div>
            </div>
          </div>

          <div class="form-group">
            <div class="col-sm-4 text-right">
              {!! Form::label($instructionsField, trans('app.form.payment_instructions') . ':*', ['class' => 'with-help control-label']) !!}
              <small class="text-info d-block">
                {!! trans('help.config_payment_instructions') !!}
              </small>
            </div>

            <div class="col-sm-8 nopadding-left">
              {!! Form::textarea($instructionsField, get_from_option_table($instructionsField), ['class' => 'form-control summernote', 'rows' => '4', 'required']) !!}
              <div class="help-block with-errors"></div>
            </div>
          </div>

          @unless ($loop->last)
            <hr>
          @endunless
        @endforeach

        <div class="spacer20"></div>
        {!! Form::submit(trans('app.update'), ['class' => 'btn btn-lg btn-flat btn-new pull-right']) !!}
        {!! Form::close() !!}
      @endif
    </div>
  </div>

  @include('admin.partials.ui.card_end')
@endsection
