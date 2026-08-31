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
    'bodyClass' => 'responsive-table',
  ])
      <table class="table table-striped admin-table">
        <thead>
          <tr>
            <th>{{ trans('app.avatar') }}</th>
            <th width="20%">{{ trans('app.name') }}</th>
            <th>{{ trans('app.email') }}</th>
            <th width="20%">{{ trans('app.addresses') }}</th>
            <th>{{ trans('app.detail') }}</th>
            <th width="20%">{{ trans('app.reason') }}</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($failed_rows as $row)
            <tr>
              <td><img src="{{ $row['data']['avatar_link'] ?? get_placeholder_img('tiny') }}" class="img-sm"></td>
              <td>
                {{ $row['data']['full_name'] }}<br />
                <strong>{{ trans('app.nice_name') }}: </strong> {{ $row['data']['nice_name'] }}
              </td>
              <td>{{ $row['data']['email'] }}</td>
              <td>
                @if ($row['data']['primary_address_line_1'])
                  <address>
                    <strong>{{ trans('app.primary_address') }}:</strong><br />
                    {{ $row['data']['primary_address_line_1'] }}<br />
                    {!! $row['data']['primary_address_line_2'] ? $row['data']['primary_address_line_2'] . '<br/>' : '' !!}
                    {{ $row['data']['primary_address_city'] . ' ' . $row['data']['primary_address_zip_code'] }}<br />
                    {{ $row['data']['primary_address_state_name'] }}<br />
                    {{ $row['data']['primary_address_country_code'] }}<br />
                    @if ($row['data']['primary_address_phone'])
                      <strong>{{ trans('app.phone') }}: </strong> {{ $row['data']['primary_address_phone'] }}<br />
                    @endif
                  </address>
                @endif
                @if ($row['data']['billing_address_line_1'])
                  <address>
                    <strong>{{ trans('app.billing_address') }}:</strong><br />
                    {{ $row['data']['billing_address_line_1'] }}<br />
                    {!! $row['data']['billing_address_line_2'] ? $row['data']['billing_address_line_2'] . '<br/>' : '' !!}
                    {{ $row['data']['billing_address_city'] . ' ' . $row['data']['billing_address_zip_code'] }}<br />
                    {{ $row['data']['billing_address_state_name'] }}<br />
                    {{ $row['data']['billing_address_country_code'] }}<br />
                    @if ($row['data']['billing_address_phone'])
                      <strong>{{ trans('app.phone') }}: </strong> {{ $row['data']['billing_address_phone'] }}<br />
                    @endif
                  </address>
                @endif
                @if ($row['data']['shipping_address_line_1'])
                  <address>
                    <strong>{{ trans('app.shipping_address') }}:</strong><br />
                    {{ $row['data']['shipping_address_line_1'] }}<br />
                    {!! $row['data']['shipping_address_line_2'] ? $row['data']['shipping_address_line_2'] . '<br/>' : '' !!}
                    {{ $row['data']['shipping_address_city'] . ' ' . $row['data']['shipping_address_zip_code'] }}<br />
                    {{ $row['data']['shipping_address_state_name'] }}<br />
                    {{ $row['data']['shipping_address_country_code'] }}<br />
                    @if ($row['data']['shipping_address_phone'])
                      <strong>{{ trans('app.phone') }}: </strong> {{ $row['data']['shipping_address_phone'] }}<br />
                    @endif
                  </address>
                @endif
              </td>
              <td>
                <dl>
                  @if ($row['data']['sex'])
                    <dt>{{ trans('app.sex') }}: </dt>
                    <dd>{{ $row['data']['sex'] }}</dd>
                  @endif
                  @if ($row['data']['dob'])
                    <dt>{{ trans('app.dob') }}: </dt>
                    <dd>{{ $row['data']['dob'] }}</dd>
                  @endif
                  @if ($row['data']['accepts_marketing'])
                    <dt>{{ trans('app.accepts_marketing') }}: </dt>
                    <dd>{{ $row['data']['accepts_marketing'] }}</dd>
                  @endif
                  @if ($row['data']['active'])
                    <dt>{{ trans('app.active') }}: </dt>
                    <dd>{{ $row['data']['active'] }}</dd>
                  @endif
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
    'cancelUrl' => route('admin.admin.customer.index'),
    'cancelClass' => 'btn-danger',
    'cancelLabel' => trans('app.dismiss'),
    'formRoute' => 'admin.admin.customer.downloadFailedRows',
    'hiddenFields' => $hiddenFields,
    'submitLabel' => trans('app.download_failed_rows'),
    'submitClass' => 'btn btn-new btn-flat',
  ])
@endsection
