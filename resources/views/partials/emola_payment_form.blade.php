<div id="emola-form" class="emola-form" style="display: none;">
  <hr class="style4 muted">
  <div class="form-group">
    <label for="emola-account">{{ trans('theme.emola_number') }}</label>
    {!! Form::text('emola_number', old('emola_number'), [
        'id' => 'emola-account',
        'class' => 'form-control emola-request-field flat',
        'placeholder' => trans('theme.emola_number_placeholder'),
        'inputmode' => 'numeric',
        'autocomplete' => 'tel',
        'maxlength' => 9,
    ]) !!}
    <small class="text-muted">{{ trans('theme.emola_number_help') }}</small>
    <div class="help-block with-errors"></div>
  </div>
</div>
