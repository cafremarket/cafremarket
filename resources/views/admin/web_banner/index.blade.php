@extends('admin.layouts.master')

@section('page_title')
  {{ trans('nav.web_banners') }}
@endsection

@section('content')
  @php
    $bannerModel = \App\Models\Banner::class;
    $bannersByGroup = $banners->groupBy('group_id');
    $homepageGroups = $groups->whereIn('id', $homepageGroupIds)->values();
    $extraGroups = $groups->whereNotIn('id', $homepageGroupIds)->values();
    $totalBanners = $banners->count();
    $homepageBannerCount = $banners->whereIn('group_id', $homepageGroupIds)->count();
  @endphp

  <div class="wb-manager">
    <div class="wb-manager__hero">
      <div class="wb-manager__hero-copy">
        <p class="wb-manager__eyebrow">{{ trans('nav.web_banners') }}</p>
        <h2 class="wb-manager__title">{{ trans('help.web_banners_intro_title') }}</h2>
        <p class="wb-manager__lead">{{ trans('help.web_banners_intro') }}</p>
      </div>

      <div class="wb-manager__stats">
        <div class="wb-manager__stat">
          <span class="wb-manager__stat-value">{{ $totalBanners }}</span>
          <span class="wb-manager__stat-label">{{ trans('app.banners') }}</span>
        </div>
        <div class="wb-manager__stat">
          <span class="wb-manager__stat-value">{{ $homepageBannerCount }}</span>
          <span class="wb-manager__stat-label">{{ trans('app.homepage') }}</span>
        </div>
        <div class="wb-manager__stat">
          <span class="wb-manager__stat-value">{{ $homepageGroups->count() }}</span>
          <span class="wb-manager__stat-label">{{ trans('app.homepage_rows') }}</span>
        </div>
      </div>

      @can('create', $bannerModel)
        <div class="wb-manager__hero-actions">
          <a href="javascript:void(0)"
             data-link="{{ route('admin.web_banner.create', ['group_id' => 'group_1']) }}"
             class="ajax-modal-btn btn btn-new btn-flat">
            <i class="fa fa-plus"></i> {{ trans('app.add_web_banner') }}
          </a>
        </div>
      @endcan
    </div>

    <div class="wb-manager__layout-guide">
      <div class="wb-manager__layout-guide-item">
        <strong>12</strong> <span>{{ trans('help.web_banner_width_full') }}</span>
      </div>
      <div class="wb-manager__layout-guide-item">
        <strong>6</strong> <span>{{ trans('help.web_banner_width_half') }}</span>
      </div>
      <div class="wb-manager__layout-guide-item">
        <strong>4</strong> <span>{{ trans('help.web_banner_width_third') }}</span>
      </div>
      <div class="wb-manager__layout-guide-item">
        <strong>3</strong> <span>{{ trans('help.web_banner_width_quarter') }}</span>
      </div>
    </div>

    <div class="wb-manager__section">
      <div class="wb-manager__section-head">
        <h3>{{ trans('app.homepage_rows') }}</h3>
        <p>{{ trans('help.web_banners_homepage_section') }}</p>
      </div>

      @foreach ($homepageGroups as $group)
        @include('admin.web_banner._group_panel', [
          'group' => $group,
          'groupBanners' => $bannersByGroup->get($group->id, collect())->sortBy('order'),
          'isHomepage' => true,
          'bannerModel' => $bannerModel,
        ])
      @endforeach
    </div>

    @if ($extraGroups->isNotEmpty())
      <div class="wb-manager__section">
        <div class="wb-manager__section-head">
          <h3>{{ trans('app.extra_banner_rows') }}</h3>
          <p>{{ trans('help.web_banners_extra_section') }}</p>
        </div>

        @foreach ($extraGroups as $group)
          @include('admin.web_banner._group_panel', [
            'group' => $group,
            'groupBanners' => $bannersByGroup->get($group->id, collect())->sortBy('order'),
            'isHomepage' => false,
            'bannerModel' => $bannerModel,
          ])
        @endforeach
      </div>
    @endif
  </div>
@endsection
