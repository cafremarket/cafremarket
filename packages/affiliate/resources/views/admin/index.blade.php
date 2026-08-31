@extends('admin.layouts.master')

@section('page_title')
  {{ trans('packages.affiliate.affiliates') }}
@endsection

@section('content')
  @php
    $affiliateModel = \App\Models\Affiliate::class;
    $massActions = [
      ['url' => route('affiliate.massDestroy'), 'label' => trans('app.delete_permanently'), 'icon' => 'fa-times'],
    ];
  @endphp

  @include('admin.partials.ui.card_start', [
    'title' => trans('packages.affiliate.affiliates'),
    'icon' => 'fa-handshake-o',
    'actions' => view('affiliate::admin._header_actions')->render(),
  ])

  <table class="table table-hover admin-table" id="all-affiliates-table">
    <thead>
      <tr>
        @include('admin.partials.ui.mass_checkbox_header', ['model' => $affiliateModel, 'massActions' => $massActions])
        @cannot('massDelete', $affiliateModel)
          <th></th>
        @endcannot
        <th>{{ trans('app.full_name') }}</th>
        <th>{{ trans('app.email') }}</th>
        <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
      </tr>
    </thead>
    <tbody id="massSelectArea"></tbody>
  </table>

  @include('admin.partials.ui.card_end')
@endsection
