@if ($transaction->hasPayoutPaymentProof() && $transaction->userCanDownloadPayoutPaymentProof())
  @php
    $proofUrl = $transaction->payoutPaymentProofUrl();
    $proofName = $transaction->payoutPaymentProofName();
    $isImage = $transaction->payoutPaymentProofIsImage();
    $downloadUrl = route('wallet.transaction.payout_proof.download', $transaction);
  @endphp
  <div class="payout-payment-proof-actions">
    <a href="{{ $downloadUrl }}" class="btn btn-default btn-xs btn-flat">
      <i class="fa fa-download"></i> {{ trans('packages.wallet.payout_payment_proof_download') }}
    </a>
    @if ($isImage && $proofUrl)
      <a href="javascript:void(0)" class="btn btn-default btn-xs btn-flat wire-proof-preview"
        data-src="{{ $proofUrl }}"
        data-name="{{ $proofName }}">
        <i class="fa fa-eye"></i> {{ trans('app.preview') }}
      </a>
    @endif
    <br>
    <small class="text-muted" title="{{ $proofName }}">
      <i class="fa fa-paperclip"></i> {{ \Illuminate\Support\Str::limit($proofName, 28) }}
    </small>
  </div>
@else
  <span class="text-muted">—</span>
@endif
