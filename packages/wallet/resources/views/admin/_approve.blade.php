@php
  $payoutMethod = $transaction->getFromMetaData('payout_method') ?: 'bank_transfer';
  $payoutInstruction = $transaction->getFromMetaData('payout_instruction');
  if (! $payoutInstruction && $transaction->payable?->pay_to) {
    $payoutInstruction = $transaction->payable->pay_to;
  }
  $withdrawAmount = abs((float) $transaction->amount);
@endphp

<div class="modal-dialog modal-sm">
  <div class="modal-content">
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
      {{ trans('packages.wallet.approval') }}
    </div>

    {!! Form::open(['route' => ['admin.payout.approve', $transaction], 'method' => 'post', 'class' => 'action-form', 'data-toggle' => 'validator']) !!}
    <div class="modal-body">
      <div class="form-group mt-2 mb-3">
        <label>{{ trans('packages.wallet.payout_method') }}:</label>
        <p class="mb-1">
          @if ($payoutMethod === 'mpesa')
            {{ trans('packages.wallet.payout_method_mpesa') }}
          @elseif ($payoutMethod === 'emola')
            {{ trans('packages.wallet.payout_method_emola') }}
          @else
            {{ trans('packages.wallet.payout_method_bank_transfer') }}
          @endif
        </p>
      </div>

      <div class="form-group mt-2 mb-4">
        <label>{{ trans('app.payout_instruction') }}:</label>
        <p>{{ $payoutInstruction ?: trans('app.payout_instruction_not_available') }}</p>
      </div>

      <div class="form-group mt-2 mb-3">
        <label>{{ trans('packages.wallet.payout_amount') }}:</label>
        <p class="lead mb-0">{{ get_formated_currency($withdrawAmount) }}</p>
      </div>

      <p class="text-muted small mb-0">
        <i class="fa fa-info-circle"></i>
        {!! trans('packages.wallet.payout_sales_commission_already_deducted', ['platform' => get_platform_title()]) !!}
      </p>
    </div>
    <div class="modal-footer">
      {!! Form::submit(trans('packages.wallet.approve'), ['class' => 'btn btn-flat btn-new']) !!}
    </div>
    {!! Form::close() !!}
  </div>
</div>
