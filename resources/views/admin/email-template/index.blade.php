@extends('admin.layouts.master')

@section('page_title')
  {{ trans('app.email_templates') }}
@endsection

@section('content')
  @php
    $templateModel = \App\Models\EmailTemplate::class;
    $massActions = [
      ['url' => route('admin.utility.emailTemplate.massTrash'), 'label' => trans('app.trash'), 'icon' => 'fa-trash'],
      ['url' => route('admin.utility.emailTemplate.massDestroy'), 'label' => trans('app.delete_permanently'), 'icon' => 'fa-times'],
    ];
  @endphp

  @include('admin.partials.ui.card_start', [
    'title' => trans('app.email_templates'),
    'icon' => 'fa-envelope-o',
    'actions' => view('admin.email-template._header_actions')->render(),
  ])

  <table class="table table-hover admin-table table-no-sort">
    <thead>
      <tr>
        @include('admin.partials.ui.mass_checkbox_header', ['model' => $templateModel, 'massActions' => $massActions])
        @cannot('massDelete', $templateModel)
          {{-- no mass column --}}
        @endcannot
        <th>{{ trans('app.name') }}</th>
        <th>{{ trans('app.subject') }}</th>
        <th>{{ trans('app.sender_email') }}</th>
        <th>{{ trans('app.type') }}</th>
        @if (\Auth::user()->isFromPlatform())
          <th>{{ trans('app.template_for') }}</th>
        @endif
        <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
      </tr>
    </thead>
    <tbody id="massSelectArea">
      @foreach ($templates as $template)
        <tr>
          @can('massDelete', $templateModel)
            <td><input id="{{ $template->id }}" type="checkbox" class="massCheck"></td>
          @endcan
          <td>{{ $template->name }}</td>
          <td>{{ $template->subject }}</td>
          <td>{{ $template->sender_email }}</td>
          <td>{{ $template->type }}</td>
          @if (\Auth::user()->isFromPlatform())
            <td>{{ $template->template_for }}</td>
          @endif
          <td class="row-options admin-row-actions">
            @can('view', $template)
              <a href="javascript:void(0)" data-link="{{ route('admin.utility.emailTemplate.show', $template->id) }}" class="admin-action-btn ajax-modal-btn" title="{{ trans('app.preview') }}" data-toggle="tooltip"><i class="fa fa-eye"></i></a>
            @endcan
            @can('update', $template)
              <a href="javascript:void(0)" data-link="{{ route('admin.utility.emailTemplate.edit', $template->id) }}" class="admin-action-btn ajax-modal-btn" title="{{ trans('app.edit') }}" data-toggle="tooltip"><i class="fa fa-edit"></i></a>
            @endcan
            @can('delete', $template)
              {!! Form::open(['route' => ['admin.utility.emailTemplate.trash', $template->id], 'method' => 'delete', 'class' => 'data-form admin-inline-form']) !!}
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

  <table class="table table-hover admin-table table-no-sort">
    <thead>
      <tr>
        <th>{{ trans('app.name') }}</th>
        <th>{{ trans('app.subject') }}</th>
        <th>{{ trans('app.sender_email') }}</th>
        <th>{{ trans('app.type') }}</th>
        <th>{{ trans('app.deleted_at') }}</th>
        <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($trashes as $trash)
        <tr>
          <td>{{ $trash->name }}</td>
          <td>{{ $trash->subject }}</td>
          <td>{{ $trash->sender_email }}</td>
          <td>{{ $trash->type }}</td>
          <td>{{ $trash->deleted_at->diffForHumans() }}</td>
          <td class="row-options admin-row-actions">
            @can('delete', $trash)
              @include('admin.partials.ui.action_btn', ['href' => route('admin.utility.emailTemplate.restore', $trash->id), 'icon' => 'fa-database', 'title' => trans('app.restore')])
              {!! Form::open(['route' => ['admin.utility.emailTemplate.destroy', $trash->id], 'method' => 'delete', 'class' => 'data-form admin-inline-form']) !!}
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
