<div class="modal-dialog modal-md">
  <div class="modal-content">
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
      {{ trans('app.response') }}
    </div>
    <div class="modal-body" style="padding: 0px;">
      <div class="col-md-4 nopadding-right" style="margin-top: 10px;">
        <div class="form-group">
          <label>{{ trans('app.customer') }}</label>
          <p class="lead">{{ optional(optional($refund->order)->customer)->getName() ?? '—' }}</p>
          @if (optional($refund->order)->customer)
            @if ($refund->order->customer->image)
              <img src="{{ get_storage_file_url(optional($refund->order->customer->image)->path, 'small') }}" class="thumbnail" alt="{{ trans('app.avatar') }}">
            @else
              <img src="{{ get_gravatar_url($refund->order->customer->email, 'small') }}" class="thumbnail" alt="{{ trans('app.avatar') }}">
            @endif
          @endif
        </div>
      </div>
      <div class="col-md-8 nopadding-left">
        <table class="table no-border">
          <tr>
            <th class="text-right">{{ trans('app.order_number') }}: </th>
            <td style="width: 60%;">
              <a href="{{ route('admin.order.order.show', $refund->order_id) }}">
                {{ optional($refund->order)->order_number ?? ('#'.$refund->order_id) }}
              </a>
            </td>
          </tr>
          <tr>
            <th class="text-right">{{ trans('app.refund_amount') }}: </th>
            <td style="width: 60%;"><span class="label label-primary">{{ get_formated_currency($refund->amount, 2, optional($refund->order)->currency_id) }}</span></td>
          </tr>
          <tr>
            <th class="text-right">{{ trans('app.order_amount') }}: </th>
            <td style="width: 60%;"><span class="label label-outline">{{ get_formated_currency(optional($refund->order)->grand_total, 2, optional($refund->order)->currency_id) }}</span></td>
          </tr>
          <tr>
            <th class="text-right">{{ trans('app.payment_status') }}: </th>
            <td style="width: 60%;">{!! optional($refund->order)->paymentStatusName() !!}</td>
          </tr>
          <tr>
            <th class="text-right">{{ trans('app.status') }}: </th>
            <td style="width: 60%;">{!! $refund->statusName() !!}</td>
          </tr>
          <tr>
            <th class="text-right">{{ trans('app.return_goods') }}: </th>
            <td style="width: 60%;">{{ get_yes_or_no($refund->return_goods) }}</td>
          </tr>
          <tr>
            <th class="text-right">{{ trans('app.order_date') }}:</th>
            <td style="width: 60%;">{{ optional(optional($refund->order)->created_at)->toDayDateTimeString() }}</td>
          </tr>
          @if ($refund->failure_reason)
            <tr>
              <th class="text-right">{{ trans('app.failure_reason') }}:</th>
              <td style="width: 60%;" class="text-danger">{{ $refund->failure_reason }}</td>
            </tr>
          @endif
          @if ($refund->admin_note)
            <tr>
              <th class="text-right">{{ trans('app.admin_note') }}:</th>
              <td style="width: 60%;">{{ $refund->admin_note }}</td>
            </tr>
          @endif
        </table>
      </div>

      <div class="spacer30"></div>

      <div class="nav-tabs-custom">
        <ul class="nav nav-tabs">
          <li class="active no-border"><a href="#tab_1" data-toggle="tab">
              {{ trans('app.description') }}
            </a></li>
        </ul>
        <div class="tab-content nopadding">
          <div class="tab-pane active" id="tab_1">
            <div class="box-body">
              {!! $refund->description ?? trans('app.description_not_available') !!}
            </div>
          </div>
        </div>
      </div>

      @if ($refund->isOpen())
        <div class="box-body">
          {!! Form::open(['route' => ['admin.refunds.markIssue', $refund], 'method' => 'post', 'id' => 'mark-issue-form']) !!}
            <div class="form-group">
              {!! Form::label('admin_note', trans('app.admin_note') . ' (' . trans('app.refund_status.issue') . ')') !!}
              {!! Form::textarea('admin_note', null, ['class' => 'form-control', 'rows' => 2, 'placeholder' => trans('app.refund_issue_note_placeholder')]) !!}
            </div>
          {!! Form::close() !!}
        </div>
      @endif
    </div>

    <div class="modal-footer">
      @if ($refund->isOpen())
        @can('approve', $refund)
          <div class="btn-group btn-group-justified" role="group" aria-label="...">
            <div class="btn-group" role="group">
              <a href="{{ route('admin.refunds.approve', $refund) }}" class="btn btn-lg btn-danger confirm ajax-silent">{{ trans('app.approve') }}</a>
            </div>
            <div class="btn-group" role="group">
              <a href="{{ route('admin.refunds.decline', $refund) }}" class="btn btn-lg btn-default confirm ajax-silent">{{ trans('app.decline') }}</a>
            </div>
            <div class="btn-group" role="group">
              <button type="submit" form="mark-issue-form" class="btn btn-lg btn-warning confirm">{{ trans('app.mark_as_issue') }}</button>
            </div>
          </div>
        @endcan
      @endif
    </div>
  </div> <!-- / .modal-content -->
</div> <!-- / .modal-dialog -->
