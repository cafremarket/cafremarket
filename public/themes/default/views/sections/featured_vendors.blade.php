@if (count($featured_vendors))
  <section>
    <div class="product-type pt-3">
      <div class="container">
        <div class="product-type-inner">
          <div class="row justify-content-center">
            @foreach ($featured_vendors as $featured_vendor)
              <div class="col-md-4 col-sm-6 col-12">
                <div class="product-list-col">
                  <div class="product-list-col-header">
                    <div class="sell-header d-flex flex-column align-items-center justify-content-end">
                      <figure class="mb-0">
                        @include('theme::partials._shop_logo_frame', [
                          'shop' => $featured_vendor,
                          'thumbSize' => 'tyni',
                          'fullSize' => 'full',
                          'class' => 'mx-auto mb-1',
                        ])
                      </figure>

                      <div class="sell-header-title">
                        <a href="{{ route('show.store', $featured_vendor->slug) }}" class="seller-info-name" target="_blank">
                          {!! $featured_vendor->getQualifiedName(10) !!}
                        </a>
                      </div>
                    </div>
                  </div>
                  <div class="product-list-col-product featured-vendors-cards">

                    @include('theme::partials._product_vertical', ['products' => $featured_vendor->inventories->take(6)])

                  </div>
                </div>
              </div>
            @endforeach
          </div>
        </div>
      </div>
    </div>
  </section>
@endif
