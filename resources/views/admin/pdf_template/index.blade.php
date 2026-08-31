@extends('admin.layouts.master')

@section('page_title')
  {{ trans('app.pdf_templates') }}
@endsection

@section('content')
  @include('admin.partials.ui.card_start', [
    'title' => trans('app.pdf_templates'),
    'icon' => 'fa-file-pdf-o',
    'actions' => view('admin.pdf_template._header_actions')->render(),
  ])

  <table class="table table-hover admin-table table-2nd-no-sort">
    <thead>
      <tr>
        <th>{{ trans('app.name') }}</th>
        <th>{{ trans('app.type') }}</th>
        <th>{{ trans('app.updated_at') }}</th>
        <th>{{ trans('app.active') }}</th>
        <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($templates as $template)
        <tr>
          <td>
            {{ $template->name }}
            @if ($template->is_default)
              <span class="label label-info">{{ trans('app.default') }}</span>
            @endif
          </td>
          <td>{{ $template->type }}</td>
          <td>{{ $template->updated_at->toFormattedDateString() }}</td>
          <td>
            <span class="label label-{{ $template->active ? 'primary' : 'default' }}">
              {{ $template->active ? trans('app.active') : trans('app.inactive') }}
            </span>
          </td>
          <td class="row-options admin-row-actions">
            <a href="{{ route('admin.utility.pdfTemplate.show', $template) }}" class="admin-action-btn" title="{{ trans('app.view_template') }}" data-toggle="tooltip"><i class="fa fa-eye"></i></a>
            <a href="{{ route('admin.utility.pdfTemplate.download', $template) }}" class="admin-action-btn" title="{{ trans('app.download_template') }}" data-toggle="tooltip"><i class="fa fa-download"></i></a>
            <a href="javascript:void(0)" data-link="{{ route('admin.utility.pdfTemplate.edit', $template) }}" class="admin-action-btn ajax-modal-btn" title="{{ trans('app.edit') }}" data-toggle="tooltip"><i class="fa fa-edit"></i></a>
            @if ($template->is_default)
              <i class="fa fa-bell-o text-muted" data-toggle="tooltip" title="{{ trans('messages.freezed_model') }}"></i>
            @else
              {!! Form::open(['route' => ['admin.utility.pdfTemplate.destroy', $template], 'method' => 'delete', 'class' => 'data-form admin-inline-form']) !!}
              <button type="submit" class="admin-action-btn confirm ajax-silent" title="{{ trans('app.delete') }}" data-toggle="tooltip"><i class="fa fa-trash-o"></i></button>
              {!! Form::close() !!}
            @endif
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>

  @include('admin.partials.ui.card_end')
@endsection
