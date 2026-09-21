@extends('admin.layouts.master')

@section('page_title')
  {{ $category->name }}
@endsection

@section('content')
  @php
    $translation_language = app()->getLocale();
  @endphp

  <p class="admin-breadcrumb">
    <a href="{{ route('admin.catalog.category.index') }}">{{ trans('app.categories') }}</a>
    <span> / </span>
    <strong>{{ $category->name }}</strong>
  </p>

  @include('admin.partials.ui.card_start', [
    'title' => $category->name,
    'icon' => 'fa-tags',
    'actions' => view('admin.category._category_show_header_actions', compact('category'))->render(),
  ])

  <div class="row" style="margin-bottom: 16px;">
    <div class="col-sm-3">
      @if ($category->coverImage)
        <img src="{{ get_storage_file_url(optional($category->coverImage)->path, 'cover_thumb') }}" class="img-responsive" alt="">
      @endif
    </div>
    <div class="col-sm-9">
      @if ($category->featured)
        <span class="label label-primary">{{ trans('app.featured') }}</span>
      @endif
      @unless ($category->active)
        <span class="label label-default">{{ trans('app.inactive') }}</span>
      @endunless
      @if ($category->description)
        <p class="text-muted" style="margin-top: 8px;">{!! $category->description !!}</p>
      @endif
    </div>
  </div>

  @include('admin.partials.ui.card_end')

  @include('admin.partials.ui.card_start', [
    'title' => trans('app.subcategories'),
    'icon' => 'fa-folder',
  ])

  <table class="table table-hover admin-table table-2nd-no-sort">
    <thead>
      <tr>
        <th>{{ trans('app.cover_image') }}</th>
        <th>{{ trans('app.subcategory') }}</th>
        <th>{{ trans('app.products') }}</th>
        <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
      </tr>
    </thead>
    <tbody>
      @forelse ($subCategories as $subCategory)
        <tr>
          <td>
            <img src="{{ get_storage_file_url(optional($subCategory->coverImage)->path, 'cover_thumb') }}" class="img-sm admin-table__banner-thumb" alt="">
          </td>
          <td>
            <a href="{{ route('admin.catalog.subcategory.show', $subCategory->id) }}">
              <strong>{{ $subCategory->name }}</strong>
            </a>
            @unless ($subCategory->active)
              <span class="label label-default">{{ trans('app.inactive') }}</span>
            @endunless
          </td>
          <td>
            <a href="{{ route('admin.catalog.subcategory.show', $subCategory->id) }}">
              <span class="label label-default">{{ $subCategory->products_count }}</span>
            </a>
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
      @empty
        <tr>
          <td colspan="4" class="text-center text-muted">{{ trans('app.no_data_found') ?? 'No subcategories yet. Add one to start listing products.' }}</td>
        </tr>
      @endforelse
    </tbody>
  </table>

  @include('admin.partials.ui.card_end')

  @include('admin.partials.ui.card_start', [
    'title' => trans('app.products'),
    'icon' => 'fa-cube',
  ])

  <table class="table table-hover admin-table">
    <thead>
      <tr>
        <th>{{ trans('app.image') }}</th>
        <th>{{ trans('app.name') }}</th>
        <th>{{ trans('app.subcategory') }}</th>
        <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
      </tr>
    </thead>
    <tbody>
      @forelse ($products as $product)
        <tr>
          <td>
            @if ($product->featureImage)
              <img src="{{ get_storage_file_url(optional($product->featureImage)->path, 'tiny') }}" class="img-sm" alt="">
            @else
              <img src="{{ get_storage_file_url(optional($product->image)->path, 'tiny') }}" class="img-sm" alt="">
            @endif
          </td>
          <td>{{ $product->name }}</td>
          <td>
            @foreach ($product->subCategories as $sub)
              <span class="label label-outline">{{ $sub->name }}</span>
            @endforeach
          </td>
          <td class="row-options admin-row-actions">
            @can('update', $product)
              <a href="{{ route('admin.catalog.product.edit', $product->id) }}" class="admin-action-btn" title="{{ trans('app.edit') }}" data-toggle="tooltip"><i class="fa fa-edit"></i></a>
            @endcan
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="4" class="text-center text-muted">{{ trans('app.no_product_found') ?? 'No products in this category yet.' }}</td>
        </tr>
      @endforelse
    </tbody>
  </table>

  <div class="text-center">
    {{ $products->links() }}
  </div>

  @include('admin.partials.ui.card_end')

  @if ($trashes->count())
    @include('admin.partials.ui.trash_start', ['title' => trans('app.trash')])
    <table class="table table-hover admin-table">
      <thead>
        <tr>
          <th>{{ trans('app.subcategory') }}</th>
          <th>{{ trans('app.deleted_at') }}</th>
          <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($trashes as $trash)
          <tr>
            <td>{{ $trash->name }}</td>
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
  @endif
@endsection
