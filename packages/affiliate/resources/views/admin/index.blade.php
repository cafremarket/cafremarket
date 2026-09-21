@extends('admin.layouts.master')

@section('page_title')
  {{ trans('packages.affiliate.affiliates') }}
@endsection

@section('content')
  @include('admin.partials.ui.card_start', [
    'title' => trans('packages.affiliate.manage_affiliates'),
    'icon' => 'fa-handshake-o',
    'actions' => view('affiliate::admin._header_actions')->render(),
  ])

  <table class="table table-hover admin-table" id="all-affiliates-table">
    <thead>
      <tr>
        <th class="massActionWrapper admin-table__check-col">
          <div class="btn-group admin-mass-actions">
            <button type="button" class="btn btn-xs btn-default checkbox-toggle" title="{{ trans('app.select_all') }}">
              <i class="fa fa-square-o"></i>
            </button>
            <button type="button" class="btn btn-xs btn-default dropdown-toggle" data-toggle="dropdown">
              <span class="caret"></span>
            </button>
            <ul class="dropdown-menu" role="menu">
              <li>
                <a href="javascript:void(0)" data-link="{{ route('admin.affiliate.massDestroy') }}" class="massAction" data-doafter="reload">
                  <i class="fa fa-times"></i> {{ trans('app.delete_permanently') }}
                </a>
              </li>
            </ul>
          </div>
        </th>
        <th>{{ trans('app.full_name') }}</th>
        <th>{{ trans('app.email') }}</th>
        <th>{{ trans('app.phone') }}</th>
        <th>{{ trans('app.status') }}</th>
        <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
      </tr>
    </thead>
    <tbody id="massSelectArea"></tbody>
  </table>

  @include('admin.partials.ui.card_end')
@endsection

@section('page-script')
  <script type="text/javascript">
    @include('affiliate::scripts.datatable')
  </script>
@endsection
