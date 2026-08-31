@extends('admin.layouts.master')

@section('page_title')
  {{ trans('app.sliders') }}
@endsection

@section('content')
  @php
    $sliderModel = \App\Models\Slider::class;
    $massActions = [
      ['url' => route('admin.appearance.slider.massDestroy'), 'label' => trans('app.delete_permanently'), 'icon' => 'fa-times'],
    ];
  @endphp

  @include('admin.partials.ui.card_start', [
    'title' => trans('app.sliders'),
    'icon' => 'fa-sliders',
    'actions' => view('admin.slider._header_actions')->render(),
  ])

  <table class="table table-hover admin-table table-2nd-no-sort">
    <thead>
      <tr>
        @include('admin.partials.ui.mass_checkbox_header', ['model' => $sliderModel, 'massActions' => $massActions])
        @cannot('massDelete', $sliderModel)
          {{-- no mass column --}}
        @endcannot
        <th>{{ trans('app.mobile_slider') }}</th>
        <th>{{ trans('app.detail') }}</th>
        <th>{{ trans('app.slider') }}</th>
        <th>{{ trans('app.options') }}</th>
        <th>{{ trans('app.created_at') }}</th>
        <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
      </tr>
    </thead>
    <tbody id="massSelectArea">
      @foreach ($sliders as $slider)
        <tr>
          @can('massDelete', $sliderModel)
            <td><input id="{{ $slider->id }}" type="checkbox" class="massCheck"></td>
          @endcan
          <td>
            <img src="{{ get_storage_file_url(optional($slider->mobileImage)->path, 'cover_thumb') }}" class="admin-table__banner-thumb" alt="">
          </td>
          <td>
            <strong style="color: {{ $slider->title_color }}">{!! $slider->title !!}</strong>
            <br>
            <small style="color: {{ $slider->sub_title_color }}">{!! $slider->sub_title !!}</small>
          </td>
          <td>
            <img src="{{ get_storage_file_url(optional($slider->featureImage)->path, 'cover_thumb') }}" class="admin-table__banner-thumb" alt="">
          </td>
          <td class="small">
            {{ trans('app.title_color') }}: <strong>{!! $slider->title_color !!}</strong><br>
            {{ trans('app.sub_title_color') }}: <strong>{!! $slider->sub_title_color !!}</strong><br>
            {{ trans('app.alternative_color') }}: <strong>{!! $slider->alt_color !!}</strong><br>
            {{ trans('app.order') }}: <strong>{!! $slider->order !!}</strong><br>
            {{ trans('app.link') }}: <strong>{!! $slider->link !!}</strong>
          </td>
          <td>{{ $slider->created_at->toFormattedDateString() }}</td>
          <td class="row-options admin-row-actions">
            @can('update', $slider)
              <a href="javascript:void(0)" data-link="{{ route('admin.appearance.slider.edit', $slider->id) }}" class="admin-action-btn ajax-modal-btn" title="{{ trans('app.edit') }}" data-toggle="tooltip"><i class="fa fa-edit"></i></a>
            @endcan
            @can('delete', $slider)
              {!! Form::open(['route' => ['admin.appearance.slider.destroy', $slider->id], 'method' => 'delete', 'class' => 'data-form admin-inline-form']) !!}
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
