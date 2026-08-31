@include('admin.partials.ui.card_start', [
  'title' => trans('app.add_inventory'),
  'icon' => 'fa-cubes',
  'class' => 'admin-form-section collapsed-box',
  'bodyClass' => '',
  'actions' => '<a href="javascript:void(0)" data-link="' . route('admin.stock.inventory.bulkUpdate.form') . '" class="ajax-modal-btn btn btn-default btn-flat btn-sm">' . e(trans('app.bulk_update')) . '</a>'
    . '<a href="javascript:void(0)" data-link="' . route('admin.stock.inventory.bulk') . '" class="ajax-modal-btn btn btn-default btn-flat btn-sm">' . e(trans('app.bulk_import')) . '</a>'
    . '<button type="button" class="btn btn-new btn-flat btn-sm" data-widget="collapse"><i class="fa fa-plus"></i> ' . e(trans('app.add_inventory')) . '</button>',
])
    @if (Auth::user()->shop->canAddMoreInventory())
      <div class="form-group">
        <div class="input-group input-group-lg">
          <span class="input-group-addon"> <i class="fa fa-search text-muted"></i> </span>
          {!! Form::text('searchProduct', null, ['id' => 'searchProduct', 'class' => 'form-control', 'placeholder' => trans('app.placeholder.search_product')]) !!}
        </div>
      </div>
      <div id="productFounds"></div>
    @else
      @include('admin.partials._max_inventory_limit_notice')
    @endif
@include('admin.partials.ui.card_end')
