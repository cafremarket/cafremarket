@if (isset($featuredCategories) && $featuredCategories->count())
  <section>
    <div class="neckbands">
      <div class="container md-100">
        <div class="neckbands-inner">
          <div class="neckbands-header">
            <div class="sell-header mb-3">
              <div class="sell-header-title">
                <h2>
                  {{ trans('theme.featured_categories') }}
                  <i class="fal fa-th-large text-warning"></i>
                </h2>
              </div>
              <div class="header-line"><span></span></div>
              <div class="best-deal-arrow">
                <ul>
                  <li><button type="button" class="left-arrow slider-arrow slick-arrow featured-categories-left" aria-label="{{ trans('theme.previous') }}"><i class="fal fa-chevron-left"></i></button></li>
                  <li><button type="button" class="right-arrow slider-arrow slick-arrow featured-categories-right" aria-label="{{ trans('theme.next') }}"><i class="fal fa-chevron-right"></i></button></li>
                </ul>
              </div>
            </div>
          </div>
          <div class="neckband-items">
            <div class="featured-items-inner featured-categories-items">
              @foreach ($featuredCategories as $category)
                <div class="items-slider">
                  <a href="{{ get_category_url($category) }}" class="featured-category-card">
                    <span class="featured-category-card__img" style="background-image:url('{{ get_cover_img_src($category, 'category') }}');"></span>
                    <span class="featured-category-card__name">{{ $category->name }}</span>
                  </a>
                </div>
              @endforeach
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
@endif
