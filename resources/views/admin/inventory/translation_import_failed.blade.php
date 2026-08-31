@extends('admin.layouts.master')

@section('page_title')
  {{ trans('app.import_failed') }}
@endsection

@section('content')
  <div class="alert alert-danger">
    <strong><i class="icon fa fa-info-circle"></i> {{ trans('app.notice') }}</strong>
    {{ trans('messages.import_ignored') }}
  </div>

  @include('admin.partials.ui.card_start', [
    'title' => trans('app.import_failed'),
    'icon' => 'fa-cubes',
    'class' => 'admin-card--danger',
    'headerExtra' => '<small class="text-muted">(' . e(trans('app.total_number_of_rows', ['value' => count($failed_rows)])) . ')</small>',
    'bodyClass' => 'responsive-table',
  ])

  <table class="table table-striped admin-table">
    <thead>
      <tr>
        <th>{{ trans('app.slug') }}</th>
        <th>{{ trans('app.language') }}</th>
        <th>{{ trans('app.title') }}</th>
        <th>{{ trans('app.description') }}</th>
        <th>{{ trans('app.key_features') }}</th>
        <th>{{ trans('app.condition_note') }}</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($failed_rows as $row)
        <tr>
          <td>{{ $row['data']['slug'] }}</td>
          <td>{{ $row['data']['lang'] }}</td>
          <td>{{ $row['data']['title'] }}</td>
          <td>{{ $row['data']['description'] }}</td>
          <td>{{ $row['data']['key_features'] }}</td>
          <td>{{ $row['data']['condition_note'] }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>

  @include('admin.partials.ui.card_end')

  @php
    $hiddenFields = '';
    foreach ($failed_rows as $row) {
      $hiddenFields .= '<input type="hidden" name="data[]" value="' . e(serialize($row['data'])) . '">';
    }
  @endphp
  @include('admin.partials.ui.import_footer', [
    'cancelUrl' => url()->previous(),
    'cancelClass' => 'btn-danger',
    'cancelLabel' => trans('app.dismiss'),
    'rowCount' => count($failed_rows),
    'formRoute' => 'admin.stock.inventory.translate.download.failedRows',
    'hiddenFields' => $hiddenFields,
    'submitLabel' => trans('app.download_failed_rows'),
    'submitClass' => 'btn btn-new btn-flat',
  ])
@endsection
