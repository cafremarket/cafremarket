@php
  $payoutMethod = $transaction->getFromMetaData('payout_method');
  $payoutInstruction = $transaction->getFromMetaData('payout_instruction');
  $payoutMethodLabel = match ($payoutMethod) {
      'mpesa' => trans('packages.wallet.payout_method_mpesa'),
      'emola' => trans('packages.wallet.payout_method_emola'),
      'bank_transfer' => trans('packages.wallet.payout_method_bank_transfer'),
      default => $payoutMethod,
  };
@endphp

{!! $transaction->getFromMetaData('description') !!}

@if ($payoutMethod && $transaction->type === 'withdraw')
  <br>
  <small class="text-muted">
    <strong>{{ trans('packages.wallet.payout_method') }}:</strong> {{ $payoutMethodLabel }}
  </small>
  @if ($payoutInstruction)
    <br>
    <small class="text-muted">
      <strong>{{ trans('app.payout_instruction') }}:</strong> {{ $payoutInstruction }}
    </small>
  @endif
@endif
