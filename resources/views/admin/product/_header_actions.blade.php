@can('create', \App\Models\Product::class)
  @if (is_catalog_enabled())
    <a href="javascript:void(0)" data-link="{{ route('admin.catalog.product.bulk') }}" class="ajax-modal-btn btn btn-default btn-flat">
      <i class="fa fa-upload"></i> {{ trans('app.bulk_import') }}
    </a>
  @elseif(Auth::user()->isFromPlatform())
    <a href="javascript:void(0)" data-link="{{ route('admin.stock.inventory.bulk') }}" class="ajax-modal-btn btn btn-default btn-flat">
      <i class="fa fa-upload"></i> {{ trans('app.bulk_import') }}
    </a>
  @endif

  <a href="{{ route('admin.catalog.product.create') }}" class="btn btn-new btn-flat">
    <i class="fa fa-plus"></i> {{ trans('app.add_product') }}
  </a>
@endcan
