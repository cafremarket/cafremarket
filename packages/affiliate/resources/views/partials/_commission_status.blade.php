{{-- Payout status of one affiliate commission. Pass: $commission, $paidLabel (text for paid). --}}
@if ($commission->isPaid())
  <i class="fa fa-check text-success"></i> {{ $paidLabel }}
@elseif ($commission->voided_at)
  <i class="fa fa-ban text-danger"></i> {{ trans('packages.affiliate.commission_voided') }}
@elseif ($commission->release_at)
  <i class="fa fa-hourglass text-info"></i> {{ trans('packages.affiliate.pending') }}
  <br><small class="text-muted">{{ trans('packages.affiliate.credited_on', ['date' => $commission->release_at->format('d/m/Y')]) }}</small>
@else
  <i class="fa fa-hourglass text-info"></i> {{ trans('packages.affiliate.pending') }}
  <br><small class="text-muted">{{ trans('packages.affiliate.credited_after_refund_period') }}</small>
@endif
