{{-- Sale amount breakdown panel (hover / click) --}}
@php
  $breakdown = $breakdown ?? [];
  $currencyId = config('system_settings.currency.id');
  $rows = [
    ['key' => 'products', 'label' => trans('app.products')],
    ['key' => 'taxes', 'label' => trans('app.taxes')],
    ['key' => 'shipping', 'label' => trans('app.shipping')],
    ['key' => 'handling', 'label' => trans('app.handling')],
    ['key' => 'packaging', 'label' => trans('app.packaging')],
    ['key' => 'discount', 'label' => trans('app.discount')],
  ];
@endphp
<span class="mp-stat-info">
  <button
    type="button"
    class="mp-stat-info__btn"
    aria-expanded="false"
    aria-label="{{ trans('app.sale_breakdown') }}"
    title="{{ trans('app.sale_breakdown') }}"
  >
    <i class="fa fa-info-circle" aria-hidden="true"></i>
  </button>
  <div class="mp-stat-info__panel" role="tooltip">
    <div class="mp-stat-info__title">{{ trans('app.sale_breakdown') }}</div>
    @if (!empty($breakdown['order_number']))
      <div class="mp-stat-info__meta">#{{ $breakdown['order_number'] }}</div>
    @elseif (!empty($breakdown['order_count']))
      <div class="mp-stat-info__meta">
        {{ trans_choice('app.sale_order_count', $breakdown['order_count'], ['count' => $breakdown['order_count']]) }}
      </div>
    @endif
    <dl class="mp-stat-info__list">
      @foreach ($rows as $row)
        <div class="mp-stat-info__row">
          <dt>{{ $row['label'] }}</dt>
          <dd>{{ get_formated_currency($breakdown[$row['key']] ?? 0, 2, $currencyId) }}</dd>
        </div>
      @endforeach
      <div class="mp-stat-info__row mp-stat-info__row--total">
        <dt>{{ trans('app.grand_total') }}</dt>
        <dd>{{ get_formated_currency($breakdown['grand_total'] ?? 0, 2, $currencyId) }}</dd>
      </div>
    </dl>
  </div>
</span>
