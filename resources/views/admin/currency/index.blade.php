@extends('admin.layouts.master')

@section('page_title')
  {{ trans('app.currencies') }}
@endsection

@section('content')
  @php
    $currencyModel = \App\Models\Currency::class;
    $massActions = [
      ['url' => route('admin.setting.currency.massDestroy'), 'label' => trans('app.delete_permanently'), 'icon' => 'fa-times'],
    ];
  @endphp

  @include('admin.partials.ui.card_start', [
    'title' => trans('app.currencies'),
    'icon' => 'fa-money',
    'actions' => view('admin.currency._header_actions')->render(),
  ])

  <table class="table table-hover admin-table table-2nd-no-sort">
    <thead>
      <tr>
        @include('admin.partials.ui.mass_checkbox_header', ['model' => $currencyModel, 'massActions' => $massActions])
        @cannot('massDelete', $currencyModel)
          {{-- no mass column --}}
        @endcannot
        <th>{{ trans('app.iso_code') }}</th>
        <th>{{ trans('app.name') }}</th>
        <th>{{ trans('app.symbol') }}</th>
        <th>{{ trans('app.subunit') }}</th>
        <th>{{ trans('app.decimal_mark') }}</th>
        <th>{{ trans('app.thousands_separator') }}</th>
        @if (is_incevio_package_loaded('dynamic-currency'))
          <th>{{ trans('packages.dynamic-currency.exchange_rate') }} @include('partials._addon_badge')</th>
        @endif
        <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
      </tr>
    </thead>
    <tbody id="massSelectArea">
      @foreach ($currencies as $currency)
        <tr>
          @can('massDelete', $currencyModel)
            <td><input id="{{ $currency->id }}" type="checkbox" class="massCheck"></td>
          @endcan
          <td>{{ $currency->iso_code }}</td>
          <td>
            {{ $currency->name }}
            @if ($currency->active)
              <span class="label label-primary">{{ trans('app.active') }}</span>
            @endif
          </td>
          <td>{{ $currency->symbol }}</td>
          <td>{{ $currency->subunit }}</td>
          <td><span class="label label-default">{{ $currency->decimal_mark }}</span></td>
          <td><span class="label label-default">{{ $currency->thousands_separator }}</span></td>
          @if (is_incevio_package_loaded('dynamic-currency'))
            <td><span class="label label-info">{{ get_formated_decimal($currency->exchange_rate, true, 3) }}</span></td>
          @endif
          <td class="row-options admin-row-actions">
            @can('update', $currency)
              <a href="javascript:void(0)" data-link="{{ route('admin.setting.currency.edit', $currency->id) }}" class="admin-action-btn ajax-modal-btn" title="{{ trans('app.edit') }}" data-toggle="tooltip"><i class="fa fa-edit"></i></a>
            @endcan
            @can('delete', $currency)
              {!! Form::open(['route' => ['admin.setting.currency.destroy', $currency->id], 'method' => 'delete', 'class' => 'data-form admin-inline-form']) !!}
              <button type="submit" class="admin-action-btn confirm ajax-silent" title="{{ trans('app.delete') }}" data-toggle="tooltip"><i class="fa fa-trash-o"></i></button>
              {!! Form::close() !!}
            @endcan
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>

  @include('admin.partials.ui.card_end')
@endsection
