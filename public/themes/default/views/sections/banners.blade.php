<section class="banner-block banner-block--full">
  <div class="banner-block__bleed">
    <div class="row g-0">
      @foreach ($banners as $banner)
        @php
          $cols = (int) ($banner['columns'] ?? 12);
          $hideText = ! empty($banner['hide_text']);
          $hasLink = ! empty($banner['link']);
          $imgPath = $banner['feature_image']['path'] ?? null;
          $imgOk = $imgPath && Storage::exists($imgPath);
        @endphp
        <div class="col-lg-{{ $cols }} col-12">
          <div class="image-banner {{ $cols > 11 ? 'single-banner' : '' }} {{ $hideText ? 'image-banner--image-only' : '' }}">
            @if ($hasLink)
              <a href="{{ $banner['link'] }}" class="banner-box-link" @if ($hideText) aria-label="{{ strip_tags($banner['title'] ?? 'Banner') }}" @endif>
            @endif
              <div class="banner-box {{ !empty($banner['effect']) ? 'outline-effect' : '' }}">
                @if ($imgOk)
                  <img class="lazy"
                       src="{{ $cols >= 12 ? get_storage_file_url($imgPath, 'tiny') : '/images/loading.webp' }}"
                       data-src="{{ get_storage_file_url($imgPath, 'full') }}"
                       alt="{{ $hideText ? '' : ($banner['title'] ?? 'Banner Image') }}"
                       title="{{ $hideText ? '' : ($banner['title'] ?? 'Banner Image') }}">
                @else
                  <img src="{{ get_storage_file_url() }}" alt="{{ $banner['title'] ?? 'Banner Image' }}">
                @endif

                @unless ($hideText)
                  <div class="banner-overlay">
                    <div class="banner-texts">
                      @if (!empty($banner['title']))
                        <div class="banner-overlay-title">
                          <h3>{!! $banner['title'] !!}</h3>
                        </div>
                      @endif
                      @if (!empty($banner['description']))
                        <div class="banner-overlay-text">
                          <p>{!! $banner['description'] !!}</p>
                        </div>
                      @endif
                    </div>

                    @if (!empty($banner['link_label']))
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
        </div>
      @endforeach
    </div>
  </div>
</section>
