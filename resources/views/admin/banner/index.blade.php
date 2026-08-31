@extends('admin.layouts.master')

@section('page_title')
  {{ trans('app.banners') }}
@endsection

@section('content')
  @php
    $bannerModel = \App\Models\Banner::class;
    $massActions = [
      ['url' => route('admin.appearance.banner.massDestroy'), 'label' => trans('app.delete_permanently'), 'icon' => 'fa-times'],
    ];
  @endphp

  @include('admin.partials.ui.card_start', [
    'title' => trans('app.banners'),
    'icon' => 'fa-image',
    'actions' => view('admin.banner._header_actions')->render(),
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
        <th>{{ trans('app.created_at') }}</th>
        <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
      </tr>
    </thead>
    <tbody id="massSelectArea">
      @foreach ($banners as $banner)
        <tr>
          @can('massDelete', $bannerModel)
            <td><input id="{{ $banner->id }}" type="checkbox" class="massCheck"></td>
          @endcan
          <td>
            <strong>{!! $banner->title !!}</strong>
            @unless ($banner->group)
              <span class="label label-default">{{ strtoupper(trans('app.draft')) }}</span>
            @endunless
            <br>
            <span class="small text-muted">{!! $banner->description !!}</span>
          </td>
          <td>
            <img src="{{ get_storage_file_url(optional($banner->featureImage)->path, 'cover_thumb') }}" class="img-sm admin-table__banner-thumb" alt="">
          </td>
          <td class="small">
            {{ trans('app.group') }}: <strong>{!! $banner->group ? $banner->group->name : trans('app.unspecified') !!}</strong><br>
            {{ trans('app.columns') }}: <strong>{!! $banner->columns !!}</strong><br>
            {{ trans('app.order') }}: <strong>{!! $banner->order !!}</strong><br>
            {{ trans('app.link_label') }}: <strong>{!! $banner->link_label !!}</strong><br>
            {{ trans('app.link') }}: <strong>{!! $banner->link !!}</strong>
          </td>
          <td>{{ $banner->created_at->toFormattedDateString() }}</td>
          <td class="row-options admin-row-actions">
            @can('update', $banner)
              <a href="javascript:void(0)" data-link="{{ route('admin.appearance.banner.edit', $banner->id) }}" class="admin-action-btn ajax-modal-btn" title="{{ trans('app.edit') }}" data-toggle="tooltip"><i class="fa fa-edit"></i></a>
            @endcan
            @can('delete', $banner)
              {!! Form::open(['route' => ['admin.appearance.banner.destroy', $banner->id], 'method' => 'delete', 'class' => 'data-form admin-inline-form']) !!}
              <button type="submit" class="admin-action-btn confirm ajax-silent" title="{{ trans('app.trash') }}" data-toggle="tooltip"><i class="fa fa-trash-o"></i></button>
              {!! Form::close() !!}
            @endcan
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>

  @include('admin.partials.ui.card_end')
@endsection
