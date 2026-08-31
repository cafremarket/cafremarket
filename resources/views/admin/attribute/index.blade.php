@extends('admin.layouts.master')

@section('page_title')
  {{ trans('app.attributes') }}
@endsection

@section('content')
  @php
    $translation_language = app()->getLocale();
    $attributeModel = \App\Models\Attribute::class;
    $massActions = [
      ['url' => route('admin.catalog.attribute.massTrash'), 'label' => trans('app.trash'), 'icon' => 'fa-trash'],
      ['url' => route('admin.catalog.attribute.massDestroy'), 'label' => trans('app.delete_permanently'), 'icon' => 'fa-times'],
    ];
  @endphp

  @include('admin.partials.ui.card_start', [
    'title' => trans('app.attributes'),
    'icon' => 'fa-list',
    'actions' => view('admin.attribute._header_actions')->render(),
  ])

  <table class="table table-hover admin-table table-2nd-no-sort" id="sortable" data-action="{{ Route('admin.catalog.attribute.reorder') }}">
    <thead>
      <tr>
        @include('admin.partials.ui.mass_checkbox_header', ['model' => $attributeModel, 'massActions' => $massActions])
        @cannot('massDelete', $attributeModel)
          <th class="massActionWrapper"></th>
        @endcannot
        <th width="7px">{{ trans('app.#') }}</th>
        <th>{{ trans('app.position') }}</th>
        <th>{{ trans('app.name') }}</th>
        <th>{{ trans('app.type') }}</th>
        <th>{{ trans('app.categories') }}</th>
        <th>{{ trans('app.entities') }}</th>
        <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
      </tr>
    </thead>
    <tbody id="massSelectArea">
      @foreach ($attributes as $attribute)
        <tr id="{{ $attribute->id }}">
          @can('massDelete', $attribute)
            <td><input id="{{ $attribute->id }}" type="checkbox" class="massCheck"></td>
          @else
            <td><input type="checkbox" disabled></td>
          @endcan
          <td><i class="fa fa-arrows sort-handler admin-table__sort-handle" data-toggle="tooltip" title="{{ trans('app.move') }}"></i></td>
          <td><span class="order">{{ $attribute->order }}</span></td>
          <td>
            @can('view', $attribute)
              <a href="{{ route('admin.catalog.attribute.entities', $attribute->id) }}">{{ $attribute->name }}</a>
            @else
              {{ $attribute->name }}
            @endcan
          </td>
          <td>{{ $attribute->attributeType->type }}</td>
          <td><span class="label label-info">{{ $attribute->categories_count }}</span></td>
          <td><span class="label label-default">{{ $attribute->attribute_values_count }}</span></td>
          <td class="row-options admin-row-actions">
            @can('view', $attribute)
              <a href="{{ route('admin.catalog.attribute.entities', $attribute->id) }}" class="admin-action-btn" title="{{ trans('app.entities') }}" data-toggle="tooltip"><i class="fa fa-plus"></i></a>
            @endcan
            @can('update', $attribute)
              <a href="javascript:void(0)" data-link="{{ route('admin.catalog.attribute.edit', $attribute->id) }}" class="admin-action-btn ajax-modal-btn" title="{{ trans('app.edit') }}" data-toggle="tooltip"><i class="fa fa-edit"></i></a>
              <a href="{{ route('admin.catalog.attribute.translate.form', ['attribute' => $attribute, 'language' => $translation_language]) }}" class="admin-action-btn" title="{{ trans('app.manage_translations') }}" data-toggle="tooltip"><em class="fa fa-language"></em></a>
            @endcan
            @can('delete', $attribute)
              {!! Form::open(['route' => ['admin.catalog.attribute.trash', $attribute->id], 'method' => 'delete', 'class' => 'data-form admin-inline-form']) !!}
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

  <table class="table table-hover admin-table table-option">
    <thead>
      <tr>
        <th>{{ trans('app.name') }}</th>
        <th>{{ trans('app.type') }}</th>
        <th>{{ trans('app.deleted_at') }}</th>
        <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($trashes as $trash)
        <tr>
          <td>{{ $trash->name }}</td>
          <td>{{ $trash->attributeType->type }}</td>
          <td>{{ $trash->deleted_at->diffForHumans() }}</td>
          <td class="row-options admin-row-actions">
            @can('delete', $trash)
              @include('admin.partials.ui.action_btn', ['href' => route('admin.catalog.attribute.restore', $trash->id), 'icon' => 'fa-database', 'title' => trans('app.restore')])
              {!! Form::open(['route' => ['admin.catalog.attribute.destroy', $trash->id], 'method' => 'delete', 'class' => 'data-form admin-inline-form']) !!}
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

@section('page-script')
  @include('plugins.drag-n-drop')
@endsection
