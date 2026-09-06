@if ($inventory->auctionable)
  {{ get_formated_currency($inventory->base_price, 2, config('system_settings.currency.id')) }}
@elseif ($inventory->hasOffer())
  <small class="text-muted">{{ $inventory->sale_price }}</small><br />
  {{ get_formated_currency($inventory->offer_price, 2, config('system_settings.currency.id')) }}
@else
  {{ get_formated_currency($inventory->sale_price, 2, config('system_settings.currency.id')) }}
@endif
