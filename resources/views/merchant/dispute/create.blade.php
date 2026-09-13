@extends('merchant.layouts.app')

@section('page_title', trans('app.raise_dispute'))

@section('content')
  <div class="mp-panel">
    <div class="mp-panel__head">
      <div class="mp-panel__head-text">
        <h2>{{ trans('app.raise_dispute') }}</h2>
        <p>{{ trans('app.raise_dispute_help') }}</p>
      </div>
      <a href="{{ route('merchant.support.dispute.index') }}" class="mp-btn mp-btn--outline mp-btn--sm">{{ trans('app.back') }}</a>
    </div>
    <div class="mp-panel__body">
      @if ($orders->isEmpty())
        <div class="mp-alert mp-alert--danger">{{ trans('app.no_eligible_orders_for_dispute') }}</div>
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
        ]) !!}

        <div class="mp-form-group">
          {!! Form::label('order_select', trans('app.order').' *') !!}
          <select id="order_select" class="mp-form-control" required>
            @foreach ($orders as $order)
              <option value="{{ $order->id }}"
                      data-action="{{ route('merchant.support.dispute.store', $order) }}"
                      {{ (int) optional($actionOrder)->id === (int) $order->id ? 'selected' : '' }}>
                #{{ $order->order_number }} — {{ optional($order->customer)->getName() }}
              </option>
            @endforeach
          </select>
        </div>

        <div class="mp-form-group">
          {!! Form::label('dispute_type_id', trans('app.dispute_type').' *') !!}
          {!! Form::select('dispute_type_id', $types, null, ['class' => 'mp-form-control', 'required', 'placeholder' => trans('app.select_type')]) !!}
        </div>

        <div class="mp-form-group">
          {!! Form::label('order_received', trans('app.order_received').' *') !!}
          <div class="mp-actions" style="margin-top:0;">
            <label>{!! Form::radio('order_received', 1, true) !!} {{ trans('app.yes') }}</label>
            <label>{!! Form::radio('order_received', 0, false) !!} {{ trans('app.no') }}</label>
          </div>
        </div>

        <div class="mp-form-group">
          {!! Form::label('refund_amount', trans('app.refund_amount').' *') !!}
          {!! Form::number('refund_amount', null, ['class' => 'mp-form-control', 'step' => '0.01', 'min' => 0, 'required']) !!}
        </div>

        <div class="mp-form-group">
          {!! Form::label('description', trans('app.description').' *') !!}
          {!! Form::textarea('description', null, ['class' => 'mp-form-control', 'rows' => 5, 'required', 'placeholder' => trans('app.describe_dispute_issue')]) !!}
        </div>

        <div class="mp-form-group">
          {!! Form::label('attachments', trans('app.attachments')) !!}
          {!! Form::file('attachments[]', ['multiple' => true, 'class' => 'mp-form-control']) !!}
        </div>

        <div class="mp-actions">
          <button type="submit" class="mp-btn mp-btn--primary">{{ trans('app.submit_ticket') }}</button>
          <a href="{{ route('merchant.support.dispute.index') }}" class="mp-btn mp-btn--outline">{{ trans('app.cancel') }}</a>
        </div>

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
