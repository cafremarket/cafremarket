@extends('admin.layouts.master')

@section('page_title')
  {{ trans('app.import_failed') }}
@endsection

@section('content')
  <div class="alert alert-danger">
    <strong><i class="icon fa fa-info-circle"></i>{{ trans('app.notice') }}</strong>
    {{ trans('messages.import_ignored') }}
  </div>
  @include('admin.partials.ui.card_start', [
    'title' => trans('app.import_failed'),
    'icon' => 'fa-times-circle',
    'class' => 'admin-card--danger',
    'actions' => Gate::allows('create', \App\Models\Product::class)
      ? '<a href="javascript:void(0)" data-link="' . route('admin.stock.inventory.bulk') . '" class="ajax-modal-btn btn btn-default btn-flat btn-sm">' . e(trans('app.bulk_import')) . '</a>'
      : '',
    'bodyClass' => 'responsive-table',
  ])
      <table class="table table-striped admin-table">
        <thead>
          <tr>
            <th>{{ trans('app.image') }}</th>
            <th width="20%">{{ trans('app.title') }}</th>
            <th>{{ trans('app.quantity') }}</th>
            <th>{{ trans('app.price') }}</th>
            <th>{{ trans('app.variants') }}</th>
            <th width="25%">{{ trans('app.listing') }}</th>
            <th width="20%">{{ trans('app.reason') }}</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($failed_rows as $row)
            <tr>
              <td>
                @php
                  $image_links = explode(',', $row['data']['image_links']);
                @endphp
                <img src="{{ count($image_links) ? $image_links[0] : get_placeholder_img('small') }}" class="img-sm">
              </td>
              <td>
                {{ $row['data']['title'] }}<br />
                <strong>{{ trans('app.slug') }}: </strong> {{ $row['data']['slug'] ?? convertToSlugString($row['data']['title'], $row['data']['sku']) }}
              </td>
              <td>{{ $row['data']['stock_quantity'] }}</td>
              <td>
                @if ($row['data']['offer_price'])
                  <dl>
                    {{ get_formated_currency($row['data']['offer_price'], 2, config('system_settings.currency.id')) }}
                    <strike>{{ get_formated_currency($row['data']['price'], 2, config('system_settings.currency.id')) }}</strike>
                  </dl>
                @else
                  {{ get_formated_currency($row['data']['price'], 2, config('system_settings.currency.id')) }}
                @endif
              </td>
              <td>
                @php
                  $variants = array_filter(
                      $row,
                      function ($key) {
                          return strpos($key, 'option_name_') === 0;
                      },
                      ARRAY_FILTER_USE_KEY,
                  );
                @endphp

                <dl>
                  @foreach ($variants as $index => $variant)
                    @if ($row['data']['option_name_' . ($index + 1)] && $row['data']['option_value_' . ($index + 1)])
                      <dt>{{ $row['data']['option_name_' . ($index + 1)] }}: </dt>
                      <dd>{{ $row['data']['option_value_' . ($index + 1)] }}</dd>
                    @endif
                  @endforeach
                </dl>
              </td>
              <td>
                <dl>
                  <dt>{{ trans('app.sku') }}: </dt>
                  <dd>{{ $row['data']['sku'] }}</dd>
                  <dt>{{ trans('app.condition') }}: </dt>
                  <dd>{{ $row['data']['condition'] }}</dd>
                  <dt>{{ trans('app.gtin') }}: </dt>
                  <dd>{{ $row['data']['gtin_type'] . ' ' . $row['data']['gtin'] }}</dd>
                  @if (isset($row['data']['available_from']))
                    <dt>{{ trans('app.available_from') }}: </dt>
                    <dd>{{ $row['data']['available_from'] }}</dd>
                  @endif
                  <dt>{{ trans('app.min_order_quantity') }}: </dt>
                  <dd>{{ $row['data']['min_order_quantity'] ? $row['data']['min_order_quantity'] : 1 }}</dd>
                  <dt>{{ trans('app.free_shipping') }}: </dt>
                  <dd><i class="fa fa-{{ $row['data']['free_shipping'] == 'TRUE' ? 'check' : 'times' }} text-muted"></i></dd>
                  @if ($row['data']['offer_starts'])
                    <dt>{{ trans('app.offer_starts') }}: </dt>
                    <dd>{{ $row['data']['offer_starts'] }}</dd>
                  @endif
                  @if ($row['data']['offer_ends'])
                    <dt>{{ trans('app.offer_ends') }}: </dt>
                    <dd>{{ $row['data']['offer_ends'] }}</dd>
                  @endif
                  <dt>{{ trans('app.active') }}: </dt>
                  <dd><i class="fa fa-{{ $row['data']['active'] == 'TRUE' ? 'check' : 'times' }} text-muted"></i></dd>
                </dl>
              </td>
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
    'cancelUrl' => route('admin.stock.inventory.index'),
    'cancelClass' => 'btn-danger',
    'cancelLabel' => trans('app.dismiss'),
    'formRoute' => 'admin.stock.inventory.downloadFailedRows',
    'hiddenFields' => $hiddenFields,
    'submitLabel' => trans('app.download_failed_rows'),
    'submitClass' => 'btn btn-new btn-flat',
  ])
@endsection
