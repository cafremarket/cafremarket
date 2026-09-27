{{--
  Seller's refund/return period for a listing or variant (0 = no refund/return, max 15 days).
  Pass: name, value (null = default), inherit (true = variant row: adds "Same as product", selected when value is null),
  bare (true = select only, for table cells whose header already labels it).
--}}
@php
  $refundOptions = \App\Models\Inventory::refundDayOptions();
  if (! empty($inherit)) {
      $refundOptions = ['' => trans('refund_period.same_as_product')] + $refundOptions;
      $refundValue = $value ?? '';
  } else {
      $refundValue = $value ?? \App\Models\Inventory::REFUND_DAYS_DEFAULT;
  }
@endphp
@if (! empty($bare))
  <div class="form-group">
    {!! Form::select($name, $refundOptions, $refundValue === '' ? '' : (int) $refundValue, ['class' => 'form-control', 'style' => 'min-width: 130px']) !!}
  </div>
@else
  <div class="form-group">
    <label class="control-label with-help">{{ trans('refund_period.label') }}</label>
    <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="top" title="{{ trans(empty($inherit) ? 'refund_period.help' : 'refund_period.variant_help') }}"></i>
    {!! Form::select($name, $refundOptions, $refundValue === '' ? '' : (int) $refundValue, ['class' => 'form-control']) !!}
  </div>
@endif
