@extends('theme::layouts.main')

@section('content')
  <!-- HEADER SECTION -->
  <div class="container">
    <header class="page-header">
      <div class="row">
        <div class="col-md-12">
          <ol class="breadcrumb nav-breadcrumb">
            @include('theme::headers.lists.home')

            @if (Request::has('ingrp'))
              <li class="active">{{ $category->name }}</li>
            @elseif(Request::has('insubgrp') && Request::get('insubgrp') != 'all')
              <li>
                <a class="link-filter-opt" data-name="ingrp" data-value="{{ $category->group->slug }}">
                  {{ $category->group->name }}
                </a>
              </li>

              <li class="active">{{ $category->name }}</li>
            @elseif(Request::has('in'))
              <li>
                <a class="link-filter-opt" data-name="ingrp" data-value="{{ $category->subGroup->group->slug }}">
                  {{ $category->subGroup->group->name }}
                </a>
              </li>

              <li>
                <a class="link-filter-opt" data-name="insubgrp" data-value="{{ $category->subGroup->slug }}">
                  {{ $category->subGroup->name }}
                </a>
              </li>
              <li class="active">{{ $category->name }}</li>
            @endif

            <li class="active">
              "<strong class="text-primary">{{ Request::get('q') }}</strong>"
              <span class="ml-1">({{ trans('app.search_result_found', ['count' => $products->total()]) }})</span>
            </li>
          </ol>
        </div> <!-- /.col -->
      </div> <!-- /.row -->
    </header>
  </div> <!-- /.container -->

  <!-- CONTENT SECTION -->
  <section>
    <div class="container category-single-page">
      <div class="row mb-3">
        <div class="col-md-12">
          <form action="{{ route('inCategoriesSearch') }}" method="GET" class="form-inline flex-wrap align-items-end">
            @foreach (['in', 'insubgrp', 'ingrp'] as $catParam)
              @if (Request::filled($catParam))
                <input type="hidden" name="{{ $catParam }}" value="{{ Request::get($catParam) }}">
              @endif
            @endforeach
            <div class="form-group mr-2 mb-2">
              <input
                type="text"
                name="q"
                class="form-control"
                placeholder="{{ trans('theme.search_keyword') ?? 'Search keyword' }}"
                value="{{ Request::get('q') }}"
                required
              >
            </div>
            <div class="form-group mr-2 mb-2">
              <input
                type="text"
                name="location"
                class="form-control"
                placeholder="{{ trans('theme.location') ?? 'Location' }}"
                value="{{ Request::get('location') }}"
              >
            </div>
            <div class="form-group mr-2 mb-2">
              <input
                type="text"
                name="province"
                class="form-control"
                placeholder="{{ trans('theme.province') ?? 'Province' }}"
                value="{{ Request::get('province') }}"
              >
            </div>
            <div class="form-group mr-2 mb-2">
              <label class="d-block small text-muted mb-0">{{ trans('theme.country') ?? 'Country' }}</label>
              <select name="country_id" class="form-control" style="min-width: 160px;" onchange="this.form.submit()">
                <option value="" @selected(! request()->filled('country_id'))>{{ trans('theme.all_countries') ?? 'All countries' }}</option>
                @foreach ($searchCountries ?? [] as $cid => $cname)
                  <option value="{{ $cid }}" @selected((string) Request::get('country_id') === (string) $cid)>{{ $cname }}</option>
                @endforeach
              </select>
            </div>
            <div class="form-group mr-2 mb-2">
              <label class="d-block small text-muted mb-0" title="{{ trans('theme.search_by_shipping_zone_help') ?? 'Only show items from shops that ship to this state/region' }}">
                {{ trans('theme.ships_to_zone') ?? 'Ships to (zone)' }}
              </label>
              <select name="state_id" class="form-control" style="min-width: 180px;">
                <option value="" @selected(! request()->filled('state_id'))>{{ trans('theme.any_state') ?? 'Any state / region' }}</option>
                @foreach ($searchStates ?? [] as $sid => $sname)
                  <option value="{{ $sid }}" @selected((string) Request::get('state_id') === (string) $sid)>{{ $sname }}</option>
                @endforeach
              </select>
            </div>
            <button type="submit" class="btn btn-primary mb-2">{{ trans('theme.button.search') ?? 'Search' }}</button>
          </form>
        </div>
      </div>

      @include('theme::contents.product_list', ['colum' => 3])

    </div> <!-- /.container -->
  </section>

  <!-- BROWSING ITEMS -->
  @include('theme::sections.recent_views')
@endsection
