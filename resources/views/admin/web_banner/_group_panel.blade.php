@php
  $columnsUsed = (int) $groupBanners->sum('columns');
  $rowLabel = trans('help.web_banner_group_'.$group->id, ['default' => $group->name]);
  $rowHint = trans('help.web_banner_group_'.$group->id.'_hint', ['default' => '']);
@endphp

<section class="wb-row {{ $isHomepage ? 'wb-row--homepage' : 'wb-row--extra' }}">
  <header class="wb-row__header">
    <div class="wb-row__heading">
      <div class="wb-row__title-wrap">
        <h4 class="wb-row__title">{{ $rowLabel }}</h4>
        @if ($isHomepage)
          <span class="wb-row__badge">{{ trans('app.homepage') }}</span>
        @endif
      </div>
      @if ($rowHint)
        <p class="wb-row__hint">{{ $rowHint }}</p>
      @endif
    </div>

    <div class="wb-row__meta">
      <span class="wb-row__count">
        {{ trans_choice('app.banner_count', $groupBanners->count(), ['count' => $groupBanners->count()]) }}
      </span>
      <span class="wb-row__columns {{ $columnsUsed > 12 ? 'is-over' : '' }}" title="{{ trans('help.web_banner_columns_hint') }}">
        {{ trans('app.columns_used', ['used' => $columnsUsed, 'max' => 12]) }}
      </span>
      @can('create', $bannerModel)
        <a href="javascript:void(0)"
           data-link="{{ route('admin.web_banner.create', ['group_id' => $group->id]) }}"
           class="ajax-modal-btn btn btn-sm btn-default btn-flat">
          <i class="fa fa-plus"></i> {{ trans('app.add_banner') }}
        </a>
      @endcan
    </div>
  </header>

  @if ($groupBanners->isEmpty())
    <div class="wb-row__empty">
      <div class="wb-row__empty-icon"><i class="fa fa-image"></i></div>
      <p>{{ trans('app.no_banner_in_group') }}</p>
      @can('create', $bannerModel)
        <a href="javascript:void(0)"
           data-link="{{ route('admin.web_banner.create', ['group_id' => $group->id]) }}"
           class="ajax-modal-btn btn btn-new btn-flat btn-sm">
          <i class="fa fa-plus"></i> {{ trans('app.add_banner') }}
        </a>
      @endcan
    </div>
  @else
    <div class="wb-row__grid">
      @foreach ($groupBanners as $banner)
        @php
          $col = max(3, min(12, (int) ($banner->columns ?: 12)));
          $img = get_storage_file_url(optional($banner->featureImage)->path, 'medium');
        @endphp
        <article class="wb-card wb-card--col-{{ $col }}">
          <div class="wb-card__media" style="background-image: url('{{ $img }}');">
            <div class="wb-card__overlay">
              <div class="wb-card__actions">
                @can('update', $banner)
                  <a href="javascript:void(0)"
                     data-link="{{ route('admin.web_banner.edit', $banner->id) }}"
                     class="wb-card__btn ajax-modal-btn"
                     title="{{ trans('app.edit') }}">
                    <i class="fa fa-pencil"></i> {{ trans('app.edit') }}
                  </a>
                @endcan
                @can('delete', $banner)
                  {!! Form::open(['route' => ['admin.web_banner.destroy', $banner->id], 'method' => 'delete', 'class' => 'data-form admin-inline-form']) !!}
                  <button type="submit" class="wb-card__btn wb-card__btn--danger confirm ajax-silent" title="{{ trans('app.delete') }}">
                    <i class="fa fa-trash"></i>
                  </button>
                  {!! Form::close() !!}
                @endcan
              </div>
            </div>
            <span class="wb-card__order">#{{ $banner->order }}</span>
            <span class="wb-card__width">{{ $col }}/12</span>
          </div>
          <div class="wb-card__body">
            <h5 class="wb-card__title">{!! $banner->title ?: trans('app.untitled') !!}</h5>
            @if ($banner->description)
              <p class="wb-card__desc">{!! Str::limit(strip_tags($banner->description), 80) !!}</p>
            @endif
            @if ($banner->link)
              <a class="wb-card__link" href="{{ $banner->link }}" target="_blank" rel="noopener">
                <i class="fa fa-external-link"></i> {{ Str::limit($banner->link, 42) }}
              </a>
            @endif
          </div>
        </article>
      @endforeach
    </div>
  @endif
</section>
