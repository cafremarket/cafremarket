<div class="modal-dialog modal-xs">
  <div class="modal-content">
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
      {{ trans('packages.wallet.approval') }}
    </div>

    {!! Form::open(['route' => ['admin.payout.approve', $transaction], 'method' => 'post', 'class' => 'action-form', 'data-toggle' => 'validator']) !!}
    <div class="modal-body">
      <p class="text-muted small mb-0">
        <i class="fa fa-info-circle"></i>
        {!! trans('packages.wallet.payout_sales_commission_already_deducted', ['platform' => get_platform_title()]) !!}
      </p>
    </div>
    <div class="modal-footer">
      {!! Form::submit(trans('packages.wallet.approve'), ['class' => 'btn btn-flat btn-new']) !!}
    </div>
    {!! Form::close() !!}
  </div>
</div>
