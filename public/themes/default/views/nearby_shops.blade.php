@extends('theme::layouts.main')

@section('content')
  <section class="brand-cover-img-wrapper mb-0">
    <div class="banner banner-o-hid cover-img-wrapeer" style="background-image:url( {{ asset('images/placeholders/shop_cover.jpg') }} );">
      <div class="page-cover-caption">
        <h5 class="page-cover-title shadow">{{ trans('app.nearby_shops') }}</h5>
        <p class="page-cover-desc">{{ trans('app.showing_shops_near_you') }}</p>
      </div>
    </div>
  </section>

  <div class="container px-2">
    <header class="page-header">
      <div class="row">
        <div class="col-md-12">
          <ol class="breadcrumb nav-breadcrumb">
            @include('theme::headers.lists.home')
            <li><a href="{{ route('shops') }}">@lang('theme.vendors')</a></li>
            <li>{{ trans('app.nearby_shops') }}</li>
          </ol>
        </div>
      </div>
    </header>
  </div>

  <section>
    <div class="container mb-4 sm-100">
      @if ($shops->isEmpty())
        <div class="alert alert-info">{{ trans('app.no_nearby_shops') }}</div>
      @endif

      <div class="row thumb-lists justify-content-center align-self-center">
        @foreach ($shops as $shop)
          <div class="col-lg-3 col-sm-6 p-1">
            <div class="card-box text-center">
              <a href="{{ route('show.store', $shop->slug) }}" class="text-reset">
                <div class="thumb-lg d-flex thumbnail rounded-circle justify-content-center align-items-center mx-auto p-2">
                  <img class="lazy w-100" src="{{ get_storage_file_url(optional($shop->logoImage)->path, 'tiny_thumb') }}" data-src="{{ get_storage_file_url(optional($shop->logoImage)->path, 'full') }}" alt="{{ $shop->name }}">
                </div>
                <h4 class="mb-1">{!! $shop->getQualifiedName(10) !!}</h4>
              </a>

              @if (isset($distances[$shop->id]))
                <p class="text-muted mb-2"><i class="fa fa-map-marker"></i> {{ number_format($distances[$shop->id], 1) }} km</p>
              @endif

              @if (isset($shop->active_inventories_count) && (int) $shop->active_inventories_count === 0)
                <p class="text-warning small mb-2">{{ trans('theme.no_products_listed_yet') }}</p>
              @endif

              @include('theme::layouts.ratings', ['ratings' => $shop->ratings, 'count' => $shop->ratings_count])

              <a href="{{ route('show.store', $shop->slug) }}" class="btn btn-default btn-rounded mt-3 waves-effect w-md waves-light">
                {{ trans('theme.visit_shop_page') }}
              </a>
            </div>
          </div>
        @endforeach
      </div>
    </div>
  </section>
@endsection
