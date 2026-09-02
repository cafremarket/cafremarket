@extends('admin.layouts.master')

@section('page_title')
  {{ trans('nav.web_banners') }}
@endsection

@section('content')
  @php
    $bannerModel = \App\Models\Banner::class;
    $massActions = [
      ['url' => route('admin.web_banner.massDestroy'), 'label' => trans('app.delete_permanently'), 'icon' => 'fa-times'],
    ];
    $bannersByGroup = $banners->groupBy('group_id');
  @endphp

  <div class="callout callout-info mb-3">
    <h4><i class="fa fa-info-circle"></i> {{ trans('help.web_banners_intro_title') }}</h4>
    <p class="mb-0">{{ trans('help.web_banners_intro') }}</p>
  </div>

  @php
    $hasMassColumn = auth()->user()->can('massDelete', $bannerModel);
    $colspan = $hasMassColumn ? 6 : 5;
  @endphp

  @include('admin.partials.ui.card_start', [
    'title' => trans('nav.web_banners'),
    'icon' => 'fa-images',
    'actions' => view('admin.web_banner._header_actions')->render(),
  ])

  <table class="table table-hover admin-table table-no-sort">
    <thead>
      <tr>
        @include('admin.partials.ui.mass_checkbox_header', ['model' => $bannerModel, 'massActions' => $massActions])
        @cannot('massDelete', $bannerModel)
          {{-- no mass column --}}
        @endcannot
        <th>{{ trans('app.detail') }}</th>
        <th>{{ trans('app.banner_image') }}</th>
        <th>{{ trans('app.options') }}</th>
        <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
      </tr>
    </thead>
    <tbody id="massSelectArea">
      @foreach ($groups as $group)
        @php
          $groupBanners = $bannersByGroup->get($group->id, collect());
          $isHomepageRow = in_array($group->id, $homepageGroupIds, true);
        @endphp

        <tr class="active">
          <td colspan="{{ $colspan }}">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
              <div>
                <strong>{{ trans('help.web_banner_group_'.$group->id, ['default' => $group->name]) }}</strong>
                @if ($isHomepageRow)
                  <span class="label label-primary">{{ trans('app.homepage') }}</span>
                @endif
                <span class="text-muted small"> — {{ trans('help.web_banner_group_'.$group->id.'_hint', ['default' => '']) }}</span>
              </div>
              @can('create', $bannerModel)
                <a href="javascript:void(0)"
                   data-link="{{ route('admin.web_banner.create', ['group_id' => $group->id]) }}"
                   class="ajax-modal-btn btn btn-xs btn-default btn-flat">
                  <i class="fa fa-plus"></i> {{ trans('app.add_banner') }}
                </a>
              @endcan
            </div>
          </td>
        </tr>

        @forelse ($groupBanners as $banner)
          <tr>
            @can('massDelete', $bannerModel)
              <td><input id="{{ $banner->id }}" type="checkbox" class="massCheck"></td>
            @endcan
            <td>
              <strong>{!! $banner->title ?: trans('app.untitled') !!}</strong>
              @if ($banner->description)
                <br><span class="small text-muted">{!! $banner->description !!}</span>
              @endif
            </td>
            <td>
              <img src="{{ get_storage_file_url(optional($banner->featureImage)->path, 'cover_thumb') }}" class="img-sm admin-table__banner-thumb" alt="">
            </td>
            <td class="small">
              {{ trans('app.columns') }}: <strong>{{ $banner->columns }}</strong><br>
              {{ trans('app.order') }}: <strong>{{ $banner->order }}</strong><br>
              @if ($banner->link)
                {{ trans('app.link') }}: <strong>{{ Str::limit($banner->link, 40) }}</strong>
              @endif
            </td>
            <td class="row-options admin-row-actions">
              @can('update', $banner)
                <a href="javascript:void(0)" data-link="{{ route('admin.web_banner.edit', $banner->id) }}" class="admin-action-btn ajax-modal-btn" title="{{ trans('app.edit') }}" data-toggle="tooltip"><i class="fa fa-edit"></i></a>
              @endcan
              @can('delete', $banner)
                {!! Form::open(['route' => ['admin.web_banner.destroy', $banner->id], 'method' => 'delete', 'class' => 'data-form admin-inline-form']) !!}
                <button type="submit" class="admin-action-btn confirm ajax-silent" title="{{ trans('app.trash') }}" data-toggle="tooltip"><i class="fa fa-trash-o"></i></button>
                {!! Form::close() !!}
              @endcan
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="{{ $colspan }}" class="text-muted small"><em>{{ trans('app.no_banner_in_group') }}</em></td>
          </tr>
        @endforelse
      @endforeach
    </tbody>
  </table>

  @include('admin.partials.ui.card_end')
@endsection
