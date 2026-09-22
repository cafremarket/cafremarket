@extends('affiliate::backend.master_layout')

@section('page_title', trans('packages.affiliate.affiliate_links'))

@section('buttons')
  <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#createAffiliateLinkModal">
    <i class="fa fa-plus"></i> {{ trans('packages.affiliate.create_affiliate_link') }}
  </button>
@endsection

@section('page-style')
  <style>
    #affiliate-shop-info {
      display: none;
      margin-top: 10px;
      padding: 12px 14px;
      background: #f8fafc;
      border: 1px solid #e5e7eb;
      border-radius: 10px;
      font-size: 13px;
      line-height: 1.5;
    }
    #affiliate-shop-info.is-visible { display: block; }
    #affiliate-shop-info .shop-info-row { margin: 0 0 6px; }
    #affiliate-shop-info .shop-info-row:last-child { margin-bottom: 0; }
    #affiliate-shop-info .shop-info-label {
      color: #6b7280;
      min-width: 72px;
      display: inline-block;
      font-weight: 600;
    }
    .affiliate-variant-tag {
      display: inline-block;
      margin-left: 6px;
      padding: 2px 8px;
      border-radius: 999px;
      background: #eff6ff;
      color: #1d4ed8;
      font-size: 11px;
      font-weight: 600;
    }
  </style>
@endsection

@section('content')
  <div class="mp-panel">
    <div class="mp-panel__head">
      <div class="mp-panel__head-text">
        <h2>{{ trans('packages.affiliate.affiliate_links') }}</h2>
      </div>
    </div>
    <div class="mp-panel__body">
      <div class="table-responsive">
        <table id="affiliate-links-table" class="table table-hover admin-table">
          <thead>
            <tr>
              <th>{{ trans('app.shop') }}</th>
              <th>{{ trans('packages.affiliate.product') }}</th>
              <th>{{ trans('app.form.url') }}</th>
              <th>{{ trans('app.price') }}</th>
              <th>{{ trans('packages.affiliate.commission_rate') . ' (%)' }}</th>
              <th>{{ trans('theme.total_sold_quantity') }}</th>
              <th>{{ trans('packages.affiliate.visitors') }}</th>
              <th>{{ trans('app.options') }}</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($links as $link)
              @if ($link->inventory)
                @php
                  $variantLabel = $link->inventory->attributeValues
                    ->map(function ($value) {
                      $name = optional($value->attribute)->name;
                      return $name ? $name.': '.$value->value : $value->value;
                    })
                    ->filter()
                    ->implode(' · ');
                @endphp
                <tr>
                  <td>{{ optional($link->inventory->shop)->name }}</td>
                  <td>
                    {{ $link->inventory->title }}
                    @if ($variantLabel)
                      <span class="affiliate-variant-tag">{{ $variantLabel }}</span>
                    @elseif ($link->inventory->parent_id)
                      <span class="affiliate-variant-tag">{{ $link->inventory->sku }}</span>
                    @endif
                  </td>
                  <td>
                    <span class="js-affiliate-link-url">{{ $link->full_url }}</span>
                    <a href="{{ storefront_product_url($link->inventory) }}" class="ml-2" target="_blank" rel="noopener">
                      <i class="fa fa-external-link text-info" data-toggle="tooltip" title="{{ trans('packages.affiliate.go_to_product_page') }}"></i>
                    </a>
                    <a href="javascript:void(0)" class="pull-right" onclick="copyAffiliateLink(this)" data-key="copy-affiliate-link">
                      <em class="fa fa-clipboard"></em>
                    </a>
                  </td>
                  <td>{{ get_formated_currency($link->inventory->sale_price, 2) }}</td>
                  <td>{{ $link->inventory->affiliates_percentage }}</td>
                  <td>{{ $link->order_count }}</td>
                  <td>{{ $link->visitor_count }}</td>
                  <td>
                    <a href="{{ route('affiliate.link.commissions', $link) }}" data-toggle="tooltip" title="{{ trans('packages.affiliate.affiliate_commissions') }}">
                      <i class="fa fa-percent"></i>
                    </a>&nbsp;
                    {!! Form::open(['route' => ['affiliate.link.destroy', $link->id], 'method' => 'delete', 'class' => 'data-form']) !!}
                    {!! Form::button('<i class="text-muted fa fa-trash"></i>', ['type' => 'submit', 'class' => 'confirm ajax-silent', 'title' => trans('app.delete_permanently'), 'data-toggle' => 'tooltip']) !!}
                    {!! Form::close() !!}
                  </td>
                </tr>
              @endif
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="mp-panel">
    <div class="mp-panel__head">
      <div class="mp-panel__head-text">
        <h2>{{ trans('packages.affiliate.invalid_links') }}</h2>
        <p>{{ trans('packages.affiliate.item_not_available') }}</p>
      </div>
    </div>
    <div class="mp-panel__body">
      <div class="table-responsive">
        <table id="affiliate-invalid-links-table" class="table table-hover admin-table">
          <thead>
            <tr>
              <th>{{ trans('app.slug') }}</th>
              <th class="text-center">{{ trans('theme.total_sold_quantity') }}</th>
              <th class="text-center">{{ trans('packages.affiliate.visitors') }}</th>
              <th class="text-center">{{ trans('app.options') }}</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($links as $link)
              @unless ($link->inventory)
                <tr>
                  <td>{{ $link->slug }}</td>
                  <td class="text-center">{{ $link->order_count }}</td>
                  <td class="text-center">{{ $link->visitor_count }}</td>
                  <td class="text-center">
                    {!! Form::open(['route' => ['affiliate.link.destroy', $link->id], 'method' => 'delete', 'class' => 'data-form']) !!}
                    {!! Form::button('<i class="text-muted fa fa-trash"></i>', ['type' => 'submit', 'class' => 'confirm ajax-silent', 'title' => trans('app.delete_permanently'), 'data-toggle' => 'tooltip']) !!}
                    {!! Form::close() !!}
                  </td>
                </tr>
              @endunless
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>

  @include('affiliate::backend._create_link_modal')
@endsection

@section('page-script')
  <script>
    (function () {
      function initAffiliateLinksTable(selector, emptyMessage, nonSortableTargets) {
        var $table = $(selector);
        if (!$table.length) {
          return;
        }

        if ($.fn.DataTable.isDataTable($table[0])) {
          $table.DataTable().destroy();
        }

        $table.DataTable({
          aaSorting: [],
          iDisplayLength: 10,
          oLanguage: {
            sInfo: '_START_ to _END_ of _TOTAL_ entries',
            sLengthMenu: 'Show _MENU_',
            sSearch: '',
            sEmptyTable: emptyMessage,
            oPaginate: {
              sNext: '<i class="fa fa-hand-o-right"></i>',
              sPrevious: '<i class="fa fa-hand-o-left"></i>',
            },
          },
          aoColumnDefs: [{
            bSortable: false,
            aTargets: nonSortableTargets
          }],
          dom: 'Bfrtip',
          buttons: ['copy', 'csv', 'excel', 'pdf', 'print']
        });
      }

      $(function () {
        initAffiliateLinksTable(
          '#affiliate-links-table',
          @json(trans('packages.affiliate.you_dont_have_any_links_yet')),
          [0, -1]
        );
        initAffiliateLinksTable(
          '#affiliate-invalid-links-table',
          '—',
          [0, -1]
        );
      });

      var shops = @json($shops);
      var shopSelect = document.getElementById('affiliate-shop');
      var productSelect = document.getElementById('affiliate-product');
      var meta = document.getElementById('affiliate-product-meta');
      var shopInfo = document.getElementById('affiliate-shop-info');
      var shopEmail = document.getElementById('affiliate-shop-email');
      var shopAddress = document.getElementById('affiliate-shop-address');
      var submit = document.getElementById('affiliate-create-link');
      var productsUrl = @json(route('affiliate.link.products'));
      var products = [];

      function resetProducts(message) {
        productSelect.innerHTML = '';
        var option = document.createElement('option');
        option.value = '';
        option.textContent = message;
        productSelect.appendChild(option);
        productSelect.disabled = true;
        submit.disabled = true;
        meta.textContent = '';
        products = [];
      }

      function renderShopInfo(shopId) {
        var shop = shops.find(function (item) {
          return String(item.id) === String(shopId);
        });

        if (!shop) {
          shopInfo.classList.remove('is-visible');
          shopEmail.textContent = '';
          shopAddress.textContent = '';
          return;
        }

        shopEmail.textContent = shop.email || @json(trans('packages.affiliate.store_email_unavailable'));
        shopAddress.textContent = shop.address || @json(trans('packages.affiliate.store_address_unavailable'));
        shopInfo.classList.add('is-visible');
      }

      function showSelectedProduct() {
        var selected = products.find(function (item) {
          return String(item.id) === String(productSelect.value);
        });

        if (!selected) {
          meta.textContent = '';
          submit.disabled = true;
          return;
        }

        var details = [selected.price, selected.commission];
        if (selected.variant) {
          details.unshift(selected.variant);
        } else if (selected.sku) {
          details.unshift('SKU: ' + selected.sku);
        }
        meta.textContent = details.join(' · ');
        submit.disabled = false;
      }

      function fillProducts(items) {
        products = items || [];
        productSelect.innerHTML = '';

        var placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = products.length
          ? @json(trans('packages.affiliate.select_product'))
          : @json(trans('packages.affiliate.no_products_for_store'));
        productSelect.appendChild(placeholder);

        var groups = {};
        products.forEach(function (item) {
          var key = item.group || item.title;
          if (!groups[key]) {
            groups[key] = [];
          }
          groups[key].push(item);
        });

        Object.keys(groups).forEach(function (groupName) {
          var groupItems = groups[groupName];
          var useGroup = groupItems.length > 1 || groupItems.some(function (item) { return item.is_variant; });

          if (useGroup) {
            var optgroup = document.createElement('optgroup');
            optgroup.label = groupName;
            groupItems.forEach(function (item) {
              var option = document.createElement('option');
              option.value = item.id;
              option.textContent = item.variant
                ? item.variant + (item.sku ? ' (' + item.sku + ')' : '')
                : (item.sku ? item.sku : item.title);
              optgroup.appendChild(option);
            });
            productSelect.appendChild(optgroup);
          } else {
            groupItems.forEach(function (item) {
              var option = document.createElement('option');
              option.value = item.id;
              option.textContent = item.title;
              productSelect.appendChild(option);
            });
          }
        });

        productSelect.disabled = products.length === 0;
        submit.disabled = true;
      }

      shopSelect.addEventListener('change', function () {
        renderShopInfo(shopSelect.value);

        if (!shopSelect.value) {
          resetProducts(@json(trans('packages.affiliate.select_store_first')));
          return;
        }

        resetProducts(@json(trans('packages.affiliate.loading_products')));

        fetch(productsUrl + '?shop_id=' + encodeURIComponent(shopSelect.value), {
          headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
          .then(function (response) { return response.json(); })
          .then(fillProducts)
          .catch(function () {
            resetProducts(@json(trans('packages.affiliate.products_load_failed')));
          });
      });

      productSelect.addEventListener('change', showSelectedProduct);

      $('#createAffiliateLinkModal').on('hidden.bs.modal', function () {
        shopSelect.value = '';
        renderShopInfo('');
        resetProducts(@json(trans('packages.affiliate.select_store_first')));
      });
    })();
  </script>
@endsection
