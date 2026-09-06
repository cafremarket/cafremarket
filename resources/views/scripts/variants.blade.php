<script type="text/javascript">
  ;
  (function($, window, document) {
    $(document).ready(function() {
      function panelUrl(url) {
        return (typeof window.toPanelUrl === 'function') ? window.toPanelUrl(url) : url;
      }

      function loadAttributeOptions(opts) {
        opts = opts || {};
        var selectedCategoryIds = $('select[name="category_list[]"]').val();
        var selectedAttributeIds = $('#product_attrs_list').val() || $('select[name="attrsList[]"]').val();

        $.ajax({
          url: panelUrl('{{ route('admin.stock.product.getAttributesByCategories') }}'),
          type: 'GET',
          data: {
            category_ids: selectedCategoryIds,
            attribute_ids: selectedAttributeIds
          },
          success: function(data) {
            $('#attributesFieldset').html(data);

            $(".select2-set_attribute").select2({
              placeholder: "{{ trans('app.choose_attributes') }}",
            });

            if (($('#attributesFieldset').find('.select2-set_attribute').length > 0)) {
              $('#set-variant-btn-block').removeClass('hide');
            } else {
              $('#set-variant-btn-block').addClass('hide');
              if (!opts.silent) {
                $('#attributesFieldset').html('<h4 class="text-center text-muted">{{ trans('help.select_category_or_attributes') }}</h4>');
              }
            }
          },
          error: function(error) {
            console.error('Error fetching attributes:', error);
          }
        });
      }

      // Load when categories change
      $('select[name="category_list[]"]').on('change', function() {
        loadAttributeOptions();
      });

      // Load when product attribute presets change
      $(document).on('change', '#product_attrs_list, select[name="attrsList[]"]', function() {
        loadAttributeOptions();
      });

      $('#reloadProductAttributes').on('click', function(e) {
        e.preventDefault();
        $('#wc_product_type').val('variable').trigger('change');
        loadAttributeOptions();
      });

      // Set Combinations — each row becomes a separate inventory (SKU / stock / price / image)
      $("#setCombinations").on("click", function(e) {
        e.preventDefault();

        var options = {};
        var all_ok = true;
        $(".select2-set_attribute").each(function(indx, attrs) {
          var attrID = $(this).attr('id');
          var attrValues = $(attrs).val();

          if (!attrValues || attrValues.length == 0) {
            all_ok = false;
            return false;
          }

          options[attrID] = attrValues;
        });

        if (all_ok == false) {
          $("#set-variant-btn-block > p").addClass('text-danger lead');
          $("#set-variant-btn-block > button").addClass('btn-danger');
          return false;
        } else {
          $("#set-variant-btn-block > p").removeClass('text-danger lead');
          $("#set-variant-btn-block > button").removeClass('btn-danger');
        }

        $.ajax({
          url: panelUrl("{{ route('admin.stock.product.getCombinations') }}"),
          data: options,
          async: false,
          success: function(variants) {
            $('#combinationsPlaceholder').html(variants);
            $('a[href="#wc_tab_attributes"]').tab('show');

            if ($('#combinationsPlaceholder').offset()) {
              $('html, body').animate({
                scrollTop: $('#combinationsPlaceholder').offset().top - 80
              }, 300);
            }
          }
        });

        var sku = $('input[name="sku"]').val();
        var price = $('input[name="sale_price"]').val();
        var purchasePrice = $('input[name="purchase_price"]').val();
        var quantity = $('input[name="stock_quantity"]').val();

        $("tr.variant-row").each(function(indx, row) {
          if (sku) {
            $(this).find('.variant-sku').val(sku + '-' + (indx + 1));
          }

          if (price) {
            $(this).find('.variant-price').val(price);
          }

          if (purchasePrice) {
            $(this).find('.variant-purchase-price').val(purchasePrice);
          }

          $(this).find('.variant-qtt').val(quantity);

          updateVariantRowSummary($(row));
        });
      });

      // Preview image on select
      $('body').on('change', '.variant-img', function() {
        var img = $(this).next("img");
        var reader = new FileReader();
        reader.onload = function(e) {
          img.attr("src", e.target.result);
        };
        reader.readAsDataURL(this.files[0]);
      });

                $('body').on('click', '.deleteThisRow', function() {
        var row = $(this).closest('tr');
        var wasDefault = row.find('input[name="default_variant"]').is(':checked');
        row.remove();
        if (wasDefault) {
          var firstRadio = $('#variantsTable input[name="default_variant"]').first();
          if (firstRadio.length) {
            firstRadio.prop('checked', true);
          }
        }
      });

      // ===== Manage-variant modal: reads/writes a row's real .variant-fields inputs =====
      var $variantManageModal = $('#variantManageModal');
      var $variantModalSlot = $variantManageModal.find('.variant-manage-modal__slot');
      var currentVariantRow = null;

      function currencySymbol() {
        return {!! json_encode(get_currency_prefix() ?: config('system_settings.currency.symbol', '$')) !!};
      }

      function updateVariantRowSummary($row) {
        var $fields = $row.find('.variant-fields');
        if (!$fields.length) return;

        var sku = $fields.find('.variant-sku').val();
        var qty = $fields.find('.variant-qtt').val();
        var price = $fields.find('.variant-price').val();
        var offerEnabled = $fields.find('.variant-offer-toggle').attr('data-enabled') === '1';
        var offerPrice = $fields.find('.variant-offer-price').val();
        var imgSrc = $fields.find('.variant-img-preview').attr('src');

        $row.find('.variant-summary-sku').text(sku || '—');
        $row.find('.variant-summary-qty').text((qty !== undefined && qty !== '') ? qty : '—');
        $row.find('.variant-summary-price').text((price !== undefined && price !== '') ? currencySymbol() + parseFloat(price).toFixed(2) : '—');
        if (imgSrc) {
          $row.find('.variant-summary-thumb').attr('src', imgSrc);
        }

        if (offerEnabled && offerPrice) {
          $row.find('.variant-summary-offer').removeClass('hide').text(currencySymbol() + parseFloat(offerPrice).toFixed(2));
          $row.find('.variant-summary-offer-empty').addClass('hide');
        } else {
          $row.find('.variant-summary-offer').addClass('hide').text('');
          $row.find('.variant-summary-offer-empty').removeClass('hide');
        }
      }

      function syncOfferToggleVisibility($fields, enabled) {
        var $toggle = $fields.find('.variant-offer-toggle');
        if (enabled === undefined) {
          enabled = $toggle.attr('data-enabled') === '1';
        }
        $fields.find('.variant-offer-fields').toggleClass('hide', !enabled);
        if (!enabled) {
          $fields.find('.variant-offer-price').val('');
        }
      }

      $(document).on('click', '.variant-offer-toggle', function(e) {
        e.preventDefault();
        var $btn = $(this);
        var enabled = !($btn.attr('data-enabled') === '1');

        $btn.attr('data-enabled', enabled ? '1' : '0');
        $btn.toggleClass('btn-primary', enabled).toggleClass('btn-default', !enabled);
        $btn.find('i').toggleClass('fa-toggle-on', enabled).toggleClass('fa-toggle-off', !enabled);

        syncOfferToggleVisibility($btn.closest('.variant-fields'), enabled);
      });

      $(document).on('click', '.manageVariantBtn', function(e) {
        e.preventDefault();
        var $row = $(this).closest('tr.variant-row');
        var $fields = $row.find('.variant-fields');
        if (!$fields.length) return;

        currentVariantRow = $row;
        $variantModalSlot.empty().append($fields);
        $variantManageModal.find('.variant-manage-modal__attrs').html($row.find('.variant-attrs-label').html() || '');
      });

      $variantManageModal.on('hidden.bs.modal', function() {
        if (!currentVariantRow) return;
        var $fields = $variantModalSlot.children('.variant-fields');
        currentVariantRow.append($fields.detach());
        updateVariantRowSummary(currentVariantRow);
        currentVariantRow = null;
      });

      // Initial summary paint for existing variants (server-rendered rows)
      $('#variantsTable tr.variant-row').each(function() {
        updateVariantRowSummary($(this));
      });

      // Prefetch options if categories/attributes already selected (edit form)
      if (($('select[name="category_list[]"]').val() || []).length || ($('#product_attrs_list').val() || []).length) {
        loadAttributeOptions({ silent: true });
      }

      // Keep Attributes-tab pricing preview in sync with Basic Info fields
      function syncAttrPricingPreview() {
        var sku = $('input[name="sku"]').first().val() || '—';
        var price = $('input[name="sale_price"]').first().val() || '—';
        var qty = $('input[name="stock_quantity"]').first().val() || '—';
        $('#attr-tab-sku-preview').text(sku);
        $('#attr-tab-price-preview').text(price);
        $('#attr-tab-qty-preview').text(qty);
      }
      $(document).on('input change', 'input[name="sku"], input[name="sale_price"], input[name="stock_quantity"]', syncAttrPricingPreview);
      syncAttrPricingPreview();

      // WooCommerce-style Simple vs Variable product toggle
      function applyProductType(type, switchTab) {
        var isVariable = type === 'variable';
        $('.wc-tab-attributes').toggleClass('hide', !isVariable);
        $('.wc-tab-general').toggleClass('hide', isVariable);
        $('.wc-simple-only').toggleClass('hide', isVariable);
        $('.wc-variable-only').toggleClass('hide', !isVariable);

        // Variable products price/offer per variant (in the manage modal) — the shared
        // General-tab fields no longer apply, so keep them from leaking into the submit.
        if (isVariable) {
          $('#wc_tab_general input[name="offer_price"]').val('');
        }

        if (switchTab) {
          if (isVariable) {
            $('a[href="#wc_tab_attributes"]').tab('show');
            loadAttributeOptions({ silent: true });
          } else {
            $('a[href="#wc_tab_general"]').tab('show');
          }
        }
      }
      $('#wc_product_type').on('change', function() {
        applyProductType($(this).val(), true);
      });
      applyProductType($('#wc_product_type').val() || 'simple', false);
    });
  }(window.jQuery, window, document));
</script>
