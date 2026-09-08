@if (isset($dealOfTheDay) && $dealOfTheDay->count())
  <section>
    <div class="neckbands">
      <div class="container md-100">
        <div class="neckbands-inner">
          <div class="neckbands-header">
            <div class="sell-header mb-3">
              <div class="sell-header-title">
                <h2>
                  {{ trans('app.deal_of_the_day') }}
                  <i class="fal fa-bolt text-warning"></i>
                </h2>
              </div>
              <div class="header-line"><span></span></div>
              @if ($dealOfTheDay->count() > 1)
                <div class="best-deal-arrow">
                  <ul>
                    <li><button type="button" class="left-arrow slider-arrow slick-arrow deal-day-left" aria-label="left arrow"><i class="fal fa-chevron-left"></i></button></li>
                    <li><button type="button" class="right-arrow slider-arrow slick-arrow deal-day-right" aria-label="right arrow"><i class="fal fa-chevron-right"></i></button></li>
                  </ul>
                </div>
              @endif
            </div>
          </div>
          <div class="neckband-items">
            <div class="featured-items-inner deal-of-the-day-items">
              @include('theme::partials._product_horizontal', ['products' => $dealOfTheDay])
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
@endif
