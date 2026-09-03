@extends('admin.layouts.master')

@section('page_title')
  {{ trans('app.categories') }}
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
    'title' => trans('app.categories'),
    'icon' => 'fa-tags',
    'actions' => view('admin.category._header_actions')->render(),
  ])

  <table class="table table-hover admin-table" id="all-categories-table">
    <thead>
      <tr>
        @include('admin.partials.ui.mass_checkbox_header', ['model' => $categoryModel, 'massActions' => $massActions])
        @cannot('massDelete', $categoryModel)
          <th></th>
        @endcannot
        <th>{{ trans('app.cover_image') }}</th>
        <th>{{ trans('app.feature_image') }}</th>
        <th>{{ trans('app.category_name') }}</th>
        <th>{{ trans('app.attributes') }}</th>
        <th>{{ trans('app.products') }}</th>
        <th>{{ trans('app.listings') }}</th>
        <th>{{ trans('app.order') }}</th>
        <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
      </tr>
    </thead>
    <tbody id="massSelectArea"></tbody>
  </table>

  @include('admin.partials.ui.card_end')

  @include('admin.partials.ui.trash_start', ['title' => trans('app.trash')])

  <table class="table table-hover admin-table table-option">
    <thead>
      <tr>
        <th>{{ trans('app.category_name') }}</th>
        <th>{{ trans('app.deleted_at') }}</th>
        <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($trashes as $trash)
        <tr>
          <td>
            <h5>{{ $trash->name }}</h5>
            @if ($trash->description)
              <span class="excerpt-td small">{!! Str::limit($trash->description, 150) !!}</span>
            @endif
          </td>
          <td>{{ $trash->deleted_at->diffForHumans() }}</td>
          <td class="row-options admin-row-actions">
            @can('delete', $trash)
              @include('admin.partials.ui.action_btn', ['href' => route('admin.catalog.category.restore', $trash->id), 'icon' => 'fa-database', 'title' => trans('app.restore')])
              {!! Form::open(['route' => ['admin.catalog.category.destroy', $trash->id], 'method' => 'delete', 'class' => 'data-form admin-inline-form']) !!}
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
