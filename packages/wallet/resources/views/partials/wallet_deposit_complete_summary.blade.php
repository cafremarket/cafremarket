@if (!empty($depositSummary) && ($depositSummary['fee'] ?? 0) > 0)
  <p class="text-muted small">
    {{ trans('packages.wallet.wallet_topup_complete_charge', [
      'total' => get_formated_currency($depositSummary['total']),
      'fee' => get_formated_currency($depositSummary['fee']),
      'credit' => get_formated_currency($depositSummary['base']),
    ]) }}
  </p>
@endif
