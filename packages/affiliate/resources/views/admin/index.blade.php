@extends('admin.layouts.master')

@section('content')
  <div class="box">
    <div class="box-header with-border">
      <h3 class="box-title">{{ trans('packages.affiliate.affiliates') }}</h3>
      <div class="box-tools">
        <a href="javascript:void(0)" data-link="{{ route('admin.affiliate.create') }}" class="ajax-modal-btn btn btn-new btn-flat btn-primary">
          <em class="fa fa-plus"></em> {{ trans('packages.affiliate.create_affiliate') }}
        </a>
      </div>
    </div> <!-- /.box-header -->
    <div class="box-body responsive-table">
      <table class="table table-hover" id="all-affiliates-table">
        <thead>
          <tr>
            @can('massDelete', \App\Models\Affiliate::class)
              <th class="massActionWrapper">
                <!-- Check all button -->
                <div class="btn-group ">
                  <button type="button" class="btn btn-xs btn-default checkbox-toggle">
                    <i class="fa fa-square-o" data-toggle="tooltip" data-placement="top" title="{{ trans('app.select_all') }}"></i>
                  </button>
                  <button type="button" class="btn btn-xs btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                    <span class="caret"></span>
                    <span class="sr-only">{{ trans('app.toggle_dropdown') }}</span>
                  </button>
                  <ul class="dropdown-menu" role="menu">
                    {{-- <li><a href="javascript:void(0)" data-link="{{ route('affiliate.massTrash') }}" class="massAction " data-doafter="reload"><i class="fa fa-trash"></i> {{ trans('app.trash') }}</a></li> --}}
                    <li><a href="javascript:void(0)" data-link="{{ route('affiliate.massDestroy') }}" class="massAction " data-doafter="reload"><i class="fa fa-times"></i> {{ trans('app.delete_permanently') }}</a></li>
                  </ul>
                </div>
              </th>
            @else
              <th></th>
            @endcan
            <th>{{ trans('app.full_name') }}</th>
            <th>{{ trans('app.email') }}</th>
            <th>{{ trans('app.option') }}</th>
          </tr>
        </thead>
        <tbody id="massSelectArea">
        </tbody>
      </table>
    </div> <!-- /.box-body -->
  </div> <!-- /.box -->
@endsection
