@extends('merchant.layouts.app')

@section('page_title', 'Raise Dispute Ticket')

@section('content')
  <div class="mp-panel">
    <div class="mp-panel__head">
      <h2 style="margin:0;font-size:16px;">Raise Dispute Ticket</h2>
    </div>
    <div class="mp-panel__body">
      <p style="color:#666;margin-top:0;">
        Disputes are handled as tickets. Admin manages resolution. This is not a live chat.
      </p>

      @if ($orders->isEmpty())
        <div class="mp-alert mp-alert--danger">No eligible orders without an open dispute ticket.</div>
      @else
        @php
          $actionOrder = $selectedOrderId
            ? $orders->firstWhere('id', (int) $selectedOrderId) ?? $orders->first()
            : $orders->first();
        @endphp

        {!! Form::open([
          'route' => ['merchant.support.dispute.store', $actionOrder],
          'files' => true,
          'id' => 'merchant-dispute-form',
          'data-toggle' => 'validator',
        ]) !!}

        <div class="form-group">
          {!! Form::label('order_select', 'Order *') !!}
          <select id="order_select" class="form-control" required>
            @foreach ($orders as $order)
              <option value="{{ $order->id }}"
                      data-action="{{ route('merchant.support.dispute.store', $order) }}"
                      {{ (int) optional($actionOrder)->id === (int) $order->id ? 'selected' : '' }}>
                #{{ $order->order_number }} — {{ optional($order->customer)->getName() }}
              </option>
            @endforeach
          </select>
        </div>

        <div class="form-group">
          {!! Form::label('dispute_type_id', trans('app.dispute_type') ?? 'Dispute type') !!} *
          {!! Form::select('dispute_type_id', $types, null, ['class' => 'form-control', 'required', 'placeholder' => 'Select type']) !!}
        </div>

        <div class="form-group">
          {!! Form::label('order_received', 'Order received? *') !!}
          <div>
            <label style="margin-right:12px;">{!! Form::radio('order_received', 1, true) !!} Yes</label>
            <label>{!! Form::radio('order_received', 0, false) !!} No</label>
          </div>
        </div>

        <div class="form-group">
          {!! Form::label('refund_amount', trans('app.refund_amount') ?? 'Refund amount') !!} *
          {!! Form::number('refund_amount', null, ['class' => 'form-control', 'step' => '0.01', 'min' => 0, 'required']) !!}
        </div>

        <div class="form-group">
          {!! Form::label('description', trans('app.description') ?? 'Description') !!} *
          {!! Form::textarea('description', null, ['class' => 'form-control', 'rows' => 5, 'required', 'placeholder' => 'Describe the issue for the admin ticket…']) !!}
        </div>

        <div class="form-group">
          {!! Form::label('attachments', trans('app.attachments') ?? 'Attachments') !!}
          {!! Form::file('attachments[]', ['multiple' => true, 'class' => 'form-control']) !!}
        </div>

        <button type="submit" class="btn btn-primary">Submit Ticket</button>
        <a href="{{ route('merchant.support.dispute.index') }}" class="btn btn-default">Cancel</a>

        {!! Form::close() !!}
      @endif
    </div>
  </div>
@endsection

@section('scripts')
<script>
(function () {
  var select = document.getElementById('order_select');
  var form = document.getElementById('merchant-dispute-form');
  if (!select || !form) return;
  select.addEventListener('change', function () {
    var opt = select.options[select.selectedIndex];
    if (opt && opt.getAttribute('data-action')) {
      form.setAttribute('action', opt.getAttribute('data-action'));
    }
  });
})();
</script>
@endsection
