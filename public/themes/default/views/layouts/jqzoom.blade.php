<div class="clearfix">
  @php
    $pImg = get_product_img_src($item, 'full');
  @endphp

  <a href="{{ $pImg }}" id="{{ $zoomID ?? 'jqzoom' }}" data-rel="gal-1">
    <img class="product-img" data-name="product_image" src="{{ $pImg }}" alt="{{ $item->title }}" title="{{ $item->title }}" />
  </a>
</div>

<ul class="jqzoom-thumbs mt-2 d-flex justify-content-center mb-md-3">
  @php
    $item_images = $item->images->count() ? $item->images : $item->product->images;
    
    if (isset($variants)) {
        // Remove images of current items from the variants imgs
        $other_images = $variants
            ->pluck('images')
            ->flatten(1)
            ->filter(function ($value, $key) use ($item) {
                return $value->imageable_id != $item->id;
            });
        $item_images = $item_images->concat($other_images);
    }
  @endphp

  @foreach ($item_images as $img)
    @continue(!$img->path)

    @php
      $fImg = get_storage_file_url($img->path, 'full');
    @endphp

    <li>
      <a class="d-flex flex-wrap align-items-center {{ $img->path == optional($item->image)->path ? 'zoomThumbActive' : '' }}" href="javascript:void(0);" data-rel="{gallery:'gal-1', smallimage: '{{ $fImg }}', largeimage: '{{ $fImg }}'}">
        <img src="{{ get_storage_file_url($img->path, 'thumbnail') }}" alt="Thumb" title="{{ $item->title }}" />
      </a>
    </li>
  @endforeach

  @php
    $productVideoUrl = optional($item->product)->hasVideo()
      ? get_product_video_url($item->product->video_path)
      : null;
  @endphp

  @if ($productVideoUrl)
    <li class="sf-pdp__video-thumb">
      <button type="button" class="sf-pdp__video-thumb-btn" data-product-video="{{ $productVideoUrl }}" aria-label="{{ trans('theme.product_video') }}">
        <i class="fas fa-play"></i>
        <span>{{ trans('theme.video') }}</span>
      </button>
    </li>
  @endif
</ul> <!-- /.jqzoom-thumbs -->

@if ($productVideoUrl)
  <div class="sf-pdp__video" id="product-video-player" hidden>
    <video controls playsinline preload="metadata" controlslist="nodownload">
      <source src="{{ $productVideoUrl }}">
      {{ trans('theme.browser_does_not_support_video') }}
    </video>
  </div>
  <script>
    (function () {
      var thumbBtn = document.querySelector('[data-product-video]');
      var playerWrap = document.getElementById('product-video-player');
      var mainImgWrap = document.querySelector('.sf-pdp__gallery .clearfix');
      if (!thumbBtn || !playerWrap) return;

      thumbBtn.addEventListener('click', function () {
        if (mainImgWrap) mainImgWrap.style.display = 'none';
        playerWrap.hidden = false;
        var video = playerWrap.querySelector('video');
        if (video) {
          video.play().catch(function () {});
        }
        document.querySelectorAll('.jqzoom-thumbs a').forEach(function (a) {
          a.classList.remove('zoomThumbActive');
        });
        thumbBtn.classList.add('is-active');
      });

      document.querySelectorAll('.jqzoom-thumbs a[data-rel]').forEach(function (a) {
        a.addEventListener('click', function () {
          playerWrap.hidden = true;
          var video = playerWrap.querySelector('video');
          if (video) {
            video.pause();
          }
          if (mainImgWrap) mainImgWrap.style.display = '';
          thumbBtn.classList.remove('is-active');
        });
      });
    })();
  </script>
@endif

