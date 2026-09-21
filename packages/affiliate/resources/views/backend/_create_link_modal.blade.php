<div class="modal fade admin-modal" id="createAffiliateLinkModal" tabindex="-1" role="dialog" aria-labelledby="createAffiliateLinkModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
        <h4 class="modal-title" id="createAffiliateLinkModalLabel">{{ trans('packages.affiliate.create_affiliate_link') }}</h4>
      </div>

      {!! Form::open(['route' => 'affiliate.link.create', 'method' => 'post', 'id' => 'affiliate-link-create-form']) !!}
      <div class="modal-body">
        <p class="text-muted">{{ trans('packages.affiliate.short_link_help') }}</p>

        <div class="form-group">
          {!! Form::label('shop_id', trans('packages.affiliate.store') . ' *') !!}
          <select name="shop_id" id="affiliate-shop" class="form-control" required>
            <option value="">{{ trans('packages.affiliate.select_store') }}</option>
            @foreach ($shops as $shop)
              <option value="{{ $shop['id'] }}">{{ $shop['name'] }}</option>
            @endforeach
          </select>

          <div id="affiliate-shop-info" aria-live="polite">
            <p class="shop-info-row">
              <span class="shop-info-label">{{ trans('packages.affiliate.store_email') }}:</span>
              <span id="affiliate-shop-email"></span>
            </p>
            <p class="shop-info-row">
              <span class="shop-info-label">{{ trans('packages.affiliate.store_address') }}:</span>
              <span id="affiliate-shop-address"></span>
            </p>
          </div>
        </div>

        <div class="form-group">
          {!! Form::label('inventory_id', trans('packages.affiliate.product') . ' *') !!}
          <select name="inventory_id" id="affiliate-product" class="form-control" required disabled>
            <option value="">{{ trans('packages.affiliate.select_store_first') }}</option>
          </select>
          <p id="affiliate-product-meta" class="help-block text-muted"></p>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">{{ trans('app.cancel') }}</button>
        <button type="submit" class="btn btn-primary" id="affiliate-create-link" disabled>
          {{ trans('packages.affiliate.create_affiliate_link') }}
        </button>
      </div>
      {!! Form::close() !!}
    </div>
  </div>
</div>
