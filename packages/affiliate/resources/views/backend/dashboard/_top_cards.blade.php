<div class="mp-stat-grid">
  <div class="mp-stat-card mp-stat-card--blue">
    <div class="mp-stat-card__icon"><i class="fa fa-users"></i></div>
    <div class="mp-stat-card__body">
      <span class="mp-stat-card__label">{{ trans('packages.affiliate.visitors_brought') }}</span>
      <span class="mp-stat-card__value">
        <span class="mp-stat-card__value-text">{{ $new_visitors ?? 0 }}</span>
      </span>
      <small class="text-muted">{{ trans('app.new_in_30_days', ['new' => 0, 'model' => trans('app.visitors')]) }}</small>
    </div>
  </div>

  <div class="mp-stat-card mp-stat-card--yellow">
    <div class="mp-stat-card__icon"><i class="fa fa-bar-chart"></i></div>
    <div class="mp-stat-card__body">
      <span class="mp-stat-card__label">{{ trans('packages.affiliate.products_sold') }}</span>
      <span class="mp-stat-card__value">
        <span class="mp-stat-card__value-text">{{ $new_product_sold }}</span>
      </span>
      <small class="text-muted">{{ trans('app.new_in_30_days', ['new' => $last_thirty_days_product_sold, 'model' => trans('packages.affiliate.products_sold')]) }}</small>
    </div>
  </div>

  <div class="mp-stat-card mp-stat-card--green">
    <div class="mp-stat-card__icon"><i class="fa fa-shopping-cart"></i></div>
    <div class="mp-stat-card__body">
      <span class="mp-stat-card__label">{{ trans('app.orders') }}</span>
      <span class="mp-stat-card__value">
        <span class="mp-stat-card__value-text">{{ $todays_order_count }}</span>
      </span>
      <small class="text-muted">{{ trans('app.new_in_30_days', ['new' => $last_thirty_days_order_count, 'model' => trans('app.orders')]) }}</small>
    </div>
  </div>

  <div class="mp-stat-card mp-stat-card--red">
    <div class="mp-stat-card__icon"><i class="fa fa-money"></i></div>
    <div class="mp-stat-card__body">
      <span class="mp-stat-card__label">{{ trans('packages.affiliate.commission') }}</span>
      <span class="mp-stat-card__value">
        <span class="mp-stat-card__value-text">{{ get_formated_currency($todays_commission, 2) }}</span>
      </span>
      <small class="text-muted">{{ trans('app.new_in_30_days', ['new' => get_formated_currency($last_thirty_days_commission, 2), 'model' => trans('packages.affiliate.affiliate_commission')]) }}</small>
    </div>
  </div>
</div>
