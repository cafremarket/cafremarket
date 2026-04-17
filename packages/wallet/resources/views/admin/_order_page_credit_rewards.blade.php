<div class="box">
  <div class="box-header with-border">
    <h3 class="box-title">
      <i class="fa fa-star"></i> {{ trans('packages.wallet.credit_back_rewards') }}
    </h3>
  </div> <!-- /.box-header -->
  <div class="box-body">
    <div class="row">
      <div class="col-sm-12">
        <table class="table table-striped">
          <thead>
            <tr>
              <th>{{ trans('packages.wallet.initiated_at') }}</th>
              <th>{{ trans('packages.wallet.amount') }}</th>
              <th>{{ trans('packages.wallet.status') }}</th>
              <th width="25%">{{ trans('packages.wallet.option') }}</th>
            </tr>
          </thead>
          <tbody>
            @if (!$order->customer_id)
              <tr>
                <td colspan="4">
                  <strong class="text-info">{{ trans('packages.wallet.guest_customer_cant_get_reward') }}</strong>
                </td>
              </tr>
            @elseif ($order->isPaid())
              @foreach ($order->creditRewards as $credit)
                <tr>
                  <td>
                    {{ $credit->created_at->toFormattedDateString() }}
                  </td>
                  <td>
                    {{ get_formated_currency($credit->amount, 2, config('system_settings.currency.id')) }}
                  </td>
                  <td>
                    {!! $credit->status_badge !!}
                  </td>
                  <td>
                    @unless ($credit->isReleased())
                      {!! Form::open(['route' => ['admin.wallet.reward.release', $credit], 'method' => 'post', 'class' => 'action-form confirm']) !!}
                      <button class="btn btn-flat btn-primary">
                        <i class="fa fa-check"></i> {{ trans('packages.wallet.release') }}
                      </button>
                      {!! Form::close() !!}
                    @endunless

                    {!! Form::open(['route' => ['admin.wallet.reward.delete', $credit], 'method' => 'delete', 'class' => 'data-form confirm']) !!}
                    <button class="btn btn-flat btn-danger">
                      <i class="fa fa-trash-o"></i> {{ trans('app.delete') }}
                    </button>
                    {!! Form::close() !!}
                  </td>
                </tr>
              @endforeach
            @else
              <tr>
                <td colspan="4">
                  <strong class="text-info">{{ trans('packages.wallet.order_needs_to_be_paid') }}</strong>
                </td>
              </tr>
            @endif
          </tbody>
        </table>
      </div> <!-- /.col-* -->
    </div> <!-- /.row -->
  </div> <!-- /.box-body -->
</div> <!-- /.box -->
