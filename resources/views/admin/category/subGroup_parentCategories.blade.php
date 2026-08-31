@extends('admin.layouts.master')

@section('page_title')
  {{ trans('app.category_sub_group') . ' : ' }}{{ $categorySubGroup->name . ' | ' }}{{ trans('app.categories') }}
@endsection

@section('content')
  @php
    $categoryModel = \App\Models\Category::class;
    $massActions = [
      ['url' => route('admin.catalog.category.massTrash'), 'label' => trans('app.trash'), 'icon' => 'fa-trash'],
      ['url' => route('admin.catalog.category.massDestroy'), 'label' => trans('app.delete_permanently'), 'icon' => 'fa-times'],
    ];
  @endphp

  @include('admin.partials.ui.card_start', [
    'title' => trans('app.category_sub_group') . ' : ' . $categorySubGroup->name . ' | ' . trans('app.categories'),
    'icon' => 'fa-sitemap',
  ])

  <table class="table table-hover admin-table">
    <thead>
      <tr>
        @include('admin.partials.ui.mass_checkbox_header', ['model' => $categoryModel, 'massActions' => $massActions])
        @cannot('massDelete', $categoryModel)
          <th>&nbsp;</th>
        @endcannot
        <th>{{ trans('app.cover_image') }}</th>
        <th>{{ trans('app.feature_image') }}</th>
        <th>{{ trans('app.category_name') }}</th>
        <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($categories as $category)
        <tr>
          @can('massDelete', $categoryModel)
            <td>
              @unless ($category->products_count)
                <input id="{{ $category->id }}" type="checkbox" class="massCheck">
              @endunless
            </td>
          @endcan
          <td>
            <img src="{{ get_storage_file_url(optional($category->coverImage)->path, 'cover_thumb') }}" class="img-sm admin-table__thumb" alt="">
          </td>
          <td>
            <img src="{{ get_storage_file_url(optional($category->featureImage)->path, 'cover_thumb') }}" class="img-sm admin-table__thumb" alt="">
          </td>
          <td>{{ $category->name }}</td>
          <td class="row-options admin-row-actions">
            @can('update', $category)
              <a href="javascript:void(0)" data-link="{{ route('admin.catalog.category.edit', $category->id) }}" class="admin-action-btn ajax-modal-btn" title="{{ trans('app.edit') }}" data-toggle="tooltip"><i class="fa fa-edit"></i></a>
              <a href="{{ route('admin.catalog.category.translate.form', ['category' => $category, 'language' => app()->getLocale()]) }}" class="admin-action-btn" title="{{ trans('app.manage_translations') }}" data-toggle="tooltip"><i class="fa fa-language"></i></a>
            @endcan
            @can('delete', $category)
              {!! Form::open(['route' => ['admin.catalog.category.trash', $category->id], 'method' => 'delete', 'class' => 'data-form']) !!}
              {!! Form::button('<i class="fa fa-trash-o"></i>', ['type' => 'submit', 'class' => 'admin-action-btn confirm ajax-silent', 'title' => trans('app.trash'), 'data-toggle' => 'tooltip']) !!}
              {!! Form::close() !!}
            @endcan
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>

  @include('admin.partials.ui.card_end')

  @include('admin.partials.ui.trash_start', ['title' => trans('app.trash')])

  <table class="table table-hover admin-table table-option">
    <thead>
      <tr>
        <th>{{ trans('app.category_name') }}</th>
        <th>{{ trans('app.parent') }}</th>
        <th>{{ trans('app.deleted_at') }}</th>
        <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($trashes as $trash)
        <tr>
          <td>
            <strong>{{ $trash->name }}</strong>
            @if ($trash->description)
              <div class="excerpt-td small text-muted">{!! Str::limit($trash->description, 150) !!}</div>
            @endif
          </td>
          <td>
            @if ($trash->subGroup->group->deleted_at)
              <i class="fa fa-trash-o small"></i>
            @endif
            {!! $trash->subGroup->group->name !!}
            &nbsp;<i class="fa fa-angle-double-right small"></i>&nbsp;
            @if ($trash->subGroup->deleted_at)
              <i class="fa fa-trash-o small"></i>
            @endif
            {!! $trash->subGroup->name !!}
          </td>
          <td>{{ $trash->deleted_at->diffForHumans() }}</td>
          <td class="row-options admin-row-actions">
            @can('delete', $trash)
              <a href="{{ route('admin.catalog.category.restore', $trash->id) }}" class="admin-action-btn" title="{{ trans('app.restore') }}" data-toggle="tooltip"><i class="fa fa-database"></i></a>
              {!! Form::open(['route' => ['admin.catalog.category.destroy', $trash->id], 'method' => 'delete', 'class' => 'data-form']) !!}
              {!! Form::button('<i class="glyphicon glyphicon-trash"></i>', ['type' => 'submit', 'class' => 'admin-action-btn confirm ajax-silent', 'title' => trans('app.delete_permanently'), 'data-toggle' => 'tooltip']) !!}
              {!! Form::close() !!}
            @endcan
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>

  @include('admin.partials.ui.card_end')
@endsection
