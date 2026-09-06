@php
  $shareUrl = isset($item) ? storefront_product_url($item) : Request::fullUrl();
  $shareTitle = $item->meta_title ?? $item->title ?? '';
  $shareDesc = $item->meta_description
    ?? \Illuminate\Support\Str::limit(trim(strip_tags($item->description ?? '')), 160, '');
  $shareImage = get_product_img_src($item, 'full');
  $sharePrice = get_formated_currency($item->current_sale_price());
  $shareText = trim($shareTitle . ($shareDesc ? ' — ' . $shareDesc : '') . ' ' . $shareUrl);
  $shareTriggerOnly = $shareTriggerOnly ?? false;
  $shareModalOnly = $shareModalOnly ?? false;
@endphp

@unless ($shareModalOnly)
  <a href="javascript:void(0);"
    class="btn btn-link sf-share__trigger"
    data-toggle="modal"
    data-target="#productShareModal"
    role="button"
    aria-label="{{ trans('theme.share') }}"
    title="{{ trans('theme.share') }}">
    <i class="fa fa-share-alt" aria-hidden="true"></i> {{ trans('theme.share') }}
  </a>
@endunless

@unless ($shareTriggerOnly)
<div class="modal fade" id="productShareModal" tabindex="-1" role="dialog" aria-labelledby="productShareModalTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content sf-share-modal">
      <div class="modal-header">
        <h4 class="modal-title" id="productShareModalTitle">
          <i class="fa fa-share-alt"></i> {{ trans('theme.share_product') }}
        </h4>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <div class="modal-body">
        <div class="sf-share-modal__product">
          <div class="sf-share-modal__thumb">
            <img src="{{ $shareImage }}" alt="{{ $shareTitle }}">
          </div>
          <div class="sf-share-modal__meta">
            <h5 class="sf-share-modal__name">{{ $shareTitle }}</h5>
            @if ($shareDesc)
              <p class="sf-share-modal__desc">{{ $shareDesc }}</p>
            @endif
            <div class="sf-share-modal__price">{{ $sharePrice }}</div>
          </div>
        </div>

        <label class="sf-share-modal__label" for="productShareLink">{{ trans('theme.product_link') }}</label>
        <div class="sf-share-modal__link-row">
          <input type="text"
            id="productShareLink"
            class="form-control"
            value="{{ $shareUrl }}"
            readonly
            aria-label="{{ trans('theme.product_link') }}">
          <button type="button" class="btn btn-primary sf-share-modal__copy" data-copy-target="#productShareLink">
            <i class="fa fa-copy"></i> {{ trans('theme.copy_link') }}
          </button>
        </div>
        <p class="sf-share-modal__copied text-success hide" role="status">{{ trans('theme.link_copied') }}</p>

        <div class="sf-share-modal__actions">
          <a class="sf-share-modal__action social-share-btn"
            href="https://wa.me/?text={{ rawurlencode($shareText) }}"
            target="_blank" rel="noopener noreferrer">
            <i class="fa fa-whatsapp"></i>
            <span>WhatsApp</span>
          </a>
          <a class="sf-share-modal__action social-share-btn"
            href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($shareUrl) }}"
            target="_blank" rel="noopener noreferrer">
            <i class="fa fa-facebook"></i>
            <span>Facebook</span>
          </a>
          <a class="sf-share-modal__action social-share-btn"
            href="https://twitter.com/intent/tweet?{{ http_build_query(['url' => $shareUrl, 'text' => $shareTitle]) }}"
            target="_blank" rel="noopener noreferrer">
            <i class="fa fa-twitter"></i>
            <span>X</span>
          </a>
          <a class="sf-share-modal__action"
            href="mailto:?subject={{ rawurlencode($shareTitle) }}&body={{ rawurlencode($shareText) }}">
            <i class="fa fa-envelope"></i>
            <span>{{ trans('theme.email') }}</span>
          </a>
        </div>
      </div>
    </div>
  </div>
</div>
@endunless
