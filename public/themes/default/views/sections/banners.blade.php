@php
  use App\Models\Banner;

  $bannerList = collect($banners)->values();
  $rowType = $bannerList->first()['display_type'] ?? Banner::TYPE_SINGLE;
  $isSlider = $rowType === Banner::TYPE_SLIDER;
  $isGridThird = ! $isSlider && (int) ($bannerList->first()['columns'] ?? Banner::LAYOUT_FULL) === Banner::LAYOUT_THIRD;
  $blockClass = $isGridThird ? 'banner-block--third' : 'banner-block--full';
@endphp

@if ($bannerList->isNotEmpty())
  <section class="banner-block {{ $blockClass }} {{ $isSlider ? 'banner-block--slider' : '' }}">
    <div class="banner-block__inner">
      @if ($isSlider)
        <div class="banner-slider homepage-banner-slider">
          @foreach ($bannerList as $banner)
            @include('theme::sections._banner_item', ['banner' => $banner, 'cols' => Banner::LAYOUT_FULL, 'inSlider' => true])
          @endforeach
        </div>
      @else
        <div class="row {{ $isGridThird ? 'g-3' : 'g-0' }}">
          @foreach ($bannerList as $banner)
            @php
              $cols = (int) ($banner['columns'] ?? Banner::LAYOUT_FULL) === Banner::LAYOUT_THIRD
                ? Banner::LAYOUT_THIRD
                : Banner::LAYOUT_FULL;
            @endphp
            <div class="col-lg-{{ $cols }} col-md-{{ $cols === Banner::LAYOUT_THIRD ? 6 : 12 }} col-12">
              @include('theme::sections._banner_item', ['banner' => $banner, 'cols' => $cols, 'inSlider' => false])
            </div>
          @endforeach
        </div>
      @endif
    </div>
  </section>
@endif
