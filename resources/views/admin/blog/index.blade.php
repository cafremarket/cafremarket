@extends('admin.layouts.master')

@section('page_title')
  {{ trans('app.blogs') }}
@endsection

@section('content')
  @php
    $blogModel = \App\Models\Blog::class;
    $massActions = [
      ['url' => route('admin.utility.blog.massTrash'), 'label' => trans('app.trash'), 'icon' => 'fa-trash'],
      ['url' => route('admin.utility.blog.massDestroy'), 'label' => trans('app.delete_permanently'), 'icon' => 'fa-times'],
    ];
  @endphp

  @include('admin.partials.ui.card_start', [
    'title' => trans('app.blogs'),
    'icon' => 'fa-newspaper-o',
    'actions' => view('admin.blog._header_actions')->render(),
  ])

  <table class="table table-hover admin-table table-2nd-no-sort">
    <thead>
      <tr>
        @include('admin.partials.ui.mass_checkbox_header', ['model' => $blogModel, 'massActions' => $massActions])
        @cannot('massDelete', $blogModel)
          {{-- no mass column --}}
        @endcannot
        <th>{{ trans('app.image') }}</th>
        <th>{{ trans('app.blog_title') }}</th>
        <th>{{ trans('app.author') }}</th>
        <th><i class="fa fa-comments"></i></th>
        <th>{{ trans('app.date') }}</th>
        <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
      </tr>
    </thead>
    <tbody id="massSelectArea">
      @foreach ($blogs as $blog)
        <tr>
          @can('massDelete', $blogModel)
            <td><input id="{{ $blog->id }}" type="checkbox" class="massCheck"></td>
          @endcan
          <td>
            <img src="{{ get_storage_file_url(optional($blog->coverImage)->path, 'tiny') }}" class="img-sm" alt="">
          </td>
          <td width="60%">
            @can('update', $blog)
              <a href="javascript:void(0)" data-link="{{ route('admin.utility.blog.edit', $blog->id) }}" class="ajax-modal-btn"><strong>{!! $blog->title !!}</strong></a>
            @else
              <strong>{!! $blog->title !!}</strong>
            @endcan
            <br>
            <span class="excerpt-td text-muted">{!! $blog->excerpt !!}</span>
            @if (!$blog->status)
              <br><span class="label label-default">{{ strtoupper(trans('app.draft')) }}</span>
            @endif
          </td>
          <td>{{ $blog->author ? $blog->author->getName() : '' }}</td>
          <td>{{ $blog->comments_count }}</td>
          <td class="small">
            @if ($blog->status)
              {{ trans('app.published_at') }}<br>
              {{ optional($blog->published_at)->toFormattedDateString() }}
            @else
              {{ trans('app.updated_at') }}<br>
              {{ $blog->updated_at->toFormattedDateString() }}
            @endif
          </td>
          <td class="row-options admin-row-actions">
            @can('update', $blog)
              <a href="javascript:void(0)" data-link="{{ route('admin.utility.blog.edit', $blog->id) }}" class="admin-action-btn ajax-modal-btn" title="{{ trans('app.edit') }}" data-toggle="tooltip"><i class="fa fa-edit"></i></a>
            @endcan
            @can('delete', $blog)
              {!! Form::open(['route' => ['admin.utility.blog.trash', $blog->id], 'method' => 'delete', 'class' => 'data-form admin-inline-form']) !!}
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
        <th>{{ trans('app.blog_title') }}</th>
        <th>{{ trans('app.author') }}</th>
        <th>{{ trans('app.deleted_at') }}</th>
        <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($trashes as $trash)
        <tr>
          <td width="65%">
            <strong>{!! $trash->title !!}</strong>
            <span class="excerpt-td text-muted">{!! $trash->excerpt !!}</span>
          </td>
          <td>{{ $trash->author->getName() }}</td>
          <td>{{ $trash->deleted_at->diffForHumans() }}</td>
          <td class="row-options admin-row-actions">
            @can('delete', $trash)
              @include('admin.partials.ui.action_btn', ['href' => route('admin.utility.blog.restore', $trash->id), 'icon' => 'fa-database', 'title' => trans('app.restore')])
              {!! Form::open(['route' => ['admin.utility.blog.destroy', $trash->id], 'method' => 'delete', 'class' => 'data-form admin-inline-form']) !!}
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
