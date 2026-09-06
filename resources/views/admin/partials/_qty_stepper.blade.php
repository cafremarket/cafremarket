@php
  $stepperMin = $min ?? 0;
  $stepperStep = $step ?? '1';
  $stepperClass = $class ?? 'form-control';
  $attrs = [
    'class' => $stepperClass,
    'min' => $stepperMin,
    'step' => $stepperStep,
  ];
  if (! empty($placeholder)) {
      $attrs['placeholder'] = $placeholder;
  }
  if (! empty($required)) {
      $attrs['required'] = 'required';
  }
@endphp
<div class="qty-stepper{{ ! empty($prefix) ? ' qty-stepper--money' : '' }}">
  <button type="button" class="qty-stepper__btn" data-dir="-1" tabindex="-1" aria-label="{{ trans('app.decrease') }}">−</button>
  @if (! empty($prefix))
    <span class="qty-stepper__prefix">{{ $prefix }}</span>
  @endif
  {!! Form::number($name, $value, $attrs) !!}
  <button type="button" class="qty-stepper__btn" data-dir="1" tabindex="-1" aria-label="{{ trans('app.increase') }}">+</button>
</div>
