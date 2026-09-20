@extends('admin.layouts.master')

@section('page_title')
  {{ trans('app.subcategories') }}
@endsection

@section('content')
  @php
    $subCategoryModel = \App\Models\SubCategory::class;
    $massActions = [
      ['url' => route('admin.catalog.subcategory.massTrash'), 'label' => trans('app.trash'), 'icon' => 'fa-trash'],
      ['url' => route('admin.catalog.subcategory.massDestroy'), 'label' => trans('app.delete_permanently'), 'icon' => 'fa-times'],
    ];
    $translation_language = app()->getLocale();
  @endphp

  @include('admin.partials.ui.card_start', [
    'title' => trans('app.subcategories'),
    'icon' => 'fa-folder',
    'actions' => view('admin.category._sub_category_header_actions')->render(),
  ])

  <table class="table table-hover admin-table table-2nd-no-sort">
    <thead>
      <tr>
        @include('admin.partials.ui.mass_checkbox_header', ['model' => $subCategoryModel, 'massActions' => $massActions])
        <th>{{ trans('app.cover_image') }}</th>
        <th>{{ trans('app.subcategory') }}</th>
        <th>{{ trans('app.parent') }}</th>
        <th>{{ trans('app.products') }}</th>
        <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
      </tr>
    </thead>
    <tbody id="massSelectArea">
      @foreach ($subCategories as $subCategory)
        <tr>
          @can('massDelete', $subCategoryModel)
            <td><input id="{{ $subCategory->id }}" type="checkbox" class="massCheck"></td>
          @endcan
          <td>
            <img src="{{ get_storage_file_url(optional($subCategory->coverImage)->path, 'cover_thumb') }}" class="img-sm admin-table__banner-thumb" alt="">
          </td>
          <td>
            {{ $subCategory->name }}
            @unless ($subCategory->active)
              <span class="label label-default">{{ trans('app.inactive') }}</span>
            @endunless
          </td>
          <td>
            @if (optional($subCategory->category)->deleted_at)
              <i class="fa fa-trash-o text-muted"></i>
            @endif
            {{ optional($subCategory->category)->name }}
          </td>
          <td>
            <span class="label label-default">{{ $subCategory->products_count }}</span>
          </td>
          <td class="row-options admin-row-actions">
            @can('update', $subCategory)
              <a href="javascript:void(0)" data-link="{{ route('admin.catalog.subcategory.edit', $subCategory->id) }}" class="admin-action-btn ajax-modal-btn" title="{{ trans('app.edit') }}" data-toggle="tooltip"><i class="fa fa-edit"></i></a>
              <a href="{{ route('admin.catalog.subcategory.translate.form', ['subCategory' => $subCategory, 'language' => $translation_language]) }}" class="admin-action-btn" title="{{ trans('app.manage_translations') }}" data-toggle="tooltip"><i class="fa fa-language"></i></a>
            @endcan
            @can('delete', $subCategory)
              {!! Form::open(['route' => ['admin.catalog.subcategory.trash', $subCategory->id], 'method' => 'delete', 'class' => 'data-form admin-inline-form']) !!}
              <button type="submit" class="admin-action-btn confirm ajax-silent" title="{{ trans('app.trash') }}" data-toggle="tooltip"><i class="fa fa-trash-o"></i></button>
              {!! Form::close() !!}
            @endcan
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>

  @include('admin.partials.ui.card_end')

  @include('admin.partials.ui.trash_start', ['title' => trans('app.trash')])

  <table class="table table-hover admin-table table-2nd-sort">
    <thead>
      <tr>
        <th>{{ trans('app.subcategory') }}</th>
        <th>{{ trans('app.parent') }}</th>
        <th>{{ trans('app.deleted_at') }}</th>
        <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($trashes as $trash)
        <tr>
          <td>{{ $trash->name }}</td>
          <td>
            @if (optional($trash->category)->deleted_at)
              <i class="fa fa-trash-o text-muted"></i>
            @endif
            {{ optional($trash->category)->name }}
          </td>
          <td>{{ $trash->deleted_at->diffForHumans() }}</td>
          <td class="row-options admin-row-actions">
            @can('delete', $trash)
              @include('admin.partials.ui.action_btn', ['href' => route('admin.catalog.subcategory.restore', $trash->id), 'icon' => 'fa-database', 'title' => trans('app.restore')])
              {!! Form::open(['route' => ['admin.catalog.subcategory.destroy', $trash->id], 'method' => 'delete', 'class' => 'data-form admin-inline-form']) !!}
              <button type="submit" class="admin-action-btn confirm ajax-silent" title="{{ trans('app.delete_permanently') }}" data-toggle="tooltip"><i class="fa fa-trash-o"></i></button>
              {!! Form::close() !!}
            @endcan
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>

  @include('admin.partials.ui.card_end')
@endsection
