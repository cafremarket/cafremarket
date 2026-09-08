@if (isset($featuredItems) && $featuredItems->count())
  <section>
    <div class="neckbands">
      <div class="container md-100">
        <div class="neckbands-inner">
          <div class="neckbands-header">
            <div class="sell-header mb-3">
              <div class="sell-header-title">
                <h2>
                  {{ trans('app.featured_items') }}
                  <i class="fal fa-star text-warning"></i>
                </h2>
              </div>
              <div class="header-line"><span></span></div>
              <div class="best-deal-arrow">
                <ul>
                  <li><button type="button" class="left-arrow slider-arrow slick-arrow featured-items-left" aria-label="left arrow"><i class="fal fa-chevron-left"></i></button></li>
                  <li><button type="button" class="right-arrow slider-arrow slick-arrow featured-items-right" aria-label="right arrow"><i class="fal fa-chevron-right"></i></button></li>
                </ul>
              </div>
            </div>
          </div>
          <div class="neckband-items">
            <div class="featured-items-inner">
              @include('theme::partials._product_horizontal', ['products' => $featuredItems])
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
@endif
