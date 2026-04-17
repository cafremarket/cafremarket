<div class="box">
  <div class="box-header with-border">
    <h3 class="box-title">
      <i class="fa fa-handshake-o"></i> {{ trans('packages.affiliate.affiliate_commissions') }}
    </h3>
  </div> <!-- /.box-header -->
  <div class="box-body">
    <div class="row">
      <div class="col-sm-12">
        <table class="table table-striped">
          <thead>
            <tr>
              <th>{{ trans('packages.affiliate.created_at') }}</th>
              <th>{{ trans('packages.affiliate.amount') }}</th>
              <th>{{ trans('packages.affiliate.status') }}</th>
              @if (auth()->user()->isSuperAdmin())
                <th width="25%">{{ trans('packages.affiliate.option') }}</th>
              @endif
            </tr>
          </thead>
          <tbody>
            @if (is_null($commissions) || $commissions->isEmpty())
              <tr>
                <td colspan="4">
                  <strong class="text-info">{{ trans('packages.affiliate.order_has_no_affiliate_commission') }}</strong>
                </td>
              </tr>
            @else
              @foreach ($commissions as $commission)
                <tr>
                  <td>
                    {{ $commission->created_at->toFormattedDateString() }}
                  </td>
                  <td>
                    {{ get_formated_currency($commission->total_commission, 2, config('system_settings.currency.id')) }}
                  </td>
                  <td>
                    @if ($commission->isPaid())
                      <i class="fa fa-check text-success"></i> {{ trans('packages.affiliate.released') }}
                    @else
                      <i class="fa fa-hourglass text-info"></i> {{ trans('packages.affiliate.pending') }}
                    @endif
                  </td>
                  @if (auth()->user()->isSuperAdmin())
                    <td>
                      @unless ($commission->isPaid())
                        {!! Form::open(['route' => ['admin.affiliate.commission.release', $commission], 'method' => 'put', 'class' => 'action-form confirm']) !!}
                        <button class="btn btn-flat btn-primary">
                          <i class="fa fa-check"></i> {{ trans('packages.affiliate.release') }}
                        </button>
                        {!! Form::close() !!}
                      @endunless
                    </td>
                  @endif
                </tr>
              @endforeach
            @endif
          </tbody>
        </table>
      </div> <!-- /.col-* -->
    </div> <!-- /.row -->
  </div> <!-- /.box-body -->
</div> <!-- /.box -->
