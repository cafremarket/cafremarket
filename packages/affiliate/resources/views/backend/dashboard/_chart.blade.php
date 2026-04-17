<div class="row">
  <div class="col-md-4">
    <div class="box">
      <div class="box-header with-border">
        <div class="box-title">
          {{ trans('packages.affiliate.commission_by_link') }}
        </div>
        <div class="box-tools pull-right">
          <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-plus"></i></button>
        </div>
      </div>
      <div class="box-body">
        <canvas class="d-flex w-100 h-100" id="js-commissionByLinkChart"></canvas>
      </div>
    </div>
  </div>
  
  <div class="col-md-4">
    <div class="box">
      <div class="box-header with-border">
        <div class="box-title">
          {{ trans('packages.affiliate.commission_by_shop') }}
        </div>
        <div class="box-tools pull-right">
          <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-plus"></i></button>
        </div>
      </div>
      <div class="box-body">
        <canvas class="d-flex w-100 h-100" id="js-commissionByShopChart"></canvas>
       </div>
    </div>
  </div>

  <div class="col-md-4">
    <div class="box">
      <div class="box-header with-border">
        <div class="box-title">
          {{ trans('packages.affiliate.visitors_by_link') }}
        </div>
        <div class="box-tools pull-right">
          <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-plus"></i></button>
        </div>
      </div>
      <div class="box-body">
        <canvas class="d-flex w-100 h-100" id="js-visitorByLinkChart"></canvas>
       </div>
    </div>
  </div>
</div>

@include('affiliate::scripts.dashboard_charts')