@extends('admin.layouts.master')

@section('page_title')
  {{ trans('nav.app_banners') }}
@endsection

@section('content')
  @php
    $bannerModel = \App\Models\Banner::class;
    $bannersByGroup = $banners->groupBy('group_id');
    $homepageGroups = $groups->whereIn('id', $homepageGroupIds)->values();
    $totalBanners = $banners->whereIn('group_id', $homepageGroupIds)->count();
  @endphp

  <div class="wb-manager">
    <div class="wb-manager__hero">
      <div class="wb-manager__hero-copy">
        <p class="wb-manager__eyebrow">{{ trans('nav.app_banners') }}</p>
        <h2 class="wb-manager__title">{{ trans('help.app_banners_intro_title') }}</h2>
        <p class="wb-manager__lead">{{ trans('help.app_banners_intro') }}</p>
      </div>

      <div class="wb-manager__stats">
        <div class="wb-manager__stat">
          <span class="wb-manager__stat-value">{{ $totalBanners }}</span>
          <span class="wb-manager__stat-label">{{ trans('app.banners') }}</span>
        </div>
        <div class="wb-manager__stat">
          <span class="wb-manager__stat-value">{{ $homepageGroups->count() }}</span>
          <span class="wb-manager__stat-label">{{ trans('app.homepage_rows') }}</span>
        </div>
      </div>

      @can('create', $bannerModel)
        <div class="wb-manager__hero-actions">
          <a href="javascript:void(0)"
             data-link="{{ route('admin.app_banner.create', ['group_id' => 'group_1']) }}"
             class="ajax-modal-btn btn btn-new btn-flat">
            <i class="fa fa-plus"></i> {{ trans('app.add_app_banner') }}
          </a>
        </div>
      @endcan
    </div>

    <div class="wb-manager__layout-guide">
      <div class="wb-manager__layout-guide-item">
        <strong>{{ trans('app.banner_type_slider') }}</strong>
        <span>{{ trans('help.web_banner_guide_slider') }}</span>
      </div>
      <div class="wb-manager__layout-guide-item">
        <strong>{{ trans('app.banner_type_single') }}</strong>
        <span>{{ trans('help.web_banner_guide_single') }}</span>
      </div>
      <div class="wb-manager__layout-guide-item">
        <strong>{{ trans('app.banner_type_colour') }}</strong>
        <span>{{ trans('help.web_banner_guide_colour') }}</span>
      </div>
      <div class="wb-manager__layout-guide-item">
        <strong>{{ trans('help.web_banner_layout_full') }}</strong>
        <span>{{ trans('help.web_banner_guide_full') }}</span>
      </div>
      <div class="wb-manager__layout-guide-item">
        <strong>{{ trans('help.web_banner_layout_third') }}</strong>
        <span>{{ trans('help.web_banner_guide_third') }}</span>
      </div>
    </div>

    <div class="wb-manager__section">
      <div class="wb-manager__section-head">
        <h3>{{ trans('app.homepage_rows') }}</h3>
        <p>{{ trans('help.app_banners_section') }}</p>
      </div>

      @foreach ($homepageGroups as $group)
        @include('admin.web_banner._group_panel', [
          'group' => $group,
          'groupBanners' => $bannersByGroup->get($group->id, collect())->sortBy('order'),
          'isHomepage' => true,
          'bannerModel' => $bannerModel,
          'bannerRoute' => 'admin.app_banner',
          'rowBadge' => trans('nav.app_banners'),
        ])
      @endforeach
    </div>
  </div>
@endsection
