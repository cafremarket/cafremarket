@include('admin.partials.ui.card_start', [
  'title' => trans('app.shipping_label_templates'),
  'icon' => 'fa-tag',
  'actions' => '<a href="javascript:void(0)" data-link="' . route('admin.shipping.label.create') . '" class="ajax-modal-btn btn btn-new btn-flat btn-sm"><i class="fa fa-upload"></i> ' . e(trans('app.upload_template')) . '</a>',
])

<table class="table table-hover admin-table">
  <thead>
    <tr>
      <th>{{ trans('app.no') }}</th>
      <th>{{ trans('app.name') }}</th>
      <th class="admin-table__actions-col">{{ trans('app.options') }}</th>
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
            <a href="{{ route('admin.shipping.label.delete', $template) }}" class="admin-action-btn confirm ajax-silent" title="{{ trans('app.delete') }}" data-toggle="tooltip"><i class="fa fa-trash"></i></a>
          @endunless
        </td>
      </tr>
    @endforeach
  </tbody>
</table>

@include('admin.partials.ui.card_end')
