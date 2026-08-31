@extends('admin.layouts.master')

@section('page_title')
  {{ trans('app.category_sub_groups') }}
@endsection

@section('content')
  @php
    $subGroupModel = \App\Models\CategorySubGroup::class;
    $massActions = [
      ['url' => route('admin.catalog.categorySubGroup.massTrash'), 'label' => trans('app.trash'), 'icon' => 'fa-trash'],
      ['url' => route('admin.catalog.categorySubGroup.massDestroy'), 'label' => trans('app.delete_permanently'), 'icon' => 'fa-times'],
    ];
    $translation_language = app()->getLocale();
  @endphp

  @include('admin.partials.ui.card_start', [
    'title' => trans('app.category_sub_groups'),
    'icon' => 'fa-folder',
    'actions' => view('admin.category._sub_group_header_actions')->render(),
  ])

  <table class="table table-hover admin-table table-2nd-no-sort">
    <thead>
      <tr>
        @include('admin.partials.ui.mass_checkbox_header', ['model' => $subGroupModel, 'massActions' => $massActions])
        @cannot('massDelete', $subGroupModel)
          {{-- no mass column --}}
        @endcannot
        <th>{{ trans('app.cover_image') }}</th>
        <th>{{ trans('app.category_sub_group') }}</th>
        <th>{{ trans('app.parent') }}</th>
        <th>{{ trans('app.categories') }}</th>
        <th>{{ trans('app.order') }}</th>
        <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
      </tr>
    </thead>
    <tbody id="massSelectArea">
      @foreach ($categorySubGrps as $categorySubGrp)
        <tr>
          @can('massDelete', $subGroupModel)
            <td><input id="{{ $categorySubGrp->id }}" type="checkbox" class="massCheck"></td>
          @endcan
          <td>
            <img src="{{ get_storage_file_url(optional($categorySubGrp->coverImage)->path, 'cover_thumb') }}" class="img-sm admin-table__banner-thumb" alt="">
          </td>
          <td>
            {{ $categorySubGrp->name }}
            @unless ($categorySubGrp->active)
              <span class="label label-default">{{ trans('app.inactive') }}</span>
            @endunless
          </td>
          <td>
            @if ($categorySubGrp->group->deleted_at)
              <i class="fa fa-trash-o text-muted"></i>
            @endif
            {{ $categorySubGrp->group->name }}
          </td>
          <td>
            <a href="{{ route('admin.catalog.categorySubGroup.showParentCategories', $categorySubGrp) }}">
              <span class="label label-default">{{ $categorySubGrp->categories_count }}</span>
            </a>
          </td>
          <td>{{ $categorySubGrp->order }}</td>
          <td class="row-options admin-row-actions">
            @can('update', $categorySubGrp)
              <a href="javascript:void(0)" data-link="{{ route('admin.catalog.categorySubGroup.edit', $categorySubGrp->id) }}" class="admin-action-btn ajax-modal-btn" title="{{ trans('app.edit') }}" data-toggle="tooltip"><i class="fa fa-edit"></i></a>
              <a href="{{ route('admin.catalog.categorySubGroup.translate.form', ['categorySubGroup' => $categorySubGrp, 'language' => $translation_language]) }}" class="admin-action-btn" title="{{ trans('app.manage_translations') }}" data-toggle="tooltip"><i class="fa fa-language"></i></a>
            @endcan
            @can('delete', $categorySubGrp)
              {!! Form::open(['route' => ['admin.catalog.categorySubGroup.trash', $categorySubGrp->id], 'method' => 'delete', 'class' => 'data-form admin-inline-form']) !!}
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
        <th>{{ trans('app.category_sub_group') }}</th>
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
            @if ($trash->group->deleted_at)
              <i class="fa fa-trash-o text-muted"></i>
            @endif
            {{ $trash->group->name }}
          </td>
          <td>{{ $trash->deleted_at->diffForHumans() }}</td>
          <td class="row-options admin-row-actions">
            @can('delete', $trash)
              @include('admin.partials.ui.action_btn', ['href' => route('admin.catalog.categorySubGroup.restore', $trash->id), 'icon' => 'fa-database', 'title' => trans('app.restore')])
              {!! Form::open(['route' => ['admin.catalog.categorySubGroup.destroy', $trash->id], 'method' => 'delete', 'class' => 'data-form admin-inline-form']) !!}
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
