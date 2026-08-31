@extends('admin.layouts.master')

@section('content')
  <div class="alert alert-danger">
    <strong><i class="icon fa fa-info-circle"></i>{{ trans('app.notice') }}</strong>
    {{ trans('messages.import_ignored') }}
  </div>
  @include('admin.partials.ui.card_start', [
    'title' => trans('app.import_failed'),
    'icon' => 'fa-times-circle',
    'class' => 'admin-card--danger',
    'headerExtra' => '<small class="text-muted">(' . e(trans('app.total_number_of_rows', ['value' => count($failed_rows)])) . ')</small>',
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
            <th width="20%">{{ trans('app.reason') }}</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($failed_rows as $row)
            <tr>
              <td><img src="{{ $row['data']['image_link'] ?? get_placeholder_img('tiny') }}" class="img-sm"></td>
              <td>
                {{ $row['data']['name'] }}<br />
                <strong>{{ trans('app.slug') }}: </strong> {{ $row['data']['slug'] ?? Str::slug($row['data']['name'], '-') }}
              </td>
              <td>{!! $row['data']['description'] !!}</td>
              <td>
                <dl>
                  <dt>{{ trans('app.gtin') }}: </dt>
                  <dd>{{ $row['data']['gtin_type'] . ' ' . $row['data']['gtin'] }}</dd>
                  @if ($row['data']['mpn'])
                    <dt>{{ trans('app.part_number') }}: </dt>
                    <dd>{{ $row['data']['mpn'] }}</dd>
                  @endif
                  @if ($row['data']['manufacturer'])
                    <dt>{{ trans('app.manufacturer') }}: </dt>
                    <dd>{{ $row['data']['manufacturer'] }}</dd>
                  @endif
                  @if ($row['data']['brand'])
                    <dt>{{ trans('app.brand') }}: </dt>
                    <dd>{{ $row['data']['brand'] }}</dd>
                  @endif
                  @if ($row['data']['model_number'])
                    <dt>{{ trans('app.model_number') }}: </dt>
                    <dd>{{ $row['data']['model_number'] }}</dd>
                  @endif
                  @if ($row['data']['origin_country'])
                    <dt>{{ trans('app.origin') }}: </dt>
                    <dd>{{ $row['data']['origin_country'] }}</dd>
                  @endif
                  @if ($row['data']['minimum_price'])
                    <dt>{{ trans('app.min_price') }}: </dt>
                    <dd>{{ get_formated_currency($row['data']['minimum_price'], 2, config('system_settings.currency.id')) }}</dd>
                  @endif
                  @if ($row['data']['maximum_price'])
                    <dt>{{ trans('app.max_price') }}: </dt>
                    <dd>{{ get_formated_currency($row['data']['maximum_price'], 2, config('system_settings.currency.id')) }}</dd>
                  @endif
                </dl>
              </td>
              <td>{{ $row['data']['categories'] }}</td>
              <td><span class="label label-danger">{{ $row['reason'] }}</span></td>
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
    'cancelUrl' => route('admin.catalog.product.index'),
    'cancelClass' => 'btn-danger',
    'cancelLabel' => trans('app.dismiss'),
    'rowCount' => count($failed_rows),
    'formRoute' => 'admin.catalog.product.downloadFailedRows',
    'hiddenFields' => $hiddenFields,
    'submitLabel' => trans('app.download_failed_rows'),
    'submitClass' => 'btn btn-new btn-flat',
  ])
@endsection
