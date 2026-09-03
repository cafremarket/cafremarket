@extends('admin.layouts.master')

@section('page_title')
  {{ trans('app.category_groups') }}
@endsection

@section('content')
  @php
    $groupModel = \App\Models\CategoryGroup::class;
    $massActions = [
      ['url' => route('admin.catalog.categoryGroup.massTrash'), 'label' => trans('app.trash'), 'icon' => 'fa-trash'],
      ['url' => route('admin.catalog.categoryGroup.massDestroy'), 'label' => trans('app.delete_permanently'), 'icon' => 'fa-times'],
    ];
  @endphp

  @include('admin.partials.ui.card_start', [
    'title' => trans('app.category_groups'),
    'icon' => 'fa-folder-open',
    'actions' => view('admin.category._group_header_actions')->render(),
  ])

  <table class="table table-hover admin-table table-2nd-no-sort">
    <thead>
      <tr>
        @include('admin.partials.ui.mass_checkbox_header', ['model' => $groupModel, 'massActions' => $massActions])
        @cannot('massDelete', $groupModel)
          {{-- no mass column --}}
        @endcannot
        <th>{{ trans('app.background_image') }}</th>
        <th>{{ trans('app.cover_image') }}</th>
        <th>{{ trans('app.category_group') }}</th>
        <th>{{ trans('app.order') }}</th>
        <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
      </tr>
    </thead>
    <tbody id="massSelectArea">
      @foreach ($categoryGrps as $categoryGrp)
        <tr>
          @can('massDelete', $groupModel)
            <td><input id="{{ $categoryGrp->id }}" type="checkbox" class="massCheck"></td>
          @endcan
          <td>
            @if ($categoryGrp->backgroundImage?->path && Storage::exists($categoryGrp->backgroundImage->path))
              <img src="{{ get_storage_file_url($categoryGrp->backgroundImage->path, 'small') }}" class="admin-table__banner-thumb" alt="">
            @endif
          </td>
          <td>
            @if ($categoryGrp->coverImage?->path && Storage::exists($categoryGrp->coverImage->path))
              <img src="{{ get_storage_file_url($categoryGrp->coverImage->path, 'cover_thumb') }}" class="img-sm admin-table__banner-thumb" alt="">
            @endif
          </td>
          <td>
            <i class="fa {{ $categoryGrp->icon }}"></i> {{ $categoryGrp->name }}
            @unless ($categoryGrp->active)
              <span class="label label-default">{{ trans('app.inactive') }}</span>
            @endunless
            @if ($categoryGrp->description)
              <br><span class="text-muted small">{!! Str::limit($categoryGrp->description, 220) !!}</span>
            @endif
          </td>
          <td>{{ $categoryGrp->order }}</td>
          <td class="row-options admin-row-actions">
            @can('update', $categoryGrp)
              <a href="javascript:void(0)" data-link="{{ route('admin.catalog.categoryGroup.edit', $categoryGrp->id) }}" class="admin-action-btn ajax-modal-btn" title="{{ trans('app.edit') }}" data-toggle="tooltip"><i class="fa fa-edit"></i></a>
              <a href="{{ route('admin.catalog.categoryGroup.translate.form', ['category_group' => $categoryGrp, 'language' => config('system_settings.default_language')]) }}" class="admin-action-btn" title="{{ trans('app.manage_translations') }}" data-toggle="tooltip"><i class="fa fa-language"></i></a>
            @endcan
            @can('delete', $categoryGrp)
              {!! Form::open(['route' => ['admin.catalog.categoryGroup.trash', $categoryGrp->id], 'method' => 'delete', 'class' => 'data-form admin-inline-form']) !!}
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
        <th>{{ trans('app.category_group') }}</th>
        <th>{{ trans('app.deleted_at') }}</th>
        <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($trashes as $trash)
        <tr>
          <td>
            <i class="fa {{ $trash->icon }}"></i> {{ $trash->name }}
            @if ($trash->description)
              <br><span class="text-muted small">{!! Str::limit($trash->description, 220) !!}</span>
            @endif
          </td>
          <td>{{ $trash->deleted_at->diffForHumans() }}</td>
          <td class="row-options admin-row-actions">
            @can('delete', $trash)
              @include('admin.partials.ui.action_btn', ['href' => route('admin.catalog.categoryGroup.restore', $trash->id), 'icon' => 'fa-database', 'title' => trans('app.restore')])
              {!! Form::open(['route' => ['admin.catalog.categoryGroup.destroy', $trash->id], 'method' => 'delete', 'class' => 'data-form admin-inline-form']) !!}
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
