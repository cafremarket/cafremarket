{{--
  Reusable store-first product picker modal.
  Usage:
    @include('admin.partials.product_picker_modal', [
      'mode' => 'multiple', // or 'single'
      'inputName' => 'featured[]',
      'selected' => $items, // [id => label]
      'buttonLabel' => 'Add products',
    ])
--}}
@php
  $mode = $mode ?? 'multiple';
  $inputName = $inputName ?? 'featured[]';
  $selected = $selected ?? [];
  $buttonLabel = $buttonLabel ?? 'Select products';
  $pickerId = $pickerId ?? 'productPicker';
@endphp

<div class="product-picker" id="{{ $pickerId }}" data-mode="{{ $mode }}" data-input-name="{{ $inputName }}">
  <div class="product-picker-selected mb-3" id="{{ $pickerId }}-selected">
    @forelse ($selected as $id => $label)
      <div class="product-picker-chip" data-id="{{ $id }}">
        <input type="hidden" name="{{ $inputName }}" value="{{ $id }}">
        <span>{{ $label }}</span>
        <button type="button" class="close product-picker-remove" aria-label="Remove">&times;</button>
      </div>
    @empty
      <p class="text-muted product-picker-empty mb-0">{{ trans('app.no_products_selected_yet') }}</p>
    @endforelse
  </div>

  <button type="button" class="btn btn-default" id="{{ $pickerId }}-open">
    <i class="fa fa-store"></i> {{ $buttonLabel }}
  </button>
</div>

<div class="modal fade" id="{{ $pickerId }}-modal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
        <h4 class="modal-title">{{ trans('app.select_product') }}</h4>
      </div>
      <div class="modal-body">
        <div class="form-group">
          <label>{{ trans('app.step_select_store') }}</label>
          <select class="form-control" id="{{ $pickerId }}-shop">
            <option value="">{{ trans('app.choose_a_store') }}</option>
          </select>
        </div>

        <div class="form-group">
          <label>{{ trans('app.step_search_store_products') }}</label>
          <input type="text" class="form-control" id="{{ $pickerId }}-search" placeholder="{{ trans('app.filter_by_name_or_sku') }}" disabled>
        </div>

        <div class="product-picker-list" id="{{ $pickerId }}-list">
          <p class="text-muted">{{ trans('app.select_store_to_load_products') }}</p>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">{{ trans('app.cancel') }}</button>
        <button type="button" class="btn btn-primary" id="{{ $pickerId }}-confirm" disabled>{{ trans('app.add_selected') }}</button>
      </div>
    </div>
  </div>
</div>

@push('script')
<style>
  .product-picker-chip {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #f4f6f9;
    border: 1px solid #d2d6de;
    border-radius: 4px;
    padding: 6px 10px;
    margin: 0 8px 8px 0;
    max-width: 100%;
  }
  .product-picker-chip span {
    font-size: 13px;
    line-height: 1.3;
  }
  .product-picker-chip .close {
    float: none;
    font-size: 18px;
    opacity: .6;
  }
  .product-picker-list {
    max-height: 360px;
    overflow-y: auto;
    border: 1px solid #e5e5e5;
    border-radius: 4px;
  }
  .product-picker-row {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 12px;
    border-bottom: 1px solid #f0f0f0;
    cursor: pointer;
    margin: 0;
    font-weight: normal;
  }
  .product-picker-row:hover { background: #f7f9fc; }
  .product-picker-row.is-selected { background: #eef7ff; }
  .product-picker-row .selected-badge {
    display: inline-block;
    margin-left: 6px;
    padding: 1px 6px;
    border-radius: 3px;
    background: #3c8dbc;
    color: #fff;
    font-size: 10px;
    font-weight: 600;
    vertical-align: middle;
  }
  .product-picker-row img {
    width: 40px;
    height: 40px;
    object-fit: cover;
    border-radius: 4px;
    background: #eee;
  }
  .product-picker-row .meta { flex: 1; min-width: 0; }
  .product-picker-row .meta strong { display: block; font-size: 13px; }
  .product-picker-row .meta small { color: #777; }
  .product-picker-row .price { white-space: nowrap; font-weight: 600; }
</style>
<script>
(function ($) {
  var rootId = @json($pickerId);
  var mode = @json($mode);
  var shopsUrl = @json(route('admin.productPicker.shops'));
  var productsUrl = @json(route('admin.productPicker.products'));
  var inputName = @json($inputName);

  var $root = $('#' + rootId);
  var $selected = $('#' + rootId + '-selected');
  var $modal = $('#' + rootId + '-modal');
  var $shop = $('#' + rootId + '-shop');
  var $search = $('#' + rootId + '-search');
  var $list = $('#' + rootId + '-list');
  var $confirm = $('#' + rootId + '-confirm');
  var $open = $('#' + rootId + '-open');

  var shopsLoaded = false;
  var draft = {}; // id -> {id,title,sku,price,image}
  var searchTimer = null;

  function selectedIds() {
    var ids = {};
    $selected.find('.product-picker-chip').each(function () {
      ids[String($(this).data('id'))] = true;
    });
    return ids;
  }

  function renderSelectedEmpty() {
    if (!$selected.find('.product-picker-chip').length) {
      if (!$selected.find('.product-picker-empty').length) {
        $selected.html('<p class="text-muted product-picker-empty mb-0">{{ trans('app.no_products_selected_yet') }}</p>');
      }
    }
  }

  function addChip(item) {
    $selected.find('.product-picker-empty').remove();
    if ($selected.find('.product-picker-chip[data-id="' + item.id + '"]').length) {
      return;
    }
    if (mode === 'single') {
      $selected.empty();
    }
    var label = item.title + (item.sku ? ' | ' + item.sku : '') + (item.price ? ' | ' + item.price : '');
    var html = '<div class="product-picker-chip" data-id="' + item.id + '">' +
      '<input type="hidden" name="' + inputName + '" value="' + item.id + '">' +
      '<span></span>' +
      '<button type="button" class="close product-picker-remove" aria-label="Remove">&times;</button>' +
      '</div>';
    var $chip = $(html);
    $chip.find('span').text(label);
    $selected.append($chip);
  }

  function updateConfirm() {
    $confirm.prop('disabled', Object.keys(draft).length === 0);
  }

  function renderList(items) {
    var already = selectedIds();
    if (!items.length) {
      $list.html('<p class="text-muted" style="padding:12px;">{{ trans('app.no_products_found_for_store') }}</p>');
      return;
    }
    var html = '';
    items.forEach(function (item) {
      var id = String(item.id);
      var isAlready = !!already[id];
      var isDraft = !!draft[id];
      var checked = isAlready || isDraft;
      var inputType = mode === 'single' ? 'radio' : 'checkbox';
      var name = rootId + '-choice';
      html += '<label class="product-picker-row' + (checked ? ' is-selected' : '') + '">' +
        '<input type="' + inputType + '" name="' + name + '" value="' + item.id + '"' +
          (checked ? ' checked' : '') +
          (isAlready ? ' data-already="1"' : '') + '>' +
        '<img src="' + (item.image || '') + '" alt="">' +
        '<div class="meta"><strong></strong><small></small></div>' +
        '<div class="price"></div>' +
        '</label>';
    });
    $list.html(html);
    $list.find('.product-picker-row').each(function (i) {
      var item = items[i];
      var id = String(item.id);
      var $strong = $(this).find('strong');
      $strong.text(item.title || '');
      if (already[id]) {
        $strong.append(' <span class="selected-badge">{{ trans('app.selected') }}</span>');
      }
      $(this).find('small').text(item.sku || '');
      $(this).find('.price').text(item.price || '');
      $(this).data('item', item);
    });
  }

  function loadShops() {
    return $.getJSON(shopsUrl).then(function (res) {
      var opts = '<option value="">— Choose a store —</option>';
      (res.data || []).forEach(function (shop) {
        opts += '<option value="' + shop.id + '"></option>';
      });
      $shop.html(opts);
      (res.data || []).forEach(function (shop, idx) {
        $shop.find('option').eq(idx + 1).text(shop.name);
      });
      shopsLoaded = true;
    });
  }

  function loadProducts() {
    var shopId = $shop.val();
    if (!shopId) {
      $list.html('<p class="text-muted" style="padding:12px;">{{ trans('app.select_store_to_load_products') }}</p>');
      $search.prop('disabled', true).val('');
      draft = {};
      updateConfirm();
      return;
    }
    $search.prop('disabled', false);
    $list.html('<p class="text-muted" style="padding:12px;">{{ trans('app.loading') }}</p>');
    $.getJSON(productsUrl, { shop_id: shopId, q: $search.val() }).then(function (res) {
      draft = {};
      updateConfirm();
      renderList(res.data || []);
    }).fail(function () {
      $list.html('<p class="text-danger" style="padding:12px;">{{ trans('app.failed_to_load_products') }}</p>');
    });
  }

  $open.on('click', function () {
    draft = {};
    updateConfirm();
    $modal.modal('show');
    if (!shopsLoaded) {
      loadShops();
    }
  });

  $shop.on('change', loadProducts);

  $search.on('input', function () {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(loadProducts, 250);
  });

  $list.on('change', 'input', function () {
    var $row = $(this).closest('.product-picker-row');
    var item = $row.data('item');
    if (!item) return;
    var id = String(item.id);
    var already = selectedIds();

    // Unchecking an already-selected product removes it from the saved chips.
    if (!this.checked && already[id]) {
      $selected.find('.product-picker-chip[data-id="' + id + '"]').remove();
      renderSelectedEmpty();
      $row.removeClass('is-selected');
      $row.find('.selected-badge').remove();
      delete draft[id];
      updateConfirm();
      return;
    }

    if (mode === 'single') {
      draft = {};
      if (this.checked) draft[item.id] = item;
    } else {
      if (this.checked) draft[item.id] = item;
      else delete draft[item.id];
    }
    $row.toggleClass('is-selected', this.checked);
    updateConfirm();
  });

  $confirm.on('click', function () {
    Object.keys(draft).forEach(function (id) {
      addChip(draft[id]);
    });
    $modal.modal('hide');
  });

  $selected.on('click', '.product-picker-remove', function () {
    $(this).closest('.product-picker-chip').remove();
    renderSelectedEmpty();
  });
})(jQuery);
</script>
@endpush
