{{-- Single reusable modal used to manage a variant's general info (image, sku, price, stock, offer pricing).
     JS moves the relevant row's real form fields into `.variant-manage-modal__slot` while the modal is open,
     then moves them back into the row (hidden) when the modal closes, so the outer product form still
     submits every variant's data on save. --}}
<div class="modal fade variant-manage-modal" id="variantManageModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title">
          {{ trans('app.variant_details') }}
          <span class="variant-manage-modal__attrs"></span>
        </h4>
      </div>
      <div class="modal-body">
        <div class="variant-manage-modal__slot"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" data-dismiss="modal">{{ trans('app.done') }}</button>
      </div>
    </div>
  </div>
</div>

<style>
  .variant-manage-modal .variant-fields__image { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; }
  .variant-manage-modal .variant-fields__image img { width: 72px; height: 72px; object-fit: cover; border-radius: 6px; border: 1px solid #e3e6f0; }
  .variant-manage-modal .variant-offer-section { margin-top: 18px; padding: 14px 16px; background: #f8f9fc; border: 1px dashed #d7dcf0; border-radius: 6px; }
  .variant-manage-modal .variant-offer-section__title { font-weight: 600; margin-bottom: 10px; display: flex; align-items: center; gap: 8px; }
  .variant-manage-modal .variant-offer-fields.hide { display: none; }
  .variant-manage-modal .modal-title .variant-manage-modal__attrs { font-size: 13px; color: #858796; font-weight: normal; margin-left: 6px; }

  /* Simplified variant summary rows (product page) */
  #variantsTable .variant-summary-thumb { width: 40px; height: 40px; object-fit: cover; border-radius: 4px; border: 1px solid #e3e6f0; }
  #variantsTable .variant-summary-offer { display: inline-block; margin-top: 4px; font-size: 11px; padding: 2px 6px; border-radius: 3px; background: #fdecea; color: #c0392b; }
  #variantsTable .variant-summary-offer.hide { display: none; }
  #variantsTable .variant-fields { display: none; }
</style>
