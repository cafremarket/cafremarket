@if ($coupons->count() > 0)
  <div class="sf-coupon-grid">
    @foreach ($coupons as $coupon)
      @php
        $value = $coupon->type == 'amount' ? get_formated_currency($coupon->value, 2) : get_formated_decimal($coupon->value) . '%';
        $expired = $coupon->ending_time < \Carbon\Carbon::now();
      @endphp

      <article class="sf-coupon-card {{ $expired ? 'sf-coupon-card--expired' : '' }}">
        <div class="sf-coupon-card__value">
          <strong>{{ trans('theme.coupon_off', ['value' => $value]) }}</strong>
          @if ($coupon->min_order_amount)
            <span class="sf-coupon-card__limit">
              {{ trans('theme.when_min_order_value', ['value' => get_formated_currency($coupon->min_order_amount, 2)]) }}
            </span>
          @endif
        </div>
        <div class="sf-coupon-card__body">
          <div class="sf-coupon-card__code">{{ $coupon->code }}</div>
          <div class="sf-coupon-card__store">
            <a href="{{ route('show.store', $coupon->shop->slug) }}" target="_blank" rel="noopener">{{ $coupon->shop->name }}</a>
            <small><i class="far fa-external-link text-muted"></i></small>
          </div>
          <div class="sf-coupon-card__validity">{!! $coupon->validityText() !!}</div>
        </div>
      </article>
    @endforeach
  </div>
@else
  <div class="sf-empty-state">
    <i class="fas fa-tags" aria-hidden="true"></i>
    <p>@lang('theme.nothing_found')</p>
  </div>
@endif

<div class="row pagenav-wrapper mb-3">
  {{ $coupons->links('theme::layouts.pagination') }}
</div>
