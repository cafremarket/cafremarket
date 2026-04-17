<div class="row dashboard-total">
  <div class="col-md-3 stretch-card grid-margin">
    <div class="card bg-gradient-danger card-img-holder text-white">
      <div class="card-body">
        <img src="/images/circle.svg" class="card-img-absolute" alt="circle">

        <h4 class="font-weight-normal mb-3">{{ trans('packages.affiliate.visitors_brought') }} <i class="icon ion-md-people float-right"></i>
        </h4>

        <h2 class="mb-5">{{ $new_visitors ?? 0 }}</h2>

        <h6 class="card-text"><i class="icon ion-md-add"></i> {{ trans('app.new_in_30_days', ['new' => 0, 'model' => trans('app.visitors')]) }}</h6>
      </div>
    </div>
  </div>

  <div class="col-md-3 stretch-card grid-margin">
    <div class="card bg-gradient-info card-img-holder text-white">
      <div class="card-body">
        <img src="/images/circle.svg" class="card-img-absolute" alt="circle">

        <h4 class="font-weight-normal mb-3">{{ trans('packages.affiliate.products_sold') }} <i class="fa fa-bar-chart-o float-right"></i>
        </h4>

        <h2 class="mb-5">{{ $new_product_sold }}</h2>

        <h6 class="card-text"><i class="icon ion-md-add"></i> {{ trans('app.new_in_30_days', ['new' => $last_thirty_days_product_sold, 'model' => trans('packages.affiliate.products_sold')]) }}</h6>
      </div>
    </div>
  </div>

  <div class="col-md-3 stretch-card grid-margin">
    <div class="card bg-gradient-primary card-img-holder text-white">
      <div class="card-body">
        <img src="/images/circle.svg" class="card-img-absolute" alt="circle">

        <h4 class="font-weight-normal mb-3">{{ trans('app.orders') }}
          <i class="icon ion-md-cart float-right"></i>
        </h4>

        <h2 class="mb-5">
          {{ $todays_order_count }}
        </h2>

        <h6 class="card-text">
          <i class="icon ion-md-add"></i> {{ trans('app.new_in_30_days', ['new' => $last_thirty_days_order_count, 'model' => trans('app.orders')]) }}
        </h6>
      </div>
    </div>
  </div>

  <div class="col-md-3 stretch-card grid-margin">
    <div class="card bg-gradient-success card-img-holder text-white">
      <div class="card-body">
        <img src="/images/circle.svg" class="card-img-absolute" alt="circle">

        <h4 class="font-weight-normal mb-3">{{ trans('packages.affiliate.commission') }}
          <i class="icon ion-md-wallet float-right"></i>
        </h4>

        <h2 class="mb-5">
          {{ get_formated_currency($todays_commission, 2) }}
        </h2>

        <h6 class="card-text">
          <i class="icon ion-md-add"></i> {{ trans('app.new_in_30_days', ['new' => get_formated_currency($last_thirty_days_commission, 2), 'model' => trans('packages.affiliate.affiliate_commission')]) }}
        </h6>
      </div>
    </div>
  </div>
</div>
