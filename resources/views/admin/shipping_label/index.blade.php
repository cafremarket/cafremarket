@extends('admin.layouts.master')

@section('page_title')
  {{ trans('app.shipping_label_templates') }}
@endsection

@section('content')
  @include('admin.partials.ui.card_start', [
    'title' => trans('app.shipping_label_templates'),
    'icon' => 'fa-tag',
    'actions' => view('admin.shipping_label._header_actions')->render(),
  ])

  <table class="table table-hover admin-table table-no-sort">
    <thead>
      <tr>
        <th width="60">{{ trans('app.no') }}</th>
        <th>{{ trans('app.name') }}</th>
        <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($shipping_label_templates as $template)
        <tr>
          <td>{{ $loop->iteration }}</td>
          <td>
            {{ $template->name }}
            @if ($template->is_default)
              <span class="label label-info">{{ trans('app.default') }}</span>
            @endif
          </td>
          <td class="row-options admin-row-actions">
            <a href="{{ route('admin.shipping.label.show', $template) }}" class="admin-action-btn" title="{{ trans('app.view_template') }}" data-toggle="tooltip"><i class="fa fa-eye"></i></a>
            <a href="{{ route('admin.shipping.label.download', $template) }}" class="admin-action-btn" title="{{ trans('app.download_template') }}" data-toggle="tooltip"><i class="fa fa-download"></i></a>
            @unless ($template->is_default || ($template->is_from_platform && auth()->user()->isMerchant()))
              <a href="{{ route('admin.shipping.label.delete', $template) }}" class="admin-action-btn confirm ajax-silent" title="{{ trans('app.delete') }}" data-toggle="tooltip"><i class="fa fa-trash-o"></i></a>
            @endunless
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>

  @include('admin.partials.ui.card_end')
@endsection
