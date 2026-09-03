@can('create', \App\Models\Product::class)
  @if (Auth::user()->isFromMerchant() && Auth::user()->shop && ! Auth::user()->shop->canAddMoreInventory())
    {{-- Limit reached: still show disabled hint via notice elsewhere --}}
  @else
    <a href="{{ mp_route('admin.stock.product.create') }}" class="btn btn-new btn-flat">
      <i class="fa fa-plus"></i> {{ trans('app.add_product') }}
    </a>
  @endif
@endcan
