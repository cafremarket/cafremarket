@extends('admin.layouts.master')

@section('page_title')
  {{ trans('app.products') }}
@endsection

@section('content')
  @php
    $productModel = \App\Models\Product::class;
    $massActions = [
      ['url' => route('admin.catalog.product.massTrash'), 'label' => trans('app.trash'), 'icon' => 'fa-trash'],
      ['url' => route('admin.catalog.product.massDestroy'), 'label' => trans('app.delete_permanently'), 'icon' => 'fa-times'],
    ];
  @endphp

  @include('admin.partials.ui.card_start', [
    'title' => trans('app.products'),
    'icon' => 'fa-cube',
    'actions' => view('admin.product._header_actions')->render(),
  ])

  <table class="table table-hover admin-table" id="all-product-table">
    <thead>
      <tr>
        @include('admin.partials.ui.mass_checkbox_header', ['model' => $productModel, 'massActions' => $massActions])
        @cannot('massDelete', $productModel)
          <th></th>
        @endcannot
        <th>{{ trans('app.image') }}</th>
        <th>{{ trans('app.name') }}</th>
        <th>{{ trans('app.type') }}</th>
        <th>{{ trans('app.gtin') }}</th>
        <th width="20%">{{ trans('app.category') }}</th>
        <th>{{ trans('app.listing') }}</th>
        @if (Auth::user()->isFromPlatform())
          <th width="15%">{{ trans('app.added_by') }}</th>
        @else
          <th></th>
        @endif
        <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
      </tr>
    </thead>
    <tbody id="massSelectArea"></tbody>
  </table>

  @include('admin.partials.ui.card_end')

  @include('admin.partials.ui.trash_start', ['title' => trans('app.trash')])

  <table class="table table-hover admin-table table-2nd-sort">
    <thead>
      <tr>
        <th>{{ trans('app.image') }}</th>
        <th>{{ trans('app.name') }}</th>
        <th>{{ trans('app.type') }}</th>
        <th>{{ trans('app.model_number') }}</th>
        <th>{{ trans('app.category') }}</th>
        <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($trashes as $trash)
        <tr>
          <td>
            @if ($trash->featureImage)
              <img src="{{ get_storage_file_url(optional($trash->featureImage)->path, 'tiny') }}" class="img-sm" alt="{{ trans('app.featured_image') }}">
            @else
              <img src="{{ get_storage_file_url(optional($trash->image)->path, 'tiny') }}" class="img-sm" alt="{{ trans('app.image') }}">
            @endif
          </td>
          <td>{{ $trash->name }}</td>
          <td>{{ $trash->type }}</td>
          <td>{{ $trash->model_number }}</td>
          <td>
            @foreach ($trash->categories as $category)
              <span class="label label-outline">{{ $category->name }}</span>
            @endforeach
          </td>
          <td class="row-options admin-row-actions">
            @can('delete', $trash)
              @include('admin.partials.ui.action_btn', ['href' => route('admin.catalog.product.restore', $trash->id), 'icon' => 'fa-database', 'title' => trans('app.restore')])
              {!! Form::open(['route' => ['admin.catalog.product.destroy', $trash->id], 'method' => 'delete', 'class' => 'data-form admin-inline-form']) !!}
              <button type="submit" class="admin-action-btn confirm ajax-silent" title="{{ trans('app.delete_permanently') }}" data-toggle="tooltip">
                <i class="fa fa-trash-o"></i>
              </button>
              {!! Form::close() !!}
            @endcan
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>

  @include('admin.partials.ui.card_end')
@endsection
