@extends('admin.layouts.master')

@section('content')
  @include('admin.partials.ui.card_start', [
    'title' => trans('app.preview'),
    'icon' => 'fa-eye',
    'headerExtra' => '<small class="text-muted">(' . e(trans('app.total_number_of_rows', ['value' => count($rows)])) . ')</small>',
    'actions' => Gate::allows('create', \App\Models\Product::class)
      ? '<a href="javascript:void(0)" data-link="' . route('admin.catalog.product.bulk') . '" class="ajax-modal-btn btn btn-default btn-flat btn-sm">' . e(trans('app.bulk_import')) . '</a>'
      : '',
    'bodyClass' => 'responsive-table',
  ])
      <table class="table table-striped admin-table">
        <thead>
          <tr>
            <th>{{ trans('app.image') }}</th>
            <th width="20%">{{ trans('app.name') }}</th>
            <th width="25%">{{ trans('app.description') }}</th>
            <th width="20%">{{ trans('app.listing') }}</th>
            <th>{{ trans('app.category') }}</th>
            <th>{{ trans('app.tags') }}</th>
            <th>{{ trans('app.requires_shipping') }}</th>
            <th>{{ trans('app.active') }}</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($rows as $row)
            <tr>
              <td>
                <img src="{{ $row['image_link'] ?? get_placeholder_img('tiny') }}" class="img-sm">
              </td>
              <td>
                {{ $row['name'] }}<br />
                <strong>{{ trans('app.slug') }}: </strong> {{ $row['slug'] ?? Str::slug($row['name'], '-') }}
              </td>
              <td>{!! $row['description'] !!}</td>
              <td>
                <dl>
                  <dt>{{ trans('app.gtin') }}: </dt>
                  <dd>{{ $row['gtin_type'] . ' ' . $row['gtin'] }}</dd>

                  @if ($row['mpn'])
                    <dt>{{ trans('app.part_number') }}: </dt>
                    <dd>{{ $row['mpn'] }}</dd>
                  @endif

                  @if ($row['manufacturer'])
                    <dt>{{ trans('app.manufacturer') }}: </dt>
                    <dd>{{ $row['manufacturer'] }}</dd>
                  @endif

                  @if ($row['brand'])
                    <dt>{{ trans('app.brand') }}: </dt>
                    <dd>{{ $row['brand'] }}</dd>
                  @endif

                  @if ($row['model_number'])
                    <dt>{{ trans('app.model_number') }}: </dt>
                    <dd>{{ $row['model_number'] }}</dd>
                  @endif

                  @if ($row['origin_country'])
                    <dt>{{ trans('app.origin') }}: </dt>
                    <dd>{{ $row['origin_country'] }}</dd>
                  @endif

                  @if ($row['minimum_price'])
                    <dt>{{ trans('app.min_price') }}: </dt>
                    <dd>{{ get_formated_currency($row['minimum_price'], 2, config('system_settings.currency.id')) }}</dd>
                  @endif

                  @if ($row['maximum_price'])
                    <dt>{{ trans('app.max_price') }}: </dt>
                    <dd>{{ get_formated_currency($row['maximum_price'], 2, config('system_settings.currency.id')) }}</dd>
                  @endif
                </dl>
              </td>
              <td>{{ $row['categories'] }}</td>
              <td>{{ $row['tags'] }}</td>
              <td class="text-center">
                <i class="fa fa-{{ $row['requires_shipping'] == 'TRUE' ? 'check' : 'times' }} text-muted"></i>
              </td>
              <td class="text-center">
                <i class="fa fa-{{ $row['active'] == 'TRUE' ? 'check' : 'times' }} text-muted"></i>
              </td>
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
    'cancelUrl' => route('admin.catalog.product.index'),
    'rowCount' => count($rows),
    'formRoute' => 'admin.catalog.product.import',
    'hiddenFields' => $hiddenFields,
    'submitLabel' => trans('app.looks_good'),
  ])
@endsection
