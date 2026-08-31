@extends('admin.layouts.master')

@section('page_title')
  {{ trans('app.preview') }}
@endsection

@section('content')
  @include('admin.partials.ui.card_start', [
    'title' => trans('app.model_translations_bulk_upload', ['model' => trans('app.model.category_sub_group')]) . ' — ' . trans('app.preview'),
    'icon' => 'fa-folder-open',
    'headerExtra' => '<small class="text-muted">(' . e(trans('app.total_number_of_rows', ['value' => count($rows)])) . ')</small>',
    'bodyClass' => 'responsive-table',
  ])

  <table class="table table-striped admin-table">
    <thead>
      <tr>
        <th>{{ trans('app.slug') }}</th>
        <th>{{ trans('app.language') }}</th>
        <th>{{ trans('app.name') }}</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($rows as $row)
        <tr>
          <td>{{ $row['slug'] }}</td>
          <td>{{ $row['lang'] }}</td>
          <td>{{ $row['name'] }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>

  @include('admin.partials.ui.card_end')

  @php
    $hiddenFields = '';
    foreach ($rows as $row) {
      $hiddenFields .= Form::hidden('data[]', serialize($row));
    }
  @endphp
  @include('admin.partials.ui.import_footer', [
    'cancelUrl' => url()->previous(),
    'rowCount' => count($rows),
    'formRoute' => 'admin.catalog.categorySubGroup.translate.bulk.import',
    'hiddenFields' => $hiddenFields,
    'submitLabel' => trans('app.looks_good'),
  ])
@endsection
