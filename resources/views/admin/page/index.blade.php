@extends('admin.layouts.master')

@section('page_title')
  {{ trans('app.pages') }}
@endsection

@section('content')
  @php
    $pageModel = \App\Models\Page::class;
    $massActions = [
      ['url' => route('admin.utility.page.massTrash'), 'label' => trans('app.trash'), 'icon' => 'fa-trash'],
      ['url' => route('admin.utility.page.massDestroy'), 'label' => trans('app.delete_permanently'), 'icon' => 'fa-times'],
    ];
  @endphp

  @include('admin.partials.ui.card_start', [
    'title' => trans('app.pages'),
    'icon' => 'fa-file-text',
    'actions' => view('admin.page._header_actions')->render(),
  ])

  <table class="table table-hover admin-table table-2nd-no-sort">
    <thead>
      <tr>
        @include('admin.partials.ui.mass_checkbox_header', ['model' => $pageModel, 'massActions' => $massActions])
        @cannot('massDelete', $pageModel)
          {{-- no mass column --}}
        @endcannot
        <th>{{ trans('app.image') }}</th>
        <th>{{ trans('app.page_title') }}</th>
        <th>{{ trans('app.visibility') }}</th>
        <th>{{ trans('app.view_position') }}</th>
        <th>{{ trans('app.author') }}</th>
        <th>{{ trans('app.date') }}</th>
        <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
      </tr>
    </thead>
    <tbody id="massSelectArea">
      @foreach ($pages as $page)
        <tr>
          @can('massDelete', $pageModel)
            <td><input id="{{ $page->id }}" type="checkbox" class="massCheck"></td>
          @endcan
          <td>
            <img src="{{ get_storage_file_url(optional($page->coverImage)->path, 'cover_thumb') }}" class="img-sm admin-table__banner-thumb" alt="">
          </td>
          <td width="45%">
            @can('update', $page)
              <a href="javascript:void(0)" data-link="{{ route('admin.utility.page.edit', $page) }}" class="ajax-modal-btn"><strong>{!! $page->title !!}</strong></a>
            @else
              <strong>{!! $page->title !!}</strong>
            @endcan
            @if (is_null($page->published_at))
              <span class="label label-default">{{ strtoupper(trans('app.draft')) }}</span>
            @endif
          </td>
          <td>{!! $page->visibilityName() !!}</td>
          <td>{!! $page->viewPosition() !!}</td>
          <td>{{ $page->author->getName() }}</td>
          <td class="small">
            @if ($page->published_at)
              @if (\Carbon\Carbon::now() < $page->published_at)
                {{ trans('app.schedule_published_at') }}<br>
                {{ optional($page->published_at)->toDayDateTimeString() }}
              @else
                {{ trans('app.published_at') }}<br>
                {{ optional($page->published_at)->toFormattedDateString() }}
              @endif
            @else
              {{ trans('app.updated_at') }}<br>
              {{ $page->updated_at->toFormattedDateString() }}
            @endif
          </td>
          <td class="row-options admin-row-actions">
            @can('view', $page)
              <a href="{{ route('page.open', $page->slug) }}" target="_blank" class="admin-action-btn" title="{{ trans('app.go_to_page') }}" data-toggle="tooltip"><i class="fa fa-external-link"></i></a>
            @endcan
            @can('update', $page)
              <a href="javascript:void(0)" data-link="{{ route('admin.utility.page.edit', $page) }}" class="admin-action-btn ajax-modal-btn" title="{{ trans('app.edit') }}" data-toggle="tooltip"><i class="fa fa-edit"></i></a>
            @endcan
            @can('delete', $page)
              @if (in_array($page->id, config('system.freeze.pages')))
                <i class="fa fa-bell-o text-muted" data-toggle="tooltip" title="{{ trans('messages.freezed_model') }}"></i>
              @else
                {!! Form::open(['route' => ['admin.utility.page.trash', $page], 'method' => 'delete', 'class' => 'data-form admin-inline-form']) !!}
                <button type="submit" class="admin-action-btn confirm ajax-silent" title="{{ trans('app.trash') }}" data-toggle="tooltip"><i class="fa fa-trash-o"></i></button>
                {!! Form::close() !!}
              @endif
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
        <th>{{ trans('app.page_title') }}</th>
        <th>{{ trans('app.visibility') }}</th>
        <th>{{ trans('app.author') }}</th>
        <th>{{ trans('app.deleted_at') }}</th>
        <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($trashes as $trash)
        <tr>
          <td width="50%"><strong>{!! $trash->title !!}</strong></td>
          <td>{!! $trash->visibilityName() !!}</td>
          <td>{{ $trash->author->getName() }}</td>
          <td>{{ $trash->deleted_at->diffForHumans() }}</td>
          <td class="row-options admin-row-actions">
            @can('delete', $trash)
              @include('admin.partials.ui.action_btn', ['href' => route('admin.utility.page.restore', $trash), 'icon' => 'fa-database', 'title' => trans('app.restore')])
              {!! Form::open(['route' => ['admin.utility.page.destroy', $trash], 'method' => 'delete', 'class' => 'data-form admin-inline-form']) !!}
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
