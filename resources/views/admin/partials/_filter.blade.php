@include('admin.partials.ui.card_start', [
  'title' => trans('app.filter'),
  'icon' => 'fa-filter',
  'class' => 'admin-form-section collapsed-box',
  'bodyClass' => '',
  'actions' => '<button type="button" class="btn bg-orange btn-flat btn-sm" data-widget="collapse"><i class="fa fa-plus"></i> ' . e(trans('app.filter')) . '</button>',
])
    {!! Form::open(['route' => 'admin.order.order.create', 'method' => 'get', 'id' => 'form', 'data-toggle' => 'validator']) !!}
    <div class="row">
      <div class="col-md-4 nopadding-right">
        @include('admin.partials._search_customer')
      </div>
      <div class="col-md-4 sm-padding">
        <div class="form-group">
          {!! Form::label('status_id', trans('app.form.by_status'), ['class' => 'with-help']) !!}
          {!! Form::select('status_id', $statuses, null, ['class' => 'form-control select2-normal', 'placeholder' => trans('app.placeholder.filter_by_status')]) !!}
          <div class="help-block with-errors"></div>
        </div>
      </div>
      <div class="col-md-4 nopadding-left">
        <div class="form-group">
          {!! Form::label('payment_status_id', trans('app.form.by_payments'), ['class' => 'with-help']) !!}
          {!! Form::select('payment_status_id', $payments, null, ['class' => 'form-control select2-normal', 'placeholder' => trans('app.placeholder.filter_by_status')]) !!}
          <div class="help-block with-errors"></div>
        </div>
      </div>
    </div>
    {!! Form::submit(trans('app.form.proceed'), ['class' => 'btn btn-flat btn-new']) !!}
    {!! Form::close() !!}
@include('admin.partials.ui.card_end')
