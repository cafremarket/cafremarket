@php
  use App\Models\Banner;

  $hideText = ! empty($banner['hide_text']);
  $hasLink = ! empty($banner['link']);
  $displayType = $banner['display_type'] ?? Banner::TYPE_SINGLE;
  $isColour = $displayType === Banner::TYPE_COLOUR;
  $imgPath = $banner['feature_image']['path'] ?? null;
  $imgOk = $imgPath && Storage::exists($imgPath);
  $bgColor = $banner['bg_color'] ?? '#f97316';
  $effectClass = ! empty($banner['effect']) ? 'outline-effect' : '';
  $imageOnlyClass = $hideText ? 'image-banner--image-only' : '';
  $colourClass = $isColour ? 'image-banner--colour' : '';
  $slideClass = ! empty($inSlider) ? 'homepage-banner-slide' : '';
@endphp

<div class="image-banner {{ $cols > 11 ? 'single-banner' : '' }} {{ $imageOnlyClass }} {{ $colourClass }} {{ $slideClass }}">
  @if ($hasLink)
    <a href="{{ $banner['link'] }}" class="banner-box-link" @if ($hideText) aria-label="{{ strip_tags($banner['title'] ?? 'Banner') }}" @endif>
  @endif
    <div class="banner-box {{ $effectClass }}"
         @if ($isColour) style="background-color: {{ $bgColor }};" @endif>
      @if (! $isColour)
        @if ($imgOk)
          <img class="lazy"
               src="{{ $cols >= 12 ? get_storage_file_url($imgPath, 'tiny') : '/images/loading.webp' }}"
               data-src="{{ get_storage_file_url($imgPath, 'full') }}"
               alt="{{ $hideText ? '' : ($banner['title'] ?? 'Banner Image') }}"
               title="{{ $hideText ? '' : ($banner['title'] ?? 'Banner Image') }}">
        @else
          <img src="{{ get_storage_file_url() }}" alt="{{ $banner['title'] ?? 'Banner Image' }}">
        @endif
      @elseif ($imgOk)
        <img class="lazy banner-box__colour-img"
             src="{{ get_storage_file_url($imgPath, 'tiny') }}"
             data-src="{{ get_storage_file_url($imgPath, 'full') }}"
             alt="{{ $hideText ? '' : ($banner['title'] ?? 'Banner Image') }}">
      @endif

      @unless ($hideText)
        <div class="banner-overlay {{ $isColour ? 'banner-overlay--colour' : '' }}">
          <div class="banner-texts">
            @if (! empty($banner['title']))
              <div class="banner-overlay-title">
                <h3>{!! $banner['title'] !!}</h3>
              </div>
            @endif
            @if (! empty($banner['description']))
              <div class="banner-overlay-text">
                <p>{!! $banner['description'] !!}</p>
              </div>
            @endif
          </div>

          @if (! empty($banner['link_label']))
            <div class="neckbands-button">
              <span>
                {!! $banner['link_label'] . ' <i class="fas fa-caret-right"></i>' !!}
              </span>
            </div>
          @endif
        </div>
      @endunless
    </div>
  @if ($hasLink)
    </a>
  @endif
</div>
