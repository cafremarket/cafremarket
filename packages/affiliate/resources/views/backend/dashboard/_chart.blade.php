<div class="row">
  <div class="col-md-4">
    <div class="mp-panel">
      <div class="mp-panel__head">
        <div class="mp-panel__head-text">
          <h2>{{ trans('packages.affiliate.commission_by_link') }}</h2>
        </div>
      </div>
      <div class="mp-panel__body">
        <canvas class="d-flex w-100 h-100" id="js-commissionByLinkChart"></canvas>
      </div>
    </div>
  </div>

  <div class="col-md-4">
    <div class="mp-panel">
      <div class="mp-panel__head">
        <div class="mp-panel__head-text">
          <h2>{{ trans('packages.affiliate.commission_by_shop') }}</h2>
        </div>
      </div>
      <div class="mp-panel__body">
        <canvas class="d-flex w-100 h-100" id="js-commissionByShopChart"></canvas>
      </div>
    </div>
  </div>

  <div class="col-md-4">
    <div class="mp-panel">
      <div class="mp-panel__head">
        <div class="mp-panel__head-text">
          <h2>{{ trans('packages.affiliate.visitors_by_link') }}</h2>
        </div>
      </div>
      <div class="mp-panel__body">
        <canvas class="d-flex w-100 h-100" id="js-visitorByLinkChart"></canvas>
      </div>
    </div>
  </div>
</div>

@include('affiliate::scripts.dashboard_charts')
