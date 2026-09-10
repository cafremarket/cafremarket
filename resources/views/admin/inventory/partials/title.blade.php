{{ $inventory->title }}

<a href="{{ storefront_product_url($inventory) }}" class="ml-3" target="_blank"><i data-toggle="tooltip" data-placement="top" title="{{ trans('app.view_as_customer') }}" class="fa fa-external-link"></i></a>

@if ($inventory->auctionable && $type != 'auction')
  <span class="label label-default pull-right">{{ trans('packages.auction.auction') }}</span>
@endif

@if (is_incevio_package_loaded('shopify') && $inventory->isFromShopify())
  <span class="label label-success cursor pull-right mx-2" data-toggle="tooltip" data-placement="top" title="{{ trans('packages.shopify.imported_from_shopify') }}">
    <i class="fa fa-shopping-bag"></i> {{ trans('packages.shopify.shopify') }}
  </span>
@endif
