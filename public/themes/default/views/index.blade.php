@extends('theme::layouts.main')

@section('content')
  {{-- Location picker hero — primary address selector on homepage --}}
  @include('theme::sections.home_location_hero')

  {{-- Hero slider --}}
  @if (count($sliders) > 0 || config('system_settings.show_empty_homepage_slider'))
    @desktop
      @include('theme::sections.slider')
    @elsedesktop
      @include('theme::sections.slider_mobile')
    @enddesktop
  @endif

  {{-- 2. Three banner rows --}}
  @if (!empty($banners['group_1']))
    @include('theme::sections.banners', ['banners' => $banners['group_1']])
  @endif

  @if (!empty($banners['group_2']))
    @include('theme::sections.banners', ['banners' => $banners['group_2']])
  @endif

  @if (!empty($banners['group_3']))
    @include('theme::sections.banners', ['banners' => $banners['group_3']])
  @endif

  {{-- 3. Featured products from nearby stores (5 items) --}}
  @include('theme::sections.nearby_featured')

  {{-- 4. Nearby stores based on delivery address --}}
  @include('theme::sections.nearby_stores')
@endsection

@section('scripts')
  <script src="{{ theme_asset_url('js/eislideshow.js') }}"></script>
  <script type="text/javascript">
    @if (count($sliders) > 0 || config('system_settings.show_empty_homepage_slider'))
      $('#ei-slider').eislideshow({
        animation: 'center',
        autoplay: true,
        slideshow_interval: 4000,
      });
    @endif

    $('.nearby-featured-slider').owlCarousel({
      loop: true,
      lazyLoad: true,
      dots: true,
      margin: 16,
      nav: true,
      responsive: {
        0: { items: 1 },
        576: { items: 2 },
        992: { items: 3 },
        1200: { items: 5 }
      }
    });
  </script>
@endsection
