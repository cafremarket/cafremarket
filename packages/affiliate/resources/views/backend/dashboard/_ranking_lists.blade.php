<div class="row">
  <div class="col-md-6">
    <div class="mp-panel">
      <div class="mp-panel__head">
        <div class="mp-panel__head-text">
          <h2>{{ trans('packages.affiliate.top_links_by_visitors') }}</h2>
        </div>
      </div>
      <div class="mp-panel__body">
        <div class="table-responsive">
          <table class="table table-hover admin-table">
            <thead>
              <tr>
                <th></th>
                <th>{{ trans('app.slug') }}</th>
                <th>{{ trans('app.shop') }}</th>
                <th>{{ trans('app.visitors') }}</th>
                <th>{{ trans('app.action') }}</th>
              </tr>
            </thead>
            <tbody>
              @if ($top_links_by_visitors->count() == 0)
                <tr>
                  <td colspan="5" class="text-center text-muted">{{ trans('packages.affiliate.you_dont_have_any_links_yet') }}</td>
                </tr>
              @else
                @foreach ($top_links_by_visitors as $link)
                  <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $link->slug }}</td>
                    <td>{{ optional(optional($link->inventory)->shop)->name }}</td>
                    <td>{{ $link->visitor_count }}</td>
                    <td>
                      @if ($link->inventory)
                        <a href="{{ storefront_product_url($link->inventory) }}" target="_blank" rel="noopener"><i class="fa fa-external-link"></i></a>
                      @endif
                    </td>
                  </tr>
                @endforeach
              @endif
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <div class="col-md-6">
    <div class="mp-panel">
      <div class="mp-panel__head">
        <div class="mp-panel__head-text">
          <h2>{{ trans('packages.affiliate.top_links_by_commission') }}</h2>
        </div>
      </div>
      <div class="mp-panel__body">
        <div class="table-responsive">
          <table class="table table-hover admin-table">
            <thead>
              <tr>
                <th></th>
                <th>{{ trans('app.slug') }}</th>
                <th>{{ trans('app.shop') }}</th>
                <th>{{ trans('packages.affiliate.commission') }}</th>
                <th>{{ trans('app.action') }}</th>
              </tr>
            </thead>
            <tbody>
              @if ($top_links_by_commission->count() == 0)
                <tr>
                  <td colspan="5" class="text-center text-muted">{{ trans('packages.affiliate.you_dont_have_any_links_with_commission') }}</td>
                </tr>
              @else
                @foreach ($top_links_by_commission as $commission)
                  <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ optional($commission->affiliateLink)->slug }}</td>
                    <td>{{ optional(optional($commission->order)->shop)->name }}</td>
                    <td>{{ get_formated_currency($commission->total_commission, 2) }}</td>
                    <td>
                      @if (optional($commission->affiliateLink)->inventory)
                        <a href="{{ storefront_product_url($commission->affiliateLink->inventory) }}" target="_blank" rel="noopener"><i class="fa fa-external-link"></i></a>
                      @endif
                    </td>
                  </tr>
                @endforeach
              @endif
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
