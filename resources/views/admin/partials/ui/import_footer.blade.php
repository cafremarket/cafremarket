{{-- Import/upload review footer. Pass: cancelUrl, cancelLabel (optional), cancelClass (optional), formAction, formMethod (optional), hiddenFields (HTML), submitLabel, submitClass (optional), extra (optional HTML before submit) --}}
<div class="admin-card admin-card--footer-only">
  <div class="admin-card__footer">
    <a href="{{ $cancelUrl }}" class="btn btn-flat {{ $cancelClass ?? 'btn-default' }}">{{ $cancelLabel ?? trans('app.cancel') }}</a>
    @if (!empty($rowCount))
      <small class="text-muted indent20">{{ trans('app.total_number_of_rows', ['value' => $rowCount]) }}</small>
    @endif
    @if (!empty($extra))
      {!! $extra !!}
    @endif
    <div class="pull-right">
      {!! Form::open(['route' => $formRoute, 'id' => $formId ?? 'form', 'class' => 'inline-form', 'data-toggle' => 'validator', 'method' => $formMethod ?? 'post']) !!}
      {!! $hiddenFields ?? '' !!}
      {!! Form::button($submitLabel, ['type' => 'submit', 'class' => ($submitClass ?? 'confirm btn btn-new btn-flat')]) !!}
      {!! Form::close() !!}
    </div>
  </div>
</div>
