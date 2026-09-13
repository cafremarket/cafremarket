@php
  $__popup = get_matching_web_popup();
@endphp

@if ($__popup)
  <style>
    #dynamicPopupModal.in,
    #dynamicPopupModal.show {
      display: flex !important;
      align-items: center;
      justify-content: center;
      padding: 24px 12px !important;
    }

    #dynamicPopupModal .dynamic-popup__dialog {
      position: relative;
      width: 92%;
      max-width: 620px;
      margin: 0 auto;
    }

    #dynamicPopupModal .dynamic-popup__content {
      position: relative;
      max-height: 88vh;
      overflow-y: auto;
      border-radius: 12px;
      border: none;
      padding: 0;
    }

    #dynamicPopupModal .dynamic-popup__image {
      display: block;
      width: 100%;
      max-height: 340px;
      object-fit: cover;
    }

    #dynamicPopupModal .dynamic-popup__image-link {
      display: block;
      cursor: pointer;
    }

    #dynamicPopupModal .dynamic-popup__body {
      padding: 24px 28px 28px;
    }

    #dynamicPopupModal .dynamic-popup__body h4 {
      margin: 0 0 10px;
      font-size: 22px;
      font-weight: 700;
    }

    #dynamicPopupModal .dynamic-popup__body p {
      margin: 0 0 18px;
      font-size: 15px;
    }

    #dynamicPopupModal .close {
      position: absolute;
      top: 10px;
      right: 10px;
      z-index: 2;
      width: 32px;
      height: 32px;
      line-height: 32px;
      border-radius: 50%;
      background: rgba(0, 0, 0, .55);
      color: #fff;
      opacity: 1;
      text-align: center;
      font-size: 20px;
    }

    #dynamicPopupModal .close:hover {
      background: rgba(0, 0, 0, .75);
      color: #fff;
    }

    @media (max-width: 480px) {
      #dynamicPopupModal .dynamic-popup__dialog {
        width: 94%;
        margin: 0 auto;
      }

      #dynamicPopupModal .dynamic-popup__image {
        max-height: 220px;
      }
    }
  </style>

  <div class="modal fade" id="dynamicPopupModal" tabindex="-1" role="dialog" aria-labelledby="dynamicPopupLabel" aria-hidden="true"
       data-popup-id="{{ $__popup->id }}" data-popup-frequency="{{ $__popup->frequency }}" data-popup-delay="{{ $__popup->delay_ms }}">
    <div class="modal-dialog dynamic-popup__dialog" role="document">
      <div class="modal-content dynamic-popup__content" @if ($__popup->bg_color) style="background-color: {{ $__popup->bg_color }};" @endif>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>

        @if (optional($__popup->featureImage)->path)
          @php $__popupImageSrc = get_storage_file_url($__popup->featureImage->path, 'full'); @endphp

          @if ($__popup->hide_text && $__popup->button_link)
            <a href="{{ $__popup->button_link }}" class="dynamic-popup__image-link">
              <img src="{{ $__popupImageSrc }}" class="dynamic-popup__image" alt="{{ $__popup->headline }}">
            </a>
          @else
            <img src="{{ $__popupImageSrc }}" class="dynamic-popup__image" alt="{{ $__popup->headline }}">
          @endif
        @endif

        @unless ($__popup->hide_text)
          <div class="dynamic-popup__body text-center">
            @if ($__popup->headline)
              <h4 id="dynamicPopupLabel">{{ $__popup->headline }}</h4>
            @endif

            @if ($__popup->description)
              <p>{{ $__popup->description }}</p>
            @endif

            @if ($__popup->button_label && $__popup->button_link)
              <a href="{{ $__popup->button_link }}" class="btn btn-primary">{{ $__popup->button_label }}</a>
            @endif
          </div>
        @endunless
      </div>
    </div>
  </div>
@endif
