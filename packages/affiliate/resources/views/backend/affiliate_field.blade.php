<div class="box">
  <div class="box-header with-border">
    <h3 class="box-title">{{ trans('packages.affiliate.affiliate_marketing') }}</h3>
    <div class="box-tools pull-right">
      <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
    </div>
  </div> <!-- /.box-header -->
  <div class="box-body">
    <div class="row">
      <div class="form-group col-md-12">
        {!! Form::label('affiliate_commission_percentage', trans('packages.affiliate.affiliate_commission'), ['class' => 'with-help']) !!}
        <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="top" title="{{ trans('packages.affiliate.help_commission_field') }}"></i>
        <div class="input-group">
          {!! Form::number('affiliate_commission_percentage', null, ['class' => 'form-control', 'placeholder' => trans('packages.affiliate.placeholder_commission_field'), 'step' => '0.01', 'min' => '0', 'max' => '100']) !!}
          <div class="input-group-addon">%</div>
        </div>
        <div class="help-block with-errors">
          <small class="text-info"><em class="fa fa-info-circle"> {{ trans('packages.affiliate.when_empty_commission_will_calculated_from_default') }}</em></small>
        </div>
      </div>
    </div>
  </div>
</div>
