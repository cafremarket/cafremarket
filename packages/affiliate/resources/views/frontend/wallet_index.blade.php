@extends('affiliate::backend.master_layout')
@section('content')
  <!-- Info boxes -->
  <div class="row dashboard-total vendor-wallet">
    <div class="col-md-3 stretch-card grid-margin">
      <div class="card bg-gradient-danger card-img-holder text-white">
        <div class="card-body">
          <img src="/images/circle.svg" class="card-img-absolute" alt="circle-image">
          <h4 class="font-weight-normal mb-3">{{ trans('packages.wallet.balance') }}
          </h4>
          <h2 class="mb-5">{{ get_formated_currency(auth()->guard('affiliate')->user()->balance, 2, config('system_settings.currency.id')) }}</h2>
        </div>
      </div>
    </div>

    <div class="col-md-3 stretch-card grid-margin">
      <div class="card bg-gradient-info card-img-holder text-white">
        <div class="card-body">
          <img src="/images/circle.svg" class="card-img-absolute" alt="circle-image">
          <h4 class="font-weight-normal mb-3">{{ trans('packages.affiliate.pending_commission') }}
          </h4>
          <h2 class="mb-5">{{ get_formated_currency($pending_commissions, 2, config('system_settings.currency.id')) }}</h2>
        </div>
      </div>
    </div>

    <div class="col-md-3 stretch-card grid-margin">
      <div class="card bg-gradient-primary card-img-holder text-white">
        <div class="card-body">
          <img src="/images/circle.svg" class="card-img-absolute" alt="circle-image">
          <h4 class="font-weight-normal mb-3">{{ trans('packages.affiliate.last_commission') }}
          </h4>
          <h2 class="mb-5">{{ get_formated_currency($wallet->transactions->where('type', 'deposit')->where('approved', 1)->first()->amount ?? 0, 2, config('system_settings.currency.id')) }}</h2>
        </div>
      </div>
    </div>

    <div class="col-md-3 stretch-card grid-margin">
      <div class="card bg-gradient-success card-img-holder text-white">
        <div class="card-body">
          <img src="/images/circle.svg" class="card-img-absolute" alt="circle-image">
          <h4 class="font-weight-normal mb-3">{{ trans('packages.wallet.last_payout') }}
          </h4>
          <h2 class="mb-5">{{ get_formated_currency($wallet->transactions->where('type', 'withdraw')->where('approved', 1)->first()->amount ?? 0, 2, config('system_settings.currency.id')) }}</h2>
        </div>
      </div>
    </div>
  </div>
  <div class="box admin-card">
    <div class="box-header with-border admin-card__header">
      <h3 class="box-title admin-card__title">{{ trans('packages.wallet.transactions') }}</h3>
      <div class="box-tools pull-right admin-card__actions">
        <a href="javascript:void(0)" data-link="{{ route('affiliate.wallet.withdrawal') }}" class="ajax-modal-btn btn btn-new btn-flat">
          <i class="fa fa-plus"></i> {{ trans('packages.wallet.payout_request') }}
        </a>
      </div>
    </div> <!-- /.box-header -->
    <div class="box-body admin-card__body">
      @if (!empty($wallet->pay_to))
        <p class="text-muted small">
          <i class="fa fa-info-circle"></i>
          {{ trans('packages.wallet.payout_saved_instruction') }}: {{ $wallet->pay_to }}
        </p>
      @endif

      <table class="table table-hover admin-table" id="affiliate-wallet-transactions-table" width="100%">
        <thead>
          <tr>
            <th>{{ trans('packages.wallet.date') }}</th>
            <th>{{ trans('packages.wallet.transaction_type') }}</th>
            <th>{{ trans('packages.wallet.description') }}</th>
            <th>{{ trans('packages.wallet.amount') }}</th>
            <th>{{ trans('packages.wallet.status') }}</th>
            <th>{{ trans('packages.wallet.payout_payment_proof') }}</th>
            <th>{{ trans('packages.wallet.option') }}</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($transactions as $transaction)
            <tr>
              <td data-order="{{ $transaction->updated_at?->timestamp ?? 0 }}">
                {{ $transaction->updated_at?->toFormattedDateString() }}
              </td>
              <td>{{ $transaction->type }}</td>
              <td>
                @include('wallet::partials.transaction_description_cell', ['transaction' => $transaction])
              </td>
              <td data-order="{{ $transaction->amount }}">
                {{ get_formated_currency($transaction->amount, 2, config('system_settings.currency.id')) }}
              </td>
              <td>{!! $transaction->statusName() !!}</td>
              <td>
                @include('wallet::admin.partials._payout_payment_proof', ['transaction' => $transaction])
              </td>
              <td>
                @if ($transaction->approved)
                  <a href="{{ route('wallet.transaction.invoice', $transaction) }}" class="btn btn-default btn-sm btn-flat" target="_blank">
                    <i class="fa fa-file-o"></i> {{ trans('app.invoice') }}
                  </a>
                @endif
              </td>
            </tr>
          @empty
          @endforelse
        </tbody>
      </table>
    </div> <!-- /.box-body -->
  </div> <!-- /.box -->
@endsection

@section('page-script')
  <script type="text/javascript">
    $(function() {
      var $table = $('#affiliate-wallet-transactions-table');

      if (!$table.length) {
        return;
      }

      if ($.fn.DataTable.isDataTable($table[0])) {
        $table.DataTable().destroy();
      }

      var pageLength = {{ getPaginationValue() }};

      $table.DataTable({
        order: [[0, 'desc']],
        pageLength: pageLength,
        lengthMenu: [
          [10, 25, 50, 100, -1],
          ['10 rows', '25 rows', '50 rows', '100 rows', 'Show all']
        ],
        columnDefs: [{
          orderable: false,
          targets: [5, 6]
        }],
        language: {
          info: '_START_ to _END_ of _TOTAL_ entries',
          lengthMenu: 'Show _MENU_',
          search: '',
          emptyTable: '{{ trans('packages.wallet.no_transaction_found') }}',
          paginate: {
            next: '<i class="fa fa-hand-o-right"></i>',
            previous: '<i class="fa fa-hand-o-left"></i>',
          },
        },
        dom: 'lfrtip',
      });
    });

    document.addEventListener('click', function (e) {
      var trigger = e.target.closest('.wire-proof-preview');
      if (!trigger) return;
      e.preventDefault();
      var src = trigger.getAttribute('data-src');
      if (!src) return;
      var name = trigger.getAttribute('data-name') || '{{ trans('packages.wallet.payout_payment_proof') }}';
      var html = '<div class="text-center"><p><strong>' + name + '</strong></p>' +
        '<img src="' + src + '" class="img-responsive" style="max-height:70vh;margin:0 auto;"></div>';
      if (typeof bootbox !== 'undefined') {
        bootbox.alert({ message: html, size: 'large' });
      } else {
        window.open(src, '_blank');
      }
    });
  </script>
@endsection
