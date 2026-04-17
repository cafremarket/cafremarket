<div class="row">
  <div class="col-md-6">
    <div class="box">
      <div class="box-header with-border">
        <div class="box-title">
          {{ trans('packages.affiliate.top_links_by_visitors') }}
        </div>
        <div class="box-tools pull-right">
          <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-plus"></i></button>
        </div>
      </div>
      <div class="box-body">
        <table class="table table-responsive">
          <thead>
            <th></th>
            <th>{{ trans('app.slug') }}</th>
            <th>{{ trans('app.shop') }}</th>
            <th>{{ trans('app.visitors') }}</th>
            <th>{{ trans('app.action') }}</th>
          </thead>
          <tbody>
            @if ($top_links_by_visitors->count() == 0)
              <tr>
                <td colspan="5" class="text-center">{{ trans('packages.affiliate.you_dont_have_any_links_yet') }}</td>
              </tr>
            @else
              @foreach ($top_links_by_visitors as $link)
                <tr>
                  <td>{{ $loop->iteration }}</td>
                  <td>{{ $link->slug }}</td>
                  <td>{{ $link->inventory->shop->name }}</td>
                  <td>{{ $link->visitor_count }}</td>
                  <td><a href="{{ route('show.product',['slug' => $link->inventory->slug]) }}"><i class="fa fa-external-link"></i></a></td>
                </tr>
              @endforeach
            @endif
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-md-6">
    <div class="box">
      <div class="box-header with-border">
        <div class="box-title">
          {{ trans('packages.affiliate.top_links_by_commission') }}
        </div>
        <div class="box-tools pull-right">
          <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-plus"></i></button>
        </div>
      </div>
      <div class="box-body">
        <table class="table table-responsive">
          <thead>
            <th></th>
            <th>{{ trans('app.slug') }}</th>
            <th>{{ trans('app.shop') }}</th>
            <th>{{ trans('packages.affiliate.commission') }}</th>
            <th>{{ trans('app.action') }}</th>
          </thead>
          <tbody>
            @if ($top_links_by_commission->count() == 0)
              <tr>
                <td colspan="5" class="text-center">{{ trans('packages.affiliate.you_dont_have_any_links_with_commission') }}</td>
              </tr>
            @else
              @foreach ($top_links_by_commission as $commission)
                <tr>
                  <td>{{ $loop->iteration }}</td>
                  <td>{{ $commission->affiliateLink->slug }}</td>
                  <td>{{ $commission->order->shop->name }}</td>
                  <td>{{ get_formated_currency($commission->total_commission, 2) }}</td>
                  <td><a href="{{ route('show.product',['slug' => $commission->affiliateLink->inventory->slug]) }}"><i class="fa fa-external-link"></i></a></td>    
                </tr>
              @endforeach
            @endif
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
