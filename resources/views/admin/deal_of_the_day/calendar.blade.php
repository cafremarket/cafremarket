@extends('admin.layouts.master')

@section('content')
  @include('admin.partials.ui.card_start', [
    'title' => trans('app.deal_of_the_day'),
    'icon' => 'fa-calendar',
  ])

  <div class="row" style="margin-bottom: 16px;">
    <div class="col-sm-6">
      <a href="{{ route('admin.dealOfTheDay', ['month' => $prevMonth]) }}" class="btn btn-default btn-sm">
        <i class="fa fa-chevron-left"></i> Previous
      </a>
      <strong style="margin: 0 12px;">{{ $cursor->format('F Y') }}</strong>
      <a href="{{ route('admin.dealOfTheDay', ['month' => $nextMonth]) }}" class="btn btn-default btn-sm">
        Next <i class="fa fa-chevron-right"></i>
      </a>
    </div>
    <div class="col-sm-6 text-right">
      <span class="text-muted">Plan multiple products per day. Select store, tick products, save.</span>
    </div>
  </div>

  <div class="table-responsive">
    <table class="table table-bordered deal-calendar-table">
      <thead>
        <tr>
          <th>Sun</th><th>Mon</th><th>Tue</th><th>Wed</th><th>Thu</th><th>Fri</th><th>Sat</th>
        </tr>
      </thead>
      <tbody>
        @php $day = $start->copy(); @endphp
        @while ($day->lte($end))
          <tr>
            @for ($i = 0; $i < 7; $i++)
              @php
                $key = $day->format('Y-m-d');
                $inMonth = $day->month === $cursor->month;
                $dayDeals = $deals->get($key, collect());
                $isToday = $day->isToday();
                $inventoryIds = $dayDeals->pluck('inventory_id')->values()->all();
                $productMeta = $dayDeals->map(function ($deal) {
                  if (! $deal->inventory) {
                    return null;
                  }
                  $inv = $deal->inventory;
                  return [
                    'id' => (int) $inv->id,
                    'title' => $inv->product->name ?? $inv->title ?? ('#'.$inv->id),
                    'shop' => $inv->shop->name ?? '',
                    'shop_id' => (int) ($inv->shop_id ?? 0),
                    'sku' => $inv->sku ?? '',
                  ];
                })->filter()->values()->all();
              @endphp
              <td class="deal-day {{ $inMonth ? '' : 'muted' }} {{ $isToday ? 'today' : '' }}"
                  data-date="{{ $key }}"
                  data-inventory-ids="{{ json_encode($inventoryIds) }}"
                  data-products="{{ e(json_encode($productMeta)) }}">
                <div class="deal-day-header">
                  <strong>{{ $day->day }}</strong>
                  @if ($isToday)<span class="label label-info">Today</span>@endif
                </div>
                @if ($dayDeals->isNotEmpty())
                  <div class="deal-day-count">{{ $dayDeals->count() }} product{{ $dayDeals->count() > 1 ? 's' : '' }}</div>
                  @foreach ($dayDeals->take(3) as $deal)
                    @if ($deal->inventory)
                      <div class="deal-day-product" title="{{ $deal->inventory->title }}">
                        {{ Str::limit($deal->inventory->product->name ?? $deal->inventory->title, 36) }}
                      </div>
                    @endif
                  @endforeach
                  @if ($dayDeals->count() > 3)
                    <div class="text-muted small">+{{ $dayDeals->count() - 3 }} more</div>
                  @endif
                @else
                  <div class="text-muted small">Click to assign</div>
                @endif
              </td>
              @php $day->addDay(); @endphp
            @endfor
          </tr>
        @endwhile
      </tbody>
    </table>
  </div>

  <h4 style="margin-top: 24px;">Upcoming planned deals</h4>
  @if ($upcoming->isEmpty())
    <p class="text-muted">No upcoming deals scheduled.</p>
  @else
    <table class="table table-striped">
      <thead>
        <tr>
          <th>Date</th>
          <th>Products</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        @foreach ($upcoming as $dateKey => $rows)
          <tr>
            <td style="white-space:nowrap;">{{ \Carbon\Carbon::parse($dateKey)->format('Y-m-d (D)') }}</td>
            <td>
              <ul class="list-unstyled mb-0">
                @foreach ($rows as $row)
                  <li>
                    {{ $row->inventory->product->name ?? ($row->inventory->title ?? '—') }}
                    <span class="text-muted">· {{ $row->inventory->shop->name ?? '—' }}</span>
                  </li>
                @endforeach
              </ul>
            </td>
            <td class="text-right">
              <button type="button" class="btn btn-xs btn-danger js-clear-deal" data-date="{{ $dateKey }}">
                Clear
              </button>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  @endif

  @include('admin.partials.ui.card_end')

  <div class="modal fade" id="dealAssignModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
          <h4 class="modal-title">Assign Deal of the Day</h4>
        </div>
        <div class="modal-body">
          <p>Date: <strong id="deal-modal-date-label"></strong></p>
          <input type="hidden" id="deal-modal-date" value="">

          <div class="form-group">
            <label>Selected products</label>
            <div id="deal-modal-selected" class="deal-selected-chips">
              <p class="text-muted mb-0">None selected yet.</p>
            </div>
          </div>

          <div class="form-group">
            <label>1. Select store</label>
            <select class="form-control" id="deal-modal-shop">
              <option value="">— Choose a store —</option>
            </select>
          </div>

          <div class="form-group">
            <label>2. Search / choose products (multiple)</label>
            <input type="text" class="form-control" id="deal-modal-search" placeholder="Filter by name or SKU…" disabled>
          </div>

          <div class="product-picker-list" id="deal-modal-list">
            <p class="text-muted" style="padding:12px;">Select a store to load products.</p>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-danger" id="deal-modal-clear">Clear day</button>
          <button type="button" class="btn btn-primary" id="deal-modal-save" disabled>Save</button>
        </div>
      </div>
    </div>
  </div>
@endsection

@section('page-style')
<style>
  .deal-calendar-table td.deal-day {
    width: 14.28%;
    height: 120px;
    vertical-align: top;
    cursor: pointer;
    background: #fff;
  }
  .deal-calendar-table td.deal-day:hover { background: #f7f9fc; }
  .deal-calendar-table td.deal-day.muted { background: #f5f5f5; color: #999; }
  .deal-calendar-table td.deal-day.today { outline: 2px solid #3c8dbc; }
  .deal-day-header { display: flex; justify-content: space-between; margin-bottom: 4px; }
  .deal-day-count { font-size: 11px; color: #3c8dbc; font-weight: 600; margin-bottom: 4px; }
  .deal-day-product { font-weight: 600; font-size: 11px; line-height: 1.25; margin-bottom: 2px; }
  .product-picker-list {
    max-height: 320px;
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
  .deal-selected-chips { min-height: 36px; }
  .deal-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #f4f6f9;
    border: 1px solid #d2d6de;
    border-radius: 4px;
    padding: 4px 8px;
    margin: 0 6px 6px 0;
    max-width: 100%;
    font-size: 12px;
  }
  .product-picker-row.is-selected {
    background: #eef7ff;
  }
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
  .deal-chip-label {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    max-width: 420px;
  }
  .deal-chip button {
    border: 0;
    background: transparent;
    font-size: 16px;
    line-height: 1;
    padding: 0 2px;
    color: #999;
  }
</style>
@endsection

@section('page-script')
<script>
  (function ($) {
    var assignUrl = @json(route('admin.dealOfTheDay.assign'));
    var clearUrl = @json(route('admin.dealOfTheDay.clear'));
    var shopsUrl = @json(route('admin.productPicker.shops'));
    var productsUrl = @json(route('admin.productPicker.products'));
    var csrf = $('meta[name="csrf-token"]').attr('content');

    var shopsLoaded = false;
    var chosen = {}; // id -> {id, title, shop, sku, price}
    var searchTimer = null;
    var productCache = {};

    function chosenIds() {
      return Object.keys(chosen);
    }

    function setSaveEnabled() {
      $('#deal-modal-save').prop('disabled', chosenIds().length === 0);
    }

    function chipLabel(item) {
      var title = item.title || ('#' + item.id);
      var parts = [title];
      if (item.shop) parts.push(item.shop);
      if (item.sku) parts.push(item.sku);
      return parts.join(' · ');
    }

    function currentShopName() {
      var $opt = $('#deal-modal-shop option:selected');
      var val = $('#deal-modal-shop').val();
      return val ? ($opt.text() || '') : '';
    }

    function renderSelected() {
      var $box = $('#deal-modal-selected');
      var ids = chosenIds();
      if (!ids.length) {
        $box.html('<p class="text-muted mb-0">None selected yet.</p>');
        setSaveEnabled();
        return;
      }
      var html = '';
      ids.forEach(function (id) {
        html += '<span class="deal-chip" data-id="' + id + '">' +
          '<span class="deal-chip-label"></span>' +
          '<button type="button" aria-label="Remove">&times;</button></span>';
      });
      $box.html(html);
      $box.find('.deal-chip').each(function () {
        var id = String($(this).data('id'));
        $(this).find('.deal-chip-label').text(chipLabel(chosen[id] || { id: id }));
      });
      setSaveEnabled();
    }

    function loadShops() {
      return $.getJSON(shopsUrl).then(function (res) {
        var $shop = $('#deal-modal-shop');
        $shop.html('<option value="">— Choose a store —</option>');
        (res.data || []).forEach(function (shop) {
          $shop.append($('<option></option>').val(shop.id).text(shop.name));
        });
        shopsLoaded = true;
      });
    }

    function renderProducts(items) {
      var $list = $('#deal-modal-list');
      var shopName = currentShopName();
      if (!items.length) {
        $list.html('<p class="text-muted" style="padding:12px;">No products found for this store.</p>');
        return;
      }
      var html = '';
      items.forEach(function (item) {
        var id = String(item.id);
        item.shop = item.shop || shopName;
        productCache[id] = item;
        var isChosen = !!chosen[id];
        html += '<label class="product-picker-row' + (isChosen ? ' is-selected' : '') + '">' +
          '<input type="checkbox" name="deal-product" value="' + item.id + '"' + (isChosen ? ' checked' : '') + '>' +
          '<img src="' + (item.image || '') + '" alt="">' +
          '<div class="meta"><strong></strong><small></small></div>' +
          '<div class="price"></div>' +
          '</label>';
      });
      $list.html(html);
      $list.find('.product-picker-row').each(function (i) {
        var item = items[i];
        var $strong = $(this).find('strong');
        $strong.text(item.title || '');
        if (chosen[String(item.id)]) {
          $strong.append(' <span class="selected-badge">Selected</span>');
        }
        var metaBits = [];
        if (item.sku) metaBits.push(item.sku);
        if (shopName) metaBits.push(shopName);
        $(this).find('small').text(metaBits.join(' · '));
        $(this).find('.price').text(item.price || '');
      });
    }

    function loadProducts() {
      var shopId = $('#deal-modal-shop').val();
      if (!shopId) {
        $('#deal-modal-search').prop('disabled', true).val('');
        $('#deal-modal-list').html('<p class="text-muted" style="padding:12px;">Select a store to load products.</p>');
        return;
      }
      $('#deal-modal-search').prop('disabled', false);
      $('#deal-modal-list').html('<p class="text-muted" style="padding:12px;">Loading…</p>');
      $.getJSON(productsUrl, { shop_id: shopId, q: $('#deal-modal-search').val() })
        .then(function (res) { renderProducts(res.data || []); })
        .fail(function () {
          $('#deal-modal-list').html('<p class="text-danger" style="padding:12px;">Failed to load products.</p>');
        });
    }

    function openModal(date, existingProducts) {
      $('#deal-modal-date').val(date);
      $('#deal-modal-date-label').text(date);
      chosen = {};
      var firstShopId = null;
      (existingProducts || []).forEach(function (item) {
        if (!item || !item.id) return;
        var id = String(item.id);
        chosen[id] = {
          id: id,
          title: item.title || '',
          shop: item.shop || '',
          shop_id: item.shop_id || '',
          sku: item.sku || '',
          price: item.price || ''
        };
        if (!firstShopId && item.shop_id) {
          firstShopId = String(item.shop_id);
        }
      });
      renderSelected();
      $('#deal-modal-search').val('');
      $('#dealAssignModal').modal('show');

      var afterShops = function () {
        if (firstShopId) {
          $('#deal-modal-shop').val(firstShopId);
          loadProducts();
        } else {
          $('#deal-modal-shop').val('');
          $('#deal-modal-search').prop('disabled', true);
          $('#deal-modal-list').html('<p class="text-muted" style="padding:12px;">Select a store to load products.</p>');
        }
      };

      if (!shopsLoaded) {
        loadShops().then(afterShops);
      } else {
        afterShops();
      }
    }

    $(document).on('click', 'td.deal-day', function () {
      var raw = $(this).attr('data-products');
      var products = [];
      try { products = JSON.parse(raw || '[]') || []; } catch (e) { products = []; }
      openModal($(this).data('date'), products);
    });

    $('#deal-modal-shop').on('change', loadProducts);
    $('#deal-modal-search').on('input', function () {
      clearTimeout(searchTimer);
      searchTimer = setTimeout(loadProducts, 250);
    });

    $('#deal-modal-list').on('change', 'input[name="deal-product"]', function () {
      var id = String($(this).val());
      if ($(this).is(':checked')) {
        var item = productCache[id] || {};
        chosen[id] = {
          id: id,
          title: item.title || '',
          shop: item.shop || currentShopName(),
          sku: item.sku || '',
          price: item.price || ''
        };
      } else {
        delete chosen[id];
      }
      renderSelected();
    });

    $('#deal-modal-selected').on('click', '.deal-chip button', function (e) {
      e.preventDefault();
      var id = String($(this).closest('.deal-chip').data('id'));
      delete chosen[id];
      renderSelected();
      $('#deal-modal-list input[value="' + id + '"]').prop('checked', false);
    });

    $('#deal-modal-save').on('click', function () {
      var date = $('#deal-modal-date').val();
      var ids = chosenIds();
      if (!ids.length) {
        alert('Please select at least one product.');
        return;
      }
      $.ajax({
        url: assignUrl,
        method: 'POST',
        data: { _token: csrf, deal_date: date, inventory_ids: ids },
        success: function () { window.location.reload(); },
        error: function (xhr) {
          alert((xhr.responseJSON && xhr.responseJSON.message) || 'Failed to save deal.');
        }
      });
    });

    function clearDeal(date) {
      if (!confirm('Clear all products for ' + date + '?')) return;
      $.ajax({
        url: clearUrl,
        method: 'POST',
        data: { _token: csrf, deal_date: date },
        success: function () { window.location.reload(); },
        error: function () { alert('Failed to clear deal.'); }
      });
    }

    $('#deal-modal-clear').on('click', function () {
      clearDeal($('#deal-modal-date').val());
    });

    $(document).on('click', '.js-clear-deal', function (e) {
      e.preventDefault();
      clearDeal($(this).data('date'));
    });
  })(jQuery);
</script>
@endsection
