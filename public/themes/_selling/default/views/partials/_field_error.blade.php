@if ($errors->has($field))
  @php
    $errorId = 'error-' . preg_replace('/[^a-zA-Z0-9_-]/', '-', $field);
  @endphp
  <p class="sf-sell-field-error" id="{{ $errorId }}" role="alert">
    <i class="fa fa-exclamation-circle" aria-hidden="true"></i>
    {{ $errors->first($field) }}
  </p>
@endif
