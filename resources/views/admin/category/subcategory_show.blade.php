@extends('admin.layouts.master')

@section('page_title')
  {{ $subCategory->name }}
@endsection

@section('content')
  <p class="admin-breadcrumb">
    <a href="{{ route('admin.catalog.category.index') }}">{{ trans('app.categories') }}</a>
    <span> / </span>
    @if ($subCategory->category)
      <a href="{{ route('admin.catalog.category.show', $subCategory->category_id) }}">{{ $subCategory->category->name }}</a>
      <span> / </span>
    @endif
    <strong>{{ $subCategory->name }}</strong>
  </p>

  @include('admin.partials.ui.card_start', [
    'title' => $subCategory->name,
    'icon' => 'fa-folder',
    'actions' => view('admin.category._subcategory_show_header_actions', compact('subCategory'))->render(),
  ])

  <p class="text-muted">
    {{ trans('app.category') }}:
    @if ($subCategory->category)
      <a href="{{ route('admin.catalog.category.show', $subCategory->category_id) }}">{{ $subCategory->category->name }}</a>
    @endif
    @unless ($subCategory->active)
      <span class="label label-default">{{ trans('app.inactive') }}</span>
    @endunless
  </p>
  @if ($subCategory->description)
    <p>{!! $subCategory->description !!}</p>
  @endif

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
          <td class="row-options admin-row-actions">
            @can('update', $product)
              <a href="{{ route('admin.catalog.product.edit', $product->id) }}" class="admin-action-btn" title="{{ trans('app.edit') }}" data-toggle="tooltip"><i class="fa fa-edit"></i></a>
            @endcan
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="3" class="text-center text-muted">{{ trans('app.no_product_found') ?? 'No products in this subcategory yet.' }}</td>
        </tr>
      @endforelse
    </tbody>
  </table>

  <div class="text-center">
    {{ $products->links() }}
  </div>

  @include('admin.partials.ui.card_end')
@endsection
