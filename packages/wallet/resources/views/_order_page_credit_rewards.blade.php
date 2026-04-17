<section id="order-detail-section" name="order-detail-section" class="account-section">
  <div class="container">
    <div class="row">
      <div class="col-md-12 p-0">
        <h4 class="title mb-4">
          @lang('packages.wallet.credit_back_rewards')
        </h4>

        <div class="table-responsive">
          <table class="table border" id="buyer-order-table" name="buyer-order-table">
            <tbody>
              <tr class="buyer-payment-info-head bg-light">
                <th>{{ trans('packages.wallet.initiated_at') }}</th>
                <th>{{ trans('packages.wallet.amount') }}</th>
                <th>{{ trans('packages.wallet.status') }}</th>
              </tr>
              @if ($order->isPaid())
                @foreach ($order->creditRewards as $credit)
                  <tr class="order-body">
                    <td>
                      {{ $credit->created_at->toFormattedDateString() }}
                    </td>
                    <td>
                      {{ get_formated_currency($credit->amount, 2, config('system_settings.currency.id')) }}
                    </td>
                    <td>
                      {!! $credit->status_badge !!}
                    </td>
                  </tr> <!-- /.order-body -->
                @endforeach
              @else
                <tr class="order-body">
                  <td colspan="3">
                    <strong class="text-info">{{ trans('packages.wallet.order_needs_to_be_paid') }}</strong>
                  </td>
                </tr> <!-- /.order-body -->
              @endif
            </tbody>
          </table>
        </div>
      </div><!-- /.col-md-12 -->
    </div><!-- /.row -->
  </div><!-- /.container -->
</section>
